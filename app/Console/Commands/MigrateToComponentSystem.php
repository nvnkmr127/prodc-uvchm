<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\FeeCategory;
use Illuminate\Support\Facades\DB;

class MigrateToComponentSystem extends Command
{
    protected $signature = 'migrate:component-system {--dry-run : Run without making changes} {--batch-size=100 : Number of records to process at once}';
    protected $description = 'Migrate from invoice system to component-based payment system';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = $this->option('batch-size');

        $this->info('Starting migration to component system...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Phase 1: Migrate invoices to student fees
        $this->migrateInvoicesToStudentFees($isDryRun, $batchSize);

        // Phase 2: Migrate payments to component system
        $this->migratePaymentsToComponents($isDryRun, $batchSize);

        // Phase 3: Create missing fee components
        $this->createMissingFeeComponents($isDryRun);

        // Phase 4: Validate migration
        $this->validateMigration();

        $this->info('Migration completed successfully!');
    }

    private function migrateInvoicesToStudentFees($isDryRun, $batchSize)
    {
        $this->info('Step 1: Migrating invoices to student fees...');

        $totalInvoices = Invoice::count();
        $this->info("Found {$totalInvoices} invoices to migrate");

        $migratedCount = 0;
        $generalFeeCategory = FeeCategory::firstOrCreate(['name' => 'General Fee']);

        Invoice::with(['student', 'items.feeCategory'])
            ->chunk($batchSize, function ($invoices) use ($isDryRun, &$migratedCount, $generalFeeCategory) {
                foreach ($invoices as $invoice) {
                    if (!$invoice->student) {
                        $this->warn("Skipping invoice {$invoice->id} - no student found");
                        continue;
                    }

                    if (!$isDryRun) {
                        // Create student fees from invoice items
                        if ($invoice->items->count() > 0) {
                            foreach ($invoice->items as $item) {
                                StudentFee::create([
                                    'student_id' => $invoice->student_id,
                                    'fee_category_id' => $item->fee_category_id,
                                    'academic_year' => $this->getAcademicYearFromDate($invoice->issue_date),
                                    'amount' => $item->amount,
                                    'paid_amount' => $this->calculatePaidAmountForItem($invoice, $item),
                                    'due_date' => $invoice->due_date,
                                    'status' => $this->mapInvoiceStatusToFeeStatus($invoice->status),
                                    'installment_number' => $invoice->term_number ?? 1,
                                    'total_installments' => 1
                                ]);
                            }
                        } else {
                            // Create single fee for invoices without items
                            StudentFee::create([
                                'student_id' => $invoice->student_id,
                                'fee_category_id' => $generalFeeCategory->id,
                                'academic_year' => $this->getAcademicYearFromDate($invoice->issue_date),
                                'amount' => $invoice->total_amount,
                                'concession_amount' => $invoice->concession_amount ?? 0,
                                'paid_amount' => $invoice->paid_amount ?? 0,
                                'due_date' => $invoice->due_date,
                                'status' => $this->mapInvoiceStatusToFeeStatus($invoice->status),
                                'installment_number' => $invoice->term_number ?? 1,
                                'total_installments' => 1
                            ]);
                        }
                    }

                    $migratedCount++;
                }
            });

        $this->info("Migrated {$migratedCount} invoices to fee components");
    }

    private function migratePaymentsToComponents($isDryRun, $batchSize)
    {
        $this->info('Step 2: Migrating payments to component system...');

        $totalPayments = Payment::whereNotNull('invoice_id')->count();
        $this->info("Found {$totalPayments} payments to migrate");

        $migratedCount = 0;

        Payment::with(['invoice.student', 'invoice.items'])
            ->whereNotNull('invoice_id')
            ->chunk($batchSize, function ($payments) use ($isDryRun, &$migratedCount) {
                foreach ($payments as $payment) {
                    if (!$payment->invoice || !$payment->invoice->student) {
                        continue;
                    }

                    if (!$isDryRun) {
                        // Update payment with component details
                        $componentDetails = $this->buildComponentDetailsFromInvoice($payment->invoice);
                        
                        $payment->update([
                            'student_id' => $payment->invoice->student_id,
                            'payment_type' => 'component',
                            'component_details' => $componentDetails,
                            'receipt_number' => $this->generateReceiptNumber($payment),
                            'academic_year' => $this->getAcademicYearFromDate($payment->payment_date)
                        ]);

                        // Create component payment items
                        $this->createComponentPaymentItems($payment);
                    }

                    $migratedCount++;
                }
            });

        $this->info("Migrated {$migratedCount} payments to component system");
    }

