<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;

class CheckETimeOfficeData extends Command
{
    protected $signature = 'etimeoffice:check {--live} {--test-webhook} {--pull-data} {--details}';
    protected $description = 'Check if ETimeOffice is sending data to your application';

    public function handle()
    {
        $this->info('🔍 ETimeOffice Data Checker');
        $this->info('==========================');

        if ($this->option('live')) {
            $this->checkLiveWebhookData();
        }

        if ($this->option('test-webhook')) {
            $this->testWebhookEndpoint();
        }

        if ($this->option('pull-data')) {
            $this->pullDataFromETimeOffice();
        }

        if (!$this->hasOptions()) {
            $this->showAllChecks();
        }

        return 0;
    }

    /**
     * Show all available checks
     */
    private function showAllChecks()
    {
        $this->info('Available check options:');
        $this->info('');
        $this->info('🔴 --live          Monitor live webhook data in real-time');
        $this->info('🧪 --test-webhook  Test your webhook endpoint');
        $this->info('📡 --pull-data     Pull data directly from ETimeOffice API');
        $this->info('📊 --details       Show detailed output');
        $this->info('');
        $this->info('Examples:');
        $this->info('  php artisan etimeoffice:check --live');
        $this->info('  php artisan etimeoffice:check --test-webhook');
        $this->info('  php artisan etimeoffice:check --pull-data --details');
        $this->info('');
        
        // Quick status check
        $this->quickStatusCheck();
    }

    /**
     * Quick status check
     */
    private function quickStatusCheck()
    {
        $this->info('📋 Quick Status Check:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━');

        // Check if ETimeOffice is enabled
        $enabled = Setting::where('key', 'etimeoffice_enabled')->value('value');
        $this->info($enabled ? '✅ ETimeOffice Integration: ENABLED' : '❌ ETimeOffice Integration: DISABLED');

        // Check configuration
        $apiUrl = Setting::where('key', 'etimeoffice_api_url')->value('value');
        $corporateId = Setting::where('key', 'etimeoffice_corporate_id')->value('value');
        $username = Setting::where('key', 'etimeoffice_username')->value('value');

        $this->info($apiUrl ? '✅ API URL: Configured' : '❌ API URL: Not configured');
        $this->info($corporateId ? '✅ Corporate ID: Configured' : '❌ Corporate ID: Not configured');
        $this->info($username ? '✅ Username: Configured' : '❌ Username: Not configured');

        // Check recent webhook data
        $recentCount = Attendance::whereDate('created_at', today())->count();
        $this->info("📊 Today's Attendance Records: {$recentCount}");

        // Check students with biometric codes
        $mappedStudents = Student::whereNotNull('biometric_employee_code')->where('status', 'active')->count();
        $totalStudents = Student::where('status', 'active')->count();
        $this->info("👥 Students with Biometric Codes: {$mappedStudents}/{$totalStudents}");

        // Check last sync
        $lastSync = Setting::where('key', 'etimeoffice_last_sync')->value('value');
        $this->info($lastSync ? "🕐 Last Sync: {$lastSync}" : '🕐 Last Sync: Never');

        $this->info('');
    }

    /**
     * Monitor live webhook data in real-time
     */
    private function checkLiveWebhookData()
    {
        $this->info('🔴 LIVE MONITORING MODE');
        $this->info('Monitoring webhook data in real-time...');
        $this->info('Press Ctrl+C to stop');
        $this->info('');

        $logFile = storage_path('logs/laravel.log');
        $lastSize = 0;

        if (file_exists($logFile)) {
            $lastSize = filesize($logFile);
        }

        while (true) {
            if (file_exists($logFile)) {
                clearstatcache(false, $logFile);
                $currentSize = filesize($logFile);
                
                if ($currentSize > $lastSize) {
                    $newContent = file_get_contents($logFile, false, null, $lastSize);
                    $lines = explode("\n", $newContent);
                    
                    foreach ($lines as $line) {
                        if (stripos($line, 'etimeoffice') !== false || 
                            stripos($line, 'webhook') !== false ||
                            stripos($line, 'biometric') !== false) {
                            
                            $timestamp = now()->format('H:i:s');
                            $this->info("[$timestamp] " . trim($line));
                        }
                    }
                    
                    $lastSize = $currentSize;
                }
            }
            
            sleep(1); // Check every second
        }
    }

    /**
     * Test webhook endpoint manually
     */
    private function testWebhookEndpoint()
    {
        $this->info('🧪 TESTING WEBHOOK ENDPOINT');
        $this->info('');

        $webhookUrl = url('/api/etimeoffice/webhook');
        $this->info("Testing URL: {$webhookUrl}");

        // Test data samples
        $testCases = [
            [
                'name' => 'Valid ETimeOffice Data',
                'data' => [
                    'Empcode' => 'TEST123',
                    'PunchDate' => now()->format('d/m/Y_H:i'),
                    'Direction' => 'IN',
                    'DeviceId' => 'TEST_DEVICE'
                ]
            ],
            [
                'name' => 'Alternative Format',
                'data' => [
                    'EmployeeCode' => 'TEST456',
                    'LogDateTime' => now()->format('Y-m-d H:i:s'),
                    'Direction' => 'OUT'
                ]
            ]
        ];

        foreach ($testCases as $testCase) {
            $this->info("Testing: {$testCase['name']}");
            
            try {
                $response = Http::timeout(10)->post($webhookUrl, $testCase['data']);
                
                if ($response->successful()) {
                    $this->info("  ✅ Status: {$response->status()}");
                } else {
                    $this->error("  ❌ Status: {$response->status()}");
                }
                
                if ($this->option('details')) {
                    $this->line("  Response: " . $response->body());
                }
                
                $this->info('  ' . str_repeat('─', 50));
                
            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
                $this->info('  ' . str_repeat('─', 50));
            }
        }
    }

