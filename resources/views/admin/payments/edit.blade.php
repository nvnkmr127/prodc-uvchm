@extends('layouts.theme')

@section('title', 'Edit Component Payment')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Edit Component Payment #{{ $componentPayment->receipt_number }}</h4>
                    <div>
                        <a href="{{ route('admin.component-payments.show', $componentPayment) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Payment
                        </a>
                        @if(isset($editHistory) && $editHistory->count() > 0)
                        <a href="{{ route('admin.component-payments.edit-history', $componentPayment) }}" class="btn btn-info btn-sm">
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
                            <p class="mb-1"><strong>{{ $componentPayment->student->name }}</strong></p>
                            <p class="mb-1">{{ $componentPayment->student->enrollment_number }}</p>
                            <p class="mb-0">{{ $componentPayment->student->batch->course->name }} - {{ $componentPayment->student->batch->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Payment Information</h6>
                            <p class="mb-1"><strong>Type:</strong> Component Payment</p>
                            <p class="mb-1"><strong>Total Amount:</strong> ₹{{ number_format($componentPayment->amount, 2) }}</p>
                            <p class="mb-0"><strong>Date:</strong> {{ $componentPayment->payment_date->format('d M Y') }}</p>
                        </div>
                    </div>

                    <!-- Current Components Breakdown -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="text-muted">Payment Components</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fee Category</th>
                                            <th class="text-end">Amount Paid</th>
                                            <th class="text-end">Total Fee</th>
                                            <th class="text-end">Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($componentPayment->componentItems as $item)
                                        <tr>
                                            <td>{{ $item->studentFee->feeCategory->name }}</td>
                                            <td class="text-end">₹{{ number_format($item->amount_paid, 2) }}</td>
                                            <td class="text-end">₹{{ number_format($item->studentFee->amount - $item->studentFee->concession_amount, 2) }}</td>
                                            <td class="text-end">₹{{ number_format(($item->studentFee->amount - $item->studentFee->concession_amount) - $item->studentFee->paid_amount, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No components found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Edit Form -->
                    <form action="{{ route('admin.component-payments.update', $componentPayment) }}" method="POST" id="editComponentPaymentForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-8">
                                <!-- Component Payment Details -->
                                <h6 class="text-muted mb-3">Edit Payment Components</h6>
                                <div id="components-container">
                                    @foreach($componentPayment->componentItems as $index => $item)
                                    <div class="component-item card mb-3" data-index="{{ $index }}">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-5">
                                                    <label class="form-label">Fee Category</label>
                                                    <select name="components[{{ $index }}][fee_category_id]" 
                                                            class="form-control component-category" 
                                                            data-index="{{ $index }}" 
                                                            required>
                                                        <option value="">Select Category</option>
                                                        @foreach($componentPayment->student->studentFees->where('status', '!=', 'paid') as $fee)
                                                        <option value="{{ $fee->fee_category_id }}" 
                                                                data-max-amount="{{ ($fee->amount - $fee->concession_amount) - $fee->paid_amount + $item->amount_paid }}"
                                                                data-fee-id="{{ $fee->id }}"
                                                                {{ $fee->fee_category_id == $item->studentFee->fee_category_id ? 'selected' : '' }}>
                                                            {{ $fee->feeCategory->name }} 
                                                            (Max: ₹{{ number_format(($fee->amount - $fee->concession_amount) - $fee->paid_amount + $item->amount_paid, 2) }})
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="components[{{ $index }}][student_fee_id]" value="{{ $item->student_fee_id }}">
                                                    @error("components.{$index}.fee_category_id")
                                                    <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Amount</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" 
                                                               name="components[{{ $index }}][amount]" 
                                                               class="form-control component-amount" 
                                                               value="{{ old("components.{$index}.amount", $item->amount_paid) }}" 
                                                               step="0.01" 
                                                               min="0.01"
                                                               max="{{ ($item->studentFee->amount - $item->studentFee->concession_amount) - $item->studentFee->paid_amount + $item->amount_paid }}"
                                                               required>
                                                    </div>
                                                    @error("components.{$index}.amount")
                                                    <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-3">
                                                    @if(count($componentPayment->componentItems) > 1)
                                                    <button type="button" class="btn btn-danger btn-sm remove-component" style="margin-top: 30px;">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="mb-3">
                                    <button type="button" class="btn btn-success btn-sm" id="add-component">
                                        <i class="fas fa-plus"></i> Add Component
                                    </button>
                                </div>

                                <!-- Payment Total Display -->
                                <div class="row">
                                    <div class="col-md-6 offset-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <strong>Total Payment Amount:</strong>
                                                    <strong id="total-amount">₹{{ number_format($componentPayment->amount, 2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Payment Details -->
                                <h6 class="text-muted mb-3">Payment Details</h6>

                                <div class="form-group mb-3">
                                    <label for="payment_date" class="required">Payment Date</label>
                                    <input type="date" 
                                           name="payment_date" 
                                           id="payment_date" 
                                           class="form-control @error('payment_date') is-invalid @enderror" 
                                           value="{{ old('payment_date', $componentPayment->payment_date->format('Y-m-d')) }}" 
                                           max="{{ date('Y-m-d') }}"
                                           required>
                                    @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="payment_method" class="required">Payment Method</label>
                                    <select name="payment_method" 
                                            id="payment_method" 
                                            class="form-control @error('payment_method') is-invalid @enderror" 
                                            required>
                                        <option value="">Select Payment Method</option>
                                        @foreach($paymentMethods as $method)
                                        <option value="{{ $method }}" {{ old('payment_method', $componentPayment->payment_method) === $method ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $method)) }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3" id="transaction-id-group">
                                    <label for="transaction_id">Transaction ID</label>
                                    <input type="text" 
                                           name="transaction_id" 
                                           id="transaction_id" 
                                           class="form-control @error('transaction_id') is-invalid @enderror" 
                                           value="{{ old('transaction_id', $componentPayment->transaction_id ?? '') }}" 
                                           maxlength="255">
                                    @error('transaction_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">For online payments, bank transfers, etc.</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" 
                                              maxlength="500">{{ old('notes', $componentPayment->notes) }}</textarea>
                                    @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Optional payment notes</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="edit_reason" class="required">Reason for Edit</label>
                                    <textarea name="edit_reason" 
                                              id="edit_reason" 
                                              class="form-control @error('edit_reason') is-invalid @enderror" 
                                              rows="4" 
                                              maxlength="1000" 
                                              required 
                                              placeholder="Please provide a detailed reason for editing this component payment...">{{ old('edit_reason') }}</textarea>
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
                                <i class="fas fa-save"></i> Update Component Payment
                            </button>
                            <a href="{{ route('admin.component-payments.show', $componentPayment) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Edit History (if any) -->
    @if(isset($editHistory) && $editHistory->count() > 0)
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
                                        <small class="text-muted">{{ $log->changes_summary ?? 'Component payment modified' }}</small>
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
                        <a href="{{ route('admin.component-payments.edit-history', $payment) }}" class="btn btn-sm btn-outline-info">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function() {
    let componentIndex = {{ count($componentPayment->componentItems) }};

    // Calculate and update total amount
    function updateTotalAmount() {
        let total = 0;
        $('.component-amount').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total += amount;
        });
        $('#total-amount').text('₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2}));
    }

    // Handle component amount changes
    $(document).on('input', '.component-amount', function() {
        updateTotalAmount();
    });

    // Handle category selection change
    $(document).on('change', '.component-category', function() {
        const selectedOption = $(this).find('option:selected');
        const maxAmount = selectedOption.data('max-amount');
        const feeId = selectedOption.data('fee-id');
        const index = $(this).data('index');
        
        if (maxAmount) {
            $(this).closest('.component-item').find('.component-amount').attr('max', maxAmount);
            $(this).closest('.component-item').find('input[name$="[student_fee_id]"]').val(feeId);
        }
    });

    // Add new component
    $('#add-component').on('click', function() {
        const newComponent = `
            <div class="component-item card mb-3" data-index="${componentIndex}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label">Fee Category</label>
                            <select name="components[${componentIndex}][fee_category_id]" 
                                    class="form-control component-category" 
                                    data-index="${componentIndex}" 
                                    required>
                                <option value="">Select Category</option>
                                                                                @foreach($componentPayment->student->studentFees->where('status', '!=', 'paid') as $fee)
                                <option value="{{ $fee->fee_category_id }}" 
                                        data-max-amount="{{ ($fee->amount - $fee->concession_amount) - $fee->paid_amount }}"
                                        data-fee-id="{{ $fee->id }}">
                                    {{ $fee->feeCategory->name }} 
                                    (Max: ₹{{ number_format(($fee->amount - $fee->concession_amount) - $fee->paid_amount, 2) }})
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="components[${componentIndex}][student_fee_id]" value="">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" 
                                       name="components[${componentIndex}][amount]" 
                                       class="form-control component-amount" 
                                       step="0.01" 
                                       min="0.01"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-danger btn-sm remove-component" style="margin-top: 30px;">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#components-container').append(newComponent);
        componentIndex++;
    });

    // Remove component
    $(document).on('click', '.remove-component', function() {
        $(this).closest('.component-item').remove();
        updateTotalAmount();
    });

    // Form validation
    $('#editComponentPaymentForm').on('submit', function(e) {
        const editReason = $('#edit_reason').val().trim();
        const totalAmount = parseFloat($('#total-amount').text().replace(/[₹,]/g, ''));
        
        if (editReason.length < 10) {
            e.preventDefault();
            alert('Please provide a detailed reason for editing this payment (minimum 10 characters).');
            $('#edit_reason').focus();
            return false;
        }
        
        if (totalAmount <= 0) {
            e.preventDefault();
            alert('Total payment amount must be greater than 0.');
            return false;
        }
        
        // Check for duplicate categories
        const selectedCategories = [];
        $('.component-category').each(function() {
            const value = $(this).val();
            if (value) {
                if (selectedCategories.includes(value)) {
                    e.preventDefault();
                    alert('Cannot select the same fee category multiple times.');
                    return false;
                }
                selectedCategories.push(value);
            }
        });
        
        return confirm('Are you sure you want to update this component payment? This action will be logged for audit purposes.');
    });
    
    // Update transaction ID field based on payment method
    $('#payment_method').on('change', function() {
        const method = $(this).val();
        const transactionField = $('#transaction_id');
        
        if (['online', 'bank_transfer', 'card'].includes(method)) {
            $('#transaction-id-group').show();
            transactionField.attr('required', true);
        } else {
            $('#transaction-id-group').show(); // Keep visible but not required
            transactionField.attr('required', false);
        }
    });
    
    // Trigger change on page load
    $('#payment_method').trigger('change');
    updateTotalAmount();
});
</script>
@endsection