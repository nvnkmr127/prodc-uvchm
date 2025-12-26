<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SyncLogsExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
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
            'Sync Date & Time',
            'Type',
            'Date Range',
            'Status',
            'Total Records',
            'Created',
            'Updated', 
            'Skipped',
            'Duration',
            'Success Rate',
            'Test Mode',
            'User',
            'Error Count'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Sync Date & Time
            'B' => 12, // Type
            'C' => 15, // Date Range
            'D' => 12, // Status
            'E' => 12, // Total Records
            'F' => 10, // Created
            'G' => 10, // Updated
            'H' => 10, // Skipped
            'I' => 12, // Duration
            'J' => 12, // Success Rate
            'K' => 10, // Test Mode
            'L' => 15, // User
            'M' => 12, // Error Count
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->data) + 1;
        
        // Header row styling
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2F75B5']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Data rows styling
        if ($lastRow > 1) {
            // Alternate row colors
            for ($i = 2; $i <= $lastRow; $i++) {
                if ($i % 2 == 0) {
                    $sheet->getStyle("A{$i}:M{$i}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA']
                        ]
                    ]);
                }
            }
            
            // Status column conditional formatting
            for ($i = 2; $i <= $lastRow; $i++) {
                $statusCell = "D{$i}";
                $status = $sheet->getCell($statusCell)->getValue();
                
                $color = '';
                switch (strtolower($status)) {
                    case 'success':
                        $color = 'C6EFCE'; // Light green
                        break;
                    case 'failed':
                        $color = 'FFC7CE'; // Light red
                        break;
                    case 'partial':
                        $color = 'FFEB9C'; // Light yellow
                        break;
                }
                
                if ($color) {
                    $sheet->getStyle($statusCell)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color]
                        ]
                    ]);
                }
            }
            
            // Test mode column highlighting
            for ($i = 2; $i <= $lastRow; $i++) {
                $testModeCell = "K{$i}";
                $testMode = $sheet->getCell($testModeCell)->getValue();
                
                if (strtolower($testMode) === 'yes') {
                    $sheet->getStyle($testModeCell)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E1D5E7']
                        ],
                        'font' => [
                            'italic' => true
                        ]
                    ]);
                }
            }
            
            // Numeric columns alignment
            $numericColumns = ['E', 'F', 'G', 'H', 'J', 'M']; // Total, Created, Updated, Skipped, Success Rate, Error Count
            foreach ($numericColumns as $col) {
                $sheet->getStyle("{$col}2:{$col}{$lastRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);
            }
            
            // Center alignment for specific columns
            $centerColumns = ['B', 'D', 'I', 'K']; // Type, Status, Duration, Test Mode
            foreach ($centerColumns as $col) {
                $sheet->getStyle("{$col}2:{$col}{$lastRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);
            }
            
            // All borders
            $sheet->getStyle("A1:M{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]);
        }

        // Freeze first row
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        return 'ETimeOffice Sync Logs';
    }
}