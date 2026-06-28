<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Services\UnifiedIdentifierService;
use Illuminate\Support\Facades\DB;

class ReassignEnrollmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student:reassign-enrollments-yearly 
                            {academic_year : The academic year name (e.g. 2026-2027)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reassign student enrollment numbers for all courses in a specific academic year';

    /**
     * Execute the console command.
     */
    public function handle(UnifiedIdentifierService $identifierService)
    {
        $yearName = $this->argument('academic_year');

        $this->info("Looking up academic year: {$yearName}");

        $year = AcademicYear::where('name', $yearName)->first();

        if (!$year) {
            $this->error("Academic year '{$yearName}' not found.");
            return;
        }

        $batches = Batch::where('academic_year_id', $year->id)->get();

        if ($batches->isEmpty()) {
            $this->warn("No batches found for academic year {$yearName}.");
            return;
        }

        $batchIds = $batches->pluck('id')->toArray();
        
        $students = Student::whereIn('batch_id', $batchIds)->with('course')->get();

        if ($students->isEmpty()) {
            $this->warn("No students found in the batches for {$yearName}.");
            return;
        }

        $this->info("Found {$students->count()} students. Reassigning numbers...");

        $prefixesProcessed = [];

        DB::transaction(function () use ($students, $year, &$prefixesProcessed) {
            // Extract the 2-digit year prefix part (e.g., '26' from '2026-06-01')
            // Using logic from UnifiedIdentifierService
            $yearString = substr($year->start_date, 2, 2);

            foreach ($students as $student) {
                $course = $student->course;
                if (!$course) continue;

                $oldEnrollment = $student->enrollment_number;
                
                // Skip if old enrollment doesn't exist
                if (!$oldEnrollment) continue;

                // Extract suffix digits using regex
                if (!preg_match('/(\d+)$/', $oldEnrollment, $matches)) {
                    $this->warn("Could not extract suffix from: {$oldEnrollment}. Skipping.");
                    continue;
                }

                $suffixStr = $matches[1];
                $suffixInt = (int) $suffixStr;

                // Build the new prefix
                $coursePrefix = $course->enrollment_prefix ?? strtoupper(substr($course->name, 0, 4));
                $newPrefix = "UV-{$coursePrefix}-{$yearString}";

                // Build the exact new enrollment
                // UnifiedIdentifierService uses pad length 3
                $newEnrollment = $newPrefix . str_pad($suffixStr, 3, '0', STR_PAD_LEFT);

                if ($oldEnrollment !== $newEnrollment) {
                    $student->enrollment_number = $newEnrollment;
                    $student->save();
                    $this->line("Updated: {$oldEnrollment} -> {$newEnrollment}");
                }

                if (!isset($prefixesProcessed[$newPrefix]) || $suffixInt > $prefixesProcessed[$newPrefix]) {
                    $prefixesProcessed[$newPrefix] = $suffixInt;
                }
            }
        });

        foreach ($prefixesProcessed as $prefix => $maxSuffix) {
            $identifierService->setSequence('enrollment', $prefix, $maxSuffix);
            $this->info("Sequence for '{$prefix}' successfully updated to continue from {$maxSuffix}.");
        }

        $this->info('Enrollment reassignment complete!');
    }
}
