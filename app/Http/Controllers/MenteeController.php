<?php

namespace App\Http\Controllers;

use App\Exceptions\MissingAcademicYearException;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\FollowUp;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MenteeController extends Controller
{
    protected AcademicYearService $academicYearService;

    public function __construct(AcademicYearService $academicYearService)
    {
        $this->academicYearService = $academicYearService;
    }

    /**
     * Display the mentee roster for the logged-in user (mentor).
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();

        // 1. Determine which mentor's mentees to display
        $mentorId = $currentUser->id;
        $isElevatedUser = false;
        try {
            $isElevatedUser = $currentUser->hasRole('super-admin') || $currentUser->can('manage students');
        } catch (\Throwable $e) {
            $isElevatedUser = $currentUser->roles()->where('name', 'super-admin')->exists();
        }

        if ($isElevatedUser && $request->filled('mentor_id')) {
            $mentorId = $request->mentor_id;
        }

        $mentor = User::findOrFail($mentorId);

        // 2. Resolve Active Academic Year
        try {
            $academicYearId = $this->academicYearService->getActiveAcademicYearId();
            $academicYear = AcademicYear::find($academicYearId);
        } catch (MissingAcademicYearException $e) {
            $academicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $academicYear?->id;
        }

        // Date range for attendance calculation (academic year or start of year to now)
        $startDate = $academicYear?->start_date ? Carbon::parse($academicYear->start_date) : now()->startOfYear();
        $endDate = now();

        // 3. Query Mentees (Faculty mentor, Counselor, or via MentorGroup)
        $menteesQuery = Student::where('students.status', 'active')
            ->where(function ($q) use ($mentor) {
                $q->where('mentor_id', $mentor->id)
                    ->orWhere('counselor_id', $mentor->id)
                    ->orWhereHas('mentorGroup', function ($gq) use ($mentor) {
                        $gq->where('faculty_id', $mentor->id)
                           ->orWhere('counselor_id', $mentor->id);
                    });
            })
            ->with(['batch.course', 'mentorGroup.faculty', 'mentorGroup.counselor', 'mentor', 'counselor', 'studentFees', 'followUps' => fn ($q) => $q->latest()]);

        if ($request->filled('batch_id')) {
            $menteesQuery->where('batch_id', $request->batch_id);
        }

        if ($request->filled('mentor_group_id')) {
            $menteesQuery->where('mentor_group_id', $request->mentor_group_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $menteesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('enrollment_number', 'like', "%{$search}%")
                    ->orWhere('student_mobile', 'like', "%{$search}%")
                    ->orWhere('father_mobile', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%");
            });
        }

        $allMentees = $menteesQuery->orderBy('name')->get();

        // 4. Calculate Key Metrics for Each Mentee
        $menteesData = $allMentees->map(function ($student) use ($startDate, $endDate) {
            // Attendance % in the active academic year
            $attendancePct = $student->getAttendancePercentage($startDate, $endDate, true);

            // Financial Summary
            $financial = $student->getFinancialSummary();

            // Latest follow-up note
            $latestNote = $student->followUps->first();

            return [
                'student' => $student,
                'attendance_percentage' => $attendancePct,
                'total_fees' => $financial['total_fees'] ?? 0,
                'total_paid' => $financial['total_paid'] ?? 0,
                'total_outstanding' => $financial['total_outstanding'] ?? 0,
                'payment_status' => $financial['payment_status'] ?? 'unknown',
                'latest_note' => $latestNote,
            ];
        });

        // Apply filters in collection if specified
        if ($request->input('attendance_filter') === 'low') {
            $menteesData = $menteesData->filter(fn ($item) => $item['attendance_percentage'] < 75);
        } elseif ($request->input('attendance_filter') === 'good') {
            $menteesData = $menteesData->filter(fn ($item) => $item['attendance_percentage'] >= 75);
        }

        if ($request->input('fee_filter') === 'pending') {
            $menteesData = $menteesData->filter(fn ($item) => $item['total_outstanding'] > 0);
        } elseif ($request->input('fee_filter') === 'cleared') {
            $menteesData = $menteesData->filter(fn ($item) => $item['total_outstanding'] <= 0);
        }

        // 5. Aggregate KPIs
        $totalMentees = $allMentees->count();
        $totalOutstandingFees = $menteesData->sum('total_outstanding');
        $lowAttendanceCount = $menteesData->where('attendance_percentage', '<', 75)->count();
        $avgAttendance = $totalMentees > 0 ? round($menteesData->avg('attendance_percentage'), 1) : 0;

        // Distinct Batches and Mentor Groups for Filtering
        $batches = Batch::with('course')->orderBy('name')->get();
        $mentorGroups = \App\Models\MentorGroup::when(! $isElevatedUser, function ($q) use ($mentor) {
            $q->where('faculty_id', $mentor->id)->orWhere('counselor_id', $mentor->id);
        })->orderBy('name')->get();

        // For elevated users, all mentors/counselors dropdown
        $allMentors = $isElevatedUser
            ? User::where(function ($q) {
                $q->whereHas('mentees')
                  ->orWhereHas('counseledStudents')
                  ->orWhereHas('facultyMentorGroups')
                  ->orWhereHas('counselorMentorGroups');
            })->orderBy('name')->get()
            : collect([$mentor]);

        return view('mentees.index', compact(
            'mentor',
            'menteesData',
            'totalMentees',
            'totalOutstandingFees',
            'lowAttendanceCount',
            'avgAttendance',
            'batches',
            'mentorGroups',
            'allMentors',
            'isElevatedUser',
            'academicYear'
        ));
    }

    /**
     * Show detailed mentee profile with attendance breakdown, fees, and coordination timeline.
     */
    public function show(Request $request, Student $student)
    {
        $currentUser = auth()->user();
        $isElevatedUser = false;
        try {
            $isElevatedUser = $currentUser->hasRole('super-admin') || $currentUser->can('manage students');
        } catch (\Throwable $e) {
            $isElevatedUser = $currentUser->roles()->where('name', 'super-admin')->exists();
        }

        // Verify access: assigned faculty, counselor, group staff, or admins
        $isAssigned = $student->mentor_id === $currentUser->id
            || $student->counselor_id === $currentUser->id
            || ($student->mentorGroup && ($student->mentorGroup->faculty_id === $currentUser->id || $student->mentorGroup->counselor_id === $currentUser->id));

        if (! $isAssigned && ! $isElevatedUser) {
            abort(403, 'Unauthorized. You are not assigned as faculty mentor or counselor to this student.');
        }

        $student->load(['batch.course', 'mentorGroup.faculty', 'mentorGroup.counselor', 'mentor', 'counselor', 'studentFees.feeCategory', 'followUps.user']);

        // Academic Year context
        try {
            $academicYear = $this->academicYearService->getCurrentAcademicYear();
        } catch (MissingAcademicYearException $e) {
            $academicYear = AcademicYear::where('is_current', true)->first();
        }

        $startDate = $academicYear?->start_date ? Carbon::parse($academicYear->start_date) : now()->startOfYear();
        $attendancePct = $student->getAttendancePercentage($startDate, now(), true);
        $financial = $student->getFinancialSummary();

        // Recent attendance records
        $recentAttendances = $student->attendances()
            ->withoutGlobalScope('academic_year')
            ->orderBy('attendance_date', 'desc')
            ->limit(20)
            ->get();

        // Follow-up interaction timeline
        $timeline = $student->followUps()->with('user')->latest()->get();

        return view('mentees.show', compact(
            'student',
            'attendancePct',
            'financial',
            'recentAttendances',
            'timeline',
            'academicYear'
        ));
    }

    /**
     * Store a parent coordination note / follow-up record for a mentee.
     */
    public function addNote(Request $request, Student $student)
    {
        $currentUser = auth()->user();
        $isElevatedUser = $currentUser->hasRole('super-admin') || $currentUser->can('manage students');

        if ($student->mentor_id !== $currentUser->id && ! $isElevatedUser) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
            'outcome' => 'nullable|string|max:100',
        ]);

        $student->followUps()->create([
            'user_id' => $currentUser->id,
            'notes' => $validated['notes'],
            'outcome' => $validated['outcome'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Parent coordination note saved successfully.');
    }
}
