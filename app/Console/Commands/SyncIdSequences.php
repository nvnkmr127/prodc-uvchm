<?php

namespace App\Console\Commands;

use App\Models\IdSequence;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncIdSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:id-sequences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans existing data and initializes the id_sequences table to prevent overlaps';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ID Sequences Synchronization...');

        // 1. Sync Receipts
        $this->syncReceipts();

        // 2. Sync Enrollments
        $this->syncEnrollments();

        // 3. Sync Biometric Students
        $this->syncBiometricStudents();

        $this->info('Synchronization complete!');
    }

    private function syncReceipts()
    {
        $this->info('Syncing Receipts...');

        $payments = Payment::withoutGlobalScope('academic_year')
            ->whereNotNull('receipt_number')
            ->get();

        $maxPerPrefix = [];

        foreach ($payments as $payment) {
            // Receipt format: RCP-YYYY-XXXXXX
            if (preg_match('/^(RCP-\d{4}-)(\d+)$/', $payment->receipt_number, $matches)) {
                $prefix = $matches[1];
                $number = (int)$matches[2];

                if (!isset($maxPerPrefix[$prefix]) || $number > $maxPerPrefix[$prefix]) {
                    $maxPerPrefix[$prefix] = $number;
                }
            }
        }

        foreach ($maxPerPrefix as $prefix => $maxNumber) {
            IdSequence::updateOrCreate(
                ['entity_type' => 'receipt', 'prefix' => $prefix],
                ['last_number' => $maxNumber]
            );
            $this->line("  Updated {$prefix} -> {$maxNumber}");
        }
    }

    private function syncEnrollments()
    {
        $this->info('Syncing Enrollments...');

        $students = Student::withoutGlobalScope('academic_year')
            ->whereNotNull('enrollment_number')
            ->get();

        $maxPerPrefix = [];

        foreach ($students as $student) {
            // Usually UV-ADHM-26001
            // We need to extract letters/hyphens prefix and numeric suffix
            if (preg_match('/^([A-Za-z\-]+)(\d+)$/', $student->enrollment_number, $matches)) {
                $prefix = $matches[1];
                $number = (int)$matches[2];

                if (!isset($maxPerPrefix[$prefix]) || $number > $maxPerPrefix[$prefix]) {
                    $maxPerPrefix[$prefix] = $number;
                }
            }
        }

        foreach ($maxPerPrefix as $prefix => $maxNumber) {
            IdSequence::updateOrCreate(
                ['entity_type' => 'enrollment', 'prefix' => $prefix],
                ['last_number' => $maxNumber]
            );
            $this->line("  Updated {$prefix} -> {$maxNumber}");
        }
    }

    private function syncBiometricStudents()
    {
        $this->info('Syncing Biometric IDs...');

        $students = Student::withoutGlobalScope('academic_year')
            ->whereNotNull('biometric_employee_code')
            ->get();

        $maxPerPrefix = [];

        foreach ($students as $student) {
            // Example: 226047 -> Prefix "226", Sequence "047"
            $code = $student->biometric_employee_code;
            if (strlen($code) >= 3) {
                // Prefix is everything EXCEPT the last 3 digits
                $prefix = substr($code, 0, -3);
                $number = (int)substr($code, -3);

                if (!empty($prefix)) {
                    if (!isset($maxPerPrefix[$prefix]) || $number > $maxPerPrefix[$prefix]) {
                        $maxPerPrefix[$prefix] = $number;
                    }
                }
            }
        }

        foreach ($maxPerPrefix as $prefix => $maxNumber) {
            IdSequence::updateOrCreate(
                ['entity_type' => 'biometric_student', 'prefix' => $prefix],
                ['last_number' => $maxNumber]
            );
            $this->line("  Updated {$prefix} -> {$maxNumber}");
        }
    }
}
