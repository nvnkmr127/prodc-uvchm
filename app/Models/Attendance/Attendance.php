<?php

// File: app/Models/Attendance/Attendance.php

namespace App\Models\Attendance;

use App\Models\Batch;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use App\Traits\WebhookEnabled;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, WebhookEnabled;

    protected $table = 'attendances';

    protected $fillable = [
        'student_id',
        'batch_id',
        'subject_id',
        'time_slot_id',
        'faculty_id',
        'attendance_date',
        'status',
        'marked_at',
        'marked_by',
        'notes',
        'biometric_log_id',
        'late_minutes',
        'location',
        'device_id',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'marked_at' => 'datetime',
        'late_minutes' => 'integer',
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

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function biometricLog(): BelongsTo
    {
        return $this->belongsTo(BiometricLog::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('attendance_date', Carbon::today());
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeForDateRange($query, $from, $to)
    {
        return $query->whereBetween('attendance_date', [$from, $to]);
    }

    // Accessors & Mutators
    public function getIsLateAttribute(): bool
    {
        return $this->status === 'late';
    }

    public function getIsPresentAttribute(): bool
    {
        return in_array($this->status, ['present', 'late']);
    }

    public function getIsAbsentAttribute(): bool
    {
        return $this->status === 'absent';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'excused' => 'Excused',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'excused' => 'info',
            default => 'secondary'
        };
    }

    // Static Methods
    public static function getValidStatuses(): array
    {
        return ['present', 'absent', 'late', 'excused'];
    }

    public static function calculateAttendancePercentage($studentId, $from = null, $to = null): float
    {
        $query = static::where('student_id', $studentId);

        if ($from && $to) {
            $query->whereBetween('attendance_date', [$from, $to]);
        }

        $total = $query->count();
        $present = $query->present()->count();

        return $total > 0 ? round(($present / $total) * 100, 2) : 0;
    }

    // Event Handlers
    protected static function booted()
    {
        static::creating(function ($attendance) {
            $attendance->marked_at = $attendance->marked_at ?? now();
            $attendance->marked_by = $attendance->marked_by ?? auth()->id();
        });

        static::created(function ($attendance) {
            // Trigger attendance created event
            event(new \App\Events\Attendance\AttendanceEvent('created', $attendance));
        });

        static::updated(function ($attendance) {
            // Trigger attendance updated event
            event(new \App\Events\Attendance\AttendanceEvent('updated', $attendance));
        });
    }
}
