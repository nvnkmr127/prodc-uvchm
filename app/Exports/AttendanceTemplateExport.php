<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    protected array $templateData;

    public function __construct(array $templateData)
    {
        $this->templateData = $templateData;
    }

    public function array(): array
    {
        $data = [];

        // Add instructions as comments in the first few rows
        $data[] = ['INSTRUCTIONS:', '', '', '', ''];
        foreach ($this->templateData['instructions'] as $instruction) {
            $data[] = [$instruction, '', '', '', ''];
        }

        // Add empty row
        $data[] = ['', '', '', '', ''];

        // Add sample data
        foreach ($this->templateData['sample_data'] as $sample) {
            $data[] = [
                $sample['enrollment_number'],
                $sample['attendance_date'],
                $sample['status'],
                $sample['notes'],
                $sample['late_minutes'],
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'enrollment_number',
            'attendance_date',
            'status',
            'notes',
            'late_minutes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],

            // Style instruction rows
            '2:6' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'F2F2F2'],
                ],
                'font' => [
                    'italic' => true,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // enrollment_number
            'B' => 15, // attendance_date
            'C' => 12, // status
            'D' => 30, // notes
            'E' => 15, // late_minutes
        ];
    }
}
