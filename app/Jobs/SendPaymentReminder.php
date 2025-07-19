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

class SendPaymentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Maximum number of attempts

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PaymentReminder $paymentReminder
    ) {
        // Set queue connection and queue name
        $this->onQueue('reminders');
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentReminderService $reminderService): void
    {
        try {
            Log::info('Processing payment reminder job', [
                'reminder_id' => $this->paymentReminder->id,
                'student_id' => $this->paymentReminder->student_id,
                'channel' => $this->paymentReminder->channel,
                'attempt' => $this->attempts()
            ]);

            // Check if reminder is still valid and pending
            if ($this->paymentReminder->status !== 'pending') {
                Log::info('Skipping reminder - status not pending', [
                    'reminder_id' => $this->paymentReminder->id,
                    'status' => $this->paymentReminder->status
                ]);
                return;
            }

            // Check if invoice is still unpaid
            if ($this->paymentReminder->invoice && $this->paymentReminder->invoice->status === 'paid') {
                Log::info('Skipping reminder - invoice already paid', [
                    'reminder_id' => $this->paymentReminder->id,
                    'invoice_id' => $this->paymentReminder->invoice_id
                ]);
                
                $this->paymentReminder->update(['status' => 'cancelled']);
                return;
            }

            // Send the reminder
            $result = $reminderService->sendReminder($this->paymentReminder);

            if ($result['success']) {
                Log::info('Payment reminder sent successfully', [
                    'reminder_id' => $this->paymentReminder->id,
                    'channel' => $this->paymentReminder->channel
                ]);
            } else {
                Log::error('Payment reminder failed', [
                    'reminder_id' => $this->paymentReminder->id,
                    'error' => $result['error'] ?? 'Unknown error',
                    'attempt' => $this->attempts()
                ]);
                
                // If this is the last attempt, mark as failed
                if ($this->attempts() >= $this->tries) {
                    $this->paymentReminder->markAsFailed(
                        'Max attempts reached: ' . ($result['error'] ?? 'Unknown error')
                    );
                }
                
                throw new \Exception($result['error'] ?? 'Failed to send reminder');
            }

        } catch (\Exception $e) {
            Log::error('Payment reminder job failed', [
                'reminder_id' => $this->paymentReminder->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Payment reminder job permanently failed', [
            'reminder_id' => $this->paymentReminder->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark the reminder as failed
        $this->paymentReminder->markAsFailed(
            'Job failed after ' . $this->tries . ' attempts: ' . $exception->getMessage()
        );
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        // Exponential backoff: 1 minute, 5 minutes, 15 minutes
        return [60, 300, 900];
    }

    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil(): \DateTime
    {
        // Retry for up to 2 hours
        return now()->addHours(2);
    }
}