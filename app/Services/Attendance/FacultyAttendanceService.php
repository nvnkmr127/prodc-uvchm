<?php

namespace App\Services\Attendance;

use App\Models\Attendance\FacultyAttendance;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FacultyAttendanceService
{
    /**
     * Record a punch for a faculty member.
     */
    public function recordPunch(User $faculty, Carbon $punchDateTime, string $direction, string $deviceId, ?int $biometricLogId = null): FacultyAttendance
    {
        $attendanceDate = $punchDateTime->toDateString();
        $punchTime = $punchDateTime->toTimeString();

        // 1. Fetch faculty settings
        $collegeStartTime = Setting::where('key', 'attendance_faculty_college_start_time')->value('value') ?? '09:00:00';
        $presentCutoff = Setting::where('key', 'attendance_faculty_present_cutoff_time')->value('value') ?? '10:30:00';
        $lateCutoff = Setting::where('key', 'attendance_faculty_late_cutoff_time')->value('value') ?? '11:00:00';
        $collegeEndTime = Setting::where('key', 'attendance_college_end_time')->value('value') ?? '17:00:00';

        // 2. Find existing attendance record
        $attendance = FacultyAttendance::where('faculty_id', $faculty->id)
            ->where('attendance_date', $attendanceDate)
            ->first();

        if (!$attendance) {
            // Check-in punch (first punch of the day)
            $statusData = $this->determineStatus($punchTime, [
                'college_start_time' => $collegeStartTime,
                'present_cutoff_time' => $presentCutoff,
                'late_cutoff_time' => $lateCutoff,
                'college_end_time' => $collegeEndTime,
            ]);

            $lateMinutes = 0;
            if ($statusData['status'] === 'late') {
                $start = Carbon::parse($attendanceDate . ' ' . $collegeStartTime);
                $lateMinutes = $punchDateTime->gt($start) ? $punchDateTime->diffInMinutes($start, true) : 0;
            }

            $attendance = FacultyAttendance::create([
                'faculty_id' => $faculty->id,
                'attendance_date' => $attendanceDate,
                'check_in_time' => $punchTime,
                'status' => $statusData['status'],
                'late_minutes' => $lateMinutes > 0 ? $lateMinutes : null,
                'notes' => 'Checked in via ETimeOffice: ' . $statusData['reason'],
                'device_id' => $deviceId,
                'biometric_log_id' => $biometricLogId,
                'marked_at' => $punchDateTime,
                'marked_by' => auth()->id() ?? $faculty->id,
            ]);

            Log::info("Created faculty check-in attendance record", [
                'faculty_name' => $faculty->name,
                'date' => $attendanceDate,
                'time' => $punchTime,
                'status' => $statusData['status']
            ]);
        } else {
            // Check if this punch is earlier than the check-in time
            $existingCheckIn = Carbon::parse($attendanceDate . ' ' . $attendance->check_in_time);
            
            if ($punchDateTime->lt($existingCheckIn)) {
                // Update check-in time if earlier punch found
                $statusData = $this->determineStatus($punchTime, [
                    'college_start_time' => $collegeStartTime,
                    'present_cutoff_time' => $presentCutoff,
                    'late_cutoff_time' => $lateCutoff,
                    'college_end_time' => $collegeEndTime,
                ]);

                $lateMinutes = 0;
                if ($statusData['status'] === 'late') {
                    $start = Carbon::parse($attendanceDate . ' ' . $collegeStartTime);
                    $lateMinutes = $punchDateTime->gt($start) ? $punchDateTime->diffInMinutes($start, true) : 0;
                }

                $attendance->update([
                    'check_in_time' => $punchTime,
                    'status' => $statusData['status'],
                    'late_minutes' => $lateMinutes > 0 ? $lateMinutes : null,
                    'notes' => $attendance->notes . ' | Check-in updated to earlier punch',
                ]);

                Log::info("Updated faculty check-in to earlier punch time", [
                    'faculty_name' => $faculty->name,
                    'date' => $attendanceDate,
                    'time' => $punchTime
                ]);
            } else {
                // This is a check-out punch (later than check-in)
                $checkInTimeStr = $attendance->check_in_time;
                $checkInDateTime = Carbon::parse($attendanceDate . ' ' . $checkInTimeStr);
                $workingHours = round($checkInDateTime->diffInMinutes($punchDateTime, true) / 60, 2);

                // Determine final status
                // If the check-in was already past the late cutoff, the status remains absent.
                $originalCheckInStatus = $this->determineStatus($checkInTimeStr, [
                    'college_start_time' => $collegeStartTime,
                    'present_cutoff_time' => $presentCutoff,
                    'late_cutoff_time' => $lateCutoff,
                    'college_end_time' => $collegeEndTime,
                ]);

                if ($originalCheckInStatus['status'] === 'absent') {
                    $finalStatus = 'absent';
                } elseif ($workingHours < 4.0) {
                    $finalStatus = 'half_day';
                } else {
                    $finalStatus = $originalCheckInStatus['status']; // present or late
                }

                $attendance->update([
                    'check_out_time' => $punchTime,
                    'working_hours' => $workingHours,
                    'status' => $finalStatus,
                    'notes' => $attendance->notes . ' | Checked out via ETimeOffice',
                ]);

                Log::info("Updated faculty check-out attendance record", [
                    'faculty_name' => $faculty->name,
                    'date' => $attendanceDate,
                    'check_in' => $checkInTimeStr,
                    'check_out' => $punchTime,
                    'working_hours' => $workingHours,
                    'status' => $finalStatus
                ]);
            }
        }

        return $attendance;
    }

    /**
     * Helper to determine status based on timing.
     */
    private function determineStatus(string $checkTime, array $settings): array
    {
        if ($checkTime < $settings['college_start_time']) {
            return [
                'status' => 'present',
                'reason' => 'Early arrival',
            ];
        } elseif ($checkTime <= $settings['present_cutoff_time']) {
            return [
                'status' => 'present',
                'reason' => 'Checked in within present window',
            ];
        } elseif ($checkTime <= $settings['late_cutoff_time']) {
            return [
                'status' => 'late',
                'reason' => 'Checked in during late window',
            ];
        } else {
            return [
                'status' => 'absent',
                'reason' => 'Checked in after cutoff time',
            ];
        }
    }
}
