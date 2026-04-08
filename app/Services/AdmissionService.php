<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Course;
use App\Models\FeeStructure;
use App\Models\Student; // MODIFIED: Replaced Invoice with StudentFee
use App\Models\StudentFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdmissionService
{
    protected $biometricService;

    // Inject the service via constructor
    public function __construct(BiometricMappingService $biometricService)
    {
        $this->biometricService = $biometricService;
    }

    /**
     * Processes an approved admission application.
     * This method handles student creation, enrollment number generation,
     * and dynamic fee plan creation within a single database transaction.
     *
     * @param  Admission  $admission  The admission record to be processed.
     * @return Student The newly created student record.
     */
    public function processApprovedAdmission(Admission $admission): Student
    {
        // Use a database transaction to ensure all operations succeed or none do.
        // This prevents data inconsistencies if an error occurs.
        return DB::transaction(function () use ($admission) {

            // 1. Find the course and its fee structure. Abort if not found.
            $course = Course::findOrFail($admission->course_id);
            // MODIFIED: Eager load fee categories with the fee structure
            $feeStructure = FeeStructure::with('feeCategories')->where('course_id', $course->id)->first();

            if (! $feeStructure) {
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

            // 4. MODIFIED: Generate fee components based on the fee structure.
            $this->generateFeeComponentsForStudent($student, $feeStructure);

            // Automatically generate Biometric ID
            $this->biometricService->assignBiometricCode($student);

            // 5. Update the admission status to 'approved'.
            $admission->update(['status' => 'approved']);

            // 6. Return the newly created student.
            return $student;
        });
    }

    /**
     * Generates a unique enrollment number, preventing race conditions.
     */
    private function generateSafeEnrollmentNumber(Course $course): string
    {
        $prefix = $course->enrollment_prefix ?? strtoupper(Str::limit($course->name, 4, ''));
        $year = date('y');

        // Lock the students table to prevent other processes from creating a student simultaneously.
        $latestStudent = Student::where('enrollment_number', 'LIKE', $prefix.'-'.$year.'%')
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
        return $prefix.'-'.$year.'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * MODIFIED: Generates fee components for a student based on a fee structure.
     * This replaces the old invoice generation logic.
     */
    private function generateFeeComponentsForStudent(Student $student, FeeStructure $feeStructure): void
    {
        $paymentTerms = $feeStructure->payment_terms ?: 1; // Default to 1 term if not set

        // Iterate over each fee category in the structure (e.g., Tuition, Lab, Library)
        foreach ($feeStructure->feeCategories as $category) {
            $totalCategoryAmount = $category->pivot->amount;
            $installmentAmount = round($totalCategoryAmount / $paymentTerms, 2);

            // Create a StudentFee record for each installment of the category
            for ($i = 1; $i <= $paymentTerms; $i++) {
                // Distribute due dates over the course duration
                $dueDate = now()->addMonths(($i - 1) * (12 / $paymentTerms));

                StudentFee::create([
                    'student_id' => $student->id,
                    'fee_structure_id' => $feeStructure->id,
                    'fee_category_id' => $category->id,
                    'academic_year' => now()->year.'-'.(now()->year + 1),
                    'installment_number' => $i,
                    'total_installments' => $paymentTerms,
                    'amount' => $installmentAmount,
                    'due_date' => $dueDate,
                    'status' => 'unpaid',
                ]);
            }
        }

        // NOTE: The women's discount logic could be applied here as a concession
        // using the ComponentPaymentService if a specific fee category is targeted for the discount.
        // For simplicity, this example creates the standard fees. A separate concession
        // can be applied later via the UI or another service call.
    }
}
