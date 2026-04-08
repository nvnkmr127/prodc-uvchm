<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\PracticalGroup;
use App\Models\Student;
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
     * Generate PDF report for lab allocation
     */
    public function generatePdf(Request $request, ?Batch $batch = null)
    {
        $request->validate([
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'format' => 'sometimes|in:detailed,summary',
        ]);

        $academicYearId = $request->academic_year_id;
        $format = $request->format ?? 'detailed';

        // Get current academic year if none specified
        if (! $academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        $academicYear = AcademicYear::find($academicYearId);

        if (! $academicYear) {
            return redirect()->back()->with('error', 'Academic year not found.');
        }

        // If specific batch is provided, get only that batch's data
        if ($batch) {
            $batches = collect([$batch->load(['course', 'practicalGroups' => function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId)
                    ->with(['classroom', 'students' => function ($q) {
                        $q->orderBy('name');
                    }]);
            }])]);
            $reportTitle = "Lab Allocation Report - {$batch->name}";
            $filename = "lab-allocation-{$batch->name}-{$academicYear->name}";
        } else {
            // Get all batches with their practical groups
            $batches = Batch::with(['course', 'practicalGroups' => function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId)
                    ->with(['classroom', 'students' => function ($q) {
                        $q->orderBy('name');
                    }]);
            }])->whereHas('practicalGroups', function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })->orderBy('name')->get();

            $reportTitle = 'Complete Lab Allocation Report';
            $filename = "lab-allocation-all-batches-{$academicYear->name}";
        }

        // Get college information from settings
        $collegeInfo = $this->getCollegeInfo();

        // Get statistics
        $statistics = $this->generateAllocationStatistics($batches, $academicYear);

        // Get unassigned students
        $unassignedStudents = $this->getUnassignedStudents($batches, $academicYearId);

        // Generate the PDF using dompdf
        try {
            // Ensure academic year dates are properly formatted
            $academicYear->start_date = \Carbon\Carbon::parse($academicYear->start_date);
            $academicYear->end_date = \Carbon\Carbon::parse($academicYear->end_date);

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.lab_allocation.pdf', [
                'batches' => $batches,
                'academicYear' => $academicYear,
                'collegeInfo' => $collegeInfo,
                'statistics' => $statistics,
                'unassignedStudents' => $unassignedStudents,
                'reportTitle' => $reportTitle,
                'format' => $format,
                'generatedAt' => now(),
                'generatedBy' => auth()->user(),
            ]);

            $pdf->setPaper('a4', 'portrait');

            return $pdf->download($filename.'.pdf');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate PDF: '.$e->getMessage());
        }
    }

    /**
     * Get college information from settings
     */
    private function getCollegeInfo()
    {
        try {
            if (class_exists('\App\Models\Setting')) {
                $settings = \App\Models\Setting::whereIn('key', [
                    'college_name', 'college_address', 'college_phone',
                    'college_email', 'college_website', 'principal_name',
                    'college_logo', 'college_code', 'affiliation',
                ])->pluck('value', 'key');

                return [
                    'name' => $settings['college_name'] ?? 'College Name',
                    'address' => $settings['college_address'] ?? 'College Address',
                    'phone' => $settings['college_phone'] ?? 'Phone Number',
                    'email' => $settings['college_email'] ?? 'Email Address',
                    'website' => $settings['college_website'] ?? 'Website',
                    'principal' => $settings['principal_name'] ?? 'Principal Name',
                    'logo' => $settings['college_logo'] ?? null,
                    'code' => $settings['college_code'] ?? 'CODE',
                    'affiliation' => $settings['affiliation'] ?? 'Affiliation',
                ];
            }
        } catch (\Exception $e) {
            // Log error but don't fail
            \Log::warning('Could not fetch college settings: '.$e->getMessage());
        }

        // Default college info if settings not available
        return [
            'name' => config('app.name', 'College Management System'),
            'address' => 'College Address',
            'phone' => 'Phone Number',
            'email' => 'college@email.com',
            'website' => 'www.college.edu',
            'principal' => 'Principal Name',
            'logo' => null,
            'code' => 'COLLEGE',
            'affiliation' => 'Education Board',
        ];
    }

    /**
     * Generate allocation statistics
     */
    private function generateAllocationStatistics($batches, $academicYear)
    {
        $totalStudents = 0;
        $allocatedStudents = 0;
        $totalGroups = 0;
        $totalLabs = 0;
        $labUtilization = [];
        $courseBreakdown = [];

        foreach ($batches as $batch) {
            $batchStudentCount = $batch->students()->where('status', 'active')->count();
            $totalStudents += $batchStudentCount;

            $batchAllocatedCount = 0;
            foreach ($batch->practicalGroups as $group) {
                $groupStudentCount = $group->students->count();
                $batchAllocatedCount += $groupStudentCount;
                $totalGroups++;

                // Lab utilization
                $labName = $group->classroom->name;
                if (! isset($labUtilization[$labName])) {
                    $labUtilization[$labName] = [
                        'capacity' => $group->classroom->capacity,
                        'used' => 0,
                        'groups' => 0,
                    ];
                    $totalLabs++;
                }
                $labUtilization[$labName]['used'] += $groupStudentCount;
                $labUtilization[$labName]['groups']++;
            }

            $allocatedStudents += $batchAllocatedCount;

            // Course breakdown
            $courseName = $batch->course->name;
            if (! isset($courseBreakdown[$courseName])) {
                $courseBreakdown[$courseName] = [
                    'batches' => 0,
                    'total_students' => 0,
                    'allocated_students' => 0,
                    'groups' => 0,
                ];
            }
            $courseBreakdown[$courseName]['batches']++;
            $courseBreakdown[$courseName]['total_students'] += $batchStudentCount;
            $courseBreakdown[$courseName]['allocated_students'] += $batchAllocatedCount;
            $courseBreakdown[$courseName]['groups'] += $batch->practicalGroups->count();
        }

        return [
            'total_students' => $totalStudents,
            'allocated_students' => $allocatedStudents,
            'unassigned_students' => $totalStudents - $allocatedStudents,
            'allocation_percentage' => $totalStudents > 0 ? round(($allocatedStudents / $totalStudents) * 100, 1) : 0,
            'total_groups' => $totalGroups,
            'total_labs' => $totalLabs,
            'average_group_size' => $totalGroups > 0 ? round($allocatedStudents / $totalGroups, 1) : 0,
            'lab_utilization' => $labUtilization,
            'course_breakdown' => $courseBreakdown,
        ];
    }

    /**
     * Get unassigned students for the academic year
     */
    private function getUnassignedStudents($batches, $academicYearId)
    {
        $unassigned = [];

        foreach ($batches as $batch) {
            $assignedStudentIds = $batch->practicalGroups
                ->flatMap(function ($group) {
                    return $group->students->pluck('id');
                })->toArray();

            $unassignedInBatch = $batch->students()
                ->where('status', 'active')
                ->whereNotIn('id', $assignedStudentIds)
                ->orderBy('name')
                ->get();

            if ($unassignedInBatch->count() > 0) {
                $unassigned[$batch->name] = $unassignedInBatch;
            }
        }

        return $unassigned;
    }

    /**
     * Export allocation data as Excel
     */
    public function exportExcel(Request $request, ?Batch $batch = null)
    {
        // Placeholder for Excel export - implement with Laravel Excel package
        return response()->json(['message' => 'Excel export feature coming soon']);
    }

    /**
     * Generate Students-Only PDF report
     */
    public function generateStudentsPdf(Request $request, ?Batch $batch = null)
    {
        $request->validate([
            'academic_year_id' => 'sometimes|exists:academic_years,id',
        ]);

        $academicYearId = $request->academic_year_id;

        // Get current academic year if none specified
        if (! $academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        $academicYear = AcademicYear::find($academicYearId);

        if (! $academicYear) {
            return redirect()->back()->with('error', 'Academic year not found.');
        }

        // If specific batch is provided, get only that batch's data
        if ($batch) {
            $batches = collect([$batch->load(['course', 'practicalGroups' => function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId)
                    ->with(['classroom', 'students' => function ($q) {
                        $q->orderBy('name');
                    }]);
            }])]);
            $reportTitle = "Student Allocation List - {$batch->name}";
            $filename = "students-allocation-{$batch->name}-{$academicYear->name}";
        } else {
            // Get all batches with their practical groups
            $batches = Batch::with(['course', 'practicalGroups' => function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId)
                    ->with(['classroom', 'students' => function ($q) {
                        $q->orderBy('name');
                    }]);
            }])->whereHas('practicalGroups', function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })->orderBy('name')->get();

            $reportTitle = 'Complete Student Allocation List';
            $filename = "students-allocation-all-batches-{$academicYear->name}";
        }

        // Get college information from settings
        $collegeInfo = $this->getCollegeInfo();

        // Get unassigned students
        $unassignedStudents = $this->getUnassignedStudents($batches, $academicYearId);

        // Generate the PDF using dompdf
        try {
            // Ensure academic year dates are properly formatted
            $academicYear->start_date = \Carbon\Carbon::parse($academicYear->start_date);
            $academicYear->end_date = \Carbon\Carbon::parse($academicYear->end_date);

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.lab_allocation.students_pdf', [
                'batches' => $batches,
                'academicYear' => $academicYear,
                'collegeInfo' => $collegeInfo,
                'unassignedStudents' => $unassignedStudents,
                'reportTitle' => $reportTitle,
                'generatedAt' => now(),
                'generatedBy' => auth()->user(),
            ]);

            $pdf->setPaper('a4', 'portrait');

            return $pdf->download($filename.'.pdf');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate students PDF: '.$e->getMessage());
        }
    }

    /**
     * Get allocation statistics for a batch
     */
    public function getStats(Batch $batch)
    {
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();

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
