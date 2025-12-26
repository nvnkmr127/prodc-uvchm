<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\WebhookEnabled;
use App\Traits\HasAcademicYear;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory, WebhookEnabled, HasAcademicYear;

    // Define the custom column name for academic year
    public $academic_year_column = 'academic_year';

    /**
     * The attributes that are mass assignable.
     * Based on your database structure from DESCRIBE payments
     */
    protected $fillable = [
        'student_id',           // REQUIRED - Foreign key to students
        'invoice_id',           // NULLABLE - For backward compatibility
        'amount',              // REQUIRED - Payment amount
        'payment_date',        // REQUIRED - Date of payment
        'payment_method',      // REQUIRED - Method (cash, card, etc.)
        'payment_type',        // REQUIRED - Type (component, bulk, etc.)
        'component_details',   // NULLABLE - JSON details
        'transaction_id',      // NULLABLE - External transaction ID
        'receipt_number',      // NULLABLE - Auto-generated receipt number
        'academic_year',       // NULLABLE - Academic year
        'notes',              // NULLABLE - Additional notes
        'status'
    ];

    /**
     * Attributes that are not mass assignable (for security)
     */
    protected $guarded = ['id', 'created_by', 'updated_by'];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'component_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate receipt number and set defaults when creating
        static::creating(function ($payment) {
            // Generate receipt number if not provided
            if (!$payment->receipt_number) {
                $payment->receipt_number = static::generateReceiptNumber();
            }

            // Set payment type if not provided
            if (!$payment->payment_type) {
                $payment->payment_type = 'component';
            }

            // Set status if not provided
            if (!$payment->status) {
                $payment->status = 'completed';
            }

            // Set payment date if not provided
            if (!$payment->payment_date) {
                $payment->payment_date = now();
            }

            // Set created_by if not already set and user is authenticated
            if (!$payment->created_by && auth()->check()) {
                $payment->created_by = auth()->id();
            }
        });

        // Handle updating
        static::updating(function ($payment) {
            // Set updated_by when updating
            if (auth()->check()) {
                $payment->updated_by = auth()->id();
            }
        });
    }

    /**
     * RELATIONSHIPS
     */

    /**
     * Get the student that owns the payment
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the invoice associated with the payment (backward compatibility)
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the component payment items for this payment
     */
    public function componentItems(): HasMany
    {
        return $this->hasMany(ComponentPaymentItem::class);
    }

    /**
     * Get the student fees associated with this payment through component items
     */
    public function studentFees(): BelongsToMany
    {
        return $this->belongsToMany(StudentFee::class, 'component_payment_items')
            ->withPivot('amount_paid', 'notes', 'created_at')
            ->withTimestamps();
    }

    /**
     * Get edit logs for this payment
     */
    public function editLogs()
    {
        return $this->hasMany(PaymentEditLog::class, 'payment_id');
    }


    /**
     * Get the user who created this payment
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    /**
     * Get the user who last updated this payment
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Alternative method names for backward compatibility
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }



    /**
     * SCOPES
     */

    /**
     * Scope to get only component payments
     */
    public function scopeComponentPayments($query)
    {
        return $query->where('payment_type', 'component');
    }

    /**
     * Scope to get payments for a specific student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to get payments for a specific academic year
     */
    public function scopeForAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Get edit history
     */
    public function getEditHistory()
    {
        return PaymentEditLog::where('payment_id', $this->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Accessor for creator name
     */
    public function getCreatorNameAttribute(): string
    {
        return $this->creator ? $this->creator->name : 'System';
    }

    /**
     * Accessor for updater name
     */
    public function getUpdaterNameAttribute(): string
    {
        return $this->updater ? $this->updater->name : 'N/A';
    }

    /**
     * Scope to get payments by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }



    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    /**
     * Scope to get payments by payment method
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope to get payments by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get recent payments
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('payment_date', '>=', now()->subDays($days));
    }

    /**
     * METHODS
     */

    /**
     * Generate a unique receipt number
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = 'RCP-' . date('Y') . '-';

        // Get the latest receipt number for this year
        $latest = static::where('receipt_number', 'LIKE', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(receipt_number, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        if ($latest) {
            // Extract the number part and increment
            $lastNumber = intval(substr($latest->receipt_number, strlen($prefix)));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if this is a component-based payment
     */
    public function isComponentPayment(): bool
    {
        return $this->payment_type === 'component';
    }

    /**
     * Force load component items with all necessary relationships
     */
    public function loadComponentsForWebhook()
    {
        if ($this->isComponentPayment()) {
            $this->load([
                'componentItems.studentFee.feeCategory',
                'student'
            ]);
        }

        return $this;
    }

    /**
     * Get components data formatted for webhooks
     */
    public function getComponentsForWebhook(): array
    {
        if (!$this->isComponentPayment()) {
            return [];
        }

        // Ensure relationships are loaded
        if (!$this->relationLoaded('componentItems')) {
            $this->load('componentItems.studentFee.feeCategory');
        }

        return $this->componentItems->map(function ($item) {
            return [
                'student_fee_id' => $item->student_fee_id,
                'category_name' => $item->studentFee?->feeCategory?->name ?? 'Unknown Category',
                'amount_paid' => (float) $item->amount_paid,
                'fee_status_after_payment' => $item->studentFee?->status ?? null,
                'notes' => $item->notes,
            ];
        })->toArray();
    }



    /**
     * Get the total amount paid through component items
     */
    public function getComponentTotal(): float
    {
        return $this->componentItems()->sum('amount_paid');
    }

    /**
     * Get formatted amount with currency symbol
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₹' . number_format($this->amount, 2);
    }

    /**
     * Get payment status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status ?? 'completed') {
            'completed' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'refunded' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Create a component payment with items
     */
    public static function createComponentPayment($studentId, array $components, array $paymentData): self
    {
        $totalAmount = collect($components)->sum('amount');

        return static::create([
            'student_id' => $studentId,
            'amount' => $totalAmount,
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'payment_method' => $paymentData['payment_method'],
            'payment_type' => 'component',
            'component_details' => $components,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
            'academic_year' => $paymentData['academic_year'] ?? null,
            'status' => $paymentData['status'] ?? 'completed',
            'created_by' => $paymentData['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Get payment summary for dashboard
     */
    public static function getPaymentSummary(array $filters = []): array
    {
        $query = static::query();

        // Apply filters
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        return [
            'total_payments' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'by_method' => $query->groupBy('payment_method')
                ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->get()
                ->keyBy('payment_method'),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
                ->get()
                ->keyBy('status'),
            'recent_payments' => $query->with('student')
                ->orderBy('payment_date', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->status === 'completed' &&
            $this->payment_date >= now()->subDays(30);
    }

    /**
     * Check if payment can be edited
     */
    public function canBeEdited(): bool
    {
        // Don't allow editing if payment is cancelled or refunded
        if (in_array($this->status, ['cancelled', 'refunded'])) {
            return false;
        }

        // Don't allow editing very old payments (older than 90 days)
        $daysSinceCreation = $this->created_at->diffInDays(now());
        if ($daysSinceCreation > 90) {
            return false;
        }

        // Check if user has permission
        if (!auth()->check() || !auth()->user()->can('edit payments')) {
            return false;
        }

        // Don't allow editing if payment has been reconciled (if you have this field)
        if (isset($this->attributes['is_reconciled']) && $this->is_reconciled) {
            return false;
        }

        return true;
    }


    /**
     * Check if payment can be reverted
     * 
     * @return bool
     */
    public function canBeReverted(): bool
    {
        // Only allow reverting if there are edit logs
        $hasEditHistory = \App\Models\PaymentEditLog::where('payment_id', $this->id)->exists();

        return $hasEditHistory &&
            $this->canBeEdited() &&
            auth()->user() &&
            auth()->user()->can('revert payments');
    }
    /**
     * Check if this payment can have a receipt generated
     */
    public function canGenerateReceipt(): bool
    {
        return $this->payment_type === 'component' &&
            in_array($this->status ?? 'completed', ['completed', 'paid']);
    }

    /**
     * Check if this payment can be deleted
     */
    public function canBeDeleted(): bool
    {
        // Only component payments can be deleted
        if ($this->payment_type !== 'component') {
            return false;
        }

        // Check if payment is recent (e.g., within 7 days)
        $deletableWindow = config('payments.deletable_window_days', 7);
        if ($this->created_at->addDays($deletableWindow)->isPast()) {
            return false;
        }

        // Check if payment status allows deletion
        if (in_array($this->status ?? 'completed', ['refunded', 'cancelled'])) {
            return false;
        }

        // Check user permissions
        if (!auth()->user()->can('delete payments')) {
            return false;
        }

        return true;
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'card' => 'Card Payment',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'online' => 'Online Payment',
            'upi' => 'UPI',
            default => ucfirst(str_replace('_', ' ', $this->payment_method))
        };
    }


    /**
     * Get related fee categories through component items
     */
    public function getFeeCategories()
    {
        return $this->componentItems()
            ->with('studentFee.feeCategory')
            ->get()
            ->pluck('studentFee.feeCategory')
            ->filter()
            ->unique('id');
    }

    /**
     * Convert to webhook payload format
     */
    public function toWebhookPayload(): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date->format('Y-m-d'),
            'receipt_number' => $this->receipt_number,
            'status' => $this->status,
            'payment_type' => $this->payment_type,
            'student' => $this->student ? [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'enrollment_number' => $this->student->enrollment_number,
            ] : null,
            'components_paid' => $this->componentItems->map(function ($item) {
                return [
                    'student_fee_id' => $item->student_fee_id,
                    'category_name' => $item->studentFee->feeCategory->name ?? 'Unknown',
                    'amount_paid' => (float) $item->amount_paid,
                ];
            }),
        ];
    }
}