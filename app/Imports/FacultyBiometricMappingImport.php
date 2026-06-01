<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class FacultyBiometricMappingImport implements ToModel, WithHeadingRow, WithValidation
{
    private $importedCount = 0;

    private $errors = [];

    public function model(array $row)
    {
        try {
            // Log the raw row data for debugging
            Log::info('Processing faculty import row', ['row' => $row]);

            $faculty = null;

            // Try different faculty ID column names (with and without spaces/underscores)
            $facultyId = $row['faculty_id'] ?? $row['Faculty ID'] ?? $row['id'] ?? null;
            $employeeId = $row['employee_id'] ?? $row['Employee ID'] ?? null;

            if (! empty($facultyId)) {
                $faculty = User::role('staff')->find($facultyId);
            } elseif (! empty($employeeId)) {
                $faculty = User::role('staff')->where('employee_id', $employeeId)->first();
            }

            if (! $faculty) {
                $error = 'Faculty not found: '.($facultyId ?? $employeeId ?? 'Unknown');
                $this->errors[] = $error;
                Log::warning('Faculty not found during import', [
                    'faculty_id' => $facultyId,
                    'employee_id' => $employeeId,
                    'available_keys' => array_keys($row),
                ]);

                return null;
            }

            // Get biometric code and ensure it's a string
            $biometricCode = $row['biometric_code'] ??
                           $row['biometric_code_fill_this'] ??
                           $row['Biometric Code (Fill This)'] ??
                           $row['Biometric Code'] ??
                           '';

            // Convert to string and trim
            $biometricCode = trim((string) $biometricCode);

            Log::info('Found biometric code for faculty', [
                'faculty_id' => $faculty->id,
                'biometric_code' => $biometricCode,
                'available_keys' => array_keys($row),
            ]);

            if (empty($biometricCode)) {
                // If empty, clear existing biometric code
                $faculty->update(['biometric_employee_code' => null]);
                $this->importedCount++;
                Log::info('Cleared biometric code for faculty', [
                    'faculty_id' => $faculty->id,
                    'faculty_name' => $faculty->name,
                ]);

                return null;
            }

            // Validate format
            if (! preg_match('/^[a-zA-Z0-9\-]+$/', $biometricCode)) {
                $error = "Invalid biometric code format for {$faculty->name}: {$biometricCode}";
                $this->errors[] = $error;
                Log::warning('Invalid faculty biometric code format', [
                    'faculty_id' => $faculty->id,
                    'biometric_code' => $biometricCode,
                ]);

                return null;
            }

            // Check uniqueness
            $existingUser = User::where('biometric_employee_code', $biometricCode)
                ->where('id', '!=', $faculty->id)
                ->first();

            if ($existingUser) {
                $error = "Biometric code '{$biometricCode}' already used by {$existingUser->name}";
                $this->errors[] = $error;
                Log::warning('Duplicate biometric code for faculty', [
                    'biometric_code' => $biometricCode,
                    'existing_user' => $existingUser->name,
                    'current_user' => $faculty->name,
                ]);

                return null;
            }

            // Update the faculty
            $faculty->update(['biometric_employee_code' => $biometricCode]);
            $this->importedCount++;

            Log::info('Successfully imported biometric code for faculty', [
                'faculty_id' => $faculty->id,
                'faculty_name' => $faculty->name,
                'employee_id' => $faculty->employee_id,
                'biometric_code' => $biometricCode,
            ]);

            return null; // We handle the update manually

        } catch (\Exception $e) {
            $error = 'Error processing row: '.$e->getMessage();
            $this->errors[] = $error;
            Log::error('Faculty biometric import row error', [
                'row' => $row,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    public function rules(): array
    {
        return [
            '*.faculty_id' => 'nullable',
            '*.Faculty ID' => 'nullable',
            '*.id' => 'nullable',
            '*.employee_id' => 'nullable',
            '*.Employee ID' => 'nullable',
            '*.biometric_code' => 'nullable',
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
