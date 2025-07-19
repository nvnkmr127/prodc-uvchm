<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt #{{ $payment->receipt_number }}</title>
    <style>
        @page {
            size: A5;
            margin: 0;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 1cm;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            max-height: 70px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #222;
        }
        .header p {
            margin: 4px 0;
            font-size: 11px;
            color: #555;
        }
        .details-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .receipt-info {
            width: 100%;
            margin-bottom: 25px;
        }
        .receipt-info td {
            padding: 2px 0;
            font-size: 12px;
        }
        .summary-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .summary-table th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .total-row strong {
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            {{-- Display college logo with different paths for web vs PDF --}}
            @if(!empty($settings['college_logo']))
                @php
                    $logoPath = $settings['college_logo'];
                    $logoShown = false;
                    
                    // Detect if we're generating a PDF or viewing in browser
                    $isPDF = request()->routeIs('*.pdf') || str_contains(request()->url(), '/pdf');
                    
                    if ($isPDF) {
                        // For PDF: Use file system paths
                        $pathsToTry = [
                            public_path('storage/' . $logoPath),
                            storage_path('app/public/' . $logoPath),
                            public_path($logoPath)
                        ];
                        
                        foreach ($pathsToTry as $path) {
                            if (file_exists($path)) {
                                echo '<img src="' . $path . '" alt="College Logo">';
                                $logoShown = true;
                                break;
                            }
                        }
                    } else {
                        // For web browser: Use URL paths
                        $webLogoUrl = asset('storage/' . $logoPath);
                        echo '<img src="' . $webLogoUrl . '" alt="College Logo" onerror="this.style.display=\'none\'">';
                        $logoShown = true;
                    }
                @endphp
                
                @if(!$logoShown)
                    <div style="height: 70px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc; color: #999; font-size: 14px;">
                        <span>{{ $settings['college_name'] ?? 'College' }} Logo</span>
                    </div>
                @endif
            @endif
            
            <h1>{{ $settings['college_name'] ?? 'My College' }}</h1>
            @if(!empty($settings['college_address']))
                <p>{{ $settings['college_address'] }}</p>
            @endif
        </div>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <h2 style="text-align: center; font-size: 18px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">Payment Receipt</h2>

        <table class="receipt-info">
            <tr>
                <td><strong>Receipt No:</strong> {{ $payment->receipt_number }}</td>
                <td class="text-right"><strong>Payment Date:</strong> {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</td>
            </tr>
            <tr>
                <td><strong>Invoice No:</strong> #{{ $payment->invoice->invoice_number }}</td>
                <td class="text-right"><strong>Payment Method:</strong> {{ $payment->payment_method }}</td>
            </tr>
        </table>

        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #eee;">
            <h3 style="margin-top: 0; font-size: 14px; margin-bottom: 10px;">Billed To:</h3>
            <p style="margin: 5px 0;"><strong>{{ $payment->invoice->student->name }}</strong></p>
            <p style="margin: 5px 0;">Enrollment No: {{ $payment->invoice->student->enrollment_number }}</p>
        </div>

        <table class="summary-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    // Get currency symbol and convert to PDF-safe format
                    $currencySymbol = $settings['currency_symbol'] ?? '₹';
                    
                    // Convert common currency symbols to HTML entities for PDF compatibility
                    $currencyDisplay = match($currencySymbol) {
                        '₹' => '&#8377;',  // Indian Rupee
                        '$' => '&#36;',    // US Dollar
                        '€' => '&#8364;',  // Euro
                        '£' => '&#163;',   // British Pound
                        '¥' => '&#165;',   // Japanese Yen
                        default => 'Rs. '  // Fallback to "Rs. " for rupees
                    };
                @endphp
                
                <tr>
                    <td>Payment towards Invoice #{{ $payment->invoice->invoice_number }}</td>
                    <td class="text-right">{!! $currencyDisplay !!}{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr style="background-color: #f9f9f9;">
                    <td class="text-right">Invoice Total:</td>
                    <td class="text-right">{!! $currencyDisplay !!}{{ number_format($payment->invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="text-right">Total Paid to Date:</td>
                    <td class="text-right">{!! $currencyDisplay !!}{{ number_format($payment->invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="total-row" style="background-color: #e9f5ff; border-top: 2px solid #b0dfff;">
                    <td class="text-right"><strong>Balance Due:</strong></td>
                    <td class="text-right"><strong>{!! $currencyDisplay !!}{{ number_format($payment->invoice->due_amount, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        @if($payment->notes)
        <div style="margin-top: 20px; font-size: 11px;">
            <strong>Notes:</strong>
            <p style="margin-top: 5px; padding: 10px; background-color: #f9f9f9; border-radius: 4px;">{{ $payment->notes }}</p>
        </div>
        @endif

        <div class="footer">
            <p>{{ $settings['invoice_footer_text'] ?? 'This is a computer-generated receipt and does not require a signature.' }}</p>
        </div>
    </div>
</body>
</html>