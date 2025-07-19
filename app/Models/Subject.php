<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // <-- This import is crucial
use Illuminate\Database\Eloquent\Relations\HasMany;      // <-- This is for the marks relationship
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\WebhookEnabled;

class Subject extends Model
{
    use WebhookEnabled;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'requires_lab',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'requires_lab'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "The subject '{$this->name}' has been {$eventName}");
    }

    // Correctly defines the many-to-many relationship with Course
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_subject');
    }

    // Correctly defines the many-to-many relationship with User (Faculty)
    public function faculty(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_user');
    }

    // Correctly defines the one-to-many relationship with Mark
    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}