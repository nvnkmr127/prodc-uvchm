<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Drive;

class UploadToGoogleDrive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:upload-gdrive 
                            {--file= : Specific file to upload}
                            {--all : Upload all backup files}
                            {--recent : Upload only recent files (last 24 hours)}
                            {--small-only : Upload only files under 50MB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload UVCHM Portal backups to Google Drive (database backups only for service accounts)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('☁️ UVCHM Portal - Google Drive Upload');
        $this->line('=========================================');

        try {
            // Initialize Google Drive client
            $client = $this->initializeGoogleClient();
            $service = new Drive($client);

            // Get backup files to upload (filter by size)
            $files = $this->getBackupFiles();

            if (empty($files)) {
                $this->warn('No suitable backup files found to upload.');
                return 0;
            }

            $this->info(sprintf('Found %d backup files to upload...', count($files)));

            // Get shared drive or create folder in user's drive
            $folderId = $this->getOrCreateBackupFolder($service);

            // Upload files
            $uploaded = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($files as $file) {
                $fileSize = filesize($file['path']);
                $fileSizeMB = round($fileSize / 1024 / 1024, 2);
                
                $this->info("Processing: " . basename($file['path']) . " ({$fileSizeMB} MB)");
                
                // Skip large files for service accounts (over 50MB)
                if ($fileSize > 50 * 1024 * 1024) {
                    $this->warn("  ⚠️ Skipped - File too large for service account upload");
                    $skipped++;
                    continue;
                }
                
                if ($this->uploadFileChunked($service, $file, $folderId)) {
                    $uploaded++;
                    $this->line("  ✅ Success");
                } else {
                    $failed++;
                    $this->line("  ❌ Failed");
                }
            }

            $this->line('');
            $this->info("Upload completed: {$uploaded} successful, {$failed} failed, {$skipped} skipped");

            // Log the result
            $this->logUploadResult($uploaded, $failed, $skipped);

            return $failed > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('Upload failed: ' . $e->getMessage());
            \Log::error('Google Drive upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Initialize Google Drive client
     */
    private function initializeGoogleClient()
    {
        $client = new Client();
        
        // Set up service account authentication
        $credentialsPath = storage_path('app/google/service-account.json');
        
        if (!file_exists($credentialsPath)) {
            throw new \Exception('Service account credentials not found: ' . $credentialsPath);
        }

        $client->setAuthConfig($credentialsPath);
        $client->addScope(Drive::DRIVE);
        $client->setApplicationName('UVCHM Portal Backup');

        return $client;
    }

    /**
     * Get backup files to upload
     */
    private function getBackupFiles()
    {
        $backupPath = storage_path('app/UVCHM Portal');
        $files = [];

        if (!is_dir($backupPath)) {
            return $files;
        }

        $option = $this->option('file') ? 'file' : 
                 ($this->option('all') ? 'all' : 
                 ($this->option('recent') ? 'recent' : 'recent'));

        switch ($option) {
            case 'file':
                $specificFile = $this->option('file');
                if (file_exists($backupPath . '/' . $specificFile)) {
                    $files[] = [
                        'path' => $backupPath . '/' . $specificFile,
                        'name' => $specificFile
                    ];
                }
                break;

            case 'all':
                $allFiles = glob($backupPath . '/*.{zip,gz,tar.gz}', GLOB_BRACE);
                foreach ($allFiles as $file) {
                    $files[] = [
                        'path' => $file,
                        'name' => basename($file)
                    ];
                }
                break;

            case 'recent':
            default:
                // Upload files from last 24 hours
                $cutoff = time() - (24 * 60 * 60);
                $allFiles = glob($backupPath . '/*.{zip,gz,tar.gz}', GLOB_BRACE);
                
                foreach ($allFiles as $file) {
                    if (filemtime($file) > $cutoff) {
                        $files[] = [
                            'path' => $file,
                            'name' => basename($file)
                        ];
                    }
                }
                break;
        }

        // Filter by size if --small-only option
        if ($this->option('small-only')) {
            $files = array_filter($files, function($file) {
                return filesize($file['path']) <= 50 * 1024 * 1024; // 50MB limit
            });
        }

        return $files;
    }

    /**
     * Get or create backup folder in Google Drive
     */
    private function getOrCreateBackupFolder($service)
    {
        $folderName = 'UVCHM Portal Backups';
        
        try {
            // Check if folder exists
            $response = $service->files->listFiles([
                'q' => "name='{$folderName}' and mimeType='application/vnd.google-apps.folder'",
                'spaces' => 'drive'
            ]);

            if (count($response->files) > 0) {
                return $response->files[0]->id;
            }

            // Create folder
            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            $folder = $service->files->create($fileMetadata, [
                'fields' => 'id'
            ]);

            $this->info("Created Google Drive folder: {$folderName}");
            
            return $folder->id;
            
        } catch (\Exception $e) {
            // If we can't create in root, try to get the folder ID from env
            $folderId = env('GOOGLE_DRIVE_FOLDER_ID');
            if ($folderId) {
                $this->info("Using existing folder ID from environment: {$folderId}");
                return $folderId;
            }
            throw $e;
        }
    }

    /**
     * Upload a file to Google Drive with chunked upload for large files
     */
    private function uploadFileChunked($service, $file, $folderId)
    {
        try {
            $fileName = $file['name'];
            $filePath = $file['path'];
            $fileSize = filesize($filePath);
            
            // Check if file already exists
            $existingFiles = $service->files->listFiles([
                'q' => "name='{$fileName}' and parents in '{$folderId}'",
                'spaces' => 'drive'
            ]);

            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $fileName,
                'parents' => [$folderId]
            ]);

            // For files under 5MB, use simple upload
            if ($fileSize < 5 * 1024 * 1024) {
                $content = file_get_contents($filePath);
                $mimeType = $this->getMimeType($filePath);

                if (count($existingFiles->files) > 0) {
                    // Update existing file
                    $fileId = $existingFiles->files[0]->id;
                    $service->files->update($fileId, $fileMetadata, [
                        'data' => $content,
                        'mimeType' => $mimeType,
                        'uploadType' => 'multipart'
                    ]);
                } else {
                    // Create new file
                    $service->files->create($fileMetadata, [
                        'data' => $content,
                        'mimeType' => $mimeType,
                        'uploadType' => 'multipart'
                    ]);
                }
            } else {
                // For larger files, use resumable upload
                $this->info("  📡 Using resumable upload for large file...");
                
                // Set chunked upload parameters
                $chunkSizeBytes = 1 * 1024 * 1024; // 1MB chunks
                $client = $service->getClient();
                $client->setDefer(true);
                
                $mimeType = $this->getMimeType($filePath);
                
                if (count($existingFiles->files) > 0) {
                    $fileId = $existingFiles->files[0]->id;
                    $request = $service->files->update($fileId, $fileMetadata);
                } else {
                    $request = $service->files->create($fileMetadata);
                }
                
                // Create media upload
                $media = new \Google\Http\MediaFileUpload(
                    $client,
                    $request,
                    $mimeType,
                    null,
                    true,
                    $chunkSizeBytes
                );
                
                $media->setFileSize($fileSize);
                
                // Upload in chunks
                $handle = fopen($filePath, "rb");
                $uploadStatus = false;
                
                while (!$uploadStatus && !feof($handle)) {
                    $chunk = fread($handle, $chunkSizeBytes);
                    $uploadStatus = $media->nextChunk($chunk);
                }
                
                fclose($handle);
                $client->setDefer(false);
            }

            return true;

        } catch (\Exception $e) {
            $this->error("Failed to upload {$file['name']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get MIME type for file
     */
    private function getMimeType($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'zip' => 'application/zip',
            'gz' => 'application/gzip',
            'tar' => 'application/x-tar',
            'sql' => 'application/sql'
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Log upload result
     */
    private function logUploadResult($uploaded, $failed, $skipped = 0)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] Google Drive Upload: {$uploaded} successful, {$failed} failed, {$skipped} skipped";
        
        file_put_contents(
            storage_path('logs/gdrive-uploads.log'),
            $logEntry . "\n",
            FILE_APPEND | LOCK_EX
        );

        \Log::info('Google Drive upload completed', [
            'uploaded' => $uploaded,
            'failed' => $failed,
            'skipped' => $skipped,
            'timestamp' => $timestamp
        ]);
    }
}