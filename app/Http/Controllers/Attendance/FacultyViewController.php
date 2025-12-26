<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\AnalyticsService;
use App\Models\User;
use App\Models\Batch;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\Attendance\Attendance;
use App\Models\Student; // Added Student model import
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class FacultyViewController extends Controller
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
        $this->middleware('permission:take attendance')->only(['takeAttendance', 'markAttendance']);
        $this->middleware('permission:view attendance')->only(['index', 'reports', 'students']);
    }

    /**
     * Faculty attendance dashboard
     */
    public function index(): View
    {
        $faculty = auth()->user();
        
        // Get today's classes
        $todaysClasses = $this->getTodaysClasses($faculty);
        
        // Get recent attendance statistics
        $stats = $this->getFacultyStats($faculty);
        
        // Get classes that need attendance marking
        $pendingClasses = $this->getPendingClasses($faculty);

        return view('attendance.faculty.index', compact(
            'faculty',
            'todaysClasses',
            'stats',
            'pendingClasses'
        ));
    }

    /**
     * Take attendance for a specific class
     */
    public function takeAttendance(Request $request): View
    {
        $timetableId = $request->get('timetable_id');
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        
        if (!$timetableId) {
            return redirect()->route('attendance.faculty.index')
                ->with('error', 'Timetable ID is required');
        }

        $timetable = Timetable::with(['batch.students', 'subject', 'timeSlot'])
            ->findOrFail($timetableId);

        // Verify faculty assignment
        if ($timetable->user_id !== auth()->id()) {
            abort(403, 'Unauthorized to take attendance for this class');
        }

        // Get existing attendance
        $existingAttendance = Attendance::where('batch_id', $timetable->batch_id)
            ->where('subject_id', $timetable->subject_id)
            ->whereDate('attendance_date', $date)
            ->pluck('status', 'student_id')
            ->toArray();

        return view('attendance.faculty.take-attendance', compact(
            'timetable',
            'date',
            'existingAttendance'
        ));
    }

    /**
     * Mark attendance for students
     */
    public function markAttendance(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'timetable_id' => 'required|exists:timetables,id',
                'attendance_date' => 'required|date',
                'attendance' => 'required|array',
                'attendance.*.student_id' => 'required|exists:students,id',
                'attendance.*.status' => 'required|in:present,absent,late,excused',
                'attendance.*.notes' => 'nullable|string|max:500'
            ]);

            $timetable = Timetable::findOrFail($validated['timetable_id']);
            
            // Verify faculty assignment
            if ($timetable->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to mark attendance for this class'
                ], 403);
            }

            // Prepare attendance data
            $attendanceData = collect($validated['attendance'])->map(function ($item) use ($timetable, $validated) {
                return [
                    'student_id' => $item['student_id'],
                    'batch_id' => $timetable->batch_id,
                    'subject_id' => $timetable->subject_id,
                    'time_slot_id' => $timetable->time_slot_id,
                    'faculty_id' => $timetable->user_id,
                    'attendance_date' => $validated['attendance_date'],
                    'status' => $item['status'],
                    'notes' => $item['notes'] ?? null
                ];
            })->toArray();

            $results = $this->attendanceService->markBulkAttendance($attendanceData);

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'data' => [
                    'created' => count($results['created']),
                    'updated' => count($results['updated']),
                    'total' => count($results['successful'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View faculty attendance reports
     */
    public function reports(Request $request): View
    {
        $faculty = auth()->user();
        $reportType = $request->get('report_type', 'monthly');
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

        // Get faculty's classes and attendance data
        $classAttendance = $this->getFacultyAttendanceData($faculty, $dateFrom, $dateTo);
        
        // Get summary statistics
        $summary = $this->getFacultySummary($faculty, $dateFrom, $dateTo);

        return view('attendance.faculty.reports', compact(
            'faculty',
            'classAttendance',
            'summary',
            'reportType',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * View students under faculty
     */
    public function students(Request $request): View
    {
        $faculty = auth()->user();
        
        // Get batches assigned to this faculty
        $batches = Batch::whereHas('timetables', function ($query) use ($faculty) {
            $query->where('user_id', $faculty->id);
        })->with(['students', 'course'])->get();

        // Get student statistics
        $studentStats = [];
        foreach ($batches as $batch) {
            foreach ($batch->students as $student) {
                $stats = $this->attendanceService->calculateStudentStats($student->id);
                $studentStats[$student->id] = $stats;
            }
        }

        return view('attendance.faculty.students', compact(
            'faculty',
            'batches',
            'studentStats'
        ));
    }

    /**
     * Get student attendance details
     */
    public function getStudentDetails(Request $request, Student $student): JsonResponse
    {
        $faculty = auth()->user();
        
        // Verify faculty can view this student
        if (!$this->canViewStudent($faculty, $student)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this student'
            ], 403);
        }

        $dateFrom = $request->get('date_from', Carbon::now()->subMonths(3));
        $dateTo = $request->get('date_to', Carbon::now());

        $attendances = $this->attendanceService->getStudentAttendance(
            $student->id,
            Carbon::parse($dateFrom),
            Carbon::parse($dateTo)
        );

        $stats = $this->attendanceService->calculateStudentStats(
            $student->id,
            Carbon::parse($dateFrom),
            Carbon::parse($dateTo)
        );

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $student,
                'attendances' => $attendances,
                'stats' => $stats
            ]
        ]);
    }

    /**
     * Get quick class statistics
     */
    public function getClassStats(Request $request): JsonResponse
    {
        $batchId = $request->get('batch_id');
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        if (!$batchId) {
            return response()->json([
                'success' => false,
                'message' => 'Batch ID is required'
            ], 422);
        }

        $summary = $this->attendanceService->getBatchSummary($batchId, Carbon::parse($date));

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Private helper methods
     */
    private function getTodaysClasses(User $faculty): array
    {
        return Timetable::with(['batch', 'subject', 'timeSlot'])
            ->where('user_id', $faculty->id)
            ->whereDate('schedule_date', Carbon::today())
            ->orderBy('time_slot_id')
            ->get()
            ->toArray();
    }

    private function getFacultyStats(User $faculty): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'classes_today' => Timetable::where('user_id', $faculty->id)
                ->whereDate('schedule_date', $today)
                ->count(),
            'classes_this_week' => Timetable::where('user_id', $faculty->id)
                ->whereBetween('schedule_date', [$thisWeek, $thisWeek->copy()->endOfWeek()])
                ->count(),
            'attendance_marked_today' => Attendance::where('faculty_id', $faculty->id)
                ->whereDate('attendance_date', $today)
                ->count(),
            'total_students' => Student::whereHas('batch.timetables', function ($query) use ($faculty) {
                $query->where('user_id', $faculty->id);
            })->distinct()->count()
        ];
    }

    private function getPendingClasses(User $faculty): array
    {
        $today = Carbon::today();
        
        return Timetable::with(['batch', 'subject', 'timeSlot'])
            ->where('user_id', $faculty->id)
            ->whereDate('schedule_date', $today)
            ->whereDoesntHave('attendances', function ($query) use ($today) {
                $query->whereDate('attendance_date', $today);
            })
            ->get()
            ->toArray();
    }

    private function getFacultyAttendanceData(User $faculty, $dateFrom, $dateTo): array
    {
        return Attendance::where('faculty_id', $faculty->id)
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->with(['student', 'batch', 'subject'])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy('batch.name')
            ->toArray();
    }

    private function getFacultySummary(User $faculty, $dateFrom, $dateTo): array
    {
        $attendances = Attendance::where('faculty_id', $faculty->id)
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->get();

        $total = $attendances->count();
        $present = $attendances->whereIn('status', ['present', 'late'])->count();

        return [
            'total_classes_taken' => $total,
            'total_present' => $present,
            'total_absent' => $attendances->where('status', 'absent')->count(),
            'total_late' => $attendances->where('status', 'late')->count(),
            'average_attendance' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            'unique_students' => $attendances->unique('student_id')->count()
        ];
    }

    private function canViewStudent(User $faculty, Student $student): bool
    {
        // Check if faculty teaches any subject to this student's batch
        return Timetable::where('user_id', $faculty->id)
            ->where('batch_id', $student->batch_id)
            ->exists();
    }
}