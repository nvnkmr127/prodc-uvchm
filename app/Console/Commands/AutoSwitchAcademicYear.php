<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSwitchAcademicYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academic-year:auto-switch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically switch to the next academic year based on configured start date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $today = now()->startOfDay();

            // Find academic years that should be auto-switched today
            $yearToActivate = AcademicYear::where('auto_switch_enabled', true)
                ->whereDate('start_date', '<=', $today)
                ->where('is_current', false)
                ->orderBy('start_date', 'desc')
                ->first();

            if (! $yearToActivate) {
                $this->info('No academic year needs to be switched today.');
                Log::info('Auto-switch check: No academic year needs switching.');

                return 0;
            }

            // Switch to the new academic year
            DB::transaction(function () use ($yearToActivate) {
                // Deactivate all current academic years
                AcademicYear::query()->update(['is_current' => false]);

                // Activate the new academic year
                $yearToActivate->update(['is_current' => true]);
            });

            $this->info("Successfully switched to academic year: {$yearToActivate->name}");
            Log::info("Auto-switched to academic year: {$yearToActivate->name}", [
                'year_id' => $yearToActivate->id,
                'start_date' => $yearToActivate->start_date,
            ]);

            // Send notification to admin (optional)
            $this->notifyAdmin($yearToActivate);

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to auto-switch academic year: '.$e->getMessage());
            Log::error('Academic year auto-switch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Notify admin about the academic year switch
     */
    private function notifyAdmin(AcademicYear $academicYear)
    {
        // You can implement notification logic here
        // For example, send email or create a system notification
        try {
            // Example: Create a system notification
            \App\Models\SystemNotification::create([
                'title' => 'Academic Year Automatically Switched',
                'message' => "The system has automatically switched to academic year: {$academicYear->name}",
                'type' => 'info',
                'user_id' => 1, // Admin user ID
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send admin notification for academic year switch', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
