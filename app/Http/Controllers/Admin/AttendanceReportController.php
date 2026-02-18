<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Holiday;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentAttendanceSummaryExport;

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
            ->when($currentYear, function ($query) use ($currentYear) {
                // If no course/batch selected, maybe still show current year batches by default?
                // But generally, for reports, we might want to see all or specific.
                // Let's at least allow all batches to show up if sorted by date.
            })->get();

        // Initial Load without filters
        if (!$request->ajax() && !$request->has('start_date')) {
            $startDate = $currentYear ? $currentYear->start_date : Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = $currentYear ? $currentYear->end_date : Carbon::now()->format('Y-m-d');

            // If current year end date is far in future, cap it to today for report
            if ($endDate > Carbon::now()->format('Y-m-d')) {
                $endDate = Carbon::now()->format('Y-m-d');
            }

            return view('admin.reports.attendance.index', compact('courses', 'batches', 'startDate', 'endDate'));
        }

        // Default dates if somehow triggered without them
        if (!$startDate) {
            $startDate = $currentYear ? $currentYear->start_date : Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        if (!$endDate) {
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
            if (!$courseId) {
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
                'batch.course'
            ])->get();

        // 2. Fetch Holidays (Global) - Ensure consistent date format
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->map(fn($h) => (is_string($h->date) ? substr($h->date, 0, 10) : $h->date->format('Y-m-d')))
            ->toArray();

        // 3. Fetch Attendance Data Efficiently - Bypass Global Scope
        $attendanceRecords = Attendance::withoutGlobalScope('academic_year')
            ->whereIn('student_id', $allStudents->pluck('id'))
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->select('student_id', 'attendance_date', 'status')
            ->get()
            ->groupBy('student_id');

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

        // 4. Process Data & Calculate Percentages Per Student
        $processedStudents = $allStudents->map(function ($student) use ($attendanceRecords, $startDate, $endDate, $holidays, $dailyCounts) {

            // Determine effective start date for this student
            $effectiveStartDate = $student->admission_date
                ? Carbon::parse($student->admission_date)->startOfDay()
                : $student->created_at->startOfDay();

            $reportStartDate = Carbon::parse($startDate)->startOfDay();
            $reportEndDate = Carbon::parse($endDate)->startOfDay();
            $today = Carbon::now()->startOfDay();

            // Calculate Working Days & Holidays for THIS specific student
            $workingDays = 0;
            $holidaysCount = 0;
            $current = $reportStartDate->copy();

            while ($current->lte($reportEndDate)) {
                $dateStr = $current->format('Y-m-d');
                $isSunday = $current->isSunday();
                $isExplicitHoliday = in_array($dateStr, $holidays);

                // Low Attendance Holiday Logic (Matches StudentController)
                $isLowAttendanceHoliday = false;
                if ($current->lte($today) && !$isSunday && !$isExplicitHoliday) {
                    $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                    if ($dayPunchCount < 10) {
                        $isLowAttendanceHoliday = true;
                    }
                }

                $isHoliday = $isSunday || $isExplicitHoliday || $isLowAttendanceHoliday;

                if ($isHoliday) {
                    $holidaysCount++;
                } else {
                    if ($current->gte($effectiveStartDate)) {
                        $workingDays++;
                    }
                }
                $current->addDay();
            }

            // Get Student Records
            $studentRecords = $attendanceRecords->get($student->id, collect())->mapWithKeys(function ($item) {
                $d = Carbon::parse($item->attendance_date)->format('Y-m-d');
                return [$d => $item];
            });

            $presentCount = 0;
            $absentCount = 0;

            $current = $reportStartDate->copy();
            while ($current->lte($reportEndDate)) {
                $dateStr = $current->format('Y-m-d');
                $isSunday = $current->isSunday();
                $isExplicitHoliday = in_array($dateStr, $holidays);

                // Low Attendance Holiday Logic
                $isLowAttendanceHoliday = false;
                if ($current->lte($today) && !$isSunday && !$isExplicitHoliday) {
                    $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                    if ($dayPunchCount < 10) {
                        $isLowAttendanceHoliday = true;
                    }
                }

                $isHoliday = $isSunday || $isExplicitHoliday || $isLowAttendanceHoliday;

                if (!$isHoliday && $current->gte($effectiveStartDate)) {
                    // Past or current day
                    if ($current->lte($today)) {
                        if (isset($studentRecords[$dateStr])) {
                            $status = strtolower(trim($studentRecords[$dateStr]->status));
                            if (in_array($status, ['present', 'late'])) {
                                $presentCount++;
                            } elseif ($status === 'absent') {
                                $absentCount++;
                            }
                        } else {
                            // Missing record on working day -> Absent (Implicit)
                            $absentCount++;
                        }
                    }
                }
                $current->addDay();
            }

            // Calculate Percentage
            $percentage = ($workingDays > 0) ? ($presentCount / $workingDays) * 100 : 0;

            if ($percentage > 100)
                $percentage = 100;

            return (object) [
                'id' => $student->id,
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'batch_name' => $student->batch->name ?? 'N/A',
                'course_name' => $student->batch->course->name ?? 'N/A',
                'total_working_days' => $workingDays,
                'present_days' => $presentCount,
                'absent_days' => $absentCount,
                'holidays' => $holidaysCount,
                'attendance_percentage' => round($percentage, 1),
                'effective_start_date' => $effectiveStartDate->format('Y-m-d') // Debug info
            ];
        });

        $processedStudents = collect($processedStudents); // Ensure collection

        // 5. Aggregate Stats for Dashboard
        $totalStudents = $processedStudents->count();
        $avgAttendance = $totalStudents > 0 ? $processedStudents->avg('attendance_percentage') : 0;
        $avgPresent = $totalStudents > 0 ? $processedStudents->avg('present_days') : 0;
        $avgAbsent = $totalStudents > 0 ? $processedStudents->avg('absent_days') : 0;

        // Distribution Buckets
        $distribution = [
            '< 50%' => 0,
            '50% - 74%' => 0,
            '75% - 89%' => 0,
            '90% +' => 0
        ];

        // Overall Present vs Absent Breakdown for Pie Chart
        $overallPresent = $processedStudents->sum('present_days');
        $overallAbsent = $processedStudents->sum('absent_days');

        foreach ($processedStudents as $s) {
            $p = $s->attendance_percentage;
            if ($p < 50)
                $distribution['< 50%']++;
            elseif ($p < 75)
                $distribution['50% - 74%']++;
            elseif ($p < 90)
                $distribution['75% - 89%']++;
            else
                $distribution['90% +']++;
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
            'total_absent' => $overallAbsent
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.attendance._table', [
                    'students' => $paginatedStudents,
                    'pagination' => $paginatedStudents,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder
                ])->render(),
                'stats' => $stats
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
                    'stats' => $stats
                ]);
    }

    public function export(Request $request)
    {
        $courseId = $request->input('course_id');
        $batchId = $request->input('batch_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return Excel::download(
            new StudentAttendanceSummaryExport($courseId, $batchId, $startDate, $endDate),
            'attendance_summary_' . now()->format('Y_m_d_H_i') . '.xlsx'
        );
    }
}