<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class StudentsSampleExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        return [
            [
                'Aarav Sharma',      // full_name
                'Rajesh Sharma',     // father_name
                'Male',              // gender
                '2025-01-15',        // admission_date (YYYY-MM-DD format)
                '9876543210',        // student_mobile (Indian 10-digit number)
                '9876543211',        // father_mobile
                'Website',           // source
                'Rampur',            // village (e.g., a village in Uttar Pradesh)
                'Priya Patel'        // referral_name
            ],
            [
                'Priya Reddy',       // full_name
                'Venkatesh Reddy',   // father_name
                'Female',            // gender
                '2025-01-16',        // admission_date
                '8765432109',        // student_mobile
                '8765432110',        // father_mobile
                'Social Media',      // source
                'Kondapur',          // village (e.g., a village in Karnataka)
                'Rahul Kumar'        // referral_name
            ],
            [
                'Rohan Gupta',       // full_name
                'Suresh Gupta',      // father_name
                'Male',              // gender
                '2025-01-17',        // admission_date
                '7654321098',        // student_mobile
                '7654321099',        // father_mobile
                'Referrals',         // source
                'Sonapur',           // village (e.g., a village in Maharashtra)
                'Anita Singh'        // referral_name
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'full_name',
            'father_name', 
            'gender',
            'admission_date',
            'student_mobile',
            'father_mobile',
            'source',
            'village',
            'referral_name'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row (now covers 9 columns: A1 to I1)
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => Color::COLOR_WHITE]
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF4472C4'
                    ]
                ]
            ],
            // Style data rows (updated range to include all 9 columns: A2 to I4)
            'A2:I4' => [  
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFF2F2F2'
                    ]
                ]
            ]
        ];
    }
}
