<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\WebhookEnabled;

class Invoice extends Model
{
    use WebhookEnabled;

    protected $fillable = [
        'student_id',
        'term_number',
        'invoice_number',
        'issue_date',
        'due_date',
        'total_amount',
        'concession_amount',
        'concession_notes',
        'paid_amount',
        'due_amount',
        'status',
        'token',
        'description',
        'payment_method',
        'transaction_id',
        'paid_at'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'concession_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    /**
     * Automatically calculate due_amount and status when creating/updating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->calculateDueAmountAndStatus();
        });

        static::updating(function ($invoice) {
            if ($invoice->isDirty(['total_amount', 'concession_amount', 'paid_amount'])) {
                $invoice->calculateDueAmountAndStatus();
            }
        });
    }

    /**
     * Calculate due amount and status automatically
     */
    public function calculateDueAmountAndStatus(): void
    {
        // Calculate due amount
        $this->due_amount = max(0, 
            $this->total_amount - 
            ($this->concession_amount ?? 0) - 
            ($this->paid_amount ?? 0)
        );

        // Calculate status automatically
        if ($this->due_amount <= 0.01) { // Use small threshold for floating point
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partially_paid'; // Correct enum value
        } else {
            $this->status = 'unpaid';
        }
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    
    /**
 * Get payment reminders for this invoice
 */
public function paymentReminders()
{
    return $this->hasMany(PaymentReminder::class);
}

/**
 * Get the latest payment reminder
 */
public function latestReminder()
{
    return $this->hasOne(PaymentReminder::class)->latest();
}

/**
 * Check if invoice is overdue
 */
public function isOverdue(): bool
{
    return $this->due_date < now() && $this->status !== 'paid';
}

/**
 * Get days overdue (returns 0 if not overdue)
 */
public function getDaysOverdue(): int
{
    if (!$this->isOverdue()) {
        return 0;
    }
    
    return \Carbon\Carbon::parse($this->due_date)->diffInDays(now());
}

/**
 * Get remaining amount to be paid
 */
public function getRemainingAmount(): float
{
    return $this->total_amount - $this->payments->sum('amount');
}

/**
 * Check if reminder was sent recently (within last 24 hours)
 */
public function wasReminderSentRecently(): bool
{
    return $this->paymentReminders()
        ->where('sent_at', '>', now()->subHours(24))
        ->exists();
}

/**
 * Get reminder count for this invoice
 */
public function getReminderCount(): int
{
    return $this->paymentReminders()->count();
}

/**
 * Scope for overdue invoices
 */
public function scopeOverdue($query)
{
    return $query->where('due_date', '<', now())
                 ->where('status', '!=', 'paid');
}

/**
 * Scope for invoices due soon (within specified days)
 */
public function scopeDueSoon($query, int $days = 7)
{
    return $query->whereBetween('due_date', [
        now(),
        now()->addDays($days)
    ])->where('status', '!=', 'paid');
}

/**
 * Update reminder tracking fields
 */
public function updateReminderTracking()
{
    $this->update([
        'reminder_sent_count' => $this->paymentReminders()->count(),
        'last_reminder_sent_at' => $this->paymentReminders()
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->value('sent_at')
    ]);
}

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}