<?php

namespace App\Traits\Attendance;

use App\Models\Student;
use App\Models\Batch;
use App\Models\Subject;
use App\Models\User;
use App\Models\Attendance\Attendance;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

trait ValidatesData
{
    /**
     * Validate attendance data for single record
     */
    public function validateAttendanceData(array $data): array
    {
        $rules = [
            'student_id' => 'required|integer|exists:students,id',
            'batch_id' => 'required|integer|exists:batches,id',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'faculty_id' => 'nullable|integer|exists:users,id',
            'attendance_date' => 'required|date|before_or_equal:today',
            'status' => 'required|in:present,absent,late,excused',
            'notes' => 'nullable|string|max:500',
            'late_minutes' => 'nullable|integer|min:0|max:1440',
            'biometric_log_id' => 'nullable|integer|exists:biometric_logs,id',
            'location' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:100'
        ];

        $messages = [
            'student_id.required' => 'Student is required',
            'student_id.exists' => 'Selected student does not exist',
            'batch_id.required' => 'Batch is required',
            'batch_id.exists' => 'Selected batch does not exist',
            'subject_id.exists' => 'Selected subject does not exist',
            'faculty_id.exists' => 'Selected faculty does not exist',
            'attendance_date.required' => 'Attendance date is required',
            'attendance_date.date' => 'Invalid attendance date format',
            'attendance_date.before_or_equal' => 'Cannot mark attendance for future dates',
            'status.required' => 'Attendance status is required',
            'status.in' => 'Invalid attendance status',
            'late_minutes.integer' => 'Late minutes must be a number',
            'late_minutes.min' => 'Late minutes cannot be negative',
            'late_minutes.max' => 'Late minutes cannot exceed 24 hours'
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // Additional business logic validations
        $this->validateBusinessRules($validated);

        return $validated;
    }

    /**
     * Validate bulk attendance data
     */
    public function validateBulkData(array $records): array
    {
        $validated = [];
        $errors = [];

        foreach ($records as $index => $record) {
            try {
                $validatedRecord = $this->validateAttendanceData($record);
                $validated[] = $validatedRecord;
            } catch (ValidationException $e) {
                $errors["record_{$index}"] = $e->errors();
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'bulk_validation' => 'Some records failed validation',
                'errors' => $errors
            ]);
        }

        // Check for duplicates within the batch
        $this->validateNoDuplicatesInBulk($validated);

        return $validated;
    }

    /**
     * Validate import data
     */
    public function validateImportData(array $data): array
    {
        $rules = [
            '*.enrollment_number' => 'required|string|exists:students,enrollment_number',
            '*.attendance_date' => 'required|date',
            '*.status' => 'required|in:present,absent,late,excused',
            '*.notes' => 'nullable|string|max:500',
            '*.late_minutes' => 'nullable|integer|min:0|max:1440',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // Convert enrollment numbers to student IDs and add batch IDs
        foreach ($validated as &$record) {
            $student = Student::where('enrollment_number', $record['enrollment_number'])->first();
            $record['student_id'] = $student->id;
            $record['batch_id'] = $student->batch_id;
            unset($record['enrollment_number']);
        }

        return $validated;
    }

    /**
     * Validate attendance permissions for user
     */
    public function validateAttendancePermissions(array $data, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        // Super admin can mark any attendance
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // College admin can mark any attendance within their institution
        if ($user->hasRole('college-admin')) {
            return true;
        }

        // Faculty can only mark attendance for their assigned subjects/batches
        if ($user->hasRole('faculty')) {
            return $this->validateFacultyPermissions($user, $data);
        }

        // Staff with attendance permission
        if ($user->hasPermissionTo('manage attendance')) {
            return true;
        }

        return false;
    }

    /**
     * Validate attendance date constraints
     */
    public function validateAttendanceDateConstraints(string $date, array $options = []): array
    {
        $errors = [];
        $attendanceDate = Carbon::parse($date);
        
        // Check if date is in the future
        if ($attendanceDate->isFuture() && !($options['allow_future'] ?? false)) {
            $errors[] = 'Cannot mark attendance for future dates';
        }

        // Check if date is too far in the past
        $maxPastDays = config('attendance.security.max_past_days', 30);
        if ($attendanceDate->lt(now()->subDays($maxPastDays)) && !($options['admin_override'] ?? false)) {
            $errors[] = "Cannot mark attendance for dates older than {$maxPastDays} days";
        }

        // Check if it's a weekend (if weekend attendance is disabled)
        if (!config('attendance.weekend_working_enabled', false) && $attendanceDate->isWeekend()) {
            $errors[] = 'Weekend attendance is not enabled';
        }

        // Check if it's a holiday
        if ($this->isHoliday($attendanceDate) && !($options['allow_holidays'] ?? false)) {
            $errors[] = 'Cannot mark attendance on holidays';
        }

        return $errors;
    }

    /**
     * Validate attendance status transitions
     */
    public function validateStatusTransition(?string $currentStatus, string $newStatus, array $options = []): array
    {
        $errors = [];

        // If no current status, any status is allowed for new records
        if (!$currentStatus) {
            return $errors;
        }

        // Define allowed transitions
        $allowedTransitions = [
            'present' => ['late', 'absent', 'excused'],
            'absent' => ['present', 'late', 'excused'],
            'late' => ['present', 'absent', 'excused'],
            'excused' => ['present', 'absent', 'late']
        ];

        // Check if transition is allowed
        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            // Allow admin override
            if (!($options['admin_override'] ?? false)) {
                $errors[] = "Cannot change status from '{$currentStatus}' to '{$newStatus}'";
            }
        }

        // Check time constraints for status changes
        if ($options['attendance_record'] ?? null) {
            $attendance = $options['attendance_record'];
            $timeSinceMarked = now()->diffInHours($attendance->marked_at);
            $timeLimit = config('attendance.security.edit_time_limit_hours', 24);

            if ($timeSinceMarked > $timeLimit && !($options['admin_override'] ?? false)) {
                $errors[] = "Cannot modify attendance after {$timeLimit} hours";
            }
        }

        return $errors;
    }

