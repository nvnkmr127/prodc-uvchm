<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentPaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'student_fee_id',
        'amount_paid',  // ✅ CORRECT COLUMN NAME
        'notes',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the payment that owns this item
     */
    public function payment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the student fee that this payment item is for
     */
    public function studentFee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StudentFee::class);
    }

    /**
     * Accessor for backwards compatibility (if needed)
     */
    public function getAmountAttribute()
    {
        return $this->amount_paid;
    }

    /**
     * Mutator for backwards compatibility (if needed)
     */
    public function setAmountAttribute($value)
    {
        $this->amount_paid = $value;
    }
}
