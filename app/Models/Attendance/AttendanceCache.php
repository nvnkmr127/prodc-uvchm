<?php

namespace App\Models\Attendance;

use App\Models\Batch;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'batch_id',
        'cache_type',
        'period_type',
        'period_value',
        'calculation_date',
        'total_classes',
        'present_classes',
        'absent_classes',
        'late_classes',
        'excused_classes',
        'attendance_percentage',
        'punctuality_percentage',
        'trend_direction',
        'last_attendance_date',
        'consecutive_absents',
        'analytics_data',
        'is_current',
        'expires_at',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'last_attendance_date' => 'date',
        'attendance_percentage' => 'decimal:2',
        'punctuality_percentage' => 'decimal:2',
        'analytics_data' => 'array',
        'is_current' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the student this cache belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the batch this cache belongs to
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Scope for current/active cache entries
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired cache entries
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->orWhere('is_current', false);
    }

    /**
     * Scope for specific cache type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('cache_type', $type);
    }

    /**
     * Scope for specific period
     */
    public function scopeForPeriod($query, string $periodType, string $periodValue)
    {
        return $query->where('period_type', $periodType)
            ->where('period_value', $periodValue);
    }

    /**
     * Scope for low attendance students
     */
    public function scopeLowAttendance($query, float $threshold = 75.0)
    {
        return $query->where('attendance_percentage', '<', $threshold);
    }

    /**
     * Get or create cache entry for student
     */
    public static function getOrCreateForStudent(
        int $studentId,
        string $cacheType = 'overall',
        string $periodType = 'academic_year',
        ?string $periodValue = null
    ): self {
        $periodValue = $periodValue ?? now()->format('Y');

        return static::firstOrCreate(
            [
                'student_id' => $studentId,
                'cache_type' => $cacheType,
                'period_type' => $periodType,
                'period_value' => $periodValue,
            ],
            [
                'calculation_date' => now()->toDateString(),
                'total_classes' => 0,
                'present_classes' => 0,
                'absent_classes' => 0,
                'late_classes' => 0,
                'excused_classes' => 0,
                'attendance_percentage' => 100.00,
                'punctuality_percentage' => 100.00,
                'is_current' => true,
                'expires_at' => now()->addHours(6), // Cache for 6 hours
            ]
        );
    }

    /**
     * Update cache with fresh attendance data
     */
    public function updateWithAttendanceData(array $attendanceData): void
    {
        $totalClasses = $attendanceData['total_classes'] ?? 0;
        $presentClasses = $attendanceData['present_classes'] ?? 0;
        $lateClasses = $attendanceData['late_classes'] ?? 0;

        $attendancePercentage = $totalClasses > 0
            ? (($presentClasses + $lateClasses) / $totalClasses) * 100
            : 100;

        $punctualityPercentage = $totalClasses > 0
            ? ($presentClasses / $totalClasses) * 100
            : 100;

        $this->update([
            'calculation_date' => now()->toDateString(),
            'total_classes' => $attendanceData['total_classes'] ?? 0,
            'present_classes' => $attendanceData['present_classes'] ?? 0,
            'absent_classes' => $attendanceData['absent_classes'] ?? 0,
            'late_classes' => $attendanceData['late_classes'] ?? 0,
            'excused_classes' => $attendanceData['excused_classes'] ?? 0,
            'attendance_percentage' => round($attendancePercentage, 2),
            'punctuality_percentage' => round($punctualityPercentage, 2),
            'trend_direction' => $this->calculateTrend($attendancePercentage),
            'last_attendance_date' => $attendanceData['last_attendance_date'] ?? null,
            'consecutive_absents' => $attendanceData['consecutive_absents'] ?? 0,
            'analytics_data' => $attendanceData['analytics_data'] ?? [],
            'is_current' => true,
            'expires_at' => now()->addHours(6),
        ]);
    }

    /**
     * Calculate trend direction based on previous data
     */
    private function calculateTrend(float $currentPercentage): string
    {
        $previousCache = static::where('student_id', $this->student_id)
            ->where('cache_type', $this->cache_type)
            ->where('id', '!=', $this->id)
            ->orderBy('calculation_date', 'desc')
            ->first();

        if (! $previousCache) {
            return 'stable';
        }

        $difference = $currentPercentage - $previousCache->attendance_percentage;

        if ($difference > 2) {
            return 'improving';
        } elseif ($difference < -2) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Mark cache as expired
     */
    public function markExpired(): void
    {
        $this->update([
            'is_current' => false,
            'expires_at' => now(),
        ]);
    }

    /**
     * Get status badge for attendance percentage
     */
    public function getAttendanceStatusAttribute(): array
    {
        $percentage = $this->attendance_percentage;

        if ($percentage >= 90) {
            return ['text' => 'Excellent', 'class' => 'success'];
        } elseif ($percentage >= 80) {
            return ['text' => 'Good', 'class' => 'info'];
        } elseif ($percentage >= 75) {
            return ['text' => 'Satisfactory', 'class' => 'warning'];
        } else {
            return ['text' => 'Poor', 'class' => 'danger'];
        }
    }

    /**
     * Get trend badge
     */
    public function getTrendBadgeAttribute(): array
    {
        return match ($this->trend_direction) {
            'improving' => ['text' => 'Improving', 'class' => 'success', 'icon' => 'fa-arrow-up'],
            'declining' => ['text' => 'Declining', 'class' => 'danger', 'icon' => 'fa-arrow-down'],
            default => ['text' => 'Stable', 'class' => 'secondary', 'icon' => 'fa-minus'],
        };
    }

    /**
     * Clean up expired cache entries
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }

    /**
     * Refresh cache for all students
     */
    public static function refreshAllStudents(): void
    {
        // This would be implemented by the AnalyticsService
        // when we create it in the next step
    }

    /**
     * Get summary statistics for dashboard
     */
    public static function getDashboardSummary(): array
    {
        $current = static::current();

        return [
            'total_students' => $current->distinct('student_id')->count(),
            'excellent_attendance' => $current->where('attendance_percentage', '>=', 90)->count(),
            'good_attendance' => $current->whereBetween('attendance_percentage', [80, 89.99])->count(),
            'satisfactory_attendance' => $current->whereBetween('attendance_percentage', [75, 79.99])->count(),
            'poor_attendance' => $current->where('attendance_percentage', '<', 75)->count(),
            'improving_trend' => $current->where('trend_direction', 'improving')->count(),
            'declining_trend' => $current->where('trend_direction', 'declining')->count(),
            'average_attendance' => round($current->avg('attendance_percentage'), 2),
        ];
    }
}
