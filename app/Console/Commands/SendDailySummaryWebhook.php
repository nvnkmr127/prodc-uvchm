<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Webhook;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendDailySummaryWebhook extends Command
{
    protected $signature = 'webhook:daily-summary 
                            {--date= : Specific date for report (YYYY-MM-DD)}
                            {--test : Run in test mode}
                            {--force : Force run even on non-working days}
                            {--debug : Show detailed debugging information}';

    protected $description = 'Send daily summary webhook with payments and attendance data';

    public function handle()
    {
        $this->info('🚀 Starting Daily Summary Webhook Process...');

        try {
            // Determine report date
            $reportDate = $this->option('date')
                ? Carbon::parse($this->option('date'))
                : Carbon::today();

            $this->info("📅 Report Date: {$reportDate->format('Y-m-d (l)')}");

            // Check working day (skip Sundays unless forced)
            if (! $this->option('force') && $reportDate->isSunday()) {
                $this->warn('⏸️  Skipping: Sunday is not a working day');

                return self::SUCCESS;
            }

            // Generate summary data
            $summaryData = $this->generateDailySummary($reportDate);
            $this->displaySummaryPreview($summaryData);

            // Get active webhooks for daily summary
            $webhooks = $this->getActiveDailySummaryWebhooks();

            if ($webhooks->isEmpty()) {
                $this->warn('⚠️  No active daily summary webhooks found');
                $this->info('💡 Create webhooks with event "daily.summary" to receive reports');

                return self::SUCCESS;
            }

            $this->info("🎯 Found {$webhooks->count()} active webhook(s)");

            // Send webhooks
            $successCount = 0;
            $failureCount = 0;

            /** @var Webhook $webhook */
            foreach ($webhooks as $webhook) {
                $this->info("🔄 Sending to: {$webhook->url}");

                if ($this->option('test')) {
                    $this->warn('  🧪 TEST MODE - Not actually sent');
                    $successCount++;

                    continue;
                }

                try {
                    $response = $this->sendWebhookRequest($webhook, $summaryData);

                    if ($response['success']) {
                        $this->info("  ✅ Success: HTTP {$response['status_code']}");
                        $successCount++;
                    } else {
                        $this->error("  ❌ Failed: {$response['error']}");
                        $failureCount++;
                    }

                } catch (Exception $e) {
                    $this->error("  ❌ Exception: {$e->getMessage()}");
                    $failureCount++;
                }
            }

            // Display results
            $this->newLine();
            $this->info('🎉 Daily Summary Webhook Complete!');
            $this->line("   ✅ Successful: {$successCount}");
            $this->line("   ❌ Failed: {$failureCount}");

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Fatal Error: {$e->getMessage()}");
            Log::error('Daily webhook summary failed', [
                'error' => $e->getMessage(),
                'date' => $reportDate ?? null,
            ]);

            return self::FAILURE;
        }
    }

    protected function generateDailySummary(Carbon $date): array
    {
        $this->info('📊 Collecting data...');

        // Get timezone from config
        $timezone = config('app.timezone', 'UTC');
        $reportTimestamp = now($timezone);

        // ===== ENHANCED PAYMENTS SUMMARY - USE CREATION DATE =====
        // Since logs have proper dates but payment_date field has issues,
        // use created_at date which reflects when payment was actually made

        $paymentsQuery = Payment::whereDate('created_at', $date)
            ->where('status', '!=', 'cancelled');

        $paymentsData = [
            'total_amount' => (float) $paymentsQuery->sum('amount') ?: 0.0,
            'total_payers' => $paymentsQuery->distinct('student_id')->count() ?: 0,
            'calculation_method' => 'by_creation_date', // Track which method was used
        ];

        // Debug information to show the difference
        if ($this->option('debug')) {
            $this->line('🔍 Payment Query Comparison:');

            // Original method (by payment_date)
            $originalQuery = Payment::whereDate('payment_date', $date);
            $originalAmount = $originalQuery->sum('amount') ?: 0.0;
            $originalPayers = $originalQuery->distinct('student_id')->count() ?: 0;
            $originalCount = $originalQuery->count() ?: 0;

            // New method (by created_at)
            $newAmount = $paymentsData['total_amount'];
            $newPayers = $paymentsData['total_payers'];
            $newCount = $paymentsQuery->count() ?: 0;

            $this->line("  By payment_date: {$originalCount} payments, ₹{$originalAmount}, {$originalPayers} payers");
            $this->line("  By created_at: {$newCount} payments, ₹{$newAmount}, {$newPayers} payers");

            // Show individual payments for today
            $todayPayments = Payment::whereDate('created_at', $date)->get();
            $this->line("  Today's Payments (by creation):");
            foreach ($todayPayments as $payment) {
                $this->line("    - ID: {$payment->id}, Student: {$payment->student_id}, Amount: ₹{$payment->amount}, Payment Date: {$payment->payment_date}, Created: {$payment->created_at}");
            }
        }

        // ===== ATTENDANCE SUMMARY (KEEP EXISTING LOGIC) =====
        // Get all active students (excluding those on internship)
        $totalActiveStudents = Student::where('status', 'active')
            ->whereHas('batch', function ($q) {
                $q->where('is_on_internship', 0);
            })
            ->count();

        // Create separate queries to avoid conflict
        $presentCount = Attendance::whereDate('attendance_date', $date)
            ->whereHas('student.batch', function ($q) {
                $q->where('is_on_internship', 0);
            })
            ->whereIn('status', ['present', 'late'])
            ->count() ?: 0;

        $absentCount = Attendance::whereDate('attendance_date', $date)
            ->whereHas('student.batch', function ($q) {
                $q->where('is_on_internship', 0);
            })
            ->where('status', 'absent')
            ->count() ?: 0;

        $totalMarkedAttendance = Attendance::whereDate('attendance_date', $date)
            ->whereHas('student.batch', function ($q) {
                $q->where('is_on_internship', 0);
            })
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

            if ($this->option('debug')) {
                $this->line("  - Calculated Absent (unmarked): {$calculatedAbsent}");
            }

            $attendanceData = [
                'present' => $presentCount,
                'absent' => $calculatedAbsent,
                'total_students' => $totalActiveStudents,
                'attendance_percentage' => $totalActiveStudents > 0
                    ? (float) number_format(($presentCount / $totalActiveStudents) * 100, 1)
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
                    ? (float) number_format(($presentCount / $totalForCalculation) * 100, 1)
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
                'report_version' => '1.2', // Updated version
                'working_day' => ! $date->isSunday(),
                'generated_by' => 'automated_scheduler',
                'timezone' => $timezone,
                'server_time' => $reportTimestamp->format('Y-m-d H:i:s T'),
                'payment_query_method' => 'creation_date', // Indicate we're using creation date
                'command_options' => [
                    'test_mode' => $this->option('test'),
                    'forced' => $this->option('force'),
                    'debug' => $this->option('debug'),
                ],
            ],
        ];

        return $payload;
    }

    protected function getActiveDailySummaryWebhooks()
    {
        return Webhook::where('is_active', true)
            ->where(function ($query) {
                $query->where('event_name', 'daily.summary')
                    ->orWhere('event_name', '*'); // Catch-all webhooks
            })
            ->get();
    }

    protected function sendWebhookRequest(Webhook $webhook, array $payload): array
    {
        try {
            // Prepare headers
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => config('app.name', 'Laravel').'/Daily-Summary-Webhook',
                'X-Webhook-Event' => 'daily.summary',
                'X-Webhook-Delivery' => uniqid('delivery_'),
            ];

            // Add signature if secret key exists
            if ($webhook->secret_key || $webhook->signing_secret) {
                $secret = $webhook->secret_key ?? $webhook->signing_secret;
                $signature = hash_hmac('sha256', json_encode($payload), $secret);
                $headers['X-Webhook-Signature'] = 'sha256='.$signature;
            }

            // Make HTTP request with timeout
            $response = Http::timeout($webhook->timeout_seconds ?: 30)
                ->withHeaders($headers)
                ->post($webhook->url, $payload);

            // Log the webhook call if WebhookCall model exists
            if (class_exists(\App\Models\WebhookCall::class)) {
                \App\Models\WebhookCall::create([
                    'webhook_id' => $webhook->id,
                    'payload' => $payload,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'success' => $response->successful(),
                    'execution_time_ms' => 0, // Simplified for now
                    'created_at' => now(),
                ]);
            }

            // Update webhook health status through standard model methods
            if ($response->successful()) {
                $webhook->markAsSuccessful();
            } else {
                $webhook->markAsFailed();
            }

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_time' => 0, // Simplified for now
                'error' => $response->successful() ? null : "HTTP {$response->status()}: {$response->body()}",
            ];

        } catch (Exception $e) {
            // Log the webhook failure
            if (class_exists(\App\Models\WebhookCall::class)) {
                \App\Models\WebhookCall::create([
                    'webhook_id' => $webhook->id,
                    'payload' => $payload,
                    'status_code' => 0,
                    'response_body' => $e->getMessage(),
                    'success' => false,
                    'execution_time_ms' => 0,
                    'created_at' => now(),
                ]);
            }

            $webhook->markAsFailed();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function displaySummaryPreview(array $data): void
    {
        $this->info('📋 Summary Preview:');
        $this->line("   💰 Payments: ₹{$data['payments']['total_amount']} from {$data['payments']['total_payers']} students");

        $attendanceNote = isset($data['attendance']['calculation_method'])
            ? " ({$data['attendance']['calculation_method']})"
            : '';
        $this->line("   👥 Attendance: {$data['attendance']['present']}/{$data['attendance']['total_students']} present ({$data['attendance']['attendance_percentage']}%){$attendanceNote}");

        if (isset($data['attendance']['notes'])) {
            $this->line("   ℹ️  Note: {$data['attendance']['notes']}");
        }

        $this->line("   📅 Report Day: {$data['report_day']}");
        $this->newLine();
    }

    /**
     * Debug attendance data for troubleshooting
     */
    protected function debugAttendanceData(Carbon $date): void
    {
        $this->line("\n🔍 Debugging Attendance Data:");
        $this->line('================================');

        // Check total students
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        $this->line("Total Students: {$totalStudents}");
        $this->line("Active Students: {$activeStudents}");

        // Check attendance table structure
        $tableExists = DB::getSchemaBuilder()->hasTable('attendances');
        $this->line('Attendance Table Exists: '.($tableExists ? 'Yes' : 'No'));

        if ($tableExists) {
            // Check columns
            $columns = DB::getSchemaBuilder()->getColumnListing('attendances');
            $this->line('Attendance Columns: '.implode(', ', $columns));

            // Check total attendance records
            $totalRecords = Attendance::count();
            $this->line("Total Attendance Records: {$totalRecords}");

            // Check records for specific date
            $dateRecords = Attendance::whereDate('attendance_date', $date)->count();
            $this->line("Records for {$date->format('Y-m-d')}: {$dateRecords}");

            // Check status distribution
            $statusCounts = Attendance::whereDate('attendance_date', $date)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            $this->line("Status Distribution for {$date->format('Y-m-d')}:");
            foreach ($statusCounts as $status) {
                $this->line("  - {$status->status}: {$status->count}");
            }

            // Check recent dates with attendance
            $recentDates = Attendance::selectRaw('attendance_date, COUNT(*) as count')
                ->groupBy('attendance_date')
                ->orderBy('attendance_date', 'desc')
                ->limit(5)
                ->get();

            $this->line('Recent Dates with Attendance:');
            foreach ($recentDates as $dateRecord) {
                $this->line("  - {$dateRecord->attendance_date}: {$dateRecord->count} records");
            }
        }

        $this->newLine();
    }
}
