<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Mock request to prevent Spatie Activitylog session errors in CLI
$request = \Illuminate\Http\Request::create('/', 'GET');
$app->instance('request', $request);
\Illuminate\Support\Facades\Gate::before(function () {
    return true;
});

use App\Models\User;
use App\Models\Setting;
use App\Models\Attendance\FacultyAttendance;
use App\Models\Attendance\Attendance as StudentAttendance;
use App\Services\Attendance\FacultyAttendanceService;
use App\Http\Controllers\Admin\FacultyAttendanceController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

// Authenticate a user to satisfy controller authorization checks
$authUser = User::first();
if ($authUser) {
    auth()->login($authUser);
}

echo "=== Faculty Attendance System End-to-End QA Validation ===\n\n";

// Helper function to print test results
function report($testName, $passed, $info = '') {
    $status = $passed ? "\033[32m[PASSED]\033[0m" : "\033[31m[FAILED]\033[0m";
    echo "{$status} {$testName}" . ($info ? " - {$info}" : "") . "\n";
    if (!$passed) {
        exit(1);
    }
}

// -------------------------------------------------------------
// CHECKLIST 1: Data Separation & Schema Integrity
// -------------------------------------------------------------
echo "--- 1. Data Separation & Schema Integrity ---\n";

$facultyTableExists = Schema::hasTable('faculty_attendances');
$studentTableExists = Schema::hasTable('attendances');

report("Faculty Table Exists", $facultyTableExists, "faculty_attendances");
report("Student Table Exists", $studentTableExists, "attendances");

// Verify separate models and tables
$facultyModelTable = (new FacultyAttendance())->getTable();
$studentModelTable = (new StudentAttendance())->getTable();

report("Table Isolation", $facultyModelTable !== $studentModelTable, "Faculty uses {$facultyModelTable}, Student uses {$studentModelTable}");

// Check unique index on faculty_attendances
$hasUniqueIndex = false;
$indexes = Schema::getIndexes('faculty_attendances');
foreach ($indexes as $index) {
    if ($index['unique'] && in_array('faculty_id', $index['columns']) && in_array('attendance_date', $index['columns'])) {
        $hasUniqueIndex = true;
    }
}
report("Unique Index [faculty_id, attendance_date] Check", $hasUniqueIndex);

// Verify columns exist
$hasColumns = Schema::hasColumns('faculty_attendances', [
    'faculty_id', 'attendance_date', 'check_in_time', 'check_out_time', 
    'working_hours', 'status', 'late_minutes'
]);
report("Table Column Integrity Check", $hasColumns);


// -------------------------------------------------------------
// CHECKLIST 2: Student Attendance Independence
// -------------------------------------------------------------
echo "\n--- 2. Student Attendance Independence ---\n";

// Count student attendances
$initialStudentAttendanceCount = StudentAttendance::count();

// Get faculty staff user
$faculty = User::role('staff')->first();
if (!$faculty) {
    $faculty = User::create([
        'name' => 'QA Faculty User',
        'email' => 'qa_faculty@example.com',
        'password' => bcrypt('password'),
        'biometric_employee_code' => '9988',
        'employee_id' => 'EMP9988',
    ]);
    $faculty->assignRole('staff');
}

// Clear all attendance records for the faculty for June 2026 to ensure isolated calculations
FacultyAttendance::where('faculty_id', $faculty->id)
    ->whereBetween('attendance_date', ['2026-06-01', '2026-06-30'])
    ->delete();

// Record a mock punch for the faculty
$service = app(FacultyAttendanceService::class);
$testDate = '2026-06-20';

$punchTime = Carbon::parse("{$testDate} 08:30:00");
$service->recordPunch($faculty, $punchTime, 'IN', 'qa-device');

$finalStudentAttendanceCount = StudentAttendance::count();
report("Student Attendance Unchanged", $initialStudentAttendanceCount === $finalStudentAttendanceCount, "Initial: {$initialStudentAttendanceCount}, Final: {$finalStudentAttendanceCount}");


// -------------------------------------------------------------
// CHECKLIST 3: Faculty Cutoff Time Validation & Attendance Flows
// -------------------------------------------------------------
echo "\n--- 3. Cutoff & Timing Configurations & Attendance Flows ---\n";

// Setup separate faculty settings
Setting::updateOrCreate(['key' => 'attendance_faculty_college_start_time'], ['value' => '09:00:00']);
Setting::updateOrCreate(['key' => 'attendance_faculty_present_cutoff_time'], ['value' => '10:30:00']);
Setting::updateOrCreate(['key' => 'attendance_faculty_late_cutoff_time'], ['value' => '11:00:00']);

// Setup separate student settings
Setting::updateOrCreate(['key' => 'attendance_student_college_start_time'], ['value' => '09:30:00']);
Setting::updateOrCreate(['key' => 'attendance_student_present_cutoff_time'], ['value' => '11:00:00']);
Setting::updateOrCreate(['key' => 'attendance_student_late_cutoff_time'], ['value' => '11:30:00']);

