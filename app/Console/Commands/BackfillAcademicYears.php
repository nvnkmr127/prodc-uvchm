<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Payment;
use App\Models\StudentFee;
use App\Models\Attendance;
use App\Models\Admission;
use App\Models\Enquiry;
use Illuminate\Support\Facades\Schema;

class BackfillAcademicYears extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academic-year:backfill
                            {--year-id= : Specific academic year ID to use for backfill}
                            {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill academic year IDs for existing records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('==============================================');
        $this->info('Academic Year Backfill Command');
        $this->info('==============================================');
        $this->newLine();

        // Get the academic year to use for backfill
        $yearId = $this->option('year-id');
        $isDryRun = $this->option('dry-run');

        if ($yearId) {
            $currentYear = AcademicYear::find($yearId);
            if (!$currentYear) {
                $this->error("Academic year with ID {$yearId} not found.");
                return 1;
            }
        } else {
            $currentYear = AcademicYear::where('is_current', true)->first();
        }

        if (!$currentYear) {
            $this->error('No current academic year found. Please create one first or specify --year-id');
            $this->info('You can create one using: php artisan tinker');
            $this->info('Then run: AcademicYear::create(["name" => "2024-2025", "start_date" => "2024-07-01", "end_date" => "2025-06-30", "is_current" => true])');
            return 1;
        }

        $this->info("Using Academic Year: {$currentYear->name} (ID: {$currentYear->id})");
        $this->newLine();

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $stats = [
            'batches' => 0,
            'payments' => 0,
            'student_fees' => 0,
            'attendances' => 0,
            'admissions' => 0,
            'enquiries' => 0,
        ];

        // Backfill Batches
        $this->info('[1/6] Processing Batches...');
        if (Schema::hasTable('batches') && Schema::hasColumn('batches', 'academic_year_id')) {
            $count = Batch::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->count();
            $stats['batches'] = $count;

            if ($count > 0) {
                $this->line("  Found {$count} batches to update");
                if (!$isDryRun) {
                    Batch::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->update(['academic_year_id' => $currentYear->id]);
                    $this->info("  ✓ Updated {$count} batches");
                } else {
                    $this->warn("  [DRY RUN] Would update {$count} batches");
                }
            } else {
                $this->comment('  No batches to update');
            }
        }
        $this->newLine();

        // Backfill Payments
        $this->info('[2/6] Processing Payments...');
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'academic_year_id')) {
            $count = Payment::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->count();
            $stats['payments'] = $count;

            if ($count > 0) {
                $this->line("  Found {$count} payments to update");
                if (!$isDryRun) {
                    Payment::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->update(['academic_year_id' => $currentYear->id]);
                    $this->info("  ✓ Updated {$count} payments");
                } else {
                    $this->warn("  [DRY RUN] Would update {$count} payments");
                }
            } else {
                $this->comment('  No payments to update');
            }
        }
        $this->newLine();

        // Backfill Student Fees
        $this->info('[3/6] Processing Student Fees...');
        if (Schema::hasTable('student_fees') && Schema::hasColumn('student_fees', 'academic_year_id')) {
            $count = StudentFee::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->count();
            $stats['student_fees'] = $count;

            if ($count > 0) {
                $this->line("  Found {$count} student fees to update");
                if (!$isDryRun) {
                    StudentFee::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->update(['academic_year_id' => $currentYear->id]);
                    $this->info("  ✓ Updated {$count} student fees");
                } else {
                    $this->warn("  [DRY RUN] Would update {$count} student fees");
                }
            } else {
                $this->comment('  No student fees to update');
            }
        }
        $this->newLine();

        // Backfill Attendances
        $this->info('[4/6] Processing Attendances...');
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'academic_year_id')) {
            $count = Attendance::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->count();
            $stats['attendances'] = $count;

            if ($count > 0) {
                $this->line("  Found {$count} attendances to update");
                if (!$isDryRun) {
                    Attendance::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->update(['academic_year_id' => $currentYear->id]);
                    $this->info("  ✓ Updated {$count} attendances");
                } else {
                    $this->warn("  [DRY RUN] Would update {$count} attendances");
                }
            } else {
                $this->comment('  No attendances to update');
            }
        }
        $this->newLine();

        // Backfill Admissions
        $this->info('[5/6] Processing Admissions...');
        if (Schema::hasTable('admissions') && Schema::hasColumn('admissions', 'academic_year_id')) {
            $count = Admission::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->count();
            $stats['admissions'] = $count;

            if ($count > 0) {
                $this->line("  Found {$count} admissions to update");
                if (!$isDryRun) {
                    Admission::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->update(['academic_year_id' => $currentYear->id]);
                    $this->info("  ✓ Updated {$count} admissions");
                } else {
                    $this->warn("  [DRY RUN] Would update {$count} admissions");
                }
            } else {
                $this->comment('  No admissions to update');
            }
        }
        $this->newLine();

        // Backfill Enquiries
        $this->info('[6/6] Processing Enquiries...');
        if (Schema::hasTable('enquiries') && Schema::hasColumn('enquiries', 'academic_year_id')) {
            $count = Enquiry::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->count();
            $stats['enquiries'] = $count;

            if ($count > 0) {
                $this->line("  Found {$count} enquiries to update");
                if (!$isDryRun) {
                    Enquiry::withoutGlobalScope('academic_year')->whereNull('academic_year_id')->update(['academic_year_id' => $currentYear->id]);
                    $this->info("  ✓ Updated {$count} enquiries");
                } else {
                    $this->warn("  [DRY RUN] Would update {$count} enquiries");
                }
            } else {
                $this->comment('  No enquiries to update');
            }
        }
        $this->newLine();

        // Summary
        $this->info('==============================================');
        $this->info('Summary:');
        $this->info('==============================================');

        $totalRecords = array_sum($stats);

        if ($totalRecords > 0) {
            $this->table(
                ['Table', 'Records Updated'],
                [
                    ['Batches', $stats['batches']],
                    ['Payments', $stats['payments']],
                    ['Student Fees', $stats['student_fees']],
                    ['Attendances', $stats['attendances']],
                    ['Admissions', $stats['admissions']],
                    ['Enquiries', $stats['enquiries']],
                    ['TOTAL', $totalRecords],
                ]
            );

            if (!$isDryRun) {
                $this->newLine();
                $this->info("✓ Backfill complete! {$totalRecords} records updated to {$currentYear->name}");
            } else {
                $this->newLine();
                $this->warn("[DRY RUN] Would update {$totalRecords} total records");
                $this->info('Run without --dry-run to apply changes');
            }
        } else {
            $this->comment('No records needed updating. All tables already have academic year data!');
        }

        $this->newLine();
        return 0;
    }
}
