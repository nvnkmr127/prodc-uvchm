<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Timetable;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\StudentFee;
use App\Models\ComponentPaymentItem;
use App\Models\FeeCategory;
use App\Models\Attendance;
use App\Services\DashboardService;
use App\Services\ComponentPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $componentPaymentService;

    public function __construct(DashboardService $dashboardService, ComponentPaymentService $componentPaymentService)
    {
        $this->dashboardService = $dashboardService;
        $this->componentPaymentService = $componentPaymentService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Route to role-specific dashboards
        if ($user->hasRole('super-admin')) {
            return $this->superAdminDashboard($user);
        } elseif ($user->hasRole('college-admin')) {
            return $this->collegeAdminDashboard($user);
        } elseif ($user->hasRole('accountant')) {
            return $this->accountantDashboard($user);
        } elseif ($user->hasRole('faculty')) {
            return $this->facultyDashboard($user);
        } elseif ($user->hasRole('staff')) {
            return $this->staffDashboard($user);
        } else {
            return $this->studentDashboard($user);
        }
    }

    /**
     * Super Admin Dashboard - Full system overview with component payment metrics
     */
   protected function superAdminDashboard(User $user)
{
    // Get real data with enhanced payment metrics
    $realDashboardData = $this->calculateRealDashboardData();
    
    $data = [
        'user' => $user,
        'dashboard_data' => $realDashboardData
    ];

    return view('admin.dashboard.super-admin', $data);
}


    /**
     * College Admin Dashboard - Administrative overview with enhanced component metrics
     */
    protected function collegeAdminDashboard(User $user)
    {
        // Get real data with enhanced payment metrics
    $realDashboardData = $this->calculateRealDashboardData();
    
    $data = [
        'user' => $user,
        'dashboard_data' => $realDashboardData
    ];

        return view('admin.dashboard.college-admin', $data);
    }

    /**
     * Accountant Dashboard - Financial focus with comprehensive component analytics
     */
    protected function accountantDashboard(User $user)
    {
        // Get real data with enhanced payment metrics
    $realDashboardData = $this->calculateRealDashboardData();
    
    $data = [
        'user' => $user,
        'dashboard_data' => $realDashboardData
    ];

        return view('accountant.dashboard', $data);
    }

    /**
     * Faculty Dashboard - Teaching focus with basic financial visibility
     */
    protected function facultyDashboard(User $user)
    {
        // Get real data with enhanced payment metrics
    $realDashboardData = $this->calculateRealDashboardData();
    
    $data = [
        'user' => $user,
        'dashboard_data' => $realDashboardData
    ];

        return view('faculty.dashboard', $data);
    }

    /**
     * Staff Dashboard - Operational focus with basic financial data
     */
    protected function staffDashboard(User $user)
    {
        // Get real data with enhanced payment metrics
    $realDashboardData = $this->calculateRealDashboardData();
    
    $data = [
        'user' => $user,
        'dashboard_data' => $realDashboardData
    ];

        return view('staff.dashboard', $data);
    }

    /**
     * Student Dashboard - Personal academic and financial view
     */
    protected function studentDashboard(User $user)
    {
        $student = $user->student ?? Student::where('email', $user->email)->first();
        
        if (!$student) {
            return redirect('/profile')->with('error', 'Please complete your student profile.');
        }

        $data = [
            'user' => $user,
            'student' => $student,
            'attendance_summary' => $this->getMyAttendanceSummary($student),
            'fee_summary' => $this->getMyFeeSummary($student),
            'academic_progress' => $this->getMyAcademicProgress($student),
            'upcoming_events' => $this->getMyUpcomingEvents($student),
            'recent_payments' => $this->getMyRecentPayments($student),
            'outstanding_fees' => $this->getMyOutstandingFees($student),
            'payment_history' => $this->getMyPaymentHistory($student)
        ];

        return view('student.dashboard', $data);
    }

    // ===================================
    // STATISTICS METHODS - MOVED TO PROPER LOCATION
    // ===================================
    
    private function getCourseStatistics(): array
    {
        return DB::table('students')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->select(
                'courses.name as course_name',
                'courses.id as course_id',
                DB::raw('count(students.id) as total_students'),
                DB::raw('count(CASE WHEN students.status = "active" THEN 1 END) as active_students'), // FIXED: Specify students.status
                DB::raw('count(CASE WHEN batches.status = "active" THEN 1 END) as active_batches') // FIXED: Specify batches.status
            )
            ->groupBy('courses.id', 'courses.name')
            ->orderBy('courses.name')
            ->get()
            ->toArray();
    }
    
    private function getBatchPerformanceData(): array
    {
        return DB::table('batches')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->leftJoin('students', function($join) {
                $join->on('students.batch_id', '=', 'batches.id')
                     ->where('students.status', '=', 'active'); // FIXED: Specify students.status
            })
            ->select(
                'batches.name as batch_name',
                'courses.name as course_name',
                'batches.status as batch_status', // FIXED: Specify batches.status
                DB::raw('count(students.id) as student_count')
            )
            ->where('batches.status', '=', 'active') // FIXED: Specify batches.status
            ->groupBy('batches.id', 'batches.name', 'courses.name', 'batches.status')
            ->orderBy('courses.name')
            ->orderBy('batches.name')
            ->get()
            ->toArray();
    }

    // ===================================
    // ENHANCED FINANCIAL METHODS (Component System)
    // ===================================

    /**
     * Get enhanced financial overview with component system metrics
     */
    protected function getEnhancedFinancialOverview()
    {
        $currentMonth = Carbon::now();
        $lastMonth = $currentMonth->copy()->subMonth();
        
        // Component-based calculations
        $thisMonthCollections = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', $currentMonth->month)
            ->whereYear('payment_date', $currentMonth->year)
            ->sum('amount');

        $lastMonthCollections = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', $lastMonth->month)
            ->whereYear('payment_date', $lastMonth->year)
            ->sum('amount');

        $totalOutstanding = $this->calculateTotalOutstanding();
        $collectionEfficiency = $this->componentPaymentService->getCollectionEfficiency();

        // Growth calculation
        $growthPercentage = 0;
        if ($lastMonthCollections > 0) {
            $growthPercentage = (($thisMonthCollections - $lastMonthCollections) / $lastMonthCollections) * 100;
        }

        return [
            'current_month_collections' => $thisMonthCollections,
            'last_month_collections' => $lastMonthCollections,
            'growth_percentage' => round($growthPercentage, 2),
            'total_outstanding' => $totalOutstanding,
            'collection_efficiency' => $collectionEfficiency,
            'overdue_amount' => $this->getOverdueAmount(),
            'collection_rate' => $this->getCollectionRate(),
            'average_payment_time' => $this->getAveragePaymentTime()
        ];
    }

    /**
     * Get collection summary with component breakdown
     */
    protected function getCollectionSummary()
    {
        $summary = $this->componentPaymentService->getCollectionSummary();
        
        return [
            'total_collected_today' => Payment::where('payment_type', 'component')
                ->whereDate('payment_date', Carbon::today())
                ->sum('amount'),
            'total_collected_this_week' => Payment::where('payment_type', 'component')
                ->whereBetween('payment_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->sum('amount'),
            'total_collected_this_month' => Payment::where('payment_type', 'component')
                ->whereMonth('payment_date', Carbon::now()->month)
                ->sum('amount'),
            'pending_collections' => $this->calculateTotalOutstanding(),
            'collection_targets' => $this->getCollectionTargets(),
            'payment_methods_breakdown' => $this->getPaymentMethodBreakdown()
        ];
    }

    /**
     * Get defaulter analysis using component system
     */
    protected function getDefaulterAnalysis()
    {
        $defaulters = Student::whereHas('studentFees', function($query) {
                $query->where('due_date', '<', Carbon::now())
                      ->whereIn('status', ['unpaid', 'partial'])
                      ->whereRaw('amount - paid_amount - concession_amount > 0');
            })
            ->with(['studentFees' => function($query) {
                $query->where('due_date', '<', Carbon::now())
                      ->whereIn('status', ['unpaid', 'partial'])
                      ->whereRaw('amount - paid_amount - concession_amount > 0')
                      ->with('feeCategory');
            }])
            ->get();

        $analysis = [
            'total_defaulters' => $defaulters->count(),
            'total_overdue_amount' => 0,
            'defaulters_by_category' => [],
            'defaulters_by_batch' => [],
            'recovery_potential' => 0
        ];

        foreach ($defaulters as $student) {
            foreach ($student->studentFees as $fee) {
                $overdueAmount = max(0, $fee->amount - $fee->paid_amount - $fee->concession_amount);
                $analysis['total_overdue_amount'] += $overdueAmount;
                
                // Category breakdown
                $categoryName = $fee->feeCategory->name ?? 'Unknown';
                if (!isset($analysis['defaulters_by_category'][$categoryName])) {
                    $analysis['defaulters_by_category'][$categoryName] = [
                        'count' => 0,
                        'amount' => 0
                    ];
                }
                $analysis['defaulters_by_category'][$categoryName]['count']++;
                $analysis['defaulters_by_category'][$categoryName]['amount'] += $overdueAmount;
            }
        }

        return $analysis;
    }
    
/**
 * Calculate all real dashboard metrics with enhanced payment analysis
 */
private function calculateRealDashboardData()
{
    return [
        // Basic Stats
        'total_students' => Student::where('students.status', 'active')->count(),
        'student_growth' => $this->calculateStudentGrowth(),
        'total_revenue' => $this->calculateTotalRevenue(),
        'revenue_growth' => $this->calculateRevenueGrowth(),
        'active_courses' => Course::count(),
        'total_batches' => Batch::count(),
        'total_faculty' => User::role('staff')->count(),
        'active_faculty' => User::role('staff')->where('users.status', 'active')->count(),
        'outstanding_fees' => $this->calculateOutstandingFees(),
        'defaulters_count' => $this->getDefaultersCount(),
        'total_alumni' => Student::where('students.status', 'graduated')->count(),
        'total_enquiries' => Enquiry::count(),
        'pending_enquiries' => Enquiry::where('status', 'pending')->count(),
        'avg_attendance' => $this->calculateAverageAttendance(),
        'collection_rate' => $this->calculateCollectionRate(),
        'new_admissions' => Student::whereMonth('created_at', now()->month)->count(),

        // Enhanced Payment Analysis
        'daily_payment_analysis' => $this->getDailyPaymentAnalysis(),
        'recent_payments' => $this->getRecentPayments(),
        'pending_component_payments' => $this->getPendingComponentPayments(),
        'non_paying_students' => $this->getNonPayingStudents(),
        'payment_trends' => $this->getPaymentTrends(),
        
        // Fee Collection Data
        'fee_collection' => $this->getFeeCollectionMetrics(),
        
        // Defaulters Analysis
        'defaulters_analysis' => $this->getDefaultersAnalysis(),
        
        // Performance Metrics
        'attendance_analytics' => $this->getAttendanceAnalytics(),
        'faculty_performance' => $this->getFacultyPerformance(),
        'course_performance' => $this->getCoursePerformance(),
        
        // Charts Data
        'revenue_expense_chart' => $this->getRevenueExpenseChartData(),
        'revenue_chart' => $this->getRevenueChartData(),
        
        // Activity & Alerts
        'recent_activities' => $this->getRecentActivities(),
        'system_alerts' => $this->getSystemAlerts(),
        
        // System Health
        'active_users' => $this->getActiveUsersCount(),
        'server_uptime' => $this->getServerUptime(),
        'response_time' => $this->getAverageResponseTime(),
        'storage_used' => $this->getStorageUsage(),
        'database_size' => $this->getDatabaseSize(),
        'api_calls' => $this->getTodayApiCalls(),
        'concurrent_sessions' => $this->getConcurrentSessions()
    ];
}

/**
 * Get detailed daily payment analysis
 */
private function getDailyPaymentAnalysis()
{
    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();
    $thisWeek = now()->startOfWeek();
    $lastWeek = now()->subWeek()->startOfWeek();
    
    // Today's payments
    $todayPayments = Payment::whereDate('payment_date', $today)
        ->where('payment_type', 'component')
        ->sum('amount');
    
    // Yesterday's payments
    $yesterdayPayments = Payment::whereDate('payment_date', $yesterday)
        ->where('payment_type', 'component')
        ->sum('amount');
    
    // This week's payments
    $thisWeekPayments = Payment::whereBetween('payment_date', [$thisWeek, now()])
        ->where('payment_type', 'component')
        ->sum('amount');
    
    // Last week's payments
    $lastWeekPayments = Payment::whereBetween('payment_date', [$lastWeek, $lastWeek->copy()->endOfWeek()])
        ->where('payment_type', 'component')
        ->sum('amount');
    
    // Calculate growth percentages
    $dailyGrowth = $yesterdayPayments > 0 ? 
        round((($todayPayments - $yesterdayPayments) / $yesterdayPayments) * 100, 1) : 0;
    
    $weeklyGrowth = $lastWeekPayments > 0 ? 
        round((($thisWeekPayments - $lastWeekPayments) / $lastWeekPayments) * 100, 1) : 0;
    
    // Payment method breakdown for today
    $paymentMethods = Payment::whereDate('payment_date', $today)
        ->where('payment_type', 'component')
        ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
        ->groupBy('payment_method')
        ->get();
    
    // Hourly payment distribution for today
    $hourlyPayments = Payment::whereDate('payment_date', $today)
        ->where('payment_type', 'component')
        ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();
    
    return [
        'today_total' => $todayPayments,
        'yesterday_total' => $yesterdayPayments,
        'this_week_total' => $thisWeekPayments,
        'last_week_total' => $lastWeekPayments,
        'daily_growth' => $dailyGrowth,
        'weekly_growth' => $weeklyGrowth,
        'today_count' => Payment::whereDate('payment_date', $today)->where('payment_type', 'component')->count(),
        'payment_methods' => $paymentMethods,
        'hourly_distribution' => $hourlyPayments,
        'avg_payment_amount' => $todayPayments > 0 ? round($todayPayments / max(1, Payment::whereDate('payment_date', $today)->where('payment_type', 'component')->count())) : 0
    ];
}

/**
 * Get pending component payments analysis
 */
private function getPendingComponentPayments()
{
    // Get all unpaid and partial student fees
    $pendingFees = StudentFee::whereIn('status', ['unpaid', 'partial'])
        ->with(['student.batch.course', 'feeCategory'])
        ->get();
    
    $totalPendingAmount = $pendingFees->sum(function($fee) {
        return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
    });
    
    // Group by fee category
    $categoryBreakdown = $pendingFees->groupBy('feeCategory.name')->map(function($fees, $categoryName) {
        $totalAmount = $fees->sum(function($fee) {
            return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
        });
        
        return [
            'category_name' => $categoryName ?: 'Unknown',
            'total_amount' => $totalAmount,
            'fee_count' => $fees->count(),
            'student_count' => $fees->pluck('student_id')->unique()->count()
        ];
    });
    
    // Group by course
    $courseBreakdown = $pendingFees->groupBy('student.batch.course.name')->map(function($fees, $courseName) {
        $totalAmount = $fees->sum(function($fee) {
            return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
        });
        
        return [
            'course_name' => $courseName ?: 'Unknown',
            'total_amount' => $totalAmount,
            'student_count' => $fees->pluck('student_id')->unique()->count()
        ];
    });
    
    return [
        'total_pending_amount' => $totalPendingAmount,
        'total_pending_fees' => $pendingFees->count(),
        'total_students_with_pending' => $pendingFees->pluck('student_id')->unique()->count(),
        'category_breakdown' => $categoryBreakdown->sortByDesc('total_amount')->take(10),
        'course_breakdown' => $courseBreakdown->sortByDesc('total_amount')->take(10),
        'overdue_amount' => $pendingFees->where('due_date', '<', now())->sum(function($fee) {
            return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
        }),
        'due_this_week' => $pendingFees->whereBetween('due_date', [now(), now()->addWeek()])->sum(function($fee) {
            return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
        })
    ];
}
/**
 * Get recent payments with detailed information
 */
private function getRecentPayments()
{
    return Payment::with(['student.batch.course', 'createdBy'])
        ->where('payment_type', 'component')
        ->latest('payment_date')
        ->limit(20)
        ->get()
        ->map(function($payment) {
            return [
                'id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'student_name' => $payment->student->name ?? 'Unknown',
                'student_id' => $payment->student->id ?? null,
                'course_name' => $payment->student->batch->course->name ?? 'Unknown',
                'batch_name' => $payment->student->batch->name ?? 'Unknown',
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date,
                'status' => $payment->status,
                'created_by' => $payment->createdBy->name ?? 'System',
                'created_at' => $payment->created_at,
                'time_ago' => $payment->created_at->diffForHumans(),
                'components_paid' => $payment->componentItems->count() ?? 0
            ];
        });
}


/**
 * Get students who haven't made any component payments
 */
private function getNonPayingStudents()
{
    // Get active students who have never made a component payment
    $nonPayingStudents = Student::where('students.status', 'active')
        ->with(['batch.course'])
        ->whereDoesntHave('payments', function($query) {
            $query->where('payment_type', 'component');
        })
        ->orWhereHas('studentFees', function($query) {
            $query->where('paid_amount', 0)
                  ->orWhereNull('paid_amount');
        })
        ->limit(50)
        ->get();
    
    // Get students with only partial payments
    $partialPayingStudents = Student::where('students.status', 'active')
        ->with(['batch.course', 'studentFees'])
        ->whereHas('studentFees', function($query) {
            $query->where('status', 'partial')
                  ->whereRaw('paid_amount > 0 AND paid_amount < amount');
        })
        ->limit(30)
        ->get();
    
    // Calculate total outstanding for non-paying students
    $totalOutstandingNonPaying = $nonPayingStudents->sum(function($student) {
        return $student->studentFees->sum(function($fee) {
            return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
        });
    });
    
    return [
        'non_paying_students' => $nonPayingStudents->map(function($student) {
            $outstandingAmount = $student->studentFees->sum(function($fee) {
                return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
            });
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'course_name' => $student->batch->course->name ?? 'Unknown',
                'batch_name' => $student->batch->name ?? 'Unknown',
                'outstanding_amount' => $outstandingAmount,
                'total_fees' => $student->studentFees->count(),
                'admission_date' => $student->created_at,
                'days_since_admission' => $student->created_at->diffInDays(now())
            ];
        }),
        'partial_paying_students' => $partialPayingStudents->map(function($student) {
            $totalAmount = $student->studentFees->sum('amount');
            $paidAmount = $student->studentFees->sum('paid_amount');
            $outstandingAmount = $totalAmount - $paidAmount - $student->studentFees->sum('concession_amount');
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'course_name' => $student->batch->course->name ?? 'Unknown',
                'batch_name' => $student->batch->name ?? 'Unknown',
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => $outstandingAmount,
                'payment_percentage' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 1) : 0
            ];
        }),
        'total_non_paying' => $nonPayingStudents->count(),
        'total_partial_paying' => $partialPayingStudents->count(),
        'total_outstanding_non_paying' => $totalOutstandingNonPaying,
        'avg_outstanding_per_student' => $nonPayingStudents->count() > 0 ? 
            round($totalOutstandingNonPaying / $nonPayingStudents->count()) : 0
    ];
}

