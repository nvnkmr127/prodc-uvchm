<?php

namespace App\Traits\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Batch;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait GeneratesReports
{
    /**
     * Generate comprehensive student attendance report
     */
    public function generateStudentReport(int $studentId, array $filters = []): array
    {
        $student = Student::with(['batch', 'user'])->findOrFail($studentId);

        // Get attendance data for the period
        $query = Attendance::where('student_id', $studentId);
        $this->applyDateFilters($query, $filters);
        $attendances = $query->with(['subject', 'faculty'])->get();

        // Calculate basic statistics
        $stats = $this->calculateStudentStats($attendances);

        // Generate monthly breakdown
        $monthlyData = $this->generateMonthlyBreakdown($attendances);

        // Generate subject-wise analysis
        $subjectAnalysis = $this->generateSubjectWiseAnalysis($attendances);

        // Calculate attendance patterns
        $patterns = $this->analyzeAttendancePatterns($attendances);

        // Generate recommendations
        $recommendations = $this->generateStudentRecommendations($stats, $patterns);

        return [
            'student_info' => [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'batch_name' => $student->batch->name ?? 'N/A',
                'course_name' => $student->batch->course->name ?? 'N/A',
            ],
            'report_period' => [
                'from' => $filters['date_from'] ?? $attendances->min('attendance_date'),
                'to' => $filters['date_to'] ?? $attendances->max('attendance_date'),
                'total_days' => $attendances->count(),
            ],
            'statistics' => $stats,
            'monthly_breakdown' => $monthlyData,
            'subject_analysis' => $subjectAnalysis,
            'attendance_patterns' => $patterns,
            'recommendations' => $recommendations,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate batch attendance report
     */
    public function generateBatchReport(int $batchId, array $filters = []): array
    {
        $batch = Batch::with(['course', 'students'])->findOrFail($batchId);

        // Get attendance data for all students in the batch
        $query = Attendance::where('batch_id', $batchId);
        $this->applyDateFilters($query, $filters);
        $attendances = $query->with(['student', 'subject', 'faculty'])->get();

        // Calculate batch statistics
        $stats = $this->calculateBatchStats($attendances, $batch);

        // Generate student-wise summary
        $studentSummary = $this->generateStudentWiseSummary($attendances);

        // Generate daily attendance trends
        $dailyTrends = $this->generateDailyTrends($attendances);

        // Identify at-risk students
        $atRiskStudents = $this->identifyAtRiskStudents($attendances);

        // Generate performance insights
        $insights = $this->generateBatchInsights($stats, $studentSummary);

        return [
            'batch_info' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'course_name' => $batch->course->name ?? 'N/A',
                'total_students' => $batch->students->count(),
                'active_students' => $batch->students->where('is_active', true)->count(),
            ],
            'report_period' => [
                'from' => $filters['date_from'] ?? $attendances->min('attendance_date'),
                'to' => $filters['date_to'] ?? $attendances->max('attendance_date'),
                'total_records' => $attendances->count(),
            ],
            'statistics' => $stats,
            'student_summary' => $studentSummary,
            'daily_trends' => $dailyTrends,
            'at_risk_students' => $atRiskStudents,
            'insights' => $insights,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate custom report based on configuration
     */
    public function generateCustomReport(array $config): array
    {
        $query = Attendance::query();

        // Apply filters from configuration
        $this->applyCustomFilters($query, $config['filters'] ?? []);

        // Load relationships based on config
        $relationships = $config['include'] ?? ['student', 'batch', 'subject', 'faculty'];
        $attendances = $query->with($relationships)->get();

        // Generate metrics based on configuration
        $metrics = [];
        foreach ($config['metrics'] ?? [] as $metric) {
            $metrics[$metric] = $this->calculateCustomMetric($metric, $attendances, $config);
        }

        // Apply grouping if specified
        $groupedData = [];
        if (isset($config['group_by'])) {
            $groupedData = $this->groupReportData($attendances, $config['group_by']);
        }

        // Generate visualizations data
        $charts = [];
        foreach ($config['charts'] ?? [] as $chartConfig) {
            $charts[] = $this->generateChartData($attendances, $chartConfig);
        }

        return [
            'config' => $config,
            'total_records' => $attendances->count(),
            'metrics' => $metrics,
            'grouped_data' => $groupedData,
            'charts' => $charts,
            'raw_data' => $config['include_raw_data'] ?? false ? $attendances->toArray() : null,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate faculty attendance summary report
     */
    public function generateFacultyReport(int $facultyId, array $filters = []): array
    {
        $faculty = User::findOrFail($facultyId);

        // Get attendance records marked by this faculty
        $query = Attendance::where('faculty_id', $facultyId);
        $this->applyDateFilters($query, $filters);
        $attendances = $query->with(['student', 'batch', 'subject'])->get();

        // Get assigned batches and subjects
        $assignedBatches = $faculty->assignedBatches ?? collect();
        $assignedSubjects = $faculty->subjects ?? collect();

        // Calculate faculty statistics
        $stats = $this->calculateFacultyStats($attendances, $assignedBatches);

        // Generate batch-wise breakdown
        $batchBreakdown = $this->generateFacultyBatchBreakdown($attendances);

        // Calculate marking efficiency
        $efficiency = $this->calculateMarkingEfficiency($facultyId, $filters);

        return [
            'faculty_info' => [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'email' => $faculty->email,
                'assigned_batches' => $assignedBatches->count(),
                'assigned_subjects' => $assignedSubjects->count(),
            ],
            'report_period' => [
                'from' => $filters['date_from'] ?? $attendances->min('attendance_date'),
                'to' => $filters['date_to'] ?? $attendances->max('attendance_date'),
                'records_marked' => $attendances->count(),
            ],
            'statistics' => $stats,
            'batch_breakdown' => $batchBreakdown,
            'marking_efficiency' => $efficiency,
            'assigned_batches' => $assignedBatches->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'student_count' => $batch->students()->count(),
                ];
            }),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate comparative analysis report
     */
    public function generateComparativeReport(array $entities, string $type, array $filters = []): array
    {
        $comparisons = [];

        foreach ($entities as $entityId) {
            switch ($type) {
                case 'batch':
                    $data = $this->generateBatchReport($entityId, $filters);
                    $comparisons[] = [
                        'entity_id' => $entityId,
                        'entity_name' => $data['batch_info']['name'],
                        'statistics' => $data['statistics'],
                    ];
                    break;

                case 'student':
                    $data = $this->generateStudentReport($entityId, $filters);
                    $comparisons[] = [
                        'entity_id' => $entityId,
                        'entity_name' => $data['student_info']['name'],
                        'statistics' => $data['statistics'],
                    ];
                    break;

                case 'faculty':
                    $data = $this->generateFacultyReport($entityId, $filters);
                    $comparisons[] = [
                        'entity_id' => $entityId,
                        'entity_name' => $data['faculty_info']['name'],
                        'statistics' => $data['statistics'],
                    ];
                    break;
            }
        }

        return [
            'type' => $type,
            'comparisons' => $comparisons,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate attendance insights for batch analysis
     */
    private function generateBatchInsights(array $stats, array $studentSummary): array
    {
        $insights = [];

        // Overall performance insight
        if ($stats['batch_attendance_rate'] >= 90) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Excellent Batch Performance',
                'description' => 'The batch maintains excellent attendance rates above 90%',
            ];
        } elseif ($stats['batch_attendance_rate'] < 75) {
            $insights[] = [
                'type' => 'concern',
                'title' => 'Batch Attendance Below Threshold',
                'description' => 'Batch attendance is below the required 75% threshold',
            ];
        }

        // At-risk students insight
        $atRiskCount = collect($studentSummary)->where('risk_level', 'high')->count() +
            collect($studentSummary)->where('risk_level', 'critical')->count();

        if ($atRiskCount > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Students Requiring Attention',
                'description' => "{$atRiskCount} students are at high or critical risk based on attendance patterns",
            ];
        }

        return $insights;
    }

    /**
     * Generate time-based attendance analysis
     */
    public function generateTimeBasedReport(array $filters = []): array
    {
        $query = Attendance::query();
        $this->applyDateFilters($query, $filters);
        $attendances = $query->with(['student', 'batch', 'subject'])->get();

        // Hourly analysis (if time data is available)
        $hourlyData = $this->generateHourlyAnalysis($attendances);

        // Day of week analysis
        $weeklyData = $this->generateWeeklyAnalysis($attendances);

        // Monthly progression
        $monthlyProgression = $this->generateMonthlyProgression($attendances);

        // Peak and low periods
        $peakAnalysis = $this->identifyPeakAndLowPeriods($attendances);

        return [
            'report_type' => 'time_based_analysis',
            'period' => [
                'from' => $filters['date_from'] ?? null,
                'to' => $filters['date_to'] ?? null,
            ],
            'hourly_analysis' => $hourlyData,
            'weekly_analysis' => $weeklyData,
            'monthly_progression' => $monthlyProgression,
            'peak_analysis' => $peakAnalysis,
            'recommendations' => $this->generateTimeBasedRecommendations($hourlyData, $weeklyData),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate attendance intervention report
     */
    public function generateInterventionReport(array $filters = []): array
    {
        // Get students who need intervention
        $atRiskStudents = $this->identifyStudentsForIntervention($filters);

        // Generate intervention strategies
        $interventionStrategies = $this->generateInterventionStrategies($atRiskStudents);

        // Track intervention effectiveness
        $effectivenessData = $this->analyzeInterventionEffectiveness($filters);

        return [
            'report_type' => 'intervention_analysis',
            'at_risk_students' => $atRiskStudents,
            'intervention_strategies' => $interventionStrategies,
            'effectiveness_data' => $effectivenessData,
            'success_metrics' => $this->calculateInterventionSuccessMetrics($atRiskStudents),
            'recommended_actions' => $this->generateInterventionRecommendations($atRiskStudents),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate attendance compliance report
     */
    public function generateComplianceReport(array $filters = []): array
    {
        $query = Attendance::query();
        $this->applyDateFilters($query, $filters);
        $attendances = $query->with(['student.batch'])->get();

        $minAttendancePercentage = config('attendance.minimum_percentage', 75);

        // Calculate compliance metrics
        $complianceData = $this->calculateComplianceMetrics($attendances, $minAttendancePercentage);

        // Identify non-compliant students
        $nonCompliantStudents = $this->identifyNonCompliantStudents($attendances, $minAttendancePercentage);

        // Generate compliance trends
        $complianceTrends = $this->generateComplianceTrends($attendances, $filters);

        return [
            'report_type' => 'compliance_analysis',
            'compliance_threshold' => $minAttendancePercentage,
            'overall_compliance' => $complianceData,
            'non_compliant_students' => $nonCompliantStudents,
            'compliance_trends' => $complianceTrends,
            'improvement_recommendations' => $this->generateComplianceRecommendations($complianceData),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Private helper methods for additional functionality
     */
    private function generateHourlyAnalysis(Collection $attendances): array
    {
        // Group by hour of day when attendance was marked
        return $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->marked_at)->hour;
        })->map(function ($hourAttendances, $hour) {
            return [
                'hour' => $hour,
                'hour_display' => Carbon::createFromTime($hour)->format('H:i'),
                'total_records' => $hourAttendances->count(),
                'status_breakdown' => $hourAttendances->groupBy('status')->map(fn ($group) => $group->count())->toArray(),
            ];
        })->sortBy('hour')->values()->toArray();
    }

    private function generateWeeklyAnalysis(Collection $attendances): array
    {
        return $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->attendance_date)->dayOfWeek;
        })->map(function ($dayAttendances, $dayOfWeek) {
            $total = $dayAttendances->count();
            $present = $dayAttendances->whereIn('status', ['present', 'late', 'excused'])->count();

            return [
                'day_of_week' => $dayOfWeek,
                'day_name' => Carbon::create()->dayOfWeek($dayOfWeek)->format('l'),
                'total_classes' => $total,
                'present_count' => $present,
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                'status_breakdown' => $dayAttendances->groupBy('status')->map(fn ($group) => $group->count())->toArray(),
            ];
        })->sortBy('day_of_week')->values()->toArray();
    }

    private function generateMonthlyProgression(Collection $attendances): array
    {
        return $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->attendance_date)->format('Y-m');
        })->map(function ($monthAttendances, $month) use ($attendances) {
            $total = $monthAttendances->count();
            $present = $monthAttendances->whereIn('status', ['present', 'late', 'excused'])->count();

            return [
                'month' => $month,
                'month_name' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                'total_classes' => $total,
                'present_count' => $present,
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                'improvement_from_previous' => $this->calculateMonthlyImprovement($month, $attendances),
            ];
        })->sortBy('month')->values()->toArray();
    }

