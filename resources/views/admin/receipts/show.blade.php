<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $payment->receipt_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .receipt-container { max-width: 800px; margin: 40px auto; background: #fff; border: 1px solid #dee2e6; box-shadow: 0 0 1rem rgba(0,0,0,.1); }
        .receipt-header { text-align: center; padding: 40px; border-bottom: 1px solid #dee2e6; }
        .receipt-header img { max-height: 90px; margin-bottom: 20px; }
        .receipt-body { padding: 40px; }
        .receipt-footer { text-align: center; padding: 20px; border-top: 1px solid #dee2e6; font-size: 0.8em; color: #6c757d; }
        @media print {
            body { background-color: #fff; }
            .receipt-container { margin: 0; border: none; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container receipt-container">
        <div class="receipt-header">
            @if(isset($settings['college_logo']))
                {{-- This special path is needed for PDF generation --}}
                <img src="{{ public_path('storage/' . $settings['college_logo']) }}" alt="College Logo">
            @endif
            <h2>{{ $settings['college_name'] ?? 'My College' }}</h2>
            <p class="text-muted">{{ $settings['college_address'] ?? 'College Address Here' }}</p>
            <h4>PAYMENT RECEIPT</h4>
        </div>
        <div class="receipt-body">
            <div class="row mb-4">
                <div class="col-6">
                    <strong>Receipt No:</strong> {{ $payment->receipt_number }}<br>
                    <strong>Payment Date:</strong> {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}
                </div>
                <div class="col-6 text-end">
                    <strong>Billed To:</strong><br>
                    {{ $payment->invoice->student->name }}<br>
                    Enrollment #: {{ $payment->invoice->student->enrollment_number }}<br>
                    Batch: {{ $payment->invoice->student->batch->name ?? 'N/A' }}
                </div>
            </div>

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount Paid ({{ $settings['currency_symbol'] ?? '₹' }})</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Payment towards Invoice #{{ $payment->invoice->invoice_number }}</td>
                        <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-end">Total Paid:</th>
                        <th class="text-end">{{ $settings['currency_symbol'] ?? '₹' }} {{ number_format($payment->amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>

            <div class="row mt-4">
                <div class="col-8">
                    <p><strong>Payment Method:</strong> {{ $payment->payment_method }}</p>
                    @if($payment->notes)
                        <p><strong>Notes:</strong> {{ $payment->notes }}</p>
                    @endif
                </div>
                <div class="col-4 text-center">
                    <p class="mt-4">__________________<br>Authorized Signatory</p>
                </div>
            </div>
        </div>
        <div class="receipt-footer">
            <p>{{ $settings['invoice_footer_text'] ?? 'This is a computer-generated receipt.' }}</p>
        </div>
    </div>

    <div class="text-center my-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <a href="{{ route('admin.payments.receipt.pdf', $payment) }}" class="btn btn-danger">Download as PDF</a>
        <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" class="btn btn-secondary">Back to Invoice</a>
    </div>

</body>
</html>
