<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Timetable; // Import the Payment model
use App\Services\ComponentPaymentService; // ✅ IMPORTED: The new service for component-based finances.
use App\Services\DashboardDataService;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    protected $dashboardService;

    protected $dataService;

    protected $componentPaymentService; // ✅ ADDED: Property for the new service.

    public function __construct(
        DashboardService $dashboardService,
        DashboardDataService $dataService,
        ComponentPaymentService $componentPaymentService // ✅ INJECTED: Dependency injection for the new service.
    ) {
        $this->middleware(['auth', 'role:student']);
        $this->dashboardService = $dashboardService;
        $this->dataService = $dataService;
        $this->componentPaymentService = $componentPaymentService; // ✅ INITIALIZED: Assign service to property.
    }

    public function index()
    {
        $user = auth()->user();
        $student = $user->student;

        if (! $student) {
            return redirect('/profile')->with('error', 'Please complete your student profile.');
        }

        $dashboardData = $this->dashboardService->getDashboardData($user);

        $data = [
            'user' => $user,
            'student' => $student,
            'dashboard_data' => $dashboardData,
            'attendance_summary' => $this->getMyAttendanceSummary($student),
            'fee_summary' => $this->getMyFeeSummary($student), // This method is now updated.
            'academic_summary' => $this->getMyAcademicSummary($student),
            'todays_schedule' => $this->getTodaysSchedule($student),
            'upcoming_events' => $this->getUpcomingEvents($student),
            'recent_notifications' => $this->getRecentNotifications($student),
        ];

        return view('student.dashboard.index', $data);
    }

    public function academicProgress()
    {
        $user = auth()->user();
        $student = $user->student;

        $data = [
            'student' => $student,
            'attendance_details' => $this->getDetailedAttendance($student),
            'performance_metrics' => $this->getPerformanceMetrics($student),
            'subject_wise_performance' => $this->getSubjectWisePerformance($student),
            'monthly_trends' => $this->getMonthlyTrends($student),
        ];

        return view('student.dashboard.academic-progress', $data);
    }

    //
    // =======================================================================
    // == FINANCIAL METHODS (UPDATED FOR COMPONENT-BASED SYSTEM)
    // =======================================================================
    //

    /**
     * ✅ UPDATED: getMyFeeSummary
     * This method now uses the ComponentPaymentService to get a detailed financial
     * summary based on individual StudentFee records, not monolithic invoices.
     */
    protected function getMyFeeSummary(Student $student): array
    {
        // Use the new service to get a comprehensive financial summary.
        $summary = $this->componentPaymentService->getStudentFinancialSummary($student);

        // Get the next due date from outstanding component fees.
        $nextDueDate = $student->studentFees()
            ->whereIn('status', ['unpaid', 'partial'])
            ->min('due_date');

        return [
            'total_amount' => $summary['total_amount'],
            'paid_amount' => $summary['paid_amount'],
            'pending_amount' => $summary['due_amount'], // 'due_amount' from service is the pending amount.
            'overdue_amount' => $summary['overdue_amount'],
            'payment_percentage' => $summary['payment_percentage'],
            'next_due_date' => $nextDueDate,
            'days_until_due' => $nextDueDate ? now()->diffInDays($nextDueDate, false) : null,
            'status' => $this->getFeeStatus($summary['due_amount'], $summary['overdue_amount']),
            'recent_payments' => $this->getRecentPayments($student), // This helper is also updated.
        ];
    }

    /**
     * ✅ UPDATED: getFeeStatus
     * Logic is updated to reflect the new summary structure.
     */
    protected function getFeeStatus(float $pendingAmount, float $overdueAmount): string
    {
        if ($pendingAmount <= 0) {
            return 'paid';
        }
        if ($overdueAmount > 0) {
            return 'overdue';
        }

        return 'pending';
    }

    /**
     * ✅ UPDATED: getRecentPayments
     * This now fetches payments of type 'component' and gets details from component items.
     */
    protected function getRecentPayments(Student $student): array
    {
        return Payment::where('student_id', $student->id)
            ->where('payment_type', 'component') // Query for component payments.
            ->with('componentItems.studentFee.feeCategory') // Load relations for details.
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                // Describe the payment based on the components paid.
                $description = $payment->componentItems
                    ->pluck('studentFee.feeCategory.name')
                    ->unique()
                    ->implode(', ');

                return [
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date->format('M j, Y'),
                    'type' => $description ?: 'Fee Payment',
                ];
            })
            ->toArray();
    }

    //
    // =======================================================================
    // == ACADEMIC & ATTENDANCE METHODS (UNCHANGED)
    // =======================================================================
    //

    protected function getMyAttendanceSummary(Student $student): array
    {
        $attendances = Attendance::where('student_id', $student->id)->get();
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();

        $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;

        $weeklyTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $weekAttendances = Attendance::where('student_id', $student->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->get();

            $weekTotal = $weekAttendances->count();
            $weekPresent = $weekAttendances->where('status', 'present')->count();
            $weekPercentage = $weekTotal > 0 ? round(($weekPresent / $weekTotal) * 100, 1) : 0;

            $weeklyTrend[] = [
                'week' => $weekStart->format('M j'),
                'percentage' => $weekPercentage,
                'classes' => $weekTotal,
            ];
        }

        return [
            'total_classes' => $total,
            'present' => $present,
            'absent' => $absent,
            'percentage' => $percentage,
            'status' => $this->getAttendanceStatus($percentage),
            'weekly_trend' => $weeklyTrend,
            'days_since_absent' => $this->getDaysSinceLastAbsent($student),
        ];
    }

    protected function getMyAcademicSummary(Student $student): array
    {
        return [
            'current_semester' => $student->current_semester ?? 1,
            'course_progress' => 75,
            'gpa' => 8.5,
            'credits_completed' => 45,
            'credits_total' => 180,
            'rank_in_class' => 15,
            'total_students' => 120,
            'subjects_enrolled' => $this->getEnrolledSubjects($student),
        ];
    }

    protected function getTodaysSchedule(Student $student): array
    {
        $batch = $student->batch;
        if (! $batch) {
            return [];
        }

        $classes = Timetable::where('batch_id', $batch->id)
            ->whereDate('schedule_date', today())
            ->with(['subject', 'user', 'classroom', 'timeSlot'])
            ->orderBy('time_slot_id')
            ->get();

        return $classes->map(function ($class) use ($student) {
            $attendance = Attendance::where('timetable_id', $class->id)
                ->where('student_id', $student->id)
                ->first();

            return [
                'subject' => $class->subject->name ?? 'Unknown',
                'faculty' => $class->user->name ?? 'TBD',
                'classroom' => $class->classroom->name ?? 'TBD',
                'start_time' => $class->timeSlot->start_time ?? 'TBD',
                'end_time' => $class->timeSlot->end_time ?? 'TBD',
                'attendance_status' => $attendance ? $attendance->status : 'pending',
                'class_status' => $this->getClassStatus($class),
            ];
        })->toArray();
    }

    // ... (All other helper methods remain the same) ...

    protected function getUpcomingEvents($student): array
    {
        // Sample events - implement based on your events system
        return [
            [
                'title' => 'Mid-term Examinations',
                'date' => now()->addWeeks(2),
                'type' => 'exam',
                'description' => 'Mid-term exams for all subjects',
            ],
            [
                'title' => 'Project Submission',
                'date' => now()->addDays(10),
                'type' => 'assignment',
                'description' => 'Final project submission deadline',
            ],
        ];
    }

    protected function getRecentNotifications($student): array
    {
        // Sample notifications - integrate with your notification system
        return [
            [
                'title' => 'Fee Payment Reminder',
                'message' => 'Your next fee installment is due on '.now()->addDays(7)->format('M j, Y'),
                'type' => 'fee',
                'priority' => 'high',
                'created_at' => now()->subHours(2),
                'read' => false,
            ],
            [
                'title' => 'Assignment Graded',
                'message' => 'Your Physics assignment has been graded. Score: 85/100',
                'type' => 'academic',
                'priority' => 'normal',
                'created_at' => now()->subDays(1),
                'read' => false,
            ],
        ];
    }

    protected function getDetailedAttendance($student): array
    {
        $attendances = Attendance::where('student_id', $student->id)
            ->with(['timetable.subject', 'timetable.user'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return $attendances->map(function ($attendance) {
            return [
                'date' => $attendance->created_at->format('M j, Y'),
                'subject' => $attendance->timetable->subject->name ?? 'Unknown',
                'faculty' => $attendance->timetable->user->name ?? 'Unknown',
                'status' => $attendance->status,
                'time' => $attendance->created_at->format('H:i A'),
            ];
        })->toArray();
    }

    protected function getPerformanceMetrics($student): array
    {
        // Sample performance metrics - implement based on your assessment system
        return [
            'overall_grade' => 'A',
            'overall_percentage' => 85.5,
            'improvement_trend' => 'positive',
            'strengths' => ['Mathematics', 'Physics'],
            'areas_for_improvement' => ['English Literature'],
        ];
    }

    protected function getSubjectWisePerformance($student): array
    {
        // Sample subject-wise performance - implement based on your system
        return [
            [
                'subject' => 'Mathematics',
                'grade' => 'A+',
                'percentage' => 92,
                'attendance' => 95,
            ],
            [
                'subject' => 'Physics',
                'grade' => 'A',
                'percentage' => 88,
                'attendance' => 90,
            ],
        ];
    }

    private function getAttendanceStatus($percentage): string
    {
        if ($percentage >= 85) {
            return 'excellent';
        }
        if ($percentage >= 75) {
            return 'good';
        }
        if ($percentage >= 65) {
            return 'warning';
        }

        return 'critical';
    }

    private function getDaysSinceLastAbsent($student): ?int
    {
        $lastAbsent = Attendance::where('student_id', $student->id)
            ->where('status', 'absent')
            ->latest()
            ->first();

        return $lastAbsent ? now()->diffInDays($lastAbsent->created_at) : null;
    }

    private function getEnrolledSubjects($student): array
    {
        $batch = $student->batch;
        if (! $batch) {
            return [];
        }

        return $batch->subjects->map(function ($subject) {
            return [
                'name' => $subject->name,
                'code' => $subject->code ?? '',
                'credits' => $subject->credits ?? 0,
            ];
        })->toArray();
    }

    private function getClassStatus($class): string
    {
        $now = now();
        $classStart = \Carbon\Carbon::parse($class->schedule_date.' '.$class->timeSlot->start_time);
        $classEnd = \Carbon\Carbon::parse($class->schedule_date.' '.$class->timeSlot->end_time);

        if ($now->lt($classStart)) {
            return 'upcoming';
        }
        if ($now->between($classStart, $classEnd)) {
            return 'ongoing';
        }

        return 'completed';
    }

    private function getMonthlyTrends($student): array
    {
        // Sample monthly trends - implement based on your requirements
        return [
            'attendance_trend' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                'data' => [88, 92, 85, 90, 87],
            ],
            'performance_trend' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                'data' => [82, 85, 88, 86, 89],
            ],
        ];
    }
}
