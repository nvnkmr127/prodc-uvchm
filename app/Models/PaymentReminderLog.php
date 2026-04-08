<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reminder_id',
        'action',
        'details',
        'metadata',
        'response_data',
        'performed_by',
        'ip_address',
        'user_agent',
        'action_timestamp',
    ];

    protected $casts = [
        'metadata' => 'array',
        'response_data' => 'array',
        'action_timestamp' => 'datetime',
    ];

    /**
     * Get the payment reminder that this log belongs to
     */
    public function paymentReminder(): BelongsTo
    {
        return $this->belongsTo(PaymentReminder::class);
    }

    /**
     * Get the user who performed the action
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope for specific actions
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for specific reminder
     */
    public function scopeForReminder($query, int $reminderId)
    {
        return $query->where('payment_reminder_id', $reminderId);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('action_timestamp', '>=', now()->subDays($days));
    }

    /**
     * Scope for successful actions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('action', 'sent');
    }

    /**
     * Scope for failed actions
     */
    public function scopeFailed($query)
    {
        return $query->where('action', 'failed');
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set user info when creating logs
        static::creating(function ($log) {
            if (auth()->check()) {
                $log->performed_by = auth()->id();
            }

            // Set IP address and user agent from request if available
            if (request()) {
                $log->ip_address = request()->ip();
                $log->user_agent = request()->userAgent();
            }

            // Set action timestamp if not provided
            if (! $log->action_timestamp) {
                $log->action_timestamp = now();
            }
        });
    }

    /**
     * Create a log entry
     */
    public static function createLog(
        PaymentReminder $reminder,
        string $action,
        ?string $details = null,
        array $metadata = [],
        array $responseData = []
    ): self {
        return self::create([
            'payment_reminder_id' => $reminder->id,
            'action' => $action,
            'details' => $details,
            'metadata' => array_merge([
                'reminder_type' => $reminder->reminder_type,
                'channel' => $reminder->channel,
                'student_id' => $reminder->student_id,
                'invoice_id' => $reminder->invoice_id,
            ], $metadata),
            'response_data' => $responseData,
        ]);
    }

    /**
     * Get formatted action timestamp
     */
    public function getFormattedTimestampAttribute(): string
    {
        return $this->action_timestamp->format('d M Y, H:i:s');
    }

    /**
     * Get action icon based on action type
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created' => '📄',
            'sent' => '✅',
            'failed' => '❌',
            'cancelled' => '🚫',
            'retried' => '🔄',
            'scheduled' => '⏰',
            'processing' => '⚙️',
            default => '📝'
        };
    }

    /**
     * Get action color for UI
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'sent' => 'success',
            'failed' => 'danger',
            'cancelled' => 'warning',
            'processing' => 'info',
            'retried' => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Get summary statistics for logs
     */
    public static function getStatistics(int $days = 30): array
    {
        $query = self::where('action_timestamp', '>=', now()->subDays($days));

        return [
            'total_actions' => $query->count(),
            'sent_count' => $query->clone()->where('action', 'sent')->count(),
            'failed_count' => $query->clone()->where('action', 'failed')->count(),
            'cancelled_count' => $query->clone()->where('action', 'cancelled')->count(),
            'retry_count' => $query->clone()->where('action', 'retried')->count(),
            'success_rate' => $query->clone()->where('action', 'sent')->count() > 0 ?
                round(($query->clone()->where('action', 'sent')->count() / $query->count()) * 100, 2) : 0,
            'actions_by_day' => $query->selectRaw('DATE(action_timestamp) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray(),
            'actions_by_type' => $query->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
        ];
    }

    /**
     * Get error details from response data
     */
    public function getErrorDetailsAttribute(): ?string
    {
        if ($this->action !== 'failed' || ! $this->response_data) {
            return null;
        }

        return $this->response_data['error'] ?? $this->details;
    }

    /**
     * Check if this log represents a successful action
     */
    public function isSuccessful(): bool
    {
        return $this->action === 'sent';
    }

    /**
     * Check if this log represents a failed action
     */
    public function isFailed(): bool
    {
        return $this->action === 'failed';
    }
}
