<?php

use App\Models\Student;
use App\Models\Attendance;
use App\Models\Batch;
use App\Services\Attendance\AttendanceService;
use Carbon\Carbon;

$today = Carbon::today();
$service = app(AttendanceService::class);

echo "--- Debugging Absent Logic for {$today->format('Y-m-d')} ---\n";

// 1. Total Active Students
$totalActive = Student::where('status', 'active')->count();
echo "1. Total Active Students: {$totalActive}\n";

// 2. Total Mark 'Present'/'Late'/'Excused'
$totalPresent = Attendance::whereDate('attendance_date', $today)
    ->whereIn('status', ['present', 'late', 'excused'])
    ->count();
echo "2. Total Present/Late/Excused: {$totalPresent}\n";

// 3. Implicit Absent (Active - Present)
$implicitAbsent = $totalActive - $totalPresent;
echo "3. Implicit Absent (Active - Present): {$implicitAbsent}\n";

// 4. Explicit 'Absent' Records
$explicitAbsent = Attendance::whereDate('attendance_date', $today)
    ->where('status', 'absent')
    ->count();
echo "4. Explicit Absent Records: {$explicitAbsent}\n";

// 5. Internship Batches
$internshipBatchIds = Batch::where('is_on_internship', true)->pluck('id');
echo "5. Internship Batches count: {$internshipBatchIds->count()}\n";

// 6. AttendanceService Logic
$serviceCount = $service->getAbsentStudentsForDate($today)->count();
echo "6. AttendanceService->getAbsentStudentsForDate(): {$serviceCount}\n";

// 7. Dashboard 'getAbsentStudents' Logic simulation
$dashboardAbsent = Student::where('status', 'active')
    ->whereNotIn('id', Attendance::whereDate('attendance_date', $today)
        ->whereIn('status', ['present', 'late', 'excused'])
        ->pluck('student_id'))
    ->count();
echo "7. Dashboard Implicit Logic: {$dashboardAbsent}\n";

// 8. Breakdown of Service Logic's "Absent" students
$serviceStudents = $service->getAbsentStudentsForDate($today);
$inInternship = $serviceStudents->whereIn('batch_id', $internshipBatchIds)->count();
$explicitlyAbsentInService = Attendance::whereDate('attendance_date', $today)
    ->where('status', 'absent')
    ->whereIn('student_id', $serviceStudents->pluck('id'))
    ->count();
echo "8. Breakdown of Service List:\n";
echo "   - In Internship Batch: {$inInternship}\n";
echo "   - Has Explicit 'Absent' Record: {$explicitlyAbsentInService}\n";
echo "   - No Record (True Implicit): " . ($serviceCount - $explicitlyAbsentInService) . "\n";
