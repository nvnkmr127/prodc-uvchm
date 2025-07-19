<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    // ... index, store, edit, update, destroy methods remain the same ...
    public function index(Request $request)
    {
        $query = Batch::with('course')->withCount('students');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $batches = $query->latest()->get();
        $courses = Course::orderBy('name')->get();
        
        return view('admin.batches.index', compact('batches', 'courses'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
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
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

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
     * Show the page to manually manage students in a specific batch.
     */
    public function manageStudents(Batch $batch)
    {
        // Get students already in this batch
        $studentsInBatch = Student::where('batch_id', $batch->id)->orderBy('name')->get();
        
        // Get students that are not assigned to ANY batch
        $unassignedStudents = Student::whereNull('batch_id')->orderBy('name')->get();

        return view('admin.batches.manage_students', compact('batch', 'studentsInBatch', 'unassignedStudents'));
    }

    /**
     * NEW: Syncs the students for a batch. Handles both adding and removing.
     */
    public function syncStudents(Request $request, Batch $batch)
    {
        // Get the list of student IDs that should be in the batch from the form.
        // If the list is empty, default to an empty array.
        $assignedStudentIds = $request->input('assigned_student_ids', []);

        DB::transaction(function () use ($batch, $assignedStudentIds) {
            // 1. Remove students who are no longer in the assigned list.
            // Find all students currently in this batch but NOT in the submitted list.
            Student::where('batch_id', $batch->id)
                   ->whereNotIn('id', $assignedStudentIds)
                   ->update(['batch_id' => null]);

            // 2. Add or update students who are in the assigned list.
            // This handles both adding new students and keeping existing ones.
            Student::whereIn('id', $assignedStudentIds)
                   ->update(['batch_id' => $batch->id]);
        });

        return redirect()->route('admin.batches.manageStudents', $batch)
                         ->with('success', 'Student list for the batch has been updated successfully.');
    }
    
    /**
     * Mark all active students in a batch as 'graduated'.
     */
    public function graduate(Batch $batch)
    {
        $studentCount = Student::where('batch_id', $batch->id)
                               ->where('status', 'active')
                               ->update(['status' => 'graduated']);

        return redirect()->route('admin.batches.index')
                         ->with('success', $studentCount . ' students from ' . $batch->name . ' have been marked as graduated.');
    }
}
