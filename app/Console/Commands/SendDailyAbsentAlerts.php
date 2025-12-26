<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\Attendance\AttendanceService;
use App\Models\Webhook;
use App\Models\Setting;
use App\Models\Attendance;
use Carbon\Carbon;

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
        // Prevents false alerts if machine is off or holiday
        $biometricCount = Attendance::whereDate('attendance_date', now())
            ->whereNotNull('device_id')
            ->whereIn('status', ['present', 'late'])
            ->count();

        $this->info("📊 Biometric Presence Count: $biometricCount");

        if (!$this->option('force') && $biometricCount < 10) {
            $this->warn("⚠️  Only $biometricCount students marked present. Threshold is 10.");
            $this->line("   Assuming Holiday or Network Issue. Skipping alerts.");
            return 0; // Exit without locking so it retries later if data comes in
        }

        // 4. Fetch Absent Students (Internship batches are excluded here)
        $this->info("🔍 Fetching absent students...");
        $absentStudents = $this->attendanceService->getAbsentStudentsForDate(now());

        if ($absentStudents->isEmpty()) {
            $this->info("✅ No absent students found (or all are on internship).");
            // Mark as sent to stop retrying today
            Setting::updateOrCreate(['key' => $lockKey], ['value' => now()->toDateTimeString()]);
            return 0;
        }

        // 5. Find Webhooks
        $webhooks = Webhook::where('is_active', true)
            ->where(function($q) {
                $q->where('event_name', 'attendance.daily_absent')
                  ->orWhere('event_name', '*');
            })->get();

        if ($webhooks->isEmpty()) {
            $this->error("❌ No webhooks configured for 'attendance.daily_absent'.");
            return 0;
        }

        // 6. Send Webhooks
        $this->info("🚀 Sending " . $absentStudents->count() . " alerts...");
        $bar = $this->output->createProgressBar($absentStudents->count());
        $bar->start();

        foreach ($absentStudents as $student) {
            $payload = [
                'event' => 'attendance.daily_absent',
                'timestamp' => now()->toIso8601String(),
                'date' => now()->format('Y-m-d'),
                'student' => $student, // Includes father_name & parent_phone
                'metadata' => [
                    'total_absent_today' => $absentStudents->count(),
                    'biometric_present_count' => $biometricCount
                ]
            ];

            foreach ($webhooks as $webhook) {
                try {
                    $this->sendWebhook($webhook, $payload);
                } catch (\Exception $e) {
                    // Log error but continue
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // 7. Mark as Sent (Lock for the day)
        Setting::updateOrCreate(['key' => $lockKey], ['value' => now()->toDateTimeString()]);
        
        $this->info("✅ Process completed successfully.");
        return 0;
    }

    private function sendWebhook($webhook, $payload)
    {
        $headers = ['Content-Type' => 'application/json', 'X-Webhook-Event' => 'attendance.daily_absent'];

        if ($webhook->signing_secret) {
            $headers['X-Webhook-Signature'] = 'sha256=' . hash_hmac('sha256', json_encode($payload), $webhook->signing_secret);
        }

        Http::timeout(5)->withHeaders($headers)->post($webhook->url, $payload);
    }
}