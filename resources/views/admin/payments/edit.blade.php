@extends('layouts.admin')

@section('title', 'Edit Payment')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Edit Payment #{{ $payment->receipt_number }}</h4>
                    <div>
                        <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Payment
                        </a>
                        @if($editHistory->count() > 0)
                        <a href="{{ route('admin.payments.edit-history', $payment) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-history"></i> Edit History ({{ $editHistory->count() }})
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Payment Info Header -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Student Information</h6>
                            <p class="mb-1"><strong>{{ $payment->invoice->student->name }}</strong></p>
                            <p class="mb-1">{{ $payment->invoice->student->enrollment_number }}</p>
                            <p class="mb-0">{{ $payment->invoice->student->batch->course->name }} - {{ $payment->invoice->student->batch->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Invoice Information</h6>
                            <p class="mb-1"><strong>Invoice:</strong> {{ $payment->invoice->invoice_number }}</p>
                            <p class="mb-1"><strong>Total Amount:</strong> ₹{{ number_format($payment->invoice->total_amount, 2) }}</p>
                            <p class="mb-0"><strong>Status:</strong> 
                                <span class="badge badge-{{ $payment->invoice->status === 'paid' ? 'success' : ($payment->invoice->status === 'partially_paid' ? 'warning' : 'danger') }}">
                                    {{ ucfirst(str_replace('_', ' ', $payment->invoice->status)) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <!-- Edit Form -->
                    <form action="{{ route('admin.payments.update', $payment) }}" method="POST" id="editPaymentForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount" class="required">Amount</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">₹</span>
                                        </div>
                                        <input type="number" 
                                               name="amount" 
                                               id="amount" 
                                               class="form-control @error('amount') is-invalid @enderror" 
                                               value="{{ old('amount', $payment->amount) }}" 
                                               step="0.01" 
                                               min="0.01"
                                               max="{{ $payment->invoice->total_amount }}"
                                               required>
                                    </div>
                                    @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Current: ₹{{ number_format($payment->amount, 2) }} | 
                                        Max allowed: ₹{{ number_format($payment->invoice->total_amount - $payment->invoice->payments()->where('id', '!=', $payment->id)->sum('amount'), 2) }}
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="payment_date" class="required">Payment Date</label>
                                    <input type="date" 
                                           name="payment_date" 
                                           id="payment_date" 
                                           class="form-control @error('payment_date') is-invalid @enderror" 
                                           value="{{ old('payment_date', $payment->payment_date) }}" 
                                           max="{{ date('Y-m-d') }}"
                                           required>
                                    @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="payment_method" class="required">Payment Method</label>
                                    <select name="payment_method" 
                                            id="payment_method" 
                                            class="form-control @error('payment_method') is-invalid @enderror" 
                                            required>
                                        <option value="">Select Payment Method</option>
                                        @foreach(['Cash', 'Card', 'Bank Transfer', 'Cheque', 'Online', 'UPI'] as $method)
                                        <option value="{{ $method }}" {{ old('payment_method', $payment->payment_method) === $method ? 'selected' : '' }}>
                                            {{ $method }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_id">Transaction ID</label>
                                    <input type="text" 
                                           name="transaction_id" 
                                           id="transaction_id" 
                                           class="form-control @error('transaction_id') is-invalid @enderror" 
                                           value="{{ old('transaction_id', $payment->transaction_id ?? '') }}" 
                                           maxlength="255">
                                    @error('transaction_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">For online payments, bank transfers, etc.</small>
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" 
                                              maxlength="500">{{ old('notes', $payment->notes) }}</textarea>
                                    @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Optional payment notes</small>
                                </div>

                                <div class="form-group">
                                    <label for="edit_reason" class="required">Reason for Edit</label>
                                    <textarea name="edit_reason" 
                                              id="edit_reason" 
                                              class="form-control @error('edit_reason') is-invalid @enderror" 
                                              rows="3" 
                                              maxlength="1000" 
                                              required 
                                              placeholder="Please provide a detailed reason for editing this payment...">{{ old('edit_reason') }}</textarea>
                                    @error('edit_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Required: Explain why this payment is being modified</small>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Payment
                            </button>
                            <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Edit History (if any) -->
    @if($editHistory->count() > 0)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Edit History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Changes</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($editHistory->take(5) as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $log->user->name ?? 'Unknown' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $log->action === 'update' ? 'info' : 'warning' }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->amount_change)
                                        <small class="text-muted">{{ $log->amount_change }}</small>
                                        @else
                                        <small class="text-muted">{{ $log->changes_summary }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ Str::limit($log->edit_reason, 50) }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($editHistory->count() > 5)
                    <div class="text-center">
                        <a href="{{ route('admin.payments.edit-history', $payment) }}" class="btn btn-sm btn-outline-info">
                            View All {{ $editHistory->count() }} History Records
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('#editPaymentForm').on('submit', function(e) {
        const amount = parseFloat($('#amount').val());
        const maxAmount = parseFloat($('#amount').attr('max'));
        const editReason = $('#edit_reason').val().trim();
        
        if (amount > maxAmount) {
            e.preventDefault();
            alert('Payment amount cannot exceed the maximum allowed amount.');
            return false;
        }
        
        if (editReason.length < 10) {
            e.preventDefault();
            alert('Please provide a detailed reason for editing this payment (minimum 10 characters).');
            $('#edit_reason').focus();
            return false;
        }
        
        return confirm('Are you sure you want to update this payment? This action will be logged for audit purposes.');
    });
    
    // Update transaction ID field based on payment method
    $('#payment_method').on('change', function() {
        const method = $(this).val();
        const transactionField = $('#transaction_id');
        
        if (['Online', 'Bank Transfer', 'Card', 'UPI'].includes(method)) {
            transactionField.closest('.form-group').show();
            transactionField.attr('required', true);
        } else {
            transactionField.closest('.form-group').show(); // Keep visible but not required
            transactionField.attr('required', false);
        }
    });
    
    // Trigger change on page load
    $('#payment_method').trigger('change');
});
</script>
@endpush
@endsection