<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Student, User, Payment, Attendance};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardAnalyticsController extends Controller
{
    public function overview()
    {
        $overview = Cache::remember('dashboard_overview', 300, function() {
            return [
                'total_users' => User::count(),
                'total_students' => Student::count(),
                'today_attendance_rate' => $this->getTodayAttendanceRate(),
                'monthly_revenue' => Payment::whereMonth('created_at', now()->month)->sum('amount'),
                'system_health' => 'Good', // You can implement actual health checks
            ];
        });
        
        return response()->json($overview);
    }
    
    public function usage()
    {
        $usage = [
            'daily_active_users' => User::whereDate('last_login_at', today())->count(),
            'weekly_active_users' => User::whereBetween('last_login_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'monthly_active_users' => User::whereMonth('last_login_at', now()->month)->count(),
            'feature_usage' => $this->getFeatureUsage(),
        ];
        
        return response()->json($usage);
    }
    
    public function performance()
    {
        $performance = [
            'response_time' => $this->getAverageResponseTime(),
            'database_queries' => $this->getDatabaseQueryStats(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'error_rate' => $this->getErrorRate(),
        ];
        
        return response()->json($performance);
    }
    
    private function getTodayAttendanceRate()
    {
        $totalClasses = Attendance::whereDate('date', today())->count();
        if ($totalClasses === 0) return 0;
        
        $presentClasses = Attendance::whereDate('date', today())->where('status', 'present')->count();
        return round(($presentClasses / $totalClasses) * 100, 2);
    }
    
    private function getFeatureUsage()
    {
        return [
            'student_management' => User::whereHas('permissions', function($query) {
                $query->where('name', 'manage students');
            })->count(),
            'financial_management' => User::whereHas('permissions', function($query) {
                $query->where('name', 'manage payments');
            })->count(),
            'attendance_management' => User::whereHas('permissions', function($query) {
                $query->where('name', 'manage attendance');
            })->count(),
        ];
    }
    
    private function getAverageResponseTime()
    {
        // This would require implementing actual response time tracking
        return '250ms';
    }
    
    private function getDatabaseQueryStats()
    {
        // This would require implementing query tracking
        return [
            'total_queries' => 1250,
            'slow_queries' => 15,
            'average_query_time' => '12ms'
        ];
    }
    
    private function getCacheHitRate()
    {
        // This would require implementing cache statistics
        return '85%';
    }
    
    private function getErrorRate()
    {
        // This would require implementing error tracking
        return '0.5%';
    }
}
