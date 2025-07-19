<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class Timetable extends Model
{
    use WebhookEnabled;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * THE FIX IS HERE: We have added 'batch_id' to this array.
     */
    protected $fillable = [
        'schedule_date',
        'batch_id',
        'subject_id',
        'user_id',
        'classroom_id',
        'time_slot_id',
    ];

    /**
     * Get the batch for this timetable slot.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the subject for this timetable slot.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the user (faculty) for this timetable slot.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the classroom for this timetable slot.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the time slot for this timetable entry.
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }
}
