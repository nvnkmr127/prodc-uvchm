

@extends('layouts.theme')

@section('title', 'Payment Receipt - ' . $payment->receipt_number)

@push('styles')
<style>
    .receipt-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .receipt-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 30px;
        text-align: center;
        border-radius: 8px 8px 0 0;
    }
    
    .receipt-body {
        padding: 30px;
    }
    
    .amount-paid {
        background: #e8f5e8;
        border: 2px solid #28a745;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        margin: 20px 0;
    }
    
    .amount-paid h2 {
        color: #28a745;
        margin: 0;
        font-size: 2.5rem;
        font-weight: bold;
    }
    
    .info-section {
        margin: 25px 0;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #555;
        min-width: 150px;
    }
    
    .info-value {
        color: #333;
        font-weight: 500;
    }
    
    .components-table {
        margin: 25px 0;
    }
    
    .components-table table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    
    .components-table th,
    .components-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .components-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }
    
    .text-right {
        text-align: right !important;
    }
    
    .financial-summary {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #007bff;
        margin: 20px 0;
    }
    
    .financial-summary h5 {
        margin-bottom: 15px;
        color: #333;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin: 8px 0;
    }
    
    .summary-row.total {
        font-weight: bold;
        font-size: 1.1rem;
        border-top: 2px solid #007bff;
        padding-top: 10px;
        margin-top: 15px;
    }
    
    .due-amount {
        color: #dc3545;
        font-weight: bold;
    }
    
    .footer-note {
        text-align: center;
        color: #666;
        font-size: 0.9rem;
        margin-top: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .no-print {
        margin-bottom: 20px;
    }
    
    /* Print Styles */
    @media print {
        .no-print { 
            display: none !important; 
        }
        body { 
            margin: 0; 
            background: white !important;
        }
        .receipt-container { 
            box-shadow: none; 
            border: 1px solid #000;
            max-width: none;
        }
        .receipt-header {
            background: #007bff !important;
            -webkit-print-color-adjust: exact;
        }
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .receipt-body {
            padding: 20px;
        }
        
        .amount-paid h2 {
            font-size: 2rem;
        }
        
        .info-row {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .info-label {
            margin-bottom: 5px;
            min-width: auto;
        }
        
        .components-table {
            overflow-x: auto;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid my-4">
    {{-- Action Buttons - FIXED WITH CORRECT ROUTE NAMES --}}
    <div class="text-center no-print">
        <div class="btn-group">
            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Back to Student
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print mr-2"></i> Print Receipt
            </button>
            {{-- ✅ FIXED: Using the actual route name from your system --}}
            <a href="{{ route('admin.payments.receipt.pdf', [$student, $payment]) }}" class="btn btn-success">
                <i class="fas fa-download mr-2"></i> Download PDF
            </a>
        </div>
    </div>

    {{-- Receipt Container --}}
    <div class="receipt-container">
        {{-- Header --}}
        <div class="receipt-header">
            <h1 style="margin: 0 0 10px 0; font-size: 2rem;">{{ setting('college_name', 'Your Institution Name') }}</h1>
            <p style="margin: 0; opacity: 0.9;">{{ setting('college_address', 'Institution Address') }}</p>
            @if(setting('college_phone') || setting('college_email'))
                <p style="margin: 10px 0 0 0; opacity: 0.9;">
                    {{ setting('college_phone', '') }}
                    @if(setting('college_phone') && setting('college_email')) | @endif
                    {{ setting('college_email', '') }}
                </p>
            @endif
            <div style="margin-top: 20px; font-size: 1.2rem; font-weight: bold;">
                📧 PAYMENT RECEIPT
            </div>
        </div>

        {{-- Body --}}
        <div class="receipt-body">
            {{-- Receipt Number & Date --}}
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #007bff;">Receipt #{{ $payment->receipt_number }}</h3>
                <p style="margin: 5px 0 0 0; color: #666;">{{ $payment->payment_date->format('d F, Y') }}</p>
            </div>

            {{-- Amount Paid Display --}}
            <div class="amount-paid">
                <p style="margin: 0 0 10px 0; color: #666; font-size: 1.1rem;">Amount Paid</p>
                <h2>₹{{ number_format($payment->amount, 2) }}</h2>
            </div>

            {{-- Student Information --}}
            <div class="info-section">
                <h4 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px;">
                    👤 Student Information
                </h4>
                <div class="info-row">
                    <span class="info-label">Student Name:</span>
                    <span class="info-value">{{ $student->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Enrollment Number:</span>
                    <span class="info-value">{{ $student->enrollment_number }}</span>
                </div>
                @if($student->batch)
                <div class="info-row">
                    <span class="info-label">Course:</span>
                    <span class="info-value">{{ $student->batch->course->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Batch:</span>
                    <span class="info-value">{{ $student->batch->name }}</span>
                </div>
                @endif
            </div>

            {{-- Payment Information --}}
            <div class="info-section">
                <h4 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px;">
                    💳 Payment Information
                </h4>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value">{{ ucfirst($payment->payment_method) }}</span>
                </div>
                @if($payment->transaction_id)
                <div class="info-row">
                    <span class="info-label">Transaction ID:</span>
                    <span class="info-value">{{ $payment->transaction_id }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Received By:</span>
                    <span class="info-value">{{ $payment->createdBy->name ?? 'System' }}</span>
                </div>
            </div>

            {{-- Fee Components Breakdown --}}
            @if($payment->componentItems && $payment->componentItems->count() > 0)
            <div class="components-table">
                <h4 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px;">
                    📋 Fee Breakdown
                </h4>
                <table>
                    <thead>
                        <tr>
                            <th>Fee Category</th>
                            <th class="text-right">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payment->componentItems as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->studentFee->feeCategory->name }}</strong>
                                @if($item->notes)
                                    <br><small style="color: #666;">{{ $item->notes }}</small>
                                @endif
                            </td>
                            <td class="text-right">
                                <strong>₹{{ number_format($item->amount_paid, 2) }}</strong>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td><strong>Total Paid</strong></td>
                            <td class="text-right"><strong>₹{{ number_format($payment->amount, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif

            {{-- Financial Summary with Due Amount --}}
            @php
                // Calculate financial summary
                $totalFees = $student->studentFees->sum('amount');
                $totalConcessions = $student->studentFees->sum('concession_amount');
                $totalPaid = $student->studentFees->sum('paid_amount');
                $dueAmount = max(0, $totalFees - $totalConcessions - $totalPaid);
                
                // Calculate overdue amount
                $overdueAmount = $student->studentFees()
                    ->whereDate('due_date', '<', now())
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->get()
                    ->sum(function ($fee) {
                        return max(0, $fee->amount - $fee->concession_amount - $fee->paid_amount);
                    });
            @endphp

            <div class="financial-summary">
                <h5>💰 Complete Financial Summary</h5>
                <div class="summary-row">
                    <span>Total Fee Amount:</span>
                    <span>₹{{ number_format($totalFees, 2) }}</span>
                </div>
                @if($totalConcessions > 0)
                <div class="summary-row">
                    <span>Total Concessions:</span>
                    <span style="color: #28a745;">- ₹{{ number_format($totalConcessions, 2) }}</span>
                </div>
                @endif
                <div class="summary-row">
                    <span>Total Paid (Till Date):</span>
                    <span style="color: #28a745;">₹{{ number_format($totalPaid, 2) }}</span>
                </div>
                <div class="summary-row total">
                    <span>Remaining Due Amount:</span>
                    <span class="{{ $dueAmount > 0 ? 'due-amount' : '' }}">
                        ₹{{ number_format($dueAmount, 2) }}
                        @if($dueAmount <= 0)
                            ✅ <small>(Fully Paid)</small>
                        @endif
                    </span>
                </div>
                @if($overdueAmount > 0)
                <div class="summary-row" style="color: #dc3545; font-weight: bold;">
                    <span>⚠️ Overdue Amount:</span>
                    <span>₹{{ number_format($overdueAmount, 2) }}</span>
                </div>
                @endif
            </div>

            {{-- Notes --}}
            @if($payment->notes)
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 20px 0;">
                <strong>📝 Notes:</strong> {{ $payment->notes }}
            </div>
            @endif

            {{-- Footer --}}
            <div class="footer-note">
                <p style="margin: 0; font-weight: bold;">Thank you for your payment! 🙏</p>
                <p style="margin: 5px 0 0 0;">
                    This is a computer-generated receipt and does not require a signature.
                </p>
                <small>
                    Generated on {{ now()->format('d F, Y \a\t h:i A') }}
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show any session messages
    @if(session('success'))
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        }
    @endif
    
    @if(session('info'))
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Information',
                text: '{{ session('info') }}',
                timer: 4000,
                showConfirmButton: false
            });
        }
    @endif
});
</script>
@endpush