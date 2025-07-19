<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #{{ $payment->receipt_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #1f2937;
        }
        
        .receipt-wrapper {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transform: translateY(0);
            transition: all 0.3s ease;
        }
        
        .receipt-wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.3);
        }
        
        /* Header with animated background */
        .receipt-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .receipt-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="60" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="60" cy="40" r="1" fill="rgba(255,255,255,0.05)"/></svg>');
            animation: float 20s ease-in-out infinite;
            opacity: 0.3;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }
        
        .logo-container {
            position: relative;
            z-index: 2;
            margin-bottom: 25px;
        }
        
        .logo-container img {
            max-height: 90px;
            max-width: 250px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .college-name {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }
        
        .college-address {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
        }
        
        .receipt-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            position: relative;
            z-index: 2;
        }
        
        /* Main Content */
        .receipt-content {
            padding: 50px 40px;
        }
        
        .receipt-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .info-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .info-card:hover::before {
            transform: scaleX(1);
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .info-sub {
            font-size: 14px;
            color: #6b7280;
        }
        
        /* Student Section */
        .student-section {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
            border-radius: 20px;
            padding: 30px;
            margin: 40px 0;
            position: relative;
            overflow: hidden;
        }
        
        .student-section::before {
            content: '👨‍🎓';
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 40px;
            opacity: 0.3;
        }
        
        .student-title {
            color: #1e40af;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        
        .student-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 10px;
        }
        
        .student-details {
            color: #3730a3;
            font-size: 16px;
            line-height: 1.6;
        }
        
        /* Payment Summary */
        .payment-summary {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin: 40px 0;
            border: 1px solid #e5e7eb;
        }
        
        .summary-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            padding: 20px 30px;
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s ease;
        }
        
        .summary-row:hover {
            background-color: #f9fafb;
        }
        
        .summary-row.highlight {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-bottom: 1px solid #bbf7d0;
        }
        
        .summary-row.total {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-weight: 700;
            font-size: 18px;
            border-bottom: none;
        }
        
        .summary-label {
            font-size: 16px;
            font-weight: 500;
            color: #374151;
        }
        
        .summary-amount {
            font-size: 18px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            color: #1f2937;
        }
        
        .total .summary-label,
        .total .summary-amount {
            color: white;
        }
        
        /* Payment Method Badge */
        .payment-method-badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #065f46;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            margin: 25px 0;
            border: 2px solid #10b981;
        }
        
        .payment-method-badge::before {
            content: '💳';
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-paid {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 1px solid #22c55e;
        }
        
        .status-partial {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        
        /* Notes Section */
        .notes-section {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 2px solid #f59e0b;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            position: relative;
        }
        
        .notes-section::before {
            content: '📝';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            opacity: 0.5;
        }
        
        .notes-title {
            color: #92400e;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        
        .notes-content {
            color: #78350f;
            font-size: 16px;
            line-height: 1.6;
            font-style: italic;
        }
        
        /* Action Buttons */
        .action-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 15px 30px;
            margin: 10px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5);
        }
        
        /* Footer */
        .receipt-footer {
            background: #1f2937;
            color: #d1d5db;
            padding: 30px 40px;
            text-align: center;
        }
        
        .footer-text {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .security-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .security-badge::before {
            content: '🔒';
            margin-right: 8px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .receipt-wrapper {
                border-radius: 15px;
            }
            
            .receipt-header {
                padding: 30px 20px;
            }
            
            .college-name {
                font-size: 24px;
            }
            
            .receipt-content {
                padding: 30px 20px;
            }
            
            .receipt-info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .info-card {
                padding: 20px;
            }
            
            .student-section {
                padding: 25px 20px;
            }
            
            .summary-row {
                padding: 15px 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .btn {
                width: 100%;
                margin: 8px 0;
            }
            
            .action-section {
                padding: 30px 20px;
            }
        }
        
        @media (max-width: 480px) {
            .receipt-header {
                padding: 25px 15px;
            }
            
            .college-name {
                font-size: 20px;
            }
            
            .receipt-badge {
                font-size: 16px;
                padding: 12px 20px;
            }
            
            .receipt-content {
                padding: 25px 15px;
            }
            
            .student-name {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-wrapper">
        <!-- Header -->
        <div class="receipt-header">
            <div class="logo-container">
                @php
                    $logoPath = $settings['college_logo'] ?? '';
                    $logoShown = false;
                    
                    if (!empty($logoPath)) {
                        $webLogoUrl = asset('storage/' . $logoPath);
                        echo '<img src="' . $webLogoUrl . '" alt="College Logo" onerror="this.style.display=\'none\'">';
                        $logoShown = true;
                    }
                @endphp
            </div>
            
            @if(!$logoShown)
                <h1 class="college-name">{{ $settings['college_name'] ?? 'My College' }}</h1>
            @endif
            
            @if(!empty($settings['college_address']))
                <p class="college-address">{{ $settings['college_address'] }}</p>
            @endif
            
            <div class="receipt-badge">Payment Receipt</div>
        </div>

        <!-- Content -->
        <div class="receipt-content">
            <!-- Receipt Information Grid -->
            <div class="receipt-info-grid">
                <div class="info-card">
                    <div class="info-label">Receipt Number</div>
                    <div class="info-value">{{ $payment->receipt_number }}</div>
                    <div class="info-sub">{{ \Carbon\Carbon::parse($payment->payment_date)->format('F j, Y \a\t g:i A') }}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-label">Invoice Reference</div>
                    <div class="info-value">#{{ $payment->invoice->invoice_number }}</div>
                    <div class="info-sub">
                        @php
                            $dueAmount = $payment->invoice->due_amount;
                            $status = $dueAmount <= 0 ? 'paid' : 'partial';
                        @endphp
                        <span class="status-badge status-{{ $status }}">
                            {{ $dueAmount <= 0 ? 'Fully Paid' : 'Partially Paid' }}
                        </span>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">{{ $payment->payment_method }}</div>
                    <div class="info-sub">Processed successfully</div>
                </div>
            </div>

            <!-- Student Information -->
            <div class="student-section">
                <div class="student-title">Billed To</div>
                <div class="student-name">{{ $payment->invoice->student->name }}</div>
                <div class="student-details">
                    <strong>Enrollment:</strong> {{ $payment->invoice->student->enrollment_number }}<br>
                    @if($payment->invoice->student->batch)
                        <strong>Batch:</strong> {{ $payment->invoice->student->batch->name }}<br>
                    @endif
                    @if($payment->invoice->student->email)
                        <strong>Email:</strong> {{ $payment->invoice->student->email }}
                    @endif
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="payment-summary">
                <div class="summary-header">Payment Summary</div>
                
                @php 
                    $currencySymbol = $settings['currency_symbol'] ?? '₹';
                @endphp
                
                <div class="summary-row highlight">
                    <span class="summary-label">Payment Amount</span>
                    <span class="summary-amount">{{ $currencySymbol }}{{ number_format($payment->amount, 2) }}</span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Invoice Total</span>
                    <span class="summary-amount">{{ $currencySymbol }}{{ number_format($payment->invoice->total_amount, 2) }}</span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Total Paid to Date</span>
                    <span class="summary-amount">{{ $currencySymbol }}{{ number_format($payment->invoice->paid_amount, 2) }}</span>
                </div>
                
                <div class="summary-row total">
                    <span class="summary-label">Outstanding Balance</span>
                    <span class="summary-amount">{{ $currencySymbol }}{{ number_format($payment->invoice->due_amount, 2) }}</span>
                </div>
            </div>

            <!-- Notes -->
            @if($payment->notes)
            <div class="notes-section">
                <div class="notes-title">Additional Notes</div>
                <div class="notes-content">{{ $payment->notes }}</div>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="footer-text">
                {{ $settings['invoice_footer_text'] ?? 'This is a computer-generated receipt and does not require a signature.' }}
            </div>
            <div class="security-badge">
                Digitally Verified & Secure
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-section">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Print Receipt
            </button>
            <a href="{{ request()->url() }}/pdf" class="btn btn-secondary">
                📄 Download PDF
            </a>
        </div>
    </div>
</body>
</html>