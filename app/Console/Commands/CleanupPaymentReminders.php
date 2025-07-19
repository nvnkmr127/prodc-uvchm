<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentReminder;
use Carbon\Carbon;

class CleanupPaymentReminders extends Command
{
    protected $signature = 'reminders:cleanup {--days=30 : Days to keep reminders}';
    protected $description = 'Clean up old payment reminder records';

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("🧹 Cleaning up payment reminders older than {$days} days...");
        
        $deletedCount = PaymentReminder::where('created_at', '<', $cutoffDate)
            ->where('status', '!=', 'pending')
            ->delete();
            
        $this->info("✅ Cleaned up {$deletedCount} old reminder records");
        
        return 0;
    }
}