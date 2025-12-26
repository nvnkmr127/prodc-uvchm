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
        'student_fee_id', 
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
     * ✅ FIXED: Added explicit return type declaration
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * ✅ FIXED: Relationship changed from invoice() to studentFee()
     * Get the fee component associated with the reminder.
     */
    public function studentFee(): BelongsTo
    {
        return $this->belongsTo(StudentFee::class);
    }

    /**
     * Get the fee category associated with the reminder
     */
    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    /**
     * Get reminder logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(PaymentReminderLog::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeScheduledFor($query, $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }

    public function scopeOverdue($query)
    {
        return $query->where('reminder_type', 'overdue');
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Helper methods
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canRetry(): bool
    {
        return $this->hasFailed() && $this->retry_count < 3;
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now()
        ]);
    }

    public function addLog(string $action, array $details = []): void
    {
        $this->logs()->create([
            'action' => $action,
            'details' => json_encode($details),
            'performed_by' => auth()->id(),
            'action_timestamp' => now()
        ]);
    }

    /**
     * Get formatted message content with placeholders replaced
     */
    public function getFormattedMessage(): string
    {
        $message = $this->message_content;
        
        if ($this->student) {
            $message = str_replace('[STUDENT_NAME]', $this->student->name, $message);
            $message = str_replace('[ENROLLMENT_NUMBER]', $this->student->enrollment_number, $message);
        }

        if ($this->studentFee) {
            $remainingAmount = $this->studentFee->getRemainingAmount();
            $message = str_replace('[AMOUNT]', '₹' . number_format($remainingAmount, 2), $message);
            
            if ($this->studentFee->due_date) {
                $message = str_replace('[DUE_DATE]', $this->studentFee->due_date->format('d/m/Y'), $message);
            }
        }

        return $message;
    }

    /**
     * Get reminder priority score
     */
    public function getPriorityScore(): int
    {
        $score = 0;

        // Base score by type
        $typeScores = [
            'upcoming_due' => 1,
            'overdue' => 3,
            'escalation' => 5,
            'final_notice' => 7
        ];

        $score += $typeScores[$this->reminder_type] ?? 0;

        // Add score based on overdue amount
        if ($this->studentFee) {
            $amount = $this->studentFee->getRemainingAmount();
            if ($amount > 10000) $score += 3;
            elseif ($amount > 5000) $score += 2;
            elseif ($amount > 1000) $score += 1;
        }

        // Add score for retry count
        $score += $this->retry_count;

        return $score;
    }

    /**
     * Get recipient information for validation
     */
    public function getRecipientInfo(): array
    {
        $recipientDetails = $this->recipient_details ?? [];

        // If recipient_details is empty, try to get from student
        if (empty($recipientDetails) && $this->student) {
            $recipientDetails = [
                'email' => $this->student->email,
                'phone' => $this->student->student_mobile ?? $this->student->father_mobile,
                'student_name' => $this->student->name,
                'enrollment_number' => $this->student->enrollment_number
            ];
        }

        return $recipientDetails;
    }
}