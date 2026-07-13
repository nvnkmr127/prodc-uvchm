<?php

namespace App\Services;

use App\Events\RealTimeNotification;
use App\Models\Setting;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $defaultSounds = [
        'success' => '/sounds/success.mp3',
        'error' => '/sounds/error.mp3',
        'warning' => '/sounds/warning.mp3',
        'info' => '/sounds/notification.mp3',
        'urgent' => '/sounds/urgent-alert.mp3',
        'payment' => '/sounds/payment-success.mp3',
    ];

    /**
     * Send a notification
     */
    public function send(array $data)
    {
        try {
            // Validate and clean data
            $cleanData = $this->validateData($data);

            // Create notification record
            $notification = SystemNotification::create($cleanData);

            // Broadcast notification if enabled
            if (config('app.broadcasting_enabled', false)) {
                $users = isset($data['users']) ? $data['users'] : [];
                $roles = isset($data['roles']) ? $data['roles'] : [];
                event(new RealTimeNotification($notification, $users, $roles));
            }

            Log::info('Notification sent successfully', [
                'notification_id' => $notification->id,
                'title' => $notification->title,
                'category' => $notification->category,
            ]);

            return $notification;

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * ✅ FIXED: Send academic notification compatible with older PHP
     */
    public function sendAcademicNotification($type, $data)
    {
        // ✅ FIX: Extract values safely before using in strings
        $studentName = isset($data['student_name']) ? $data['student_name'] : 'Unknown Student';
        $studentId = isset($data['student_id']) ? $data['student_id'] : 1;
        $courseName = isset($data['course_name']) ? $data['course_name'] : 'Unknown Course';
        $attendancePercentage = isset($data['attendance_percentage']) ? $data['attendance_percentage'] : '0';
        $batchName = isset($data['batch_name']) ? $data['batch_name'] : 'Unknown Batch';

        $configs = [
            'new_admission' => [
                'title' => 'New Admission',
                'message' => "New student admission: {$studentName} in {$courseName}",
                'type' => 'info',
                'priority' => 'normal',
                'play_sound' => false,
                'action_url' => $this->generateUrl('students.show', $studentId),
                'action_text' => 'View Student',
                'roles' => ['super-admin', 'college-admin'],
            ],
            'low_attendance' => [
                'title' => 'Low Attendance Alert',
                'message' => "{$studentName} has {$attendancePercentage}% attendance",
                'type' => 'warning',
                'priority' => 'high',
                'play_sound' => true,
                'requires_action' => true,
                'action_url' => $this->generateUrl('students.show', $studentId),
                'action_text' => 'View Student',
                'roles' => ['super-admin', 'college-admin', 'staff'],
            ],
            'batch_assigned' => [
                'title' => 'Student Batch Assignment',
                'message' => "Student {$studentName} has been assigned to batch {$batchName}",
                'type' => 'info',
                'priority' => 'normal',
                'play_sound' => false,
                'action_url' => $this->generateUrl('students.show', $studentId),
                'action_text' => 'View Student',
                'roles' => ['super-admin', 'college-admin'],
            ],
        ];

        // ✅ FIX: Use isset instead of null coalescing operator
        if (isset($configs[$type])) {
            $baseConfig = $configs[$type];
            $configType = isset($configs[$type]['type']) ? $configs[$type]['type'] : 'info';
        } else {
            $baseConfig = [
                'title' => 'Academic Notification',
                'message' => 'Academic update for '.$studentName,
                'type' => 'info',
                'priority' => 'normal',
                'play_sound' => false,
                'roles' => ['super-admin', 'college-admin'],
            ];
            $configType = 'info';
        }

        $config = array_merge($baseConfig, [
            'category' => 'academic',
            'data' => $data,
            'sound_file' => $this->getSoundFile($configType),
        ]);

        return $this->send($config);
    }

    /**
     * Send system alert
     */
    public function sendSystemAlert($message, $priority = 'normal', $data = [])
    {
        return $this->send([
            'title' => 'System Alert',
            'message' => $message,
            'type' => $priority === 'urgent' ? 'error' : 'warning',
            'category' => 'system',
            'priority' => $priority,
            'play_sound' => in_array($priority, ['urgent', 'high']),
            'sound_file' => $this->getSoundFile($priority === 'urgent' ? 'error' : 'warning'),
            'data' => $data,
            'roles' => ['super-admin'],
            'requires_action' => $priority === 'urgent',
        ]);
    }

    /**
     * Fixed method to handle attendance notifications safely
     */
    public function processAttendanceNotification($student, $attendanceData = [])
    {
        try {
            // Safely get attendance percentage with fallback
            $attendancePercentage = 0;

            if (isset($attendanceData['attendance_percentage'])) {
                $attendancePercentage = $attendanceData['attendance_percentage'];
            } elseif (isset($student->attendance_percentage)) {
                $attendancePercentage = $student->attendance_percentage;
            } else {
                $attendancePercentage = $this->calculateAttendancePercentage($student);
            }

            if (empty($attendancePercentage)) {
                $attendancePercentage = 0;
            }

            // Get threshold from settings
            $threshold = $this->getSetting('low_attendance_threshold', 75);

            // Only send notification if attendance is below threshold
            if ($attendancePercentage < $threshold) {
                $this->sendLowAttendanceNotification($student, $attendancePercentage, $threshold);
            }

            return true;

        } catch (\Exception $e) {
            $studentId = isset($student->id) ? $student->id : 'unknown';
            Log::error('Attendance notification failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Safe setting getter
     */
    private function getSetting($key, $default = null)
    {
        try {
            if (function_exists('setting')) {
                return setting($key, $default);
            }

            if (class_exists(Setting::class)) {
                $setting = Setting::where('key', $key)->first();

                return $setting ? $setting->value : $default;
            }

            return $default;
        } catch (\Exception $e) {
            Log::warning('Failed to get setting', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    /**
     * Calculate attendance percentage for a student
     */
    private function calculateAttendancePercentage($student)
    {
        try {
            if (! $student) {
                return 0;
            }

            // Try different methods to get attendance data
            if (method_exists($student, 'attendances')) {
                $totalClasses = $student->attendances()->count();
                $presentClasses = $student->attendances()->where('status', 'present')->count();
            } elseif (method_exists($student, 'attendanceRecords')) {
                $totalClasses = $student->attendanceRecords()->count();
                $presentClasses = $student->attendanceRecords()->where('status', 'present')->count();
            } else {
                return 0;
            }

            if ($totalClasses === 0) {
                return 0;
            }

            return round(($presentClasses / $totalClasses) * 100, 2);

        } catch (\Exception $e) {
            $studentId = isset($student->id) ? $student->id : 'unknown';
            Log::warning('Failed to calculate attendance percentage', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}

/**
 * Helper function to get setting value safely.
 */
if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        try {
            if (class_exists(Setting::class)) {
                $setting = Setting::where('key', $key)->first();

                return $setting ? $setting->value : $default;
            }

            return $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
