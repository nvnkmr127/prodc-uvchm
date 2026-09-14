<?php

namespace App\Console\Commands;

use App\Services\Attendance\FacultyAttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateFacultyAttendanceHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:update-faculty-holidays 
                           {--from= : Start date (Y-m-d), defaults to start of current month}
                           {--to= : End date (Y-m-d), defaults to today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing faculty attendance records to mark holidays (days with fewer than 2 logins)';

    /**
     * Execute the console command.
     */
    public function handle(FacultyAttendanceService $service): int
    {
        $fromOption = $this->option('from');
        $toOption = $this->option('to');

        $startDate = $fromOption ? Carbon::parse($fromOption) : Carbon::now()->startOfMonth();
        $endDate = $toOption ? Carbon::parse($toOption) : Carbon::now();

        if ($startDate->gt($endDate)) {
            $this->error('Start date cannot be after end date.');
            return 1;
        }

        $this->info("Updating faculty attendance records from {$startDate->toDateString()} to {$endDate->toDateString()}...");

        $summary = $service->updateHistoricalHolidays($startDate, $endDate);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Dates Evaluated', $summary['total_dates']],
                ['Holidays Marked (< 2 logins)', $summary['holidays_marked']],
                ['Working Days (>= 2 logins)', $summary['working_days']],
            ]
        );

        $this->info('Faculty attendance data updated successfully!');
        return 0;
    }
}
