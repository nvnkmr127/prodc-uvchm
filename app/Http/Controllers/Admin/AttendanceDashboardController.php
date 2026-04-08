<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get today's date or requested date
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        // Get filters
        $batchId = $request->get('batch_id');
        $courseId = $request->get('course_id');

        // Get today's attendance statistics
        $todayStats = $this->getTodayStats($selectedDate, $batchId, $courseId);

        // Get absent students with contact information
        $absentStudents = $this->getAbsentStudents($selectedDate, $batchId, $courseId);

        // Get recent attendance activity
        $recentActivity = $this->getRecentActivity($selectedDate, $batchId, $courseId);

        // Get filter options
        $batches = Batch::with('course')->orderBy('name')->get();
        $courses = Course::orderBy('name')->get();

        // Weekly trend data
        $weeklyTrend = $this->getWeeklyTrend($selectedDate);

        return view('admin.attendance.dashboard', compact(
            'todayStats',
            'absentStudents',
            'recentActivity',
            'batches',
            'courses',
            'selectedDate',
            'batchId',
            'courseId',
            'weeklyTrend'
        ));
    }

    public function getAbsentStudentsAjax(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);
        $batchId = $request->get('batch_id');
        $courseId = $request->get('course_id');

        $absentStudents = $this->getAbsentStudents($selectedDate, $batchId, $courseId);

        return response()->json([
            'success' => true,
            'data' => $absentStudents,
            'count' => $absentStudents->count(),
            'last_updated' => now()->format('H:i:s'),
        ]);
    }

    public function getRecentActivityAjax(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);
        $batchId = $request->get('batch_id');
        $courseId = $request->get('course_id');

        $recentActivity = $this->getRecentActivity($selectedDate, $batchId, $courseId);

        return response()->json([
            'success' => true,
            'data' => $recentActivity,
            'count' => $recentActivity->count(),
            'last_updated' => now()->format('H:i:s'),
        ]);
    }

    public function getTodayStatsAjax(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);
        $batchId = $request->get('batch_id');
        $courseId = $request->get('course_id');

        $todayStats = $this->getTodayStats($selectedDate, $batchId, $courseId);

        return response()->json([
            'success' => true,
            'data' => $todayStats,
            'last_updated' => now()->format('H:i:s'),
        ]);
    }

    private function getTodayStats($date, $batchId = null, $courseId = null)
    {
        // Base query for students
        $studentsQuery = Student::where('status', 'active')
            ->whereHas('attendances', function ($query) {
                $query->whereIn('status', ['present', 'late']);
            });

        if ($batchId) {
            $studentsQuery->where('batch_id', $batchId);
        }

        if ($courseId) {
            $studentsQuery->whereHas('batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $totalStudents = $studentsQuery->count();

        // Get attendance for the date
        $attendanceQuery = Attendance::whereDate('attendance_date', $date);

        if ($batchId) {
            $attendanceQuery->whereHas('student', function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            });
        }

        if ($courseId) {
            $attendanceQuery->whereHas('student.batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $attendanceStats = $attendanceQuery->select([
            DB::raw('COUNT(*) as total_marked'),
            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present'),
            DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
            DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent'),
            DB::raw('SUM(CASE WHEN status = "excused" THEN 1 ELSE 0 END) as excused'),
        ])->first();

        $present = ($attendanceStats->present ?? 0) + ($attendanceStats->late ?? 0);
        $absent = $totalStudents - $present;
        $percentage = $totalStudents > 0 ? round(($present / $totalStudents) * 100, 1) : 0;

        return [
            'students' => [
                'total' => $totalStudents,
                'present' => $present,
                'absent' => $absent,
                'late' => $attendanceStats->late ?? 0,
                'excused' => $attendanceStats->excused ?? 0,
                'percentage' => $percentage,
            ],
        ];
    }

    private function getAbsentStudents($date, $batchId = null, $courseId = null)
    {
        // Get all active students
        $studentsQuery = Student::where('status', 'active')
            ->with(['batch.course']);

        if ($batchId) {
            $studentsQuery->where('batch_id', $batchId);
        }

        if ($courseId) {
            $studentsQuery->whereHas('batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $allStudents = $studentsQuery->get();

        // Get students who have marked attendance today
        $attendanceQuery = Attendance::whereDate('attendance_date', $date)
            ->whereIn('status', ['present', 'late', 'excused']);

        if ($batchId) {
            $attendanceQuery->whereHas('student', function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            });
        }

        if ($courseId) {
            $attendanceQuery->whereHas('student.batch', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        $presentStudentIds = $attendanceQuery->pluck('student_id')->toArray();

        // Filter absent students
        $absentStudents = $allStudents->whereNotIn('id', $presentStudentIds);

        return $absentStudents->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'student_mobile' => $student->student_mobile,
                'father_mobile' => $student->father_mobile,
                'father_name' => $student->father_name,
                'batch_name' => $student->batch->name ?? 'N/A',
                'course_name' => $student->batch->course->name ?? 'N/A',
                'last_attendance' => $this->getLastAttendanceDate($student->id),
            ];
        });
    }

    private function getRecentActivity($date, $batchId = null, $courseId = null, $limit = 20)
    {
        $query = Attendance::with(['student.batch.course', 'faculty'])
            ->whereDate('attendance_date', $date)
            ->orderBy('marked_at', 'desc');

        if ($batchId) {
            $query->whereHas('student', function ($q) use ($batchId) {
                $q->where('batch_id', $batchId);
            });
        }

        if ($courseId) {
            $query->whereHas('student.batch', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        return $query->limit($limit)->get()->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'student_name' => $attendance->student->name,
                'enrollment_number' => $attendance->student->enrollment_number,
                'batch_name' => $attendance->student->batch->name ?? 'N/A',
                'course_name' => $attendance->student->batch->course->name ?? 'N/A',
                'status' => $attendance->status,
                'check_in_time' => $attendance->check_in_time,
                'check_out_time' => $attendance->check_out_time,
                'marked_at' => $attendance->marked_at,
                'marked_by' => $attendance->faculty->name ?? 'System',
                'late_minutes' => $attendance->late_minutes,
                'notes' => $attendance->notes,
            ];
        });
    }

    private function getLastAttendanceDate($studentId)
    {
        $lastAttendance = Attendance::where('student_id', $studentId)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('attendance_date', 'desc')
            ->first();

        return $lastAttendance ? $lastAttendance->attendance_date : null;
    }

    private function getWeeklyTrend($selectedDate)
    {
        $endDate = $selectedDate;
        $startDate = $selectedDate->copy()->subDays(6);

        $trend = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayStats = $this->getTodayStats($date);
            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'percentage' => $dayStats['students']['percentage'],
            ];
        }

        return $trend;
    }

    public function markStudentPresent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
        ]);

        $student = Student::findOrFail($request->student_id);
        $date = $request->date;

        // Check if attendance already exists
        $existingAttendance = Attendance::where('student_id', $student->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($existingAttendance) {
            $existingAttendance->update([
                'status' => 'present',
                'marked_at' => now(),
                'marked_by' => auth()->id(),
                'notes' => 'Marked from dashboard',
            ]);
        } else {
            Attendance::create([
                'student_id' => $student->id,
                'batch_id' => $student->batch_id,
                'subject_id' => 1, // Default subject
                'faculty_id' => auth()->id(),
                'attendance_date' => $date,
                'status' => 'present',
                'check_in_time' => now()->format('H:i:s'),
                'marked_at' => now(),
                'marked_by' => auth()->id(),
                'notes' => 'Marked from dashboard',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Student marked as present successfully',
        ]);
    }

    public function bulkMarkPresent(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'date' => 'required|date',
        ]);

        $date = $request->date;
        $markedCount = 0;

        foreach ($request->student_ids as $studentId) {
            $student = Student::find($studentId);

            $existingAttendance = Attendance::where('student_id', $studentId)
                ->whereDate('attendance_date', $date)
                ->first();

            if ($existingAttendance) {
                $existingAttendance->update([
                    'status' => 'present',
                    'marked_at' => now(),
                    'marked_by' => auth()->id(),
                    'notes' => 'Bulk marked from dashboard',
                ]);
            } else {
                Attendance::create([
                    'student_id' => $studentId,
                    'batch_id' => $student->batch_id,
                    'subject_id' => 1,
                    'faculty_id' => auth()->id(),
                    'attendance_date' => $date,
                    'status' => 'present',
                    'check_in_time' => now()->format('H:i:s'),
                    'marked_at' => now(),
                    'marked_by' => auth()->id(),
                    'notes' => 'Bulk marked from dashboard',
                ]);
            }
            $markedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$markedCount} students marked as present successfully",
        ]);
    }

    /**
     * Export absent students list
     */
    public function exportAbsentStudents(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);
        $batchId = $request->get('batch_id');
        $courseId = $request->get('course_id');

        $absentStudents = $this->getAbsentStudents($selectedDate, $batchId, $courseId);

        if ($request->get('export') === 'csv') {
            return $this->exportAsCSV($absentStudents, $selectedDate);
        }

        return response()->json(['error' => 'Invalid export format'], 400);
    }

    private function exportAsCSV($absentStudents, $date)
    {
        $filename = 'absent_students_'.$date->format('Y_m_d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($absentStudents) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Name',
                'Enrollment Number',
                'Batch',
                'Course',
                'Student Mobile',
                'Father Mobile',
                'Father Name',
                'Last Present Date',
            ]);

            // CSV data
            foreach ($absentStudents as $student) {
                fputcsv($file, [
                    $student['name'],
                    $student['enrollment_number'],
                    $student['batch_name'],
                    $student['course_name'],
                    $student['student_mobile'] ?? '',
                    $student['father_mobile'] ?? '',
                    $student['father_name'] ?? '',
                    $student['last_attendance'] ?? 'Never',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get attendance alerts for dashboard
     */
    public function getAttendanceAlerts(Request $request)
    {
        $alerts = [];

        // Students with consecutive absences
        $consecutiveAbsents = Student::whereHas('attendances', function ($query) {
            $query->where('status', 'absent')
                ->where('attendance_date', '>=', Carbon::now()->subDays(3))
                ->groupBy('student_id')
                ->havingRaw('COUNT(*) >= 3');
        })->with('batch.course')->limit(10)->get();

        foreach ($consecutiveAbsents as $student) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fas fa-exclamation-triangle',
                'title' => 'Consecutive Absences',
                'message' => "{$student->name} has been absent for 3+ consecutive days",
                'student_id' => $student->id,
                'action_url' => route('admin.students.show', $student->id),
            ];
        }

        // Students with low attendance percentage
        $lowAttendanceStudents = Student::where('status', 'active')
            ->whereHas('attendances', function ($query) {
                $query->where('attendance_date', '>=', Carbon::now()->subDays(30));
            })
            ->with('batch.course')
            ->get()
            ->filter(function ($student) {
                $totalDays = $student->attendances()
                    ->where('attendance_date', '>=', Carbon::now()->subDays(30))
                    ->count();
                $presentDays = $student->attendances()
                    ->where('attendance_date', '>=', Carbon::now()->subDays(30))
                    ->whereIn('status', ['present', 'late'])
                    ->count();

                $percentage = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 100;

                return $percentage < 75;
            });

        foreach ($lowAttendanceStudents as $student) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fas fa-chart-line',
                'title' => 'Low Attendance',
                'message' => "{$student->name} has attendance below 75% this month",
                'student_id' => $student->id,
                'action_url' => route('admin.students.show', $student->id),
            ];
        }

        return response()->json([
            'success' => true,
            'alerts' => array_slice($alerts, 0, 5), // Limit to 5 alerts
        ]);
    }

    /**
     * Get batch-wise attendance summary
     */
    public function getBatchWiseSummary(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        $batches = Batch::with('course')
            ->withCount([
                'students as total_students' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->get()
            ->map(function ($batch) use ($selectedDate) {
                $presentCount = Attendance::whereDate('attendance_date', $selectedDate)
                    ->whereHas('student', function ($query) use ($batch) {
                        $query->where('batch_id', $batch->id);
                    })
                    ->whereIn('status', ['present', 'late'])
                    ->count();

                $percentage = $batch->total_students > 0
                    ? round(($presentCount / $batch->total_students) * 100, 1)
                    : 0;

                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'course_name' => $batch->course->name ?? 'N/A',
                    'total_students' => $batch->total_students,
                    'present_count' => $presentCount,
                    'absent_count' => $batch->total_students - $presentCount,
                    'attendance_percentage' => $percentage,
                    'status' => $percentage >= 80 ? 'good' : ($percentage >= 60 ? 'average' : 'poor'),
                ];
            })
            ->sortByDesc('attendance_percentage');

        return response()->json([
            'success' => true,
            'batches' => $batches->values()->all(),
        ]);
    }

    /**
     * Send SMS notification to absent students
     */
    public function sendAbsentNotifications(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'message_template' => 'required|string',
        ]);

        $students = Student::whereIn('id', $request->student_ids)->get();
        $sentCount = 0;
        $errors = [];

        foreach ($students as $student) {
            try {
                // Replace placeholders in message
                $message = str_replace([
                    '{student_name}',
                    '{date}',
                    '{college_name}',
                ], [
                    $student->name,
                    Carbon::today()->format('d/m/Y'),
                    config('app.name', 'College'),
                ], $request->message_template);

                // Send to student mobile
                if ($student->student_mobile) {
                    $this->sendSMS($student->student_mobile, $message);
                    $sentCount++;
                }

                // Send to father mobile if requested
                if ($request->get('send_to_father') && $student->father_mobile) {
                    $fatherMessage = str_replace($student->name, $student->name.' (your child)', $message);
                    $this->sendSMS($student->father_mobile, $fatherMessage);
                    $sentCount++;
                }

            } catch (\Exception $e) {
                $errors[] = "Failed to send SMS to {$student->name}: ".$e->getMessage();
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'sent_count' => $sentCount,
            'errors' => $errors,
            'message' => $sentCount > 0 ? "SMS sent to {$sentCount} contacts" : 'No SMS sent',
        ]);
    }

    private function sendSMS($mobile, $message)
    {
        // Implement your SMS sending logic here
        // This could be integration with services like Twilio, MSG91, etc.

        // Example placeholder - replace with actual SMS service
        \Log::info("SMS to {$mobile}: {$message}");

        // You can integrate with your preferred SMS service here
        // For example, if using MSG91:
        /*
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.msg91.com/api/sendhttp.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query([
                'authkey' => config('services.msg91.auth_key'),
                'mobiles' => $mobile,
                'message' => $message,
                'sender' => config('services.msg91.sender_id'),
                'route' => '4'
            ])
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        if (!$response) {
            throw new \Exception('SMS service unavailable');
        }
        */
    }

    /**
     * Get attendance heatmap data for calendar view
     */
    public function getAttendanceHeatmap(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month.'-01');
        $endDate = $startDate->copy()->endOfMonth();

        $batchId = $request->get('batch_id');
        $courseId = $request->get('course_id');

        $heatmapData = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            if ($date->isWeekday()) { // Only include weekdays
                $stats = $this->getTodayStats($date, $batchId, $courseId);

                $heatmapData[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $date->format('j'),
                    'weekday' => $date->format('D'),
                    'percentage' => $stats['students']['percentage'],
                    'present' => $stats['students']['present'],
                    'total' => $stats['students']['total'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'heatmap_data' => $heatmapData,
            'month' => $startDate->format('F Y'),
        ]);
    }

    /**
     * Generate QR code for quick attendance marking
     */
    public function generateAttendanceQR(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $batchId = $request->get('batch_id');

        // Create QR code data
        $qrData = [
            'action' => 'mark_attendance',
            'date' => $date,
            'batch_id' => $batchId,
            'timestamp' => time(),
            'token' => csrf_token(),
        ];

        $qrString = encrypt(json_encode($qrData));
        $qrUrl = route('admin.attendance.qr.scan', ['data' => $qrString]);

        return response()->json([
            'success' => true,
            'qr_data' => $qrString,
            'qr_url' => $qrUrl,
            'expires_at' => Carbon::now()->addHours(2)->format('H:i'),
        ]);
    }

    /**
     * Process QR code scan for attendance
     */
    public function processQRScan(Request $request)
    {
        try {
            $encryptedData = $request->get('data');
            $qrData = json_decode(decrypt($encryptedData), true);

            // Validate QR code
            if (! $qrData || $qrData['action'] !== 'mark_attendance') {
                throw new \Exception('Invalid QR code');
            }

            // Check if QR code is not expired (2 hours)
            if (time() - $qrData['timestamp'] > 7200) {
                throw new \Exception('QR code has expired');
            }

            $date = $qrData['date'];
            $batchId = $qrData['batch_id'];

            // Get batch students
            $batch = Batch::with('students')->findOrFail($batchId);

            return view('admin.attendance.qr_scan', compact('batch', 'date', 'qrData'));

        } catch (\Exception $e) {
            return redirect()->route('admin.attendance.dashboard')
                ->with('error', 'Invalid or expired QR code: '.$e->getMessage());
        }
    }

    /**
     * Get student attendance history
     */
    public function getStudentAttendanceHistory(Request $request, $studentId)
    {
        $student = Student::with('batch.course')->findOrFail($studentId);
        $days = $request->get('days', 30);

        $attendance = Attendance::where('student_id', $studentId)
            ->where('attendance_date', '>=', Carbon::now()->subDays($days))
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'date' => $record->attendance_date,
                    'status' => $record->status,
                    'check_in_time' => $record->check_in_time,
                    'check_out_time' => $record->check_out_time,
                    'late_minutes' => $record->late_minutes,
                ];
            });

        $stats = [
            'total_days' => $attendance->count(),
            'present_days' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'absent_days' => $attendance->where('status', 'absent')->count(),
            'late_days' => $attendance->where('status', 'late')->count(),
            'percentage' => $attendance->count() > 0
                ? round(($attendance->whereIn('status', ['present', 'late'])->count() / $attendance->count()) * 100, 1)
                : 0,
        ];

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'batch' => $student->batch->name ?? 'N/A',
                'course' => $student->batch->course->name ?? 'N/A',
            ],
            'attendance' => $attendance,
            'stats' => $stats,
        ]);
    }

    /**
     * Bulk update attendance status
     */
    public function bulkUpdateAttendance(Request $request)
    {
        $request->validate([
            'attendance_ids' => 'required|array',
            'attendance_ids.*' => 'exists:attendances,id',
            'new_status' => 'required|in:present,absent,late,excused',
        ]);

        $updatedCount = Attendance::whereIn('id', $request->attendance_ids)
            ->update([
                'status' => $request->new_status,
                'marked_by' => auth()->id(),
                'marked_at' => now(),
                'notes' => 'Bulk updated from dashboard',
            ]);

        return response()->json([
            'success' => true,
            'updated_count' => $updatedCount,
            'message' => "{$updatedCount} attendance records updated successfully",
        ]);
    }
}
