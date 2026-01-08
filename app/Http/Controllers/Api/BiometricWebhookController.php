<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BiometricWebhookController extends Controller
{
    /**
     * Handle ETimeOffice API data - Main webhook endpoint
     */
    public function handleETimeOffice(Request $request)
    {
        try {
            Log::info('ETimeOffice webhook received', $request->all());

            // Validate ETimeOffice request format
            $validated = $request->validate([
                'Empcode' => 'required|string',
                'PunchDate' => 'required|string',
                'LogDateTime' => 'nullable|string',
                'Direction' => 'nullable|string|in:IN,OUT',
                'DeviceId' => 'nullable|string'
            ]);

            // Extract employee code and datetime
            $biometricCode = $validated['Empcode'];
            $punchDateTime = $validated['LogDateTime'] ?? $validated['PunchDate'];
            $direction = $validated['Direction'] ?? 'IN';
            $deviceId = $validated['DeviceId'] ?? 'etimeoffice-device';

            // Find student by biometric code
            $student = $this->findStudentByBiometricCode($biometricCode);

            if (!$student) {
                Log::warning('ETimeOffice: Student not found', [
                    'biometric_code' => $biometricCode,
                    'punch_datetime' => $punchDateTime
                ]);

                return response()->json([
                    'Result' => 'Error',
                    'Status' => 'Student not found',
                    'Message' => 'No student found with biometric code: ' . $biometricCode
                ], 404);
            }

            // Parse datetime
            try {
                $carbonDate = Carbon::createFromFormat('d/m/Y_H:i', $punchDateTime);
            } catch (\Exception $e) {
                // Try alternative format
                try {
                    $carbonDate = Carbon::parse($punchDateTime);
                } catch (\Exception $e) {
                    Log::error('ETimeOffice: Invalid datetime format', [
                        'punch_datetime' => $punchDateTime,
                        'error' => $e->getMessage()
                    ]);

                    return response()->json([
                        'Result' => 'Error',
                        'Status' => 'Invalid datetime format',
                        'Message' => 'Cannot parse datetime: ' . $punchDateTime
                    ], 400);
                }
            }

            // Process attendance
            $attendanceData = $this->processETimeOfficeAttendance(
                $student,
                $carbonDate,
                $direction,
                $deviceId,
                $request->all()
            );

            Log::info('ETimeOffice attendance processed successfully', [
                'student_name' => $student->name,
                'attendance_id' => $attendanceData['attendance_id'],
                'status' => $attendanceData['status'],
                'action' => $attendanceData['action']
            ]);

            return response()->json([
                'Result' => 'OK',
                'Status' => 'Success',
                'Message' => "Attendance {$attendanceData['action']} for {$student->name}",
                'Data' => $attendanceData
            ], 200);

        } catch (\Exception $e) {
            Log::error('ETimeOffice webhook error', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'Result' => 'Error',
                'Status' => 'Processing failed',
                'Message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Legacy endpoint for backward compatibility - redirects to ETimeOffice
     * @deprecated Use handleETimeOffice instead
     */
    public function handleBiometric(Request $request)
    {
        Log::warning('Deprecated biometric endpoint called, redirecting to ETimeOffice handler');
        return $this->handleETimeOffice($request);
    }

    /**
     * Handle realtime data - DEPRECATED, use ETimeOffice only
     * @deprecated
     */
    public function handleRealtime(Request $request)
    {
        Log::error('Realtime biometric endpoint called but has been disabled. Only ETimeOffice is supported.');

        return response()->json([
            'Result' => 'Error',
            'Status' => 'Endpoint disabled',
            'Message' => 'Realtime biometric integration has been disabled. Please use ETimeOffice integration only.'
        ], 410); // 410 Gone
    }

    /**
     * Find student by biometric code with enrollment fallback
     */
    private function findStudentByBiometricCode($biometricCode)
    {
        $startTime = microtime(true);

        // First try direct biometric code lookup
        $student = Student::where('biometric_employee_code', $biometricCode)->first();

        if ($student) {
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Student found by biometric code', [
                'biometric_code' => $biometricCode,
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'query_time_ms' => $queryTime
            ]);
            return $student;
        }

        // Fallback to enrollment number patterns
        Log::info('Biometric code not found, trying enrollment patterns', [
            'biometric_code' => $biometricCode
        ]);

        $student = $this->findStudentByEnrollmentNumber($biometricCode);

        if ($student) {
            // Auto-populate biometric code for future fast lookups
            if (empty($student->biometric_employee_code)) {
                try {
                    $student->update(['biometric_employee_code' => $biometricCode]);
                    Log::info('Auto-populated biometric code', [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'enrollment_number' => $student->enrollment_number,
                        'biometric_code' => $biometricCode
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to auto-populate biometric code', [
                        'student_id' => $student->id,
                        'biometric_code' => $biometricCode,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $student;
    }

    /**
     * Find student by enrollment number with various patterns
     */
    private function findStudentByEnrollmentNumber($code)
    {
        $patterns = [
            $code,
            'UVCHM-' . $code,
            'UV-' . $code,
            'ENR-' . $code,
            preg_replace('/[^0-9]/', '', $code) // Numbers only
        ];

        foreach ($patterns as $pattern) {
            $student = Student::where('enrollment_number', 'LIKE', "%{$pattern}%")->first();
            if ($student) {
                Log::info('Student found by enrollment pattern', [
                    'pattern' => $pattern,
                    'enrollment_number' => $student->enrollment_number,
                    'student_name' => $student->name
                ]);
                return $student;
            }
        }

        return null;
    }

    /**
     * Process ETimeOffice attendance data
     */
    private function processETimeOfficeAttendance($student, $carbonDate, $direction, $deviceId, $rawData)
    {
        $attendanceDate = $carbonDate->toDateString();
        $attendanceTime = $carbonDate->toTimeString();

        // Determine attendance status based on time rules
        $status = $this->determineAttendanceStatus($carbonDate, $student);

        // Find or create attendance record
        $attendance = Attendance::firstOrCreate(
            [
                'student_id' => $student->id,
                'attendance_date' => $attendanceDate,
            ],
            [
                'status' => $status['status'],
                'check_in_time' => $attendanceTime,
                'notes' => 'ETimeOffice - ' . $status['reason'],
                'created_by' => null // System generated
            ]
        );

        $action = $attendance->wasRecentlyCreated ? 'created' : 'updated';

        // Update if it's a check-out or later time
        if (!$attendance->wasRecentlyCreated) {
            if ($direction === 'OUT' || $carbonDate->gt(Carbon::parse($attendanceDate . ' ' . $attendance->check_in_time))) {
                $attendance->update([
                    'check_out_time' => $attendanceTime,
                    'notes' => $attendance->notes . ' | Updated via ETimeOffice'
                ]);
            }
        }

        return [
            'attendance_id' => $attendance->id,
            'student_name' => $student->name,
            'enrollment_number' => $student->enrollment_number,
            'biometric_code' => $student->biometric_employee_code,
            'status' => $status['status'],
            'action' => $action,
            'direction' => $direction,
            'timestamp' => $carbonDate->toISOString()
        ];
    }

    /**
     * Determine attendance status based on college rules
     */
    private function determineAttendanceStatus($punchTime, $student)
    {
        // Get college timing settings
        $collegeStartTime = Setting::where('key', 'college_start_time')->value('value') ?? '09:00';
        $lateThreshold = Setting::where('key', 'late_threshold_minutes')->value('value') ?? 15;

        $collegeStart = Carbon::parse($punchTime->toDateString() . ' ' . $collegeStartTime);
        $lateLimit = $collegeStart->copy()->addMinutes($lateThreshold);

        if ($punchTime->lte($collegeStart)) {
            return [
                'status' => 'present',
                'reason' => 'On time'
            ];
        } elseif ($punchTime->lte($lateLimit)) {
            return [
                'status' => 'late',
                'reason' => 'Late arrival'
            ];
        } else {
            return [
                'status' => 'absent',
                'reason' => 'Very late - marked absent'
            ];
        }
    }
}