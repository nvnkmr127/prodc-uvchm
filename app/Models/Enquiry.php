<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\WebhookEnabled;

class Enquiry extends Model
{
    use WebhookEnabled;
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     * These now include fields that were previously on the Admission model.
     */
    protected $fillable = [
        'student_name',
        'phone_number',
        'email', // <-- Added from Admission
        'gender', // <-- Added from Admission
        'date_of_birth', // <-- Added from Admission
        'address',
        'education_qualification', // <-- Added from Admission
        'course_id',
        'source',
        'referral_name',
        'notes',
        'next_follow_up_date',
        'status',
        'assigned_to_user_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'next_follow_up_date', 'assigned_to_user_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "The enquiry for '{$this->student_name}' has been {$eventName}");
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get all of the follow-up notes for the enquiry.
     * This uses the unified polymorphic relationship we built.
     */
    public function followUps(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'followable');
    }
}
