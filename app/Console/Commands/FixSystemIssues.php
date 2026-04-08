<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class FixSystemIssues extends Command
{
    protected $signature = 'system:fix 
                           {--dry-run : Show what would be fixed without making changes}
                           {--force : Skip confirmation prompts}';

    protected $description = 'Fix common system issues in the college management application';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔧 College Management System - Issue Fixer');
        $this->info('============================================');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $issues = $this->detectIssues();
        
        if (empty($issues)) {
            $this->info('✅ No issues detected! Your system appears to be healthy.');
            return Command::SUCCESS;
        }

        $this->warn("🚨 Found " . count($issues) . " issues:");
        foreach ($issues as $issue) {
            $this->line("   • {$issue['description']}");
        }
        $this->newLine();

        if (!$force && !$dryRun) {
            if (!$this->confirm('Do you want to fix these issues?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $fixed = 0;
        $failed = 0;

        foreach ($issues as $issue) {
            $this->info("🔧 Fixing: {$issue['description']}");

            try {
                if (!$dryRun) {
                    $result = $this->fixIssue($issue);
                    if ($result) {
                        $fixed++;
                        $this->info("   ✅ Fixed successfully");
                    } else {
                        $failed++;
                        $this->error("   ❌ Failed to fix");
                    }
                } else {
                    $this->info("   🔍 Would fix: {$issue['type']}");
                    $fixed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("   ❌ Error: " . $e->getMessage());
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->info("🔍 Dry run completed. {$fixed} issues would be fixed.");
        } else {
            $this->info("✅ Fixed {$fixed} issues successfully.");
            if ($failed > 0) {
                $this->warn("⚠️  {$failed} issues could not be fixed automatically.");
            }
        }

        if (!$dryRun && $fixed > 0) {
            $this->info('🧹 Clearing caches...');
            $this->clearCaches();
        }

        $this->newLine();
        $this->info('🎉 System fix operation completed!');
        
        return Command::SUCCESS;
    }

    private function detectIssues(): array
    {
        $issues = [];

        // Check for missing settings table
        if (!Schema::hasTable('settings')) {
            $issues[] = [
                'type' => 'missing_table',
                'description' => 'Settings table does not exist',
                'table' => 'settings'
            ];
        }

        // Check for missing columns in existing tables
        $issues = array_merge($issues, $this->checkMissingColumns());

        // Check for orphaned records
        $issues = array_merge($issues, $this->checkOrphanedRecords());

        // Check for old course columns that need removal
        $issues = array_merge($issues, $this->checkOldColumns());

        // Check for stalled ETimeOffice syncs
        $issues = array_merge($issues, $this->checkETimeOfficeSync());

        // Check for backup health
        $issues = array_merge($issues, $this->checkBackupHealth());

        // Check for missing ETimeOffice mappings
        $issues = array_merge($issues, $this->checkETimeOfficeMappings());

        // Check for pending migrations
        $issues = array_merge($issues, $this->checkMigrationStatus());

        return $issues;
    }

    private function checkMissingColumns(): array
    {
        $issues = [];

        $requiredColumns = [
            'certificate_templates' => ['description', 'is_active'],
            'id_card_templates' => ['description', 'is_active'],
            'holidays' => ['description'],
            'students' => ['status', 'enrollment_number'],
            'settings' => ['is_encrypted', 'validation_rules'],
        ];

        foreach ($requiredColumns as $table => $columns) {
            if (Schema::hasTable($table)) {
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        $issues[] = [
                            'type' => 'missing_column',
                            'description' => "Missing column '{$column}' in '{$table}' table",
                            'table' => $table,
                            'column' => $column
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    private function checkOrphanedRecords(): array
    {
        $issues = [];

        try {
            // Check for students without batches (if batches table exists)
            if (Schema::hasTable('students') && Schema::hasTable('batches')) {
                $orphanedStudents = DB::table('students')
                    ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
                    ->whereNull('batches.id')
                    ->whereNotNull('students.batch_id')
                    ->count();

                if ($orphanedStudents > 0) {
                    $issues[] = [
                        'type' => 'orphaned_students',
                        'description' => "{$orphanedStudents} students with invalid batch references",
                        'count' => $orphanedStudents
                    ];
                }
            }
        } catch (\Exception $e) {
            // If we can't check, skip this test
        }

        return $issues;
    }

    private function checkOldColumns(): array
    {
        $issues = [];

        if (Schema::hasTable('courses')) {
            $oldColumns = ['duration_in_years', 'max_batch_size', 'enrollment_prefix'];
            foreach ($oldColumns as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $issues[] = [
                        'type' => 'old_column',
                        'description' => "Old unused column '{$column}' in courses table",
                        'table' => 'courses',
                        'column' => $column
                    ];
                }
            }
        }

        return $issues;
    }

    private function fixIssue(array $issue): bool
    {
        switch ($issue['type']) {
            case 'missing_table':
                return $this->createMissingTable($issue);
            
            case 'missing_column':
                return $this->addMissingColumn($issue);
            
            case 'old_column':
                return $this->removeOldColumn($issue);
            
            case 'orphaned_students':
                return $this->fixOrphanedStudents($issue);
            
            case 'stalled_sync':
                return $this->fixStalledSyncs($issue);
            
            case 'missing_mapping':
                return $this->fixETimeOfficeMappings($issue);
            
            case 'pending_migrations':
                return $this->fixPendingMigrations($issue);
            
            case 'backup_disabled':
                return $this->fixBackupHealth($issue);
            
            default:
                return false;
        }
    }

    private function createMissingTable(array $issue): bool
    {
        if ($issue['table'] === 'settings') {
            try {
                Artisan::call('migrate', ['--force' => true]);
                return true;
            } catch (\Exception $e) {
                $this->error("Failed to create settings table: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    private function addMissingColumn(array $issue): bool
    {
        try {
            $table = $issue['table'];
            $column = $issue['column'];

            Schema::table($table, function ($tableSchema) use ($column) {
                switch ($column) {
                    case 'description':
                        $tableSchema->text('description')->nullable();
                        break;
                    case 'is_active':
                        $tableSchema->boolean('is_active')->default(true);
                        break;
                    case 'status':
                        $tableSchema->enum('status', ['active', 'inactive', 'graduated', 'transferred', 'suspended'])
                                   ->default('active');
                        break;
                    case 'enrollment_number':
                        $tableSchema->string('enrollment_number', 50)->nullable();
                        break;
                    case 'is_encrypted':
                        $tableSchema->boolean('is_encrypted')->default(false);
                        break;
                    case 'validation_rules':
                        $tableSchema->text('validation_rules')->nullable();
                        break;
                    default:
                        $tableSchema->string($column)->nullable();
                }
            });
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to add column {$issue['column']}: " . $e->getMessage());
            return false;
        }
    }

    private function removeOldColumn(array $issue): bool
    {
        try {
            Schema::table($issue['table'], function ($table) use ($issue) {
                $table->dropColumn($issue['column']);
            });
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to remove column {$issue['column']}: " . $e->getMessage());
            return false;
        }
    }

    private function fixOrphanedStudents(array $issue): bool
    {
        try {
            // Set orphaned students' batch_id to null
            DB::table('students')
                ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
                ->whereNull('batches.id')
                ->whereNotNull('students.batch_id')
                ->update(['students.batch_id' => null]);
            
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to fix orphaned students: " . $e->getMessage());
            return false;
        }
    }

    private function checkETimeOfficeSync(): array
    {
        $issues = [];
        if (Schema::hasTable('etimeoffice_sync_logs')) {
            $stalledSyncs = DB::table('etimeoffice_sync_logs')
                ->where('status', 'running')
                ->where('started_at', '<', now()->subMinutes(30))
                ->count();

            if ($stalledSyncs > 0) {
                $issues[] = [
                    'type' => 'stalled_sync',
                    'description' => "{$stalledSyncs} ETimeOffice sync(s) appear to be stalled",
                    'count' => $stalledSyncs
                ];
            }
        }
        return $issues;
    }

    private function checkBackupHealth(): array
    {
        $issues = [];
        
        // Check if auto backup is enabled
        $autoBackup = Setting::where('key', 'auto_backup')->value('value');
        if (!$autoBackup || !filter_var($autoBackup, FILTER_VALIDATE_BOOLEAN)) {
            $issues[] = [
                'type' => 'backup_disabled',
                'description' => 'Auto backup is disabled in settings'
            ];
        }

        // Check last backup age (logic from BackupHealthCheck.php)
        try {
            $disks = config('backup.backup.destination.disks', ['local']);
            $disk = Storage::disk($disks[0]);
            $appName = config('backup.backup.name', config('app.name', 'Laravel'));
            
            if ($disk->exists($appName)) {
                $files = $disk->files($appName);
                $lastModified = null;
                
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                        $modified = $disk->lastModified($file);
                        if (!$lastModified || $modified > $lastModified) {
                            $lastModified = $modified;
                        }
                    }
                }
                
                if ($lastModified) {
                    $lastBackupDate = Carbon::createFromTimestamp($lastModified);
                    if ($lastBackupDate->diffInDays(now()) > 1) {
                        $issues[] = [
                            'type' => 'backup_outdated',
                            'description' => "Last backup was more than 24 hours ago ({$lastBackupDate->diffForHumans()})"
                        ];
                    }
                } else {
                    $issues[] = [
                        'type' => 'no_backups',
                        'description' => 'No backup files found in storage'
                    ];
                }
            }
        } catch (\Exception $e) {
            // Skip if backup config or disk is not available
        }

        return $issues;
    }

    private function checkETimeOfficeMappings(): array
    {
        $issues = [];
        if (Schema::hasTable('students')) {
            $unmappedStudents = Student::where('status', 'active')
                ->whereNull('biometric_employee_code')
                ->whereNotNull('enrollment_number')
                ->count();

            if ($unmappedStudents > 0) {
                $issues[] = [
                    'type' => 'missing_mapping',
                    'description' => "{$unmappedStudents} active students are missing ETimeOffice biometric mapping",
                    'count' => $unmappedStudents
                ];
            }
        }
        return $issues;
    }

    private function checkMigrationStatus(): array
    {
        $issues = [];
        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();
            
            if (stripos($output, 'Pending') !== false) {
                $issues[] = [
                    'type' => 'pending_migrations',
                    'description' => 'There are pending database migrations'
                ];
            }
        } catch (\Exception $e) {
            // Migration status check failed
        }
        return $issues;
    }

    private function fixStalledSyncs(array $issue): bool
    {
        try {
            DB::table('etimeoffice_sync_logs')
                ->where('status', 'running')
                ->where('started_at', '<', now()->subMinutes(30))
                ->update([
                    'status' => 'failed',
                    'errors' => json_encode(['Sync stalled and was automatically marked as failed']),
                    'completed_at' => now()
                ]);
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to fix stalled syncs: " . $e->getMessage());
            return false;
        }
    }

    private function fixETimeOfficeMappings(array $issue): bool
    {
        try {
            $count = Student::where('status', 'active')
                ->whereNull('biometric_employee_code')
                ->whereNotNull('enrollment_number')
                ->chunkById(100, function ($students) {
                    foreach ($students as $student) {
                        $student->update(['biometric_employee_code' => $student->enrollment_number]);
                    }
                });
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to fix student mappings: " . $e->getMessage());
            return false;
        }
    }

    private function fixPendingMigrations(array $issue): bool
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to run migrations: " . $e->getMessage());
            return false;
        }
    }

    private function fixBackupHealth(array $issue): bool
    {
        if ($issue['type'] === 'backup_disabled') {
            try {
                Setting::updateOrCreate(['key' => 'auto_backup'], ['value' => '1']);
                return true;
            } catch (\Exception $e) {
                $this->error("Failed to enable auto backup: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    private function clearCaches(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            $this->info('✅ Caches cleared successfully');
        } catch (\Exception $e) {
            $this->warn('⚠️  Some caches could not be cleared: ' . $e->getMessage());
        }
    }
}