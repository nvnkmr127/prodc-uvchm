<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeeCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'is_required',
        'is_installment_allowed',
        'display_order',
        'status',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_installment_allowed' => 'boolean',
        'display_order' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_required', 'is_installment_allowed', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Fee category '{$this->name}' has been {$eventName}");
    }

    /**
     * Scope to get active fee categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get required fee categories
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Relationship with student fees
     */
    public function studentFees()
    {
        return $this->hasMany(\App\Models\StudentFee::class);
    }

    /**
     * Relationship with fee structures
     */
    public function feeStructures()
    {
        return $this->hasMany(\App\Models\FeeStructure::class, 'fee_category_id');
    }

    /**
     * Get total amount collected for this category
     */
    public function getTotalCollectedAttribute()
    {
        return $this->studentFees()->sum('paid_amount');
    }

    /**
     * Get total outstanding amount for this category
     */
    public function getTotalOutstandingAttribute()
    {
        return $this->studentFees()
            ->selectRaw('SUM(amount - concession_amount - paid_amount) as outstanding')
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->value('outstanding') ?? 0;
    }

    /**
     * Get number of students with this fee category
     */
    public function getStudentCountAttribute()
    {
        return $this->studentFees()->distinct('student_id')->count('student_id');
    }
}
