<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\{DashboardService, DashboardDataService};
use App\Models\{Timetable, Attendance};

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
            'pending_tasks' => $this->getPendingTasks($user)
        ];

        return view('faculty.dashboard.index', $data);
    }

    public function myClasses()
    {
        $user = auth()->user();
        
        $classes = Timetable::where('user_id', $user->id)
            ->with(['subject', 'batch.course', 'classroom', 'timeSlot'])
            ->orderBy('schedule_date')
            ->orderBy('time_slot_id')
            ->paginate(20);

        return view('faculty.dashboard.my-classes', compact('classes'));
    }

    public function attendanceOverview()
    {
        $user = auth()->user();
        
        $data = [
            'attendance_statistics' => $this->getAttendanceStatistics($user),
            'class_wise_attendance' => $this->getClassWiseAttendance($user),
            'monthly_trends' => $this->getMonthlyAttendanceTrends($user),
            'student_performance' => $this->getStudentAttendancePerformance($user)
        ];

        return view('faculty.dashboard.attendance-overview', $data);
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
                    'student_count' => $class->batch->students()->count()
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
            'present_records' => $presentRecords
        ];
    }

    protected function getMyStudentPerformance($user): array
    {
        $myClasses = Timetable::where('user_id', $user->id)->pluck('id');
        
        $attendanceData = Attendance::whereIn('timetable_id', $myClasses)
            ->select('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        $performanceData = [];
        foreach ($attendanceData as $studentId => $records) {
            $total = $records->count();
            $present = $records->where('status', 'present')->count();
            $percentage = $total > 0 ? ($present / $total) * 100 : 0;
            
            $performanceData[] = [
                'student_id' => $studentId,
                'attendance_percentage' => round($percentage, 1),
                'total_classes' => $total,
                'classes_attended' => $present
            ];
        }

        return [
            'total_students' => count($performanceData),
            'average_performance' => count($performanceData) > 0 ? 
                round(collect($performanceData)->avg('attendance_percentage'), 1) : 0,
            'top_performers' => collect($performanceData)
                ->sortByDesc('attendance_percentage')
                ->take(5)
                ->values()
                ->toArray(),
            'low_performers' => collect($performanceData)
                ->where('attendance_percentage', '<', 75)
                ->count()
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
                    'days_until' => $class->schedule_date->diffInDays(now())
                ];
            })
            ->toArray();
    }

    protected function getPendingTasks($user): array
    {
        // Classes today that need attendance marking
        $todayClasses = Timetable::where('user_id', $user->id)
            ->whereDate('schedule_date', today())
            ->whereDoesntHave('attendances')
            ->count();

        // Classes this week that still need attendance
        $weeklyPendingAttendance = Timetable::where('user_id', $user->id)
            ->whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereDoesntHave('attendances')
            ->where('schedule_date', '<=', today())
            ->count();

        // Overdue attendance (classes from previous days without attendance)
        $overdueAttendance = Timetable::where('user_id', $user->id)
            ->where('schedule_date', '<', today())
            ->whereDoesntHave('attendances')
            ->count();

        // Upcoming classes today
        $upcomingToday = Timetable::where('user_id', $user->id)
            ->whereDate('schedule_date', today())
            ->where('schedule_date', '>', now())
            ->count();

        return [
            'attendance_pending' => $todayClasses,
            'weekly_pending_attendance' => $weeklyPendingAttendance,
            'overdue_attendance' => $overdueAttendance,
            'upcoming_classes_today' => $upcomingToday,
            'priority_level' => $this->calculateTaskPriority($overdueAttendance, $todayClasses),
            'total_pending' => $todayClasses + $overdueAttendance
        ];
    }

    protected function getAttendanceStatistics($user): array
    {
        // Get all classes for current academic year
        $allClasses = Timetable::where('user_id', $user->id)
            ->where('schedule_date', '>=', now()->startOfYear())
            ->get();
        
        $totalClasses = $allClasses->count();
        
        // Classes with attendance taken
        $completedClasses = $allClasses->filter(function ($class) {
            return Attendance::where('batch_id', $class->batch_id)
                ->where('subject_id', $class->subject_id)
                ->whereDate('attendance_date', $class->schedule_date)
                ->exists();
        })->count();

        // This month's statistics
        $thisMonthClasses = Timetable::where('user_id', $user->id)
            ->whereMonth('schedule_date', now()->month)
            ->whereYear('schedule_date', now()->year)
            ->count();

        $thisMonthCompleted = Timetable::where('user_id', $user->id)
            ->whereMonth('schedule_date', now()->month)
            ->whereYear('schedule_date', now()->year)
            ->whereHas('attendances')
            ->count();

        // Weekly trend (last 4 weeks)
        $weeklyTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            
            $weekClasses = Timetable::where('user_id', $user->id)
                ->whereBetween('schedule_date', [$weekStart, $weekEnd])
                ->count();
            
            $weekCompleted = Timetable::where('user_id', $user->id)
                ->whereBetween('schedule_date', [$weekStart, $weekEnd])
                ->whereHas('attendances')
                ->count();
            
            $weeklyTrend[] = [
                'week' => $weekStart->format('M j'),
                'total' => $weekClasses,
                'completed' => $weekCompleted,
                'percentage' => $weekClasses > 0 ? round(($weekCompleted / $weekClasses) * 100, 1) : 0
            ];
        }

        $completionPercentage = $totalClasses > 0 ? round(($completedClasses / $totalClasses) * 100, 1) : 0;
        $monthlyPercentage = $thisMonthClasses > 0 ? round(($thisMonthCompleted / $thisMonthClasses) * 100, 1) : 0;

        return [
            'total_classes_assigned' => $totalClasses,
            'attendance_completed' => $completedClasses,
            'completion_percentage' => $completionPercentage,
            'pending_attendance' => $totalClasses - $completedClasses,
            'monthly_classes' => $thisMonthClasses,
            'monthly_completed' => $thisMonthCompleted,
            'monthly_percentage' => $monthlyPercentage,
            'weekly_trend' => $weeklyTrend,
            'performance_grade' => $this->getAttendanceGrade($completionPercentage),
            'improvement_needed' => $completionPercentage < 80,
            'streak_days' => $this->calculateAttendanceStreak($user)
        ];
    }

    /**
     * Calculate task priority based on overdue and pending items
     */
    private function calculateTaskPriority($overdueCount, $pendingCount): string
    {
        if ($overdueCount > 5) {
            return 'critical';
        } elseif ($overdueCount > 2 || $pendingCount > 10) {
            return 'high';
        } elseif ($overdueCount > 0 || $pendingCount > 5) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Get attendance performance grade
     */
    private function getAttendanceGrade($percentage): string
    {
        if ($percentage >= 95) {
            return 'A+';
        } elseif ($percentage >= 90) {
            return 'A';
        } elseif ($percentage >= 85) {
            return 'B+';
        } elseif ($percentage >= 80) {
            return 'B';
        } elseif ($percentage >= 75) {
            return 'C+';
        } elseif ($percentage >= 70) {
            return 'C';
        } elseif ($percentage >= 60) {
            return 'D';
        }
        return 'F';
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