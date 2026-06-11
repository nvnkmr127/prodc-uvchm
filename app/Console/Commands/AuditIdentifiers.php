<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditIdentifiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:identifiers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit all identifiers across the system to detect duplicates or malformed IDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting System Wide Identifier Audit...');

        $this->auditReceipts();
        $this->auditEnrollments();
        $this->auditBiometricStudents();
        $this->auditBiometricFaculty();

        $this->info('Audit Complete!');
    }

    private function auditReceipts()
    {
        $this->line('Auditing Receipts...');
        
        $duplicates = Payment::withoutGlobalScope('academic_year')
            ->select('receipt_number', DB::raw('count(*) as total'))
            ->whereNotNull('receipt_number')
            ->groupBy('receipt_number')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->error('Found duplicate receipt numbers:');
            foreach ($duplicates as $dup) {
                $this->error(" - {$dup->receipt_number} ({$dup->total} times)");
            }
        } else {
            $this->info('✅ No duplicate receipts found.');
        }
    }

    private function auditEnrollments()
    {
        $this->line('Auditing Enrollments...');
        
        $duplicates = Student::withoutGlobalScope('academic_year')
            ->select('enrollment_number', DB::raw('count(*) as total'))
            ->whereNotNull('enrollment_number')
            ->groupBy('enrollment_number')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->error('Found duplicate enrollment numbers:');
            foreach ($duplicates as $dup) {
                $this->error(" - {$dup->enrollment_number} ({$dup->total} times)");
            }
        } else {
            $this->info('✅ No duplicate enrollments found.');
        }
    }

    private function auditBiometricStudents()
    {
        $this->line('Auditing Student Biometric IDs...');
        
        $duplicates = Student::withoutGlobalScope('academic_year')
            ->select('biometric_employee_code', DB::raw('count(*) as total'))
            ->whereNotNull('biometric_employee_code')
            ->groupBy('biometric_employee_code')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->error('Found duplicate student biometric IDs:');
            foreach ($duplicates as $dup) {
                $this->error(" - {$dup->biometric_employee_code} ({$dup->total} times)");
            }
        } else {
            $this->info('✅ No duplicate student biometric IDs found.');
        }
    }

    private function auditBiometricFaculty()
    {
        $this->line('Auditing Faculty Biometric IDs...');
        
        $duplicates = User::select('biometric_employee_code', DB::raw('count(*) as total'))
            ->whereNotNull('biometric_employee_code')
            ->groupBy('biometric_employee_code')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->error('Found duplicate faculty biometric IDs:');
            foreach ($duplicates as $dup) {
                $this->error(" - {$dup->biometric_employee_code} ({$dup->total} times)");
            }
        } else {
            $this->info('✅ No duplicate faculty biometric IDs found.');
        }
    }
}
