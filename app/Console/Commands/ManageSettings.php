<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ManageSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:manage 
                            {action : Action to perform (list, get, set, delete, export, import, backup, restore, clear-cache)}
                            {key? : Setting key (required for get, set, delete)}
                            {value? : Setting value (required for set)}
                            {--group= : Filter by group (for list action)}
                            {--type= : Setting type (for set action)}
                            {--file= : File path (for import, export, restore actions)}
                            {--force : Force action without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application settings from command line';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listSettings();
            case 'get':
                return $this->getSetting();
            case 'set':
                return $this->setSetting();
            case 'delete':
                return $this->deleteSetting();
            case 'export':
                return $this->exportSettings();
            case 'import':
                return $this->importSettings();
            case 'backup':
                return $this->backupSettings();
            case 'restore':
                return $this->restoreSettings();
            case 'clear-cache':
                return $this->clearCache();
            default:
                $this->error("Unknown action: {$action}");
                $this->showHelp();

                return Command::FAILURE;
        }
    }

    /**
     * List all settings
     */
    protected function listSettings()
    {
        $query = Setting::query();

        if ($group = $this->option('group')) {
            $query->where('group', $group);
        }

        $settings = $query->orderBy('group')->orderBy('key')->get();

        if ($settings->isEmpty()) {
            $this->info('No settings found.');

            return Command::SUCCESS;
        }

        $this->info('Settings List:');
        $this->line('');

        $currentGroup = null;
        foreach ($settings as $setting) {
            if ($setting->group !== $currentGroup) {
                $currentGroup = $setting->group;
                $this->comment("Group: {$currentGroup}");
                $this->line(str_repeat('-', 50));
            }

            $value = $setting->is_encrypted ? '[ENCRYPTED]' : $setting->value;
            $this->line("  {$setting->key}: {$value} ({$setting->type})");
        }

        $this->line('');
        $this->info("Total: {$settings->count()} settings");

        return Command::SUCCESS;
    }

    /**
     * Get a specific setting
     */
    protected function getSetting()
    {
        $key = $this->argument('key');

        if (! $key) {
            $this->error('Setting key is required for get action.');

            return Command::FAILURE;
        }

        $setting = Setting::where('key', $key)->first();

        if (! $setting) {
            $this->error("Setting '{$key}' not found.");

            return Command::FAILURE;
        }

        $this->info('Setting Details:');
        $this->line("Key: {$setting->key}");
        $this->line('Value: '.($setting->is_encrypted ? '[ENCRYPTED]' : $setting->value));
        $this->line("Group: {$setting->group}");
        $this->line("Type: {$setting->type}");
        $this->line('Public: '.($setting->is_public ? 'Yes' : 'No'));
        $this->line('Encrypted: '.($setting->is_encrypted ? 'Yes' : 'No'));
        $this->line("Description: {$setting->description}");
        $this->line("Created: {$setting->created_at}");
        $this->line("Updated: {$setting->updated_at}");

        return Command::SUCCESS;
    }

    /**
     * Set a setting value
     */
    protected function setSetting()
    {
        $key = $this->argument('key');
        $value = $this->argument('value');

        if (! $key) {
            $this->error('Setting key is required for set action.');

            return Command::FAILURE;
        }

        if ($value === null) {
            $this->error('Setting value is required for set action.');

            return Command::FAILURE;
        }

        $type = $this->option('type') ?? 'text';

        try {
            $setting = Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'group' => $this->option('group') ?? 'general',
                ]
            );

            $this->info("Setting '{$key}' has been set to '{$value}'.");

            // Clear cache
            clear_settings_cache();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to set setting: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Delete a setting
     */
    protected function deleteSetting()
    {
        $key = $this->argument('key');

        if (! $key) {
            $this->error('Setting key is required for delete action.');

            return Command::FAILURE;
        }

        $setting = Setting::where('key', $key)->first();

        if (! $setting) {
            $this->error("Setting '{$key}' not found.");

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("Are you sure you want to delete setting '{$key}'?")) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        try {
            $setting->delete();
            clear_settings_cache();

            $this->info("Setting '{$key}' has been deleted.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to delete setting: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Export settings to file
     */
    protected function exportSettings()
    {
        $file = $this->option('file') ?? 'settings-export-'.date('Y-m-d-H-i-s').'.json';

        try {
            $settings = Setting::all();

            $export = [
                'exported_at' => now()->toISOString(),
                'app_version' => config('app.version', '1.0.0'),
                'total_settings' => $settings->count(),
                'settings' => $settings->toArray(),
            ];

            $content = json_encode($export, JSON_PRETTY_PRINT);

            if (str_starts_with($file, '/')) {
                // Absolute path
                file_put_contents($file, $content);
            } else {
                // Relative path
                $file = storage_path("app/{$file}");
                file_put_contents($file, $content);
            }

            $this->info("Settings exported to: {$file}");
            $this->info("Total settings exported: {$settings->count()}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to export settings: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Import settings from file
     */
    protected function importSettings()
    {
        $file = $this->option('file');

        if (! $file) {
            $this->error('File path is required for import action.');

            return Command::FAILURE;
        }

        if (! str_starts_with($file, '/')) {
            $file = storage_path("app/{$file}");
        }

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return Command::FAILURE;
        }

        try {
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (! isset($data['settings'])) {
                $this->error('Invalid settings file format.');

                return Command::FAILURE;
            }

            if (! $this->option('force')) {
                $count = count($data['settings']);
                if (! $this->confirm("Import {$count} settings? This will overwrite existing settings.")) {
                    $this->info('Operation cancelled.');

                    return Command::SUCCESS;
                }
            }

            $imported = 0;
            $errors = 0;

            foreach ($data['settings'] as $settingData) {
                try {
                    Setting::updateOrCreate(
                        ['key' => $settingData['key']],
                        $settingData
                    );
                    $imported++;
                } catch (\Exception $e) {
                    $this->warn("Failed to import {$settingData['key']}: {$e->getMessage()}");
                    $errors++;
                }
            }

            clear_settings_cache();

            $this->info('Import completed:');
            $this->info("  Imported: {$imported}");
            if ($errors > 0) {
                $this->warn("  Errors: {$errors}");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to import settings: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Create backup of settings
     */
    protected function backupSettings()
    {
        try {
            $backupPath = backup_settings();

            if ($backupPath) {
                $this->info('Backup created successfully: '.basename($backupPath));

                return Command::SUCCESS;
            } else {
                $this->error('Failed to create backup.');

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Restore settings from backup
     */
    protected function restoreSettings()
    {
        $file = $this->option('file');

        if (! $file) {
            $this->error('File path is required for restore action.');

            return Command::FAILURE;
        }

        if (! str_starts_with($file, '/')) {
            $file = storage_path("app/backups/{$file}");
        }

        if (! file_exists($file)) {
            $this->error("Backup file not found: {$file}");

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm('This will overwrite all current settings. Continue?')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        try {
            $success = restore_settings($file);

            if ($success) {
                $this->info('Settings restored successfully from: '.basename($file));

                return Command::SUCCESS;
            } else {
                $this->error('Failed to restore settings.');

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Restore failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Clear settings cache
     */
    protected function clearCache()
    {
        try {
            clear_settings_cache();
            $this->info('Settings cache cleared successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clear cache: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Show help information
     */
    protected function showHelp()
    {
        $this->line('');
        $this->comment('Available actions:');
        $this->line('  list          List all settings (use --group to filter)');
        $this->line('  get           Get a specific setting value');
        $this->line('  set           Set a setting value');
        $this->line('  delete        Delete a setting');
        $this->line('  export        Export settings to file');
        $this->line('  import        Import settings from file');
        $this->line('  backup        Create backup of all settings');
        $this->line('  restore       Restore settings from backup');
        $this->line('  clear-cache   Clear settings cache');
        $this->line('');
        $this->comment('Examples:');
        $this->line('  php artisan settings:manage list --group=general');
        $this->line('  php artisan settings:manage get app_name');
        $this->line('  php artisan settings:manage set app_name "My College" --type=text');
        $this->line('  php artisan settings:manage export --file=my-settings.json');
        $this->line('  php artisan settings:manage import --file=my-settings.json --force');
        $this->line('  php artisan settings:manage backup');
        $this->line('  php artisan settings:manage restore --file=settings-backup-2024-01-01.json');
        $this->line('');
    }
}
