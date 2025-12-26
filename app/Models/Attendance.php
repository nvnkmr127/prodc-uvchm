<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAcademicYear;

class Attendance extends Model
{
    use HasAcademicYear;
    protected $fillable = [
        'student_id',
        'batch_id',
        'faculty_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'status',
        'marked_at',
        'marked_by',
        'notes',
        'late_minutes',
        'location',
        'device_id',
        'biometric_log_id'
    ];
    
    protected $casts = [
        'attendance_date' => 'date',
        'marked_at' => 'datetime',
        'check_in_time' => 'datetime:H:i:s',
        'check_out_time' => 'datetime:H:i:s',
        'late_minutes' => 'integer'
    ];
    
    // Default values for required fields
    protected $attributes = [
        'status' => 'present',
        'device_id' => 'manual'
    ];
    
    /**
     * Student relationship
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    
    /**
     * Batch relationship
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
    
    /**
     * Faculty relationship (who marked the attendance)
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }
    
    /**
     * User who marked this attendance
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
    
    /**
     * Calculate total hours spent (if both check-in and check-out exist)
     */
    public function getTotalHoursAttribute(): ?float
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }
        
        $checkIn = \Carbon\Carbon::parse($this->attendance_date . ' ' . $this->check_in_time);
        $checkOut = \Carbon\Carbon::parse($this->attendance_date . ' ' . $this->check_out_time);
        
        return $checkOut->diffInHours($checkIn, true);
    }
    
    /**
     * Get formatted check-in time
     */
    public function getFormattedCheckInAttribute(): ?string
    {
        return $this->check_in_time ? \Carbon\Carbon::parse($this->check_in_time)->format('h:i A') : null;
    }
    
    /**
     * Get formatted check-out time
     */
    public function getFormattedCheckOutAttribute(): ?string
    {
        return $this->check_out_time ? \Carbon\Carbon::parse($this->check_out_time)->format('h:i A') : null;
    }
    
    /**
     * Check if student was late
     */
    public function getIsLateAttribute(): bool
    {
        return $this->late_minutes > 0 || $this->status === 'late';
    }
    
    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'present' => 'success',
            'late' => 'warning',
            'absent' => 'danger',
            'excused' => 'info',
            default => 'secondary'
        };
    }
    
    /**
     * Scope for today's attendance
     */
    public function scopeToday($query)
    {
        return $query->whereDate('attendance_date', today());
    }
    
    /**
     * Scope for present students
     */
    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }
    
    /**
     * Scope for absent students
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }
    
    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }
}