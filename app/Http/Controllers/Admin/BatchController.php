<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student; // Added for attendance check
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        // [FIX] Uses 'HasAcademicYear' trait implicitly now
        $query = Batch::with('course')
            ->withCount('students');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // Optional: Filter by Academic Year manually if selected in dropdown
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $batches = $query->latest()->get();

        $courses = Course::orderBy('name')->get();
        $academicYears = \App\Models\AcademicYear::orderBy('start_date', 'desc')->get();

        return view('admin.batches.index', compact('batches', 'courses', 'academicYears'));
    }

    public function store(Request $request)
    {
        // 1. Validate (Make status nullable so we can default it)
        $validatedData = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|in:active,completed,archived', // Changed to nullable
            'is_on_internship' => 'nullable',
        ]);

        // 2. Set Defaults
        // If status is missing, default to 'active'
        $validatedData['status'] = $request->status ?? 'active';

        // Handle checkbox (true if checked, false if missing)
        $validatedData['is_on_internship'] = $request->has('is_on_internship');

        // 3. Create
        Batch::create($validatedData);

        return redirect()->route('admin.batches.index')->with('success', 'Batch created successfully.');
    }

    public function edit(Batch $batch)
    {
        $courses = Course::orderBy('name')->get();

        return view('admin.batches.edit', compact('batch', 'courses'));
    }

    public function update(Request $request, Batch $batch)
    {
        $validatedData = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:active,completed,archived',
            'is_on_internship' => 'nullable',
        ]);
        // Handle checkbox for update
        $validatedData['is_on_internship'] = $request->boolean('is_on_internship');

        $batch->update($validatedData);

        return redirect()->route('admin.batches.index')->with('success', 'Batch updated successfully.');
    }

    public function destroy(Batch $batch)
    {
        if ($batch->students()->count() > 0) {
            return redirect()->route('admin.batches.index')->with('error', 'Cannot delete a batch that has students assigned to it.');
        }

        $batch->delete();

        return redirect()->route('admin.batches.index')->with('success', 'Batch deleted successfully.');
    }

    /**
     * Quick Toggle for Internship Status (AJAX)
     */
    public function toggleInternship(Batch $batch)
    {
        $newState = ! $batch->is_on_internship;
        $batch->update([
            'is_on_internship' => $newState,
            'internship_start_date' => $newState ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'is_on_internship' => $batch->is_on_internship,
            'message' => $batch->is_on_internship ? 'Batch marked as On Internship' : 'Batch marked as In College',
        ]);
    }

    public function manageStudents(Batch $batch)
    {
        $studentsInBatch = Student::where('batch_id', $batch->id)->orderBy('name')->get();
        $unassignedStudents = Student::whereNull('batch_id')->orderBy('name')->get();

        return view('admin.batches.manage_students', compact('batch', 'studentsInBatch', 'unassignedStudents'));
    }

    public function syncStudents(Request $request, Batch $batch)
    {
        $assignedStudentIds = $request->input('assigned_student_ids', []);

        DB::transaction(function () use ($batch, $assignedStudentIds) {
            Student::where('batch_id', $batch->id)
                ->whereNotIn('id', $assignedStudentIds)
                ->update(['batch_id' => null]);

            Student::whereIn('id', $assignedStudentIds)
                ->update(['batch_id' => $batch->id]);
        });

        return redirect()->route('admin.batches.manageStudents', $batch)
            ->with('success', 'Student list for the batch has been updated successfully.');
    }

    public function getPracticalGroups(Batch $batch)
    {
        try {
            $practicalGroups = $batch->practicalGroups()
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json($practicalGroups);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load practical groups'], 500);
        }
    }

    public function graduate(Batch $batch)
    {
        $studentCount = Student::where('batch_id', $batch->id)
            ->where('status', 'active')
            ->update(['status' => 'graduated']);

        return redirect()->route('admin.batches.index')
            ->with('success', $studentCount.' students from '.$batch->name.' have been marked as graduated.');
    }

    public function getStudentsWithAttendance(Request $request, Batch $batch)
    {
        try {
            $date = $request->input('date', now()->format('Y-m-d'));

            $students = Student::where('batch_id', $batch->id)
                ->where('status', 'active')
                ->select('id', 'name', 'email', 'enrollment_number')
                ->orderBy('name')
                ->get();

            $existingAttendance = [];
            if ($date) {
                $attendanceRecords = Attendance::where('batch_id', $batch->id)
                    ->where('attendance_date', $date)
                    ->get();

                foreach ($attendanceRecords as $attendance) {
                    $existingAttendance[$attendance->student_id] = $attendance->status;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'existing_attendance' => $existingAttendance,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students: '.$e->getMessage(),
            ], 500);
        }
    }
}
