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

    /** Single source of truth for all enquiry source options */
    const SOURCES = [
        'Website'       => 'Website / Google',
        'Social Media'  => 'Social Media',
        'Agent'         => 'Agent',
        'Referrals'     => 'Referrals',
        'Student Refer' => 'Student Referral',
        'Walk-in'       => 'Walk-in',
        'Other'         => 'Other',
    ];

    protected $fillable = [
        'student_name',
        'phone_number',
        'gender',
        'date_of_birth',
        'address',
        'education_qualification',
        'course_id',
        'source',
        'referral_name',
        'notes',
        'next_follow_up_date',
        'status',
        'assigned_to_user_id',
        'email',
        'test_attended',
        'test_marks',
        'discount_offered',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'next_follow_up_date' => 'date', // <--- Added this line to fix the error
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

    public function followUps(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'followable');
    }
}