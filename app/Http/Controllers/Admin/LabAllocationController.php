<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\PracticalGroup;
use App\Models\Student;
use Illuminate\Http\Request;

class LabAllocationController extends Controller
{
    public function index(Request $request)
    {
        $batches = Batch::with('course')->orderBy('name')->get();
        $selectedBatch = null;
        $practicalGroups = collect();

        if ($request->filled('batch_id')) {
            $selectedBatch = Batch::with(['practicalGroups.classroom', 'course.terms'])
                                  ->findOrFail($request->batch_id);
            
            $practicalGroups = $selectedBatch->practicalGroups()->withCount('students')->get();
        }

        return view('admin.lab_allocation.index', compact('batches', 'selectedBatch', 'practicalGroups'));
    }

    public function automate(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'course_term_id' => 'required|exists:course_terms,id',
        ]);

        $batch = Batch::find($request->batch_id);
        $term = \App\Models\CourseTerm::find($request->course_term_id);
        $labCapacity = 30;

        $labs = Classroom::where('type', 'lab')->get();
        if ($labs->isEmpty()) {
            return redirect()->back()->with('error', 'No classrooms of type "Lab" found.');
        }

        $unassignedStudents = Student::where('batch_id', $batch->id)
            ->whereDoesntHave('practicalGroups', fn ($query) => $query->where('course_term_id', $term->id))
            ->get();

        if ($unassignedStudents->isEmpty()) {
            return redirect()->route('admin.lab-allocation.index', ['batch_id' => $batch->id])->with('info', 'No unassigned students found in this batch to allocate for this term.');
        }

        $studentChunks = $unassignedStudents->chunk($labCapacity);
        $labCounter = 0;
        $report = "Automation Report for " . $batch->name . " (" . $term->name . "):\n";

        foreach ($studentChunks as $chunk) {
            if (!isset($labs[$labCounter])) {
                $report .= "Could not assign " . $chunk->count() . " students as there are not enough labs available.";
                break;
            }
            
            $lab = $labs[$labCounter];
            $groupName = $batch->name . ' - ' . $lab->name . ' Group ' . ($labCounter + 1);
            $practicalGroup = PracticalGroup::create([
                'name' => $groupName, 'batch_id' => $batch->id,
                'classroom_id' => $lab->id, 'course_term_id' => $term->id,
            ]);
            $practicalGroup->students()->attach($chunk->pluck('id'));
            $report .= "Created '" . $groupName . "' and assigned " . $chunk->count() . " students.\n";
            $labCounter++;
        }

        return redirect()->route('admin.lab-allocation.index', ['batch_id' => $batch->id])->with('success', $report);
    }
    
    /**
     * Show the page to manually manage students in a single practical group.
     */
    public function manageGroup(PracticalGroup $group)
    {
        $group->load('students', 'batch.students');
        $studentsInGroupIds = $group->students->pluck('id');

        // Find students who are in the same main batch but not in this specific practical group
        $unassignedStudents = $group->batch->students()
            ->whereNotIn('id', $studentsInGroupIds)
            ->get();
        
        return view('admin.lab_allocation.manage', [
            'group' => $group,
            'studentsInGroup' => $group->students,
            'unassignedStudents' => $unassignedStudents
        ]);
    }

    /**
     * Manually add a student to a group.
     */
    public function addStudentToGroup(Request $request, PracticalGroup $group)
    {
        $request->validate(['student_id' => 'required|exists:students,id']);
        // Prevent adding a student if the lab is full
        if ($group->students()->count() >= $group->classroom->capacity) {
            return redirect()->back()->with('error', 'This lab group is already at full capacity.');
        }
        $group->students()->attach($request->student_id);
        return redirect()->back()->with('success', 'Student added to group.');
    }

    /**
     * Manually remove a student from a group.
     */
    public function removeStudentFromGroup(PracticalGroup $group, Student $student)
    {
        $group->students()->detach($student->id);
        return redirect()->back()->with('success', 'Student removed from group.');
    }
}
