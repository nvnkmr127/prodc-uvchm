<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Timetable;
use App\Services\DashboardDataService;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    protected $dashboardService;

    protected $dataService;

    public function __construct(DashboardService $dashboardService, DashboardDataService $dataService)
    {
        $this->middleware(['auth', 'role:staff']);
        $this->dashboardService = $dashboardService;
        $this->dataService = $dataService;
    }

    public function index()
    {
        $user = auth()->user();
        $dashboardData = $this->dashboardService->getDashboardData($user);

        $data = [
            'user' => $user,
            'dashboard_data' => $dashboardData,
            'todays_schedule' => $this->getTodaysSchedule($user),
            'weekly_schedule' => $this->getWeeklySchedule($user),
            'attendance_summary' => $this->getMyAttendanceSummary($user),
            'student_performance' => $this->getMyStudentPerformance($user),
            'upcoming_classes' => $this->getUpcomingClasses($user),
            'pending_tasks' => $this->getPendingTasks($user),
        ];

        return view('faculty.dashboard.index', $data);
    }

    // Helper Methods
    protected function getTodaysSchedule($user): array
    {
        return Timetable::where('user_id', $user->id)
            ->whereDate('schedule_date', today())
            ->with(['subject', 'batch.course', 'classroom', 'timeSlot'])
            ->orderBy('time_slot_id')
            ->get()
            ->map(function ($class) {
                return [
                    'id' => $class->id,
                    'subject' => $class->subject->name ?? 'Unknown',
                    'course' => $class->batch->course->name ?? 'Unknown',
                    'batch' => $class->batch->name ?? 'Unknown',
                    'classroom' => $class->classroom->name ?? 'TBD',
                    'start_time' => $class->timeSlot->start_time ?? 'TBD',
                    'end_time' => $class->timeSlot->end_time ?? 'TBD',
                    'attendance_taken' => Attendance::where('timetable_id', $class->id)->exists(),
                    'student_count' => $class->batch->students()->count(),
                ];
            })
            ->toArray();
    }

    protected function getWeeklySchedule($user): array
    {
        return Timetable::where('user_id', $user->id)
            ->whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->with(['subject', 'batch', 'timeSlot'])
            ->orderBy('schedule_date')
            ->orderBy('time_slot_id')
            ->get()
            ->groupBy(function ($item) {
                return $item->schedule_date->format('Y-m-d');
            })
            ->toArray();
    }

    protected function getMyAttendanceSummary($user): array
    {
        $totalClasses = Timetable::where('user_id', $user->id)->count();
        $classesWithAttendance = Timetable::where('user_id', $user->id)
            ->whereHas('attendances')
            ->count();

        $attendanceRecords = Attendance::whereHas('timetable', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        $totalStudentRecords = $attendanceRecords->count();
        $presentRecords = $attendanceRecords->where('status', 'present')->count();

        return [
            'total_classes' => $totalClasses,
            'attendance_taken' => $classesWithAttendance,
            'completion_rate' => $totalClasses > 0 ?
                round(($classesWithAttendance / $totalClasses) * 100, 1) : 0,
            'average_attendance' => $totalStudentRecords > 0 ?
                round(($presentRecords / $totalStudentRecords) * 100, 1) : 0,
            'total_student_records' => $totalStudentRecords,
            'present_records' => $presentRecords,
        ];
    }

    protected function getUpcomingClasses($user): array
    {
        return Timetable::where('user_id', $user->id)
            ->where('schedule_date', '>=', now())
            ->with(['subject', 'batch.course', 'classroom', 'timeSlot'])
            ->orderBy('schedule_date')
            ->orderBy('time_slot_id')
            ->limit(5)
            ->get()
            ->map(function ($class) {
                return [
                    'subject' => $class->subject->name ?? 'Unknown',
                    'course' => $class->batch->course->name ?? 'Unknown',
                    'batch' => $class->batch->name ?? 'Unknown',
                    'classroom' => $class->classroom->name ?? 'TBD',
                    'date' => $class->schedule_date->format('M j, Y'),
                    'time' => $class->timeSlot->start_time ?? 'TBD',
                    'days_until' => $class->schedule_date->diffInDays(now()),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate consecutive days of attendance completion
     */
    private function calculateAttendanceStreak($user): int
    {
        $streak = 0;
        $currentDate = now()->subDay();

        // Check last 30 days for consecutive attendance completion
        for ($i = 0; $i < 30; $i++) {
            $dayClasses = Timetable::where('user_id', $user->id)
                ->whereDate('schedule_date', $currentDate)
                ->count();

            if ($dayClasses === 0) {
                $currentDate->subDay();

                continue; // Skip days with no classes
            }

            $dayCompleted = Timetable::where('user_id', $user->id)
                ->whereDate('schedule_date', $currentDate)
                ->whereHas('attendances')
                ->count();

            if ($dayCompleted === $dayClasses && $dayClasses > 0) {
                $streak++;
            } else {
                break; // Streak broken
            }

            $currentDate->subDay();
        }

        return $streak;
    }
}
