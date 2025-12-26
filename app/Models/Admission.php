<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Traits\WebhookEnabled;
use App\Traits\HasAcademicYear;

class Admission extends Model
{
    use WebhookEnabled;
    use HasFactory;
    use HasAcademicYear;

    protected $fillable = [
        'course_id', 'full_name', 'email', 'phone_number', 
        'date_of_birth', 'address', 'education_qualification', 
        'source', 'referral_name', 'status', 'gender', 'enquiry_id'
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    // --- TEMPORARY: The old relationship, renamed for the migration script ---
    public function oldAdmissionFollowUps(): HasMany
    {
        // This points to your ORIGINAL AdmissionFollowUp model
        return $this->hasMany(\App\Models\AdmissionFollowUp::class)->latest();
    }

    // --- NEW: The new polymorphic relationship ---
    public function followUps(): MorphMany
    {
        return $this->morphMany(\App\Models\FollowUp::class, 'followable');
    }
}