<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;
class Attendance extends Model
{
    use WebhookEnabled;
    use HasFactory;

    // ✅ CORRECT fillable fields matching database schema
    protected $fillable = [
        'student_id',
        'batch_id', 
        'subject_id',
        'time_slot_id',
        'faculty_id',
        'attendance_date',
        'status'
    ];

    protected $casts = [
        'attendance_date' => 'date'
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }
}