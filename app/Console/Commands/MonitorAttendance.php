<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use App\Services\NotificationService;

class MonitorAttendance extends Command
{
    protected $signature = 'attendance:monitor {--dry-run : Show what would be reported without sending notifications}';
    protected $description = 'Monitor attendance patterns and send alerts for concerning trends';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('📊 Monitoring Attendance Patterns...');
        
        $minimumAttendance = (int) setting('minimum_attendance_percentage', 75);
        $studentsWithIssues = [];
        
        $students = Student::with('batch')->where('status', 'active')->get();
        
        foreach ($students as $student) {
            $attendanceData = $this->analyzeStudentAttendance($student);
            
            if ($attendanceData['has_issues']) {
                $studentsWithIssues[] = $attendanceData;
                
                if (!$this->option('dry-run')) {
                    $this->sendAttendanceAlert($student, $attendanceData);
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->displayDryRunResults($studentsWithIssues);
        } else {
            $this->info("✅ Attendance monitoring completed");
            $this->line("📋 Students with attendance issues: " . count($studentsWithIssues));
        }

        return 0;
    }

    private function analyzeStudentAttendance($student)
    {
        $totalClasses = Attendance::where('student_id', $student->id)->count();
        $presentClasses = Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])
            ->count();
            
        $attendancePercentage = $totalClasses > 0 ? ($presentClasses / $totalClasses) * 100 : 100;
        
        // Check recent absences (last 7 days)
        $recentAbsences = Attendance::where('student_id', $student->id)
            ->where('attendance_date', '>=', now()->subDays(7))
            ->where('status', 'absent')
            ->count();
            
        // Check for long absence streaks
        $lastPresent = Attendance::where('student_id', $student->id)
            ->where('status', 'present')
            ->orderBy('attendance_date', 'desc')
            ->first();
            
        $daysSincePresent = $lastPresent ? now()->diffInDays($lastPresent->attendance_date) : 999;
        
        $minimumAttendance = (int) setting('minimum_attendance_percentage', 75);
        
        return [
            'student' => $student,
            'attendance_percentage' => round($attendancePercentage, 2),
            'recent_absences' => $recentAbsences,
            'days_since_present' => $daysSincePresent,
            'total_classes' => $totalClasses,
            'present_classes' => $presentClasses,
            'has_issues' => $attendancePercentage < $minimumAttendance || $recentAbsences >= 3 || $daysSincePresent >= 5,
            'issue_type' => $this->determineIssueType($attendancePercentage, $recentAbsences, $daysSincePresent, $minimumAttendance),
        ];
    }

    private function determineIssueType($percentage, $recentAbsences, $daysSincePresent, $minimum)
    {
        if ($daysSincePresent >= 7) return 'long_absence';
        if ($recentAbsences >= 4) return 'frequent_recent_absences';
        if ($percentage < $minimum) return 'low_overall_attendance';
        return 'minor_concern';
    }

    private function sendAttendanceAlert($student, $data)
    {
        $priorityMap = [
            'long_absence' => 'urgent',
            'frequent_recent_absences' => 'high',
            'low_overall_attendance' => 'normal',
            'minor_concern' => 'low',
        ];

        $this->notificationService->send([
            'title' => 'Attendance Alert: ' . $student->name,
            'message' => "Student attendance issue detected: {$data['attendance_percentage']}% attendance",
            'type' => 'warning',
            'category' => 'attendance',
            'priority' => $priorityMap[$data['issue_type']] ?? 'normal',
            'roles' => ['super-admin', 'college-admin'],
            'requires_action' => in_array($data['issue_type'], ['long_absence', 'frequent_recent_absences']),
            'action_url' => route('admin.students.show', $student->id),
            'action_text' => 'Review Student',
            'data' => $data,
        ]);
    }

    private function displayDryRunResults($studentsWithIssues)
    {
        if (empty($studentsWithIssues)) {
            $this->info("✅ No attendance issues found!");
            return;
        }

        $this->table(
            ['Student', 'Batch', 'Attendance %', 'Recent Absences', 'Days Since Present', 'Issue Type'],
            array_map(function($data) {
                return [
                    $data['student']->name,
                    $data['student']->batch->name ?? 'N/A',
                    $data['attendance_percentage'] . '%',
                    $data['recent_absences'],
                    $data['days_since_present'],
                    $data['issue_type'],
                ];
            }, $studentsWithIssues)
        );

        $this->info("Would send " . count($studentsWithIssues) . " attendance alerts");
    }
}