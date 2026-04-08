<?php

namespace App\Traits\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Attendance\NotificationLog;
use App\Models\Attendance\ParentContact;
use App\Models\Student;
use App\Models\User;
use App\Services\Attendance\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

trait HandlesNotifications
{
    /**
     * Send attendance notification for a single attendance record
     */
    public function sendAttendanceNotification(Attendance $attendance, array $options = []): array
    {
        $results = [];

        try {
            // Determine notification type based on attendance status
            $notificationType = $this->determineNotificationType($attendance);

            // Send to parents if enabled
            if ($options['notify_parents'] ?? true) {
                $parentResults = $this->sendParentNotification($attendance->student, $attendance, $notificationType);
                $results['parent_notifications'] = $parentResults;
            }

            // Send to faculty if enabled
            if ($options['notify_faculty'] ?? false) {
                $facultyResults = $this->sendFacultyNotification($attendance, $notificationType);
                $results['faculty_notifications'] = $facultyResults;
            }

            // Send to admins if enabled for critical cases
            if ($this->shouldNotifyAdmins($attendance) && ($options['notify_admins'] ?? true)) {
                $adminResults = $this->sendAdminNotification($attendance, $notificationType);
                $results['admin_notifications'] = $adminResults;
            }

            Log::info('Attendance notifications sent', [
                'attendance_id' => $attendance->id,
                'student_id' => $attendance->student_id,
                'notification_type' => $notificationType,
                'results' => $results,
            ]);

            return [
                'success' => true,
                'results' => $results,
                'notification_type' => $notificationType,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send attendance notification', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notifications for multiple attendance records
     */
    public function sendBulkNotifications(Collection $attendances, array $options = []): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'summary' => [
                'total_processed' => 0,
                'notifications_sent' => 0,
                'errors' => 0,
            ],
        ];

        foreach ($attendances as $attendance) {
            try {
                $notificationResult = $this->sendAttendanceNotification($attendance, $options);

                if ($notificationResult['success']) {
                    $results['successful'][] = [
                        'attendance_id' => $attendance->id,
                        'student_id' => $attendance->student_id,
                        'result' => $notificationResult,
                    ];
                    $results['summary']['notifications_sent']++;
                } else {
                    $results['failed'][] = [
                        'attendance_id' => $attendance->id,
                        'student_id' => $attendance->student_id,
                        'error' => $notificationResult['error'],
                    ];
                    $results['summary']['errors']++;
                }

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'attendance_id' => $attendance->id,
                    'student_id' => $attendance->student_id,
                    'error' => $e->getMessage(),
                ];
                $results['summary']['errors']++;
            }

            $results['summary']['total_processed']++;
        }

        Log::info('Bulk attendance notifications completed', [
            'total_processed' => $results['summary']['total_processed'],
            'successful' => $results['summary']['notifications_sent'],
            'failed' => $results['summary']['errors'],
        ]);

        return $results;
    }

