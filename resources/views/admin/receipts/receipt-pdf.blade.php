{{-- resources/views/admin/payments/receipt-pdf.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $payment->receipt_number }}</title>
    <style>
        /* A5 Paper Size with larger margins for more empty space */
        @page {
            size: A5;
            margin: 15mm; /* Increased margin for more whitespace */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }

        .receipt-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            text-align: center;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
        }

        .college-name {
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }

        .college-address, .college-contact {
            font-size: 9px;
            color: #555;
        }

        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
            text-decoration: underline;
            text-transform: uppercase;
            color: #000;
        }
        
        /* Main Details Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .details-table td {
            padding: 5px 8px; /* Slightly increased padding */
            border: 1px solid #ccc;
            vertical-align: top;
        }

        .details-table .label {
            font-weight: bold;
            width: 25%;
            color: #000;
        }

        .details-table .value {
            width: 25%;
        }

        .amount-paid-row td {
            background-color: #f0f0f0;
            font-size: 14px;
            font-weight: bold;
            padding: 8px;
        }
        
        /* Financial Summary */
        .summary-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 15px;
        }

        .summary-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: center;
            text-transform: uppercase;
        }

        .summary-table {
            width: 100%;
            font-size: 10px;
        }
        
        .summary-table td {
            padding: 2px 0;
        }

        .summary-table .text-right {
            text-align: right;
            font-weight: bold;
        }
        
        .summary-total-due {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 4px;
            font-weight: bold;
        }

        /* Notes Section */
        .notes-section {
            margin-bottom: 15px;
        }
        .notes-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .notes-content {
            font-size: 9px;
            color: #555;
        }
        
        /* Footer */
        .footer-content {
            margin-top: auto; /* Pushes footer to the bottom */
            font-size: 9px;
            color: #555;
            padding-top: 20px; /* Space from content above */
        }

        .footer-text {
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }

        .footer-text div {
            margin-bottom: 2px;
        }

    </style>
</head>
<body>
    <div class="receipt-container">
        {{-- Header --}}
        <div class="header">
            <div class="college-name">{{ setting('college_name', 'INSTITUTION NAME') }}</div>
            <div class="college-address">{{ setting('college_address', 'Institution Address') }}</div>
            @if(setting('college_phone') || setting('college_email'))
                <div class="college-contact">
                    {{ setting('college_phone', '') }}
                    @if(setting('college_phone') && setting('college_email')) | @endif
                    {{ setting('college_email', '') }}
                </div>
            @endif
            <div class="receipt-title">Payment Receipt</div>
        </div>

        {{-- Combined Details Table --}}
        <table class="details-table">
            <tr>
                <td class="label">Receipt No:</td>
                <td class="value">{{ $payment->receipt_number }}</td>
                <td class="label">Date:</td>
                <td class="value">{{ $payment->payment_date->format('d-M-Y') }}</td>
            </tr>
            <tr>
                <td class="label">Student Name:</td>
                <td class="value">{{ $student->name }}</td>
                <td class="label">Enrollment No:</td>
                <td class="value">{{ $student->enrollment_number }}</td>
            </tr>
            @if($student->batch)
            <tr>
                <td class="label">Course:</td>
                <td class="value">{{ $student->batch->course->name ?? 'N/A' }}</td>
                <td class="label">Batch:</td>
                <td class="value">{{ $student->batch->name }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Payment Method:</td>
                <td class="value" colspan="3">{{ ucfirst($payment->payment_method) }}</td>
            </tr>
            <tr class="amount-paid-row">
                <td class="label">AMOUNT PAID:</td>
                <td colspan="3">Rs. {{ number_format($payment->amount, 2) }}</td>
            </tr>
        </table>
        
        {{-- Financial Summary --}}
        @php
            $totalFees = $student->studentFees->sum('amount');
            $totalConcessions = $student->studentFees->sum('concession_amount');
            $totalPaid = $student->studentFees->sum('paid_amount');
            $dueAmount = max(0, $totalFees - $totalConcessions - $totalPaid);
        @endphp
        <div class="summary-box">
            <div class="summary-title">Overall Financial Summary</div>
            <table class="summary-table">
                <tr>
                    <td>Total Fees:</td>
                    <td class="text-right">Rs. {{ number_format($totalFees, 2) }}</td>
                </tr>
                @if($totalConcessions > 0)
                <tr>
                    <td>Concessions:</td>
                    <td class="text-right">(-) Rs. {{ number_format($totalConcessions, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>Total Paid (All Time):</td>
                    <td class="text-right">(-) Rs. {{ number_format($totalPaid, 2) }}</td>
                </tr>
                <tr class="summary-total-due">
                    <td>REMAINING BALANCE:</td>
                    <td class="text-right">Rs. {{ number_format($dueAmount, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Notes --}}
        @if($payment->notes)
        <div class="notes-section">
            <div class="notes-title">Notes:</div>
            <div class="notes-content">{{ $payment->notes }}</div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer-content">
            <div class="footer-text">
                <div><strong>Thank you for your payment!</strong></div>
                <div>This is a computer-generated receipt. Generated on: {{ now()->format('d-M-Y H:i') }}</div>
            </div>
        </div>
    </div>
</body>
</html>