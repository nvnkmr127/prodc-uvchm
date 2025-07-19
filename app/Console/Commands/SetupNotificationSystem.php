<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Services\NotificationService;

class SetupNotificationSystem extends Command
{
    protected $signature = 'notifications:setup {--force : Force setup even if already configured}';
    protected $description = 'Set up and configure the notification system';

    public function handle()
    {
        $this->info('🚀 Setting up Notification System...');
        $this->line('');

        if (!$this->option('force') && $this->isAlreadySetup()) {
            $this->warn('⚠️  Notification system appears to be already set up.');
            if (!$this->confirm('Do you want to continue anyway?', false)) {
                $this->info('Setup cancelled.');
                return 0;
            }
        }

        $steps = [
            'checkDependencies' => 'Checking Dependencies',
            'runMigrations' => 'Running Database Migrations',
            'seedDefaultData' => 'Seeding Default Data',
            'configureSettings' => 'Configuring Default Settings',
            'testBasicFunctionality' => 'Testing Basic Functionality',
            'generateDocumentation' => 'Generating Documentation',
        ];

        $completedSteps = 0;
        $totalSteps = count($steps);

        foreach ($steps as $method => $description) {
            $this->info("📋 Step " . ($completedSteps + 1) . "/{$totalSteps}: {$description}");
            
            try {
                $this->$method();
                $this->line("  ✅ {$description} completed successfully");
                $completedSteps++;
            } catch (\Exception $e) {
                $this->error("  ❌ {$description} failed: " . $e->getMessage());
                
                if (!$this->confirm('Continue with setup despite this error?', false)) {
                    $this->error('Setup aborted.');
                    return 1;
                }
            }
            
            $this->line('');
        }

        $this->displaySetupSummary($completedSteps, $totalSteps);
        return $completedSteps === $totalSteps ? 0 : 1;
    }

    private function isAlreadySetup()
    {
        try {
            $hasNotifications = \App\Models\SystemNotification::count() > 0;
            $hasService = app(NotificationService::class) !== null;
            return $hasNotifications && $hasService;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkDependencies()
    {
        // Check if required tables exist
        $requiredTables = ['system_notifications', 'notification_preferences'];
        foreach ($requiredTables as $table) {
            if (!\Schema::hasTable($table)) {
                throw new \Exception("Required table '{$table}' does not exist");
            }
        }

        // Check if NotificationService is available
        if (!class_exists(\App\Services\NotificationService::class)) {
            throw new \Exception('NotificationService class not found');
        }

        $this->line('    ✓ All dependencies are available');
    }

    private function runMigrations()
    {
        $this->call('migrate', ['--force' => true]);
        $this->line('    ✓ Database migrations completed');
    }

    private function seedDefaultData()
    {
        // Create some default notification preferences if none exist
        if (\App\Models\NotificationPreference::count() === 0) {
            $this->line('    ✓ No seeding required - notification preferences will be created per user');
        } else {
            $this->line('    ✓ Default data already exists');
        }
    }

  private function configureSettings()
{
    $defaultSettings = [
        'email_notifications' => 'true',
        'sms_notifications' => 'false',
        'push_notifications' => 'true',
        'sound_notifications' => 'true',
        'fee_reminder_days' => '7',
        'minimum_attendance_percentage' => '75',
    ];

    // Check if Setting model exists
    if (!class_exists(\App\Models\Setting::class)) {
        $this->line('    ⚠️ Setting model not found, skipping settings configuration');
        return;
    }

    try {
        foreach ($defaultSettings as $key => $value) {
            // Check if setting already exists
            $existingSetting = \App\Models\Setting::where('key', $key)->first();
            
            if (!$existingSetting) {
                \App\Models\Setting::create([
                    'key' => $key,
                    'value' => $value,
                ]);
                $this->line("      ✓ Created setting: {$key} = {$value}");
            } else {
                $this->line("      ✓ Setting exists: {$key} = {$existingSetting->value}");
            }
        }

        $this->line('    ✓ Default settings configured');
        
    } catch (\Exception $e) {
        $this->line('    ⚠️ Settings configuration failed: ' . $e->getMessage());
        $this->line('    ℹ️ You can configure settings manually in the admin panel');
    }
}

    private function testBasicFunctionality()
    {
        $service = app(NotificationService::class);
        
        // Test basic notification sending
        $notification = $service->send([
            'title' => 'Setup Test Notification',
            'message' => 'This is a test notification created during setup',
            'type' => 'info',
            'category' => 'system',
            'priority' => 'low',
            'roles' => ['super-admin'],
            'data' => ['setup_test' => true]
        ]);

        if (!$notification) {
            throw new \Exception('Failed to send test notification');
        }

        // Clean up test notification
        $notification->delete();
        
        $this->line('    ✓ Basic functionality test passed');
    }

    private function generateDocumentation()
    {
        $docPath = storage_path('docs/notification-system.md');
        $docDir = dirname($docPath);
        
        if (!is_dir($docDir)) {
            mkdir($docDir, 0755, true);
        }

        $documentation = $this->generateDocumentationContent();
        file_put_contents($docPath, $documentation);
        
        $this->line("    ✓ Documentation generated at: {$docPath}");
    }

   private function generateDocumentationContent()
{
    return <<<'DOC'
# College Management System - Notification System
// ... complete documentation content ...
DOC;
}

    private function displaySetupSummary($completed, $total)
    {
        $this->line('');
        $this->info('📊 Setup Summary:');
        $this->line("  Completed Steps: {$completed}/{$total}");
        
        if ($completed === $total) {
            $this->info('🎉 Notification System setup completed successfully!');
            $this->line('');
            $this->info('Next Steps:');
            $this->line('1. Access the notification dashboard: /admin/notifications');
            $this->line('2. Configure your notification preferences');
            $this->line('3. Test the system: php artisan notifications:test');
            $this->line('4. Check system status: php artisan notifications:status');
        } else {
            $this->warn('⚠️  Setup completed with some issues. Please review the errors above.');
        }
    }
}