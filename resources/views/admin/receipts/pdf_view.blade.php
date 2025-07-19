<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #{{ $payment->receipt_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        /* CSS Variables for a consistent design system */
        :root {
            --brand-color: #4338ca; /* A professional deep blue/indigo */
            --text-dark: #111827;
            --text-medium: #374151;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --border-color: #e5e7eb;
            --success-color: #059669;
            --success-bg: #f0fdf4;
            --warning-color: #b45309;
            --warning-bg: #fffbeb;
        }

        @page {
            size: A4;
            margin: 0; /* We'll control margin via the container */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 11pt; /* Use points for better print consistency */
            color: var(--text-medium);
            background-color: var(--bg-white); /* White background for printing */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .receipt-container {
            max-width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            margin: 0 auto;
            background-color: var(--bg-white);
            display: flex;
            flex-direction: column;
            padding: 15mm;
        }

        /* --- Header --- */
        .receipt-header {
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--brand-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
        }

        .header-brand .logo {
            height: 60px; /* Adjusted for a professional look */
            max-width: 250px;
            margin-bottom: 0.75rem;
        }

        .header-brand .college-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .header-brand .college-address {
            font-size: 0.9rem;
            line-height: 1.5;
            color: var(--text-light);
            max-width: 300px; /* Prevents address from being too wide */
        }

        .header-details .receipt-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--text-dark);
            text-align: right;
            line-height: 1.1;
            margin-bottom: 0.5rem;
        }

        .header-details .receipt-number,
        .header-details .receipt-date {
            text-align: right;
            font-size: 1rem;
            color: var(--text-medium);
        }
        .header-details .receipt-number span {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* --- Body --- */
        .receipt-body {
            padding: 2rem 0;
            flex-grow: 1; /* Allows the body to fill available space */
        }

        /* --- Billed To & Payment Meta --- */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-group h3 {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-light);
            margin-bottom: 0.75rem;
        }

        .info-group .student-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .info-group p {
            font-size: 1rem;
            line-height: 1.6;
        }
        .info-group p strong {
            color: var(--text-dark);
        }

        .payment-status {
            margin-top: 1rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .status-badge.paid {
            border-color: var(--success-color);
            color: var(--success-color);
            background-color: var(--success-bg);
        }
        .status-badge.partial {
            border-color: var(--warning-color);
            color: var(--warning-color);
            background-color: var(--warning-bg);
        }


        /* --- Payment Summary Table --- */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }

        .summary-table thead th {
            padding: 0.75rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-dark);
            border-bottom: 2px solid var(--text-dark);
        }

        .summary-table th.text-right {
            text-align: right;
        }

        .summary-table tbody td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }
        
        .summary-table .description {
            color: var(--text-dark);
            font-weight: 500;
        }

        .summary-table .amount {
            text-align: right;
            font-weight: 500;
            font-family: 'Courier New', monospace;
        }
        
        /* Summary calculation rows */
        .summary-table tfoot td {
            padding: 0.5rem 0.75rem;
            border-bottom: none;
            text-align: right;
            font-weight: 600;
        }
        .summary-table tfoot .label {
             text-align: right;
             color: var(--text-medium);
        }
        .summary-table tfoot .value {
             font-family: 'Courier New', monospace;
             color: var(--text-dark);
        }
        
        .summary-table .grand-total td {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--brand-color);
            padding-top: 1rem;
            border-top: 2px solid var(--text-dark);
        }

        /* --- Notes & Footer --- */
        .notes-section {
            margin-top: 2rem;
            padding: 1rem;
            background: var(--bg-light);
            border-left: 3px solid var(--brand-color);
        }
        .notes-section h4 {
            font-size: 0.9rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .notes-section p {
            font-size: 0.85rem;
            color: var(--text-medium);
            line-height: 1.5;
        }
        
        .receipt-footer {
            margin-top: auto; /* Pushes footer to the bottom */
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-light);
        }

        /* --- Print-Specific Styles --- */
        @media print {
            body {
                background: none;
            }
            .receipt-container {
                box-shadow: none;
                margin: 0;
                padding: 15mm; /* Ensure padding is consistent */
            }
            .no-print {
                display: none !important;
            }
            .status-badge.paid {
                background-color: var(--success-bg) !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .status-badge.partial {
                background-color: var(--warning-bg) !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        
        <header class="receipt-header">
            <div class="header-brand">
                @php
                    // Simplified logo handling for PDF
                    // Recommendation: Use a helper function to get a base64 encoded image string
                    // This makes embedding in PDFs much more reliable.
                    $logoPath = $settings['college_logo'] ?? '';
                    $logoShown = false;
                    if (!empty($logoPath)) {
                        $fullPath = public_path('storage/' . $logoPath);
                        if (file_exists($fullPath) && is_readable($fullPath)) {
                            $type = pathinfo($fullPath, PATHINFO_EXTENSION);
                            $data = file_get_contents($fullPath);
                            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            echo '<img src="' . $base64 . '" alt="College Logo" class="logo">';
                            $logoShown = true;
                        }
                    }
                @endphp

                @if(!$logoShown)
                    <h1 class="college-name">{{ $settings['college_name'] ?? 'My College' }}</h1>
                @endif
                
                @if(!empty($settings['college_address']))
                    <p class="college-address">{{ $settings['college_address'] }}</p>
                @endif
            </div>

            <div class="header-details">
                <h2 class="receipt-title">RECEIPT</h2>
                <p class="receipt-number">Receipt No: <span>{{ $payment->receipt_number }}</span></p>
                <p class="receipt-date">Date: <span>{{ \Carbon\Carbon::parse($payment->payment_date)->format('F j, Y') }}</span></p>
            </div>
        </header>

        <main class="receipt-body">
            <div class="info-grid">
                <div class="info-group">
                    <h3>Billed To</h3>
                    <p class="student-name">{{ $payment->invoice->student->name }}</p>
                    <p>Enrollment No: <strong>{{ $payment->invoice->student->enrollment_number }}</strong></p>
                    @if($payment->invoice->student->batch)
                        <p>Batch: <strong>{{ $payment->invoice->student->batch->name }}</strong></p>
                    @endif
                </div>
                <div class="info-group">
                    <h3>Payment Details</h3>
                    <p>Invoice Ref: <strong>#{{ $payment->invoice->invoice_number }}</strong></p>
                    <p>Payment Method: <strong>{{ $payment->payment_method }}</strong></p>
                    <div class="payment-status">
                        @php
                            $dueAmount = $payment->invoice->due_amount;
                            $status = $dueAmount <= 0 ? 'paid' : 'partial';
                        @endphp
                        <span class="status-badge {{ $status }}">
                            {{ $dueAmount <= 0 ? 'Fully Paid' : 'Partially Paid' }}
                        </span>
                    </div>
                </div>
            </div>

            @php
                $currencySymbol = $settings['currency_symbol'] ?? '₹';
                // Using a simple mapping for currency display
                $currencyMap = ['₹' => '&#8377;', '$' => '&#36;', '€' => '&#8364;', '£' => '&#163;', '¥' => '&#165;'];
                $currencyDisplay = $currencyMap[$currencySymbol] ?? $currencySymbol;
            @endphp
            
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="description">Payment towards Invoice #{{ $payment->invoice->invoice_number }}</td>
                        <td class="amount">{!! $currencyDisplay !!}{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    </tbody>
                <tfoot>
                    <tr>
                        <td class="label">Invoice Total:</td>
                        <td class="value">{!! $currencyDisplay !!}{{ number_format($payment->invoice->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Total Paid to Date:</td>
                        <td class="value">{!! $currencyDisplay !!}{{ number_format($payment->invoice->paid_amount, 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td class="label">Outstanding Balance:</td>
                        <td class="value">{!! $currencyDisplay !!}{{ number_format($payment->invoice->due_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            @if($payment->notes)
            <div class="notes-section">
                <h4>Notes</h4>
                <p>{{ $payment->notes }}</p>
            </div>
            @endif

        </main>
        
        <footer class="receipt-footer">
            <p>{{ $settings['invoice_footer_text'] ?? 'This is a computer-generated receipt and does not require a signature. Thank you for your payment!' }}</p>
        </footer>

    </div>


</body>
</html>