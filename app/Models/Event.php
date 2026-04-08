<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = [
        'name',
        'course_id',
        'subject_id',
        'user_id',
        'classroom_id',
        'event_date',
        'start_time',
        'end_time',
    ];

    // Define relationships to get details easily
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    } // This is the faculty

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
