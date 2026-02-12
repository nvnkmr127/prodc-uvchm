<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Webhook;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendBirthdayWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:send-birthday {--date= : The date to check for birthdays (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send birthday webhooks for all active students celebrating their birthday today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateStr = $this->option('date') ?: now()->format('Y-m-d');
        $date = Carbon::parse($dateStr);

        $this->info("Checking for birthdays on: " . $date->format('M d'));

        // Find active students with birthday today
        $students = Student::active()
            ->whereMonth('dob', $date->month)
            ->whereDay('dob', $date->day)
            ->get();

        if ($students->isEmpty()) {
            $this->info("No birthdays found on this date.");
            return 0;
        }

        $this->info("Found " . $students->count() . " active student(s) with birthday today.");

        foreach ($students as $student) {
            try {
                // Add student name and number to webhook data
                $student->addWebhookData('student_name', $student->name);
                $student->addWebhookData('student_mobile', $student->student_mobile);
                $student->addWebhookData('wish_type', 'birthday');

                // Fire the business event which triggers webhooks configured for 'student.birthday'
                $student->fireBusinessEvent('student.birthday', [
                    'name' => $student->name,
                    'mobile' => $student->student_mobile,
                    'enrollment_number' => $student->enrollment_number,
                    'is_active' => true,
                    'triggered_at' => now()->toDateTimeString()
                ]);

                $this->line(" - Webhook triggered for: {$student->name} ({$student->enrollment_number})");

            } catch (\Exception $e) {
                $this->error(" - Failed for student ID {$student->id}: " . $e->getMessage());
                Log::error("Birthday webhook failed for student {$student->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Birthday webhook processing completed.");
        return 0;
    }
}
