<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt</title>
</head>
<body>
    <p>Dear {{ $payment->invoice->student->name }},</p>
    <p>Thank you for your payment. Please find your receipt attached to this email.</p>
    <p><strong>Payment Details:</strong></p>
    <ul>
        <li><strong>Receipt No:</strong> {{ $payment->receipt_number }}</li>
        <li><strong>Amount Paid:</strong> {{ setting('currency_symbol', '₹') }}{{ number_format($payment->amount, 2) }}</li>
        <li><strong>Payment Date:</strong> {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</li>
    </ul>
    <br>
    <p>Thank you,</p>
    <p><strong>{{ setting('college_name', 'Your College') }}</strong></p>
</body>
</html>