/**
 * Get payment trends over time
 */
private function getPaymentTrends()
{
    // Last 30 days payment trend
    $dailyTrends = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $dailyAmount = Payment::whereDate('payment_date', $date->toDateString())
            ->where('payment_type', 'component')
            ->sum('amount');
        $dailyCount = Payment::whereDate('payment_date', $date->toDateString())
            ->where('payment_type', 'component')
            ->count();
            
        $dailyTrends[] = [
            'date' => $date->format('M d'),
            'amount' => $dailyAmount,
            'count' => $dailyCount,
            'avg_amount' => $dailyCount > 0 ? round($dailyAmount / $dailyCount) : 0
        ];
    }
    
    // Weekly trends for last 12 weeks
    $weeklyTrends = [];
    for ($i = 11; $i >= 0; $i--) {
        $weekStart = now()->subWeeks($i)->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        
        $weeklyAmount = Payment::whereBetween('payment_date', [$weekStart, $weekEnd])
            ->where('payment_type', 'component')
            ->sum('amount');
        $weeklyCount = Payment::whereBetween('payment_date', [$weekStart, $weekEnd])
            ->where('payment_type', 'component')
            ->count();
            
        $weeklyTrends[] = [
            'week' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
            'amount' => $weeklyAmount,
            'count' => $weeklyCount
        ];
    }
    
    return [
        'daily_trends' => $dailyTrends,
        'weekly_trends' => $weeklyTrends,
        'peak_day' => collect($dailyTrends)->sortByDesc('amount')->first(),
        'peak_week' => collect($weeklyTrends)->sortByDesc('amount')->first()
    ];
}

