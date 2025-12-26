<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\AnalyticsService;
use App\Models\Student;
use App\Models\Attendance\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class StudentViewController extends Controller
{
    protected AttendanceService $attendanceService;
    protected AnalyticsService $analyticsService;

    public function __construct(
        AttendanceService $attendanceService,
        AnalyticsService $analyticsService
    ) {
        $this->attendanceService = $attendanceService;
        $this->analyticsService = $analyticsService;
        
        // Apply permissions
        $this->middleware('permission:view attendance');
    }

    /**
     * Student attendance dashboard
     */
    public function dashboard(Request $request): View
    {
        $studentId = $this->getStudentId($request);
        $student = Student::with('batch')->findOrFail($studentId);
        
        // Get attendance statistics
        $stats = $this->attendanceService->calculateStudentStats($studentId);
        
        // Get recent attendance
        $recentAttendance = $this->attendanceService->getStudentAttendance(
            $studentId,
            Carbon::now()->subDays(30)
        );

        // Get calendar data
        $calendarData = $this->getCalendarData($studentId);

        return view('attendance.student.dashboard', compact(
            'student',
            'stats',
            'recentAttendance',
            'calendarData'
        ));
    }

    /**
     * Student attendance calendar view
     */
    public function calendar(Request $request): View
    {
        $studentId = $this->getStudentId($request);
        $student = Student::with('batch')->findOrFail($studentId);
        
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);
        
        $attendances = Attendance::where('student_id', $studentId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->with(['subject', 'faculty'])
            ->get()
            ->keyBy(function($item) {
                return $item->attendance_date->format('Y-m-d');
            });

        return view('attendance.student.calendar', compact(
            'student',
            'attendances',
            'month',
            'year'
        ));
    }

    /**
     * Student attendance reports
     */
    public function reports(Request $request): View
    {
        $studentId = $this->getStudentId($request);
        $student = Student::with('batch')->findOrFail($studentId);
        
        $reportType = $request->get('report_type', 'monthly');
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

        $attendances = $this->attendanceService->getStudentAttendance(
            $studentId,
            Carbon::parse($dateFrom),
            Carbon::parse($dateTo)
        );

        $stats = $this->attendanceService->calculateStudentStats(
            $studentId,
            Carbon::parse($dateFrom),
            Carbon::parse($dateTo)
        );

        return view('attendance.student.reports', compact(
            'student',
            'attendances',
            'stats',
            'reportType',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Get attendance data for AJAX requests
     */
    public function getAttendanceData(Request $request): JsonResponse
    {
        $studentId = $this->getStudentId($request);
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $attendances = $this->attendanceService->getStudentAttendance(
            $studentId,
            $dateFrom ? Carbon::parse($dateFrom) : null,
            $dateTo ? Carbon::parse($dateTo) : null
        );

        $stats = $this->attendanceService->calculateStudentStats(
            $studentId,
            $dateFrom ? Carbon::parse($dateFrom) : null,
            $dateTo ? Carbon::parse($dateTo) : null
        );

        return response()->json([
            'success' => true,
            'data' => [
                'attendances' => $attendances,
                'stats' => $stats
            ]
        ]);
    }

    /**
     * Get calendar events for student
     */
    public function getCalendarEvents(Request $request): JsonResponse
    {
        $studentId = $this->getStudentId($request);
        $start = Carbon::parse($request->get('start'));
        $end = Carbon::parse($request->get('end'));

        $attendances = Attendance::where('student_id', $studentId)
            ->whereBetween('attendance_date', [$start, $end])
            ->with(['subject'])
            ->get();

        $events = $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'title' => $attendance->subject->name ?? 'Class',
                'start' => $attendance->attendance_date->format('Y-m-d'),
                'backgroundColor' => $this->getStatusColor($attendance->status),
                'borderColor' => $this->getStatusColor($attendance->status),
                'extendedProps' => [
                    'status' => $attendance->status,
                    'subject' => $attendance->subject->name ?? 'N/A',
                    'notes' => $attendance->notes
                ]
            ];
        });

        return response()->json($events);
    }

    /**
     * Generate attendance certificate
     */
    public function generateCertificate(Request $request): JsonResponse
    {
        try {
            $studentId = $this->getStudentId($request);
            $student = Student::with('batch')->findOrFail($studentId);
            
            $dateFrom = Carbon::parse($request->get('date_from', Carbon::now()->startOfYear()));
            $dateTo = Carbon::parse($request->get('date_to', Carbon::now()));

            $stats = $this->attendanceService->calculateStudentStats($studentId, $dateFrom, $dateTo);

            // Generate certificate PDF
            $certificateData = [
                'student' => $student,
                'stats' => $stats,
                'date_range' => [
                    'from' => $dateFrom->format('d/m/Y'),
                    'to' => $dateTo->format('d/m/Y')
                ],
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'generated_by' => auth()->user()->name
            ];

            // This would typically generate a PDF
            // For now, return the data
            return response()->json([
                'success' => true,
                'message' => 'Certificate generated successfully',
                'data' => $certificateData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate certificate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Private helper methods
     */
    private function getStudentId(Request $request): int
    {
        // If user is a student, return their own ID
        if (auth()->user()->hasRole('student')) {
            return auth()->user()->student->id;
        }
        
        // Otherwise, get from request parameter
        return $request->get('student_id') ?? $request->route('student');
    }

    private function getCalendarData(int $studentId): array
    {
        $currentMonth = Carbon::now();
        $attendances = Attendance::where('student_id', $studentId)
            ->whereYear('attendance_date', $currentMonth->year)
            ->whereMonth('attendance_date', $currentMonth->month)
            ->get();

        $calendarData = [];
        foreach ($attendances as $attendance) {
            $date = $attendance->attendance_date->format('Y-m-d');
            $calendarData[$date] = [
                'status' => $attendance->status,
                'color' => $this->getStatusColor($attendance->status),
                'subject' => $attendance->subject->name ?? 'Class'
            ];
        }

        return $calendarData;
    }

    private function getStatusColor(string $status): string
    {
        return match($status) {
            'present' => '#28a745',
            'absent' => '#dc3545',
            'late' => '#ffc107',
            'excused' => '#17a2b8',
            default => '#6c757d'
        };
    }
}