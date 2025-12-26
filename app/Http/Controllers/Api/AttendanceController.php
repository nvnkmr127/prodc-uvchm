<?php
// File: app/Http/Controllers/Api/AttendanceController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Models\Setting;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ErrorHandler;

class AttendanceController extends Controller
{
    /**
     * Mark single attendance
     */
    public function markAttendance(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'nullable|exists:students,id',
                'faculty_id' => 'nullable|exists:users,id',
                'biometric_id' => 'nullable|string',
                'check_in_time' => 'nullable|date_format:H:i:s',
                'attendance_date' => 'nullable|date',
                'force_status' => 'nullable|in:present,late,absent,excused',
                'subject_id' => 'nullable|exists:subjects,id',
                'batch_id' => 'nullable|exists:batches,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $checkInTime = $request->check_in_time 
                ? Carbon::createFromFormat('H:i:s', $request->check_in_time)
                : Carbon::now();
            
            $attendanceDate = $request->attendance_date 
                ? Carbon::parse($request->attendance_date)
                : Carbon::today();

            // Determine user type and get appropriate rules
            $userType = $request->student_id ? 'student' : 'faculty';
            $rules = $this->getAttendanceRules($userType);
            
            // Determine attendance status
            $statusResult = $this->determineAttendanceStatus($checkInTime, $rules, $request->force_status);

            // Find or identify the person
            $person = null;
            if ($request->student_id) {
                $person = Student::find($request->student_id);
            } elseif ($request->faculty_id) {
                $person = User::find($request->faculty_id);
            } elseif ($request->biometric_id) {
                // Try to find by biometric ID
                $person = Student::where('biometric_id', $request->biometric_id)->first()
                    ?? User::where('biometric_id', $request->biometric_id)->first();
            }

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'Person not found',
                    'biometric_id' => $request->biometric_id
                ], 404);
            }

            // Create attendance record
            $attendanceData = [
                'attendance_date' => $attendanceDate,
                'check_in_time' => $checkInTime->format('H:i:s'),
                'status' => $statusResult['status'],
                'remarks' => $statusResult['message'],
                'subject_id' => $request->subject_id ?? $this->getDefaultSubject(),
                'batch_id' => $request->batch_id ?? ($person instanceof Student ? $person->batch_id : null),
                'marked_by' => auth()->id() ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($person instanceof Student) {
                $attendanceData['student_id'] = $person->id;
                $attendanceData['faculty_id'] = $this->getDefaultFaculty();
            } else {
                $attendanceData['faculty_id'] = $person->id;
            }

            // Check for existing attendance
            $existingQuery = Attendance::where('attendance_date', $attendanceDate);
            if ($person instanceof Student) {
                $existingQuery->where('student_id', $person->id);
            } else {
                $existingQuery->where('faculty_id', $person->id);
            }
            
            $existing = $existingQuery->first();

            if ($existing) {
                $existing->update($attendanceData);
                $attendance = $existing;
                $action = 'updated';
            } else {
                $attendance = Attendance::create($attendanceData);
                $action = 'created';
            }

            Log::info('Attendance marked via API', [
                'attendance_id' => $attendance->id,
                'person_type' => $userType,
                'person_id' => $person->id,
                'status' => $statusResult['status'],
                'action' => $action
            ]);

            return response()->json([
                'success' => true,
                'message' => "Attendance {$action} successfully",
                'data' => [
                    'attendance_id' => $attendance->id,
                    'person_name' => $person->name,
                    'person_type' => $userType,
                    'status' => $statusResult['status'],
                    'check_in_time' => $checkInTime->format('H:i:s'),
                    'message' => $statusResult['message'],
                    'action' => $action
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark attendance via API', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return ErrorHandler::handleApiException(
                $e,
                'Failed to mark attendance via API',
                'Failed to mark attendance',
                500
            );
        }
    }

    /**
     * Mark bulk attendance
     */
    public function markBulkAttendance(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'attendance_records' => 'required|array|min:1',
                'attendance_records.*.student_id' => 'nullable|exists:students,id',
                'attendance_records.*.faculty_id' => 'nullable|exists:users,id',
                'attendance_records.*.biometric_id' => 'nullable|string',
                'attendance_records.*.status' => 'required|in:present,late,absent,excused',
                'attendance_records.*.check_in_time' => 'nullable|date_format:H:i:s',
                'batch_id' => 'nullable|exists:batches,id',
                'subject_id' => 'nullable|exists:subjects,id',
                'attendance_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $attendanceDate = $request->attendance_date 
                ? Carbon::parse($request->attendance_date)
                : Carbon::today();

            $successful = [];
            $failed = [];

            foreach ($request->attendance_records as $record) {
                try {
                    $checkInTime = isset($record['check_in_time']) 
                        ? Carbon::createFromFormat('H:i:s', $record['check_in_time'])
                        : Carbon::now();

                    // Find person
                    $person = null;
                    if (!empty($record['student_id'])) {
                        $person = Student::find($record['student_id']);
                        $userType = 'student';
                    } elseif (!empty($record['faculty_id'])) {
                        $person = User::find($record['faculty_id']);
                        $userType = 'faculty';
                    } elseif (!empty($record['biometric_id'])) {
                        $person = Student::where('biometric_id', $record['biometric_id'])->first()
                            ?? User::where('biometric_id', $record['biometric_id'])->first();
                        $userType = $person instanceof Student ? 'student' : 'faculty';
                    }

                    if (!$person) {
                        $failed[] = [
                            'record' => $record,
                            'error' => 'Person not found'
                        ];
                        continue;
                    }

                    // Create attendance
                    $attendanceData = [
                        'attendance_date' => $attendanceDate,
                        'check_in_time' => $checkInTime->format('H:i:s'),
                        'status' => $record['status'],
                        'subject_id' => $request->subject_id ?? $this->getDefaultSubject(),
                        'batch_id' => $request->batch_id ?? ($person instanceof Student ? $person->batch_id : null),
                        'marked_by' => auth()->id() ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($person instanceof Student) {
                        $attendanceData['student_id'] = $person->id;
                        $attendanceData['faculty_id'] = $this->getDefaultFaculty();
                    } else {
                        $attendanceData['faculty_id'] = $person->id;
                    }

                    $attendance = Attendance::create($attendanceData);
                    
                    $successful[] = [
                        'attendance_id' => $attendance->id,
                        'person_name' => $person->name,
                        'person_type' => $userType,
                        'status' => $record['status']
                    ];

                } catch (\Exception $e) {
                    $failed[] = [
                        'record' => $record,
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('Bulk attendance processed', [
                'total_records' => count($request->attendance_records),
                'successful' => count($successful),
                'failed' => count($failed)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk attendance processed',
                'data' => [
                    'total_records' => count($request->attendance_records),
                    'successful_count' => count($successful),
                    'failed_count' => count($failed),
                    'successful' => $successful,
                    'failed' => $failed
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process bulk attendance', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return ErrorHandler::handleApiException(
                $e,
                'Failed to process bulk attendance',
                'Failed to process bulk attendance',
                500
            );
        }
    }

    /**
     * ✅ FIX 3: Get attendance configuration (GET method)
     */
    public function getConfig(Request $request): JsonResponse
    {
        try {
            $studentRules = $this->getAttendanceRules('student');
            $facultyRules = $this->getAttendanceRules('faculty');
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance configuration retrieved',
                'data' => [
                    'student_rules' => $studentRules,
                    'faculty_rules' => $facultyRules,
                    'available_time_slots' => TimeSlot::select('id', 'name', 'start_time', 'end_time')
                        ->orderBy('start_time')
                        ->get(),
                    'example_scenarios' => [
                        'student' => [
                            [
                                'check_in_time' => $studentRules['college_start_time'],
                                'expected_status' => 'present',
                                'reason' => 'On time arrival'
                            ],
                            [
                                'check_in_time' => $studentRules['present_cutoff_time'],
                                'expected_status' => 'present',
                                'reason' => 'Just within present window'
                            ],
                            [
                                'check_in_time' => $studentRules['late_cutoff_time'],
                                'expected_status' => 'late',
                                'reason' => 'Within late window'
                            ]
                        ],
                        'faculty' => [
                            [
                                'check_in_time' => $facultyRules['college_start_time'],
                                'expected_status' => 'present',
                                'reason' => 'On time arrival'
                            ],
                            [
                                'check_in_time' => $facultyRules['present_cutoff_time'],
                                'expected_status' => 'present',
                                'reason' => 'Just within present window'
                            ],
                            [
                                'check_in_time' => $facultyRules['late_cutoff_time'],
                                'expected_status' => 'late',
                                'reason' => 'Within late window'
                            ]
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiException(
                $e,
                'Failed to retrieve attendance configuration',
                'Failed to retrieve attendance configuration',
                500
            );
        }
    }

    /**
     * ✅ FIX 3: Update attendance configuration (POST method)
     */
    public function updateConfig(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                // Student settings
                'student_college_start_time' => 'required|date_format:H:i:s',
                'student_present_cutoff_time' => 'required|date_format:H:i:s',
                'student_late_cutoff_time' => 'required|date_format:H:i:s',
                
                // Faculty settings
                'faculty_college_start_time' => 'required|date_format:H:i:s',
                'faculty_present_cutoff_time' => 'required|date_format:H:i:s',
                'faculty_late_cutoff_time' => 'required|date_format:H:i:s',
                
                // General settings
                'college_end_time' => 'required|date_format:H:i:s',
                'weekend_enabled' => 'boolean',
                'grace_period_minutes' => 'integer|min:0|max:60',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update student settings
            $this->updateSetting('attendance_student_college_start_time', $request->student_college_start_time);
            $this->updateSetting('attendance_student_present_cutoff_time', $request->student_present_cutoff_time);
            $this->updateSetting('attendance_student_late_cutoff_time', $request->student_late_cutoff_time);
            
            // Update faculty settings
            $this->updateSetting('attendance_faculty_college_start_time', $request->faculty_college_start_time);
            $this->updateSetting('attendance_faculty_present_cutoff_time', $request->faculty_present_cutoff_time);
            $this->updateSetting('attendance_faculty_late_cutoff_time', $request->faculty_late_cutoff_time);
            
            // Update general settings
            $this->updateSetting('attendance_college_end_time', $request->college_end_time);
            $this->updateSetting('attendance_weekend_enabled', $request->boolean('weekend_enabled', false));
            $this->updateSetting('attendance_grace_period_minutes', $request->grace_period_minutes ?? 10);

            Log::info('Attendance configuration updated via API', [
                'updated_by' => auth()->id(),
                'new_config' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance configuration updated successfully',
                'data' => [
                    'student_rules' => $this->getAttendanceRules('student'),
                    'faculty_rules' => $this->getAttendanceRules('faculty')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update attendance configuration via API', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return ErrorHandler::handleApiException(
                $e,
                'Failed to update attendance configuration',
                'Failed to update attendance configuration',
                500
            );
        }
    }

    /**
     * Get today's attendance
     */
    public function getTodayAttendance(Request $request): JsonResponse
    {
        try {
            $today = Carbon::today();
            $type = $request->get('type', 'all'); // all, students, faculty
            
            $query = Attendance::whereDate('attendance_date', $today);
            
            if ($type === 'students') {
                $query->whereNotNull('student_id')->with(['student.batch', 'subject']);
            } elseif ($type === 'faculty') {
                $query->whereNotNull('faculty_id')->with(['faculty', 'subject']);
            } else {
                $query->with(['student.batch', 'faculty', 'subject']);
            }
            
            $attendances = $query->orderBy('check_in_time', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $today->format('Y-m-d'),
                    'type' => $type,
                    'attendances' => $attendances,
                    'count' => $attendances->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get today\'s attendance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get real-time attendance data
     */
    public function getRealTimeData(Request $request): JsonResponse
    {
        try {
            $today = Carbon::today();
            
            // Get recent attendance (last 50 records)
            $recentAttendances = Attendance::whereDate('attendance_date', $today)
                ->with(['student.batch', 'faculty', 'subject'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            // Get statistics
            $stats = [
                'students' => [
                    'total' => Attendance::whereDate('attendance_date', $today)->whereNotNull('student_id')->distinct('student_id')->count(),
                    'present' => Attendance::whereDate('attendance_date', $today)->whereNotNull('student_id')->whereIn('status', ['present', 'late'])->distinct('student_id')->count(),
                ],
                'faculty' => [
                    'total' => Attendance::whereDate('attendance_date', $today)->whereNotNull('faculty_id')->distinct('faculty_id')->count(),
                    'present' => Attendance::whereDate('attendance_date', $today)->whereNotNull('faculty_id')->whereIn('status', ['present', 'late'])->distinct('faculty_id')->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'recent_attendances' => $recentAttendances,
                    'statistics' => $stats,
                    'last_updated' => Carbon::now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            return ErrorHandler::handleApiException(
                $e,
                'Failed to get real-time data',
                'Failed to get real-time data',
                500
            );
        }
    }

    /**
     * ✅ FIX 4: Get attendance rules based on user type (student/faculty)
     */
    private function getAttendanceRules(string $userType = 'student'): array
    {
        $defaultRules = [
            'student' => [
                'college_start_time' => '09:30:00',
                'present_cutoff_time' => '11:00:00',
                'late_cutoff_time' => '11:30:00',
                'college_end_time' => '17:00:00',
                'weekend_enabled' => false,
                'grace_period_minutes' => 10,
            ],
            'faculty' => [
                'college_start_time' => '09:00:00',
                'present_cutoff_time' => '10:30:00',
                'late_cutoff_time' => '11:00:00',
                'college_end_time' => '17:00:00',
                'weekend_enabled' => false,
                'grace_period_minutes' => 10,
            ]
        ];

        try {
            $prefix = $userType === 'faculty' ? 'attendance_faculty_' : 'attendance_student_';
            
            return [
                'college_start_time' => $this->getSetting($prefix . 'college_start_time', $defaultRules[$userType]['college_start_time']),
                'present_cutoff_time' => $this->getSetting($prefix . 'present_cutoff_time', $defaultRules[$userType]['present_cutoff_time']),
                'late_cutoff_time' => $this->getSetting($prefix . 'late_cutoff_time', $defaultRules[$userType]['late_cutoff_time']),
                'college_end_time' => $this->getSetting('attendance_college_end_time', $defaultRules[$userType]['college_end_time']),
                'weekend_enabled' => $this->getSetting('attendance_weekend_enabled', $defaultRules[$userType]['weekend_enabled']),
                'grace_period_minutes' => $this->getSetting('attendance_grace_period_minutes', $defaultRules[$userType]['grace_period_minutes']),
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to load attendance rules from settings, using defaults', ['error' => $e->getMessage()]);
            return $defaultRules[$userType];
        }
    }

    /**
     * Get setting value with fallback
     */
    private function getSetting(string $key, $default)
    {
        try {
            if (class_exists('App\Models\Setting')) {
                $setting = Setting::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            }
        } catch (\Exception $e) {
            Log::debug('Setting lookup failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
        
        return $default;
    }

    /**
     * Update or create setting
     */
    private function updateSetting(string $key, $value): void
    {
        try {
            if (class_exists('App\Models\Setting')) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => is_bool($value) ? 'boolean' : 'text',
                        'group' => 'attendance',
                        'description' => "Attendance configuration: {$key}",
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to update setting', ['key' => $key, 'value' => $value, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Determine attendance status based on check-in time and rules
     */
    private function determineAttendanceStatus(Carbon $checkInTime, array $rules, ?string $forceStatus = null): array
    {
        // If admin forces a status, use it
        if ($forceStatus) {
            return [
                'status' => $forceStatus,
                'message' => "Attendance marked as {$forceStatus} (Admin override)",
                'reason' => 'Admin forced status'
            ];
        }

        $checkTime = $checkInTime->format('H:i:s');
        $dayOfWeek = $checkInTime->format('l');

        // Check if weekend and weekend is disabled
        if (!$rules['weekend_enabled'] && in_array($dayOfWeek, ['Saturday', 'Sunday'])) {
            return [
                'status' => 'absent',
                'message' => 'Marked absent - Weekend attendance disabled',
                'reason' => 'Weekend not allowed'
            ];
        }

        // Check if before college start time
        if ($checkTime < $rules['college_start_time']) {
            return [
                'status' => 'present',
                'message' => 'Early arrival - Marked present',
                'reason' => 'Early arrival before college start time'
            ];
        }

        // Check if within present window (college start to present cutoff)
        if ($checkTime <= $rules['present_cutoff_time']) {
            return [
                'status' => 'present',
                'message' => 'On time - Marked present',
                'reason' => 'Checked in within present time window'
            ];
        }

        // Check if within late window (present cutoff to late cutoff)
        if ($checkTime <= $rules['late_cutoff_time']) {
            return [
                'status' => 'late',
                'message' => 'Late arrival - Marked late',
                'reason' => 'Checked in during late window'
            ];
        }

        // Check if after college end time
        if ($checkTime > $rules['college_end_time']) {
            return [
                'status' => 'absent',
                'message' => 'Too late - Marked absent (after college hours)',
                'reason' => 'Checked in after college end time'
            ];
        }

        // Default case - too late but before end time
        return [
            'status' => 'absent',
            'message' => 'Too late - Marked absent',
            'reason' => 'Checked in after late cutoff time'
        ];
    }

    /**
     * Get default subject
     */
    private function getDefaultSubject(): int
    {
        try {
            $generalSubject = \App\Models\Subject::where('name', 'General')->first();
            if ($generalSubject) return $generalSubject->id;

            $firstSubject = \App\Models\Subject::first();
            return $firstSubject ? $firstSubject->id : 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Get default faculty
     */
    private function getDefaultFaculty(): int
    {
        try {
            $systemUser = User::where('name', 'Biometric System')->first();
            if ($systemUser) return $systemUser->id;

            $adminUser = User::role(['admin', 'super-admin'])->first();
            if ($adminUser) return $adminUser->id;

            $facultyUser = User::role(['staff', 'faculty'])->first();
            if ($facultyUser) return $facultyUser->id;

            return 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Get student attendance
     */
    public function getStudentAttendance(Request $request, $studentId): JsonResponse
    {
        try {
            $student = Student::findOrFail($studentId);
            $user = $request->user();
            
            // Use Laravel's policy for authorization
            if ($user->cannot('view', $student)) {
                 return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this student\'s attendance.'
                ], 403);
            }
            
            $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
            
            $attendances = Attendance::where('student_id', $studentId)
                ->whereBetween('attendance_date', [$dateFrom, $dateTo])
                ->with(['subject', 'faculty'])
                ->orderBy('attendance_date', 'desc')
                ->get();
            
            // Calculate statistics
            $total = $attendances->count();
            $present = $attendances->whereIn('status', ['present', 'late'])->count();
            $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'attendances' => $attendances,
                    'statistics' => [
                        'total' => $total,
                        'present' => $present,
                        'absent' => $attendances->where('status', 'absent')->count(),
                        'late' => $attendances->where('status', 'late')->count(),
                        'excused' => $attendances->where('status', 'excused')->count(),
                        'percentage' => $percentage
                    ],
                    'date_range' => [
                        'from' => $dateFrom,
                        'to' => $dateTo
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return ErrorHandler::handleApiException(
                $e,
                'Failed to get student attendance',
                'Failed to get student attendance',
                404
            );
        }
    }

    /**
     * Get batch attendance
     */
    public function getBatchAttendance(Request $request, $batchId): JsonResponse
    {
        try {
            $batch = \App\Models\Batch::with('students')->findOrFail($batchId);
            $user = $request->user();
            
            // Use Laravel's policy for authorization
            if ($user->cannot('view', $batch)) {
                 return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this batch\'s attendance.'
                ], 403);
            }
            
            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            
            $attendances = Attendance::where('batch_id', $batchId)
                ->whereDate('attendance_date', $date)
                ->with(['student', 'subject'])
                ->get();
            
            // Get students who haven't marked attendance
            $markedStudentIds = $attendances->pluck('student_id')->toArray();
            $absentStudents = $batch->students->whereNotIn('id', $markedStudentIds);
            
            $statistics = [
                'total_students' => $batch->students->count(),
                'marked_attendance' => $attendances->count(),
                'present' => $attendances->whereIn('status', ['present', 'late'])->count(),
                'absent' => $attendances->where('status', 'absent')->count() + $absentStudents->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'excused' => $attendances->where('status', 'excused')->count(),
            ];
            
            $statistics['percentage'] = $statistics['total_students'] > 0 
                ? round(($statistics['present'] / $statistics['total_students']) * 100, 2) 
                : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'batch' => $batch,
                    'date' => $date,
                    'attendances' => $attendances,
                    'absent_students' => $absentStudents->values(),
                    'statistics' => $statistics
                ]
            ]);
            
        } catch (\Exception $e) {
            return ErrorHandler::handleApiException(
                $e,
                'Failed to get batch attendance',
                'Failed to get batch attendance',
                404
            );
        }
    }

    /**
     * Get today's statistics
     */
    public function getTodayStats(): JsonResponse
    {
        try {
            $today = Carbon::today();
            
            $studentStats = Attendance::whereDate('attendance_date', $today)
                ->whereNotNull('student_id')
                ->selectRaw('
                    COUNT(DISTINCT student_id) as total,
                    COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present,
                    COUNT(CASE WHEN status = "absent" THEN 1 END) as absent,
                    COUNT(CASE WHEN status = "late" THEN 1 END) as late
                ')
                ->first();
                
            $facultyStats = Attendance::whereDate('attendance_date', $today)
                ->whereNotNull('faculty_id')
                ->selectRaw('
                    COUNT(DISTINCT faculty_id) as total,
                    COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present,
                    COUNT(CASE WHEN status = "absent" THEN 1 END) as absent,
                    COUNT(CASE WHEN status = "late" THEN 1 END) as late
                ')
                ->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $today->format('Y-m-d'),
                    'students' => [
                        'total' => $studentStats->total ?? 0,
                        'present' => $studentStats->present ?? 0,
                        'absent' => $studentStats->absent ?? 0,
                        'late' => $studentStats->late ?? 0,
                        'percentage' => $studentStats->total > 0 ? round(($studentStats->present / $studentStats->total) * 100, 2) : 0
                    ],
                    'faculty' => [
                        'total' => $facultyStats->total ?? 0,
                        'present' => $facultyStats->present ?? 0,
                        'absent' => $facultyStats->absent ?? 0,
                        'late' => $facultyStats->late ?? 0,
                        'percentage' => $facultyStats->total > 0 ? round(($facultyStats->present / $facultyStats->total) * 100, 2) : 0
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get today\'s statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get weekly statistics
     */
    public function getWeeklyStats(): JsonResponse
    {
        try {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
            
            $weeklyData = [];
            
            for ($date = $weekStart->copy(); $date <= $weekEnd; $date->addDay()) {
                $dayStats = Attendance::whereDate('attendance_date', $date)
                    ->selectRaw('
                        COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present_count,
                        COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_count,
                        COUNT(*) as total_count
                    ')
                    ->first();
                
                $weeklyData[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $date->format('l'),
                    'present' => $dayStats->present_count ?? 0,
                    'absent' => $dayStats->absent_count ?? 0,
                    'total' => $dayStats->total_count ?? 0,
                    'percentage' => $dayStats->total_count > 0 
                        ? round(($dayStats->present_count / $dayStats->total_count) * 100, 2) 
                        : 0
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'week_start' => $weekStart->format('Y-m-d'),
                    'week_end' => $weekEnd->format('Y-m-d'),
                    'daily_stats' => $weeklyData
                ]
            ]);
            
        } catch (\Exception $e) {
            return ErrorHandler::handleApiException(
                $e,
                'Failed to get weekly statistics',
                'Failed to get weekly statistics',
                500
            );
        }
    }

    /**
     * Get monthly statistics
     */
    public function getMonthlyStats(): JsonResponse
    {
        try {
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd = Carbon::now()->endOfMonth();
            
            $monthlyStats = Attendance::whereBetween('attendance_date', [$monthStart, $monthEnd])
                ->selectRaw('
                    DATE(attendance_date) as date,
                    COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present_count,
                    COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_count,
                    COUNT(*) as total_count
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'month_start' => $monthStart->format('Y-m-d'),
                    'month_end' => $monthEnd->format('Y-m-d'),
                    'daily_stats' => $monthlyStats->map(function($stat) {
                        return [
                            'date' => $stat->date,
                            'present' => $stat->present_count,
                            'absent' => $stat->absent_count,
                            'total' => $stat->total_count,
                            'percentage' => $stat->total_count > 0 
                                ? round(($stat->present_count / $stat->total_count) * 100, 2) 
                                : 0
                        ];
                    })
                ]
            ]);
            
        } catch (\Exception $e) {
            return ErrorHandler::handleApiException(
                $e,
                'Failed to get monthly statistics',
                'Failed to get monthly statistics',
                500
            );
        }
    }

    /**
     * Get live feed for real-time updates
     */
    public function getLiveFeed(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $fiveMinutesAgo = Carbon::now()->subMinutes(5);
            
            // Get attendance marked in the last 5 minutes
            $recentAttendances = Attendance::where('created_at', '>=', $fiveMinutesAgo)
                ->with(['student.batch', 'faculty', 'subject'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recent_attendances' => $recentAttendances,
                    'count' => $recentAttendances->count(),
                    'timestamp' => Carbon::now()->toISOString(),
                    'refresh_interval' => 30 // seconds
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get live feed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get attendance by date range
     */
    public function getAttendanceByDateRange(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'student_id' => 'nullable|exists:students,id',
                'faculty_id' => 'nullable|exists:users,id',
                'batch_id' => 'nullable|exists:batches,id',
                'status' => 'nullable|in:present,late,absent,excused',
                'limit' => 'nullable|integer|min:1|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Authorization checks based on user role
            $user = $request->user();
            
            // If a student is making the request, enforce that they can only see their own data.
            if ($user->hasRole('student')) {
                $student = $user->student;
                if (!$student || ($request->student_id && $request->student_id != $student->id)) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }
                $request->merge(['student_id' => $student->id]); // Force filter to self
            } elseif ($user->cannot('viewAny', Attendance::class)) {
                 if ($user->hasRole('student')) {
                    $student = $user->student;
                    if (!$student) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Student profile not found'
                        ], 404);
                    }
                    // Force student_id to current user's student record
                    $request->merge(['student_id' => $student->id]);
                    // Students cannot access other faculty or batch data
                    if ($request->faculty_id || $request->batch_id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Students can only access their own attendance data'
                        ], 403);
                    }
                }
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $query = Attendance::whereBetween('attendance_date', [$request->date_from, $request->date_to])
                ->with(['student.batch', 'faculty', 'subject', 'batch']);
                
            // Apply role-based filtering for non-admin users
            if (!$user->hasRole('super-admin')) {
                if ($user->hasRole('faculty')) {
                    // Faculty can only see attendance for their subjects/batches
                    $query->where(function($q) use ($user) {
                        $q->where('faculty_id', $user->id)
                          ->orWhereHas('batch', function($bq) use ($user) {
                              $bq->whereHas('subjects', function($sq) use ($user) {
                                  $sq->where('faculty_id', $user->id);
                              });
                          });
                    });
                }
            }

            // Apply filters
            if ($request->student_id) {
                $query->where('student_id', $request->student_id);
            }

            if ($request->faculty_id) {
                $query->where('faculty_id', $request->faculty_id);
            }

            if ($request->batch_id) {
                $query->where('batch_id', $request->batch_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $limit = $request->get('limit', 100);
            $attendances = $query->orderBy('attendance_date', 'desc')
                ->orderBy('check_in_time', 'desc')
                ->limit($limit)
                ->get();

            // Calculate summary statistics
            $totalRecords = $query->count();
            $statusCounts = $query->selectRaw('
                COUNT(CASE WHEN status = "present" THEN 1 END) as present_count,
                COUNT(CASE WHEN status = "late" THEN 1 END) as late_count,
                COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_count,
                COUNT(CASE WHEN status = "excused" THEN 1 END) as excused_count
            ')->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'attendances' => $attendances,
                    'summary' => [
                        'total_records' => $totalRecords,
                        'present' => $statusCounts->present_count ?? 0,
                        'late' => $statusCounts->late_count ?? 0,
                        'absent' => $statusCounts->absent_count ?? 0,
                        'excused' => $statusCounts->excused_count ?? 0,
                        'attendance_percentage' => $totalRecords > 0 
                            ? round((($statusCounts->present_count + $statusCounts->late_count + $statusCounts->excused_count) / $totalRecords) * 100, 2)
                            : 0
                    ],
                    'filters' => [
                        'date_from' => $request->date_from,
                        'date_to' => $request->date_to,
                        'student_id' => $request->student_id,
                        'faculty_id' => $request->faculty_id,
                        'batch_id' => $request->batch_id,
                        'status' => $request->status,
                        'limit' => $limit
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return ErrorHandler::handleApiException(
                $e,
                'Failed to get attendance by date range',
                'Failed to get attendance by date range',
                500
            );
        }
    }

    /**
     * Get attendance summary for dashboard
     */
    public function getAttendanceSummary(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'today'); // today, week, month, year
            
            $startDate = match($period) {
                'today' => Carbon::today(),
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                'year' => Carbon::now()->startOfYear(),
                default => Carbon::today()
            };
            
            $endDate = match($period) {
                'today' => Carbon::today(),
                'week' => Carbon::now()->endOfWeek(),
                'month' => Carbon::now()->endOfMonth(),
                'year' => Carbon::now()->endOfYear(),
                default => Carbon::today()
            };

            // Get overall statistics
            $overallStats = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_records,
                    COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present_count,
                    COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_count,
                    COUNT(CASE WHEN status = "late" THEN 1 END) as late_count,
                    COUNT(CASE WHEN status = "excused" THEN 1 END) as excused_count,
                    COUNT(DISTINCT student_id) as unique_students,
                    COUNT(DISTINCT faculty_id) as unique_faculty
                ')
                ->first();

            // Get daily breakdown
            $dailyStats = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->selectRaw('
                    DATE(attendance_date) as date,
                    COUNT(*) as total,
                    COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present,
                    COUNT(CASE WHEN status = "absent" THEN 1 END) as absent
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function($stat) {
                    return [
                        'date' => $stat->date,
                        'total' => $stat->total,
                        'present' => $stat->present,
                        'absent' => $stat->absent,
                        'percentage' => $stat->total > 0 ? round(($stat->present / $stat->total) * 100, 2) : 0
                    ];
                });

            // Get top performing batches
            $topBatches = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->whereNotNull('batch_id')
                ->selectRaw('
                    batch_id,
                    COUNT(*) as total,
                    COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) as present,
                    ROUND((COUNT(CASE WHEN status IN ("present", "late") THEN 1 END) / COUNT(*)) * 100, 2) as percentage
                ')
                ->with('batch:id,name')
                ->groupBy('batch_id')
                ->having('total', '>=', 5) // Only batches with at least 5 records
                ->orderByDesc('percentage')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ],
                    'overall_statistics' => [
                        'total_records' => $overallStats->total_records ?? 0,
                        'present_count' => $overallStats->present_count ?? 0,
                        'absent_count' => $overallStats->absent_count ?? 0,
                        'late_count' => $overallStats->late_count ?? 0,
                        'excused_count' => $overallStats->excused_count ?? 0,
                        'unique_students' => $overallStats->unique_students ?? 0,
                        'unique_faculty' => $overallStats->unique_faculty ?? 0,
                        'overall_percentage' => $overallStats->total_records > 0 
                            ? round(($overallStats->present_count / $overallStats->total_records) * 100, 2) 
                            : 0
                    ],
                    'daily_breakdown' => $dailyStats,
                    'top_performing_batches' => $topBatches,
                    'generated_at' => Carbon::now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return ErrorHandler::handleApiException(
                $e,
                'Failed to get attendance summary',
                'Failed to get attendance summary',
                500
            );
        }
    }
}