    /**
     * Pull data directly from ETimeOffice API - FIXED VERSION
     */
    private function pullDataFromETimeOffice()
    {
        $this->info('📡 PULLING DATA FROM ETIMEOFFICE API');
        $this->info('');

        // Get ETimeOffice configuration
        $apiUrl = Setting::where('key', 'etimeoffice_api_url')->value('value');
        $corporateId = Setting::where('key', 'etimeoffice_corporate_id')->value('value');
        $username = Setting::where('key', 'etimeoffice_username')->value('value');
        $password = Setting::where('key', 'etimeoffice_password')->value('value');

        if (!$apiUrl || !$corporateId || !$username || !$password) {
            $this->error('❌ ETimeOffice configuration is incomplete');
            $this->info('Missing fields:');
            if (!$apiUrl) $this->info('  - API URL');
            if (!$corporateId) $this->info('  - Corporate ID');
            if (!$username) $this->info('  - Username');
            if (!$password) $this->info('  - Password');
            return;
        }

        $this->info("API URL: {$apiUrl}");
        $this->info("Corporate ID: {$corporateId}");
        $this->info("Username: {$username}");
        $this->info('');

        try {
            // Create authentication token
            $authToken = base64_encode("{$corporateId}:{$username}:{$password}:true");

            // Test different time ranges to find data
            $timeRanges = [
                'Last 2 hours' => [
                    'FromDate' => now()->subHours(2)->format('d/m/Y_H:i'),
                    'ToDate' => now()->format('d/m/Y_H:i')
                ],
                'Today' => [
                    'FromDate' => now()->startOfDay()->format('d/m/Y_H:i'),
                    'ToDate' => now()->format('d/m/Y_H:i')
                ],
                'Yesterday' => [
                    'FromDate' => now()->subDay()->startOfDay()->format('d/m/Y_H:i'),
                    'ToDate' => now()->subDay()->endOfDay()->format('d/m/Y_H:i')
                ]
            ];

            foreach ($timeRanges as $rangeName => $dateRange) {
                $this->info("Testing range: {$rangeName}");
                
                $params = [
                    'Empcode' => 'ALL',
                    'FromDate' => $dateRange['FromDate'],
                    'ToDate' => $dateRange['ToDate']
                ];
                
                $queryString = http_build_query($params);
                $testUrl = rtrim($apiUrl, '/') . '/DownloadPunchData?' . $queryString;
                
                if ($this->option('details')) {
                    $this->info("  URL: {$testUrl}");
                }

                try {
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Basic ' . $authToken,
                            'Accept' => 'application/json',
                        ])
                        ->get($testUrl);

                    if ($response->successful()) {
                        $this->info("  ✅ Success: {$response->status()}");
                        
                        // Try to decode as JSON first
                        $data = $response->json();
                        
                        if (is_array($data) && count($data) > 0) {
                            $this->info("  📊 Records found: " . count($data));
                            
                            // Show sample records safely
                            if ($this->option('details')) {
                                $this->info("  Sample records:");
                                $sampleCount = min(3, count($data));
                                for ($i = 0; $i < $sampleCount; $i++) {
                                    if (isset($data[$i])) {
                                        $this->line("    Record " . ($i + 1) . ": " . json_encode($data[$i], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                    }
                                }
                            } else {
                                // Show just the first record structure without details
                                if (isset($data[0])) {
                                    $firstRecord = $data[0];
                                    $this->info("  Sample record fields: " . implode(', ', array_keys($firstRecord)));
                                }
                            }
                            
                            // Check if any of these employee codes match our students
                            $this->checkEmployeeCodeMatching($data);
                            
                            break; // Found data, no need to check other ranges
                            
                        } else {
                            $this->info("  📄 No records found in this time range");
                        }
                    } else {
                        $this->error("  ❌ Failed: {$response->status()}");
                        if ($this->option('details')) {
                            $responseBody = $response->body();
                            $this->error("  Error: " . (strlen($responseBody) > 200 ? substr($responseBody, 0, 200) . '...' : $responseBody));
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("  ❌ Request failed: " . $e->getMessage());
                }
                
                $this->info('  ' . str_repeat('─', 50));
            }

        } catch (\Exception $e) {
            $this->error("❌ API Setup Error: " . $e->getMessage());
        }
    }

    /**
     * Check if employee codes from ETimeOffice match our students
     */
    private function checkEmployeeCodeMatching($apiData)
    {
        if (!is_array($apiData) || count($apiData) === 0) {
            return;
        }

        $this->info("  🔍 Checking employee code matching:");
        
        $employeeCodes = [];
        foreach ($apiData as $record) {
            if (isset($record['Empcode'])) {
                $employeeCodes[] = $record['Empcode'];
            } elseif (isset($record['EmployeeCode'])) {
                $employeeCodes[] = $record['EmployeeCode'];
            }
        }
        
        $uniqueCodes = array_unique($employeeCodes);
        $this->info("    Unique employee codes found: " . count($uniqueCodes));
        
        if ($this->option('details') && count($uniqueCodes) > 0) {
            $this->info("    Codes: " . implode(', ', array_slice($uniqueCodes, 0, 10)) . (count($uniqueCodes) > 10 ? '...' : ''));
        }
        
        // Check how many match our students
        $matchedCount = 0;
        $unmatchedCodes = [];
        
        foreach ($uniqueCodes as $code) {
            $student = Student::where('biometric_employee_code', $code)
                             ->orWhere('enrollment_number', $code)
                             ->orWhere('enrollment_number', 'LIKE', "%{$code}%")
                             ->first();
            
            if ($student) {
                $matchedCount++;
            } else {
                $unmatchedCodes[] = $code;
            }
        }
        
        $this->info("    ✅ Matched to students: {$matchedCount}/" . count($uniqueCodes));
        
        if (count($unmatchedCodes) > 0) {
            $this->warn("    ⚠️  Unmatched codes: " . count($unmatchedCodes));
            if ($this->option('details')) {
                $this->info("    Unmatched: " . implode(', ', array_slice($unmatchedCodes, 0, 5)) . (count($unmatchedCodes) > 5 ? '...' : ''));
            }
        }
    }

    /**
     * Check if any options are provided
     */
    private function hasOptions()
    {
        return $this->option('live') || 
               $this->option('test-webhook') || 
               $this->option('pull-data');
    }
}