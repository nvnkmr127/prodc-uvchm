<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
// ✅ CHANGED: Import the StudentFee model instead of Invoice
use App\Models\StudentFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CleanupImportDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:cleanup-duplicates {--dry-run : Show what would be cleaned without actually doing it}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up duplicate students and fee components created during import issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in DRY-RUN mode - no changes will be made');
        }
        
        $this->info('Starting cleanup of import duplicates...');
        
        // Clean up duplicate students and their fee components
        $this->cleanupDuplicateStudents($dryRun);
        
        // Clean up duplicate mobile numbers
        $this->cleanupDuplicateMobileNumbers($dryRun);
        
        // ✅ CHANGED: Clean up duplicate fee components instead of invoices
        $this->cleanupDuplicateStudentFees($dryRun);
        
        // ✅ CHANGED: Clean up orphaned fee components instead of invoices
        $this->cleanupOrphanedStudentFees($dryRun);
        
        $this->info('Cleanup completed!');
    }
    
    private function cleanupDuplicateStudents($dryRun = false)
    {
        $this->info('Checking for duplicate students...');
        
        $duplicateEnrollments = Student::select('enrollment_number')
            ->groupBy('enrollment_number')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->pluck('enrollment_number');
            
        if ($duplicateEnrollments->count() > 0) {
            $this->warn("Found {$duplicateEnrollments->count()} duplicate enrollment numbers");
            
            foreach ($duplicateEnrollments as $enrollmentNumber) {
                $students = Student::where('enrollment_number', $enrollmentNumber)
                    ->orderBy('created_at', 'asc')
                    ->get();
                    
                $this->info("Processing enrollment number: {$enrollmentNumber}");
                
                $keepStudent = $students->first();
                $duplicates = $students->skip(1);
                
                foreach ($duplicates as $duplicate) {
                    $this->warn("  - Would remove student ID {$duplicate->id} (Created: {$duplicate->created_at})");
                    
                    if (!$dryRun) {
                        // ✅ CHANGED: Transfer studentFees to the kept student
                        $duplicate->studentFees()->update(['student_id' => $keepStudent->id]);
                        
                        $duplicate->delete();
                        $this->info("  - Removed duplicate student ID {$duplicate->id}");
                    }
                }
            }
        } else {
            $this->info('No duplicate enrollment numbers found');
        }
    }

    private function cleanupDuplicateMobileNumbers($dryRun = false)
    {
        $this->info('Checking for duplicate mobile numbers...');
        
        $duplicateStudentMobiles = Student::select('student_mobile')
            ->whereNotNull('student_mobile')
            ->where('student_mobile', '!=', '')
            ->groupBy('student_mobile')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->pluck('student_mobile');
            
        if ($duplicateStudentMobiles->count() > 0) {
            $this->warn("Found {$duplicateStudentMobiles->count()} duplicate student mobile numbers");
            
            foreach ($duplicateStudentMobiles as $mobile) {
                $students = Student::where('student_mobile', $mobile)->orderBy('created_at', 'asc')->get();
                $this->info("Processing student mobile: {$mobile}");
                
                $duplicates = $students->skip(1);
                
                foreach ($duplicates as $duplicate) {
                    $this->warn("  - Would clear student mobile for student ID {$duplicate->id} ({$duplicate->name})");
                    if (!$dryRun) {
                        $duplicate->update(['student_mobile' => null]);
                        $this->info("  - Cleared student mobile for student ID {$duplicate->id}");
                    }
                }
            }
        } else {
            $this->info('No duplicate student mobile numbers found');
        }
    }
    
    /**
     * ✅ CHANGED: This method now cleans up duplicate StudentFee records.
     * A duplicate is defined as a student having the same fee category twice for the same academic year.
     */
    private function cleanupDuplicateStudentFees($dryRun = false)
    {
        $this->info('Checking for duplicate fee components...');

        $duplicateFees = DB::table('student_fees')
            ->select('student_id', 'fee_category_id', 'academic_year', DB::raw('COUNT(*) as count'))
            ->groupBy('student_id', 'fee_category_id', 'academic_year')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateFees->count() > 0) {
            $this->warn("Found {$duplicateFees->count()} cases of duplicate fee components.");

            foreach ($duplicateFees as $duplicate) {
                $fees = StudentFee::where([
                    'student_id' => $duplicate->student_id,
                    'fee_category_id' => $duplicate->fee_category_id,
                    'academic_year' => $duplicate->academic_year,
                ])
                ->orderBy('created_at', 'asc')
                ->get();

                $studentName = $fees->first()->student->name ?? 'Unknown Student';
                $categoryName = $fees->first()->feeCategory->name ?? 'Unknown Category';
                $this->info("Processing duplicates for Student: {$studentName}, Fee: {$categoryName}, Year: {$duplicate->academic_year}");

                // Keep the first one, remove the rest
                $duplicatesToRemove = $fees->skip(1);

                foreach ($duplicatesToRemove as $feeToRemove) {
                    $this->warn("  - Would remove duplicate StudentFee ID {$feeToRemove->id} (Created: {$feeToRemove->created_at})");

                    if (!$dryRun) {
                        // First, delete any payment items associated with this fee to avoid constraint violations
                        $feeToRemove->componentPaymentItems()->delete();
                        // Then delete the duplicate fee component
                        $feeToRemove->delete();
                        $this->info("  - Removed duplicate StudentFee ID {$feeToRemove->id}");
                    }
                }
            }
        } else {
            $this->info('No duplicate fee components found.');
        }
    }
    
    /**
     * ✅ CHANGED: This method now cleans up orphaned StudentFee records.
     */
    private function cleanupOrphanedStudentFees($dryRun = false)
    {
        $this->info('Checking for orphaned fee components...');
        
        $orphanedFees = StudentFee::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('students')
                ->whereColumn('students.id', 'student_fees.student_id');
        })->get();
        
        if ($orphanedFees->count() > 0) {
            $this->warn("Found {$orphanedFees->count()} orphaned fee components");
            
            foreach ($orphanedFees as $fee) {
                $this->warn("  - Would remove orphaned StudentFee ID {$fee->id} for non-existent student ID {$fee->student_id}");
                
                if (!$dryRun) {
                    // Clean up associated payment items first
                    $fee->componentPaymentItems()->delete();
                    $fee->delete();
                    $this->info("  - Removed orphaned StudentFee ID {$fee->id}");
                }
            }
        } else {
            $this->info('No orphaned fee components found');
        }
    }
    
    private function generateUniqueEmail($name)
    {
        $baseEmail = strtolower(str_replace(' ', '.', $name));
        $baseEmail = preg_replace('/[^a-z0-9.]/', '', $baseEmail);
        $baseEmail = trim($baseEmail, '.');
        
        if (empty($baseEmail)) {
            $baseEmail = 'student' . time() . rand(1000, 9999);
        }
        
        $email = $baseEmail . '@example.com';
        
        $counter = 1;
        while (Student::where('email', $email)->exists()) {
            $email = $baseEmail . $counter . '@example.com';
            $counter++;
        }
        
        return $email;
    }
}