    private function identifyPeakAndLowPeriods(Collection $attendances): array
    {
        $dailyStats = $attendances->groupBy('attendance_date')
            ->map(function ($dayAttendances, $date) {
                $total = $dayAttendances->count();
                $present = $dayAttendances->whereIn('status', ['present', 'late', 'excused'])->count();

                return [
                    'date' => $date,
                    'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                ];
            });

        $sortedStats = $dailyStats->sortBy('attendance_percentage');

        return [
            'lowest_attendance_days' => $sortedStats->take(5)->values()->toArray(),
            'highest_attendance_days' => $sortedStats->sortByDesc('attendance_percentage')->take(5)->values()->toArray(),
            'average_attendance' => round($dailyStats->avg('attendance_percentage'), 2),
        ];
    }

    private function generateTimeBasedRecommendations(array $hourlyData, array $weeklyData): array
    {
        $recommendations = [];

        // Find the day with lowest attendance
        $lowestDay = collect($weeklyData)->sortBy('attendance_percentage')->first();
        if ($lowestDay && $lowestDay['attendance_percentage'] < 80) {
            $recommendations[] = [
                'type' => 'schedule_optimization',
                'title' => 'Improve '.$lowestDay['day_name'].' Attendance',
                'description' => "Attendance on {$lowestDay['day_name']} is significantly lower at {$lowestDay['attendance_percentage']}%",
                'suggested_actions' => [
                    'Review class schedules for '.$lowestDay['day_name'],
                    'Consider incentives for '.$lowestDay['day_name'].' classes',
                    'Investigate external factors affecting '.$lowestDay['day_name'],
                ],
            ];
        }

        return $recommendations;
    }

    private function identifyStudentsForIntervention(array $filters): array
    {
        $threshold = config('attendance.intervention_threshold', 70);
        $query = Attendance::query();
        $this->applyDateFilters($query, $filters);

        $studentStats = $query->get()->groupBy('student_id')
            ->map(function ($studentAttendances) {
                $student = $studentAttendances->first()->student;
                $stats = $this->calculateStudentStats($studentAttendances);

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'batch_name' => $student->batch->name ?? 'Unknown',
                    'statistics' => $stats,
                    'intervention_priority' => $this->calculateInterventionPriority($stats),
                ];
            })
            ->filter(function ($studentData) use ($threshold) {
                return $studentData['statistics']['attendance_percentage'] < $threshold;
            })
            ->sortBy('statistics.attendance_percentage')
            ->values()
            ->toArray();

        return $studentStats;
    }

