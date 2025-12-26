<?php

namespace App\Jobs;

use App\Models\PaymentReminder;
use App\Services\ComponentPaymentReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessPendingReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes
    public $tries = 2;

    public function handle(ComponentPaymentReminderService $reminderService): void
    {
        try {
            Log::info('Starting to process pending payment reminders');

            $processedCount = 0;
            $sentCount = 0;
            $failedCount = 0;
            $skippedCount = 0;
            $errors = [];

            // Get pending reminders that are due for processing
            $pendingReminders = PaymentReminder::with(['student', 'studentFee'])
                ->where('status', 'pending')
                ->where('scheduled_date', '<=', now())
                ->orderBy('scheduled_date')
                ->limit(500) // Process in batches
                ->get();

            Log::info('Found pending reminders to process', [
                'count' => $pendingReminders->count()
            ]);

            foreach ($pendingReminders as $reminder) {
                try {
                    $processedCount++;

                    // Check if reminder should be skipped
                    if ($this->shouldSkipReminder($reminder)) {
                        $skippedCount++;
                        continue;
                    }

                    // Process the reminder
                    if (config('payment_reminders.use_queue', false)) {
                        // Dispatch individual jobs with delay
                        SendPaymentReminder::dispatch($reminder)
                            ->delay($this->calculateDelay($reminder));
                        $sentCount++;
                        
                        Log::info('Reminder queued for processing', [
                            'reminder_id' => $reminder->id,
                            'student_id' => $reminder->student_id
                        ]);
                    } else {
                        // Process directly
                        $result = $reminderService->sendReminder($reminder);
                        
                        if ($result['success']) {
                            $sentCount++;
                        } else {
                            $failedCount++;
                            $errors[] = "Reminder {$reminder->id}: " . ($result['error'] ?? 'Unknown error');
                        }
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Reminder {$reminder->id}: " . $e->getMessage();
                    
                    Log::error('Error processing individual reminder', [
                        'reminder_id' => $reminder->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log final results
            Log::info('Completed processing pending reminders', [
                'processed' => $processedCount,
                'sent' => $sentCount,
                'failed' => $failedCount,
                'skipped' => $skippedCount,
                'processing_method' => config('payment_reminders.use_queue', false) 
                    ? 'individual_jobs' : 'direct_processing'
            ]);

            // Log errors if any
            if (!empty($errors)) {
                Log::warning('Errors encountered while processing reminders', [
                    'errors' => array_slice($errors, 0, 10) // Log first 10 errors
                ]);
            }

            // Update defaulter records after processing
            if (method_exists($reminderService, 'updateComponentDefaulterRecords')) {
                $reminderService->updateComponentDefaulterRecords();
            }

        } catch (\Exception $e) {
            Log::error('Failed to process pending reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Determine if a reminder should be skipped (Component-based checks)
     */
    private function shouldSkipReminder(PaymentReminder $reminder): bool
    {
        // Skip if reminder is too old (more than 90 days)
        if ($reminder->scheduled_date < now()->subDays(90)) {
            Log::info('Skipping old reminder', [
                'reminder_id' => $reminder->id,
                'scheduled_date' => $reminder->scheduled_date
            ]);
            $reminder->update(['status' => 'expired']);
            return true;
        }

        // Skip if student doesn't exist
        if (!$reminder->student) {
            Log::info('Skipping reminder - student not found', [
                'reminder_id' => $reminder->id,
                'student_id' => $reminder->student_id
            ]);
            $reminder->update(['status' => 'cancelled']);
            return true;
        }

        // ✅ FIXED: Check if student fee component is already paid (component-based)
        if ($reminder->studentFee) {
            $remainingAmount = $reminder->studentFee->amount 
                             - $reminder->studentFee->concession_amount 
                             - $reminder->studentFee->paid_amount;
            
            if ($remainingAmount <= 0) {
                Log::info('Skipping reminder - fee component already paid', [
                    'reminder_id' => $reminder->id,
                    'student_fee_id' => $reminder->student_fee_id,
                    'remaining_amount' => $remainingAmount
                ]);
                $reminder->update(['status' => 'cancelled']);
                return true;
            }
        }

        // Skip if student is inactive
        if (isset($reminder->student->is_active) && !$reminder->student->is_active) {
            Log::info('Skipping reminder - student inactive', [
                'reminder_id' => $reminder->id,
                'student_id' => $reminder->student_id
            ]);
            $reminder->update(['status' => 'cancelled']);
            return true;
        }

        return false;
    }

    /**
     * Calculate delay for individual job dispatch
     */
    private function calculateDelay(PaymentReminder $reminder): Carbon
    {
        // Add small random delay to spread out the load
        $randomSeconds = rand(0, 300); // 0-5 minutes
        
        // Add extra delay based on channel priority
        $channelDelays = [
            'email' => 0,
            'sms' => 60,      // 1 minute
            'whatsapp' => 120, // 2 minutes
            'phone_call' => 300 // 5 minutes
        ];
        
        $channelDelay = $channelDelays[$reminder->channel] ?? 0;
        
        return now()->addSeconds($randomSeconds + $channelDelay);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPendingReminders job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}