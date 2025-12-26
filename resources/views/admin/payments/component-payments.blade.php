@extends('layouts.theme')

@if(!$student)
    @section('content')
    <div class="alert alert-danger">
        <h4>Error: Student Not Found</h4>
        <p>The requested student could not be found.</p>
        <a href="{{ route('admin.students.index') }}" class="btn btn-primary">Back to Students List</a>
    </div>
    @endsection
    @php return; @endphp
@endif

@section('title', 'Student Component Dashboard' . ($student ? ' - ' . $student->name : ''))

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Component Payment Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Students</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.students.show', $student) }}">{{ $student->name }}</a></li>
                    <li class="breadcrumb-item active">Payment Dashboard</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Student
            </a>
            @can('create payments')
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#quickPaymentModal">
                <i class="fas fa-plus"></i> Quick Payment
            </button>
            @endcan
        </div>
    </div>

    <!-- Student Info Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; background: #4e73df; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 32px; font-weight: bold;">
                                    {{ substr($student->name, 0, 1) }}
                                </div>
                                <h5 class="mb-1">{{ $student->name ?? 'N/A' }}</h5>
                                <p class="text-muted mb-0">{{ $student->enrollment_number }}</p>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="font-weight-bold">Course:</td>
                                            <td>{{ $student->batch->course->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Batch:</td>
                                            <td>{{ $student->batch->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Mobile:</td>
                                            <td>{{ $student->student_mobile ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="font-weight-bold">Total Billed:</td>
                                            <td><strong class="text-info">₹{{ number_format($totalBilled, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Total Paid:</td>
                                            <td><strong class="text-success">₹{{ number_format($totalPaid, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Balance Due:</td>
                                            <td><strong class="text-{{ $balanceDue > 0 ? 'danger' : 'success' }}">₹{{ number_format($balanceDue, 2) }}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Components -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Fee Components</h5>
                </div>
                <div class="card-body">
                    @if($studentFees->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Fee Category</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Concession</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentFees as $fee)
                                @php
                                    $netAmount = $fee->amount - $fee->concession_amount;
                                    $balance = $netAmount - $fee->paid_amount;
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $fee->feeCategory->name }}</strong>
                                        <br>
                                        <small class="text-muted">Due: {{ $fee->due_date ? $fee->due_date->format('d M Y') : 'N/A' }}</small>
                                    </td>
                                    <td class="text-end">₹{{ number_format($fee->amount, 2) }}</td>
                                    <td class="text-end">
                                        @if($fee->concession_amount > 0)
                                        <span class="text-info">₹{{ number_format($fee->concession_amount, 2) }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success">₹{{ number_format($fee->paid_amount, 2) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-{{ $balance > 0 ? 'danger' : 'success' }}">
                                            ₹{{ number_format($balance, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @switch($fee->status)
                                            @case('paid')
                                                <span class="badge badge-success">Paid</span>
                                                @break
                                            @case('partial')
                                                <span class="badge badge-warning">Partial</span>
                                                @break
                                            @case('overdue')
                                                <span class="badge badge-danger">Overdue</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">{{ ucfirst($fee->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-center">
                                        @if($balance > 0)
                                        @can('create payments')
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="openQuickPayment({{ $fee->id }}, '{{ $fee->feeCategory->name }}', {{ $balance }})">
                                            <i class="fas fa-money-bill"></i> Pay
                                        </button>
                                        @endcan
                                        @else
                                        <span class="text-muted">Paid</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Fee Components Found</h5>
                        <p class="text-muted">No fee components have been generated for this student.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Payments</h5>
                </div>
                <div class="card-body">
                    @if($recentPayments->count() > 0)
                    <div class="payment-timeline">
                        @foreach($recentPayments as $payment)
                        <div class="payment-item mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">₹{{ number_format($payment->amount, 2) }}</h6>
                                    <div class="small text-muted">
                                        {{ $payment->payment_date ? $payment->payment_date->format('d M Y') : 'N/A' }}
                                        <br>
                                        <span class="badge badge-info badge-sm">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                    </div>
                                    <div class="small mt-1">
                                        @if($payment->componentItems && $payment->componentItems->count() > 0)
                                            @foreach($payment->componentItems->take(2) as $item)
                                            <div>{{ $item->studentFee->feeCategory->name }}: ₹{{ number_format($item->amount_paid, 2) }}</div>
                                            @endforeach
                                            @if($payment->componentItems->count() > 2)
                                            <div class="text-muted">+{{ $payment->componentItems->count() - 2 }} more...</div>
                                            @endif
                                        @else
                                            <div class="text-muted">Payment details not available</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a href="{{ route('admin.component-payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No payments recorded yet</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Payment Modal -->
@can('create payments')
<div class="modal fade" id="quickPaymentModal" tabindex="-1" role="dialog" aria-labelledby="quickPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickPaymentModalLabel">Quick Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="quickPaymentForm" novalidate>
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <input type="hidden" name="student_fee_id" id="student_fee_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Fee Category</label>
                        <input type="text" id="fee_category_name" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount" class="required">Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">₹</span>
                            </div>
                            <input type="number" name="amount" id="amount" class="form-control" 
                                   step="0.01" min="0.01" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <small class="form-text text-muted">Maximum: ₹<span id="max_amount">0.00</span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method" class="required">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_date" class="required">Payment Date</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control" 
                               value="{{ date('Y-m-d') }}" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_id">Transaction ID</label>
                        <input type="text" name="transaction_id" id="transaction_id" class="form-control" 
                               maxlength="100" placeholder="For online payments, bank transfers, etc.">
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" 
                                  maxlength="500" placeholder="Optional payment notes"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize quick payment modal
    function openQuickPayment(studentFeeId, categoryName, maxAmount) {
        // Clear any previous validation errors
        clearValidationErrors();
        
        $('#student_fee_id').val(studentFeeId);
        $('#fee_category_name').val(categoryName);
        $('#max_amount').text(maxAmount.toFixed(2));
        $('#amount').attr('max', maxAmount);
        $('#amount').val('');
        $('#quickPaymentModal').modal('show');
    }
    
    // Make openQuickPayment global
    window.openQuickPayment = openQuickPayment;
    
    // Clear validation errors
    function clearValidationErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }
    
    // Show validation errors
    function showValidationErrors(errors) {
        clearValidationErrors();
        
        $.each(errors, function(field, messages) {
            const input = $('[name="' + field + '"]');
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(messages[0]);
        });
    }
    
    // Handle quick payment form submission
    $('#quickPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Clear previous validation errors
        clearValidationErrors();
        
        // Disable submit button
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        // Determine the correct store URL
        let storeUrl;
        try {
            storeUrl = '{{ route("component-payments.store-quick") }}';
        } catch (e) {
            storeUrl = '{{ url("admin/component-payments/store-quick") }}';
        }
        
        $.ajax({
            url: storeUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Payment recorded successfully');
                    } else {
                        alert(response.message || 'Payment recorded successfully');
                    }
                    
                    // Close modal
                    $('#quickPaymentModal').modal('hide');
                    
                    // Reload page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Failed to record payment');
                    } else {
                        alert(response.message || 'Failed to record payment');
                    }
                }
            },
            error: function(xhr) {
                console.error('Payment error:', xhr);
                let errorMessage = 'Failed to record payment';
                
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    // Handle validation errors
                    showValidationErrors(xhr.responseJSON.errors);
                    errorMessage = 'Please correct the errors and try again';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMessage);
                } else {
                    alert(errorMessage);
                }
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Amount validation
    $('#amount').on('input', function() {
        const amount = parseFloat($(this).val());
        const maxAmount = parseFloat($(this).attr('max'));
        
        if (amount > maxAmount) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('Amount cannot exceed the remaining balance');
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('');
        }
    });
    
    // Reset form when modal is closed
    $('#quickPaymentModal').on('hidden.bs.modal', function() {
        $('#quickPaymentForm')[0].reset();
        clearValidationErrors();
    });
});
</script>
@endpush

@push('styles')
<style>
.avatar-circle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: bold;
}

.payment-timeline .payment-item:last-child {
    border-bottom: none !important;
    padding-bottom: 0 !important;
}

.badge-sm {
    font-size: 0.65em;
    padding: 0.2em 0.4em;
}

.table th, .table td {
    vertical-align: middle;
}

.required:after {
    content: " *";
    color: red;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}
</style>
@endpush