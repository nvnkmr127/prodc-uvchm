<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class TestNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $type = $request->get('type', 'general');

        try {
            $testData = $this->getTestData($type);

            if (str_starts_with($type, 'financial_')) {
                $financialType = str_replace('financial_', '', $type);
                $notification = $this->notificationService->sendFinancialAlert($financialType, $testData);
            } elseif (str_starts_with($type, 'academic_')) {
                $academicType = str_replace('academic_', '', $type);
                $notification = $this->notificationService->sendAcademicNotification($academicType, $testData);
            } elseif (str_starts_with($type, 'system_')) {
                $notification = $this->notificationService->sendSystemAlert(
                    $testData['message'],
                    $testData['priority'],
                    $testData['data'] ?? []
                );
            } else {
                // Default test notification
                $notification = $this->notificationService->send([
                    'title' => 'Test Notification',
                    'message' => 'This is a test notification sent at '.now()->format('H:i:s'),
                    'type' => 'info',
                    'category' => 'general',
                    'priority' => 'normal',
                    'play_sound' => true,
                    'roles' => ['super-admin'],
                ]);
            }

            return response()->json([
                'success' => true,
                'notification' => $notification,
                'message' => 'Test notification sent successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get test data for different notification types
     */
    private function getTestData($type): array
    {
        return match ($type) {
            'financial_payment_received' => [
                'payment_id' => 123,
                'student_id' => 1,
                'student_name' => 'John Doe',
                'amount' => 15000,
                'payment_method' => 'Online',
            ],
            'financial_payment_failed' => [
                'payment_id' => 124,
                'student_id' => 2,
                'student_name' => 'Jane Smith',
                'amount' => 12000,
                'failure_reason' => 'Insufficient funds',
            ],
            'financial_fee_reminder' => [
                'student_id' => 3,
                'student_name' => 'Alice Johnson',
                'amount' => 8500,
            ],
            'financial_overdue_payment' => [
                'student_id' => 4,
                'student_name' => 'Robert Wilson',
                'amount' => 25000,
                'days_overdue' => 15,
            ],
            'academic_new_admission' => [
                'student_id' => 5,
                'student_name' => 'Sarah Davis',
                'course_name' => 'Computer Science',
            ],
            'academic_low_attendance' => [
                'student_id' => 6,
                'student_name' => 'Michael Brown',
                'attendance_percentage' => 65,
                'subject' => 'Physics',
            ],
            'system_error' => [
                'message' => 'Database connection error detected. System performance may be affected.',
                'priority' => 'urgent',
                'data' => ['error_code' => 'DB_001'],
            ],
            'system_maintenance' => [
                'message' => 'System maintenance scheduled for tonight at 2:00 AM.',
                'priority' => 'normal',
                'data' => ['maintenance_window' => '02:00-04:00'],
            ],
            default => [
                'message' => 'This is a test notification',
                'priority' => 'normal',
                'data' => [],
            ]
        };
    }
}
