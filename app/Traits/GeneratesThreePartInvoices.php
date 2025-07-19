<?php

namespace App\Traits;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Support\Str;
use Carbon\Carbon;

trait GeneratesThreePartInvoices
{
    /**
     * Generate a 3-part invoice plan for a given student.
     *
     * @param Student $student
     * @return void
     */
    protected function generateInvoicesForStudent(Student $student)
    {
        // Ensure the student and their batch/course exist
        if (!$student || !$student->batch || !$student->batch->course) {
            return; // Cannot generate invoices without this information
        }

        // 1. Fetch the Fee Structure for the student's course
        $feeItems = FeeStructure::where('course_id', $student->batch->course_id)->get();
        
        if ($feeItems->isNotEmpty()) {
            $totalCourseFee = $feeItems->sum('amount');
            $discountPercentage = (float) setting('womens_discount_percentage', 0);
            $concessionAmount = 0;
            $concessionNotes = null;

            // 2. Check for gender and calculate discount automatically
            if ($student->gender === 'Female' && $discountPercentage > 0) {
                $concessionAmount = ($totalCourseFee * $discountPercentage) / 100;
                $concessionNotes = "Women's Discount (" . $discountPercentage . "%) Applied";
            }
            
            $payableAmount = $totalCourseFee - $concessionAmount;
            $installmentAmount = round($payableAmount / 3, 2);

            // 3. Create three installment invoices with different due dates
            $dueDates = [
                now()->addDays(15), // 1st term due in 15 days
                now()->addMonths(4), // 2nd term due in 4 months
                now()->addMonths(8), // 3rd term due in 8 months
            ];

            for ($i = 0; $i < 3; $i++) {
                $invoice = Invoice::create([
                    'token' => Str::uuid(),
                    'student_id' => $student->id,
                    'invoice_number' => 'INV-TERM' . ($i + 1) . '-S' . $student->id,
                    'issue_date' => now(),
                    'due_date' => $dueDates[$i],
                    'total_amount' => $installmentAmount,
                    'concession_amount' => ($i === 0) ? $concessionAmount : 0,
                    'concession_notes' => ($i === 0) ? $concessionNotes : null,
                    'paid_amount' => 0,
                    'due_amount' => ($i === 0) ? $installmentAmount - $concessionAmount : $installmentAmount,
                    'status' => 'unpaid',
                ]);

                // Add a single clear line item to the invoice
                $invoice->items()->create(['description' => 'Installment ' . ($i + 1) . ' Fee', 'amount' => $installmentAmount]);
            }
        }
    }
}
