<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Admission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DiagnoseAcademicYear extends Command
{
    protected $signature = 'academic-year:diagnose';
    protected $description = 'Diagnose academic year filtering issues';

    public function handle()
    {
        $this->info('==============================================');
        $this->info('Academic Year Diagnostic Report');
        $this->info('==============================================');
        $this->newLine();

        // Check academic years
        $this->info('[1] Academic Years:');
        $years = AcademicYear::all();
        if ($years->isEmpty()) {
            $this->error('  ❌ No academic years found!');
        } else {
            foreach ($years as $year) {
                $current = $year->is_current ? ' [CURRENT]' : '';
                $this->line("  - ID: {$year->id} | Name: {$year->name}{$current}");
            }
        }
        $this->newLine();

        // Check session
        $this->info('[2] Session Data:');
        $sessionYearId = session('selected_academic_year_id', 'Not set');
        $this->line("  Selected Year ID in Session: {$sessionYearId}");
        $this->newLine();

        // Check batches
        $this->info('[3] Batches:');
        if (Schema::hasTable('batches') && Schema::hasColumn('batches', 'academic_year_id')) {
            $totalBatches = Batch::count();
            $batchesWithYear = Batch::whereNotNull('academic_year_id')->count();
            $batchesWithoutYear = Batch::whereNull('academic_year_id')->count();

            $this->line("  Total: {$totalBatches}");
            $this->line("  With academic_year_id: {$batchesWithYear}");
            if ($batchesWithoutYear > 0) {
                $this->error("  ❌ Without academic_year_id: {$batchesWithoutYear}");
            } else {
                $this->info("  ✓ Without academic_year_id: {$batchesWithoutYear}");
            }

            // Show distribution
            if ($totalBatches > 0) {
                $distribution = Batch::select('academic_year_id', DB::raw('count(*) as count'))
                    ->groupBy('academic_year_id')
                    ->get();
                $this->line("  Distribution by Year:");
                foreach ($distribution as $dist) {
                    $yearName = $dist->academic_year_id ? AcademicYear::find($dist->academic_year_id)?->name : 'NULL';
                    $this->line("    - Year {$yearName}: {$dist->count} batches");
                }
            }
        } else {
            $this->error('  ❌ batches table or academic_year_id column not found');
        }
        $this->newLine();

        // Check students
        $this->info('[4] Students:');
        $totalStudents = Student::count();
        $this->line("  Total Students: {$totalStudents}");
        if ($totalStudents > 0) {
            $studentsPerYear = Student::join('batches', 'students.batch_id', '=', 'batches.id')
                ->select('batches.academic_year_id', DB::raw('count(*) as count'))
                ->groupBy('batches.academic_year_id')
                ->get();
            $this->line("  Distribution by Year (via batch):");
            foreach ($studentsPerYear as $dist) {
                $yearName = $dist->academic_year_id ? AcademicYear::find($dist->academic_year_id)?->name : 'NULL';
                $this->line("    - Year {$yearName}: {$dist->count} students");
            }
        }
        $this->newLine();

        // Check payments
        $this->info('[5] Payments:');
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'academic_year_id')) {
            $totalPayments = Payment::count();
            $paymentsWithYear = Payment::whereNotNull('academic_year_id')->count();
            $paymentsWithoutYear = Payment::whereNull('academic_year_id')->count();

            $this->line("  Total: {$totalPayments}");
            $this->line("  With academic_year_id: {$paymentsWithYear}");
            if ($paymentsWithoutYear > 0) {
                $this->error("  ❌ Without academic_year_id: {$paymentsWithoutYear}");
            } else {
                $this->info("  ✓ Without academic_year_id: {$paymentsWithoutYear}");
            }

            if ($totalPayments > 0) {
                $distribution = Payment::select('academic_year_id', DB::raw('count(*) as count'))
                    ->groupBy('academic_year_id')
                    ->get();
                $this->line("  Distribution by Year:");
                foreach ($distribution as $dist) {
                    $yearName = $dist->academic_year_id ? AcademicYear::find($dist->academic_year_id)?->name : 'NULL';
                    $this->line("    - Year {$yearName}: {$dist->count} payments");
                }
            }
        } else {
            $this->error('  ❌ payments table or academic_year_id column not found');
        }
        $this->newLine();

        // Check admissions
        $this->info('[6] Admissions:');
        if (Schema::hasTable('admissions') && Schema::hasColumn('admissions', 'academic_year_id')) {
            $totalAdmissions = Admission::count();
            $admissionsWithYear = Admission::whereNotNull('academic_year_id')->count();
            $admissionsWithoutYear = Admission::whereNull('academic_year_id')->count();

            $this->line("  Total: {$totalAdmissions}");
            $this->line("  With academic_year_id: {$admissionsWithYear}");
            if ($admissionsWithoutYear > 0) {
                $this->error("  ❌ Without academic_year_id: {$admissionsWithoutYear}");
            } else {
                $this->info("  ✓ Without academic_year_id: {$admissionsWithoutYear}");
            }

            if ($totalAdmissions > 0) {
                $distribution = Admission::select('academic_year_id', DB::raw('count(*) as count'))
                    ->groupBy('academic_year_id')
                    ->get();
                $this->line("  Distribution by Year:");
                foreach ($distribution as $dist) {
                    $yearName = $dist->academic_year_id ? AcademicYear::find($dist->academic_year_id)?->name : 'NULL';
                    $this->line("    - Year {$yearName}: {$dist->count} admissions");
                }
            }
        } else {
            $this->error('  ❌ admissions table or academic_year_id column not found');
        }
        $this->newLine();

        $this->info('==============================================');
        $this->info('Recommendations:');
        $this->info('==============================================');

        if ($years->isEmpty()) {
            $this->warn('1. Create academic years first');
        }

        if (Batch::whereNull('academic_year_id')->count() > 0) {
            $this->warn('2. Run: php artisan academic-year:backfill');
        }

        if (Payment::whereNull('academic_year_id')->count() > 0) {
            $this->warn('3. Run: php artisan academic-year:backfill');
        }

        $this->newLine();
        return 0;
    }
}
