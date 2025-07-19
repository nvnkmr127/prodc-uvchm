<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\WebhookEnabled;

class FeeCategory extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = [
        'name',
        'category_code', // Add this
        'category_type',
        'is_mandatory',
        'is_recurring',
        'recurrence_type',
        'late_fee_percentage',
        'reminder_days_before',
        'escalation_days_after'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_recurring' => 'boolean',
        'late_fee_percentage' => 'decimal:2',
    ];

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    // Auto-generate category_code if not provided
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($feeCategory) {
            if (empty($feeCategory->category_code)) {
                $feeCategory->category_code = strtoupper(substr($feeCategory->name, 0, 3)) . '-' . time();
            }
        });
    }
}