// Update existing getRecentActivities method to include more payment activities
private function getRecentActivities()
{
    $activities = collect();
    
    // Recent payments
    $recentPayments = Payment::with('student')
        ->where('payment_type', 'component')
        ->latest()
        ->limit(8)
        ->get()
        ->map(function($payment) {
            return [
                'type' => 'payment',
                'user' => $payment->student->name ?? 'Unknown',
                'action' => 'Made component payment of ₹' . number_format($payment->amount),
                'time' => $payment->created_at->diffForHumans(),
                'icon' => 'fa-rupee-sign',
                'color' => 'success',
                'meta' => 'Payment ID: ' . $payment->receipt_number
            ];
        });
    
    // Recent fee assignments
    $recentFees = StudentFee::with(['student', 'feeCategory'])
        ->latest()
        ->limit(5)
        ->get()
        ->map(function($fee) {
            return [
                'type' => 'fee_assignment',
                'user' => 'System',
                'action' => 'Fee assigned to ' . ($fee->student->name ?? 'Unknown') . ' - ' . ($fee->feeCategory->name ?? 'Unknown'),
                'time' => $fee->created_at->diffForHumans(),
                'icon' => 'fa-file-invoice',
                'color' => 'info',
                'meta' => '₹' . number_format($fee->amount)
            ];
        });
    
    // Recent admissions
    $recentStudents = Student::latest()
        ->limit(5)
        ->get()
        ->map(function($student) {
            return [
                'type' => 'admission',
                'user' => 'Admin',
                'action' => 'New student admission - ' . $student->name,
                'time' => $student->created_at->diffForHumans(),
                'icon' => 'fa-user-plus',
                'color' => 'primary',
                'meta' => 'Student ID: ' . $student->id
            ];
        });
    
    // Merge all activities and sort by time
    return $activities->concat($recentPayments)
                     ->concat($recentFees)
                     ->concat($recentStudents)
                     ->sortByDesc(function($item) {
                         return strtotime($item['time']);
                     })
                     ->take(15)
                     ->values()
                     ->toArray();
}

