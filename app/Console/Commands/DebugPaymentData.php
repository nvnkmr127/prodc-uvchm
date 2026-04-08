<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugPaymentData extends Command
{
    protected $signature = 'debug:payments 
                            {--date= : Specific date for debugging (YYYY-MM-DD)}
                            {--detailed : Show detailed payment records}';

    protected $description = 'Debug payment data for a specific date';

    public function handle()
    {
        $this->info('🔍 Payment Data Debugging Tool');
        $this->line('================================');

        // Determine debug date
        $debugDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $this->info("📅 Debugging Date: {$debugDate->format('Y-m-d (l)')}");
        $this->newLine();

        // 1. Check Payment Table Structure
        $this->checkPaymentTableStructure();

        // 2. Raw Payment Data for the Date
        $this->checkRawPaymentData($debugDate);

        // 3. Check for Status Filtering Issues
        $this->checkPaymentStatusIssues($debugDate);

        // 4. Check for Timezone Issues
        $this->checkTimezoneIssues($debugDate);

        // 5. Check for Data Type Issues
        $this->checkDataTypeIssues($debugDate);

        // 6. Manual Query Test
        $this->runManualQuery($debugDate);

        return self::SUCCESS;
    }

    private function checkPaymentTableStructure()
    {
        $this->info('🔧 Payment Table Structure:');

        // Get table columns
        $columns = DB::select('DESCRIBE payments');

        foreach ($columns as $column) {
            $this->line("  - {$column->Field}: {$column->Type} ({$column->Null}) - Default: {$column->Default}");
        }

        // Check total payment records
        $totalPayments = Payment::count();
        $this->line("  - Total Payment Records: {$totalPayments}");

        $this->newLine();
    }

    private function checkRawPaymentData(Carbon $date)
    {
        $this->info('📊 Raw Payment Data Analysis:');

        // All payments for the date (no filtering)
        $allPayments = DB::table('payments')
            ->whereDate('payment_date', $date)
            ->select('id', 'student_id', 'amount', 'payment_date', 'payment_method', 'payment_type', 'status', 'created_at')
            ->get();

        $this->line("  - Total payments for {$date->format('Y-m-d')}: {$allPayments->count()}");

        if ($allPayments->count() > 0) {
            $totalAmount = $allPayments->sum('amount');
            $uniqueStudents = $allPayments->unique('student_id')->count();

            $this->line("  - Total Amount: ₹{$totalAmount}");
            $this->line("  - Unique Students: {$uniqueStudents}");

            // Group by status
            $statusGroups = $allPayments->groupBy('status');
            foreach ($statusGroups as $status => $payments) {
                $statusAmount = $payments->sum('amount');
                $statusCount = $payments->count();
                $this->line("  - Status '{$status}': {$statusCount} payments, ₹{$statusAmount}");
            }

            // Group by payment_type
            $typeGroups = $allPayments->groupBy('payment_type');
            foreach ($typeGroups as $type => $payments) {
                $typeAmount = $payments->sum('amount');
                $typeCount = $payments->count();
                $this->line("  - Type '{$type}': {$typeCount} payments, ₹{$typeAmount}");
            }

            // Show individual payments if detailed flag is set
            if ($this->option('detailed')) {
                $this->newLine();
                $this->line('  📋 Individual Payment Records:');
                foreach ($allPayments as $payment) {
                    $this->line("    - ID: {$payment->id}, Student: {$payment->student_id}, Amount: ₹{$payment->amount}, Type: {$payment->payment_type}, Status: {$payment->status}");
                }
            }
        }

        $this->newLine();
    }

    private function checkPaymentStatusIssues(Carbon $date)
    {
        $this->info('⚠️  Status Filtering Analysis:');

        // Check what the webhook command query returns
        $webhookQuery = Payment::whereDate('payment_date', $date);
        $webhookResults = $webhookQuery->get();

        $this->line("  - Webhook Query Results: {$webhookResults->count()} payments");
        $this->line("  - Webhook Total Amount: ₹{$webhookResults->sum('amount')}");
        $this->line("  - Webhook Unique Students: {$webhookResults->unique('student_id')->count()}");

        // Check if there's a default scope or status filtering
        $activeStatusPayments = Payment::whereDate('payment_date', $date)
            ->where('status', 'completed')
            ->get();

        $this->line("  - 'Completed' Status Only: {$activeStatusPayments->count()} payments, ₹{$activeStatusPayments->sum('amount')}");

        // Check for NULL status
        $nullStatusPayments = Payment::whereDate('payment_date', $date)
            ->whereNull('status')
            ->get();

        $this->line("  - NULL Status: {$nullStatusPayments->count()} payments, ₹{$nullStatusPayments->sum('amount')}");

        $this->newLine();
    }

    private function checkTimezoneIssues(Carbon $date)
    {
        $this->info('🌍 Timezone Analysis:');

        $timezone = config('app.timezone', 'UTC');
        $this->line("  - App Timezone: {$timezone}");

        // Check payments in different timezone contexts
        $utcDate = $date->utc();
        $localDate = $date->setTimezone($timezone);

        $this->line("  - Debug Date UTC: {$utcDate->format('Y-m-d H:i:s T')}");
        $this->line("  - Debug Date Local: {$localDate->format('Y-m-d H:i:s T')}");

        // Check if there are payments created on different dates due to timezone
        $paymentsByCreatedDate = DB::table('payments')
            ->whereDate('payment_date', $date)
            ->selectRaw('DATE(created_at) as created_date, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('created_date')
            ->get();

        foreach ($paymentsByCreatedDate as $group) {
            $this->line("  - Created on {$group->created_date}: {$group->count} payments, ₹{$group->total}");
        }

        $this->newLine();
    }

    private function checkDataTypeIssues(Carbon $date)
    {
        $this->info('🔢 Data Type Analysis:');

        // Check for amount data type issues
        $amountCheck = DB::table('payments')
            ->whereDate('payment_date', $date)
            ->selectRaw('amount, typeof(amount) as amount_type')
            ->get();

        if ($amountCheck->count() > 0) {
            $this->line('  - Amount data types found:');
            $typeGroups = $amountCheck->groupBy('amount_type');
            foreach ($typeGroups as $type => $records) {
                $this->line("    - Type '{$type}': {$records->count()} records");
            }
        }

        // Check for student_id issues
        $studentIdCheck = Payment::whereDate('payment_date', $date)
            ->whereNull('student_id')
            ->count();

        $this->line("  - Payments with NULL student_id: {$studentIdCheck}");

        $this->newLine();
    }

    private function runManualQuery(Carbon $date)
    {
        $this->info('🔍 Manual Query Test (Replicating Webhook Logic):');

        // Exact same query as in the webhook
        $paymentsQuery = Payment::whereDate('payment_date', $date);

        // Clone the query to avoid affecting the original
        $totalAmount = (clone $paymentsQuery)->sum('amount') ?: 0.0;
        $totalPayers = (clone $paymentsQuery)->distinct('student_id')->count() ?: 0;

        $this->line("  - Manual Query Total Amount: ₹{$totalAmount}");
        $this->line("  - Manual Query Total Payers: {$totalPayers}");

        // Get the SQL query being executed
        $sql = $paymentsQuery->toSql();
        $bindings = $paymentsQuery->getBindings();

        $this->line("  - SQL Query: {$sql}");
        $this->line('  - Bindings: '.json_encode($bindings));

        // Test different date formats
        $this->info("\n🧪 Testing Different Date Formats:");

        $formats = [
            $date->format('Y-m-d'),
            $date->format('Y-m-d 00:00:00'),
            $date->startOfDay(),
        ];

        foreach ($formats as $format) {
            $testQuery = Payment::whereDate('payment_date', $format);
            $testAmount = $testQuery->sum('amount');
            $testCount = $testQuery->count();
            $this->line("  - Format '{$format}': {$testCount} payments, ₹{$testAmount}");
        }

        $this->newLine();
    }

    protected function generateDailySummary(Carbon $date): array
    {
        $this->info('📊 Collecting data...');

        // Get timezone from config
        $timezone = config('app.timezone', 'UTC');
        $reportTimestamp = now($timezone);

        // ===== ENHANCED PAYMENTS SUMMARY WITH DEBUGGING =====

        // Check multiple payment query scenarios
        $allPaymentsQuery = Payment::whereDate('payment_date', $date);
        $completedPaymentsQuery = Payment::whereDate('payment_date', $date)->where('status', 'completed');
        $nonCancelledPaymentsQuery = Payment::whereDate('payment_date', $date)->whereNotIn('status', ['cancelled', 'failed']);

        // Get counts for each scenario
        $allPaymentsCount = $allPaymentsQuery->count();
        $allPaymentsAmount = $allPaymentsQuery->sum('amount') ?: 0.0;
        $allPaymentsPayers = $allPaymentsQuery->distinct('student_id')->count() ?: 0;

        $completedPaymentsCount = $completedPaymentsQuery->count();
        $completedPaymentsAmount = $completedPaymentsQuery->sum('amount') ?: 0.0;
        $completedPaymentsPayers = $completedPaymentsQuery->distinct('student_id')->count() ?: 0;

        $nonCancelledPaymentsCount = $nonCancelledPaymentsQuery->count();
        $nonCancelledPaymentsAmount = $nonCancelledPaymentsQuery->sum('amount') ?: 0.0;
        $nonCancelledPaymentsPayers = $nonCancelledPaymentsQuery->distinct('student_id')->count() ?: 0;

        // Debug output
        if ($this->option('debug')) {
            $this->line('🔍 Payment Query Debug:');
            $this->line("  All Payments: {$allPaymentsCount} records, ₹{$allPaymentsAmount}, {$allPaymentsPayers} payers");
            $this->line("  Completed Only: {$completedPaymentsCount} records, ₹{$completedPaymentsAmount}, {$completedPaymentsPayers} payers");
            $this->line("  Non-Cancelled: {$nonCancelledPaymentsCount} records, ₹{$nonCancelledPaymentsAmount}, {$nonCancelledPaymentsPayers} payers");

            // Show payment status distribution
            $statusDistribution = Payment::whereDate('payment_date', $date)
                ->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('status')
                ->get();

            $this->line('  Status Distribution:');
            foreach ($statusDistribution as $status) {
                $this->line("    - {$status->status}: {$status->count} payments, ₹{$status->total}");
            }

            // Show individual payment records if there are few
            if ($allPaymentsCount <= 10) {
                $this->line('  Individual Records:');
                $payments = Payment::whereDate('payment_date', $date)
                    ->select('id', 'student_id', 'amount', 'status', 'payment_type', 'payment_method')
                    ->get();
                foreach ($payments as $payment) {
                    $this->line("    - ID: {$payment->id}, Student: {$payment->student_id}, Amount: ₹{$payment->amount}, Status: {$payment->status}, Type: {$payment->payment_type}");
                }
            }
        }

        // Determine which query to use (you may need to adjust this logic)
        // Option 1: Use all payments (including pending, completed, etc.)
        // Option 2: Use only completed payments
        // Option 3: Use non-cancelled payments

        // 🚨 CHANGE THIS LOGIC BASED ON YOUR REQUIREMENTS 🚨
        // Currently using all payments - change if needed
        $paymentsQuery = $allPaymentsQuery;
        $paymentsData = [
            'total_amount' => (float) $allPaymentsAmount,
            'total_payers' => $allPaymentsPayers,
            'debug_info' => [
                'all_payments' => ['count' => $allPaymentsCount, 'amount' => $allPaymentsAmount, 'payers' => $allPaymentsPayers],
                'completed_only' => ['count' => $completedPaymentsCount, 'amount' => $completedPaymentsAmount, 'payers' => $completedPaymentsPayers],
                'non_cancelled' => ['count' => $nonCancelledPaymentsCount, 'amount' => $nonCancelledPaymentsAmount, 'payers' => $nonCancelledPaymentsPayers],
            ],
        ];

        // ===== ATTENDANCE SUMMARY (EXISTING LOGIC) =====
        // Get all active students
        $totalActiveStudents = Student::where('status', 'active')->count();

        // Create separate queries to avoid conflict
        $presentCount = Attendance::whereDate('attendance_date', $date)
            ->whereIn('status', ['present', 'late'])
            ->count() ?: 0;

        $absentCount = Attendance::whereDate('attendance_date', $date)
            ->where('status', 'absent')
            ->count() ?: 0;

        $totalMarkedAttendance = Attendance::whereDate('attendance_date', $date)
            ->count() ?: 0;

        // Debug information for attendance
        if ($this->option('debug')) {
            $this->line('🔍 Attendance Debug Info:');
            $this->line("  - Date: {$date->format('Y-m-d')}");
            $this->line("  - Total Active Students: {$totalActiveStudents}");
            $this->line("  - Present Count: {$presentCount}");
            $this->line("  - Absent Count: {$absentCount}");
            $this->line("  - Total Marked: {$totalMarkedAttendance}");

            // Show available statuses in attendance table
            $availableStatuses = Attendance::distinct('status')->pluck('status');
            $this->line('  - Available Status Values: '.$availableStatuses->implode(', '));
        }

        // Calculate attendance based on system behavior
        if ($totalMarkedAttendance === 0) {
            $attendanceData = [
                'present' => 0,
                'absent' => 0,
                'total_students' => $totalActiveStudents,
                'attendance_percentage' => 0.0,
                'notes' => 'No attendance records found for this date',
            ];
        } elseif ($absentCount === 0 && $presentCount > 0 && $presentCount < $totalActiveStudents) {
            $calculatedAbsent = $totalActiveStudents - $presentCount;

            $attendanceData = [
                'present' => $presentCount,
                'absent' => $calculatedAbsent,
                'total_students' => $totalActiveStudents,
                'attendance_percentage' => $totalActiveStudents > 0
                    ? round(($presentCount / $totalActiveStudents) * 100, 1)
                    : 0.0,
                'calculation_method' => 'absent_calculated_from_missing_records',
            ];
        } elseif ($absentCount === 0 && $presentCount === $totalActiveStudents) {
            $attendanceData = [
                'present' => $presentCount,
                'absent' => 0,
                'total_students' => $totalActiveStudents,
                'attendance_percentage' => 100.0,
                'calculation_method' => 'perfect_attendance',
            ];
        } else {
            $totalForCalculation = max($totalMarkedAttendance, $totalActiveStudents);

            $attendanceData = [
                'present' => $presentCount,
                'absent' => $absentCount,
                'total_students' => $totalForCalculation,
                'attendance_percentage' => $totalForCalculation > 0
                    ? round(($presentCount / $totalForCalculation) * 100, 1)
                    : 0.0,
                'calculation_method' => 'explicit_marking',
            ];
        }

        // ===== ENHANCED PAYLOAD WITH METADATA =====
        $payload = [
            'date' => $date->format('Y-m-d'),
            'report_day' => $date->format('l'),
            'report_generated_at' => $date->format('Y-m-d').' Time: '.$reportTimestamp->format('h:i A'),
            'payments' => $paymentsData,
            'attendance' => $attendanceData,
            'metadata' => [
                'portal_name' => config('app.name', 'UVCHM Portal'),
                'report_version' => '1.2', // Incremented version
                'working_day' => ! $date->isSunday(),
                'generated_by' => 'automated_scheduler',
                'timezone' => $timezone,
                'server_time' => $reportTimestamp->format('Y-m-d H:i:s T'),
                'command_options' => [
                    'test_mode' => $this->option('test'),
                    'forced' => $this->option('force'),
                    'debug' => $this->option('debug'),
                ],
            ],
        ];

        return $payload;
    }
}
