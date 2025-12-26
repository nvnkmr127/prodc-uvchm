<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class StudentConcession extends Model
{
    use HasFactory, WebhookEnabled;

    protected $fillable = [
        'student_id',
        'student_fee_id',
        'fee_category_id',
        'concession_type',
        'concession_value', // For backward compatibility
        'concession_amount',
        'percentage',
        'status',
        'notes',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'applied_by',
        'applied_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason'
    ];

    protected $casts = [
        'concession_value' => 'decimal:2',
        'concession_amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
        'reversed_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($concession) {
            // Set defaults
            if (!$concession->status) {
                $concession->status = 'pending';
            }
            
            if (!$concession->requested_by) {
                $concession->requested_by = auth()->id();
            }
        });
    }

    /**
     * Relationships
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studentFee()
    {
        return $this->belongsTo(StudentFee::class);
    }

    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'applied']);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('fee_category_id', $categoryId);
    }

    /**
     * Helper methods
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeApplied(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeReversed(): bool
    {
        return in_array($this->status, ['approved', 'applied']);
    }

    /**
     * Approve the concession
     */
    public function approve(string $approvalNotes = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $approvalNotes
        ]);

        return true;
    }

    /**
     * Reject the concession
     */
    public function reject(string $rejectionReason = null): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $rejectionReason
        ]);

        return true;
    }

    /**
     * Apply the concession to student fee
     */
    public function apply(): bool
    {
        if (!$this->canBeApplied()) {
            return false;
        }

        try {
            \DB::beginTransaction();

            // Update the student fee
            if ($this->student_fee_id) {
                $studentFee = $this->studentFee;
                $studentFee->update([
                    'concession_amount' => $studentFee->concession_amount + $this->concession_amount,
                    'concession_reason' => $this->reason,
                    'concession_approved_by' => $this->approved_by,
                    'concession_approved_at' => $this->approved_at
                ]);

                // Update fee status
                $studentFee->updateStatus();
            }

            // Update concession status
            $this->update([
                'status' => 'applied',
                'applied_by' => auth()->id(),
                'applied_at' => now()
            ]);

            \DB::commit();
            return true;

        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Failed to apply concession: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reverse the concession
     */
    public function reverse(string $reason = null): bool
    {
        if (!$this->canBeReversed()) {
            return false;
        }

        try {
            \DB::beginTransaction();

            // Reverse from student fee if applied
            if ($this->status === 'applied' && $this->student_fee_id) {
                $studentFee = $this->studentFee;
                $studentFee->update([
                    'concession_amount' => max(0, $studentFee->concession_amount - $this->concession_amount)
                ]);

                // Update fee status
                $studentFee->updateStatus();
            }

            // Update concession status
            $this->update([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'reversal_reason' => $reason
            ]);

            \DB::commit();
            return true;

        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Failed to reverse concession: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate concession amount from percentage
     */
    public function calculateAmountFromPercentage(float $baseAmount): float
    {
        if (!$this->percentage) {
            return 0;
        }

        return ($baseAmount * $this->percentage) / 100;
    }

    /**
     * Accessors
     */
    public function getFormattedConcessionAttribute(): string
    {
        if ($this->concession_type === 'percentage' && $this->percentage) {
            return $this->percentage . '%';
        }
        return '₹' . number_format($this->concession_amount, 2);
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'approved' => '<span class="badge badge-info">Approved</span>',
            'applied' => '<span class="badge badge-success">Applied</span>',
            'rejected' => '<span class="badge badge-danger">Rejected</span>',
            'reversed' => '<span class="badge badge-secondary">Reversed</span>'
        ];

        return $badges[$this->status] ?? '<span class="badge badge-light">Unknown</span>';
    }

    public function getConcessionTypeNameAttribute(): string
    {
        $types = [
            'scholarship' => 'Scholarship',
            'financial_aid' => 'Financial Aid',
            'discount' => 'Discount',
            'special_case' => 'Special Case',
            'percentage' => 'Percentage Discount',
            'fixed_amount' => 'Fixed Amount Discount'
        ];

        return $types[$this->concession_type] ?? ucfirst(str_replace('_', ' ', $this->concession_type));
    }

    /**
     * Static methods
     */
    public static function getTotalConcessionForStudent(int $studentId): float
    {
        return static::where('student_id', $studentId)
            ->whereIn('status', ['applied'])
            ->sum('concession_amount');
    }

    public static function getTotalConcessionForCategory(int $categoryId): float
    {
        return static::where('fee_category_id', $categoryId)
            ->whereIn('status', ['applied'])
            ->sum('concession_amount');
    }

    public static function getPendingConcessions(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['student', 'studentFee.feeCategory', 'requestedBy'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }
}