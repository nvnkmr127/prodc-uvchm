<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentPlacementExport implements FromCollection, WithHeadings, WithMapping, WithDrawings, WithEvents, WithColumnWidths, WithStyles
{
    protected $students;

    public function __construct($students)
    {
        $this->students = collect($students);
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return [
            'Profile Photo',
            'Student Name',
            'Batch Name',
            'Passing Year',
            'Mobile',
            'Email',
            'Placement Status',
            'Placed At (Company)',
            'Designation',
        ];
    }

    public function map($student): array
    {
        $passingYear = $student->batch && $student->batch->end_date 
            ? $student->batch->end_date->format('Y') 
            : 'N/A';

        return [
            '', // Blank space for drawing overlay
            $student->name,
            $student->batch->name ?? 'N/A',
            $passingYear,
            $student->student_mobile,
            $student->email,
            $student->placement_status ?? 'Not Placed',
            $student->placed_at ?? '-',
            $student->placement_designation ?? '-',
        ];
    }

    public function drawings()
    {
        $drawings = [];
        foreach ($this->students as $index => $student) {
            $rowNumber = $index + 2; // Headings are on row 1

            if (\App\Traits\StudentPhotoHelper::hasRealPhoto($student)) {
                $photoPath = ltrim($student->photo, '/');
                $fullPath = storage_path('app/public/' . $photoPath);
                
                if (!file_exists($fullPath)) {
                    $prefixes = ['student_photos/', 'students/', 'student-photos/'];
                    foreach ($prefixes as $prefix) {
                        $possiblePath = storage_path('app/public/' . $prefix . basename($photoPath));
                        if (file_exists($possiblePath)) {
                            $fullPath = $possiblePath;
                            break;
                        }
                    }
                }

                if (file_exists($fullPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('Profile Photo');
                    $drawing->setDescription('Profile Photo');
                    $drawing->setPath($fullPath);
                    $drawing->setHeight(80);
                    $drawing->setCoordinates('A' . $rowNumber);
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(10);
                    $drawings[] = $drawing;
                }
            }
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Header row height
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(25);
                
                // Data rows height
                for ($i = 0; $i < count($this->students); $i++) {
                    $event->sheet->getDelegate()->getRowDimension($i + 2)->setRowHeight(85);
                }
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, // Profile Photo
            'B' => 25, // Name
            'C' => 20, // Batch
            'D' => 15, // Passing Year
            'E' => 15, // Mobile
            'F' => 30, // Email
            'G' => 20, // Placement Status
            'H' => 25, // Placed At
            'I' => 20, // Designation
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:I' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }
}
