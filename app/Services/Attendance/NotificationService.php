<?php
// File: app/Services/Attendance/NotificationService.php
// ✅ Create this file if it doesn't exist

namespace App\Services\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    protected $notificationService;

    public function __construct(\App\Services\NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
                'status' => $attendance->status
            ]);

            $student = $attendance->student;

            if ($student) {
                // Determine message based on status
                $message = "Your attendance for " . now()->format('Y-m-d') . " has been marked as " . $attendance->status;
                $type = $attendance->status === 'present' ? 'success' : 'warning';

                $this->notificationService->send([
                    'title' => 'Attendance Update',
                    'message' => $message,
                    'type' => $type,
                    'category' => 'attendance',
                    'users' => [$student->user_id],
                    'data' => [
                        'attendance_id' => $attendance->id,
                        'status' => $attendance->status
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send attendance notification', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send low attendance alert
     */
    public function sendLowAttendanceAlert(Student $student, array $stats): void
    {
        try {
            $percentage = $stats['attendance_percentage'] ?? 0;

            Log::info('Low attendance alert triggered', [
                'student_id' => $student->id,
                'percentage' => $percentage
            ]);

            // Use the generic service's dedicated method if available, or fallback to generic send
            if (method_exists($this->notificationService, 'processAttendanceNotification')) {
                $this->notificationService->processAttendanceNotification($student, ['attendance_percentage' => $percentage]);
            } else {
                $this->notificationService->sendAcademicNotification('low_attendance', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'attendance_percentage' => $percentage,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send low attendance alert', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send absent alert
     */
    public function sendAbsentAlert(Attendance $attendance): void
    {
        try {
            Log::info('Absent alert triggered', [
                'attendance_id' => $attendance->id,
                'student_id' => $attendance->student_id
            ]);

            $student = $attendance->student;
            if ($student) {
                $this->notificationService->send([
                    'title' => 'Absent Alert',
                    'message' => "You have been marked absent for " . now()->format('Y-m-d'),
                    'type' => 'error',
                    'priority' => 'high',
                    'category' => 'attendance',
                    'users' => [$student->user_id],
                    'data' => [
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send absent alert', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * Send bulk attendance notification
     */
    public function sendBulkAttendanceNotification(array $attendances): void
    {
        try {
            Log::info('Bulk attendance notification triggered', [
                'count' => count($attendances)
            ]);

            foreach ($attendances as $attendanceData) {
                // Assuming attendanceData contains necessary info or we'd fetch it
                // For now, this is a placeholder implementation as array structure isn't fully defined
                // In a real scenario, we would loop and call sendAttendanceNotification or batch send
                if ($attendanceData instanceof Attendance) {
                    $this->sendAttendanceNotification($attendanceData);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send bulk attendance notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send faculty attendance reminder
     */
    public function sendFacultyAttendanceReminder(User $faculty): void
    {
        try {
            Log::info('Faculty attendance reminder sent', [
                'faculty_id' => $faculty->id
            ]);

            $this->notificationService->send([
                'title' => 'Attendance Reminder',
                'message' => 'Please mark today\'s attendance.',
                'type' => 'info',
                'category' => 'attendance',
                'users' => [$faculty->id],
                'action_url' => route('faculty.attendance.index'),
                'action_text' => 'Mark Attendance'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send faculty attendance reminder', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage()
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
                'status' => $attendance->status
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
                'error' => $e->getMessage()
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
                'message' => 'Attendance summary for ' . $date->format('Y-m-d'),
                'type' => 'info',
                'category' => 'system',
                'roles' => ['admin', 'principal', 'college-admin'] // Assuming role based auth
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send daily summary', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send weekly report
     */
    public function sendWeeklyReport($weekEndDate): void
    {
        try {
            $this->notificationService->send([
                'title' => 'Weekly Attendance Report',
                'message' => 'Weekly attendance report generated for week ending ' . $weekEndDate->format('Y-m-d'),
                'type' => 'info',
                'category' => 'system',
                'roles' => ['admin', 'principal', 'college-admin']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send weekly report', ['error' => $e->getMessage()]);
        }
    }
}