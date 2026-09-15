<?php

namespace App\Models;

use App\Traits\HasAcademicYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorAllocation extends Model
{
    use HasAcademicYear, HasFactory;

    protected $fillable = [
        'academic_year_id',
        'student_id',
        'mentor_id',
        'assigned_by',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the student in this allocation.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the mentor (user) in this allocation.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Get the user who made the assignment.
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the academic year for this allocation.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Scope for active allocations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
