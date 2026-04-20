<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'changes_summary',
        'changes',
        'edit_reason',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who made this edit
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Static method to log payment changes
     */
    public static function logPaymentChange(
        Payment $payment,
        string $action,
        array $oldValues,
        array $newValues,
        ?string $editReason = null
    ): self {
        $changesSummary = self::generateChangesSummary($oldValues, $newValues);

        return self::create([
            'payment_id' => $payment->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changes_summary' => $changesSummary,
            'changes' => $changesSummary,
            'edit_reason' => $editReason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'payment_type' => $payment->payment_type,
                'student_id' => $payment->student_id,
                'original_amount' => $payment->getOriginal('amount'),
                'new_amount' => $payment->amount,
            ],
        ]);
    }

    /**
     * Generate a human-readable summary of changes
     */
    private static function generateChangesSummary(array $oldValues, array $newValues): string
    {
        $changes = [];

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;

            if ($oldValue != $newValue) {
                switch ($key) {
                    case 'amount':
                        $changes[] = 'Amount: ₹'.number_format($oldValue, 2).' → ₹'.number_format($newValue, 2);
                        break;
                    case 'payment_method':
                        $changes[] = 'Payment Method: '.ucfirst($oldValue).' → '.ucfirst($newValue);
                        break;
                    case 'payment_date':
                        $changes[] = 'Payment Date: '.$oldValue.' → '.$newValue;
                        break;
                    case 'components':
                        $changes[] = 'Payment Components Modified';
                        break;
                    default:
                        $changes[] = ucfirst(str_replace('_', ' ', $key)).' changed';
                }
            }
        }

        return implode(', ', $changes);
    }

    /**
     * Get formatted change description
     */
    public function getFormattedChangesAttribute(): string
    {
        if ($this->changes_summary) {
            return $this->changes_summary;
        }

        return "Payment {$this->action}";
    }

    /**
     * Get amount change description
     */
    public function getAmountChangeAttribute(): ?string
    {
        $oldAmount = $this->old_values['amount'] ?? null;
        $newAmount = $this->new_values['amount'] ?? null;

        if ($oldAmount && $newAmount && $oldAmount != $newAmount) {
            $difference = $newAmount - $oldAmount;
            $symbol = $difference > 0 ? '+' : '';

            return '₹'.number_format($oldAmount, 2).' → ₹'.number_format($newAmount, 2)." ({$symbol}₹".number_format($difference, 2).')';
        }

        return null;
    }

    /**
     * Check if this is a significant change
     */
    public function isSignificantChange(): bool
    {
        // Consider a change significant if:
        // 1. Amount changed by more than ₹10
        // 2. Payment method changed
        // 3. Components were modified

        $oldAmount = $this->old_values['amount'] ?? 0;
        $newAmount = $this->new_values['amount'] ?? 0;
        $amountDifference = abs($newAmount - $oldAmount);

        if ($amountDifference > 10) {
            return true;
        }

        $significantFields = ['payment_method', 'components'];
        foreach ($significantFields as $field) {
            if (isset($this->old_values[$field]) && isset($this->new_values[$field])) {
                if ($this->old_values[$field] != $this->new_values[$field]) {
                    return true;
                }
            }
        }

        return false;
    }
}
