@extends('layouts.theme')

@section('content')
<div class="receipt-container">
    <div class="receipt-header">
        <h2>Payment Receipt #{{ $payment->receipt_number }}</h2>
        <p>Date: {{ $payment->payment_date->format('d/m/Y') }}</p>
    </div>

    <div class="student-info">
        <h4>Student Information</h4>
        <p><strong>Name:</strong> {{ $payment->student->name }}</p>
        <p><strong>Enrollment:</strong> {{ $payment->student->enrollment_number }}</p>
    </div>

    <div class="payment-details">
        <h4>Payment Breakdown</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Fee Category</th>
                    <th>Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payment->componentItems as $item)
                <tr>
                    <td>{{ $item->studentFee->feeCategory->name }}</td>
                    <td>₹{{ number_format($item->amount_paid, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Total Paid:</th>
                    <th>₹{{ number_format($payment->amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="receipt-footer">
        <p>Payment Method: {{ $payment->payment_method }}</p>
        @if($payment->transaction_id)
        <p>Transaction ID: {{ $payment->transaction_id }}</p>
        @endif
    </div>
</div>
@endsection