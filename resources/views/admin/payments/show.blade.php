@extends('layouts.admin')

@section('title', 'Payment Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Payment #{{ $payment->receipt_number }}</h4>
                    <div>
                        @can('edit payments')
                        @if($payment->canBeEdited())
                        <a href="{{ route('admin.payments.edit', $payment) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit Payment
                        </a>
                        @else
                        <span class="btn btn-secondary btn-sm disabled" title="Payment cannot be edited due to business rules">
                            <i class="fas fa-lock"></i> Edit Locked
                        </span>
                        @endif
                        @endcan
                        <a href="{{ route('admin.payments.receipt.show', $payment) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-receipt"></i> View Receipt
                        </a>
                        <a href="{{ route('admin.payments.receipt.pdf', $payment) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                        @if($payment->hasBeenEdited())
                        <span class="badge badge-warning ml-2" title="This payment has been edited {{ $payment->edit_count }} time(s)">
                            <i class="fas fa-history"></i> Edited ({{ $payment->edit_count }})
                        </span>
                        @endif
                    </div>
                        <a href="{{ route('admin.payments.receipt.show', $payment) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-receipt"></i> View Receipt
                        </a>
                        <a href="{{ route('admin.payments.receipt.pdf', $payment) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Payment Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Payment Details</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="40%"><strong>Receipt Number:</strong></td>
                                    <td>{{ $payment->receipt_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Amount:</strong></td>
                                    <td class="text-success">
                                        <h5 class="mb-0">₹{{ number_format($payment->amount, 2) }}</h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Date:</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Method:</strong></td>
                                    <td>
                                        <span class="badge badge-primary">{{ $payment->payment_method }}</span>
                                    </td>
                                </tr>
                                @if($payment->transaction_id)
                                <tr>
                                    <td><strong>Transaction ID:</strong></td>
                                    <td><code>{{ $payment->transaction_id }}</code></td>
                                </tr>
                                @endif
                                @if($payment->notes)
                                <tr>
                                    <td><strong>Notes:</strong></td>
                                    <td>{{ $payment->notes }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                                @if($payment->updated_at != $payment->created_at)
                                <tr>
                                    <td><strong>Last Modified:</strong></td>
                                    <td>{{ $payment->updated_at->format('M d, Y H:i') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Student Information</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>
                                        <a href="{{ route('admin.students.show', $payment->invoice->student) }}">
                                            {{ $payment->invoice->student->name }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Enrollment:</strong></td>
                                    <td>{{ $payment->invoice->student->enrollment_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Course:</strong></td>
                                    <td>{{ $payment->invoice->student->batch->course->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Batch:</strong></td>
                                    <td>{{ $payment->invoice->student->batch->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $payment->invoice->student->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Mobile:</strong></td>
                                    <td>{{ $payment->invoice->student->student_mobile }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- Invoice Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="text-muted">Related Invoice Information</h6>
                            <div class="card border-left-info">
                                <div class="card-body py-3">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Invoice Number:</strong><br>
                                            <a href="{{ route('admin.invoices.show', $payment->invoice) }}">
                                                {{ $payment->invoice->invoice_number }}
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Amount:</strong><br>
                                            ₹{{ number_format($payment->invoice->total_amount, 2) }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Paid Amount:</strong><br>
                                            ₹{{ number_format($payment->invoice->paid_amount, 2) }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Due Amount:</strong><br>
                                            <span class="{{ $payment->invoice->due_amount > 0 ? 'text-danger' : 'text-success' }}">
                                                ₹{{ number_format($payment->invoice->due_amount, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <strong>Status:</strong>
                                            <span class="badge badge-{{ $payment->invoice->status === 'paid' ? 'success' : ($payment->invoice->status === 'partially_paid' ? 'warning' : 'danger') }} ml-2">
                                                {{ ucfirst(str_replace('_', ' ', $payment->invoice->status)) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($otherPayments->count() > 0)
                    <hr>
                    <!-- Other Payments for this Invoice -->
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="text-muted">Other Payments for this Invoice</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Receipt #</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Method</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($otherPayments as $otherPayment)
                                        <tr>
                                            <td>{{ $otherPayment->receipt_number }}</td>
                                            <td>₹{{ number_format($otherPayment->amount, 2) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($otherPayment->payment_date)->format('M d, Y') }}</td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $otherPayment->payment_method }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.payments.show', $otherPayment) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Edit History Sidebar -->
        <div class="col-md-4">
            @if($editHistory->count() > 0)
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Edit History</h6>
                    @can('edit payments')
                    <a href="{{ route('admin.payments.edit-history', $payment) }}" class="btn btn-sm btn-outline-info">
                        View All
                    </a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($editHistory as $log)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">
                                            <span class="badge badge-{{ $log->action === 'update' ? 'info' : 'warning' }}">
                                                {{ ucfirst($log->action) }}
                                            </span>
                                        </h6>
                                        <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                    </div>
                                    @if($log->amount_change)
                                    <p class="mb-1">
                                        <small class="text-success">{{ $log->amount_change }}</small>
                                    </p>
                                    @endif
                                    <p class="mb-1">
                                        <small>{{ Str::limit($log->edit_reason, 80) }}</small>
                                    </p>
                                    <small class="text-muted">by {{ $log->user->name ?? 'Unknown' }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @can('edit payments')
                        @if($payment->canBeEdited())
                        <a href="{{ route('admin.payments.edit', $payment) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit text-warning"></i> Edit Payment
                        </a>
                        @else
                        <div class="list-group-item list-group-item-action disabled text-muted">
                            <i class="fas fa-lock text-secondary"></i> Edit Locked (Business Rules)
                        </div>
                        @endif
                        @endcan
                        <a href="{{ route('admin.payments.receipt.show', $payment) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-receipt text-info"></i> View Receipt
                        </a>
                        <a href="{{ route('admin.payments.receipt.pdf', $payment) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-download text-success"></i> Download PDF
                        </a>
                        <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-invoice text-primary"></i> View Invoice
                        </a>
                        <a href="{{ route('admin.students.show', $payment->invoice->student) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-user text-secondary"></i> View Student
                        </a>
                        @can('reverse payments')
                        <button class="list-group-item list-group-item-action text-danger" onclick="confirmReversal()">
                            <i class="fas fa-undo text-danger"></i> Reverse Payment
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmReversal() {
    if (confirm('Are you sure you want to reverse this payment? This action cannot be undone and will affect the invoice balance.')) {
        // Create form and submit for payment reversal
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.payments.reverse", $payment) }}';
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Add method
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection