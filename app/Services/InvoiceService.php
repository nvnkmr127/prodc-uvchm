<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\FeeStructure;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class InvoiceService
{
    /**
     * Generate invoices for a student based on their batch's fee structure
     */
    public function generateInvoicesForStudent(Student $student): void
    {
        try {
            Log::info('Starting invoice generation for student:', [
                'student_id' => $student->id,
                'batch_id' => $student->batch_id
            ]);

            // Check if student already has invoices to prevent duplicates
            if ($student->invoices()->exists()) {
                Log::info('Student already has invoices, skipping generation:', [
                    'student_id' => $student->id,
                    'existing_invoices' => $student->invoices()->count()
                ]);
                return;
            }

            // Get the fee structure for the student's batch
            $feeStructure = FeeStructure::where('batch_id', $student->batch_id)
                ->with('feeCategories')
                ->first();

            if (!$feeStructure) {
                Log::warning('No fee structure found for batch:', [
                    'batch_id' => $student->batch_id,
                    'student_id' => $student->id
                ]);
                return;
            }

            // Get current academic year
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            if (!$currentAcademicYear) {
                Log::warning('No current academic year found, using default dates');
                $currentAcademicYear = (object) [
                    'id' => null,
                    'start_date' => now()->startOfYear(),
                    'end_date' => now()->endOfYear()
                ];
            }

            // Use database transaction to ensure data consistency
            DB::transaction(function () use ($student, $feeStructure, $currentAcademicYear) {
                // Generate invoice number
                $invoiceNumber = $this->generateInvoiceNumber();

                // Calculate total amount
                $totalAmount = $feeStructure->feeCategories->sum('pivot.amount');

                // ✅ FIX: Create the invoice with issue_date included
                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'student_id' => $student->id,
                    'batch_id' => $student->batch_id,
                    'academic_year_id' => $currentAcademicYear->id,
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'due_amount' => $totalAmount,
                    'status' => 'unpaid', // ✅ Changed from 'pending' to 'unpaid'
                    'issue_date' => now(), // ✅ ADD: Missing issue_date field
                    'due_date' => now()->addDays(30), // 30 days from now
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Create invoice items for each fee category
                foreach ($feeStructure->feeCategories as $category) {
                    $invoice->items()->create([
                        'fee_category_id' => $category->id,
                        'amount' => $category->pivot->amount,
                        'description' => $category->name
                    ]);
                }

                Log::info('Invoice created successfully:', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'student_id' => $student->id,
                    'total_amount' => $totalAmount
                ]);
            });

        } catch (Exception $e) {
            Log::error('Failed to generate invoices for student:', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to be handled by the caller
            throw $e;
        }
    }
    
    /**
     * Generate term invoices for a student (alias for generateInvoicesForStudent)
     */
    public function generateTermInvoicesForStudent(Student $student): void
    {
        $this->generateInvoicesForStudent($student);
    }

    /**
     * Generate a unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $currentYear = date('Y');
        $shortYear = substr($currentYear, -2);
        
        // Get the last invoice number for the current year
        $lastInvoice = Invoice::where('invoice_number', 'LIKE', $prefix . $shortYear . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            // Extract the sequence number from the last invoice
            $lastSequence = (int) substr($lastInvoice->invoice_number, -6);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }
        
        // Format: INV25000001
        $invoiceNumber = $prefix . $shortYear . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
        
        // Ensure uniqueness (failsafe)
        $counter = 1;
        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $newSequence++;
            $invoiceNumber = $prefix . $shortYear . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
            $counter++;
            
            if ($counter > 1000) {
                // Ultimate failsafe: add timestamp
                $invoiceNumber = $prefix . $shortYear . time();
                break;
            }
        }
        
        return $invoiceNumber;
    }

    /**
     * Generate invoices for multiple students (bulk operation)
     */
    public function generateInvoicesForMultipleStudents(array $studentIds): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($studentIds as $studentId) {
            try {
                $student = Student::find($studentId);
                if ($student) {
                    $this->generateInvoicesForStudent($student);
                    $results['success'][] = $studentId;
                } else {
                    $results['failed'][] = [
                        'student_id' => $studentId,
                        'error' => 'Student not found'
                    ];
                }
            } catch (Exception $e) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Check if student can have invoices generated
     */
    public function canGenerateInvoices(Student $student): bool
    {
        // Check if student already has invoices
        if ($student->invoices()->exists()) {
            return false;
        }

        // Check if batch has fee structure
        $feeStructure = FeeStructure::where('batch_id', $student->batch_id)->first();
        if (!$feeStructure) {
            return false;
        }

        return true;
    }

    /**
     * Regenerate invoices for a student (delete existing and create new)
     */
    public function regenerateInvoicesForStudent(Student $student): void
    {
        try {
            DB::transaction(function () use ($student) {
                // Delete existing invoices and their items
                $existingInvoices = $student->invoices;
                foreach ($existingInvoices as $invoice) {
                    $invoice->items()->delete();
                    $invoice->delete();
                }

                // Generate new invoices
                $this->generateInvoicesForStudent($student);
            });

            Log::info('Invoices regenerated successfully for student:', [
                'student_id' => $student->id
            ]);
        } catch (Exception $e) {
            Log::error('Failed to regenerate invoices for student:', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update invoice amounts based on current fee structure
     */
    public function updateInvoiceAmounts(Student $student): void
    {
        try {
            $feeStructure = FeeStructure::where('batch_id', $student->batch_id)
                ->with('feeCategories')
                ->first();

            if (!$feeStructure) {
                throw new Exception('No fee structure found for batch');
            }

            $invoice = $student->invoices()->latest()->first();
            if (!$invoice) {
                throw new Exception('No invoice found for student');
            }

            DB::transaction(function () use ($invoice, $feeStructure) {
                // Delete existing items
                $invoice->items()->delete();

                // Calculate new total
                $totalAmount = $feeStructure->feeCategories->sum('pivot.amount');

                // ✅ FIX: Update invoice with issue_date if it's missing
                $updateData = [
                    'total_amount' => $totalAmount,
                    'due_amount' => $totalAmount - $invoice->paid_amount,
                    'updated_at' => now()
                ];

                // Add issue_date if it's missing
                if (!$invoice->issue_date) {
                    $updateData['issue_date'] = now();
                }

                $invoice->update($updateData);

                // Create new items
                foreach ($feeStructure->feeCategories as $category) {
                    $invoice->items()->create([
                        'fee_category_id' => $category->id,
                        'amount' => $category->pivot->amount,
                        'description' => $category->name
                    ]);
                }
            });

            Log::info('Invoice amounts updated successfully:', [
                'invoice_id' => $invoice->id,
                'student_id' => $student->id
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update invoice amounts:', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get invoice statistics for a batch
     */
    public function getBatchInvoiceStats(int $batchId): array
    {
        $stats = [
            'total_invoices' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'due_amount' => 0,
            'pending_count' => 0,
            'paid_count' => 0,
            'overdue_count' => 0
        ];

        $invoices = Invoice::where('batch_id', $batchId)->get();

        $stats['total_invoices'] = $invoices->count();
        $stats['total_amount'] = $invoices->sum('total_amount');
        $stats['paid_amount'] = $invoices->sum('paid_amount');
        $stats['due_amount'] = $invoices->sum('due_amount');
        $stats['pending_count'] = $invoices->where('status', 'pending')->count();
        $stats['paid_count'] = $invoices->where('status', 'paid')->count();
        $stats['overdue_count'] = $invoices->where('status', 'overdue')->count();

        return $stats;
    }

    /**
     * Mark invoice as paid
     */
    public function markInvoiceAsPaid(Invoice $invoice, float $amount): void
    {
        try {
            DB::transaction(function () use ($invoice, $amount) {
                // ✅ FIX: Ensure issue_date is set when updating
                $updateData = [
                    'paid_amount' => $invoice->paid_amount + $amount,
                    'due_amount' => $invoice->total_amount - ($invoice->paid_amount + $amount),
                    'status' => ($invoice->paid_amount + $amount) >= $invoice->total_amount ? 'paid' : 'partial',
                    'updated_at' => now()
                ];

                // Add issue_date if it's missing
                if (!$invoice->issue_date) {
                    $updateData['issue_date'] = now();
                }

                $invoice->update($updateData);
            });

            Log::info('Invoice marked as paid:', [
                'invoice_id' => $invoice->id,
                'amount' => $amount
            ]);
        } catch (Exception $e) {
            Log::error('Failed to mark invoice as paid:', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update overdue invoices
     */
    public function updateOverdueInvoices(): int
    {
        $overdueCount = 0;
        
        try {
            $overdueInvoices = Invoice::where('status', 'pending')
                ->where('due_date', '<', now())
                ->get();

            foreach ($overdueInvoices as $invoice) {
                // ✅ FIX: Ensure issue_date is set when updating status
                $updateData = ['status' => 'overdue'];
                
                if (!$invoice->issue_date) {
                    $updateData['issue_date'] = $invoice->created_at ?? now();
                }

                $invoice->update($updateData);
                $overdueCount++;
            }

            Log::info('Updated overdue invoices:', [
                'count' => $overdueCount
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update overdue invoices:', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $overdueCount;
    }

    /**
     * Get payment summary for a student
     */
    public function getStudentPaymentSummary(Student $student): array
    {
        $invoices = $student->invoices;
        
        return [
            'total_invoices' => $invoices->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'paid_amount' => $invoices->sum('paid_amount'),
            'due_amount' => $invoices->sum('due_amount'),
            'status' => $this->getOverallPaymentStatus($invoices)
        ];
    }

    /**
     * Get overall payment status for a collection of invoices
     */
    private function getOverallPaymentStatus($invoices): string
    {
        if ($invoices->isEmpty()) {
            return 'no_invoices';
        }

        $totalAmount = $invoices->sum('total_amount');
        $paidAmount = $invoices->sum('paid_amount');

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        } elseif ($paidAmount > 0) {
            return 'partial';
        } elseif ($invoices->where('due_date', '<', now())->count() > 0) {
            return 'overdue';
        } else {
            return 'pending';
        }
    }

    /**
     * Bulk generate invoices for multiple students
     */
    public function bulkGenerateInvoices(array $studentIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($studentIds as $studentId) {
            try {
                $student = Student::find($studentId);
                if (!$student) {
                    $results['failed']++;
                    $results['errors'][] = "Student ID {$studentId} not found";
                    continue;
                }

                if (!$this->canGenerateInvoices($student)) {
                    $results['failed']++;
                    $results['errors'][] = "Cannot generate invoices for student {$student->name} (ID: {$studentId})";
                    continue;
                }

                $this->generateInvoicesForStudent($student);
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Failed to generate invoice for student ID {$studentId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Delete invoice and its items
     */
    public function deleteInvoice(Invoice $invoice): void
    {
        try {
            DB::transaction(function () use ($invoice) {
                $invoice->items()->delete();
                $invoice->delete();
            });

            Log::info('Invoice deleted successfully:', [
                'invoice_id' => $invoice->id
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete invoice:', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}