    private function generateInterventionStrategies(array $atRiskStudents): array
    {
        $strategies = [];

        foreach ($atRiskStudents as $student) {
            $stats = $student['statistics'];
            $strategies[] = [
                'student_id' => $student['student_id'],
                'student_name' => $student['student_name'],
                'recommended_strategy' => $this->determineInterventionStrategy($stats),
                'urgency_level' => $student['intervention_priority'],
                'specific_actions' => $this->generateSpecificActions($stats),
            ];
        }

        return $strategies;
    }

    private function calculateInterventionPriority(array $stats): string
    {
        $percentage = $stats['attendance_percentage'];
        $consecutiveAbsents = $stats['consecutive_absents'] ?? 0;

        if ($percentage < 50 || $consecutiveAbsents >= 7) {
            return 'critical';
        } elseif ($percentage < 65 || $consecutiveAbsents >= 5) {
            return 'high';
        } elseif ($percentage < 75 || $consecutiveAbsents >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function determineInterventionStrategy(array $stats): string
    {
        $percentage = $stats['attendance_percentage'];
        $lateCount = $stats['late_count'] ?? 0;
        $totalClasses = $stats['total_classes'] ?? 1;

        if ($percentage < 50) {
            return 'intensive_counseling';
        } elseif ($lateCount / $totalClasses > 0.3) {
            return 'punctuality_improvement';
        } elseif ($percentage < 70) {
            return 'regular_monitoring';
        } else {
            return 'standard_support';
        }
    }

    private function generateSpecificActions(array $stats): array
    {
        $actions = [];
        $percentage = $stats['attendance_percentage'];

        if ($percentage < 60) {
            $actions[] = 'Schedule immediate parent meeting';
            $actions[] = 'Assess academic support needs';
            $actions[] = 'Consider flexible attendance options';
        } elseif ($percentage < 75) {
            $actions[] = 'Send attendance warning notice';
            $actions[] = 'Schedule counselor session';
            $actions[] = 'Monitor weekly progress';
        }

        if (($stats['late_count'] ?? 0) > 5) {
            $actions[] = 'Address punctuality issues';
            $actions[] = 'Review transportation arrangements';
        }

        return $actions;
    }

    private function analyzeInterventionEffectiveness(array $filters): array
    {
        // This would analyze how effective past interventions have been
        // For now, return placeholder data
        return [
            'total_interventions' => 15,
            'successful_interventions' => 12,
            'success_rate' => 80.0,
            'average_improvement' => 15.5, // percentage points
        ];
    }

    private function calculateInterventionSuccessMetrics(array $atRiskStudents): array
    {
        return [
            'students_identified' => count($atRiskStudents),
            'critical_cases' => collect($atRiskStudents)->where('intervention_priority', 'critical')->count(),
            'high_priority_cases' => collect($atRiskStudents)->where('intervention_priority', 'high')->count(),
            'average_attendance_deficit' => $this->calculateAverageDeficit($atRiskStudents),
        ];
    }

    private function generateInterventionRecommendations(array $atRiskStudents): array
    {
        $recommendations = [];
        $criticalCount = collect($atRiskStudents)->where('intervention_priority', 'critical')->count();

        if ($criticalCount > 5) {
            $recommendations[] = [
                'type' => 'system_wide',
                'title' => 'Review Attendance Policies',
                'description' => 'High number of critical cases suggests need for policy review',
            ];
        }

        return $recommendations;
    }

    private function calculateComplianceMetrics(Collection $attendances, float $threshold): array
    {
        $studentStats = $attendances->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $this->calculateAttendancePercentage($studentAttendances);
            });

        $compliantStudents = $studentStats->filter(function ($percentage) use ($threshold) {
            return $percentage >= $threshold;
        });

        return [
            'total_students' => $studentStats->count(),
            'compliant_students' => $compliantStudents->count(),
            'compliance_rate' => $studentStats->count() > 0 ?
                round(($compliantStudents->count() / $studentStats->count()) * 100, 2) : 0,
            'average_attendance' => round($studentStats->avg(), 2),
            'threshold' => $threshold,
        ];
    }

