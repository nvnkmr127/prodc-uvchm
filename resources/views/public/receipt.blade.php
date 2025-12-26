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
</head>
<body class="bg-light">
    <div class="container-fluid my-4">
        {{-- Action Buttons --}}
        <div class="text-center no-print">
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print mr-2"></i> Print Receipt
                </button>
                <a href="{{ route('public.receipt.pdf', $payment->receipt_number) }}" class="btn btn-success">
                    <i class="fas fa-download mr-2"></i> Download PDF
                </a>
            </div>
        </div>

        {{-- Receipt Container --}}
        <div class="receipt-container">
            {{-- Header --}}
            <div class="receipt-header">
                <h1 style="margin: 0 0 10px 0; font-size: 2rem;">{{ config('app.name', 'Your Institution Name') }}</h1>
                <p style="margin: 0; opacity: 0.9;">Institution Address</p>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">Phone | Email</p>
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
                        <span class="info-value">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                    </div>
                    @if($payment->transaction_id)
                    <div class="info-row">
                        <span class="info-label">Transaction ID:</span>
                        <span class="info-value">{{ $payment->transaction_id }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Payment Received:</span>
                        <span class="info-value">{{ $payment->created_at->format('d M Y, h:i A') }}</span>
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

                {{-- Financial Summary (with concessions calculated but not shown) --}}
                @php
                    // Calculate comprehensive summary including concessions
                    $totalFees = $student->studentFees->sum('amount');
                    $totalConcessions = $student->studentFees->sum('concession_amount');
                    $totalPaid = $student->studentFees->sum('paid_amount');
                    
                    // Net amount = Original Fee - Concessions
                    $netFeeAmount = $totalFees - $totalConcessions;
                    
                    // Remaining due = Net Fee Amount - Total Paid
                    $dueAmount = max(0, $netFeeAmount - $totalPaid);
                @endphp

                <div class="financial-summary">
                    <h5>💰 Financial Summary</h5>
                    <div class="summary-row">
                        <span>Total Fee Amount:</span>
                        <span>₹{{ number_format($totalFees, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Net Payable Amount:</span>
                        <span>₹{{ number_format($netFeeAmount, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Paid (Till Date):</span>
                        <span style="color: #28a745;">₹{{ number_format($totalPaid, 2) }}</span>
                    </div>
                    <div class="summary-row total">
                        <span>Remaining Due Amount:</span>
                        <span class="{{ $dueAmount > 0 ? 'text-danger' : 'text-success' }}">
                            ₹{{ number_format($dueAmount, 2) }}
                            @if($dueAmount <= 0)
                                ✅ <small>(Fully Paid)</small>
                            @endif
                        </span>
                    </div>
                </div>

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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>