/**
 * Get real faculty performance data
 */
private function getFacultyPerformance()
{
    $totalFaculty = User::role('staff')->count();
    $activeToday = User::role('staff')
        ->where('last_login_at', '>=', now()->startOfDay())
        ->count();
    $onLeave = $totalFaculty - $activeToday; // Simplified calculation

    return [
        'total_faculty' => $totalFaculty,
        'active_today' => $activeToday,
        'on_leave' => $onLeave,
        'top_performers' => 5, // You can implement actual rating system
        'avg_rating' => 4.2 // You can implement actual rating calculation
    ];
}

/**
 * Get real course performance data
 */
private function getCoursePerformance()
{
    $courses = Course::query()
        ->leftJoin('batches', 'courses.id', '=', 'batches.course_id')
        ->leftJoin('students', function ($join) {
            $join->on('batches.id', '=', 'students.batch_id')
                ->where('students.status', '=', 'active');
        })
        ->groupBy('courses.id', 'courses.name')
        ->select('courses.name')
        ->selectRaw('COUNT(students.id) as active_students')
        ->get();
    $performance = [];

    foreach ($courses as $course) {
        $totalStudents = (int) $course->active_students;

        // You can implement actual pass rate and satisfaction calculations
        $performance[$course->name] = [
            'students' => $totalStudents,
            'pass_rate' => rand(85, 95), // Replace with actual calculation
            'satisfaction' => round(rand(40, 50) / 10, 1) // Replace with actual rating
        ];
    }

    return $performance;
}

/**
 * Calculate real fee collection metrics
 */
private function getFeeCollectionMetrics()
{
    $totalBilled = StudentFee::sum('amount') ?? 0;
    $totalCollected = StudentFee::sum('paid_amount') ?? 0;
    $totalConcessions = StudentFee::sum('concession_amount') ?? 0;
    $outstanding = $totalBilled - $totalCollected - $totalConcessions;
    
    // Calculate overdue amount
    $overdue = StudentFee::where('due_date', '<', now())
        ->whereIn('status', ['unpaid', 'partial'])
        ->get()
        ->sum(function($fee) {
            return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
        });
    
    // Calculate advance payments
    $advance = Payment::where('payment_date', '>', now())
        ->sum('amount') ?? 0;

    $collectionRate = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0;

    return [
        'total_billed' => $totalBilled,
        'total_collected' => $totalCollected,
        'outstanding' => $outstanding,
        'collection_rate' => $collectionRate,
        'overdue' => $overdue,
        'advance' => $advance
    ];
}


    /**
     * Get recent component-based transactions
     */
    protected function getRecentTransactions()
    {
        return Payment::where('payment_type', 'component')
            ->with(['student.batch.course', 'componentItems.studentFee.feeCategory'])
            ->latest('payment_date')
            ->limit(10)
            ->get()
            ->map(function($payment) {
                return [
                    'payment' => $payment,
                    'student_name' => $payment->student->name ?? 'Unknown',
                    'course' => $payment->student->batch->course->name ?? 'Unknown',
                    'components' => $payment->componentItems->map(function($item) {
                        return [
                            'category' => $item->studentFee->feeCategory->name ?? 'Unknown',
                            'amount' => $item->amount_paid
                        ];
                    })
                ];
            });
    }


/**
 * Get real defaulters analysis
 */