// Verify settings values in DB
report("Faculty Start Setting", Setting::where('key', 'attendance_faculty_college_start_time')->value('value') === '09:00:00');
report("Student Start Setting", Setting::where('key', 'attendance_student_college_start_time')->value('value') === '09:30:00');

// Test Case 3.1: Punch before start time (Early check-in)
$date1 = '2026-06-21';
FacultyAttendance::where('faculty_id', $faculty->id)->where('attendance_date', $date1)->delete();
$punchTime1 = Carbon::parse("{$date1} 08:45:00");
$rec1 = $service->recordPunch($faculty, $punchTime1, 'IN', 'qa-device');
report("Flow: Check-in before start", $rec1->status === 'present' && $rec1->late_minutes === null, "Status: {$rec1->status}, Late: " . var_export($rec1->late_minutes, true));

// Test Case 3.2: Punch after start time, before present cutoff (Present)
$date2 = '2026-06-22';
FacultyAttendance::where('faculty_id', $faculty->id)->where('attendance_date', $date2)->delete();
$punchTime2 = Carbon::parse("{$date2} 10:15:00");
$rec2 = $service->recordPunch($faculty, $punchTime2, 'IN', 'qa-device');
report("Flow: Check-in before present cutoff", $rec2->status === 'present' && $rec2->late_minutes === null, "Status: {$rec2->status}");

// Test Case 3.3: Punch after present cutoff, before late cutoff (Late with correct minutes)
$date3 = '2026-06-23';
FacultyAttendance::where('faculty_id', $faculty->id)->where('attendance_date', $date3)->delete();
$punchTime3 = Carbon::parse("{$date3} 10:45:00");
$rec3 = $service->recordPunch($faculty, $punchTime3, 'IN', 'qa-device');
report("Flow: Check-in before late cutoff", $rec3->status === 'late' && $rec3->late_minutes === 105, "Status: {$rec3->status}, Late minutes: {$rec3->late_minutes}");

// Test Case 3.4: Punch after late cutoff (Absent)
$date4 = '2026-06-24';
FacultyAttendance::where('faculty_id', $faculty->id)->where('attendance_date', $date4)->delete();
$punchTime4 = Carbon::parse("{$date4} 11:15:00");
$rec4 = $service->recordPunch($faculty, $punchTime4, 'IN', 'qa-device');
report("Flow: Check-in after late cutoff", $rec4->status === 'absent', "Status: {$rec4->status}");

// Test Case 3.5: Multiple faculty records on the same day (Unique Check)
$faculty2 = User::role('staff')->where('id', '!=', $faculty->id)->first();
if (!$faculty2) {
    $faculty2 = User::create([
        'name' => 'QA Faculty User 2',
        'email' => 'qa_faculty2@example.com',
        'password' => bcrypt('password'),
        'biometric_employee_code' => '9987',
        'employee_id' => 'EMP9987',
    ]);
    $faculty2->assignRole('staff');
}
FacultyAttendance::where('faculty_id', $faculty2->id)->where('attendance_date', $date1)->delete();
$punchTimeF2 = Carbon::parse("{$date1} 08:50:00");
$recF2 = $service->recordPunch($faculty2, $punchTimeF2, 'IN', 'qa-device');
report("Flow: Multiple faculty same day isolation", $recF2->faculty_id !== $rec1->faculty_id && $recF2->attendance_date->toDateString() === $rec1->attendance_date->toDateString());

// Test Case 3.6: Try to force duplicate entry in DB (Unique Constraint validation)
try {
    FacultyAttendance::create([
        'faculty_id' => $faculty->id,
        'attendance_date' => $date1,
        'check_in_time' => '10:00:00',
        'status' => 'present',
    ]);
    report("Duplicate database insertion prevention", false, "Allowed duplicate key insertion!");
} catch (\Illuminate\Database\QueryException $e) {
    report("Duplicate database insertion prevention", true, "Caught duplicate exception correctly");
}

// Test Case 3.7: Checkout and Working Hours Calculation
$checkoutTime = Carbon::parse("{$date1} 17:30:00"); // 08:45:00 to 17:30:00 = 8.75 hours
$rec1_out = $service->recordPunch($faculty, $checkoutTime, 'OUT', 'qa-device');
report("Flow: Checkout time mapping", $rec1_out->check_out_time === '17:30:00');
report("Flow: Working hours calculation", floatval($rec1_out->working_hours) === 8.75, "Hours calculated: {$rec1_out->working_hours}");
report("Flow: Final Present Value (>= 4 hrs)", floatval($rec1_out->present_value) === 1.0, "Present value: {$rec1_out->present_value}");

