<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance\FacultyAttendance;
use App\Models\User;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Exports\FacultyAttendanceExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class FacultyAttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Check permissions
        $this->authorize('view attendance');

        // 1. Handle filters
        $dateRange = $request->input('date_range', 'today');
        $startDateStr = $request->input('start_date');
        $endDateStr = $request->input('end_date');
        $departmentFilter = $request->input('department');
        $statusFilter = $request->input('status');
        $searchQuery = $request->input('search');

        // Calculate start & end Carbon dates
        [$startDate, $endDate] = $this->parseDateRange($dateRange, $startDateStr, $endDateStr);

        // Fetch active departments for filter dropdown
        $departments = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['staff', 'faculty']);
            })
            ->where('status', 'active')
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->filter()
            ->values();

        // 2. Fetch Faculty Users
        $facultyQuery = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['staff', 'faculty']);
            })
            ->where('status', 'active')
            ->orderBy('name');

        if ($departmentFilter) {
            $facultyQuery->where('department', $departmentFilter);
        }

        if ($searchQuery) {
            $facultyQuery->where(function($q) use ($searchQuery) {
                $q->where('name', 'like', "%{$searchQuery}%")
                  ->orWhere('employee_id', 'like', "%{$searchQuery}%")
                  ->orWhere('email', 'like', "%{$searchQuery}%");
            });
        }

        $faculties = $facultyQuery->get();

        // 3. Fetch working days in the range
        $workingDaysList = $this->getWorkingDays($startDate, $endDate);
        $totalWorkingDaysCount = collect($workingDaysList)->where('is_working', true)->count();

        // 4. Fetch Attendance records for the range
        $attendances = FacultyAttendance::whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy(function($item) {
                return $item->faculty_id . '_' . $item->attendance_date->toDateString();
            });

        // 5. Fetch Approved Leave Applications in the range
        $leaves = collect();
        if (class_exists(LeaveApplication::class)) {
            $leaves = LeaveApplication::where('status', 'approved')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate->toDateString())
                              ->where('end_date', '>=', $endDate->toDateString());
                        });
                })
                ->get();
        }

        // 6. Build records list (Faculty x Date)
        $records = [];
        $realtimeCheckedIn = [];

        // For real-time checked-in monitoring (only checked in today with check_out_time = null)
        $todayStr = Carbon::today()->toDateString();
        $todayAttendances = FacultyAttendance::with('faculty')->whereDate('attendance_date', $todayStr)->get();
        foreach ($todayAttendances as $todayAtt) {
            if ($todayAtt->check_in_time && !$todayAtt->check_out_time) {
                $realtimeCheckedIn[] = [
                    'faculty' => $todayAtt->faculty,
                    'check_in' => $todayAtt->check_in_time,
                    'status' => $todayAtt->status,
                ];
            }
        }

        // Statistics
        $totalPresent = 0.0;
        $totalLate = 0;
        $totalAbsent = 0.0;
        $totalLeave = 0.0;
        $totalHours = 0.0;
        $totalRecordsCount = 0;

        $leaderboard = [];
        $today = Carbon::today();
        $todayStr = $today->toDateString();

        $elapsedWorkingDaysList = collect($workingDaysList)->filter(fn($d) => $d['date'] <= $todayStr);
        $elapsedWorkingDaysCount = $elapsedWorkingDaysList->where('is_working', true)->count();
        $evalWorkingDaysCount = ($endDate->isFuture() || $endDate->isToday()) ? $elapsedWorkingDaysCount : $totalWorkingDaysCount;

        foreach ($faculties as $faculty) {
            $facultyRecords = [];
            $facultyPresentDays = 0.0;
            $facultyAbsentDays = 0.0;
            $facultyLeaveDays = 0.0;
            $facultyLateCount = 0;
            $facultyHalfDayCount = 0;
            $facultyTotalHours = 0.0;

            // Group leaves by date for this user
            $userLeaves = $leaves->where('user_id', $faculty->id);

            foreach ($workingDaysList as $day) {
                $dateStr = $day['date'];
                $isWorking = $day['is_working'];
                $isFuture = ($dateStr > $todayStr);
                $key = $faculty->id . '_' . $dateStr;

                $attendanceRecord = $attendances->get($key)?->first();

                // Check if user has approved leave on this date
                $onLeave = false;
                $leaveWeight = 0.0;
                $leaveApp = $userLeaves->first(function($l) use ($dateStr) {
                    return $dateStr >= $l->start_date && $dateStr <= $l->end_date;
                });
                if ($leaveApp) {
                    $onLeave = true;
                    $leaveWeight = $leaveApp->is_half_day ? 0.5 : 1.0;
                }

                $status = 'absent';
                $checkIn = null;
                $checkOut = null;
                $hours = 0.0;
                $notes = '';
                $lateMinutes = 0;

                if ($attendanceRecord) {
                    $status = $attendanceRecord->status;
                    $checkIn = $attendanceRecord->check_in_time;
                    $checkOut = $attendanceRecord->check_out_time;
                    $hours = floatval($attendanceRecord->working_hours);
                    $notes = $attendanceRecord->notes;
                    $lateMinutes = intval($attendanceRecord->late_minutes);

                    $presentValue = $attendanceRecord->present_value;
                    $facultyPresentDays += $presentValue;
                    $facultyTotalHours += $hours;

                    if ($isWorking && $attendanceRecord->status !== 'holiday') {
                        $totalPresent += $presentValue;
                        $totalHours += $hours;

                        if ($attendanceRecord->status === 'late' || $lateMinutes > 0) {
                            $facultyLateCount++;
                            $totalLate++;
                        }
                        if ($presentValue == 0.5 || $status === 'half_day') {
                            $facultyHalfDayCount++;
                        }
                    }

                    // If it was half day due to hours or single punch, status is half_day
                    if ($presentValue == 0.5 && $status !== 'half_day' && $status !== 'holiday') {
                        $status = 'half_day';
                    }
                } elseif ($onLeave && $isWorking) {
                    $status = 'excused';
                    $facultyLeaveDays += $leaveWeight;
                    $notes = 'Approved Leave';
                    if (!$isFuture) {
                        $totalLeave += $leaveWeight;
                    }
                } elseif ($isWorking && !$isFuture) {
                    $status = 'absent';
                    $facultyAbsentDays += 1.0;
                    $totalAbsent += 1.0;
                } elseif (!$isWorking) {
                    $status = ($attendanceRecord && $attendanceRecord->status === 'holiday') ? 'holiday' : 'weekend/holiday';
                    $notes = $attendanceRecord?->notes ?? ($day['reason'] ?? 'Non-working day');
                } else {
                    // Future working day with no punches or leaves: do not mark absent
                    continue;
                }

                $recordData = [
                    'faculty_id' => $faculty->id,
                    'faculty_name' => $faculty->name,
                    'employee_id' => $faculty->employee_id,
                    'department' => $faculty->department,
                    'date' => $dateStr,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'working_hours' => $checkOut ? $hours : null,
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'notes' => $notes,
                    'is_working' => $isWorking,
                ];

                // Filter record level
                if ($statusFilter && $statusFilter !== 'all' && $status !== $statusFilter) {
                    continue;
                }

                $facultyRecords[] = $recordData;
                $totalRecordsCount++;
            }

            $records = array_merge($records, $facultyRecords);

            $facultyAttendanceRate = $evalWorkingDaysCount > 0 
                ? min(100.0, round(($facultyPresentDays / $evalWorkingDaysCount) * 100, 1)) 
                : 0.0;

            $leaderboard[] = [
                'faculty_id' => $faculty->id,
                'faculty_name' => $faculty->name,
                'department' => $faculty->department ?? 'General',
                'employee_id' => $faculty->employee_id ?? ('EMP-' . str_pad($faculty->id, 4, '0', STR_PAD_LEFT)),
                'present_days' => $facultyPresentDays,
                'late_days' => $facultyLateCount,
                'half_day_days' => $facultyHalfDayCount,
                'absent_days' => $facultyAbsentDays,
                'leave_days' => $facultyLeaveDays,
                'total_hours' => round($facultyTotalHours, 1),
                'attendance_rate' => $facultyAttendanceRate,
            ];
        }

        // Sort leaderboard by attendance rate desc, then present days desc, then late days asc
        usort($leaderboard, function ($a, $b) {
            if ($b['attendance_rate'] != $a['attendance_rate']) {
                return $b['attendance_rate'] <=> $a['attendance_rate'];
            }
            if ($b['present_days'] != $a['present_days']) {
                return $b['present_days'] <=> $a['present_days'];
            }
            return $a['late_days'] <=> $b['late_days'];
        });

        // Format stats for view (dynamic based on selected range and filters)
        $isSingleDay = $startDate->toDateString() === $endDate->toDateString();
        $isToday = $isSingleDay && $startDate->isToday();
        $totalWeekendDays = collect($workingDaysList)->where('is_working', false)->filter(fn($d) => ($d['reason'] ?? '') === 'Weekend')->count();
        $totalHolidayDays = collect($workingDaysList)->where('is_working', false)->filter(fn($d) => ($d['reason'] ?? '') !== 'Weekend')->count();
        $totalFacultyCount = $faculties->count();
        
        $evalPossibleAttendanceDays = $evalWorkingDaysCount * max(1, $totalFacultyCount);
        $attendancePercentage = ($evalWorkingDaysCount > 0 && $totalFacultyCount > 0)
            ? min(100.0, round(($totalPresent / $evalPossibleAttendanceDays) * 100, 1))
            : 0.0;

        $stats = [
            'is_single_day' => $isSingleDay,
            'is_today' => $isToday,
            'date_range_label' => $isSingleDay 
                ? $startDate->format('D, d M Y') 
                : ($startDate->format('d M Y') . ' — ' . $endDate->format('d M Y')),
            'total_faculty' => $totalFacultyCount,
            'total_working_days' => $totalWorkingDaysCount,
            'elapsed_working_days' => $evalWorkingDaysCount,
            'total_holidays' => $totalHolidayDays,
            'total_weekends' => $totalWeekendDays,
            'total_off_days' => $totalHolidayDays + $totalWeekendDays,
            'attendance_percentage' => $attendancePercentage,
            'checked_in_today' => count($realtimeCheckedIn),
            'present_count' => $totalPresent,
            'late_count' => $totalLate,
            'absent_count' => $totalAbsent,
            'leave_count' => $totalLeave,
            'present_today' => $totalPresent,
            'late_today' => $totalLate,
            'avg_working_hours' => $totalPresent > 0 
                ? round($totalHours / max(1, $totalPresent), 2) 
                : 0.0,
            'total_present_days' => $totalPresent,
            'total_absent_days' => $totalAbsent,
            'total_late_arrivals' => $totalLate,
            'avg_hours_summary' => $evalWorkingDaysCount > 0 && $totalFacultyCount > 0 
                ? round($totalHours / $totalFacultyCount, 2) 
                : 0.0,
            'is_holiday_today' => $isSingleDay && !($workingDaysList[0]['is_working'] ?? true),
            'holiday_reason' => $isSingleDay ? ($workingDaysList[0]['reason'] ?? 'Holiday') : null,
        ];

        // Sort records by date desc, then name asc
        usort($records, function($a, $b) {
            if ($a['date'] === $b['date']) {
                return strcmp($a['faculty_name'], $b['faculty_name']);
            }
            return strcmp($b['date'], $a['date']);
        });

        // Paginate records (15 per page)
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage('page') ?: 1;
        $perPage = 15;
        $recordsCollection = collect($records);
        $currentPageItems = $recordsCollection->slice(($page - 1) * $perPage, $perPage)->values()->all();
        
        $paginatedRecords = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $recordsCollection->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $records = $paginatedRecords;

        if ($request->ajax()) {
            return view('admin.faculty.attendance.partials.content', compact(
                'records',
                'stats',
                'departments',
                'leaderboard',
                'realtimeCheckedIn',
                'startDate',
                'endDate',
                'dateRange',
                'departmentFilter',
                'statusFilter',
                'searchQuery'
            ));
        }

        return view('admin.faculty.attendance.index', compact(
            'records',
            'stats',
            'departments',
            'leaderboard',
            'realtimeCheckedIn',
            'startDate',
            'endDate',
            'dateRange',
            'departmentFilter',
            'statusFilter',
            'searchQuery'
        ));
    }

    public function export(Request $request, string $format)
    {
        $this->authorize('view attendance');

        // Similar filters
        $dateRange = $request->input('date_range', 'today');
        $startDateStr = $request->input('start_date');
        $endDateStr = $request->input('end_date');
        $departmentFilter = $request->input('department');
        $statusFilter = $request->input('status');
        $searchQuery = $request->input('search');

        [$startDate, $endDate] = $this->parseDateRange($dateRange, $startDateStr, $endDateStr);

        $facultyQuery = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['staff', 'faculty']);
            })
            ->where('status', 'active')
            ->orderBy('name');
        if ($departmentFilter) {
            $facultyQuery->where('department', $departmentFilter);
        }
        if ($searchQuery) {
            $facultyQuery->where(function($q) use ($searchQuery) {
                $q->where('name', 'like', "%{$searchQuery}%")
                  ->orWhere('employee_id', 'like', "%{$searchQuery}%")
                  ->orWhere('email', 'like', "%{$searchQuery}%");
            });
        }
        $faculties = $facultyQuery->get();

        $workingDaysList = $this->getWorkingDays($startDate, $endDate);
        
        $attendances = FacultyAttendance::whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy(function($item) {
                return $item->faculty_id . '_' . $item->attendance_date->toDateString();
            });

        $leaves = collect();
        if (class_exists(LeaveApplication::class)) {
            $leaves = LeaveApplication::where('status', 'approved')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate->toDateString())
                              ->where('end_date', '>=', $endDate->toDateString());
                        });
                })->get();
        }

        $exportData = [];
        $todayStr = Carbon::today()->toDateString();
        foreach ($faculties as $faculty) {
            $userLeaves = $leaves->where('user_id', $faculty->id);

            foreach ($workingDaysList as $day) {
                $dateStr = $day['date'];
                $isWorking = $day['is_working'];
                $isFuture = ($dateStr > $todayStr);
                $key = $faculty->id . '_' . $dateStr;

                $attendanceRecord = $attendances->get($key)?->first();
                $onLeave = $userLeaves->first(function($l) use ($dateStr) {
                    return $dateStr >= $l->start_date && $dateStr <= $l->end_date;
                });

                $status = 'Absent';
                $checkIn = null;
                $checkOut = null;
                $hours = null;
                $rawStatus = 'absent';

                if ($attendanceRecord) {
                    $rawStatus = $attendanceRecord->status;
                    $status = $attendanceRecord->status_label;
                    $checkIn = $attendanceRecord->check_in_time;
                    $checkOut = $attendanceRecord->check_out_time;
                    $hours = $attendanceRecord->working_hours;
                    
                    if ($attendanceRecord->present_value == 0.5 && $rawStatus !== 'half_day' && $rawStatus !== 'holiday') {
                        $rawStatus = 'half_day';
                        $status = 'Half Day';
                    }
                } elseif ($onLeave && $isWorking) {
                    $rawStatus = 'excused';
                    $status = 'On Leave';
                } elseif ($isWorking && !$isFuture) {
                    $rawStatus = 'absent';
                    $status = 'Absent';
                } elseif (!$isWorking) {
                    $rawStatus = ($attendanceRecord && $attendanceRecord->status === 'holiday') ? 'holiday' : 'weekend/holiday';
                    $status = ($attendanceRecord && $attendanceRecord->status === 'holiday') ? 'Holiday' : ($day['reason'] ?? 'Weekend/Holiday');
                } else {
                    continue;
                }

                // Filter status
                if ($statusFilter && $statusFilter !== 'all' && $rawStatus !== $statusFilter) {
                    continue;
                }

                $exportData[] = [
                    'faculty_name' => $faculty->name,
                    'date' => $dateStr,
                    'check_in_time' => $checkIn ?? 'N/A',
                    'check_out_time' => $checkOut ?? 'N/A',
                    'working_hours' => $hours !== null ? $hours : 'N/A',
                    'status' => $status,
                ];
            }
        }

        $filename = 'faculty_attendance_' . $startDate->format('Ymd') . '_to_' . $endDate->format('Ymd');
        $export = new FacultyAttendanceExport($exportData);

        if ($format === 'csv') {
            return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $filename . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    private function parseDateRange(string $range, ?string $start, ?string $end): array
    {
        $now = Carbon::today();
        switch ($range) {
            case 'today':
                return [$now->copy(), $now->copy()];
            case 'yesterday':
                return [$now->copy()->subDay(), $now->copy()->subDay()];
            case 'this_week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'last_week':
                return [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()];
            case 'this_month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'last_month':
                return [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
            case 'custom':
                if ($start && $end) {
                    return [Carbon::parse($start), Carbon::parse($end)];
                }
                // fallback
            default:
                return [$now->copy(), $now->copy()];
        }
    }

    private function getWorkingDays(Carbon $start, Carbon $end): array
    {
        $dates = [];
        $holidays = [];
        if (class_exists(Holiday::class)) {
            $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->toArray();
        }

        // Count unique faculty logins per date in the range
        $loginsPerDate = FacultyAttendance::whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) {
                $query->whereNotNull('check_in_time')
                    ->orWhereNotNull('check_out_time')
                    ->orWhereIn('status', ['present', 'late', 'half_day']);
            })
            ->selectRaw('attendance_date, COUNT(DISTINCT faculty_id) as login_count')
            ->groupBy('attendance_date')
            ->pluck('login_count', 'attendance_date')
            ->mapWithKeys(fn ($val, $k) => [is_string($k) ? substr($k, 0, 10) : $k->format('Y-m-d') => (int) $val])
            ->toArray();

        // Check if dates have records explicitly marked as holiday
        $explicitHolidayDates = FacultyAttendance::whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'holiday')
            ->pluck('attendance_date')
            ->map(fn ($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))
            ->unique()
            ->toArray();

        $temp = $start->copy();
        $today = Carbon::today();

        while ($temp->lte($end)) {
            $dateStr = $temp->toDateString();
            $isWeekend = $temp->isWeekend();
            $isExplicitHoliday = in_array($dateStr, $holidays) || in_array($dateStr, $explicitHolidayDates);
            $hasFewerThanTwoLogins = ($loginsPerDate[$dateStr] ?? 0) < 2;

            // Past dates with < 2 logins or any date explicitly marked is a holiday
            $isLowLoginHoliday = ($temp->lt($today) && $hasFewerThanTwoLogins);
            $isHoliday = $isExplicitHoliday || $isLowLoginHoliday;

            if (!$isWeekend && !$isHoliday) {
                $dates[] = [
                    'date' => $dateStr,
                    'is_working' => true,
                ];
            } else {
                $reason = $isWeekend ? 'Weekend' : 'Holiday';
                if (!$isWeekend && $isLowLoginHoliday && !in_array($dateStr, $holidays)) {
                    $reason = 'Holiday (< 2 logins)';
                }
                $dates[] = [
                    'date' => $dateStr,
                    'is_working' => false,
                    'reason' => $reason,
                ];
            }
            $temp->addDay();
        }
        return $dates;
    }
}
