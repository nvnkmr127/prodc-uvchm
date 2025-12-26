<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StudentsSampleExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnWidths
{
    public function array(): array
    {
        return [
            // Example 1: Full payment with concession
            [
                // Student Information
                'Aarav Sharma',           // full_name
                'Rajesh Sharma',          // father_name
                'Male',                   // gender
                '2025-01-15',            // admission_date (YYYY-MM-DD format)
                '9876543210',            // student_mobile (Indian 10-digit number)
                '9876543211',            // father_mobile
                'Website',               // source
                'Rampur',                // village
                'Priya Patel',           // referral_name
                
                // Financial Information
                85000,                   // total_fee_amount
                50000,                   // paid_amount
                5000,                    // concession_amount
                12000,                   // uniform_fee
                '2025-01-15',           // payment_date (YYYY-MM-DD format)
                'Cash',                  // payment_method
                'Partial payment with merit scholarship' // payment_remarks
            ],
            
            // Example 2: Online payment, no concession
            [
                // Student Information
                'Priya Reddy',           // full_name
                'Venkatesh Reddy',       // father_name
                'Female',                // gender
                '2025-01-16',           // admission_date
                '8765432109',           // student_mobile
                '8765432110',           // father_mobile
                'Social Media',         // source
                'Kondapur',             // village
                'Rahul Kumar',          // referral_name
                
                // Financial Information
                85000,                  // total_fee_amount
                85000,                  // paid_amount (full payment)
                0,                      // concession_amount
                12000,                  // uniform_fee
                '2025-01-16',          // payment_date
                'Online',              // payment_method
                'Full payment via UPI - PhonePe' // payment_remarks
            ],
            
            // Example 3: Cheque payment with family discount
            [
                // Student Information
                'Rohan Gupta',          // full_name
                'Suresh Gupta',         // father_name
                'Male',                 // gender
                '2025-01-17',          // admission_date
                '7654321098',          // student_mobile
                '7654321099',          // father_mobile
                'Referrals',           // source
                'Sonapur',             // village
                'Anita Singh',         // referral_name
                
                // Financial Information
                120000,                // total_fee_amount (PG course - higher fee)
                30000,                 // paid_amount
                15000,                 // concession_amount (family discount)
                12000,                 // uniform_fee
                '2025-01-17',         // payment_date
                'Cheque',             // payment_method
                'Initial payment via cheque, family discount applied' // payment_remarks
            ],
            
            // Example 4: No payment yet, but concession applied
            [
                // Student Information
                'Sneha Patel',         // full_name
                'Ramesh Patel',        // father_name
                'Female',              // gender
                '2025-01-18',         // admission_date
                '6543210987',         // student_mobile
                '6543210988',         // father_mobile
                'Walk-in',            // source
                'Mehsana',            // village
                '',                   // referral_name (empty - no referral)
                
                // Financial Information
                85000,                // total_fee_amount
                0,                    // paid_amount (no payment yet)
                8500,                 // concession_amount (10% scholarship)
                12000,                // uniform_fee
                '',                   // payment_date (empty - no payment yet)
                '',                   // payment_method (empty - no payment yet)
                'Merit scholarship applied - 10% discount, payment pending' // payment_remarks
            ],
            
            // Example 5: Bank transfer with installment plan
            [
                // Student Information
                'Arjun Singh',         // full_name
                'Vikram Singh',        // father_name
                'Male',                // gender
                '2025-01-19',         // admission_date
                '5432109876',         // student_mobile
                '5432109877',         // father_mobile
                'Student Referral',   // source
                'Jaipur',             // village
                'Mohan Sharma',       // referral_name
                
                // Financial Information
                85000,                // total_fee_amount
                25000,                // paid_amount (installment 1)
                2000,                 // concession_amount (early bird discount)
                12000,                // uniform_fee
                '2025-01-19',        // payment_date
                'Bank Transfer',      // payment_method
                'First installment - NEFT transfer, early bird discount' // payment_remarks
            ],
            
            // Example 6: UPI payment, complete uniform fee paid
            [
                // Student Information
                'Kavya Nair',          // full_name
                'Suresh Nair',         // father_name
                'Female',              // gender
                '2025-01-20',         // admission_date
                '4321098765',         // student_mobile
                '4321098766',         // father_mobile
                'Online Ads',         // source
                'Kochi',              // village
                'Deepa Thomas',       // referral_name
                
                // Financial Information
                85000,                // total_fee_amount
                15000,                // paid_amount (partial)
                0,                    // concession_amount
                12000,                // uniform_fee (will be paid separately)
                '2025-01-20',        // payment_date
                'UPI',                // payment_method
                'Advance payment via GooglePay, uniform fee included' // payment_remarks
            ]
        ];
    }

    public function headings(): array
    {
        return [
            // Student Information Columns (A-I)
            'full_name',              // A - Required
            'father_name',            // B - Required  
            'gender',                 // C - Required (Male/Female)
            'admission_date',         // D - Required (YYYY-MM-DD)
            'student_mobile',         // E - Optional (10-digit number)
            'father_mobile',          // F - Optional (10-digit number)
            'source',                 // G - Optional (how they found college)
            'village',                // H - Optional (student location)
            'referral_name',          // I - Optional (who referred them)
            
            // Financial Information Columns (J-P)
            'total_fee_amount',       // J - Optional (override default fees)
            'paid_amount',            // K - Optional (amount already paid)
            'concession_amount',      // L - Optional (discounts/scholarships)
            'uniform_fee',            // M - Optional (uniform fee amount)
            'payment_date',           // N - Optional (YYYY-MM-DD format)
            'payment_method',         // O - Optional (Cash/Online/Cheque/UPI/Bank Transfer)
            'payment_remarks'         // P - Optional (notes about payment)
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // full_name
            'B' => 20, // father_name
            'C' => 10, // gender
            'D' => 15, // admission_date
            'E' => 15, // student_mobile
            'F' => 15, // father_mobile
            'G' => 15, // source
            'H' => 15, // village
            'I' => 18, // referral_name
            'J' => 18, // total_fee_amount
            'K' => 15, // paid_amount
            'L' => 18, // concession_amount
            'M' => 15, // uniform_fee
            'N' => 15, // payment_date
            'O' => 18, // payment_method
            'P' => 40, // payment_remarks
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Add instructions at the top
        $sheet->insertNewRowBefore(1, 3);
        
        // Add title and instructions
        $sheet->setCellValue('A1', 'STUDENT BULK IMPORT TEMPLATE WITH FINANCIAL DATA');
        $sheet->setCellValue('A2', 'Instructions: Fill student data (A-I) and financial data (J-P). Financial columns are optional.');
        $sheet->setCellValue('A3', 'Date Format: YYYY-MM-DD | Mobile: 10-digit numbers | Amounts: Numbers only (no commas)');
        
        return [
            // Title styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['argb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '2F75B5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            
            // Instructions styling
            2 => [
                'font' => [
                    'size' => 10,
                    'color' => ['argb' => '0070C0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ],
            
            3 => [
                'font' => [
                    'size' => 9,
                    'color' => ['argb' => 'C5504B'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ],
            
            // Header row styling (row 4 after instructions)
            4 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['argb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '4472C4'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            
            // Student information columns (A-I) - Light blue background
            'A4:I4' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'D9E2F3'],
                ],
                'font' => [
                    'color' => ['argb' => '1F4E79'],
                    'bold' => true,
                ],
            ],
            
            // Financial information columns (J-P) - Light green background
            'J4:P4' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'E2EFDA'],
                ],
                'font' => [
                    'color' => ['argb' => '375623'],
                    'bold' => true,
                ],
            ],
            
            // Data rows styling
            'A5:P10' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'D0D0D0'],
                    ],
                ],
            ],
            
            // Numeric columns formatting (amounts)
            'J:M' => [
                'numberFormat' => [
                    'formatCode' => '#,##0'
                ],
            ],
            
            // Date columns formatting
            'D:D' => [
                'numberFormat' => [
                    'formatCode' => 'yyyy-mm-dd'
                ],
            ],
            'N:N' => [
                'numberFormat' => [
                    'formatCode' => 'yyyy-mm-dd'
                ],
            ],
            
            // Mobile number columns - text format to preserve leading zeros
            'E:F' => [
                'numberFormat' => [
                    'formatCode' => '@' // Text format
                ],
            ],
            
            // Merge title cell across all columns
            'A1:P1' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}