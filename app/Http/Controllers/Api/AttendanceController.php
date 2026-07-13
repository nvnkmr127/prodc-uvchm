<?php

// File: app/Http/Controllers/Api/AttendanceController.php

namespace App\Http\Controllers\Api;

use App\Helpers\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance;
use App\Models\Batch;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
                    'errors' => $validator->errors(),
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

            if (! $person) {
                return response()->json([
                    'success' => false,
                    'message' => 'Person not found',
                    'biometric_id' => $request->biometric_id,
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
                'action' => $action,
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
                    'action' => $action,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark attendance via API', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
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
                    'count' => $attendances->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get today\'s attendance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
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
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'recent_attendances' => $recentAttendances,
                    'statistics' => $stats,
                    'last_updated' => Carbon::now()->toISOString(),
                ],
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
                'reason' => 'Admin forced status',
            ];
        }

        $checkTime = $checkInTime->format('H:i:s');
        $dayOfWeek = $checkInTime->format('l');

        // Check if weekend and weekend is disabled
        if (! $rules['weekend_enabled'] && in_array($dayOfWeek, ['Saturday', 'Sunday'])) {
            return [
                'status' => 'absent',
                'message' => 'Marked absent - Weekend attendance disabled',
                'reason' => 'Weekend not allowed',
            ];
        }

        // Check if before college start time
        if ($checkTime < $rules['college_start_time']) {
            return [
                'status' => 'present',
                'message' => 'Early arrival - Marked present',
                'reason' => 'Early arrival before college start time',
            ];
        }

        // Check if within present window (college start to present cutoff)
        if ($checkTime <= $rules['present_cutoff_time']) {
            return [
                'status' => 'present',
                'message' => 'On time - Marked present',
                'reason' => 'Checked in within present time window',
            ];
        }

        // Check if within late window (present cutoff to late cutoff)
        if ($checkTime <= $rules['late_cutoff_time']) {
            return [
                'status' => 'late',
                'message' => 'Late arrival - Marked late',
                'reason' => 'Checked in during late window',
            ];
        }

        // Check if after college end time
        if ($checkTime > $rules['college_end_time']) {
            return [
                'status' => 'absent',
                'message' => 'Too late - Marked absent (after college hours)',
                'reason' => 'Checked in after college end time',
            ];
        }

        // Default case - too late but before end time
        return [
            'status' => 'absent',
            'message' => 'Too late - Marked absent',
            'reason' => 'Checked in after late cutoff time',
        ];
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
                    'message' => 'You are not authorized to view this student\'s attendance.',
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
                        'percentage' => $percentage,
                    ],
                    'date_range' => [
                        'from' => $dateFrom,
                        'to' => $dateTo,
                    ],
                ],
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
            $batch = Batch::with('students')->findOrFail($batchId);
            $user = $request->user();

            // Use Laravel's policy for authorization
            if ($user->cannot('view', $batch)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this batch\'s attendance.',
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
                    'statistics' => $statistics,
                ],
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
                        'percentage' => $studentStats->total > 0 ? round(($studentStats->present / $studentStats->total) * 100, 2) : 0,
                    ],
                    'faculty' => [
                        'total' => $facultyStats->total ?? 0,
                        'present' => $facultyStats->present ?? 0,
                        'absent' => $facultyStats->absent ?? 0,
                        'late' => $facultyStats->late ?? 0,
                        'percentage' => $facultyStats->total > 0 ? round(($facultyStats->present / $facultyStats->total) * 100, 2) : 0,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get today\'s statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
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
                        : 0,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'week_start' => $weekStart->format('Y-m-d'),
                    'week_end' => $weekEnd->format('Y-m-d'),
                    'daily_stats' => $weeklyData,
                ],
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
                    'daily_stats' => $monthlyStats->map(function ($stat) {
                        return [
                            'date' => $stat->date,
                            'present' => $stat->present_count,
                            'absent' => $stat->absent_count,
                            'total' => $stat->total_count,
                            'percentage' => $stat->total_count > 0
                                ? round(($stat->present_count / $stat->total_count) * 100, 2)
                                : 0,
                        ];
                    }),
                ],
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
                    'refresh_interval' => 30, // seconds
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get live feed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get attendance summary for dashboard
     */
    public function getAttendanceSummary(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'today'); // today, week, month, year

            $startDate = match ($period) {
                'today' => Carbon::today(),
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                'year' => Carbon::now()->startOfYear(),
                default => Carbon::today()
            };

            $endDate = match ($period) {
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
                ->map(function ($stat) {
                    return [
                        'date' => $stat->date,
                        'total' => $stat->total,
                        'present' => $stat->present,
                        'absent' => $stat->absent,
                        'percentage' => $stat->total > 0 ? round(($stat->present / $stat->total) * 100, 2) : 0,
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
                        'end' => $endDate->format('Y-m-d'),
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
                            : 0,
                    ],
                    'daily_breakdown' => $dailyStats,
                    'top_performing_batches' => $topBatches,
                    'generated_at' => Carbon::now()->toISOString(),
                ],
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
