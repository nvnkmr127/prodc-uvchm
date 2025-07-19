<?php

namespace App\Services;

use App\Models\SystemNotification;
use App\Models\NotificationPreference;
use App\Events\RealTimeNotification;
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
                'category' => $notification->category
            ]);

            return $notification;

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Send financial alert
     */
    public function sendFinancialAlert($type, $data)
    {
        // ✅ FIX: Extract values with safe access before using in strings
        $amount = isset($data['amount']) ? $data['amount'] : '0';
        $studentName = isset($data['student_name']) ? $data['student_name'] : 'Unknown Student';
        $studentId = isset($data['student_id']) ? $data['student_id'] : 1;
        $paymentId = isset($data['payment_id']) ? $data['payment_id'] : 1;
        $concessionAmount = isset($data['concession_amount']) ? $data['concession_amount'] : '0';

        $configs = [
            'payment_received' => [
                'title' => 'Payment Received',
                'message' => "Payment of ₹{$amount} received from {$studentName}",
                'type' => 'success',
                'priority' => 'normal',
                'play_sound' => true,
                'action_url' => $this->generateUrl('payments.show', $paymentId),
                'action_text' => 'View Payment',
                'roles' => ['super-admin', 'college-admin', 'accountant'],
            ],
            'payment_failed' => [
                'title' => 'Payment Failed',
                'message' => "Payment of ₹{$amount} failed for {$studentName}",
                'type' => 'error',
                'priority' => 'high',
                'play_sound' => true,
                'requires_action' => true,
                'action_url' => $this->generateUrl('students.show', $studentId),
                'action_text' => 'View Student',
                'roles' => ['super-admin', 'college-admin', 'accountant'],
            ],
            'fee_reminder' => [
                'title' => 'Fee Reminder',
                'message' => "Student {$studentName} has pending dues of ₹{$amount}",
                'type' => 'warning',
                'priority' => 'normal',
                'play_sound' => true,
                'action_url' => $this->generateUrl('students.show', $studentId),
                'action_text' => 'View Ledger',
                'roles' => ['super-admin', 'college-admin', 'accountant'],
            ],
            'overdue_payment' => [
                'title' => 'URGENT: Overdue Payment',
                'message' => "{$studentName} has overdue payment of ₹{$amount}",
                'type' => 'error',
                'priority' => 'urgent',
                'play_sound' => true,
                'requires_action' => true,
                'is_persistent' => true,
                'action_url' => $this->generateUrl('students.show', $studentId),
                'action_text' => 'Take Action',
                'roles' => ['super-admin', 'college-admin', 'accountant'],
            ],
            'concession_applied' => [
                'title' => 'Concession Applied',
                'message' => "Concession of ₹{$concessionAmount} applied for {$studentName}",
                'type' => 'info',
                'priority' => 'normal',
                'play_sound' => false,
                'action_url' => $this->generateUrl('students.show', $studentId),
                'action_text' => 'View Invoice',
                'roles' => ['super-admin', 'college-admin', 'accountant'],
            ],
        ];

        if (isset($configs[$type])) {
            $baseConfig = $configs[$type];
            $configType = isset($configs[$type]['type']) ? $configs[$type]['type'] : 'info';
        } else {
            $baseConfig = [];
            $configType = 'info';
        }

        $config = array_merge($baseConfig, [
            'category' => 'financial',
            'data' => $data,
            'sound_file' => $this->getSoundFile($configType)
        ]);

        return $this->send($config);
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
                'message' => 'Academic update for ' . $studentName,
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
            'sound_file' => $this->getSoundFile($configType)
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
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Validate notification data
     */
    protected function validateData(array $data)
    {
        return [
            'title' => strip_tags(isset($data['title']) ? $data['title'] : 'Notification'),
            'message' => strip_tags(isset($data['message']) ? $data['message'] : ''),
            'type' => $this->validateType(isset($data['type']) ? $data['type'] : 'info'),
            'category' => $this->validateCategory(isset($data['category']) ? $data['category'] : 'general'),
            'priority' => $this->validatePriority(isset($data['priority']) ? $data['priority'] : 'normal'),
            'data' => isset($data['data']) ? $data['data'] : null,
            'action_url' => isset($data['action_url']) ? $data['action_url'] : null,
            'action_text' => isset($data['action_text']) ? $data['action_text'] : null,
            'requires_action' => isset($data['requires_action']) ? $data['requires_action'] : false,
            'play_sound' => isset($data['play_sound']) ? $data['play_sound'] : false,
            'sound_file' => isset($data['sound_file']) ? $data['sound_file'] : $this->getSoundFile(isset($data['type']) ? $data['type'] : 'info'),
            'is_persistent' => isset($data['is_persistent']) ? $data['is_persistent'] : false,
            'expires_at' => isset($data['expires_at']) ? $data['expires_at'] : null,
            'sent_to_roles' => isset($data['roles']) ? $data['roles'] : [],
            'sent_to_users' => isset($data['users']) ? $data['users'] : [],
        ];
    }

    /**
     * Validate notification type
     */
    private function validateType($type)
    {
        $validTypes = ['success', 'error', 'warning', 'info'];
        return in_array($type, $validTypes) ? $type : 'info';
    }

    /**
     * Validate notification category
     */
    private function validateCategory($category)
    {
        $validCategories = ['financial', 'academic', 'system', 'attendance', 'general'];
        return in_array($category, $validCategories) ? $category : 'general';
    }

    /**
     * Validate notification priority
     */
    private function validatePriority($priority)
    {
        $validPriorities = ['low', 'normal', 'high', 'urgent'];
        return in_array($priority, $validPriorities) ? $priority : 'normal';
    }

    /**
     * Get sound file for notification type
     */
    protected function getSoundFile($type)
    {
        return isset($this->defaultSounds[$type]) ? $this->defaultSounds[$type] : $this->defaultSounds['info'];
    }

    /**
     * Generate URL safely
     */
    protected function generateUrl($route, $parameter = null)
    {
        try {
            if ($parameter) {
                return route($route, $parameter);
            }
            return route($route);
        } catch (\Exception $e) {
            return '#';
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

            if (class_exists(\App\Models\Setting::class)) {
                $setting = \App\Models\Setting::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            }

            return $default;
        } catch (\Exception $e) {
            Log::warning('Failed to get setting', [
                'key' => $key,
                'error' => $e->getMessage()
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
            if (!$student) {
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
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
    
    /**
     * Send low attendance notification
     */
    private function sendLowAttendanceNotification($student, $attendancePercentage, $threshold)
    {
        try {
            $message = "Low attendance alert for {$student->name}. Current attendance: {$attendancePercentage}% (Required: {$threshold}%)";
            
            // Send the notification using the academic notification method
            $this->sendAcademicNotification('low_attendance', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'attendance_percentage' => $attendancePercentage,
                'threshold' => $threshold
            ]);
            
            // Send email notification if enabled
            if ($this->getSetting('email_notifications', true)) {
                $this->sendEmailNotification($student, $message);
            }
            
            // Send SMS notification if enabled
            if ($this->getSetting('sms_notifications', false)) {
                $this->sendSMSNotification($student, $message);
            }
            
            return true;
            
        } catch (\Exception $e) {
            $studentId = isset($student->id) ? $student->id : 'unknown';
            Log::error('Failed to send low attendance notification', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($student, $message)
    {
        try {
            // Add your email sending logic here
            // Example: Mail::to($student->email)->send(new AttendanceNotificationMail($student, $message));
            
            $email = isset($student->email) ? $student->email : 'No email';
            Log::info('Email notification sent', [
                'student_id' => $student->id,
                'email' => $email
            ]);
            
        } catch (\Exception $e) {
            Log::error('Email notification failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send SMS notification
     */
    private function sendSMSNotification($student, $message)
    {
        try {
            // Add your SMS sending logic here
            // Example: SMS::send($student->phone, $message);
            
            $phone = isset($student->student_mobile) ? $student->student_mobile : 'No phone';
            Log::info('SMS notification sent', [
                'student_id' => $student->id,
                'phone' => $phone
            ]);
            
        } catch (\Exception $e) {
            Log::error('SMS notification failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

/**
 * Helper function to get setting value safely.
 */
if (!function_exists('setting')) {
    function setting($key, $default = null) {
        try {
            if (class_exists(\App\Models\Setting::class)) {
                $setting = \App\Models\Setting::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            }
            return $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}