<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\Attendance\AttendanceService;
use App\Models\Webhook;
use App\Models\Setting;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookCall;

class SendDailyAbsentAlerts extends Command
{
    protected $signature = 'attendance:send-daily-absent-webhook {--force : Ignore time checks and sent flags}';
    protected $description = 'Send individual absent alerts (Excludes Internships & Holidays)';

    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        parent::__construct();
        $this->attendanceService = $attendanceService;
    }

    public function handle()
    {
        Log::channel('attendance-webhook')->info("🏁 Starting SendDailyAbsentAlerts command.");

        $this->info('⏳ Checking conditions for absent alerts...');

        // 1. Time Check
        $cutoffTimeStr = Setting::where('key', 'attendance_student_present_cutoff_time')->value('value') ?? '11:00';
        $cutoffTime = Carbon::createFromTimeString($cutoffTimeStr);

        if (!$this->option('force') && now()->lt($cutoffTime)) {
            $this->info("⚠️  Current time (" . now()->format('H:i') . ") is before cutoff ($cutoffTimeStr). Skipping.");
            return 0;
        }

        // 2. Duplicate Check
        $lockKey = "absent_webhook_sent_" . now()->format('Y-m-d');
        if (!$this->option('force') && Setting::where('key', $lockKey)->exists()) {
            $this->info("✅ Absent alerts already sent for today.");
            return 0;
        }

        // 3. Biometric Data Sufficiency Check
        $biometricCount = Attendance::whereDate('attendance_date', now())
            ->whereNotNull('device_id')
            ->whereIn('status', ['present', 'late'])
            ->count();

        $this->info("📊 Biometric Presence Count: $biometricCount");
        Log::channel('attendance-webhook')->info("Biometric Presence Count: $biometricCount");

        if (!$this->option('force') && $biometricCount < 10) {
            $msg = "⚠️  Only $biometricCount students marked present. Threshold is 10. Skipping.";
            $this->warn($msg);
            Log::channel('attendance-webhook')->warning($msg);
            return 0;
        }

        // 4. Fetch Absent Students
        $this->info("🔍 Fetching absent students...");
        $absentStudents = $this->attendanceService->getAbsentStudentsForDate(now());



        if ($absentStudents->isEmpty()) {
            $msg = "✅ No absent students found (or all are on internship).";
            $this->info($msg);
            Log::channel('attendance-webhook')->info($msg);
            Setting::updateOrCreate(['key' => $lockKey], ['value' => now()->toDateTimeString()]);
            return 0;
        }

        // 5. Find Webhooks
        $webhooks = Webhook::where('is_active', true)
            ->where(function ($q) {
                $q->where('event_name', 'attendance.daily_absent')
                    ->orWhere('event_name', '*');
            })->get();

        if ($webhooks->isEmpty()) {
            $msg = "❌ No webhooks configured for 'attendance.daily_absent'.";
            $this->error($msg);
            Log::channel('attendance-webhook')->error($msg);
            return 0;
        }

        // 6. Send Webhooks
        $this->info("🚀 Sending " . $absentStudents->count() . " alerts...");
        Log::channel('attendance-webhook')->info("🚀 Processing " . $absentStudents->count() . " absent students.");

        $bar = $this->output->createProgressBar($absentStudents->count());
        $bar->start();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($absentStudents as $student) {
            $studentName = $student['name'] ?? 'Unknown';
            $studentId = $student['id'] ?? 'Unknown';

            Log::channel('attendance-webhook')->info("Processing Student: $studentName (ID: $studentId)");

            $payload = [
                'event' => 'attendance.daily_absent',
                'timestamp' => now()->toIso8601String(),
                'date' => now()->format('Y-m-d'),
                'student' => $student,
                'metadata' => [
                    'total_absent_today' => $absentStudents->count(),
                    'biometric_present_count' => $biometricCount
                ]
            ];

            foreach ($webhooks as $webhook) {
                try {
                    $this->sendWebhook($webhook, $payload);
                    $sentCount++;
                    Log::channel('attendance-webhook')->info("  -> Webhook sent successfully to: {$webhook->url}");
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::channel('attendance-webhook')->error("  -> Webhook failed for student $studentName: " . $e->getMessage());
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // 7. Mark as Sent
        Setting::updateOrCreate(['key' => $lockKey], ['value' => now()->toDateTimeString()]);

        $summary = "✅ Process completed. Sent: $sentCount, Failed: $failedCount.";
        $this->info($summary);
        Log::channel('attendance-webhook')->info($summary);

        return 0;
    }

    private function sendWebhook($webhook, $payload)
    {
        $headers = ['Content-Type' => 'application/json', 'X-Webhook-Event' => 'attendance.daily_absent'];

        if ($webhook->signing_secret) {
            $headers['X-Webhook-Signature'] = 'sha256=' . hash_hmac('sha256', json_encode($payload), $webhook->signing_secret);
        }

        $startTime = microtime(true);
        $status = 0;
        $success = false;
        $responseBody = null;

        try {
            $response = Http::timeout(5)->withHeaders($headers)->post($webhook->url, $payload);
            $status = $response->status();
            $success = $response->successful();
            $responseBody = $response->body();

            // Throw exception for non-successful responses to trigger the catch block in handle()
            // ensuring failedCount is updated and error is logged in file
            $response->throw();

        } catch (\Exception $e) {
            $success = false;
            $responseBody = $e->getMessage();

            // If it's a generic connection error, status remains 0. 
            // If it's a request exception with response (from throw()), get status.
            if ($e instanceof \Illuminate\Http\Client\RequestException && $e->response) {
                $status = $e->response->status();
                $responseBody = $e->response->body();
            }

            throw $e; // Re-throw to handle()
        } finally {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            WebhookCall::create([
                'webhook_id' => $webhook->id,
                'success' => $success,
                'status_code' => $status,
                'payload' => $payload,
                'response_body' => substr($responseBody, 0, 65535), // Limit size for text column
                'execution_time_ms' => $duration
            ]);
        }
    }
}