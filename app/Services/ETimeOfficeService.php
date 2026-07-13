<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\Attendance\FacultyAttendanceService;
use Carbon\Carbon;
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
            if (! Schema::hasTable('settings')) {
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
                    // Check if it is faculty
                    $faculty = User::role('staff')
                        ->where('biometric_employee_code', $empcode)
                        ->orWhere('employee_id', $empcode)
                        ->first();

                    if ($faculty) {
                        try {
                            $carbonDate = null;
                            $dateFormats = [
                                'd/m/Y H:i:s',
                                'Y-m-d H:i:s',
                                'd-m-Y H:i:s',
                                'm/d/Y H:i:s',
                                'd/m/Y H:i',
                                'Y-m-d H:i',
                            ];
                            foreach ($dateFormats as $format) {
                                try {
                                    $carbonDate = Carbon::createFromFormat($format, $punchDate);
                                    if ($carbonDate !== false) {
                                        break;
                                    }
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            if (! $carbonDate) {
                                $carbonDate = Carbon::parse($punchDate);
                            }

                            $facultyAttendanceService = app(FacultyAttendanceService::class);
                            $facultyAttendanceService->recordPunch($faculty, $carbonDate, 'AUTO', 'etimeoffice-api');
                            $results['created']++;
                        } catch (\Exception $e) {
                            $results['errors'][] = "Error processing faculty punch for {$empcode}: ".$e->getMessage();
                        }

                        continue;
                    }

                    $results['skipped']++;
                    $results['errors'][] = "Student or Faculty not found for empcode: {$empcode} (Name: {$name})";

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
}
