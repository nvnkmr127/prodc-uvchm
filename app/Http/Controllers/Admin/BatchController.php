<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
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

        // Filter by Academic Year if explicitly requested, otherwise trait handles session-based filtering
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $batches = $query->latest()->get();
        $courses = Course::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

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
            'status' => 'nullable|in:active,inactive,completed,cancelled', // Match DB enum
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

    // Edit is handled via modal in index view

    public function update(Request $request, Batch $batch)
    {
        $validatedData = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive,completed,cancelled',
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

        if ($batch->timetableEntries()->exists()) {
            return redirect()->route('admin.batches.index')->with('error', 'Cannot delete a batch that has timetables assigned to it.');
        }

        if ($batch->feeStructure()->exists()) {
            return redirect()->route('admin.batches.index')->with('error', 'Cannot delete a batch that has a fee structure defined.');
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
        // Use withoutGlobalScope to ensure we can see students even if they belong to a batch of a different year than the current session
        $studentsInBatch = Student::withoutGlobalScope('academic_year')
            ->where('batch_id', $batch->id)
            ->orderBy('name')
            ->get();

        $unassignedStudents = Student::withoutGlobalScope('academic_year')
            ->whereNull('batch_id')
            ->orderBy('name')
            ->get();

        return view('admin.batches.manage_students', compact('batch', 'studentsInBatch', 'unassignedStudents'));
    }

    public function syncStudents(Request $request, Batch $batch)
    {
        $assignedStudentIds = $request->input('assigned_student_ids', []);

        // Capacity Enforcement
        if (count($assignedStudentIds) > $batch->course->max_batch_size) {
            return redirect()->back()->with('error', 'Cannot assign more students than the maximum batch size of '.$batch->course->max_batch_size.'.');
        }

        // Course Integrity
        $invalidStudents = Student::whereIn('id', $assignedStudentIds)->where('course_id', '!=', $batch->course_id)->count();
        if ($invalidStudents > 0) {
            return redirect()->back()->with('error', 'Cannot assign students from a different course to this batch.');
        }

        DB::transaction(function () use ($batch, $assignedStudentIds) {
            $originalIds = Student::withoutGlobalScope('academic_year')
                ->where('batch_id', $batch->id)
                ->pluck('id')->toArray();

            $added = array_diff($assignedStudentIds, $originalIds);
            $removed = array_diff($originalIds, $assignedStudentIds);

            if (count($added) > 0 || count($removed) > 0) {
                \Log::info("Batch {$batch->id} ({$batch->name}) students synced. Added: ".implode(',', $added).'. Removed: '.implode(',', $removed));
            }

            // Use withoutGlobalScope to ensure updates happen correctly regardless of session state
            Student::withoutGlobalScope('academic_year')
                ->where('batch_id', $batch->id)
                ->whereNotIn('id', $assignedStudentIds)
                ->update(['batch_id' => null]);

            Student::withoutGlobalScope('academic_year')
                ->whereIn('id', $assignedStudentIds)
                ->update(['batch_id' => $batch->id]);
        });

        return redirect()->route('admin.batches.manageStudents', $batch)
            ->with('success', 'Student list for the batch has been updated successfully.');
    }

    public function graduate(Batch $batch)
    {
        // Students being graduated (active only). Eager-load fees so the
        // informational fee check below does not trigger an N+1 query.
        $studentsToGraduate = Student::withoutGlobalScope('academic_year')
            ->where('batch_id', $batch->id)
            ->where('status', 'active')
            ->with('studentFees')
            ->get();

        // Informational fee check ONLY - graduation is never blocked by fees.
        $withOutstanding = $studentsToGraduate->filter(function (Student $student) {
            return $student->studentFees->sum(function ($fee) {
                return max(0, ($fee->amount ?? 0) - ($fee->concession_amount ?? 0) - ($fee->paid_amount ?? 0));
            }) > 0;
        })->count();

        DB::transaction(function () use ($batch) {
            // 1. Push graduated people to the archive: active students -> graduated
            //    (graduated students automatically appear in the Alumni Network).
            Student::withoutGlobalScope('academic_year')
                ->where('batch_id', $batch->id)
                ->where('status', 'active')
                ->update(['status' => 'graduated']);

            // 2. Archive the batch itself now that its cohort has graduated.
            $batch->update(['status' => 'completed']);
        });

        $count = $studentsToGraduate->count();
        $message = $count.' student(s) from '.$batch->name.' have been marked as graduated, and the batch has been archived (marked completed).';

        if ($withOutstanding > 0) {
            $message .= ' Note: '.$withOutstanding.' of them still had outstanding fees at graduation.';
        }

        return redirect()->route('admin.batches.index')->with('success', $message);
    }
}
