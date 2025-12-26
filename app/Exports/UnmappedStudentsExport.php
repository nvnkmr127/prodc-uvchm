<?php
namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UnmappedStudentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Student::where('status', 'active')
            ->whereNull('biometric_employee_code')
            ->with(['batch.course'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'student_id',                    // ✅ Fixed: lowercase with underscore
            'name',
            'enrollment_number',             // ✅ Fixed: lowercase with underscore
            'batch',
            'course',
            'suggested_biometric_code',
            'biometric_code'                 // ✅ Fixed: matches import expectation
        ];
    }

    public function map($student): array
    {
        return [
            $student->id,
            $student->name,
            $student->enrollment_number,
            $student->batch->name ?? 'No Batch',
            $student->batch->course->name ?? 'No Course',
            $this->generateBiometricCodeFromEnrollment($student->enrollment_number),
            '' // Empty column for manual entry - this will be the biometric_code column
        ];
    }

    private function generateBiometricCodeFromEnrollment(string $enrollmentNumber): string
    {
        // Remove common prefixes and extract numbers/letters
        $code = preg_replace('/^(UVCHM-|UV-|ENR-|STD-)/i', '', $enrollmentNumber);
        
        // Remove any non-alphanumeric characters except hyphens
        $code = preg_replace('/[^a-zA-Z0-9\-]/', '', $code);
        
        return $code;
    }
}