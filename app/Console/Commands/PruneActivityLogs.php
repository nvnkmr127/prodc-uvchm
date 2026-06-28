<?php

namespace App\Console\Commands;

use App\Models\StudentPortalActivityLog;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class PruneActivityLogs extends Command
{
    protected $signature = 'logs:prune {--days=15 : Number of days to retain logs}';

    protected $description = 'Delete activity logs older than the specified number of days (default: 15)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $spatieDeleted = Activity::where('created_at', '<', $cutoff)->delete();
        $portalDeleted = StudentPortalActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned logs older than {$days} days.");
        $this->line("  - Activity logs (Spatie): {$spatieDeleted} deleted");
        $this->line("  - Student portal logs:    {$portalDeleted} deleted");

        return self::SUCCESS;
    }
}
