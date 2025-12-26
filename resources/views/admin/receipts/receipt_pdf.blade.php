<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $payment->receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { font-size: 13px; line-height: 1.3; color: #333; padding: 15px; max-width: 800px; margin: 0 auto; }
        .receipt-container { border: 1px solid #ddd; padding: 15px; }
        
        /* Header */
        .header { text-align: center; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 2px solid #000; }
        .college-name { font-size: 18px; font-weight: bold; margin-bottom: 3px; }
        .college-address, .college-contact { font-size: 11px; color: #555; margin-bottom: 2px; }
        .receipt-title { font-size: 14px; font-weight: bold; margin-top: 5px; text-decoration: underline; }
        
        /* Details Table */
        .details-table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12px; }
        .details-table td { padding: 6px 8px; border: 1px solid #ddd; }
        .details-table .label { font-weight: bold; background-color: #f5f5f5; width: 20%; }
        .amount-paid-row td { background-color: #f0f0f0; font-weight: bold; padding: 8px; }
        
        /* Fee Components */
        .components-section { margin: 10px 0; }
        .components-title { font-size: 12px; font-weight: bold; margin-bottom: 5px; }
        .components-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .components-table th, .components-table td { padding: 4px 6px; border: 1px solid #ddd; }
        .components-table th { background-color: #f5f5f5; text-align: left; }
        .text-right { text-align: right; }
        
        /* Summary */
        .summary-box { border: 1px solid #000; padding: 10px; margin: 10px 0; background-color: #f9f9f9; }
        .summary-title { font-size: 12px; font-weight: bold; margin-bottom: 5px; text-align: center; }
        .summary-table { width: 100%; font-size: 12px; }
        .summary-table td { padding: 4px 0; }
        .summary-total-due { border-top: 1px solid #000; padding-top: 6px; margin-top: 6px; font-weight: bold; }
        
        /* Footer */
        .footer { margin-top: 10px; font-size: 11px; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; }
        .thank-you { font-weight: bold; margin-bottom: 3px; }
        
        @media print {
            body { padding: 5px; }
            .receipt-container { border: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="college-name">{{ config('app.name', 'INSTITUTION NAME') }}</div>
          
            <div class="receipt-title">PAYMENT RECEIPT</div>
        </div>

        <table class="details-table">
            <tr>
                <td class="label">Receipt No:</td>
                <td>{{ $payment->receipt_number }}</td>
                <td class="label">Date:</td>
                <td>{{ $payment->payment_date->format('d-M-Y') }}</td>
            </tr>
            <tr>
                <td class="label">Student Name:</td>
                <td>{{ $student->name }}</td>
                <td class="label">Enrollment No:</td>
                <td>{{ $student->enrollment_number }}</td>
            </tr>
            @if($student->batch)
            <tr>
                <td class="label">Course:</td>
                <td>{{ $student->batch->course->name ?? 'N/A' }}</td>
                <td class="label">Batch:</td>
                <td>{{ $student->batch->name }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Payment Method:</td>
                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                <td class="label">Payment Received:</td>
                <td>{{ $payment->created_at->format('d-M-Y H:i') }}</td>
            </tr>
            <tr class="amount-paid-row">
                <td colspan="2">AMOUNT PAID:</td>
                <td colspan="2">Rs. {{ number_format($payment->amount, 2) }}</td>
            </tr>
        </table>

        @if($payment->componentItems && $payment->componentItems->count() > 0)
        <div class="components-section">
            <div class="components-title">FEE BREAKDOWN</div>
            <table class="components-table">
                <thead>
                    <tr>
                        <th>Fee Category</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment->componentItems as $item)
                    <tr>
                        <td>{{ $item->studentFee->feeCategory->name ?? 'Unknown Category' }}</td>
                        <td class="text-right">Rs. {{ number_format($item->amount_paid, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        
        @php
            $totalFees = $student->studentFees->sum('amount');
            $totalPaid = $student->studentFees->sum('paid_amount');
            $dueAmount = max(0, $totalFees - $totalPaid);
        @endphp
        <div class="summary-box">
            <div class="summary-title">FINANCIAL SUMMARY</div>
            <table class="summary-table">
                <tr>
                    <td>Total Fees:</td>
                    <td class="text-right">Rs. {{ number_format($totalFees, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Paid:</td>
                    <td class="text-right">Rs. {{ number_format($totalPaid, 2) }}</td>
                </tr>
                <tr class="summary-total-due">
                    <td>REMAINING BALANCE:</td>
                    <td class="text-right">Rs. {{ number_format($dueAmount, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div class="thank-you">Thank you for your payment!</div>
            <div>Computer generated receipt on {{ now()->format('d-M-Y H:i') }}</div>
        </div>
    </div>
</body>
</html>