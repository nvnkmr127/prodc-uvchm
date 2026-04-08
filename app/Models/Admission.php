<?php

namespace App\Models;

use App\Traits\HasAcademicYear;
use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Admission extends Model
{
    use HasAcademicYear;
    use HasFactory, LogsActivity;
    use WebhookEnabled;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'course_id', 'full_name'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Admission for '{$this->full_name}' was {$eventName}");
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
        'enquiry_id',
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
