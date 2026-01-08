@extends('layouts.theme')

@section('title', 'Record Component Payment - ' . $student->name)

@push('styles')
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --warning-gradient: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .page-header-modern {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .page-header-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
        }

        .page-header-modern .content {
            position: relative;
            z-index: 2;
        }

        .modern-card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
        }

        .modern-card:hover {
            box-shadow: var(--shadow-medium);
            transform: translateY(-2px);
        }

        .modern-card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            font-weight: 600;
            color: #495057;
            border-radius: 16px 16px 0 0;
        }

        .component-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .component-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .component-checkbox {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .component-checkbox:checked {
            background: var(--primary-gradient);
            border-color: #667eea;
        }

        .modern-input,
        .modern-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .modern-input:focus,
        .modern-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }

        .btn-gradient {
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-gradient:hover::before {
            left: 100%;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-gradient-primary {
            background: var(--primary-gradient);
        }

        .btn-gradient-success {
            background: var(--success-gradient);
        }

        .btn-gradient-danger {
            background: var(--danger-gradient);
        }

        .btn-gradient-info {
            background: var(--info-gradient);
        }

        .btn-gradient-warning {
            background: var(--warning-gradient);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            border-left: 4px solid;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .stats-card.danger {
            border-left-color: #fa709a;
        }

        .stats-card.warning {
            border-left-color: #fcb69f;
        }

        .stats-card.info {
            border-left-color: #a8edea;
        }

        .payment-summary {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 2px solid rgba(102, 126, 234, 0.2);
        }

        .component-details {
            background: rgba(248, 249, 250, 0.5);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .fee-breakdown-item {
            background: white;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.overdue {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
        }

        .status-badge.due-soon {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #8b4513;
        }

        .recent-payment-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .recent-payment-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }

        @media (max-width: 768px) {
            .page-header-modern {
                padding: 1rem;
            }

            .component-card {
                padding: 1rem;
            }

            .modern-card {
                margin-bottom: 1rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Modern Page Header -->
        <div class="page-header-modern">
            <div class="content">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">Record Component Payment</h1>
                        <p class="mb-0 opacity-90">Process payment for {{ $student->name }}
                            ({{ $student->enrollment_number }})</p>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('admin.component-payments.index') }}" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Payments
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper bg-primary me-3"
                                style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-credit-card text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Payment Information</h5>
                                <small class="text-muted">Select components and enter payment details</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger rounded-3 mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Please fix the following errors:</strong>
                                </div>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('admin.component-payments.store') }}" method="POST" id="paymentForm">
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $student->id }}">

                            <!-- Component Selection Section -->
                            <div class="mb-4">
                                <h6 class="text-uppercase text-muted mb-3 fw-bold">
                                    <i class="fas fa-list-check me-2"></i>Select Fee Components
                                </h6>

                                @if(isset($unpaidFees) && $unpaidFees->count() > 0)
                                    @foreach($unpaidFees->groupBy('fee_category_id') as $categoryId => $fees)
                                        @php

                                            // Add checks to ensure both the fee and its category exist before proceeding.
                                            if (!$firstFee || !$firstFee->feeCategory) {
                                                continue;
                                            }

                                            $category = $firstFee->feeCategory;
                                            $totalAmount = $fees->sum('amount') ?? 0;
                                            $totalPaid = $fees->sum('paid_amount') ?? 0;
                                            $totalConcession = $fees->sum('concession_amount') ?? 0;
                                            $totalRemaining = $totalAmount - $totalPaid - $totalConcession;

                                            if ($totalRemaining <= 0)
                                                continue; // Skip if nothing remaining to pay

                                            $hasOverdue = $fees->some(function ($fee) {
                                                return $fee->due_date && $fee->due_date < now() && $fee->status !== 'paid';
                                            });
                                        @endphp

                                        <div class="component-card" data-category-id="{{ $categoryId }}">
                                            <div class="d-flex align-items-start">
                                                <div class="form-check me-3 mt-1">
                                                    <input type="checkbox" class="form-check-input component-checkbox"
                                                        id="component_{{ $categoryId }}"
                                                        name="components[{{ $categoryId }}][selected]" value="1">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <label class="form-check-label fw-bold"
                                                                for="component_{{ $categoryId }}">
                                                                {{ $category->name }}
                                                            </label>
                                                            @if($hasOverdue)
                                                                <span class="status-badge overdue ms-2">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-primary">
                                                                ₹{{ number_format($totalRemaining, 2) }}</div>
                                                            <small class="text-muted">remaining</small>
                                                        </div>
                                                    </div>

                                                    <div class="component-details" style="display: none;">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Amount to Pay*</label>
                                                                <input type="hidden"
                                                                    name="components[{{ $categoryId }}][fee_category_id]"
                                                                    value="{{ $categoryId }}">
                                                                <div class="input-group">
                                                                    <span class="input-group-text">₹</span>
                                                                    <input type="number"
                                                                        class="form-control modern-input amount-input"
                                                                        name="components[{{ $categoryId }}][amount]" min="0.01"
                                                                        max="{{ $totalRemaining }}" step="0.01" placeholder="0.00">
                                                                </div>
                                                                <small class="text-muted">Maximum:
                                                                    ₹{{ number_format($totalRemaining, 2) }}</small>

                                                                <div class="mt-2">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary pay-full-btn"
                                                                        data-amount="{{ $totalRemaining }}">
                                                                        Pay Full Amount
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Fee Breakdown</label>
                                                                <div class="fee-breakdown">
                                                                    @foreach($fees as $fee)
                                                                        @php
                                                                            $remaining = ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0);
                                                                        @endphp
                                                                        @if($remaining > 0)
                                                                            <div class="fee-breakdown-item">
                                                                                <div class="flex-grow-1">
                                                                                    <div class="small fw-semibold">
                                                                                        @if($fee->installment_number)
                                                                                            Installment {{ $fee->installment_number }}
                                                                                        @else
                                                                                            {{ $category->name ?? 'Fee Payment' }}
                                                                                        @endif
                                                                                    </div>
                                                                                    <div class="small text-muted">
                                                                                        Due:
                                                                                        {{ $fee->due_date ? $fee->due_date->format('d M Y') : 'N/A' }}
                                                                                    </div>
                                                                                </div>
                                                                                <div class="text-end">
                                                                                    <div class="small fw-bold">
                                                                                        ₹{{ number_format($remaining, 2) }}</div>
                                                                                    @if($fee->due_date && $fee->due_date < now())
                                                                                        <span class="status-badge overdue">Overdue</span>
                                                                                    @elseif($fee->due_date && $fee->due_date->diffInDays(now()) <= 7)
                                                                                        <span class="status-badge due-soon">Due Soon</span>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-5">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <h5>All fees are paid!</h5>
                                        <p class="text-muted">This student has no outstanding fee components.</p>
                                        <a href="{{ route('admin.students.show', $student) }}"
                                            class="btn btn-gradient btn-gradient-primary">
                                            View Student Details
                                        </a>
                                    </div>
                                @endif
                            </div>

                            @if(isset($unpaidFees) && $unpaidFees->count() > 0)
                                <!-- Payment Details Section -->
                                <div class="mb-4">
                                    <h6 class="text-uppercase text-muted mb-3 fw-bold">
                                        <i class="fas fa-credit-card me-2"></i>Payment Details
                                    </h6>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="payment_method" class="form-label fw-bold">Payment Method*</label>
                                                <select name="payment_method" id="payment_method"
                                                    class="form-control modern-select" required>
                                                    <option value="">Select Payment Method</option>
                                                    <option value="cash">Cash Payment</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                    <option value="online">Online Payment</option>
                                                    <option value="cheque">Cheque</option>
                                                    <option value="dd">Demand Draft</option>
                                                    <option value="upi">UPI Payment</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="transaction_id" class="form-label fw-bold">Transaction
                                                    Reference</label>
                                                <input type="text" name="transaction_id" id="transaction_id"
                                                    class="form-control modern-input"
                                                    placeholder="Enter transaction ID or reference">
                                                <small class="text-muted">Optional for cash payments</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label fw-bold">Payment Date*</label>
                                        <input type="date" name="payment_date" id="payment_date"
                                            class="form-control modern-input" value="{{ date('Y-m-d') }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label fw-bold">Additional Notes</label>
                                        <textarea name="notes" id="notes" class="form-control modern-input" rows="3"
                                            placeholder="Add any additional notes about this payment (optional)"></textarea>
                                    </div>
                                </div>

                                <!-- Payment Summary -->
                                <div class="payment-summary">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">Total Payment Amount</h6>
                                            <small class="text-muted">Amount will be processed for selected components</small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="h3 mb-0 fw-bold text-primary" id="totalAmount">₹0.00</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between pt-3">
                                    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-gradient btn-gradient-success" id="submitBtn" disabled>
                                        <i class="fas fa-save me-2"></i>Record Payment
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <!-- Student Summary Sidebar -->
            <div class="col-lg-4">
                <!-- Student Information Card -->
                <div class="modern-card mb-4">
                    <div class="modern-card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>Student Information
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-lg bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                style="width: 80px; height: 80px; font-size: 2rem;">
                                {{ substr($student->name, 0, 1) }}
                            </div>
                            <h5 class="mb-1">{{ $student->name }}</h5>
                            <p class="text-muted mb-1">{{ $student->enrollment_number }}</p>
                            <small class="text-muted">
                                {{ $student->batch?->course?->name ?? 'N/A' }} - {{ $student->batch?->name ?? 'N/A' }}
                            </small>
                        </div>

                        @php
                            $studentFees = $student->studentFees()
                                ->whereIn('status', ['unpaid', 'partial'])
                                ->with('feeCategory')
                                ->get();

                            $totalOutstanding = $studentFees->sum(function ($fee) {
                                return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
                            });

                            $overdueAmount = $studentFees->filter(function ($fee) {
                                return $fee->due_date && $fee->due_date < now();
                            })->sum(function ($fee) {
                                return max(0, ($fee->amount ?? 0) - ($fee->paid_amount ?? 0) - ($fee->concession_amount ?? 0));
                            });

                            $totalPaid = $studentFees->sum('paid_amount') ?? 0;
                        @endphp

                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stats-card danger text-center">
                                    <div class="h5 text-danger mb-1">₹{{ number_format($totalOutstanding, 2) }}</div>
                                    <div class="small text-muted">Outstanding</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stats-card warning text-center">
                                    <div class="h5 text-warning mb-1">₹{{ number_format($overdueAmount, 2) }}</div>
                                    <div class="small text-muted">Overdue</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stats-card info text-center">
                                    <div class="h5 text-success mb-1">₹{{ number_format($totalPaid, 2) }}</div>
                                    <div class="small text-muted">Paid</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments Card -->
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recent Payments
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        @if($recentPayments->count() > 0)
                            @foreach($recentPayments as $payment)
                                <div class="recent-payment-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold text-success">₹{{ number_format($payment->amount ?? 0, 2) }}</div>
                                            <div class="small text-muted">
                                                {{ $payment->payment_date ? $payment->payment_date->format('d M Y') : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="small fw-semibold">#{{ $payment->receipt_number ?? 'N/A' }}</div>
                                            <div class="small text-muted text-capitalize">
                                                {{ $payment->payment_method ? str_replace('_', ' ', $payment->payment_method) : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-2x text-muted mb-3"></i>
                                <div class="text-muted">No recent payments found</div>
                                <small class="text-muted">This will be the first payment for this student</small>
                            </div>
                        @endif

                        @if($recentPayments->count() > 0)
                            <div class="text-center mt-3">
                                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-outline-primary">
                                    View All Payments
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Handle component selection
            $('.component-checkbox').change(function () {
                const componentCard = $(this).closest('.component-card');
                const details = componentCard.find('.component-details');
                const amountInput = details.find('.amount-input');

                if ($(this).is(':checked')) {
                    componentCard.addClass('selected');
                    details.slideDown(300);
                    amountInput.attr('required', true);
                } else {
                    componentCard.removeClass('selected');
                    details.slideUp(300);
                    amountInput.attr('required', false).val('');
                }

                updateTotal();
            });

            // Handle amount input changes
            $('.amount-input').on('input', function () {
                updateTotal();

                // Add visual feedback for valid amounts
                const value = parseFloat($(this).val()) || 0;
                const max = parseFloat($(this).attr('max')) || 0;

                if (value > max) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Pay full amount button
            $('.pay-full-btn').click(function () {
                const amount = $(this).data('amount');
                const input = $(this).closest('.component-details').find('.amount-input');
                input.val(amount).trigger('input');
            });

            // Update total amount calculation
            function updateTotal() {
                let total = 0;
                let hasSelectedComponents = false;
                let hasValidAmounts = true;

                $('.component-checkbox:checked').each(function () {
                    hasSelectedComponents = true;
                    const amountInput = $(this).closest('.component-card').find('.amount-input');
                    const amount = parseFloat(amountInput.val()) || 0;
                    const max = parseFloat(amountInput.attr('max')) || 0;

                    if (amount <= 0 || amount > max) {
                        hasValidAmounts = false;
                    }

                    total += amount;
                });

                // Update total display with animation
                $('#totalAmount').text('₹' + total.toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                // Enable/disable submit button
                const isValid = hasSelectedComponents && hasValidAmounts && total > 0;
                $('#submitBtn').prop('disabled', !isValid);

                if (isValid) {
                    $('#submitBtn').removeClass('btn-outline-success').addClass('btn-gradient-success');
                } else {
                    $('#submitBtn').removeClass('btn-gradient-success').addClass('btn-outline-success');
                }
            }

            // Form validation before submission
            $('#paymentForm').submit(function (e) {
                const selectedComponents = $('.component-checkbox:checked').length;

                if (selectedComponents === 0) {
                    e.preventDefault();
                    showAlert('Please select at least one fee component to proceed.', 'warning');
                    return false;
                }

                let totalAmount = 0;
                let hasInvalidAmounts = false;

                $('.component-checkbox:checked').each(function () {
                    const amountInput = $(this).closest('.component-card').find('.amount-input');
                    const amount = parseFloat(amountInput.val()) || 0;
                    const max = parseFloat(amountInput.attr('max')) || 0;

                    if (amount <= 0) {
                        hasInvalidAmounts = true;
                        amountInput.focus();
                        return false;
                    }

                    if (amount > max) {
                        hasInvalidAmounts = true;
                        amountInput.focus();
                        return false;
                    }

                    totalAmount += amount;
                });

                if (hasInvalidAmounts) {
                    e.preventDefault();
                    showAlert('Please enter valid payment amounts for all selected components.', 'error');
                    return false;
                }

                if (totalAmount <= 0) {
                    e.preventDefault();
                    showAlert('Total payment amount must be greater than zero.', 'error');
                    return false;
                }

                // Show loading state
                $('#submitBtn').html('<i class="fas fa-spinner fa-spin me-2"></i>Processing Payment...').prop('disabled', true);

                return true;
            });

            // Payment method change handler
            $('#payment_method').change(function () {
                const method = $(this).val();
                const transactionField = $('#transaction_id');
                const transactionLabel = $('label[for="transaction_id"]');

                // Update field requirements based on payment method
                if (method === 'cash') {
                    transactionField.attr('placeholder', 'Cash receipt number (optional)');
                    transactionLabel.html('Receipt Reference <small class="text-muted">(Optional)</small>');
                } else if (method === 'online' || method === 'upi') {
                    transactionField.attr('placeholder', 'Transaction ID or UPI reference');
                    transactionLabel.html('Transaction Reference <small class="text-danger">*</small>');
                    transactionField.attr('required', true);
                } else if (method === 'cheque') {
                    transactionField.attr('placeholder', 'Cheque number');
                    transactionLabel.html('Cheque Number <small class="text-danger">*</small>');
                    transactionField.attr('required', true);
                } else if (method === 'dd') {
                    transactionField.attr('placeholder', 'DD number');
                    transactionLabel.html('DD Number <small class="text-danger">*</small>');
                    transactionField.attr('required', true);
                } else if (method === 'bank_transfer') {
                    transactionField.attr('placeholder', 'Transfer reference number');
                    transactionLabel.html('Transfer Reference <small class="text-danger">*</small>');
                    transactionField.attr('required', true);
                } else {
                    transactionField.attr('placeholder', 'Transaction reference');
                    transactionLabel.html('Transaction Reference');
                    transactionField.removeAttr('required');
                }
            });

            // Auto-focus on first amount input when component is selected
            $('.component-checkbox').change(function () {
                if ($(this).is(':checked')) {
                    setTimeout(() => {
                        $(this).closest('.component-card').find('.amount-input').focus();
                    }, 300);
                }
            });

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Keyboard shortcuts
            $(document).keydown(function (e) {
                // Ctrl/Cmd + Enter to submit form
                if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
                    if (!$('#submitBtn').prop('disabled')) {
                        $('#paymentForm').submit();
                    }
                }

                // Escape to cancel
                if (e.keyCode === 27) {
                    window.location.href = "{{ route('admin.students.show', $student) }}";
                }
            });
        });

        // Custom alert function with modern styling
        function showAlert(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' :
                type === 'error' ? 'alert-danger' :
                    type === 'warning' ? 'alert-warning' : 'alert-info';

            const alert = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                     role="alert" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 10px;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);

            $('body').append(alert);

            setTimeout(() => {
                alert.alert('close');
            }, 5000);
        }

        // Real-time validation feedback
        $('.modern-input, .modern-select').on('blur', function () {
            if ($(this).attr('required') && !$(this).val()) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        $('.modern-input, .modern-select').on('input change', function () {
            if ($(this).hasClass('is-invalid') && $(this).val()) {
                $(this).removeClass('is-invalid');
            }
        });

        // Auto-save draft functionality (optional - stores in localStorage)
        function saveDraft() {
            const formData = {
                student_id: "{{ $student->id }}",
                selected_components: [],
                payment_method: $('#payment_method').val(),
                transaction_id: $('#transaction_id').val(),
                payment_date: $('#payment_date').val(),
                notes: $('#notes').val(),
                timestamp: new Date().toISOString()
            };

            $('.component-checkbox:checked').each(function () {
                const categoryId = $(this).closest('.component-card').data('category-id');
                const amount = $(this).closest('.component-card').find('.amount-input').val();

                formData.selected_components.push({
                    category_id: categoryId,
                    amount: amount
                });
            });

            try {
                localStorage.setItem('payment_draft_' + "{{ $student->id }}", JSON.stringify(formData));
            } catch (e) {
                console.warn('Could not save draft to localStorage:', e);
            }
        }

        // Load draft on page load
        function loadDraft() {
            try {
                const draftKey = 'payment_draft_' + "{{ $student->id }}";
                const draft = localStorage.getItem(draftKey);

                if (draft) {
                    const formData = JSON.parse(draft);
                    const draftAge = new Date() - new Date(formData.timestamp);

                    // Only load draft if it's less than 1 hour old
                    if (draftAge < 3600000) {
                        if (confirm('A draft payment form was found. Would you like to restore it?')) {
                            // Restore form data
                            $('#payment_method').val(formData.payment_method);
                            $('#transaction_id').val(formData.transaction_id);
                            $('#payment_date').val(formData.payment_date);
                            $('#notes').val(formData.notes);

                            // Restore selected components
                            formData.selected_components.forEach(function (component) {
                                const checkbox = $(`.component-card[data-category-id="${component.category_id}"] .component-checkbox`);
                                const amountInput = $(`.component-card[data-category-id="${component.category_id}"] .amount-input`);

                                checkbox.prop('checked', true).trigger('change');
                                amountInput.val(component.amount);
                            });

                            updateTotal();
                        } else {
                            localStorage.removeItem(draftKey);
                        }
                    } else {
                        localStorage.removeItem(draftKey);
                    }
                }
            } catch (e) {
                console.warn('Could not load draft from localStorage:', e);
            }
        }

        // Auto-save draft every 30 seconds
        setInterval(saveDraft, 30000);

        // Clear draft when form is successfully submitted
        $('#paymentForm').on('submit', function () {
            try {
                localStorage.removeItem('payment_draft_' + "{{ $student->id }}");
            } catch (e) {
                console.warn('Could not clear draft from localStorage:', e);
            }
        });

        // Load draft when page loads
        $(document).ready(function () {
            setTimeout(loadDraft, 1000); // Delay to ensure form is fully rendered
        });
    </script>
@endpush