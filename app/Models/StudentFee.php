<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\WebhookEnabled;
use App\Traits\HasAcademicYear;

class StudentFee extends Model
{
    use WebhookEnabled, HasFactory;

    public $academic_year_column = 'academic_year'; // Keep for reference, though used manually now

    protected $fillable = [
        'student_id',
        'fee_structure_id',
        'fee_category_id',
        'academic_year',
        'installment_number',
        'total_installments',
        'amount',
        'original_amount',
        'paid_amount',
        'concession_amount',
        'concession_notes',
        'due_date',
        'paid_date',
        'status',
        'payment_method',
        'transaction_id',
        'remarks',
        'invoice_id' // Keep for backward compatibility during migration
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'concession_amount' => 'decimal:2',
        'installment_number' => 'integer',
        'total_installments' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();

        // [FIX] Robust Academic Year Global Scope (Handles Strict Name & Legacy Relations)
        if (config('app.enable_academic_year_global_scope', true) && !app()->runningInConsole() && !request()->is('api/*')) {
            static::addGlobalScope('academic_year', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $yearId = session('selected_academic_year_id');

                // Default to current year
                if (!$yearId) {
                    $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
                    $yearId = $currentYear?->id;
                }

                if ($yearId) {
                    // Get Year Name for string column
                    $yearName = \App\Models\AcademicYear::find($yearId)?->name;

                    $builder->where(function ($q) use ($yearId, $yearName) {
                        // 1. Primary: Match by explicit academic_year column string
                        if ($yearName) {
                            $q->where('academic_year', $yearName);
                        }

                        // 2. Fallback: Check Student's Batch Year
                        // [FIX] Removed whereNull check to allow fallback even if column has mismatched string data
                        $q->orWhereHas('student.batch', function ($bq) use ($yearId) {
                            $bq->where('academic_year_id', $yearId);
                        });
                    });
                }
            });
        }

        static::creating(function ($studentFee) {
            // Set original amount if not provided
            if (!$studentFee->original_amount) {
                $studentFee->original_amount = $studentFee->amount;
            }

            // Set academic year if not provided
            if (!$studentFee->academic_year) {
                // Try to get from student's batch first
                $student = \App\Models\Student::find($studentFee->student_id);
                if ($student && $student->batch && $student->batch->academicYear) {
                    $studentFee->academic_year = $student->batch->academicYear->name;
                } else {
                    // Fallback to current date logic
                    $studentFee->academic_year = date('Y') . '-' . (date('Y') + 1);
                }
            }

            // Update status based on amounts
            $studentFee->updateStatus();
        });

