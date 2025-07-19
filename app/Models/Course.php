<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\WebhookEnabled;

class Course extends Model
{
    use WebhookEnabled;
    use HasFactory, LogsActivity;

    // COMPLETE: All fields that exist in your database
    protected $fillable = [
        'name',
        'enrollment_prefix',
        'code',
        'duration_in_years',
        'duration_months',
        'max_batch_size',
        'description'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'duration_in_years', 'max_batch_size'])
            ->setDescriptionForEvent(fn(string $eventName) => "The course '{$this->name}' has been {$eventName}");
    }
    
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(Student::class, Batch::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class);
    }
    
    public function feeStructures()
    {
        return $this->hasManyThrough(\App\Models\FeeStructure::class, \App\Models\Batch::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
    
    public function terms(): HasMany
    {
        return $this->hasMany(CourseTerm::class)->orderBy('sequence');
    }
}