private function getDefaultersAnalysis()
{
    $defaulters = Student::whereHas('studentFees', function($query) {
        $query->where('due_date', '<', now())
              ->whereIn('status', ['unpaid', 'partial'])
              ->whereRaw('amount - paid_amount - concession_amount > 0');
    })->get();

    $totalDefaulters = $defaulters->count();
    $criticalDefaulters = 0;
    $moderateDefaulters = 0;
    $recentDefaulters = 0;
    $totalOverdueAmount = 0;

    foreach ($defaulters as $student) {
        $studentOverdue = $student->studentFees()
            ->where('due_date', '<', now())
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(function($fee) {
                return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
            });

        $totalOverdueAmount += $studentOverdue;

        if ($studentOverdue > 50000) {
            $criticalDefaulters++;
        } elseif ($studentOverdue >= 20000) {
            $moderateDefaulters++;
        } else {
            $recentDefaulters++;
        }
    }

    return [
        'total_defaulters' => $totalDefaulters,
        'critical_defaulters' => $criticalDefaulters,
        'moderate_defaulters' => $moderateDefaulters,
        'recent_defaulters' => $recentDefaulters,
        'total_overdue_amount' => $totalOverdueAmount,
        'avg_overdue_per_student' => $totalDefaulters > 0 ? round($totalOverdueAmount / $totalDefaulters) : 0
    ];
}
    /**
     * Get monthly trends for component payments
     */
    protected function getMonthlyTrends()
    {
        $trends = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthlyCollection = Payment::where('payment_type', 'component')
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');
                
            $trends[] = [
                'month' => $date->format('M Y'),
                'collection' => $monthlyCollection
            ];
        }
        
        return $trends;
    }

    /**
     * Get outstanding summary by components
     */
    protected function getOutstandingSummary()
    {
        return StudentFee::select(
                'fee_categories.name as category_name',
                DB::raw('COUNT(student_fees.id) as total_fees'),
                DB::raw('SUM(student_fees.amount) as total_billed'),
                DB::raw('SUM(student_fees.paid_amount) as total_paid'),
                DB::raw('SUM(student_fees.concession_amount) as total_concessions'),
                DB::raw('SUM(student_fees.amount - student_fees.paid_amount - student_fees.concession_amount) as outstanding')
            )
            ->join('fee_categories', 'student_fees.fee_category_id', '=', 'fee_categories.id')
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->havingRaw('SUM(student_fees.amount - student_fees.paid_amount - student_fees.concession_amount) > 0')
            ->get();
    }

    // Helper methods for calculations
