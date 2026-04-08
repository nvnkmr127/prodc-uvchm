<?php

// File: app/Console/Commands/DashboardSetupCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DashboardSetupCommand extends Command
{
    protected $signature = 'dashboard:setup 
                          {--force : Force setup even if already configured}
                          {--seed : Run seeders after setup}';

    protected $description = 'Set up the complete dashboard system';

    public function handle()
    {
        $this->info('🚀 Setting up Dashboard System...');
        $this->newLine();

        // Check if already set up
        if (! $this->option('force') && $this->isDashboardSetup()) {
            $this->warn('Dashboard system appears to be already set up.');
            if (! $this->confirm('Do you want to continue anyway?')) {
                return 0;
            }
        }

        $steps = [
            'runMigrations' => 'Running database migrations...',
            'createDirectories' => 'Creating required directories...',
            'syncWidgets' => 'Syncing widget definitions...',
            'runSeeders' => 'Running dashboard seeders...',
            'clearCaches' => 'Clearing caches...',
            'validateSetup' => 'Validating setup...',
        ];

        $progressBar = $this->output->createProgressBar(count($steps));
        $progressBar->start();

        foreach ($steps as $method => $description) {
            $this->info("\n".$description);
            $this->{$method}();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✅ Dashboard system setup completed successfully!');
        $this->displayNextSteps();

        return 0;
    }

    private function isDashboardSetup(): bool
    {
        try {
            return \App\Models\Dashboard::count() > 0 &&
                   \App\Models\Widget::count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function runMigrations(): void
    {
        $this->call('migrate');
    }

    private function createDirectories(): void
    {
        $directories = [
            storage_path('app/exports'),
            storage_path('app/dashboard-cache'),
            public_path('dashboard-assets'),
            resource_path('views/dashboard/widgets'),
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
                $this->line("Created: {$directory}");
            }
        }
    }

    private function syncWidgets(): void
    {
        $this->call('dashboard:sync-widgets');
    }

    private function runSeeders(): void
    {
        if ($this->option('seed')) {
            $seeders = [
                'DashboardPermissionSeeder',
                'WidgetCategorySeeder',
                'WidgetSeeder',
                'DashboardSeeder',
            ];

            foreach ($seeders as $seeder) {
                $this->call('db:seed', ['--class' => $seeder]);
            }
        }
    }

    private function clearCaches(): void
    {
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');
    }

    private function validateSetup(): void
    {
        $this->call('dashboard:status');
    }

    private function displayNextSteps(): void
    {
        $this->newLine();
        $this->info('🎉 Dashboard Setup Complete!');
        $this->line('1. Assign permissions to users and roles');
        $this->line('2. Users can access their dashboards from /admin/dashboard');
        $this->line('4. Test the dashboard system with different user roles');
        $this->newLine();
    }
}
