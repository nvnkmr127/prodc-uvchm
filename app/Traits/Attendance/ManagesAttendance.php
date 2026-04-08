<?php

namespace App\Traits\Attendance;

use App\Events\Attendance\AttendanceEvent;
use App\Models\Attendance\Attendance;
use App\Models\Batch;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ManagesAttendance
{
    /**
     * Mark attendance for a single student
     */
    public function markAttendance(array $data): Attendance
    {
        DB::beginTransaction();
        try {
            // Check for existing attendance
            $existing = $this->findExistingAttendance(
                $data['student_id'],
                $data['attendance_date'],
                $data['batch_id'] ?? null
            );

            if ($existing) {
                $attendance = $this->updateExistingAttendance($existing, $data);
                $action = 'updated';
            } else {
                $attendance = $this->createNewAttendance($data);
                $action = 'created';
            }

            DB::commit();

            // Fire event
            event(new AttendanceEvent($action, $attendance));

            Log::info("Attendance {$action}", [
                'attendance_id' => $attendance->id,
                'student_id' => $attendance->student_id,
                'status' => $attendance->status,
            ]);

            return $attendance;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark attendance', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark attendance for multiple students in bulk
     */
    public function bulkMarkAttendance(array $records): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'skipped' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($records as $record) {
                try {
                    // Validate individual record
                    if (! $this->validateSingleRecord($record)) {
                        $results['skipped'][] = [
                            'record' => $record,
                            'reason' => 'Invalid data',
                        ];

                        continue;
                    }

                    // Check for duplicates
                    $existing = $this->findExistingAttendance(
                        $record['student_id'],
                        $record['attendance_date'],
                        $record['batch_id'] ?? null
                    );

                    if ($existing && ! ($record['allow_update'] ?? false)) {
                        $results['skipped'][] = [
                            'record' => $record,
                            'reason' => 'Already exists',
                        ];

                        continue;
                    }

                    // Mark attendance
                    if ($existing) {
                        $attendance = $this->updateExistingAttendance($existing, $record);
                    } else {
                        $attendance = $this->createNewAttendance($record);
                    }

                    $results['successful'][] = $attendance;

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'record' => $record,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            // Fire bulk event
            event(new AttendanceEvent('bulk_processed', null, $results));

            Log::info('Bulk attendance processed', [
                'total_records' => count($records),
                'successful' => count($results['successful']),
                'failed' => count($results['failed']),
                'skipped' => count($results['skipped']),
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk attendance marking failed', [
                'error' => $e->getMessage(),
                'records_count' => count($records),
            ]);
            throw $e;
        }
    }

    /**
     * Update existing attendance record
     */
    public function updateAttendance(int $attendanceId, array $data): Attendance
    {
        DB::beginTransaction();
        try {
            $attendance = Attendance::findOrFail($attendanceId);

            // Store original values for comparison
            $originalStatus = $attendance->status;
            $originalData = $attendance->toArray();

            // Update attendance
            $attendance->update([
                'status' => $data['status'] ?? $attendance->status,
                'notes' => $data['notes'] ?? $attendance->notes,
                'late_minutes' => $data['late_minutes'] ?? $attendance->late_minutes,
                'marked_by' => auth()->id(),
                'marked_at' => now(),
            ]);

            // Log the change if status changed
            if ($originalStatus !== $attendance->status) {
                Log::info('Attendance status changed', [
                    'attendance_id' => $attendance->id,
                    'student_id' => $attendance->student_id,
                    'old_status' => $originalStatus,
                    'new_status' => $attendance->status,
                    'changed_by' => auth()->id(),
                ]);
            }

            DB::commit();

            // Fire event
            event(new AttendanceEvent('updated', $attendance, [
                'previous_data' => $originalData,
                'changes' => $attendance->getChanges(),
            ]));

            return $attendance->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update attendance', [
                'attendance_id' => $attendanceId,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendance(int $attendanceId): bool
    {
        DB::beginTransaction();
        try {
            $attendance = Attendance::findOrFail($attendanceId);
            $attendanceData = $attendance->toArray();

            $attendance->delete();

            DB::commit();

            // Fire event
            event(new AttendanceEvent('deleted', null, [
                'deleted_attendance' => $attendanceData,
                'deleted_by' => auth()->id(),
            ]));

            Log::info('Attendance deleted', [
                'attendance_id' => $attendanceId,
                'student_id' => $attendanceData['student_id'],
                'deleted_by' => auth()->id(),
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete attendance', [
                'attendance_id' => $attendanceId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk delete attendance records
     */
    public function bulkDeleteAttendance(array $attendanceIds): array
    {
        $results = [
            'deleted' => [],
            'failed' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($attendanceIds as $attendanceId) {
                try {
                    $attendance = Attendance::findOrFail($attendanceId);
                    $attendanceData = $attendance->toArray();

                    $attendance->delete();
                    $results['deleted'][] = $attendanceData;

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'attendance_id' => $attendanceId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            // Fire bulk delete event
            event(new AttendanceEvent('bulk_deleted', null, $results));

            Log::info('Bulk attendance deletion completed', [
                'total_ids' => count($attendanceIds),
                'deleted' => count($results['deleted']),
                'failed' => count($results['failed']),
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk attendance deletion failed', [
                'error' => $e->getMessage(),
                'ids_count' => count($attendanceIds),
            ]);
            throw $e;
        }
    }

    /**
     * Transfer attendance from one batch to another
     */
    public function transferAttendanceToBatch(int $fromBatchId, int $toBatchId, array $filters = []): array
    {
        DB::beginTransaction();
        try {
            $query = Attendance::where('batch_id', $fromBatchId);

            // Apply date filters if provided
            if (isset($filters['date_from'])) {
                $query->where('attendance_date', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('attendance_date', '<=', $filters['date_to']);
            }

            $attendances = $query->get();
            $transferredCount = 0;

            foreach ($attendances as $attendance) {
                $attendance->update(['batch_id' => $toBatchId]);
                $transferredCount++;
            }

            DB::commit();

            // Fire transfer event
            event(new AttendanceEvent('batch_transferred', null, [
                'from_batch_id' => $fromBatchId,
                'to_batch_id' => $toBatchId,
                'transferred_count' => $transferredCount,
                'filters' => $filters,
            ]));

            Log::info('Attendance transferred between batches', [
                'from_batch_id' => $fromBatchId,
                'to_batch_id' => $toBatchId,
                'transferred_count' => $transferredCount,
            ]);

            return [
                'success' => true,
                'transferred_count' => $transferredCount,
                'from_batch_id' => $fromBatchId,
                'to_batch_id' => $toBatchId,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendance transfer failed', [
                'from_batch_id' => $fromBatchId,
                'to_batch_id' => $toBatchId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Private helper methods
     */
    private function findExistingAttendance(int $studentId, string $date, ?int $batchId = null): ?Attendance
    {
        $query = Attendance::where('student_id', $studentId)
            ->where('attendance_date', $date);

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        return $query->first();
    }

    private function createNewAttendance(array $data): Attendance
    {
        return Attendance::create([
            'student_id' => $data['student_id'],
            'batch_id' => $data['batch_id'],
            'subject_id' => $data['subject_id'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? auth()->id(),
            'attendance_date' => $data['attendance_date'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'late_minutes' => $data['late_minutes'] ?? null,
            'marked_by' => auth()->id(),
            'marked_at' => now(),
            'biometric_log_id' => $data['biometric_log_id'] ?? null,
            'location' => $data['location'] ?? null,
            'device_id' => $data['device_id'] ?? null,
        ]);
    }

    private function updateExistingAttendance(Attendance $attendance, array $data): Attendance
    {
        $attendance->update([
            'status' => $data['status'] ?? $attendance->status,
            'notes' => $data['notes'] ?? $attendance->notes,
            'late_minutes' => $data['late_minutes'] ?? $attendance->late_minutes,
            'marked_by' => auth()->id(),
            'marked_at' => now(),
            'biometric_log_id' => $data['biometric_log_id'] ?? $attendance->biometric_log_id,
            'location' => $data['location'] ?? $attendance->location,
            'device_id' => $data['device_id'] ?? $attendance->device_id,
        ]);

        return $attendance;
    }

    private function validateSingleRecord(array $record): bool
    {
        $required = ['student_id', 'attendance_date', 'status'];

        foreach ($required as $field) {
            if (! isset($record[$field]) || empty($record[$field])) {
                return false;
            }
        }

        // Validate status
        $validStatuses = ['present', 'absent', 'late', 'excused'];
        if (! in_array($record['status'], $validStatuses)) {
            return false;
        }

        // Validate date format
        try {
            Carbon::parse($record['attendance_date']);
        } catch (\Exception $e) {
            return false;
        }

        // Validate student exists
        if (! Student::find($record['student_id'])) {
            return false;
        }

        return true;
    }
}
