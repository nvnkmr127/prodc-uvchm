<?php
use App\Models\Attendance;
use Carbon\Carbon;

$date = Carbon::today();
// Or find a date with data if today is empty (weekend?)
if (Attendance::whereDate('attendance_date', $date)->count() == 0) {
    echo "No attendance today, checking recent...\n";
    $lastAttendance = Attendance::latest('attendance_date')->first();
    if ($lastAttendance) {
        $date = $lastAttendance->attendance_date;
    }
}

echo "Checking date: " . $date->format('Y-m-d') . "\n";

$duplicates = Attendance::whereDate('attendance_date', $date)
    ->select('student_id', \DB::raw('count(*) as count'))
    ->groupBy('student_id')
    ->having('count', '>', 1)
    ->get();

echo "Found " . $duplicates->count() . " students with multiple attendance records.\n";
foreach ($duplicates->take(5) as $dup) {
    echo "Student ID: " . $dup->student_id . " has " . $dup->count . " records.\n";
}
