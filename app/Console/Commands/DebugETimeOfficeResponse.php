<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use Carbon\Carbon;

class DebugETimeOfficeResponse extends Command
{
    protected $signature = 'etimeoffice:debug {--hours=2}';
    protected $description = 'Debug ETimeOffice API response structure';

    public function handle()
    {
        $this->info('🔍 ETimeOffice API Response Debugger');
        $this->info('=====================================');

        // Get configuration
        $apiUrl = Setting::where('key', 'etimeoffice_api_url')->value('value') ?? 'https://api.etimeoffice.com/api';
        $corporateId = Setting::where('key', 'etimeoffice_corporate_id')->value('value');
        $username = Setting::where('key', 'etimeoffice_username')->value('value');
        $password = Setting::where('key', 'etimeoffice_password')->value('value');

        if (!$corporateId || !$username || !$password) {
            $this->error('❌ ETimeOffice configuration missing');
            return 1;
        }

        $authToken = base64_encode("{$corporateId}:{$username}:{$password}:true");
        $hours = $this->option('hours');
        
        $fromDate = now()->subHours($hours);
        $toDate = now();

        $params = [
            'Empcode' => 'ALL',
            'FromDate' => $fromDate->format('d/m/Y_H:i'),
            'ToDate' => $toDate->format('d/m/Y_H:i')
        ];

        $url = rtrim($apiUrl, '/') . '/DownloadPunchData?' . http_build_query($params);
        
        $this->info("📡 API URL: {$url}");
        $this->info("🔑 Auth Token: " . substr($authToken, 0, 20) . '...');
        $this->info('');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Basic ' . $authToken,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            $this->info("📊 HTTP Status: {$response->status()}");
            $this->info("📋 Response Headers:");
            foreach ($response->headers() as $key => $value) {
                $this->info("  {$key}: " . (is_array($value) ? implode(', ', $value) : $value));
            }
            $this->info('');

            if ($response->successful()) {
                $rawBody = $response->body();
                $this->info("📄 Raw Response Body (first 500 chars):");
                $this->info(substr($rawBody, 0, 500) . (strlen($rawBody) > 500 ? '...' : ''));
                $this->info('');

                // Try to decode as JSON
                try {
                    $data = $response->json();
                    $this->info("✅ JSON Decode: SUCCESS");
                    $this->info("📊 Data Type: " . gettype($data));
                    
                    if (is_array($data)) {
                        $this->info("📊 Array Length: " . count($data));
                        
                        if (count($data) > 0) {
                            $this->info('');
                            $this->info("🔍 DETAILED RECORD ANALYSIS:");
                            
                            for ($i = 0; $i < min(3, count($data)); $i++) {
                                $this->info("");
                                $this->info("Record #{$i}:");
                                $this->info("  Type: " . gettype($data[$i]));
                                
                                if (is_array($data[$i])) {
                                    $this->info("  Keys: " . implode(', ', array_keys($data[$i])));
                                    foreach ($data[$i] as $key => $value) {
                                        $displayValue = is_string($value) ? $value : json_encode($value);
                                        $this->info("    {$key}: {$displayValue}");
                                    }
                                } else {
                                    $this->info("  Value: " . json_encode($data[$i]));
                                }
                            }
                        } else {
                            $this->warn("⚠️  Array is empty");
                        }
                    } else {
                        $this->info("📊 Data Content: " . json_encode($data));
                    }

                } catch (\Exception $e) {
                    $this->error("❌ JSON Decode Failed: " . $e->getMessage());
                    
                    // Try to decode as different formats
                    $this->info('');
                    $this->info("🔍 TRYING OTHER FORMATS:");
                    
                    // Check if it's XML
                    if (strpos($rawBody, '<?xml') !== false || strpos($rawBody, '<xml') !== false) {
                        $this->info("  Appears to be XML format");
                    }
                    
                    // Check if it's CSV
                    if (substr_count($rawBody, ',') > 3) {
                        $this->info("  Might be CSV format");
                        $lines = explode("\n", $rawBody);
                        $this->info("  Lines: " . count($lines));
                        if (count($lines) > 0) {
                            $this->info("  First line: " . trim($lines[0]));
                        }
                    }
                    
                    // Check if it's plain text with delimiters
                    if (strpos($rawBody, '|') !== false) {
                        $this->info("  Might be pipe-delimited format");
                    }
                }

            } else {
                $this->error("❌ API Request Failed");
                $this->error("Response: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error("❌ Request Exception: " . $e->getMessage());
        }

        // Additional diagnostic info
        $this->info('');
        $this->info("🔧 DIAGNOSTIC INFO:");
        $this->info("  PHP JSON Extension: " . (extension_loaded('json') ? 'YES' : 'NO'));
        $this->info("  cURL Extension: " . (extension_loaded('curl') ? 'YES' : 'NO'));
        $this->info("  Current DateTime: " . now()->toISOString());
        $this->info("  From Date: " . $fromDate->toISOString());
        $this->info("  To Date: " . $toDate->toISOString());
        
        return 0;
    }
}