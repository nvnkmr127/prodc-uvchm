@extends('layouts.theme')

@section('title', 'Edit Payment - ' . $payment->receipt_number)

@push('styles')
<style>
    .edit-form-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .payment-info-card {
        background: linear-gradient(135deg, var(--primary-color, #4e73df) 0%, var(--secondary-color, #224abe) 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .form-section {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border: 1px solid #e3e6f0;
    }
    
    .component-item {
        background: #f8f9fc;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .component-item:hover {
        border-color: #d1ecf1;
        background: #f1f8ff;
    }
    
    .component-item.selected {
        border-color: var(--primary-color, #4e73df);
        background: linear-gradient(135deg, #f1f8ff 0%, #e8f4fd 100%);
        box-shadow: 0 0.25rem 0.75rem rgba(78, 115, 223, 0.1);
    }
    
    .amount-summary {
        background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 1.5rem;
        border: 2px solid #dee2e6;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .danger-zone {
        background: linear-gradient(135deg, #ffe6e6 0%, #fff0f0 100%);
        border: 2px solid #e74a3b;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(231, 74, 59, 0.15);
    }
    
    .edit-history-item {
        border-left: 4px solid var(--primary-color, #4e73df);
        background: #f8f9fc;
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 0 10px 10px 0;
    }

    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: var(--primary-color, #4e73df);
        border-color: var(--primary-color, #4e73df);
    }

    .btn-theme-primary {
        background: linear-gradient(135deg, var(--primary-color, #4e73df) 0%, var(--secondary-color, #224abe) 100%);
        border: none;
        color: white;
    }

    .btn-theme-primary:hover {
        background: linear-gradient(135deg, var(--secondary-color, #224abe) 0%, var(--primary-color, #4e73df) 100%);
        color: white;
    }

    .form-control:focus {
        border-color: var(--primary-color, #4e73df);
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    .text-primary-theme {
        color: var(--primary-color, #4e73df) !important;
    }

    .validation-message {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .validation-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .validation-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .validation-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    @media (max-width: 768px) {
        .edit-form-container {
            padding: 0 1rem;
        }
        
        .payment-info-card,
        .form-section {
            padding: 1rem;
        }
        
        .component-item {
            padding: 1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="edit-form-container">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
            <div class="mb-3 mb-md-0">
                <h2 class="h3 mb-1 text-gray-800">Edit Payment</h2>
                <p class="text-muted mb-0">{{ $payment->receipt_number }} • {{ $payment->student->name }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.students.show', $payment->student) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Student
                </a>
                @if(Route::has('admin.payments.receipt'))
                <a href="{{ route('admin.payments.receipt', [$payment->student, $payment]) }}" class="btn btn-info">
                    <i class="fas fa-receipt mr-2"></i> View Receipt
                </a>
                @endif
            </div>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        {{-- Current Payment Info --}}
        <div class="payment-info-card">
            <div class="row">
                <div class="col-lg-8">
                    <h4 class="mb-3">
                        <i class="fas fa-info-circle mr-2"></i> Current Payment Details
                    </h4>
                    <div class="row">
                        <div class="col-sm-6">
                            <p class="mb-2"><strong>Student:</strong> {{ $payment->student->name }}</p>
                            <p class="mb-2"><strong>Receipt:</strong> {{ $payment->receipt_number }}</p>
                            <p class="mb-2"><strong>Date:</strong> {{ $payment->payment_date->format('d M, Y') }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-2"><strong>Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
                            <p class="mb-2"><strong>Created By:</strong> {{ $payment->createdBy->name ?? 'System' }}</p>
                            <p class="mb-0"><strong>Status:</strong> <span class="badge badge-light">{{ ucfirst($payment->status ?? 'completed') }}</span></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                    <div class="display-4 mb-2">₹{{ number_format($payment->amount, 2) }}</div>
                    <p class="mb-0 opacity-75">Current Amount</p>
                </div>
            </div>
        </div>

        {{-- Edit Form --}}
        <form id="editPaymentForm" action="{{ route('admin.payment-edit.update', $payment) }}" method="POST" novalidate>
            @csrf
            @method('PUT')
            
            {{-- Payment Details Section --}}
            <div class="form-section">
                <h5 class="text-primary-theme mb-4">
                    <i class="fas fa-edit mr-2"></i> Edit Payment Details
                </h5>
                
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="amount" class="font-weight-bold text-gray-800">Payment Amount *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-rupee-sign"></i></span>
                                </div>
                                <input type="number" step="0.01" name="amount" id="amount" 
                                       class="form-control form-control-lg @error('amount') is-invalid @enderror" 
                                       value="{{ old('amount', $payment->amount) }}" required min="0.01">
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="payment_method" class="font-weight-bold text-gray-800">Payment Method *</label>
                            <select name="payment_method" id="payment_method" 
                                    class="form-control form-control-lg @error('payment_method') is-invalid @enderror" required>
                                <option value="">Select Method</option>
                                <option value="cash" {{ old('payment_method', $payment->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="card" {{ old('payment_method', $payment->payment_method) == 'card' ? 'selected' : '' }}>Card</option>
                                <option value="bank_transfer" {{ old('payment_method', $payment->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="upi" {{ old('payment_method', $payment->payment_method) == 'upi' ? 'selected' : '' }}>UPI</option>
                                <option value="cheque" {{ old('payment_method', $payment->payment_method) == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="online" {{ old('payment_method', $payment->payment_method) == 'online' ? 'selected' : '' }}>Online</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="payment_date" class="font-weight-bold text-gray-800">Payment Date *</label>
                            <input type="date" name="payment_date" id="payment_date" 
                                   class="form-control form-control-lg @error('payment_date') is-invalid @enderror" 
                                   value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="transaction_id" class="font-weight-bold text-gray-800">Transaction Reference</label>
                            <input type="text" name="transaction_id" id="transaction_id" 
                                   class="form-control @error('transaction_id') is-invalid @enderror" 
                                   value="{{ old('transaction_id', $payment->transaction_id) }}"
                                   placeholder="Transaction ID / Reference Number">
                            @error('transaction_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes" class="font-weight-bold text-gray-800">Notes</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" 
                                      rows="3" placeholder="Payment notes or comments">{{ old('notes', $payment->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Fee Components Section --}}
            <div class="form-section">
                <h5 class="text-primary-theme mb-4">
                    <i class="fas fa-list-check mr-2"></i> Edit Fee Component Allocation
                </h5>
                
                <div id="fee-components-list">
                    @if($availableFees && $availableFees->count() > 0)
                        @foreach($availableFees as $index => $studentFee)
                            @php
                                $currentComponent = $payment->componentItems->where('student_fee_id', $studentFee->id)->first();
                                $dueAmount = $studentFee->amount - $studentFee->paid_amount - ($studentFee->concession_amount ?? 0);
                                if ($currentComponent) {
                                    $dueAmount += $currentComponent->amount_paid; // Add back the current payment
                                }
                            @endphp
                            
                            @if($dueAmount > 0 || $currentComponent)
                            <div class="component-item {{ $currentComponent ? 'selected' : '' }}" 
                                 data-fee-id="{{ $studentFee->id }}" data-max-amount="{{ $dueAmount }}">
                                <div class="row align-items-center">
                                    <div class="col-md-1 col-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input component-checkbox" 
                                                   id="component_{{ $studentFee->id }}" 
                                                   name="components[{{ $index }}][selected]" 
                                                   value="1" {{ $currentComponent ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="component_{{ $studentFee->id }}"></label>
                                        </div>
                                        <input type="hidden" name="components[{{ $index }}][student_fee_id]" value="{{ $studentFee->id }}">
                                    </div>
                                    <div class="col-md-5 col-10">
                                        <div class="font-weight-bold text-gray-800">{{ $studentFee->feeCategory->name ?? 'Fee Component' }}</div>
                                        <small class="text-muted">{{ $studentFee->feeCategory->description ?? 'Standard fee component' }}</small>
                                    </div>
                                    <div class="col-md-3 col-6 mt-2 mt-md-0">
                                        <div class="text-center">
                                            <div class="font-weight-bold text-primary-theme">₹{{ number_format($studentFee->amount, 0) }}</div>
                                            <small class="text-muted">Total Fee</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mt-2 mt-md-0">
                                        <div class="form-group mb-0">
                                            <label class="small font-weight-bold text-gray-700">Payment Amount</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-rupee-sign"></i></span>
                                                </div>
                                                <input type="number" step="0.01" 
                                                       name="components[{{ $index }}][amount]" 
                                                       class="form-control component-amount" 
                                                       placeholder="0.00" min="0" max="{{ $dueAmount }}" 
                                                       value="{{ $currentComponent ? $currentComponent->amount_paid : '' }}"
                                                       {{ $currentComponent ? '' : 'disabled' }}>
                                            </div>
                                            <small class="text-muted">Max: ₹{{ number_format($dueAmount, 0) }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            No available fee components found for this student.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Edit Reason Section --}}
            <div class="form-section">
                <h5 class="text-primary-theme mb-4">
                    <i class="fas fa-clipboard-list mr-2"></i> Edit Reason & Summary
                </h5>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-group">
                            <label for="edit_reason" class="font-weight-bold text-gray-800">Reason for Editing *</label>
                            <textarea name="edit_reason" id="edit_reason" 
                                      class="form-control @error('edit_reason') is-invalid @enderror" 
                                      rows="4" required 
                                      placeholder="Please provide a detailed reason for editing this payment...">{{ old('edit_reason') }}</textarea>
                            @error('edit_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">This will be logged for audit purposes</small>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="amount-summary">
                            <h6 class="font-weight-bold mb-3 text-gray-800">
                                <i class="fas fa-calculator mr-2"></i> Payment Summary
                            </h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-gray-600">Original Amount:</span>
                                <span class="font-weight-bold text-gray-800">₹{{ number_format($payment->amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-gray-600">New Amount:</span>
                                <span class="font-weight-bold text-primary-theme" id="new-amount-display">₹0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="text-gray-600">Difference:</span>
                                <span class="font-weight-bold" id="amount-difference">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Validation Message --}}
            <div id="validation-message" class="validation-message validation-info mb-3">
                <i class="fas fa-info-circle mr-2"></i>
                Make changes and provide a reason to save
            </div>

            {{-- Action Buttons --}}
            <div class="form-section">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <button type="submit" class="btn btn-warning btn-lg mr-2" id="save-changes-btn" disabled>
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.students.show', $payment->student) }}" class="btn btn-secondary btn-lg mr-2">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
                        @can('view payment history')
                        @if(Route::has('admin.payment-edit.history'))
                        <a href="{{ route('admin.payment-edit.history', $payment) }}" class="btn btn-info btn-lg">
                            <i class="fas fa-history mr-2"></i> View History
                        </a>
                        @endif
                        @endcan
                    </div>
                </div>
            </div>
        </form>

        {{-- Danger Zone --}}
        @can('revert payments')
        <div class="danger-zone">
            <h5 class="text-danger mb-3">
                <i class="fas fa-exclamation-triangle mr-2"></i> Danger Zone
            </h5>
            <p class="mb-3 text-gray-700">These actions are irreversible. Please be certain before proceeding.</p>
            @if(Route::has('admin.payment-edit.history'))
            <a href="{{ route('admin.payment-edit.history', $payment) }}" class="btn btn-outline-danger">
                <i class="fas fa-undo mr-2"></i> Revert to Previous State
            </a>
            @endif
        </div>
        @endcan
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Ensure jQuery is loaded
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    const originalAmount = parseFloat({{ $payment->amount }}) || 0;
    
    // Initialize form functionality
    initializeEditForm();
    
    function initializeEditForm() {
        try {
            const amountInput = $('#amount');
            const componentCheckboxes = $('.component-checkbox');
            const componentAmounts = $('.component-amount');
            const saveBtn = $('#save-changes-btn');
            const validationMessage = $('#validation-message');
            
            // Ensure elements exist
            if (!amountInput.length) {
                console.error('Amount input not found');
                return;
            }
            
            // Amount input change handler
            amountInput.on('input change', function() {
                updateSummary();
                validateForm();
            });
            
            // Component checkbox change handler
            componentCheckboxes.on('change', function() {
                const checkbox = $(this);
                const componentItem = checkbox.closest('.component-item');
                const amountInput = componentItem.find('.component-amount');
                
                if (checkbox.is(':checked')) {
                    amountInput.prop('disabled', false);
                    componentItem.addClass('selected');
                    amountInput.focus();
                } else {
                    amountInput.prop('disabled', true);
                    componentItem.removeClass('selected');
                    amountInput.val('');
                }
                
                updateComponentAllocation();
                updateSummary();
                validateForm();
            });
            
            // Component amount change handler
            componentAmounts.on('input change', function() {
                updateSummary();
                validateForm();
            });
            
            // Edit reason change handler
            $('#edit_reason').on('input change', function() {
                validateForm();
            });
            
            // Payment method change handler
            $('#payment_method').on('change', function() {
                validateForm();
            });
            
            // Initialize summary and validation
            updateSummary();
            validateForm();
            
        } catch (error) {
            console.error('Error initializing form:', error);
        }
    }
    
    function updateSummary() {
        try {
            const newAmount = parseFloat($('#amount').val()) || 0;
            const difference = newAmount - originalAmount;
            
            // Update new amount display
            $('#new-amount-display').text('₹' + newAmount.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            
            // Update difference display
            const differenceElement = $('#amount-difference');
            if (difference > 0) {
                differenceElement.text('+₹' + Math.abs(difference).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })).removeClass('text-success').addClass('text-danger');
            } else if (difference < 0) {
                differenceElement.text('-₹' + Math.abs(difference).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })).removeClass('text-danger').addClass('text-success');
            } else {
                differenceElement.text('₹0.00').removeClass('text-danger text-success');
            }
        } catch (error) {
            console.error('Error updating summary:', error);
        }
    }
    
    function updateComponentAllocation() {
        try {
            const totalAmount = parseFloat($('#amount').val()) || 0;
            const checkedComponents = $('.component-checkbox:checked');
            
            if (checkedComponents.length === 0 || totalAmount === 0) return;
            
            const amountPerComponent = totalAmount / checkedComponents.length;
            
            checkedComponents.each(function() {
                const componentItem = $(this).closest('.component-item');
                const amountInput = componentItem.find('.component-amount');
                const maxAmount = parseFloat(componentItem.data('max-amount')) || 0;
                
                const allocatedAmount = Math.min(amountPerComponent, maxAmount);
                amountInput.val(allocatedAmount.toFixed(2));
            });
        } catch (error) {
            console.error('Error updating component allocation:', error);
        }
    }
    
    function validateForm() {
        try {
            const newAmount = parseFloat($('#amount').val()) || 0;
            const editReason = $('#edit_reason').val().trim();
            const paymentMethod = $('#payment_method').val();
            const hasSelectedComponents = $('.component-checkbox:checked').length > 0;
            
            let isValid = true;
            let message = '';
            let messageClass = 'validation-info';
            
            if (newAmount <= 0) {
                isValid = false;
                message = 'Please enter a valid payment amount';
                messageClass = 'validation-error';
            } else if (!paymentMethod) {
                isValid = false;
                message = 'Please select a payment method';
                messageClass = 'validation-error';
            } else if (!hasSelectedComponents) {
                isValid = false;
                message = 'Please select at least one fee component';
                messageClass = 'validation-error';
            } else if (!editReason) {
                isValid = false;
                message = 'Please provide a reason for editing this payment';
                messageClass = 'validation-error';
            } else {
                // Check if component allocation matches total amount
                let allocatedAmount = 0;
                $('.component-checkbox:checked').each(function() {
                    const amountInput = $(this).closest('.component-item').find('.component-amount');
                    allocatedAmount += parseFloat(amountInput.val()) || 0;
                });
                
                const amountDifference = Math.abs(allocatedAmount - newAmount);
                if (amountDifference > 0.01) { // Allow for small rounding differences
                    isValid = false;
                    message = `Component allocation (₹${allocatedAmount.toFixed(2)}) must equal total payment amount (₹${newAmount.toFixed(2)})`;
                    messageClass = 'validation-error';
                } else {
                    message = 'Ready to save changes';
                    messageClass = 'validation-success';
                }
            }
            
            // Update validation message
            const validationElement = $('#validation-message');
            validationElement
                .removeClass('validation-success validation-error validation-info')
                .addClass(messageClass)
                .html('<i class="fas fa-' + (isValid ? 'check-circle' : 'exclamation-circle') + ' mr-2"></i>' + message);
            
            // Update save button
            $('#save-changes-btn').prop('disabled', !isValid);
            
        } catch (error) {
            console.error('Error validating form:', error);
        }
    }
    
    // Form submission handler
    $('#editPaymentForm').on('submit', function(e) {
        try {
            if (!confirm('Are you sure you want to save these changes? This action will be logged for audit purposes.')) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const saveBtn = $('#save-changes-btn');
            saveBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving Changes...')
                   .prop('disabled', true);
                   
            // Prevent double submission
            setTimeout(function() {
                saveBtn.prop('disabled', false)
                       .html('<i class="fas fa-save mr-2"></i>Save Changes');
            }, 5000);
            
        } catch (error) {
            console.error('Error handling form submission:', error);
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-distribute amount when it changes
    $('#amount').on('blur', function() {
        updateComponentAllocation();
    });
    
    // Component item click handler (make entire item clickable)
    $('.component-item').on('click', function(e) {
        if (e.target.type !== 'checkbox' && e.target.type !== 'number') {
            const checkbox = $(this).find('.component-checkbox');
            checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
        }
    });
    
    // Prevent component item click when clicking on input fields
    $('.component-amount, .component-checkbox').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Format number inputs on blur
    $('.component-amount, #amount').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });
    
    // Initialize tooltips if Bootstrap is available
    if (typeof $().tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Auto-save draft functionality (optional)
    let autoSaveTimer;
    $('input, select, textarea').on('input change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Could implement auto-save to localStorage here
            console.log('Form data changed - could auto-save draft');
        }, 2000);
    });
    
    // Warn user about unsaved changes
    let formChanged = false;
    $('input, select, textarea').on('input change', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function(e) {
        if (formChanged) {
            const message = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });
    
    // Clear the warning when form is submitted
    $('#editPaymentForm').on('submit', function() {
        formChanged = false;
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if (!$('#save-changes-btn').is(':disabled')) {
                $('#editPaymentForm').submit();
            }
        }
        
        // Escape to cancel
        if (e.key === 'Escape') {
            if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
                window.location.href = "{{ route('admin.students.show', $payment->student) }}";
            }
        }
    });
    
    // Mobile responsive improvements
    function handleMobileView() {
        if ($(window).width() < 768) {
            $('.component-item .row').addClass('text-center');
            $('.amount-summary').addClass('mt-3');
        } else {
            $('.component-item .row').removeClass('text-center');
            $('.amount-summary').removeClass('mt-3');
        }
    }
    
    // Run on load and resize
    handleMobileView();
    $(window).on('resize', handleMobileView);
    
    // Error handling for AJAX if needed
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        console.error('AJAX Error:', thrownError);
        
        // Show user-friendly error message
        const errorMsg = xhr.responseJSON?.message || 'An error occurred. Please try again.';
        
        // Could show toast notification or modal here
        alert('Error: ' + errorMsg);
    });
    
    // Success handler for form completion
    function handleFormSuccess() {
        formChanged = false;
        
        // Show success message
        const successMsg = $('<div class="alert alert-success alert-dismissible fade show" role="alert">')
            .html('<i class="fas fa-check-circle mr-2"></i>Payment updated successfully! <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>');
        
        $('.edit-form-container').prepend(successMsg);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 500);
    }
    
    // Initialize perfect scrollbar if available
    if (typeof PerfectScrollbar !== 'undefined') {
        const scrollableElements = document.querySelectorAll('.scrollable');
        scrollableElements.forEach(el => new PerfectScrollbar(el));
    }
    
    console.log('Payment edit form initialized successfully');
});

// Fallback for browsers without modern JS support
if (!window.jQuery) {
    document.addEventListener('DOMContentLoaded', function() {
        console.warn('jQuery not available - form will work with basic functionality only');
        
        // Basic form validation without jQuery
        const form = document.getElementById('editPaymentForm');
        const amountInput = document.getElementById('amount');
        const editReasonInput = document.getElementById('edit_reason');
        const saveBtn = document.getElementById('save-changes-btn');
        
        function basicValidation() {
            const amount = parseFloat(amountInput.value) || 0;
            const reason = editReasonInput.value.trim();
            const isValid = amount > 0 && reason.length > 0;
            
            saveBtn.disabled = !isValid;
        }
        
        if (amountInput) amountInput.addEventListener('input', basicValidation);
        if (editReasonInput) editReasonInput.addEventListener('input', basicValidation);
        
        basicValidation(); // Initial check
    });
}
</script>
@endpush