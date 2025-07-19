<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Holiday;
use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $batches = Batch::with('course')->get();
        $reportData = null;

        if ($request->filled(['batch_id', 'start_date', 'end_date'])) {
            $request->validate([
                'batch_id' => 'required|exists:batches,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $batch = Batch::findOrFail($request->batch_id);
            $students = Student::where('batch_id', $batch->id)->orderBy('name')->get();
            $holidays = Holiday::whereBetween('date', [$request->start_date, $request->end_date])->pluck('date')->map(fn($date) => $date->format('Y-m-d'));

            // Calculate total working days in the period
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            $totalWorkingDays = 0;
            foreach ($period as $date) {
                if (!$date->isSunday() && !$holidays->contains($date->format('Y-m-d'))) {
                    $totalWorkingDays++;
                }
            }

            $reportData = [];
            foreach ($students as $student) {
                $presentDays = Attendance::where('student_id', $student->id)
                    ->whereBetween('attendance_date', [$request->start_date, $request->end_date])
                    ->where('status', 'present')
                    ->count();

                $absentDays = Attendance::where('student_id', $student->id)
                    ->whereBetween('attendance_date', [$request->start_date, $request->end_date])
                    ->where('status', 'absent')
                    ->count();

                $percentage = ($totalWorkingDays > 0) ? round(($presentDays / $totalWorkingDays) * 100, 2) : 0;

                $reportData[] = [
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'total_working_days' => $totalWorkingDays,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'attendance_percentage' => $percentage,
                ];
            }
        }

        return view('admin.reports.attendance.index', compact('batches', 'reportData'));
    }
}