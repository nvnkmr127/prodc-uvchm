<?php
// Create these Blade view files:

// 1. resources/views/public/receipt.blade.php (HTML view)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $payment->receipt_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
        }
        .logo-section {
            text-align: center;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .receipt-header {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .amount-section {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #0d6efd;
        }
        @media print {
            .no-print { display: none !important; }
            .receipt-container { border: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="receipt-container">
            <!-- Logo and Institution Info -->
            <div class="logo-section">
                <h2 class="text-primary mb-1">{{ config('app.name', 'UVCHM') }}</h2>
                <p class="text-muted mb-0">Payment Receipt</p>
            </div>

            <!-- Receipt Header -->
            <div class="receipt-header">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="text-success mb-3">
                            <i class="fas fa-check-circle"></i> Payment Received
                        </h4>
                        <p><strong>Receipt Number:</strong> {{ $payment->receipt_number }}</p>
                        <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</p>
                        <p><strong>Payment Method:</strong> {{ $payment->payment_method }}</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><strong>Invoice Number:</strong> {{ $payment->invoice->invoice_number ?? 'N/A' }}</p>
                        <p><strong>Student ID:</strong> {{ $payment->invoice->student->enrollment_number ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Student Information -->
            @if($payment->invoice && $payment->invoice->student)
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2">Student Information</h5>
                    <p><strong>Name:</strong> {{ $payment->invoice->student->name }}</p>
                    <p><strong>Enrollment Number:</strong> {{ $payment->invoice->student->enrollment_number }}</p>
                    <p><strong>Email:</strong> {{ $payment->invoice->student->email }}</p>
                    <p><strong>Mobile:</strong> {{ $payment->invoice->student->student_mobile }}</p>
                </div>
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2">Parent Information</h5>
                    <p><strong>Father's Name:</strong> {{ $payment->invoice->student->father_name ?? 'N/A' }}</p>
                    <p><strong>Father's Mobile:</strong> {{ $payment->invoice->student->father_mobile ?? 'N/A' }}</p>
                    @if($payment->invoice->student->batch)
                        <p><strong>Batch:</strong> {{ $payment->invoice->student->batch->name }}</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Amount Information -->
            <div class="amount-section mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-3">Payment Details</h5>
                        @if($payment->invoice)
                            <div class="row">
                                <div class="col-6"><strong>Total Fee Amount:</strong></div>
                                <div class="col-6">₹{{ number_format($payment->invoice->total_amount, 2) }}</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Previously Paid:</strong></div>
                                <div class="col-6">₹{{ number_format($payment->invoice->paid_amount ?? 0, 2) }}</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Current Payment:</strong></div>
                                <div class="col-6 text-success"><strong>₹{{ number_format($payment->amount, 2) }}</strong></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6"><strong>Remaining Balance:</strong></div>
                                <div class="col-6">
                                    @php
                                        $remaining = $payment->invoice->total_amount - ($payment->invoice->paid_amount + $payment->amount);
                                    @endphp
                                    <strong>₹{{ number_format(max(0, $remaining), 2) }}</strong>
                                </div>
                            </div>
                        @else
                            <div class="row">
                                <div class="col-6"><strong>Amount Paid:</strong></div>
                                <div class="col-6 text-success"><strong>₹{{ number_format($payment->amount, 2) }}</strong></div>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4 text-center">
                        <h3 class="text-primary">₹{{ number_format($payment->amount, 2) }}</h3>
                        <span class="badge bg-success">Paid</span>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            @if($payment->notes)
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Notes</h5>
                <p>{{ $payment->notes }}</p>
            </div>
            @endif

            <!-- Footer -->
            <div class="text-center mt-4 pt-4 border-top">
                <p class="text-muted mb-2">Thank you for your payment!</p>
                <p class="small text-muted">This is a computer-generated receipt and is valid without signature.</p>
                
                <!-- Action Buttons -->
                <div class="no-print mt-3">
                    <button onclick="window.print()" class="btn btn-primary me-2">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    <a href="{{ route('receipts.public.pdf', $payment->receipt_number) }}" 
                       class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>

<?php
// 2. resources/views/public/receipt-pdf.blade.php (PDF-optimized view)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - {{ $payment->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .receipt-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .receipt-info > div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .section h3 {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .amount-highlight {
            background-color: #f0f8ff;
            padding: 15px;
            text-align: center;
            border: 2px solid #007bff;
            margin: 20px 0;
        }
        .amount-highlight h2 {
            margin: 0;
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table td {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #333;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ config('app.name', 'UVCHM') }}</h1>
        <p>Payment Receipt</p>
    </div>

    <!-- Receipt Information -->
    <div class="receipt-info">
        <div>
            <strong>Receipt Number:</strong> {{ $payment->receipt_number }}<br>
            <strong>Date:</strong> {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}<br>
            <strong>Payment Method:</strong> {{ $payment->payment_method }}
        </div>
        <div class="text-right">
            @if($payment->invoice)
                <strong>Invoice Number:</strong> {{ $payment->invoice->invoice_number }}<br>
                <strong>Student ID:</strong> {{ $payment->invoice->student->enrollment_number ?? 'N/A' }}
            @endif
        </div>
    </div>

    <!-- Student Information -->
    @if($payment->invoice && $payment->invoice->student)
    <div class="section">
        <h3>Student Information</h3>
        <table>
            <tr>
                <td><strong>Name:</strong></td>
                <td>{{ $payment->invoice->student->name }}</td>
                <td><strong>Father's Name:</strong></td>
                <td>{{ $payment->invoice->student->father_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Enrollment Number:</strong></td>
                <td>{{ $payment->invoice->student->enrollment_number }}</td>
                <td><strong>Father's Mobile:</strong></td>
                <td>{{ $payment->invoice->student->father_mobile ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Mobile:</strong></td>
                <td>{{ $payment->invoice->student->student_mobile }}</td>
                <td><strong>Batch:</strong></td>
                <td>{{ $payment->invoice->student->batch->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Payment Amount (Highlighted) -->
    <div class="amount-highlight">
        <h2>Amount Paid: ₹{{ number_format($payment->amount, 2) }}</h2>
        <p>Status: <strong>PAID</strong></p>
    </div>

    <!-- Payment Details -->
    @if($payment->invoice)
    <div class="section">
        <h3>Payment Breakdown</h3>
        <table>
            <tr>
                <td><strong>Total Fee Amount:</strong></td>
                <td class="text-right">₹{{ number_format($payment->invoice->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Previously Paid:</strong></td>
                <td class="text-right">₹{{ number_format($payment->invoice->paid_amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Current Payment:</strong></td>
                <td class="text-right"><strong>₹{{ number_format($payment->amount, 2) }}</strong></td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td><strong>Remaining Balance:</strong></td>
                <td class="text-right">
                    @php
                        $remaining = $payment->invoice->total_amount - ($payment->invoice->paid_amount + $payment->amount);
                    @endphp
                    <strong>₹{{ number_format(max(0, $remaining), 2) }}</strong>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Notes -->
    @if($payment->notes)
    <div class="section">
        <h3>Notes</h3>
        <p>{{ $payment->notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for your payment!</p>
        <p>This is a computer-generated receipt and is valid without signature.</p>
        <p>Generated on {{ now()->format('d M, Y h:i A') }}</p>
    </div>
</body>
</html>