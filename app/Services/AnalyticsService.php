<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;

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
     * Calculate fee collection efficiency
     */
    private function calculateCollectionEfficiency(): float
    {
        $totalExpected = Payment::sum('amount');
        $totalCollected = Payment::where('status', 'completed')->sum('amount');

        return $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;
    }
}
