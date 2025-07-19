<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PaymentReminder extends Model
{
    protected $fillable = [
        'student_id',
        'invoice_id',
        'fee_category_id',
        'reminder_type',
        'status',
        'scheduled_date',
        'sent_at',
        'channel',
        'recipient_details',
        'message_content',
        'response_received',
        'error_message',
        'retry_count',
        'last_retry_at'
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'sent_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'recipient_details' => 'array',
        'response_received' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentReminderLog::class);
    }

   /**
 * Scope for pending reminders
 */
public function scopePending($query)
{
    return $query->where('status', 'pending');
}

/**
 * Scope for sent reminders
 */
public function scopeSent($query)
{
    return $query->where('status', 'sent');
}

/**
 * Scope for failed reminders
 */
public function scopeFailed($query)
{
    return $query->where('status', 'failed');
}

/**
 * Scope for overdue scheduled reminders
 */
public function scopeOverdue($query)
{
    return $query->where('scheduled_date', '<', now())
                 ->where('status', 'pending');
}

/**
 * Scope for reminders due today
 */
public function scopeDueToday($query)
{
    return $query->whereDate('scheduled_date', today())
                 ->where('status', 'pending');
}

/**
 * Scope for reminders by type
 */
public function scopeOfType($query, string $type)
{
    return $query->where('reminder_type', $type);
}

/**
 * Scope for reminders by channel
 */
public function scopeByChannel($query, string $channel)
{
    return $query->where('channel', $channel);
}

/**
 * Scope for recent reminders (last N days)
 */
public function scopeRecent($query, int $days = 7)
{
    return $query->where('created_at', '>=', now()->subDays($days));
}

/**
 * Check if reminder can be retried
 */
public function canRetry(): bool
{
    return $this->status === 'failed' && 
           $this->retry_count < 3 && 
           ($this->last_retry_at === null || 
            $this->last_retry_at < now()->subHours(2));
}


/**
 * Schedule retry for failed reminder
 */
public function scheduleRetry(\Carbon\Carbon $retryAt = null)
{
    if (!$this->canRetry()) {
        return false;
    }

    $this->update([
        'status' => 'pending',
        'scheduled_date' => $retryAt ?? now()->addHours(2),
        'error_message' => null
    ]);

    return true;
}

/**
 * Get formatted recipient details
 */
public function getRecipientInfo(): array
{
    return $this->recipient_details ? 
        json_decode($this->recipient_details, true) : 
        [];
}

/**
 * Check if reminder is urgent (overdue or final notice)
 */
public function isUrgent(): bool
{
    return in_array($this->reminder_type, ['escalation', 'final_notice']) ||
           ($this->reminder_type === 'overdue' && $this->scheduled_date < now()->subDays(1));
}
    /**
     * Accessors & Mutators
     */
    public function getReminderTypeBadgeAttribute(): string
    {
        return match($this->reminder_type) {
            'upcoming_due' => 'badge-info',
            'overdue' => 'badge-warning',
            'escalation' => 'badge-danger',
            'final_notice' => 'badge-dark',
            default => 'badge-secondary'
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'badge-warning',
            'sent' => 'badge-success',
            'failed' => 'badge-danger',
            'cancelled' => 'badge-secondary',
            default => 'badge-light'
        };
    }

    public function getChannelIconAttribute(): string
    {
        return match($this->channel) {
            'email' => 'fas fa-envelope',
            'sms' => 'fas fa-sms',
            'whatsapp' => 'fab fa-whatsapp',
            'phone_call' => 'fas fa-phone',
            'physical_notice' => 'fas fa-file-text',
            default => 'fas fa-bell'
        };
    }

    public function getChannelColorAttribute(): string
    {
        return match($this->channel) {
            'email' => 'text-primary',
            'sms' => 'text-success',
            'whatsapp' => 'text-success',
            'phone_call' => 'text-info',
            'physical_notice' => 'text-dark',
            default => 'text-secondary'
        };
    }

    public function getTimeToSendAttribute(): string
    {
        if ($this->status !== 'pending') {
            return 'N/A';
        }

        $now = now();
        $scheduledDate = $this->scheduled_date;

        if ($scheduledDate->isFuture()) {
            return $scheduledDate->diffForHumans($now);
        } else {
            return 'Overdue by ' . $scheduledDate->diffForHumans($now, true);
        }
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->scheduled_date->isPast();
    }

    public function getCanRetryAttribute(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    public function getFormattedRecipientAttribute(): string
    {
        $details = $this->recipient_details;
        if (!$details) return 'N/A';

        switch ($this->channel) {
            case 'email':
                return $details['email'] ?? 'N/A';
            case 'sms':
            case 'whatsapp':
            case 'phone_call':
                return $details['phone'] ?? 'N/A';
            default:
                return $details['student_name'] ?? 'N/A';
        }
    }

    /**
     * Methods
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);

        $this->logActivity('sent', 'Reminder sent successfully');
    }

    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now()
        ]);

        $this->logActivity('failed', $errorMessage ?: 'Reminder sending failed');
    }

    public function cancel(string $reason = null): void
    {
        $this->update(['status' => 'cancelled']);
        $this->logActivity('cancelled', $reason ?: 'Reminder cancelled');
    }

    public function reschedule(Carbon $newDate): void
    {
        $oldDate = $this->scheduled_date;
        $this->update([
            'scheduled_date' => $newDate,
            'status' => 'pending'
        ]);

        $this->logActivity('rescheduled', "Rescheduled from {$oldDate->format('Y-m-d H:i')} to {$newDate->format('Y-m-d H:i')}");
    }

    public function retry(): void
    {
        if (!$this->can_retry) {
            throw new \Exception('Cannot retry this reminder');
        }

        $this->update([
            'status' => 'pending',
            'error_message' => null
        ]);

        $this->logActivity('rescheduled', 'Retry attempt #' . ($this->retry_count + 1));
    }

    public function logActivity(string $action, string $details = null): void
    {
        PaymentReminderLog::create([
            'payment_reminder_id' => $this->id,
            'action' => $action,
            'details' => $details,
            'performed_by' => auth()->id(),
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ]
        ]);
    }

    /**
     * Static methods
     */
    public static function getChannels(): array
    {
        return [
            'email' => 'Email',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'phone_call' => 'Phone Call',
            'physical_notice' => 'Physical Notice'
        ];
    }

    public static function getReminderTypes(): array
    {
        return [
            'upcoming_due' => 'Upcoming Due',
            'overdue' => 'Overdue',
            'escalation' => 'Escalation',
            'final_notice' => 'Final Notice'
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'sent' => 'Sent',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled'
        ];
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reminder) {
            $reminder->logActivity('scheduled', 'Reminder scheduled');
        });
    }
}