<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\Setting;

class BackupController extends Controller
{
    public function index()
    {
        try {
            // Get both spatie backups and settings backups
            $spatieBackups = $this->getSpatieBackups();
            $settingsBackups = $this->getSettingsBackups();
            
            // Get backup configuration
            $backupConfig = $this->getBackupConfiguration();
            
            return view('admin.backups.index', compact('spatieBackups', 'settingsBackups', 'backupConfig'));
        } catch (\Exception $e) {
            \Log::error('Backup index error: ' . $e->getMessage());
            return view('admin.backups.index', [
                'spatieBackups' => [],
                'settingsBackups' => [],
                'backupConfig' => $this->getDefaultBackupConfig()
            ]);
        }
    }

    public function create(Request $request)
    {
        // Add detailed debugging
        \Log::info('Backup create request', [
            'type' => $request->input('type'),
            'all_input' => $request->all(),
            'method' => $request->method()
        ]);

        // Validate the request
        $request->validate([
            'type' => 'required|in:db,files,settings'
        ]);

        try {
            // Set execution limits
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $type = $request->input('type');

            switch ($type) {
                case 'db':
                    return $this->createDatabaseBackup();
                case 'files':
                    return $this->createFilesBackup();
                case 'settings':
                    return $this->createSettingsBackup();
                default:
                    return redirect()->back()->with('error', 'Invalid backup type: ' . $type);
            }
        } catch (\Exception $e) {
            \Log::error('Backup creation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    private function createDatabaseBackup()
    {
        try {
            $exitCode = Artisan::call('backup:run', ['--only-db' => true]);
            
            if ($exitCode === 0) {
                return redirect()->back()->with('success', 'Database backup created successfully!');
            } else {
                $output = Artisan::output();
                \Log::error('Database backup failed', ['output' => $output]);
                return redirect()->back()->with('error', 'Database backup failed. Check logs for details.');
            }
        } catch (\Exception $e) {
            \Log::error('Database backup exception: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Database backup failed: ' . $e->getMessage());
        }
    }

    private function createFilesBackup()
    {
        try {
            $exitCode = Artisan::call('backup:run', ['--only-files' => true]);
            
            if ($exitCode === 0) {
                return redirect()->back()->with('success', 'Files backup created successfully!');
            } else {
                $output = Artisan::output();
                \Log::error('Files backup failed', ['output' => $output]);
                return redirect()->back()->with('error', 'Files backup failed. Check logs for details.');
            }
        } catch (\Exception $e) {
            \Log::error('Files backup exception: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Files backup failed: ' . $e->getMessage());
        }
    }

    private function createSettingsBackup()
    {
        try {
            $backupPath = backup_settings();
            
            if ($backupPath) {
                return redirect()->back()->with('success', 'Settings backup created successfully!');
            } else {
                return redirect()->back()->with('error', 'Settings backup failed.');
            }
        } catch (\Exception $e) {
            \Log::error('Settings backup exception: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Settings backup failed: ' . $e->getMessage());
        }
    }

    public function download($fileName)
    {
        try {
            // Check if it's a spatie backup
            $disks = config('backup.backup.destination.disks', ['local']);
            $disk = Storage::disk($disks[0]);
            $appName = config('backup.backup.name', config('app.name', 'Laravel'));
            $spatePath = $appName . '/' . $fileName;

            if ($disk->exists($spatePath)) {
                return $disk->download($spatePath);
            }

            // Check if it's a settings backup
            $settingsPath = storage_path('app/backups/' . $fileName);
            if (file_exists($settingsPath)) {
                return response()->download($settingsPath);
            }

            return redirect()->back()->with('error', 'Backup file not found.');
        } catch (\Exception $e) {
            \Log::error('Backup download error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to download backup: ' . $e->getMessage());
        }
    }
    
    public function destroy($fileName)
    {
        try {
            // Try spatie backup first
            $disks = config('backup.backup.destination.disks', ['local']);
            $disk = Storage::disk($disks[0]);
            $appName = config('backup.backup.name', config('app.name', 'Laravel'));
            $spatePath = $appName . '/' . $fileName;

            if ($disk->exists($spatePath)) {
                $disk->delete($spatePath);
                return redirect()->back()->with('success', 'Backup file deleted successfully.');
            }

            // Try settings backup
            $settingsPath = storage_path('app/backups/' . $fileName);
            if (file_exists($settingsPath) && str_starts_with($fileName, 'settings-backup-')) {
                unlink($settingsPath);
                return redirect()->back()->with('success', 'Settings backup deleted successfully.');
            }

            return redirect()->back()->with('error', 'Backup file not found.');
        } catch (\Exception $e) {
            \Log::error('Backup deletion error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'auto_backup' => 'boolean',
            'backup_frequency' => 'in:daily,weekly,monthly',
            'backup_retention_days' => 'integer|min:1|max:365',
            'auto_cleanup' => 'boolean',
            'backup_notifications' => 'boolean',
            'notification_email' => 'nullable|email'
        ]);

        try {
            foreach ($request->only(['auto_backup', 'backup_frequency', 'backup_retention_days', 'auto_cleanup', 'backup_notifications', 'notification_email']) as $key => $value) {
                update_setting($key, $value);
            }

            return redirect()->back()->with('success', 'Backup settings updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Backup settings update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    public function restoreSettings(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:json'
        ]);

        try {
            $success = restore_settings($request->file('backup_file')->path());
            
            if ($success) {
                return redirect()->back()->with('success', 'Settings restored successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to restore settings from backup file.');
            }
        } catch (\Exception $e) {
            \Log::error('Settings restore error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    public function testBackup(Request $request)
    {
        try {
            $type = $request->input('type', 'db');
            
            // Test backup command without actually creating files
            $command = $type === 'db' ? 'backup:run --only-db --dry-run' : 'backup:run --only-files --dry-run';
            
            $exitCode = Artisan::call($command);
            $output = Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Backup test successful!' : 'Backup test failed.',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ]);
        }
    }

    private function getSpatieBackups()
    {
        try {
            $disks = config('backup.backup.destination.disks', ['local']);
            $disk = Storage::disk($disks[0]);
            $appName = config('backup.backup.name', config('app.name', 'Laravel'));
            
            $files = $disk->files($appName);
            $backups = [];
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                    $backups[] = [
                        'name' => basename($file),
                        'type' => $this->determineBackupType(basename($file)),
                        'size' => $this->formatBytes($disk->size($file)),
                        'date' => Carbon::createFromTimestamp($disk->lastModified($file))->format('M d, Y H:i'),
                        'category' => 'spatie'
                    ];
                }
            }
            
            return collect($backups)->sortByDesc('date')->values()->all();
        } catch (\Exception $e) {
            \Log::error('Error getting spatie backups: ' . $e->getMessage());
            return [];
        }
    }

    private function getSettingsBackups()
    {
        try {
            $backupPath = storage_path('app/backups');
            $backups = [];
            
            if (is_dir($backupPath)) {
                $files = glob($backupPath . '/settings-backup-*.json');
                
                foreach ($files as $file) {
                    $backups[] = [
                        'name' => basename($file),
                        'type' => 'Settings',
                        'size' => $this->formatBytes(filesize($file)),
                        'date' => date('M d, Y H:i', filemtime($file)),
                        'category' => 'settings'
                    ];
                }
            }
            
            return collect($backups)->sortByDesc('date')->values()->all();
        } catch (\Exception $e) {
            \Log::error('Error getting settings backups: ' . $e->getMessage());
            return [];
        }
    }

    private function getBackupConfiguration()
    {
        return [
            'auto_backup' => setting('auto_backup', false, 'bool'),
            'backup_frequency' => setting('backup_frequency', 'daily'),
            'backup_retention_days' => setting('backup_retention_days', 30),
            'auto_cleanup' => setting('auto_cleanup', true, 'bool'),
            'backup_notifications' => setting('backup_notifications', false, 'bool'),
            'notification_email' => setting('notification_email', ''),
            'disk_usage' => $this->getDiskUsage(),
            'last_backup' => $this->getLastBackupInfo()
        ];
    }

    private function getDefaultBackupConfig()
    {
        return [
            'auto_backup' => false,
            'backup_frequency' => 'daily',
            'backup_retention_days' => 30,
            'auto_cleanup' => true,
            'backup_notifications' => false,
            'notification_email' => '',
            'disk_usage' => ['used' => 0, 'total' => 0, 'percentage' => 0],
            'last_backup' => null
        ];
    }

    private function getDiskUsage()
    {
        try {
            $path = storage_path('app');
            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);
            $usedSpace = $totalSpace - $freeSpace;
            
            return [
                'used' => $this->formatBytes($usedSpace),
                'total' => $this->formatBytes($totalSpace),
                'percentage' => round(($usedSpace / $totalSpace) * 100, 2)
            ];
        } catch (\Exception $e) {
            return ['used' => 'Unknown', 'total' => 'Unknown', 'percentage' => 0];
        }
    }

    private function getLastBackupInfo()
    {
        $allBackups = array_merge($this->getSpatieBackups(), $this->getSettingsBackups());
        if (empty($allBackups)) {
            return null;
        }
        
        return collect($allBackups)->sortByDesc('date')->first();
    }

    private function determineBackupType($filename)
    {
        if (str_contains($filename, 'db')) {
            return 'Database';
        } elseif (str_contains($filename, 'files')) {
            return 'Files';
        } else {
            return 'Full';
        }
    }

    public function cleanupBackups(Request $request)
    {
        try {
            $retentionDays = setting('backup_retention_days', 30);
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            $deletedCount = 0;

            // Cleanup spatie backups
            $disks = config('backup.backup.destination.disks', ['local']);
            $disk = Storage::disk($disks[0]);
            $appName = config('backup.backup.name', config('app.name', 'Laravel'));
            $files = $disk->files($appName);

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                    $fileDate = Carbon::createFromTimestamp($disk->lastModified($file));
                    if ($fileDate->lt($cutoffDate)) {
                        $disk->delete($file);
                        $deletedCount++;
                    }
                }
            }

            // Cleanup settings backups
            $backupPath = storage_path('app/backups');
            if (is_dir($backupPath)) {
                $files = glob($backupPath . '/settings-backup-*.json');
                foreach ($files as $file) {
                    $fileDate = Carbon::createFromTimestamp(filemtime($file));
                    if ($fileDate->lt($cutoffDate)) {
                        unlink($file);
                        $deletedCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$deletedCount} old backup files.",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Backup cleanup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ]);
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}