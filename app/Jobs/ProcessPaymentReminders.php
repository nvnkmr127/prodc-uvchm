<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ComponentPaymentReminderService;

class ProcessPaymentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchSize;
    protected $filters;

    public function __construct($batchSize = 50, $filters = [])
    {
        $this->batchSize = $batchSize;
        $this->filters = $filters;
    }

    public function handle()
    {
        $reminderService = app(ComponentPaymentReminderService::class);
        
        // Process reminders in batches to avoid memory issues
        $result = $reminderService->processPendingReminders($this->batchSize, $this->filters);
        
        \Log::info('Processed payment reminders', [
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'batch_size' => $this->batchSize
        ]);
    }
}
