<?php
// app/Services/RealtimeDashboardService.php

namespace App\Services;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use App\Events\DashboardDataUpdated;
use App\Events\WidgetDataUpdated;
use App\Events\SystemAlertCreated;
use App\Models\{User, Dashboard, Widget};

class RealtimeDashboardService
{
    protected $dashboardService;
    protected $dataService;

    public function __construct(
        DashboardService $dashboardService,
        DashboardDataService $dataService
    ) {
        $this->dashboardService = $dashboardService;
        $this->dataService = $dataService;
    }

    /**
     * Broadcast dashboard updates to users
     */
    public function broadcastDashboardUpdate(string $eventType, array $data, array $targetRoles = [])
    {
        $event = new DashboardDataUpdated($eventType, $data, $targetRoles);
        broadcast($event);
        
        // Also update cache
        $this->updateDashboardCache($eventType, $data);
    }

    /**
     * Broadcast widget-specific updates
     */
    public function broadcastWidgetUpdate(string $widgetType, array $data, array $targetUsers = [])
    {
        $event = new WidgetDataUpdated($widgetType, $data, $targetUsers);
        broadcast($event);
        
        // Update widget cache
        $this->updateWidgetCache($widgetType, $data);
    }

    /**
     * Broadcast system alerts
     */
    public function broadcastSystemAlert(string $level, string $message, array $targetRoles = ['super-admin'])
    {
        $alert = [
            'id' => uniqid(),
            'level' => $level,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'icon' => $this->getAlertIcon($level)
        ];

        $event = new SystemAlertCreated($alert, $targetRoles);
        broadcast($event);
        
        // Store alert in cache for new users
        $this->storeSystemAlert($alert);
    }

    /**
     * Update dashboard data when specific events occur
     */
    public function handleDataChange(string $eventType, array $eventData)
    {
        switch ($eventType) {
            case 'student_enrolled':
                $this->updateStudentMetrics();
                break;
                
            case 'payment_received':
                $this->updateFinancialMetrics($eventData);
                break;
                
            case 'attendance_marked':
                $this->updateAttendanceMetrics($eventData);
                break;
                
            case 'system_health_check':
                $this->updateSystemHealthMetrics($eventData);
                break;
                
            case 'new_enquiry':
                $this->updateEnquiryMetrics($eventData);
                break;
        }
    }

    /**
     * Update student-related metrics
     */
    protected function updateStudentMetrics()
    {
        $totalStudents = \App\Models\Student::count();
        $activeStudents = \App\Models\Student::where('status', 'active')->count();
        $newThisMonth = \App\Models\Student::whereMonth('created_at', now()->month)->count();
        
        $data = [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'new_students_this_month' => $newThisMonth,
            'growth_percentage' => $this->calculateGrowthPercentage('students', $newThisMonth)
        ];

        $this->broadcastWidgetUpdate('total-students', $data, ['super-admin', 'college-admin']);
    }

    /**
     * Update financial metrics
     */
    protected function updateFinancialMetrics(array $paymentData)
    {
        $totalRevenue = \App\Models\Invoice::where('status', 'paid')->sum('amount');
        $pendingAmount = \App\Models\Invoice::where('status', 'pending')->sum('amount');
        $overdueAmount = \App\Models\Invoice::where('status', 'pending')
            ->where('due_date', '<', now())->sum('amount');
        
        $collectionRate = $totalRevenue > 0 ? 
            (($totalRevenue / ($totalRevenue + $pendingAmount)) * 100) : 0;

        $data = [
            'collected_amount' => $totalRevenue,
            'pending_amount' => $pendingAmount,
            'overdue_amount' => $overdueAmount,
            'collection_percentage' => round($collectionRate, 1),
            'recent_payment' => $paymentData
        ];

        $this->broadcastWidgetUpdate('fee-collection-status', $data, ['super-admin', 'college-admin', 'accountant']);
        $this->broadcastWidgetUpdate('revenue-chart', $this->getRevenueChartData(), ['super-admin', 'accountant']);
    }

    /**
     * Update attendance metrics
     */
    protected function updateAttendanceMetrics(array $attendanceData)
    {
        $today = now()->format('Y-m-d');
        $todayAttendance = \App\Models\Attendance::whereDate('date', $today)->get();
        
        $presentCount = $todayAttendance->where('status', 'present')->count();
        $absentCount = $todayAttendance->where('status', 'absent')->count();
        $lateCount = $todayAttendance->where('status', 'late')->count();
        $total = $todayAttendance->count();
        
        $attendancePercentage = $total > 0 ? round(($presentCount / $total) * 100, 1) : 0;

        $data = [
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'attendance_percentage' => $attendancePercentage,
            'last_updated' => now()->toISOString()
        ];

        $this->broadcastWidgetUpdate('attendance-analytics', $data, ['super-admin', 'college-admin', 'staff']);
    }

    /**
     * Update system health metrics
     */
    protected function updateSystemHealthMetrics(array $healthData)
    {
        $data = [
            'overall_status' => $healthData['status'] ?? 'healthy',
            'database_status' => $healthData['database'] ?? 'healthy',
            'memory_usage' => $healthData['memory'] ?? '45%',
            'storage_usage' => $healthData['storage'] ?? '60%',
            'performance_status' => $healthData['performance'] ?? 'good',
            'last_check' => now()->format('M j, Y \a\t g:i A'),
            'uptime' => $healthData['uptime'] ?? '99.9%'
        ];

        $this->broadcastWidgetUpdate('system-health', $data, ['super-admin']);
        
        // Broadcast alert if system is unhealthy
        if ($healthData['status'] !== 'healthy') {
            $this->broadcastSystemAlert(
                $healthData['status'] === 'critical' ? 'critical' : 'warning',
                'System health check detected issues: ' . ($healthData['message'] ?? 'Unknown issue'),
                ['super-admin']
            );
        }
    }

