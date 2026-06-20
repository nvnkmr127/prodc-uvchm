<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FacultyAttendanceSummaryExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance\FacultyAttendance;
use App\Models\Holiday;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class FacultyAttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $departmentFilter = $request->input('department');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sortBy = $request->input('sort_by', 'attendance_percentage');
        $sortOrder = $request->input('sort_order', 'desc');

        $departments = User::role(['staff', 'faculty'])->whereNotNull('department')->pluck('department')->unique()->sort();

        // Initial Load without filters (Default to today to satisfy user request)
        if (! $request->ajax() && ! $request->has('start_date')) {
            $todayStr = Carbon::now()->format('Y-m-d');
            $startDate = $todayStr;
            $endDate = $todayStr;

            return view('admin.reports.faculty-attendance.index', compact('departments', 'startDate', 'endDate'));
        }

        // Default dates if somehow triggered without them
        if (! $startDate) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        if (! $endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
        }

        // 1. Fetch Faculty
        $facultyQuery = User::role(['staff', 'faculty'])->where('status', 'active');

        if ($departmentFilter) {
            $facultyQuery->where('department', $departmentFilter);
        }

        // Get basic faculty info
        $allFaculties = $facultyQuery->select('id', 'name', 'biometric_employee_code', 'employee_id', 'department', 'created_at')
            ->get();

        // 2. Fetch Holidays - Ensure consistent date format
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->map(fn ($h) => (is_string($h->date) ? substr($h->date, 0, 10) : $h->date->format('Y-m-d')))
            ->toArray();

        $isSingleDay = $startDate === $endDate;

        // 3. Fetch Attendance Data Efficiently
        $attendanceRecords = FacultyAttendance::whereIn('faculty_id', $allFaculties->pluck('id'))
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->select('faculty_id', 'attendance_date', 'status', 'check_in_time', 'check_out_time')
            ->get()
            ->groupBy('faculty_id');

        // 3a. Fetch first biometric record for all faculty to avoid N+1
        $firstPunches = FacultyAttendance::whereIn('faculty_id', $allFaculties->pluck('id'))
            ->whereIn('status', ['present', 'late', 'half_day'])
            ->selectRaw('faculty_id, MIN(attendance_date) as first_date')
            ->groupBy('faculty_id')
            ->pluck('first_date', 'faculty_id')
            ->toArray();

        // 3c. Generate Months List for Monthly Breakdown
        $period = CarbonPeriod::create($startDate, '1 month', $endDate);
        $months = [];
        foreach ($period as $dt) {
            $months[] = $dt->copy()->startOfMonth();
        }

        // 4. Process Data & Calculate Percentages Per Faculty
        $processedFaculties = $allFaculties->map(function ($faculty) use ($attendanceRecords, $startDate, $endDate, $holidays, $months, $firstPunches, $isSingleDay) {

            // Get Faculty Records indexed by date
            $facultyRecords = $attendanceRecords->get($faculty->id, collect())->mapWithKeys(function ($item) {
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

                $monthlyStats[$monthDt->format('M_Y')] = $this->calculateFacultyStats($faculty, $monthStart, $monthEnd, $holidays, $facultyRecords, $firstPunches);
            }

            // Calculate Overall Stats
            $overall = $this->calculateFacultyStats($faculty, Carbon::parse($startDate), Carbon::parse($endDate), $holidays, $facultyRecords, $firstPunches);

            $dailyRecord = null;
            if ($isSingleDay) {
                $dailyRecordStr = Carbon::parse($startDate)->format('Y-m-d');
                $dailyRecord = $facultyRecords->get($dailyRecordStr);
            }

            return (object) [
                'id' => $faculty->id,
                'faculty_name' => $faculty->name,
                'biometric_code' => $faculty->biometric_employee_code ?? $faculty->employee_id ?? 'N/A',
                'department' => $faculty->department ?? 'General Staff',
                'total_working_days' => $overall['working_days'],
                'present_days' => $overall['present'],
                'late_days' => $overall['late'],
                'half_days' => $overall['half_days'],
                'absent_days' => $overall['absent'],
                'excused_days' => $overall['excused'],
                'holidays' => $overall['holidays'],
                'attendance_percentage' => round($overall['percentage'], 1),
                'monthly_stats' => $monthlyStats,
                'daily_record' => $dailyRecord,
            ];
        });

        $processedFaculties = collect($processedFaculties);

        // 5. Aggregate Stats for Dashboard
        $totalFaculties = $processedFaculties->count();
        $avgAttendance = $totalFaculties > 0 ? $processedFaculties->avg('attendance_percentage') : 0;
        $avgPresent = $totalFaculties > 0 ? $processedFaculties->avg(fn($f) => $f->present_days + $f->late_days + ($f->half_days * 0.5)) : 0;
        $avgAbsent = $totalFaculties > 0 ? $processedFaculties->avg('absent_days') : 0;

        // Distribution Buckets (Aligned with PRD)
        $distribution = [
            'Excellent (≥ 90%)' => 0,
            'Good (75-89.9%)' => 0,
            'Satisfactory (60-74.9%)' => 0,
            'Needs Improvement (< 60%)' => 0,
        ];

        // Overall Present vs Absent Breakdown for Pie Chart
        $overallPresent = $processedFaculties->sum(fn($f) => $f->present_days + $f->late_days + ($f->half_days * 0.5));
        $overallAbsent = $processedFaculties->sum('absent_days');

        foreach ($processedFaculties as $f) {
            $p = $f->attendance_percentage;
            if ($p >= 90) {
                $distribution['Excellent (≥ 90%)']++;
                $f->attendance_status = 'excellent';
            } elseif ($p >= 75) {
                $distribution['Good (75-89.9%)']++;
                $f->attendance_status = 'good';
            } elseif ($p >= 60) {
                $distribution['Satisfactory (60-74.9%)']++;
                $f->attendance_status = 'satisfactory';
            } else {
                $distribution['Needs Improvement (< 60%)']++;
                $f->attendance_status = 'needs_improvement';
            }
        }

        // 6. Sorting
        if ($sortBy === 'attendance_percentage') {
            $processedFaculties = $sortOrder === 'asc'
                ? $processedFaculties->sortBy('attendance_percentage')
                : $processedFaculties->sortByDesc('attendance_percentage');
        } elseif ($sortBy === 'name') {
            $processedFaculties = $sortOrder === 'asc'
                ? $processedFaculties->sortBy('faculty_name', SORT_NATURAL | SORT_FLAG_CASE)
                : $processedFaculties->sortByDesc('faculty_name', SORT_NATURAL | SORT_FLAG_CASE);
        }

        // 7. Pagination
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;
        $currentItems = $processedFaculties->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedFaculties = new LengthAwarePaginator(
            $currentItems,
            $totalFaculties,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $stats = [
            'total_students' => $totalFaculties, // keeping name for js compatibility
            'avg_attendance' => number_format($avgAttendance, 1),
            'avg_present' => number_format($avgPresent, 1),
            'avg_absent' => number_format($avgAbsent, 1),
            'distribution' => $distribution,
            'total_present' => $overallPresent,
            'total_absent' => $overallAbsent,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports.faculty-attendance._table', [
                    'faculties' => $paginatedFaculties,
                    'pagination' => $paginatedFaculties,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder,
                    'months' => $months,
                    'isSingleDay' => $isSingleDay,
                ])->render(),
                'stats' => $stats,
            ]);
        }

        return view('admin.reports.faculty-attendance.index', compact(
            'departments',
            'startDate',
            'endDate',
            'sortBy',
            'sortOrder'
        ))->with([
            'faculties' => $paginatedFaculties,
            'pagination' => $paginatedFaculties,
            'stats' => $stats,
            'months' => $months,
            'isSingleDay' => $isSingleDay,
        ]);
    }

    /**
     * Helper to calculate stats for a specific range and faculty
     */
    private function calculateFacultyStats($faculty, $start, $end, $holidays, $facultyRecords, $firstPunches = [])
    {
        $profileStartDate = $faculty->created_at->startOfDay();
        $firstBiometricUse = $firstPunches[$faculty->id] ?? null;

        $todayStr = Carbon::now()->format('Y-m-d');

        $presentCount = 0;
        $lateCount = 0;
        $halfDayCount = 0;
        $absentCount = 0;
        $excusedCount = 0;
        $holidaysCount = 0;

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $isSunday = $current->isSunday();
            $isExplicitHoliday = in_array($dateStr, $holidays);
            $isFuture = $dateStr > $todayStr;

            $isHoliday = $isSunday || $isExplicitHoliday;

            $status = 'none';
            if (isset($facultyRecords[$dateStr])) {
                $att = $facultyRecords[$dateStr];
                $status = strtolower(trim($att->status));

                if (! $isFuture && $status !== 'none') {
                    if ($status === 'present') {
                        $presentCount++;
                    } elseif ($status === 'late') {
                        $lateCount++;
                    } elseif ($status === 'half_day') {
                        $halfDayCount++;
                    } elseif ($status === 'absent') {
                        $absentCount++;
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
                    $absentCount++;
                    $status = 'absent';
                }
            }
            $current->addDay();
        }

        $totalCalculatedDays = $presentCount + $lateCount + $halfDayCount + $absentCount + $excusedCount;
        $percentage = $totalCalculatedDays > 0 ? round((($presentCount + $lateCount + ($halfDayCount * 0.5)) / $totalCalculatedDays) * 100, 1) : 0;

        return [
            'working_days' => $totalCalculatedDays,
            'present' => $presentCount,
            'late' => $lateCount,
            'half_days' => $halfDayCount,
            'absent' => $absentCount,
            'excused' => $excusedCount,
            'holidays' => $holidaysCount,
            'percentage' => $percentage,
        ];
    }

    public function export(Request $request)
    {
        $departmentFilter = $request->input('department');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return Excel::download(
            new FacultyAttendanceSummaryExport($departmentFilter, $startDate, $endDate),
            'faculty_attendance_summary_'.now()->format('Y_m_d_H_i').'.xlsx'
        );
    }
}
