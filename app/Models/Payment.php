<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\WebhookEnabled;
use App\Models\PaymentEditLog; // Ensure you have this model created
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    use HasFactory, LogsActivity, WebhookEnabled;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'student_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_id',
        'notes',
        'receipt_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'payment_date',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * Auto-generate a unique receipt number before creating a new payment if one isn't provided.
         */
        static::creating(function ($payment) {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = static::generateUniqueReceiptNumber();
                Log::info('Auto-generated receipt number for payment.', [
                    'receipt_number' => $payment->receipt_number,
                    'invoice_id' => $payment->invoice_id,
                ]);
            }
        });

        /**
         * When a payment's amount is updated, log the change and recalculate the associated invoice's totals.
         */
        static::updated(function ($payment) {
            if ($payment->isDirty('amount')) {
                $payment->recalculateInvoiceTotals();
            }
            Log::info('Payment updated - webhook should fire.', ['payment_id' => $payment->id, 'changes' => $payment->getChanges()]);
        });

        /**
         * When a payment is deleted, recalculate the associated invoice's totals.
         */
        static::deleted(function ($payment) {
            $payment->recalculateInvoiceTotals();
        });

         /**
         * Log the creation of a new payment.
         */
        static::created(function ($payment) {
            Log::info('Payment created - webhook should fire.', ['payment_id' => $payment->id, 'receipt_number' => $payment->receipt_number]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the invoice that this payment belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the student that made the payment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the edit history logs for this payment.
     */
    public function editLogs(): HasMany
    {
        return $this->hasMany(PaymentEditLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * Configure the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'payment_date', 'payment_method', 'transaction_id', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "A payment of {$this->formatted_amount} for invoice '{$this->invoice->invoice_number}' was {$eventName}"
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * Define the model events that should trigger webhooks.
     */
    public function getWebhookEvents(): array
    {
        return ['created', 'updated'];
    }

    /**
     * Define the data structure for the webhook payload.
     */
    public function getWebhookData(): array
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $receiptNumber = $this->receipt_number;
        $receiptUrls = [];

        try {
            $receiptUrls = [
                'view' => route('admin.payments.receipt.show', $this->id),
                'download_pdf' => route('admin.payments.receipt.pdf', $this->id),
                'public' => $receiptNumber ? route('public.receipt.show', $receiptNumber) : null,
            ];
        } catch (\Exception $e) {
            Log::warning('Could not generate named receipt URLs for webhook.', [
                'payment_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            // Fallback to manually constructed URLs
            $receiptUrls['view'] = "{$baseUrl}/admin/payments/{$this->id}/receipt/show";
            $receiptUrls['download_pdf'] = "{$baseUrl}/admin/payments/{$this->id}/receipt/pdf";
            if ($receiptNumber) {
                $receiptUrls['public'] = "{$baseUrl}/receipts/{$receiptNumber}";
            }
        }

        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date->toIso8601String(),
            'receipt_number' => $receiptNumber,
            'status' => $this->status ?? 'completed',
            'invoice_number' => $this->invoice->invoice_number ?? null,
            'student_name' => $this->invoice->student->name ?? null,
            'student_id' => $this->invoice->student_id ?? null,
            'receipt_urls' => $receiptUrls,
            'webhook_triggered_at' => now()->toIso8601String(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the count of edits for the payment.
     */
    public function getEditCountAttribute(): int
    {
        return $this->editLogs()->count();
    }

    /**
     * Get the formatted amount attribute.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₹' . number_format($this->amount, 2);
    }

    /**
     * Get the formatted payment date attribute.
     */
    public function getFormattedPaymentDateAttribute(): string
    {
        return $this->payment_date->format('M d, Y');
    }

    /**
     * Get the CSS class for the payment method badge.
     */
    public function getPaymentMethodBadgeClassAttribute(): string
    {
        $classes = [
            'Cash' => 'success',
            'Card' => 'info',
            'Bank Transfer' => 'primary',
            'Cheque' => 'warning',
            'Online' => 'info',
            'UPI' => 'success',
        ];

        return $classes[$this->payment_method] ?? 'secondary';
    }


    /*
    |--------------------------------------------------------------------------
    | Business Logic & Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Generate a unique, sequential receipt number.
     */
    public static function generateUniqueReceiptNumber(): string
    {
        $year = now()->year;
        $prefix = 'RCPT-' . $year . '-';

        $lastPayment = static::where('receipt_number', 'like', $prefix . '%')
                             ->orderBy('id', 'desc')
                             ->first();

        $nextNumber = 1;
        if ($lastPayment && $lastPayment->receipt_number) {
            $lastNumber = (int) str_replace($prefix, '', $lastPayment->receipt_number);
            $nextNumber = $lastNumber + 1;
        }

        // Loop to ensure uniqueness in case of race conditions
        $maxAttempts = 10;
        for ($attempts = 0; $attempts < $maxAttempts; $attempts++) {
            $receiptNumber = $prefix . str_pad($nextNumber + $attempts, 6, '0', STR_PAD_LEFT);
            if (!static::where('receipt_number', $receiptNumber)->exists()) {
                return $receiptNumber;
            }
        }

        // Fallback to a timestamp-based unique ID if sequential generation fails
        return $prefix . time() . '-' . rand(100, 999);
    }

    /**
     * Check if the payment has been edited.
     */
    public function hasBeenEdited(): bool
    {
        return $this->editLogs()->exists();
    }

    /**
     * Check if the payment can be edited based on business rules.
     */
    public function canBeEdited(): bool
    {
        // Rule 1: User must have permission
        if (!auth()->check() || !auth()->user()->can('edit payments')) {
            return false;
        }

        // Rule 2: Payment must be recent (e.g., within 30 days)
        if ($this->created_at < now()->subDays(30)) {
            return false;
        }

        // Rule 3: Related invoice must not be finalized/locked
        if ($this->invoice && method_exists($this->invoice, 'isFinalized') && $this->invoice->isFinalized()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the payment can be reversed based on business rules.
     */
    public function canBeReversed(): bool
    {
        // Rule 1: It must be the most recent payment for the invoice
        $latestPayment = $this->invoice->payments()->latest('id')->first();
        if (!$latestPayment || $latestPayment->id !== $this->id) {
            return false;
        }

        // Rule 2: Payment must be recent (e.g., within 7 days)
        if ($this->created_at < now()->subDays(7)) {
            return false;
        }

        return true;
    }

    /**
     * Recalculate the paid and due amounts for the associated invoice.
     */
    public function recalculateInvoiceTotals()
    {
        if ($this->invoice) {
            $totalPaid = $this->invoice->payments()->sum('amount');
            $dueAmount = max(0, $this->invoice->total_amount - $totalPaid - ($this->invoice->concession_amount ?? 0));

            $status = 'unpaid';
            if ($dueAmount <= 0 && $totalPaid > 0) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partially_paid';
            }

            $this->invoice->update([
                'paid_amount' => $totalPaid,
                'due_amount' => $dueAmount,
                'status' => $status,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include payments within a given date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include payments of a specific method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope a query to only include payments that have been edited.
     */
    public function scopeWithEdits($query)
    {
        return $query->whereHas('editLogs');
    }

    /**
     * Scope a query to only include recent payments.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}