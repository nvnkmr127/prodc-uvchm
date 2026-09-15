<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MissingAcademicYearException;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Course;
use App\Models\MentorAllocation;
use App\Models\MentorGroup;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MentorAllocationController extends Controller
{
    protected AcademicYearService $academicYearService;

    public function __construct(AcademicYearService $academicYearService)
    {
        $this->academicYearService = $academicYearService;
    }

    /**
     * Display the mentor allocation management dashboard.
     */
    public function index(Request $request)
    {
        // 1. Resolve Academic Year
        $allAcademicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        try {
            $defaultYearId = $this->academicYearService->getActiveAcademicYearId();
        } catch (MissingAcademicYearException $e) {
            $defaultYearId = $allAcademicYears->first()?->id;
        }

        $selectedAcademicYearId = $request->input(
            'academic_year_id',
            session('selected_academic_year_id', $defaultYearId)
        );

        $selectedAcademicYear = AcademicYear::find($selectedAcademicYearId);

        // 2. Fetch Filter Options
        $courses = Course::orderBy('name')->get();
        $batches = Batch::when($selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->orderBy('name')
            ->get();

        // Anyone is allowed to be a mentor (not only faculty), but only those
        // marked available can receive new allocations (R2).
        $potentialMentors = User::where('status', 'active')
            ->where('is_available', true)
            ->with('roles')
            ->orderBy('name')
            ->get();

        // Mentor Groups for the academic year
        $mentorGroups = MentorGroup::with(['faculty.roles', 'counselor.roles', 'academicYear'])
            ->withCount('students')
            ->when($selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->orderBy('name')
            ->get();

        // 3. Query Students for Allocation
        $studentsQuery = Student::query()
            ->with(['batch.course', 'mentor.roles', 'counselor.roles', 'mentorGroup.faculty', 'mentorGroup.counselor'])
            ->where('students.status', 'active');

        if ($selectedAcademicYearId) {
            $studentsQuery->whereHas('batch', function ($q) use ($selectedAcademicYearId) {
                $q->where('academic_year_id', $selectedAcademicYearId);
            });
        }

        if ($request->filled('course_id')) {
            $studentsQuery->whereHas('batch', fn ($q) => $q->where('course_id', $request->course_id));
        }

        if ($request->filled('batch_id')) {
            $studentsQuery->where('batch_id', $request->batch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('enrollment_number', 'like', "%{$search}%")
                    ->orWhere('student_mobile', 'like', "%{$search}%")
                    ->orWhere('father_mobile', 'like', "%{$search}%");
            });
        }

        // At-risk escalation queue filter (R8)
        if ($request->boolean('needs_escalation')) {
            $studentsQuery->where('needs_escalation', true);
        }

        // Filter by allocation status
        $statusFilter = $request->input('allocation_status', 'all');
        if ($statusFilter === 'unassigned') {
            $studentsQuery->whereNull('mentor_id');
        } elseif ($statusFilter === 'assigned') {
            $studentsQuery->whereNotNull('mentor_id');
        } elseif ($statusFilter !== 'all' && is_numeric($statusFilter)) {
            $studentsQuery->where('mentor_id', $statusFilter);
        }

        if ($request->filled('mentor_group_id')) {
            $studentsQuery->where('mentor_group_id', $request->mentor_group_id);
        }

        // Stats calculation for the selected academic year
        $statsBaseQuery = Student::where('status', 'active');
        if ($selectedAcademicYearId) {
            $statsBaseQuery->whereHas('batch', fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId));
        }

        $totalStudents = (clone $statsBaseQuery)->count();
        $assignedCount = (clone $statsBaseQuery)->whereNotNull('mentor_id')->count();
        $unassignedCount = max(0, $totalStudents - $assignedCount);
        $activeMentorsCount = (clone $statsBaseQuery)->whereNotNull('mentor_id')->distinct('mentor_id')->count('mentor_id');

        // Mentor summary breakdown
        $mentorSummaries = User::whereHas('mentees', function ($q) use ($selectedAcademicYearId) {
            $q->where('status', 'active');
            if ($selectedAcademicYearId) {
                $q->whereHas('batch', fn ($bq) => $bq->where('academic_year_id', $selectedAcademicYearId));
            }
        })
            ->withCount(['mentees' => function ($q) use ($selectedAcademicYearId) {
                $q->where('status', 'active');
                if ($selectedAcademicYearId) {
                    $q->whereHas('batch', fn ($bq) => $bq->where('academic_year_id', $selectedAcademicYearId));
                }
            }])
            ->with('roles')
            ->orderBy('name')
            ->get();

        $students = $studentsQuery->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.mentor_allocation.index', compact(
            'allAcademicYears',
            'selectedAcademicYearId',
            'selectedAcademicYear',
            'courses',
            'batches',
            'potentialMentors',
            'students',
            'totalStudents',
            'assignedCount',
            'unassignedCount',
            'activeMentorsCount',
            'mentorSummaries',
            'mentorGroups',
            'statusFilter'
        ));
    }

    /**
     * Bulk assign students to a mentor.
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|exists:students,id',
            'mentor_id' => 'required|exists:users,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $mentor = User::findOrFail($validated['mentor_id']);

        // R2: a mentor marked unavailable cannot receive new allocations.
        if (! $mentor->is_available) {
            return redirect()->back()->with('error', "Mentor {$mentor->name} is marked unavailable for new allocations.");
        }

        $academicYearId = $validated['academic_year_id'] ?? null;

        if (! $academicYearId) {
            try {
                $academicYearId = $this->academicYearService->getActiveAcademicYearId();
            } catch (MissingAcademicYearException $e) {
                $academicYearId = AcademicYear::where('is_current', true)->value('id');
            }
        }

        DB::beginTransaction();
        try {
            foreach ($validated['student_ids'] as $studentId) {
                // Deactivate any existing allocation for this student in this academic year
                MentorAllocation::where('student_id', $studentId)
                    ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                    ->update(['is_active' => false]);

                // Create new allocation record
                MentorAllocation::create([
                    'academic_year_id' => $academicYearId,
                    'student_id' => $studentId,
                    'mentor_id' => $mentor->id,
                    'assigned_by' => auth()->id(),
                    'is_active' => true,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Update current mentor on student model for quick access
                Student::where('id', $studentId)->update([
                    'mentor_id' => $mentor->id,
                ]);
            }

            DB::commit();

            $count = count($validated['student_ids']);

            return redirect()->back()->with('success', "Successfully assigned {$count} students to mentor {$mentor->name}.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to assign students: '.$e->getMessage());
        }
    }

    /**
     * Bulk unassign mentor from students.
     */
    public function unassign(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|exists:students,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $academicYearId = $validated['academic_year_id'] ?? null;

        DB::beginTransaction();
        try {
            // Deactivate allocations
            MentorAllocation::whereIn('student_id', $validated['student_ids'])
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->update(['is_active' => false]);

            // Clear mentor pointer on students
            Student::whereIn('id', $validated['student_ids'])->update([
                'mentor_id' => null,
            ]);

            DB::commit();

            $count = count($validated['student_ids']);

            return redirect()->back()->with('success', "Successfully unassigned mentor from {$count} students.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to unassign students: '.$e->getMessage());
        }
    }

    /**
     * Bulk assign students (across any courses/departments) to a Mentor Group.
     */
    public function assignGroup(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|exists:students,id',
            'mentor_group_id' => 'required|exists:mentor_groups,id',
        ]);

        $group = MentorGroup::with(['faculty', 'counselor'])->findOrFail($validated['mentor_group_id']);

        DB::beginTransaction();
        try {
            $updates = [
                'mentor_group_id' => $group->id,
            ];
            if ($group->faculty_id) {
                $updates['mentor_id'] = $group->faculty_id;
            }
            if ($group->counselor_id) {
                $updates['counselor_id'] = $group->counselor_id;
            }

            Student::whereIn('id', $validated['student_ids'])->update($updates);

            // Record faculty allocation history if faculty is present
            if (! empty($updates['mentor_id'])) {
                MentorAllocation::whereIn('student_id', $validated['student_ids'])
                    ->when($group->academic_year_id, fn ($q) => $q->where('academic_year_id', $group->academic_year_id))
                    ->update(['is_active' => false]);

                foreach ($validated['student_ids'] as $sid) {
                    MentorAllocation::create([
                        'academic_year_id' => $group->academic_year_id,
                        'student_id' => $sid,
                        'mentor_id' => $updates['mentor_id'],
                        'assigned_by' => auth()->id(),
                        'is_active' => true,
                        'notes' => 'Allocated via Mentor Group: ' . $group->name,
                    ]);
                }
            }

            DB::commit();

            $count = count($validated['student_ids']);
            $facultyName = $group->faculty?->name ?? 'None';
            $counselorName = $group->counselor?->name ?? 'None';

            return redirect()->back()->with('success', "Successfully assigned {$count} students to {$group->name} (Faculty: {$facultyName}, Counselor: {$counselorName}).");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to assign students to group: ' . $e->getMessage());
        }
    }

    /**
     * Create a new Mentor Group with Faculty and Counselor.
     */
    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'academic_year_id' => 'required|exists:academic_years,id',
            'faculty_id' => 'nullable|exists:users,id',
            'counselor_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string|max:500',
        ]);

        $group = MentorGroup::create($validated);

        return redirect()->back()->with('success', "Mentor Group '{$group->name}' created successfully.");
    }

    /**
     * Update an existing Mentor Group's details (name, year, faculty, counselor).
     *
     * When "apply_to_members" is set (R4), a changed faculty/counselor is pushed
     * onto the group's current students and mentor allocation history is written,
     * mirroring assignGroup(). Otherwise only the group record changes.
     */
    public function updateGroup(Request $request, MentorGroup $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'academic_year_id' => 'required|exists:academic_years,id',
            'faculty_id' => 'nullable|exists:users,id',
            'counselor_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string|max:500',
        ]);

        $applyToMembers = $request->boolean('apply_to_members');

        DB::beginTransaction();
        try {
            $group->update($validated);

            if ($applyToMembers) {
                $studentIds = Student::where('mentor_group_id', $group->id)->pluck('id')->all();

                if (! empty($studentIds)) {
                    $updates = [];
                    if ($group->faculty_id) {
                        $updates['mentor_id'] = $group->faculty_id;
                    }
                    if ($group->counselor_id) {
                        $updates['counselor_id'] = $group->counselor_id;
                    }

                    if (! empty($updates)) {
                        Student::whereIn('id', $studentIds)->update($updates);
                    }

                    // Record faculty allocation history when a faculty mentor is set.
                    if (! empty($updates['mentor_id'])) {
                        MentorAllocation::whereIn('student_id', $studentIds)
                            ->when($group->academic_year_id, fn ($q) => $q->where('academic_year_id', $group->academic_year_id))
                            ->update(['is_active' => false]);

                        foreach ($studentIds as $sid) {
                            MentorAllocation::create([
                                'academic_year_id' => $group->academic_year_id,
                                'student_id' => $sid,
                                'mentor_id' => $updates['mentor_id'],
                                'assigned_by' => auth()->id(),
                                'is_active' => true,
                                'notes' => 'Updated via Mentor Group: '.$group->name,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $suffix = $applyToMembers ? ' Changes applied to current members.' : '';

            return redirect()->back()->with('success', "Mentor Group '{$group->name}' updated successfully.".$suffix);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to update group: '.$e->getMessage());
        }
    }

    /**
     * Export the at-risk escalation queue to CSV (R8).
     */
    public function exportEscalations(Request $request)
    {
        $students = Student::where('needs_escalation', true)
            ->where('status', 'active')
            ->with(['batch.course', 'mentor', 'counselor', 'escalatedBy'])
            ->orderBy('escalated_at', 'desc')
            ->get();

        $fileName = 'escalation_queue_'.date('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($students) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, [
                'S.No', 'Student Name', 'Enrollment Number', 'Course', 'Batch',
                'Mentor', 'Counselor', 'Flagged By', 'Flagged At', 'Student Mobile',
            ]);

            $index = 1;
            foreach ($students as $s) {
                fputcsv($file, [
                    $index++,
                    $s->name,
                    $s->enrollment_number ?? '',
                    $s->batch?->course?->name ?? '',
                    $s->batch?->name ?? '',
                    $s->mentor?->name ?? 'Unassigned',
                    $s->counselor?->name ?? 'Unassigned',
                    $s->escalatedBy?->name ?? '',
                    $s->escalated_at ? $s->escalated_at->format('Y-m-d H:i') : '',
                    $s->student_mobile ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete a Mentor Group.
     */
    public function destroyGroup(MentorGroup $group)
    {
        // Unlink students from this group
        Student::where('mentor_group_id', $group->id)->update(['mentor_group_id' => null]);
        $name = $group->name;
        $group->delete();

        return redirect()->back()->with('success', "Mentor Group '{$name}' deleted successfully.");
    }

    /**
     * Export students of a specific mentor group to CSV.
     */
    public function exportGroupCsv(MentorGroup $group)
    {
        $group->load(['faculty', 'counselor']);
        $students = Student::where('mentor_group_id', $group->id)
            ->with(['batch.course'])
            ->orderBy('name')
            ->get();

        $slugName = \Illuminate\Support\Str::slug($group->name);
        $fileName = "mentor_group_{$slugName}_" . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($group, $students) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($file, [
                'S.No',
                'Mentor Group Name',
                'Faculty Mentor',
                'Counselor',
                'Student Name',
                'Enrollment Number',
                'Course',
                'Batch',
                'Student Mobile',
                'Father Name',
                'Father Mobile',
                'Mother Mobile',
            ]);

            $index = 1;
            foreach ($students as $s) {
                fputcsv($file, [
                    $index++,
                    $group->name,
                    $group->faculty?->name ?? 'Unassigned',
                    $group->counselor?->name ?? 'Unassigned',
                    $s->name,
                    $s->enrollment_number ?? '',
                    $s->batch?->course?->name ?? '',
                    $s->batch?->name ?? '',
                    $s->student_mobile ?? '',
                    $s->father_name ?? '',
                    $s->father_mobile ?? '',
                    $s->mother_mobile ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
