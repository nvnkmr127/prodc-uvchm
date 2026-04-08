<?php

// Create this model: php artisan make:model ETimeOfficeSyncLog

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ETimeOfficeSyncLog extends Model
{
    protected $table = 'etimeoffice_sync_logs';

    protected $fillable = [
        'sync_type',
        'date_range_type',
        'date_range_start',
        'date_range_end',
        'employee_codes',
        'test_mode',
        'status',
        'total_records',
        'processed_records',
        'created_records',
        'updated_records',
        'skipped_records',
        'errors',
        'started_at',
        'completed_at',
        'duration_seconds',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'employee_codes' => 'array',
        'errors' => 'array',
        'test_mode' => 'boolean',
        'date_range_start' => 'datetime',
        'date_range_end' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who initiated this sync
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_records == 0) {
            return 0;
        }

        $successfulRecords = $this->created_records + $this->updated_records;

        return round(($successfulRecords / $this->total_records) * 100, 2);
    }

    /**
     * Scope for recent syncs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for successful syncs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get formatted date range
     */
    public function getFormattedDateRangeAttribute(): string
    {
        $start = $this->date_range_start->format('M j, Y H:i');
        $end = $this->date_range_end->format('M j, Y H:i');

        if ($this->date_range_start->isSameDay($this->date_range_end)) {
            return $this->date_range_start->format('M j, Y')." ({$this->date_range_start->format('H:i')} - {$this->date_range_end->format('H:i')})";
        }

        return "{$start} - {$end}";
    }

    /**
     * Create a new sync log entry
     */
    public static function createSyncLog(array $data): self
    {
        return self::create(array_merge([
            'started_at' => now(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'sync_type' => 'manual',
        ], $data));
    }

    /**
     * Complete the sync log with results
     */
    public function completeSyncLog(array $results): void
    {
        $this->update([
            'status' => $results['success'] ? 'success' : 'failed',
            'total_records' => $results['total_records'] ?? 0,
            'processed_records' => $results['processed_records'] ?? 0,
            'created_records' => $results['created_records'] ?? 0,
            'updated_records' => $results['updated_records'] ?? 0,
            'skipped_records' => $results['skipped_records'] ?? 0,
            'errors' => $results['errors'] ?? [],
            'completed_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
        ]);
    }
}
