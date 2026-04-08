<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get dashboard analytics data
     */
    public function getDashboardAnalytics(): array
    {
        return [
            'user_stats' => $this->getUserStats(),
            'student_stats' => $this->getStudentStats(),
            'payment_stats' => $this->getPaymentStats(),
            'attendance_stats' => $this->getAttendanceStats(),
        ];
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'users_by_role' => User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->groupBy('roles.name')
                ->select('roles.name', DB::raw('count(*) as count'))
                ->pluck('count', 'name')
                ->toArray(),
        ];
    }

    /**
     * Get student statistics
     */
    public function getStudentStats(): array
    {
        return [
            'total_students' => Student::count(),
            'active_students' => Student::where('status', 'active')->count(),
            'new_admissions_this_month' => Student::whereMonth('created_at', now()->month)->count(),
            'students_by_course' => Student::join('batches', 'students.batch_id', '=', 'batches.id')
                ->join('courses', 'batches.course_id', '=', 'courses.id')
                ->groupBy('courses.name')
                ->select('courses.name', DB::raw('count(*) as count'))
                ->pluck('count', 'name')
                ->toArray(),
        ];
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        return [
            'total_revenue' => Payment::sum('amount'),
            'monthly_revenue' => Payment::whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')->sum('amount'),
            'successful_payments' => Payment::where('status', 'completed')->count(),
            'failed_payments' => Payment::where('status', 'failed')->count(),
            'payment_methods' => Payment::where('status', 'completed')
                ->groupBy('payment_method')
                ->select('payment_method', DB::raw('count(*) as count'))
                ->pluck('count', 'payment_method')
                ->toArray(),
        ];
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats(): array
    {
        $today = now()->format('Y-m-d');
        $currentMonth = now()->month;

        return [
            'today_attendance' => Attendance::whereDate('date', $today)->count(),
            'monthly_attendance' => Attendance::whereMonth('date', $currentMonth)->count(),
            'average_attendance' => $this->calculateAverageAttendance(),
            'attendance_trends' => $this->getAttendanceTrends(),
        ];
    }

    /**
     * Calculate average attendance percentage
     */
    private function calculateAverageAttendance(): float
    {
        $totalStudents = Student::where('status', 'active')->count();
        if ($totalStudents === 0) {
            return 0;
        }

        $currentMonth = now()->month;
        $attendanceRecords = Attendance::whereMonth('date', $currentMonth)
            ->where('status', 'present')
            ->count();

        $workingDays = now()->day; // Simplified calculation
        $expectedAttendance = $totalStudents * $workingDays;

        return $expectedAttendance > 0 ? ($attendanceRecords / $expectedAttendance) * 100 : 0;
    }

    /**
     * Get attendance trends for the last 7 days
     */
    private function getAttendanceTrends(): array
    {
        $trends = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trends[$date] = Attendance::whereDate('date', $date)
                ->where('status', 'present')
                ->count();
        }

        return $trends;
    }

    /**
     * Get revenue trends
     */
    public function getRevenueTrends(int $days = 30): array
    {
        $trends = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trends[$date] = Payment::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('amount');
        }

        return $trends;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'growth_rate' => $this->calculateGrowthRate(),
            'retention_rate' => $this->calculateRetentionRate(),
            'collection_efficiency' => $this->calculateCollectionEfficiency(),
        ];
    }

    /**
     * Calculate student growth rate
     */
    private function calculateGrowthRate(): float
    {
        $currentMonth = Student::whereMonth('created_at', now()->month)->count();
        $previousMonth = Student::whereMonth('created_at', now()->subMonth()->month)->count();

        if ($previousMonth === 0) {
            return $currentMonth > 0 ? 100 : 0;
        }

        return (($currentMonth - $previousMonth) / $previousMonth) * 100;
    }

    /**
     * Calculate student retention rate
     */
    private function calculateRetentionRate(): float
    {
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();

        return $totalStudents > 0 ? ($activeStudents / $totalStudents) * 100 : 0;
    }

    /**
     * Calculate fee collection efficiency
     */
    private function calculateCollectionEfficiency(): float
    {
        $totalExpected = Payment::sum('amount');
        $totalCollected = Payment::where('status', 'completed')->sum('amount');

        return $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;
    }
}
