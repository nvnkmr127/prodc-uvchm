<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| UVCHM Portal - Simplified Scheduled Tasks (No Overlapping)
|--------------------------------------------------------------------------
*/

/**
 * Simple, reliable scheduler logger
 */
if (!function_exists('logSchedulerActivity')) {
    function logSchedulerActivity(string $command, string $description, string $phase, array $additionalData = []): void {
        try {
            $timestamp = Carbon::now();
            $memory = round(memory_get_usage(true) / 1024 / 1024, 2);
            
            $logEntry = "[{$timestamp->format('Y-m-d H:i:s')}] {$command} | {$phase} | {$description} | Memory: {$memory}MB";
            
            if (!empty($additionalData)) {
                $logEntry .= " | Data: " . json_encode($additionalData);
            }
            
            $logEntry .= PHP_EOL;
            
            // Ensure logs directory exists
            $logsDir = storage_path('logs');
            if (!is_dir($logsDir)) {
                mkdir($logsDir, 0755, true);
            }
            
            // Write to main scheduler log
            $logFile = storage_path('logs/scheduler.log');
            error_log($logEntry, 3, $logFile);
            
            // Also use Laravel's built-in logging for critical events
            if ($phase === 'FAILED') {
                Log::error("Scheduler: {$command} failed", [
                    'description' => $description,
                    'data' => $additionalData
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Scheduler logging failed', [
                'command' => $command,
                'error' => $e->getMessage()
            ]);
        }
    }
}

/**
 * System health monitoring
 */
if (!function_exists('checkSystemHealth')) {
    function checkSystemHealth(): array {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toDateTimeString(),
            'issues' => []
        ];
        
        try {
            // Database check
            DB::connection()->getPdo();
            $health['database'] = 'connected';
            
            // Disk space check
            $freeSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usagePercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
            
            $health['disk_usage_percent'] = $usagePercent;
            
            if ($usagePercent > 90) {
                $health['issues'][] = "Disk usage critical: {$usagePercent}%";
                $health['status'] = 'critical';
            } elseif ($usagePercent > 80) {
                $health['issues'][] = "Disk usage warning: {$usagePercent}%";
                $health['status'] = 'warning';
            }
            
        } catch (\Exception $e) {
            $health['status'] = 'error';
            $health['issues'][] = "Health check failed: " . $e->getMessage();
        }
        
        return $health;
    }
}

/**
 * Ensure required directories exist
 */
if (!function_exists('ensureDirectoriesExist')) {
    function ensureDirectoriesExist(): void {
        $directories = [
            storage_path('logs'),
            storage_path('app/backups'),
            storage_path('framework/cache'),
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
}

/**
 * Helper function to add directory to ZIP
 */
if (!function_exists('addDirectoryToZip')) {
    function addDirectoryToZip($zip, $directory, $localPath) {
        $fileCount = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = $localPath . '/' . substr($filePath, strlen($directory) + 1);
                
                // Skip certain file types
                $skipExtensions = ['log', 'cache', 'tmp'];
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                if (!in_array($extension, $skipExtensions)) {
                    $zip->addFile($filePath, $relativePath);
                    $fileCount++;
                }
            }
        }
        
        return $fileCount;
    }
}

// Ensure directories exist before any scheduled tasks run
ensureDirectoriesExist();

/*
|--------------------------------------------------------------------------
| PAYMENT REMINDER SYSTEM (Simplified)
|--------------------------------------------------------------------------
*/

