<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance;
use App\Models\Student;
use App\Models\Batch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SingleAttendanceController extends Controller
{
    public function index()
    {
        return view('admin.attendance.single.index');
    }

    public function getStudents(Request $request)
    {
        try {
            $query = Student::where('status', 'active');

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('enrollment_number', 'like', "%{$search}%");
                });
            } elseif ($request->has('batch_id')) {
                $query->where('batch_id', $request->get('batch_id'));
            } else {
                return response()->json([]); // No criteria
            }

            $students = $query->orderBy('name')
                ->limit(20)
                ->get()
                ->append('batch_name');

            return response()->json($students);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    public function getCalendar(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2099',
        ]);

        $student = Student::findOrFail($request->student_id);
        $month = $request->month;
        $year = $request->year;

        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // 1. Fetch Attendance Records
        $attendances = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy(function ($item) {
                return $item->attendance_date->format('Y-m-d');
            });

        // 2. Fetch Holidays
        $holidays = \App\Models\Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->pluck('name', 'date')->toArray();

        // 3. Fetch Daily Punch Counts (for "Low Attendance" holiday logic)
        $dailyCounts = Attendance::whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->selectRaw('DATE(attendance_date) as date, count(distinct student_id) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // 4. Build Calendar Data
        $calendar = [];
        $current = $startDate->copy();
        $todayStr = now()->format('Y-m-d');

        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');

            $isFuture = ($dateStr > $todayStr);
            $isWeekend = $current->isSunday();
            $isExplicitHoliday = isset($holidays[$dateStr]);

            // Low Attendance/Existing Attendance Holiday Logic
            $isLowAttendanceHoliday = false;
            // Only check if not already a weekend or future
            if (!$isFuture && !$isWeekend && !$isExplicitHoliday) {
                $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                // If fewer than 10 students present, treat as holiday (e.g. unexpected leave)
                if ($dayPunchCount < 10) {
                    $isLowAttendanceHoliday = true;
                }
            }

            $isEffectiveHoliday = $isExplicitHoliday || $isLowAttendanceHoliday;
            $holidayName = $isExplicitHoliday ? $holidays[$dateStr] : 'Holiday';

            $status = 'pending'; // Default
            $record = $attendances[$dateStr] ?? null;

            if ($record) {
                $status = strtolower($record->status);
            } else {
                if ($isFuture) {
                    $status = 'pending';
                } elseif ($isWeekend) {
                    $status = 'weekend'; // Sunday is specialized "oneside"
                } elseif ($isEffectiveHoliday) {
                    $status = 'holiday';
                } elseif ($dateStr < $todayStr) {
                    $status = 'absent'; // Past working day with no record is Absent
                } else {
                    $status = 'pending'; // Today with no record
                }
            }

            $calendar[$dateStr] = [
                'date' => $current->copy(),
                'status' => $status,
                'record' => $record,
                'holiday_name' => ($status === 'holiday') ? $holidayName : null,
                'is_sunday' => $isWeekend
            ];

            $current->addDay();
        }

        return view('admin.attendance.single.calendar', compact('student', 'calendar', 'startDate', 'endDate'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'attendance' => 'required|array',
            'attendance.*.date' => 'required|date',
            'attendance.*.status' => 'required|in:present,absent,late,excused,holiday',
        ]);

        $student = Student::findOrFail($request->student_id);

        DB::transaction(function () use ($request, $student) {
            foreach ($request->attendance as $data) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'attendance_date' => $data['date'],
                    ],
                    [
                        'status' => $data['status'],
                        'batch_id' => $student->batch_id,
                        'marked_by' => auth()->id(),
                        'marked_at' => now(),
                    ]
                );
            }
        });

        return response()->json(['message' => 'Attendance updated successfully']);
    }
}
