@extends('layouts.theme')
@section('title', 'Payment Dashboard - ' . $student->name)

@push('styles')
<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        transform: rotate(45deg);
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
    }
    
    .metric-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--accent-color, #4e73df);
    }
    
    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .metric-card.success::before { background: #1cc88a; }
    .metric-card.warning::before { background: #f6c23e; }
    .metric-card.danger::before { background: #e74a3b; }
    .metric-card.info::before { background: #36b9cc; }
    
    .metric-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .metric-label {
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .component-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .component-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        border: 1px solid #e3e6f0;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .component-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    }
    
    .component-card.completed {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border-color: #28a745;
    }
    
    .component-card.overdue {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border-color: #dc3545;
    }
    
    .component-header {
        display: flex;
        justify-content: between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .component-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: #2d3748;
        margin: 0;
        flex: 1;
    }
    
    .component-status {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .progress-circle {
        position: relative;
        width: 80px;
        height: 80px;
        margin: 1rem auto;
    }
    
    .progress-circle svg {
        transform: rotate(-90deg);
    }
    
    .progress-circle .circle-bg {
        fill: none;
        stroke: #e9ecef;
        stroke-width: 8;
    }
    
    .progress-circle .circle-progress {
        fill: none;
        stroke: #28a745;
        stroke-width: 8;
        stroke-linecap: round;
        transition: stroke-dasharray 0.5s ease;
    }
    
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: 700;
        font-size: 0.875rem;
        color: #2d3748;
    }
    
    .activity-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        border: 1px solid #e3e6f0;
        overflow: hidden;
    }
    
    .activity-header {
        background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
        padding: 1.5rem;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .activity-timeline {
        padding: 1.5rem;
        max-height: 500px;
        overflow-y: auto;
    }
    
    .timeline-item {
        display: flex;
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 20px;
        top: 45px;
        bottom: -25px;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }
    
    .timeline-icon.success { background: #d4edda; color: #155724; }
    .timeline-icon.warning { background: #fff3cd; color: #856404; }
    .timeline-icon.info { background: #d1ecf1; color: #0c5460; }
    
    .timeline-content {
        flex: 1;
        background: #f8f9fc;
        border-radius: 10px;
        padding: 1rem;
        border-left: 4px solid #e9ecef;
    }
    
    .timeline-title {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    
    .timeline-meta {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }
    
    .timeline-details {
        font-size: 0.8rem;
        color: #495057;
        background: white;
        padding: 0.5rem;
        border-radius: 5px;
        border: 1px solid #e9ecef;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .btn-modern {
        border-radius: 25px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
        transition: all 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .btn-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .btn-modern:hover::before {
        left: 100%;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-item {
        text-align: center;
        padding: 1rem;
        background: white;
        border-radius: 10px;
        border: 1px solid #e9ecef;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .component-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .metric-value {
            font-size: 2rem;
        }
        
        .dashboard-hero {
            padding: 1.5rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Hero Section -->
    <div class="dashboard-hero">
        <div class="hero-content">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Payment Dashboard
                    </h1>
                    <h3 class="mb-1">{{ $student->name }}</h3>
                    <p class="mb-0 opacity-75">
                        {{ $student->enrollment_number }} | 
                        {{ $student->batch->name ?? 'No Batch' }} | 
                        {{ $student->batch->course->name ?? 'No Course' }}
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <div class="action-buttons">
                        <a href="{{ route('admin.students.show', $student) }}" class="btn btn-light btn-modern">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Student
                        </a>
                        <button type="button" class="btn btn-success btn-modern" data-toggle="modal" data-target="#quickPaymentModal">
                            <i class="fas fa-plus mr-1"></i> Quick Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Financial Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card info">
                <div class="metric-label">Total Billed</div>
                <div class="metric-value">{{ setting('currency_symbol','₹') }}{{ number_format($totalBilled, 0) }}</div>
                <small class="text-muted">Net amount after concessions</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card success">
                <div class="metric-label">Total Paid</div>
                <div class="metric-value">{{ setting('currency_symbol','₹') }}{{ number_format($totalPaid, 0) }}</div>
                <small class="text-muted">{{ $totalBilled > 0 ? round(($totalPaid / $totalBilled) * 100, 1) : 0 }}% of total billed</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card warning">
                <div class="metric-label">Concessions</div>
                <div class="metric-value">{{ setting('currency_symbol','₹') }}{{ number_format($totalConcession, 0) }}</div>
                <small class="text-muted">Applied discounts</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card danger">
                <div class="metric-label">Balance Due</div>
                <div class="metric-value">{{ setting('currency_symbol','₹') }}{{ number_format($balanceDue, 0) }}</div>
                <small class="text-muted">Remaining amount</small>
            </div>
        </div>
    </div>

    <!-- Gender-based Concession Alert -->
    @if($student->gender === 'Female' && setting('womens_discount_percentage', 0) > 0)
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1">
                        <i class="fas fa-female mr-2"></i>Gender-Based Concession Available
                    </h6>
                    <p class="mb-0">This student is eligible for {{ setting('womens_discount_percentage') }}% automatic discount.</p>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-success btn-modern" onclick="applyAutomaticGenderConcession()">
                        <i class="fas fa-magic mr-1"></i> Apply Auto Discount
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <!-- Fee Components -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">
                                <i class="fas fa-puzzle-piece text-primary mr-2"></i>
                                Fee Components
                            </h5>
                        </div>
                        <div class="col-auto">
                            <span class="badge badge-primary">{{ $studentFees->count() }} Components</span>
                            <button class="btn btn-outline-warning btn-sm ml-2" data-toggle="modal" data-target="#applyConcessionModal">
                                <i class="fas fa-percent mr-1"></i> Apply Concession
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="component-grid p-3">
                        @forelse($studentFees as $studentFee)
                            @php
                                $remainingAmount = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;
                                $paymentPercentage = $studentFee->amount > 0 ? round(($studentFee->paid_amount / $studentFee->amount) * 100, 1) : 0;
                                $isOverdue = $studentFee->due_date && \Carbon\Carbon::parse($studentFee->due_date)->isPast() && $remainingAmount > 0;
                                $isCompleted = $remainingAmount <= 0;
                            @endphp
                            
                            <div class="component-card {{ $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : '') }}">
                                <div class="component-header">
                                    <h6 class="component-title">{{ $studentFee->feeCategory->name ?? 'Unknown Category' }}</h6>
                                    <span class="component-status badge-{{ $isCompleted ? 'success' : ($isOverdue ? 'danger' : 'warning') }}">
                                        {{ $isCompleted ? 'Paid' : ($isOverdue ? 'Overdue' : 'Pending') }}
                                    </span>
                                </div>

                                <div class="progress-circle">
                                    <svg width="80" height="80">
                                        <circle class="circle-bg" cx="40" cy="40" r="32"></circle>
                                        <circle class="circle-progress" cx="40" cy="40" r="32" 
                                                style="stroke-dasharray: {{ $paymentPercentage * 2.01 }} 201.06;"></circle>
                                    </svg>
                                    <div class="progress-text">{{ $paymentPercentage }}%</div>
                                </div>

                                <div class="text-center mb-3">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="stat-value text-primary">{{ setting('currency_symbol','₹') }}{{ number_format($studentFee->amount, 0) }}</div>
                                            <div class="stat-label">Total</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-value text-success">{{ setting('currency_symbol','₹') }}{{ number_format($studentFee->paid_amount, 0) }}</div>
                                            <div class="stat-label">Paid</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-value text-{{ $remainingAmount <= 0 ? 'success' : 'danger' }}">{{ setting('currency_symbol','₹') }}{{ number_format($remainingAmount, 0) }}</div>
                                            <div class="stat-label">Due</div>
                                        </div>
                                    </div>
                                </div>

                                @if($studentFee->concession_amount > 0)
                                    <div class="text-center mb-3">
                                        <small class="text-warning">
                                            <i class="fas fa-percentage mr-1"></i>
                                            Concession: {{ setting('currency_symbol','₹') }}{{ number_format($studentFee->concession_amount, 0) }}
                                        </small>
                                    </div>
                                @endif

                                <div class="text-center">
                                    @if($remainingAmount > 0)
                                        <button type="button" class="btn btn-success btn-modern"
                                                onclick="openQuickPayment({{ $studentFee->id }}, '{{ $studentFee->feeCategory->name }}', {{ $remainingAmount }})">
                                            <i class="fas fa-credit-card mr-1"></i> Pay {{ setting('currency_symbol','₹') }}{{ number_format($remainingAmount, 0) }}
                                        </button>
                                    @else
                                        <span class="badge badge-success px-3 py-2">
                                            <i class="fas fa-check mr-1"></i> Fully Paid
                                        </span>
                                    @endif
                                </div>

                                @if($isOverdue)
                                    <div class="text-center mt-2">
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Due: {{ \Carbon\Carbon::parse($studentFee->due_date)->format('M d, Y') }}
                                        </small>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="fas fa-puzzle-piece"></i>
                                    <h5>No Fee Components</h5>
                                    <p>This student doesn't have any fee components assigned yet.</p>
                                    <a href="{{ route('admin.fee-structures.index') }}" class="btn btn-primary btn-modern">
                                        <i class="fas fa-cog mr-1"></i> Configure Fee Structure
                                    </a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="col-lg-4 mb-4">
            <div class="activity-card">
                <div class="activity-header">
                    <h5 class="mb-1">
                        <i class="fas fa-history mr-2"></i>Payment Activity
                    </h5>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value text-primary">{{ $paymentStats['total_transactions'] ?? 0 }}</div>
                            <div class="stat-label">Transactions</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value text-info">{{ $paymentStats['payment_frequency'] ?? 'N/A' }}</div>
                            <div class="stat-label">Frequency</div>
                        </div>
                    </div>
                </div>
                <div class="activity-timeline">
                    @forelse($paymentActivities ?? [] as $activity)
                        <div class="timeline-item">
                            <div class="timeline-icon {{ $activity['type'] === 'payment_created' ? 'success' : 'info' }}">
                                <i class="fas fa-{{ $activity['type'] === 'payment_created' ? 'plus' : 'info' }}"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">{{ $activity['description'] }}</div>
                                <div class="timeline-meta">
                                    {{ $activity['timestamp']->diffForHumans() }} by {{ $activity['user'] }}
                                </div>
                                @if($activity['details'])
                                    <div class="timeline-details">
                                        <strong>Components:</strong> {{ $activity['details'] }}<br>
                                        <strong>Receipt:</strong> #{{ $activity['receipt_number'] }}<br>
                                        <strong>Method:</strong> {{ ucfirst($activity['payment_method']) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h6>No Activity</h6>
                            <p>No payment activity found.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments Table -->
    @if($recentPayments->count() > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-receipt mr-2"></i>Recent Payments
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">Date</th>
                                <th class="border-0">Receipt</th>
                                <th class="border-0">Component</th>
                                <th class="border-0">Amount</th>
                                <th class="border-0">Method</th>
                                <th class="border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPayments as $payment)
                                @foreach($payment->componentItems as $item)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}</td>
                                        <td><code>{{ $payment->receipt_number }}</code></td>
                                        <td>{{ $item->studentFee->feeCategory->name ?? 'N/A' }}</td>
                                        <td><strong>{{ setting('currency_symbol','₹') }}{{ number_format($item->amount_paid, 0) }}</strong></td>
                                        <td><span class="badge badge-info">{{ ucfirst($payment->payment_method) }}</span></td>
                                        <td>
                                            <a href="{{ route('admin.payments.receipt', [$student, $payment]) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Include your existing modals (Quick Payment, Apply Concession) with same functionality -->
@include('admin.payments.partials.quick-payment-modal')
@include('admin.payments.partials.concession-modal')


@push('scripts')
<script>
// Maintain all your existing JavaScript functionality
$(document).ready(function() {
    // Initialize progress circles
    initializeProgressCircles();
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

function initializeProgressCircles() {
    $('.progress-circle').each(function() {
        const percentage = parseFloat($(this).find('.progress-text').text());
        const circumference = 2 * Math.PI * 32; // radius = 32
        const strokeDasharray = (percentage / 100) * circumference;
        
        $(this).find('.circle-progress').css({
            'stroke-dasharray': strokeDasharray + ' ' + circumference
        });
    });
}

// Your existing JavaScript functions remain the same
function openQuickPayment(studentFeeId, componentName, remainingAmount) {
    $('#modal_student_fee_id').val(studentFeeId);
    $('#modal_component_name').text(componentName);
    $('#modal_remaining_amount').text(remainingAmount.toLocaleString());
    $('#modal_amount').val(remainingAmount);
    $('#modal_amount').attr('max', remainingAmount);
    $('#quickPaymentModal').modal('show');
}

function applyAutomaticGenderConcession() {
    if (!confirm('Apply automatic gender-based concession to all eligible fee components?')) {
        return;
    }

    fetch('{{ url("admin/students/" . $student->id . "/apply-auto-gender-concession") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error applying automatic concession: ' + error.message);
    });
}

function showAlert(type, message) {
    const alertType = type === 'error' ? 'danger' : type;
    const alertHtml = `
        <div class="alert alert-${alertType} alert-dismissible fade show">
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    `;
    
    $('.alert').remove();
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Handle quick payment form submission
$('#quickPaymentForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = $('#submitPaymentBtn');
    const originalText = submitBtn.html();
    
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
    
    const storeUrl = '{{ url("admin/component-payments/store-quick") }}';
    
    $.ajax({
        url: storeUrl,
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message + ' Receipt: ' + response.receipt_number);
                $('#quickPaymentModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while processing payment.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showAlert('error', errorMessage);
        },
        complete: function() {
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
});

// Validate amount input
$('#modal_amount').on('input', function() {
    const amount = parseFloat($(this).val());
    const maxAmount = parseFloat($(this).attr('max'));
    
    if (amount > maxAmount) {
        $(this).val(maxAmount);
        showAlert('warning', 'Amount cannot exceed remaining balance');
    }
});
</script>
@endpush
@endsection