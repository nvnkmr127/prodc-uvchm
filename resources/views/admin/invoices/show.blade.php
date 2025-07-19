@extends('layouts.theme')
@section('title', 'Invoice #' . $invoice->invoice_number)

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Invoice Details</h1>
    <div>
        <a href="{{ route('admin.financials.student.ledger', $invoice->student) }}" class="btn btn-sm btn-info shadow-sm">
            <i class="fas fa-user-circle fa-sm text-white-50"></i> View Student Ledger
        </a>
        @can('manage finances')
            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> Edit Invoice
            </a>
        @endcan
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Financial Hub
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        {{-- Invoice Details Card --}}
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Invoice #{{ $invoice->invoice_number }}</h6>
                <div>
                    @if($invoice->term_number)
                        <span class="badge badge-light">Term: {{ $invoice->term_number }}</span>
                    @endif
                    @if($editHistory->count() > 0)
                        <span class="badge badge-warning ml-2" title="This invoice has been edited {{ $editHistory->count() }} time(s)">
                            <i class="fas fa-edit"></i> Edited {{ $editHistory->count() }}x
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                {{-- Student and Invoice Info --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Billed To:</h5>
                        <address>
                            <strong>{{ $invoice->student->name }}</strong><br>
                            Enrollment: {{ $invoice->student->enrollment_number }}<br>
                            Batch: {{ $invoice->student->batch->name ?? 'N/A' }}<br>
                            Course: {{ $invoice->student->batch->course->name ?? 'N/A' }}
                        </address>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <p><strong>Issue Date:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d M, Y') }}</p>
                        <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') }}</p>
                        <div class="mt-2">
                             <strong>Status:</strong>
                             @if($invoice->status == 'paid')
                                <span class="badge badge-success" style="font-size: 1rem;">Paid</span>
                            @elseif($invoice->status == 'partial')
                                <span class="badge badge-warning" style="font-size: 1rem;">Partially Paid</span>
                            @else
                                <span class="badge badge-danger" style="font-size: 1rem;">Unpaid</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Invoice Items --}}
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description ?? $item->feeCategory->name ?? 'Fee' }}</td>
                                <td class="text-right">₹{{ number_format($item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-right">Subtotal:</th>
                                <th class="text-right">₹{{ number_format($invoice->items->sum('amount'), 2) }}</th>
                            </tr>
                            @if($invoice->concession_amount > 0)
                            <tr>
                                <th class="text-right">Concession <small>({{ $invoice->concession_notes ?? 'Discount' }})</small>:</th>
                                <th class="text-right text-success">- ₹{{ number_format($invoice->concession_amount, 2) }}</th>
                            </tr>
                            @endif
                            <tr>
                                <th class="text-right">Total Amount:</th>
                                <th class="text-right">₹{{ number_format($invoice->total_amount, 2) }}</th>
                            </tr>
                            <tr>
                                <th class="text-right">Total Paid:</th>
                                <th class="text-right text-success">- ₹{{ number_format($invoice->paid_amount, 2) }}</th>
                            </tr>
                            <tr class="table-primary font-weight-bold" style="font-size: 1.2rem;">
                                <th class="text-right">Balance Due:</th>
                                <th class="text-right">₹{{ number_format($invoice->due_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment History Card --}}
        <div class="card shadow mb-4">
             <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Payment History</h6></div>
             <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Receipt #</th></tr></thead>
                        <tbody>
                            @forelse($invoice->payments as $payment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</td>
                                <td>₹{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td><a href="{{ route('admin.payments.receipt.show', $payment) }}">{{ $payment->receipt_number }}</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center">No payments recorded for this invoice yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
             </div>
        </div>

        {{-- ✅ NEW: Edit History Card --}}
        @if($editHistory->count() > 0)
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-history"></i> Edit History ({{ $editHistory->count() }} changes)
                </h6>
                @can('manage finances')
                    <a href="{{ route('admin.invoices.edit-history', $invoice) }}" class="btn btn-sm btn-outline-primary">
                        View Full History
                    </a>
                @endcan
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                @foreach($editHistory->take(5) as $log)
                <div class="border-left border-{{ $log->action_badge_class }} pl-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge badge-{{ $log->action_badge_class }} mb-2">
                                <i class="{{ $log->action_icon }}"></i> {{ ucfirst($log->action) }}
                            </span>
                            <div class="small text-muted mb-1">
                                <i class="fas fa-user"></i> {{ $log->user_name }} • 
                                <i class="fas fa-clock"></i> {{ $log->created_at->format('d M, Y H:i A') }}
                            </div>
                            @if($log->notes)
                                <div class="small mb-2"><strong>Reason:</strong> {{ $log->notes }}</div>
                            @endif
                            <div class="small">
                                {!! $log->formatted_changes !!}
                            </div>
                            @if($log->total_amount_change)
                                <div class="mt-2">
                                    <span class="badge badge-info">
                                        Amount: ₹{{ number_format($log->total_amount_change['from'], 2) }} → 
                                        ₹{{ number_format($log->total_amount_change['to'], 2) }}
                                        @if($log->total_amount_change['difference'] != 0)
                                            <span class="text-{{ $log->total_amount_change['difference'] > 0 ? 'success' : 'danger' }}">
                                                ({{ $log->total_amount_change['difference'] > 0 ? '+' : '' }}₹{{ number_format($log->total_amount_change['difference'], 2) }})
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                        @if($log->canRevert() && $log->action === 'edit')
                            <form action="{{ route('admin.invoices.revert', [$invoice, $log]) }}" method="POST" 
                                  onsubmit="return confirm('Are you sure you want to revert to this state? This will undo all changes made after this edit.')" class="ml-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Revert to this state">
                                    <i class="fas fa-undo"></i> Revert
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @endforeach
                
                @if($editHistory->count() > 5)
                <div class="text-center">
                    <a href="{{ route('admin.invoices.edit-history', $invoice) }}" class="btn btn-sm btn-outline-primary">
                        View All {{ $editHistory->count() }} Changes
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- Actions Card --}}
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Actions</h6></div>
            <div class="card-body text-center">
                 @if($invoice->status != 'paid')
                    <button class="btn btn-success m-2" data-toggle="modal" data-target="#recordPaymentModal">
                        <i class="fas fa-plus"></i> Record Payment
                    </button>
                    <button class="btn btn-warning m-2" data-toggle="modal" data-target="#applyConcessionModal">
                        <i class="fas fa-percent"></i> Apply Concession
                    </button>
                    @can('manage finances')
                        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-primary m-2">
                            <i class="fas fa-edit"></i> Edit Invoice
                        </a>
                    @endcan
                @else
                    <p class="text-success mb-3"><i class="fas fa-check-circle"></i> This invoice is fully paid.</p>
                    @can('manage finances')
                        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-primary m-2">
                            <i class="fas fa-edit"></i> Edit Invoice
                        </a>
                    @endcan
                @endif
                
                {{-- Download/Print Options --}}
                <div class="mt-3">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn btn-outline-secondary btn-sm ml-1" onclick="downloadPDF()">
                        <i class="fas fa-download"></i> PDF
                    </button>
                </div>
            </div>
        </div>

        {{-- ✅ ENHANCED: Activity Log Card with Edit Logs --}}
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6></div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                {{-- Show Edit Logs --}}
                @foreach($editHistory->take(3) as $log)
                    <div class="mb-3 border-left border-{{ $log->action_badge_class }} pl-3">
                        <div class="small text-gray-600">
                             <i class="{{ $log->action_icon }} mr-1"></i> {{ $log->created_at->format('d M, Y H:i A') }}
                             by {{ $log->user_name }}
                        </div>
                        <div class="small">
                            <strong>{{ ucfirst($log->action) }}:</strong> 
                            @if($log->has_significant_changes)
                                @if(isset($log->changes['total_amount']))
                                    Amount changed to ₹{{ number_format($log->changes['total_amount']['to'], 2) }}
                                @elseif(isset($log->changes['items']))
                                    Invoice items modified
                                @else
                                    Invoice updated
                                @endif
                            @else
                                Minor updates
                            @endif
                        </div>
                        @if($log->notes)
                            <div class="small text-muted">{{ Str::limit($log->notes, 50) }}</div>
                        @endif
                    </div>
                @endforeach

                {{-- Show General Activities --}}
                @if(isset($activities))
                    @forelse($activities->take(5) as $activity)
                        <div class="mb-3">
                            <div class="small text-gray-600">
                                 <i class="fas fa-history mr-1"></i> {{ $activity->created_at->format('d M, Y H:i A') }}
                                 by {{ $activity->causer->name ?? 'System' }}
                            </div>
                            <div class="small">{{ $activity->description }}</div>
                        </div>
                    @empty
                        @if($editHistory->count() === 0)
                            <p class="small text-muted">No activity recorded for this invoice.</p>
                        @endif
                    @endforelse
                @endif
            </div>
        </div>

        {{-- ✅ NEW: Invoice Summary Stats --}}
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Invoice Summary</h6></div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Amount</div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800">₹{{ number_format($invoice->total_amount, 2) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Paid Amount</div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800">₹{{ number_format($invoice->paid_amount, 2) }}</div>
                    </div>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Due Amount</div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800">₹{{ number_format($invoice->due_amount, 2) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Payment %</div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                            {{ $invoice->total_amount > 0 ? round(($invoice->paid_amount / $invoice->total_amount) * 100, 1) : 0 }}%
                        </div>
                    </div>
                </div>
                @if($invoice->concession_amount > 0)
                <div class="row text-center mt-3">
                    <div class="col-12">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Concession Applied</div>
                        <div class="h6 mb-0 font-weight-bold text-success">₹{{ number_format($invoice->concession_amount, 2) }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- MODALS --}}

{{-- Record Payment Modal --}}
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.invoices.payments.store', $invoice) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Amount Paid*</label>
                        <input type="number" step="0.01" max="{{ $invoice->due_amount }}" name="amount" class="form-control" value="{{ old('amount', $invoice->due_amount) }}" required>
                        <small class="text-muted">Maximum: ₹{{ number_format($invoice->due_amount, 2) }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date*</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Online">Online</option>
                            <option value="Cheque">Cheque</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Payment reference, transaction ID, etc."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Save Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Apply Concession Modal --}}
<div class="modal fade" id="applyConcessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply Concession</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.invoices.concession.store', $invoice) }}" method="POST">
                    @csrf
                     <div class="mb-3">
                        <label for="concession_type" class="form-label">Concession Type</label>
                        <select name="concession_type" class="form-control" onchange="updateConcessionLabel()">
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="concession_value" class="form-label">Value</label>
                        <input type="number" step="0.01" name="concession_value" class="form-control" required>
                        <small class="text-muted" id="concession_help">Enter the fixed amount to deduct</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason / Notes</label>
                        <textarea name="concession_notes" class="form-control" rows="2" placeholder="e.g., Scholarship, Staff discount, Financial hardship"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning">Apply Concession</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateConcessionLabel() {
    const type = document.querySelector('select[name="concession_type"]').value;
    const help = document.getElementById('concession_help');
    
    if (type === 'percentage') {
        help.textContent = 'Enter percentage (0-100)';
    } else {
        help.textContent = 'Enter the fixed amount to deduct';
    }
}

function downloadPDF() {
    // You can implement PDF generation here
    alert('PDF download feature will be implemented');
}

// Auto-refresh payment status if needed
@if($invoice->status !== 'paid')
setTimeout(function() {
    // You can add auto-refresh logic here if needed
}, 30000); // Refresh every 30 seconds
@endif
</script>
@endpush

@push('styles')
<style>
.border-primary { border-color: #007bff !important; }
.border-warning { border-color: #ffc107 !important; }
.border-success { border-color: #28a745 !important; }
.border-danger { border-color: #dc3545 !important; }

@media print {
    .btn, .modal, .card:last-child { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
@endpush
@endsection