<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // <-- This import is crucial
use Illuminate\Database\Eloquent\Relations\BelongsToMany;      // <-- This is for the marks relationship
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Subject extends Model
{
    use HasFactory, LogsActivity;
    use WebhookEnabled;

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
            ->setDescriptionForEvent(fn (string $eventName) => "The subject '{$this->name}' has been {$eventName}");
    }

    // Correctly defines the many-to-many relationship with Course
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_subject');
    }

    // Correctly defines the many-to-many relationship with User (Faculty)
    public function faculty()
    {
        return $this->belongsToMany(User::class, 'subject_user', 'subject_id', 'user_id')
            ->withTimestamps()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'staff');
            });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'subject_user', 'subject_id', 'user_id')
            ->withTimestamps();
    }

    // Correctly defines the one-to-many relationship with Mark
    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
