<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $students;

    /**
     * Constructor to accept filtered students collection
     * This fixes the 404 error by properly accepting the parameter from controller
     */
    public function __construct($students)
    {
        $this->students = $students;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Return the filtered students passed from controller
        return $this->students;
    }

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
     * @var Student
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
