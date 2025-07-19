@extends('layouts.theme')
@section('title', 'Component Payment Dashboard - ' . $student->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Component Payment Dashboard</h1>
        <div class="btn-group">
            <a href="{{ route('admin.invoices.show-student-ledger', $student) }}" class="btn btn-secondary">
                <i class="fas fa-file-invoice"></i> Full Ledger
            </a>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#recordPaymentModal">
                <i class="fas fa-plus"></i> Record Payment
            </button>
        </div>
    </div>

    <!-- Student Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-primary shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Student:</strong> {{ $student->name }}<br>
                            <strong>Enrollment:</strong> {{ $student->enrollment_number }}
                        </div>
                        <div class="col-md-3">
                            <strong>Batch:</strong> {{ $student->batch->name ?? 'N/A' }}<br>
                            <strong>Course:</strong> {{ $student->batch->course->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-3">
                            <strong>Email:</strong> {{ $student->email }}<br>
                            <strong>Mobile:</strong> {{ $student->student_mobile }}
                        </div>
                        <div class="col-md-3">
                            @php
                                $totalDue = $feeComponents->sum('pending_amount');
                                $totalPaid = $feeComponents->sum('paid_amount');
                                $totalAmount = $feeComponents->sum('total_amount');
                            @endphp
                            <strong>Total Outstanding:</strong> ₹{{ number_format($totalDue, 2) }}<br>
                            <strong>Total Paid:</strong> ₹{{ number_format($totalPaid, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Component-wise Payment Status -->
    <div class="row">
        @foreach($feeComponents as $component)
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card border-left-{{ $component['status'] === 'fully_paid' ? 'success' : ($component['status'] === 'partially_paid' ? 'warning' : 'danger') }} shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-{{ $component['status'] === 'fully_paid' ? 'success' : ($component['status'] === 'partially_paid' ? 'warning' : 'danger') }}">
                        {{ $component['category']->name }}
                    </h6>
                    <span class="badge badge-{{ $component['status'] === 'fully_paid' ? 'success' : ($component['status'] === 'partially_paid' ? 'warning' : 'danger') }}">
                        {{ $component['payment_percentage'] }}% Paid
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Total Amount</small><br>
                            <strong>₹{{ number_format($component['total_amount'], 2) }}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Pending</small><br>
                            <strong class="text-{{ $component['pending_amount'] > 0 ? 'danger' : 'success' }}">
                                ₹{{ number_format($component['pending_amount'], 2) }}
                            </strong>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-{{ $component['status'] === 'fully_paid' ? 'success' : ($component['status'] === 'partially_paid' ? 'warning' : 'danger') }}" 
                             role="progressbar" 
                             style="width: {{ $component['payment_percentage'] }}%">
                        </div>
                    </div>

                    <!-- Individual Fee Details -->
                    <div class="accordion" id="accordion{{ $component['category']->id }}">
                        <div class="card">
                            <div class="card-header p-2" id="heading{{ $component['category']->id }}">
                                <button class="btn btn-link btn-sm collapsed" type="button" 
                                        data-toggle="collapse" 
                                        data-target="#collapse{{ $component['category']->id }}">
                                    <i class="fas fa-list"></i> View Fee Details ({{ $component['fees']->count() }} items)
                                </button>
                            </div>
                            <div id="collapse{{ $component['category']->id }}" 
                                 class="collapse" 
                                 data-parent="#accordion{{ $component['category']->id }}">
                                <div class="card-body p-2">
                                    @foreach($component['fees'] as $fee)
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                        <div>
                                            <small class="text-muted">Due: {{ $fee->due_date->format('d M Y') }}</small><br>
                                            <span class="badge badge-{{ $fee->status === 'paid' ? 'success' : ($fee->status === 'partial' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($fee->status) }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <strong>₹{{ number_format($fee->amount, 2) }}</strong>
                                            @if($fee->paid_date)
                                                <br><small class="text-success">Paid: {{ $fee->paid_date->format('d M Y') }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($component['pending_amount'] > 0)
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-primary btn-block pay-component-btn"
                                data-category-id="{{ $component['category']->id }}"
                                data-category-name="{{ $component['category']->name }}"
                                data-pending-amount="{{ $component['pending_amount'] }}">
                            <i class="fas fa-credit-card"></i> Pay ₹{{ number_format($component['pending_amount'], 2) }}
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Recent Component Payments -->
    @if($recentPayments->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Component Payments</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($recentPayments as $payment)
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h6 class="text-success">{{ $payment['category']->name }}</h6>
                                <p class="mb-1">
                                    <strong>Total Paid:</strong> ₹{{ number_format($payment['total_paid'], 2) }}<br>
                                    <strong>Payments:</strong> {{ $payment['payment_count'] }} transaction(s)<br>
                                    <strong>Last Payment:</strong> {{ \Carbon\Carbon::parse($payment['last_payment'])->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Component Payment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.component-payments.record', $student) }}" method="POST" id="paymentForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_method">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-control" required>
                                <option value="">Select Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_date">Payment Date</label>
                            <input type="date" name="payment_date" id="payment_date" class="form-control" 
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="transaction_id">Transaction ID (Optional)</label>
                            <input type="text" name="transaction_id" id="transaction_id" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="notes">Notes (Optional)</label>
                            <input type="text" name="notes" id="notes" class="form-control">
                        </div>
                    </div>

                    <h6>Select Components to Pay:</h6>
                    <div id="component-selection">
                        @foreach($feeComponents as $component)
                        @if($component['pending_amount'] > 0)
                        <div class="card mb-2">
                            <div class="card-body p-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input component-checkbox" 
                                           name="components[{{ $loop->index }}][enabled]" 
                                           id="component_{{ $component['category']->id }}"
                                           data-category-id="{{ $component['category']->id }}"
                                           data-max-amount="{{ $component['pending_amount'] }}">
                                    <label class="form-check-label" for="component_{{ $component['category']->id }}">
                                        <strong>{{ $component['category']->name }}</strong>
                                        <span class="text-muted">(Pending: ₹{{ number_format($component['pending_amount'], 2) }})</span>
                                    </label>
                                </div>
                                <div class="row mt-2 component-amount-row" style="display: none;">
                                    <div class="col-md-6">
                                        <input type="hidden" name="components[{{ $loop->index }}][fee_category_id]" 
                                               value="{{ $component['category']->id }}">
                                        <input type="number" name="components[{{ $loop->index }}][amount]" 
                                               class="form-control component-amount" 
                                               placeholder="Amount" 
                                               step="0.01" 
                                               min="0.01" 
                                               max="{{ $component['pending_amount'] }}">
                                    </div>
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-sm btn-secondary full-amount-btn"
                                                data-amount="{{ $component['pending_amount'] }}">
                                            Pay Full Amount
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>

                    <div class="mt-3 p-3 bg-light rounded">
                        <h6>Payment Summary:</h6>
                        <div class="d-flex justify-content-between">
                            <span>Total Payment Amount:</span>
                            <strong id="total-amount">₹0.00</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitPayment" disabled>Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Handle component checkbox change
    $('.component-checkbox').change(function() {
        const $checkbox = $(this);
        const $amountRow = $checkbox.closest('.card-body').find('.component-amount-row');
        const $amountInput = $amountRow.find('.component-amount');
        
        if ($checkbox.is(':checked')) {
            $amountRow.show();
            $amountInput.prop('required', true);
        } else {
            $amountRow.hide();
            $amountInput.prop('required', false).val('');
            updateTotalAmount();
        }
    });

    // Handle full amount button
    $('.full-amount-btn').click(function() {
        const amount = $(this).data('amount');
        $(this).closest('.component-amount-row').find('.component-amount').val(amount);
        updateTotalAmount();
    });

    // Update total when amount changes
    $('.component-amount').on('input', function() {
        updateTotalAmount();
    });

    // Handle pay component button (quick pay)
    $('.pay-component-btn').click(function() {
        const categoryId = $(this).data('category-id');
        const categoryName = $(this).data('category-name');
        const pendingAmount = $(this).data('pending-amount');
        
        // Clear previous selections
        $('.component-checkbox').prop('checked', false);
        $('.component-amount-row').hide();
        $('.component-amount').val('').prop('required', false);
        
        // Select and fill this component
        const $targetCheckbox = $(`.component-checkbox[data-category-id="${categoryId}"]`);
        $targetCheckbox.prop('checked', true);
        
        const $amountRow = $targetCheckbox.closest('.card-body').find('.component-amount-row');
        const $amountInput = $amountRow.find('.component-amount');
        
        $amountRow.show();
        $amountInput.prop('required', true).val(pendingAmount);
        
        updateTotalAmount();
        $('#recordPaymentModal').modal('show');
    });

    function updateTotalAmount() {
        let total = 0;
        $('.component-checkbox:checked').each(function() {
            const $amountInput = $(this).closest('.card-body').find('.component-amount');
            const amount = parseFloat($amountInput.val()) || 0;
            total += amount;
        });
        
        $('#total-amount').text('₹' + total.toFixed(2));
        $('#submitPayment').prop('disabled', total <= 0);
    }

    // Form validation
    $('#paymentForm').on('submit', function(e) {
        const checkedComponents = $('.component-checkbox:checked').length;
        if (checkedComponents === 0) {
            e.preventDefault();
            alert('Please select at least one component to pay.');
            return false;
        }
        
        let isValid = true;
        $('.component-checkbox:checked').each(function() {
            const $amountInput = $(this).closest('.card-body').find('.component-amount');
            const amount = parseFloat($amountInput.val()) || 0;
            if (amount <= 0) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please enter valid amounts for all selected components.');
            return false;
        }
    });
});
</script>
@endsection