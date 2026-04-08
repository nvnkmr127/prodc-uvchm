<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupService
{
    /**
     * Create a database backup with consistent naming
     *
     * @param  string  $type  Type of backup (daily, weekly, monthly, manual)
     * @return array
     */
    public function createDatabaseBackup($type = 'daily')
    {
        try {
            // Create consistent timestamp format
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');

            // Use consistent naming pattern: backup_{type}_{date}.sql
            $filename = "backup_{$type}_{$timestamp}.sql";
            $backupPath = storage_path("app/backups/{$filename}");

            // Ensure backup directory exists
            if (! file_exists(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }

            Log::info('Creating database backup', [
                'type' => $type,
                'filename' => $filename,
                'path' => $backupPath,
            ]);

            // Use improved PHP database backup method
            $this->createPHPDatabaseBackup($backupPath);

            // Verify backup file was created and has content
            if (! file_exists($backupPath) || filesize($backupPath) === 0) {
                throw new \Exception('Backup file was not created or is empty');
            }

            $fileSize = filesize($backupPath);

            Log::info('Database backup created successfully', [
                'filename' => $filename,
                'path' => $backupPath,
                'size' => $fileSize,
                'type' => $type,
                'size_mb' => round($fileSize / 1024 / 1024, 2),
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $backupPath,
                'size' => $fileSize,
                'type' => $type,
                'created_at' => Carbon::now(),
            ];

        } catch (\Exception $e) {
            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => $type,
            ];
        }
    }

    /**
     * Improved database backup using PHP PDO with better error handling
     */
    private function createPHPDatabaseBackup($backupPath)
    {
        try {
            // Check disk space first
            $freeSpace = disk_free_space(dirname($backupPath));
            if ($freeSpace < 100 * 1024 * 1024) { // Less than 100MB free
                throw new \Exception('Insufficient disk space. Only '.round($freeSpace / 1024 / 1024, 2).'MB available.');
            }

            $pdo = DB::connection()->getPdo();
            $dbName = config('database.connections.mysql.database');

            $sql = "-- Database Backup for {$dbName}\n";
            $sql .= '-- Generated on: '.date('Y-m-d H:i:s')."\n";
            $sql .= '-- Laravel Application: '.config('app.name')."\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
            $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
            $sql .= "SET time_zone = '+00:00';\n\n";

            // Get all tables
            $stmt = $pdo->query('SHOW TABLES');
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($tables)) {
                throw new \Exception('No tables found in database');
            }

            Log::info("Starting backup of {$dbName} with ".count($tables).' tables');

            foreach ($tables as $table) {
                Log::debug("Backing up table: {$table}");

                // Drop table if exists
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

                // Get CREATE TABLE statement with better error handling
                try {
                    $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                    $createTableRow = $stmt->fetch(\PDO::FETCH_ASSOC);

                    // Check if we got the expected result
                    if (! $createTableRow) {
                        Log::warning("Could not get CREATE TABLE for {$table}, skipping");

                        continue;
                    }

                    // Handle different possible keys returned by SHOW CREATE TABLE
                    $createTableSQL = null;
                    if (isset($createTableRow['Create Table'])) {
                        $createTableSQL = $createTableRow['Create Table'];
                    } elseif (isset($createTableRow['Create View'])) {
                        $createTableSQL = $createTableRow['Create View'];
                    } else {
                        // Try to get the value from the second column (index 1)
                        $values = array_values($createTableRow);
                        if (isset($values[1])) {
                            $createTableSQL = $values[1];
                        } else {
                            Log::warning("Unexpected SHOW CREATE TABLE result for {$table}: ".json_encode($createTableRow));

                            continue;
                        }
                    }

                    $sql .= $createTableSQL.";\n\n";

                } catch (\Exception $e) {
                    Log::warning("Failed to get CREATE TABLE for {$table}: ".$e->getMessage());

                    continue;
                }

                // Get table data with limit to avoid memory issues
                try {
                    $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                    $rowCount = $countStmt->fetchColumn();

                    if ($rowCount > 0) {
                        // For large tables, use chunking
                        $chunkSize = 1000;
                        $offset = 0;

                        while ($offset < $rowCount) {
                            $stmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}");
                            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                            if (! empty($rows)) {
                                if ($offset === 0) {
                                    // First chunk - add INSERT statement header
                                    $columns = array_keys($rows[0]);
                                    $columnList = '`'.implode('`, `', $columns).'`';
                                    $sql .= "INSERT INTO `{$table}` ({$columnList}) VALUES\n";
                                }

                                $insertValues = [];
                                foreach ($rows as $row) {
                                    $values = [];
                                    foreach ($row as $value) {
                                        if ($value === null) {
                                            $values[] = 'NULL';
                                        } else {
                                            $values[] = $pdo->quote($value);
                                        }
                                    }
                                    $insertValues[] = '('.implode(', ', $values).')';
                                }

                                if ($offset + $chunkSize >= $rowCount) {
                                    // Last chunk - end with semicolon
                                    $sql .= implode(",\n", $insertValues).";\n\n";
                                } else {
                                    // More chunks coming - end with comma
                                    $sql .= implode(",\n", $insertValues).",\n";
                                }
                            }

                            $offset += $chunkSize;
                        }
                    }

                } catch (\Exception $e) {
                    Log::warning("Failed to backup data for table {$table}: ".$e->getMessage());

                    // Continue with other tables
                    continue;
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            $sql .= "-- End of backup\n";

            // Write to file
            $bytesWritten = file_put_contents($backupPath, $sql);

            if ($bytesWritten === false) {
                throw new \Exception('Failed to write backup file');
            }

            Log::info('Backup file written successfully', [
                'bytes' => $bytesWritten,
                'tables_count' => count($tables),
                'size_mb' => round($bytesWritten / 1024 / 1024, 2),
            ]);

        } catch (\Exception $e) {
            Log::error('PHP database backup failed', [
                'error' => $e->getMessage(),
                'backup_path' => $backupPath,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a code backup with improved error handling
     *
     * @return array
     */
    public function createCodeBackup()
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "code_backup_{$timestamp}.zip";
            $backupPath = storage_path("app/backups/{$filename}");

            // Check if zip extension is available
            if (! class_exists('ZipArchive')) {
                Log::warning('Code backup skipped - ZipArchive not available');

                return [
                    'success' => false,
                    'error' => 'Code backup requires PHP zip extension to be installed',
                    'type' => 'code',
                ];
            }

            // Ensure backup directory exists
            if (! file_exists(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Failed to create zip file: '.$backupPath);
            }

            // Directories to include in code backup (only if they exist)
            $includeDirs = [
                'app',
                'config',
                'database/migrations',
                'database/seeders',
                'routes',
                'resources/views',
                'public',
            ];

            // Directories to exclude
            $excludeDirs = [
                'storage/logs',
                'storage/framework/cache',
                'storage/framework/sessions',
                'storage/app/backups',
                'vendor',
                'node_modules',
                '.git',
                '.env',
            ];

            $addedFiles = 0;
            $addedDirs = 0;

            foreach ($includeDirs as $dir) {
                $fullPath = base_path($dir);
                if (is_dir($fullPath)) {
                    Log::info("Adding directory to backup: {$dir}");
                    $result = $this->addDirectoryToZipSafely($zip, $fullPath, $dir, $excludeDirs);
                    $addedFiles += $result['files'];
                    $addedDirs += $result['dirs'];
                } else {
                    Log::info("Skipping non-existent directory: {$dir}");
                }
            }

            // Add important files (only if they exist)
            $importantFiles = [
                'composer.json',
                'package.json',
                '.env.example',
                'artisan',
                'README.md',
            ];

            foreach ($importantFiles as $file) {
                $filePath = base_path($file);
                if (file_exists($filePath) && is_file($filePath)) {
                    if ($zip->addFile($filePath, $file)) {
                        $addedFiles++;
                        Log::info("Added file to backup: {$file}");
                    } else {
                        Log::warning("Failed to add file to backup: {$file}");
                    }
                } else {
                    Log::info("Skipping non-existent file: {$file}");
                }
            }

            $zip->close();

            $fileSize = filesize($backupPath);

            if ($fileSize === 0) {
                throw new \Exception('Backup file is empty - no files were added');
            }

            Log::info('Code backup created successfully', [
                'filename' => $filename,
                'path' => $backupPath,
                'size' => $fileSize,
                'files_added' => $addedFiles,
                'dirs_added' => $addedDirs,
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $backupPath,
                'size' => $fileSize,
                'type' => 'code',
                'created_at' => Carbon::now(),
                'files_count' => $addedFiles,
                'dirs_count' => $addedDirs,
            ];

        } catch (\Exception $e) {
            Log::error('Code backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up failed backup file
            if (isset($backupPath) && file_exists($backupPath)) {
                unlink($backupPath);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => 'code',
            ];
        }
    }

    /**
     * Add directory to zip recursively with better error handling
     *
     * @param  ZipArchive  $zip
     * @param  string  $dir
     * @param  string  $zipDir
     * @param  array  $excludeDirs
     * @return array
     */
    private function addDirectoryToZipSafely($zip, $dir, $zipDir = '', $excludeDirs = [])
    {
        $filesAdded = 0;
        $dirsAdded = 0;

        if (! is_dir($dir)) {
            Log::warning("Directory does not exist: {$dir}");

            return ['files' => 0, 'dirs' => 0];
        }

        try {
            $files = scandir($dir);
            if ($files === false) {
                Log::warning("Cannot read directory: {$dir}");

                return ['files' => 0, 'dirs' => 0];
            }

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $dir.DIRECTORY_SEPARATOR.$file;
                $zipPath = $zipDir ? $zipDir.'/'.$file : $file;

                // Skip excluded directories/files
                $shouldExclude = false;
                foreach ($excludeDirs as $excludeDir) {
                    if (strpos($zipPath, $excludeDir) === 0 || strpos($file, '.') === 0) {
                        $shouldExclude = true;
                        break;
                    }
                }

                if ($shouldExclude) {
                    continue;
                }

                if (is_dir($filePath)) {
                    if ($zip->addEmptyDir($zipPath)) {
                        $dirsAdded++;
                        $result = $this->addDirectoryToZipSafely($zip, $filePath, $zipPath, $excludeDirs);
                        $filesAdded += $result['files'];
                        $dirsAdded += $result['dirs'];
                    }
                } elseif (is_file($filePath) && is_readable($filePath)) {
                    if ($zip->addFile($filePath, $zipPath)) {
                        $filesAdded++;
                    } else {
                        Log::warning("Failed to add file to zip: {$filePath}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error processing directory {$dir}: ".$e->getMessage());
        }

        return ['files' => $filesAdded, 'dirs' => $dirsAdded];
    }

    /**
     * Get list of local backups
     *
     * @return array
     */
    public function getLocalBackups()
    {
        $backupDir = storage_path('app/backups');
        $backups = [];

        if (is_dir($backupDir)) {
            $files = scandir($backupDir);

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $backupDir.DIRECTORY_SEPARATOR.$file;
                if (is_file($filePath)) {
                    $backups[] = [
                        'filename' => $file,
                        'path' => $filePath,
                        'size' => filesize($filePath),
                        'created_at' => Carbon::createFromTimestamp(filemtime($filePath)),
                        'type' => $this->getBackupType($file),
                    ];
                }
            }
        }

        // Sort by creation date (newest first)
        usort($backups, function ($a, $b) {
            return $b['created_at']->timestamp - $a['created_at']->timestamp;
        });

        return $backups;
    }

    /**
     * Restore database from backup file
     *
     * @param  string  $filename
     * @return array
     */
    public function restoreDatabaseBackup($filename)
    {
        try {
            $backupPath = storage_path("app/backups/{$filename}");

            // Check if backup file exists
            if (! file_exists($backupPath)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found: '.$filename,
                ];
            }

            // Verify it's a SQL file
            if (pathinfo($backupPath, PATHINFO_EXTENSION) !== 'sql') {
                return [
                    'success' => false,
                    'message' => 'Invalid backup file format. Only SQL files are supported.',
                ];
            }

            // Get database configuration
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            // Create mysql restore command
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($backupPath)
            );

            // Execute restore command
            $output = [];
            $returnCode = 0;
            exec($command.' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Database restore failed: '.implode('\n', $output));
            }

            Log::info('Database restored successfully', [
                'filename' => $filename,
                'path' => $backupPath,
            ]);

            return [
                'success' => true,
                'message' => 'Database restored successfully from '.$filename,
                'filename' => $filename,
            ];

        } catch (\Exception $e) {
            Log::error('Database restore failed', [
                'error' => $e->getMessage(),
                'filename' => $filename,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get list of backup files (alias for getLocalBackups for compatibility)
     *
     * @return array
     */
    public function getBackupsList()
    {
        return $this->getLocalBackups();
    }

    /**
     * Determine backup type from filename (if this method doesn't exist)
     *
     * @param  string  $filename
     * @return string
     */
    private function getBackupType($filename)
    {
        if (strpos($filename, 'backup_daily') !== false) {
            return 'Database';
        } elseif (strpos($filename, 'backup_manual') !== false) {
            return 'Database';
        } elseif (strpos($filename, 'backup_weekly') !== false) {
            return 'Database';
        } elseif (strpos($filename, 'settings-backup') !== false || strpos($filename, 'settings_backup') !== false) {
            return 'Settings';
        } elseif (strpos($filename, 'code_backup') !== false) {
            return 'Files';
        } elseif (pathinfo($filename, PATHINFO_EXTENSION) === 'zip') {
            return 'Files';
        } elseif (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
            return 'Database';
        }

        return 'Database'; // Default
    }

    /**
     * Delete old backups based on retention policy
     *
     * @param  int  $daysToKeep
     * @return int Number of deleted backups
     */
    public function cleanupOldBackups($daysToKeep = 30)
    {
        $backups = $this->getLocalBackups();
        $deletedCount = 0;
        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        foreach ($backups as $backup) {
            if ($backup['created_at']->lt($cutoffDate)) {
                if (unlink($backup['path'])) {
                    $deletedCount++;
                    Log::info('Old backup deleted', [
                        'filename' => $backup['filename'],
                        'created_at' => $backup['created_at'],
                    ]);
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Upload backup to Google Drive with improved error handling
     *
     * @param  string  $filePath
     * @param  string  $fileName
     * @return array
     */
    public function uploadToGoogleDrive($filePath, $fileName)
    {
        try {
            // Check if file exists and get size
            if (! file_exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'Backup file not found: '.$filePath,
                    'error_type' => 'file_not_found',
                ];
            }

            $fileSize = filesize($filePath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            Log::info('Starting Google Drive upload', [
                'filename' => $fileName,
                'file_size_bytes' => $fileSize,
                'file_size_mb' => $fileSizeMB,
            ]);

            // Get Google Drive client
            $client = $this->getGoogleDriveClient();
            if (! $client) {
                return [
                    'success' => false,
                    'error' => 'Google Drive client not available. Please check OAuth authorization.',
                    'error_type' => 'authentication',
                ];
            }

            $service = new GoogleDrive($client);

            // Get folder ID
            try {
                $folderId = $this->getGoogleDriveFolderId();
            } catch (\Exception $folderError) {
                return [
                    'success' => false,
                    'error' => 'Failed to access Google Drive folder: '.$folderError->getMessage(),
                    'error_type' => 'folder_access',
                ];
            }

            // Check if file already exists
            $existingFile = $this->findFileInGoogleDrive($service, $fileName, $folderId);
            if ($existingFile) {
                Log::info('File already exists in Google Drive, skipping upload', [
                    'filename' => $fileName,
                    'existing_file_id' => $existingFile->getId(),
                ]);

                return [
                    'success' => true,
                    'google_drive_id' => $existingFile->getId(),
                    'filename' => $fileName,
                    'message' => 'File already exists in Google Drive',
                    'skipped' => true,
                ];
            }

            // Create file metadata
            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $fileName,
                'parents' => [$folderId],
                'description' => 'Automated backup created on '.date('Y-m-d H:i:s'),
            ]);

            // Upload file
            $content = file_get_contents($filePath);

            $file = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => 'application/octet-stream',
                'uploadType' => 'multipart',
            ]);

            Log::info('File uploaded to Google Drive successfully', [
                'filename' => $fileName,
                'google_drive_id' => $file->getId(),
                'file_size_mb' => $fileSizeMB,
            ]);

            return [
                'success' => true,
                'google_drive_id' => $file->getId(),
                'filename' => $fileName,
                'file_size' => $fileSize,
            ];

        } catch (\Google\Service\Exception $googleError) {
            $errorDetails = json_decode($googleError->getMessage(), true);
            $errorMessage = 'Google Drive API error';

            if (isset($errorDetails['error']['message'])) {
                $errorMessage = $errorDetails['error']['message'];
            }

            Log::error('Google Drive API error during upload', [
                'filename' => $fileName,
                'error' => $googleError->getMessage(),
                'error_code' => $googleError->getCode(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_type' => 'api_error',
                'error_code' => $googleError->getCode(),
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive upload failed', [
                'filename' => $fileName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'general_error',
            ];
        }
    }

    /**
     * Check if file already exists in Google Drive
     */
    private function findFileInGoogleDrive($service, $fileName, $folderId)
    {
        try {
            $query = "name = '{$fileName}' and parents in '{$folderId}' and trashed = false";
            $results = $service->files->listFiles([
                'q' => $query,
                'pageSize' => 1,
                'fields' => 'files(id, name, size, createdTime)',
            ]);

            $files = $results->getFiles();

            return ! empty($files) ? $files[0] : null;

        } catch (\Exception $e) {
            Log::warning('Failed to check for existing file in Google Drive', [
                'filename' => $fileName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get Google Drive client - FIXED for 403 OAuth issues
     *
     * @return GoogleClient|null
     */
    public function getGoogleDriveClient()
    {
        try {
            // Get credentials
            $clientId = Setting::get('gdrive_client_id');
            $clientSecret = Setting::get('gdrive_client_secret');

            Log::info('Getting Google Drive credentials', [
                'client_id_exists' => ! empty($clientId),
                'client_secret_exists' => ! empty($clientSecret),
                'client_id_length' => $clientId ? strlen($clientId) : 0,
                'client_secret_length' => $clientSecret ? strlen($clientSecret) : 0,
            ]);

            if (! $clientId || ! $clientSecret) {
                Log::warning('Google Drive credentials not configured');

                return null;
            }

            $client = new GoogleClient;
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri(route('admin.backups.gdrive.callback'));
            $client->addScope(GoogleDrive::DRIVE_FILE);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            // IMPORTANT: Only proceed with API calls if we have a valid access token
            $accessToken = Setting::get('gdrive_access_token');
            if (! $accessToken) {
                Log::info('No access token found - OAuth authorization required');

                return null; // Don't try to make API calls without authorization
            }

            try {
                // Setting model automatically decodes JSON, so $accessToken is already an array
                $storedToken = is_array($accessToken) ? $accessToken : json_decode($accessToken, true);

                if (! $storedToken || ! is_array($storedToken)) {
                    Log::warning('Invalid stored token format');

                    return null;
                }

                $client->setAccessToken($storedToken);

                // Check if token is expired and refresh if needed
                if ($client->isAccessTokenExpired()) {
                    Log::info('Access token expired, attempting to refresh');

                    $refreshToken = isset($storedToken['refresh_token']) ?
                        $storedToken['refresh_token'] :
                        Setting::get('gdrive_refresh_token');

                    if (! $refreshToken) {
                        Log::warning('No refresh token available - re-authorization required');

                        return null;
                    }

                    try {
                        $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

                        if (isset($newToken['error'])) {
                            Log::error('Token refresh failed', ['error' => $newToken['error']]);
                            // Clear invalid tokens
                            Setting::remove('gdrive_access_token');
                            Setting::remove('gdrive_refresh_token');

                            return null;
                        }

                        // Preserve refresh token if not included in response
                        if (! isset($newToken['refresh_token']) && $refreshToken) {
                            $newToken['refresh_token'] = $refreshToken;
                        }

                        // Save new token
                        Setting::set('gdrive_access_token', json_encode($newToken), [
                            'group' => 'backup',
                            'type' => 'json',
                            'description' => 'Google Drive access token',
                            'is_encrypted' => true,
                        ]);

                        $client->setAccessToken($newToken);
                        Log::info('Access token refreshed successfully');

                    } catch (\Exception $refreshError) {
                        Log::error('Token refresh exception', ['error' => $refreshError->getMessage()]);
                        // Clear invalid tokens
                        Setting::remove('gdrive_access_token');
                        Setting::remove('gdrive_refresh_token');

                        return null;
                    }
                }

            } catch (\Exception $tokenError) {
                Log::error('Error processing stored token', ['error' => $tokenError->getMessage()]);

                return null;
            }

            Log::info('Google Drive client created successfully with valid token');

            return $client;

        } catch (\Exception $e) {
            Log::error('Failed to create Google Drive client', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get Google Drive folder ID - UPDATED to prevent 403 errors
     */
    private function getGoogleDriveFolderId()
    {
        // Check if we already have a folder ID
        $folderId = Setting::get('google_drive_folder_id');
        if ($folderId) {
            return $folderId;
        }

        // Only try to create folder if we have proper authorization
        $client = $this->getGoogleDriveClient();
        if (! $client) {
            throw new \Exception('Google Drive not authorized. Please complete OAuth authorization first.');
        }

        // Check if client has valid access token
        if (! $client->getAccessToken()) {
            throw new \Exception('No access token available. Please authorize Google Drive first.');
        }

        if ($client->isAccessTokenExpired()) {
            throw new \Exception('Access token expired. Please re-authorize Google Drive.');
        }

        try {
            $service = new GoogleDrive($client);
            $folderName = Setting::get('gdrive_folder_name', 'College-Backups');

            // Check if folder already exists
            $response = $service->files->listFiles([
                'q' => "name='{$folderName}' and mimeType='application/vnd.google-apps.folder'",
                'fields' => 'files(id, name)',
            ]);

            $files = $response->getFiles();

            if (count($files) > 0) {
                $folderId = $files[0]->getId();
                Log::info('Found existing Google Drive backup folder', [
                    'folder_name' => $folderName,
                    'folder_id' => $folderId,
                ]);
            } else {
                // Create folder
                $fileMetadata = new \Google\Service\Drive\DriveFile([
                    'name' => $folderName,
                    'mimeType' => 'application/vnd.google-apps.folder',
                ]);

                $folder = $service->files->create($fileMetadata, [
                    'fields' => 'id',
                ]);

                $folderId = $folder->getId();
                Log::info('Created new Google Drive backup folder', [
                    'folder_name' => $folderName,
                    'folder_id' => $folderId,
                ]);
            }

            // Store folder ID
            Setting::set('google_drive_folder_id', $folderId, [
                'group' => 'backup',
                'type' => 'text',
                'description' => 'Google Drive folder ID for backups',
            ]);

            return $folderId;

        } catch (\Google\Service\Exception $googleError) {
            $errorDetails = json_decode($googleError->getMessage(), true);
            $errorMessage = isset($errorDetails['error']['message']) ?
                $errorDetails['error']['message'] :
                $googleError->getMessage();

            Log::error('Google Drive API error', [
                'error' => $errorMessage,
                'code' => $googleError->getCode(),
            ]);

            throw new \Exception('Google Drive API error: '.$errorMessage);
        } catch (\Exception $e) {
            Log::error('Failed to create/get Google Drive folder', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get Google Drive backups - UPDATED to prevent unauthorized calls
     *
     * @return array
     */
    public function getGoogleDriveBackups()
    {
        try {
            $client = $this->getGoogleDriveClient();

            // Don't try to make API calls without proper authorization
            if (! $client) {
                Log::info('Google Drive client not available - skipping backup list');

                return [];
            }

            // Verify we have valid access
            if (! $client->getAccessToken() || $client->isAccessTokenExpired()) {
                Log::info('No valid access token - skipping Google Drive backup list');

                return [];
            }

            $service = new GoogleDrive($client);

            // Try to get folder ID, but don't create it if it doesn't exist
            $folderId = Setting::get('google_drive_folder_id');
            if (! $folderId) {
                Log::info('No Google Drive folder ID found - will be created after first backup');

                return [];
            }

            $response = $service->files->listFiles([
                'q' => "parents in '{$folderId}' and trashed=false",
                'orderBy' => 'createdTime desc',
                'fields' => 'files(id,name,size,createdTime,mimeType)',
            ]);

            $backups = [];
            foreach ($response->getFiles() as $file) {
                $backups[] = [
                    'id' => $file->getId(),
                    'filename' => $file->getName(),
                    'size' => $file->getSize(),
                    'created_at' => Carbon::parse($file->getCreatedTime()),
                    'type' => $this->getBackupType($file->getName()),
                    'storage' => 'google_drive',
                ];
            }

            return $backups;

        } catch (\Exception $e) {
            Log::warning('Could not get Google Drive backups', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Download backup from Google Drive
     *
     * @param  string  $fileId
     * @param  string  $fileName
     * @return string|false Path to downloaded file or false on failure
     */
    public function downloadFromGoogleDrive($fileId, $fileName)
    {
        try {
            $client = $this->getGoogleDriveClient();

            if (! $client) {
                throw new \Exception('Google Drive client not configured');
            }

            $service = new GoogleDrive($client);
            $response = $service->files->get($fileId, ['alt' => 'media']);

            $downloadPath = storage_path("app/temp/{$fileName}");

            // Ensure temp directory exists
            if (! file_exists(dirname($downloadPath))) {
                mkdir(dirname($downloadPath), 0755, true);
            }

            file_put_contents($downloadPath, $response->getBody()->getContents());

            return $downloadPath;

        } catch (\Exception $e) {
            Log::error('Failed to download from Google Drive', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete backup from Google Drive
     *
     * @param  string  $fileId
     * @return bool
     */
    public function deleteFromGoogleDrive($fileId)
    {
        try {
            $client = $this->getGoogleDriveClient();

            if (! $client) {
                throw new \Exception('Google Drive client not configured');
            }

            $service = new GoogleDrive($client);
            $service->files->delete($fileId);

            Log::info('File deleted from Google Drive', ['file_id' => $fileId]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete from Google Drive', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Test Google Drive connection
     *
     * @return array
     */
    public function testGoogleDriveConnection()
    {
        try {
            $client = $this->getGoogleDriveClient();

            if (! $client) {
                return [
                    'success' => false,
                    'message' => 'Google Drive credentials not configured or invalid. Please check your Client ID and Client Secret.',
                ];
            }

            // Check if we have a valid access token
            if (! $client->getAccessToken()) {
                return [
                    'success' => false,
                    'message' => 'No access token available. Please authorize the application first.',
                ];
            }

            if ($client->isAccessTokenExpired()) {
                return [
                    'success' => false,
                    'message' => 'Access token has expired and could not be refreshed. Please re-authorize.',
                ];
            }

            $service = new GoogleDrive($client);

            // Test with a simple API call
            $about = $service->about->get(['fields' => 'user,storageQuota']);

            Log::info('Google Drive connection test successful', [
                'user_email' => $about->getUser()->getEmailAddress(),
            ]);

            return [
                'success' => true,
                'message' => 'Connected to Google Drive successfully',
                'user_email' => $about->getUser()->getEmailAddress(),
                'storage_info' => [
                    'usage' => $about->getStorageQuota()->getUsage(),
                    'limit' => $about->getStorageQuota()->getLimit(),
                ],
            ];

        } catch (\Google\Service\Exception $googleError) {
            $errorDetails = json_decode($googleError->getMessage(), true);
            $errorMessage = isset($errorDetails['error']['message']) ?
                $errorDetails['error']['message'] :
                'Google Drive API error';

            Log::error('Google Drive connection test failed - API Error', [
                'error' => $errorMessage,
                'error_code' => $googleError->getCode(),
            ]);

            return [
                'success' => false,
                'message' => 'Google Drive connection failed: '.$errorMessage,
                'error_type' => 'api_error',
                'error_code' => $googleError->getCode(),
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive connection test failed - General Error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Google Drive connection failed: '.$e->getMessage(),
                'error_type' => 'general_error',
            ];
        }
    }
}
