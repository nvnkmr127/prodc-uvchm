<?php

namespace App\Models\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyAttendance extends Model
{
    use HasFactory;

    protected $table = 'faculty_attendances';

    protected $fillable = [
        'faculty_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'working_hours',
        'status',
        'late_minutes',
        'notes',
        'device_id',
        'biometric_log_id',
        'marked_at',
        'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'marked_at' => 'datetime',
        'working_hours' => 'decimal:2',
        'late_minutes' => 'integer',
    ];

    // Relationships
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // Scopes
    public function scopeForFaculty($query, $facultyId)
    {
        return $query->where('faculty_id', $facultyId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeForDateRange($query, $from, $to)
    {
        return $query->whereBetween('attendance_date', [$from, $to]);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late', 'half_day']);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeHalfDay($query)
    {
        return $query->where('status', 'half_day');
    }

    public function scopeExcused($query)
    {
        return $query->where('status', 'excused');
    }

    // Accessors
    public function getPresentValueAttribute(): float
    {
        if ($this->status === 'absent' || $this->status === 'excused') {
            return 0.0;
        }
        
        if ($this->status === 'half_day') {
            return 0.5;
        }

        // If check-out is missing (single punch)
        if (!$this->check_out_time) {
            // For today, assume they are still checked in and present
            if ($this->attendance_date && $this->attendance_date->isToday()) {
                return 1.0;
            }
            return 0.5;
        }

        // The Service handles assigning half_day status based on dynamic shift length,
        // so we don't need a hard-coded 4.0 hours check here anymore.
        return 1.0;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'half_day' => 'Half Day',
            'excused' => 'On Leave',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'half_day' => 'info',
            'excused' => 'primary',
            default => 'secondary'
        };
    }

    // Booted
    protected static function booted()
    {
        static::creating(function ($attendance) {
            $attendance->marked_at = $attendance->marked_at ?? now();
            $attendance->marked_by = $attendance->marked_by ?? auth()->id();
        });
    }
}
