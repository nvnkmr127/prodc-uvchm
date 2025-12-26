<?php

namespace App\Jobs\Attendance;

use App\Services\Attendance\NotificationService;
use App\Models\Attendance\NotificationLog;
use App\Models\Student;
use App\Events\Attendance\NotificationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Retry delays in seconds

    protected $notificationData;
    protected $type;

    public function __construct(array $notificationData, string $type = 'single')
    {
        $this->notificationData = $notificationData;
        $this->type = $type;
        $this->onQueue($this->determineQueue($notificationData));
    }

    /**
     * Execute the job
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            switch ($this->type) {
                case 'single':
                    $this->processSingleNotification($notificationService);
                    break;
                case 'absent_alert':
                    $this->processAbsentAlert($notificationService);
                    break;
                case 'low_attendance_alert':
                    $this->processLowAttendanceAlert($notificationService);
                    break;
                case 'daily_summary':
                    $this->processDailySummary($notificationService);
                    break;
                case 'weekly_report':
                    $this->processWeeklyReport($notificationService);
                    break;
                case 'bulk_notifications':
                    $this->processBulkNotifications($notificationService);
                    break;
                case 'retry_failed':
                    $this->retryFailedNotifications();
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown notification type: {$this->type}");
            }

        } catch (\Exception $e) {
            Log::error('Notification processing job failed', [
                'type' => $this->type,
                'data' => $this->notificationData,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job permanently failed', [
            'type' => $this->type,
            'data' => $this->notificationData,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts
        ]);

        // Mark notification as failed if ID is provided
        if (isset($this->notificationData['notification_id'])) {
            $notification = NotificationLog::find($this->notificationData['notification_id']);
            if ($notification) {
                $notification->markAsFailed($exception->getMessage());
            }
        }
    }

    /**
     * Process single notification
     */
    private function processSingleNotification(NotificationService $notificationService): void
    {
        // Implementation depends on your notification structure
        // This is a placeholder for the actual notification sending logic
        
        $notificationLog = NotificationLog::create([
            'notification_type' => $this->notificationData['type'] ?? 'general',
            'channel' => implode(',', $this->notificationData['channels'] ?? ['database']),
            'title' => $this->notificationData['title'],
            'message' => $this->notificationData['message'],
            'data' => $this->notificationData['data'] ?? [],
            'status' => 'pending',
            'priority' => $this->notificationData['priority'] ?? 'normal'
        ]);

        try {
            // Send the notification
            $this->sendNotification($this->notificationData);
            $notificationLog->markAsSent();
            
            event(new NotificationEvent('sent', $this->notificationData, $notificationLog));
            
        } catch (\Exception $e) {
            $notificationLog->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Process absent alert
     */
    private function processAbsentAlert(NotificationService $notificationService): void
    {
        $attendanceId = $this->notificationData['attendance_id'];
        $attendance = \App\Models\Attendance\Attendance::with('student')->findOrFail($attendanceId);
        
        $notificationService->sendAbsentAlert($attendance);
        
        Log::info('Absent alert processed', [
            'attendance_id' => $attendanceId,
            'student_id' => $attendance->student_id
        ]);
    }

    /**
     * Process low attendance alert
     */
    private function processLowAttendanceAlert(NotificationService $notificationService): void
    {
        $studentId = $this->notificationData['student_id'];
        $student = Student::findOrFail($studentId);
        $stats = $this->notificationData['stats'];
        
        $notificationService->sendLowAttendanceAlert($student, $stats);
        
        Log::info('Low attendance alert processed', [
            'student_id' => $studentId,
            'attendance_percentage' => $stats['attendance_percentage']
        ]);
    }

    /**
     * Process daily summary
     */
    private function processDailySummary(NotificationService $notificationService): void
    {
        $date = isset($this->notificationData['date']) ? 
            Carbon::parse($this->notificationData['date']) : 
            Carbon::today();
        
        $notificationService->sendDailySummary($date);
        
        Log::info('Daily summary processed', ['date' => $date->format('Y-m-d')]);
    }

    /**
     * Process weekly report
     */
    private function processWeeklyReport(NotificationService $notificationService): void
    {
        $weekEndDate = isset($this->notificationData['week_end_date']) ? 
            Carbon::parse($this->notificationData['week_end_date']) : 
            Carbon::now()->endOfWeek();
        
        $notificationService->sendWeeklyReport($weekEndDate);
        
        Log::info('Weekly report processed', ['week_end_date' => $weekEndDate->format('Y-m-d')]);
    }

    /**
     * Process bulk notifications
     */
    private function processBulkNotifications(NotificationService $notificationService): void
    {
        $notifications = $this->notificationData['notifications'];
        $processed = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                $this->sendNotification($notification);
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                Log::warning('Bulk notification item failed', [
                    'notification' => $notification,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Bulk notifications processed', [
            'total' => count($notifications),
            'processed' => $processed,
            'failed' => $failed
        ]);
    }

    /**
     * Retry failed notifications
     */
    private function retryFailedNotifications(): void
    {
        $notificationIds = $this->notificationData['notification_ids'] ?? [];
        $retried = 0;

        foreach ($notificationIds as $id) {
            $notification = NotificationLog::find($id);
            if ($notification && $notification->canRetry()) {
                try {
                    $this->sendNotification([
                        'type' => $notification->notification_type,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'channels' => explode(',', $notification->channel),
                        'data' => $notification->data
                    ]);
                    
                    $notification->markAsSent();
                    $retried++;
                    
                } catch (\Exception $e) {
                    $notification->markAsFailed($e->getMessage());
                }
            }
        }

        Log::info('Failed notifications retried', ['retried' => $retried]);
    }

    /**
     * Send notification via appropriate channels
     */
    private function sendNotification(array $data): void
    {
        // This is a placeholder - implement actual notification sending logic
        // based on your notification system (mail, SMS, push, etc.)
        
        $channels = $data['channels'] ?? ['database'];
        
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'mail':
                    $this->sendEmailNotification($data);
                    break;
                case 'sms':
                    $this->sendSmsNotification($data);
                    break;
                case 'database':
                    $this->sendDatabaseNotification($data);
                    break;
                case 'push':
                    $this->sendPushNotification($data);
                    break;
                default:
                    Log::warning('Unknown notification channel', ['channel' => $channel]);
            }
        }
    }

    /**
     * Determine queue based on notification priority
     */
    private function determineQueue(array $data): string
    {
        $priority = $data['priority'] ?? 'normal';
        
        return match($priority) {
            'urgent', 'high' => 'high-priority-notifications',
            'low' => 'low-priority-notifications',
            default => 'notifications'
        };
    }

    /**
     * Placeholder notification methods
     */
    private function sendEmailNotification(array $data): void
    {
        // Implement email sending logic
    }

    private function sendSmsNotification(array $data): void
    {
        // Implement SMS sending logic
    }

    private function sendDatabaseNotification(array $data): void
    {
        // Implement database notification logic
    }

    private function sendPushNotification(array $data): void
    {
        // Implement push notification logic
    }
}
