<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timetable extends Model
{
    use HasFactory, WebhookEnabled;

    protected $fillable = [
        'batch_id',
        'practical_group_id',    // NEW: For lab sessions
        'subject_id',
        'user_id',
        'classroom_id',
        'time_slot_id',
        'schedule_date',
        'academic_year_id',
        'is_lab_session',        // NEW: Flag for lab sessions
        'notes',                  // NEW: Additional notes
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'is_lab_session' => 'boolean',  // NEW
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the batch that this timetable entry belongs to
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * NEW: Get the practical group for lab sessions
     */
    public function practicalGroup(): BelongsTo
    {
        return $this->belongsTo(PracticalGroup::class);
    }

    /**
     * Get the subject for this timetable entry
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the faculty/user assigned to this timetable entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the classroom assigned to this timetable entry
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the time slot for this timetable entry
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * Get the academic year for this timetable entry
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // =================== NEW LAB-SPECIFIC SCOPES ===================

    /**
     * NEW: Scope for lab sessions only
     */
    public function scopeLabSessions($query)
    {
        return $query->where('is_lab_session', true);
    }

    /**
     * NEW: Scope for regular classes only
     */
    public function scopeRegularClasses($query)
    {
        return $query->where('is_lab_session', false);
    }

    /**
     * NEW: Scope to filter by practical group
     */
    public function scopeForPracticalGroup($query, $practicalGroupId)
    {
        return $query->where('practical_group_id', $practicalGroupId);
    }

    // =================== EXISTING SCOPES ===================

    /**
     * Scope to filter by academic year
     */
    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
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
     * Scope to filter by batch
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('schedule_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by weekday
     */
    public function scopeForWeekday($query, $weekday)
    {
        return $query->whereRaw('DAYNAME(schedule_date) = ?', [$weekday]);
    }

    // =================== NEW HELPER METHODS ===================

    /**
     * NEW: Check if this is a lab session
     */
    public function isLabSession(): bool
    {
        return $this->is_lab_session;
    }

    /**
     * NEW: Get display name based on session type
     */
    public function getDisplayName(): string
    {
        if ($this->isLabSession() && $this->practicalGroup) {
            return $this->practicalGroup->name.' - '.$this->subject?->name.' (Lab)';
        }

        return $this->batch?->name.' - '.$this->subject?->name;
    }

    /**
     * NEW: Get student count based on session type
     */
    public function getStudentCount(): int
    {
        if ($this->isLabSession() && $this->practicalGroup) {
            return $this->practicalGroup->students()->count();
        }

        return $this->batch?->students()->where('status', 'active')->count() ?? 0;
    }

    /**
     * NEW: Get attendees based on session type
     */
    public function getAttendees()
    {
        if ($this->isLabSession() && $this->practicalGroup) {
            return $this->practicalGroup->students;
        }

        return $this->batch?->students()->where('status', 'active')->get() ?? collect();
    }

    // =================== EXISTING COMPUTED ATTRIBUTES ===================

    /**
     * Get the course through the batch relationship
     */
    public function getCourseAttribute()
    {
        return $this->batch?->course;
    }

    /**
     * Get the weekday name for this timetable entry
     */
    public function getWeekdayAttribute()
    {
        return $this->schedule_date->format('l');
    }

    /**
     * Get formatted time range for this entry
     */
    public function getTimeRangeAttribute()
    {
        if (! $this->timeSlot) {
            return 'No time slot';
        }

        return $this->timeSlot->start_time.' - '.$this->timeSlot->end_time;
    }

    /**
     * ENHANCED: Get a display-friendly title for this timetable entry
     */
    public function getTitleAttribute()
    {
        $title = $this->getDisplayName();

        if ($this->isLabSession()) {
            return $title; // Already includes (Lab) suffix
        }

        return $title;
    }

    /**
     * ENHANCED: Get a detailed description for this timetable entry
     */
    public function getDescriptionAttribute()
    {
        $parts = [];

        if ($this->subject) {
            $parts[] = 'Subject: '.$this->subject->name;
        }

        if ($this->user) {
            $parts[] = 'Faculty: '.$this->user->name;
        }

        if ($this->classroom) {
            $parts[] = 'Room: '.$this->classroom->name;
        }

        // NEW: Add practical group info for lab sessions
        if ($this->isLabSession() && $this->practicalGroup) {
            $parts[] = 'Group: '.$this->practicalGroup->name;
            $parts[] = 'Students: '.$this->getStudentCount();
        }

        if ($this->academicYear) {
            $parts[] = 'Year: '.$this->academicYear->name;
        }

        return implode(' | ', $parts);
    }

    // =================== ENHANCED CONFLICT DETECTION ===================

    /**
     * ENHANCED: Check for scheduling conflicts with lab group awareness
     */
    public function hasConflicts($excludeId = null)
    {
        $query = self::where('schedule_date', $this->schedule_date)
            ->where('time_slot_id', $this->time_slot_id)
            ->where('academic_year_id', $this->academic_year_id);

        // Check different types of conflicts
        $query->where(function ($q) {
            // Faculty conflict (same faculty teaching multiple classes)
            $q->where('user_id', $this->user_id)
              // Classroom conflict (same room being used)
                ->orWhere('classroom_id', $this->classroom_id);

            // NEW: Batch-level conflict check
            if (! $this->isLabSession()) {
                // For regular classes, check if batch has any other session
                $q->orWhere('batch_id', $this->batch_id);
            } else {
                // For lab sessions, check if any student in the practical group has another class
                if ($this->practical_group_id) {
                    $q->orWhere(function ($subQuery) {
                        $subQuery->where('batch_id', $this->batch_id)
                            ->where('is_lab_session', false); // Regular classes conflict with lab sessions
                    });
                }
            }
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * ENHANCED: Get conflicts for this timetable entry
     */
    public function getConflicts($excludeId = null)
    {
        $query = self::with(['batch', 'practicalGroup', 'subject', 'user', 'classroom'])
            ->where('schedule_date', $this->schedule_date)
            ->where('time_slot_id', $this->time_slot_id)
            ->where('academic_year_id', $this->academic_year_id)
            ->where(function ($q) {
                $q->where('user_id', $this->user_id)
                    ->orWhere('classroom_id', $this->classroom_id);

                if (! $this->isLabSession()) {
                    $q->orWhere('batch_id', $this->batch_id);
                } else {
                    if ($this->practical_group_id) {
                        $q->orWhere(function ($subQuery) {
                            $subQuery->where('batch_id', $this->batch_id)
                                ->where('is_lab_session', false);
                        });
                    }
                }
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    // =================== ENHANCED MODEL EVENTS ===================

    /**
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();

        // When a timetable entry is created, log the activity
        static::created(function ($timetable) {
            $properties = [
                'batch_name' => $timetable->batch?->name,
                'subject_name' => $timetable->subject?->name,
                'faculty_name' => $timetable->user?->name,
                'classroom_name' => $timetable->classroom?->name,
                'academic_year' => $timetable->academicYear?->name,
                'schedule_date' => $timetable->schedule_date,
                'time_range' => $timetable->time_range,
                'is_lab_session' => $timetable->is_lab_session,
            ];

            // NEW: Add practical group info for lab sessions
            if ($timetable->isLabSession() && $timetable->practicalGroup) {
                $properties['practical_group'] = $timetable->practicalGroup->name;
                $properties['student_count'] = $timetable->getStudentCount();
            }

            activity()
                ->causedBy(auth()->user())
                ->performedOn($timetable)
                ->withProperties($properties)
                ->log($timetable->isLabSession() ? 'Lab session scheduled' : 'Timetable entry created');
        });

        static::updated(function ($timetable) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($timetable)
                ->log($timetable->isLabSession() ? 'Lab session updated' : 'Timetable entry updated');
        });

        static::deleted(function ($timetable) {
            $properties = [
                'batch_name' => $timetable->batch?->name,
                'subject_name' => $timetable->subject?->name,
                'schedule_date' => $timetable->schedule_date,
                'is_lab_session' => $timetable->is_lab_session,
            ];

            if ($timetable->isLabSession() && $timetable->practicalGroup) {
                $properties['practical_group'] = $timetable->practicalGroup->name;
            }

            activity()
                ->causedBy(auth()->user())
                ->performedOn($timetable)
                ->withProperties($properties)
                ->log($timetable->isLabSession() ? 'Lab session deleted' : 'Timetable entry deleted');
        });
    }
}
