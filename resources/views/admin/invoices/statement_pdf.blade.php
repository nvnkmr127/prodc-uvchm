<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statement of Account for {{ $student->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .header p { margin: 0; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        .table th { background-color: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .text-danger { color: #dc3545; }
        .text-success { color: #198754; }
    </style>
</head>
<body>
    <div class="header">
        {{-- This assumes you have a setting for 'college_name' --}}
        <h1>{{ setting('college_name', 'My College') }}</h1>
        <p>{{ setting('college_address', 'College Address Here') }}</p>
        <p>Email: {{ setting('college_email', '') }} | Phone: {{ setting('college_phone', '') }}</p>
        <hr>
        <h2>Statement of Account</h2>
    </div>

    <p><strong>Student Name:</strong> {{ $student->name }}</p>
    <p><strong>Enrollment No:</strong> {{ $student->enrollment_number }}</p>
    <p><strong>Statement Period:</strong> {{ $startDate->format('d M, Y') }} to {{ $endDate->format('d M, Y') }}</p>
    <br>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Particulars</th>
                <th class="text-right">Debit (Fee Incurred)</th>
                <th class="text-right">Credit (Paid)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('d M, Y') }}</td>
                    @if($transaction instanceof App\Models\Invoice)
                        <td>Invoice Generated (#{{ $transaction->invoice_number }})</td>
                        <td class="text-right text-danger">{{ number_format($transaction->total_amount, 2) }}</td>
                        <td class="text-right"></td>
                    @elseif($transaction instanceof App\Models\Payment)
                        <td>Payment Received (Receipt #{{$transaction->receipt_number}})</td>
                        <td class="text-right"></td>
                        <td class="text-right text-success">{{ number_format($transaction->amount, 2) }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="4" style="text-align: center;">No transactions found in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>