    private function createMissingFeeComponents($isDryRun)
    {
        $this->info('Step 3: Creating missing fee components for students...');

        $students = Student::with(['batch.feeStructure.feeCategories'])->get();
        $createdCount = 0;
        $currentAcademicYear = $this->getCurrentAcademicYear();

        foreach ($students as $student) {
            if (!$student->batch || !$student->batch->feeStructure) {
                continue;
            }

            foreach ($student->batch->feeStructure->feeCategories as $category) {
                $existingFee = StudentFee::where([
                    'student_id' => $student->id,
                    'fee_category_id' => $category->id,
                    'academic_year' => $currentAcademicYear
                ])->first();

                if (!$existingFee && !$isDryRun) {
                    StudentFee::create([
                        'student_id' => $student->id,
                        'fee_structure_id' => $student->batch->feeStructure->id,
                        'fee_category_id' => $category->id,
                        'academic_year' => $currentAcademicYear,
                        'amount' => $category->pivot->amount ?? 0,
                        'due_date' => now()->addDays(30),
                        'status' => 'unpaid',
                        'installment_number' => 1,
                        'total_installments' => 1
                    ]);

                    $createdCount++;
                }
            }
        }

        $this->info("Created {$createdCount} new fee components");
    }

    private function validateMigration()
    {
        $this->info('Step 4: Validating migration...');

        $studentsWithFees = Student::whereHas('studentFees')->count();
        $totalStudents = Student::count();
        $totalFees = StudentFee::count();
        $componentPayments = Payment::where('payment_type', 'component')->count();

        $this->info("Students with fees: {$studentsWithFees}/{$totalStudents}");
        $this->info("Total fee components: {$totalFees}");
        $this->info("Component payments: {$componentPayments}");

        // Validate data integrity
        $orphanedFees = StudentFee::whereDoesntHave('student')->count();
        $orphanedPayments = Payment::whereDoesntHave('student')->count();

        if ($orphanedFees > 0) {
            $this->warn("Found {$orphanedFees} orphaned fee records");
        }

        if ($orphanedPayments > 0) {
            $this->warn("Found {$orphanedPayments} orphaned payment records");
        }

        $this->info('Validation completed');
    }

    // Helper methods
    private function getAcademicYearFromDate($date): string
    {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));
        
        if ($month >= 4) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    private function getCurrentAcademicYear(): string
    {
        return $this->getAcademicYearFromDate(now());
    }

    private function mapInvoiceStatusToFeeStatus($invoiceStatus): string
    {
        return match($invoiceStatus) {
            'paid' => 'paid',
            'partially_paid', 'partial' => 'partial',
            'unpaid' => 'unpaid',
            'overdue' => 'overdue',
            default => 'unpaid'
        };
    }

    private function calculatePaidAmountForItem($invoice, $item): float
    {
        if ($invoice->items->count() == 1) {
            return $invoice->paid_amount ?? 0;
        }

        // Proportional distribution for multiple items
        $totalInvoiceAmount = $invoice->items->sum('amount');
        $itemProportion = $item->amount / $totalInvoiceAmount;
        
        return ($invoice->paid_amount ?? 0) * $itemProportion;
    }

    private function buildComponentDetailsFromInvoice($invoice): array
    {
        $components = [];
        
        if ($invoice->items->count() > 0) {
            foreach ($invoice->items as $item) {
                $components[] = [
                    'fee_category_id' => $item->fee_category_id,
                    'amount' => $this->calculatePaidAmountForItem($invoice, $item)
                ];
            }
        } else {
            $generalFeeCategory = FeeCategory::firstOrCreate(['name' => 'General Fee']);
            $components[] = [
                'fee_category_id' => $generalFeeCategory->id,
                'amount' => $invoice->paid_amount ?? 0
            ];
        }

        return $components;
    }

    private function createComponentPaymentItems($payment)
    {
        foreach ($payment->component_details as $component) {
            $studentFee = StudentFee::where([
                'student_id' => $payment->student_id,
                'fee_category_id' => $component['fee_category_id'],
                'academic_year' => $payment->academic_year
            ])->first();

            if ($studentFee) {
                DB::table('component_payment_items')->insert([
                    'payment_id' => $payment->id,
                    'student_fee_id' => $studentFee->id,
                    'amount_paid' => $component['amount'],
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at
                ]);
            }
        }
    }

    private function generateReceiptNumber($payment): string
    {
        return 'RCP' . date('Y', strtotime($payment->payment_date)) . 
               str_pad($payment->id, 6, '0', STR_PAD_LEFT);
    }
}