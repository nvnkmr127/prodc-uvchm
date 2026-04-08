<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserCollectionExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $payments;

    protected $user;

    protected $startDate;

    protected $endDate;

    public function __construct(Collection $payments, User $user, Carbon $startDate, Carbon $endDate)
    {
        $this->payments = $payments;
        $this->user = $user;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return $this->payments->map(function ($payment, $index) {
            return [
                'sr_no' => $index + 1,
                'receipt_number' => $payment->receipt_number ?? 'N/A',
                'payment_date' => Carbon::parse($payment->payment_date)->format('d-m-Y'),
                'student_name' => $payment->student->name ?? 'N/A',
                'admission_number' => $payment->student->admission_number ?? 'N/A',
                'payment_method' => ucfirst($payment->payment_method ?? 'cash'),
                'amount' => $payment->amount,
                'transaction_id' => $payment->transaction_id ?? 'N/A',
                'status' => ucfirst($payment->status),
                'created_at' => $payment->created_at->format('d-m-Y H:i:s'),
                'notes' => $payment->notes ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sr. No.',
            'Receipt Number',
            'Payment Date',
            'Student Name',
            'Admission Number',
            'Payment Method',
            'Amount (₹)',
            'Transaction ID',
            'Status',
            'Created At',
            'Notes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '4e73df']],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
            'A:K' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function title(): string
    {
        return 'Collections_'.$this->user->name.'_'.$this->startDate->format('d-m-Y').'_to_'.$this->endDate->format('d-m-Y');
    }
}
