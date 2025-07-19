<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService; // ADD THIS IMPORT
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $notificationService; // ADD THIS PROPERTY

    // ADD CONSTRUCTOR FOR DEPENDENCY INJECTION
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Store a new attendance record from a biometric device.
     */
    public function store(Request $request)
    {
        // 1. Authenticate the Device using the API Key
        $apiKey = Setting::where('key', 'biometric_api_key')->value('value');

        if (!$apiKey || $request->header('X-API-KEY') !== $apiKey) {
            // 🔔 SECURITY ALERT: Unauthorized biometric access
            $this->notificationService->sendSystemAlert(
                'Unauthorized biometric device access attempt',
                'high',
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'provided_key' => $request->header('X-API-KEY'),
                    'timestamp' => now()->toISOString(),
                ]
            );

            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // 2. Validate the incoming data
        $validated = $request->validate([
            'enrollment_number' => 'required|string|exists:students,enrollment_number',
            'timestamp' => 'sometimes|integer',
            'device_id' => 'sometimes|string' // Optional device identifier
        ]);

        // 3. Find the student
        $student = Student::where('enrollment_number', $validated['enrollment_number'])->first();
        if (!$student || !$student->batch_id) {
            return response()->json(['status' => 'error', 'message' => 'Student not found or not assigned to a batch.'], 404);
        }

        // 4. Determine the date and time of the scan
        $scanTime = isset($validated['timestamp']) ? Carbon::createFromTimestamp($validated['timestamp']) : Carbon::now();
        $scanDate = $scanTime->format('Y-m-d');
        $currentTime = $scanTime->format('H:i:s');

        // 5. Find the correct class slot
        $timetableEntry = Timetable::where('batch_id', $student->batch_id)
            ->where('schedule_date', $scanDate)
            ->whereHas('timeSlot', function ($query) use ($currentTime) {
                $query->where('start_time', '<=', $currentTime)->where('end_time', '>=', $currentTime);
            })
            ->first();

        if (!$timetableEntry) {
            // 🔔 NOTIFICATION: No active class found
            $this->notificationService->send([
                'title' => 'Biometric Scan Outside Class Hours',
                'message' => "{$student->name} scanned biometric device but no active class found",
                'type' => 'warning',
                'category' => 'attendance',
                'priority' => 'low',
                'roles' => ['super-admin', 'college-admin'],
                'data' => [
                    'student_id' => $student->id,
                    'scan_time' => $scanTime->format('Y-m-d H:i:s'),
                    'device_id' => $validated['device_id'] ?? 'unknown',
                ]
            ]);

            return response()->json(['status' => 'error', 'message' => 'No active class found for this student at this time.'], 404);
        }

        // 6. Record the attendance
        $attendance = Attendance::updateOrCreate(
            [
                'student_id'      => $student->id,
                'attendance_date' => $scanDate,
            ],
            [
                'batch_id'        => $student->batch_id,
                'faculty_id'      => $timetableEntry->faculty_id,
                'status'          => 'present',
            ]
        );

        // 7. Send notifications based on the result
        if ($attendance->wasRecentlyCreated) {
            // 🔔 NOTIFICATION: Successful biometric attendance
            $this->notificationService->send([
                'title' => 'Biometric Attendance Recorded',
                'message' => "Attendance marked for {$student->name} via biometric device",
                'type' => 'success',
                'category' => 'attendance',
                'priority' => 'low',
                'roles' => ['super-admin', 'college-admin'],
                'data' => [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'scan_time' => $scanTime->format('Y-m-d H:i:s'),
                    'device_id' => $validated['device_id'] ?? 'unknown',
                    'class_subject' => $timetableEntry->subject->name ?? 'Unknown',
                ]
            ]);
        } else {
            // 🔔 NOTIFICATION: Duplicate scan attempt
            $this->notificationService->send([
                'title' => 'Duplicate Biometric Scan',
                'message' => "{$student->name} attempted duplicate biometric scan",
                'type' => 'info',
                'category' => 'attendance',
                'priority' => 'low',
                'roles' => ['super-admin'],
                'data' => [
                    'student_id' => $student->id,
                    'original_scan_time' => $attendance->updated_at->format('Y-m-d H:i:s'),
                    'duplicate_scan_time' => $scanTime->format('Y-m-d H:i:s'),
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance recorded for ' . $student->name,
            'data' => [
                'student_name' => $student->name,
                'attendance_date' => $scanDate,
                'scan_time' => $scanTime->format('H:i:s'),
                'was_new_record' => $attendance->wasRecentlyCreated,
            ]
        ], 200);
    }
}