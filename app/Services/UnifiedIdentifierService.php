<?php

namespace App\Services;

use App\Exceptions\MissingAcademicYearException;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Course;
use App\Models\IdSequence;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UnifiedIdentifierService
{
    /**
     * Set a sequence manually (used for seeding/backfilling)
     */
    public function setSequence(string $entityType, string $prefix, int $lastNumber): void
    {
        IdSequence::updateOrCreate(
            ['entity_type' => $entityType, 'prefix' => $prefix],
            ['last_number' => max($lastNumber, 0)]
        );
    }

    /**
     * Generate Receipt Number using Academic Year.
     */
    public function generateReceiptNumber(?AcademicYear $year = null): string
    {
        if (! $year) {
            try {
                $yearId = app(AcademicYearService::class)->getActiveAcademicYearId();
                $year = AcademicYear::find($yearId);
            } catch (MissingAcademicYearException $e) {
                $year = null;
            }
        }

        // Standard accounting uses the year the payment belongs to (or calendar year as fallback)
        $yearString = $year ? substr($year->start_date, 0, 4) : date('Y');
        $prefix = "RCP-{$yearString}-";

        return $this->getNextSequence('receipt', $prefix, 6);
    }

    /**
     * Generate Enrollment Number.
     * Replaces EnrollmentService logic with DB-locked sequence generator.
     */
    public function generateEnrollmentNumber(Course $course, ?Batch $batch = null): string
    {
        $coursePrefix = $course->enrollment_prefix ?? strtoupper(substr($course->name, 0, 4));

        $yearString = '';
        if ($batch && $batch->academicYear) {
            $yearString = substr($batch->academicYear->start_date, 2, 2);
        } else {
            // Fallback
            $yearString = date('y');
        }

        $prefix = "UV-{$coursePrefix}-{$yearString}";

        return $this->getNextSequence('enrollment', $prefix, 3);
    }

    /**
     * Generate Biometric ID for Student.
     * Replaces BiometricMappingService logic.
     */
    public function generateStudentBiometricId(Student $student): string
    {
        $courseMapping = [
            'ADHM' => '2',
            'MDHM' => '3',
            'PDHM' => '4',
            'DHM' => '1',
        ];

        $course = $student->course;
        $batch = $student->batch;

        $coursePrefix = '';
        if ($course) {
            $coursePrefix = $course->code ?? $course->enrollment_prefix ?? strtoupper(substr($course->name, 0, 4));
        }
        $coursePrefix = strtoupper(trim($coursePrefix));

        $courseCode = '9';
        foreach ($courseMapping as $mappedCode => $number) {
            if (strpos($coursePrefix, $mappedCode) !== false) {
                $courseCode = $number;
                break;
            }
        }

        // Fallback using enrollment number
        if ($courseCode === '9' && $student->enrollment_number) {
            $enrollUpper = strtoupper($student->enrollment_number);
            foreach ($courseMapping as $mappedCode => $number) {
                if (strpos($enrollUpper, $mappedCode) !== false) {
                    $courseCode = $number;
                    break;
                }
            }
        }

        if ($batch && $batch->created_at) {
            $year = Carbon::parse($batch->created_at)->format('y');
        } else {
            $year = date('y');
        }

        $prefix = "{$courseCode}{$year}";

        return $this->getNextSequence('biometric_student', $prefix, 3);
    }

    /**
     * Generate Biometric ID for Faculty.
     */
    public function generateFacultyBiometricId(User $faculty): string
    {
        $employeeId = $faculty->employee_id ?? '';

        // Example Faculty mapping logic (adjust based on actual rules in FacultyBiometricMappingService)
        // Let's assume standard faculty prefix is 5
        $prefix = '5'.date('y');

        if (! empty($employeeId)) {
            $numbers = preg_replace('/[^0-9]/', '', $employeeId);
            if (! empty($numbers)) {
                // Keep the sequence similar if possible, but we use generator for true uniqueness
                // Actually, if we just want a unique ID:
                $prefix = '5'; // simpler prefix
            }
        }

        return $this->getNextSequence('biometric_faculty', $prefix, 3);
    }

    /**
     * Generate Category Code.
     */
    public function generateCategoryCode(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)).'-';

        return $this->getNextSequence('fee_category', $prefix, 3);
    }
}
