<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ETimeOfficeService
{
    protected string $apiUrl;

    protected string $corporateId;

    protected string $username;

    protected string $password;

    protected string $authToken;

    public function __construct()
    {
        // Don't load configuration in constructor to avoid database queries during bootstrap
    }

    /**
     * Load API configuration from settings
     */
    private function loadConfiguration(): void
    {
        try {
            // Check if settings table exists before querying
            if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $this->setDefaultConfiguration();

                return;
            }

            $this->apiUrl = Setting::where('key', 'etimeoffice_api_url')->value('value') ?? 'https://api.etimeoffice.com/api';
            $this->corporateId = Setting::where('key', 'etimeoffice_corporate_id')->value('value') ?? '';
            $this->username = Setting::where('key', 'etimeoffice_username')->value('value') ?? '';
            $this->password = Setting::where('key', 'etimeoffice_password')->value('value') ?? '';

            // Create Basic Auth token (base64 encoded)
            $this->authToken = base64_encode("{$this->corporateId}:{$this->username}:{$this->password}:true");
        } catch (\Exception $e) {
            // Fallback to default configuration if database is not available
            $this->setDefaultConfiguration();
        }
    }

    /**
     * Set default configuration when database is not available
     */
    private function setDefaultConfiguration(): void
    {
        $this->apiUrl = 'https://api.etimeoffice.com/api';
        $this->corporateId = '';
        $this->username = '';
        $this->password = '';
        $this->authToken = base64_encode(':::true');
    }

    /**
     * Ensure configuration is loaded
     */
    private function ensureConfigurationLoaded(): void
    {
        if (! isset($this->apiUrl)) {
            $this->loadConfiguration();
        }
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        $this->ensureConfigurationLoaded();

        try {
            $response = $this->makeApiCall('DownloadPunchData', [
                'Empcode' => 'ALL',
                'FromDate' => now()->format('d/m/Y_H:i'),
                'ToDate' => now()->format('d/m/Y_H:i'),
            ]);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'API connection successful',
                    'data_count' => count($response['data']['PunchData'] ?? []),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'API connection failed: '.$response['error'],
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Fetch punch data for a specific date range
     */
    public function fetchPunchData(Carbon $fromDate, Carbon $toDate, ?string $empcode = 'ALL'): array
    {
        $this->ensureConfigurationLoaded();

        try {
            Log::info('Fetching eTimeOffice punch data', [
                'from_date' => $fromDate->format('d/m/Y_H:i'),
                'to_date' => $toDate->format('d/m/Y_H:i'),
                'empcode' => $empcode,
            ]);

            $response = $this->makeApiCall('DownloadPunchData', [
                'Empcode' => $empcode,
                'FromDate' => $fromDate->format('d/m/Y_H:i'),
                'ToDate' => $toDate->format('d/m/Y_H:i'),
            ]);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'],
                    'data' => [],
                ];
            }

            $punchData = $response['data']['PunchData'] ?? [];

            Log::info('eTimeOffice data fetched successfully', [
                'records_count' => count($punchData),
                'from_date' => $fromDate->format('Y-m-d H:i'),
                'to_date' => $toDate->format('Y-m-d H:i'),
            ]);

            return [
                'success' => true,
                'data' => $punchData,
                'count' => count($punchData),
            ];

        } catch (\Exception $e) {
            Log::error('eTimeOffice fetch error', [
                'error' => $e->getMessage(),
                'from_date' => $fromDate->format('Y-m-d H:i'),
                'to_date' => $toDate->format('Y-m-d H:i'),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Fetch IN/OUT punch data with work time calculation
     */
    public function fetchInOutPunchData(Carbon $fromDate, Carbon $toDate, ?string $empcode = 'ALL'): array
    {
        $this->ensureConfigurationLoaded();

        try {
            $response = $this->makeApiCall('DownloadInOutPunchData', [
                'Empcode' => $empcode,
                'FromDate' => $fromDate->format('d/m/Y'),
                'ToDate' => $toDate->format('d/m/Y'),
            ]);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'],
                    'data' => [],
                ];
            }

            $inOutData = $response['data']['PunchData'] ?? [];

            Log::info('eTimeOffice IN/OUT data fetched', [
                'records_count' => count($inOutData),
                'date_range' => $fromDate->format('Y-m-d').' to '.$toDate->format('Y-m-d'),
            ]);

            return [
                'success' => true,
                'data' => $inOutData,
                'count' => count($inOutData),
            ];

        } catch (\Exception $e) {
            Log::error('eTimeOffice IN/OUT fetch error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Fetch incremental data using LastRecord parameter
     */
    public function fetchIncrementalData(): array
    {
        $this->ensureConfigurationLoaded();

        try {
            $lastRecord = Setting::where('key', 'etimeoffice_last_sync_record')->value('value') ?? '';

            // If no last record, start with current month
            if (empty($lastRecord)) {
                $lastRecord = now()->format('mY').'$0';
            }

            $response = $this->makeApiCall('DownloadLastPunchData', [
                'Empcode' => 'ALL',
                'LastRecord' => $lastRecord,
            ]);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'],
                    'data' => [],
                ];
            }

            $punchData = $response['data']['PunchData'] ?? [];
            $maxRecord = $response['data']['MaxRecord'] ?? $lastRecord;

            // Update last sync record for next incremental sync
            if ($maxRecord !== $lastRecord) {
                Setting::updateOrCreate(
                    ['key' => 'etimeoffice_last_sync_record'],
                    ['value' => $maxRecord]
                );
            }

            Log::info('eTimeOffice incremental sync completed', [
                'records_count' => count($punchData),
                'last_record' => $lastRecord,
                'new_max_record' => $maxRecord,
            ]);

            return [
                'success' => true,
                'data' => $punchData,
                'count' => count($punchData),
                'last_record' => $lastRecord,
                'new_max_record' => $maxRecord,
            ];

        } catch (\Exception $e) {
            Log::error('eTimeOffice incremental sync error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Process fetched punch data and create attendance records
     */
    public function processPunchData(array $punchData): array
    {
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($punchData as $punch) {
            try {
                $results['processed']++;

                $empcode = $punch['Empcode'] ?? $punch['EmpcardNo'] ?? null;
                $punchDate = $punch['PunchDate'] ?? $punch['LogDateTime'] ?? null;
                $name = $punch['Name'] ?? 'Unknown';

                if (! $empcode || ! $punchDate) {
                    $results['skipped']++;
                    $results['errors'][] = "Missing empcode or punch date for: {$name}";

                    continue;
                }

                // Find student using optimized lookup
                $student = $this->findStudentByBiometricCode($empcode);

                if (! $student) {
                    $results['skipped']++;
                    $results['errors'][] = "Student not found for empcode: {$empcode} (Name: {$name})";

                    continue;
                }

                // Parse punch date/time
                $carbonDate = Carbon::createFromFormat('d/m/Y H:i:s', $punchDate);
                $attendanceDate = $carbonDate->toDateString();

                // Check if attendance already exists
                $existingAttendance = Attendance::where('student_id', $student->id)
                    ->where('attendance_date', $attendanceDate)
                    ->first();

                if ($existingAttendance) {
                    // Update existing attendance
                    $existingAttendance->update([
                        'marked_at' => $carbonDate,
                        'notes' => "Updated via eTimeOffice API - {$name}",
                        'device_id' => 'etimeoffice-api',
                    ]);
                    $results['updated']++;
                } else {
                    // Create new attendance record
                    Attendance::create([
                        'student_id' => $student->id,
                        'batch_id' => $student->batch_id,
                        'faculty_id' => 1,
                        'attendance_date' => $attendanceDate,
                        'status' => 'present',
                        'marked_at' => $carbonDate,
                        'notes' => "Marked via eTimeOffice API - {$name}",
                        'device_id' => 'etimeoffice-api',
                    ]);
                    $results['created']++;
                }

                Log::debug('Processed eTimeOffice punch record', [
                    'empcode' => $empcode,
                    'student_name' => $student->name,
                    'punch_date' => $punchDate,
                    'action' => $existingAttendance ? 'updated' : 'created',
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = "Error processing punch for {$empcode}: ".$e->getMessage();
                Log::error('Error processing eTimeOffice punch data', [
                    'empcode' => $empcode ?? 'unknown',
                    'punch' => $punch,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Make API call to eTimeOffice
     */
    private function makeApiCall(string $endpoint, array $params): array
    {
        $this->ensureConfigurationLoaded();

        try {
            $url = $this->apiUrl.'/'.$endpoint;
            $queryString = http_build_query($params);
            $fullUrl = $url.'?'.$queryString;

            Log::info('Making eTimeOffice API call', [
                'endpoint' => $endpoint,
                'url' => $fullUrl,
                'params' => $params,
            ]);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Basic '.$this->authToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->get($fullUrl);

            if (! $response->successful()) {
                throw new \Exception("API request failed with status: {$response->status()}");
            }

            $data = $response->json();

            // Check if API returned an error
            if (isset($data['Error']) && $data['Error'] === true) {
                throw new \Exception($data['Msg'] ?? 'Unknown API error');
            }

            Log::info('eTimeOffice API call successful', [
                'endpoint' => $endpoint,
                'response_size' => strlen($response->body()),
                'has_data' => isset($data['PunchData']),
            ]);

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('eTimeOffice API call failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'url' => $fullUrl ?? $url,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find student using biometric code with fallback
     */
    private function findStudentByBiometricCode(string $biometricCode): ?Student
    {
        // First try biometric employee code
        $student = Student::where('biometric_employee_code', $biometricCode)->first();

        if ($student) {
            return $student;
        }

        // Fallback to enrollment number patterns
        $patterns = [
            $biometricCode,
            'UV-'.$biometricCode,
            'UVCHM-'.$biometricCode,
        ];

        foreach ($patterns as $pattern) {
            $student = Student::where('enrollment_number', $pattern)->first();
            if ($student) {
                // Auto-populate biometric code
                if (empty($student->biometric_employee_code)) {
                    $student->update(['biometric_employee_code' => $biometricCode]);
                }

                return $student;
            }
        }

        return null;
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats(): array
    {
        $lastSyncRecord = Setting::where('key', 'etimeoffice_last_sync_record')->value('value');
        $lastSyncTime = Setting::where('key', 'etimeoffice_last_sync_time')->value('value');

        return [
            'last_sync_record' => $lastSyncRecord,
            'last_sync_time' => $lastSyncTime ? Carbon::parse($lastSyncTime)->format('Y-m-d H:i:s') : 'Never',
            'api_configured' => ! empty($this->corporateId) && ! empty($this->username) && ! empty($this->password),
            'api_url' => $this->apiUrl,
        ];
    }

    /**
     * Fetch data for specific date ranges with better error handling
     */
    public function fetchDataForDateRange(string $rangeType, ?Carbon $customStart = null, ?Carbon $customEnd = null): array
    {
        $this->ensureConfigurationLoaded();

        try {
            $dateRange = $this->calculateDateRangeFromType($rangeType, $customStart, $customEnd);

            Log::info('Fetching ETimeOffice data for range', [
                'range_type' => $rangeType,
                'start' => $dateRange['start']->format('Y-m-d H:i'),
                'end' => $dateRange['end']->format('Y-m-d H:i'),
            ]);

            return $this->fetchPunchData($dateRange['start'], $dateRange['end']);

        } catch (\Exception $e) {
            Log::error('Error fetching data for date range', [
                'range_type' => $rangeType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Calculate date range from type
     */
    private function calculateDateRangeFromType(string $rangeType, ?Carbon $customStart = null, ?Carbon $customEnd = null): array
    {
        $now = now();

        switch ($rangeType) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];

            case 'yesterday':
                $yesterday = $now->copy()->subDay();

                return [
                    'start' => $yesterday->copy()->startOfDay(),
                    'end' => $yesterday->copy()->endOfDay(),
                ];

            case 'last_3_days':
                return [
                    'start' => $now->copy()->subDays(2)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];

            case 'last_7_days':
                return [
                    'start' => $now->copy()->subDays(6)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];

            case 'last_30_days':
                return [
                    'start' => $now->copy()->subDays(29)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];

            case 'this_week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek(),
                ];

            case 'last_week':
                $lastWeek = $now->copy()->subWeek();

                return [
                    'start' => $lastWeek->copy()->startOfWeek(),
                    'end' => $lastWeek->copy()->endOfWeek(),
                ];

            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];

            case 'last_month':
                $lastMonth = $now->copy()->subMonth();

                return [
                    'start' => $lastMonth->copy()->startOfMonth(),
                    'end' => $lastMonth->copy()->endOfMonth(),
                ];

            case 'custom':
                if (! $customStart || ! $customEnd) {
                    throw new \InvalidArgumentException('Custom date range requires both start and end dates');
                }

                return [
                    'start' => $customStart->copy()->startOfDay(),
                    'end' => $customEnd->copy()->endOfDay(),
                ];

            default:
                throw new \InvalidArgumentException("Invalid date range type: {$rangeType}");
        }
    }

    /**
     * Validate API configuration before making calls
     */
    public function validateConfiguration(): array
    {
        $this->ensureConfigurationLoaded();

        $issues = [];

        if (empty($this->apiUrl)) {
            $issues[] = 'API URL is not configured';
        }

        if (empty($this->corporateId)) {
            $issues[] = 'Corporate ID is not configured';
        }

        if (empty($this->username)) {
            $issues[] = 'Username is not configured';
        }

        if (empty($this->password)) {
            $issues[] = 'Password is not configured';
        }

        // Test URL format
        if (! empty($this->apiUrl) && ! filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            $issues[] = 'API URL format is invalid';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Get comprehensive sync statistics
     */
    public function getComprehensiveStats(): array
    {
        try {
            $validation = $this->validateConfiguration();

            return [
                'configuration' => [
                    'valid' => $validation['valid'],
                    'issues' => $validation['issues'],
                    'api_url' => $this->apiUrl ?? 'Not configured',
                    'corporate_id' => ! empty($this->corporateId) ? substr($this->corporateId, 0, 3).'***' : 'Not configured',
                    'username' => ! empty($this->username) ? substr($this->username, 0, 3).'***' : 'Not configured',
                    'password_set' => ! empty($this->password),
                ],
                'sync_stats' => $this->getSyncStats(),
                'last_24h_records' => $this->getRecordCount(now()->subDay(), now()),
                'today_records' => $this->getRecordCount(now()->startOfDay(), now()),
                'this_week_records' => $this->getRecordCount(now()->startOfWeek(), now()),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting comprehensive stats', ['error' => $e->getMessage()]);

            return [
                'configuration' => ['valid' => false, 'issues' => ['Unable to load configuration']],
                'sync_stats' => ['error' => $e->getMessage()],
                'last_24h_records' => 0,
                'today_records' => 0,
                'this_week_records' => 0,
            ];
        }
    }

    /**
     * Get attendance record count for date range
     */
    private function getRecordCount(Carbon $start, Carbon $end): int
    {
        try {
            if (! Schema::hasTable('attendances')) {
                return 0;
            }

            return \App\Models\Attendance::whereBetween('attendance_date', [
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
            ])
                ->where('device_id', 'etimeoffice-api')
                ->count();
        } catch (\Exception $e) {
            Log::error('Error getting record count', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Enhanced error handling for API calls
     */
    private function handleApiError(\Exception $e, string $context): array
    {
        $errorMessage = $e->getMessage();
        $errorCode = $e->getCode();

        // Categorize errors for better user feedback
        if (strpos($errorMessage, 'timeout') !== false) {
            $userMessage = 'Connection timed out. Please check your network connection and try again.';
            $category = 'timeout';
        } elseif (strpos($errorMessage, 'Unauthorized') !== false || $errorCode === 401) {
            $userMessage = 'Authentication failed. Please check your Corporate ID, Username, and Password.';
            $category = 'auth';
        } elseif (strpos($errorMessage, 'Not Found') !== false || $errorCode === 404) {
            $userMessage = 'API endpoint not found. Please check your API URL configuration.';
            $category = 'config';
        } elseif (strpos($errorMessage, 'Server Error') !== false || $errorCode >= 500) {
            $userMessage = 'ETimeOffice server error. Please try again later or contact support.';
            $category = 'server';
        } else {
            $userMessage = 'Connection failed: '.$errorMessage;
            $category = 'unknown';
        }

        Log::error("ETimeOffice API Error - {$context}", [
            'error' => $errorMessage,
            'code' => $errorCode,
            'category' => $category,
            'context' => $context,
        ]);

        return [
            'success' => false,
            'error' => $userMessage,
            'error_category' => $category,
            'technical_error' => $errorMessage,
        ];
    }

    /**
     * Retry mechanism for API calls
     */
    private function makeApiCallWithRetry(string $endpoint, array $params, int $maxRetries = 1): array
    {
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $result = $this->makeApiCall($endpoint, $params);

                if ($result['success']) {
                    if ($attempt > 1) {
                        Log::info("API call succeeded on attempt {$attempt}", [
                            'endpoint' => $endpoint,
                        ]);
                    }

                    return $result;
                }

                $lastError = $result['error'];

                // Don't retry on authentication errors
                if (strpos($lastError, 'Authentication') !== false || strpos($lastError, 'Unauthorized') !== false) {
                    break;
                }

                // Wait before retry (exponential backoff)
                if ($attempt < $maxRetries) {
                    $waitTime = pow(2, $attempt - 1); // 1s, 2s, 4s
                    sleep($waitTime);

                    Log::info("Retrying API call in {$waitTime}s (attempt {$attempt}/{$maxRetries})", [
                        'endpoint' => $endpoint,
                        'error' => $lastError,
                    ]);
                }

            } catch (\Exception $e) {
                $lastError = $e->getMessage();

                // Don't retry on configuration errors
                if (strpos($lastError, 'configuration') !== false) {
                    break;
                }

                if ($attempt < $maxRetries) {
                    $waitTime = pow(2, $attempt - 1);
                    sleep($waitTime);
                }
            }
        }

        return [
            'success' => false,
            'error' => $lastError ?? 'Unknown error after '.$maxRetries.' attempts',
        ];
    }
}
