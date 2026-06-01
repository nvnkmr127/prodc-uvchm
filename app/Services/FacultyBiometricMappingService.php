<?php

namespace App\Services;

use App\Exports\UnmappedFacultyExport;
use App\Imports\FacultyBiometricMappingImport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class FacultyBiometricMappingService
{
    /**
     * Get biometric mapping statistics for faculty
     */
    public function getMappingStats(): array
    {
        $totalFaculty = User::role('staff')->where('status', 'active')->count();
        $mappedFaculty = User::role('staff')
            ->where('status', 'active')
            ->whereNotNull('biometric_employee_code')
            ->count();
        $unmappedFaculty = $totalFaculty - $mappedFaculty;

        return [
            'total_faculty' => $totalFaculty,
            'mapped_faculty' => $mappedFaculty,
            'unmapped_faculty' => $unmappedFaculty,
            'mapping_percentage' => $totalFaculty > 0 ? round(($mappedFaculty / $totalFaculty) * 100, 2) : 0,
        ];
    }

    /**
     * Get unmapped faculty with suggestions
     */
    public function getUnmappedFaculty()
    {
        return User::role('staff')
            ->where('status', 'active')
            ->whereNull('biometric_employee_code')
            ->get()
            ->map(function ($faculty) {
                return [
                    'id' => $faculty->id,
                    'name' => $faculty->name,
                    'employee_id' => $faculty->employee_id,
                    'department' => $faculty->department ?? 'No Department',
                    'phone' => $faculty->phone ?? 'No Phone',
                    'suggested_code' => $this->generateBiometricCodeFromEmployeeId($faculty->employee_id ?? ''),
                ];
            });
    }

    /**
     * Generate and assign a biometric code for a single faculty member.
     */
    public function assignBiometricCode(User $faculty): void
    {
        if (empty($faculty->employee_id)) {
            Log::warning("Cannot assign biometric code to faculty {$faculty->name} because employee_id is empty.");
            return;
        }

        // 1. Generate base code from employee ID
        $generatedCode = $this->generateBiometricCodeFromEmployeeId($faculty->employee_id);

        // 2. Ensure uniqueness (Handle collisions)
        $counter = 1;
        $originalCode = $generatedCode;

        while (User::where('biometric_employee_code', $generatedCode)
            ->where('id', '!=', $faculty->id)
            ->exists()) {
            $generatedCode = $originalCode.'-'.$counter;
            $counter++;
        }

        // 3. Save to faculty
        $faculty->update(['biometric_employee_code' => $generatedCode]);

        Log::info("Automatically assigned biometric code {$generatedCode} to faculty {$faculty->name}");
    }

    /**
     * Auto-generate biometric codes for all unmapped faculty
     */
    public function autoGenerateAllCodes(): array
    {
        $unmappedFaculty = User::role('staff')
            ->where('status', 'active')
            ->whereNull('biometric_employee_code')
            ->get();

        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
        ];

        foreach ($unmappedFaculty as $faculty) {
            try {
                if (empty($faculty->employee_id)) {
                    $results['error_count']++;
                    $results['errors'][] = "Error generating code for {$faculty->name}: Employee ID is empty.";
                    continue;
                }

                $generatedCode = $this->generateBiometricCodeFromEmployeeId($faculty->employee_id);

                // Ensure uniqueness
                $counter = 1;
                $originalCode = $generatedCode;

                while (User::where('biometric_employee_code', $generatedCode)
                    ->where('id', '!=', $faculty->id)
                    ->exists()) {
                    $generatedCode = $originalCode.'-'.$counter;
                    $counter++;
                }

                $faculty->update(['biometric_employee_code' => $generatedCode]);
                $results['success_count']++;

                Log::info('Auto-generated biometric code for faculty', [
                    'faculty_id' => $faculty->id,
                    'faculty_name' => $faculty->name,
                    'employee_id' => $faculty->employee_id,
                    'generated_code' => $generatedCode,
                ]);

            } catch (\Exception $e) {
                $results['error_count']++;
                $results['errors'][] = "Error generating code for {$faculty->name}: ".$e->getMessage();

                Log::error('Failed to auto-generate biometric code for faculty', [
                    'faculty_id' => $faculty->id,
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
                if (! isset($mapping['faculty_id']) || ! isset($mapping['biometric_code'])) {
                    $results['error_count']++;
                    $results['errors'][] = 'Missing faculty_id or biometric_code in mapping';
                    continue;
                }

                $faculty = User::role('staff')->find($mapping['faculty_id']);

                if (! $faculty) {
                    $results['error_count']++;
                    $results['errors'][] = "Faculty not found: ID {$mapping['faculty_id']}";
                    continue;
                }

                // Check for empty codes
                if (empty(trim($mapping['biometric_code']))) {
                    // If empty code, clear the existing one
                    $faculty->update(['biometric_employee_code' => null]);
                    $results['success_count']++;
                    continue;
                }

                // Validate biometric code format
                if (! preg_match('/^[a-zA-Z0-9\-]+$/', $mapping['biometric_code'])) {
                    $results['error_count']++;
                    $results['errors'][] = "Invalid biometric code format for {$faculty->name}: {$mapping['biometric_code']}";
                    continue;
                }

                // Check uniqueness
                $existingUser = User::where('biometric_employee_code', $mapping['biometric_code'])
                    ->where('id', '!=', $faculty->id)
                    ->first();

                if ($existingUser) {
                    $results['error_count']++;
                    $results['errors'][] = "Biometric code '{$mapping['biometric_code']}' already used by {$existingUser->name}";
                    continue;
                }

                // Update faculty
                $faculty->update(['biometric_employee_code' => $mapping['biometric_code']]);
                $results['success_count']++;

                Log::info('Bulk updated biometric code for faculty', [
                    'faculty_id' => $faculty->id,
                    'faculty_name' => $faculty->name,
                    'employee_id' => $faculty->employee_id,
                    'biometric_code' => $mapping['biometric_code'],
                ]);

            } catch (\Exception $e) {
                $results['error_count']++;
                $results['errors'][] = "Error updating faculty ID {$mapping['faculty_id']}: ".$e->getMessage();

                Log::error('Bulk biometric code update failed for faculty', [
                    'faculty_id' => $mapping['faculty_id'] ?? 'unknown',
                    'biometric_code' => $mapping['biometric_code'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate biometric code from Employee ID
     * Example: EMP-001 -> 9001
     */
    public function generateBiometricCodeFromEmployeeId(string $employeeId): string
    {
        try {
            $prefix = '9'; // Default faculty prefix

            // Step 1: Extract all numeric characters from the string.
            $allNumbers = preg_replace('/[^0-9]/', '', $employeeId);

            // Step 2: Take only the last 3 characters from the extracted numbers.
            if (! empty($allNumbers) && strlen($allNumbers) >= 3) {
                $numberPart = substr($allNumbers, -3);
            } else {
                $numberPart = str_pad($allNumbers, 3, '0', STR_PAD_LEFT);
            }

            // Step 3: Combine parts
            return $prefix . $numberPart;

        } catch (\Exception $e) {
            Log::error('Error in generateBiometricCodeFromEmployeeId', ['error' => $e->getMessage()]);

            $allNumbers = preg_replace('/[^0-9]/', '', $employeeId);
            $numbers = ! empty($allNumbers) && strlen($allNumbers) >= 3 ? substr($allNumbers, -3) : str_pad($allNumbers, 3, '0', STR_PAD_LEFT);

            return '9'.$numbers;
        }
    }

    /**
     * Export unmapped faculty to Excel
     */
    public function exportUnmappedFaculty()
    {
        return Excel::download(new UnmappedFacultyExport, 'unmapped_faculty_'.date('Y-m-d').'.xlsx');
    }

    /**
     * Import biometric mappings from Excel/CSV
     */
    public function importBiometricMappings($file): array
    {
        try {
            $import = new FacultyBiometricMappingImport;
            Excel::import($import, $file);

            return [
                'success' => true,
                'imported_count' => $import->getImportedCount(),
                'errors' => $import->getErrors(),
            ];
        } catch (\Exception $e) {
            Log::error('Faculty biometric mapping import failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
