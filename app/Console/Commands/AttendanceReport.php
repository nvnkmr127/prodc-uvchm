<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class AttendanceReport extends Command
{
    protected $signature = 'attendance:report 
                            {--period=week : Report period (day, week, month)}
                            {--batch= : Specific batch ID}
                            {--threshold=75 : Attendance threshold percentage}
                            {--notify : Send notifications for concerning attendance}';

    protected $description = 'Generate attendance reports and alerts';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $period = $this->option('period');
        $batchId = $this->option('batch');
        $threshold = (int) $this->option('threshold');
        $sendNotifications = $this->option('notify');

        $this->info("📊 Generating {$period}ly attendance report...");

        $dateRange = $this->getDateRange($period);
        $students = $this->getStudents($batchId);

        $reportData = [];
        $concerningStudents = [];

        foreach ($students as $student) {
            $attendanceData = $this->getStudentAttendanceData($student, $dateRange);
            $reportData[] = $attendanceData;

            if ($attendanceData['percentage'] < $threshold) {
                $concerningStudents[] = $attendanceData;
            }
        }

        $this->displayReport($reportData, $period, $threshold);

        if ($sendNotifications && ! empty($concerningStudents)) {
            $this->sendNotifications($concerningStudents, $period, $threshold);
        }

        return 0;
    }

    private function getDateRange($period)
    {
        switch ($period) {
            case 'day':
                return [now()->format('Y-m-d'), now()->format('Y-m-d')];
            case 'week':
                return [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')];
            case 'month':
                return [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')];
            default:
                return [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')];
        }
    }

    private function getStudents($batchId)
    {
        $query = Student::with('batch')->where('status', 'active');

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        return $query->get();
    }

    private function getStudentAttendanceData($student, $dateRange)
    {
        $totalClasses = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', $dateRange)
            ->count();

        $presentClasses = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', $dateRange)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $absentClasses = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', $dateRange)
            ->where('status', 'absent')
            ->count();

        $lateClasses = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', $dateRange)
            ->where('status', 'late')
            ->count();

        $percentage = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100, 2) : 0;

        return [
            'student' => $student,
            'total_classes' => $totalClasses,
            'present_classes' => $presentClasses,
            'absent_classes' => $absentClasses,
            'late_classes' => $lateClasses,
            'percentage' => $percentage,
        ];
    }

    private function displayReport($reportData, $period, $threshold)
    {
        $this->table(
            ['Student', 'Batch', 'Total', 'Present', 'Absent', 'Late', 'Percentage', 'Status'],
            array_map(function ($data) use ($threshold) {
                $status = $data['percentage'] >= $threshold ? '✅ Good' : '⚠️ Low';
                if ($data['percentage'] < 50) {
                    $status = '❌ Critical';
                }

                return [
                    $data['student']->name,
                    $data['student']->batch->name ?? 'N/A',
                    $data['total_classes'],
                    $data['present_classes'],
                    $data['absent_classes'],
                    $data['late_classes'],
                    $data['percentage'].'%',
                    $status,
                ];
            }, $reportData)
        );

        $totalStudents = count($reportData);
        $goodAttendance = count(array_filter($reportData, fn ($d) => $d['percentage'] >= $threshold));
        $lowAttendance = $totalStudents - $goodAttendance;

        $this->info("📈 Summary: {$goodAttendance}/{$totalStudents} students have good attendance (≥{$threshold}%)");

        if ($lowAttendance > 0) {
            $this->warn("⚠️  {$lowAttendance} students need attention");
        }
    }

    private function sendNotifications($concerningStudents, $period, $threshold)
    {
        $this->info('📧 Sending notifications for concerning attendance...');

        foreach ($concerningStudents as $data) {
            $this->notificationService->send([
                'title' => ucfirst($period).'ly Attendance Alert',
                'message' => "{$data['student']->name} has {$data['percentage']}% attendance this {$period}",
                'type' => $data['percentage'] < 50 ? 'error' : 'warning',
                'category' => 'attendance',
                'priority' => $data['percentage'] < 50 ? 'high' : 'normal',
                'roles' => ['super-admin', 'college-admin'],
                'requires_action' => $data['percentage'] < 50,
                'action_url' => route('admin.students.show', $data['student']->id),
                'action_text' => 'Review Student',
                'data' => [
                    'student_id' => $data['student']->id,
                    'period' => $period,
                    'attendance_percentage' => $data['percentage'],
                    'threshold' => $threshold,
                    'total_classes' => $data['total_classes'],
                    'present_classes' => $data['present_classes'],
                    'absent_classes' => $data['absent_classes'],
                ],
            ]);
        }

        $this->info('Sent '.count($concerningStudents).' notifications');
    }
}
