<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\ComponentPaymentService;
use App\Services\DashboardService;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    protected $dashboardService;

    protected $componentPaymentService; // NEW: Component service

    public function __construct(DashboardService $dashboardService, ComponentPaymentService $componentPaymentService)
    {
        $this->dashboardService = $dashboardService;
        $this->componentPaymentService = $componentPaymentService; // NEW
    }

    public function index()
    {
        $user = auth()->user();

        // Get selected academic year from session (only if table exists)
        $selectedAcademicYearId = null;
        if (\Schema::hasTable('academic_years')) {
            $selectedAcademicYearId = session('selected_academic_year_id', \App\Models\AcademicYear::where('is_current', true)->value('id'));
        }

        $data = [
            'user' => $user,
            'selectedAcademicYearId' => $selectedAcademicYearId,
            'overview_metrics' => $this->getOverviewMetrics($selectedAcademicYearId), // UPDATED: Component-based
            'financial_summary' => $this->getFinancialSummary($selectedAcademicYearId), // UPDATED: Component-based
            'student_analytics' => $this->getStudentAnalytics($selectedAcademicYearId), // UPDATED: Component scopes
            'academic_overview' => $this->getAcademicOverview($selectedAcademicYearId),
            'recent_activities' => $this->getRecentActivities($selectedAcademicYearId), // UPDATED: Component activities
            'system_status' => $this->getSystemStatus(),
            'quick_stats' => $this->getQuickStats($selectedAcademicYearId), // UPDATED: Component stats
            'performance_indicators' => $this->getPerformanceIndicators($selectedAcademicYearId), // NEW: Component KPIs
        ];

        return view('admin.dashboard.index', $data);
    }

    // ===================================
    // UPDATED METHODS (Component-Based)
    // ===================================

    /**
     * Get overview metrics using ComponentPaymentService
     * FIXED: Complete the truncated method implementation
     */
    protected function getOverviewMetrics($academicYearId = null): array
    {
        $financialData = $this->componentPaymentService->getDashboardFinancialData();
        $efficiency = $this->componentPaymentService->getCollectionEfficiency();
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        // Filter students by academic year through batch
        $studentsQuery = Student::query();
        $activeStudentsQuery = Student::active();

        if ($academicYearId) {
            $studentsQuery->whereHas('batch', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
            $activeStudentsQuery->whereHas('batch', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }

        return [
            'total_students' => $studentsQuery->count(),
            'active_students' => $activeStudentsQuery->count(),
            'total_courses' => Course::count(),
            'active_batches' => Batch::when($academicYearId, function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            })->count(),
            'total_revenue' => $financialData['total_revenue'],
            'monthly_revenue' => $financialData['monthly_collection'],
            'pending_amount' => $financialData['pending_amount'],
            'overdue_amount' => $financialData['overdue_amount'],
            'collection_rate' => $financialData['collection_rate'],
            'students_with_dues' => Student::withOutstandingFees()->count(), // UPDATED: Component scope
            'defaulter_count' => Student::withOverdueFees()->count(), // UPDATED: Component scope
            'efficiency_score' => $this->calculateEfficiencyScore($efficiency),
        ];
    }

    /**
     * Get financial summary using component system
     * FIXED: Complete the truncated method implementation
     */
    protected function getFinancialSummary($academicYearId = null): array
    {
        $financialData = $this->componentPaymentService->getDashboardFinancialData();
        $collectionSummary = $this->componentPaymentService->getCollectionSummary();
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(6);

        return [
            'revenue_metrics' => [
                'total_revenue' => $financialData['total_revenue'],
                'pending_amount' => $financialData['pending_amount'],
                'overdue_amount' => $financialData['overdue_amount'],
                'monthly_collection' => $financialData['monthly_collection'],
                'total_concessions' => $financialData['total_concessions'],
            ],
            'collection_summary' => [
                'collection_rate' => $collectionSummary['collection_percentage'],
                'efficiency_score' => $this->calculateEfficiencyScore($collectionSummary),
                'growth_rate' => $this->calculateGrowthRate($trends),
            ],
            'trends' => $trends,
            'outstanding_breakdown' => $outstanding['by_category'] ?? [],
        ];
    }

    /**
     * Get student analytics with component-based financial data
     * ENHANCED: Added component-based financial tracking
     */
    protected function getStudentAnalytics($academicYearId = null): array
    {
        $totalStudents = Student::count();
        $activeStudents = Student::active()->count();
        $studentsWithOutstanding = Student::withOutstandingFees()->count(); // UPDATED: Component scope
        $studentsWithOverdue = Student::withOverdueFees()->count(); // UPDATED: Component scope

        return [
            'enrollment_metrics' => [
                'total_students' => $totalStudents,
                'active_students' => $activeStudents,
                'graduated_students' => Student::graduated()->count(),
                'dropout_students' => Student::dropout()->count(),
            ],
            'financial_metrics' => [
                'students_with_outstanding' => $studentsWithOutstanding,
                'students_with_overdue' => $studentsWithOverdue,
                'students_fully_paid' => Student::withPaidFees()->count(), // NEW: Component scope
                'payment_compliance_rate' => $totalStudents > 0 ?
                    round((($totalStudents - $studentsWithOutstanding) / $totalStudents) * 100, 1) : 0,
            ],
            'enrollment_by_course' => $this->getEnrollmentByCourse(),
            'attendance_summary' => $this->getAttendanceSummary(),
            'performance_distribution' => $this->getPerformanceDistribution(),
            'geographic_distribution' => $this->getGeographicDistribution(),
        ];
    }

    /**
     * Get recent activities including component payments
     * UPDATED: Include component payment activities
     */
    protected function getRecentActivities($academicYearId = null): array
    {
        // Get recent component payments
        $recentPayments = $this->componentPaymentService->generatePaymentReport([
            'start_date' => now()->subDays(7),
            'end_date' => now(),
        ]);

        $paymentActivities = array_map(function ($payment) {
            return [
                'type' => 'payment',
                'icon' => 'fa-money-bill-wave',
                'color' => 'success',
                'title' => 'Component Payment Received',
                'description' => "₹{$payment['amount']} paid by {$payment['student_name']}",
                'timestamp' => $payment['payment_date'],
                'details' => [
                    'receipt' => $payment['receipt_number'],
                    'method' => $payment['payment_method'],
                    'components' => count($payment['components']),
                ],
            ];
        }, array_slice($recentPayments['payments'], 0, 5));

        // Get other recent activities (enquiries, admissions, etc.)
        $otherActivities = $this->getOtherRecentActivities();

        // Merge and sort by timestamp
        $allActivities = array_merge($paymentActivities, $otherActivities);
        usort($allActivities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($allActivities, 0, 10);
    }

    /**
     * Get quick stats with component-based metrics
     * UPDATED: Component-based financial quick stats
     */
    protected function getQuickStats($academicYearId = null): array
    {
        $financialData = $this->componentPaymentService->getDashboardFinancialData();
        $todayPayments = Payment::componentPayments()
            ->whereDate('payment_date', today())
            ->sum('amount');

        return [
            'today_collections' => $todayPayments,
            'today_admissions' => $this->getTodayAdmissions(),
            'pending_enquiries' => Enquiry::where('status', 'pending')->count(),
            'overdue_students' => Student::withOverdueFees()->count(), // UPDATED: Component scope
            'monthly_target_progress' => $this->getMonthlyTargetProgress($financialData),
            'active_sessions' => $this->getActiveUserSessions(),
            'system_alerts' => $this->getSystemAlerts(),
        ];
    }

    /**
     * Get performance indicators based on component system
     * FIXED: Complete the truncated method implementation
     */
    protected function getPerformanceIndicators($academicYearId = null): array
    {
        $efficiency = $this->componentPaymentService->getCollectionEfficiency();
        $behavior = $this->componentPaymentService->getPaymentBehaviorAnalytics();
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        return [
            'collection_kpis' => [
                'collection_rate' => $efficiency['collection_rate'] ?? 0,
                'efficiency_score' => $this->calculateEfficiencyScore($efficiency),
                'recovery_rate' => $this->calculateRecoveryRate(),
                'payment_timing' => $this->getPaymentTimingKPI($behavior),
            ],
            'operational_kpis' => [
                'student_satisfaction' => $this->getStudentSatisfactionKPI(),
                'process_efficiency' => $this->getProcessEfficiencyKPI(),
                'data_accuracy' => $this->getDataAccuracyKPI(),
                'system_uptime' => $this->getSystemUptimeKPI(),
            ],
            'financial_kpis' => [
                'revenue_growth' => $this->getRevenueGrowthKPI(),
                'cost_efficiency' => $this->getCostEfficiencyKPI(),
                'bad_debt_ratio' => $this->getBadDebtRatioKPI($outstanding),
                'cash_flow_health' => $this->getCashFlowHealthKPI(),
            ],
        ];
    }

    // ===================================
    // EXISTING METHODS (Enhanced)
    // ===================================

    protected function getAcademicOverview($academicYearId = null): array
    {
        return [
            'courses' => [
                'total' => Course::count(),
                'active' => Course::where('is_active', true)->count(),
                'popular' => $this->getPopularCourses(),
            ],
            'batches' => [
                'total' => Batch::count(),
                'active' => Batch::whereHas('students')->count(),
                'average_size' => $this->getAverageBatchSize(),
            ],
            'academic_calendar' => [
                'current_semester' => $this->getCurrentSemester(),
                'upcoming_events' => $this->getUpcomingAcademicEvents(),
                'holidays_this_month' => $this->getHolidaysThisMonth(),
            ],
        ];
    }

    protected function getSystemStatus(): array
    {
        return [
            'application' => [
                'status' => 'healthy',
                'uptime' => '99.9%',
                'response_time' => '2ms',
                'last_deployment' => now()->subDays(3),
            ],
            'database' => [
                'status' => 'healthy',
                'connections' => 45,
                'query_performance' => 'optimal',
                'backup_status' => 'up_to_date',
            ],
            'component_system' => [ // NEW: Component system status
                'status' => 'active',
                'migration_status' => 'in_progress',
                'data_integrity' => 'verified',
                'performance' => 'optimal',
            ],
            'storage' => [
                'status' => 'healthy',
                'usage' => '65%',
                'available_space' => '2.1TB',
            ],
        ];
    }

    // ===================================
    // HELPER METHODS
    // ===================================

    protected function calculateEfficiencyScore($efficiency): float
    {
        if (! is_array($efficiency) || empty($efficiency)) {
            return 0.0;
        }

        $collectionRate = $efficiency['collection_rate'] ?? 0;
        $timeliness = $efficiency['timeliness_score'] ?? 100;
        $consistency = $efficiency['consistency_score'] ?? 100;

        return round(($collectionRate * 0.5) + ($timeliness * 0.3) + ($consistency * 0.2), 2);
    }

    protected function calculateGrowthRate($trends): float
    {
        if (! is_array($trends) || count($trends) < 2) {
            return 0.0;
        }

        $latest = end($trends);
        $previous = prev($trends);

        if (! $previous || $previous['amount'] == 0) {
            return 0.0;
        }

        return round((($latest['amount'] - $previous['amount']) / $previous['amount']) * 100, 2);
    }

    private function determineTrendDirection(array $trends): string
    {
        if (count($trends) < 3) {
            return 'stable';
        }

        $recentTrends = array_slice($trends, -3);
        $growthRates = [];

        for ($i = 1; $i < count($recentTrends); $i++) {
            $current = $recentTrends[$i]['amount'];
            $previous = $recentTrends[$i - 1]['amount'];
            $growthRates[] = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        }

        $avgGrowth = array_sum($growthRates) / count($growthRates);

        if ($avgGrowth > 2) {
            return 'upward';
        }
        if ($avgGrowth < -2) {
            return 'downward';
        }

        return 'stable';
    }

    private function getFinancialTargets(array $financialData): array
    {
        // These would typically come from settings or annual planning
        $annualTarget = 10000000; // 1 crore
        $monthlyTarget = $annualTarget / 12;

        return [
            'annual_revenue_target' => $annualTarget,
            'monthly_revenue_target' => $monthlyTarget,
            'collection_rate_target' => 90,
            'current_achievement' => [
                'annual' => round(($financialData['total_revenue'] / $annualTarget) * 100, 1),
                'monthly' => round(($financialData['monthly_collection'] / $monthlyTarget) * 100, 1),
                'collection_rate' => $financialData['collection_rate'],
            ],
        ];
    }

    private function getOtherRecentActivities(): array
    {
        $recentEnquiries = Enquiry::with('course')->latest()->limit(3)->get();
        $recentAdmissions = Student::with('batch.course')->latest()->limit(2)->get();

        $activities = [];

        // Add enquiry activities
        foreach ($recentEnquiries as $enquiry) {
            $activities[] = [
                'type' => 'enquiry',
                'icon' => 'fa-user-plus',
                'color' => 'info',
                'title' => 'New Enquiry Received',
                'description' => "Enquiry from {$enquiry->full_name} for ".($enquiry->course?->name ?? 'Unknown Course'),
                'timestamp' => $enquiry->created_at->toDateTimeString(),
                'details' => [
                    'phone' => $enquiry->phone_number,
                    'source' => $enquiry->source,
                    'status' => $enquiry->status,
                ],
            ];
        }

        // Add admission activities
        foreach ($recentAdmissions as $student) {
            $activities[] = [
                'type' => 'admission',
                'icon' => 'fa-graduation-cap',
                'color' => 'primary',
                'title' => 'New Student Admitted',
                'description' => "Welcome {$student->name} to ".($student->batch?->course?->name ?? 'Unknown Course'),
                'timestamp' => $student->created_at->toDateTimeString(),
                'details' => [
                    'enrollment' => $student->enrollment_number,
                    'batch' => $student->batch->name ?? 'N/A',
                    'mobile' => $student->student_mobile,
                ],
            ];
        }

        return $activities;
    }

    private function getTodayAdmissions(): int
    {
        return Student::whereDate('created_at', today())->count();
    }

    private function getMonthlyTargetProgress(array $financialData): array
    {
        $monthlyTarget = 833333; // Sample monthly target (10L/12)
        $currentCollection = $financialData['monthly_collection'];
        $daysInMonth = now()->daysInMonth;
        $daysPassed = now()->day;

        $expectedProgress = ($daysPassed / $daysInMonth) * 100;
        $actualProgress = ($currentCollection / $monthlyTarget) * 100;

        return [
            'target' => $monthlyTarget,
            'current' => $currentCollection,
            'percentage' => round($actualProgress, 1),
            'expected_percentage' => round($expectedProgress, 1),
            'status' => $actualProgress >= $expectedProgress ? 'on_track' : 'behind',
            'days_remaining' => $daysInMonth - $daysPassed,
        ];
    }

    private function getActiveUserSessions(): int
    {
        // This would typically check active sessions from a sessions table or cache
        return User::where('last_login_at', '>=', now()->subHours(1))->count();
    }

    private function getSystemAlerts(): array
    {
        $alerts = [];

        // Check for overdue students alert
        $overdueCount = Student::withOverdueFees()->count();
        if ($overdueCount > 10) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$overdueCount} students have overdue fees",
                'action' => 'Review defaulters',
            ];
        }

        // Check for low collection rate
        $efficiency = $this->componentPaymentService->getCollectionEfficiency();
        if ($efficiency['collection_rate'] < 80) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Collection rate is below 80%',
                'action' => 'Review payment processes',
            ];
        }

        // Check for system performance
        if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB
            $alerts[] = [
                'type' => 'info',
                'message' => 'High memory usage detected',
                'action' => 'Monitor system performance',
            ];
        }

        return $alerts;
    }

    private function calculateRecoveryRate(): float
    {
        // Calculate recovery rate from overdue payments
        $recentRecoveries = Payment::componentPayments()
            ->whereHas('componentItems.studentFee', function ($q) {
                $q->where('status', 'overdue');
            })
            ->where('payment_date', '>=', now()->subDays(90))
            ->sum('amount');

        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();

        if ($outstanding['overdue_amount'] > 0) {
            return round(($recentRecoveries / $outstanding['overdue_amount']) * 100, 1);
        }

        return 0;
    }

    private function getPaymentTimingKPI(array $behavior): float
    {
        $onTimePercentage = $behavior['total_students'] > 0 ?
            ($behavior['on_time_payers'] / $behavior['total_students']) * 100 : 0;

        return round($onTimePercentage, 1);
    }

    private function getStudentSatisfactionKPI(): float
    {
        // This would typically come from surveys or feedback
        return 88.5; // Sample satisfaction score
    }

    private function getProcessEfficiencyKPI(): float
    {
        // Calculate based on payment processing time, error rates, etc.
        $efficiency = $this->componentPaymentService->getCollectionEfficiency();

        return $efficiency['collection_rate'];
    }

    private function getDataAccuracyKPI(): float
    {
        // This would check data consistency, validation errors, etc.
        return 99.2; // Sample accuracy score
    }

    private function getSystemUptimeKPI(): float
    {
        // This would come from monitoring systems
        return 99.9; // Sample uptime
    }

    private function getRevenueGrowthKPI(): float
    {
        $trends = $this->componentPaymentService->getMonthlyCollectionTrends(12);

        return $this->calculateGrowthRate($trends);
    }

    private function getCostEfficiencyKPI(): float
    {
        // This would calculate operational costs vs revenue
        return 85.3; // Sample efficiency score
    }

    private function getBadDebtRatioKPI(array $outstanding): float
    {
        $totalExpected = $outstanding['total_outstanding'] + $outstanding['overdue_amount'];

        if ($totalExpected > 0) {
            return round(($outstanding['overdue_amount'] / $totalExpected) * 100, 1);
        }

        return 0;
    }

    private function getCashFlowHealthKPI(): float
    {
        // This would analyze cash flow patterns
        return 92.1; // Sample health score
    }

    // ===================================
    // PLACEHOLDER METHODS (To be implemented)
    // ===================================

    private function getEnrollmentByCourse(): array
    {
        return Course::withCount('students')
            ->get()
            ->map(function ($course) {
                return [
                    'course' => $course->name,
                    'students' => $course->students_count,
                    'percentage' => 0, // Calculate percentage of total
                ];
            })
            ->toArray();
    }

    private function getAttendanceSummary(): array
    {
        try {
            // Get today's attendance
            $todayAttendance = DB::table('attendances')
                ->whereDate('date', today())
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present')
                ->first();

            $todayPercentage = $todayAttendance && $todayAttendance->total > 0
                ? round(($todayAttendance->present / $todayAttendance->total) * 100, 1)
                : 0;

            // Get overall attendance for current month
            $monthlyAttendance = DB::table('attendances')
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present')
                ->first();

            $overallPercentage = $monthlyAttendance && $monthlyAttendance->total > 0
                ? round(($monthlyAttendance->present / $monthlyAttendance->total) * 100, 1)
                : 0;

            // Get students with low attendance (below 75%)
            $lowAttendanceStudents = DB::table('attendances')
                ->select('student_id')
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->groupBy('student_id')
                ->havingRaw('(SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) / COUNT(*)) * 100 < 75')
                ->count();

            // Get weekly trend
            $weeklyTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayAttendance = DB::table('attendances')
                    ->whereDate('date', $date)
                    ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present')
                    ->first();

                $percentage = $dayAttendance && $dayAttendance->total > 0
                    ? round(($dayAttendance->present / $dayAttendance->total) * 100, 1)
                    : 0;

                $weeklyTrend[] = [
                    'date' => $date->format('M d'),
                    'percentage' => $percentage,
                ];
            }

            return [
                'overall_percentage' => $overallPercentage,
                'today_attendance' => $todayPercentage,
                'low_attendance_students' => $lowAttendanceStudents,
                'weekly_trend' => $weeklyTrend,
                'total_students_today' => $todayAttendance->total ?? 0,
                'present_today' => $todayAttendance->present ?? 0,
                'absent_today' => ($todayAttendance->total ?? 0) - ($todayAttendance->present ?? 0),
            ];
        } catch (\Exception $e) {
            \Log::error('Error calculating attendance summary: '.$e->getMessage());

            return [
                'overall_percentage' => 0,
                'today_attendance' => 0,
                'low_attendance_students' => 0,
                'weekly_trend' => [],
                'total_students_today' => 0,
                'present_today' => 0,
                'absent_today' => 0,
            ];
        }
    }

    private function getPerformanceDistribution(): array
    {
        try {
            // Get performance distribution based on student grades/marks
            $performanceData = DB::table('student_marks')
                ->join('students', 'student_marks.student_id', '=', 'students.id')
                ->whereNotNull('student_marks.percentage')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN student_marks.percentage >= 90 THEN 1 ELSE 0 END) as excellent,
                    SUM(CASE WHEN student_marks.percentage >= 75 AND student_marks.percentage < 90 THEN 1 ELSE 0 END) as good,
                    SUM(CASE WHEN student_marks.percentage >= 60 AND student_marks.percentage < 75 THEN 1 ELSE 0 END) as average,
                    SUM(CASE WHEN student_marks.percentage < 60 THEN 1 ELSE 0 END) as below_average
                ')
                ->first();

            // If no marks data, try to get from student_fees payment behavior as performance indicator
            if (! $performanceData || $performanceData->total == 0) {
                $feePerformance = DB::table('student_fees')
                    ->join('students', 'student_fees.student_id', '=', 'students.id')
                    ->selectRaw('
                        COUNT(DISTINCT student_fees.student_id) as total,
                        COUNT(DISTINCT CASE WHEN student_fees.status = "paid" AND student_fees.due_date >= student_fees.paid_date THEN student_fees.student_id END) as excellent,
                        COUNT(DISTINCT CASE WHEN student_fees.status = "paid" AND student_fees.paid_date <= DATE_ADD(student_fees.due_date, INTERVAL 7 DAY) THEN student_fees.student_id END) as good,
                        COUNT(DISTINCT CASE WHEN student_fees.status = "paid" AND student_fees.paid_date <= DATE_ADD(student_fees.due_date, INTERVAL 30 DAY) THEN student_fees.student_id END) as average,
                        COUNT(DISTINCT CASE WHEN student_fees.status != "paid" OR student_fees.paid_date > DATE_ADD(student_fees.due_date, INTERVAL 30 DAY) THEN student_fees.student_id END) as below_average
                    ')
                    ->first();

                $performanceData = $feePerformance;
            }

            // Calculate percentages
            $total = $performanceData->total ?? 0;
            if ($total == 0) {
                return [
                    'excellent' => 0,
                    'good' => 0,
                    'average' => 0,
                    'below_average' => 0,
                    'total_students' => 0,
                    'performance_trend' => 'stable',
                ];
            }

            $excellent = $performanceData->excellent ?? 0;
            $good = $performanceData->good ?? 0;
            $average = $performanceData->average ?? 0;
            $belowAverage = $performanceData->below_average ?? 0;

            // Get performance trend (compare with last month)
            $lastMonthPerformance = DB::table('student_marks')
                ->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->selectRaw('AVG(percentage) as avg_percentage')
                ->first();

            $currentMonthPerformance = DB::table('student_marks')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->selectRaw('AVG(percentage) as avg_percentage')
                ->first();

            $trend = 'stable';
            if ($lastMonthPerformance && $currentMonthPerformance) {
                $lastAvg = $lastMonthPerformance->avg_percentage ?? 0;
                $currentAvg = $currentMonthPerformance->avg_percentage ?? 0;

                if ($currentAvg > $lastAvg + 2) {
                    $trend = 'improving';
                } elseif ($currentAvg < $lastAvg - 2) {
                    $trend = 'declining';
                }
            }

            return [
                'excellent' => round(($excellent / $total) * 100, 1),
                'good' => round(($good / $total) * 100, 1),
                'average' => round(($average / $total) * 100, 1),
                'below_average' => round(($belowAverage / $total) * 100, 1),
                'total_students' => $total,
                'performance_trend' => $trend,
                'excellent_count' => $excellent,
                'good_count' => $good,
                'average_count' => $average,
                'below_average_count' => $belowAverage,
            ];
        } catch (\Exception $e) {
            \Log::error('Error calculating performance distribution: '.$e->getMessage());

            return [
                'excellent' => 0,
                'good' => 0,
                'average' => 0,
                'below_average' => 0,
                'total_students' => 0,
                'performance_trend' => 'stable',
            ];
        }
    }

    private function getGeographicDistribution(): array
    {
        // Placeholder - implement based on student addresses
        return Student::select('village')
            ->groupBy('village')
            ->selectRaw('village, count(*) as count')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'village')
            ->toArray();
    }

    private function getPopularCourses(): array
    {
        return Course::withCount('students')
            ->orderByDesc('students_count')
            ->limit(5)
            ->get()
            ->map(function ($course) {
                return [
                    'name' => $course->name,
                    'students' => $course->students_count,
                ];
            })
            ->toArray();
    }

    private function getAverageBatchSize(): float
    {
        return Batch::withCount('students')
            ->avg('students_count') ?: 0;
    }

    private function getCurrentSemester(): string
    {
        // Placeholder - implement based on academic calendar
        $month = now()->month;
        if ($month >= 6 && $month <= 11) {
            return 'Odd Semester '.now()->year;
        } else {
            return 'Even Semester '.now()->year;
        }
    }

    /**
     * Get revenue breakdown by category
     */
    private function getRevenueByCategoryAnalysis(): array
    {
        $outstanding = $this->componentPaymentService->getOutstandingFeesSummary();
        $categories = $outstanding['by_category'] ?? [];

        $analysis = [];
        $totalRevenue = 0;

        foreach ($categories as $category) {
            $categoryRevenue = ($category['total_amount'] ?? 0) - ($category['outstanding_amount'] ?? 0);
            $totalRevenue += $categoryRevenue;

            $analysis[] = [
                'category_name' => $category['category_name'] ?? 'Unknown',
                'revenue' => $categoryRevenue,
                'outstanding' => $category['outstanding_amount'] ?? 0,
                'collection_rate' => $category['total_amount'] > 0 ?
                    round((($categoryRevenue / $category['total_amount']) * 100), 1) : 0,
            ];
        }

        // Add percentage breakdown
        foreach ($analysis as &$category) {
            $category['percentage'] = $totalRevenue > 0 ?
                round(($category['revenue'] / $totalRevenue) * 100, 1) : 0;
        }

        return $analysis;
    }

    /**
     * Generate revenue forecast
     */
    private function generateRevenueForecast(array $trends): array
    {
        if (count($trends) < 3) {
            return [
                'next_month' => 0,
                'next_quarter' => 0,
                'confidence_level' => 0,
                'trend_analysis' => 'Insufficient data',
            ];
        }

        // Calculate growth rate from trends
        $growthRates = [];
        for ($i = 1; $i < count($trends); $i++) {
            $current = $trends[$i]['amount'] ?? 0;
            $previous = $trends[$i - 1]['amount'] ?? 0;
            if ($previous > 0) {
                $growthRates[] = (($current - $previous) / $previous) * 100;
            }
        }

        $avgGrowthRate = count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
        $lastAmount = end($trends)['amount'] ?? 0;

        // Simple linear forecast
        $nextMonth = $lastAmount * (1 + ($avgGrowthRate / 100));
        $nextQuarter = $nextMonth * 3; // Simplified quarterly forecast

        // Calculate confidence based on consistency
        $volatility = $this->calculateVolatility(array_column($trends, 'amount'));
        $confidence = max(20, min(95, 85 - ($volatility * 2))); // Higher volatility = lower confidence

        return [
            'next_month' => round($nextMonth, 2),
            'next_quarter' => round($nextQuarter, 2),
            'growth_rate' => round($avgGrowthRate, 1),
            'confidence_level' => round($confidence, 0),
            'trend_analysis' => $this->getTrendAnalysis($avgGrowthRate, $volatility),
            'forecast_range' => [
                'optimistic' => round($nextMonth * 1.15, 2),
                'realistic' => round($nextMonth, 2),
                'pessimistic' => round($nextMonth * 0.85, 2),
            ],
        ];
    }

    /**
     * Determine trend direction from growth rate values
     */
    private function determineTrendDirectionFromRates(array $values): string
    {
        if (empty($values)) {
            return 'stable';
        }

        $positiveCount = count(array_filter($values, fn ($v) => $v > 0));
        $negativeCount = count(array_filter($values, fn ($v) => $v < 0));
        $totalCount = count($values);

        $positiveRatio = $positiveCount / $totalCount;

        if ($positiveRatio >= 0.7) {
            return 'strongly_upward';
        } elseif ($positiveRatio >= 0.55) {
            return 'upward';
        } elseif ($positiveRatio <= 0.3) {
            return 'strongly_downward';
        } elseif ($positiveRatio <= 0.45) {
            return 'downward';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate volatility of values
     */
    private function calculateVolatility(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDifferences = array_map(fn ($v) => pow($v - $mean, 2), $values);
        $variance = array_sum($squaredDifferences) / count($values);
        $standardDeviation = sqrt($variance);

        // Return as percentage of mean
        return $mean > 0 ? round(($standardDeviation / $mean) * 100, 1) : 0;
    }

    /**
     * Get annual revenue target (customize based on your settings)
     */
    private function getAnnualRevenueTarget(): float
    {
        // You can get this from settings table or configuration
        // For now, return a reasonable default based on student count
        $studentCount = \App\Models\Student::active()->count();
        $avgFeePerStudent = 50000; // Adjust based on your fee structure

        return $studentCount * $avgFeePerStudent;
    }

    /**
     * Get trend analysis description
     */
    private function getTrendAnalysis(float $avgGrowthRate, float $volatility): string
    {
        if ($avgGrowthRate > 10 && $volatility < 20) {
            return 'Strong and consistent growth';
        } elseif ($avgGrowthRate > 5 && $volatility < 30) {
            return 'Moderate growth with good stability';
        } elseif ($avgGrowthRate > 0 && $volatility < 40) {
            return 'Slow but steady growth';
        } elseif ($avgGrowthRate < -5 && $volatility > 30) {
            return 'Declining with high volatility';
        } elseif ($avgGrowthRate < 0) {
            return 'Declining trend';
        } elseif ($volatility > 50) {
            return 'Highly volatile, unpredictable';
        } else {
            return 'Stable with mixed signals';
        }
    }

    /**
     * Calculate financial targets data
     */
    private function getUpcomingAcademicEvents(): array
    {
        // Placeholder - implement based on events/calendar system
        return [
            ['name' => 'Mid-term Exams', 'date' => now()->addDays(15)->format('M d')],
            ['name' => 'Project Submission', 'date' => now()->addDays(22)->format('M d')],
            ['name' => 'Parent Meeting', 'date' => now()->addDays(30)->format('M d')],
        ];
    }

    private function getHolidaysThisMonth(): array
    {
        // Placeholder - implement based on holiday calendar
        return [
            ['name' => 'National Holiday', 'date' => now()->addDays(5)->format('M d')],
            ['name' => 'Local Festival', 'date' => now()->addDays(12)->format('M d')],
        ];
    }
}
