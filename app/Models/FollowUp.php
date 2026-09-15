<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FollowUp extends Model
{
    use HasFactory, LogsActivity;
    use WebhookEnabled;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['notes'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Follow-up note was {$eventName}");
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'notes',
        'outcome',
        'interaction_type',
        'follow_up_date',
        'followable_id',
        'followable_type',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    /**
     * Get the parent followable model (Enquiry or Admission).
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created the follow-up note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