// Test Case 3.8: Checkout with short working hours (< 4 hours => Half Day)
$checkoutShort = Carbon::parse("{$date2} 12:15:00"); // 10:15:00 to 12:15:00 = 2 hours
$rec2_out = $service->recordPunch($faculty, $checkoutShort, 'OUT', 'qa-device');
report("Flow: Short working hours status mapping", $rec2_out->status === 'half_day', "Status: {$rec2_out->status}");
report("Flow: Short working hours present value", floatval($rec2_out->present_value) === 0.5, "Present value: {$rec2_out->present_value}");

// Test Case 3.9: Missing check-out on past date vs today
// Past date (date3 = 2026-06-23): check-out is missing
report("Single Punch Past Date Present Value", floatval($rec3->present_value) === 0.5, "Value: {$rec3->present_value}");

// Today date: check-out is missing
$todayStr = Carbon::today()->toDateString();
FacultyAttendance::where('faculty_id', $faculty->id)->where('attendance_date', $todayStr)->delete();
$recToday = $service->recordPunch($faculty, Carbon::now()->startOfDay()->addHours(9), 'IN', 'qa-device');
report("Single Punch Today Present Value", floatval($recToday->present_value) === 1.0, "Value: {$recToday->present_value}");


// -------------------------------------------------------------
// CHECKLIST 4: Dashboard & Filters Validation
// -------------------------------------------------------------
echo "\n--- 4. Dashboard & Filters Validation ---\n";

$controller = app(FacultyAttendanceController::class);

// Invoke controller index method directly
$req = Request::create('/admin/faculty-attendance', 'GET', [
    'date_range' => 'custom',
    'start_date' => '2026-06-20',
    'end_date' => '2026-06-25',
]);

$response = $controller->index($req);
$viewData = $response->getData();

report("Controller view response", $response->name() === 'admin.faculty.attendance.index');
report("View has stats variable", isset($viewData['stats']));
report("View has records variable", isset($viewData['records']));
report("View has departments variable", isset($viewData['departments']));
report("View has realtimeCheckedIn variable", isset($viewData['realtimeCheckedIn']));

// Verify Pagination works on records
report("Records Pagination Instance Check", $viewData['records'] instanceof \Illuminate\Pagination\LengthAwarePaginator);

// Filter by Status half_day
$reqHalfDay = Request::create('/admin/faculty-attendance', 'GET', [
    'date_range' => 'custom',
    'start_date' => '2026-06-20',
    'end_date' => '2026-06-25',
    'status' => 'half_day',
]);
$viewDataHalfDay = $controller->index($reqHalfDay)->getData();
$allHalfDay = true;
foreach ($viewDataHalfDay['records'] as $r) {
    if ($r['status'] !== 'half_day') {
        $allHalfDay = false;
    }
}
report("Status Filter (half_day) Check", $allHalfDay && $viewDataHalfDay['records']->total() > 0, "Found " . $viewDataHalfDay['records']->total() . " records, all match status 'half_day'");


// -------------------------------------------------------------
// CHECKLIST 5: Export Validation
// -------------------------------------------------------------
echo "\n--- 5. Export Validation ---\n";

// Test export CSV
$exportResponseCsv = $controller->export($req, 'csv');
report("Export CSV response type", $exportResponseCsv instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse);

// Test export XLSX
$exportResponseXlsx = $controller->export($req, 'xlsx');
report("Export Excel response type", $exportResponseXlsx instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse);


// -------------------------------------------------------------
// CHECKLIST 6: Payroll Calculation Validation
// -------------------------------------------------------------
echo "\n--- 6. Payroll Calculation Validation ---\n";

// Programmatically test Payslip Controller's private calculation method
$payslipController = app(\App\Http\Controllers\Admin\PayslipController::class);

// Invoke private calculateBiometricSalaryStats using reflection
$reflection = new \ReflectionClass(get_class($payslipController));
$method = $reflection->getMethod('calculateBiometricSalaryStats');
$method->setAccessible(true);

$month = 'June';
$year = 2026;

// Set up attendance records in June 2026
// We have:
// - 2026-06-20: IN 08:30 (No out, single punch past date = 0.5)
// - 2026-06-21: IN 08:45, OUT 17:30 (>= 4 hrs, present value = 1.0)
// - 2026-06-22: IN 10:15, OUT 12:15 (< 4 hrs, present value = 0.5)
// - 2026-06-23: IN 10:45 (No out, single punch past date = 0.5)
// - 2026-06-24: IN 11:15 (Absent, present value = 0.0)
// Total present value = 0.5 + 1.0 + 0.5 + 0.5 + 0.0 = 2.5 days

$salaryStats = $method->invokeArgs($payslipController, [$faculty, $month, $year]);

report("Payroll: Days Present calculation", floatval($salaryStats['days_present']) === 3.5, "Calculated present days: {$salaryStats['days_present']} (Expected: 3.5)");
report("Payroll: Return array keys", isset($salaryStats['working_days']) && isset($salaryStats['days_present']) && isset($salaryStats['leave_days']) && isset($salaryStats['payment_multiplier']));

echo "\n=== All QA and Validation Checks Passed Successfully! ===\n";
