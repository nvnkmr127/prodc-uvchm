<?php

namespace App\Traits\Attendance;

use App\Models\Attendance\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait CalculatesMetrics
{
    /**
     * Calculate attendance percentage for given records
     */
    public function calculateAttendancePercentage(Collection $attendances): float
    {
        if ($attendances->isEmpty()) {
            return 0.0;
        }

        $totalClasses = $attendances->count();
        $presentClasses = $attendances->whereIn('status', ['present', 'late', 'excused'])->count();

        return round(($presentClasses / $totalClasses) * 100, 2);
    }

    /**
     * Calculate punctuality percentage (excluding late arrivals)
     */
    public function calculatePunctualityPercentage(Collection $attendances): float
    {
        if ($attendances->isEmpty()) {
            return 0.0;
        }

        $totalClasses = $attendances->count();
        $punctualClasses = $attendances->whereIn('status', ['present', 'excused'])->count();

        return round(($punctualClasses / $totalClasses) * 100, 2);
    }

    /**
     * Calculate consecutive absences
     */
    public function calculateConsecutiveAbsences(Collection $attendances): int
    {
        if ($attendances->isEmpty()) {
            return 0;
        }

        $consecutiveAbsents = 0;
        $sortedAttendances = $attendances->sortByDesc('attendance_date');

        foreach ($sortedAttendances as $attendance) {
            if ($attendance->status === 'absent') {
                $consecutiveAbsents++;
            } else {
                break; // Stop counting when we hit a non-absent status
            }
        }

        return $consecutiveAbsents;
    }

    /**
     * Calculate monthly attendance trends
     */
    public function calculateMonthlyTrends(Collection $attendances): array
    {
        if ($attendances->isEmpty()) {
            return [];
        }

        return $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->attendance_date)->format('Y-m');
        })->map(function ($monthAttendances, $month) {
            $total = $monthAttendances->count();
            $present = $monthAttendances->whereIn('status', ['present', 'late', 'excused'])->count();
            $absent = $monthAttendances->where('status', 'absent')->count();
            $late = $monthAttendances->where('status', 'late')->count();

            return [
                'month' => $month,
                'total_classes' => $total,
                'present_count' => $present,
                'absent_count' => $absent,
                'late_count' => $late,
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                'punctuality_percentage' => $total > 0 ? round((($present - $late) / $total) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Calculate weekly attendance patterns
     */
    public function calculateWeeklyPatterns(Collection $attendances): array
    {
        if ($attendances->isEmpty()) {
            return [];
        }

        $weeklyData = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->attendance_date)->dayOfWeek;
        })->map(function ($dayAttendances, $dayOfWeek) {
            $total = $dayAttendances->count();
            $present = $dayAttendances->whereIn('status', ['present', 'late', 'excused'])->count();

            return [
                'day_of_week' => $dayOfWeek,
                'day_name' => Carbon::create()->dayOfWeek($dayOfWeek)->format('l'),
                'total_classes' => $total,
                'present_count' => $present,
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];
        });

        // Sort by day of week (Monday = 1, Sunday = 0)
        return $weeklyData->sortBy(function ($item) {
            return $item['day_of_week'] == 0 ? 7 : $item['day_of_week'];
        })->values()->toArray();
    }

    /**
     * Calculate risk level based on attendance statistics
     */
    public function calculateRiskLevel(array $stats): string
    {
        $attendancePercentage = $stats['attendance_percentage'] ?? 0;
        $consecutiveAbsents = $stats['consecutive_absents'] ?? 0;

        // Critical risk
        if ($attendancePercentage < 50 || $consecutiveAbsents >= 7) {
            return 'critical';
        }

        // High risk
        if ($attendancePercentage < 70 || $consecutiveAbsents >= 5) {
            return 'high';
        }

        // Medium risk
        if ($attendancePercentage < 85 || $consecutiveAbsents >= 3) {
            return 'medium';
        }

        // Low risk
        return 'low';
    }

    /**
     * Calculate performance level based on attendance percentage
     */
    public function calculatePerformanceLevel(float $attendancePercentage): string
    {
        if ($attendancePercentage >= 95) {
            return 'excellent';
        } elseif ($attendancePercentage >= 85) {
            return 'good';
        } elseif ($attendancePercentage >= 75) {
            return 'satisfactory';
        } elseif ($attendancePercentage >= 60) {
            return 'needs_improvement';
        } else {
            return 'critical';
        }
    }

    /**
     * Calculate attendance streak (consecutive present days)
     */
    public function calculateAttendanceStreak(Collection $attendances): int
    {
        if ($attendances->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $sortedAttendances = $attendances->sortByDesc('attendance_date');

        foreach ($sortedAttendances as $attendance) {
            if (in_array($attendance->status, ['present', 'late', 'excused'])) {
                $streak++;
            } else {
                break; // Stop counting when we hit an absent status
            }
        }

        return $streak;
    }

    /**
     * Calculate daily attendance statistics
     */
    public function calculateDailyStats(Carbon $date, ?int $batchId = null): array
    {
        $query = Attendance::where('attendance_date', $date->format('Y-m-d'));

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        $attendances = $query->get();

        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('status', 'late')->count();
        $excused = $attendances->where('status', 'excused')->count();

        return [
            'date' => $date->format('Y-m-d'),
            'total_classes' => $total,
            'present_count' => $present,
            'absent_count' => $absent,
            'late_count' => $late,
            'excused_count' => $excused,
            'attendance_percentage' => $total > 0 ? round((($present + $late + $excused) / $total) * 100, 2) : 0,
            'punctuality_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate batch comparison metrics
     */
    public function calculateBatchComparison(array $batchIds, array $dateRange = []): array
    {
        $results = [];

        foreach ($batchIds as $batchId) {
            $query = Attendance::where('batch_id', $batchId);

            if (! empty($dateRange['from'])) {
                $query->where('attendance_date', '>=', $dateRange['from']);
            }
            if (! empty($dateRange['to'])) {
                $query->where('attendance_date', '<=', $dateRange['to']);
            }

            $attendances = $query->get();
            $batchInfo = \App\Models\Batch::find($batchId);

            $total = $attendances->count();
            $present = $attendances->whereIn('status', ['present', 'late', 'excused'])->count();

            $results[] = [
                'batch_id' => $batchId,
                'batch_name' => $batchInfo->name ?? 'Unknown',
                'total_classes' => $total,
                'present_count' => $present,
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                'student_count' => $batchInfo->students()->count() ?? 0,
            ];
        }

        // Sort by attendance percentage descending
        usort($results, function ($a, $b) {
            return $b['attendance_percentage'] <=> $a['attendance_percentage'];
        });

        return $results;
    }

    /**
     * Calculate improvement metrics (comparing two time periods)
     */
    public function calculateImprovementMetrics(Collection $currentPeriod, Collection $previousPeriod): array
    {
        $currentPercentage = $this->calculateAttendancePercentage($currentPeriod);
        $previousPercentage = $this->calculateAttendancePercentage($previousPeriod);

        $improvement = $currentPercentage - $previousPercentage;
        $improvementPercentage = $previousPercentage > 0 ?
            round(($improvement / $previousPercentage) * 100, 2) : 0;

        return [
            'current_percentage' => $currentPercentage,
            'previous_percentage' => $previousPercentage,
            'improvement' => $improvement,
            'improvement_percentage' => $improvementPercentage,
            'trend' => $improvement > 0 ? 'improving' : ($improvement < 0 ? 'declining' : 'stable'),
        ];
    }

    /**
     * Calculate subject-wise attendance metrics
     */
    public function calculateSubjectWiseMetrics(int $studentId, array $dateRange = []): array
    {
        $query = Attendance::where('student_id', $studentId)->whereNotNull('subject_id');

        if (! empty($dateRange['from'])) {
            $query->where('attendance_date', '>=', $dateRange['from']);
        }
        if (! empty($dateRange['to'])) {
            $query->where('attendance_date', '<=', $dateRange['to']);
        }

        $attendances = $query->with('subject')->get();

        return $attendances->groupBy('subject_id')->map(function ($subjectAttendances) {
            $subject = $subjectAttendances->first()->subject;
            $total = $subjectAttendances->count();
            $present = $subjectAttendances->whereIn('status', ['present', 'late', 'excused'])->count();

            return [
                'subject_id' => $subject->id ?? null,
                'subject_name' => $subject->name ?? 'Unknown',
                'total_classes' => $total,
                'present_count' => $present,
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Calculate late arrival patterns
     */
    public function calculateLatePatterns(Collection $attendances): array
    {
        $lateAttendances = $attendances->where('status', 'late')->where('late_minutes', '>', 0);

        if ($lateAttendances->isEmpty()) {
            return [
                'total_late_days' => 0,
                'average_late_minutes' => 0,
                'max_late_minutes' => 0,
                'late_frequency_by_day' => [],
            ];
        }

        $totalLateDays = $lateAttendances->count();
        $totalLateMinutes = $lateAttendances->sum('late_minutes');
        $averageLateMinutes = round($totalLateMinutes / $totalLateDays, 2);
        $maxLateMinutes = $lateAttendances->max('late_minutes');

        // Group by day of week
        $lateByDay = $lateAttendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->attendance_date)->format('l');
        })->map(function ($dayLates, $day) {
            return [
                'day' => $day,
                'count' => $dayLates->count(),
                'average_minutes' => round($dayLates->avg('late_minutes'), 2),
            ];
        })->values()->toArray();

        return [
            'total_late_days' => $totalLateDays,
            'average_late_minutes' => $averageLateMinutes,
            'max_late_minutes' => $maxLateMinutes,
            'late_frequency_by_day' => $lateByDay,
        ];
    }
}
