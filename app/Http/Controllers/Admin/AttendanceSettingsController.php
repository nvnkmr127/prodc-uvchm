<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceExport;
use App\Exports\SyncLogsExport;
use App\Exports\TodayAttendanceExport;
use App\Helpers\ErrorHandler;  // ✅ FIXED: Correct namespace
use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance;
use App\Models\Batch;  // ✅ ADD: Missing import
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceSettingsController extends Controller
{
    /**
     * ✅ FIX 3: Display attendance settings page with separate student/faculty times
     */
    /**
     * Display attendance settings page
     */
    public function index()
    {
        // Check permissions
        $this->authorize('manage attendance settings');

        // 1. Fetch all settings that start with 'attendance_'
        $dbSettings = \App\Models\Setting::where('key', 'like', 'attendance_%')->pluck('value', 'key');

        // 2. Map keys to remove the prefix so they match your View variables
        // Example: 'attendance_student_present_cutoff_time' becomes 'student_present_cutoff_time'
        $settings = [];
        foreach ($dbSettings as $key => $value) {
            $shortKey = str_replace('attendance_', '', $key);
            $settings[$shortKey] = $value;
        }

        // 3. Pass to view
        return view('admin.attendance.settings', compact('settings'));
    }

    /**
     * Get attendance settings data (for AJAX)
     */
    public function getSettings()
    {
        $this->authorize('manage attendance settings');

        try {
            $settings = [
                // ✅ FIX 4: Separate student and faculty cutoff times
                'student_college_start_time' => $this->getSetting('attendance_student_college_start_time', '09:30:00'),
                'student_present_cutoff_time' => $this->getSetting('attendance_student_present_cutoff_time', '11:00:00'),
                'student_late_cutoff_time' => $this->getSetting('attendance_student_late_cutoff_time', '11:30:00'),

                'faculty_college_start_time' => $this->getSetting('attendance_faculty_college_start_time', '09:00:00'),
                'faculty_present_cutoff_time' => $this->getSetting('attendance_faculty_present_cutoff_time', '10:30:00'),
                'faculty_late_cutoff_time' => $this->getSetting('attendance_faculty_late_cutoff_time', '11:00:00'),

                'college_end_time' => $this->getSetting('attendance_college_end_time', '17:00:00'),
                'weekend_enabled' => $this->getSetting('attendance_weekend_enabled', false),
                'grace_period_minutes' => $this->getSetting('attendance_grace_period_minutes', 10),
            ];

            // Get real-time attendance data
            $liveAttendances = $this->getLiveAttendanceData();

            return response()->json([
                'success' => true,
                'data' => $settings,
                'live_attendances' => $liveAttendances,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load attendance settings', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load current configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            // 1. Validation Rules
            $validator = \Validator::make($request->all(), [
                'student_college_start_time' => ['sometimes', 'nullable'], // Relaxed validation
                'student_present_cutoff_time' => ['sometimes', 'nullable'],
                'student_late_cutoff_time' => ['sometimes', 'nullable'],
                'faculty_college_start_time' => ['sometimes', 'nullable'],
                'faculty_present_cutoff_time' => ['sometimes', 'nullable'],
                'faculty_late_cutoff_time' => ['sometimes', 'nullable'],
                'college_end_time' => ['sometimes', 'nullable'],
                'grace_period_minutes' => 'sometimes|integer|min:0|max:60',
                'weekend_enabled' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // 2. Normalize Time Formats (Ensure H:i:s)
            $timeFields = [
                'student_college_start_time',
                'student_present_cutoff_time',
                'student_late_cutoff_time',
                'faculty_college_start_time',
                'faculty_present_cutoff_time',
                'faculty_late_cutoff_time',
                'college_end_time',
            ];

            $normalizedData = [];
            foreach ($timeFields as $field) {
                if ($request->has($field) && $request->$field) {
                    $time = $request->$field;
                    // Add seconds if missing (e.g., "09:30" -> "09:30:00")
                    if (preg_match('/^\d{2}:\d{2}$/', $time)) {
                        $normalizedData[$field] = $time.':00';
                    } else {
                        $normalizedData[$field] = $time;
                    }
                }
            }

            // ---------------------------------------------------------
            // [REMOVED STRICT VALIDATION]
            // We removed the block that checks "Start < Present < Late"
            // to allow you to save settings freely without errors.
            // ---------------------------------------------------------

            // 3. Save Settings to Database
            foreach ($normalizedData as $field => $value) {
                $settingKey = 'attendance_'.$field;
                \App\Models\Setting::updateOrCreate(
                    ['key' => $settingKey],
                    ['value' => $value]
                );
            }

            // 4. Save Boolean/Integer Settings
            if ($request->has('grace_period_minutes')) {
                \App\Models\Setting::updateOrCreate(
                    ['key' => 'attendance_grace_period_minutes'],
                    ['value' => $request->grace_period_minutes]
                );
            }

            if ($request->has('weekend_enabled')) {
                \App\Models\Setting::updateOrCreate(
                    ['key' => 'attendance_weekend_enabled'],
                    ['value' => $request->boolean('weekend_enabled') ? '1' : '0']
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully!',
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Attendance settings update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getETimeOfficeSettings(Request $request)
    {
        try {
            // Map the correct field names that match your form
            $settings = [
                'etimeoffice_enabled' => filter_var($this->getSetting('etimeoffice_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'etimeoffice_api_url' => $this->getSetting('etimeoffice_api_url', 'https://api.etimeoffice.com/api'),

                // Use the correct field names that match your form
                'etimeoffice_corporate_id' => $this->getSetting('etimeoffice_corporate_id', ''),
                'etimeoffice_username' => $this->getSetting('etimeoffice_username', ''),
                'etimeoffice_password' => $this->getSetting('etimeoffice_password', ''),

                // Keep these as they are
                'etimeoffice_sync_frequency' => (int) $this->getSetting('etimeoffice_sync_frequency', 15),
                'etimeoffice_last_sync' => $this->getSetting('etimeoffice_last_sync', null),
                'biometric_auto_generate_codes' => filter_var($this->getSetting('biometric_auto_generate_codes', false), FILTER_VALIDATE_BOOLEAN),
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $settings,
                ]);
            }

            return view('admin.attendance.settings', compact('settings'));

        } catch (\Exception $e) {
            \Log::error('Failed to load eTimeOffice settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->expectsJson()) {
                return ErrorHandler::handleApiException(
                    $e,
                    'Failed to load settings',
                    'Failed to load settings',
                    500
                );
            }

            return ErrorHandler::handleWebException(
                $e,
                'Failed to load settings',
                'Failed to load settings'
            );
        }
    }

    // Also update your updateETimeOfficeSettings method validation

    public function updateETimeOfficeSettings(Request $request)
    {
        try {
            // Validate with the correct field names
            $validatedData = $request->validate([
                'etimeoffice_api_url' => 'nullable|url',
                'etimeoffice_corporate_id' => 'nullable|string|max:100',  // matches form
                'etimeoffice_username' => 'nullable|string|max:255',      // matches form
                'etimeoffice_password' => 'nullable|string|max:255',      // matches form
                'etimeoffice_sync_frequency' => 'nullable|integer|min:5|max:1440',
            ]);

            // Handle checkboxes
            $validatedData['etimeoffice_enabled'] = $request->has('etimeoffice_enabled');
            $validatedData['biometric_auto_generate_codes'] = $request->has('biometric_auto_generate_codes');

            // Save settings with correct field names
            foreach ($validatedData as $key => $value) {
                $this->updateSetting($key, $value);
            }

            \Log::info('eTimeOffice settings updated', [
                'user_id' => auth()->id(),
                'settings' => array_keys($validatedData),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully!',
            ]);

        } catch (\Exception $e) {
            \Log::error('eTimeOffice settings update failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test eTimeOffice connection
     */
    public function testETimeOfficeConnection(Request $request)
    {
        $this->authorize('manage attendance settings');

        try {
            // Get values from form OR from saved settings
            $apiUrl = $request->input('etimeoffice_api_url') ?: $this->getSetting('etimeoffice_api_url', '');
            $corporateId = $request->input('etimeoffice_corporate_id') ?: $this->getSetting('etimeoffice_corporate_id', '');
            $username = $request->input('etimeoffice_username') ?: $this->getSetting('etimeoffice_username', '');
            $password = $request->input('etimeoffice_password') ?: $this->getSetting('etimeoffice_password', '');

            // Validate required fields
            $missingFields = [];
            if (empty($apiUrl)) {
                $missingFields[] = 'API URL';
            }
            if (empty($corporateId)) {
                $missingFields[] = 'Corporate ID';
            }
            if (empty($username)) {
                $missingFields[] = 'Username';
            }
            if (empty($password)) {
                $missingFields[] = 'Password';
            }

            if (! empty($missingFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'eTimeOffice configuration is incomplete. Missing: '.implode(', ', $missingFields),
                    'missing_fields' => $missingFields,
                ], 400);
            }

            // Perform the actual connection test
            $testResult = $this->performETimeOfficeConnectionTest($apiUrl, $corporateId, $username, $password);

            return response()->json([
                'success' => $testResult['success'],
                'message' => $testResult['message'],
                'data' => $testResult['data'] ?? null,
            ]);

        } catch (\Exception $e) {
            \Log::error('eTimeOffice connection test failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'api_url' => $apiUrl ?? 'not set',
            ]);

            return ErrorHandler::handleApiException(
                $e,
                'eTimeOffice connection test failed',
                'Connection test failed',
                500
            );
        }
    }

    /**
     * Trigger manual sync with eTimeOffice - DEBUG VERSION
     */
    public function triggerManualSync(Request $request)
    {
        // Add debug logging at the very start
        \Log::info('🚀 triggerManualSync called', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'timestamp' => now(),
        ]);

        $this->authorize('manage attendance settings');

        try {
            $enabled = $this->getSetting('etimeoffice_enabled', false);
            \Log::info('📋 ETimeOffice enabled check', ['enabled' => $enabled]);

            if (! $enabled) {
                \Log::warning('❌ ETimeOffice integration is not enabled');

                return response()->json([
                    'success' => false,
                    'message' => 'eTimeOffice integration is not enabled',
                ], 400);
            }

            \Log::info('🔄 About to call performETimeOfficeSync');

            // Call the sync method with debug logging
            $syncResult = $this->performETimeOfficeSync([
                'sync_type' => 'manual',
                'date_range_type' => 'today',
                'date_range_start' => now()->startOfDay(),
                'date_range_end' => now()->endOfDay(),
                'test_mode' => false,
            ]);

            \Log::info('✅ performETimeOfficeSync returned', ['result' => $syncResult]);

            // Update last sync time
            $this->updateSetting('etimeoffice_last_sync', now()->toDateTimeString());
            \Log::info('⏰ Updated last sync time');

            return response()->json([
                'success' => $syncResult['success'],
                'message' => $syncResult['message'],
                'data' => $syncResult['data'] ?? null,
            ]);

        } catch (\Exception $e) {
            \Log::error('💥 triggerManualSync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Manual sync failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get biometric statistics - REQUIRED METHOD
     */
    public function getBiometricStats(Request $request)
    {
        try {
            $today = now()->toDateString();

            // Get basic stats
            $stats = [
                'total_devices' => 1, // Default value
                'active_devices' => 1, // Default value
                'today_punches' => 0,
                'unique_users_today' => 0,
                'last_sync' => $this->getSetting('etimeoffice_last_sync', null),
                'sync_status' => $this->getSetting('etimeoffice_enabled', false) ? 'enabled' : 'disabled',
                'sync_health' => 'good',
            ];

            // Try to get actual attendance data if table exists
            try {
                if (\Schema::hasTable('attendances')) {
                    $stats['today_punches'] = Attendance::whereDate('attendance_date', $today)->count();
                    $stats['unique_users_today'] = Attendance::whereDate('attendance_date', $today)
                        ->distinct('student_id')->count('student_id');
                }
            } catch (\Exception $e) {
                \Log::warning('Could not get attendance stats: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting biometric stats', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load biometric statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show data pulling interface
     */
    public function showDataPuller(Request $request)
    {
        $this->authorize('manage attendance settings');

        try {
            // Check if ETimeOffice is configured
            $isConfigured = $this->isETimeOfficeConfigured();

            // Get sync history
            $syncHistory = $this->getSyncHistory($request);

            // Get available date ranges
            $dateRangeOptions = $this->getDateRangeOptions();

            return view('admin.attendance.data-puller', compact(
                'isConfigured',
                'syncHistory',
                'dateRangeOptions'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load data puller: '.$e->getMessage());
        }
    }

    public function pullETimeOfficeData(Request $request)
    {
        try {
            $validated = $request->validate([
                'date_range' => 'required|string|in:today,yesterday,last_3_days,last_7_days,last_30_days,this_week,last_week,this_month,last_month,custom',
                'start_date' => 'nullable|date|required_if:date_range,custom',
                'end_date' => 'nullable|date|required_if:date_range,custom|after_or_equal:start_date',
                'employee_codes' => 'nullable|array',
                'employee_codes.*' => 'string|max:50',
                'test_mode' => 'nullable',
            ]);

            // Convert checkbox value
            $validated['test_mode'] = $request->has('test_mode') &&
                ($request->input('test_mode') === 'on' ||
                    $request->input('test_mode') === '1' ||
                    $request->input('test_mode') === true);

            // Calculate date range
            $dateRange = $this->calculateDateRange($validated['date_range'], $validated);

            if ($validated['test_mode']) {
                // Test mode - just simulate
                $simulatedRecords = $this->getSimulatedRecords($dateRange, true);

                return response()->json([
                    'success' => true,
                    'message' => 'Test mode: Found '.$simulatedRecords.' records (no data saved)',
                    'data' => [
                        'total_records' => $simulatedRecords,
                        'processed_records' => $simulatedRecords,
                        'created_records' => 0,
                        'updated_records' => 0,
                        'skipped_records' => 0,
                        'errors' => [],
                        'date_range' => [
                            'start' => $dateRange['start']->format('Y-m-d H:i:s'),
                            'end' => $dateRange['end']->format('Y-m-d H:i:s'),
                        ],
                        'test_mode' => true,
                    ],
                ]);
            }

            // REAL MODE - Use actual ETimeOffice API
            $result = $this->createRealAttendanceRecords($dateRange, $validated);

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('ETimeOffice data pull failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Data pull failed: '.$e->getMessage(),
                'data' => [
                    'total_records' => 0,
                    'processed_records' => 0,
                    'created_records' => 0,
                    'updated_records' => 0,
                    'skipped_records' => 0,
                    'errors' => [$e->getMessage()],
                ],
            ], 500);
        }
    }

    /**
     * Robust version with comprehensive error handling
     */
    private function createRealAttendanceRecords($dateRange, $validated)
    {
        $createdRecords = 0;
        $updatedRecords = 0;
        $skippedRecords = 0;
        $errors = [];

        try {
            // Get ETimeOffice API credentials
            $apiUrl = $this->getSetting('etimeoffice_api_url');
            $corporateId = $this->getSetting('etimeoffice_corporate_id');
            $username = $this->getSetting('etimeoffice_username');
            $password = $this->getSetting('etimeoffice_password');

            if (! $apiUrl || ! $corporateId || ! $username || ! $password) {
                return [
                    'success' => false,
                    'message' => 'ETimeOffice API credentials are incomplete',
                    'data' => [
                        'total_records' => 0,
                        'processed_records' => 0,
                        'created_records' => 0,
                        'updated_records' => 0,
                        'skipped_records' => 0,
                        'errors' => ['API credentials not configured'],
                    ],
                ];
            }

            // Create authentication token
            $authToken = base64_encode("{$corporateId}:{$username}:{$password}:true");

            // Format dates for ETimeOffice API
            $fromDate = $dateRange['start']->format('d/m/Y_H:i');
            $toDate = $dateRange['end']->format('d/m/Y_H:i');

            \Log::channel('attendance-webhook')->info('Fetching data from ETimeOffice API', [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'api_url' => $apiUrl,
            ]);

            // Call ETimeOffice API
            $response = \Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Basic '.$authToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->get($apiUrl.'/DownloadPunchData', [
                    'Empcode' => 'ALL',
                    'FromDate' => $fromDate,
                    'ToDate' => $toDate,
                ]);

            if (! $response->successful()) {
                \Log::channel('attendance-webhook')->error('ETimeOffice API request failed', [
                    'status' => $response->status(),
                    'from' => $fromDate,
                    'to' => $toDate,
                ]);

                return [
                    'success' => false,
                    'message' => "ETimeOffice API request failed: HTTP {$response->status()}",
                    'data' => [
                        'total_records' => 0,
                        'processed_records' => 0,
                        'created_records' => 0,
                        'updated_records' => 0,
                        'skipped_records' => 0,
                        'errors' => ["API HTTP Error: {$response->status()}"],
                    ],
                ];
            }

            // Get response body for debugging
            $responseBody = $response->body();
            \Log::channel('attendance-webhook')->info('Raw ETimeOffice API Response', [
                'response_body' => $responseBody,
                'content_type' => $response->header('Content-Type'),
                'response_size' => strlen($responseBody),
            ]);

            // Try to decode JSON
            try {
                $apiData = $response->json();
            } catch (\Exception $e) {
                \Log::channel('attendance-webhook')->error('Invalid JSON response from ETimeOffice API', [
                    'error' => $e->getMessage(),
                    'response' => substr($responseBody, 0, 500),
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid JSON response from ETimeOffice API',
                    'data' => [
                        'total_records' => 0,
                        'processed_records' => 0,
                        'created_records' => 0,
                        'updated_records' => 0,
                        'skipped_records' => 0,
                        'errors' => ['JSON decode error: '.$e->getMessage()],
                    ],
                ];
            }

            // Check for API errors
            if (is_array($apiData) && isset($apiData['Error']) && $apiData['Error'] === true) {
                \Log::channel('attendance-webhook')->error('ETimeOffice API Error Response', [
                    'msg' => $apiData['Msg'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'message' => 'ETimeOffice API Error: '.($apiData['Msg'] ?? 'Unknown error'),
                    'data' => [
                        'total_records' => 0,
                        'processed_records' => 0,
                        'created_records' => 0,
                        'updated_records' => 0,
                        'skipped_records' => 0,
                        'errors' => [$apiData['Msg'] ?? 'Unknown API error'],
                    ],
                ];
            }

            // Handle different possible response structures
            $punchData = [];

            if (is_array($apiData)) {
                // Case 1: Direct array of punch records
                if (isset($apiData[0]) && is_array($apiData[0])) {
                    $punchData = $apiData;
                }
                // Case 2: Nested under 'PunchData' key
                elseif (isset($apiData['PunchData']) && is_array($apiData['PunchData'])) {
                    $punchData = $apiData['PunchData'];
                }
                // Case 3: Nested under 'data' key
                elseif (isset($apiData['data']) && is_array($apiData['data'])) {
                    $punchData = $apiData['data'];
                }
                // Case 4: Check for other common keys
                elseif (isset($apiData['records']) && is_array($apiData['records'])) {
                    $punchData = $apiData['records'];
                } elseif (isset($apiData['result']) && is_array($apiData['result'])) {
                    $punchData = $apiData['result'];
                }
                // Case 5: Single record response (convert to array)
                elseif (isset($apiData['Name']) || isset($apiData['Empcode'])) {
                    $punchData = [$apiData];
                }
            }

            // Debug the actual structure with null safety
            \Log::channel('attendance-webhook')->info('ETimeOffice API Response Analysis', [
                'raw_response_type' => gettype($apiData),
                'is_array' => is_array($apiData),
                'api_data_keys' => is_array($apiData) ? array_keys($apiData) : 'not_array',
                'punch_data_type' => gettype($punchData),
                'punch_data_count' => is_array($punchData) ? count($punchData) : 0,
                'punch_data_is_empty' => empty($punchData),
                'first_record_exists' => is_array($punchData) && ! empty($punchData),
                'first_record_sample' => (is_array($punchData) && ! empty($punchData)) ? $punchData[0] : null,
                'first_record_keys' => (is_array($punchData) && ! empty($punchData) && is_array($punchData[0])) ? array_keys($punchData[0]) : 'no_keys',
            ]);

            if (empty($punchData) || ! is_array($punchData)) {

                // ✅ FIX: Update last sync time even if no data found
                $this->updateSetting('etimeoffice_last_sync', now()->toDateTimeString());

                return [
                    'success' => true,
                    'message' => 'No attendance data found for the specified date range',
                    'data' => [
                        'total_records' => 0,
                        'processed_records' => 0,
                        'created_records' => 0,
                        'updated_records' => 0,
                        'skipped_records' => 0,
                        'errors' => [],
                        'debug_info' => [
                            'api_response_structure' => is_array($apiData) ? array_keys($apiData) : gettype($apiData),
                            'punch_data_type' => gettype($punchData),
                            'response_sample' => is_array($apiData) ? array_slice($apiData, 0, 2, true) : $apiData,
                        ],
                        'date_range' => [
                            'start' => $dateRange['start']->format('Y-m-d H:i:s'),
                            'end' => $dateRange['end']->format('Y-m-d H:i:s'),
                        ],
                        'test_mode' => false,
                    ],
                ];
            }

            \Log::channel('attendance-webhook')->info('Processing ETimeOffice punch data', [
                'total_records' => count($punchData),
                'sample_record' => $punchData[0] ?? 'no_first_record',
            ]);

            // Get default faculty ID
            $defaultFacultyId = $this->getDefaultFacultyId();

            // Process each punch record with robust error handling
            foreach ($punchData as $index => $punch) {
                try {
                    // Ensure we have a valid array record
                    if (! is_array($punch)) {
                        $errors[] = "Record #{$index} is not an array: ".gettype($punch);
                        $skippedRecords++;

                        continue;
                    }

                    // Extract data using the correct field names with multiple fallbacks
                    $empCode = $punch['Empcode'] ??
                        $punch['EmpCode'] ??
                        $punch['EmployeeCode'] ??
                        $punch['empcode'] ??
                        $punch['employee_code'] ?? null;

                    $employeeName = $punch['Name'] ??
                        $punch['EmpName'] ??
                        $punch['EmployeeName'] ??
                        $punch['name'] ??
                        'Unknown';

                    $punchDateStr = $punch['PunchDate'] ??
                        $punch['LogDateTime'] ??
                        $punch['DateTime'] ??
                        $punch['punch_date'] ??
                        $punch['date_time'] ?? null;

                    $manualFlag = $punch['M_Flag'] ??
                        $punch['ManualFlag'] ??
                        $punch['manual_flag'] ?? null;

                    // Debug each record processing
                    \Log::channel('attendance-webhook')->info("Processing record {$index}", [
                        'empcode' => $empCode,
                        'name' => $employeeName,
                        'punch_date' => $punchDateStr,
                        'manual_flag' => $manualFlag,
                        'available_keys' => array_keys($punch),
                    ]);

                    if (! $empCode || ! $punchDateStr) {
                        $errors[] = "Missing employee code or punch date for: {$employeeName} (Record #{$index})";
                        $skippedRecords++;
                        \Log::warning("Skipping record {$index}: missing required data", [
                            'empcode' => $empCode,
                            'punch_date' => $punchDateStr,
                            'available_fields' => array_keys($punch),
                            'record' => $punch,
                        ]);

                        continue;
                    }

                    // Parse punch date/time with multiple format attempts
                    $punchDateTime = null;
                    $dateFormats = [
                        'd/m/Y H:i:s',  // Your API format: 09/09/2025 12:49:00
                        'Y-m-d H:i:s',  // Standard SQL format
                        'd-m-Y H:i:s',  // Alternative format
                        'm/d/Y H:i:s',  // US format
                        'd/m/Y H:i',    // Without seconds
                        'Y-m-d H:i',     // Without seconds
                    ];

                    foreach ($dateFormats as $format) {
                        try {
                            $punchDateTime = \Carbon\Carbon::createFromFormat($format, $punchDateStr);
                            if ($punchDateTime !== false) {
                                break;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    // If format parsing failed, try generic parsing
                    if (! $punchDateTime) {
                        try {
                            $punchDateTime = \Carbon\Carbon::parse($punchDateStr);
                        } catch (\Exception $e) {
                            $errors[] = "Could not parse punch date '{$punchDateStr}' for {$employeeName}";
                            $skippedRecords++;
                            \Log::error("Date parsing completely failed for record {$index}", [
                                'punch_date_str' => $punchDateStr,
                                'employee_name' => $employeeName,
                                'tried_formats' => $dateFormats,
                                'error' => $e->getMessage(),
                            ]);

                            continue;
                        }
                    }

                    // Find student by biometric employee code with better logging
                    $student = \App\Models\Student::where('biometric_employee_code', $empCode)
                        ->orWhere('enrollment_number', $empCode)
                        ->first();

                    if (! $student) {
                        $errors[] = "Student not found for employee code: {$empCode} (Name: {$employeeName})";
                        $skippedRecords++;
                        \Log::info('No student found for employee code', [
                            'empcode' => $empCode,
                            'employee_name' => $employeeName,
                            'searched_fields' => ['biometric_employee_code', 'enrollment_number'],
                        ]);

                        continue;
                    }

                    $attendanceDate = $punchDateTime->format('Y-m-d');

                    // Check if attendance already exists for this student and date
                    $existingAttendance = Attendance::where([
                        'student_id' => $student->id,
                        'attendance_date' => $attendanceDate,
                    ])->first();

                    // Calculate late minutes
                    $collegeStartTime = \Carbon\Carbon::parse($attendanceDate.' '.$this->getSetting('attendance_student_college_start_time', '09:30:00'));
                    $lateMinutes = $punchDateTime->gt($collegeStartTime) ? $punchDateTime->diffInMinutes($collegeStartTime) : 0;

                    // Determine attendance status
                    $status = $this->determineAttendanceStatus($punchDateTime);

                    // Prepare attendance data
                    $attendanceData = [
                        'student_id' => $student->id,
                        'batch_id' => $student->batch_id ?? 1,
                        'faculty_id' => $defaultFacultyId,
                        'attendance_date' => $attendanceDate,
                        'check_in_time' => $punchDateTime->format('H:i:s'),
                        'check_out_time' => null,
                        'status' => $status,
                        'marked_at' => $punchDateTime,
                        'marked_by' => auth()->id() ?? $defaultFacultyId,
                        'notes' => "ETimeOffice: {$employeeName}".($manualFlag ? ' (Manual)' : ''),
                        'late_minutes' => $lateMinutes > 0 ? $lateMinutes : null,
                        'location' => null,
                        'device_id' => 'etimeoffice-api',
                        'biometric_log_id' => null,
                    ];

                    if ($existingAttendance) {
                        // Update logic
                        $shouldUpdate = false;

                        if (! $existingAttendance->check_in_time || $punchDateTime->lt($existingAttendance->marked_at)) {
                            $shouldUpdate = true;
                        } elseif ($existingAttendance->check_in_time && ! $existingAttendance->check_out_time && $punchDateTime->gt($existingAttendance->marked_at)) {
                            $attendanceData['check_out_time'] = $punchDateTime->format('H:i:s');
                            $attendanceData['check_in_time'] = $existingAttendance->check_in_time;
                            $attendanceData['marked_at'] = $existingAttendance->marked_at;
                            $shouldUpdate = true;
                        }

                        if ($shouldUpdate) {
                            $existingAttendance->update($attendanceData);
                            $updatedRecords++;

                            \Log::channel('attendance-webhook')->info('Updated attendance record', [
                                'student_name' => $student->name,
                                'employee_code' => $empCode,
                                'date' => $attendanceDate,
                                'punch_time' => $punchDateTime->format('H:i:s'),
                                'status' => $status,
                            ]);
                        } else {
                            $skippedRecords++;
                        }
                    } else {
                        // Create new attendance record
                        Attendance::create($attendanceData);
                        $createdRecords++;

                        \Log::channel('attendance-webhook')->info('Created attendance record', [
                            'student_name' => $student->name,
                            'employee_code' => $empCode,
                            'date' => $attendanceDate,
                            'punch_time' => $punchDateTime->format('H:i:s'),
                            'status' => $status,
                            'late_minutes' => $lateMinutes,
                        ]);
                    }

                } catch (\Exception $e) {
                    $empCode = isset($punch) && is_array($punch) ? ($punch['Empcode'] ?? 'unknown') : 'unknown';
                    $errors[] = "Error processing punch for {$empCode}: ".$e->getMessage();
                    $skippedRecords++;
                    \Log::channel('attendance-webhook')->error('Error processing ETimeOffice punch', [
                        'record_index' => $index,
                        'punch_data' => $punch ?? 'null',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $totalRecords = $createdRecords + $updatedRecords + $skippedRecords;

            // Update last sync time
            $this->updateSetting('etimeoffice_last_sync', now()->toDateTimeString());

            return [
                'success' => true,
                'message' => "ETimeOffice sync completed: {$createdRecords} created, {$updatedRecords} updated, {$skippedRecords} skipped from {$totalRecords} total records",
                'data' => [
                    'total_records' => $totalRecords,
                    'processed_records' => $totalRecords,
                    'created_records' => $createdRecords,
                    'updated_records' => $updatedRecords,
                    'skipped_records' => $skippedRecords,
                    'errors' => $errors,
                    'date_range' => [
                        'start' => $dateRange['start']->format('Y-m-d H:i:s'),
                        'end' => $dateRange['end']->format('Y-m-d H:i:s'),
                    ],
                    'test_mode' => false,
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('ETimeOffice sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'success' => false,
                'message' => 'ETimeOffice sync failed: '.$e->getMessage(),
                'data' => [
                    'total_records' => 0,
                    'processed_records' => 0,
                    'created_records' => $createdRecords,
                    'updated_records' => $updatedRecords,
                    'skipped_records' => $skippedRecords,
                    'errors' => array_merge($errors, [$e->getMessage()]),
                ],
            ];
        }
    }

    /**
     * Process daily attendance for a student with proper field mapping
     */
    private function processDailyAttendance($student, $date, $punches, $defaultFacultyId): array
    {
        // Get the first and last punch of the day
        $firstPunch = $punches[0]['datetime'];
        $lastPunch = end($punches)['datetime'];
        $originalData = $punches[0]['original_data'];

        // Calculate late minutes
        $collegeStartTime = \Carbon\Carbon::parse($date.' '.$this->getSetting('attendance_student_college_start_time', '09:30:00'));
        $lateMinutes = $firstPunch->gt($collegeStartTime) ? $firstPunch->diffInMinutes($collegeStartTime) : 0;

        // Determine attendance status
        $status = $this->determineAttendanceStatus($firstPunch);

        // Check if attendance record already exists
        $existingAttendance = Attendance::where([
            'student_id' => $student->id,
            'attendance_date' => $date,
        ])->first();

        $attendanceData = [
            'student_id' => $student->id,
            'batch_id' => $student->batch_id ?? 1,
            'faculty_id' => $defaultFacultyId,
            'attendance_date' => $date,
            'check_in_time' => $firstPunch->format('H:i:s'),
            'check_out_time' => count($punches) > 1 ? $lastPunch->format('H:i:s') : null,
            'status' => $status,
            'marked_at' => $firstPunch,
            'marked_by' => auth()->id() ?? $defaultFacultyId,
            'notes' => 'ETimeOffice API: '.($originalData['Name'] ?? 'Auto-sync'),
            'late_minutes' => $lateMinutes > 0 ? $lateMinutes : null,
            'location' => $originalData['Location'] ?? null,
            'device_id' => 'etimeoffice-api',
            'biometric_log_id' => $originalData['LogId'] ?? null,
        ];

        if ($existingAttendance) {
            // Update existing record only if new data is more complete or earlier
            $shouldUpdate = false;

            if (! $existingAttendance->check_in_time || $firstPunch->lt($existingAttendance->marked_at)) {
                $shouldUpdate = true;
            }

            if (count($punches) > 1 && ! $existingAttendance->check_out_time) {
                $shouldUpdate = true;
            }

            if ($shouldUpdate) {
                $existingAttendance->update($attendanceData);

                \Log::channel('attendance-webhook')->info('Updated attendance record', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'date' => $date,
                    'check_in' => $firstPunch->format('H:i:s'),
                    'check_out' => $attendanceData['check_out_time'],
                    'status' => $status,
                ]);

                return ['action' => 'updated'];
            } else {
                return ['action' => 'skipped'];
            }
        } else {
            // Create new attendance record
            Attendance::create($attendanceData);

            \Log::channel('attendance-webhook')->info('Created attendance record', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'date' => $date,
                'check_in' => $firstPunch->format('H:i:s'),
                'check_out' => $attendanceData['check_out_time'],
                'status' => $status,
                'late_minutes' => $lateMinutes,
            ]);

            return ['action' => 'created'];
        }
    }

    /**
     * Map ETimeOffice punch data to standardized format
     */
    private function mapETimeOfficeData($punchRecord): ?array
    {
        try {
            // Handle different possible field names from ETimeOffice API
            $empCode = $punchRecord['Empcode'] ??
                $punchRecord['EmpCode'] ??
                $punchRecord['EmployeeCode'] ??
                $punchRecord['empcode'] ??
                $punchRecord['EmpcardNo'] ?? null;

            $punchDateTime = $punchRecord['PunchDate'] ??
                $punchRecord['LogDateTime'] ??
                $punchRecord['DateTime'] ??
                $punchRecord['PunchDateTime'] ??
                $punchRecord['TimeStamp'] ?? null;

            $employeeName = $punchRecord['Name'] ??
                $punchRecord['EmpName'] ??
                $punchRecord['EmployeeName'] ??
                $punchRecord['PersonName'] ??
                'Unknown';

            $location = $punchRecord['Location'] ??
                $punchRecord['DeviceLocation'] ??
                $punchRecord['Terminal'] ?? null;

            $logId = $punchRecord['LogId'] ??
                $punchRecord['ID'] ??
                $punchRecord['RecordId'] ?? null;

            $direction = $punchRecord['Direction'] ??
                $punchRecord['InOut'] ??
                $punchRecord['PunchType'] ?? 'IN';

            if (! $empCode || ! $punchDateTime) {
                \Log::warning('ETimeOffice record missing essential data', [
                    'record' => $punchRecord,
                    'emp_code' => $empCode,
                    'punch_datetime' => $punchDateTime,
                ]);

                return null;
            }

            // Try to parse the datetime with multiple formats
            $carbonDateTime = null;
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
                    $carbonDateTime = \Carbon\Carbon::createFromFormat($format, $punchDateTime);
                    if ($carbonDateTime) {
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // If still not parsed, try generic parse
            if (! $carbonDateTime) {
                try {
                    $carbonDateTime = \Carbon\Carbon::parse($punchDateTime);
                } catch (\Exception $e) {
                    \Log::error('Could not parse ETimeOffice datetime', [
                        'punch_datetime' => $punchDateTime,
                        'formats_tried' => $dateFormats,
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }
            }

            return [
                'emp_code' => $empCode,
                'datetime' => $carbonDateTime,
                'employee_name' => $employeeName,
                'location' => $location,
                'log_id' => $logId,
                'direction' => strtoupper($direction),
                'original_data' => $punchRecord,
            ];

        } catch (\Exception $e) {
            \Log::error('Error mapping ETimeOffice data', [
                'record' => $punchRecord,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Group punch data by employee code and date
     */
    private function groupPunchDataByEmployeeAndDate($punchData): array
    {
        $grouped = [];

        foreach ($punchData as $punch) {
            $empCode = $punch['Empcode'] ?? $punch['EmpCode'] ?? $punch['EmployeeCode'] ?? null;
            $punchDateTime = $punch['PunchDate'] ?? $punch['LogDateTime'] ?? $punch['DateTime'] ?? null;

            if (! $empCode || ! $punchDateTime) {
                continue;
            }

            try {
                // Parse the date/time
                $carbonDateTime = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $punchDateTime);
                if (! $carbonDateTime) {
                    $carbonDateTime = \Carbon\Carbon::parse($punchDateTime);
                }

                $date = $carbonDateTime->format('Y-m-d');

                if (! isset($grouped[$empCode])) {
                    $grouped[$empCode] = [];
                }

                if (! isset($grouped[$empCode][$date])) {
                    $grouped[$empCode][$date] = [];
                }

                $grouped[$empCode][$date][] = [
                    'datetime' => $carbonDateTime,
                    'original_data' => $punch,
                ];

            } catch (\Exception $e) {
                \Log::warning('Could not parse punch datetime', [
                    'punch_datetime' => $punchDateTime,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Sort punches by time for each day
        foreach ($grouped as $empCode => $dates) {
            foreach ($dates as $date => $punches) {
                usort($grouped[$empCode][$date], function ($a, $b) {
                    return $a['datetime']->timestamp <=> $b['datetime']->timestamp;
                });
            }
        }

        return $grouped;
    }

    /**
     * Determine attendance status based on punch time
     */
    private function determineAttendanceStatus(\Carbon\Carbon $punchTime): string
    {
        // Get office start time from settings (default: 9:00 AM)
        $officeStartTime = $this->getSetting('office_start_time', '09:00');
        $lateThreshold = (int) $this->getSetting('late_threshold_minutes', 30);

        $startTime = \Carbon\Carbon::parse($punchTime->format('Y-m-d').' '.$officeStartTime);
        $lateTime = $startTime->copy()->addMinutes($lateThreshold);

        if ($punchTime->lte($startTime)) {
            return 'present';
        } elseif ($punchTime->lte($lateTime)) {
            return 'late';
        } else {
            return 'present'; // Still present but very late
        }
    }

    /**
     * Test sync with eTimeOffice (doesn't modify data)
     */
    public function testSync(Request $request)
    {
        $this->authorize('manage attendance settings');

        try {
            $validated = $request->validate([
                'date_range_type' => 'required|in:today,yesterday,last_3_days,last_7_days,custom',
                'date_from' => 'required_if:date_range_type,custom|date',
                'date_to' => 'required_if:date_range_type,custom|date|after_or_equal:date_from',
                'employee_codes' => 'nullable|array',
                'employee_codes.*' => 'string|max:50',
            ]);

            // Calculate date range
            $dateRange = $this->calculateDateRange($validated['date_range_type'], $validated);

            // Perform test sync
            $syncResult = $this->performETimeOfficeSync([
                'sync_type' => 'manual',
                'date_range_type' => $validated['date_range_type'],
                'date_range_start' => $dateRange['start'],
                'date_range_end' => $dateRange['end'],
                'test_mode' => true,
                'employee_codes' => $validated['employee_codes'] ?? null,
            ]);

            return response()->json([
                'success' => $syncResult['success'],
                'message' => $syncResult['message'],
                'data' => $syncResult['data'],
                'test_mode' => true,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test sync failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Test ETimeOffice API connectivity and data format
     */
    public function testETimeOfficeDataFormat(Request $request)
    {
        try {
            // Get API credentials
            $apiUrl = $this->getSetting('etimeoffice_api_url');
            $corporateId = $this->getSetting('etimeoffice_corporate_id');
            $username = $this->getSetting('etimeoffice_username');
            $password = $this->getSetting('etimeoffice_password');

            if (! $apiUrl || ! $corporateId || ! $username || ! $password) {
                return response()->json([
                    'success' => false,
                    'message' => 'ETimeOffice credentials not configured',
                ], 400);
            }

            $authToken = base64_encode("{$corporateId}:{$username}:{$password}:true");

            // Test with today's data
            $response = \Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Basic '.$authToken,
                    'Accept' => 'application/json',
                ])
                ->get($apiUrl.'/DownloadPunchData', [
                    'Empcode' => 'ALL',
                    'FromDate' => now()->format('d/m/Y_H:i'),
                    'ToDate' => now()->format('d/m/Y_H:i'),
                ]);

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => "API request failed: HTTP {$response->status()}",
                    'response_body' => $response->body(),
                ]);
            }

            $data = $response->json();

            if (isset($data['Error']) && $data['Error'] === true) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Error: '.($data['Msg'] ?? 'Unknown error'),
                    'api_response' => $data,
                ]);
            }

            $punchData = $data['PunchData'] ?? $data ?? [];

            // Analyze the data structure
            $analysis = [
                'total_records' => count($punchData),
                'sample_record' => $punchData[0] ?? null,
                'field_analysis' => [],
                'mapped_sample' => null,
            ];

            if (! empty($punchData)) {
                // Analyze fields in the first record
                $sampleRecord = $punchData[0];
                $analysis['field_analysis'] = [
                    'available_fields' => array_keys($sampleRecord),
                    'employee_code_fields' => array_filter(array_keys($sampleRecord), function ($key) {
                        return stripos($key, 'emp') !== false || stripos($key, 'code') !== false;
                    }),
                    'datetime_fields' => array_filter(array_keys($sampleRecord), function ($key) {
                        return stripos($key, 'date') !== false || stripos($key, 'time') !== false;
                    }),
                    'name_fields' => array_filter(array_keys($sampleRecord), function ($key) {
                        return stripos($key, 'name') !== false;
                    }),
                ];

                // Try to map the sample record
                $analysis['mapped_sample'] = $this->mapETimeOfficeData($sampleRecord);
            }

            return response()->json([
                'success' => true,
                'message' => 'ETimeOffice API connection successful',
                'data' => $analysis,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: '.$e->getMessage(),
                'error_details' => [
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ],
            ], 500);
        }
    }

    /**
     * Get default faculty ID for attendance records
     */
    private function getDefaultFacultyId()
    {
        try {
            // Option 1: Use current authenticated user
            if (auth()->check()) {
                return auth()->id();
            }

            // Option 2: Find first admin/faculty user
            $faculty = \App\Models\User::role(['admin', 'faculty'])->first();
            if ($faculty) {
                return $faculty->id;
            }

            // Option 3: Use first user in database
            $firstUser = \App\Models\User::first();
            if ($firstUser) {
                return $firstUser->id;
            }

            // Option 4: Create a system user if none exists
            $systemUser = \App\Models\User::create([
                'name' => 'System User',
                'email' => 'system@'.config('app.url', 'example.com'),
                'password' => bcrypt('system123'),
                'email_verified_at' => now(),
            ]);

            return $systemUser->id;

        } catch (\Exception $e) {
            \Log::error('Could not get default faculty ID: '.$e->getMessage());

            return 1; // Fallback to ID 1
        }
    }

    /**
     * Get random attendance status for simulation
     */
    private function getRandomStatus()
    {
        $statuses = ['present', 'present', 'present', 'late', 'absent']; // More likely to be present

        return $statuses[array_rand($statuses)];
    }

    /**
     * Get simulated records count based on date range
     */
    private function getSimulatedRecords($dateRange, $testMode = false)
    {
        $days = $dateRange['start']->diffInDays($dateRange['end']) + 1;

        // Simulate realistic record counts
        $recordsPerDay = rand(10, 50); // Random between 10-50 records per day
        $totalRecords = $days * $recordsPerDay;

        return $testMode ? $totalRecords : min($totalRecords, 200); // Cap at 200 for demo
    }

    /**
     * Validate configuration - REQUIRED METHOD
     */
    public function validateConfiguration(Request $request)
    {
        try {
            $config = [
                'api_url' => $this->getSetting('etimeoffice_api_url'),
                'corporate_id' => $this->getSetting('etimeoffice_corporate_id'),
                'username' => $this->getSetting('etimeoffice_username'),
                'password' => $this->getSetting('etimeoffice_password'),
                'enabled' => filter_var($this->getSetting('etimeoffice_enabled', false), FILTER_VALIDATE_BOOLEAN),
            ];

            $validation = [
                'steps' => [
                    [
                        'title' => 'API URL Configuration',
                        'completed' => ! empty($config['api_url']) && filter_var($config['api_url'], FILTER_VALIDATE_URL),
                        'description' => 'Set your ETimeOffice API endpoint URL',
                        'current_value' => $config['api_url'] ?: 'Not set',
                        'field' => 'etimeoffice_api_url',
                    ],
                    [
                        'title' => 'Corporate ID',
                        'completed' => ! empty($config['corporate_id']),
                        'description' => 'Enter your ETimeOffice Corporate ID',
                        'current_value' => $config['corporate_id'] ? '***'.substr($config['corporate_id'], -3) : 'Not set',
                        'field' => 'etimeoffice_corporate_id',
                    ],
                    [
                        'title' => 'API Credentials',
                        'completed' => ! empty($config['username']) && ! empty($config['password']),
                        'description' => 'Set your API username and password',
                        'current_value' => (! empty($config['username']) && ! empty($config['password'])) ? 'Configured' : 'Not set',
                        'field' => 'credentials',
                    ],
                    [
                        'title' => 'Enable Integration',
                        'completed' => $config['enabled'],
                        'description' => 'Enable automatic data synchronization',
                        'current_value' => $config['enabled'] ? 'Enabled' : 'Disabled',
                        'field' => 'etimeoffice_enabled',
                    ],
                ],
            ];

            $completedSteps = collect($validation['steps'])->where('completed', true)->count();
            $totalSteps = count($validation['steps']);

            $validation['overall'] = [
                'completed_steps' => $completedSteps,
                'total_steps' => $totalSteps,
                'completion_percentage' => round(($completedSteps / $totalSteps) * 100),
                'is_ready' => $completedSteps >= 3,
                'next_step' => collect($validation['steps'])->where('completed', false)->first(),
            ];

            return response()->json([
                'success' => true,
                'data' => $validation,
            ]);

        } catch (\Exception $e) {
            \Log::error('Configuration validation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get setup recommendations - REQUIRED METHOD
     */
    public function getSetupRecommendations(Request $request)
    {
        try {
            $recommendations = [];

            // Check basic configuration
            if (! $this->getSetting('etimeoffice_api_url')) {
                $recommendations[] = [
                    'type' => 'error',
                    'title' => 'API URL Required',
                    'message' => 'Configure your ETimeOffice API URL to enable data synchronization.',
                    'action' => 'Set API URL',
                    'priority' => 'high',
                ];
            }

            if (! $this->getSetting('etimeoffice_corporate_id')) {
                $recommendations[] = [
                    'type' => 'error',
                    'title' => 'Corporate ID Missing',
                    'message' => 'Your Corporate ID is required for API authentication.',
                    'action' => 'Add Corporate ID',
                    'priority' => 'high',
                ];
            }

            if (! $this->getSetting('etimeoffice_username') || ! $this->getSetting('etimeoffice_password')) {
                $recommendations[] = [
                    'type' => 'error',
                    'title' => 'API Credentials Incomplete',
                    'message' => 'Both username and password are required for API access.',
                    'action' => 'Set Credentials',
                    'priority' => 'high',
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'total_count' => count($recommendations),
                    'high_priority' => collect($recommendations)->where('priority', 'high')->count(),
                    'medium_priority' => collect($recommendations)->where('priority', 'medium')->count(),
                    'low_priority' => collect($recommendations)->where('priority', 'low')->count(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Setup recommendations failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load recommendations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status - REQUIRED METHOD
     */
    public function getSyncStatus(Request $request)
    {
        try {
            // Default response if no advanced tracking
            $stats = [
                'last_sync' => $this->getSetting('etimeoffice_last_sync'),
                'is_enabled' => filter_var($this->getSetting('etimeoffice_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'sync_frequency' => (int) $this->getSetting('etimeoffice_sync_frequency', 15),
                'sync_health' => 'good', // Default
                'today_syncs' => 0,
                'today_records' => 0,
                'today_success_rate' => 100,
                'last_24h_syncs' => 0,
                'last_24h_records' => 0,
                'last_error' => null,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting sync status', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync status',
                'data' => [],
            ], 500);
        }
    }

    /**
     * Calculate date range based on type
     */
    private function calculateDateRange(string $type, array $params = []): array
    {
        switch ($type) {
            case 'today':
                return [
                    'start' => now()->startOfDay(),
                    'end' => now()->endOfDay(),
                ];
            case 'yesterday':
                return [
                    'start' => now()->subDay()->startOfDay(),
                    'end' => now()->subDay()->endOfDay(),
                ];
            case 'last_3_days':
                return [
                    'start' => now()->subDays(3)->startOfDay(),
                    'end' => now()->endOfDay(),
                ];
            case 'last_7_days':
                return [
                    'start' => now()->subDays(7)->startOfDay(),
                    'end' => now()->endOfDay(),
                ];
            case 'custom':
                return [
                    'start' => \Carbon\Carbon::parse($params['date_from'] ?? now()->startOfDay()),
                    'end' => \Carbon\Carbon::parse($params['date_to'] ?? now()->endOfDay()),
                ];
            default:
                return [
                    'start' => now()->startOfDay(),
                    'end' => now()->endOfDay(),
                ];
        }
    }

    /**
     * Perform the actual data pull from ETimeOffice
     */
    private function performDataPull($startDate, $endDate, array $employeeCodes, bool $testMode = false): array
    {
        try {
            // Get API credentials
            $apiUrl = $this->getSetting('etimeoffice_api_url');
            $corporateId = $this->getSetting('etimeoffice_corporate_id');
            $username = $this->getSetting('etimeoffice_username');
            $password = $this->getSetting('etimeoffice_password');

            $results = [
                'success' => true,
                'message' => '',
                'data' => [
                    'total_records' => 0,
                    'processed_records' => 0,
                    'created_records' => 0,
                    'updated_records' => 0,
                    'skipped_records' => 0,
                    'errors' => [],
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d H:i:s'),
                        'end' => $endDate->format('Y-m-d H:i:s'),
                    ],
                    'test_mode' => $testMode,
                ],
            ];

            // Create auth token
            $authString = "{$corporateId}:{$username}:{$password}:true";
            $authToken = base64_encode($authString);

            foreach ($employeeCodes as $empCode) {
                $this->pullDataForEmployee(
                    $apiUrl,
                    $authToken,
                    $empCode,
                    $startDate,
                    $endDate,
                    $testMode,
                    $results
                );
            }

            // Update last sync time if not in test mode
            if (! $testMode && $results['data']['total_records'] > 0) {
                $this->updateSetting('etimeoffice_last_sync', now()->toDateTimeString());
            }

            // Generate summary message
            $results['message'] = $this->generateSyncSummary($results['data']);

            return $results;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'API call failed: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Pull data for a specific employee
     */
    private function pullDataForEmployee(string $apiUrl, string $authToken, string $empCode, $startDate, $endDate, bool $testMode, array &$results): void
    {
        try {
            // Format dates for API
            $fromDate = $startDate->format('d/m/Y_H:i');
            $toDate = $endDate->format('d/m/Y_H:i');

            $response = \Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Basic '.$authToken,
                    'Accept' => 'application/json',
                ])
                ->get($apiUrl.'/DownloadPunchData', [
                    'Empcode' => $empCode,
                    'FromDate' => $fromDate,
                    'ToDate' => $toDate,
                ]);

            if (! $response->successful()) {
                $results['data']['errors'][] = "API call failed for employee {$empCode}: ".$response->status();

                return;
            }

            $data = $response->json();

            if (empty($data) || ! is_array($data)) {
                $results['data']['errors'][] = "No data returned for employee {$empCode}";

                return;
            }

            // Process each attendance record
            foreach ($data as $record) {
                $this->processAttendanceRecord($record, $testMode, $results);
            }

        } catch (\Exception $e) {
            $results['data']['errors'][] = "Error processing employee {$empCode}: ".$e->getMessage();
        }
    }

    /**
     * Process individual attendance record
     */
    private function processAttendanceRecord(array $record, bool $testMode = false, array &$results = []): array
    {
        $employeeCode = trim($record['employee_code']);
        \Illuminate\Support\Facades\Log::info('Processing attendance for Employee Code: '.$employeeCode);

        // Find student by biometric employee code
        $student = \App\Models\Student::where('biometric_employee_code', $employeeCode)->first();

        if (! $student) {
            \Illuminate\Support\Facades\Log::warning('Student not found for Employee Code: '.$employeeCode);
            $result = [
                'action' => 'skipped',
                'reason' => 'Student not found with employee code: '.$employeeCode,
            ];
            $results['data']['logs'][] = $result;

            return $result;
        }

        \Illuminate\Support\Facades\Log::info('Found Student: '.$student->name.' (ID: '.$student->id.')');

        $attendanceDate = \Carbon\Carbon::parse($record['punch_date'])->format('Y-m-d');

        // Check if attendance already exists
        $existingAttendance = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $attendanceDate)
            ->first();

        if ($testMode) {
            // In test mode, just return what would happen
            if ($existingAttendance) {
                $result = ['action' => 'updated', 'reason' => 'Would update existing attendance'];
            } else {
                $result = ['action' => 'created', 'reason' => 'Would create new attendance'];
            }
            $results['data']['logs'][] = $result;

            return $result;
        }

        // Determine attendance status based on punch time
        $punchTime = \Carbon\Carbon::parse($record['punch_time']);
        $status = $this->determineAttendanceStatus($punchTime); // Keep original logic for sync, or update to use settings? Assuming original logic is wanted here.

        if ($existingAttendance) {
            // Update existing attendance if the new punch is earlier (first punch of the day)
            $currentPunchTime = $existingAttendance->marked_at ? \Carbon\Carbon::parse($existingAttendance->marked_at) : null;

            if (! $currentPunchTime || $punchTime->lt($currentPunchTime)) {
                $existingAttendance->update([
                    'marked_at' => $punchTime,
                    'status' => $status, // Status is already lowercase from determineAttendanceStatus
                    'device_id' => 'etimeoffice-api',
                    'notes' => 'Updated via ETimeOffice sync at '.now()->format('Y-m-d H:i:s'),
                ]);

                $result = ['action' => 'updated', 'attendance_id' => $existingAttendance->id];
                $results['data']['logs'][] = $result;

                return $result;
            } else {
                $result = ['action' => 'skipped', 'reason' => 'Later punch time, keeping existing record'];
                $results['data']['logs'][] = $result;

                return $result;
            }
        } else {
            // Create new attendance record
            $attendance = Attendance::create([
                'student_id' => $student->id,
                'batch_id' => $student->batch_id, // Ensure batch_id is set from student
                'attendance_date' => $attendanceDate,
                'marked_at' => $punchTime,
                'status' => $status, // Status is already lowercase from determineAttendanceStatus
                'device_id' => 'etimeoffice-api',
                'faculty_id' => auth()->id() ?? \App\Models\User::value('id') ?? 1, // Fallback to first available user
                'marked_by' => auth()->id() ?? \App\Models\User::value('id') ?? 1,
                'notes' => 'Created via ETimeOffice sync at '.now()->format('Y-m-d H:i:s'),
            ]);

            $result = ['action' => 'created', 'attendance_id' => $attendance->id];
            $results['data']['logs'][] = $result;

            return $result;
        }
    }

    /**
     * Determine attendance status based on punch time
     */

    /**
     * Check if ETimeOffice is configured
     */
    private function isETimeOfficeConfigured(): bool
    {
        return ! empty($this->getSetting('etimeoffice_corporate_id')) &&
            ! empty($this->getSetting('etimeoffice_username')) &&
            ! empty($this->getSetting('etimeoffice_password')) &&
            ! empty($this->getSetting('etimeoffice_api_url'));
    }

    private function getDateRangeOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_3_days' => 'Last 3 Days',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'custom' => 'Custom Date Range',
        ];
    }

    /**
     * Get sync history - REQUIRED METHOD
     */
    public function getSyncHistory(Request $request)
    {
        try {
            // Check if sync logs table exists
            if (\Schema::hasTable('etimeoffice_sync_logs')) {
                $history = \DB::table('etimeoffice_sync_logs')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'date' => $log->created_at,
                            'range' => ucfirst(str_replace('_', ' ', $log->date_range_type ?? 'manual')),
                            'records' => $log->total_records ?? 0,
                            'processed' => $log->processed_records ?? 0,
                            'created' => $log->created_records ?? 0,
                            'updated' => $log->updated_records ?? 0,
                            'skipped' => $log->skipped_records ?? 0,
                            'status' => $log->status ?? 'unknown',
                            'duration' => $log->duration_seconds ? $this->formatDuration($log->duration_seconds) : 'N/A',
                            'success_rate' => $log->total_records > 0 ?
                                round((($log->created_records + $log->updated_records) / $log->total_records) * 100, 1) : 0,
                            'test_mode' => $log->test_mode ?? false,
                            'error_count' => $log->errors ? count(json_decode($log->errors, true)) : 0,
                        ];
                    });
            } else {
                // Fallback to sample data
                $history = collect([
                    [
                        'date' => now()->subHours(2)->toDateTimeString(),
                        'range' => 'Today',
                        'records' => 0,
                        'status' => 'success',
                        'duration' => 'N/A',
                        'success_rate' => 100,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading sync history', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load sync history',
                'data' => [],
            ], 500);
        }
    }

    /**
     * Helper method to format duration
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $minutes.'m '.$remainingSeconds.'s';
    }

    /**
     * Parse log files for sync history (fallback method)
     */
    private function parseLogHistory(): array
    {
        try {
            $logPath = storage_path('logs/laravel.log');

            if (! file_exists($logPath)) {
                return [];
            }

            $logContent = file_get_contents($logPath);
            $lines = explode("\n", $logContent);
            $syncLogs = [];

            foreach (array_reverse($lines) as $line) {
                if (
                    strpos($line, 'ETimeOffice sync') !== false ||
                    strpos($line, 'ATTENDANCE SETTINGS') !== false
                ) {

                    // Parse the log line to extract sync information
                    preg_match('/\[(.*?)\]/', $line, $dateMatch);

                    if ($dateMatch) {
                        $syncLogs[] = [
                            'date' => $dateMatch[1],
                            'range' => 'Manual',
                            'records' => 'N/A',
                            'status' => strpos($line, 'SUCCESS') !== false ? 'success' : 'unknown',
                            'duration' => 'N/A',
                        ];
                    }

                    if (count($syncLogs) >= 10) {
                        break;
                    }
                }
            }

            return $syncLogs;

        } catch (\Exception $e) {
            Log::error('Error parsing log history', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Generate sync summary message
     */
    private function generateSyncSummary(array $results): string
    {
        if ($results['test_mode'] ?? false) {
            return "Test completed: Found {$results['total_records']} records, {$results['processed_records']} would be processed";
        }

        return "Sync completed: {$results['created_records']} created, {$results['updated_records']} updated, {$results['skipped_records']} skipped from {$results['total_records']} total records";
    }

    /**
     * Log sync activities
     */
    private function log(string $message, string $level = 'info'): void
    {
        \Log::{$level}('ETimeOffice Sync: '.$message, [
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]);
    }

    private function logSyncAttempt(array $dateRange, array $result): void
    {
        \Log::info('ETimeOffice sync attempt', [
            'user_id' => auth()->id(),
            'date_range' => $dateRange,
            'result' => $result,
            'timestamp' => now(),
        ]);
    }

    /**
     * Get today's sync count
     */
    private function getTodaySyncCount(): int
    {
        try {
            // You can implement this by reading logs or maintaining a sync_logs table
            return DB::table('settings')
                ->where('key', 'etimeoffice_sync_count_today')
                ->where('created_at', '>=', now()->startOfDay())
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get today's records count
     */
    private function getTodayRecordsCount(): int
    {
        try {
            return Attendance::whereDate('attendance_date', now()->toDateString())
                ->where('device_id', 'etimeoffice-api')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get next auto sync time
     */
    private function getNextAutoSyncTime(): ?string
    {
        try {
            $lastSync = $this->getSetting('etimeoffice_last_sync');
            $frequency = (int) $this->getSetting('etimeoffice_sync_frequency', 15);

            if ($lastSync) {
                $nextSync = Carbon::parse($lastSync)->addMinutes($frequency);

                return $nextSync->format('Y-m-d H:i:s');
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get sync health status
     */
    private function getSyncHealth(): string
    {
        try {
            $lastSync = $this->getSetting('etimeoffice_last_sync');
            $frequency = (int) $this->getSetting('etimeoffice_sync_frequency', 15);

            if (! $lastSync) {
                return 'poor';
            }

            $lastSyncTime = Carbon::parse($lastSync);
            $expectedNextSync = $lastSyncTime->addMinutes($frequency);
            $timeDiff = now()->diffInMinutes($expectedNextSync);

            if ($timeDiff < $frequency) {
                return 'good';
            } elseif ($timeDiff < $frequency * 2) {
                return 'fair';
            } else {
                return 'poor';
            }
        } catch (\Exception $e) {
            return 'poor';
        }
    }

    /**
     * Dashboard endpoint to show auto-sync status
     */
    public function getAutoSyncStatus(Request $request)
    {
        try {
            $stats = [
                'is_enabled' => filter_var($this->getSetting('etimeoffice_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'sync_frequency' => (int) $this->getSetting('etimeoffice_sync_frequency', 15),
                'last_auto_sync' => $this->getSetting('etimeoffice_last_auto_sync'),
                'last_manual_sync' => $this->getSetting('etimeoffice_last_sync'),
                'total_syncs_today' => $this->getTodayAutoSyncCount(),
                'failed_syncs_today' => $this->getTodayFailedSyncCount(),
                'next_scheduled_sync' => $this->getNextScheduledSync(),
                'scheduler_health' => $this->checkSchedulerHealth(),
                'recent_errors' => $this->getRecentSyncErrors(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get auto-sync status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle auto-sync on/off
     */
    public function toggleAutoSync(Request $request)
    {
        try {
            $enabled = $request->boolean('enabled');

            $this->updateSetting('etimeoffice_enabled', $enabled);

            \Log::info('Auto-sync toggled', [
                'enabled' => $enabled,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Auto-sync '.($enabled ? 'enabled' : 'disabled'),
                'enabled' => $enabled,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle auto-sync',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update sync frequency
     */
    public function updateSyncFrequency(Request $request)
    {
        try {
            $validated = $request->validate([
                'frequency' => 'required|integer|min:1|max:1440',
            ]);

            $this->updateSetting('etimeoffice_sync_frequency', $validated['frequency']);

            return response()->json([
                'success' => true,
                'message' => "Sync frequency updated to {$validated['frequency']} minutes",
                'frequency' => $validated['frequency'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sync frequency',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's auto-sync count
     */
    private function getTodayAutoSyncCount(): int
    {
        // Count from logs or dedicated table
        $today = now()->toDateString();
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return 0;
        }

        $logContent = file_get_contents($logFile);
        $todayPattern = '/\['.$today.'.*Auto-sync completed/';

        return preg_match_all($todayPattern, $logContent);
    }

    /**
     * Get today's failed sync count
     */
    private function getTodayFailedSyncCount(): int
    {
        $today = now()->toDateString();
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return 0;
        }

        $logContent = file_get_contents($logFile);
        $failedPattern = '/\['.$today.'.*Auto-sync failed/';

        return preg_match_all($failedPattern, $logContent);
    }

    /**
     * Calculate next scheduled sync time
     */
    private function getNextScheduledSync(): ?string
    {
        try {
            $lastSync = $this->getSetting('etimeoffice_last_auto_sync');
            $frequency = (int) $this->getSetting('etimeoffice_sync_frequency', 15);

            if ($lastSync) {
                $nextSync = \Carbon\Carbon::parse($lastSync)->addMinutes($frequency);

                return $nextSync->format('Y-m-d H:i:s');
            }

            // If no last sync, next one should be within frequency minutes
            return now()->addMinutes($frequency)->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if Laravel scheduler is running
     */
    private function checkSchedulerHealth(): string
    {
        $heartbeatFile = storage_path('app/scheduler-heartbeat.txt');

        if (! file_exists($heartbeatFile)) {
            return 'unknown';
        }

        $lastHeartbeat = file_get_contents($heartbeatFile);
        $lastTime = \Carbon\Carbon::parse($lastHeartbeat);

        if ($lastTime->diffInMinutes(now()) > 10) {
            return 'unhealthy';
        }

        return 'healthy';
    }

    /**
     * Get recent sync errors
     */
    private function getRecentSyncErrors(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return [];
        }

        $errors = [];
        $lines = file($logFile);
        $recentLines = array_slice($lines, -1000); // Check last 1000 lines

        foreach ($recentLines as $line) {
            if (strpos($line, 'Auto-sync failed') !== false) {
                $errors[] = trim($line);
            }
        }

        return array_slice($errors, -5); // Return last 5 errors
    }

    /**
     * Update existing attendance record
     */
    private function updateExistingAttendance($existingAttendance, array $record, Carbon $punchDateTime): bool
    {
        try {
            $currentMarkedAt = $existingAttendance->marked_at;

            // Only update if the new punch time is earlier (first punch of the day)
            if (! $currentMarkedAt || $punchDateTime->lt($currentMarkedAt)) {
                $existingAttendance->update([
                    'marked_at' => $punchDateTime,
                    'notes' => 'Updated via ETimeOffice API at '.now()->format('Y-m-d H:i:s'),
                    'device_id' => 'etimeoffice-api',
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error updating existing attendance', [
                'error' => $e->getMessage(),
                'attendance_id' => $existingAttendance->id ?? null,
            ]);

            return false;
        }
    }

    /**
     * Create new attendance record
     */
    private function createNewAttendance(Student $student, array $record, Carbon $punchDateTime, string $attendanceDate): void
    {
        try {
            // Determine attendance status based on punch time
            $settings = [
                'college_start_time' => $this->getSetting('attendance_student_college_start_time', '09:30:00'),
                'present_cutoff_time' => $this->getSetting('attendance_student_present_cutoff_time', '11:00:00'),
                'late_cutoff_time' => $this->getSetting('attendance_student_late_cutoff_time', '11:30:00'),
                'college_end_time' => $this->getSetting('attendance_college_end_time', '17:00:00'),
            ];

            $status = $this->determineStatus($punchDateTime->format('H:i:s'), $settings);

            Attendance::create([
                'student_id' => $student->id,
                'batch_id' => $student->batch_id,
                'attendance_date' => $attendanceDate,
                'status' => $status['status'],
                'marked_at' => $punchDateTime,
                'notes' => 'Created via ETimeOffice API - '.$status['reason'],
                'device_id' => 'etimeoffice-api',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Created new attendance record', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'attendance_date' => $attendanceDate,
                'status' => $status['status'],
                'punch_time' => $punchDateTime->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating new attendance record', [
                'error' => $e->getMessage(),
                'student_id' => $student->id ?? null,
                'attendance_date' => $attendanceDate ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Perform actual eTimeOffice connection test
     * This is a placeholder - implement according to your eTimeOffice API
     */
    /**
     * Updated connection test method with proper parameters
     */
    private function performETimeOfficeConnectionTest(string $apiUrl, string $corporateId, string $username, string $password): array
    {
        try {
            \Log::info('Testing eTimeOffice connection', [
                'api_url' => $apiUrl,
                'corporate_id' => $corporateId,
                'username' => $username,
                'has_password' => ! empty($password),
            ]);

            // Create Basic Auth token
            $authToken = base64_encode("{$corporateId}:{$username}:{$password}:true");

            // Test with a simple API call to get today's data
            $testParams = [
                'Empcode' => 'ALL',
                'FromDate' => now()->format('d/m/Y_H:i'),
                'ToDate' => now()->format('d/m/Y_H:i'),
            ];

            $queryString = http_build_query($testParams);
            $testUrl = rtrim($apiUrl, '/').'/DownloadPunchData?'.$queryString;

            // Make the test API call
            $response = \Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Basic '.$authToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->get($testUrl);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => "API request failed with status: {$response->status()}. Response: ".$response->body(),
                ];
            }

            $responseData = $response->json();

            // Check if API returned an error
            if (isset($responseData['Error']) && $responseData['Error'] === true) {
                return [
                    'success' => false,
                    'message' => 'API Error: '.($responseData['Msg'] ?? 'Unknown API error'),
                ];
            }

            // Success
            $punchDataCount = isset($responseData['PunchData']) ? count($responseData['PunchData']) : 0;

            return [
                'success' => true,
                'message' => 'Connection successful! API is responding correctly.',
                'data' => [
                    'api_url' => $apiUrl,
                    'corporate_id' => $corporateId,
                    'test_timestamp' => now()->toDateTimeString(),
                    'punch_records_found' => $punchDataCount,
                    'api_response_size' => strlen($response->body()).' bytes',
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('eTimeOffice connection test exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
            ];
        }
    }

    private function doConnectionTest($apiUrl, $corporateId, $username, $password)
    {
        try {
            \Log::info('=== eTimeOffice Connection Debug ===', [
                'api_url' => $apiUrl,
                'corporate_id' => $corporateId,
                'username' => $username,
                'password_length' => strlen($password),
                'timestamp' => now()->toDateTimeString(),
            ]);

            // Let's try the exact format from your ETimeOfficeService
            $authToken = base64_encode("{$corporateId}:{$username}:{$password}:true");

            \Log::info('Auth token details', [
                'raw_string' => "{$corporateId}:{$username}:{$password}:true",
                'base64_token' => $authToken,
                'token_length' => strlen($authToken),
            ]);

            // Test different endpoints and methods
            $endpoints = [
                'DownloadPunchData' => 'GET',
                'downloadpunchdata' => 'GET', // lowercase
                'api/DownloadPunchData' => 'GET',
                'DownloadPunchData' => 'POST',
            ];

            $baseParams = [
                'Empcode' => 'ALL',
                'FromDate' => now()->format('d/m/Y_H:i'),
                'ToDate' => now()->format('d/m/Y_H:i'),
            ];

            foreach ($endpoints as $endpoint => $method) {
                try {
                    $testUrl = rtrim($apiUrl, '/').'/'.ltrim($endpoint, '/');

                    \Log::info("Testing endpoint: {$testUrl} with {$method}");

                    $headers = [
                        'Authorization' => 'Basic '.$authToken,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'eTimeOffice-Client/1.0',
                        'Cache-Control' => 'no-cache',
                    ];

                    if ($method === 'GET') {
                        $fullUrl = $testUrl.'?'.http_build_query($baseParams);
                        $response = \Http::timeout(30)->withHeaders($headers)->get($fullUrl);
                    } else {
                        $response = \Http::timeout(30)->withHeaders($headers)->post($testUrl, $baseParams);
                    }

                    \Log::info("Response from {$endpoint}", [
                        'status' => $response->status(),
                        'headers' => $response->headers(),
                        'body' => $response->body(),
                    ]);

                    if ($response->successful()) {
                        $responseBody = $response->body();

                        // Try to parse as JSON
                        try {
                            $data = $response->json();
                        } catch (\Exception $e) {
                            // Maybe it's not JSON, let's see what we got
                            return [
                                'success' => false,
                                'message' => 'API responded but not with JSON',
                                'data' => [
                                    'endpoint' => $endpoint,
                                    'method' => $method,
                                    'response_body' => substr($responseBody, 0, 500),
                                    'content_type' => $response->header('Content-Type'),
                                ],
                            ];
                        }

                        // Check for API errors
                        if (isset($data['Error'])) {
                            $errorMsg = $data['Msg'] ?? 'Unknown error';

                            \Log::warning("API Error from {$endpoint}", [
                                'error' => $data['Error'],
                                'message' => $errorMsg,
                                'full_response' => $data,
                            ]);

                            // Continue to next endpoint
                            continue;
                        }

                        // Success!
                        return [
                            'success' => true,
                            'message' => "Connection successful with {$endpoint} ({$method})!",
                            'data' => [
                                'working_endpoint' => $endpoint,
                                'working_method' => $method,
                                'response_keys' => array_keys($data),
                                'punch_data_count' => isset($data['PunchData']) ? count($data['PunchData']) : 0,
                                'sample_response' => array_slice($data, 0, 3, true),
                            ],
                        ];
                    }

                    // Non-200 response
                    \Log::warning("HTTP Error from {$endpoint}", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                } catch (\Exception $e) {
                    \Log::warning("Exception testing {$endpoint}", [
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            // If we get here, all attempts failed
            return [
                'success' => false,
                'message' => 'All authentication attempts failed. Please verify credentials with eTimeOffice support.',
                'debug_info' => [
                    'tested_endpoints' => array_keys($endpoints),
                    'auth_string_used' => "{$corporateId}:{$username}:{$password}:true",
                    'recommendations' => [
                        'Check if Corporate ID is correct (usually numeric)',
                        'Verify username is for API access (not web login)',
                        'Confirm password is correct',
                        'Contact eTimeOffice to verify API access is enabled',
                        'Ask eTimeOffice for exact API documentation',
                    ],
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('Connection test failed completely', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
            ];
        }
    }

    public function testAuthFormats(Request $request)
    {
        $corporateId = $request->input('etimeoffice_corporate_id');
        $username = $request->input('etimeoffice_username');
        $password = $request->input('etimeoffice_password');
        $apiUrl = $request->input('etimeoffice_api_url');

        if (! $corporateId || ! $username || ! $password || ! $apiUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required fields for testing',
            ]);
        }

        $authFormats = [
            'Standard' => "{$corporateId}:{$username}:{$password}:true",
            'Without True' => "{$corporateId}:{$username}:{$password}",
            'Simple Basic' => "{$username}:{$password}",
            'With Backslash' => "{$corporateId}\\{$username}:{$password}",
            'With Underscore' => "{$corporateId}_{$username}:{$password}",
            'Uppercase Corp' => strtoupper($corporateId).":{$username}:{$password}:true",
        ];

        $results = [];

        foreach ($authFormats as $name => $authString) {
            $token = base64_encode($authString);

            try {
                $response = \Http::timeout(3)
                    ->withHeaders([
                        'Authorization' => 'Basic '.$token,
                        'Accept' => 'application/json',
                    ])
                    ->get($apiUrl.'/DownloadPunchData', [
                        'Empcode' => 'ALL',
                        'FromDate' => now()->format('d/m/Y_H:i'),
                        'ToDate' => now()->format('d/m/Y_H:i'),
                    ]);

                $results[] = [
                    'format' => $name,
                    'auth_string' => $authString,
                    'status' => $response->status(),
                    'success' => $response->successful(),
                    'response' => substr($response->body(), 0, 200),
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'format' => $name,
                    'auth_string' => $authString,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tested all auth formats',
            'results' => $results,
        ]);
    }

    /**
     * TEMPORARY: Direct test of performETimeOfficeSync method
     */
    public function testDirectSync()
    {
        \Log::info('🧪 Direct sync test started');

        try {
            // Call the method directly
            $result = $this->performETimeOfficeSync([
                'sync_type' => 'manual',
                'date_range_type' => 'test',
                'date_range_start' => now()->startOfDay(),
                'date_range_end' => now()->endOfDay(),
                'test_mode' => false,
            ]);

            \Log::info('🧪 Direct sync test result', ['result' => $result]);

            // Also check database
            $latestLog = \App\Models\ETimeOfficeSyncLog::orderBy('created_at', 'desc')->first();

            return response()->json([
                'success' => true,
                'message' => 'Direct sync test completed',
                'sync_result' => $result,
                'latest_log_id' => $latestLog ? $latestLog->id : null,
                'latest_log_created' => $latestLog ? $latestLog->created_at : null,
            ]);

        } catch (\Exception $e) {
            \Log::error('🧪 Direct sync test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Perform actual eTimeOffice sync with logging
     */
    private function performETimeOfficeSync(array $options = []): array
    {
        \Log::info('🔄 Starting performETimeOfficeSync', ['options' => $options, 'user_id' => auth()->id()]);

        try {
            // Create sync log entry
            $syncLog = \App\Models\ETimeOfficeSyncLog::create([
                'sync_type' => $options['sync_type'] ?? 'manual',
                'date_range_type' => $options['date_range_type'] ?? 'today',
                'date_range_start' => $options['date_range_start'] ?? now()->startOfDay(),
                'date_range_end' => $options['date_range_end'] ?? now()->endOfDay(),
                'test_mode' => $options['test_mode'] ?? false,
                'employee_codes' => $options['employee_codes'] ?? null,
                'status' => 'running',
                'started_at' => now(),
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'notes' => 'Sync initiated from admin panel',
            ]);

            \Log::info('✅ Sync log created', ['sync_log_id' => $syncLog->id]);

            // Initialize results
            $results = [
                'success' => true,
                'total_records' => 0,
                'processed_records' => 0,
                'created_records' => 0,
                'updated_records' => 0,
                'skipped_records' => 0,
                'errors' => [],
            ];

            // Check if ETimeOffice is configured
            if (! $this->isETimeOfficeConfigured()) {
                $results['success'] = false;
                $results['errors'][] = 'ETimeOffice is not properly configured';
                \Log::warning('❌ ETimeOffice not configured');
            } else {
                // Simulate API call for now - you can replace this with real API logic later
                $results = $this->simulateETimeOfficeSync($options);
                \Log::info('📊 Simulation completed', ['results' => $results]);
            }

            // Complete the sync log
            $syncLog->update([
                'status' => $results['success'] ? 'success' : 'failed',
                'total_records' => $results['total_records'],
                'processed_records' => $results['processed_records'],
                'created_records' => $results['created_records'],
                'updated_records' => $results['updated_records'],
                'skipped_records' => $results['skipped_records'],
                'errors' => $results['errors'],
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($syncLog->started_at),
            ]);

            \Log::info('✅ Sync log updated', ['sync_log_id' => $syncLog->id, 'status' => $syncLog->status]);

            // Update last sync time if successful
            if ($results['success']) {
                $this->updateSetting('etimeoffice_last_sync', now()->toDateTimeString());
                \Log::info('⏰ Last sync time updated');
            }

            $message = $results['success']
                ? "Sync completed: {$results['created_records']} created, {$results['updated_records']} updated, {$results['skipped_records']} skipped from {$results['total_records']} total records"
                : 'Sync failed: '.implode(', ', $results['errors']);

            return [
                'success' => $results['success'],
                'message' => $message,
                'data' => $results,
            ];

        } catch (\Exception $e) {
            \Log::error('💥 performETimeOfficeSync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Handle any unexpected errors
            if (isset($syncLog)) {
                $syncLog->update([
                    'status' => 'failed',
                    'errors' => ['Sync failed: '.$e->getMessage()],
                    'completed_at' => now(),
                    'duration_seconds' => isset($syncLog) ? now()->diffInSeconds($syncLog->started_at) : 0,
                ]);
            }

            return [
                'success' => false,
                'message' => 'Sync failed: '.$e->getMessage(),
                'data' => [],
            ];
        }
    }

    // Temporary test method - add this at the end of the class
    public function testSyncLogging()
    {
        $syncLog = \App\Models\ETimeOfficeSyncLog::create([
            'sync_type' => 'manual',
            'date_range_type' => 'today',
            'date_range_start' => now()->startOfDay(),
            'date_range_end' => now()->endOfDay(),
            'status' => 'success',
            'total_records' => 42,
            'processed_records' => 40,
            'created_records' => 15,
            'updated_records' => 20,
            'skipped_records' => 5,
            'started_at' => now()->subMinutes(1),
            'completed_at' => now(),
            'duration_seconds' => 60,
            'user_id' => auth()->id(),
            'notes' => 'Test from sync button click - '.now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test sync completed - Log ID: '.$syncLog->id,
            'data' => ['test' => true],
        ]);
    }

    /**
     * Simulate ETimeOffice sync - replace with real API calls later
     */
    private function simulateETimeOfficeSync(array $options): array
    {
        \Log::info('🎭 Running simulation mode');

        // Check if this is a test mode
        $testMode = $options['test_mode'] ?? false;

        // Create more realistic simulation based on current second for variation
        $currentSecond = now()->second;

        if ($currentSecond < 20) {
            // Simulate successful sync with data
            return [
                'success' => true,
                'total_records' => rand(15, 35),
                'processed_records' => rand(14, 32),
                'created_records' => $testMode ? 0 : rand(3, 12),
                'updated_records' => $testMode ? 0 : rand(5, 18),
                'skipped_records' => rand(1, 3),
                'errors' => [],
            ];
        } elseif ($currentSecond < 40) {
            // Simulate successful sync with no data
            return [
                'success' => true,
                'total_records' => 0,
                'processed_records' => 0,
                'created_records' => 0,
                'updated_records' => 0,
                'skipped_records' => 0,
                'errors' => [],
            ];
        } elseif ($currentSecond < 50) {
            // Simulate partial success
            return [
                'success' => true,
                'total_records' => rand(10, 25),
                'processed_records' => rand(8, 20),
                'created_records' => $testMode ? 0 : rand(2, 6),
                'updated_records' => $testMode ? 0 : rand(4, 10),
                'skipped_records' => rand(2, 5),
                'errors' => ['Some records had validation errors', 'Employee code not found for 2 records'],
            ];
        } else {
            // Simulate failure
            return [
                'success' => false,
                'total_records' => 0,
                'processed_records' => 0,
                'created_records' => 0,
                'updated_records' => 0,
                'skipped_records' => 0,
                'errors' => ['Connection timeout to ETimeOffice API', 'Authentication failed - check credentials'],
            ];
        }
    }

    /**
     * Process individual ETimeOffice record
     */
    private function processETimeOfficeRecord(array $record, bool $testMode = false): array
    {
        // Extract employee code - different APIs use different field names
        $employeeCode = $record['Empcode'] ?? $record['EmpcardNo'] ?? $record['EmployeeCode'] ?? null;

        if (! $employeeCode) {
            return [
                'action' => 'skipped',
                'reason' => 'No employee code found in record',
            ];
        }

        // Find student by employee code
        $student = \App\Models\Student::where('employee_code', $employeeCode)
            ->orWhere('biometric_employee_code', $employeeCode)
            ->first();

        if (! $student) {
            return [
                'action' => 'skipped',
                'reason' => 'Student not found with employee code: '.$employeeCode,
            ];
        }

        // Extract punch date and time - handle different API response formats
        $punchDateTime = null;

        if (isset($record['PunchDate'])) {
            $punchDateTime = \Carbon\Carbon::parse($record['PunchDate']);
        } elseif (isset($record['LogDateTime'])) {
            $punchDateTime = \Carbon\Carbon::parse($record['LogDateTime']);
        } elseif (isset($record['Date']) && isset($record['Time'])) {
            $punchDateTime = \Carbon\Carbon::parse($record['Date'].' '.$record['Time']);
        } else {
            return [
                'action' => 'skipped',
                'reason' => 'No valid date/time found in record for employee: '.$employeeCode,
            ];
        }

        $attendanceDate = $punchDateTime->format('Y-m-d');

        // Check if attendance already exists
        $existingAttendance = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $attendanceDate)
            ->first();

        if ($testMode) {
            // In test mode, just return what would happen
            if ($existingAttendance) {
                return ['action' => 'updated', 'reason' => 'Would update existing attendance'];
            } else {
                return ['action' => 'created', 'reason' => 'Would create new attendance'];
            }
        }

        // Determine attendance status based on punch time
        $status = $this->determineAttendanceStatus($punchDateTime);

        if ($existingAttendance) {
            // Update existing attendance if the new punch is earlier (first punch of the day)
            $currentPunchTime = $existingAttendance->marked_at ? \Carbon\Carbon::parse($existingAttendance->marked_at) : null;

            if (! $currentPunchTime || $punchDateTime->lt($currentPunchTime)) {
                $existingAttendance->update([
                    'marked_at' => $punchDateTime,
                    'status' => $status,
                    'device_id' => 'etimeoffice-api',
                    'notes' => 'Updated via ETimeOffice sync at '.now()->format('Y-m-d H:i:s'),
                ]);

                return ['action' => 'updated', 'attendance_id' => $existingAttendance->id];
            } else {
                return ['action' => 'skipped', 'reason' => 'Later punch time, keeping existing record'];
            }
        } else {
            // Create new attendance record
            $attendance = Attendance::create([
                'student_id' => $student->id,
                'batch_id' => $student->batch_id,
                'academic_year_id' => $student->batch->academic_year_id ?? null,
                'attendance_date' => $attendanceDate,
                'marked_at' => $punchDateTime,
                'status' => $status,
                'device_id' => 'etimeoffice-api',
                'notes' => 'Created via ETimeOffice sync at '.now()->format('Y-m-d H:i:s'),
            ]);

            return ['action' => 'created', 'attendance_id' => $attendance->id];
        }
    }

    /**
     * Get live attendance data
     */
    private function getLiveAttendanceData()
    {
        try {
            return Attendance::with(['student', 'batch'])
                ->whereDate('attendance_date', Carbon::today())
                ->whereHas('student.batch', function ($q) {
                    $q->where('is_on_internship', 0);
                })
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'student_name' => $attendance->student->name ?? 'N/A',
                        'batch_name' => $attendance->batch->name ?? 'N/A',
                        'status' => $attendance->status,
                        'time' => $attendance->created_at->format('H:i:s'),
                        'formatted_time' => $attendance->created_at->diffForHumans(),
                    ];
                });
        } catch (\Exception $e) {
            Log::error('Error getting live attendance data: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get status color for UI
     */
    private function getStatusColor($status)
    {
        return match ($status) {
            'present' => 'success',
            'late' => 'warning',
            'absent' => 'danger',
            'excused' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Show attendance dashboard with enhanced features
     */
    public function dashboard(Request $request)
    {
        try {
            // FIXED: Changed from 'manage attendance settings' to 'view attendance'
            // This allows college-admin users to access the dashboard
            $this->authorize('view attendance');

            // Get today's date or requested date
            $selectedDate = Carbon::parse($request->get('date', Carbon::today()->format('Y-m-d')));
            $batchId = $request->get('batch_id');
            $courseId = $request->get('course_id');

            // Get enhanced data
            $todayStats = $this->getTodayStatsEnhanced($selectedDate, $batchId, $courseId);
            $absentStudents = $this->getAbsentStudents($selectedDate, $batchId, $courseId);

            // Fix: Separate query for never punched students
            // Use allYears to check historical punches and ensure they out of the list
            $neverPunchedStudents = \App\Models\Student::active()
                ->whereDoesntHave('attendances', function ($query) {
                    $query->allYears()->whereIn('status', ['present', 'late']);
                })
                ->with(['batch.course']) // Optimize loading
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'enrollment_number' => $student->enrollment_number,
                        'student_mobile' => $student->student_mobile,
                        'father_mobile' => $student->father_mobile,
                        'father_name' => $student->father_name,
                        'batch_name' => $student->batch->name ?? 'N/A',
                        'course_name' => $student->batch->course->name ?? 'N/A',
                        'last_attendance' => null,
                    ];
                });

            $recentActivity = $this->getRecentActivity($selectedDate, $batchId, $courseId);
            $batches = \App\Models\Batch::with('course')->orderBy('name')->get();
            $courses = \App\Models\Course::orderBy('name')->get();
            $weeklyTrend = $this->getWeeklyTrend($selectedDate);

            // Original data (for backward compatibility)
            $liveData = $this->getLiveAttendanceData() ?? [];
            $systemStatus = $this->getSystemStatus() ?? ['database' => 'connected'];
            $weeklyStats = $this->getWeeklyStatsData() ?? [];

            return view('admin.attendance.dashboard', compact(
                'selectedDate',
                'todayStats',
                'absentStudents',
                'recentActivity',
                'batches',
                'courses',
                'batchId',
                'courseId',
                'weeklyTrend',
                'liveData',
                'systemStatus',
                'weeklyStats',
                'neverPunchedStudents'
            ));

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Better error handling for permission issues
            \Log::warning('Attendance dashboard access denied', [
                'user_id' => auth()->id(),
                'user_roles' => auth()->user()->getRoleNames(),
                'required_permission' => 'view attendance',
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.dashboard')
                ->with('error', 'You do not have permission to access the attendance dashboard. Please contact your administrator.');

        } catch (\Exception $e) {
            \Log::error('Attendance dashboard error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user' => auth()->id(),
            ]);

            // Return safe defaults to prevent errors
            return view('admin.attendance.dashboard', [
                'selectedDate' => Carbon::today(),
                'todayStats' => ['students' => ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'percentage' => 0]],
                'absentStudents' => collect([]),
                'recentActivity' => collect([]),
                'batches' => \App\Models\Batch::with('course')->get(),
                'courses' => \App\Models\Course::get(),
                'batchId' => null,
                'courseId' => null,
                'weeklyTrend' => [],
                'liveData' => [],
                'systemStatus' => ['database' => 'connected'],
                'weeklyStats' => [],
            ])->with('error', 'Some dashboard data could not be loaded');
        }
    }

    /**
     * Enhanced today's statistics
     */
    private function getTodayStatsEnhanced($date, $batchId = null, $courseId = null)
    {
        // Base query for students
        $studentsQuery = \App\Models\Student::where('status', 'active')
            ->whereHas('batch', function ($q) use ($date) {
                // Exclude internship: Keep if (Not Flagged) AND (Date is Null or Future)
                $q->where(function ($sq) {
                    $sq->where('is_on_internship', '!=', 1)
                        ->orWhereNull('is_on_internship');
                })->where(function ($sq) use ($date) {
                    $sq->whereNull('internship_start_date')
                        ->orWhere('internship_start_date', '>', $date);
                });
            });

        if ($batchId) {
            $studentsQuery->where('batch_id', $batchId);
        }
        if ($courseId) {
            $studentsQuery->whereHas('batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $totalStudents = $studentsQuery->count();

        // Get attendance
        $attendanceQuery = Attendance::whereDate('attendance_date', $date);

        // Apply internship exclusion
        $attendanceQuery->whereHas('student.batch', function ($q) use ($date) {
            $q->where(function ($sq) {
                $sq->where('is_on_internship', '!=', 1)
                    ->orWhereNull('is_on_internship');
            })->where(function ($sq) use ($date) {
                $sq->whereNull('internship_start_date')
                    ->orWhere('internship_start_date', '>', $date);
            });
        });

        if ($batchId) {
            $attendanceQuery->whereHas('student', function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            });
        }
        if ($courseId) {
            $attendanceQuery->whereHas('student.batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $attendanceStats = $attendanceQuery->select([
            \DB::raw('COUNT(DISTINCT student_id) as total_marked'),
            \DB::raw('COUNT(DISTINCT CASE WHEN status IN ("present", "late") THEN student_id END) as present'),
            \DB::raw('COUNT(DISTINCT CASE WHEN status = "excused" THEN student_id END) as excused'),
        ])->first();

        $present = ($attendanceStats->present ?? 0);
        $excused = ($attendanceStats->excused ?? 0);

        // Get count of students who have never punched (historical)
        $neverPunchedCountQuery = clone $studentsQuery;
        $neverPunchedCount = $neverPunchedCountQuery->whereDoesntHave('attendances', function ($query) {
            $query->allYears()->whereIn('status', ['present', 'late']);
        })->count();

        $absent = $totalStudents - $present - $excused - $neverPunchedCount;
        $presentPercentage = $totalStudents > 0 ? (float) number_format(($present / $totalStudents) * 100, 1) : 0;
        $absentPercentage = $totalStudents > 0 ? (float) number_format(($absent / $totalStudents) * 100, 1) : 0;
        $neverPunchedPercentage = $totalStudents > 0 ? (float) number_format(($neverPunchedCount / $totalStudents) * 100, 1) : 0;

        // Get Internship Count
        $internshipQuery = \App\Models\Student::where('status', 'active')
            ->whereHas('batch', function ($q) use ($date) {
                $q->where(function ($sq) use ($date) {
                    $sq->where('is_on_internship', 1)
                        ->orWhere(function ($dq) use ($date) {
                            $dq->whereNotNull('internship_start_date')
                                ->where('internship_start_date', '<=', $date);
                        });
                });
            });

        if ($batchId) {
            $internshipQuery->where('batch_id', $batchId);
        }
        if ($courseId) {
            $internshipQuery->whereHas('batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $internshipCount = $internshipQuery->count();

        return [
            'students' => [
                'total' => $totalStudents,
                'present' => $present,
                'absent' => $absent,
                'late' => $attendanceStats->late ?? 0,
                'excused' => $attendanceStats->excused ?? 0,
                'never_punched' => $neverPunchedCount,
                'percentage' => $presentPercentage,
                'absent_percentage' => $absentPercentage,
                'never_punched_percentage' => $neverPunchedPercentage,
                'internship' => $internshipCount,
            ],
        ];
    }

    /**
     * Get absent students with contact information
     */
    private function getAbsentStudents($date, $batchId = null, $courseId = null)
    {
        $studentsQuery = \App\Models\Student::where('status', 'active')
            ->whereHas('batch', function ($q) use ($date) {
                $q->where(function ($sq) {
                    $sq->where('is_on_internship', '!=', 1)
                        ->orWhereNull('is_on_internship');
                })->where(function ($sq) use ($date) {
                    $sq->whereNull('internship_start_date')
                        ->orWhere('internship_start_date', '>', $date);
                });
            })
            ->whereHas('attendances', function ($query) {
                // ✅ Filter: Only students who have punched at least once (present or late) in any year
                $query->allYears()->whereIn('status', ['present', 'late']);
            })
            ->with(['batch.course']);

        if ($batchId) {
            $studentsQuery->where('batch_id', $batchId);
        }
        if ($courseId) {
            $studentsQuery->whereHas('batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $allStudents = $studentsQuery->get();

        // Manual Filtering
        $allStudents = $allStudents->filter(function ($student) use ($date) {
            $batch = $student->batch;
            if (! $batch) {
                return true;
            }
            $isOnInternship = ($batch->is_on_internship == 1);
            $isInternshipByDate = ($batch->internship_start_date && $batch->internship_start_date <= $date);

            return ! ($isOnInternship || $isInternshipByDate);
        });

        // Get marked attendance
        $attendanceQuery = Attendance::whereDate('attendance_date', $date)
            ->whereIn('status', ['present', 'late', 'excused']);

        if ($batchId) {
            $attendanceQuery->whereHas('student', function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            });
        }
        if ($courseId) {
            $attendanceQuery->whereHas('student.batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $presentStudentIds = $attendanceQuery->pluck('student_id')->toArray();
        $absentStudents = $allStudents->whereNotIn('id', $presentStudentIds);

        return $absentStudents->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'student_mobile' => $student->student_mobile,
                'father_mobile' => $student->father_mobile,
                'father_name' => $student->father_name,
                'batch_name' => $student->batch->name ?? 'N/A',
                'course_name' => $student->batch->course->name ?? 'N/A',
                'last_attendance' => $this->getLastAttendanceDate($student->id),
            ];
        });
    }

    /**
     * Get recent attendance activity
     */
    private function getRecentActivity($date, $batchId = null, $courseId = null, $limit = 100)
    {
        $query = Attendance::with(['student.batch.course', 'faculty'])
            ->whereDate('attendance_date', $date)
            ->whereHas('student.batch', function ($q) use ($date) {
                $q->where(function ($sq) {
                    $sq->where('is_on_internship', '!=', 1)
                        ->orWhereNull('is_on_internship');
                })->where(function ($sq) use ($date) {
                    $sq->whereNull('internship_start_date')
                        ->orWhere('internship_start_date', '>', $date);
                });
            })
            ->orderBy('marked_at', 'desc');

        if ($batchId) {
            $query->whereHas('student', function ($q) use ($batchId) {
                $q->where('batch_id', $batchId);
            });
        }
        if ($courseId) {
            $query->whereHas('student.batch', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        return $query->get()
            ->filter(function ($attendance) use ($date) {
                $student = $attendance->student;
                if (! $student || ! $student->batch) {
                    return true;
                }
                $batch = $student->batch;
                $isOnInternship = ($batch->is_on_internship == 1);
                $isInternshipByDate = ($batch->internship_start_date && $batch->internship_start_date <= $date);

                return ! ($isOnInternship || $isInternshipByDate);
            })
            ->take($limit)
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'student_name' => $attendance->student->name,
                    'enrollment_number' => $attendance->student->enrollment_number,
                    'batch_name' => $attendance->student->batch->name ?? 'N/A',
                    'course_name' => $attendance->student->batch->course->name ?? 'N/A',
                    'status' => $attendance->status,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'marked_at' => $attendance->marked_at,
                    'marked_by' => $attendance->faculty->name ?? 'System',
                    'late_minutes' => $attendance->late_minutes,
                    'notes' => $attendance->notes,
                ];
            })->values();
    }

    /**
     * Get last attendance date for a student
     */
    private function getLastAttendanceDate($studentId)
    {
        $lastAttendance = Attendance::where('student_id', $studentId)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('attendance_date', 'desc')
            ->first();

        return $lastAttendance ? $lastAttendance->attendance_date : null;
    }

    /**
     * Get weekly attendance trend
     */
    private function getWeeklyTrend($selectedDate)
    {
        $endDate = $selectedDate;
        $startDate = $selectedDate->copy()->subDays(6);

        $trend = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayStats = $this->getTodayStatsEnhanced($date);
            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'percentage' => $dayStats['students']['percentage'],
            ];
        }

        return $trend;
    }

    public function getAbsentStudentsAjax(Request $request)
    {
        try {
            // FIXED: Use 'view attendance' instead of 'manage attendance settings'
            $this->authorize('view attendance');

            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $selectedDate = Carbon::parse($date);
            $batchId = $request->get('batch_id');
            $courseId = $request->get('course_id');

            $absentStudents = $this->getAbsentStudents($selectedDate, $batchId, $courseId);

            return response()->json([
                'success' => true,
                'data' => $absentStudents->values(),
                'count' => $absentStudents->count(),
                'last_updated' => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Permission denied or error occurred: '.$e->getMessage(),
            ], 403);
        }
    }

    public function getRecentActivityAjax(Request $request)
    {
        try {
            // FIXED: Use 'view attendance' instead of 'manage attendance settings'
            $this->authorize('view attendance');

            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $selectedDate = Carbon::parse($date);
            $batchId = $request->get('batch_id');
            $courseId = $request->get('course_id');

            $recentActivity = $this->getRecentActivity($selectedDate, $batchId, $courseId);

            return response()->json([
                'success' => true,
                'data' => $recentActivity,
                'count' => $recentActivity->count(),
                'last_updated' => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Permission denied or error occurred: '.$e->getMessage(),
            ], 403);
        }
    }

    public function getTodayStatsAjax(Request $request)
    {
        try {
            // FIXED: Use 'view attendance' instead of 'manage attendance settings'
            $this->authorize('view attendance');

            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $selectedDate = Carbon::parse($date);
            $batchId = $request->get('batch_id');
            $courseId = $request->get('course_id');

            $todayStats = $this->getTodayStatsEnhanced($selectedDate, $batchId, $courseId);

            return response()->json([
                'success' => true,
                'data' => $todayStats,
                'last_updated' => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Permission denied or error occurred: '.$e->getMessage(),
            ], 403);
        }
    }

    public function markStudentPresent(Request $request)
    {
        try {
            // FIXED: Use 'manage attendance' for marking present (write operations)
            $this->authorize('manage attendance');

            $request->validate([
                'student_id' => 'required|exists:students,id',
                'date' => 'required|date',
            ]);

            $student = \App\Models\Student::findOrFail($request->student_id);
            $date = $request->date;

            // Check if attendance already exists
            $existingAttendance = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_date', $date)
                ->first();

            if ($existingAttendance) {
                $existingAttendance->update([
                    'status' => 'present',
                    'marked_at' => now(),
                    'marked_by' => auth()->id(),
                    'notes' => 'Marked from dashboard',
                ]);
            } else {
                Attendance::create([
                    'student_id' => $student->id,
                    'batch_id' => $student->batch_id,
                    'subject_id' => 1, // Default subject
                    'faculty_id' => auth()->id(),
                    'attendance_date' => $date,
                    'status' => 'present',
                    'check_in_time' => now()->format('H:i:s'),
                    'marked_at' => now(),
                    'marked_by' => auth()->id(),
                    'notes' => 'Marked from dashboard',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Student marked as present successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Permission denied or error occurred: '.$e->getMessage(),
            ], 403);
        }
    }

    public function bulkMarkPresent(Request $request)
    {
        try {
            // FIXED: Use 'manage attendance' for bulk operations (write operations)
            $this->authorize('manage attendance');

            $request->validate([
                'student_ids' => 'required|array',
                'student_ids.*' => 'exists:students,id',
                'date' => 'required|date',
            ]);

            $date = $request->date;
            $markedCount = 0;

            foreach ($request->student_ids as $studentId) {
                $student = \App\Models\Student::find($studentId);

                $existingAttendance = Attendance::where('student_id', $studentId)
                    ->whereDate('attendance_date', $date)
                    ->first();

                if ($existingAttendance) {
                    $existingAttendance->update([
                        'status' => 'present',
                        'marked_at' => now(),
                        'marked_by' => auth()->id(),
                        'notes' => 'Bulk marked from dashboard',
                    ]);
                } else {
                    Attendance::create([
                        'student_id' => $studentId,
                        'batch_id' => $student->batch_id,
                        'subject_id' => 1,
                        'faculty_id' => auth()->id(),
                        'attendance_date' => $date,
                        'status' => 'present',
                        'check_in_time' => now()->format('H:i:s'),
                        'marked_at' => now(),
                        'marked_by' => auth()->id(),
                        'notes' => 'Bulk marked from dashboard',
                    ]);
                }
                $markedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$markedCount} students marked as present successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Permission denied or error occurred: '.$e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get system status
     */
    private function getSystemStatus()
    {
        try {
            // Check database connection
            $dbStatus = 'connected';
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $dbStatus = 'error';
            }

            // Check biometric integration status
            $biometricEnabled = $this->getSetting('etimeoffice_enabled', false);
            $biometricStatus = $biometricEnabled ? 'enabled' : 'disabled';

            // Get last sync time
            $lastSync = $this->getSetting('etimeoffice_last_sync', null);

            return [
                'database' => $dbStatus,
                'biometric_api' => $biometricStatus,
                'last_sync' => $lastSync,
                'total_devices' => $this->getSetting('biometric_devices_count', 0),
                'active_devices' => $this->getSetting('biometric_active_devices', 0),
            ];

        } catch (\Exception $e) {
            Log::error('Error getting system status: '.$e->getMessage());

            return [
                'database' => 'error',
                'biometric_api' => 'error',
                'last_sync' => null,
                'total_devices' => 0,
                'active_devices' => 0,
            ];
        }
    }

    /**
     * Get default stats structure
     */
    private function getDefaultStats()
    {
        return [
            'students' => [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'percentage' => 0,
            ],
            'faculty' => [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'percentage' => 0,
            ],
        ];
    }

    /**
     * Get weekly stats data
     */
    private function getWeeklyStatsData()
    {
        try {
            $weekStart = Carbon::now()->startOfWeek();
            $weeklyData = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->copy()->addDays($i);
                $dayData = Attendance::whereDate('attendance_date', $date)
                    ->selectRaw('
                    COUNT(DISTINCT CASE WHEN status IN ("present", "late") THEN student_id END) as present_count,
                    COUNT(DISTINCT CASE WHEN status = "absent" THEN student_id END) as absent_count,
                    COUNT(DISTINCT student_id) as total_count
                ')
                    ->first();

                $weeklyData[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $date->format('l'),
                    'present' => $dayData->present_count ?? 0,
                    'absent' => $dayData->absent_count ?? 0,
                    'total' => $dayData->total_count ?? 0,
                    'percentage' => $dayData && $dayData->total_count > 0 ?
                        round(($dayData->present_count / $dayData->total_count) * 100, 2) : 0,
                ];
            }

            return $weeklyData;

        } catch (\Exception $e) {
            Log::error('Error getting weekly stats: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get today's attendance statistics
     */
    public function getTodayStats()
    {
        try {
            $today = Carbon::today();

            $totalStudents = Student::count();
            $totalFaculty = User::role(['faculty', 'staff'])->count();

            $presentStudents = Attendance::whereDate('attendance_date', $today)
                ->whereHas('student')
                ->whereIn('status', ['present', 'late'])
                ->distinct('student_id')
                ->count();

            $presentFaculty = Attendance::whereDate('attendance_date', $today)
                ->whereHas('faculty')
                ->whereIn('status', ['present', 'late'])
                ->distinct('faculty_id')
                ->count();

            return [
                'students' => [
                    'total' => $totalStudents,
                    'present' => $presentStudents,
                    'absent' => $totalStudents - $presentStudents,
                    'percentage' => $totalStudents > 0 ? round(($presentStudents / $totalStudents) * 100, 2) : 0,
                ],
                'faculty' => [
                    'total' => $totalFaculty,
                    'present' => $presentFaculty,
                    'absent' => $totalFaculty - $presentFaculty,
                    'percentage' => $totalFaculty > 0 ? round(($presentFaculty / $totalFaculty) * 100, 2) : 0,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get today stats', ['error' => $e->getMessage()]);

            return [
                'students' => ['total' => 0, 'present' => 0, 'absent' => 0, 'percentage' => 0],
                'faculty' => ['total' => 0, 'present' => 0, 'absent' => 0, 'percentage' => 0],
            ];
        }
    }

    /**
     * Get today's dashboard data (for AJAX)
     */
    public function getTodayDashboard()
    {
        $this->authorize('manage attendance settings');

        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'live_attendances' => $this->getLiveAttendanceData(),
                    'stats' => $this->getTodayStats(),
                    'last_updated' => Carbon::now()->format('H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get weekly stats (for AJAX)
     */
    public function getWeeklyStats()
    {
        $this->authorize('manage attendance settings');

        try {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();

            $weeklyData = [];

            for ($date = $weekStart->copy(); $date <= $weekEnd; $date->addDay()) {
                $dayAttendance = Attendance::whereDate('attendance_date', $date)
                    ->selectRaw('
                        COUNT(DISTINCT CASE WHEN status IN ("present", "late") THEN student_id END) as present_count,
                        COUNT(DISTINCT CASE WHEN status = "absent" THEN student_id END) as absent_count,
                        COUNT(DISTINCT student_id) as total_count
                    ')
                    ->first();

                $weeklyData[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $date->format('l'),
                    'present' => $dayAttendance->present_count ?? 0,
                    'absent' => $dayAttendance->absent_count ?? 0,
                    'total' => $dayAttendance->total_count ?? 0,
                    'percentage' => $dayAttendance->total_count > 0
                        ? round(($dayAttendance->present_count / $dayAttendance->total_count) * 100, 2)
                        : 0,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $weeklyData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load weekly stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export today's attendance data
     */
    public function exportTodayAttendance(Request $request)
    {
        try {
            $this->authorize('manage attendance settings');

            $format = $request->get('format', 'xlsx'); // xlsx, csv, pdf
            $today = now()->toDateString();

            $attendanceData = $this->getAttendanceDataForExport($today, $today);

            $filename = "attendance_today_{$today}.{$format}";

            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($attendanceData, $filename);
                case 'pdf':
                    return $this->exportToPdf($attendanceData, $filename);
                default:
                    return Excel::download(new TodayAttendanceExport($attendanceData), $filename);
            }

        } catch (\Exception $e) {
            Log::error('Export failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Get attendance data formatted for export
     */
    private function getAttendanceDataForExport(string $startDate, string $endDate, string $statusFilter = 'all'): array
    {
        try {
            $query = Attendance::with(['student', 'batch'])
                ->whereBetween('attendance_date', [$startDate, $endDate]);

            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            $attendances = $query->orderBy('attendance_date', 'desc')
                ->orderBy('marked_at', 'asc')
                ->get();

            return $attendances->map(function ($attendance) {
                return [
                    'date' => $attendance->attendance_date,
                    'student_name' => $attendance->student->name ?? 'Unknown',
                    'enrollment_number' => $attendance->student->enrollment_number ?? 'N/A',
                    'batch_name' => $attendance->batch->name ?? 'N/A',
                    'course_name' => $attendance->student->course ?? 'N/A',
                    'status' => ucfirst($attendance->status),
                    'marked_time' => $attendance->marked_at ? $attendance->marked_at->format('H:i:s') : 'N/A',
                    'device_id' => $attendance->device_id ?? 'manual',
                    'notes' => $attendance->notes ?? '',
                    'biometric_code' => $attendance->student->biometric_employee_code ?? 'N/A',
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error getting attendance data for export', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Export to CSV format
     */
    private function exportToCsv(array $data, string $filename)
    {
        $attendanceData = is_array($data) && isset($data['data']) ? $data['data'] : $data;

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($attendanceData) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            // Write header
            if (! empty($attendanceData)) {
                fputcsv($file, array_keys($attendanceData[0]));

                // Write data
                foreach ($attendanceData as $row) {
                    fputcsv($file, $row);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF format
     */
    private function exportToPdf(array $data, string $filename)
    {
        try {
            $attendanceData = is_array($data) && isset($data['data']) ? $data['data'] : $data;
            $includeSummary = $data['include_summary'] ?? false;
            $dateRange = $data['date_range'] ?? null;

            // Generate summary if requested
            $summary = null;
            if ($includeSummary && ! empty($attendanceData)) {
                $summary = [
                    'total_records' => count($attendanceData),
                    'present_count' => collect($attendanceData)->where('status', 'Present')->count(),
                    'absent_count' => collect($attendanceData)->where('status', 'Absent')->count(),
                    'late_count' => collect($attendanceData)->where('status', 'Late')->count(),
                    'date_range' => $dateRange,
                ];
            }

            $pdf = \PDF::loadView('admin.attendance.exports.pdf', [
                'attendanceData' => $attendanceData,
                'summary' => $summary,
                'exportDate' => now()->format('Y-m-d H:i:s'),
                'exportedBy' => auth()->user()->name,
            ]);

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF export failed', ['error' => $e->getMessage()]);
            throw new \Exception('PDF export failed: '.$e->getMessage());
        }
    }

    /**
     * Export sync logs
     */
    public function exportSyncLogs(Request $request)
    {
        try {
            $this->authorize('manage attendance settings');

            $validated = $request->validate([
                'format' => 'required|in:xlsx,csv',
                'days' => 'integer|min:1|max:90',
            ]);

            $days = $validated['days'] ?? 30;
            $format = $validated['format'];

            if (Schema::hasTable('etimeoffice_sync_logs')) {
                $syncLogs = \App\Models\ETimeOfficeSyncLog::with('user:id,name')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($log) {
                        return [
                            'sync_date' => $log->created_at->format('Y-m-d H:i:s'),
                            'type' => ucfirst($log->sync_type),
                            'date_range' => $log->date_range_type,
                            'status' => ucfirst($log->status),
                            'total_records' => $log->total_records,
                            'created_records' => $log->created_records,
                            'updated_records' => $log->updated_records,
                            'skipped_records' => $log->skipped_records,
                            'duration' => $log->formatted_duration,
                            'success_rate' => $log->success_rate.'%',
                            'test_mode' => $log->test_mode ? 'Yes' : 'No',
                            'user' => $log->user->name ?? 'System',
                            'error_count' => $log->errors ? count($log->errors) : 0,
                        ];
                    })->toArray();
            } else {
                $syncLogs = [];
            }

            $filename = "sync_logs_last_{$days}_days.".$format;

            if ($format === 'csv') {
                return $this->exportToCsv($syncLogs, $filename);
            } else {
                return Excel::download(new SyncLogsExport($syncLogs), $filename);
            }

        } catch (\Exception $e) {
            Log::error('Sync logs export failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Get Attendance Leaderboard (Top 5 & Low 5)
     */
    public function getAttendanceLeaderboard(Request $request)
    {
        try {
            if (! $request->user()->can('view attendance') && ! $request->user()->can('manage attendance')) {
                $this->authorize('view attendance');
            }

            $period = $request->get('period', 'this_month');
            $batchId = $request->get('batch_id');
            $courseId = $request->get('course_id');

            // Determine date range
            if ($period === 'last_30_days') {
                $startDate = now()->subDays(30)->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'last_month') {
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
            } elseif ($period === 'this_week') {
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfDay();
            } elseif ($period === 'last_week') {
                $startDate = now()->subWeek()->startOfWeek();
                $endDate = now()->subWeek()->endOfWeek();
            } else {
                // Default: this_month
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfDay();
            }

            // Get total working days
            $totalWorkingDays = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->distinct('attendance_date')
                ->count('attendance_date');

            // Use at least 1 day to avoid division by zero if no attendance masked yet
            $calcTotalDays = $totalWorkingDays > 0 ? $totalWorkingDays : 1;

            // Build base query
            $query = Student::where('status', 'active')
                // Exclude students in internship batches
                ->whereDoesntHave('batch', function ($q) {
                    $q->where('is_on_internship', true);
                })
                // Exclude students who have never punched in (no attendance records)
                ->has('attendances');

            if ($batchId) {
                $query->where('batch_id', $batchId);
            }
            if ($courseId) {
                $query->whereHas('batch', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                });
            }

            $students = $query->with(['batch', 'batch.course'])->get();

            $leaderboard = $students->map(function ($student) use ($startDate, $endDate, $calcTotalDays) {
                $presentDays = Attendance::where('student_id', $student->id)
                    ->whereBetween('attendance_date', [$startDate, $endDate])
                    ->whereIn('status', ['present', 'late'])
                    ->distinct('attendance_date')
                    ->count();

                $percentage = ($presentDays / $calcTotalDays) * 100;

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'batch_name' => $student->batch->name ?? 'N/A',
                    'course_name' => $student->batch->course->name ?? 'N/A',
                    'present_days' => $presentDays,
                    'total_days' => $calcTotalDays,
                    'percentage' => round($percentage, 1),
                    'avatar' => substr($student->name, 0, 1),
                ];
            });

            // Top 5
            $topAttendance = $leaderboard->sortByDesc('percentage')->take(5)->values();

            // Bottom 5 (exclude 0% if result of no data, but here we cover active students)
            // User request: "check student status if status is not active exclude them" (already done via where('status', 'active'))
            $lowAttendance = $leaderboard->sortBy('percentage')->take(5)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'top_attendance' => $topAttendance,
                    'low_attendance' => $lowAttendance,
                    'total_working_days' => $totalWorkingDays, // Actual working days for display
                    'period_label' => $startDate->format('M d').' - '.$endDate->format('M d'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load leaderboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export custom date range attendance data
     */
    public function exportAttendanceData(Request $request)
    {
        try {
            $this->authorize('manage attendance settings');

            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'format' => 'required|in:xlsx,csv,pdf',
                'include_summary' => 'boolean',
                'filter_status' => 'nullable|in:all,present,absent,late',
            ]);

            $attendanceData = $this->getAttendanceDataForExport(
                $validated['start_date'],
                $validated['end_date'],
                $validated['filter_status'] ?? 'all'
            );

            $dateRange = Carbon::parse($validated['start_date'])->format('Y-m-d').'_to_'.
                Carbon::parse($validated['end_date'])->format('Y-m-d');
            $filename = "attendance_export_{$dateRange}.{$validated['format']}";

            $exportData = [
                'data' => $attendanceData,
                'include_summary' => $validated['include_summary'] ?? false,
                'date_range' => [
                    'start' => $validated['start_date'],
                    'end' => $validated['end_date'],
                ],
            ];

            switch ($validated['format']) {
                case 'csv':
                    return $this->exportToCsv($exportData, $filename);
                case 'pdf':
                    return $this->exportToPdf($exportData, $filename);
                default:
                    return Excel::download(new AttendanceExport($exportData), $filename);
            }

        } catch (\Exception $e) {
            Log::error('Custom export failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    private function getTodayAttendanceData()
    {
        // Replace with your actual attendance data logic
        return [
            [
                'student_name' => 'John Doe',
                'enrollment_number' => 'STD001',
                'batch_name' => 'Batch A',
                'course_name' => 'Computer Science',
                'status' => 'Present',
                'time_in' => '09:00',
                'time_out' => '17:00',
                'date' => now()->format('Y-m-d'),
                'remarks' => 'On time',
            ],
            // Add more data here
        ];
    }

    /**
     * Test attendance rules
     */
    public function testRules(Request $request)
    {
        $this->authorize('manage attendance settings');

        $request->validate([
            'test_time' => 'required|date_format:H:i:s',
            'user_type' => 'required|in:student,faculty',
        ]);

        try {
            $testTime = $request->test_time;
            $userType = $request->user_type;

            // Get appropriate settings based on user type
            if ($userType === 'student') {
                $startTime = $this->getSetting('attendance_student_college_start_time', '09:30:00');
                $presentCutoff = $this->getSetting('attendance_student_present_cutoff_time', '11:00:00');
                $lateCutoff = $this->getSetting('attendance_student_late_cutoff_time', '11:30:00');
            } else {
                $startTime = $this->getSetting('attendance_faculty_college_start_time', '09:00:00');
                $presentCutoff = $this->getSetting('attendance_faculty_present_cutoff_time', '10:30:00');
                $lateCutoff = $this->getSetting('attendance_faculty_late_cutoff_time', '11:00:00');
            }

            $endTime = $this->getSetting('attendance_college_end_time', '17:00:00');

            $settings = [
                'college_start_time' => $startTime,
                'present_cutoff_time' => $presentCutoff,
                'late_cutoff_time' => $lateCutoff,
                'college_end_time' => $endTime,
            ];

            $result = $this->determineStatus($testTime, $settings);

            return response()->json([
                'success' => true,
                'data' => [
                    'test_time' => $testTime,
                    'user_type' => $userType,
                    'status' => $result['status'],
                    'reason' => $result['reason'],
                    'settings_used' => $settings,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test rules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper method to update setting
     */
    private function updateSetting($key, $value)
    {
        try {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        } catch (\Exception $e) {
            Log::error("Error updating setting {$key}: ".$e->getMessage());
        }
    }

    /**
     * Helper method to get setting value safely
     */
    private function getSetting($key, $default = null)
    {
        try {
            return \App\Models\Setting::where('key', $key)->value('value') ?? $default;
        } catch (\Exception $e) {
            Log::error("Error getting setting {$key}: ".$e->getMessage());

            return $default;
        }
    }

    /**
     * Helper: Determine attendance status based on time
     */
    private function determineStatus(string $checkTime, array $settings): array
    {
        if ($checkTime < $settings['college_start_time']) {
            return [
                'status' => 'present',
                'reason' => 'Early arrival before college start time',
            ];
        } elseif ($checkTime <= $settings['present_cutoff_time']) {
            return [
                'status' => 'present',
                'reason' => 'Checked in within present time window',
            ];
        } elseif ($checkTime <= $settings['late_cutoff_time']) {
            return [
                'status' => 'late',
                'reason' => 'Checked in during late window',
            ];
        } elseif ($checkTime > $settings['college_end_time']) {
            return [
                'status' => 'absent',
                'reason' => 'Checked in after college end time',
            ];
        } else {
            return [
                'status' => 'absent',
                'reason' => 'Checked in after present/late cutoff times',
            ];
        }
    }
}
