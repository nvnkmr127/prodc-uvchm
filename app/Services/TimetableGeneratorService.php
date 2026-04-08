<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\Timetable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimetableGeneratorService
{
    /**
     * Generate weekly timetable with configurable working days
     *
     * @param  array  $courseIds
     * @param  AcademicYear  $academicYear
     * @param  Carbon  $weekStart
     * @param  array  $options
     * @return array
     */
    public function generateWeeklyTimetable($courseIds, $academicYear, $weekStart, $options = [])
    {
        try {
            Log::info('Starting timetable generation', [
                'course_ids' => $courseIds,
                'week_start' => $weekStart->format('Y-m-d'),
                'options' => $options,
            ]);

            DB::beginTransaction();

            $result = [
                'success' => true,
                'message' => '',
                'sessions_created' => 0,
                'lab_sessions' => 0,
                'theory_sessions' => 0,
                'conflicts' => 0,
                'skipped' => 0,
                'report' => '',
                'details' => [],
            ];

            // Get working days configuration
            $workingDays = $this->getWorkingDaysConfig();
            $result['report'] .= '📅 Working Days: '.implode(', ', array_map('ucfirst', $workingDays))."\n";
            $result['report'] .= str_repeat('=', 60)."\n\n";

            // Process each course
            foreach ($courseIds as $courseId) {
                $course = Course::with(['batches.students', 'subjects'])->findOrFail($courseId);
                $result['report'] .= "🎓 Processing Course: {$course->name}\n";

                $courseResult = $this->generateCourseWeeklyTimetable($course, $academicYear, $weekStart, $workingDays, $options);

                // Merge results
                $result['sessions_created'] += $courseResult['sessions_created'];
                $result['lab_sessions'] += $courseResult['lab_sessions'];
                $result['theory_sessions'] += $courseResult['theory_sessions'];
                $result['conflicts'] += $courseResult['conflicts'];
                $result['skipped'] += $courseResult['skipped'];
                $result['report'] .= $courseResult['report']."\n";
                $result['details'] = array_merge($result['details'], $courseResult['details']);
            }

            DB::commit();

            $result['message'] = "✅ Timetable generated successfully! Created {$result['sessions_created']} sessions ".
                "({$result['theory_sessions']} theory, {$result['lab_sessions']} lab). ".
                "Conflicts: {$result['conflicts']}, Skipped: {$result['skipped']}";

            Log::info('Timetable generation completed', $result);

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Timetable generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Timetable generation failed: '.$e->getMessage(),
                'sessions_created' => 0,
                'lab_sessions' => 0,
                'theory_sessions' => 0,
                'conflicts' => 0,
                'skipped' => 0,
                'report' => '❌ Error: '.$e->getMessage(),
                'details' => [],
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
            'skipped' => 0,
            'report' => '',
            'details' => [],
        ];

        // Get course subjects
        $subjects = $course->subjects ?? collect();
        $labSubjects = $subjects->where('requires_lab', true);
        $theorySubjects = $subjects->where('requires_lab', false);

        $result['report'] .= "  📚 Total Subjects: {$subjects->count()} (Lab: {$labSubjects->count()}, Theory: {$theorySubjects->count()})\n";

        // Get course batches
        $batches = $course->batches ?? collect();
        $result['report'] .= "  👥 Batches: {$batches->count()}\n";

        if ($batches->isEmpty()) {
            $result['report'] .= "  ⚠️  No batches found for this course\n";

            return $result;
        }

        // Process each batch
        foreach ($batches as $batch) {
            $count = isset($batch->students_count) ? $batch->students_count : 0;
            $result['report'] .= "    📋 Processing Batch: {$batch->name} ({$count} students)\n";

            $batchResult = $this->generateBatchWeeklyTimetable(
                $batch,
                $subjects,
                $academicYear,
                $weekStart,
                $workingDays,
                $options
            );

            // Merge batch results
            $result['sessions_created'] += $batchResult['sessions_created'];
            $result['lab_sessions'] += $batchResult['lab_sessions'];
            $result['theory_sessions'] += $batchResult['theory_sessions'];
            $result['conflicts'] += $batchResult['conflicts'];
            $result['skipped'] += $batchResult['skipped'];
            $result['report'] .= $batchResult['report'];
            $result['details'] = array_merge($result['details'], $batchResult['details']);
        }

        return $result;
    }

    /**
     * Generate weekly timetable for a specific batch
     */
    private function generateBatchWeeklyTimetable($batch, $subjects, $academicYear, $weekStart, $workingDays, $options)
    {
        $result = [
            'sessions_created' => 0,
            'lab_sessions' => 0,
            'theory_sessions' => 0,
            'conflicts' => 0,
            'skipped' => 0,
            'report' => '',
            'details' => [],
        ];

        // Get week dates based on working days
        $weekDates = $this->getWeekDates($weekStart, $workingDays);
        $timeSlots = TimeSlot::orderBy('start_time')->get();

        if ($timeSlots->isEmpty()) {
            $result['report'] .= "      ⚠️  No time slots configured\n";

            return $result;
        }

        $result['report'] .= '      📅 Week dates: '.$weekDates->map(fn ($d) => $d->format('M d'))->join(', ')."\n";
        $result['report'] .= "      ⏰ Time slots: {$timeSlots->count()}\n";

        // Generate sessions for each subject
        foreach ($subjects as $subject) {
            if (! $this->shouldGenerateSubject($subject, $options)) {
                continue;
            }

            $subjectResult = $this->generateSubjectSessions(
                $batch,
                $subject,
                $academicYear,
                $weekDates,
                $timeSlots,
                $options
            );

            // Merge subject results
            $result['sessions_created'] += $subjectResult['sessions_created'];
            $result['lab_sessions'] += $subjectResult['lab_sessions'];
            $result['theory_sessions'] += $subjectResult['theory_sessions'];
            $result['conflicts'] += $subjectResult['conflicts'];
            $result['skipped'] += $subjectResult['skipped'];
            $result['report'] .= $subjectResult['report'];
            $result['details'] = array_merge($result['details'], $subjectResult['details']);
        }

        return $result;
    }

    /**
     * Generate sessions for a specific subject
     */
    private function generateSubjectSessions($batch, $subject, $academicYear, $weekDates, $timeSlots, $options)
    {
        $result = [
            'sessions_created' => 0,
            'lab_sessions' => 0,
            'theory_sessions' => 0,
            'conflicts' => 0,
            'skipped' => 0,
            'report' => '',
            'details' => [],
        ];

        $isLabSubject = $subject->requires_lab ?? false;
        $sessionsNeeded = $subject->weekly_hours ?? 2; // Default 2 sessions per week

        $result['report'] .= "        📖 Subject: {$subject->name} ".
            ($isLabSubject ? '(LAB)' : '(THEORY)').
            " - Need {$sessionsNeeded} sessions\n";

        $sessionsScheduled = 0;

        // Try to schedule the required number of sessions
        foreach ($weekDates as $date) {
            if ($sessionsScheduled >= $sessionsNeeded) {
                break;
            }

            // Try different time slots for this date
            foreach ($timeSlots as $timeSlot) {
                if ($sessionsScheduled >= $sessionsNeeded) {
                    break;
                }

                $sessionResult = $this->createTimetableSession(
                    $batch,
                    $subject,
                    $academicYear,
                    $date,
                    $timeSlot,
                    $isLabSubject,
                    $options
                );

                if ($sessionResult['success']) {
                    $sessionsScheduled++;
                    $result['sessions_created']++;

                    if ($isLabSubject) {
                        $result['lab_sessions']++;
                    } else {
                        $result['theory_sessions']++;
                    }

                    $result['details'][] = $sessionResult['session'];
                    $result['report'] .= "          ✅ Scheduled: {$date->format('D M d')} at {$timeSlot->start_time}-{$timeSlot->end_time}\n";
                } else {
                    if ($sessionResult['conflict']) {
                        $result['conflicts']++;
                    } else {
                        $result['skipped']++;
                    }
                    // Don't log every failure to keep report concise
                }
            }
        }

        if ($sessionsScheduled < $sessionsNeeded) {
            $result['report'] .= "          ⚠️  Only scheduled {$sessionsScheduled}/{$sessionsNeeded} sessions\n";
        }

        return $result;
    }

    /**
     * Create a single timetable session
     */
    private function createTimetableSession($batch, $subject, $academicYear, $date, $timeSlot, $isLabSession, $options)
    {
        try {
            // Check for conflicts first
            $conflicts = $this->checkForConflicts($batch, $date, $timeSlot, $options);

            if (! empty($conflicts) && ! ($options['allow_conflicts'] ?? false)) {
                return [
                    'success' => false,
                    'conflict' => true,
                    'message' => 'Conflicts detected: '.implode(', ', $conflicts),
                ];
            }

            // Find available faculty
            $faculty = $this->findAvailableFaculty($subject, $date, $timeSlot);
            if (! $faculty) {
                return [
                    'success' => false,
                    'conflict' => false,
                    'message' => 'No available faculty',
                ];
            }

            // Find available classroom
            $classroom = $this->findAvailableClassroom($date, $timeSlot, $isLabSession);
            if (! $classroom) {
                return [
                    'success' => false,
                    'conflict' => false,
                    'message' => 'No available classroom',
                ];
            }

            // Create the timetable entry
            $timetable = Timetable::create([
                'batch_id' => $batch->id,
                'subject_id' => $subject->id,
                'user_id' => $faculty->id,
                'classroom_id' => $classroom->id,
                'time_slot_id' => $timeSlot->id,
                'schedule_date' => $date->format('Y-m-d'),
                'academic_year_id' => $academicYear->id,
                'is_lab_session' => $isLabSession,
                'notes' => 'Auto-generated on '.now()->format('Y-m-d H:i:s'),
            ]);

            return [
                'success' => true,
                'session' => [
                    'id' => $timetable->id,
                    'batch' => $batch->name,
                    'subject' => $subject->name,
                    'faculty' => $faculty->name,
                    'classroom' => $classroom->name,
                    'date' => $date->format('Y-m-d'),
                    'time' => $timeSlot->start_time.'-'.$timeSlot->end_time,
                    'type' => $isLabSession ? 'Lab' : 'Theory',
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create timetable session', [
                'error' => $e->getMessage(),
                'batch_id' => $batch->id,
                'subject_id' => $subject->id,
                'date' => $date->format('Y-m-d'),
                'time_slot_id' => $timeSlot->id,
            ]);

            return [
                'success' => false,
                'conflict' => false,
                'message' => 'Database error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check for scheduling conflicts
     */
    private function checkForConflicts($batch, $date, $timeSlot, $options)
    {
        $conflicts = [];

        // Check if batch already has a class at this time
        $batchClash = Timetable::where('batch_id', $batch->id)
            ->where('schedule_date', $date)
            ->where('time_slot_id', $timeSlot->id)
            ->exists();

        if ($batchClash) {
            $conflicts[] = 'Batch already has a class scheduled';
        }

        return $conflicts;
    }

    /**
     * Find available faculty for the subject
     */
    private function findAvailableFaculty($subject, $date, $timeSlot)
    {
        // Get all staff members
        $faculties = User::role('staff')->get();

        // Filter based on subject expertise if relationship exists
        if (method_exists($subject, 'qualifiedFaculties')) {
            $qualifiedFaculties = $subject->qualifiedFaculties;
            if ($qualifiedFaculties->isNotEmpty()) {
                $faculties = $qualifiedFaculties;
            }
        }

        // Find faculty not busy at this time
        foreach ($faculties as $faculty) {
            $isBusy = Timetable::where('user_id', $faculty->id)
                ->where('schedule_date', $date)
                ->where('time_slot_id', $timeSlot->id)
                ->exists();

            if (! $isBusy) {
                return $faculty;
            }
        }

        // If no qualified faculty available, return any available faculty
        return $faculties->first();
    }

    /**
     * Find available classroom
     */
    private function findAvailableClassroom($date, $timeSlot, $isLabSession = false)
    {
        $query = Classroom::query();

        // Filter by lab requirement
        if ($isLabSession) {
            $query->where('is_lab', true);
        }

        $classrooms = $query->get();

        // Find classroom not occupied at this time
        foreach ($classrooms as $classroom) {
            $isOccupied = Timetable::where('classroom_id', $classroom->id)
                ->where('schedule_date', $date)
                ->where('time_slot_id', $timeSlot->id)
                ->exists();

            if (! $isOccupied) {
                return $classroom;
            }
        }

        return null;
    }

    /**
     * Get working days configuration
     */
    private function getWorkingDaysConfig()
    {
        $workingDays = Setting::where('key', 'working_days')->first();

        if ($workingDays && $workingDays->value) {
            $configured = json_decode($workingDays->value, true);

            return is_array($configured) ? $configured : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        }

        return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']; // Default 5 days
    }

    /**
     * Get week dates based on working days
     */
    private function getWeekDates($weekStart, $workingDays)
    {
        $dates = collect();
        $dayMap = [
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
        ];

        foreach ($workingDays as $day) {
            if (isset($dayMap[$day])) {
                $date = $weekStart->copy()->startOfWeek()->addDays($dayMap[$day] - 1);
                $dates->push($date);
            }
        }

        return $dates->sort();
    }

    /**
     * Check if subject should be generated based on options
     */
    private function shouldGenerateSubject($subject, $options)
    {
        $isLab = $subject->requires_lab ?? false;

        if ($isLab && ! ($options['generate_labs'] ?? true)) {
            return false;
        }

        if (! $isLab && ! ($options['generate_theory'] ?? true)) {
            return false;
        }

        return true;
    }

    /**
     * Clear existing timetable entries for the week
     */
    public function clearWeeklyTimetable($courseIds, $academicYear, $weekStart)
    {
        try {
            $batchIds = Batch::whereIn('course_id', $courseIds)->pluck('id');
            $weekEnd = $weekStart->copy()->endOfWeek();

            $deleted = Timetable::whereIn('batch_id', $batchIds)
                ->where('academic_year_id', $academicYear->id)
                ->whereBetween('schedule_date', [$weekStart, $weekEnd])
                ->delete();

            Log::info('Cleared existing timetable entries', [
                'deleted_count' => $deleted,
                'week_start' => $weekStart->format('Y-m-d'),
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to clear timetable entries', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Validate timetable generation requirements
     */
    public function validateGenerationRequirements($courseIds)
    {
        $issues = [];

        // Check if courses exist
        $courses = Course::whereIn('id', $courseIds)->get();
        if ($courses->count() !== count($courseIds)) {
            $issues[] = 'Some courses not found';
        }

        // Check if courses have batches
        foreach ($courses as $course) {
            $batchCount = $course->batches()->count();
            if ($batchCount === 0) {
                $issues[] = "Course '{$course->name}' has no batches";
            }
        }

        // Check if there are subjects
        $subjectCount = Subject::count();
        if ($subjectCount === 0) {
            $issues[] = 'No subjects configured';
        }

        // Check if there are faculty members
        $facultyCount = User::role('staff')->count();
        if ($facultyCount === 0) {
            $issues[] = 'No faculty members configured';
        }

        // Check if there are classrooms
        $classroomCount = Classroom::count();
        if ($classroomCount === 0) {
            $issues[] = 'No classrooms configured';
        }

        // Check if there are time slots
        $timeSlotCount = TimeSlot::count();
        if ($timeSlotCount === 0) {
            $issues[] = 'No time slots configured';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }
}
