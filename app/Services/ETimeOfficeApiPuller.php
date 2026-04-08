<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ETimeOfficeApiPuller
{
    private $apiUrl;

    private $corporateId;

    private $username;

    private $password;

    private $authToken;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Load ETimeOffice configuration from settings
     */
    private function loadConfiguration()
    {
        $this->apiUrl = Setting::where('key', 'etimeoffice_api_url')->value('value') ?? 'https://api.etimeoffice.com/api';
        $this->corporateId = Setting::where('key', 'etimeoffice_corporate_id')->value('value');
        $this->username = Setting::where('key', 'etimeoffice_username')->value('value');
        $this->password = Setting::where('key', 'etimeoffice_password')->value('value');

        if ($this->corporateId && $this->username && $this->password) {
            $this->authToken = base64_encode("{$this->corporateId}:{$this->username}:{$this->password}:true");
        }
    }

    /**
     * Pull attendance data from ETimeOffice API for a specific time range
     */
    public function pullAttendanceData(Carbon $fromDate, Carbon $toDate, $empcode = 'ALL')
    {
        if (! $this->authToken) {
            throw new \Exception('ETimeOffice configuration is incomplete');
        }

        $params = [
            'Empcode' => $empcode,
            'FromDate' => $fromDate->format('d/m/Y_H:i'),
            'ToDate' => $toDate->format('d/m/Y_H:i'),
        ];

        $url = rtrim($this->apiUrl, '/').'/DownloadPunchData?'.http_build_query($params);

        Log::info('ETimeOffice API Request', [
            'url' => $url,
            'from_date' => $fromDate->toISOString(),
            'to_date' => $toDate->toISOString(),
            'empcode' => $empcode,
        ]);

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Basic '.$this->authToken,
                    'Accept' => 'application/json',
                    'User-Agent' => 'Laravel-ETimeOffice-Client/1.0',
                ])
                ->get($url);

            if ($response->successful()) {
                $responseData = $response->json();

                // Handle ETimeOffice API structure: {"Error":false,"Msg":"Success","IsAdmin":true,"PunchData":[...]}
                if (isset($responseData['Error']) && $responseData['Error'] === false) {
                    $punchData = $responseData['PunchData'] ?? [];

                    Log::info('ETimeOffice API Response', [
                        'status' => $response->status(),
                        'error' => $responseData['Error'],
                        'message' => $responseData['Msg'] ?? 'No message',
                        'punch_data_count' => is_array($punchData) ? count($punchData) : 0,
                        'is_admin' => $responseData['IsAdmin'] ?? false,
                    ]);

                    return [
                        'success' => true,
                        'data' => $punchData,
                        'count' => is_array($punchData) ? count($punchData) : 0,
                        'api_message' => $responseData['Msg'] ?? 'Success',
                        'is_admin' => $responseData['IsAdmin'] ?? false,
                    ];

                } else {
                    $errorMsg = $responseData['Msg'] ?? 'Unknown API error';
                    Log::error('ETimeOffice API returned error', [
                        'error' => $responseData['Error'] ?? 'Unknown',
                        'message' => $errorMsg,
                        'response' => $responseData,
                    ]);

                    return [
                        'success' => false,
                        'error' => 'ETimeOffice API error: '.$errorMsg,
                        'api_response' => $responseData,
                    ];
                }
            } else {
                Log::error('ETimeOffice API HTTP Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'API request failed: HTTP '.$response->status(),
                    'response' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('ETimeOffice API Exception', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process and save attendance records from ETimeOffice data
     */
    public function processAttendanceRecords($punchData)
    {
        if (! is_array($punchData)) {
            Log::warning('ETimeOffice punch data is not an array', ['data_type' => gettype($punchData)]);

            return [
                'processed' => 0,
                'errors' => ['Punch data is not in expected format'],
                'employee_codes_found' => [],
            ];
        }

        if (empty($punchData)) {
            Log::info('ETimeOffice returned empty punch data');

            return [
                'processed' => 0,
                'errors' => [],
                'employee_codes_found' => [],
                'message' => 'No punch data available for the specified time range',
            ];
        }

        $processed = 0;
        $errors = [];
        $employeeCodes = [];

        foreach ($punchData as $index => $record) {
            try {
                // Log the first record structure for debugging
                if ($index === 0) {
                    Log::info('ETimeOffice Punch Record Structure', [
                        'sample_record' => $record,
                        'keys' => is_array($record) ? array_keys($record) : 'not_array',
                    ]);
                }

                // Extract data from record - ETimeOffice typically uses these field names
                $empcode = null;
                $punchDate = null;
                $direction = 'IN'; // Default

                if (is_array($record)) {
                    // ETimeOffice common field names (case sensitive)
                    $empcode = $record['Empcode'] ??
                              $record['EmployeeCode'] ??
                              $record['EmpCode'] ??
                              $record['empcode'] ?? null;

                    // ETimeOffice date/time field names
                    $punchDate = $record['PunchDate'] ??
                                $record['LogDateTime'] ??
                                $record['DateTime'] ??
                                $record['Date'] ??
                                $record['Time'] ?? null;

                    // Direction/Type field
                    $direction = $record['Direction'] ??
                                $record['InOut'] ??
                                $record['Type'] ??
                                $record['PunchType'] ??
                                'IN';

                    if ($empcode) {
                        $employeeCodes[] = $empcode;
                    }
                }

                if (! $empcode || ! $punchDate) {
                    Log::warning('ETimeOffice record missing required fields', [
                        'record_index' => $index,
                        'record' => $record,
                        'empcode' => $empcode,
                        'punch_date' => $punchDate,
                    ]);
                    $errors[] = "Record #{$index}: missing empcode or punch date";

                    continue;
                }

                // Find student
                $student = $this->findStudentByEmployeeCode($empcode);
                if (! $student) {
                    Log::info('Student not found for employee code', [
                        'empcode' => $empcode,
                        'record_index' => $index,
                    ]);
                    $errors[] = "Student not found for employee code: {$empcode}";

                    continue;
                }

                // Parse punch date
                $carbonDate = $this->parsePunchDate($punchDate);
                if (! $carbonDate) {
                    $errors[] = "Could not parse punch date: {$punchDate}";

                    continue;
                }

                // Create or update attendance record
                $attendanceData = $this->createAttendanceRecord($student, $carbonDate, $direction, $record);
                if ($attendanceData) {
                    $processed++;
                }

            } catch (\Exception $e) {
                Log::error('Error processing ETimeOffice punch record', [
                    'record_index' => $index,
                    'record' => $record,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "Error processing record #{$index}: ".$e->getMessage();
            }
        }

        // Log employee codes found for debugging
        $uniqueCodes = array_unique(array_filter($employeeCodes));
        Log::info('ETimeOffice Employee Codes Analysis', [
            'total_punch_records' => count($punchData),
            'unique_employee_codes' => count($uniqueCodes),
            'codes_sample' => array_slice($uniqueCodes, 0, 10),
            'processed_records' => $processed,
            'error_count' => count($errors),
        ]);

        return [
            'processed' => $processed,
            'errors' => $errors,
            'employee_codes_found' => $uniqueCodes,
        ];
    }

    /**
     * Find student by employee code with multiple lookup strategies
     */
    private function findStudentByEmployeeCode($empcode)
    {
        // Strategy 1: Direct biometric code match
        $student = Student::where('biometric_employee_code', $empcode)->first();
        if ($student) {
            return $student;
        }

        // Strategy 2: Enrollment number exact match
        $student = Student::where('enrollment_number', $empcode)->first();
        if ($student) {
            // Auto-populate biometric code
            $student->update(['biometric_employee_code' => $empcode]);
            Log::info('Auto-populated biometric code from enrollment match', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'empcode' => $empcode,
                'enrollment' => $student->enrollment_number,
            ]);

            return $student;
        }

        // Strategy 3: Enrollment number pattern matching
        $patterns = [
            "UVCHM-{$empcode}",
            "UV-{$empcode}",
            "ENR-{$empcode}",
            preg_replace('/^[A-Z]+-/', '', $empcode), // Remove prefix like UVCHM-123 -> 123
            preg_replace('/[^0-9]/', '', $empcode),    // Extract numbers only
        ];

        foreach ($patterns as $pattern) {
            if (empty($pattern)) {
                continue;
            }

            $student = Student::where('enrollment_number', $pattern)
                ->orWhere('enrollment_number', 'LIKE', "%{$pattern}%")
                ->first();
            if ($student) {
                // Auto-populate biometric code
                $student->update(['biometric_employee_code' => $empcode]);
                Log::info('Auto-populated biometric code from pattern match', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'empcode' => $empcode,
                    'pattern_matched' => $pattern,
                    'enrollment' => $student->enrollment_number,
                ]);

                return $student;
            }
        }

        return null;
    }

    /**
     * Parse punch date from various ETimeOffice formats
     */
    private function parsePunchDate($punchDate)
    {
        // ETimeOffice common date formats
        $formats = [
            'd/m/Y H:i:s',    // 24/08/2025 17:30:45
            'd/m/Y_H:i',      // 24/08/2025_17:30
            'd/m/Y H:i',      // 24/08/2025 17:30
            'Y-m-d H:i:s',    // 2025-08-24 17:30:00
            'd-m-Y H:i:s',    // 24-08-2025 17:30:00
            'd/m/Y',          // 24/08/2025 (date only)
            'Y-m-d',          // 2025-08-24 (date only)
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $punchDate);
                if ($parsed) {
                    return $parsed;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try standard parsing as fallback
        try {
            return Carbon::parse($punchDate);
        } catch (\Exception $e) {
            Log::warning('Could not parse ETimeOffice punch date', [
                'punch_date' => $punchDate,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create attendance record from ETimeOffice data
     */
    private function createAttendanceRecord($student, $carbonDate, $direction, $rawRecord)
    {
        $attendanceDate = $carbonDate->toDateString();
        $attendanceTime = $carbonDate->toTimeString();

        // Determine attendance status based on college timing
        $status = $this->determineAttendanceStatus($carbonDate);

        try {
            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'batch_id' => $student->batch_id, // <-- Add this line
                    'status' => $status['status'],

                    'check_in_time' => $attendanceTime,
                    'notes' => 'ETimeOffice API - '.$status['reason'],
                    'created_by' => null, // System generated
                ]
            );

            // Handle OUT punches for check_out_time
            if (strtoupper($direction) === 'OUT' && $attendance->check_in_time) {
                $checkInDateTime = Carbon::parse($attendanceDate.' '.$attendance->check_in_time);
                if ($carbonDate->gt($checkInDateTime)) {
                    $attendance->update([
                        'check_out_time' => $attendanceTime,
                        'notes' => $attendance->notes.' | OUT: '.$attendanceTime,
                    ]);
                }
            }

            Log::info('Processed ETimeOffice attendance', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'enrollment' => $student->enrollment_number,
                'attendance_date' => $attendanceDate,
                'time' => $attendanceTime,
                'status' => $status['status'],
                'direction' => $direction,
                'was_created' => $attendance->wasRecentlyCreated,
            ]);

            return $attendance;

        } catch (\Exception $e) {
            Log::error('Failed to create ETimeOffice attendance record', [
                'student_id' => $student->id,
                'attendance_date' => $attendanceDate,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Determine attendance status based on college timing rules
     */
    private function determineAttendanceStatus($punchTime)
    {
        $collegeStartTime = Setting::where('key', 'college_start_time')->value('value') ?? '09:00';
        $lateThreshold = Setting::where('key', 'late_threshold_minutes')->value('value') ?? 15;

        $collegeStart = Carbon::parse($punchTime->toDateString().' '.$collegeStartTime);
        $lateLimit = $collegeStart->copy()->addMinutes($lateThreshold);

        if ($punchTime->lte($collegeStart)) {
            return [
                'status' => 'present',
                'reason' => 'On time',
            ];
        } elseif ($punchTime->lte($lateLimit)) {
            return [
                'status' => 'late',
                'reason' => 'Late arrival',
            ];
        } else {
            return [
                'status' => 'absent',
                'reason' => 'Very late - marked absent',
            ];
        }
    }

    /**
     * Pull and process data for a specific time range
     */
    public function pullAndProcess(Carbon $fromDate, Carbon $toDate, $empcode = 'ALL')
    {
        // Pull data from API
        $apiResult = $this->pullAttendanceData($fromDate, $toDate, $empcode);

        if (! $apiResult['success']) {
            return [
                'success' => false,
                'error' => $apiResult['error'],
                'processed' => 0,
            ];
        }

        // Process the punch data
        $processResult = $this->processAttendanceRecords($apiResult['data']);

        // Update last sync time
        Setting::updateOrCreate(
            ['key' => 'etimeoffice_last_sync'],
            ['value' => now()->toISOString()]
        );

        return [
            'success' => true,
            'api_records' => $apiResult['count'],
            'processed' => $processResult['processed'],
            'errors' => $processResult['errors'],
            'employee_codes_found' => $processResult['employee_codes_found'] ?? [],
            'api_message' => $apiResult['api_message'] ?? 'Success',
        ];
    }

    /**
     * Pull today's data
     */
    public function pullTodayData()
    {
        $today = now()->startOfDay();
        $now = now();

        return $this->pullAndProcess($today, $now);
    }

    /**
     * Pull recent data (last N hours)
     */
    public function pullRecentData($hours = 2)
    {
        $from = now()->subHours($hours);
        $to = now();

        return $this->pullAndProcess($from, $to);
    }

    /**
     * Test API connection and show sample data
     */
    public function testConnection($hours = 24)
    {
        $from = now()->subHours($hours);
        $to = now();

        return $this->pullAttendanceData($from, $to, 'ALL');
    }
}
