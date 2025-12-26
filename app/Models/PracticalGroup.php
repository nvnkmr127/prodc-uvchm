<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\WebhookEnabled;

class PracticalGroup extends Model
{
    use HasFactory, WebhookEnabled;

    protected $fillable = [
        'name',
        'batch_id',
        'classroom_id',
        'academic_year_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the batch that this practical group belongs to
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the classroom/lab assigned to this practical group
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the academic year for this practical group
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get all students assigned to this practical group
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'practical_group_student')
            ->withTimestamps()
            ->orderBy('name');
    }

    /**
     * Get active students only
     */
    public function activeStudents(): BelongsToMany
    {
        return $this->students()->where('students.status', 'active');
    }

    /**
     * Scope to filter by academic year
     */
    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope to filter by batch
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter by current academic year
     */
    public function scopeForCurrentYear($query)
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        if ($currentYear) {
            return $query->where('academic_year_id', $currentYear->id);
        }
        return $query;
    }

    /**
     * Get the utilization percentage of this group
     */
    public function getUtilizationAttribute(): float
    {
        if (!$this->classroom || $this->classroom->capacity == 0) {
            return 0;
        }
        
        $studentCount = $this->students()->count();
        return round(($studentCount / $this->classroom->capacity) * 100, 1);
    }

 public function timetableEntries()
    {
        return $this->hasMany(Timetable::class);
    }

    // ADD THIS: Get lab sessions for this group
    public function labSessions()
    {
        return $this->hasMany(Timetable::class)->where('is_lab_session', true);
    }

    // ADD THIS: Check if group has any scheduled sessions
    public function hasScheduledSessions()
    {
        return $this->timetableEntries()->exists();
    }

    // ADD THIS: Get upcoming lab sessions
    public function upcomingLabSessions()
    {
        return $this->labSessions()
                   ->where('schedule_date', '>=', now()->toDateString())
                   ->with(['subject', 'user', 'timeSlot'])
                   ->orderBy('schedule_date')
                   ->orderBy('time_slot_id');
    }

    /**
     * Check if the group is at full capacity
     */
    public function isFullAttribute(): bool
    {
        if (!$this->classroom) {
            return false;
        }
        
        return $this->students()->count() >= $this->classroom->capacity;
    }

    /**
     * Get available spots in this group
     */
    public function getAvailableSpotsAttribute(): int
    {
        if (!$this->classroom) {
            return 0;
        }
        
        $occupiedSpots = $this->students()->count();
        $availableSpots = $this->classroom->capacity - $occupiedSpots;
        
        return max(0, $availableSpots);
    }

    /**
     * Get the status of this group based on utilization
     */
    public function getStatusAttribute(): string
    {
        $utilization = $this->utilization;
        
        if ($utilization >= 95) {
            return 'full';
        } elseif ($utilization >= 80) {
            return 'high';
        } elseif ($utilization >= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get the status color for UI display
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'full' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get a short description of the group
     */
    public function getDescriptionAttribute(): string
    {
        $studentCount = $this->students()->count();
        $capacity = $this->classroom?->capacity ?? 0;
        $labName = $this->classroom?->name ?? 'Unknown Lab';
        
        return "Lab: {$labName} | Students: {$studentCount}/{$capacity}";
    }

    /**
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();

        // When a practical group is created, log the activity
        static::created(function ($practicalGroup) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($practicalGroup)
                ->withProperties([
                    'batch_name' => $practicalGroup->batch?->name,
                    'lab_name' => $practicalGroup->classroom?->name,
                    'academic_year' => $practicalGroup->academicYear?->name
                ])
                ->log('Practical group created');
        });

        // When students are attached/detached, we can handle it via pivot events
        static::updated(function ($practicalGroup) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($practicalGroup)
                ->log('Practical group updated');
        });

        static::deleted(function ($practicalGroup) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($practicalGroup)
                ->withProperties([
                    'batch_name' => $practicalGroup->batch?->name,
                    'lab_name' => $practicalGroup->classroom?->name,
                    'student_count' => $practicalGroup->students()->count()
                ])
                ->log('Practical group deleted');
        });
    }

    /**
     * Get the course through the batch relationship
     */
    public function getCourseAttribute()
    {
        return $this->batch?->course;
    }

    /**
     * Generate a suggested name for the group
     */
    public static function generateGroupName(Batch $batch, Classroom $classroom, int $groupNumber = 1): string
    {
        return "{$batch->name} - {$classroom->name} - Group {$groupNumber}";
    }

    /**
     * Check if a student can be added to this group
     */
    public function canAddStudent(Student $student): array
    {
        // Check if student is in the same batch
        if ($student->batch_id !== $this->batch_id) {
            return [
                'can_add' => false,
                'reason' => 'Student is not in the same batch as this group'
            ];
        }

        // Check if student is already in this group
        if ($this->students()->where('student_id', $student->id)->exists()) {
            return [
                'can_add' => false,
                'reason' => 'Student is already in this group'
            ];
        }

        // Check if student is in another group for this academic year
        $existingGroup = PracticalGroup::where('academic_year_id', $this->academic_year_id)
            ->whereHas('students', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
            ->first();

        if ($existingGroup) {
            return [
                'can_add' => false,
                'reason' => "Student is already assigned to '{$existingGroup->name}' for this academic year"
            ];
        }

        // Check capacity (warning, not blocking)
        $currentCount = $this->students()->count();
        if ($currentCount >= $this->classroom->capacity) {
            return [
                'can_add' => true,
                'reason' => 'Group is at full capacity but student can still be added',
                'warning' => true
            ];
        }

        return [
            'can_add' => true,
            'reason' => 'Student can be added to this group'
        ];
    }
}