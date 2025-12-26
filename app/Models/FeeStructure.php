<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAcademicYear;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\WebhookEnabled;

class FeeStructure extends Model
{
    use WebhookEnabled;
    use HasFactory, HasAcademicYear;

    /**
     * The attributes that are mass assignable.
     *
     * We've added 'amount' here as a workaround for an extra column
     * in your database schema. It will hold the same value as 'total_amount'.
     *
     * @var array
     */
    protected $fillable = [
        'batch_id',
        'total_amount',
        'amount', // Your existing workaround field
        'payment_terms' // <-- Add this
    ];

    /**
     * Get the batch that owns the fee structure.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * The fee categories (components) that belong to the fee structure.
     */
    public function feeCategories(): BelongsToMany
    {
        return $this->belongsToMany(FeeCategory::class, 'fee_structure_fee_category')
            ->withPivot('amount')
            ->withTimestamps();
    }
}
