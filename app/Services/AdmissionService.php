<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Course;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdmissionService
{
    /**
     * Processes an approved admission application.
     * This method handles student creation, enrollment number generation,
     * and dynamic fee plan creation within a single database transaction.
     *
     * @param Admission $admission The admission record to be processed.
     * @return Student The newly created student record.
     */
    public function processApprovedAdmission(Admission $admission): Student
    {
        // Use a database transaction to ensure all operations succeed or none do.
        // This prevents data inconsistencies if an error occurs.
        return DB::transaction(function () use ($admission) {

            // 1. Find the course and its fee structure. Abort if not found.
            $course = Course::findOrFail($admission->course_id);
            $feeStructure = FeeStructure::where('course_id', $course->id)->first();

            if (!$feeStructure) {
                // Throw an exception to automatically roll back the transaction.
                throw new \Exception("No fee structure found for the course '{$course->name}'. Please create one first.");
            }

            // 2. Generate a safe and unique enrollment number.
            $enrollmentNumber = $this->generateSafeEnrollmentNumber($course);

            // 3. Create the student record from the admission data.
            $student = Student::create([
                'admission_id' => $admission->id,
                'name' => $admission->full_name,
                'email' => $admission->email,
                'gender' => $admission->gender, // This relies on the gender field being present
                'student_mobile' => $admission->phone_number,
                'village' => $admission->address,
                'admission_date' => now(),
                'enrollment_number' => $enrollmentNumber,
                'status' => 'active',
            ]);

            // 4. Generate invoices based on the fee structure.
            $this->generateInvoicesForStudent($student, $feeStructure);

            // 5. Update the admission status to 'approved'.
            $admission->update(['status' => 'approved']);

            // 6. Return the newly created student.
            return $student;
        });
    }

    /**
     * Generates a unique enrollment number, preventing race conditions.
     *
     * @param Course $course
     * @return string
     */
    private function generateSafeEnrollmentNumber(Course $course): string
    {
        $prefix = $course->enrollment_prefix ?? strtoupper(Str::limit($course->name, 4, ''));
        $year = date('y');
        
        // Lock the students table to prevent other processes from creating a student simultaneously.
        $latestStudent = Student::where('enrollment_number', 'LIKE', $prefix . '-' . $year . '%')
                                ->lockForUpdate()
                                ->orderBy('id', 'desc')
                                ->first();

        $nextId = 1;
        if ($latestStudent) {
            // Extract the last number from the enrollment ID and increment it.
            $lastNumber = (int) substr($latestStudent->enrollment_number, -4);
            $nextId = $lastNumber + 1;
        }
        
        // Pad the number with leading zeros to ensure a consistent length (e.g., 0001, 0002).
        return $prefix . '-' . $year . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generates installment invoices for a student based on a fee structure.
     *
     * @param Student $student
     * @param FeeStructure $feeStructure
     */
    private function generateInvoicesForStudent(Student $student, FeeStructure $feeStructure): void
    {
        $totalCourseFee = $feeStructure->total_amount;
        $numberOfTerms = $feeStructure->payment_terms ?: 1; // Default to 1 term if not set
        $discountPercentage = (float) setting('womens_discount_percentage', 0);
        $concessionAmount = 0;
        $concessionNotes = null;

        // Calculate discount if applicable
        if ($student->gender === 'Female' && $discountPercentage > 0) {
            $concessionAmount = ($totalCourseFee * $discountPercentage) / 100;
            $concessionNotes = "Women's Discount (" . $discountPercentage . "%) Applied";
        }
        
        $payableAmount = $totalCourseFee - $concessionAmount;
        $installmentAmount = round($payableAmount / $numberOfTerms, 2);

        // Create an invoice for each payment term
        for ($i = 0; $i < $numberOfTerms; $i++) {
            // Distribute due dates over the course duration
            $dueDate = now()->addMonths($i * (12 / $numberOfTerms));

            $invoice = Invoice::create([
                'token' => Str::uuid(),
                'student_id' => $student->id,
                'invoice_number' => 'INV-T' . ($i + 1) . '-S' . $student->id,
                'issue_date' => now(),
                'due_date' => $dueDate,
                'total_amount' => $installmentAmount,
                // Apply the entire concession to the first installment only
                'concession_amount' => ($i === 0) ? $concessionAmount : 0,
                'concession_notes' => ($i === 0) ? $concessionNotes : null,
                'paid_amount' => 0,
                'due_amount' => ($i === 0) ? $installmentAmount : $installmentAmount, // Initial due amount
                'status' => 'unpaid',
            ]);
            
            // Add a clear line item to the invoice
            $invoice->items()->create(['description' => 'Term ' . ($i + 1) . ' Fee', 'amount' => $installmentAmount]);
        }
    }
}
