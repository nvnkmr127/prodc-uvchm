<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Invoice;
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
    protected $description = 'Clean up duplicate students and invoices created during import issues';

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
        
        // Clean up duplicate students
        $this->cleanupDuplicateStudents($dryRun);
        
        // ✅ NEW: Clean up duplicate mobile numbers
        $this->cleanupDuplicateMobileNumbers($dryRun);
        
        // Clean up duplicate invoices
        $this->cleanupDuplicateInvoices($dryRun);
        
        // Clean up orphaned invoices
        $this->cleanupOrphanedInvoices($dryRun);
        
        $this->info('Cleanup completed!');
    }
    
    private function cleanupDuplicateStudents($dryRun = false)
    {
        $this->info('Checking for duplicate students...');
        
        // Find students with duplicate enrollment numbers
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
                
                // Keep the first student, remove the rest
                $keepStudent = $students->first();
                $duplicates = $students->skip(1);
                
                foreach ($duplicates as $duplicate) {
                    $this->warn("  - Would remove student ID {$duplicate->id} (Created: {$duplicate->created_at})");
                    
                    if (!$dryRun) {
                        // Transfer any invoices to the kept student
                        $duplicate->invoices()->update(['student_id' => $keepStudent->id]);
                        
                        // Delete the duplicate
                        $duplicate->delete();
                        
                        $this->info("  - Removed duplicate student ID {$duplicate->id}");
                    }
                }
            }
        } else {
            $this->info('No duplicate enrollment numbers found');
        }
        
        // Find students with duplicate emails (only if email column exists)
        if (Schema::hasColumn('students', 'email')) {
            $duplicateEmails = Student::select('email')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->groupBy('email')
                ->having(DB::raw('COUNT(*)'), '>', 1)
                ->pluck('email');
                
            if ($duplicateEmails->count() > 0) {
                $this->warn("Found {$duplicateEmails->count()} duplicate email addresses");
                
                foreach ($duplicateEmails as $email) {
                    $students = Student::where('email', $email)
                        ->orderBy('created_at', 'asc')
                        ->get();
                        
                    $this->info("Processing email: {$email}");
                    
                    // Keep the first student, update emails for the rest
                    $keepStudent = $students->first();
                    $duplicates = $students->skip(1);
                    
                    foreach ($duplicates as $duplicate) {
                        $duplicateName = $duplicate->full_name ?? $duplicate->name;
                        $newEmail = $this->generateUniqueEmail($duplicateName);
                        $this->warn("  - Would update student ID {$duplicate->id} email to: {$newEmail}");
                        
                        if (!$dryRun) {
                            $duplicate->update(['email' => $newEmail]);
                            $this->info("  - Updated student ID {$duplicate->id} email");
                        }
                    }
                }
            } else {
                $this->info('No duplicate email addresses found');
            }
        } else {
            $this->info('Email column not found in students table');
        }
    }

    // ✅ NEW: Clean up duplicate mobile numbers
    private function cleanupDuplicateMobileNumbers($dryRun = false)
    {
        $this->info('Checking for duplicate mobile numbers...');
        
        // Check for duplicate student mobile numbers
        $duplicateStudentMobiles = Student::select('student_mobile')
            ->whereNotNull('student_mobile')
            ->where('student_mobile', '!=', '')
            ->groupBy('student_mobile')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->pluck('student_mobile');
            
        if ($duplicateStudentMobiles->count() > 0) {
            $this->warn("Found {$duplicateStudentMobiles->count()} duplicate student mobile numbers");
            
            foreach ($duplicateStudentMobiles as $mobile) {
                $students = Student::where('student_mobile', $mobile)
                    ->orderBy('created_at', 'asc')
                    ->get();
                    
                $this->info("Processing student mobile: {$mobile}");
                
                // Keep the first student, clear mobile for the rest
                $keepStudent = $students->first();
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

        // Check for duplicate father mobile numbers
        $duplicateFatherMobiles = Student::select('father_mobile')
            ->whereNotNull('father_mobile')
            ->where('father_mobile', '!=', '')
            ->groupBy('father_mobile')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->pluck('father_mobile');
            
        if ($duplicateFatherMobiles->count() > 0) {
            $this->warn("Found {$duplicateFatherMobiles->count()} duplicate father mobile numbers");
            
            foreach ($duplicateFatherMobiles as $mobile) {
                $students = Student::where('father_mobile', $mobile)
                    ->orderBy('created_at', 'asc')
                    ->get();
                    
                $this->info("Processing father mobile: {$mobile}");
                
                // Keep the first student, clear mobile for the rest
                $keepStudent = $students->first();
                $duplicates = $students->skip(1);
                
                foreach ($duplicates as $duplicate) {
                    $this->warn("  - Would clear father mobile for student ID {$duplicate->id} ({$duplicate->name})");
                    
                    if (!$dryRun) {
                        $duplicate->update(['father_mobile' => null]);
                        $this->info("  - Cleared father mobile for student ID {$duplicate->id}");
                    }
                }
            }
        } else {
            $this->info('No duplicate father mobile numbers found');
        }
    }
    
    private function cleanupDuplicateInvoices($dryRun = false)
    {
        $this->info('Checking for duplicate invoices...');
        
        // Find invoices with duplicate invoice numbers
        $duplicateInvoiceNumbers = Invoice::select('invoice_number')
            ->groupBy('invoice_number')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->pluck('invoice_number');
            
        if ($duplicateInvoiceNumbers->count() > 0) {
            $this->warn("Found {$duplicateInvoiceNumbers->count()} duplicate invoice numbers");
            
            foreach ($duplicateInvoiceNumbers as $invoiceNumber) {
                $invoices = Invoice::where('invoice_number', $invoiceNumber)
                    ->orderBy('created_at', 'asc')
                    ->get();
                    
                $this->info("Processing invoice number: {$invoiceNumber}");
                
                // Keep the first invoice, remove the rest
                $keepInvoice = $invoices->first();
                $duplicates = $invoices->skip(1);
                
                foreach ($duplicates as $duplicate) {
                    $this->warn("  - Would remove invoice ID {$duplicate->id} (Created: {$duplicate->created_at})");
                    
                    if (!$dryRun) {
                        // Delete invoice items first (if the table exists)
                        if (Schema::hasTable('invoice_items')) {
                            $duplicate->items()->delete();
                        }
                        
                        // Delete the duplicate invoice
                        $duplicate->delete();
                        
                        $this->info("  - Removed duplicate invoice ID {$duplicate->id}");
                    }
                }
            }
        } else {
            $this->info('No duplicate invoice numbers found');
        }
        
        // Find students with multiple invoices (only check by student_id since batch_id may not exist)
        $studentsWithMultipleInvoices = Invoice::select('student_id')
            ->groupBy('student_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();
            
        if ($studentsWithMultipleInvoices->count() > 0) {
            $this->warn("Found {$studentsWithMultipleInvoices->count()} students with multiple invoices");
            
            foreach ($studentsWithMultipleInvoices as $record) {
                $invoices = Invoice::where('student_id', $record->student_id)
                    ->orderBy('created_at', 'asc')
                    ->get();
                    
                $student = Student::find($record->student_id);
                if (!$student) {
                    continue;
                }
                
                $studentName = $student->full_name ?? $student->name;
                $this->info("Processing student: {$studentName} (ID: {$student->id})");
                
                // Keep the first invoice, remove the rest
                $keepInvoice = $invoices->first();
                $duplicates = $invoices->skip(1);
                
                foreach ($duplicates as $duplicate) {
                    $this->warn("  - Would remove invoice ID {$duplicate->id} (Created: {$duplicate->created_at})");
                    
                    if (!$dryRun) {
                        // Delete invoice items first (if the table exists)
                        if (Schema::hasTable('invoice_items')) {
                            $duplicate->items()->delete();
                        }
                        
                        // Delete the duplicate invoice
                        $duplicate->delete();
                        
                        $this->info("  - Removed duplicate invoice ID {$duplicate->id}");
                    }
                }
            }
        } else {
            $this->info('No students with multiple invoices found');
        }
    }
    
    private function cleanupOrphanedInvoices($dryRun = false)
    {
        $this->info('Checking for orphaned invoices...');
        
        $orphanedInvoices = Invoice::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('students')
                ->whereColumn('students.id', 'invoices.student_id');
        })->get();
        
        if ($orphanedInvoices->count() > 0) {
            $this->warn("Found {$orphanedInvoices->count()} orphaned invoices");
            
            foreach ($orphanedInvoices as $invoice) {
                $this->warn("  - Would remove orphaned invoice ID {$invoice->id} (Number: {$invoice->invoice_number})");
                
                if (!$dryRun) {
                    // Delete invoice items first (if the table exists)
                    if (Schema::hasTable('invoice_items')) {
                        $invoice->items()->delete();
                    }
                    
                    $invoice->delete();
                    
                    $this->info("  - Removed orphaned invoice ID {$invoice->id}");
                }
            }
        } else {
            $this->info('No orphaned invoices found');
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