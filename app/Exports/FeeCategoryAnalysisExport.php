<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FeeCategoryAnalysisExport implements WithMultipleSheets
{
    protected $data;

    protected $type;

    public function __construct($data, $type = 'overview')
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function sheets(): array
    {
        $sheets = [];

        switch ($this->type) {
            case 'overview':
                $sheets[] = new FeeCategoryOverviewSheet($this->data);
                break;

            case 'detailed':
                $sheets[] = new FeeCategoryDetailedSheet($this->data);
                break;

            case 'pending':
                $sheets[] = new FeeCategoryPendingSheet($this->data);
                break;

            case 'pending_simple':
                $sheets[] = new SimplePendingSheet($this->data);
                break;

            default:
                $sheets[] = new FeeCategoryOverviewSheet($this->data);
        }

        return $sheets;
    }
}

class FeeCategoryOverviewSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Category Name',
            'Category Code',
            'Category Type',
            'Is Mandatory',
            'Total Students',
            'Paid Students',
            'Pending Students',
            'Student Payment Rate %',
            'Total Billed',
            'Total Collected',
            'Total Concessions',
            'Total Pending',
            'Total Overdue',
            'Collection Rate %',
            'Overdue Rate %',
            'Average Fee Amount',
            'Earliest Due Date',
            'Latest Due Date',
            'Status',
        ];
    }

    public function map($category): array
    {
        // Handle both object and array data
        $totalBilled = $category->total_billed ?? $category->total_amount ?? 0;
        $totalCollected = $category->total_collected ?? $category->total_paid ?? 0;
        $totalConcessions = $category->total_concessions ?? 0;
        $totalPending = $category->total_pending ?? $category->pending_amount ?? 0;
        $totalOverdue = $category->total_overdue ?? $category->overdue_amount ?? 0;

        // Calculate net amount (excluding concessions)
        $netAmount = $totalBilled - $totalConcessions;
        $collectionRate = $netAmount > 0 ?
            round(($totalCollected / $netAmount) * 100, 2) : 0;

        $totalStudents = $category->total_students ?? 0;
        $paidStudents = $category->paid_students ?? 0;
        $pendingStudents = $category->pending_students ?? 0;

        $studentPaymentRate = $totalStudents > 0 ?
            round(($paidStudents / $totalStudents) * 100, 2) : 0;

        $totalFees = $category->total_fees ?? 0;
        $overdueFees = $category->overdue_fees ?? 0;
        $overdueRate = $totalFees > 0 ?
            round(($overdueFees / $totalFees) * 100, 2) : 0;

        $status = $collectionRate >= 80 ? 'Good' :
                 ($collectionRate >= 60 ? 'Warning' : 'Critical');

        // Get average fee amount
        $avgFeeAmount = $category->avg_fee_amount ?? ($totalFees > 0 ? round($totalBilled / $totalFees, 2) : 0);

        return [
            $category->name ?? 'N/A',
            $category->category_code ?? 'N/A',
            ucfirst($category->category_type ?? 'general'),
            ($category->is_mandatory ?? false) ? 'Yes' : 'No',
            $totalStudents,
            $paidStudents,
            $pendingStudents,
            $studentPaymentRate,
            number_format($totalBilled, 2, '.', ''),
            number_format($totalCollected, 2, '.', ''),
            number_format($totalConcessions, 2, '.', ''),
            number_format($totalPending, 2, '.', ''),
            number_format($totalOverdue, 2, '.', ''),
            $collectionRate,
            $overdueRate,
            number_format($avgFeeAmount, 2, '.', ''),
            isset($category->earliest_due_date) && $category->earliest_due_date ?
                \Carbon\Carbon::parse($category->earliest_due_date)->format('Y-m-d') : 'N/A',
            isset($category->latest_due_date) && $category->latest_due_date ?
                \Carbon\Carbon::parse($category->latest_due_date)->format('Y-m-d') : 'N/A',
            $status,
        ];
    }

    public function title(): string
    {
        return 'Fee Category Overview';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:S' => ['alignment' => ['horizontal' => 'left']],
            'E:P' => ['numberFormat' => ['formatCode' => '#,##0.00']],
        ];
    }
}

class FeeCategoryDetailedSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Enrollment Number',
            'Course',
            'Batch',
            'Fee Category',
            'Total Amount',
            'Concession',
            'Paid Amount',
            'Balance',
            'Status',
            'Due Date',
            'Last Payment Date',
        ];
    }

    public function map($fee): array
    {
        // Extract Student Details safely
        $studentName = $fee->student->name ?? 'N/A';
        $enrollment = $fee->student->enrollment_number ?? 'N/A';
        $course = $fee->student->batch->course->name ?? 'N/A';
        $batch = $fee->student->batch->name ?? 'N/A';
        $category = $fee->feeCategory->name ?? 'N/A';

        // Financials
        $amount = $fee->amount ?? 0;
        $concession = $fee->concession_amount ?? 0;
        $paid = $fee->paid_amount ?? 0;
        $balance = $amount - $concession - $paid;

        // Status & Dates
        $status = ucfirst($fee->status ?? 'pending');
        $dueDate = $fee->due_date ? \Carbon\Carbon::parse($fee->due_date)->format('Y-m-d') : 'N/A';

        // Get last payment date if available
        $lastPayment = 'N/A';
        if (isset($fee->last_payment_date)) {
            $lastPayment = \Carbon\Carbon::parse($fee->last_payment_date)->format('Y-m-d');
        } elseif (isset($fee->payments) && $fee->payments->isNotEmpty()) {
            $lastPayment = $fee->payments->sortByDesc('payment_date')->first()->payment_date->format('Y-m-d');
        }

        return [
            $studentName,
            $enrollment,
            $course,
            $batch,
            $category,
            number_format($amount, 2, '.', ''),
            number_format($concession, 2, '.', ''),
            number_format($paid, 2, '.', ''),
            number_format($balance, 2, '.', ''),
            $status,
            $dueDate,
            $lastPayment,
        ];
    }

    public function title(): string
    {
        return 'Detailed Student List';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:L' => ['alignment' => ['horizontal' => 'left']],
            'F:I' => ['numberFormat' => ['formatCode' => '#,##0.00']],
        ];
    }
}

class FeeCategoryPendingSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Enrollment Number',
            'Course',
            'Batch',
            'Fee Category',
            'Total Amount',
            'Paid Amount',
            'Concession Amount',
            'Pending Amount',
            'Due Date',
            'Days Overdue',
            'Status',
            'Priority',
            'Student Mobile',
            'Father Mobile',
            'Contact Email',
            'Last Payment Date',
        ];
    }

    public function map($fee): array
    {
        // Calculate pending amount
        $amount = $fee->amount ?? 0;
        $concessionAmount = $fee->concession_amount ?? 0;
        $paidAmount = $fee->paid_amount ?? 0;
        $pendingAmount = $amount - $concessionAmount - $paidAmount;

        // Parse due date
        $dueDate = null;
        if (isset($fee->due_date) && $fee->due_date) {
            try {
                $dueDate = \Carbon\Carbon::parse($fee->due_date);
            } catch (\Exception $e) {
                $dueDate = null;
            }
        }

        $daysOverdue = $dueDate && $dueDate->isPast() && $pendingAmount > 0 ?
            $dueDate->diffInDays(now()) : 0;

        // Determine priority based on urgency level if available, otherwise use days overdue
        $priority = $fee->urgency_level ?? ($daysOverdue > 30 ? 'high' : ($daysOverdue > 15 ? 'medium' : 'low'));
        $priority = ucfirst($priority);

        // Determine status
        $status = $fee->status ?? 'unknown';
        $status = $status === 'unpaid' ? 'Unpaid' : ($status === 'partial' ? 'Partially Paid' : ucfirst($status));

        // Get student information
        $studentName = 'N/A';
        $enrollmentNumber = 'N/A';
        $courseName = 'N/A';
        $batchName = 'N/A';
        $studentMobile = 'N/A';
        $fatherMobile = 'N/A';
        $email = 'N/A';
        $lastPaymentDate = 'N/A';

        if (isset($fee->student) && $fee->student) {
            $studentName = $fee->student->name ?? 'N/A';
            $enrollmentNumber = $fee->student->enrollment_number ?? 'N/A';
            $studentMobile = $fee->student->student_mobile ?? 'N/A';
            $fatherMobile = $fee->student->father_mobile ?? 'N/A';
            $email = $fee->student->email ?? 'N/A';

            if (isset($fee->student->batch)) {
                $batchName = $fee->student->batch->name ?? 'N/A';
                if (isset($fee->student->batch->course)) {
                    $courseName = $fee->student->batch->course->name ?? 'N/A';
                }
            }

            // Try to get last payment date
            if (isset($fee->last_payment_date)) {
                $lastPaymentDate = \Carbon\Carbon::parse($fee->last_payment_date)->format('Y-m-d');
            } elseif (isset($fee->student->last_payment_date)) {
                $lastPaymentDate = $fee->student->last_payment_date;
            }
        }

        // Get fee category name
        $feeCategoryName = 'N/A';
        if (isset($fee->feeCategory) && $fee->feeCategory) {
            $feeCategoryName = $fee->feeCategory->name ?? 'N/A';
        }

        return [
            $studentName,
            $enrollmentNumber,
            $courseName,
            $batchName,
            $feeCategoryName,
            number_format($amount, 2, '.', ''),
            number_format($paidAmount, 2, '.', ''),
            number_format($concessionAmount, 2, '.', ''),
            number_format($pendingAmount, 2, '.', ''),
            $dueDate ? $dueDate->format('Y-m-d') : 'N/A',
            $daysOverdue,
            $status,
            $priority,
            $studentMobile,
            $fatherMobile,
            $email,
            $lastPaymentDate,
        ];
    }

    public function title(): string
    {
        return 'Pending Payments';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:Q' => ['alignment' => ['horizontal' => 'left']],
            'F:I' => ['numberFormat' => ['formatCode' => '#,##0.00']],
        ];
    }
}

class SimplePendingSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Student',
            'Contact',
            'Amount',
        ];
    }

    public function map($fee): array
    {
        $studentName = $fee->student->name ?? 'N/A';
        $enrollment = $fee->student->enrollment_number ?? 'N/A';
        $student = $studentName . ' (' . $enrollment . ')';

        $studentMobile = $fee->student->student_mobile ?? '';
        $fatherMobile = $fee->student->father_mobile ?? '';
        $contact = trim(($studentMobile ? $studentMobile . ' (S)' : '') . ' ' . ($fatherMobile ? $fatherMobile . ' (F)' : ''));
        if (empty($contact)) $contact = 'N/A';

        $amount = $fee->amount ?? 0;
        $concessionAmount = $fee->concession_amount ?? 0;
        $paidAmount = $fee->paid_amount ?? 0;
        $pendingAmount = $amount - $concessionAmount - $paidAmount;

        return [
            $student,
            $contact,
            number_format($pendingAmount, 2, '.', ''),
        ];
    }

    public function title(): string
    {
        return 'Pending Students';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:C' => ['alignment' => ['horizontal' => 'left']],
            'C' => ['numberFormat' => ['formatCode' => '#,##0.00']],
        ];
    }
}
