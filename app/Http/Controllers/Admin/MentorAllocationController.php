<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MissingAcademicYearException;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Course;
use App\Models\MentorAllocation;
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

        // Anyone is allowed to be a mentor (not only faculty)
        $potentialMentors = User::where('status', 'active')
            ->with('roles')
            ->orderBy('name')
            ->get();

        // 3. Query Students for Allocation
        $studentsQuery = Student::query()
            ->with(['batch.course', 'mentor.roles'])
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

        // Filter by allocation status
        $statusFilter = $request->input('allocation_status', 'all');
        if ($statusFilter === 'unassigned') {
            $studentsQuery->whereNull('mentor_id');
        } elseif ($statusFilter === 'assigned') {
            $studentsQuery->whereNotNull('mentor_id');
        } elseif ($statusFilter !== 'all' && is_numeric($statusFilter)) {
            $studentsQuery->where('mentor_id', $statusFilter);
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
}
