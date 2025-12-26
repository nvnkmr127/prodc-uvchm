<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Student;
use App\Models\User;
use App\Models\Attendance\ParentContact;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_type',
        'category',
        'priority',
        'channel',
        'recipient_type',
        'recipient_id',
        'student_id',
        'parent_contact_id',
        'sender_id',
        'subject',
        'message',
        'template_used',
        'personalization_data',
        'delivery_status',
        'delivery_attempts',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
        'provider_response',
        'provider_message_id',
        'cost',
        'batch_id',
        'triggered_by',
        'metadata'
    ];

    protected $casts = [
        'personalization_data' => 'array',
        'provider_response' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'cost' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the student this notification is related to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the parent contact if this was sent to a parent
     */
    public function parentContact(): BelongsTo
    {
        return $this->belongsTo(ParentContact::class);
    }

    /**
     * Get the user who sent this notification
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the entity that triggered this notification (polymorphic)
     */
    public function triggeredBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for successful deliveries
     */
    public function scopeDelivered($query)
    {
        return $query->where('delivery_status', 'delivered');
    }

    /**
     * Scope for failed deliveries
     */
    public function scopeFailed($query)
    {
        return $query->where('delivery_status', 'failed');
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('delivery_status', 'pending');
    }

    /**
     * Scope for specific notification type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope for specific channel
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for specific priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for today's notifications
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for attendance-related notifications
     */
    public function scopeAttendanceRelated($query)
    {
        return $query->where('category', 'attendance');
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(array $providerResponse = null): void
    {
        $this->update([
            'delivery_status' => 'sent',
            'sent_at' => now(),
            'delivery_attempts' => $this->delivery_attempts + 1,
            'provider_response' => $providerResponse,
            'provider_message_id' => $providerResponse['message_id'] ?? null,
        ]);
    }

    /**
     * Mark notification as delivered
     */
    public function markAsDelivered(array $deliveryData = null): void
    {
        $this->update([
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
            'provider_response' => array_merge($this->provider_response ?? [], $deliveryData ?? []),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(string $errorMessage, array $providerResponse = null): void
    {
        $this->update([
            'delivery_status' => 'failed',
            'failed_at' => now(),
            'delivery_attempts' => $this->delivery_attempts + 1,
            'error_message' => $errorMessage,
            'provider_response' => $providerResponse,
        ]);
    }

    /**
     * Retry failed notification
     */
    public function retry(): void
    {
        if ($this->delivery_attempts < 3) {
            $this->update([
                'delivery_status' => 'pending',
                'error_message' => null,
                'failed_at' => null,
            ]);
        }
    }

    /**
     * Get delivery time in seconds
     */
    public function getDeliveryTimeAttribute(): ?int
    {
        if ($this->sent_at && $this->delivered_at) {
            return $this->sent_at->diffInSeconds($this->delivered_at);
        }
        return null;
    }

    /**
     * Get status badge for display
     */
    public function getStatusBadgeAttribute(): array
    {
        return match($this->delivery_status) {
            'delivered' => ['text' => 'Delivered', 'class' => 'success'],
            'sent' => ['text' => 'Sent', 'class' => 'info'],
            'failed' => ['text' => 'Failed', 'class' => 'danger'],
            'pending' => ['text' => 'Pending', 'class' => 'warning'],
            'cancelled' => ['text' => 'Cancelled', 'class' => 'secondary'],
            default => ['text' => 'Unknown', 'class' => 'dark'],
        };
    }

    /**
     * Get priority badge for display
     */
    public function getPriorityBadgeAttribute(): array
    {
        return match($this->priority) {
            'urgent' => ['text' => 'Urgent', 'class' => 'danger'],
            'high' => ['text' => 'High', 'class' => 'warning'],
            'normal' => ['text' => 'Normal', 'class' => 'info'],
            'low' => ['text' => 'Low', 'class' => 'secondary'],
            default => ['text' => 'Normal', 'class' => 'info'],
        };
    }

    /**
     * Get channel icon for display
     */
    public function getChannelIconAttribute(): string
    {
        return match($this->channel) {
            'sms' => 'fa-sms',
            'email' => 'fa-envelope',
            'whatsapp' => 'fa-whatsapp',
            'push' => 'fa-bell',
            'voice' => 'fa-phone',
            'in_app' => 'fa-bell-o',
            default => 'fa-paper-plane',
        };
    }

    /**
     * Calculate notification cost based on channel and message length
     */
    public function calculateCost(): float
    {
        $rates = config('notifications.rates', [
            'sms' => 0.05,
            'whatsapp' => 0.02,
            'email' => 0.001,
            'voice' => 0.10,
        ]);

        $baseRate = $rates[$this->channel] ?? 0;
        
        // For SMS, charge per 160 characters
        if ($this->channel === 'sms') {
            $parts = ceil(strlen($this->message) / 160);
            return $baseRate * $parts;
        }

        return $baseRate;
    }

    /**
     * Get delivery statistics for dashboard
     */
    public static function getDeliveryStats(array $filters = []): array
    {
        $query = static::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }
        if (isset($filters['type'])) {
            $query->where('notification_type', $filters['type']);
        }

        $total = $query->count();
        $delivered = $query->clone()->where('delivery_status', 'delivered')->count();
        $failed = $query->clone()->where('delivery_status', 'failed')->count();
        $pending = $query->clone()->where('delivery_status', 'pending')->count();

        return [
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $pending,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
            'average_delivery_time' => static::where('delivery_status', 'delivered')
                                           ->whereNotNull('delivery_time')
                                           ->avg('delivery_time'),
            'total_cost' => $query->sum('cost'),
        ];
    }

    /**
     * Get channel performance statistics
     */
    public static function getChannelPerformance(): array
    {
        return static::selectRaw('
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN delivery_status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN delivery_status = "failed" THEN 1 ELSE 0 END) as failed,
                AVG(CASE WHEN delivery_status = "delivered" THEN delivery_time END) as avg_delivery_time,
                SUM(cost) as total_cost
            ')
            ->groupBy('channel')
            ->get()
            ->map(function ($item) {
                $item->delivery_rate = $item->total > 0 ? round(($item->delivered / $item->total) * 100, 2) : 0;
                return $item;
            });
    }

    /**
     * Clean up old notification logs
     */
    public static function cleanupOldLogs(int $daysToKeep = 90): int
    {
        return static::where('created_at', '<', now()->subDays($daysToKeep))->delete();
    }

    /**
     * Get failed notifications that can be retried
     */
    public static function getRetryableFailures(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('delivery_status', 'failed')
                    ->where('delivery_attempts', '<', 3)
                    ->where('failed_at', '>', now()->subHours(24))
                    ->orderBy('priority', 'desc')
                    ->orderBy('failed_at')
                    ->get();
    }

    /**
     * Get notification trends for analytics
     */
    public static function getTrends(int $days = 30): array
    {
        $data = static::selectRaw('
                DATE(created_at) as date,
                notification_type,
                channel,
                COUNT(*) as count,
                SUM(CASE WHEN delivery_status = "delivered" THEN 1 ELSE 0 END) as delivered
            ')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(['date', 'notification_type', 'channel'])
            ->orderBy('date')
            ->get();

        return $data->groupBy('date')->map(function ($dailyData) {
            return [
                'total' => $dailyData->sum('count'),
                'delivered' => $dailyData->sum('delivered'),
                'by_type' => $dailyData->groupBy('notification_type'),
                'by_channel' => $dailyData->groupBy('channel'),
            ];
        })->toArray();
    }
}