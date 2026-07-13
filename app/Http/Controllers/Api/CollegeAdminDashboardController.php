<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\MissingAcademicYearException;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CollegeAdminDashboardController extends Controller
{
    /**
     * Main dashboard view with complete data
     */
    public function index()
    {
        $user = auth()->user();
        $dashboard_data = $this->getDashboardData();

        return view('admin.dashboard.college-admin', compact('dashboard_data'));
    }

    /**
     * Get complete dashboard data
     */
    public function getDashboardData()
    {
        $user = auth()->user();
        $today = now();

        $cacheKey = "api_college_admin_dashboard_user_{$user->id}";

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            try {
                $academicYear = app(AcademicYearService::class)->getCurrentAcademicYear()->name;
            } catch (MissingAcademicYearException $e) {
                $academicYear = 'No Active Year';
            }

            return [
                'academic_year' => $academicYear,
                'my_students_count' => $this->getMyStudentsCount(),
                'students_growth' => $this->getStudentsGrowth(),
                'my_collections' => $this->getMyCollections(),
                'collections_growth' => $this->getCollectionsGrowth(),
                'avg_attendance' => $this->getCurrentAttendanceRate(),
                'attendance_trend' => $this->getAttendanceTrend(),
                'my_activities_count' => $this->getMyActivitiesCount(),
                'last_activity_time' => $this->getLastActivityTime(),
                'my_activities' => $this->getMyActivities(),
                'attendance_stats' => $this->getAttendanceStats(),
                'payment_trends' => $this->getPaymentTrends(),
                'attendance_chart' => $this->getAttendanceChart(),
                'payment_modes' => $this->getPaymentModesData(),
                'pending_collections' => $this->getPendingCollections(),
                'birthdays' => $this->getBirthdayData(),
                'enquiry_stats' => $this->getEnquiryStats(),
            ];
        });

        // Add dynamic fields that shouldn't be cached for 5 minutes
        $data['current_time'] = $today->format('H:i:s');
        $data['current_date'] = $today->format('d M Y');
        $data['user_name'] = $user->name;

        return $data;
    }

    /**
     * Get user-specific student count
     */
    private function getMyStudentsCount()
    {
        // Adjust this based on your user-student relationship
        // Example: if users are assigned to specific batches or courses
        return Student::where('status', 'active')->count();
    }

    /**
     * Get user's recent activities
     */
    private function getMyActivities()
    {
        $user = auth()->user();
        $activities = collect();

        // Recent payments by this user
        $recentPayments = Payment::withoutGlobalScope('academic_year')
            ->with('student')
            ->where('payment_type', 'component')
            ->latest()
            ->limit(15)
            ->get();

        foreach ($recentPayments as $payment) {
            $activities->push([
                'type' => 'payment',
                'icon' => 'money-bill-wave',
                'description' => 'Payment collected from '.($payment->student->name ?? 'Student'),
                'student_name' => $payment->student->name ?? 'Unknown',
                'amount' => $payment->amount,
                'created_at' => $payment->created_at,
            ]);
        }

        return $activities->sortByDesc('created_at')->values()->toArray();
    }

    /**
     * Get attendance statistics breakdown
     */
    private function getAttendanceStats()
    {
        // Get all students' attendance percentages
        $students = Student::where('status', 'active')->get();
        $stats = ['excellent' => 0, 'good' => 0, 'average' => 0, 'poor' => 0];

        foreach ($students as $student) {
            $attendanceRate = $this->getStudentAttendanceRate($student->id);

            if ($attendanceRate >= 90) {
                $stats['excellent']++;
            } elseif ($attendanceRate >= 75) {
                $stats['good']++;
            } elseif ($attendanceRate >= 60) {
                $stats['average']++;
            } else {
                $stats['poor']++;
            }
        }

        $total = array_sum($stats);

        return [
            'excellent' => $total > 0 ? round(($stats['excellent'] / $total) * 100, 1) : 0,
            'good' => $total > 0 ? round(($stats['good'] / $total) * 100, 1) : 0,
            'average' => $total > 0 ? round(($stats['average'] / $total) * 100, 1) : 0,
            'poor' => $total > 0 ? round(($stats['poor'] / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get payment trends for chart
     */
    private function getPaymentTrends()
    {
        $user = auth()->user();
        $trends = [
            'labels' => [],
            'amounts' => [],
            'counts' => [],
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $dayPayments = Payment::withoutGlobalScope('academic_year')
                ->where('payment_type', 'component')
                ->whereDate('payment_date', $date)
                ->get();

            $trends['labels'][] = $date->format('M d');
            $trends['amounts'][] = $dayPayments->sum('amount');
            $trends['counts'][] = $dayPayments->count();
        }

        return $trends;
    }

    private function getBirthdayData()
    {
        $today = now();

        return [
            'today' => $this->getStudentsWithBirthdayOn($today),
            'tomorrow' => $this->getStudentsWithBirthdayOn(now()->addDay()),
            'upcoming_3_days' => $this->getStudentsWithBirthdayInRange(now()->addDays(2), now()->addDays(4)),
            'last_3_days' => $this->getStudentsWithBirthdayInRange(now()->subDays(3), now()->subDay()),
        ];
    }

    private function getStudentsWithBirthdayOn($date)
    {
        return Student::where('status', 'active')
            ->whereMonth('dob', $date->month)
            ->whereDay('dob', $date->day)
            ->with(['batch.course'])
            ->get();
    }

    private function getStudentsWithBirthdayInRange($start, $end)
    {
        $query = Student::where('status', 'active')
            ->with(['batch.course']);

        $startMonth = $start->month;
        $startDay = $start->day;
        $endMonth = $end->month;
        $endDay = $end->day;

        if ($startMonth == $endMonth) {
            $query->whereMonth('dob', $startMonth)
                ->whereBetween(DB::raw('DAY(dob)'), [$startDay, $endDay]);
        } else {
            $query->where(function ($q) use ($startMonth, $startDay, $endMonth, $endDay) {
                $q->where(function ($sub) use ($startMonth, $startDay) {
                    $sub->whereMonth('dob', $startMonth)
                        ->where(DB::raw('DAY(dob)'), '>=', $startDay);
                })->orWhere(function ($sub) use ($endMonth, $endDay) {
                    $sub->whereMonth('dob', $endMonth)
                        ->where(DB::raw('DAY(dob)'), '<=', $endDay);
                });
            });
        }

        return $query->get();
    }

    // ==============================================
    // EXISTING API METHODS (Enhanced)
    // ==============================================

    public function academicMetrics()
    {
        $metrics = Cache::remember('api_college_admin_academic_metrics', now()->addMinutes(5), function () {
            return [
                'total_students' => Student::count(),
                'active_students' => Student::where('status', 'active')->count(),
                'graduated_students' => Student::where('status', 'graduated')->count(),
                'dropout_students' => Student::where('status', 'dropout')->count(),
                'total_courses' => Course::count(),
                'total_batches' => Batch::count(),
                'current_attendance_rate' => $this->getCurrentAttendanceRate(),
            ];
        });

        return response()->json($metrics);
    }

    public function enrollmentTrends()
    {
        $trends = Cache::remember('api_college_admin_enrollment_trends', now()->addMinutes(5), function () {
            return [
                'monthly_enrollments' => $this->getMonthlyEnrollments(),
                'course_wise_enrollments' => $this->getCourseWiseEnrollments(),
                'batch_performance' => $this->getBatchPerformance(),
            ];
        });

        return response()->json($trends);
    }

    /**
     * API: Get server time for real-time synchronization
     */
    public function getServerTime()
    {
        return response()->json([
            'timestamp' => now()->toISOString(),
            'timezone' => config('app.timezone'),
            'unix_timestamp' => now()->timestamp,
        ]);
    }

    /**
     * API: Get user's payment data for specific period with comparison
     */
    public function getMyPaymentData(Request $request)
    {
        try {
            $user = auth()->user();
            $period = $request->get('period', 'today');
            $includeComparison = $request->get('compare', true); // Add comparison by default

            $cacheKey = "api_college_admin_payment_data_{$user->id}_{$period}_{$includeComparison}";

            $response = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $period, $includeComparison) {
                $today = now();

                // Calculate current period date range
                $currentPeriod = $this->getDateRangeForPeriod($period, $today);

                // Calculate comparison period date range
                $comparisonPeriod = $this->getComparisonDateRange($period, $today);

                // Get payments for current period
                $currentPayments = Payment::where('payment_type', 'component')
                    ->where('created_by', $user->id)
                    ->whereBetween('payment_date', [$currentPeriod['start'], $currentPeriod['end']])
                    ->get();

                // Get payments for comparison period
                $comparisonPayments = Payment::where('payment_type', 'component')
                    ->where('created_by', $user->id)
                    ->whereBetween('payment_date', [$comparisonPeriod['start'], $comparisonPeriod['end']])
                    ->get();

                // Calculate current period stats
                $currentStats = $this->calculatePaymentStats($currentPayments);

                // Calculate comparison period stats
                $comparisonStats = $this->calculatePaymentStats($comparisonPayments);

                // Calculate growth metrics
                $growth = $this->calculateGrowthMetrics($currentStats, $comparisonStats, $period);

                // Build chart data for comparison
                $chartData = $this->buildComparisonChartData($currentStats, $comparisonStats, $period);

                $responseData = [
                    'success' => true,
                    'period' => $period,
                    'date_range' => [
                        'start' => $currentPeriod['start']->format('Y-m-d H:i:s'),
                        'end' => $currentPeriod['end']->format('Y-m-d H:i:s'),
                    ],
                    // Current period data (maintain backward compatibility)
                    'total_collected' => $currentStats['total_collected'],
                    'transactions_count' => $currentStats['transactions_count'],
                    'avg_amount' => $currentStats['avg_amount'],
                    'online_percentage' => $currentStats['online_percentage'],
                    'raw_data' => [
                        'payments_found' => $currentPayments->count(),
                        'user_id' => $user->id,
                        'query_executed' => true,
                    ],
                ];

                // Add comparison data if requested
                if ($includeComparison) {
                    $responseData['comparison'] = [
                        'period_label' => $this->getComparisonPeriodLabel($period),
                        'date_range' => [
                            'start' => $comparisonPeriod['start']->format('Y-m-d H:i:s'),
                            'end' => $comparisonPeriod['end']->format('Y-m-d H:i:s'),
                        ],
                        'total_collected' => $comparisonStats['total_collected'],
                        'transactions_count' => $comparisonStats['transactions_count'],
                        'avg_amount' => $comparisonStats['avg_amount'],
                        'online_percentage' => $comparisonStats['online_percentage'],
                    ];

                    $responseData['growth'] = $growth;
                    $responseData['chart_data'] = $chartData;
                }

                return $responseData;
            });

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Error in getMyPaymentData: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'period' => $request->get('period'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch payment data',
                'message' => $e->getMessage(),
                'period' => $request->get('period', 'today'),
            ], 500);
        }
    }

    /**
     * API: Get user's recent activities
     */
    public function getMyActivitiesApi(Request $request)
    {
        $user = auth()->user();
        $cacheKey = "api_college_admin_activities_{$user->id}";

        $activities = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->getMyActivities();
        });

        return response()->json([
            'success' => true,
            'activities' => $activities,
            'total' => count($activities),
        ]);
    }

    /**
     * API: Get attendance data for different views
     */
    public function getAttendanceData(Request $request)
    {
        $view = $request->get('view', 'daily');

        $cacheKey = "api_college_admin_attendance_data_{$view}";
        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($view) {
            switch ($view) {
                case 'weekly':
                    return $this->getWeeklyAttendanceChart();
                case 'monthly':
                    return $this->getMonthlyAttendanceChart();
                case 'daily':
                default:
                    return $this->getAttendanceChart();
            }
        });

        return response()->json([
            'success' => true,
            'view' => $view,
            'data' => $data,
        ]);
    }

    private function getBatchPerformance()
    {
        return Batch::withCount('students')->get()->map(function ($batch) {
            return [
                'name' => $batch->name,
                'student_count' => $batch->students_count,
                'course' => $batch->course->name ?? 'Unknown',
            ];
        });
    }
}
