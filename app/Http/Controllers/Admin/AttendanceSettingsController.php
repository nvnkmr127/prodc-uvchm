<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceExport;
use App\Exports\SyncLogsExport;
use App\Exports\TodayAttendanceExport;
use App\Helpers\ErrorHandler;  // ✅ FIXED: Correct namespace
use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance;
use App\Models\Batch;  // ✅ ADD: Missing import
use App\Models\Course;
use App\Models\ETimeOfficeSyncLog;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
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
        $dbSettings = Setting::where('key', 'like', 'attendance_%')->pluck('value', 'key');

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
                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    ['value' => $value]
                );
            }

            // 4. Save Boolean/Integer Settings
            if ($request->has('grace_period_minutes')) {
                Setting::updateOrCreate(
                    ['key' => 'attendance_grace_period_minutes'],
                    ['value' => $request->grace_period_minutes]
                );
            }

            if ($request->has('weekend_enabled')) {
                Setting::updateOrCreate(
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
     * Determine attendance status based on punch time
     */
    private function determineAttendanceStatus(Carbon $punchTime): string
    {
        // Get office start time from settings (default: 9:00 AM)
        $officeStartTime = $this->getSetting('office_start_time', '09:00');
        $lateThreshold = (int) $this->getSetting('late_threshold_minutes', 30);

        $startTime = Carbon::parse($punchTime->format('Y-m-d').' '.$officeStartTime);
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
     * Log sync activities
     */
    private function log(string $message, string $level = 'info'): void
    {
        \Log::{$level}('ETimeOffice Sync: '.$message, [
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]);
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
            $latestLog = ETimeOfficeSyncLog::orderBy('created_at', 'desc')->first();

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

    // Temporary test method - add this at the end of the class

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
            $neverPunchedStudents = Student::active()
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
            $batches = Batch::with('course')->orderBy('name')->get();
            $courses = Course::orderBy('name')->get();
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

        } catch (AuthorizationException $e) {
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
                'batches' => Batch::with('course')->get(),
                'courses' => Course::get(),
                'batchId' => null,
                'courseId' => null,
                'weeklyTrend' => [],
                'liveData' => [],
                'systemStatus' => ['database' => 'connected'],
                'weeklyStats' => [],
            ])->with('error', 'Some dashboard data could not be loaded');
        }
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

            $student = Student::findOrFail($request->student_id);
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
                $student = Student::find($studentId);

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
                $syncLogs = ETimeOfficeSyncLog::with('user:id,name')
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
            Setting::updateOrCreate(
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
            return Setting::where('key', $key)->value('value') ?? $default;
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
