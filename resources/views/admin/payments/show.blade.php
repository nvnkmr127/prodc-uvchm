@extends('layouts.theme')
@section('title', 'Financial Ledger for ' . $student->name)

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Financial Ledger: <strong>{{ $student->name }}</strong></h1>
        <div class="btn-group">
            <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Students
            </a>

            {{-- Component Payment Dashboard --}}
            <a href="{{ route('admin.payments.component-dashboard', $student) }}" class="btn btn-sm btn-info shadow-sm">
                <i class="fas fa-tachometer-alt fa-sm text-white-50"></i> Component Dashboard
            </a>

            {{-- Record New Payment --}}
            <a href="{{ route('admin.component-payments.create', ['student_id' => $student->id]) }}"
                class="btn btn-sm btn-success shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Record Payment
            </a>
        </div>
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

    {{-- Financial Summary Cards --}}
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Fee Amount</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ setting('currency_symbol', '₹') }}
                        {{ number_format($totalBilled ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Paid</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalPaid ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Concession</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalConcession ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Balance Due</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($balanceDue ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Component-wise Fee Breakdown --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-info shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-puzzle-piece"></i> Fee Components Breakdown
                    </h6>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.component-payments.create', ['student_id' => $student->id]) }}"
                            class="btn btn-info btn-sm">
                            <i class="fas fa-plus"></i> Record Payment
                        </a>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="refreshComponentSummary()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row" id="component-summary">
                        @if(isset($studentFees) && $studentFees->count() > 0)
                            @foreach($studentFees as $studentFee)
                                @php
                                    $paymentPercentage = ($studentFee->amount > 0) ?
                                        round(($studentFee->paid_amount / $studentFee->amount) * 100, 1) : 0;
                                    $dueAmount = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;

                                    if ($paymentPercentage >= 100) {
                                        $statusColor = 'success';
                                    } elseif ($paymentPercentage > 0) {
                                        $statusColor = 'warning';
                                    } else {
                                        $statusColor = 'danger';
                                    }
                                @endphp

                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-{{ $statusColor }} h-100 component-card">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="text-{{ $statusColor }} mb-0 small font-weight-bold">
                                                    {{ $studentFee->feeCategory->name }}
                                                </h6>
                                                <span class="badge badge-{{ $statusColor }}">{{ $paymentPercentage }}%</span>
                                            </div>
                                            <div class="small">
                                                <div class="d-flex justify-content-between">
                                                    <span>Total:</span>
                                                    <span
                                                        class="font-weight-bold">₹{{ number_format($studentFee->amount, 2) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Paid:</span>
                                                    <span
                                                        class="text-success font-weight-bold">₹{{ number_format($studentFee->paid_amount, 2) }}</span>
                                                </div>
                                                @if($studentFee->concession_amount > 0)
                                                    <div class="d-flex justify-content-between">
                                                        <span>Concession:</span>
                                                        <span
                                                            class="text-info">₹{{ number_format($studentFee->concession_amount, 2) }}</span>
                                                    </div>
                                                @endif
                                                <div class="d-flex justify-content-between">
                                                    <span>Pending:</span>
                                                    <span class="text-{{ $dueAmount > 0 ? 'danger' : 'success' }} font-weight-bold">
                                                        ₹{{ number_format($dueAmount, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="progress mt-2" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $statusColor }}"
                                                    style="width: {{ $paymentPercentage }}%"></div>
                                            </div>
                                            @if($dueAmount > 0)
                                                <button class="btn btn-{{ $statusColor }} btn-sm btn-block mt-2"
                                                    onclick="quickPayComponent('{{ $studentFee->id }}', '{{ $studentFee->feeCategory->name }}', {{ $dueAmount }})">
                                                    <i class="fas fa-bolt"></i> Quick Pay
                                                </button>
                                            @else
                                                <div class="text-center mt-2">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                    <small class="text-success d-block">Fully Paid</small>
                                                </div>
                                            @endif
                                            @if($studentFee->due_date && \Carbon\Carbon::parse($studentFee->due_date)->isPast() && $dueAmount > 0)
                                                <div class="mt-1">
                                                    <small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        Overdue since
                                                        {{ \Carbon\Carbon::parse($studentFee->due_date)->format('d M, Y') }}
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-12 text-center text-muted">
                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                <h5>No Fee Components Found</h5>
                                <p>This student doesn't have any fee components assigned yet.</p>
                                <a href="{{ route('admin.fee-structures.index') }}" class="btn btn-primary">
                                    <i class="fas fa-cog"></i> Configure Fee Structure
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Payment & Management Options</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-left-success">
                        <div class="card-body text-center">
                            <i class="fas fa-credit-card fa-2x text-success mb-2"></i>
                            <h6 class="text-success">Record Payment</h6>
                            <p class="small text-muted mb-3">Add payment for fee components</p>
                            <a href="#" class="btn btn-success btn-sm" data-toggle="modal"
                                data-target="#recordPaymentModal">
                                <i class="fas fa-plus"></i> Record Payment
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-left-info">
                        <div class="card-body text-center">
                            <i class="fas fa-tachometer-alt fa-2x text-info mb-2"></i>
                            <h6 class="text-info">Payment Dashboard</h6>
                            <p class="small text-muted mb-3">Comprehensive payment overview</p>
                            <a href="{{ route('admin.payments.component-dashboard', $student) }}"
                                class="btn btn-info btn-sm">
                                <i class="fas fa-external-link-alt"></i> View Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-left-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-percent fa-2x text-warning mb-2"></i>
                            <h6 class="text-warning">Manage Concessions</h6>
                            <p class="small text-muted mb-3">Apply discounts to fee components</p>
                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#applyConcessionModal">
                                <i class="fas fa-percent"></i> Apply Concession
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-left-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-file-download fa-2x text-primary mb-2"></i>
                            <h6 class="text-primary">Generate Reports</h6>
                            <p class="small text-muted mb-3">Download financial statements</p>
                            <button class="btn btn-primary btn-sm" onclick="printFinancialStatement()">
                                <i class="fas fa-file-pdf"></i> Print Statement
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment History Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-primary btn-sm" onclick="filterPayments('all')">All</button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterPayments('component')">Component
                    Payments</button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    onclick="filterPayments('recent')">Recent</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="payment-history-table">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Fee Component</th>
                            <th>Payment Method</th>
                            <th class="text-right">Amount</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentHistory ?? [] as $payment)
                            <tr class="payment-row" data-type="component" data-date="{{ $payment->payment_date }}">
                                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</td>
                                <td>
                                    <span class="font-weight-bold">
                                        {{ $payment->studentFee?->feeCategory?->name ?? 'N/A' }}
                                    </span>
                                    @if($payment->notes)
                                        <br><small class="text-muted">{{ Str::limit($payment->notes, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ ucfirst($payment->payment_method) }}</span>
                                </td>
                                <td class="text-right font-weight-bold text-success">
                                    ₹{{ number_format($payment->amount, 2) }}
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $payment->transaction_reference ?? 'N/A' }}
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-success">Completed</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.component-payments.show', $payment) }}"
                                            class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(isset($payment->receipt_number))
                                            <a href="{{ route('public.receipt.show', $payment->receipt_number) }}"
                                                class="btn btn-outline-info btn-sm" target="_blank">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-outline-info btn-sm" onclick="window.print()" title="Print Page">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="py-4">
                                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No Payment History</h6>
                                        <p class="text-muted">No payments have been recorded for this student yet.</p>
                                        <a href="{{ route('admin.component-payments.create', ['student_id' => $student->id]) }}"
                                            class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Record First Payment
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Activity Timeline --}}
    @if(isset($recentActivity) && $recentActivity->count() > 0)
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-secondary">
                    <i class="fas fa-clock"></i> Recent Financial Activity
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($recentActivity->take(10) as $activity)
                        <div class="timeline-item">
                            <div
                                class="timeline-marker bg-{{ ($activity->properties['type'] ?? 'default') === 'payment' ? 'success' : 'primary' }}">
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">{{ $activity->description }}</h6>
                                <p class="text-muted mb-1">{{ $activity->created_at->diffForHumans() }}</p>
                                <small class="text-muted">by {{ $activity->causer->name ?? 'System' }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ================= MODALS ================= --}}

    {{-- Apply Concession Modal --}}
    @include('admin.payments.partials.concession-modal')

    {{-- Record Payment Modal --}}
    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment for {{ $student->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="recordPaymentForm" action="{{ route('admin.component-payments.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $student->id }}">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_amount">Payment Amount*</label>
                                    <input type="number" step="0.01" name="amount" id="payment_amount" class="form-control"
                                        required min="0.01">
                                    <small class="text-muted">Enter the total payment amount</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_method">Payment Method*</label>
                                    <select name="payment_method" id="payment_method" class="form-control" required>
                                        <option value="">Select Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="upi">UPI</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="online">Online</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_date">Payment Date*</label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="transaction_reference">Transaction Reference</label>
                                    <input type="text" name="transaction_reference" id="transaction_reference"
                                        class="form-control" placeholder="Optional">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="font-weight-bold">Select Fee Components to Pay:</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                @if(isset($studentFees))
                                    @foreach($studentFees as $fee)
                                        @php $remaining = $fee->amount - $fee->paid_amount - $fee->concession_amount; @endphp
                                        @if($remaining > 0)
                                            <div class="form-check mb-2 fee-component-item" data-max-amount="{{ $remaining }}">
                                                <input class="form-check-input fee-component-checkbox" type="checkbox"
                                                    name="components[{{ $fee->id }}][selected]" value="1" id="component_{{ $fee->id }}">
                                                <label class="form-check-label d-flex justify-content-between align-items-center w-100"
                                                    for="component_{{ $fee->id }}">
                                                    <div>
                                                        <strong>{{ $fee->feeCategory->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            Due: ₹{{ number_format($remaining, 2) }}
                                                            @if($fee->due_date)
                                                                | Due Date: {{ \Carbon\Carbon::parse($fee->due_date)->format('d M, Y') }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                    <div class="text-right">
                                                        <input type="number" step="0.01" name="components[{{ $fee->id }}][amount]"
                                                            class="form-control form-control-sm component-amount-input"
                                                            placeholder="Amount" style="width: 120px;" max="{{ $remaining }}" disabled>
                                                        <input type="hidden" name="components[{{ $fee->id }}][student_fee_id]"
                                                            value="{{ $fee->id }}">
                                                    </div>
                                                </label>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-info-circle"></i> No unpaid fee components found.
                                    </div>
                                @endif
                            </div>
                            <small class="text-info mt-2 d-block">
                                <i class="fas fa-info-circle"></i>
                                Select components and enter amounts. Total should match the payment amount above.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="payment_notes">Notes</label>
                            <textarea name="notes" id="payment_notes" class="form-control" rows="3"
                                placeholder="Optional payment notes"></textarea>
                        </div>

                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Payment Amount:</strong> <span id="payment_total_display">₹0.00</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Components Total:</strong> <span id="components_total_display">₹0.00</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small id="payment_validation_message" class="text-muted">
                                    Enter payment amount and select components to allocate the payment.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-success" id="record_payment_submit">
                                <i class="fas fa-credit-card"></i> Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Make functions globally available by defining them outside of document.ready
        function openQuickPayment(studentFeeId, componentName, remainingAmount) {
            $('#modal_student_fee_id').val(studentFeeId);
            $('#modal_component_name').text(componentName);
            $('#modal_remaining_amount').text(remainingAmount.toLocaleString());
            $('#modal_amount').val(remainingAmount);
            $('#modal_amount').attr('max', remainingAmount);
            $('#quickPaymentModal').modal('show');
        }

        function showAlert(type, message) {
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}<button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>`;
            $('.container-fluid').prepend(alertHtml);
            setTimeout(() => $('.alert').fadeOut(), 5000);
        }

        $(document).ready(function () {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Auto-refresh component summary if there are pending payments
            const hasPendingPayments = $('.text-danger').length > 0;
            if (hasPendingPayments) {
                setInterval(refreshComponentSummary, 60000); // Refresh every minute
            }
        });

        function refreshComponentSummary() {
            // Add loading state
            $('#component-summary').prepend(`
            <div class="col-12 text-center loading-overlay">
                <div class="spinner-border text-info" role="status">
                    <span class="sr-only">Refreshing...</span>
                </div>
            </div>
        `);

            // Reload the page to get fresh data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Record Payment Modal Logic
        document.addEventListener('DOMContentLoaded', function () {
            const paymentAmountInput = document.getElementById('payment_amount');
            const componentCheckboxes = document.querySelectorAll('.fee-component-checkbox');
            const componentAmountInputs = document.querySelectorAll('.component-amount-input');
            const paymentTotalDisplay = document.getElementById('payment_total_display');
            const componentsTotalDisplay = document.getElementById('components_total_display');
            const validationMessage = document.getElementById('payment_validation_message');
            const submitButton = document.getElementById('record_payment_submit');
            const recordPaymentForm = document.getElementById('recordPaymentForm');

            function updateTotals() {
                const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
                let componentsTotal = 0;

                componentAmountInputs.forEach(input => {
                    if (!input.disabled && input.value) {
                        componentsTotal += parseFloat(input.value) || 0;
                    }
                });

                paymentTotalDisplay.textContent = '₹' + paymentAmount.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                componentsTotalDisplay.textContent = '₹' + componentsTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });

                // Validation logic
                if (paymentAmount === 0) {
                    validationMessage.textContent = 'Enter payment amount and select components to allocate the payment.';
                    validationMessage.className = 'text-muted';
                    submitButton.disabled = true;
                } else if (componentsTotal === 0) {
                    validationMessage.textContent = 'Please select at least one fee component and enter amount.';
                    validationMessage.className = 'text-warning';
                    submitButton.disabled = true;
                } else if (Math.abs(paymentAmount - componentsTotal) > 0.01) {
                    validationMessage.textContent = `Payment amount (₹${paymentAmount}) must equal components total (₹${componentsTotal}).`;
                    validationMessage.className = 'text-danger';
                    submitButton.disabled = true;
                } else {
                    validationMessage.textContent = 'Payment allocation is correct. Ready to record payment.';
                    validationMessage.className = 'text-success';
                    submitButton.disabled = false;
                }
            }

            // Enable/disable amount inputs based on checkbox selection
            componentCheckboxes.forEach((checkbox, index) => {
                const amountInput = componentAmountInputs[index];
                const maxAmount = parseFloat(checkbox.closest('.fee-component-item').dataset.maxAmount);

                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        amountInput.disabled = false;
                        amountInput.focus();
                        // Auto-fill with remaining amount if payment amount is set
                        if (!amountInput.value && paymentAmountInput.value) {
                            const remainingPayment = parseFloat(paymentAmountInput.value) - getCurrentComponentsTotal();
                            if (remainingPayment > 0) {
                                amountInput.value = Math.min(remainingPayment, maxAmount).toFixed(2);
                            }
                        }
                    } else {
                        amountInput.disabled = true;
                        amountInput.value = '';
                    }
                    updateTotals();
                });

                amountInput.addEventListener('input', function () {
                    const value = parseFloat(this.value) || 0;
                    if (value > maxAmount) {
                        this.setCustomValidity(`Amount cannot exceed ₹${maxAmount.toLocaleString()}`);
                        this.classList.add('is-invalid');
                    } else {
                        this.setCustomValidity('');
                        this.classList.remove('is-invalid');
                    }
                    updateTotals();
                });
            });

            paymentAmountInput.addEventListener('input', updateTotals);

            function getCurrentComponentsTotal() {
                let total = 0;
                componentAmountInputs.forEach(input => {
                    if (!input.disabled && input.value) {
                        total += parseFloat(input.value) || 0;
                    }
                });
                return total;
            }

            // Auto-distribute payment amount
            window.autoDistributePayment = function () {
                const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
                if (paymentAmount <= 0) {
                    alert('Please enter a payment amount first.');
                    return;
                }

                let remainingAmount = paymentAmount;
                const checkedComponents = Array.from(componentCheckboxes).filter(cb => cb.checked);

                if (checkedComponents.length === 0) {
                    alert('Please select at least one fee component first.');
                    return;
                }

                checkedComponents.forEach((checkbox, index) => {
                    const amountInput = checkbox.closest('.fee-component-item').querySelector('.component-amount-input');
                    const maxAmount = parseFloat(checkbox.closest('.fee-component-item').dataset.maxAmount);

                    if (remainingAmount > 0) {
                        const allocateAmount = Math.min(remainingAmount, maxAmount);
                        amountInput.value = allocateAmount.toFixed(2);
                        remainingAmount -= allocateAmount;
                    }
                });

                updateTotals();
            };

            // Form submission
            recordPaymentForm.addEventListener('submit', function (e) {
                const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
                const componentsTotal = getCurrentComponentsTotal();

                if (Math.abs(paymentAmount - componentsTotal) > 0.01) {
                    e.preventDefault();
                    alert('Payment amount must equal the sum of component amounts.');
                    return false;
                }

                if (componentsTotal === 0) {
                    e.preventDefault();
                    alert('Please select at least one fee component and enter an amount.');
                    return false;
                }

                // Show loading state
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recording Payment...';
            });

            // Initialize totals
            updateTotals();
        });

        // Quick fill buttons for common scenarios
        window.payFullDues = function () {
            const checkedComponents = document.querySelectorAll('.fee-component-checkbox:checked');
            if (checkedComponents.length === 0) {
                alert('Please select fee components first.');
                return;
            }

            let totalDue = 0;
            checkedComponents.forEach(checkbox => {
                const maxAmount = parseFloat(checkbox.closest('.fee-component-item').dataset.maxAmount);
                totalDue += maxAmount;
            });

            document.getElementById('payment_amount').value = totalDue.toFixed(2);
            autoDistributePayment();
        };

        window.selectAllComponents = function () {
            const checkboxes = document.querySelectorAll('.fee-component-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change'));
            });
        };

        // Payment filtering
        function filterPayments(type) {
            const rows = document.querySelectorAll('.payment-row');
            const buttons = document.querySelectorAll('[onclick^="filterPayments"]');

            // Update button states
            buttons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            event.target.classList.remove('btn-outline-primary');
            event.target.classList.add('btn-primary');

            rows.forEach(row => {
                if (type === 'all') {
                    row.style.display = '';
                } else if (type === 'component') {
                    row.style.display = row.dataset.type === 'component' ? '' : 'none';
                } else if (type === 'recent') {
                    const paymentDate = new Date(row.dataset.date);
                    const thirtyDaysAgo = new Date();
                    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                    row.style.display = paymentDate >= thirtyDaysAgo ? '' : 'none';
                }
            });
        }

        // Concession modal logic - Direct application to individual components
        document.addEventListener('DOMContentLoaded', function () {
            const concessionComponentSelect = document.getElementById('concessionComponentSelect');
            const concessionAmountInput = document.getElementById('concession_amount');
            const concessionAmountHint = document.getElementById('concession_amount_hint');
            const concessionForm = document.getElementById('concessionForm');

            function updateConcessionInput() {
                const selectedOption = concessionComponentSelect.options[concessionComponentSelect.selectedIndex];
                const remainingAmount = selectedOption.getAttribute('data-remaining');

                if (remainingAmount) {
                    const maxAmount = parseFloat(remainingAmount);
                    concessionAmountInput.max = maxAmount;
                    concessionAmountHint.textContent = `Maximum: ₹${maxAmount.toLocaleString()}`;
                } else {
                    concessionAmountInput.max = null;
                    concessionAmountHint.textContent = 'Select a fee component first';
                }
            }

            // Update inputs when component selection changes
            if (concessionComponentSelect) {
                concessionComponentSelect.addEventListener('change', updateConcessionInput);
            }

            // Form validation
            if (concessionForm) {
                concessionForm.addEventListener('submit', function (e) {
                    const amount = parseFloat(concessionAmountInput.value);
                    const maxAmount = parseFloat(concessionAmountInput.max);

                    if (!amount || amount <= 0) {
                        e.preventDefault();
                        alert('Please enter a valid concession amount.');
                        return false;
                    }

                    if (maxAmount && amount > maxAmount) {
                        e.preventDefault();
                        alert(`Concession amount cannot exceed ₹${maxAmount.toLocaleString()}`);
                        return false;
                    }

                    // Show loading state
                    const submitBtn = concessionForm.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying Concession...';
                });
            }

            // Real-time validation for concession amount
            if (concessionAmountInput) {
                concessionAmountInput.addEventListener('input', function () {
                    const amount = parseFloat(this.value);
                    const maxAmount = parseFloat(this.max);

                    if (amount > maxAmount && maxAmount) {
                        this.setCustomValidity(`Amount cannot exceed ₹${maxAmount.toLocaleString()}`);
                        this.classList.add('is-invalid');
                    } else if (amount <= 0) {
                        this.setCustomValidity('Amount must be greater than 0');
                        this.classList.add('is-invalid');
                    } else {
                        this.setCustomValidity('');
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
            }
        });

        // Generate financial report
        function generateFinancialReport() {
            // Use existing financial reports route or print functionality
            const reportUrl = `{{ route('admin.reports.financial.show') }}?student_id={{ $student->id }}`;
            window.open(reportUrl, '_blank');
        }

        // Generate receipt for payment
        function generateReceipt(paymentId) {
            // Use the public receipt route which exists
            const receiptUrl = `{{ url('admin/component-payments') }}/${paymentId}`;
            window.open(receiptUrl, '_blank');
        }

        // Enhanced error handling for forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function (e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                }
            });
        });

        // Print functionality for financial statements
        function printFinancialStatement() {
            const printWindow = window.open('', '_blank');
            const content = document.querySelector('.container-fluid').innerHTML;

            printWindow.document.write(`
            <html>
            <head>
                <title>Financial Statement - {{ $student->name }}</title>
                <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        .btn, .modal, .card-header .btn-group { display: none !important; }
                        .card { border: 1px solid #dee2e6 !important; page-break-inside: avoid; }
                        .component-card { margin-bottom: 10px !important; }
                    }
                    body { font-size: 12px; }
                    .h3 { font-size: 18px; }
                    .h5, .h6 { font-size: 14px; }
                </style>
            </head>
            <body>
                <div class="container-fluid">${content}</div>
                <div class="text-center mt-4">
                    <small class="text-muted">Generated on {{ date('d M, Y H:i:s') }}</small>
                </div>
            </body>
            </html>
        `);

            printWindow.document.close();
            printWindow.print();
        }

        // Keyboard shortcuts for quick actions
        document.addEventListener('keydown', function (e) {
            // Ctrl/Cmd + P for quick payment
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.location.href = "{{ route('admin.component-payments.create', ['student_id' => $student->id]) }}";
            }

            // Ctrl/Cmd + R for refresh summary
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                refreshComponentSummary();
            }

            // Ctrl/Cmd + D for dashboard
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                window.location.href = "{{ route('admin.payments.component-dashboard', $student) }}";
            }
        });

        // Component interaction enhancements
        $('.component-card').hover(
            function () {
                $(this).addClass('shadow-lg');
            },
            function () {
                $(this).removeClass('shadow-lg');
            }
        );

        // Progressive enhancement for better UX
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fadeInUp');
                    }
                });
            });

            document.querySelectorAll('.card').forEach(card => {
                observer.observe(card);
            });
        });

        // Real-time validation for payment amounts
        document.addEventListener('input', function (e) {
            if (e.target.name === 'amount' || e.target.id === 'quick_amount') {
                const amount = parseFloat(e.target.value);
                const max = parseFloat(e.target.max);

                if (max && amount > max) {
                    e.target.setCustomValidity(`Amount cannot exceed ${max.toLocaleString()}`);
                    e.target.classList.add('is-invalid');
                } else if (amount <= 0) {
                    e.target.setCustomValidity('Amount must be greater than 0');
                    e.target.classList.add('is-invalid');
                } else {
                    e.target.setCustomValidity('');
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                }
            }
        });

        // Enhanced table interactions
        $('#payment-history-table tbody tr').hover(
            function () {
                $(this).addClass('table-active');
            },
            function () {
                $(this).removeClass('table-active');
            }
        );

        // Context menu for payment rows (right-click)
        $('#payment-history-table tbody tr').on('contextmenu', function (e) {
            e.preventDefault();
            const paymentId = $(this).data('payment-id');
            if (paymentId) {
                const contextMenu = $(`
                <div class="dropdown-menu show" style="position: fixed; top: ${e.pageY}px; left: ${e.pageX}px; z-index: 9999;">
                    <a class="dropdown-item" href="{{ route('admin.component-payments.show', ':id') }}".replace(':id', '${paymentId}')">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                    <a class="dropdown-item" href="#" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Statement
                    </a>
                </div>
            `);

                $('body').append(contextMenu);

                $(document).one('click', function () {
                    contextMenu.remove();
                });
            }
        });

        // Smooth scrolling for anchor links
        $('a[href^="#"]').on('click', function (event) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 80
                }, 1000);
            }
        });

        // Format currency inputs automatically
        function formatCurrencyInput(input) {
            let value = input.value.replace(/[^\d.]/g, '');
            if (value) {
                const number = parseFloat(value);
                if (!isNaN(number)) {
                    input.value = number.toFixed(2);
                }
            }
        }

        // Apply currency formatting on blur
        document.addEventListener('blur', function (e) {
            if (e.target.type === 'number' && e.target.step === '0.01') {
                formatCurrencyInput(e.target);
            }
        }, true);

        // Advanced search functionality for payment history
        function searchPayments() {
            const searchTerm = document.getElementById('payment-search')?.value?.toLowerCase();
            if (!searchTerm) return;

            const rows = document.querySelectorAll('#payment-history-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }

        // Export functionality
        function exportPaymentHistory() {
            const table = document.getElementById('payment-history-table');
            const rows = Array.from(table.querySelectorAll('tr:not([style*="display: none"])'));

            let csv = '';
            rows.forEach(row => {
                const cells = Array.from(row.cells).slice(0, -1); // Exclude actions column
                const rowData = cells.map(cell => `"${cell.textContent.trim()}"`).join(',');
                csv += rowData + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `{{ $student->name }}_payment_history_{{ date('Y-m-d') }}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Component analytics tracking (for admin insights)
        function trackComponentInteraction(action, componentName) {
            // Track component interactions for analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', action, {
                    event_category: 'fee_component',
                    event_label: componentName,
                    student_id: '{{ $student->id }}'
                });
            }
        }

        // Initialize component click tracking
        $('.component-card').on('click', function () {
            const componentName = $(this).find('h6').text().trim();
            trackComponentInteraction('component_card_click', componentName);
        });

        $('[onclick^="quickPayComponent"]').on('click', function () {
            const componentName = $(this).closest('.component-card').find('h6').text().trim();
            trackComponentInteraction('quick_pay_click', componentName);
        });

        console.log('Financial Ledger initialized with component-based payment system');
    </script>
@endpush

{{-- Enhanced CSS for component-based design --}}
@push('styles')
    <style>
        /* Component card enhancements */
        .component-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .component-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .component-card .progress {
            border-radius: 10px;
            overflow: hidden;
            background-color: #e9ecef;
        }

        .component-card .progress-bar {
            transition: width 0.6s ease;
        }

        /* Enhanced status indicators */
        .border-left-success {
            border-left: 5px solid #28a745 !important;
        }

        .border-left-warning {
            border-left: 5px solid #ffc107 !important;
        }

        .border-left-danger {
            border-left: 5px solid #dc3545 !important;
        }

        .border-left-info {
            border-left: 5px solid #17a2b8 !important;
        }

        .border-left-primary {
            border-left: 5px solid #007bff !important;
        }

        /* Table enhancements */
        #payment-history-table {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        #payment-history-table thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        #payment-history-table tbody tr {
            transition: all 0.2s ease;
        }

        #payment-history-table tbody tr:hover {
            background-color: #f8f9fc;
            transform: scale(1.01);
        }

        /* Modal enhancements */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-header .close {
            color: white;
            opacity: 0.8;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        /* Loading states */
        .loading-overlay {
            position: relative;
            z-index: 1000;
        }

        .spinner-border {
            width: 2rem;
            height: 2rem;
        }

        /* Form enhancements */
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-control.is-valid {
            border-color: #28a745;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        /* Button enhancements */
        .btn {
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }

        /* Timeline styles for activity */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #007bff, #6f42c1);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-marker {
            position: absolute;
            left: -2.25rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            border-left: 3px solid #007bff;
        }

        /* Responsive enhancements */
        @media (max-width: 768px) {
            .component-card {
                margin-bottom: 1rem;
            }

            .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn-group .btn {
                margin-bottom: 0.5rem;
                border-radius: 0.5rem !important;
            }

            .timeline {
                padding-left: 1.5rem;
            }

            .timeline-marker {
                left: -1.75rem;
            }

            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
        }

        @media (max-width: 576px) {
            .card-body .row .col-md-3 {
                margin-bottom: 1rem;
            }

            .table-responsive {
                font-size: 0.875rem;
            }

            .btn-group-sm .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }

        /* Animation classes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Print styles */
        @media print {

            .btn,
            .modal,
            .card-header .btn-group,
            .dropdown-menu,
            .loading-overlay {
                display: none !important;
            }

            .card {
                border: 1px solid #dee2e6 !important;
                page-break-inside: avoid;
                margin-bottom: 1rem !important;
            }

            .component-card {
                margin-bottom: 0.5rem !important;
                box-shadow: none !important;
            }

            body {
                font-size: 12px;
                line-height: 1.4;
            }

            .h1,
            .h2,
            .h3 {
                font-size: 16px;
            }

            .h4,
            .h5,
            .h6 {
                font-size: 14px;
            }

            .table {
                font-size: 11px;
            }

            .badge {
                border: 1px solid #000;
                background: transparent !important;
                color: #000 !important;
            }
        }

        /* Accessibility improvements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus indicators */
        .btn:focus,
        .form-control:focus,
        .custom-select:focus {
            outline: 2px solid #007bff;
            outline-offset: 2px;
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .card {
                border: 2px solid #000 !important;
            }

            .btn {
                border: 2px solid currentColor;
            }

            .badge {
                border: 1px solid currentColor;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
@endpush