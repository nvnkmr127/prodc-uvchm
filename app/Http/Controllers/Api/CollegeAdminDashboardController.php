<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Student, Course, Batch, Attendance, Payment, User};
use Illuminate\Http\Request;
use Carbon\Carbon;
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
        
        $academicYear = \App\Models\AcademicYear::where('is_current', true)->first()?->name ?? '2024-25';
        
        return [
            'academic_year' => $academicYear,
            'current_time' => $today->format('H:i:s'),
            'current_date' => $today->format('d M Y'),
            'user_name' => $user->name,
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
            'enquiry_stats' => $this->getEnquiryStats()
        ];
    }

    /**
     * Get real-time enquiry statistics
     */
    private function getEnquiryStats()
    {
        $today = today();
        return [
            'today_count' => \App\Models\Enquiry::whereDate('created_at', $today)->count(),
            'new_count' => \App\Models\Enquiry::where('status', 'New')->count(),
            'interested_count' => \App\Models\Enquiry::where('status', 'Interested')->count(),
            'followup_today' => \App\Models\Enquiry::whereDate('next_follow_up_date', $today)->count(),
            'admitted_count' => \App\Models\Enquiry::where('status', 'Admitted')->count(),
            'total_count' => \App\Models\Enquiry::count(),
        ];
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
     * Get students growth percentage
     */
    private function getStudentsGrowth()
    {
        $thisMonth = Student::whereMonth('admission_date', now()->month)
                           ->whereYear('admission_date', now()->year)
                           ->count();
        
        $lastMonth = Student::whereMonth('admission_date', now()->subMonth()->month)
                           ->whereYear('admission_date', now()->subMonth()->year)
                           ->count();
        
        if ($lastMonth == 0) return 0;
        
        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    /**
     * Get user's payment collections
     */
    private function getMyCollections()
    {
        $user = auth()->user();
        $today = now();
        
        $todayPayments = Payment::where('payment_type', 'component')
            ->where('created_by', $user->id)
            ->whereDate('payment_date', $today)
            ->get();
        
        $totalToday = $todayPayments->sum('amount');
        $transactionsToday = $todayPayments->count();
        $avgAmount = $transactionsToday > 0 ? ($totalToday / $transactionsToday) : 0;
        
        // Calculate online payment percentage
        $onlinePayments = $todayPayments->whereIn('payment_method', ['online', 'card', 'upi'])->count();
        $onlinePercentage = $transactionsToday > 0 ? ($onlinePayments / $transactionsToday * 100) : 0;
        
        return [
            'today' => $totalToday,
            'transactions' => $transactionsToday,
            'avg_amount' => $avgAmount,
            'online_percentage' => round($onlinePercentage, 1)
        ];
    }

    /**
     * Get collections growth
     */
    private function getCollectionsGrowth()
    {
        $user = auth()->user();
        $today = now();
        
        $todayAmount = Payment::where('payment_type', 'component')
            ->where('created_by', $user->id)
            ->whereDate('payment_date', $today)
            ->sum('amount');
        
        $yesterdayAmount = Payment::where('payment_type', 'component')
            ->where('created_by', $user->id)
            ->whereDate('payment_date', $today->copy()->subDay())
            ->sum('amount');
        
        if ($yesterdayAmount == 0) return 0;
        
        return round((($todayAmount - $yesterdayAmount) / $yesterdayAmount) * 100, 1);
    }

    /**
     * Get current attendance rate
     */
    private function getCurrentAttendanceRate()
    {
        $totalClasses = \App\Models\Attendance::whereDate('attendance_date', today())->count();
        if ($totalClasses === 0) return 0;
        
        $presentClasses = \App\Models\Attendance::whereDate('attendance_date', today())
                                  ->where('status', 'present')
                                  ->count();
        
        return round(($presentClasses / $totalClasses) * 100, 2);
    }

    /**
     * Get attendance trend
     */
    private function getAttendanceTrend()
    {
        $thisWeek = $this->getWeeklyAttendanceRate(now());
        $lastWeek = $this->getWeeklyAttendanceRate(now()->subWeek());
        
        if ($lastWeek == 0) return 0;
        
        return round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1);
    }

    /**
     * Get weekly attendance rate
     */
    private function getWeeklyAttendanceRate($week)
    {
        $startOfWeek = $week->copy()->startOfWeek();
        $endOfWeek = $week->copy()->endOfWeek();
        
        $totalClasses = \App\Models\Attendance::whereBetween('attendance_date', [$startOfWeek, $endOfWeek])->count();
        if ($totalClasses === 0) return 0;
        
        $presentClasses = \App\Models\Attendance::whereBetween('attendance_date', [$startOfWeek, $endOfWeek])
                                  ->where('status', 'present')
                                  ->count();
        
        return round(($presentClasses / $totalClasses) * 100, 2);
    }
    
    
    /**
 * API: Get payment modes distribution
 */
public function getPaymentModes(Request $request)
{
    try {
        $paymentModes = $this->getPaymentModesData(); // Call your private method

        return response()->json([
            'success' => true,
            'payment_modes' => $paymentModes
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in getPaymentModes API: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
/**
 * Rename your private method to avoid confusion
 */
private function getPaymentModesData()
{
    // Your existing getPaymentModes() code here
    $user = auth()->user();
    $today = now();

    // Get actual payment data
    $paymentModes = Payment::where('payment_type', 'component')
        ->where('created_by', $user->id)
        ->whereDate('payment_date', $today)
        ->selectRaw('payment_method, SUM(amount) as total')
        ->groupBy('payment_method')
        ->pluck('total', 'payment_method')
        ->toArray();

    // Debug log
    // Debug: Check if user has any payments at all
    $totalPayments = Payment::where('created_by', $user->id)->count();
    \Log::info("User {$user->id} has {$totalPayments} total payments");
    
    if ($totalPayments == 0) {
        \Log::info('No payments found, returning zero data');
        return [
            'labels' => ['Cash', 'Online', 'Card', 'UPI'],
            'values' => [0, 0, 0, 0]
        ];
    }

    // If no payments today, get last 7 days data for demo
    if (empty($paymentModes) || array_sum($paymentModes) == 0) {
        $paymentModes = Payment::where('payment_type', 'component')
            ->where('created_by', $user->id)
            ->whereBetween('payment_date', [now()->subDays(7), now()])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        \Log::info('Payment modes last 7 days:', $paymentModes);
    }

    // Ensure we have all payment methods
    $defaultModes = ['cash' => 0, 'online' => 0, 'card' => 0, 'upi' => 0];
    $paymentModes = array_merge($defaultModes, $paymentModes);

    $result = [
        'labels' => ['Cash', 'Online', 'Card', 'UPI'],
        'values' => [
            (float)($paymentModes['cash'] ?? 0),
            (float)($paymentModes['online'] ?? 0),
            (float)($paymentModes['card'] ?? 0),
            (float)($paymentModes['upi'] ?? 0),
        ]
    ];

    \Log::info('Final payment modes result:', $result);

    return $result;
}

    /**
     * Get user's activities count
     */
    private function getMyActivitiesCount()
    {
        $user = auth()->user();
        
        return Payment::where('payment_type', 'component')
            ->where('created_by', $user->id)
            ->whereDate('payment_date', today())
            ->count();
    }

    /**
     * Get last activity time
     */
    private function getLastActivityTime()
    {
        $user = auth()->user();
        
        $lastPayment = Payment::where('payment_type', 'component')
            ->where('created_by', $user->id)
            ->latest()
            ->first();
        
        return $lastPayment ? $lastPayment->created_at->diffForHumans() : 'No recent activity';
    }

    /**
     * Get user's recent activities
     */
    private function getMyActivities()
    {
        $user = auth()->user();
        $activities = collect();
        
        // Recent payments by this user
        $recentPayments = Payment::with('student')
            ->where('payment_type', 'component')
            ->where('created_by', $user->id)
            ->latest()
            ->limit(15)
            ->get();
            
        foreach ($recentPayments as $payment) {
            $activities->push([
                'type' => 'payment',
                'icon' => 'money-bill-wave',
                'description' => 'Payment collected from ' . ($payment->student->name ?? 'Student'),
                'student_name' => $payment->student->name ?? 'Unknown',
                'amount' => $payment->amount,
                'created_at' => $payment->created_at
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
     * Get student attendance rate
     */
    private function getStudentAttendanceRate($studentId)
    {
        $totalClasses = \App\Models\Attendance::where('student_id', $studentId)->count();
        if ($totalClasses === 0) return 0;
        
        $presentClasses = \App\Models\Attendance::where('student_id', $studentId)
                                  ->where('status', 'present')
                                  ->count();
        
        return round(($presentClasses / $totalClasses) * 100, 2);
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
            'counts' => []
        ];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $dayPayments = Payment::where('payment_type', 'component')
                ->where('created_by', $user->id)
                ->whereDate('payment_date', $date)
                ->get();
            
            $trends['labels'][] = $date->format('M d');
            $trends['amounts'][] = $dayPayments->sum('amount');
            $trends['counts'][] = $dayPayments->count();
        }
        
        return $trends;
    }

    /**
     * Get attendance chart data
     */
    private function getAttendanceChart()
    {
        $chart = [
            'labels' => [],
            'present' => [],
            'absent' => [],
            'late' => []
        ];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $attendanceData = \App\Models\Attendance::whereDate('attendance_date', $date)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');
            
            $chart['labels'][] = $date->format('M d');
            $chart['present'][] = $attendanceData['present'] ?? 0;
            $chart['absent'][] = $attendanceData['absent'] ?? 0;
            $chart['late'][] = $attendanceData['late'] ?? 0;
        }
        
        return $chart;
    }


    /**
     * Get pending collections
     */
    private function getPendingCollections()
    {
        return Student::with(['studentFees.feeCategory', 'batch.course'])
            ->whereHas('studentFees', function($query) {
                $query->whereIn('status', ['unpaid', 'partial']);
            })
            ->take(10)
            ->get()
            ->map(function($student) {
                $unpaidFees = $student->studentFees->whereIn('status', ['unpaid', 'partial']);
                $totalDue = $unpaidFees->sum(function($fee) {
                    return ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0);
                });
                
                return [
                    'student_name' => $student->name,
                    'course' => $student->batch->course->name ?? 'N/A',
                    'amount' => $totalDue ?: 0,
                    'due_date' => $unpaidFees->min('due_date') ? Carbon::parse($unpaidFees->min('due_date'))->format('M d') : 'N/A'
                ];
            })->toArray();
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
        $metrics = [
            'total_students' => Student::count(),
            'active_students' => Student::where('status', 'active')->count(),
            'graduated_students' => Student::where('status', 'graduated')->count(),
            'dropout_students' => Student::where('status', 'dropout')->count(),
            'total_courses' => Course::count(),
            'total_batches' => Batch::count(),
            'current_attendance_rate' => $this->getCurrentAttendanceRate(),
        ];
        
        return response()->json($metrics);
    }
    
    public function enrollmentTrends()
    {
        $trends = [
            'monthly_enrollments' => $this->getMonthlyEnrollments(),
            'course_wise_enrollments' => $this->getCourseWiseEnrollments(),
            'batch_performance' => $this->getBatchPerformance(),
        ];
        
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
            'unix_timestamp' => now()->timestamp
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
        
        $response = [
            'success' => true,
            'period' => $period,
            'date_range' => [
                'start' => $currentPeriod['start']->format('Y-m-d H:i:s'),
                'end' => $currentPeriod['end']->format('Y-m-d H:i:s')
            ],
            // Current period data (maintain backward compatibility)
            'total_collected' => $currentStats['total_collected'],
            'transactions_count' => $currentStats['transactions_count'],
            'avg_amount' => $currentStats['avg_amount'],
            'online_percentage' => $currentStats['online_percentage'],
            'raw_data' => [
                'payments_found' => $currentPayments->count(),
                'user_id' => $user->id,
                'query_executed' => true
            ]
        ];
        
        // Add comparison data if requested
        if ($includeComparison) {
            $response['comparison'] = [
                'period_label' => $this->getComparisonPeriodLabel($period),
                'date_range' => [
                    'start' => $comparisonPeriod['start']->format('Y-m-d H:i:s'),
                    'end' => $comparisonPeriod['end']->format('Y-m-d H:i:s')
                ],
                'total_collected' => $comparisonStats['total_collected'],
                'transactions_count' => $comparisonStats['transactions_count'],
                'avg_amount' => $comparisonStats['avg_amount'],
                'online_percentage' => $comparisonStats['online_percentage'],
            ];
            
            $response['growth'] = $growth;
            $response['chart_data'] = $chartData;
        }
        
        return response()->json($response);
        
    } catch (\Exception $e) {
        \Log::error('Error in getMyPaymentData: ' . $e->getMessage(), [
            'user_id' => auth()->id(),
            'period' => $request->get('period'),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch payment data',
            'message' => $e->getMessage(),
            'period' => $request->get('period', 'today')
        ], 500);
    }
}

    /**
     * API: Get user's recent activities
     */
    public function getMyActivitiesApi(Request $request)
    {
        $activities = $this->getMyActivities();
        
        return response()->json([
            'success' => true,
            'activities' => $activities,
            'total' => count($activities)
        ]);
    }

    /**
     * API: Get attendance data for different views
     */
    public function getAttendanceData(Request $request)
    {
        $view = $request->get('view', 'daily');
        
        switch ($view) {
            case 'weekly':
                $data = $this->getWeeklyAttendanceChart();
                break;
            case 'monthly':
                $data = $this->getMonthlyAttendanceChart();
                break;
            case 'daily':
            default:
                $data = $this->getAttendanceChart();
                break;
        }
        
        return response()->json([
            'success' => true,
            'view' => $view,
            'data' => $data
        ]);
    }
    
    /**
 * Get comparison date range based on current period
 */
private function getComparisonDateRange($period, $today)
{
    switch ($period) {
        case 'yesterday':
            // Compare with day before yesterday
            return [
                'start' => $today->copy()->subDays(2)->startOfDay(),
                'end' => $today->copy()->subDays(2)->endOfDay()
            ];
        case 'this_week':
            // Compare with last week
            return [
                'start' => $today->copy()->subWeek()->startOfWeek(),
                'end' => $today->copy()->subWeek()->endOfWeek()
            ];
        case 'last_7_days':
            // Compare with previous 7 days
            return [
                'start' => $today->copy()->subDays(14),
                'end' => $today->copy()->subDays(7)
            ];
        case 'this_month':
            // Compare with last month
            return [
                'start' => $today->copy()->subMonth()->startOfMonth(),
                'end' => $today->copy()->subMonth()->endOfMonth()
            ];
        case 'last_30_days':
            // Compare with previous 30 days
            return [
                'start' => $today->copy()->subDays(60),
                'end' => $today->copy()->subDays(30)
            ];
        case 'today':
        default:
            // Compare with yesterday
            return [
                'start' => $today->copy()->subDay()->startOfDay(),
                'end' => $today->copy()->subDay()->endOfDay()
            ];
    }
}

/**
 * Calculate payment statistics from payment collection
 */
private function calculatePaymentStats($payments)
{
    $totalCollected = $payments->sum('amount');
    $transactionsCount = $payments->count();
    $avgAmount = $transactionsCount > 0 ? ($totalCollected / $transactionsCount) : 0;
    
    // Calculate online payment percentage
    $onlinePayments = $payments->whereIn('payment_method', ['online', 'card', 'upi'])->count();
    $onlinePercentage = $transactionsCount > 0 ? ($onlinePayments / $transactionsCount * 100) : 0;
    
    return [
        'total_collected' => $totalCollected,
        'transactions_count' => $transactionsCount,
        'avg_amount' => round($avgAmount, 2),
        'online_percentage' => round($onlinePercentage, 1),
    ];
}

/**
 * Calculate growth metrics between current and comparison periods
 */
private function calculateGrowthMetrics($current, $comparison, $period)
{
    $amountGrowth = 0;
    $transactionGrowth = 0;
    $avgAmountGrowth = 0;
    
    if ($comparison['total_collected'] > 0) {
        $amountGrowth = (($current['total_collected'] - $comparison['total_collected']) / $comparison['total_collected']) * 100;
    } elseif ($current['total_collected'] > 0) {
        $amountGrowth = 100; // 100% growth from zero
    }
    
    if ($comparison['transactions_count'] > 0) {
        $transactionGrowth = (($current['transactions_count'] - $comparison['transactions_count']) / $comparison['transactions_count']) * 100;
    } elseif ($current['transactions_count'] > 0) {
        $transactionGrowth = 100;
    }
    
    if ($comparison['avg_amount'] > 0) {
        $avgAmountGrowth = (($current['avg_amount'] - $comparison['avg_amount']) / $comparison['avg_amount']) * 100;
    } elseif ($current['avg_amount'] > 0) {
        $avgAmountGrowth = 100;
    }
    
    return [
        'amount_growth' => round($amountGrowth, 1),
        'transaction_growth' => round($transactionGrowth, 1),
        'avg_amount_growth' => round($avgAmountGrowth, 1),
        'online_percentage_change' => round($current['online_percentage'] - $comparison['online_percentage'], 1),
        'comparison_label' => $this->getComparisonPeriodLabel($period),
        'is_positive' => $amountGrowth >= 0,
        'summary' => $this->getGrowthSummary($amountGrowth, $transactionGrowth)
    ];
}



/**
 * Build chart data for comparison visualization
 */
private function buildComparisonChartData($current, $comparison, $period)
{
    $labels = $this->getChartLabels($period);
    
    return [
        'labels' => $labels,
        'amounts' => [
            $comparison['total_collected'],
            $current['total_collected']
        ],
        'counts' => [
            $comparison['transactions_count'],
            $current['transactions_count']
        ],
        'avg_amounts' => [
            $comparison['avg_amount'],
            $current['avg_amount']
        ],
        'online_percentages' => [
            $comparison['online_percentage'],
            $current['online_percentage']
        ]
    ];
}

/**
 * Get chart labels for different periods
 */
private function getChartLabels($period)
{
    switch ($period) {
        case 'yesterday':
            return ['Day Before', 'Yesterday'];
        case 'this_week':
            return ['Last Week', 'This Week'];
        case 'last_7_days':
            return ['Previous 7 Days', 'Last 7 Days'];
        case 'this_month':
            return ['Last Month', 'This Month'];
        case 'last_30_days':
            return ['Previous 30 Days', 'Last 30 Days'];
        case 'today':
        default:
            return ['Yesterday', 'Today'];
    }
}

/**
 * Get comparison period label
 */
private function getComparisonPeriodLabel($period)
{
    switch ($period) {
        case 'yesterday':
            return 'vs Day Before';
        case 'this_week':
            return 'vs Last Week';
        case 'last_7_days':
            return 'vs Previous 7 Days';
        case 'this_month':
            return 'vs Last Month';
        case 'last_30_days':
            return 'vs Previous 30 Days';
        case 'today':
        default:
            return 'vs Yesterday';
    }
}

/**
 * Get growth summary text
 */
private function getGrowthSummary($amountGrowth, $transactionGrowth)
{
    if ($amountGrowth > 0 && $transactionGrowth > 0) {
        return 'Both revenue and transactions increased';
    } elseif ($amountGrowth > 0 && $transactionGrowth <= 0) {
        return 'Revenue increased but fewer transactions';
    } elseif ($amountGrowth <= 0 && $transactionGrowth > 0) {
        return 'More transactions but lower revenue';
    } else {
        return 'Both revenue and transactions decreased';
    }
}

/**
 * Get date range for a given period
 */
private function getDateRangeForPeriod($period, $today)
{
    switch ($period) {
        case 'yesterday':
            return [
                'start' => $today->copy()->subDay()->startOfDay(),
                'end' => $today->copy()->subDay()->endOfDay()
            ];
        case 'this_week':
            return [
                'start' => $today->copy()->startOfWeek(),
                'end' => $today->copy()->endOfWeek()
            ];
        case 'last_7_days':
            return [
                'start' => $today->copy()->subDays(7),
                'end' => $today->copy()
            ];
        case 'this_month':
            return [
                'start' => $today->copy()->startOfMonth(),
                'end' => $today->copy()->endOfMonth()
            ];
        case 'last_30_days':
            return [
                'start' => $today->copy()->subDays(30),
                'end' => $today->copy()
            ];
        case 'today':
        default:
            return [
                'start' => $today->copy()->startOfDay(),
                'end' => $today->copy()->endOfDay()
            ];
    }
}

    private function getWeeklyAttendanceChart()
    {
        $chart = ['labels' => [], 'present' => [], 'absent' => [], 'late' => []];
        
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            $chart['labels'][] = $weekStart->format('M d') . ' - ' . $weekEnd->format('d');
            
            $weeklyData = \App\Models\Attendance::whereBetween('attendance_date', [$weekStart, $weekEnd])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');
            
            $chart['present'][] = $weeklyData['present'] ?? 0;
            $chart['absent'][] = $weeklyData['absent'] ?? 0;
            $chart['late'][] = $weeklyData['late'] ?? 0;
        }
        
        return $chart;
    }

    private function getMonthlyAttendanceChart()
    {
        $chart = ['labels' => [], 'present' => [], 'absent' => [], 'late' => []];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $chart['labels'][] = $month->format('M Y');
            
            $monthlyData = \App\Models\Attendance::whereYear('attendance_date', $month->year)
                ->whereMonth('attendance_date', $month->month)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');
            
            $chart['present'][] = $monthlyData['present'] ?? 0;
            $chart['absent'][] = $monthlyData['absent'] ?? 0;
            $chart['late'][] = $monthlyData['late'] ?? 0;
        }
        
        return $chart;
    }
    
    private function getMonthlyEnrollments()
    {
        return Student::selectRaw('MONTH(admission_date) as month, COUNT(*) as count')
                     ->whereYear('admission_date', now()->year)
                     ->groupBy('month')
                     ->pluck('count', 'month');
    }
    
    private function getCourseWiseEnrollments()
    {
        return Student::with('batch.course')
                     ->get()
                     ->groupBy('batch.course.name')
                     ->map->count();
    }
    
    private function getBatchPerformance()
    {
        return Batch::withCount('students')->get()->map(function($batch) {
            return [
                'name' => $batch->name,
                'student_count' => $batch->students_count,
                'course' => $batch->course->name ?? 'Unknown'
            ];
        });
    }
}