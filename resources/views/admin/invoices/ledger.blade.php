@extends('layouts.theme')
@section('title', 'Financial Ledger for ' . $student->name)

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Financial Ledger: <strong>{{ $student->name }}</strong></h1>
    <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Financial Hub</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('info'))
    <div class="alert alert-info">{{ session('info') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
    </div>
@endif

{{-- Stats Cards --}}
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-info shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Billed</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ setting('currency_symbol','₹') }} {{ number_format($totalBilled, 2) }}</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Paid</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalPaid, 2) }}</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Concession</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalConcession, 2) }}</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-danger shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Balance Due</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($balanceDue, 2) }}</div></div></div></div>
</div>

{{-- Action Bar --}}
<div class="card shadow mb-4">
    <div class="card-body d-flex justify-content-center flex-wrap">
        <button class="btn btn-success m-2" data-toggle="modal" data-target="#recordPaymentModal"><i class="fas fa-plus"></i> Record Payment</button>
        <button class="btn btn-warning m-2" data-toggle="modal" data-target="#applyConcessionModal"><i class="fas fa-percent"></i> Apply Concession</button>
        <a href="{{ route('admin.financials.student.statement.download', ['student' => $student, 'start_date' => $student->admission_date, 'end_date' => date('Y-m-d')]) }}" target="_blank" class="btn btn-danger m-2"><i class="fas fa-file-pdf"></i> Download Full Statement</a>
    </div>
</div>

{{-- Add this to your existing invoice ledger view (resources/views/admin/invoices/show-student-ledger.blade.php) --}}