Schedule::command('reminders:process')
    ->everyFifteenMinutes()
    ->between('08:00', '18:00')
    ->name('payment-reminders-process')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('reminders:process', 'Payment Reminders Processing', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('reminders:process', 'Payment Reminders Processing', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('reminders:process', 'Payment Reminders Processing', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/payment-reminders.log'));

Schedule::command('payments:enhanced-reminders')
    ->dailyAt('09:00')
    ->name('enhanced-payment-reminders')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('payments:enhanced-reminders', 'Enhanced Payment Reminders', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('payments:enhanced-reminders', 'Enhanced Payment Reminders', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('payments:enhanced-reminders', 'Enhanced Payment Reminders', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/fee-reminders.log'));

Schedule::command('app:send-follow-up-reminders')
    ->dailyAt('10:00')
    ->name('follow-up-reminders')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('app:send-follow-up-reminders', 'Follow-up Reminders', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('app:send-follow-up-reminders', 'Follow-up Reminders', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('app:send-follow-up-reminders', 'Follow-up Reminders', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/follow-up-reminders.log'));

Schedule::command('app:send-fee-reminders')
    ->dailyAt('09:00')
    ->name('standard-fee-reminders')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('app:send-fee-reminders', 'Standard Fee Reminders', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('app:send-fee-reminders', 'Standard Fee Reminders', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('app:send-fee-reminders', 'Standard Fee Reminders', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/app-fee-reminders.log'));

// Urgent payment reminders (twice daily)
Schedule::command('payments:enhanced-reminders', ['--fee-type=urgent'])
    ->twiceDaily(10, 16)
    ->name('urgent-payment-reminders')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('payments:enhanced-reminders --urgent', 'Urgent Payment Reminders', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('payments:enhanced-reminders --urgent', 'Urgent Payment Reminders', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('payments:enhanced-reminders --urgent', 'Urgent Payment Reminders', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/urgent-reminders.log'));

/*
|--------------------------------------------------------------------------
| Daily Absent Alert Schedule
|--------------------------------------------------------------------------
*/
Schedule::command('attendance:send-daily-absent-webhook')
    ->everyThirtyMinutes() // Checks every 30 mins
    ->between('09:00', '17:30') // Only during college hours
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('attendance:send-daily-absent-webhook', 'Daily Absent Webhook Check', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('attendance:send-daily-absent-webhook', 'Daily Absent Webhook Check', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('attendance:send-daily-absent-webhook', 'Daily Absent Webhook Check', 'FAILED');
    });

// Tuition fee reminders (high priority)
Schedule::command('payments:enhanced-reminders', ['--fee-type=tuition_fee'])
    ->dailyAt('09:30')
    ->name('tuition-fee-reminders')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('payments:enhanced-reminders --tuition', 'Tuition Fee Reminders', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('payments:enhanced-reminders --tuition', 'Tuition Fee Reminders', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('payments:enhanced-reminders --tuition', 'Tuition Fee Reminders', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/tuition-reminders.log'));

// Daily webhook summary
Schedule::command('webhook:daily-summary')
    ->dailyAt('17:00')
    ->days([1, 2, 3, 4, 5, 6]) // Monday through Saturday
    ->name('daily-summary-webhook')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('webhook:daily-summary', 'Daily Summary Webhook', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('webhook:daily-summary', 'Daily Summary Webhook', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('webhook:daily-summary', 'Daily Summary Webhook', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/daily-summary-webhook.log'));

// Defaulter analysis
Schedule::command('defaulters:analyze')
    ->dailyAt('08:00')
    ->name('defaulter-analysis')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('defaulters:analyze', 'Defaulter Analysis', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('defaulters:analyze', 'Defaulter Analysis', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('defaulters:analyze', 'Defaulter Analysis', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/defaulter-analysis.log'));

/*
|--------------------------------------------------------------------------
| FIXED BACKUP SYSTEM - PROPERLY IMPLEMENTED
|--------------------------------------------------------------------------
*/

// Settings backup - Every 14 days (FIXED)
Schedule::call(function () {
    logSchedulerActivity('backup:settings', 'Settings Backup (14-day schedule)', 'STARTING');
    
    try {
        // Check if it's time for settings backup (every 14 days)
        $lastBackupSetting = null;
        $shouldRunBackup = false;
        
        try {
            if (class_exists('App\Models\Setting')) {
                $lastBackupSetting = \App\Models\Setting::where('key', 'last_settings_backup_date')->first();
            } else {
                $lastBackupSetting = DB::table('settings')->where('key', 'last_settings_backup_date')->first();
            }
            
            if (!$lastBackupSetting || !$lastBackupSetting->value) {
                $shouldRunBackup = true; // No previous backup
            } else {
                $lastBackupDate = Carbon::parse($lastBackupSetting->value);
                $daysSinceLastBackup = $lastBackupDate->diffInDays(Carbon::now());
                $shouldRunBackup = $daysSinceLastBackup >= 14;
            }
        } catch (Exception $e) {
            $shouldRunBackup = true; // Run backup if we can't determine last backup date
        }
        
        if (!$shouldRunBackup) {
            logSchedulerActivity('backup:settings', 'Settings backup not due yet (14-day interval)', 'SKIPPED');
            return;
        }
        
        // Ensure backup directory exists
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "settings-backup-{$timestamp}.json";
        $backupPath = $backupDir . '/' . $filename;

        // Get all settings from database
        $settings = [];
        
        try {
            // Try to get settings from Settings model
            if (class_exists('App\Models\Setting')) {
                $settingsData = \App\Models\Setting::all();
                foreach ($settingsData as $setting) {
                    // Skip sensitive/encrypted settings
                    if (!in_array($setting->key, ['google_drive_credentials', 'encryption_key', 'app_key'])) {
                        $settings[$setting->key] = $setting->value;
                    }
                }
            }
        } catch (Exception $e) {
            // If Settings model doesn't exist, try config table
            try {
                $configData = DB::table('settings')->get();
                foreach ($configData as $config) {
                    // Skip sensitive settings
                    if (!in_array($config->key, ['google_drive_credentials', 'encryption_key', 'app_key'])) {
                        $settings[$config->key] = $config->value;
                    }
                }
            } catch (Exception $e2) {
                throw new Exception('Unable to find settings in database');
            }
        }

        if (!empty($settings)) {
            $backupData = [
                'backup_info' => [
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'backup_type' => 'settings',
                    'app_version' => config('app.version', '1.0'),
                    'total_settings' => count($settings)
                ],
                'settings' => $settings
            ];

            if (file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT)) !== false) {
                // Update last backup date
                try {
                    if (class_exists('App\Models\Setting')) {
                        \App\Models\Setting::updateOrCreate(
                            ['key' => 'last_settings_backup_date'],
                            ['value' => Carbon::now()->toDateTimeString()]
                        );
                    } else {
                        DB::table('settings')->updateOrInsert(
                            ['key' => 'last_settings_backup_date'],
                            ['value' => Carbon::now()->toDateTimeString()]
                        );
                    }
                } catch (Exception $e) {
                    // Continue even if we can't update the setting
                    Log::warning('Could not update last_settings_backup_date setting', ['error' => $e->getMessage()]);
                }
                
                logSchedulerActivity('backup:settings', 'Settings Backup (14-day schedule)', 'COMPLETED', [
                    'file' => $filename,
                    'settings_count' => count($settings),
                    'size_kb' => round(filesize($backupPath) / 1024, 2)
                ]);
            } else {
                throw new Exception('Failed to write backup file');
            }
        } else {
            throw new Exception('No settings found to backup');
        }

    } catch (Exception $e) {
        logSchedulerActivity('backup:settings', 'Settings Backup (14-day schedule)', 'FAILED');
        Log::error('Settings backup failed: ' . $e->getMessage());
    }
})
    ->dailyAt('01:00') // Check daily but only backup every 14 days
    ->name('settings-backup-biweekly')
    ->withoutOverlapping()
    ->description('Settings backup every 14 days');

// Database backup (Daily - IMPROVED)
Schedule::call(function () {
    logSchedulerActivity('backup:database', 'Daily Database Backup', 'STARTING');
    
    try {
        // Check if backups are enabled
        $backupEnabled = '1'; // Default to enabled
        try {
            if (class_exists('App\Models\Setting')) {
                $backupEnabled = \App\Models\Setting::where('key', 'auto_backup')->value('value') ?? '1';
            } else {
                $backupEnabled = DB::table('settings')->where('key', 'auto_backup')->value('value') ?? '1';
            }
        } catch (Exception $e) {
            // Continue with backup if we can't check the setting
        }
        
        if ($backupEnabled !== '1') {
            logSchedulerActivity('backup:database', 'Backups disabled in settings', 'SKIPPED');
            return;
        }
        
        // Ensure backup directory exists
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Generate backup filename
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_daily_{$timestamp}.sql";
        $backupPath = $backupDir . '/' . $filename;

        // Get database config
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port") ?: 3306;

        // Try multiple mysqldump paths
        $mysqldumpPaths = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            'mysqldump' // Try PATH
        ];
        
        $mysqldumpSuccess = false;
        
        foreach ($mysqldumpPaths as $mysqldumpPath) {
            // Check if mysqldump is available
            $checkCommand = "which $mysqldumpPath 2>/dev/null";
            exec($checkCommand, $output, $returnCode);
            
            if ($returnCode === 0 || is_executable($mysqldumpPath)) {
                $command = sprintf(
                    '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --add-drop-table %s > %s 2>&1',
                    $mysqldumpPath,
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($backupPath)
                );

                $output = [];
                $returnCode = 0;
                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($backupPath) && filesize($backupPath) > 1000) {
                    $mysqldumpSuccess = true;
                    $backupSize = round(filesize($backupPath) / 1024 / 1024, 2);
                    logSchedulerActivity('backup:database', 'Database Backup via mysqldump', 'COMPLETED', [
                        'method' => 'mysqldump',
                        'file' => $filename,
                        'size_mb' => $backupSize,
                        'mysqldump_path' => $mysqldumpPath
                    ]);
                    break;
                }
            }
        }

        // Fallback to PHP method if mysqldump failed
        if (!$mysqldumpSuccess) {
            Log::info('mysqldump not available, using PHP fallback method');
            
            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $tableColumn = 'Tables_in_' . $database;

            if (!empty($tables)) {
                $sql = "-- Database backup created at " . Carbon::now()->toDateTimeString() . "\n";
                $sql .= "-- Generated by PHP fallback method\n";
                $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
                $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
                $sql .= "SET time_zone = \"+00:00\";\n\n";

                foreach ($tables as $table) {
                    $tableName = $table->$tableColumn;
                    
                    try {
                        // Get table structure
                        $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                        if (!empty($createTable)) {
                            $sql .= "-- Structure for table `{$tableName}`\n";
                            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
                        }

                        // Get table data in chunks to prevent memory issues
                        $sql .= "-- Data for table `{$tableName}`\n";
                        
                        $chunkSize = 1000;
                        $offset = 0;
                        $hasData = false;
                        
                        do {
                            $rows = DB::table($tableName)->offset($offset)->limit($chunkSize)->get();
                            
                            if ($rows->isNotEmpty()) {
                                if (!$hasData) {
                                    $hasData = true;
                                    // Get column names
                                    $columns = array_keys((array) $rows->first());
                                    $columnsList = '`' . implode('`, `', $columns) . '`';
                                    $sql .= "INSERT INTO `{$tableName}` ({$columnsList}) VALUES\n";
                                }
                                
                                $values = [];
                                foreach ($rows as $row) {
                                    $rowData = (array) $row;
                                    $escapedValues = array_map(function($value) {
                                        if ($value === null) return 'NULL';
                                        return "'" . addslashes($value) . "'";
                                    }, $rowData);
                                    $values[] = '(' . implode(', ', $escapedValues) . ')';
                                }
                                
                                $sql .= implode(",\n", $values);
                                
                                if ($rows->count() < $chunkSize) {
                                    // Last chunk
                                    $sql .= ";\n\n";
                                } else {
                                    $sql .= ",\n";
                                }
                            }
                            
                            $offset += $chunkSize;
                        } while ($rows->count() === $chunkSize);
                        
                        if (!$hasData) {
                            $sql .= "-- No data found for table `{$tableName}`\n\n";
                        }
                        
                    } catch (Exception $tableError) {
                        $sql .= "-- Error backing up table `{$tableName}`: " . $tableError->getMessage() . "\n\n";
                        Log::warning("Error backing up table {$tableName}", ['error' => $tableError->getMessage()]);
                    }
                }

                $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

                if (file_put_contents($backupPath, $sql) !== false && filesize($backupPath) > 1000) {
                    $backupSize = round(filesize($backupPath) / 1024 / 1024, 2);
                    logSchedulerActivity('backup:database', 'Database Backup via PHP', 'COMPLETED', [
                        'method' => 'php_fallback',
                        'file' => $filename,
                        'size_mb' => $backupSize,
                        'tables_count' => count($tables)
                    ]);
                } else {
                    throw new Exception('Failed to write backup file or backup file is too small');
                }
            } else {
                throw new Exception('No tables found in database');
            }
        }

    } catch (Exception $e) {
        logSchedulerActivity('backup:database', 'Daily Database Backup', 'FAILED');
        Log::error('Database backup failed: ' . $e->getMessage());
    }
})
    ->dailyAt('02:00')
    ->name('database-backup-daily')
    ->withoutOverlapping(15)
    ->description('Daily database backup with improved fallback');

// Code backup (weekly on Sundays) - IMPROVED
Schedule::call(function () {
    logSchedulerActivity('backup:code', 'Weekly Code Backup', 'STARTING');
    
    try {
        // Check if ZIP extension is available
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not available - install php-zip extension');
        }

        // Ensure backup directory exists
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "code_backup_{$timestamp}.zip";
        $backupPath = $backupDir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('Failed to create ZIP file');
        }

        // Directories to include
        $includeDirs = [
            'app',
            'config', 
            'database/migrations',
            'database/seeders',
            'routes',
            'resources/views'
        ];

        // Important files to include
        $includeFiles = [
            'composer.json',
            'package.json',
            '.env.example'
        ];

        $fileCount = 0;

        // Add directories
        foreach ($includeDirs as $dir) {
            $fullPath = base_path($dir);
            if (is_dir($fullPath)) {
                $fileCount += addDirectoryToZip($zip, $fullPath, $dir);
            }
        }

        // Add individual files
        foreach ($includeFiles as $file) {
            $filePath = base_path($file);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $file);
                $fileCount++;
            }
        }

        $zip->close();

        if (file_exists($backupPath) && filesize($backupPath) > 0) {
            $backupSize = round(filesize($backupPath) / 1024 / 1024, 2);
            logSchedulerActivity('backup:code', 'Weekly Code Backup', 'COMPLETED', [
                'file' => $filename,
                'files_count' => $fileCount,
                'size_mb' => $backupSize
            ]);
        } else {
            throw new Exception('Backup file was not created or is empty');
        }

    } catch (Exception $e) {
        logSchedulerActivity('backup:code', 'Weekly Code Backup', 'FAILED');
        Log::error('Code backup failed: ' . $e->getMessage());
    }
})
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->name('code-backup-weekly')
    ->withoutOverlapping()
    ->description('Weekly code backup');
    
    

// Google Drive upload for database backups (FIXED)
Schedule::call(function () {
    logSchedulerActivity('backup:upload-database', 'Database Upload to Google Drive', 'STARTING');
    
    try {
        // Check if Google Drive is enabled
        $gdriveEnabled = '0';
        try {
            if (class_exists('App\Models\Setting')) {
                $gdriveEnabled = \App\Models\Setting::where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
            } else {
                $gdriveEnabled = DB::table('settings')->where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
            }
        } catch (Exception $e) {
            // Default to disabled if we can't check
        }
        
        if ($gdriveEnabled !== '1') {
            logSchedulerActivity('backup:upload-database', 'Google Drive upload disabled in settings', 'SKIPPED');
            return;
        }
        
        $backupDir = storage_path('app/backups');
        
        // Find recent database backups (last 3 hours to catch today's backup)
        $dbBackups = glob($backupDir . '/backup_daily_*.sql');
        $recentBackups = array_filter($dbBackups, function($file) {
            return filemtime($file) > (time() - 3*3600); // Last 3 hours
        });
        
        if (empty($recentBackups)) {
            logSchedulerActivity('backup:upload-database', 'No recent database backups found to upload', 'SKIPPED');
            return;
        }
        
        // Get the most recent backup
        usort($recentBackups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestBackup = $recentBackups[0];
        $filename = basename($latestBackup);
        $fileSize = round(filesize($latestBackup) / 1024 / 1024, 2);
        
        // Try multiple service classes (FIXED)
        $serviceClasses = [
            'App\Services\BackupService',
            'App\Services\GoogleDriveService'
        ];
        
        $uploadSuccess = false;
        
        foreach ($serviceClasses as $serviceClass) {
            if (class_exists($serviceClass)) {
                try {
                    $service = app($serviceClass);
                    
                    // Different method names for different services
                    if (method_exists($service, 'uploadToGoogleDrive')) {
                        $result = $service->uploadToGoogleDrive($latestBackup, $filename);
                    } elseif (method_exists($service, 'uploadFile')) {
                        $result = $service->uploadFile($latestBackup, $filename, 'backups/database');
                    } else {
                        continue;
                    }
                    
                    if (is_array($result) && isset($result['success']) && $result['success']) {
                        if (isset($result['skipped']) && $result['skipped']) {
                            logSchedulerActivity('backup:upload-database', 'Database already exists in Google Drive', 'SKIPPED', [
                                'file' => $filename,
                                'size_mb' => $fileSize,
                                'service' => $serviceClass
                            ]);
                        } else {
                            logSchedulerActivity('backup:upload-database', 'Database Upload to Google Drive', 'COMPLETED', [
                                'file' => $filename,
                                'size_mb' => $fileSize,
                                'upload_id' => $result['google_drive_id'] ?? null,
                                'service' => $serviceClass
                            ]);
                        }
                        $uploadSuccess = true;
                        break;
                    } elseif ($result && !is_array($result)) {
                        // Legacy service returned just an ID
                        logSchedulerActivity('backup:upload-database', 'Database Upload to Google Drive', 'COMPLETED', [
                            'file' => $filename,
                            'size_mb' => $fileSize,
                            'upload_id' => $result,
                            'service' => $serviceClass
                        ]);
                        $uploadSuccess = true;
                        break;
                    }
                    
                } catch (Exception $serviceError) {
                    Log::warning("Google Drive service {$serviceClass} failed", [
                        'error' => $serviceError->getMessage(),
                        'file' => $filename
                    ]);
                    continue;
                }
            }
        }
        
        if (!$uploadSuccess) {
            throw new Exception('No working Google Drive service found or all upload attempts failed');
        }

    } catch (Exception $e) {
        logSchedulerActivity('backup:upload-database', 'Database Upload to Google Drive', 'FAILED', [
            'error' => $e->getMessage()
        ]);
        Log::error('Database backup upload to Google Drive failed: ' . $e->getMessage());
    }
})
    ->dailyAt('02:30') // 30 minutes after database backup
    ->name('database-upload-gdrive')
    ->withoutOverlapping()
    ->description('Upload daily database backup to Google Drive');

// Google Drive upload for settings backups (FIXED)
Schedule::call(function () {
    logSchedulerActivity('backup:upload-settings', 'Settings Upload to Google Drive', 'STARTING');
    
    try {
        // Check if Google Drive is enabled
        $gdriveEnabled = '0';
        try {
            if (class_exists('App\Models\Setting')) {
                $gdriveEnabled = \App\Models\Setting::where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
            } else {
                $gdriveEnabled = DB::table('settings')->where('key', 'backup_gdrive_enabled')->value('value') ?? '0';
            }
        } catch (Exception $e) {
            // Default to disabled if we can't check
        }
        
        if ($gdriveEnabled !== '1') {
            logSchedulerActivity('backup:upload-settings', 'Google Drive upload disabled in settings', 'SKIPPED');
            return;
        }
        
        $backupDir = storage_path('app/backups');
        
        // Find recent settings backups (last 24 hours)
        $settingsBackups = glob($backupDir . '/settings-backup-*.json');
        $recentBackups = array_filter($settingsBackups, function($file) {
            return filemtime($file) > (time() - 24*3600); // Last 24 hours
        });
        
        if (empty($recentBackups)) {
            logSchedulerActivity('backup:upload-settings', 'No recent settings backups found to upload', 'SKIPPED');
            return;
        }
        
        // Get the most recent backup
        usort($recentBackups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestBackup = $recentBackups[0];
        $filename = basename($latestBackup);
        $fileSize = round(filesize($latestBackup) / 1024, 2); // Size in KB
        
        // Try multiple service classes (same fix as database)
        $serviceClasses = [
            'App\Services\BackupService',
            'App\Services\GoogleDriveService'
        ];
        
        $uploadSuccess = false;
        
        foreach ($serviceClasses as $serviceClass) {
            if (class_exists($serviceClass)) {
                try {
                    $service = app($serviceClass);
                    
                    // Different method names for different services
                    if (method_exists($service, 'uploadToGoogleDrive')) {
                        $result = $service->uploadToGoogleDrive($latestBackup, $filename);
                    } elseif (method_exists($service, 'uploadFile')) {
                        $result = $service->uploadFile($latestBackup, $filename, 'backups/settings');
                    } else {
                        continue;
                    }
                    
                    if (is_array($result) && isset($result['success']) && $result['success']) {
                        if (isset($result['skipped']) && $result['skipped']) {
                            logSchedulerActivity('backup:upload-settings', 'Settings backup already exists in Google Drive', 'SKIPPED', [
                                'file' => $filename,
                                'size_kb' => $fileSize,
                                'service' => $serviceClass
                            ]);
                        } else {
                            logSchedulerActivity('backup:upload-settings', 'Settings Upload to Google Drive', 'COMPLETED', [
                                'file' => $filename,
                                'size_kb' => $fileSize,
                                'upload_id' => $result['google_drive_id'] ?? null,
                                'service' => $serviceClass
                            ]);
                        }
                        $uploadSuccess = true;
                        break;
                    } elseif ($result && !is_array($result)) {
                        // Legacy service returned just an ID
                        logSchedulerActivity('backup:upload-settings', 'Settings Upload to Google Drive', 'COMPLETED', [
                            'file' => $filename,
                            'size_kb' => $fileSize,
                            'upload_id' => $result,
                            'service' => $serviceClass
                        ]);
                        $uploadSuccess = true;
                        break;
                    }
                    
                } catch (Exception $serviceError) {
                    Log::warning("Google Drive service {$serviceClass} failed", [
                        'error' => $serviceError->getMessage(),
                        'file' => $filename
                    ]);
                    continue;
                }
            }
        }
        
        if (!$uploadSuccess) {
            // Don't throw error for settings backup upload failure
            logSchedulerActivity('backup:upload-settings', 'No working Google Drive service found', 'SKIPPED');
        }

    } catch (Exception $e) {
        logSchedulerActivity('backup:upload-settings', 'Settings Upload to Google Drive', 'FAILED', [
            'error' => $e->getMessage()
        ]);
        Log::error('Settings backup upload to Google Drive failed: ' . $e->getMessage());
    }
})
    ->dailyAt('01:30') // 30 minutes after settings backup check
    ->name('settings-upload-gdrive')
    ->withoutOverlapping()
    ->description('Upload recent settings backup to Google Drive');

// Full backup weekly (combines DB and code)
Schedule::command('backup:run')
    ->weekly()
    ->sundays()
    ->at('02:45')
    ->name('full-backup-weekly')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('backup:run', 'Weekly Full Backup', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('backup:run', 'Weekly Full Backup', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('backup:run', 'Weekly Full Backup', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/full-backups.log'));

/*
|--------------------------------------------------------------------------
| SYSTEM MONITORING (Simplified)
|--------------------------------------------------------------------------
*/

// System health monitoring
Schedule::call(function () {
    $health = checkSystemHealth();
    
    logSchedulerActivity('system:health-check', 'System Health Check', 'MONITORING', [
        'status' => $health['status'],
        'issues_count' => count($health['issues'])
    ]);
    
    // Log critical issues
    if ($health['status'] === 'critical') {
        Log::critical('System Health Critical', $health);
    } elseif ($health['status'] === 'warning') {
        Log::warning('System Health Warning', $health);
    }
    
})->hourly()->name('system-health-monitor')->description('System health monitoring');

// Scheduler heartbeat
Schedule::call(function () {
    // Simple heartbeat to verify scheduler is running
    $heartbeatFile = storage_path('app/scheduler-heartbeat.txt');
    file_put_contents($heartbeatFile, now()->toDateTimeString());
    
    // Cache the heartbeat for quick access
    cache(['last_schedule_run' => now()], now()->addMinutes(10));
    
    // Only log heartbeat every hour to reduce noise
    if (now()->minute === 0) {
        logSchedulerActivity('scheduler:heartbeat', 'Scheduler Running', 'MONITORING', [
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    }
})->everyMinute()->name('scheduler-heartbeat')->description('Scheduler heartbeat');

// Backup cleanup (weekly) - IMPROVED
Schedule::call(function () {
    logSchedulerActivity('backup:clean', 'Backup Cleanup', 'STARTING');
    
    try {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            logSchedulerActivity('backup:clean', 'Backup directory does not exist', 'COMPLETED');
            return;
        }

        // Get retention settings
        $retentionDays = 30; // Default retention
        try {
            if (class_exists('App\Models\Setting')) {
                $retentionDays = (int) \App\Models\Setting::where('key', 'backup_retention_days')->value('value') ?? 30;
            } else {
                $retentionDays = (int) DB::table('settings')->where('key', 'backup_retention_days')->value('value') ?? 30;
            }
        } catch (Exception $e) {
            // Use default if we can't get setting
        }

        $allBackups = glob($backupDir . '/*');
        $cutoffTime = time() - ($retentionDays * 24 * 3600);
        
        $deletedCount = 0;
        $deletedSize = 0;
        
        foreach ($allBackups as $backupFile) {
            if (is_file($backupFile) && filemtime($backupFile) < $cutoffTime) {
                $fileSize = filesize($backupFile);
                if (unlink($backupFile)) {
                    $deletedCount++;
                    $deletedSize += $fileSize;
                }
            }
        }

        $remainingBackups = count(glob($backupDir . '/*'));
        
        logSchedulerActivity('backup:clean', 'Backup Cleanup', 'COMPLETED', [
            'deleted_files' => $deletedCount,
            'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
            'remaining_files' => $remainingBackups,
            'retention_days' => $retentionDays
        ]);

    } catch (Exception $e) {
        logSchedulerActivity('backup:clean', 'Backup Cleanup', 'FAILED');
        Log::error('Backup cleanup failed: ' . $e->getMessage());
    }
})->weekly()->name('backup-cleanup-weekly')->description('Cleanup old backup files');

// Backup health check
Schedule::command('backup:health-check', ['--notify'])
    ->dailyAt('08:00')
    ->name('backup-health-check')
    ->withoutOverlapping()
    ->before(function () {
        logSchedulerActivity('backup:health-check', 'Backup Health Check', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('backup:health-check', 'Backup Health Check', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('backup:health-check', 'Backup Health Check', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/backup-health.log'));

// Log rotation (daily)
Schedule::call(function () {
    try {
        $logFiles = [
            storage_path('logs/scheduler.log'),
            storage_path('logs/laravel.log'),
        ];
        
        $rotatedCount = 0;
        
        foreach ($logFiles as $logFile) {
            if (file_exists($logFile) && filesize($logFile) > 50 * 1024 * 1024) { // 50MB
                // Rotate the log file
                $rotatedFile = $logFile . '.' . date('Y-m-d-H-i-s');
                if (rename($logFile, $rotatedFile)) {
                    // Create new empty log file
                    touch($logFile);
                    chmod($logFile, 0644);
                    $rotatedCount++;
                }
            }
        }
        
        if ($rotatedCount > 0) {
            logSchedulerActivity('log:rotation', "Rotated {$rotatedCount} log files", 'COMPLETED');
        }
        
        // Clean old rotated logs (older than 30 days)
        $oldLogs = glob(storage_path('logs/*.log.*'));
        $cleanedCount = 0;
        foreach ($oldLogs as $oldLog) {
            if (filemtime($oldLog) < (time() - 30*24*3600)) {
                if (unlink($oldLog)) {
                    $cleanedCount++;
                }
            }
        }
        
        if ($cleanedCount > 0) {
            logSchedulerActivity('log:cleanup', "Cleaned {$cleanedCount} old log files", 'COMPLETED');
        }
        
    } catch (Exception $e) {
        Log::error('Log rotation failed', ['error' => $e->getMessage()]);
    }
})->daily()->at('04:00')->name('log-rotation-daily')->description('Log rotation and cleanup');

// ETimeOffice Auto-Sync (using the correct command)
Schedule::command('etimeoffice:auto-sync', ['--range=today'])
    ->everyFiveMinutes()
    ->name('etimeoffice-auto-sync')
    ->withoutOverlapping(10)
    ->when(function () {
        // Only run if ETimeOffice is enabled in settings
        try {
            if (class_exists('App\Models\Setting')) {
                return \App\Models\Setting::where('key', 'etimeoffice_enabled')->value('value') === '1';
            } else {
                return DB::table('settings')->where('key', 'etimeoffice_enabled')->value('value') === '1';
            }
        } catch (Exception $e) {
            return false;
        }
    })
    ->before(function () {
        logSchedulerActivity('etimeoffice:auto-sync', 'ETimeOffice Auto-Sync', 'STARTING');
    })
    ->after(function () {
        logSchedulerActivity('etimeoffice:auto-sync', 'ETimeOffice Auto-Sync', 'COMPLETED');
    })
    ->onFailure(function () {
        logSchedulerActivity('etimeoffice:auto-sync', 'ETimeOffice Auto-Sync', 'FAILED');
    })
    ->appendOutputTo(storage_path('logs/etimeoffice-sync.log'))
    ->description('ETimeOffice attendance auto-sync');
    
    
   /*
|--------------------------------------------------------------------------
| Notification Cleanup Schedule
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    // Delete notifications that are:
    // 1. Older than 3 days
    // 2. Have been read (read_by is not empty)
    // 3. Are user-specific (safer to delete than global ones)
    
    $deleted = SystemNotification::where('created_at', '<', now()->subDays(3))
        ->whereNotNull('user_id') // Only personal notifications
        ->whereNotNull('read_by') // Must be read
        ->delete();
        
    // Optional: Force clean very old notifications (e.g., 30 days) even if unread
    SystemNotification::where('created_at', '<', now()->subDays(30))->delete();
    
    \Illuminate\Support\Facades\Log::info("Cleaned up $deleted old notifications.");
    
})->dailyAt('04:00'); 
    
    

/*
|--------------------------------------------------------------------------
| BACKUP SCHEDULE SUMMARY
|--------------------------------------------------------------------------
| 
| Daily Schedule:
| - 01:00 AM: Check for settings backup (runs every 14 days)
| - 01:30 AM: Upload recent settings backup to Google Drive
| - 02:00 AM: Daily database backup
| - 02:30 AM: Upload database backup to Google Drive
| - 04:00 AM: Log rotation and cleanup
| - 08:00 AM: Backup health check
| 
| Weekly Schedule (Sundays):
| - 02:45 AM: Full backup (spatie/laravel-backup)
| - 03:00 AM: Code backup
| - Weekly: Cleanup old backup files
| 
| Settings Backup: Every 14 days (tracked automatically)
| Database Backup: Daily
| Code Backup: Weekly (Sundays)
| Google Drive Upload: Automatic for all backups (if enabled)
| 
|--------------------------------------------------------------------------
*/