<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithMultipleSheets
{
    protected $student;
    protected $exportData;

    public function __construct($student, array $exportData)
    {
        $this->student = $student;
        $this->exportData = $exportData;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Main attendance data sheet
        $sheets[] = new AttendanceDataSheet($this->exportData['data']);

        // Summary sheet if requested
        if ($this->exportData['include_summary'] ?? false) {
            $sheets[] = new AttendanceSummarySheet($this->exportData);
        }

        return $sheets;
    }

    public function array(): array
    {
        return $this->exportData['data'] ?? [];
    }

    public function headings(): array
    {
        return [
            'Date',
            'Student Name',
            'Enrollment Number',
            'Batch',
            'Course',
            'Status',
            'Marked Time',
            'Device ID',
            'Biometric Code',
            'Notes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            // All cells border
            'A1:J' . (count($this->exportData['data']) + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]
        ];
    }

    public function title(): string
    {
        $dateRange = $this->exportData['date_range'] ?? null;
        if ($dateRange) {
            return 'Attendance ' . $dateRange['start'] . ' to ' . $dateRange['end'];
        }
        return 'Attendance Export';
    }
}

class AttendanceDataSheet implements FromArray, WithHeadings, WithStyles, WithTitle
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
            'Date',
            'Student Name',
            'Enrollment Number',
            'Batch',
            'Course',
            'Status',
            'Marked Time',
            'Device ID',
            'Biometric Code',
            'Notes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Auto-size columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Header styling
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Data rows styling
        if (count($this->data) > 0) {
            $lastRow = count($this->data) + 1;

            // Alternate row colors
            for ($i = 2; $i <= $lastRow; $i++) {
                if ($i % 2 == 0) {
                    $sheet->getStyle("A{$i}:J{$i}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F2F2F2']
                        ]
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
                    case 'absent':
                        $color = 'FFC7CE'; // Light red
                        break;
                }

                if ($color) {
                    $sheet->getStyle("F{$i}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color]
                        ]
                    ]);
                }
            }

            // All borders
            $sheet->getStyle("A1:J{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
        }

        return [];
    }

    public function title(): string
    {
        return 'Attendance Data';
    }
}

class AttendanceSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $exportData;

    public function __construct(array $exportData)
    {
        $this->exportData = $exportData;
    }

    public function array(): array
    {
        $data = $this->exportData['data'] ?? [];
        $dateRange = $this->exportData['date_range'] ?? null;

        // Calculate summary statistics
        $totalRecords = count($data);
        $presentCount = collect($data)->where('status', 'Present')->count();
        $absentCount = collect($data)->where('status', 'Absent')->count();
        $lateCount = collect($data)->where('status', 'Late')->count();

        $presentPercentage = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 2) : 0;
        $absentPercentage = $totalRecords > 0 ? round(($absentCount / $totalRecords) * 100, 2) : 0;
        $latePercentage = $totalRecords > 0 ? round(($lateCount / $totalRecords) * 100, 2) : 0;

        // Get unique students and dates
        $uniqueStudents = collect($data)->pluck('enrollment_number')->unique()->count();
        $uniqueDates = collect($data)->pluck('date')->unique()->count();

        $summary = [
            ['Metric', 'Value', 'Percentage'],
            ['', '', ''], // Empty row
            ['Export Information', '', ''],
            ['Export Date', now()->format('Y-m-d H:i:s'), ''],
            ['Date Range', $dateRange ? $dateRange['start'] . ' to ' . $dateRange['end'] : 'N/A', ''],
            ['', '', ''], // Empty row
            ['Summary Statistics', '', ''],
            ['Total Records', $totalRecords, '100%'],
            ['Present Records', $presentCount, $presentPercentage . '%'],
            ['Absent Records', $absentCount, $absentPercentage . '%'],
            ['Late Records', $lateCount, $latePercentage . '%'],
            ['', '', ''], // Empty row
            ['Additional Information', '', ''],
            ['Unique Students', $uniqueStudents, ''],
            ['Unique Dates', $uniqueDates, ''],
            ['Average Attendance Rate', $presentPercentage + $latePercentage . '%', ''],
        ];

        // Add batch-wise breakdown if available
        $batchStats = collect($data)->groupBy('batch_name')->map(function ($records, $batch) {
            $total = $records->count();
            $present = $records->where('status', 'Present')->count();
            $late = $records->where('status', 'Late')->count();
            $attendanceRate = $total > 0 ? round((($present + $late) / $total) * 100, 2) : 0;

            return [
                'batch' => $batch,
                'total' => $total,
                'attendance_rate' => $attendanceRate
            ];
        });

        if ($batchStats->isNotEmpty()) {
            $summary[] = ['', '', ''];
            $summary[] = ['Batch-wise Statistics', '', ''];
            $summary[] = ['Batch Name', 'Total Records', 'Attendance Rate'];

            foreach ($batchStats as $stat) {
                $summary[] = [$stat['batch'], $stat['total'], $stat['attendance_rate'] . '%'];
            }
        }

        return $summary;
    }

    public function headings(): array
    {
        return []; // Headers are included in the data array
    }

    public function styles(Worksheet $sheet)
    {
        // Auto-size columns
        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Header rows styling
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ]
        ]);

        // Section headers styling
        $sectionRows = [3, 7, 13]; // Adjust based on your summary structure
        foreach ($sectionRows as $row) {
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47']
                ]
            ]);
        }

        return [];
    }

    public function title(): string
    {
        return 'Summary';
    }
}