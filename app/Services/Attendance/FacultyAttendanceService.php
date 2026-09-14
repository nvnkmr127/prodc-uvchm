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
        $scanType = strtolower($direction); // 'in' or 'out'

        // 1. Fetch faculty settings
        $collegeStartTime = Setting::where('key', 'attendance_faculty_college_start_time')->value('value') ?? '09:00:00';
        $presentCutoff = Setting::where('key', 'attendance_faculty_present_cutoff_time')->value('value') ?? '10:30:00';
        $lateCutoff = Setting::where('key', 'attendance_faculty_late_cutoff_time')->value('value') ?? '11:00:00';
        $collegeEndTime = Setting::where('key', 'attendance_college_end_time')->value('value') ?? '17:00:00';

        // Apply custom timings if the faculty has them
        if ($faculty->custom_check_in_time) {
            $customStartObj = Carbon::parse($faculty->custom_check_in_time);
            $globalStartObj = Carbon::parse($collegeStartTime);
            
            $presentOffset = Carbon::parse($presentCutoff)->diffInMinutes($globalStartObj);
            $lateOffset = Carbon::parse($lateCutoff)->diffInMinutes($globalStartObj);
            
            $collegeStartTime = $customStartObj->format('H:i:s');
            $presentCutoff = $customStartObj->copy()->addMinutes($presentOffset)->format('H:i:s');
            $lateCutoff = $customStartObj->copy()->addMinutes($lateOffset)->format('H:i:s');
        }

        if ($faculty->custom_check_out_time) {
            $collegeEndTime = Carbon::parse($faculty->custom_check_out_time)->format('H:i:s');
        }

        // Calculate expected half day threshold dynamically based on their specific shift length
        $expectedHours = Carbon::parse($collegeEndTime)->diffInMinutes(Carbon::parse($collegeStartTime)) / 60;
        $halfDayThreshold = max(2.0, $expectedHours / 2);

        // 2. Find existing attendance record
        $attendance = FacultyAttendance::where('faculty_id', $faculty->id)
            ->where('attendance_date', $attendanceDate)
            ->first();

        $settings = [
            'college_start_time' => $collegeStartTime,
            'present_cutoff_time' => $presentCutoff,
            'late_cutoff_time' => $lateCutoff,
            'college_end_time' => $collegeEndTime,
        ];

        if (!$attendance) {
            // First punch of the day
            if ($scanType === 'out') {
                // Edge case: First punch is an explicit OUT punch. We create the record with check_out_time only.
                $attendance = FacultyAttendance::create([
                    'faculty_id' => $faculty->id,
                    'attendance_date' => $attendanceDate,
                    'check_out_time' => $punchTime,
                    'status' => 'absent', // Absent because no check-in
                    'notes' => 'Checked out via ETimeOffice (Missing Check-in)',
                    'device_id' => $deviceId,
                    'biometric_log_id' => $biometricLogId,
                    'marked_at' => $punchDateTime,
                    'marked_by' => auth()->id() ?? $faculty->id,
                ]);
            } else {
                // Normal Check-in
                $statusData = $this->determineStatus($punchTime, $settings);

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
            }
        } else {
            // Record exists, let's update it based on scan logic and direction
            $existingCheckIn = $attendance->check_in_time ? Carbon::parse($attendanceDate . ' ' . $attendance->check_in_time) : null;
            $existingCheckOut = $attendance->check_out_time ? Carbon::parse($attendanceDate . ' ' . $attendance->check_out_time) : null;

            // Double punch protection (5 minutes = 300 seconds)
            if ($existingCheckIn && abs($punchDateTime->diffInSeconds($existingCheckIn)) < 300) {
                return $attendance;
            }
            if ($existingCheckOut && abs($punchDateTime->diffInSeconds($existingCheckOut)) < 300) {
                return $attendance;
            }

            // Determine if this should be treated as an IN or OUT punch
            $treatAsIn = false;
            $treatAsOut = false;

            if ($scanType === 'in') {
                $treatAsIn = true;
            } elseif ($scanType === 'out') {
                $treatAsOut = true;
            } else {
                // Auto-detect if direction is missing or invalid
                if (!$existingCheckIn) {
                    $treatAsIn = true;
                } else {
                    // It has a check-in. To prevent duplicate morning punches from becoming checkouts,
                    // we require the punch to be either past the shift's midpoint or at least 60 minutes later.
                    $shiftStart = Carbon::parse($attendanceDate . ' ' . $collegeStartTime);
                    $shiftEnd = Carbon::parse($attendanceDate . ' ' . $collegeEndTime);
                    $midpoint = $shiftStart->copy()->addMinutes($shiftStart->diffInMinutes($shiftEnd) / 2);
                    
                    $minutesSinceCheckIn = $punchDateTime->diffInMinutes($existingCheckIn, true);
                    
                    if ($punchDateTime->gt($existingCheckIn)) {
                        if ($punchDateTime->gte($midpoint) || $minutesSinceCheckIn > 60) {
                            $treatAsOut = true;
                        } else {
                            $treatAsIn = true; // Treat as IN (which will be ignored since it's later than existing check-in)
                        }
                    } else {
                        $treatAsIn = true; // Earlier punch, so update check-in
                    }
                }
            }

            if ($treatAsIn) {
                // Only update if it's an earlier punch OR if we didn't have one
                if (!$existingCheckIn || $punchDateTime->lt($existingCheckIn)) {
                    $statusData = $this->determineStatus($punchTime, $settings);

                    $lateMinutes = 0;
                    if ($statusData['status'] === 'late') {
                        $start = Carbon::parse($attendanceDate . ' ' . $collegeStartTime);
                        $lateMinutes = $punchDateTime->gt($start) ? $punchDateTime->diffInMinutes($start, true) : 0;
                    }

                    $newNotes = $attendance->notes;
                    if (!str_contains((string)$newNotes, 'Check-in updated')) {
                        $newNotes = $newNotes ? $newNotes . ' | Check-in updated' : 'Check-in recorded';
                    }

                    $updateData = [
                        'check_in_time' => $punchTime,
                        'status' => $statusData['status'],
                        'late_minutes' => $lateMinutes > 0 ? $lateMinutes : null,
                        'notes' => $newNotes,
                    ];
                    
                    if ($existingCheckOut) {
                        if ($punchDateTime->lt($existingCheckOut)) {
                            $workingHours = round($punchDateTime->diffInMinutes($existingCheckOut, true) / 60, 2);
                            $updateData['working_hours'] = $workingHours;
                            
                            if ($workingHours < $halfDayThreshold) {
                                $updateData['status'] = 'half_day';
                            } elseif ($updateData['status'] === 'half_day' && $workingHours >= $expectedHours) {
                                $updateData['status'] = 'late';
                            }
                        } else {
                            // Invalid range (IN after OUT) - clear the invalid checkout
                            $updateData['check_out_time'] = null;
                            $updateData['working_hours'] = null;
                        }
                    }

                    $attendance->update($updateData);
                }
            }
            
            if ($treatAsOut) {
                $workingHours = $attendance->working_hours;
                $finalStatus = $attendance->status;

                // Calculate working hours if we have a check-in
                if ($attendance->check_in_time) {
                    // Re-parse just in case it was updated above
                    $currentCheckIn = Carbon::parse($attendanceDate . ' ' . $attendance->check_in_time);
                    
                    if ($punchDateTime->gt($currentCheckIn)) {
                        $workingHours = round($currentCheckIn->diffInMinutes($punchDateTime, true) / 60, 2);

                        $originalCheckInStatus = $this->determineStatus($attendance->check_in_time, $settings);

                        if ($originalCheckInStatus['status'] === 'absent') {
                            $finalStatus = 'absent';
                        } elseif ($workingHours < $halfDayThreshold) {
                            $finalStatus = 'half_day';
                        } else {
                            $finalStatus = $originalCheckInStatus['status'];
                            
                            // Upgrade half_day to late if they worked full expected hours
                            if ($finalStatus === 'half_day' && $workingHours >= $expectedHours) {
                                $finalStatus = 'late';
                            }
                        }
                    }
                }

                $newNotes = $attendance->notes;
                if (!str_contains((string)$newNotes, 'Checked out via ETimeOffice')) {
                    $newNotes = $newNotes ? $newNotes . ' | Checked out via ETimeOffice' : 'Checked out via ETimeOffice';
                }

                $attendance->update([
                    'check_out_time' => $punchTime,
                    'working_hours' => $workingHours,
                    'status' => $finalStatus,
                    'notes' => $newNotes,
                ]);
            }
        }

        // If this punch brings unique logins >= 2, clean up any previous holiday status on this date
        $this->cleanupHolidayIfWorkingDay($attendanceDate, $settings);

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
            // For now, let's make it present but late if they check in past cutoff,
            // to avoid them being marked absent when they are actually working.
            // Wait, the user wants "proper logics". Usually > late cutoff is half_day.
            return [
                'status' => 'half_day',
                'reason' => 'Checked in after late cutoff',
            ];
        }
    }

    /**
     * Count unique faculty logins for a given date.
     * A login is counted if the faculty has a recorded check_in_time, check_out_time,
     * or active punch status ('present', 'late', 'half_day').
     */
    public function getUniqueLoginsCount(Carbon|string $date): int
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $activeFacultyIds = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['staff', 'faculty']);
        })->where('status', 'active')->pluck('id');

        if ($activeFacultyIds->isEmpty()) {
            return 0;
        }

        return FacultyAttendance::whereIn('faculty_id', $activeFacultyIds)
            ->whereDate('attendance_date', $dateStr)
            ->where(function ($query) {
                $query->whereNotNull('check_in_time')
                    ->orWhereNotNull('check_out_time')
                    ->orWhereIn('status', ['present', 'late', 'half_day']);
            })
            ->distinct('faculty_id')
            ->count('faculty_id');
    }

    /**
     * Check if a given date is a working day (at least 2 unique faculty logins).
     */
    public function isWorkingDay(Carbon|string $date): bool
    {
        return $this->getUniqueLoginsCount($date) >= 2;
    }

    /**
     * Check Working Day vs Holiday condition for a date:
     * - Count total unique faculty logins.
     * - If < 2 logins, consider the day a "Holiday" and explicitly mark status as "Holiday" in attendance logs/database.
     * - If >= 2 logins, consider the day a "Working Day".
     *
     * @return bool True if holiday (< 2 logins), false if working day (>= 2 logins).
     */
    public function checkAndMarkHoliday(Carbon|string $date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $activeFaculties = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['staff', 'faculty']);
        })->where('status', 'active')->get();

        if ($activeFaculties->isEmpty()) {
            return true;
        }

        $records = FacultyAttendance::whereIn('faculty_id', $activeFaculties->pluck('id'))
            ->whereDate('attendance_date', $dateStr)
            ->get()
            ->keyBy('faculty_id');

        $uniqueLogins = $records->filter(function ($r) {
            return !empty($r->check_in_time)
                || !empty($r->check_out_time)
                || in_array(strtolower(trim($r->status ?? '')), ['present', 'late', 'half_day']);
        })->count();

        if ($uniqueLogins < 2) {
            // Explicitly mark status as "Holiday" in attendance logs/database for all active faculty
            foreach ($activeFaculties as $faculty) {
                $record = $records->get($faculty->id);
                if ($record) {
                    if ($record->status !== 'holiday') {
                        $newNotes = $record->notes
                            ? $record->notes . ' | Marked as Holiday (< 2 faculty logins)'
                            : 'Holiday (fewer than 2 faculty logins)';
                        $record->update([
                            'status' => 'holiday',
                            'notes' => $newNotes,
                        ]);
                    }
                } else {
                    FacultyAttendance::create([
                        'faculty_id' => $faculty->id,
                        'attendance_date' => $dateStr,
                        'status' => 'holiday',
                        'notes' => 'Holiday (fewer than 2 faculty logins)',
                        'marked_at' => now(),
                        'marked_by' => auth()->id() ?? $faculty->id,
                    ]);
                }
            }
            return true;
        }

        return false;
    }

    /**
     * If attendance records on a date have >= 2 logins, ensure any temporary holiday status is resolved.
     */
    public function cleanupHolidayIfWorkingDay(string $dateStr, array $settings): void
    {
        $uniqueLogins = FacultyAttendance::whereDate('attendance_date', $dateStr)
            ->where(function ($query) {
                $query->whereNotNull('check_in_time')
                    ->orWhereNotNull('check_out_time')
                    ->orWhereIn('status', ['present', 'late', 'half_day']);
            })
            ->distinct('faculty_id')
            ->count('faculty_id');

        if ($uniqueLogins >= 2) {
            // Restore status for any punched faculty that were marked holiday
            $punchedHolidayRecords = FacultyAttendance::whereDate('attendance_date', $dateStr)
                ->where('status', 'holiday')
                ->whereNotNull('check_in_time')
                ->get();

            foreach ($punchedHolidayRecords as $att) {
                $statusData = $this->determineStatus($att->check_in_time, $settings);
                $att->update(['status' => $statusData['status']]);
            }

            // Remove placeholder holiday records that had no punches
            FacultyAttendance::whereDate('attendance_date', $dateStr)
                ->where('status', 'holiday')
                ->whereNull('check_in_time')
                ->whereNull('check_out_time')
                ->delete();
        }
    }
}
