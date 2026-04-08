<?php

namespace App\Models\Attendance;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'employee_code',
        'scan_datetime',
        'scan_type',
        'raw_data',
        'processed',
        'student_id',
        'attendance_id',
        'processing_notes',
        'device_manufacturer',
        'device_location',
        'sync_status',
        'failure_reason',
    ];

    protected $casts = [
        'scan_datetime' => 'datetime',
        'raw_data' => 'array',
        'processed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'scan_datetime',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the student associated with this biometric log
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the attendance record created from this log
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Scope for unprocessed logs
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Scope for processed logs
     */
    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    /**
     * Scope for specific device
     */
    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('scan_datetime', [$startDate, $endDate]);
    }

    /**
     * Scope for failed processing
     */
    public function scopeFailed($query)
    {
        return $query->where('processed', false)
            ->whereNotNull('failure_reason');
    }

    /**
     * Mark this log as processed
     */
    public function markAsProcessed(?Attendance $attendance = null, ?string $notes = null): void
    {
        $this->update([
            'processed' => true,
            'attendance_id' => $attendance?->id,
            'processing_notes' => $notes,
            'sync_status' => 'success',
        ]);
    }

    /**
     * Mark this log as failed processing
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'processed' => false,
            'failure_reason' => $reason,
            'sync_status' => 'failed',
        ]);
    }

    /**
     * Get formatted scan time
     */
    public function getFormattedScanTimeAttribute(): string
    {
        return $this->scan_datetime->format('Y-m-d H:i:s');
    }

    /**
     * Get scan time in human readable format
     */
    public function getHumanScanTimeAttribute(): string
    {
        return $this->scan_datetime->diffForHumans();
    }

    /**
     * Check if this is a duplicate scan within specified minutes
     */
    public function isDuplicateScan(int $windowMinutes = 5): bool
    {
        $windowStart = $this->scan_datetime->copy()->subMinutes($windowMinutes);
        $windowEnd = $this->scan_datetime->copy()->addMinutes($windowMinutes);

        return static::where('employee_code', $this->employee_code)
            ->where('device_id', $this->device_id)
            ->whereBetween('scan_datetime', [$windowStart, $windowEnd])
            ->where('id', '!=', $this->id)
            ->exists();
    }

    /**
     * Get processing status with color
     */
    public function getStatusBadgeAttribute(): array
    {
        if ($this->processed && $this->attendance_id) {
            return ['text' => 'Processed', 'class' => 'success'];
        } elseif ($this->processed && ! $this->attendance_id) {
            return ['text' => 'Ignored', 'class' => 'warning'];
        } elseif (! $this->processed && $this->failure_reason) {
            return ['text' => 'Failed', 'class' => 'danger'];
        } else {
            return ['text' => 'Pending', 'class' => 'info'];
        }
    }
}
