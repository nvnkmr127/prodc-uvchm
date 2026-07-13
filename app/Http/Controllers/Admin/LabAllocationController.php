<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MissingAcademicYearException;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\PracticalGroup;
use App\Models\Student;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabAllocationController extends Controller
{
    public function index(Request $request)
    {
        $batches = Batch::with('course')->orderBy('name')->get();
        $selectedBatch = null;
        $practicalGroups = collect();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        if ($request->filled('batch_id')) {
            $selectedBatch = Batch::with(['practicalGroups.classroom', 'course'])
                ->findOrFail($request->batch_id);

            $practicalGroups = $selectedBatch->practicalGroups()->withCount('students')->get();
        }

        return view('admin.lab_allocation.index', compact('batches', 'selectedBatch', 'practicalGroups', 'academicYears'));
    }

    public function automate(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'lab_capacity' => 'sometimes|integer|min:10|max:50',
        ]);

        DB::beginTransaction();

        try {
            $batch = Batch::findOrFail($request->batch_id);
            $academicYear = AcademicYear::findOrFail($request->academic_year_id);
            $labCapacity = $request->lab_capacity ?? 30;

            // Get available labs
            $labs = Classroom::where('type', 'lab')
                ->orderBy('name')
                ->get();

            if ($labs->isEmpty()) {
                return redirect()->back()->with('error', 'No active labs found. Please create lab classrooms first.');
            }

            // Find unassigned students for this academic year
            $unassignedStudents = Student::where('batch_id', $batch->id)
                ->where('status', 'active')
                ->whereDoesntHave('practicalGroups', function ($query) use ($academicYear) {
                    $query->where('academic_year_id', $academicYear->id);
                })
                ->orderBy('name')
                ->get();

            if ($unassignedStudents->isEmpty()) {
                return redirect()->route('admin.lab-allocation.index', ['batch_id' => $batch->id])
                    ->with('info', "No unassigned students found in {$batch->name} for academic year {$academicYear->name}.");
            }

            // Check if groups already exist for this batch and academic year
            $existingGroups = PracticalGroup::where('batch_id', $batch->id)
                ->where('academic_year_id', $academicYear->id)
                ->count();

            if ($existingGroups > 0 && ! $request->has('force_recreate')) {
                return redirect()->back()
                    ->with('warning', "Groups already exist for {$batch->name} in {$academicYear->name}. Use 'Force Recreate' if you want to create new groups.");
            }

            // Split students into chunks based on lab capacity
            $studentChunks = $unassignedStudents->chunk($labCapacity);
            $labCounter = 0;
            $createdGroups = [];
            $report = "🎯 Lab Allocation Report\n";
            $report .= "Batch: {$batch->name}\n";
            $report .= "Academic Year: {$academicYear->name}\n";
            $report .= "Total Students: {$unassignedStudents->count()}\n";
            $report .= "Lab Capacity: {$labCapacity} students per lab\n\n";

            foreach ($studentChunks as $chunkIndex => $chunk) {
                if (! isset($labs[$labCounter])) {
                    $report .= '⚠️ Could not assign '.$chunk->count()." students - insufficient labs available.\n";
                    break;
                }

                $lab = $labs[$labCounter];

                // Check if lab capacity allows this group
                if ($chunk->count() > $lab->capacity) {
                    $report .= "⚠️ Lab {$lab->name} capacity ({$lab->capacity}) is less than group size ({$chunk->count()}). Proceeding anyway.\n";
                }

                // Create group name
                $groupName = "{$batch->name} - {$lab->name} - Group ".($chunkIndex + 1);

                // Create practical group
                $practicalGroup = PracticalGroup::create([
                    'name' => $groupName,
                    'batch_id' => $batch->id,
                    'classroom_id' => $lab->id,
                    'academic_year_id' => $academicYear->id,
                ]);

                // Assign students to group
                $practicalGroup->students()->attach($chunk->pluck('id'));

                $createdGroups[] = $practicalGroup;
                $report .= "✅ Created '{$groupName}'\n";
                $report .= "   📍 Lab: {$lab->name} (Capacity: {$lab->capacity})\n";
                $report .= "   👥 Students: {$chunk->count()}\n";
                $report .= '   📋 Names: '.$chunk->pluck('name')->join(', ')."\n\n";

                $labCounter++;
            }

            $report .= "🎉 Allocation completed successfully!\n";
            $report .= '📊 Summary: '.count($createdGroups)." groups created using {$labCounter} labs.";

            DB::commit();

            return redirect()->route('admin.lab-allocation.index', ['batch_id' => $batch->id])
                ->with('success', $report);

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Allocation failed: '.$e->getMessage())
                ->withInput();
        }
    }

    public function destroy(PracticalGroup $practicalGroup)
    {
        try {
            $practicalGroup->delete();

            return redirect()->route('admin.lab-allocation.index')
                ->with('success', 'Practical group deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete practical group: '.$e->getMessage());
        }
    }

    /**
     * Show the page to manually manage students in a single practical group.
     */
    public function manageGroup(PracticalGroup $group)
    {
        $group->load(['students', 'batch.students', 'classroom', 'academicYear']);
        $studentsInGroupIds = $group->students->pluck('id');

        // Find students who are in the same batch but not in this specific practical group
        // and not in any other practical group for this academic year
        $unassignedStudents = $group->batch->students()
            ->where('status', 'active')
            ->whereNotIn('id', $studentsInGroupIds)
            ->whereDoesntHave('practicalGroups', function ($query) use ($group) {
                $query->where('academic_year_id', $group->academic_year_id);
            })
            ->orderBy('name')
            ->get();

        return view('admin.lab_allocation.manage', [
            'group' => $group,
            'studentsInGroup' => $group->students,
            'unassignedStudents' => $unassignedStudents,
        ]);
    }

    /**
     * Manually add a student to a group.
     */
    public function addStudentToGroup(Request $request, PracticalGroup $group)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($request->student_id);

        // Check if student is already in this group
        if ($group->students()->where('student_id', $student->id)->exists()) {
            return redirect()->back()->with('error', 'Student is already in this group.');
        }

        // Check if student is in another group for this academic year
        $existingGroup = PracticalGroup::where('academic_year_id', $group->academic_year_id)
            ->whereHas('students', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
            ->first();

        if ($existingGroup) {
            return redirect()->back()
                ->with('error', "Student is already assigned to '{$existingGroup->name}' for this academic year.");
        }

        // Check lab capacity
        $currentCount = $group->students()->count();
        if ($currentCount >= $group->classroom->capacity) {
            return redirect()->back()
                ->with('warning', "Lab is at full capacity ({$group->classroom->capacity} students). Added anyway.");
        }

        // Add student to group
        $group->students()->attach($student->id);

        return redirect()->back()
            ->with('success', "Successfully added {$student->name} to {$group->name}.");
    }

    /**
     * Manually remove a student from a group.
     */
    public function removeStudentFromGroup(PracticalGroup $group, Student $student)
    {
        if (! $group->students()->where('student_id', $student->id)->exists()) {
            return redirect()->back()->with('error', 'Student is not in this group.');
        }

        $group->students()->detach($student->id);

        return redirect()->back()
            ->with('success', "Successfully removed {$student->name} from {$group->name}.");
    }

    /**
     * Delete a practical group
     */
    public function deleteGroup(PracticalGroup $group)
    {
        $groupName = $group->name;
        $studentCount = $group->students()->count();

        // Remove all student associations first
        $group->students()->detach();

        // Delete the group
        $group->delete();

        return redirect()->back()
            ->with('success', "Successfully deleted group '{$groupName}' and unassigned {$studentCount} students.");
    }

    /**
     * Get allocation statistics for a batch
     */
    public function getStats(Batch $batch)
    {
        try {
            $currentAcademicYear = app(AcademicYearService::class)->getCurrentAcademicYear();
        } catch (MissingAcademicYearException $e) {
            $currentAcademicYear = null;
        }

        if (! $currentAcademicYear) {
            return response()->json(['error' => 'No current academic year set'], 400);
        }

        $totalStudents = $batch->students()->where('status', 'active')->count();
        $assignedStudents = Student::where('batch_id', $batch->id)
            ->where('status', 'active')
            ->whereHas('practicalGroups', function ($query) use ($currentAcademicYear) {
                $query->where('academic_year_id', $currentAcademicYear->id);
            })
            ->count();

        $groups = PracticalGroup::where('batch_id', $batch->id)
            ->where('academic_year_id', $currentAcademicYear->id)
            ->withCount('students')
            ->with('classroom')
            ->get();

        $stats = [
            'total_students' => $totalStudents,
            'assigned_students' => $assignedStudents,
            'unassigned_students' => $totalStudents - $assignedStudents,
            'total_groups' => $groups->count(),
            'groups' => $groups->map(function ($group) {
                return [
                    'name' => $group->name,
                    'lab' => $group->classroom->name,
                    'students_count' => $group->students_count,
                    'capacity' => $group->classroom->capacity,
                    'utilization' => round(($group->students_count / $group->classroom->capacity) * 100, 1),
                ];
            }),
        ];

        return response()->json($stats);
    }
}
