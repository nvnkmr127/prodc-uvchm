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
        $departments = User::role('staff')
            ->where('status', 'active')
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->filter()
            ->values();

        // 2. Fetch Faculty Users
        $facultyQuery = User::role(['staff', 'faculty'])
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
        $totalPresent = 0;
        $totalLate = 0;
        $totalAbsent = 0;
        $totalHours = 0;
        $totalRecordsCount = 0;

        foreach ($faculties as $faculty) {
            $facultyRecords = [];
            $facultyPresentDays = 0.0;
            $facultyAbsentDays = 0.0;
            $facultyLeaveDays = 0.0;
            $facultyLateCount = 0;
            $facultyTotalHours = 0.0;

            // Group leaves by date for this user
            $userLeaves = $leaves->where('user_id', $faculty->id);

            foreach ($workingDaysList as $day) {
                $dateStr = $day['date'];
                $isWorking = $day['is_working'];
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
                    if ($status === 'late') {
                        $facultyLateCount++;
                    }
                    $facultyTotalHours += $hours;

                    // If it was half day due to hours or single punch, status is half_day
                    if ($presentValue == 0.5 && $status !== 'half_day') {
                        $status = 'half_day';
                    }
                } elseif ($onLeave && $isWorking) {
                    $status = 'excused';
                    $facultyLeaveDays += $leaveWeight;
                    $notes = 'Approved Leave';
                } elseif ($isWorking) {
                    $status = 'absent';
                    $facultyAbsentDays += 1.0;
                } else {
                    $status = 'weekend/holiday';
                    $notes = $day['reason'] ?? 'Non-working day';
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

                // Accumulate overall stats if it is a working day
                if ($isWorking) {
                    if ($attendanceRecord) {
                        $totalPresent += $attendanceRecord->present_value;
                        if ($attendanceRecord->status === 'late') {
                            $totalLate++;
                        }
                        $totalHours += $hours;
                    } elseif ($onLeave) {
                        // ignore leave
                    } else {
                        $totalAbsent++;
                    }
                    $totalRecordsCount++;
                }
            }

            $records = array_merge($records, $facultyRecords);
        }

        // Format stats for view
        $stats = [
            'total_faculty' => $faculties->count(),
            'present_today' => FacultyAttendance::whereDate('attendance_date', today())->present()->count(),
            'late_today' => FacultyAttendance::whereDate('attendance_date', today())->late()->count(),
            'checked_in_today' => count($realtimeCheckedIn),
            'avg_working_hours' => FacultyAttendance::whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->whereNotNull('check_out_time')
                ->avg('working_hours') ?? 0.0,
            'total_present_days' => $totalPresent,
            'total_absent_days' => $totalAbsent,
            'total_late_arrivals' => $totalLate,
            'avg_hours_summary' => $totalWorkingDaysCount > 0 && $faculties->count() > 0 ? round($totalHours / $faculties->count(), 2) : 0,
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

        return view('admin.faculty.attendance.index', compact(
            'records',
            'stats',
            'departments',
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

        $facultyQuery = User::role(['staff', 'faculty'])->where('status', 'active');
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
        foreach ($faculties as $faculty) {
            $userLeaves = $leaves->where('user_id', $faculty->id);

            foreach ($workingDaysList as $day) {
                $dateStr = $day['date'];
                $isWorking = $day['is_working'];
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
                    
                    if ($attendanceRecord->present_value == 0.5 && $rawStatus !== 'half_day') {
                        $rawStatus = 'half_day';
                        $status = 'Half Day';
                    }
                } elseif ($onLeave && $isWorking) {
                    $rawStatus = 'excused';
                    $status = 'On Leave';
                } elseif ($isWorking) {
                    $rawStatus = 'absent';
                    $status = 'Absent';
                } else {
                    $rawStatus = 'weekend/holiday';
                    $status = $day['reason'] ?? 'Weekend/Holiday';
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

        $temp = $start->copy();
        while ($temp->lte($end)) {
            $dateStr = $temp->toDateString();
            $isWeekend = $temp->isWeekend();
            $isHoliday = in_array($dateStr, $holidays);

            if (!$isWeekend && !$isHoliday) {
                $dates[] = [
                    'date' => $dateStr,
                    'is_working' => true,
                ];
            } else {
                $dates[] = [
                    'date' => $dateStr,
                    'is_working' => false,
                    'reason' => $isWeekend ? 'Weekend' : 'Holiday',
                ];
            }
            $temp->addDay();
        }
        return $dates;
    }
}