    /**
     * Validate batch and student compatibility
     */
    public function validateBatchStudentCompatibility(int $studentId, int $batchId): array
    {
        $errors = [];

        $student = Student::find($studentId);
        if (!$student) {
            $errors[] = 'Student not found';
            return $errors;
        }

        $batch = Batch::find($batchId);
        if (!$batch) {
            $errors[] = 'Batch not found';
            return $errors;
        }

        // Check if student belongs to the batch
        if ($student->batch_id !== $batchId) {
            $errors[] = 'Student does not belong to the specified batch';
        }

        // Check if student is active
        if (!$student->is_active) {
            $errors[] = 'Cannot mark attendance for inactive student';
        }

        // Check if batch is active
        if (!$batch->is_active) {
            $errors[] = 'Cannot mark attendance for inactive batch';
        }

        return $errors;
    }

    /**
     * Validate late arrival data
     */
    public function validateLateArrival(array $data): array
    {
        $errors = [];

        if ($data['status'] === 'late') {
            // Late minutes are required for late status
            if (!isset($data['late_minutes']) || $data['late_minutes'] <= 0) {
                $errors[] = 'Late minutes are required when status is late';
            }

            // Check if late minutes are reasonable
            $maxLateMinutes = config('attendance.max_late_minutes', 120);
            if (($data['late_minutes'] ?? 0) > $maxLateMinutes) {
                $errors[] = "Late minutes cannot exceed {$maxLateMinutes} minutes";
            }
        } else {
            // Clear late minutes for non-late status
            if (isset($data['late_minutes']) && $data['late_minutes'] > 0) {
                $errors[] = 'Late minutes should only be set when status is late';
            }
        }

        return $errors;
    }

    /**
     * Validate attendance quota (if applicable)
     */
    public function validateAttendanceQuota(int $studentId, string $date): array
    {
        $errors = [];

        // Check daily attendance quota (prevent multiple entries per day)
        $existingCount = Attendance::where('student_id', $studentId)
            ->where('attendance_date', $date)
            ->count();

        $maxDailyEntries = config('attendance.max_daily_entries', 1);
        if ($existingCount >= $maxDailyEntries) {
            $errors[] = 'Maximum daily attendance entries reached for this student';
        }

        return $errors;
    }

    /**
     * Private helper methods
     */
    private function validateBusinessRules(array $data): void
    {
        $errors = [];

        // Validate date constraints
        $dateErrors = $this->validateAttendanceDateConstraints($data['attendance_date']);
        $errors = array_merge($errors, $dateErrors);

        // Validate batch-student compatibility
        $compatibilityErrors = $this->validateBatchStudentCompatibility(
            $data['student_id'], 
            $data['batch_id']
        );
        $errors = array_merge($errors, $compatibilityErrors);

        // Validate late arrival data
        $lateErrors = $this->validateLateArrival($data);
        $errors = array_merge($errors, $lateErrors);

        // Validate attendance quota
        $quotaErrors = $this->validateAttendanceQuota(
            $data['student_id'],
            $data['attendance_date']
        );
        $errors = array_merge($errors, $quotaErrors);

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'business_rules' => $errors
            ]);
        }
    }

    private function validateFacultyPermissions(User $faculty, array $data): bool
    {
        // Check if faculty is assigned to the batch
        if (isset($data['batch_id'])) {
            $batchAssigned = $faculty->assignedBatches()
                ->where('batch_id', $data['batch_id'])
                ->exists();

            if ($batchAssigned) {
                return true;
            }
        }

        // Check if faculty teaches the subject
        if (isset($data['subject_id'])) {
            $subjectAssigned = $faculty->subjects()
                ->where('subject_id', $data['subject_id'])
                ->exists();

            if ($subjectAssigned) {
                return true;
            }
        }

        // Check if faculty is the class teacher
        if (isset($data['batch_id'])) {
            $batch = Batch::find($data['batch_id']);
            if ($batch && $batch->class_teacher_id === $faculty->id) {
                return true;
            }
        }

        return false;
    }

    private function validateNoDuplicatesInBulk(array $validated): void
    {
        $combinations = [];

        foreach ($validated as $data) {
            $key = $data['student_id'] . '|' . $data['attendance_date'];
            
            if (isset($data['subject_id'])) {
                $key .= '|' . $data['subject_id'];
            }

            if (in_array($key, $combinations)) {
                throw ValidationException::withMessages([
                    'duplicate_records' => 'Duplicate attendance records found in bulk data'
                ]);
            }

            $combinations[] = $key;

            // Also check database for existing records
            $existingQuery = Attendance::where('student_id', $data['student_id'])
                ->where('attendance_date', $data['attendance_date']);

            if (isset($data['subject_id'])) {
                $existingQuery->where('subject_id', $data['subject_id']);
            }

            if ($existingQuery->exists()) {
                throw ValidationException::withMessages([
                    'existing_records' => "Attendance already exists for student ID {$data['student_id']} on {$data['attendance_date']}"
                ]);
            }
        }
    }

    private function isHoliday(Carbon $date): bool
    {
        // This would integrate with a holiday management system
        // For now, return false - can be enhanced to check against holiday calendar
        
        // Basic implementation - check if it's a major holiday
        $holidays = config('attendance.holidays', []);
        $dateString = $date->format('m-d'); // Month-day format
        
        return in_array($dateString, $holidays);
    }
}