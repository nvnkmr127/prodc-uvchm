<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentAttendanceSummaryExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Holiday;
use App\Models\Student;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $courseId = $request->input('course_id');
        $batchId = $request->input('batch_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sortBy = $request->input('sort_by', 'attendance_percentage');
        $sortOrder = $request->input('sort_order', 'desc');

        $courses = Course::all();

        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();

        $batches = Batch::withoutGlobalScope('academic_year')
            ->when($courseId, function ($query) use ($courseId) {
                return $query->where('course_id', $courseId);
            })
            ->when($currentYear, function ($query) {
                // If no course/batch selected, maybe still show current year batches by default?
                // But generally, for reports, we might want to see all or specific.
                // Let's at least allow all batches to show up if sorted by date.
            })->get();

        // Initial Load without filters (Default to today to satisfy user request)
        if (! $request->ajax() && ! $request->has('start_date')) {
            $todayStr = Carbon::now()->format('Y-m-d');
            $startDate = $todayStr;
            $endDate = $todayStr;

            return view('admin.reports.attendance.index', compact('courses', 'batches', 'startDate', 'endDate'));
        }

        // Default dates if somehow triggered without them
        if (! $startDate) {
            $startDate = $currentYear ? $currentYear->start_date : Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        if (! $endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
        }

        // 1. Fetch Students - Bypass Academic Year Global Scope to allow historical reporting
        $studentsQuery = Student::withoutGlobalScope('academic_year');

        // 1a. Exclude Dropouts (Global Rule for this report)
        $studentsQuery->where('status', '!=', 'dropout');

        if ($courseId) {
            $studentsQuery->whereHas('batch', function ($q) use ($courseId) {
                $q->withoutGlobalScope('academic_year')->where('course_id', $courseId);
            });

            // 1b. Internship Specific Rule: Only Active Students
            $course = Course::find($courseId);
            if ($course && stripos($course->name, 'Internship') !== false) {
                $studentsQuery->where('status', 'active');
            }
        }

        if ($batchId) {
            $studentsQuery->where('batch_id', $batchId);

            // 1c. Check Batch Name too just in case course isn't selected but batch is
            // (Though usually batch implies a course, if batch is internship, apply rule)
            // For now, let's rely on Course check if course selected, or we can check relation.
            // If only batch is selected, we can check its course.
            if (! $courseId) {
                $batch = Batch::withoutGlobalScope('academic_year')->with('course')->find($batchId);
                if ($batch && $batch->course && stripos($batch->course->name, 'Internship') !== false) {
                    $studentsQuery->where('status', 'active');
                }
            }
        }

        // Get basic student info
        $allStudents = $studentsQuery->select('id', 'name', 'enrollment_number', 'batch_id', 'admission_date', 'created_at')
            ->with([
                'batch' => function ($q) {
                    $q->withoutGlobalScope('academic_year');
                },
                'batch.course',
            ])->get();

        // 2. Fetch Holidays (Global) - Ensure consistent date format
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->map(fn ($h) => (is_string($h->date) ? substr($h->date, 0, 10) : $h->date->format('Y-m-d')))
            ->toArray();

        // 3. Fetch Attendance Data Efficiently - Bypass Global Scope
        $attendanceRecords = Attendance::withoutGlobalScope('academic_year')
            ->whereIn('student_id', $allStudents->pluck('id'))
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->select('student_id', 'attendance_date', 'status')
            ->get()
            ->groupBy('student_id');

        // 3a. Fetch first biometric record for all students to avoid N+1
        $firstPunches = Attendance::withoutGlobalScope('academic_year')
            ->whereIn('student_id', $allStudents->pluck('id'))
            ->whereIn('status', ['present', 'late'])
            ->selectRaw('student_id, MIN(attendance_date) as first_date')
            ->groupBy('student_id')
            ->pluck('first_date', 'student_id')
            ->toArray();

        // 3b. Fetch Daily Punch Counts for "Low Attendance" holiday check - Bypass Global Scope
        $dailyCounts = Attendance::withoutGlobalScope('academic_year')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->selectRaw('DATE(attendance_date) as date, count(distinct student_id) as count')
            ->groupBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                // Ensure date is string Y-m-d
                $d = is_string($item->date) ? substr($item->date, 0, 10) : Carbon::parse($item->date)->format('Y-m-d');

                return [$d => $item->count];
            })
            ->toArray();

        // 3c. Generate Months List for Monthly Breakdown
        $period = CarbonPeriod::create($startDate, '1 month', $endDate);
        $months = [];
        foreach ($period as $dt) {
            $months[] = $dt->copy()->startOfMonth();
        }

        // 4. Process Data & Calculate Percentages Per Student
        $processedStudents = $allStudents->map(function ($student) use ($attendanceRecords, $startDate, $endDate, $holidays, $dailyCounts, $months, $firstPunches) {

            // Get Student Records indexed by date
            $studentRecords = $attendanceRecords->get($student->id, collect())->mapWithKeys(function ($item) {
                $d = Carbon::parse($item->attendance_date)->format('Y-m-d');

                return [$d => $item];
            });

            // Calculate Monthly Stats
            $monthlyStats = [];
            foreach ($months as $monthDt) {
                $monthStart = $monthDt->copy()->startOfMonth();
                $monthEnd = $monthDt->copy()->endOfMonth();

                // Cap month dates to select range
                if ($monthStart->lt(Carbon::parse($startDate))) {
                    $monthStart = Carbon::parse($startDate);
                }
                if ($monthEnd->gt(Carbon::parse($endDate))) {
                    $monthEnd = Carbon::parse($endDate);
                }

                $monthlyStats[$monthDt->format('M_Y')] = $this->calculateStudentStats($student, $monthStart, $monthEnd, $holidays, $dailyCounts, $studentRecords, $firstPunches);
            }

            // Calculate Overall Stats
            $overall = $this->calculateStudentStats($student, Carbon::parse($startDate), Carbon::parse($endDate), $holidays, $dailyCounts, $studentRecords, $firstPunches);

            return (object) [
                'id' => $student->id,
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'batch_name' => $student->batch?->name ?? 'N/A',
                'course_name' => $student->batch?->course?->name ?? 'N/A',
                'total_working_days' => $overall['working_days'],
                'present_days' => $overall['present'],
                'late_days' => $overall['late'],
                'absent_days' => $overall['absent'],
                'internship_days' => $overall['internship'],
                'excused_days' => $overall['excused'],
                'holidays' => $overall['holidays'],
                'attendance_percentage' => round($overall['percentage'], 1),
                'monthly_stats' => $monthlyStats,
            ];
        });

        $processedStudents = collect($processedStudents); // Ensure collection

        // 5. Aggregate Stats for Dashboard
        $totalStudents = $processedStudents->count();
        $avgAttendance = $totalStudents > 0 ? $processedStudents->avg('attendance_percentage') : 0;
        $avgPresent = $totalStudents > 0 ? $processedStudents->avg(fn($s) => $s->present_days + $s->late_days + $s->internship_days) : 0;
        $avgAbsent = $totalStudents > 0 ? $processedStudents->avg('absent_days') : 0;

        // Distribution Buckets (Aligned with PRD)
        $distribution = [
            'Excellent (≥ 90%)' => 0,
            'Good (75-89.9%)' => 0,
            'Satisfactory (60-74.9%)' => 0,
            'Needs Improvement (< 60%)' => 0,
        ];

        // Overall Present vs Absent Breakdown for Pie Chart
        $overallPresent = $processedStudents->sum(fn($s) => $s->present_days + $s->late_days + $s->internship_days);
        $overallAbsent = $processedStudents->sum('absent_days');

        foreach ($processedStudents as $s) {
            $p = $s->attendance_percentage;
            if ($p >= 90) {
                $distribution['Excellent (≥ 90%)']++;
                $s->attendance_status = 'excellent';
            } elseif ($p >= 75) {
                $distribution['Good (75-89.9%)']++;
                $s->attendance_status = 'good';
            } elseif ($p >= 60) {
                $distribution['Satisfactory (60-74.9%)']++;
                $s->attendance_status = 'satisfactory';
            } else {
                $distribution['Needs Improvement (< 60%)']++;
                $s->attendance_status = 'needs_improvement';
            }
        }

        // 6. Sorting
        if ($sortBy === 'attendance_percentage') {
            $processedStudents = $sortOrder === 'asc'
                ? $processedStudents->sortBy('attendance_percentage')
                : $processedStudents->sortByDesc('attendance_percentage');
        } elseif ($sortBy === 'name') {
            $processedStudents = $sortOrder === 'asc'
                ? $processedStudents->sortBy('student_name', SORT_NATURAL | SORT_FLAG_CASE)
                : $processedStudents->sortByDesc('student_name', SORT_NATURAL | SORT_FLAG_CASE);
        }

        // 7. Pagination
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;
        $currentItems = $processedStudents->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedStudents = new LengthAwarePaginator(
            $currentItems,
            $totalStudents,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $stats = [
            'total_students' => $totalStudents,
            'avg_attendance' => number_format($avgAttendance, 1),
            'avg_present' => number_format($avgPresent, 1),
            'avg_absent' => number_format($avgAbsent, 1),
            'distribution' => $distribution,
            'total_present' => $overallPresent,
            'total_absent' => $overallAbsent,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.attendance._table', [
                    'students' => $paginatedStudents,
                    'pagination' => $paginatedStudents,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder,
                    'months' => $months,
                ])->render(),
                'stats' => $stats,
            ]);
        }

        return view('admin.reports.attendance.index', compact(
            'courses',
            'batches',
            'startDate',
            'endDate',
            'sortBy',
            'sortOrder'
        ))->with([
            'students' => $paginatedStudents,
            'pagination' => $paginatedStudents,
            'stats' => $stats,
            'months' => $months,
        ]);
    }

    /**
     * Helper to calculate stats for a specific range and student
     */
    private function calculateStudentStats($student, $start, $end, $holidays, $dailyCounts, $studentRecords, $firstPunches = [])
    {
        $profileStartDate = $student->admission_date ? Carbon::parse($student->admission_date)->startOfDay() : $student->created_at->startOfDay();
        $firstBiometricUse = $firstPunches[$student->id] ?? null;

        $todayStr = Carbon::now()->format('Y-m-d');
        $isOnInternship = $student->batch && $student->batch->is_on_internship;
        $internshipStartDate = $isOnInternship ? $student->batch->internship_start_date : null;

        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $internshipCount = 0;
        $excusedCount = 0;
        $holidaysCount = 0;

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $isSunday = $current->isSunday();
            $isExplicitHoliday = in_array($dateStr, $holidays);
            $isFuture = $dateStr > $todayStr;

            $isLowAttendanceHoliday = false;
            if (! $isFuture && ! $isSunday && ! $isExplicitHoliday) {
                $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                if ($dayPunchCount < 10) {
                    $isLowAttendanceHoliday = true;
                }
            }

            $isHoliday = $isSunday || $isExplicitHoliday || $isLowAttendanceHoliday;

            $status = 'none';
            if (isset($studentRecords[$dateStr])) {
                $att = $studentRecords[$dateStr];
                $status = strtolower(trim($att->status));

                if (! $isFuture && $status !== 'none') {
                    if ($status === 'present') {
                        $presentCount++;
                    } elseif ($status === 'late') {
                        $lateCount++;
                    } elseif ($status === 'absent') {
                        $absentCount++;
                    } elseif ($status === 'internship') {
                        $internshipCount++;
                    } elseif ($status === 'excused') {
                        $excusedCount++;
                    } else {
                        $absentCount++;
                    }
                }
            } else {
                // No manual record, check for holiday/future/ignore
                $shouldIgnore = $current->lt($profileStartDate) || is_null($firstBiometricUse);

                if ($isFuture || $shouldIgnore) {
                    $status = 'none';
                } elseif ($isHoliday) {
                    $holidaysCount++;
                    $status = 'holiday';
                } else {
                    // Working Day - Check for Internship
                    $isInternshipDay = $isOnInternship && (! $internshipStartDate || $current->gte(Carbon::parse($internshipStartDate)));
                    if ($isInternshipDay) {
                        $internshipCount++;
                        $status = 'internship';
                    } else {
                        $absentCount++;
                        $status = 'absent';
                    }
                }
            }
            $current->addDay();
        }

        // Logic from Student Profile
        $totalCalculatedDays = $presentCount + $lateCount + $absentCount + $excusedCount + $internshipCount;
        $percentage = $totalCalculatedDays > 0 ? round((($presentCount + $lateCount + $internshipCount) / $totalCalculatedDays) * 100, 1) : 0;

        return [
            'working_days' => $totalCalculatedDays,
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'internship' => $internshipCount,
            'excused' => $excusedCount,
            'holidays' => $holidaysCount,
            'percentage' => $percentage,
        ];
    }

    public function export(Request $request)
    {
        $courseId = $request->input('course_id');
        $batchId = $request->input('batch_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return Excel::download(
            new StudentAttendanceSummaryExport($courseId, $batchId, $startDate, $endDate),
            'attendance_summary_'.now()->format('Y_m_d_H_i').'.xlsx'
        );
    }
}
