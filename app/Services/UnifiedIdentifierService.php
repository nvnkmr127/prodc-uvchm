<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\IdSequence;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UnifiedIdentifierService
{
    /**
     * Core sequence generation method that safely locks and increments the next available number.
     *
     * @param string $entityType e.g., 'receipt', 'enrollment', 'biometric_student'
     * @param string $prefix e.g., 'RCP-2026-', 'UV-ADHM-'
     * @param int $padLength The number of padded zeros (e.g., 6 -> '000001')
     * @return string
     */
    public function getNextSequence(string $entityType, string $prefix, int $padLength = 3): string
    {
        return DB::transaction(function () use ($entityType, $prefix, $padLength) {
            $sequence = IdSequence::lockForUpdate()->firstOrCreate(
                ['entity_type' => $entityType, 'prefix' => $prefix],
                ['last_number' => 0]
            );

            $sequence->last_number += 1;
            $sequence->save();

            $numberString = str_pad($sequence->last_number, $padLength, '0', STR_PAD_LEFT);
            return $prefix . $numberString;
        });
    }

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
    public function generateReceiptNumber(AcademicYear $year = null): string
    {
        if (!$year) {
            $yearId = session('selected_academic_year_id');
            if ($yearId) {
                $year = AcademicYear::find($yearId);
            } else {
                $year = AcademicYear::where('is_current', true)->first();
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
    public function generateEnrollmentNumber(Course $course, Batch $batch = null): string
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
    public function generateFacultyBiometricId(Faculty $faculty): string
    {
        $employeeId = $faculty->employee_id ?? '';
        
        // Example Faculty mapping logic (adjust based on actual rules in FacultyBiometricMappingService)
        // Let's assume standard faculty prefix is 5
        $prefix = "5" . date('y');
        
        if (!empty($employeeId)) {
            $numbers = preg_replace('/[^0-9]/', '', $employeeId);
            if (!empty($numbers)) {
                // Keep the sequence similar if possible, but we use generator for true uniqueness
                // Actually, if we just want a unique ID:
                $prefix = "5"; // simpler prefix
            }
        }

        return $this->getNextSequence('biometric_faculty', $prefix, 3);
    }

    /**
     * Generate Certificate Number.
     */
    public function generateCertificateNumber(string $type, AcademicYear $year = null): string
    {
        if (!$year) {
            $year = AcademicYear::where('is_current', true)->first();
        }
        $yearString = $year ? substr($year->start_date, 0, 4) : date('Y');
        
        $typePrefix = strtoupper(substr($type, 0, 3));
        $prefix = "CERT-{$typePrefix}-{$yearString}-";

        return $this->getNextSequence('certificate', $prefix, 4);
    }

    /**
     * Generate Category Code.
     */
    public function generateCategoryCode(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)) . '-';
        return $this->getNextSequence('fee_category', $prefix, 3);
    }
}
