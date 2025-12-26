<?php

// Create this command: php artisan make:command ETimeOfficeAutoSync

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\AttendanceSettingsController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ETimeOfficeAutoSync extends Command
{
    protected $signature = 'etimeoffice:auto-sync 
                           {--frequency=5 : Sync frequency in minutes}
                           {--range=today : Date range to sync (today, yesterday, last_3_days)}
                           {--test : Run in test mode}';

    protected $description = 'Automatically sync attendance data from ETimeOffice every few minutes';

    public function handle()
    {
        $frequency = $this->option('frequency');
        $range = $this->option('range');
        $testMode = $this->option('test');
        
        $this->info("Starting ETimeOffice Auto-Sync (every {$frequency} minutes)");
        $this->info("Date Range: {$range}");
        $this->info("Test Mode: " . ($testMode ? 'ON' : 'OFF'));
        
        // Check if sync is enabled
        if (!$this->isSyncEnabled()) {
            $this->warn('ETimeOffice integration is disabled. Skipping sync.');
            return 0;
        }
        
        // Perform the sync
        $result = $this->performSync($range, $testMode);
        
        if ($result['success']) {
            $this->info("Sync completed successfully:");
            $this->info("- Total Records: {$result['data']['total_records']}");
            $this->info("- Created: {$result['data']['created_records']}");
            $this->info("- Updated: {$result['data']['updated_records']}");
            $this->info("- Skipped: {$result['data']['skipped_records']}");
            
            if (!empty($result['data']['errors'])) {
                $this->warn("Errors encountered: " . count($result['data']['errors']));
                foreach ($result['data']['errors'] as $error) {
                    $this->error("- {$error}");
                }
            }
        } else {
            $this->error("Sync failed: {$result['message']}");
            return 1;
        }
        
        return 0;
    }
    
    private function isSyncEnabled(): bool
    {
        try {
            $enabled = \App\Models\Setting::where('key', 'etimeoffice_enabled')->value('value');
            return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        } catch (\Exception $e) {
            Log::error('Could not check ETimeOffice sync status: ' . $e->getMessage());
            return false;
        }
    }
    
    private function performSync($range, $testMode): array
    {
        try {
            // Create a mock request
            $request = new \Illuminate\Http\Request([
                'date_range' => $range,
                'test_mode' => $testMode ? 'on' : null,
                'employee_codes' => null
            ]);
            
            // Use the existing controller method
            $controller = new AttendanceSettingsController();
            $response = $controller->pullETimeOfficeData($request);
            
            $responseData = json_decode($response->getContent(), true);
            
            // Log the sync attempt
            Log::info('Auto-sync completed', [
                'range' => $range,
                'test_mode' => $testMode,
                'result' => $responseData
            ]);
            
            return $responseData;
            
        } catch (\Exception $e) {
            Log::error('Auto-sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
}