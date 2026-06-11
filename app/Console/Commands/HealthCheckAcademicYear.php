<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;

class HealthCheckAcademicYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:health-check-academic-year';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the health of the academic year configuration and service.';

    /**
     * Execute the console command.
     */
    public function handle(AcademicYearService $service)
    {
        $this->info('Starting Academic Year Health Check...');

        // Check if there is exactly one current year
        $currentYearsCount = AcademicYear::where('is_current', true)->count();
        if ($currentYearsCount === 0) {
            $this->error('FAIL: No academic year is marked as current.');
        } elseif ($currentYearsCount > 1) {
            $this->error("FAIL: Multiple academic years ({$currentYearsCount}) are marked as current. Only one should be current.");
        } else {
            $this->info('PASS: Exactly one academic year is marked as current.');
        }

        // Test the service
        try {
            $activeYear = $service->getCurrentAcademicYear();
            $this->info("PASS: AcademicYearService returned current year '{$activeYear->name}'.");
            
            $activeYearId = $service->getActiveAcademicYearId();
            if ($activeYear->id === $activeYearId) {
                $this->info('PASS: AcademicYearService getActiveAcademicYearId() matches.');
            } else {
                $this->error('FAIL: AcademicYearService ID mismatch.');
            }
        } catch (\App\Exceptions\MissingAcademicYearException $e) {
            $this->error('FAIL: AcademicYearService threw MissingAcademicYearException: ' . $e->getMessage());
        }

        $this->info('Academic Year Health Check completed.');
    }
}
