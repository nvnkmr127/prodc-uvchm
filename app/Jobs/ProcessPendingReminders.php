<?php

namespace App\Jobs;

use App\Models\PaymentReminder;
use App\Services\PaymentReminderService;
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

    public $timeout = 1800; // 30 minutes timeout
    public $tries = 2;

    private int $batchSize;
    private bool $dispatchIndividualJobs;

    /**
     * Create a new job instance.
     */
    public function __construct(int $batchSize = 50, bool $dispatchIndividualJobs = true)
    {
        $this->batchSize = $batchSize;
        $this->dispatchIndividualJobs = $dispatchIndividualJobs;
        
        // Set queue name
        $this->onQueue('reminders');
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentReminderService $reminderService): void
    {
        try {
            Log::info('Starting to process pending payment reminders', [
                'batch_size' => $this->batchSize,
                'dispatch_individual_jobs' => $this->dispatchIndividualJobs,
                'timestamp' => now()->toDateTimeString()
            ]);

            $processedCount = 0;
            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            do {
                // Get batch of pending reminders
                $pendingReminders = PaymentReminder::where('status', 'pending')
                    ->where('scheduled_date', '<=', now())
                    ->with(['student', 'feeCategory', 'invoice'])
                    ->limit($this->batchSize)
                    ->get();

                if ($pendingReminders->isEmpty()) {
                    break;
                }

                Log::info('Processing batch of reminders', [
                    'batch_count' => $pendingReminders->count(),
                    'total_processed' => $processedCount
                ]);

                foreach ($pendingReminders as $reminder) {
                    $processedCount++;

                    try {
                        // Skip if reminder is too old or invalid
                        if ($this->shouldSkipReminder($reminder)) {
                            $reminder->update(['status' => 'cancelled']);
                            continue;
                        }

                        if ($this->dispatchIndividualJobs) {
                            // Dispatch individual job for each reminder
                            SendPaymentReminder::dispatch($reminder)
                                ->delay($this->calculateDelay($reminder))
                                ->onQueue('reminders');
                            
                            $successCount++;
                            
                            // Mark as processing to avoid duplicate processing
                            $reminder->update(['status' => 'processing']);
                            
                        } else {
                            // Process directly in this job
                            $result = $reminderService->sendReminder($reminder);
                            
                            if ($result['success']) {
                                $successCount++;
                            } else {
                                $failedCount++;
                                $errors[] = [
                                    'reminder_id' => $reminder->id,
                                    'student' => $reminder->student->name ?? 'Unknown',
                                    'error' => $result['error']
                                ];
                            }
                        }

                    } catch (\Exception $e) {
                        $failedCount++;
                        $errors[] = [
                            'reminder_id' => $reminder->id,
                            'student' => $reminder->student->name ?? 'Unknown',
                            'error' => $e->getMessage()
                        ];

                        Log::error('Failed to process individual reminder', [
                            'reminder_id' => $reminder->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Add small delay between batches to prevent overwhelming the system
                if ($pendingReminders->count() === $this->batchSize) {
                    sleep(2);
                }

            } while ($pendingReminders->count() === $this->batchSize);

            // Log final results
            Log::info('Completed processing pending payment reminders', [
                'total_processed' => $processedCount,
                'successful' => $successCount,
                'failed' => $failedCount,
                'errors_count' => count($errors),
                'dispatch_mode' => $this->dispatchIndividualJobs ? 'individual_jobs' : 'direct_processing'
            ]);

            // Log errors if any
            if (!empty($errors)) {
                Log::warning('Errors encountered while processing reminders', [
                    'errors' => array_slice($errors, 0, 10) // Log first 10 errors
                ]);
            }

            // Update defaulter records after processing
            $reminderService->updateDefaulterRecords();

        } catch (\Exception $e) {
            Log::error('Failed to process pending reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Determine if a reminder should be skipped
     */
    private function shouldSkipReminder(PaymentReminder $reminder): bool
    {
        // Skip if reminder is too old (more than 90 days)
        if ($reminder->scheduled_date < now()->subDays(90)) {
            Log::info('Skipping old reminder', [
                'reminder_id' => $reminder->id,
                'scheduled_date' => $reminder->scheduled_date
            ]);
            return true;
        }

        // Skip if student doesn't exist
        if (!$reminder->student) {
            Log::info('Skipping reminder - student not found', [
                'reminder_id' => $reminder->id,
                'student_id' => $reminder->student_id
            ]);
            return true;
        }

        // Skip if invoice is already paid
        if ($reminder->invoice && $reminder->invoice->status === 'paid') {
            Log::info('Skipping reminder - invoice already paid', [
                'reminder_id' => $reminder->id,
                'invoice_id' => $reminder->invoice_id
            ]);
            return true;
        }

        // Skip if student is inactive
        if (isset($reminder->student->is_active) && !$reminder->student->is_active) {
            Log::info('Skipping reminder - student inactive', [
                'reminder_id' => $reminder->id,
                'student_id' => $reminder->student_id
            ]);
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
        Log::error('ProcessPendingReminders job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        // Wait 5 minutes before retry
        return [300];
    }
}