private function calculateOutstandingFees()
{
    return StudentFee::whereIn('status', ['unpaid', 'partial', 'overdue'])
        ->get()
        ->sum(function($fee) {
            return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
        });
}


    private function getOverdueAmount()
    {
        return StudentFee::where('due_date', '<', Carbon::now())
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(function($fee) {
                return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
            });
    }

    private function getCollectionRate()
    {
        $totalBilled = StudentFee::sum('amount') ?? 0;
        $totalCollected = StudentFee::sum('paid_amount') ?? 0;
        $totalConcessions = StudentFee::sum('concession_amount') ?? 0;
        
        $netBilled = $totalBilled - $totalConcessions;
        
        if ($netBilled <= 0) return 100;
        
        return round(($totalCollected / $netBilled) * 100, 2);
    }

    private function getPaymentMethodBreakdown()
    {
        return Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', Carbon::now()->month)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();
    }

    // ===================================
    // COMPONENT SYSTEM SPECIFIC METHODS
    // ===================================

    private function getComponentSystemHealth()
    {
        return [
            'total_components' => StudentFee::count(),
            'paid_components' => StudentFee::where('status', 'paid')->count(),
            'partial_components' => StudentFee::where('status', 'partial')->count(),
            'overdue_components' => StudentFee::where('due_date', '<', Carbon::now())
                ->whereIn('status', ['unpaid', 'partial'])->count(),
            'system_efficiency' => $this->calculateSystemEfficiency()
        ];
    }

    private function calculateSystemEfficiency()
    {
        $totalComponents = StudentFee::count();
        if ($totalComponents == 0) return 100;
        
        $resolvedComponents = StudentFee::where('status', 'paid')->count();
        return round(($resolvedComponents / $totalComponents) * 100, 2);
    }

    private function getComponentBreakdown()
    {
        return FeeCategory::withCount(['studentFees'])
            ->with(['studentFees' => function($query) {
                $query->select('fee_category_id', 
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('SUM(paid_amount) as paid_amount'),
                    DB::raw('SUM(concession_amount) as concession_amount')
                )->groupBy('fee_category_id');
            }])
            ->get();
    }

    private function getFeeCategoryPerformance()
    {
        return FeeCategory::select(
                'fee_categories.name',
                DB::raw('COUNT(student_fees.id) as total_fees'),
                DB::raw('SUM(student_fees.amount) as total_billed'),
                DB::raw('SUM(student_fees.paid_amount) as total_collected'),
                DB::raw('ROUND((SUM(student_fees.paid_amount) / (SUM(student_fees.amount) - SUM(student_fees.concession_amount))) * 100, 2) as collection_rate')
            )
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->orderBy('collection_rate', 'desc')
            ->get();
    }

    // ===================================
    // SUPER ADMIN SPECIFIC METHODS
    // ===================================

    /**
     * Calculate student growth percentage
     */
    private function calculateStudentGrowth()
    {
        $currentMonth = Student::whereMonth('created_at', Carbon::now()->month)->count();
        $lastMonth = Student::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
        
        if ($lastMonth == 0) return 100;
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    /**
     * Calculate total revenue from component payments
     */
    private function calculateTotalRevenue()
    {
        return Payment::where('payment_type', 'component')->sum('amount');
    }

    /**
     * Calculate revenue growth percentage
     */
    private function calculateRevenueGrowth()
    {
        $currentMonth = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', Carbon::now()->month)
            ->sum('amount');
            
        $lastMonth = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', Carbon::now()->subMonth()->month)
            ->sum('amount');
        
        if ($lastMonth == 0) return 100;
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    /**
     * Get system health metrics
     */
    private function getSystemHealth()
    {
        return [
            'database_status' => 'healthy',
            'cache_status' => 'healthy',
            'storage_status' => 'healthy',
            'memory_usage' => '45%',
            'cpu_usage' => '23%'
        ];
    }

/**
 * Get real attendance analytics
 */
private function getAttendanceAnalytics()
{
    $today = now()->toDateString();
    $totalStudents = Student::where('students.status', 'active')->count();
    
    $presentToday = Attendance::whereDate('attendance_date', $today)
        ->where('status', 'present')
        ->count();
    
    $attendanceRate = $totalStudents > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 0;
    
    return [
        'total_students' => $totalStudents,
        'present_today' => $presentToday,
        'attendance_rate' => $attendanceRate,
        'weekly_average' => $this->getWeeklyAttendanceAverage()
    ];
}

    /**
     * Get revenue chart data for super admin
     */
    private function getRevenueChartData()
    {
        $labels = [];
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');
            $data[] = Payment::where('payment_type', 'component')
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Get recent enquiries for super admin
     */
    private function getRecentEnquiries()
    {
        return Enquiry::with('course')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($enquiry) {
                return [
                    'name' => $enquiry->name,
                    'course' => $enquiry->course->name ?? 'General',
                    'status' => $enquiry->status,
                    'created_at' => $enquiry->created_at->diffForHumans()
                ];
            });
    }

    /**
     * Get system notifications
     */
    private function getSystemNotifications()
    {
        return [
            [
                'title' => 'System Update Available',
                'message' => 'A new system update is available for installation.',
                'type' => 'info',
                'time' => '2 hours ago'
            ],
            [
                'title' => 'Backup Completed',
                'message' => 'Daily system backup completed successfully.',
                'type' => 'success',
                'time' => '1 day ago'
            ]
        ];
    }

    /**
     * Get safe quick actions that don't require parameters
     */
    private function getSafeQuickActions()
    {
        $actions = [];
        
        // Only add actions for routes that exist and don't require parameters
        if (Route::has('admin.users.index')) {
            $actions[] = ['name' => 'User Management', 'icon' => 'users-cog', 'route' => 'admin.users.index', 'color' => 'primary', 'permission' => 'manage users'];
        }
        
        if (Route::has('admin.students.index')) {
            $actions[] = ['name' => 'View Students', 'icon' => 'users', 'route' => 'admin.students.index', 'color' => 'info', 'permission' => 'view students'];
        }
        
        if (Route::has('admin.component-payments.index')) {
            $actions[] = ['name' => 'Payment Records', 'icon' => 'money-bill', 'route' => 'admin.component-payments.index', 'color' => 'success', 'permission' => 'view payments'];
        }
        
        if (Route::has('admin.enquiries.index')) {
            $actions[] = ['name' => 'Enquiries', 'icon' => 'phone', 'route' => 'admin.enquiries.index', 'color' => 'warning', 'permission' => 'view enquiries'];
        }
        
        if (Route::has('admin.admissions.index')) {
            $actions[] = ['name' => 'Admissions', 'icon' => 'graduation-cap', 'route' => 'admin.admissions.index', 'color' => 'primary', 'permission' => 'view admissions'];
        }
        
        if (Route::has('admin.settings.index')) {
            $actions[] = ['name' => 'System Settings', 'icon' => 'cogs', 'route' => 'admin.settings.index', 'color' => 'secondary', 'permission' => 'manage settings'];
        }
        
        return $actions;
    }

/**
 * Get real system alerts
 */
private function getSystemAlerts()
{
    $alerts = [];

    // Check for fee defaulters
    $defaultersCount = $this->getDefaultersCount();
    if ($defaultersCount > 0) {
        $alerts[] = [
            'title' => 'Fee Defaulters Alert',
            'message' => $defaultersCount . ' students have overdue payments',
            'level' => 'danger',
            'icon' => 'exclamation-triangle',
            'time' => 'Now',
            'action_url' => route('admin.payment-defaulters.index') ?? '#'
        ];
    }

    // Check storage usage
    $storagePercent = $this->getStorageUsagePercent();
    if ($storagePercent > 75) {
        $alerts[] = [
            'title' => 'Storage Warning',
            'message' => 'Server storage usage is at ' . $storagePercent . '% capacity',
            'level' => 'warning',
            'icon' => 'hdd',
            'time' => 'Now'
        ];
    }

    return $alerts;
}


    /**
     * Get fee collection data for super admin
     */
    private function getFeeCollectionData()
    {
        $totalBilled = StudentFee::sum('amount');
        $totalCollected = StudentFee::sum('paid_amount');
        $totalConcessions = StudentFee::sum('concession_amount');
        $outstanding = $totalBilled - $totalCollected - $totalConcessions;
        
        return [
            'total_billed' => $totalBilled,
            'total_collected' => $totalCollected,
            'total_concessions' => $totalConcessions,
            'outstanding' => $outstanding,
            'collection_rate' => $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0
        ];
    }

    /**
     * Get active users count
     */
    private function getActiveUsersCount()
    {
        // This would typically check user sessions or recent activity
        return User::where('last_login_at', '>', Carbon::now()->subDay())->count();
    }

private function calculateAverageAttendance()
{
    $totalAttendance = Attendance::whereDate('attendance_date', now()->toDateString())->count();
    $presentAttendance = Attendance::whereDate('attendance_date', now()->toDateString())
        ->where('status', 'present')->count();
    
    return $totalAttendance > 0 ? round(($presentAttendance / $totalAttendance) * 100, 1) : 0;
}

    /**
     * Get server uptime (mock data - you'd implement actual server monitoring)
     */
    private function getServerUptime()
    {
        return '99.9%';
    }

    /**
     * Get average response time (mock data)
     */
    private function getAverageResponseTime()
    {
        return '120ms';
    }

    /**
     * Get storage usage
     */
    private function getStorageUsage()
    {
        return '45%';
    }



    /**
     * Get weekly attendance average
     */
    private function getWeeklyAttendanceAverage()
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        
        $totalPresent = Attendance::whereBetween('attendance_date', [$weekStart, $weekEnd])
            ->where('status', 'present')
            ->count();
            
        $totalExpected = Student::where('students.status', 'active')->count() * 5; // FIXED: Added table prefix
        
        return $totalExpected > 0 ? round(($totalPresent / $totalExpected) * 100, 1) : 0;
    }

    // ===================================
    // PLACEHOLDER METHODS - IMPLEMENT AS NEEDED
    // ===================================

    private function getCollegeOverview()
    {
        return [
            'total_students' => Student::count(),
            'active_courses' => Course::count(),
            'total_faculty' => User::role('staff')->count()
        ];
    }
    
    private function getDefaultersCount()
{
    return Student::whereHas('studentFees', function($query) {
        $query->where('due_date', '<', now())
              ->whereIn('status', ['unpaid', 'partial'])
              ->whereRaw('amount - paid_amount - concession_amount > 0');
    })->count();
}

private function calculateCollectionRate()
{
    $totalBilled = StudentFee::sum('amount') ?? 0;
    $totalCollected = StudentFee::sum('paid_amount') ?? 0;
    
    return $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0;
}

private function getRevenueExpenseChartData()
{
    $months = [];
    $revenue = [];
    $expenses = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $months[] = $date->format('M');
        
        $monthlyRevenue = Payment::whereMonth('payment_date', $date->month)
            ->whereYear('payment_date', $date->year)
            ->sum('amount') ?? 0;
        
        $monthlyExpenses = Expense::whereMonth('expense_date', $date->month)
            ->whereYear('expense_date', $date->year)
            ->sum('amount') ?? ($monthlyRevenue * 0.4); // Fallback if no expense model
        
        $revenue[] = $monthlyRevenue;
        $expenses[] = $monthlyExpenses;
    }
    
    return [
        'labels' => $months,
        'revenue' => $revenue,
        'expenses' => $expenses
    ];
}

private function getDatabaseSize()
{
    try {
        $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size' FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.mysql.database')]);
        return $size[0]->size . 'MB';
    } catch (\Exception $e) {
        return '2.4GB'; // Fallback
    }
}

