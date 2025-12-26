<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupDashboards extends Command
{
    protected $signature = 'dashboard:setup';
    protected $description = 'Set up the complete dashboard system with migrations, seeders, and initial data';

    public function handle()
    {
        $this->info('🚀 Setting up Dashboard System...');
        
        // Run migrations
        $this->info('📊 Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());
        
        // Run seeders
        $this->info('🌱 Seeding dashboard data...');
        
        $seeders = [
            'DashboardPermissionSeeder',
            'WidgetCategorySeeder',
            'WidgetSeeder',
            'DashboardSeeder'
        ];
        
        foreach ($seeders as $seeder) {
            $this->info("   - Running {$seeder}...");
            try {
                Artisan::call('db:seed', ['--class' => $seeder]);
                $this->info("   ✅ {$seeder} completed");
            } catch (\Exception $e) {
                $this->error("   ❌ {$seeder} failed: " . $e->getMessage());
            }
        }
        
        // Sync widgets from blade files
        $this->info('🔄 Syncing widgets from Blade files...');
        Artisan::call('dashboard:sync-widgets');
        
        // Clear caches
        $this->info('🧹 Clearing caches...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        
        $this->info('✅ Dashboard system setup completed successfully!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('Dashboard setup completed successfully!');
        $this->line('Users can access their dashboards from /admin/dashboard');
        
        return Command::SUCCESS;
    }
}