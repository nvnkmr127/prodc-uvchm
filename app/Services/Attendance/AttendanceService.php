<?php

namespace App\Services\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Batch;
use App\Models\Student;
use App\Services\BiometricMappingService;
use App\Traits\Attendance\CalculatesMetrics;
use App\Traits\Attendance\HandlesNotifications;
use App\Traits\Attendance\ManagesAttendance;
use App\Traits\Attendance\ValidatesData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    use CalculatesMetrics, HandlesNotifications, ManagesAttendance, ValidatesData;

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
                'consecutive_absents' => $consecutiveAbsents,
            ]),
        ];

        return $stats;
    }

    /**
     * Get absent students for a specific date
     */
    public function getAbsentStudentsForDate(Carbon $date): Collection
    {
        // 1. Get IDs of students who marked attendance (Present, Late, or Excused)
        $presentStudentIds = \App\Models\Attendance::whereDate('attendance_date', $date)
            ->whereIn('status', ['present', 'late', 'excused'])
            ->pluck('student_id');

        // 2. [NEW] Get IDs of Batches currently marked as "On Internship"

        // 3. Get Active Students who are:
        //    - NOT Present
        //    - NOT in an Internship Batch (Whole batch exclusion)
        return Student::where('status', 'active')
            ->has('attendances') // [NEW] Ensure at least one punch exists
            ->whereNotIn('id', $presentStudentIds)
            ->whereHas('batch', function ($q) use ($date) {
                // Keep students if: (Not Flagged Internship OR Null) AND (Internship Start Date is Null OR Future)
                $q->where(function ($sq) {
                    $sq->where('is_on_internship', '!=', 1)
                        ->orWhereNull('is_on_internship');
                })->where(function ($sq) use ($date) {
                    $sq->whereNull('internship_start_date')
                        ->orWhere('internship_start_date', '>', $date);
                });
            })
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
                    'parent_phone' => $student->father_mobile,
                ];
            });
    }

    /**
     * ✅ NEW: Bulk update biometric codes
     */
    public function bulkUpdateBiometricCodes(array $mappings): array
    {
        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
        ];

        foreach ($mappings as $mapping) {
            try {
                $student = Student::find($mapping['student_id']);

                if (! $student) {
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
                    'biometric_code' => $mapping['biometric_code'],
                ]);

            } catch (\Exception $e) {
                $results['error_count']++;
                $results['errors'][] = "Error updating student ID {$mapping['student_id']}: ".$e->getMessage();

                Log::error('Bulk biometric code update failed', [
                    'student_id' => $mapping['student_id'],
                    'biometric_code' => $mapping['biometric_code'],
                    'error' => $e->getMessage(),
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
        $biometricService = app(BiometricMappingService::class);

        return $biometricService->autoGenerateAllCodes();
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
                    'risk_level' => $stats['risk_level'],
                ]);
            }
        }

        return $lowAttendanceStudents->sortBy('attendance_percentage');
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
            return 'stable';
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
            'risk_level' => 'low',
        ];
    }
}
