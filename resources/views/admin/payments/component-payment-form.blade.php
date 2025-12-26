@extends('layouts.theme')

@section('title', 'Component Payment - ' . $student->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Component Payment - {{ $student->name }}</h1>
        <div class="btn-group">
            <a href="{{ route('component-payments.student-dashboard', $student) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Student Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-info shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Enrollment:</strong> {{ $student->enrollment_number }}
                        </div>
                        <div class="col-md-3">
                            <strong>Batch:</strong> {{ $student->batch->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-3">
                            <strong>Course:</strong> {{ $student->batch->course->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-3">
                            <strong>Mobile:</strong> {{ $student->student_mobile ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Record Component Payment</h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('component-payments.record', $student) }}" id="componentPaymentForm">
                        @csrf
                        
                        <!-- Unpaid Fee Components -->
                        <div class="table-responsive mb-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Due Date</th>
                                        <th class="text-right">Amount</th>
                                        <th class="text-right">Paid</th>
                                        <th class="text-right">Balance</th>
                                        <th class="text-right">Pay Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($unpaidFees as $index => $fee)
                                    <tr>
                                        <td>
                                            <strong>{{ $fee->feeCategory->name }}</strong>
                                            @if($fee->isOverdue())
                                                <span class="badge badge-danger ml-1">Overdue</span>
                                            @endif
                                        </td>
                                        <td>{{ $fee->due_date->format('d/m/Y') }}</td>
                                        <td class="text-right">₹{{ number_format($fee->amount, 2) }}</td>
                                        <td class="text-right">₹{{ number_format($fee->paid_amount, 2) }}</td>
                                        <td class="text-right">
                                            <strong>₹{{ number_format($fee->getRemainingAmount(), 2) }}</strong>
                                        </td>
                                        <td class="text-right">
                                            <input type="number" 
                                                   name="components[{{ $index }}][amount]" 
                                                   class="form-control payment-amount text-right" 
                                                   min="0" 
                                                   max="{{ $fee->getRemainingAmount() }}" 
                                                   step="0.01"
                                                   placeholder="0.00"
                                                   data-max="{{ $fee->getRemainingAmount() }}"
                                                   data-category="{{ $fee->feeCategory->name }}">
                                            <input type="hidden" 
                                                   name="components[{{ $index }}][fee_category_id]" 
                                                   value="{{ $fee->fee_category_id }}">
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No unpaid fees found for this student.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($unpaidFees->count() > 0)
                        <!-- Payment Details -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_method">Payment Method *</label>
                                    <select name="payment_method" id="payment_method" class="form-control" required>
                                        <option value="">Select Method</option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                                        <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                        <option value="online" {{ old('payment_method') == 'online' ? 'selected' : '' }}>Online</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_date">Payment Date *</label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control" 
                                           value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_id">Transaction ID (Optional)</label>
                                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" 
                                           value="{{ old('transaction_id') }}" placeholder="Transaction/Reference ID">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">Notes (Optional)</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="2" 
                                              placeholder="Additional notes">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-center">
                            <div class="alert alert-info">
                                <strong>Total Payment: ₹<span id="total-payment">0.00</span></strong>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg" id="process-payment" disabled>
                                <i class="fas fa-credit-card"></i> Process Payment
                            </button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment Summary Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-info">Student Fee Summary</h6>
                </div>
                <div class="card-body">
                    @php
                        $totalFees = $student->studentFees->sum('amount');
                        $totalPaid = $student->studentFees->sum('paid_amount');
                        $totalConcession = $student->studentFees->sum('concession_amount');
                        $totalDue = $totalFees - $totalConcession - $totalPaid;
                        $netFees = $totalFees - $totalConcession;
                    @endphp
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Fees:</span>
                            <strong>₹{{ number_format($totalFees, 2) }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Concession:</span>
                            <span class="text-warning">₹{{ number_format($totalConcession, 2) }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Net Fees:</span>
                            <strong>₹{{ number_format($netFees, 2) }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Paid:</span>
                            <span class="text-success">₹{{ number_format($totalPaid, 2) }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Amount Due:</span>
                            <strong class="text-danger">₹{{ number_format($totalDue, 2) }}</strong>
                        </div>
                    </div>
                    
                    <div class="progress mb-3">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: {{ $netFees > 0 ? ($totalPaid / $netFees) * 100 : 0 }}%">
                            {{ $netFees > 0 ? round(($totalPaid / $netFees) * 100) : 0 }}%
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    @php
                        $recentPayments = $student->componentPayments()->latest()->limit(3)->get();
                    @endphp
                    
                    @if($recentPayments->count() > 0)
                    <h6 class="text-muted mb-2">Recent Payments</h6>
                    @foreach($recentPayments as $payment)
                    <div class="small mb-2">
                        <div class="d-flex justify-content-between">
                            <span>{{ $payment->receipt_number }}</span>
                            <span>₹{{ number_format($payment->amount, 2) }}</span>
                        </div>
                        <div class="text-muted">{{ $payment->payment_date->format('d/m/Y') }}</div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>

            <!-- Quick Payment Card -->
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-success">Quick Payment</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Select a category for quick payment</p>
                    @foreach($unpaidFees->groupBy('fee_category_id') as $categoryId => $fees)
                        @php
                            $category = $fees->first()->feeCategory;
                            $totalRemaining = $fees->sum(fn($fee) => $fee->getRemainingAmount());
                        @endphp
                        @if($totalRemaining > 0)
                        <button type="button" class="btn btn-sm btn-outline-success mb-2 quick-pay-btn" 
                                data-category-id="{{ $categoryId }}"
                                data-category-name="{{ $category->name }}"
                                data-amount="{{ $totalRemaining }}">
                            {{ $category->name }} (₹{{ number_format($totalRemaining, 2) }})
                        </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Payment Modal -->
<div class="modal fade" id="quickPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Payment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="quickPaymentForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="quick_fee_category_id" name="fee_category_id">
                    
                    <div class="form-group">
                        <label>Category:</label>
                        <p class="form-control-plaintext" id="quick_category_name"></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="quick_amount">Amount *</label>
                        <input type="number" id="quick_amount" name="amount" class="form-control" 
                               min="0.01" step="0.01" required>
                        <small class="text-muted">Maximum: ₹<span id="quick_max_amount"></span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="quick_payment_method">Payment Method *</label>
                        <select id="quick_payment_method" name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quick_notes">Notes (Optional)</label>
                        <textarea id="quick_notes" name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Process Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentAmounts = document.querySelectorAll('.payment-amount');
    const totalPaymentSpan = document.getElementById('total-payment');
    const processButton = document.getElementById('process-payment');

    function updateTotal() {
        let total = 0;
        let hasPayment = false;

        paymentAmounts.forEach(input => {
            const amount = parseFloat(input.value) || 0;
            const maxAmount = parseFloat(input.dataset.max) || 0;
            
            // Validate amount doesn't exceed maximum
            if (amount > maxAmount) {
                input.value = maxAmount;
                amount = maxAmount;
            }
            
            if (amount > 0) {
                hasPayment = true;
                total += amount;
            }
        });

        totalPaymentSpan.textContent = total.toFixed(2);
        processButton.disabled = !hasPayment;
    }

    // Update total when payment amounts change
    paymentAmounts.forEach(input => {
        input.addEventListener('input', updateTotal);
        input.addEventListener('blur', function() {
            const amount = parseFloat(this.value) || 0;
            const maxAmount = parseFloat(this.dataset.max) || 0;
            
            if (amount > maxAmount) {
                this.value = maxAmount.toFixed(2);
                updateTotal();
            }
        });
    });

    // Quick payment functionality
    const quickPayBtns = document.querySelectorAll('.quick-pay-btn');
    const quickPaymentModal = $('#quickPaymentModal');
    const quickPaymentForm = document.getElementById('quickPaymentForm');

    quickPayBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const categoryName = this.dataset.categoryName;
            const maxAmount = parseFloat(this.dataset.amount);

            document.getElementById('quick_fee_category_id').value = categoryId;
            document.getElementById('quick_category_name').textContent = categoryName;
            document.getElementById('quick_amount').setAttribute('max', maxAmount);
            document.getElementById('quick_amount').value = maxAmount.toFixed(2);
            document.getElementById('quick_max_amount').textContent = maxAmount.toFixed(2);

            quickPaymentModal.modal('show');
        });
    });

    // Handle quick payment form submission
    quickPaymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        fetch('{{ route("component-payments.quick-pay", $student) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                quickPaymentModal.modal('hide');
                // Show success message and reload page
                alert('Payment processed successfully! Receipt: ' + data.payment.receipt_number);
                window.location.reload();
            } else {
                alert('Payment failed: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing the payment.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Process Payment';
        });
    });

    // Form validation
    const mainForm = document.getElementById('componentPaymentForm');
    mainForm.addEventListener('submit', function(e) {
        const hasPayment = Array.from(paymentAmounts).some(input => parseFloat(input.value) > 0);
        
        if (!hasPayment) {
            e.preventDefault();
            alert('Please enter at least one payment amount.');
            return false;
        }
    });
});
</script>

<style>
.payment-amount {
    max-width: 120px;
}

.quick-pay-btn {
    width: 100%;
    text-align: left;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .payment-amount {
        max-width: 100px;
    }
}
</style>
@endsection