    /**
     * Send notification to parents about student attendance
     */
    public function sendParentNotification(Student $student, Attendance $attendance, string $notificationType): array
    {
        $results = [];

        try {
            // Get parent contacts for this student
            $parentContacts = ParentContact::getNotificationContactsForStudent(
                $student->id,
                $notificationType
            );

            if ($parentContacts->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No parent contacts found',
                ];
            }

            foreach ($parentContacts as $contact) {
                if (! $contact->canReceiveNotifications()) {
                    continue;
                }

                // Get preferred channels for this contact
                $channels = $contact->getPreferredChannels();

                foreach ($channels as $channel) {
                    $result = $this->sendNotificationToChannel(
                        $contact,
                        $student,
                        $attendance,
                        $channel,
                        $notificationType
                    );

                    $results[] = [
                        'contact_id' => $contact->id,
                        'channel' => $channel,
                        'result' => $result,
                    ];

                    // Log the notification attempt
                    $this->logNotificationAttempt($contact, $student, $attendance, $channel, $result);
                }
            }

            return [
                'success' => true,
                'contacts_notified' => count($parentContacts),
                'results' => $results,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send parent notification', [
                'student_id' => $student->id,
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to faculty about attendance issues
     */
    public function sendFacultyNotification(Attendance $attendance, string $notificationType): array
    {
        try {
            // Get faculty who should be notified
            $faculty = $this->getFacultyToNotify($attendance);

            if ($faculty->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No faculty to notify',
                ];
            }

            $results = [];

            foreach ($faculty as $facultyMember) {
                $notificationData = [
                    'title' => $this->getFacultyNotificationTitle($notificationType),
                    'message' => $this->getFacultyNotificationMessage($attendance, $notificationType),
                    'type' => $this->getNotificationPriority($notificationType),
                    'category' => 'attendance',
                    'priority' => 'normal',
                    'recipient' => $facultyMember,
                    'data' => [
                        'attendance_id' => $attendance->id,
                        'student_id' => $attendance->student_id,
                        'student_name' => $attendance->student->name,
                        'status' => $attendance->status,
                        'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                    ],
                ];

                $result = app(NotificationService::class)->send($notificationData);
                $results[] = [
                    'faculty_id' => $facultyMember->id,
                    'result' => $result,
                ];
            }

            return [
                'success' => true,
                'faculty_notified' => count($faculty),
                'results' => $results,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send faculty notification', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to administrators for critical cases
     */
    public function sendAdminNotification(Attendance $attendance, string $notificationType): array
    {
        try {
            $notificationData = [
                'title' => $this->getAdminNotificationTitle($notificationType),
                'message' => $this->getAdminNotificationMessage($attendance, $notificationType),
                'type' => 'warning',
                'category' => 'attendance',
                'priority' => 'high',
                'roles' => ['super-admin', 'college-admin'],
                'data' => [
                    'attendance_id' => $attendance->id,
                    'student_id' => $attendance->student_id,
                    'student_name' => $attendance->student->name,
                    'batch_name' => $attendance->batch->name,
                    'status' => $attendance->status,
                    'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                    'consecutive_absents' => $this->calculateConsecutiveAbsents($attendance->student_id),
                ],
            ];

            $result = app(NotificationService::class)->send($notificationData);

            return [
                'success' => true,
                'result' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send admin notification', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send low attendance warning to parents
     */
    public function sendLowAttendanceWarning(Student $student, array $attendanceStats): array
    {
        try {
            $parentContacts = ParentContact::getNotificationContactsForStudent(
                $student->id,
                'low_attendance_warning'
            );

            if ($parentContacts->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No parent contacts found',
                ];
            }

            $results = [];

            foreach ($parentContacts as $contact) {
                if (! $contact->canReceiveNotifications()) {
                    continue;
                }

                $message = $this->generateLowAttendanceMessage($student, $attendanceStats);
                $channels = $contact->getPreferredChannels();

                foreach ($channels as $channel) {
                    $notificationData = [
                        'title' => 'Low Attendance Warning',
                        'message' => $message,
                        'type' => 'warning',
                        'category' => 'attendance',
                        'priority' => 'high',
                        'channel' => $channel,
                        'recipient' => $contact->getContactInfoForChannel($channel),
                        'data' => [
                            'student_id' => $student->id,
                            'student_name' => $student->name,
                            'attendance_percentage' => $attendanceStats['attendance_percentage'],
                            'minimum_required' => config('attendance.minimum_percentage', 75),
                        ],
                    ];

                    $result = app(NotificationService::class)->send($notificationData);
                    $results[] = [
                        'contact_id' => $contact->id,
                        'channel' => $channel,
                        'result' => $result,
                    ];
                }
            }

            return [
                'success' => true,
                'contacts_notified' => count($parentContacts),
                'results' => $results,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send low attendance warning', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Private helper methods
     */
    private function determineNotificationType(Attendance $attendance): string
    {
        switch ($attendance->status) {
            case 'absent':
                return 'absence_alert';
            case 'late':
                return 'late_arrival';
            case 'present':
            case 'excused':
                return 'attendance_marked';
            default:
                return 'attendance_update';
        }
    }

    private function shouldNotifyAdmins(Attendance $attendance): bool
    {
        // Notify admins for absences or if student has multiple consecutive absences
        if ($attendance->status === 'absent') {
            $consecutiveAbsents = $this->calculateConsecutiveAbsents($attendance->student_id);

            return $consecutiveAbsents >= 3;
        }

        return false;
    }

    private function calculateConsecutiveAbsents(int $studentId): int
    {
        $recentAttendances = Attendance::where('student_id', $studentId)
            ->orderBy('attendance_date', 'desc')
            ->limit(10)
            ->get();

        $consecutive = 0;
        foreach ($recentAttendances as $attendance) {
            if ($attendance->status === 'absent') {
                $consecutive++;
            } else {
                break;
            }
        }

        return $consecutive;
    }

    private function sendNotificationToChannel($contact, Student $student, Attendance $attendance, string $channel, string $notificationType): array
    {
        $message = $this->generateNotificationMessage($student, $attendance, $notificationType);
        $contactInfo = $contact->getContactInfoForChannel($channel);

        if (! $contactInfo) {
            return [
                'success' => false,
                'error' => "No contact info for channel: {$channel}",
            ];
        }

        $notificationData = [
            'title' => $this->getNotificationTitle($notificationType),
            'message' => $message,
            'type' => $this->getNotificationPriority($notificationType),
            'category' => 'attendance',
            'priority' => 'normal',
            'channel' => $channel,
            'recipient' => $contactInfo,
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'attendance_id' => $attendance->id,
                'status' => $attendance->status,
                'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
            ],
        ];

        return app(NotificationService::class)->send($notificationData);
    }

    private function generateNotificationMessage(Student $student, Attendance $attendance, string $notificationType): string
    {
        $templates = [
            'absence_alert' => "Dear Parent, {$student->name} was marked absent on {$attendance->attendance_date->format('M d, Y')}. Please contact the school if there are any concerns.",
            'late_arrival' => "Dear Parent, {$student->name} arrived late on {$attendance->attendance_date->format('M d, Y')}. Please ensure timely arrival to avoid missing important lessons.",
            'attendance_marked' => "Dear Parent, {$student->name}'s attendance has been recorded as {$attendance->status} for {$attendance->attendance_date->format('M d, Y')}.",
        ];

        return $templates[$notificationType] ?? $templates['attendance_marked'];
    }

    private function generateLowAttendanceMessage(Student $student, array $stats): string
    {
        $percentage = $stats['attendance_percentage'];
        $required = config('attendance.minimum_percentage', 75);

        return "Dear Parent, {$student->name}'s attendance is currently {$percentage}%, which is below the required {$required}%. ".
               'Please ensure regular attendance to avoid academic impact. Contact the school for support if needed.';
    }

    private function getNotificationTitle(string $type): string
    {
        $titles = [
            'absence_alert' => 'Student Absent Alert',
            'late_arrival' => 'Late Arrival Notice',
            'attendance_marked' => 'Attendance Update',
            'low_attendance_warning' => 'Low Attendance Warning',
        ];

        return $titles[$type] ?? 'Attendance Notification';
    }

    private function getFacultyNotificationTitle(string $type): string
    {
        $titles = [
            'absence_alert' => 'Student Absence Report',
            'late_arrival' => 'Late Arrival Report',
            'attendance_marked' => 'Attendance Recorded',
        ];

        return $titles[$type] ?? 'Attendance Update';
    }

    private function getAdminNotificationTitle(string $type): string
    {
        return 'Critical Attendance Alert';
    }

    private function getFacultyNotificationMessage(Attendance $attendance, string $type): string
    {
        $student = $attendance->student;
        $date = $attendance->attendance_date->format('M d, Y');

        $messages = [
            'absence_alert' => "Student {$student->name} was marked absent on {$date}.",
            'late_arrival' => "Student {$student->name} arrived late on {$date}.",
            'attendance_marked' => "Attendance recorded for {$student->name} on {$date} as {$attendance->status}.",
        ];

        return $messages[$type] ?? $messages['attendance_marked'];
    }

    private function getAdminNotificationMessage(Attendance $attendance, string $type): string
    {
        $student = $attendance->student;
        $consecutiveAbsents = $this->calculateConsecutiveAbsents($student->id);

        return "Student {$student->name} from {$attendance->batch->name} has been absent for {$consecutiveAbsents} consecutive days. ".
               'This requires immediate attention.';
    }

    private function getNotificationPriority(string $type): string
    {
        $priorities = [
            'absence_alert' => 'warning',
            'late_arrival' => 'info',
            'attendance_marked' => 'success',
            'low_attendance_warning' => 'warning',
        ];

        return $priorities[$type] ?? 'info';
    }

    private function getFacultyToNotify(Attendance $attendance): Collection
    {
        // Get faculty assigned to this batch/subject
        $faculty = collect();

        // Add the faculty who marked the attendance
        if ($attendance->faculty) {
            $faculty->push($attendance->faculty);
        }

        // Add batch coordinators
        $batchCoordinators = User::role('faculty')
            ->whereHas('assignedBatches', function ($query) use ($attendance) {
                $query->where('batch_id', $attendance->batch_id);
            })
            ->get();

        $faculty = $faculty->merge($batchCoordinators);

        // Add subject teachers if subject is specified
        if ($attendance->subject_id) {
            $subjectTeachers = User::role('faculty')
                ->whereHas('subjects', function ($query) use ($attendance) {
                    $query->where('subject_id', $attendance->subject_id);
                })
                ->get();

            $faculty = $faculty->merge($subjectTeachers);
        }

        return $faculty->unique('id');
    }

    private function logNotificationAttempt($contact, Student $student, Attendance $attendance, string $channel, array $result): void
    {
        try {
            NotificationLog::create([
                'notification_type' => 'attendance_notification',
                'category' => 'attendance',
                'priority' => 'normal',
                'channel' => $channel,
                'recipient_type' => 'parent',
                'recipient_id' => $contact->getContactInfoForChannel($channel),
                'student_id' => $student->id,
                'parent_contact_id' => $contact->id,
                'sender_id' => auth()->id(),
                'subject' => $this->getNotificationTitle($this->determineNotificationType($attendance)),
                'message' => $this->generateNotificationMessage($student, $attendance, $this->determineNotificationType($attendance)),
                'delivery_status' => $result['success'] ? 'sent' : 'failed',
                'sent_at' => $result['success'] ? now() : null,
                'failed_at' => ! $result['success'] ? now() : null,
                'error_message' => $result['error'] ?? null,
                'metadata' => [
                    'attendance_id' => $attendance->id,
                    'attendance_status' => $attendance->status,
                    'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                ],
            ]);

            // Update contact success/failure counts
            if ($result['success']) {
                $contact->recordSuccessfulContact($channel);
            } else {
                $contact->recordFailedContact($result['error'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            Log::error('Failed to log notification attempt', [
                'contact_id' => $contact->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
