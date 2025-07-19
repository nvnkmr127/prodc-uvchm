<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\SystemNotification;

class TestNotificationSystem extends Command
{
    protected $signature = 'notifications:test 
                            {--category= : Test specific category (financial, academic, system, attendance)}
                            {--dry-run : Show what would be sent without actually sending}
                            {--cleanup : Clean up test notifications after}';
    
    protected $description = 'Comprehensive test of the notification system';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('🧪 Testing Notification System...');

        $category = $this->option('category');
        $dryRun = $this->option('dry-run');
        $cleanup = $this->option('cleanup');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No notifications will be sent');
        }

        $testResults = [];

        if (!$category || $category === 'financial') {
            $testResults['financial'] = $this->testFinancialNotifications($dryRun);
        }

        if (!$category || $category === 'academic') {
            $testResults['academic'] = $this->testAcademicNotifications($dryRun);
        }

        if (!$category || $category === 'system') {
            $testResults['system'] = $this->testSystemNotifications($dryRun);
        }

        if (!$category || $category === 'attendance') {
            $testResults['attendance'] = $this->testAttendanceNotifications($dryRun);
        }

        $this->displayResults($testResults);

        if ($cleanup && !$dryRun) {
            $this->cleanupTestNotifications();
        }

        return $this->allTestsPassed($testResults) ? 0 : 1;
    }

    private function testFinancialNotifications($dryRun = false)
    {
        $this->info('📊 Testing Financial Notifications...');
        
        $tests = [
            'payment_received' => [
                'payment_id' => 9999,
                'student_id' => 1,
                'student_name' => 'Test Student',
                'amount' => 15000,
                'payment_method' => 'Test',
            ],
            'payment_failed' => [
                'student_id' => 1,
                'student_name' => 'Test Student',
                'amount' => 12000,
                'failure_reason' => 'Test failure',
            ],
            'fee_reminder' => [
                'student_id' => 1,
                'student_name' => 'Test Student',
                'amount' => 8500,
            ],
        ];

        $results = [];
        foreach ($tests as $testName => $testData) {
            try {
                if (!$dryRun) {
                    $notification = $this->notificationService->sendFinancialAlert($testName, $testData);
                    $results[$testName] = $notification ? 'PASS' : 'FAIL';
                } else {
                    $results[$testName] = 'SKIPPED (DRY RUN)';
                }
            } catch (\Exception $e) {
                $results[$testName] = 'ERROR: ' . $e->getMessage();
            }
        }

        return $results;
    }

    private function testAcademicNotifications($dryRun = false)
    {
        $this->info('🎓 Testing Academic Notifications...');
        
        $tests = [
            'new_admission' => [
                'student_id' => 1,
                'student_name' => 'Test Student',
                'course_name' => 'Test Course',
            ],
            'low_attendance' => [
                'student_id' => 1,
                'student_name' => 'Test Student',
                'attendance_percentage' => 65,
            ],
        ];

        $results = [];
        foreach ($tests as $testName => $testData) {
            try {
                if (!$dryRun) {
                    $notification = $this->notificationService->sendAcademicNotification($testName, $testData);
                    $results[$testName] = $notification ? 'PASS' : 'FAIL';
                } else {
                    $results[$testName] = 'SKIPPED (DRY RUN)';
                }
            } catch (\Exception $e) {
                $results[$testName] = 'ERROR: ' . $e->getMessage();
            }
        }

        return $results;
    }

    private function testSystemNotifications($dryRun = false)
    {
        $this->info('⚙️ Testing System Notifications...');
        
        $tests = [
            'normal_alert' => [
                'message' => 'Test system alert - everything is working correctly',
                'priority' => 'normal',
                'data' => ['test' => true]
            ],
            'urgent_alert' => [
                'message' => 'Test urgent system alert',
                'priority' => 'urgent',
                'data' => ['test' => true, 'urgent' => true]
            ],
        ];

        $results = [];
        foreach ($tests as $testName => $testData) {
            try {
                if (!$dryRun) {
                    $notification = $this->notificationService->sendSystemAlert(
                        $testData['message'],
                        $testData['priority'],
                        $testData['data']
                    );
                    $results[$testName] = $notification ? 'PASS' : 'FAIL';
                } else {
                    $results[$testName] = 'SKIPPED (DRY RUN)';
                }
            } catch (\Exception $e) {
                $results[$testName] = 'ERROR: ' . $e->getMessage();
            }
        }

        return $results;
    }

    private function testAttendanceNotifications($dryRun = false)
    {
        $this->info('📅 Testing Attendance Notifications...');
        
        $tests = [
            'general_attendance' => [
                'title' => 'Test Attendance Notification',
                'message' => 'This is a test attendance notification',
                'type' => 'info',
                'category' => 'attendance',
                'priority' => 'normal',
                'roles' => ['super-admin'],
                'data' => ['test' => true]
            ],
        ];

        $results = [];
        foreach ($tests as $testName => $testData) {
            try {
                if (!$dryRun) {
                    $notification = $this->notificationService->send($testData);
                    $results[$testName] = $notification ? 'PASS' : 'FAIL';
                } else {
                    $results[$testName] = 'SKIPPED (DRY RUN)';
                }
            } catch (\Exception $e) {
                $results[$testName] = 'ERROR: ' . $e->getMessage();
            }
        }

        return $results;
    }

    private function displayResults($testResults)
    {
        $this->info('📋 Test Results:');
        
        foreach ($testResults as $category => $results) {
            $this->line('');
            $this->line(strtoupper($category) . ' NOTIFICATIONS:');
            
            foreach ($results as $testName => $result) {
                $icon = match(true) {
                    str_contains($result, 'PASS') => '✅',
                    str_contains($result, 'FAIL') => '❌',
                    str_contains($result, 'ERROR') => '💥',
                    str_contains($result, 'SKIPPED') => '⏭️',
                    default => '❓'
                };
                
                $this->line("  {$icon} {$testName}: {$result}");
            }
        }
    }

    private function allTestsPassed($testResults)
    {
        foreach ($testResults as $category => $results) {
            foreach ($results as $result) {
                if (str_contains($result, 'FAIL') || str_contains($result, 'ERROR')) {
                    return false;
                }
            }
        }
        return true;
    }

    private function cleanupTestNotifications()
    {
        $this->info('🧹 Cleaning up test notifications...');
        
        $count = SystemNotification::where('message', 'like', '%Test%')
            ->orWhere('message', 'like', '%test%')
            ->orWhere('title', 'like', '%Test%')
            ->delete();
            
        $this->info("Cleaned up {$count} test notifications");
    }
}
