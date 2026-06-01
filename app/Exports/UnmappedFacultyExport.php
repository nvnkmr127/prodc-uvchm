<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UnmappedFacultyExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return User::role('staff')
            ->where('status', 'active')
            ->whereNull('biometric_employee_code')
            ->get();
    }

    public function headings(): array
    {
        return [
            'faculty_id',
            'name',
            'employee_id',
            'department',
            'phone',
            'suggested_biometric_code',
            'biometric_code',
        ];
    }

    public function map($faculty): array
    {
        return [
            $faculty->id,
            $faculty->name,
            $faculty->employee_id,
            $faculty->department ?? 'No Department',
            $faculty->phone ?? 'No Phone',
            $this->generateBiometricCodeFromEmployeeId($faculty->employee_id ?? ''),
            '', // Empty column for manual entry
        ];
    }

    private function generateBiometricCodeFromEmployeeId(string $employeeId): string
    {
        // Remove common prefixes and extract numbers/letters
        $code = preg_replace('/^(EMP-|FAC-|UV-|STAFF-)/i', '', $employeeId);

        // Remove any non-alphanumeric characters except hyphens
        $code = preg_replace('/[^a-zA-Z0-9\-]/', '', $code);

        return $code;
    }
}
