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
        $batches = Batch::when($courseId, function ($query) use ($courseId) {
            return $query->where('course_id', $courseId);
        })->get();

        // Initial Load without filers
        if (!$request->ajax() && !$request->has('start_date')) {
            return view('admin.reports.attendance.index', compact('courses', 'batches'));
        }

        // Default dates if somehow triggered without them (though UI enforces required)
        if (!$startDate)
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        if (!$endDate)
            $endDate = Carbon::now()->format('Y-m-d');

        // 1. Fetch Students
        $studentsQuery = Student::query();

        // 1a. Exclude Dropouts (Global Rule for this report)
        $studentsQuery->where('status', '!=', 'dropout');

        if ($courseId) {
            $studentsQuery->whereHas('batch', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
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
                $batch = Batch::with('course')->find($batchId);
                if ($batch && $batch->course && stripos($batch->course->name, 'Internship') !== false) {
                    $studentsQuery->where('status', 'active');
                }
            }
        }

        // Get basic student info
        $allStudents = $studentsQuery->select('id', 'name', 'enrollment_number', 'batch_id')->with(['batch.course'])->get();

        // 2. Calculate Total Working Days (Assuming Global Holidays for now)
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->toArray();

        $period = CarbonPeriod::create($startDate, $endDate);
        $totalWorkingDays = 0;
        foreach ($period as $date) {
            if (!$date->isSunday() && !in_array($date->format('Y-m-d'), $holidays)) {
                $totalWorkingDays++;
            }
        }

        // 3. Fetch Attendance Data Efficiently
        $attendanceStats = Attendance::whereIn('student_id', $allStudents->pluck('id'))
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->select('student_id', 'status', DB::raw('count(*) as count'))
            ->groupBy('student_id', 'status')
            ->get();

        // 4. Process Data & Calculate Percentages
        $processedStudents = $allStudents->map(function ($student) use ($attendanceStats, $totalWorkingDays) {
            $presentCount = $attendanceStats->where('student_id', $student->id)->where('status', 'present')->sum('count');
            $absentCount = $attendanceStats->where('student_id', $student->id)->where('status', 'absent')->sum('count');

            // Calculate Percentage
            $percentage = ($totalWorkingDays > 0) ? ($presentCount / $totalWorkingDays) * 100 : 0;

            if ($percentage > 100)
                $percentage = 100;

            return [
                'id' => $student->id,
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'batch_name' => $student->batch->name ?? 'N/A',
                'course_name' => $student->batch->course->name ?? 'N/A',
                'total_working_days' => $totalWorkingDays,
                'present_days' => $presentCount,
                'absent_days' => $absentCount,
                'attendance_percentage' => round($percentage, 1)
            ];
        });

        // 5. Aggregate Stats for Dashboard
        $totalStudents = $processedStudents->count();
        $avgAttendance = $processedStudents->avg('attendance_percentage') ?? 0;
        $avgPresent = $processedStudents->avg('present_days') ?? 0;
        $avgAbsent = $processedStudents->avg('absent_days') ?? 0;

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
            $p = $s['attendance_percentage'];
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