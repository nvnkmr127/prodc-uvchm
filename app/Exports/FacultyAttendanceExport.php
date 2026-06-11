<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacultyAttendanceExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Faculty Name',
            'Date',
            'Check-In Time',
            'Check-Out Time',
            'Working Hours',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Header styling
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E4620'], // Dark Green Theme for Faculty
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if (count($this->data) > 0) {
            $lastRow = count($this->data) + 1;

            // Alternate row colors
            for ($i = 2; $i <= $lastRow; $i++) {
                if ($i % 2 == 0) {
                    $sheet->getStyle("A{$i}:F{$i}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F9FBF9'],
                        ],
                    ]);
                }
            }

            // Status column conditional formatting
            for ($i = 2; $i <= $lastRow; $i++) {
                $status = $sheet->getCell("F{$i}")->getValue();
                $color = '';

                switch (strtolower($status)) {
                    case 'present':
                        $color = 'C6EFCE'; // Light green
                        break;
                    case 'late':
                        $color = 'FFEB9C'; // Light yellow
                        break;
                    case 'half day':
                        $color = 'E8F4F8'; // Light blue
                        break;
                    case 'absent':
                        $color = 'FFC7CE'; // Light red
                        break;
                    case 'on leave':
                        $color = 'D9E1F2'; // Light purple/indigo
                        break;
                }

                if ($color) {
                    $sheet->getStyle("F{$i}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color],
                        ],
                    ]);
                }
            }

            // All borders
            $sheet->getStyle("A1:F{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D0D0D0'],
                    ],
                ],
            ]);
        }

        return [];
    }

    public function title(): string
    {
        return 'Faculty Attendance';
    }
}
