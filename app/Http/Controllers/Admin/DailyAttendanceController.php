<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Student;
use App\Services\NotificationService; // ADD THIS IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailyAttendanceController extends Controller
{
    protected $notificationService; // ADD THIS PROPERTY

    // ADD CONSTRUCTOR FOR DEPENDENCY INJECTION
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function show(Request $request)
    {
        $batches = Batch::with('course')->get();
        $students = null;
        $selectedBatch = null;

        if ($request->filled('batch_id') && $request->filled('date')) {
            $selectedBatch = Batch::findOrFail($request->batch_id);
            $students = Student::where('batch_id', $selectedBatch->id)->orderBy('name')->get();

            // Get existing attendance for this day
            $existing_attendance = Attendance::where('batch_id', $selectedBatch->id)
                ->where('attendance_date', $request->date)
                ->pluck('status', 'student_id');

            // Add the existing status to each student object
            foreach($students as $student) {
                $student->todays_attendance_status = $existing_attendance[$student->id] ?? null;
            }
        }

        return view('admin.daily_attendance.show', compact('batches', 'students', 'selectedBatch'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent',
            'batch_id' => 'required|exists:batches,id',
            'attendance_date' => 'required|date',
        ]);

        $batch = Batch::with('course')->find($request->batch_id);
        $absentStudents = [];
        $presentStudents = [];
        $totalStudents = count($request->attendance);

        foreach ($request->attendance as $studentId => $status) {
            $attendance = Attendance::updateOrCreate(
                [
                    'student_id'      => $studentId,
                    'attendance_date' => $request->attendance_date,
                ],
                [
                    'batch_id'        => $request->batch_id,
                    'faculty_id'      => Auth::id(),
                    'status'          => $status,
                ]
            );

            if ($status === 'absent') {
                $absentStudents[] = $attendance;
            } else {
                $presentStudents[] = $attendance;
            }
        }

        // 🔔 BULK ATTENDANCE SUMMARY
        $this->sendBulkAttendanceSummary($request, $batch, $totalStudents, $absentStudents, $presentStudents);

        // 🔔 CHECK FOR ATTENDANCE PATTERNS
        $this->checkBulkAttendancePatterns($absentStudents);

        return redirect()->back()->with('success', 'Attendance for ' . $request->attendance_date . ' saved successfully.');
    }

    /**
     * Send bulk attendance summary
     */
    private function sendBulkAttendanceSummary($request, $batch, $totalStudents, $absentStudents, $presentStudents)
    {
        $absentCount = count($absentStudents);
        $presentCount = count($presentStudents);
        $attendancePercentage = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0;

        $this->notificationService->send([
            'title' => 'Bulk Attendance Recorded',
            'message' => "Attendance recorded for {$batch->name}: {$attendancePercentage}% ({$presentCount}/{$totalStudents} present)",
            'type' => $attendancePercentage < 70 ? 'warning' : 'success',
            'category' => 'attendance',
            'priority' => 'normal',
            'roles' => ['super-admin', 'college-admin'],
            'data' => [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'attendance_date' => $request->attendance_date,
                'total_students' => $totalStudents,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'attendance_percentage' => $attendancePercentage,
                'recorded_by' => auth()->user()->name,
            ]
        ]);
    }

    /**
     * Check for concerning attendance patterns in bulk data
     */
    private function checkBulkAttendancePatterns($absentStudents)
    {
        $minimumAttendance = (int) setting('minimum_attendance_percentage', 75);

        foreach ($absentStudents as $attendance) {
            $student = $attendance->student;
            
            // Check overall attendance percentage
            $attendancePercentage = $this->calculateAttendancePercentage($student);
            
            if ($attendancePercentage < $minimumAttendance) {
                $this->notificationService->sendAcademicNotification('low_attendance', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'attendance_percentage' => round($attendancePercentage, 2),
                    'minimum_required' => $minimumAttendance,
                    'enrollment_number' => $student->enrollment_number,
                    'batch_name' => $student->batch->name ?? 'Unknown',
                    'recorded_via' => 'bulk_entry',
                ]);
            }

            // Check for recent absence pattern
            $recentAbsences = Attendance::where('student_id', $student->id)
                ->where('attendance_date', '>=', now()->subDays(10))
                ->where('status', 'absent')
                ->count();

            if ($recentAbsences >= 4) {
                $this->notificationService->send([
                    'title' => 'Frequent Absences Alert',
                    'message' => "{$student->name} has {$recentAbsences} absences in the last 10 days",
                    'type' => 'warning',
                    'category' => 'attendance',
                    'priority' => 'high',
                    'roles' => ['super-admin', 'college-admin'],
                    'requires_action' => true,
                    'action_url' => route('admin.students.show', $student->id),
                    'action_text' => 'Review Student',
                    'data' => [
                        'student_id' => $student->id,
                        'recent_absences' => $recentAbsences,
                        'period' => '10 days',
                        'overall_percentage' => round($attendancePercentage, 2),
                    ]
                ]);
            }
        }
    }

    /**
     * Calculate attendance percentage for a student
     */
    private function calculateAttendancePercentage($student)
    {
        $totalClasses = Attendance::where('student_id', $student->id)->count();
        $presentClasses = Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])
            ->count();
            
        return $totalClasses > 0 ? ($presentClasses / $totalClasses) * 100 : 100;
    }
}