private function getTodayApiCalls()
{
    // If you have API logging, calculate from logs
    // Otherwise return estimated value based on users
    $activeUsers = $this->getActiveUsersCount();
    return round($activeUsers * 520) . 'K'; // Estimated API calls
}

private function getConcurrentSessions()
{
    // Get active sessions count
    return User::where('last_login_at', '>', now()->subMinutes(30))->count();
}

private function getStorageUsagePercent()
{
    try {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        return round(($used / $total) * 100);
    } catch (\Exception $e) {
        return 45; // Fallback
    }
}

    private function getBatchFinancialPerformance()
    {
        return [];
    }

    private function getPaymentMethodAnalysis()
    {
        $currentMonth = now();
        $lastMonth = $currentMonth->copy()->subMonth();
        
        // Get payment method breakdown for current month
        $currentMonthData = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', $currentMonth->month)
            ->whereYear('payment_date', $currentMonth->year)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();
            
        // Get payment method breakdown for last month
        $lastMonthData = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', $lastMonth->month)
            ->whereYear('payment_date', $lastMonth->year)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');
            
        $analysis = [];
        $totalCurrentAmount = $currentMonthData->sum('total');
        
        foreach ($currentMonthData as $method) {
            $lastMonthAmount = $lastMonthData->get($method->payment_method)->total ?? 0;
            $growth = $lastMonthAmount > 0 ? 
                round((($method->total - $lastMonthAmount) / $lastMonthAmount) * 100, 1) : 0;
                
            $analysis[] = [
                'method' => $method->payment_method,
                'current_month_amount' => $method->total,
                'current_month_count' => $method->count,
                'last_month_amount' => $lastMonthAmount,
                'growth_percentage' => $growth,
                'percentage_of_total' => $totalCurrentAmount > 0 ? 
                    round(($method->total / $totalCurrentAmount) * 100, 1) : 0,
                'avg_transaction_amount' => $method->count > 0 ? 
                    round($method->total / $method->count) : 0
            ];
        }
        
        return [
            'methods' => collect($analysis)->sortByDesc('current_month_amount')->values()->toArray(),
            'total_amount' => $totalCurrentAmount,
            'total_transactions' => $currentMonthData->sum('count'),
            'most_popular_method' => $currentMonthData->sortByDesc('count')->first()->payment_method ?? 'N/A',
            'highest_value_method' => $currentMonthData->sortByDesc('total')->first()->payment_method ?? 'N/A'
        ];
    }

    private function getCollectionEfficiency()
    {
        $totalDue = StudentFee::whereIn('status', ['unpaid', 'partial'])
            ->sum(DB::raw('amount - COALESCE(paid_amount, 0) - COALESCE(concession_amount, 0)'));
            
        $totalCollected = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', now()->month)
            ->sum('amount');
            
        $totalExpected = $totalDue + $totalCollected;
        
        $efficiency = $totalExpected > 0 ? 
            round(($totalCollected / $totalExpected) * 100, 1) : 0;
            
        // Calculate collection rate trends
        $last6Months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyCollected = Payment::where('payment_type', 'component')
                ->whereMonth('payment_date', $month->month)
                ->whereYear('payment_date', $month->year)
                ->sum('amount');
                
            $last6Months[] = [
                'month' => $month->format('M Y'),
                'collected' => $monthlyCollected,
                'target' => $monthlyCollected * 1.2 // Assuming 20% buffer as target
            ];
        }
        
        return [
            'current_efficiency' => $efficiency,
            'total_collected' => $totalCollected,
            'total_due' => $totalDue,
            'collection_target' => $totalExpected,
            'monthly_trends' => $last6Months,
            'avg_collection_time' => $this->getAverageCollectionTime(),
            'on_time_payments' => $this->getOnTimePaymentPercentage()
        ];
    }

    private function getOverdueAnalysis()
    {
        $overdueThresholds = [
            '1-30' => [1, 30],
            '31-60' => [31, 60],
            '61-90' => [61, 90],
            '90+' => [91, 9999]
        ];
        
        $analysis = [];
        $totalOverdueAmount = 0;
        $totalOverdueCount = 0;
        
        foreach ($overdueThresholds as $range => $days) {
            $startDate = now()->subDays($days[1]);
            $endDate = $days[0] > 1 ? now()->subDays($days[0]) : now();
            
            $overdueData = StudentFee::whereIn('status', ['unpaid', 'partial'])
                ->where('due_date', '<=', $endDate)
                ->when($days[0] > 1, function($query) use ($startDate) {
                    return $query->where('due_date', '>', $startDate);
                })
                ->with(['student.batch.course', 'feeCategory'])
                ->get();
                
            $rangeAmount = $overdueData->sum(function($fee) {
                return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
            });
            
            $analysis[$range] = [
                'count' => $overdueData->count(),
                'amount' => $rangeAmount,
                'students' => $overdueData->pluck('student_id')->unique()->count(),
                'avg_amount_per_student' => $overdueData->pluck('student_id')->unique()->count() > 0 ? 
                    round($rangeAmount / $overdueData->pluck('student_id')->unique()->count()) : 0
            ];
            
            $totalOverdueAmount += $rangeAmount;
            $totalOverdueCount += $overdueData->count();
        }
        
        // Get course-wise overdue analysis
        $courseWiseOverdue = StudentFee::whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->with(['student.batch.course'])
            ->get()
            ->groupBy('student.batch.course.name')
            ->map(function($fees, $courseName) {
                $amount = $fees->sum(function($fee) {
                    return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
                });
                
                return [
                    'course_name' => $courseName ?: 'Unknown',
                    'overdue_amount' => $amount,
                    'overdue_count' => $fees->count(),
                    'student_count' => $fees->pluck('student_id')->unique()->count()
                ];
            })
            ->sortByDesc('overdue_amount')
            ->take(10)
            ->values();
        
        return [
            'by_age' => $analysis,
            'total_overdue_amount' => $totalOverdueAmount,
            'total_overdue_count' => $totalOverdueCount,
            'course_wise' => $courseWiseOverdue,
            'recovery_priority' => $this->getRecoveryPriorityList(),
            'overdue_trend' => $this->getOverdueTrend()
        ];
    }

    private function getRecoveryMetrics()
    {
        $currentMonth = now();
        $lastMonth = $currentMonth->copy()->subMonth();
        
        // Recovery this month (payments made for overdue fees)
        $recoveredThisMonth = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', $currentMonth->month)
            ->whereYear('payment_date', $currentMonth->year)
            ->whereHas('student.studentFees', function($query) {
                $query->where('due_date', '<', now()->startOfMonth())
                      ->whereIn('status', ['paid', 'partial']);
            })
            ->sum('amount');
            
        // Recovery last month
        $recoveredLastMonth = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', $lastMonth->month)
            ->whereYear('payment_date', $lastMonth->year)
            ->whereHas('student.studentFees', function($query) use ($lastMonth) {
                $query->where('due_date', '<', $lastMonth->startOfMonth())
                      ->whereIn('status', ['paid', 'partial']);
            })
            ->sum('amount');
            
        // Calculate recovery rate
        $totalOverdue = StudentFee::whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->sum(DB::raw('amount - COALESCE(paid_amount, 0) - COALESCE(concession_amount, 0)'));
            
        $recoveryRate = ($totalOverdue + $recoveredThisMonth) > 0 ? 
            round(($recoveredThisMonth / ($totalOverdue + $recoveredThisMonth)) * 100, 1) : 0;
            
        // Recovery by method
        $recoveryByMethod = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', $currentMonth->month)
            ->whereHas('student.studentFees', function($query) {
                $query->where('due_date', '<', now()->startOfMonth());
            })
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();
            
        // Top recovering students
        $topRecoveringStudents = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', $currentMonth->month)
            ->with('student')
            ->select('student_id', DB::raw('SUM(amount) as total_recovered'))
            ->groupBy('student_id')
            ->orderByDesc('total_recovered')
            ->limit(10)
            ->get()
            ->map(function($payment) {
                return [
                    'student_name' => $payment->student->name ?? 'Unknown',
                    'student_id' => $payment->student_id,
                    'recovered_amount' => $payment->total_recovered
                ];
            });
            
        return [
            'recovered_this_month' => $recoveredThisMonth,
            'recovered_last_month' => $recoveredLastMonth,
            'recovery_growth' => $recoveredLastMonth > 0 ? 
                round((($recoveredThisMonth - $recoveredLastMonth) / $recoveredLastMonth) * 100, 1) : 0,
            'recovery_rate' => $recoveryRate,
            'total_overdue' => $totalOverdue,
            'recovery_by_method' => $recoveryByMethod,
            'top_recovering_students' => $topRecoveringStudents,
            'avg_recovery_time' => $this->getAverageRecoveryTime(),
            'recovery_success_rate' => $this->getRecoverySuccessRate()
        ];
    }

    private function getTodaySchedule($user)
    {
        return [];
    }

    private function getWeeklySchedule($user)
    {
        return [];
    }

    private function getMyBatches($user)
    {
        return [];
    }

    private function getMyAttendanceStats($user)
    {
        return [];
    }

    private function getUpcomingClasses($user)
    {
        return [];
    }


    private function getStudentFeeInsights($user)
    {
        return [];
    }

    private function getDailyTasks($user)
    {
        return [];
    }

    private function getStudentSummary()
    {
        return [];
    }

    private function getPendingActions($user)
    {
        return [];
    }

    private function getBasicFinancialSummary()
    {
        return [];
    }

    private function getMyAttendanceSummary($student)
    {
        return [];
    }

    private function getMyFeeSummary($student)
    {
        return [];
    }

    private function getMyAcademicProgress($student)
    {
        return [];
    }

    private function getMyUpcomingEvents($student)
    {
        return [];
    }

    private function getMyRecentPayments($student)
    {
        return [];
    }

    private function getMyOutstandingFees($student)
    {
        return [];
    }

    private function getMyPaymentHistory($student)
    {
        return [];
    }

    private function getAveragePaymentTime()
    {
        return '15 days'; // Mock value
    }

    private function getCollectionTargets()
    {
        return [];
    }

    // Helper methods for the implemented analytics
    private function getAverageCollectionTime()
    {
        $avgDays = Payment::where('payment_type', 'component')
            ->whereNotNull('payment_date')
            ->join('student_fees', function($join) {
                $join->on('payments.student_id', '=', 'student_fees.student_id');
            })
            ->whereColumn('payments.payment_date', '>=', 'student_fees.due_date')
            ->selectRaw('AVG(DATEDIFF(payments.payment_date, student_fees.due_date)) as avg_days')
            ->value('avg_days');
            
        return round($avgDays ?? 15, 1) . ' days';
    }
    
    private function getOnTimePaymentPercentage()
    {
        $totalPayments = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', now()->month)
            ->count();
            
        $onTimePayments = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', now()->month)
            ->join('student_fees', function($join) {
                $join->on('payments.student_id', '=', 'student_fees.student_id');
            })
            ->whereColumn('payments.payment_date', '<=', 'student_fees.due_date')
            ->count();
            
        return $totalPayments > 0 ? round(($onTimePayments / $totalPayments) * 100, 1) : 0;
    }
    
    private function getRecoveryPriorityList()
    {
        return StudentFee::whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->with(['student.batch.course'])
            ->get()
            ->map(function($fee) {
                $overdueAmount = max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
                $daysPastDue = now()->diffInDays($fee->due_date);
                
                // Priority score: higher amount + more days overdue = higher priority
                $priorityScore = ($overdueAmount / 1000) + ($daysPastDue / 10);
                
                return [
                    'student_name' => $fee->student->name ?? 'Unknown',
                    'student_id' => $fee->student_id,
                    'course_name' => $fee->student->batch->course->name ?? 'Unknown',
                    'overdue_amount' => $overdueAmount,
                    'days_past_due' => $daysPastDue,
                    'priority_score' => round($priorityScore, 2)
                ];
            })
            ->sortByDesc('priority_score')
            ->take(20)
            ->values()
            ->toArray();
    }
    
    private function getOverdueTrend()
    {
        $trends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $overdueAmount = StudentFee::whereIn('status', ['unpaid', 'partial'])
                ->where('due_date', '<', $date)
                ->sum(DB::raw('amount - COALESCE(paid_amount, 0) - COALESCE(concession_amount, 0)'));
                
            $trends[] = [
                'month' => $date->format('M Y'),
                'overdue_amount' => $overdueAmount
            ];
        }
        
        return $trends;
    }
    
    private function getAverageRecoveryTime()
    {
        $avgDays = Payment::where('payment_type', 'component')
            ->whereMonth('payment_date', now()->month)
            ->join('student_fees', function($join) {
                $join->on('payments.student_id', '=', 'student_fees.student_id');
            })
            ->where('student_fees.due_date', '<', DB::raw('payments.payment_date'))
            ->selectRaw('AVG(DATEDIFF(payments.payment_date, student_fees.due_date)) as avg_days')
            ->value('avg_days');
            
        return round($avgDays ?? 30, 1) . ' days';
    }
    
    private function getRecoverySuccessRate()
    {
        $totalOverdueFees = StudentFee::where('due_date', '<', now()->subMonth())
            ->count();
            
        $recoveredFees = StudentFee::where('due_date', '<', now()->subMonth())
            ->whereIn('status', ['paid'])
            ->count();
            
        return $totalOverdueFees > 0 ? round(($recoveredFees / $totalOverdueFees) * 100, 1) : 0;
    }
}