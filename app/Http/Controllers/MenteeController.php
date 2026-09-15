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

        // 3. Query Mentees
        $menteesQuery = Student::where('mentor_id', $mentor->id)
            ->where('students.status', 'active')
            ->with(['batch.course', 'studentFees', 'followUps' => fn ($q) => $q->latest()]);

        if ($request->filled('batch_id')) {
            $menteesQuery->where('batch_id', $request->batch_id);
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

        // Summary Statistics
        $totalMentees = $allMentees->count();
        $totalOutstandingFees = $menteesData->sum('total_outstanding');
        $lowAttendanceCount = $allMentees->filter(fn ($s) => $s->getAttendancePercentage($startDate, $endDate, true) < 75)->count();
        $avgAttendance = $totalMentees > 0
            ? round($allMentees->avg(fn ($s) => $s->getAttendancePercentage($startDate, $endDate, true)), 1)
            : 0;

        // Filter dropdowns
        $batches = Batch::whereIn('id', $allMentees->pluck('batch_id')->filter()->unique())
            ->orderBy('name')
            ->get();

        $allMentors = $isElevatedUser
            ? User::whereHas('mentees')->orderBy('name')->get()
            : collect([$mentor]);

        return view('mentees.index', compact(
            'mentor',
            'menteesData',
            'totalMentees',
            'totalOutstandingFees',
            'lowAttendanceCount',
            'avgAttendance',
            'batches',
            'allMentors',
            'isElevatedUser',
            'academicYear'
        ));
    }

    /**
     * Show detailed mentee profile with attendance breakdown, fees, and coordination timeline.
     */
    public function show(Student $student)
    {
        $currentUser = auth()->user();
        $isElevatedUser = $currentUser->hasRole('super-admin') || $currentUser->can('manage students');

        // Verify access: only the assigned mentor or admins can view
        if ($student->mentor_id !== $currentUser->id && ! $isElevatedUser) {
            abort(403, 'Unauthorized. You are not assigned as mentor to this student.');
        }

        $student->load(['batch.course', 'studentFees.feeCategory', 'followUps.user']);

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
