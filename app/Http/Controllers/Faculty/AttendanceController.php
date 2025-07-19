<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Timetable;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Show the form for taking attendance for a specific class.
     */
    public function create(Timetable $timetable)
    {
        // Ensure the logged-in faculty is the one assigned to this class
        if ($timetable->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to take attendance for this class.');
        }

        // Eager load relationships to prevent extra database queries
        $timetable->load('batch.students', 'subject', 'timeSlot');

        // Get all students for the batch associated with this timetable entry
        $students = $timetable->batch->students()->orderBy('name')->get();

        // Check for any attendance already recorded for this specific class session
        $existing_attendance = Attendance::where('attendance_date', $timetable->schedule_date)
            ->where('batch_id', $timetable->batch_id)
            ->get();

        return view('faculty.attendance.create', compact('timetable', 'students', 'existing_attendance'));
    }

    /**
     * Store the submitted attendance data in the database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent,late,excused',
            'batch_id' => 'required|exists:batches,id',
            'attendance_date' => 'required|date',
        ]);

        $attendanceRecords = [];
        $absentStudents = [];
        $lateStudents = [];
        $totalStudents = count($request->attendance);

        foreach ($request->attendance as $studentId => $status) {
            // Use updateOrCreate to either create a new record or update an existing one
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

            $attendanceRecords[] = $attendance;

            // Track different attendance statuses
            if ($status === 'absent') {
                $absentStudents[] = $attendance;
            } elseif ($status === 'late') {
                $lateStudents[] = $attendance;
            }
        }

        // 🔔 ATTENDANCE SUMMARY NOTIFICATION
        $this->sendAttendanceSummaryNotification($request, $totalStudents, $absentStudents, $lateStudents);

        // 🔔 CHECK FOR LOW ATTENDANCE PATTERNS
        $this->checkLowAttendancePatterns($absentStudents);

        // 🔔 CHECK FOR CONSECUTIVE ABSENCES
        $this->checkConsecutiveAbsences($absentStudents);

        return redirect()->route('dashboard')->with('success', 'Attendance submitted successfully.');
    }

    /**
     * Send attendance summary notification to admins
     */
    private function sendAttendanceSummaryNotification($request, $totalStudents, $absentStudents, $lateStudents)
    {
        $presentCount = $totalStudents - count($absentStudents) - count($lateStudents);
        $absentCount = count($absentStudents);
        $lateCount = count($lateStudents);
        $attendancePercentage = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0;

        // Only send notification if attendance is concerning (below 80%)
        if ($attendancePercentage < 80 || $absentCount > 5) {
            $this->notificationService->send([
                'title' => 'Low Class Attendance Alert',
                'message' => "Class attendance is {$attendancePercentage}% ({$presentCount}/{$totalStudents} present, {$absentCount} absent)",
                'type' => $attendancePercentage < 60 ? 'error' : 'warning',
                'category' => 'attendance',
                'priority' => $attendancePercentage < 60 ? 'high' : 'normal',
                'roles' => ['super-admin', 'college-admin'],
                'data' => [
                    'attendance_date' => $request->attendance_date,
                    'batch_id' => $request->batch_id,
                    'faculty_id' => Auth::id(),
                    'total_students' => $totalStudents,
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                    'late_count' => $lateCount,
                    'attendance_percentage' => $attendancePercentage,
                ]
            ]);
        }
    }

    /**
     * Check for students with low attendance patterns
     */
    private function checkLowAttendancePatterns($absentStudents)
    {
        $minimumAttendance = (int) setting('minimum_attendance_percentage', 75);

        foreach ($absentStudents as $attendance) {
            $student = $attendance->student;
            $attendancePercentage = $this->calculateAttendancePercentage($student);

            if ($attendancePercentage < $minimumAttendance) {
                $this->notificationService->sendAcademicNotification('low_attendance', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'attendance_percentage' => round($attendancePercentage, 2), // Fixed: was missing this key
                    'minimum_required' => $minimumAttendance,
                    'enrollment_number' => $student->enrollment_number,
                    'batch_name' => $student->batch->name ?? 'Unknown',
                ]);
            }
        }
    }

    /**
     * Check for consecutive absences (red flag)
     */
    private function checkConsecutiveAbsences($absentStudents)
    {
        foreach ($absentStudents as $attendance) {
            $student = $attendance->student;
            
            // Check for consecutive absences in the last 7 days
            $recentAbsences = Attendance::where('student_id', $student->id)
                ->where('attendance_date', '>=', now()->subDays(7))
                ->where('status', 'absent')
                ->orderBy('attendance_date', 'desc')
                ->get();

            if ($recentAbsences->count() >= 3) {
                $this->notificationService->send([
                    'title' => 'URGENT: Consecutive Absences Alert',
                    'message' => "{$student->name} has {$recentAbsences->count()} absences in the last 7 days",
                    'type' => 'error',
                    'category' => 'attendance',
                    'priority' => 'urgent',
                    'roles' => ['super-admin', 'college-admin'],
                    'requires_action' => true,
                    'action_url' => route('admin.students.show', $student->id),
                    'action_text' => 'Review Student',
                    'data' => [
                        'student_id' => $student->id,
                        'consecutive_absences' => $recentAbsences->count(),
                        'last_present_date' => $this->getLastPresentDate($student),
                        'requires_intervention' => true,
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

    /**
     * Get the last date the student was present
     */
    private function getLastPresentDate($student)
    {
        $lastPresent = Attendance::where('student_id', $student->id)
            ->where('status', 'present')
            ->orderBy('attendance_date', 'desc')
            ->first();

        return $lastPresent ? $lastPresent->attendance_date : null;
    }

    /**
     * Get attendance report for faculty
     */
    public function getAttendanceReport(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        // Ensure faculty can only view their batch's attendance
        $attendance = Attendance::where('batch_id', $request->batch_id)
            ->where('faculty_id', Auth::id())
            ->whereBetween('attendance_date', [$request->date_from, $request->date_to])
            ->with(['student', 'batch'])
            ->orderBy('attendance_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendance->map(function ($att) {
                return [
                    'student_name' => $att->student->name,
                    'enrollment_number' => $att->student->enrollment_number,
                    'date' => $att->attendance_date,
                    'status' => $att->status,
                    'batch' => $att->batch->name,
                ];
            })
        ]);
    }

    /**
     * Bulk update attendance status
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'attendance_ids' => 'required|array',
            'attendance_ids.*' => 'exists:attendances,id',
            'status' => 'required|in:present,absent,late,excused',
        ]);

        try {
            $updated = 0;
            
            foreach ($request->attendance_ids as $attendanceId) {
                $attendance = Attendance::where('id', $attendanceId)
                    ->where('faculty_id', Auth::id()) // Ensure faculty can only update their records
                    ->first();
                    
                if ($attendance) {
                    $attendance->update(['status' => $request->status]);
                    $updated++;
                }
            }

            // Send notification for bulk changes
            $this->notificationService->send([
                'title' => 'Bulk Attendance Update',
                'message' => "Faculty updated {$updated} attendance records to '{$request->status}'",
                'type' => 'info',
                'category' => 'attendance',
                'priority' => 'low',
                'roles' => ['super-admin', 'college-admin'],
                'data' => [
                    'faculty_id' => Auth::id(),
                    'updated_count' => $updated,
                    'new_status' => $request->status,
                    'timestamp' => now()->toISOString(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} attendance records",
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}