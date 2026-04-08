<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\NotificationService; // Assuming you have this service
use Illuminate\Console\Command;

class SendLowAttendanceAlerts extends Command
{
    protected $signature = 'attendance:send-low-attendance-alerts';

    protected $description = 'Find students with low attendance and send notification alerts.';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Checking for students with low attendance...');

        $minimumAttendance = (float) setting('minimum_attendance_percentage', 75);

        // This is an efficient query that gets all data in one go.
        $studentsWithLowAttendance = Student::query()
            ->where('status', 'active') // Only check active students
            ->withCount([
                'attendances as total_classes',
                'attendances as present_classes' => function ($query) {
                    $query->whereIn('status', ['present', 'late']);
                },
            ])
            ->get()
            // After getting the counts, filter in PHP to find the ones below the threshold.
            ->filter(function ($student) use ($minimumAttendance) {
                if ($student->total_classes === 0) {
                    return false; // Skip students with no attendance records.
                }
                $student->attendance_percentage = ($student->present_classes / $student->total_classes) * 100;

                return $student->attendance_percentage < $minimumAttendance;
            });

        if ($studentsWithLowAttendance->isEmpty()) {
            $this->info('No students with low attendance found. All good!');

            return self::SUCCESS;
        }

        $this->warn("Found {$studentsWithLowAttendance->count()} students below the {$minimumAttendance}% attendance threshold.");

        foreach ($studentsWithLowAttendance as $student) {
            $notificationService->sendAcademicNotification('low_attendance', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'attendance_percentage' => round($student->attendance_percentage, 2),
                'check_type' => 'weekly_review',
            ]);
            $this->line("Alert sent for: {$student->name} ({$student->attendance_percentage}%)");
        }

        $this->info('Low attendance alerts sent successfully.');

        return self::SUCCESS;
    }
}
