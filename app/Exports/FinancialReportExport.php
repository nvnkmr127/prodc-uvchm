<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinancialReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    protected $type;

    public function __construct($data, $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        if ($this->type === 'defaulters') {
            return ['Enrollment #', 'Student Name', 'Course', 'Batch', 'Total Amount Due'];
        }
        if ($this->type === 'collections') {
            return ['Payment Date', 'Receipt #', 'Student Name', 'Amount Paid', 'Method'];
        }

        // Add more headings for other report types if needed
        return [];
    }

    public function map($row): array
    {
        if ($this->type === 'defaulters') {
            return [
                $row->enrollment_number,
                $row->name,
                $row->batch->course->name ?? 'N/A',
                $row->batch->name ?? 'N/A',
                $row->total_due,
            ];
        }
        if ($this->type === 'collections') {
            return [
                $row->payment_date,
                $row->receipt_number,
                $row->student->name ?? 'N/A',
                $row->amount,
                $row->payment_method,
            ];
        }

        return [];
    }
}
