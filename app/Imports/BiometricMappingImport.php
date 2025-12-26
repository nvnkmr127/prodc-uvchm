<?php
namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

class BiometricMappingImport implements ToModel, WithHeadingRow, WithValidation
{
    private $importedCount = 0;
    private $errors = [];

    public function model(array $row)
    {
        try {
            // Log the raw row data for debugging
            Log::info('Processing import row', ['row' => $row]);
            
            // Handle multiple header formats: student_id or enrollment_number
            $student = null;
            
            // Try different student ID column names (with and without spaces)
            $studentId = $row['student_id'] ?? $row['Student ID'] ?? null;
            $enrollmentNumber = $row['enrollment_number'] ?? $row['Enrollment Number'] ?? null;
            
            if (!empty($studentId)) {
                $student = Student::find($studentId);
            } elseif (!empty($enrollmentNumber)) {
                $student = Student::where('enrollment_number', $enrollmentNumber)->first();
            }

            if (!$student) {
                $error = "Student not found: " . ($studentId ?? $enrollmentNumber ?? 'Unknown');
                $this->errors[] = $error;
                Log::warning('Student not found during import', [
                    'student_id' => $studentId,
                    'enrollment_number' => $enrollmentNumber,
                    'available_keys' => array_keys($row)
                ]);
                return null;
            }

            // Get biometric code and ensure it's a string
            $biometricCode = $row['biometric_code'] ?? 
                           $row['biometric_code_fill_this'] ?? 
                           $row['Biometric Code (Fill This)'] ?? 
                           $row['Biometric Code'] ?? 
                           '';

            // Convert to string and trim (handles numbers from Excel)
            $biometricCode = trim((string) $biometricCode);

            Log::info('Found biometric code', [
                'student_id' => $student->id,
                'biometric_code' => $biometricCode,
                'available_keys' => array_keys($row)
            ]);

            if (empty($biometricCode)) {
                // If empty, clear existing biometric code
                $student->update(['biometric_employee_code' => null]);
                $this->importedCount++;
                Log::info('Cleared biometric code for student', [
                    'student_id' => $student->id,
                    'student_name' => $student->name
                ]);
                return null;
            }

            // Validate format
            if (!preg_match('/^[a-zA-Z0-9\-]+$/', $biometricCode)) {
                $error = "Invalid biometric code format for {$student->name}: {$biometricCode}";
                $this->errors[] = $error;
                Log::warning('Invalid biometric code format', [
                    'student_id' => $student->id,
                    'biometric_code' => $biometricCode
                ]);
                return null;
            }

            // Check uniqueness
            $existingStudent = Student::where('biometric_employee_code', $biometricCode)
                ->where('id', '!=', $student->id)
                ->first();

            if ($existingStudent) {
                $error = "Biometric code '{$biometricCode}' already used by {$existingStudent->name}";
                $this->errors[] = $error;
                Log::warning('Duplicate biometric code', [
                    'biometric_code' => $biometricCode,
                    'existing_student' => $existingStudent->name,
                    'current_student' => $student->name
                ]);
                return null;
            }

            // Update the student
            $student->update(['biometric_employee_code' => $biometricCode]);
            $this->importedCount++;

            Log::info('Successfully imported biometric code', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'biometric_code' => $biometricCode
            ]);

            return null; // We handle the update manually

        } catch (\Exception $e) {
            $error = "Error processing row: " . $e->getMessage();
            $this->errors[] = $error;
            Log::error('Biometric import row error', [
                'row' => $row,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function rules(): array
    {
        return [
            '*.student_id' => 'nullable',  // Allow any type, convert in model()
            '*.Student ID' => 'nullable',
            '*.enrollment_number' => 'nullable',
            '*.Enrollment Number' => 'nullable',
            '*.biometric_code' => 'nullable',  // Allow any type, convert in model()
            '*.biometric_code_fill_this' => 'nullable',
            '*.Biometric Code (Fill This)' => 'nullable',
            '*.Biometric Code' => 'nullable',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}