    private function identifyNonCompliantStudents(Collection $attendances, float $threshold): array
    {
        return $attendances->groupBy('student_id')
            ->map(function ($studentAttendances) use ($threshold) {
                $student = $studentAttendances->first()->student;
                $percentage = $this->calculateAttendancePercentage($studentAttendances);

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'attendance_percentage' => $percentage,
                    'deficit' => round($threshold - $percentage, 2),
                ];
            })
            ->filter(function ($studentData) use ($threshold) {
                return $studentData['attendance_percentage'] < $threshold;
            })
            ->sortBy('attendance_percentage')
            ->values()
            ->toArray();
    }

    private function generateComplianceTrends(Collection $attendances, array $filters): array
    {
        // Generate month-by-month compliance trends
        $monthlyCompliance = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->attendance_date)->format('Y-m');
        })->map(function ($monthAttendances, $month) {
            $threshold = config('attendance.minimum_percentage', 75);

            return $this->calculateComplianceMetrics($monthAttendances, $threshold);
        });

        return $monthlyCompliance->map(function ($compliance, $month) {
            return [
                'month' => $month,
                'month_name' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                'compliance_rate' => $compliance['compliance_rate'],
                'average_attendance' => $compliance['average_attendance'],
            ];
        })->sortBy('month')->values()->toArray();
    }

    private function generateComplianceRecommendations(array $complianceData): array
    {
        $recommendations = [];
        $rate = $complianceData['compliance_rate'];

        if ($rate < 70) {
            $recommendations[] = [
                'priority' => 'critical',
                'title' => 'Immediate Action Required',
                'description' => 'Compliance rate is critically low',
                'actions' => [
                    'Review attendance policies',
                    'Implement intensive intervention program',
                    'Conduct system-wide attendance review',
                ],
            ];
        } elseif ($rate < 85) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Improve Compliance Rate',
                'description' => 'Compliance rate needs improvement',
                'actions' => [
                    'Enhance student support services',
                    'Increase parent engagement',
                    'Review class scheduling',
                ],
            ];
        }

        return $recommendations;
    }

    private function calculateMonthlyImprovement(string $month, Collection $attendances): ?float
    {
        // Calculate improvement from previous month
        $currentMonth = Carbon::createFromFormat('Y-m', $month);
        $previousMonth = $currentMonth->copy()->subMonth()->format('Y-m');

        $currentAttendances = $attendances->filter(function ($attendance) use ($month) {
            return Carbon::parse($attendance->attendance_date)->format('Y-m') === $month;
        });

        $previousAttendances = $attendances->filter(function ($attendance) use ($previousMonth) {
            return Carbon::parse($attendance->attendance_date)->format('Y-m') === $previousMonth;
        });

        if ($previousAttendances->isEmpty()) {
            return null;
        }

        $currentRate = $this->calculateAttendancePercentage($currentAttendances);
        $previousRate = $this->calculateAttendancePercentage($previousAttendances);

        return round($currentRate - $previousRate, 2);
    }

    private function calculateAverageDeficit(array $atRiskStudents): float
    {
        $threshold = config('attendance.minimum_percentage', 75);
        $deficits = collect($atRiskStudents)->map(function ($student) use ($threshold) {
            return $threshold - $student['statistics']['attendance_percentage'];
        });

        return round($deficits->avg(), 2);
    }
}
