<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;

class FacultyDashboardController extends Controller
{
    private function getTodayAttendance($user)
    {
        return Attendance::whereHas('student.batch', function ($query) use ($user) {
            $query->where('faculty_id', $user->id);
        })->whereDate('date', today())->where('status', 'present')->count();
    }

    private function getBatchPerformance($user)
    {
        return Batch::where('faculty_id', $user->id)
            ->withCount('students')
            ->get()
            ->map(function ($batch) {
                return [
                    'name' => $batch->name,
                    'student_count' => $batch->students_count,
                    'average_attendance' => $this->getBatchAttendanceRate($batch),
                ];
            });
    }

    private function getAttendanceTrends($user)
    {
        return Attendance::whereHas('student.batch', function ($query) use ($user) {
            $query->where('faculty_id', $user->id);
        })
            ->selectRaw('DATE(date) as date, 
                                COUNT(*) as total_classes,
                                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_classes')
            ->whereBetween('date', [now()->subDays(30), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getStudentAttendancePercentage($student)
    {
        $totalClasses = $student->attendances()->count();
        if ($totalClasses === 0) {
            return 0;
        }

        $presentClasses = $student->attendances()->where('status', 'present')->count();

        return round(($presentClasses / $totalClasses) * 100, 2);
    }

    private function getBatchAttendanceRate($batch)
    {
        $totalClasses = Attendance::whereHas('student', function ($query) use ($batch) {
            $query->where('batch_id', $batch->id);
        })->count();

        if ($totalClasses === 0) {
            return 0;
        }

        $presentClasses = Attendance::whereHas('student', function ($query) use ($batch) {
            $query->where('batch_id', $batch->id);
        })->where('status', 'present')->count();

        return round(($presentClasses / $totalClasses) * 100, 2);
    }
}
