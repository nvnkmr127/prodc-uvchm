<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TodayAttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $attendanceData;

    public function __construct($attendanceData)
    {
        $this->attendanceData = collect($attendanceData);
    }

    public function collection()
    {
        return $this->attendanceData;
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Enrollment Number',
            'Batch',
            'Course',
            'Status',
            'Time In',
            'Time Out',
            'Date',
            'Remarks'
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance['student_name'] ?? '',
            $attendance['enrollment_number'] ?? '',
            $attendance['batch_name'] ?? '',
            $attendance['course_name'] ?? '',
            $attendance['status'] ?? '',
            $attendance['time_in'] ?? '',
            $attendance['time_out'] ?? '',
            $attendance['date'] ?? '',
            $attendance['remarks'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}