        static::updating(function ($studentFee) {
            // Auto-update status when amounts change
            if ($studentFee->isDirty(['amount', 'paid_amount', 'concession_amount'])) {
                $studentFee->updateStatus();
            }
        });
    }

    /**
     * Update fee status based on payment amounts
     */
    public function updateStatus()
    {
        $netAmount = $this->amount - $this->concession_amount;

        if ($this->paid_amount >= $netAmount) {
            $this->status = 'paid';
            if (!$this->paid_date) {
                $this->paid_date = now();
            }
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            // 🔧 CRITICAL FIX: Always use 'unpaid' instead of 'overdue'
            // Your database schema only supports: ['unpaid', 'paid', 'partial', 'waived']
            $this->status = 'unpaid';
        }
    }

    /**
     * Relationships
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function componentPaymentItems()
    {
        return $this->hasMany(ComponentPaymentItem::class);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'component_payment_items')
            ->withPivot('amount_paid', 'notes', 'created_at');
    }

    public function concessions()
    {
        return $this->hasMany(StudentConcession::class, 'fee_category_id', 'fee_category_id')
            ->where('student_id', $this->student_id);
    }

    /**
     * Scopes
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'unpaid')
                    ->where('due_date', '<', now());
            });
    }

    public function scopeForAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('fee_category_id', $categoryId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Helper methods
     */
    public function isOverdue()
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }

    public function getRemainingAmount()
    {
        return max(0, $this->amount - $this->concession_amount - $this->paid_amount);
    }

    public function getNetAmount()
    {
        return $this->amount - $this->concession_amount;
    }

    public function canMakePayment()
    {
        return $this->getRemainingAmount() > 0;
    }

    public function getPaymentPercentage()
    {
        $netAmount = $this->getNetAmount();
        return $netAmount > 0 ? round(($this->paid_amount / $netAmount) * 100, 2) : 100;
    }

    public function getOverdueDays()
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    public function getLatestPaymentDate()
    {
        return $this->componentPaymentItems()
            ->with('payment')
            ->latest('created_at')
            ->first()
            ?->payment
                ?->payment_date;
    }

    /**
     * Apply payment to this fee
     */
    public function applyPayment($amount, Payment $payment)
    {
        $remainingAmount = $this->getRemainingAmount();
        $paymentAmount = min($amount, $remainingAmount);

        if ($paymentAmount <= 0) {
            return 0;
        }

        $this->increment('paid_amount', $paymentAmount);

        // Create payment item record
        $payment->componentItems()->create([
            'student_fee_id' => $this->id,
            'amount_paid' => $paymentAmount,
        ]);

        $this->updateStatus();
        $this->save();

        return $paymentAmount;
    }

    /**
     * Apply concession to this fee
     */
    public function applyConcession($amount, $notes = null)
    {
        $maxConcession = $this->amount - $this->paid_amount;
        $concessionAmount = min($amount, $maxConcession);

        if ($concessionAmount <= 0) {
            return 0;
        }

        $this->increment('concession_amount', $concessionAmount);
        $this->concession_notes = $notes;

        $this->updateStatus();
        $this->save();

        return $concessionAmount;
    }

    /**
     * Reverse payment for this fee
     */
    public function reversePayment($paymentAmount)
    {
        if ($paymentAmount > $this->paid_amount) {
            throw new \Exception('Cannot reverse more than paid amount');
        }

        $this->decrement('paid_amount', $paymentAmount);
        $this->updateStatus();
        $this->save();

        return $paymentAmount;
    }

    /**
     * Format fee for display
     */

    public function toDisplayArray()
    {
        return [
            'id' => $this->id,
            'category' => $this->feeCategory->name,
            'category_type' => $this->feeCategory->category_type ?? 'other',
            'amount' => $this->amount,
            'concession_amount' => $this->concession_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->getRemainingAmount(),
            'net_amount' => $this->getNetAmount(),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'status' => $this->status, // Database status
            'display_status' => $this->getDisplayStatus(), // 🆕 For UI (includes overdue)
            'is_overdue' => $this->isOverdue(),
            'overdue_days' => $this->getOverdueDays(),
            'payment_percentage' => $this->getPaymentPercentage(),
            'can_pay' => $this->canMakePayment(),
            'installment' => $this->installment_number . '/' . $this->total_installments,
            'latest_payment_date' => $this->getLatestPaymentDate()?->format('Y-m-d'),
        ];
    }

    /**
     * Get display status (includes overdue logic for UI without affecting database)
     */
    public function getDisplayStatus()
    {
        if ($this->status === 'unpaid' && $this->isOverdue()) {
            return 'overdue';
        }
        return $this->status;
    }

    /**
     * Static methods
     */
    public static function getTotalOutstanding($filters = [])
    {
        $query = static::query();

        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['fee_category_id'])) {
            $query->where('fee_category_id', $filters['fee_category_id']);
        }

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        return $query->get()->sum(function ($fee) {
            return $fee->getRemainingAmount();
        });
    }

    public static function getCollectionSummary($filters = [])
    {
        $query = static::query();

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (isset($filters['fee_category_id'])) {
            $query->where('fee_category_id', $filters['fee_category_id']);
        }

        $fees = $query->get();

        $totalAmount = $fees->sum('amount');
        $totalConcession = $fees->sum('concession_amount');
        $totalPaid = $fees->sum('paid_amount');
        $netAmount = $totalAmount - $totalConcession;
        $outstanding = $netAmount - $totalPaid;

        return [
            'total_amount' => $totalAmount,
            'total_concession' => $totalConcession,
            'net_amount' => $netAmount,
            'total_paid' => $totalPaid,
            'outstanding' => $outstanding,
            'collection_percentage' => $netAmount > 0 ? round(($totalPaid / $netAmount) * 100, 2) : 100,
            'fees_count' => $fees->count(),
            'paid_count' => $fees->where('status', 'paid')->count(),
            'unpaid_count' => $fees->whereIn('status', ['unpaid', 'partial', 'overdue'])->count()
        ];
    }
}