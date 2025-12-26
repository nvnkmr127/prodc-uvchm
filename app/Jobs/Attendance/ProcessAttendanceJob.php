<?php

namespace App\Jobs\Attendance;

use App\Models\Attendance\BiometricLog;
use App\Models\Attendance\AttendanceCache;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\NotificationService;
use App\Events\Attendance\AttendanceEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry delays in seconds

    protected $data;
    protected $type;

    public function __construct(array $data, string $type = 'single')
    {
        $this->data = $data;
        $this->type = $type;
        $this->onQueue('attendance');
    }

    /**
     * Execute the job
     */
    public function handle(
        AttendanceService $attendanceService,
        NotificationService $notificationService
    ): void {
        try {
            switch ($this->type) {
                case 'single':
                    $this->processSingleAttendance($attendanceService, $notificationService);
                    break;
                case 'bulk':
                    $this->processBulkAttendance($attendanceService, $notificationService);
                    break;
                case 'biometric_log':
                    $this->processBiometricLog($attendanceService, $notificationService);
                    break;
                case 'auto_mark_absent':
                    $this->processAutoMarkAbsent($attendanceService);
                    break;
                case 'update_cache':
                    $this->updateAttendanceCache();
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown processing type: {$this->type}");
            }

        } catch (\Exception $e) {
            Log::error('Attendance processing job failed', [
                'type' => $this->type,
                'data' => $this->data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Attendance processing job permanently failed', [
            'type' => $this->type,
            'data' => $this->data,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts
        ]);

        // Mark biometric log as failed if applicable
        if ($this->type === 'biometric_log' && isset($this->data['log_id'])) {
            $log = BiometricLog::find($this->data['log_id']);
            if ($log) {
                $log->markAsFailed($exception->getMessage());
            }
        }
    }

    /**
     * Process single attendance record
     */
    private function processSingleAttendance(
        AttendanceService $attendanceService,
        NotificationService $notificationService
    ): void {
        $attendance = $attendanceService->markAttendance($this->data);
        
        // Update cache for this student
        AttendanceCache::updateForStudent($attendance->student_id);
        
        // Fire event
        event(new AttendanceEvent('processed', $attendance));
        
        Log::info('Single attendance processed', [
            'attendance_id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'status' => $attendance->status
        ]);
    }

    /**
     * Process bulk attendance records
     */
    private function processBulkAttendance(
        AttendanceService $attendanceService,
        NotificationService $notificationService
    ): void {
        $results = $attendanceService->markBulkAttendance($this->data['attendance_records']);
        
        // Update cache for all affected students
        $studentIds = collect($results['successful'])->pluck('student_id')->unique();
        foreach ($studentIds as $studentId) {
            AttendanceCache::updateForStudent($studentId);
        }
        
        // Fire bulk event
        event(new AttendanceEvent('bulk_processed', null, $results));
        
        Log::info('Bulk attendance processed', [
            'total_records' => count($this->data['attendance_records']),
            'successful' => count($results['successful']),
            'failed' => count($results['failed'])
        ]);
    }

    /**
     * Process biometric log entry
     */
    private function processBiometricLog(
        AttendanceService $attendanceService,
        NotificationService $notificationService
    ): void {
        $log = BiometricLog::findOrFail($this->data['log_id']);
        
        if ($log->status !== 'pending') {
            Log::info('Biometric log already processed', ['log_id' => $log->id]);
            return;
        }

        try {
            // Convert biometric log to attendance data
            $attendanceData = $this->convertBiometricLogToAttendance($log);
            
            if (!$attendanceData) {
                $log->markAsFailed('Unable to convert to attendance data');
                return;
            }

            $attendance = $attendanceService->markAttendance($attendanceData);
            $log->markAsProcessed($attendance->id);
            
            // Update cache
            AttendanceCache::updateForStudent($attendance->student_id);
            
            Log::info('Biometric log processed', [
                'log_id' => $log->id,
                'attendance_id' => $attendance->id,
                'student_id' => $attendance->student_id
            ]);

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Process auto-mark absent students
     */
    private function processAutoMarkAbsent(AttendanceService $attendanceService): void
    {
        $date = isset($this->data['date']) ? Carbon::parse($this->data['date']) : Carbon::today();
        $results = $attendanceService->autoMarkAbsentStudents($date);
        
        Log::info('Auto-mark absent completed', [
            'date' => $date->format('Y-m-d'),
            'marked' => $results['marked'],
            'errors' => count($results['errors'])
        ]);
    }

    /**
     * Update attendance cache for students
     */
    private function updateAttendanceCache(): void
    {
        $studentIds = $this->data['student_ids'] ?? [];
        
        if (empty($studentIds)) {
            // Update all students if no specific IDs provided
            \App\Models\Student::chunk(100, function ($students) {
                foreach ($students as $student) {
                    AttendanceCache::updateForStudent($student->id);
                }
            });
        } else {
            foreach ($studentIds as $studentId) {
                AttendanceCache::updateForStudent($studentId);
            }
        }
        
        Log::info('Attendance cache updated', [
            'student_count' => empty($studentIds) ? 'all' : count($studentIds)
        ]);
    }

    /**
     * Convert biometric log to attendance data
     */
    private function convertBiometricLogToAttendance(BiometricLog $log): ?array
    {
        if (!$log->student_id) {
            return null;
        }

        $student = $log->student;
        if (!$student || !$student->batch_id) {
            return null;
        }

        $attendanceDate = $log->scan_datetime->format('Y-m-d');
        
        // Determine status based on scan time and grace period
        $status = $this->determineStatusFromScanTime($log->scan_datetime);
        
        return [
            'student_id' => $log->student_id,
            'batch_id' => $student->batch_id,
            'attendance_date' => $attendanceDate,
            'status' => $status,
            'biometric_log_id' => $log->id,
            'device_id' => $log->device_id,
            'notes' => 'Marked via biometric device',
            'marked_at' => $log->scan_datetime
        ];
    }

    /**
     * Determine attendance status from scan time
     */
    private function determineStatusFromScanTime(Carbon $scanTime): string
    {
        $gracePeriod = config('attendance.rules.grace_period_minutes', 10);
        $lateThreshold = config('attendance.rules.late_threshold_minutes', 15);
        
        // For now, simple logic - can be enhanced with timetable integration
        $currentTime = $scanTime->format('H:i');
        
        // Assume class starts at 9:00 AM for basic logic
        $classStartTime = Carbon::parse('09:00');
        
        if ($scanTime->lte($classStartTime->copy()->addMinutes($gracePeriod))) {
            return 'present';
        } elseif ($scanTime->lte($classStartTime->copy()->addMinutes($lateThreshold))) {
            return 'late';
        } else {
            return 'present'; // Still mark as present if they came, even if very late
        }
    }
}