    /**
     * Update enquiry metrics
     */
    protected function updateEnquiryMetrics(array $enquiryData)
    {
        $totalEnquiries = \App\Models\Enquiry::count();
        $unreadCount = \App\Models\Enquiry::where('is_read', false)->count();
        $todayEnquiries = \App\Models\Enquiry::whereDate('created_at', today())->count();

        $data = [
            'total_count' => $totalEnquiries,
            'unread_count' => $unreadCount,
            'today_count' => $todayEnquiries,
            'new_enquiry' => $enquiryData
        ];

        $this->broadcastWidgetUpdate('recent-enquiries', $data, ['super-admin', 'college-admin']);
    }

    /**
     * Get revenue chart data
     */
    protected function getRevenueChartData(): array
    {
        $months = collect(range(1, 12))->map(function($month) {
            $monthlyRevenue = \App\Models\Invoice::where('status', 'paid')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');
            
            return [
                'month' => date('M', mktime(0, 0, 0, $month, 1)),
                'revenue' => $monthlyRevenue
            ];
        });

        return [
            'labels' => $months->pluck('month')->toArray(),
            'revenue' => $months->pluck('revenue')->toArray(),
            'target' => array_fill(0, 12, 500000) // Example target
        ];
    }

    /**
     * Calculate growth percentage
     */
    protected function calculateGrowthPercentage(string $metric, int $currentValue): float
    {
        $lastMonthValue = Cache::get("last_month_{$metric}", $currentValue);
        
        if ($lastMonthValue == 0) {
            return 0;
        }
        
        return round((($currentValue - $lastMonthValue) / $lastMonthValue) * 100, 1);
    }

    /**
     * Update dashboard cache
     */
    protected function updateDashboardCache(string $eventType, array $data)
    {
        $cacheKey = "dashboard_realtime_{$eventType}";
        Cache::put($cacheKey, $data, 300); // 5 minutes
    }

    /**
     * Update widget cache
     */
    protected function updateWidgetCache(string $widgetType, array $data)
    {
        $cacheKey = "widget_realtime_{$widgetType}";
        Cache::put($cacheKey, $data, 300); // 5 minutes
    }

    /**
     * Store system alert
     */
    protected function storeSystemAlert(array $alert)
    {
        $alerts = Cache::get('system_alerts', []);
        array_unshift($alerts, $alert);
        
        // Keep only last 50 alerts
        $alerts = array_slice($alerts, 0, 50);
        
        Cache::put('system_alerts', $alerts, 3600); // 1 hour
    }

    /**
     * Get alert icon based on level
     */
    protected function getAlertIcon(string $level): string
    {
        return match($level) {
            'critical' => 'exclamation-triangle',
            'warning' => 'exclamation-circle',
            'info' => 'info-circle',
            'success' => 'check-circle',
            default => 'bell'
        };
    }

    /**
     * Get active dashboard users
     */
    public function getActiveDashboardUsers(): array
    {
        $activeUsers = Cache::get('active_dashboard_users', []);
        
        // Clean up users who haven't been active in the last 5 minutes
        $fiveMinutesAgo = now()->subMinutes(5);
        $activeUsers = array_filter($activeUsers, function($lastSeen) use ($fiveMinutesAgo) {
            return \Carbon\Carbon::parse($lastSeen)->gt($fiveMinutesAgo);
        });
        
        Cache::put('active_dashboard_users', $activeUsers, 300);
        
        return array_keys($activeUsers);
    }

    /**
     * Mark user as active on dashboard
     */
    public function markUserActive(User $user)
    {
        $activeUsers = Cache::get('active_dashboard_users', []);
        $activeUsers[$user->id] = now()->toISOString();
        Cache::put('active_dashboard_users', $activeUsers, 300);
    }

    /**
     * Schedule periodic updates
     */
    public function schedulePeriodicUpdates()
    {
        // Update system metrics every minute
        $this->handleDataChange('system_health_check', [
            'status' => $this->getSystemHealth(),
            'timestamp' => now()->toISOString()
        ]);
        
        // Update financial metrics every 5 minutes
        if (now()->minute % 5 === 0) {
            $this->updateFinancialMetrics([]);
        }
        
        // Update student metrics every 10 minutes
        if (now()->minute % 10 === 0) {
            $this->updateStudentMetrics();
        }
    }

    /**
     * Get system health status
     */
    protected function getSystemHealth(): string
    {
        // Simple health check - expand based on your needs
        $dbConnected = true;
        $diskSpace = disk_free_space('/') / disk_total_space('/') > 0.1;
        $memoryUsage = memory_get_usage(true) / (1024 * 1024 * 1024) < 1; // Less than 1GB
        
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbConnected = false;
        }
        
        if (!$dbConnected) {
            return 'critical';
        }
        
        if (!$diskSpace || !$memoryUsage) {
            return 'warning';
        }
        
        return 'healthy';
    }
}