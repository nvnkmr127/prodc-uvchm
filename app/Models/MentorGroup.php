<?php

namespace App\Models;

use App\Traits\HasAcademicYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MentorGroup extends Model
{
    use HasAcademicYear, HasFactory;

    protected $fillable = [
        'name',
        'academic_year_id',
        'faculty_id',
        'counselor_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Faculty mentor assigned to this group.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    /**
     * Counselor assigned to this group.
     */
    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    /**
     * Academic year for this group.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Students belonging to this group (across all courses/departments).
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'mentor_group_id');
    }

    /**
     * Scope for active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
