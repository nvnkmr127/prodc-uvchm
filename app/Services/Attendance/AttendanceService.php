<?php

namespace App\Services\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceCache;
use App\Models\Student;
use App\Models\Batch;
use App\Models\Timetable;
use App\Services\Attendance\NotificationService;
use App\Events\Attendance\AttendanceEvent;
use App\Traits\Attendance\ManagesAttendance;
use App\Traits\Attendance\CalculatesMetrics;
use App\Traits\Attendance\ValidatesData;
use App\Traits\Attendance\HandlesNotifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AttendanceService
{
    use ManagesAttendance, CalculatesMetrics, ValidatesData, HandlesNotifications;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get attendance data for a student with filters
     */
    public function getStudentAttendance(int $studentId, ?Carbon $fromDate = null, ?Carbon $toDate = null): Collection
    {
        $query = Attendance::where('student_id', $studentId)
                          ->with(['subject', 'faculty', 'batch'])
                          ->orderBy('attendance_date', 'desc');

        if ($fromDate) {
            $query->where('attendance_date', '>=', $fromDate->format('Y-m-d'));
        }

        if ($toDate) {
            $query->where('attendance_date', '<=', $toDate->format('Y-m-d'));
        }

        return $query->get();
    }

    /**
     * Get attendance data for a batch with filters
     */
    public function getBatchAttendance(int $batchId, array $filters = []): Collection
    {
        $query = Attendance::where('batch_id', $batchId)
                          ->with(['student', 'subject', 'faculty'])
                          ->orderBy('attendance_date', 'desc');

        if (isset($filters['date_from'])) {
            $query->where('attendance_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('attendance_date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        return $query->get();
    }

    /**
     * Calculate comprehensive statistics for a student
     */
    public function calculateStudentStats(int $studentId, array $filters = []): array
    {
        $attendances = $this->getStudentAttendance(
            $studentId,
            isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : null,
            isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : null
        );

        if ($attendances->isEmpty()) {
            return $this->getEmptyStats();
        }

        // Use trait methods for calculations
        $attendancePercentage = $this->calculateAttendancePercentage($attendances);
        $punctualityPercentage = $this->calculatePunctualityPercentage($attendances);
        $consecutiveAbsents = $this->calculateConsecutiveAbsences($attendances);
        $monthlyTrends = $this->calculateMonthlyTrends($attendances);
        $weeklyPatterns = $this->calculateWeeklyPatterns($attendances);
        $latePatterns = $this->calculateLatePatterns($attendances);

        $stats = [
            'total_classes' => $attendances->count(),
            'present_count' => $attendances->whereIn('status', ['present', 'late', 'excused'])->count(),
            'absent_count' => $attendances->where('status', 'absent')->count(),
            'late_count' => $attendances->where('status', 'late')->count(),
            'excused_count' => $attendances->where('status', 'excused')->count(),
            'attendance_percentage' => $attendancePercentage,
            'punctuality_percentage' => $punctualityPercentage,
            'consecutive_absents' => $consecutiveAbsents,
            'attendance_streak' => $this->calculateAttendanceStreak($attendances),
            'monthly_trends' => $monthlyTrends,
            'weekly_patterns' => $weeklyPatterns,
            'late_patterns' => $latePatterns,
            'performance_level' => $this->calculatePerformanceLevel($attendancePercentage),
            'risk_level' => $this->calculateRiskLevel([
                'attendance_percentage' => $attendancePercentage,
                'consecutive_absents' => $consecutiveAbsents
            ])
        ];

        return $stats;
    }
    
    /**
     * Get absent students for a specific date
     *
     * @param \Carbon\Carbon $date
     * @return \Illuminate\Support\Collection
     */

    public function getAbsentStudentsForDate(\Carbon\Carbon $date): \Illuminate\Support\Collection
    {
        // 1. Get IDs of students who marked attendance (Present, Late, or Excused)
        $presentStudentIds = \App\Models\Attendance::whereDate('attendance_date', $date)
            ->whereIn('status', ['present', 'late', 'excused'])
            ->pluck('student_id');

// 2. [NEW] Get IDs of Batches currently marked as "On Internship"
        $internshipBatchIds = \App\Models\Batch::where('is_on_internship', true)->pluck('id');

       // 3. Get Active Students who are:
        //    - NOT Present
        //    - NOT in an Internship Batch (Whole batch exclusion)
        return \App\Models\Student::where('status', 'active')
            ->whereNotIn('id', $presentStudentIds)
            ->whereNotIn('batch_id', $internshipBatchIds) // [CRITICAL CHANGE]
            ->with(['batch.course'])
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'batch' => $student->batch->name ?? 'N/A',
                    'course' => $student->batch->course->name ?? 'N/A',
                    'phone' => $student->student_mobile,
                    
                    // [ADDED] Father details
                    'father_name' => $student->father_name, 
                    'parent_phone' => $student->father_mobile
                ];
            });
    }
    /**
     * Get today's attendance summary for faculty dashboard
     */
    public function getTodaysAttendanceSummary(?int $facultyId = null): array
    {
        $today = Carbon::today();
        $query = Attendance::where('attendance_date', $today);

        if ($facultyId) {
            $query->where('faculty_id', $facultyId);
        }

        $todaysAttendance = $query->get();

        return [
            'date' => $today->format('Y-m-d'),
            'total_records' => $todaysAttendance->count(),
            'present_count' => $todaysAttendance->where('status', 'present')->count(),
            'absent_count' => $todaysAttendance->where('status', 'absent')->count(),
            'late_count' => $todaysAttendance->where('status', 'late')->count(),
            'excused_count' => $todaysAttendance->where('status', 'excused')->count(),
            'attendance_percentage' => $this->calculateAttendancePercentage($todaysAttendance)
        ];
    }

    /**
     * ✅ UPDATED: Process biometric attendance data with optimized lookup
     */
    public function processBiometricAttendance(array $biometricData): array
    {
        try {
            $startTime = microtime(true);
            
            // ✅ OPTIMIZED: Use biometric employee code for lookup
            $employeeCode = $biometricData['employee_code'];
            
            // Try biometric code first, then fallback to enrollment number
            $student = Student::where('biometric_employee_code', $employeeCode)->first();
            
            if (!$student) {
                // Fallback to enrollment number lookup with multiple patterns
                $student = $this->findStudentByEnrollmentPatterns($employeeCode);
                
                // If found via enrollment, auto-populate biometric code
                if ($student && empty($student->biometric_employee_code)) {
                    try {
                        $student->update(['biometric_employee_code' => $employeeCode]);
                        Log::info('Auto-populated biometric code during attendance processing', [
                            'student_id' => $student->id,
                            'enrollment_number' => $student->enrollment_number,
                            'biometric_code' => $employeeCode
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to auto-populate biometric code', [
                            'student_id' => $student->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            if (!$student) {
                $processingTime = round((microtime(true) - $startTime) * 1000, 2);
                
                Log::warning('Student not found in biometric attendance processing', [
                    'employee_code' => $employeeCode,
                    'processing_time_ms' => $processingTime
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Student not found',
                    'employee_code' => $employeeCode,
                    'processing_time_ms' => $processingTime
                ];
            }

            // Prepare attendance data
            $attendanceData = [
                'student_id' => $student->id,
                'batch_id' => $student->batch_id,
                'attendance_date' => Carbon::parse($biometricData['scan_datetime'])->format('Y-m-d'),
                'status' => 'present',
                'biometric_log_id' => $biometricData['log_id'] ?? null,
                'device_id' => $biometricData['device_id'] ?? null,
                'marked_at' => Carbon::parse($biometricData['scan_datetime'])
            ];

            // Mark attendance using trait method
            $attendance = $this->markAttendance($attendanceData);

            // Send notifications using trait method
            $this->sendAttendanceNotification($attendance, [
                'notify_parents' => config('attendance.biometric.notify_parents', false)
            ]);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Biometric attendance processed successfully', [
                'attendance_id' => $attendance->id,
                'student_name' => $student->name,
                'biometric_code' => $student->biometric_employee_code,
                'enrollment_number' => $student->enrollment_number,
                'processing_time_ms' => $processingTime
            ]);

            return [
                'success' => true,
                'attendance_id' => $attendance->id,
                'student_name' => $student->name,
                'student_id' => $student->id,
                'biometric_code' => $student->biometric_employee_code,
                'enrollment_number' => $student->enrollment_number,
                'status' => $attendance->status,
                'processing_time_ms' => $processingTime
            ];

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Biometric attendance processing failed', [
                'biometric_data' => $biometricData,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ];
        }
    }

    /**
     * ✅ NEW: Find student by enrollment number patterns (fallback method)
     */
    private function findStudentByEnrollmentPatterns(string $employeeCode): ?Student
    {
        // Try different enrollment number patterns
        $patterns = [
            $employeeCode,                    // Exact match
            'UV-' . $employeeCode,           // UV prefix
            'UVCHM-' . $employeeCode,        // UVCHM prefix
            'STD-' . $employeeCode,          // STD prefix
            'ENR-' . $employeeCode           // ENR prefix
        ];

        foreach ($patterns as $pattern) {
            $student = Student::where('enrollment_number', $pattern)->first();
            if ($student) {
                Log::debug('Student found via enrollment pattern', [
                    'employee_code' => $employeeCode,
                    'pattern_used' => $pattern,
                    'enrollment_number' => $student->enrollment_number
                ]);
                return $student;
            }
        }

        // Try partial match as last resort
        $student = Student::where('enrollment_number', 'LIKE', '%' . $employeeCode)->first();
        if ($student) {
            Log::debug('Student found via partial match', [
                'employee_code' => $employeeCode,
                'enrollment_number' => $student->enrollment_number
            ]);
        }

        return $student;
    }

    /**
     * ✅ NEW: Get biometric integration statistics
     */
    public function getBiometricIntegrationStats(): array
    {
        $totalStudents = Student::count();
        $studentsWithBiometric = Student::whereNotNull('biometric_employee_code')->count();
        $studentsWithoutBiometric = $totalStudents - $studentsWithBiometric;
        
        $recentBiometricAttendance = Attendance::whereNotNull('device_id')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        return [
            'total_students' => $totalStudents,
            'students_with_biometric' => $studentsWithBiometric,
            'students_without_biometric' => $studentsWithoutBiometric,
            'mapping_percentage' => $totalStudents > 0 ? round(($studentsWithBiometric / $totalStudents) * 100, 2) : 0,
            'recent_biometric_attendance_count' => $recentBiometricAttendance,
            'integration_health' => $this->calculateIntegrationHealth($studentsWithBiometric, $totalStudents, $recentBiometricAttendance)
        ];
    }

    /**
     * ✅ NEW: Calculate integration health score
     */
    private function calculateIntegrationHealth(int $mappedStudents, int $totalStudents, int $recentActivity): string
    {
        if ($totalStudents === 0) return 'no_data';
        
        $mappingPercentage = ($mappedStudents / $totalStudents) * 100;
        
        if ($mappingPercentage >= 80 && $recentActivity > 0) {
            return 'excellent';
        } elseif ($mappingPercentage >= 60 && $recentActivity > 0) {
            return 'good';
        } elseif ($mappingPercentage >= 40) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * ✅ NEW: Get students without biometric codes for admin action
     */
    public function getStudentsWithoutBiometricCodes(): Collection
    {
        return Student::whereNull('biometric_employee_code')
            ->with(['batch.course'])
            ->where('status', 'active')
            ->orderBy('enrollment_number')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'batch_name' => $student->batch->name ?? 'No Batch',
                    'course_name' => $student->batch->course->name ?? 'No Course',
                    'suggested_biometric_code' => $this->generateBiometricCodeFromEnrollment($student->enrollment_number)
                ];
            });
    }

    /**
     * ✅ NEW: Generate biometric code from enrollment number
     */
    private function generateBiometricCodeFromEnrollment(string $enrollmentNumber): string
    {
        // Remove common prefixes and extract numbers
        $code = preg_replace('/^(UVCHM-|UV-|ENR-|STD-)/i', '', $enrollmentNumber);
        
        // Remove any non-alphanumeric characters except hyphens
        $code = preg_replace('/[^a-zA-Z0-9-]/', '', $code);
        
        return $code;
    }

    /**
     * ✅ NEW: Bulk update biometric codes
     */
    public function bulkUpdateBiometricCodes(array $mappings): array
    {
        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => []
        ];

        foreach ($mappings as $mapping) {
            try {
                $student = Student::find($mapping['student_id']);
                
                if (!$student) {
                    $results['error_count']++;
                    $results['errors'][] = "Student not found: ID {$mapping['student_id']}";
                    continue;
                }

                // Validate biometric code uniqueness
                $existingStudent = Student::where('biometric_employee_code', $mapping['biometric_code'])
                    ->where('id', '!=', $student->id)
                    ->first();

                if ($existingStudent) {
                    $results['error_count']++;
                    $results['errors'][] = "Biometric code '{$mapping['biometric_code']}' already used by {$existingStudent->name}";
                    continue;
                }

                // Update student
                $student->update(['biometric_employee_code' => $mapping['biometric_code']]);
                $results['success_count']++;

                Log::info('Bulk updated biometric code', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'biometric_code' => $mapping['biometric_code']
                ]);

            } catch (\Exception $e) {
                $results['error_count']++;
                $results['errors'][] = "Error updating student ID {$mapping['student_id']}: " . $e->getMessage();
                
                Log::error('Bulk biometric code update failed', [
                    'student_id' => $mapping['student_id'],
                    'biometric_code' => $mapping['biometric_code'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * ✅ NEW: Auto-generate biometric codes for all students without them
     */
    public function autoGenerateBiometricCodes(): array
    {
        $studentsWithoutCodes = Student::whereNull('biometric_employee_code')
            ->where('status', 'active')
            ->get();
        
        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => []
        ];

        foreach ($studentsWithoutCodes as $student) {
            try {
                $generatedCode = $this->generateBiometricCodeFromEnrollment($student->enrollment_number);
                
                // Ensure uniqueness
                $counter = 1;
                $originalCode = $generatedCode;
                
                while (Student::where('biometric_employee_code', $generatedCode)
                          ->where('id', '!=', $student->id)
                          ->exists()) {
                    $generatedCode = $originalCode . '-' . $counter;
                    $counter++;
                }
                
                $student->update(['biometric_employee_code' => $generatedCode]);
                $results['success_count']++;
                
                Log::info('Auto-generated biometric code', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'generated_code' => $generatedCode
                ]);
                
            } catch (\Exception $e) {
                $results['error_count']++;
                $results['errors'][] = "Error generating code for {$student->name}: " . $e->getMessage();
                
                Log::error('Auto-generation failed', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Get low attendance students for intervention
     */
    public function getLowAttendanceStudents(float $threshold = 75.0, array $filters = []): Collection
    {
        $students = Student::with(['batch'])->where('status', 'active');

        if (isset($filters['batch_id'])) {
            $students->where('batch_id', $filters['batch_id']);
        }

        $students = $students->get();
        $lowAttendanceStudents = collect();

        foreach ($students as $student) {
            $stats = $this->calculateStudentStats($student->id, $filters);
            
            if ($stats['attendance_percentage'] < $threshold) {
                $lowAttendanceStudents->push([
                    'student' => $student,
                    'stats' => $stats,
                    'attendance_percentage' => $stats['attendance_percentage'],
                    'consecutive_absents' => $stats['consecutive_absents'],
                    'risk_level' => $stats['risk_level']
                ]);
            }
        }

        return $lowAttendanceStudents->sortBy('attendance_percentage');
    }

    /**
     * Send bulk low attendance warnings
     */
    public function sendLowAttendanceWarnings(array $filters = []): array
    {
        $threshold = config('attendance.minimum_percentage', 75);
        $lowAttendanceStudents = $this->getLowAttendanceStudents($threshold, $filters);

        $results = [
            'total_students_checked' => $lowAttendanceStudents->count(),
            'warnings_sent' => 0,
            'failed_notifications' => 0,
            'details' => []
        ];

        foreach ($lowAttendanceStudents as $studentData) {
            $student = $studentData['student'];
            $stats = $studentData['stats'];

            try {
                // Send warning using trait method
                $warningResult = $this->sendLowAttendanceWarning($student, $stats);
                
                if ($warningResult['success']) {
                    $results['warnings_sent']++;
                } else {
                    $results['failed_notifications']++;
                }

                $results['details'][] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'attendance_percentage' => $stats['attendance_percentage'],
                    'notification_result' => $warningResult
                ];

            } catch (\Exception $e) {
                $results['failed_notifications']++;
                $results['details'][] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Low attendance warnings processed', [
            'total_checked' => $results['total_students_checked'],
            'warnings_sent' => $results['warnings_sent'],
            'failed' => $results['failed_notifications']
        ]);

        return $results;
    }

    /**
     * Update attendance cache for student
     */
    public function updateStudentCache(int $studentId, array $options = []): void
    {
        try {
            $stats = $this->calculateStudentStats($studentId, $options);
            
            AttendanceCache::updateOrCreate([
                'student_id' => $studentId,
                'cache_type' => $options['cache_type'] ?? 'overall',
                'period_type' => $options['period_type'] ?? 'academic_year',
                'period_value' => $options['period_value'] ?? date('Y'),
                'is_current' => true
            ], [
                'calculation_date' => now()->toDateString(),
                'total_classes' => $stats['total_classes'],
                'present_classes' => $stats['present_count'],
                'absent_classes' => $stats['absent_count'],
                'late_classes' => $stats['late_count'],
                'excused_classes' => $stats['excused_count'],
                'attendance_percentage' => $stats['attendance_percentage'],
                'punctuality_percentage' => $stats['punctuality_percentage'],
                'trend_direction' => $this->determineTrendDirection($stats),
                'consecutive_absents' => $stats['consecutive_absents'],
                'analytics_data' => [
                    'monthly_trends' => $stats['monthly_trends'],
                    'weekly_patterns' => $stats['weekly_patterns'],
                    'performance_level' => $stats['performance_level'],
                    'risk_level' => $stats['risk_level']
                ],
                'expires_at' => now()->addHours(6)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update attendance cache', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get attendance summary for multiple students
     */
    public function getBulkStudentSummary(array $studentIds, array $filters = []): array
    {
        $summaries = [];

        foreach ($studentIds as $studentId) {
            try {
                $student = Student::find($studentId);
                if (!$student) continue;

                $stats = $this->calculateStudentStats($studentId, $filters);
                
                $summaries[] = [
                    'student_id' => $studentId,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'biometric_code' => $student->biometric_employee_code,
                    'batch_name' => $student->batch->name ?? 'Unknown',
                    'statistics' => $stats
                ];

            } catch (\Exception $e) {
                Log::error('Failed to get student summary', [
                    'student_id' => $studentId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $summaries;
    }

    /**
     * Process attendance correction/update
     */
    public function processAttendanceCorrection(int $attendanceId, array $correctionData): array
    {
        try {
            DB::beginTransaction();

            $attendance = Attendance::findOrFail($attendanceId);
            $originalData = $attendance->toArray();

            // Validate correction permissions using trait
            if (!$this->validateAttendancePermissions($correctionData)) {
                throw new \Exception('Insufficient permissions for attendance correction');
            }

            // Validate status transition using trait
            $transitionErrors = $this->validateStatusTransition(
                $attendance->status,
                $correctionData['status'],
                ['attendance_record' => $attendance, 'admin_override' => $correctionData['admin_override'] ?? false]
            );

            if (!empty($transitionErrors)) {
                throw new \Exception('Invalid status transition: ' . implode(', ', $transitionErrors));
            }

            // Update attendance using trait method
            $updatedAttendance = $this->updateAttendance($attendanceId, $correctionData);

            // Update cache
            $this->updateStudentCache($attendance->student_id);

            // Send notification if status changed significantly
            if ($originalData['status'] !== $updatedAttendance->status) {
                $this->sendAttendanceNotification($updatedAttendance, [
                    'notify_parents' => $correctionData['notify_parents'] ?? false,
                    'notify_faculty' => true
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'attendance' => $updatedAttendance,
                'changes' => $updatedAttendance->getChanges(),
                'original_status' => $originalData['status']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Private helper methods
     */
    private function getEmptyStats(): array
    {
        return [
            'total_classes' => 0,
            'present_count' => 0,
            'absent_count' => 0,
            'late_count' => 0,
            'excused_count' => 0,
            'attendance_percentage' => 0,
            'punctuality_percentage' => 0,
            'consecutive_absents' => 0,
            'attendance_streak' => 0,
            'monthly_trends' => [],
            'weekly_patterns' => [],
            'late_patterns' => [],
            'performance_level' => 'no_data',
            'risk_level' => 'low'
        ];
    }

    private function determineTrendDirection(array $stats): string
    {
        // Simple trend determination based on recent performance
        // This could be enhanced with more sophisticated trend analysis
        $percentage = $stats['attendance_percentage'];
        
        if ($percentage >= 85) {
            return 'improving';
        } elseif ($percentage < 70) {
            return 'declining';
        } else {
            'stable';
        }
    }
}