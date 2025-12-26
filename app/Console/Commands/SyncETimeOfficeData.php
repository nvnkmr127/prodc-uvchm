<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ETimeOfficeService;
use App\Models\Setting;
use Carbon\Carbon;

class SyncETimeOfficeData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'etimeoffice:sync 
                          {--mode=incremental : Sync mode: incremental, today, yesterday, range}
                          {--from= : Start date for range mode (Y-m-d)}
                          {--to= : End date for range mode (Y-m-d)}
                          {--empcode=ALL : Employee code to sync (ALL for all employees)}
                          {--test : Test mode - don\'t create attendance records}';

    /**
     * The console command description.
     */
    protected $description = 'Sync attendance data from eTimeOffice API';

    protected ETimeOfficeService $eTimeOfficeService;

    public function __construct(ETimeOfficeService $eTimeOfficeService)
    {
        parent::__construct();
        $this->eTimeOfficeService = $eTimeOfficeService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting eTimeOffice data sync...');
        $this->newLine();

        // Check if API is configured
        if (!$this->checkApiConfiguration()) {
            $this->error('❌ eTimeOffice API is not properly configured!');
            $this->warn('Please configure the following settings:');
            $this->warn('- etimeoffice_corporate_id');
            $this->warn('- etimeoffice_username'); 
            $this->warn('- etimeoffice_password');
            return 1;
        }

        // Test API connection
        $this->info('🔗 Testing API connection...');
        $connectionTest = $this->eTimeOfficeService->testConnection();
        
        if (!$connectionTest['success']) {
            $this->error('❌ API connection failed: ' . $connectionTest['message']);
            return 1;
        }
        
        $this->info('✅ API connection successful');
        $this->newLine();

        // Determine sync mode and dates
        $mode = $this->option('mode');
        $empcode = $this->option('empcode');
        $isTestMode = $this->option('test');

        if ($isTestMode) {
            $this->warn('⚠️ Running in TEST MODE - no attendance records will be created');
            $this->newLine();
        }

        switch ($mode) {
            case 'incremental':
                return $this->syncIncremental($isTestMode);
                
            case 'today':
                return $this->syncDateRange(today(), today(), $empcode, $isTestMode);
                
            case 'yesterday':
                return $this->syncDateRange(yesterday(), yesterday(), $empcode, $isTestMode);
                
            case 'range':
                return $this->syncDateRange(
                    Carbon::parse($this->option('from')),
                    Carbon::parse($this->option('to')),
                    $empcode,
                    $isTestMode
                );
                
            default:
                $this->error('❌ Invalid sync mode. Use: incremental, today, yesterday, or range');
                return 1;
        }
    }

    /**
     * Sync incremental data using LastRecord
     */
    private function syncIncremental(bool $testMode): int
    {
        $this->info('📥 Fetching incremental data from eTimeOffice...');
        
        $result = $this->eTimeOfficeService->fetchIncrementalData();
        
        if (!$result['success']) {
            $this->error('❌ Failed to fetch incremental data: ' . $result['error']);
            return 1;
        }

        $this->info("✅ Fetched {$result['count']} records");
        
        if ($result['count'] === 0) {
            $this->info('ℹ️ No new data to process');
            return 0;
        }

        if ($testMode) {
            $this->showDataPreview($result['data']);
            return 0;
        }

        return $this->processData($result['data']);
    }

    /**
     * Sync data for specific date range
     */
    private function syncDateRange(Carbon $fromDate, Carbon $toDate, string $empcode, bool $testMode): int
    {
        $this->info("📥 Fetching data from {$fromDate->format('Y-m-d')} to {$toDate->format('Y-m-d')}...");
        
        $result = $this->eTimeOfficeService->fetchPunchData($fromDate, $toDate, $empcode);
        
        if (!$result['success']) {
            $this->error('❌ Failed to fetch data: ' . $result['error']);
            return 1;
        }

        $this->info("✅ Fetched {$result['count']} records");
        
        if ($result['count'] === 0) {
            $this->info('ℹ️ No data found for the specified date range');
            return 0;
        }

        if ($testMode) {
            $this->showDataPreview($result['data']);
            return 0;
        }

        return $this->processData($result['data']);
    }

    /**
     * Process punch data and create attendance records
     */
    private function processData(array $punchData): int
    {
        $this->info('⚙️ Processing punch data...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($punchData));
        $progressBar->setFormat('Processing: %current%/%max% [%bar%] %percent:3s%% %memory:6s%');
        $progressBar->start();

        $result = $this->eTimeOfficeService->processPunchData($punchData);

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        $this->info('📊 Processing Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Records Processed', $result['processed']],
                ['Attendance Created', $result['created']],
                ['Attendance Updated', $result['updated']],
                ['Records Skipped', $result['skipped']],
                ['Errors', count($result['errors'])]
            ]
        );

        // Show errors if any
        if (!empty($result['errors'])) {
            $this->newLine();
            $this->warn('⚠️ Errors encountered:');
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->line("  • {$error}");
            }
            
            if (count($result['errors']) > 10) {
                $this->line("  • ... and " . (count($result['errors']) - 10) . " more errors");
            }
        }

        // Update last sync time
        Setting::updateOrCreate(
            ['key' => 'etimeoffice_last_sync_time'],
            ['value' => now()->toISOString()]
        );

        $this->newLine();
        $this->info('✅ Sync completed successfully!');
        
        return 0;
    }

    /**
     * Show preview of data without processing
     */
    private function showDataPreview(array $data): void
    {
        $this->info('📋 Data Preview (first 5 records):');
        $this->newLine();

        $previewData = array_slice($data, 0, 5);
        $headers = ['Employee Code', 'Name', 'Punch Date', 'Status'];
        $rows = [];

        foreach ($previewData as $punch) {
            $rows[] = [
                $punch['Empcode'] ?? $punch['EmpcardNo'] ?? 'N/A',
                $punch['Name'] ?? 'Unknown',
                $punch['PunchDate'] ?? $punch['LogDateTime'] ?? 'N/A',
                isset($punch['InTime']) ? 'IN/OUT Data' : 'Raw Punch'
            ];
        }

        $this->table($headers, $rows);

        if (count($data) > 5) {
            $this->info("... and " . (count($data) - 5) . " more records");
        }
    }

    /**
     * Check if API is properly configured
     */
    private function checkApiConfiguration(): bool
    {
        $corporateId = Setting::where('key', 'etimeoffice_corporate_id')->value('value');
        $username = Setting::where('key', 'etimeoffice_username')->value('value');
        $password = Setting::where('key', 'etimeoffice_password')->value('value');

        return !empty($corporateId) && !empty($username) && !empty($password);
    }
}