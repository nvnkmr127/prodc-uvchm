<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Subject;
use App\Models\Batch;
use App\Models\User;
use App\Models\Classroom;
use App\Models\Timetable;
use App\Models\TimeSlot;
use App\Models\PracticalGroup;
use App\Models\AcademicYear;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class EnhancedTimetableGenerationService
{
    /**
     * Required lab types as per FR-2, FR-3
     */
    const REQUIRED_LAB_TYPES = [
        'Service Lab',
        'Kitchen Lab', 
        'Front Office Lab',
        'Housekeeping Lab'
    ];

    /**
     * Generate weekly timetable with configurable working days
     */
    public function generateWeeklyTimetable($courseIds, $academicYear, $weekStart, $options = [])
    {
        try {
            DB::beginTransaction();

            $result = [
                'success' => true,
                'message' => '',
                'sessions_created' => 0,
                'lab_sessions' => 0,
                'theory_sessions' => 0,
                'conflicts' => 0,
                'report' => ''
            ];

            // Get working days configuration from settings
            $workingDays = $this->getWorkingDaysConfig();
            $result['report'] .= "📅 Working Days: " . implode(', ', $workingDays) . "\n";
            $result['report'] .= str_repeat("=", 50) . "\n";

            foreach ($courseIds as $courseId) {
                $course = Course::with(['batches.practicalGroups', 'subjects'])->findOrFail($courseId);
                $courseResult = $this->generateCourseWeeklyTimetable($course, $academicYear, $weekStart, $workingDays, $options);
                
                $result['sessions_created'] += $courseResult['sessions_created'];
                $result['lab_sessions'] += $courseResult['lab_sessions'];
                $result['theory_sessions'] += $courseResult['theory_sessions'];
                $result['conflicts'] += $courseResult['conflicts'];
                $result['report'] .= $courseResult['report'] . "\n";
            }

            DB::commit();

            $result['message'] = "Timetable generated successfully! Created {$result['sessions_created']} sessions.";
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Timetable generation failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Timetable generation failed: ' . $e->getMessage(),
                'sessions_created' => 0,
                'lab_sessions' => 0,
                'theory_sessions' => 0,
                'conflicts' => 0,
                'report' => "❌ Error: " . $e->getMessage()
            ];
        }
    }

    /**
     * Generate timetable for a specific course
     */
    private function generateCourseWeeklyTimetable($course, $academicYear, $weekStart, $workingDays, $options)
    {
        $result = [
            'sessions_created' => 0,
            'lab_sessions' => 0,
            'theory_sessions' => 0,
            'conflicts' => 0,
            'report' => "🎓 Processing Course: {$course->name}\n"
        ];

        // Get time slots categorized by time of day
        $timeSlots = TimeSlot::orderBy('start_time')->get();
        $morningSlots = $timeSlots->filter(function($slot) {
            return Carbon::parse($slot->start_time)->hour < 12;
        });
        $afternoonSlots = $timeSlots->filter(function($slot) {
            return Carbon::parse($slot->start_time)->hour >= 12;
        });

        // Get lab and theory subjects
        $labSubjects = $this->getLabSubjects();
        $theorySubjects = $course->subjects->where('requires_lab', false);

        foreach ($course->batches as $batch) {
            foreach ($batch->practicalGroups as $group) {
                $groupResult = $this->generateGroupWeeklySchedule(
                    $group, 
                    $academicYear, 
                    $weekStart, 
                    $workingDays, 
                    $labSubjects, 
                    $theorySubjects,
                    $morningSlots, 
                    $afternoonSlots
                );
                
                $result['sessions_created'] += $groupResult['sessions_created'];
                $result['lab_sessions'] += $groupResult['lab_sessions'];
                $result['theory_sessions'] += $groupResult['theory_sessions'];
                $result['conflicts'] += $groupResult['conflicts'];
                $result['report'] .= $groupResult['report'];
            }
        }

        return $result;
    }

    /**
     * Generate schedule for a practical group
     */
    private function generateGroupWeeklySchedule($group, $academicYear, $weekStart, $workingDays, $labSubjects, $theorySubjects, $morningSlots, $afternoonSlots)
    {
        $result = [
            'sessions_created' => 0,
            'lab_sessions' => 0,
            'theory_sessions' => 0,
            'conflicts' => 0,
            'report' => "  📚 Group: {$group->name}\n"
        ];

        // Generate week days based on working days configuration
        $weekDays = $this->generateWeekDays($weekStart, $workingDays);
        $scheduledLabDays = [];

        // Schedule required labs (FR-2, FR-3) - one per week
        foreach (self::REQUIRED_LAB_TYPES as $index => $labType) {
            $subject = $labSubjects->firstWhere('name', 'LIKE', "%{$labType}%");
            if (!$subject) {
                $result['report'] .= "    ⚠️ Subject for '{$labType}' not found\n";
                continue;
            }

            // Find available day (avoid Saturday for labs if configured)
            $availableDays = $this->getAvailableLabDays($weekDays, $scheduledLabDays, $workingDays);
            if (empty($availableDays)) {
                $result['report'] .= "    ⚠️ No available days for '{$labType}'\n";
                continue;
            }

            $dayIndex = array_shift($availableDays);
            $scheduleDate = $weekDays[$dayIndex];
            
            // Try morning first, then afternoon (FR-4, FR-5)
            $labScheduled = false;
            
            // Morning lab attempt
            foreach ($morningSlots as $slot) {
                if ($this->scheduleLabSession($group, $subject, $scheduleDate, $slot, $academicYear)) {
                    $result['sessions_created']++;
                    $result['lab_sessions']++;
                    $result['report'] .= "    ✅ {$labType} - {$scheduleDate->format('l')} {$slot->start_time} (Morning)\n";
                    $scheduledLabDays[] = $dayIndex;
                    $labScheduled = true;
                    
                    // FR-4: Theory in afternoon when lab in morning
                    $this->scheduleTheorySession($group, $theorySubjects, $scheduleDate, $afternoonSlots, $academicYear, $result);
                    break;
                }
            }

            // Afternoon lab if morning failed
            if (!$labScheduled) {
                foreach ($afternoonSlots as $slot) {
                    if ($this->scheduleLabSession($group, $subject, $scheduleDate, $slot, $academicYear)) {
                        $result['sessions_created']++;
                        $result['lab_sessions']++;
                        $result['report'] .= "    ✅ {$labType} - {$scheduleDate->format('l')} {$slot->start_time} (Afternoon)\n";
                        $scheduledLabDays[] = $dayIndex;
                        $labScheduled = true;
                        
                        // FR-5: Theory in morning when lab in afternoon
                        $this->scheduleTheorySession($group, $theorySubjects, $scheduleDate, $morningSlots, $academicYear, $result);
                        break;
                    }
                }
            }
        }

        // Schedule theory sessions for remaining days
        foreach ($weekDays as $dayIndex => $date) {
            if (!in_array($dayIndex, $scheduledLabDays)) {
                // FR-5: Saturday theory only
                if ($date->isSaturday()) {
                    $this->scheduleTheorySession($group, $theorySubjects, $date, $morningSlots, $academicYear, $result);
                    $this->scheduleTheorySession($group, $theorySubjects, $date, $afternoonSlots, $academicYear, $result);
                } else {
                    // Regular days: both morning and afternoon theory
                    $this->scheduleTheorySession($group, $theorySubjects, $date, $morningSlots, $academicYear, $result);
                    $this->scheduleTheorySession($group, $theorySubjects, $date, $afternoonSlots, $academicYear, $result);
                }
            }
        }

        return $result;
    }

    /**
     * Get working days configuration from admin settings
     */
    private function getWorkingDaysConfig(): array
    {
        // Get from settings or use default 5.5 days (Mon-Sat)
        $workingDaysSetting = Setting::where('key', 'working_days')->first();
        
        if ($workingDaysSetting && $workingDaysSetting->value) {
            $configuredDays = json_decode($workingDaysSetting->value, true);
            return is_array($configuredDays) ? $configuredDays : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        }
        
        // Default: 5.5 working days (Mon-Sat)
        return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    }

    /**
     * Generate week days based on working days configuration
     */
    private function generateWeekDays($weekStart, $workingDays): array
    {
        $weekDays = [];
        $monday = Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY);
        
        $dayMap = [
            'monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 
            'thursday' => 3, 'friday' => 4, 'saturday' => 5, 'sunday' => 6
        ];
        
        foreach ($workingDays as $day) {
            if (isset($dayMap[$day])) {
                $weekDays[$dayMap[$day]] = $monday->copy()->addDays($dayMap[$day]);
            }
        }
        
        return $weekDays;
    }

    /**
     * Get available days for lab scheduling (avoid Saturday for labs)
     */
    private function getAvailableLabDays($weekDays, $scheduledLabDays, $workingDays): array
    {
        $availableDays = [];
        
        foreach ($weekDays as $dayIndex => $date) {
            // Skip already scheduled days
            if (in_array($dayIndex, $scheduledLabDays)) {
                continue;
            }
            
            // Skip Saturday for labs (theory only on Saturday - FR-5)
            if ($date->isSaturday()) {
                continue;
            }
            
            $availableDays[] = $dayIndex;
        }
        
        return $availableDays;
    }

    /**
     * Schedule a lab session with conflict checking
     */
    private function scheduleLabSession($group, $subject, $date, $slot, $academicYear)
    {
        try {
            // Find available faculty
            $faculty = $this->findAvailableFaculty($subject, $date, $slot);
            if (!$faculty) {
                return false;
            }

            // Find available classroom
            $classroom = $this->findAvailableClassroom($date, $slot, true); // true = lab classroom
            if (!$classroom) {
                return false;
            }

            // Check for conflicts
            if ($this->hasConflict($group, $faculty, $classroom, $date, $slot)) {
                return false;
            }

            // Create lab session
            Timetable::create([
                'batch_id' => $group->batch_id,
                'practical_group_id' => $group->id,
                'subject_id' => $subject->id,
                'user_id' => $faculty->id,
                'classroom_id' => $classroom->id,
                'time_slot_id' => $slot->id,
                'schedule_date' => $date->format('Y-m-d'),
                'academic_year_id' => $academicYear->id,
                'is_lab_session' => true,
                'notes' => "Lab session for {$group->name} - {$subject->name}"
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to create lab session', [
                'error' => $e->getMessage(),
                'group_id' => $group->id,
                'subject_id' => $subject->id,
                'date' => $date->format('Y-m-d'),
                'slot_id' => $slot->id
            ]);
            
            return false;
        }
    }

    /**
     * Schedule theory session
     */
    private function scheduleTheorySession($group, $theorySubjects, $date, $timeSlots, $academicYear, &$result)
    {
        foreach ($timeSlots as $slot) {
            $subject = $theorySubjects->random();
            if (!$subject) continue;

            $faculty = $this->findAvailableFaculty($subject, $date, $slot);
            if (!$faculty) continue;

            $classroom = $this->findAvailableClassroom($date, $slot, false); // false = regular classroom
            if (!$classroom) continue;

            if ($this->hasConflict($group, $faculty, $classroom, $date, $slot)) {
                continue;
            }

            // Create theory session
            Timetable::create([
                'batch_id' => $group->batch_id,
                'practical_group_id' => null, // Theory is for entire batch
                'subject_id' => $subject->id,
                'user_id' => $faculty->id,
                'classroom_id' => $classroom->id,
                'time_slot_id' => $slot->id,
                'schedule_date' => $date->format('Y-m-d'),
                'academic_year_id' => $academicYear->id,
                'is_lab_session' => false,
                'notes' => "Theory session for {$group->batch->name}"
            ]);

            $result['sessions_created']++;
            $result['theory_sessions']++;
            $result['report'] .= "    ✅ Theory: {$subject->name} - {$date->format('l')} {$slot->start_time}\n";
            return; // Only one theory session per time slot
        }
    }

    /**
     * Get lab subjects, create if missing
     */
    private function getLabSubjects(): Collection
    {
        $subjects = collect();
        
        foreach (self::REQUIRED_LAB_TYPES as $labType) {
            $subject = Subject::where('requires_lab', true)
                ->where('name', 'LIKE', "%{$labType}%")
                ->first();
                
            if (!$subject) {
                // Auto-create missing lab subject
                $subject = Subject::create([
                    'name' => $labType,
                    'code' => strtoupper(str_replace(' ', '', $labType)),
                    'requires_lab' => true,
                    'lab_hours' => 2,
                    'theory_hours' => 0,
                    'description' => "Practical {$labType} sessions"
                ]);
            }
            
            $subjects->push($subject);
        }
        
        return $subjects;
    }

    /**
     * Find available faculty for a subject at given time
     */
    private function findAvailableFaculty(Subject $subject, Carbon $date, TimeSlot $slot)
    {
        // Get faculty assigned to this subject
        $assignedFaculty = $subject->users()->role('staff')->get();
        
        foreach ($assignedFaculty as $faculty) {
            // Check if faculty is available at this time
            $hasConflict = Timetable::where('user_id', $faculty->id)
                ->where('schedule_date', $date->format('Y-m-d'))
                ->where('time_slot_id', $slot->id)
                ->exists();
                
            if (!$hasConflict) {
                return $faculty;
            }
        }
        
        return null;
    }

    /**
     * Find available classroom for given time
     */
    private function findAvailableClassroom(Carbon $date, TimeSlot $slot, $isLab = false)
    {
        $query = Classroom::query();
        
        if ($isLab) {
            $query->where('is_lab', true);
        }
        
        $classrooms = $query->get();
        
        foreach ($classrooms as $classroom) {
            // Check if classroom is available at this time
            $hasConflict = Timetable::where('classroom_id', $classroom->id)
                ->where('schedule_date', $date->format('Y-m-d'))
                ->where('time_slot_id', $slot->id)
                ->exists();
                
            if (!$hasConflict) {
                return $classroom;
            }
        }
        
        return null;
    }

    /**
     * Check for scheduling conflicts
     */
    private function hasConflict($group, $faculty, $classroom, $date, $slot)
    {
        // Check batch conflict
        $batchConflict = Timetable::where('batch_id', $group->batch_id)
            ->where('schedule_date', $date->format('Y-m-d'))
            ->where('time_slot_id', $slot->id)
            ->exists();

        // Check faculty conflict  
        $facultyConflict = Timetable::where('user_id', $faculty->id)
            ->where('schedule_date', $date->format('Y-m-d'))
            ->where('time_slot_id', $slot->id)
            ->exists();

        // Check classroom conflict
        $classroomConflict = Timetable::where('classroom_id', $classroom->id)
            ->where('schedule_date', $date->format('Y-m-d'))
            ->where('time_slot_id', $slot->id)
            ->exists();

        return $batchConflict || $facultyConflict || $classroomConflict;
    }
}