<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'user_id',
        'action',
        'previous_state',
        'new_state',
        'changes',
        'edit_reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'previous_state' => 'array',
        'new_state' => 'array',
        'changes' => 'array',
    ];

    /**
     * Get the payment that was edited
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who made the edit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted change summary
     */
    public function getChangesSummaryAttribute(): string
    {
        if (empty($this->changes)) {
            return 'No changes recorded';
        }

        $summary = [];
        foreach ($this->changes as $field => $change) {
            $fieldName = ucfirst(str_replace('_', ' ', $field));
            $summary[] = "{$fieldName}: {$change['old']} → {$change['new']}";
        }

        return implode(', ', $summary);
    }

    /**
     * Get formatted amount change if applicable
     */
    public function getAmountChangeAttribute(): ?string
    {
        if (!isset($this->changes['amount'])) {
            return null;
        }

        $old = $this->changes['amount']['old'];
        $new = $this->changes['amount']['new'];
        $difference = $new - $old;
        
        $symbol = $difference > 0 ? '+' : '';
        
        return "₹" . number_format($old, 2) . " → ₹" . number_format($new, 2) . " ({$symbol}₹" . number_format($difference, 2) . ")";
    }

    /**
     * Scope to get logs for a specific payment
     */
    public function scopeForPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    /**
     * Scope to get logs by action type
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get recent logs
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}