{{-- Add this button group to the existing page header --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Student Financial Ledger - {{ $student->name }}</h1>
    <div class="btn-group">
        {{-- Your existing buttons --}}
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Invoices
        </a>
        
        {{-- NEW: Component Payment Dashboard Button --}}
        <a href="{{ route('admin.component-payments.student-dashboard', $student) }}" class="btn btn-info">
            <i class="fas fa-th-large"></i> Component Dashboard
        </a>
        
        {{-- Your existing add payment button --}}
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPaymentModal">
            <i class="fas fa-plus"></i> Add Payment
        </button>
    </div>
</div>

{{-- Add this component summary card after student info --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-left-info shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">Component-wise Payment Summary</h6>
            </div>
            <div class="card-body">
                <div class="row" id="component-summary">
                    {{-- This will be populated via AJAX --}}
                    <div class="col-12 text-center">
                        <div class="spinner-border text-info" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading component summary...</p>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <a href="{{ route('admin.component-payments.student-dashboard', $student) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-external-link-alt"></i> View Detailed Component Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- Transaction History Card --}}
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Transaction History</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Date</th><th>Particulars</th><th class="text-right">Debit (Fee)</th><th class="text-right">Credit (Paid)</th></tr></thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('d M, Y') }}</td>
                            @if($transaction instanceof App\Models\Invoice)
                                <td>
                                    Invoice Generated
                                    @if($transaction->term_number)
                                        <span class="badge badge-info ml-1">Term {{ $transaction->term_number }}</span>
                                    @endif
                                    (<a href="{{ route('admin.invoices.show', $transaction) }}">#{{ $transaction->invoice_number }}</a>)
                                </td>
                                <td class="text-right text-danger">{{ number_format($transaction->total_amount, 2) }}</td>
                                <td class="text-right"></td>
                            @elseif($transaction instanceof App\Models\Payment)
                                <td>Payment Received (<a href="{{ route('admin.payments.receipt.show', $transaction) }}">Receipt #{{$transaction->receipt_number}}</a>) via {{ $transaction->payment_method }}</td>
                                <td class="text-right"></td>
                                <td class="text-right text-success">{{ number_format($transaction->amount, 2) }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No financial activity found for this student.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>



{{-- ================= MODALS ================= --}}

{{-- Record Payment Modal --}}
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Record Payment for {{ $student->name }}</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p>Select the invoice you are recording a payment against.</p>
                <form id="paymentForm" action="" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label>Invoice to Pay*</label>
                        <select name="invoice_id" id="paymentInvoiceSelect" class="form-control" required>
                            <option value="">-- Select Unpaid Invoice --</option>
                            @foreach($student->invoices->whereIn('status', ['unpaid', 'partially_paid']) as $invoice)
                                <option value="{{ $invoice->id }}" data-due="{{ $invoice->due_amount }}">#{{$invoice->invoice_number}} (Due: {{$invoice->due_amount}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Amount Paid*</label><input type="number" step="0.01" name="amount" id="paymentAmountInput" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Payment Date*</label><input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="mb-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-control"><option value="Cash">Cash</option><option value="Bank Transfer">Bank Transfer</option></select></div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    <button type="submit" class="btn btn-success">Record Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Apply Concession Modal --}}
<div class="modal fade" id="applyConcessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Apply Concession for {{ $student->name }}</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                 <p>Select the invoice to apply the concession to.</p>
                <form id="concessionForm" action="" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label>Invoice*</label>
                        <select name="invoice_id" id="concessionInvoiceSelect" class="form-control" required>
                            <option value="">-- Select Unpaid Invoice --</option>
                            @foreach($student->invoices->whereIn('status', ['unpaid', 'partially_paid']) as $invoice)
                                <option value="{{ $invoice->id }}" data-total="{{ $invoice->total_amount }}">#{{$invoice->invoice_number}} (Total: {{$invoice->total_amount}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="concession_type" class="form-label">Concession Type</label>
                        <select name="concession_type" id="concession_type" class="form-control">
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="concession_value" class="form-label" id="concession_value_label">Concession Value</label>
                        <input type="number" step="0.01" name="concession_value" id="concession_value" class="form-control" required>
                    </div>
                    <div class="mb-3"><label class="form-label">Reason for Concession</label><textarea name="concession_notes" class="form-control" rows="2" placeholder="e.g., Staff discount"></textarea></div>
                    <button type="submit" class="btn btn-warning">Apply Concession</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Add this JavaScript to the bottom of your existing view --}}
@push('scripts')
<script>
$(document).ready(function() {
    // Load component summary
    loadComponentSummary();
});

function loadComponentSummary() {
    $.get('{{ url("/api/admin/students/{$student->id}/fee-components") }}')
        .done(function(data) {
            let html = '';
            
            if (Object.keys(data).length === 0) {
                html = '<div class="col-12 text-center text-muted">No fee components found.</div>';
            } else {
                Object.values(data).forEach(function(component) {
                    const percentage = component.total_amount > 0 ? 
                        Math.round((component.paid_amount / component.total_amount) * 100) : 0;
                    
                    const statusColor = percentage === 100 ? 'success' : 
                                       percentage > 0 ? 'warning' : 'danger';
                    
                    html += `
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-${statusColor} h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="text-${statusColor} mb-0">${component.category_name}</h6>
                                        <span class="badge badge-${statusColor}">${percentage}%</span>
                                    </div>
                                    <div class="small">
                                        <div class="d-flex justify-content-between">
                                            <span>Total:</span>
                                            <span>₹${parseFloat(component.total_amount).toLocaleString()}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Paid:</span>
                                            <span class="text-success">₹${parseFloat(component.paid_amount).toLocaleString()}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Pending:</span>
                                            <span class="text-${component.pending_amount > 0 ? 'danger' : 'success'}">₹${parseFloat(component.pending_amount).toLocaleString()}</span>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 4px;">
                                        <div class="progress-bar bg-${statusColor}" 
                                             style="width: ${percentage}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            $('#component-summary').html(html);
        })
        .fail(function() {
            $('#component-summary').html(
                '<div class="col-12 text-center text-danger">Failed to load component summary.</div>'
            );
        });
}
</script>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Payment Modal Logic ---
    const paymentInvoiceSelect = document.getElementById('paymentInvoiceSelect');
    const paymentForm = document.getElementById('paymentForm');
    const paymentAmountInput = document.getElementById('paymentAmountInput');
    const paymentActionUrl = "{{ route('admin.invoices.payments.store', ['invoice' => ':id']) }}";

    if (paymentInvoiceSelect) {
        paymentInvoiceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const invoiceId = this.value;
            const dueAmount = selectedOption.getAttribute('data-due');
            paymentForm.action = invoiceId ? paymentActionUrl.replace(':id', invoiceId) : "";
            if (dueAmount) {
                paymentAmountInput.max = dueAmount;
                paymentAmountInput.placeholder = `Max: ${dueAmount}`;
            } else {
                paymentAmountInput.max = null;
                paymentAmountInput.placeholder = '';
            }
        });
    }

    // --- Concession Modal Logic ---
    const concessionInvoiceSelect = document.getElementById('concessionInvoiceSelect');
    const concessionForm = document.getElementById('concessionForm');
    const concessionType = document.getElementById('concession_type');
    const concessionValueInput = document.getElementById('concession_value');
    const concessionValueLabel = document.getElementById('concession_value_label');
    const concessionActionUrl = "{{ route('admin.invoices.concession.store', ['invoice' => ':id']) }}";

    function updateConcessionInput() {
        const selectedInvoiceOption = concessionInvoiceSelect.options[concessionInvoiceSelect.selectedIndex];
        const invoiceTotal = selectedInvoiceOption.getAttribute('data-total');

        if (concessionType.value === 'percentage') {
            concessionValueLabel.textContent = 'Concession Percentage (%)';
            concessionValueInput.placeholder = 'e.g., 10 for 10%';
            concessionValueInput.max = 100;
        } else { // 'fixed'
            concessionValueLabel.textContent = 'Concession Amount';
            if (invoiceTotal) {
                concessionValueInput.placeholder = `Max: ${invoiceTotal}`;
                concessionValueInput.max = invoiceTotal;
            } else {
                concessionValueInput.placeholder = 'Select an invoice first';
                concessionValueInput.max = null;
            }
        }
    }

    if (concessionInvoiceSelect) {
        concessionInvoiceSelect.addEventListener('change', function() {
            const invoiceId = this.value;
            concessionForm.action = invoiceId ? concessionActionUrl.replace(':id', invoiceId) : "";
            updateConcessionInput();
        });
    }

    if (concessionType) {
        concessionType.addEventListener('change', updateConcessionInput);
    }
});
</script>
@endpush
