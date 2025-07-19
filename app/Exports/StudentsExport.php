<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Fetch all students with their related batch and course data
        return Student::with('batch.course')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // These will be the column headers in the Excel file
        return [
            'Enrollment #',
            'Name',
            'Email',
            'Father Name',
            'Student Mobile',
            'Father Mobile',
            'Village',
            'Admission Date',
            'Course',
            'Batch',
            'Status',
        ];
    }

    /**
     * @var Student $student
     */
    public function map($student): array
    {
        // This maps the data for each row
        return [
            $student->enrollment_number,
            $student->name,
            $student->email,
            $student->father_name,
            $student->student_mobile,
            $student->father_mobile,
            $student->village,
            $student->admission_date,
            $student->batch->course->name ?? 'N/A',
            $student->batch->name ?? 'N/A',
            $student->status,
        ];
    }
}