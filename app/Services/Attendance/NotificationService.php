<?php

// File: app/Services/Attendance/NotificationService.php
// ✅ Create this file if it doesn't exist

namespace App\Services\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    protected $notificationService;

    public function __construct(\App\Services\NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function send(array $data)
    {
        return $this->notificationService->send($data);
    }

    /**
     * Send attendance notification
     */
    public function sendAttendanceNotification(Attendance $attendance): void
    {
        try {
            // Log the notification
            Log::info('Attendance notification triggered', [
                'attendance_id' => $attendance->id,
                'student_id' => $attendance->student_id,
                'status' => $attendance->status,
            ]);

            $student = $attendance->student;

            if ($student) {
                // Determine message based on status
                $message = 'Your attendance for '.now()->format('Y-m-d').' has been marked as '.$attendance->status;
                $type = $attendance->status === 'present' ? 'success' : 'warning';

                $this->notificationService->send([
                    'title' => 'Attendance Update',
                    'message' => $message,
                    'type' => $type,
                    'category' => 'attendance',
                    'users' => [$student->user_id],
                    'data' => [
                        'attendance_id' => $attendance->id,
                        'status' => $attendance->status,
                    ],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send attendance notification', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send parent notification
     */
    public function sendParentNotification(Student $student, Attendance $attendance): void
    {
        try {
            Log::info('Parent notification sent', [
                'student_id' => $student->id,
                'attendance_id' => $attendance->id,
                'status' => $attendance->status,
            ]);

            // In a real app, we would look up parent contact info.
            // For now, logging and potential generic notification if parent user exists
            $message = "Student {$student->name} was marked {$attendance->status} today.";

            // Placeholder: Assume logic to find parent user ID exists or just skip if not linked
            // $parentUserId = $student->parent_user_id;

            // For now just logging that we would notify parent
            Log::info("Would notify parent of {$student->name} about status: {$attendance->status}");

        } catch (\Exception $e) {
            Log::error('Failed to send parent notification', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send daily summary
     */
    public function sendDailySummary($date): void
    {
        // Implementation for daily summary (e.g. to admins)
        try {
            $this->notificationService->send([
                'title' => 'Daily Attendance Summary',
                'message' => 'Attendance summary for '.$date->format('Y-m-d'),
                'type' => 'info',
                'category' => 'system',
                'roles' => ['admin', 'principal', 'college-admin'], // Assuming role based auth
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send daily summary', ['error' => $e->getMessage()]);
        }
    }
}
