<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Traits\WebhookEnabled;
use App\Traits\HasAcademicYear;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Admission extends Model
{
    use WebhookEnabled;
    use HasFactory, LogsActivity;
    use HasAcademicYear;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'course_id', 'full_name'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Admission for '{$this->full_name}' was {$eventName}");
    }

    protected $fillable = [
        'course_id',
        'full_name',
        'email',
        'phone_number',
        'date_of_birth',
        'address',
        'education_qualification',
        'source',
        'referral_name',
        'status',
        'gender',
        'enquiry_id'
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function followUps(): MorphMany
    {
        return $this->morphMany(\App\Models\FollowUp::class, 'followable');
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class);
    }
}