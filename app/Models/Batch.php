<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne; // <-- FIX: Added the missing import
use App\Traits\WebhookEnabled;
use App\Traits\HasAcademicYear;

class Batch extends Model
{
    use WebhookEnabled;
    use HasFactory;
    use HasAcademicYear;

    protected $fillable = [
        'academic_year_id',
        'course_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_on_internship'
    ];
protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_on_internship' => 'boolean', // [ADDED]
    ];
    /**
     * A Batch belongs to one Course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * A Batch can have many Students.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * A Batch has many timetable entries.
     */
    public function timetableEntries(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    /**
     * A Batch has many practical groups.
     */
    public function practicalGroups(): HasMany
    {
        return $this->hasMany(PracticalGroup::class);
    }
    
    public function feeStructure(): HasOne
    {
        return $this->hasOne(FeeStructure::class);
    }

    /**
     * A Batch has many Subjects THROUGH its Course.
     */
    public function subjects(): HasManyThrough
    {
        // This relationship seems complex and might not be correct. 
        // A direct relationship might be better if subjects can be batch-specific.
        // For now, leaving as is.
        return $this->hasManyThrough(Subject::class, Course::class, 'id', 'id', 'course_id', 'id');
    }
}
