<?php

namespace App\Services;

use App\Exports\UnmappedStudentsExport;
use App\Imports\BiometricMappingImport;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class BiometricMappingService
{
    /**
     * Get biometric mapping statistics
     */
    public function getMappingStats(): array
    {
        $totalStudents = Student::where('status', 'active')->count();
        $mappedStudents = Student::where('status', 'active')
            ->whereNotNull('biometric_employee_code')
            ->count();
        $unmappedStudents = $totalStudents - $mappedStudents;

        return [
            'total_students' => $totalStudents,
            'mapped_students' => $mappedStudents,
            'unmapped_students' => $unmappedStudents,
            'mapping_percentage' => $totalStudents > 0 ? round(($mappedStudents / $totalStudents) * 100, 2) : 0,
        ];
    }

    /**
     * Get unmapped students with suggestions
     */
    public function getUnmappedStudents()
    {
        return Student::where('status', 'active')
            ->whereNull('biometric_employee_code')
            ->with(['batch.course'])
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'batch_name' => $student->batch->name ?? 'No Batch',
                    'course_name' => $student->batch->course->name ?? 'No Course',
                    'suggested_code' => $this->generateBiometricCodeFromEnrollment($student->enrollment_number),
                ];
            });
    }

    /**
     * Generate and assign a biometric code for a single student.
     * Call this immediately after creating a student.
     */
    public function assignBiometricCode(Student $student): void
    {
        // 1. Generate base code from enrollment number
        $generatedCode = $this->generateBiometricCodeFromEnrollment($student->enrollment_number);

        // 2. Ensure uniqueness (Handle collisions)
        $counter = 1;
        $originalCode = $generatedCode;

        while (Student::where('biometric_employee_code', $generatedCode)
            ->where('id', '!=', $student->id)
            ->exists()) {
            $generatedCode = $originalCode.'-'.$counter;
            $counter++;
        }

        // 3. Save to student
        $student->update(['biometric_employee_code' => $generatedCode]);

        Log::info("Automatically assigned biometric code {$generatedCode} to student {$student->name}");
    }

    /**
     * Auto-generate biometric codes for all unmapped students
     */
    public function autoGenerateAllCodes(): array
    {
        $unmappedStudents = Student::where('status', 'active')
            ->whereNull('biometric_employee_code')
            ->get();

        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
        ];

        foreach ($unmappedStudents as $student) {
            try {
                $generatedCode = $this->generateBiometricCodeFromEnrollment($student->enrollment_number);

                // Ensure uniqueness
                $counter = 1;
                $originalCode = $generatedCode;

                while (Student::where('biometric_employee_code', $generatedCode)
                    ->where('id', '!=', $student->id)
                    ->exists()) {
                    $generatedCode = $originalCode.'-'.$counter;
                    $counter++;
                }

                $student->update(['biometric_employee_code' => $generatedCode]);
                $results['success_count']++;

                Log::info('Auto-generated biometric code', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'generated_code' => $generatedCode,
                ]);

            } catch (\Exception $e) {
                $results['error_count']++;
                $results['errors'][] = "Error generating code for {$student->name}: ".$e->getMessage();

                Log::error('Failed to auto-generate biometric code', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Bulk update biometric codes
     */
    public function bulkUpdateCodes(array $mappings): array
    {
        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
        ];

        foreach ($mappings as $mapping) {
            try {
                if (! isset($mapping['student_id']) || ! isset($mapping['biometric_code'])) {
                    $results['error_count']++;
                    $results['errors'][] = 'Missing student_id or biometric_code in mapping';

                    continue;
                }

                $student = Student::find($mapping['student_id']);

                if (! $student) {
                    $results['error_count']++;
                    $results['errors'][] = "Student not found: ID {$mapping['student_id']}";

                    continue;
                }

                // Check for empty codes
                if (empty(trim($mapping['biometric_code']))) {
                    // If empty code, clear the existing one
                    $student->update(['biometric_employee_code' => null]);
                    $results['success_count']++;

                    continue;
                }

                // Validate biometric code format
                if (! preg_match('/^[a-zA-Z0-9\-]+$/', $mapping['biometric_code'])) {
                    $results['error_count']++;
                    $results['errors'][] = "Invalid biometric code format for {$student->name}: {$mapping['biometric_code']}";

                    continue;
                }

                // Check uniqueness
                $existingStudent = Student::where('biometric_employee_code', $mapping['biometric_code'])
                    ->where('id', '!=', $student->id)
                    ->first();

                if ($existingStudent) {
                    $results['error_count']++;
                    $results['errors'][] = "Biometric code '{$mapping['biometric_code']}' already used by {$existingStudent->name}";

                    continue;
                }

                // Update student with enhanced debugging
                Log::info('About to update student biometric code', [
                    'student_id' => $student->id,
                    'current_biometric_code' => $student->biometric_employee_code,
                    'new_biometric_code' => $mapping['biometric_code'],
                    'student_fillable' => $student->getFillable(),
                ]);

                $updateResult = $student->update(['biometric_employee_code' => $mapping['biometric_code']]);

                Log::info('Student update result', [
                    'student_id' => $student->id,
                    'update_result' => $updateResult,
                    'updated_biometric_code' => $student->fresh()->biometric_employee_code,
                ]);

                $results['success_count']++;

                Log::info('Bulk updated biometric code', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'enrollment_number' => $student->enrollment_number,
                    'biometric_code' => $mapping['biometric_code'],
                    'verification_check' => $student->fresh()->biometric_employee_code,
                ]);

            } catch (\Exception $e) {
                $results['error_count']++;
                $results['errors'][] = "Error updating student ID {$mapping['student_id']}: ".$e->getMessage();

                Log::error('Bulk biometric code update failed', [
                    'student_id' => $mapping['student_id'] ?? 'unknown',
                    'biometric_code' => $mapping['biometric_code'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate biometric code with new course mapping
     * DHM(1) + last 3 digits = 1003
     * ADHM(2) + last 3 digits = 2003
     */
    public function generateBiometricCodeFromEnrollment(string $enrollmentNumber): string
    {
        try {
            $courseMapping = [
                'ADHM' => '2',
                'MDHM' => '3',
                'PDHM' => '4',
                'DHM' => '1', // The shortest name is now last for proper matching
            ];

            // Convert to uppercase for case-insensitive comparison
            $enrollmentUpper = strtoupper($enrollmentNumber);

            // Set a default course code in case no mapping is found
            $courseCode = '9';
            foreach ($courseMapping as $course => $number) {
                if (strpos($enrollmentUpper, $course) !== false) {
                    $courseCode = $number;
                    break;
                }
            }

            // Step 1: Extract all numeric characters from the string.
            $allNumbers = preg_replace('/[^0-9]/', '', $enrollmentNumber);

            // Step 2: Take only the last 3 characters from the extracted numbers.
            if (! empty($allNumbers) && strlen($allNumbers) >= 3) {
                $studentNumber = substr($allNumbers, -3);
            } else {
                // Fallback for numbers shorter than 3 digits
                $studentNumber = str_pad($allNumbers, 3, '0', STR_PAD_LEFT);
            }

            // Step 3: Combine the parts to get the final code.
            $biometricCode = $courseCode.$studentNumber;

            return $biometricCode;

        } catch (\Exception $e) {
            // Fallback logic in case of an unexpected error
            Log::error('Error in generateBiometricCodeFromEnrollment', ['error' => $e->getMessage()]);

            $allNumbers = preg_replace('/[^0-9]/', '', $enrollmentNumber);
            $numbers = ! empty($allNumbers) && strlen($allNumbers) >= 3 ? substr($allNumbers, -3) : str_pad($allNumbers, 3, '0', STR_PAD_LEFT);

            return '9'.$numbers;
        }
    }

    /**
     * Export unmapped students to Excel
     */
    public function exportUnmappedStudents()
    {
        return Excel::download(new UnmappedStudentsExport, 'unmapped_students_'.date('Y-m-d').'.xlsx');
    }

    /**
     * Import biometric mappings from Excel/CSV
     */
    public function importBiometricMappings($file): array
    {
        try {
            $import = new BiometricMappingImport;
            Excel::import($import, $file);

            return [
                'success' => true,
                'imported_count' => $import->getImportedCount(),
                'errors' => $import->getErrors(),
            ];
        } catch (\Exception $e) {
            Log::error('Biometric mapping import failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
