@extends('layouts.theme')

@section('title', 'Payment Reminders Dashboard')

@push('styles')
<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
    --warning-gradient: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
    --danger-gradient: linear-gradient(135deg, #e74a3b 0%, #dc3545 100%);
    --info-gradient: linear-gradient(135deg, #36b9cc 0%, #2e96aa 100%);
    --shadow: 0 4px 12px rgba(0,0,0,0.1);
    --shadow-lg: 0 8px 25px rgba(0,0,0,0.15);
    --border-radius: 15px;
}

/* Page Header */
.page-header-modern {
    background: var(--primary-gradient);
    color: white;
    padding: 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.page-header-modern::before {
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

.header-content {
    position: relative;
    z-index: 2;
}

.header-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.header-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

/* Modern Cards */
.modern-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    border: none;
    overflow: hidden;
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

.modern-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Enhanced Stats Cards */
.stats-card-enhanced {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    margin-bottom: 1.5rem;
}

.stats-card-enhanced:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.stats-card-enhanced .card-body {
    padding: 1.5rem;
    position: relative;
    z-index: 2;
}

.stats-card-enhanced.primary {
    background: var(--primary-gradient);
    color: white;
}

.stats-card-enhanced.success {
    background: var(--success-gradient);
    color: white;
}

.stats-card-enhanced.warning {
    background: var(--warning-gradient);
    color: white;
}

.stats-card-enhanced.danger {
    background: var(--danger-gradient);
    color: white;
}

.stats-icon-large {
    font-size: 2.5rem;
    opacity: 0.9;
}

.stats-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stats-label {
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

.stats-change {
    display: flex;
    align-items: center;
    margin-top: 0.75rem;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
}

/* Action Buttons */
.action-btn-modern {
    border-radius: 25px;
    padding: 12px 20px;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin: 0.25rem;
    text-decoration: none;
    display: inline-block;
}

.action-btn-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.2);
    transition: left 0.3s ease;
}

.action-btn-modern:hover::before {
    left: 100%;
}

.action-btn-modern i {
    margin-right: 0.5rem;
}

/* Quick Actions Section */
.quick-actions-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

/* Table Enhancements */
.table-modern {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    background: white;
    margin-bottom: 0;
}

.table-modern thead th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    padding: 1rem;
}

.table-modern tbody td {
    border: none;
    padding: 1rem;
    vertical-align: middle;
}

.table-modern tbody tr {
    border-bottom: 1px solid #f1f3f4;
    transition: background-color 0.2s ease;
}

.table-modern tbody tr:hover {
    background: #f8f9fa;
}

/* Badge Enhancements */
.badge-modern {
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-modern.status-pending {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    color: #8b4513;
}

.badge-modern.status-sent {
    background: var(--success-gradient);
    color: white;
}

.badge-modern.status-failed {
    background: var(--danger-gradient);
    color: white;
}

.badge-modern.status-cancelled {
    background: linear-gradient(135deg, #d3d3d3 0%, #a8a8a8 100%);
    color: #333;
}

/* Avatar Enhancements */
.avatar-modern {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    background: var(--primary-gradient);
    color: white;
    margin-right: 0.75rem;
}

/* Form Enhancements */
.form-modern {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
}

.form-control-modern {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-label-modern {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: #adb5bd;
}

/* Loading Modal */
.loading-modal .modal-content {
    border-radius: var(--border-radius);
    border: none;
    box-shadow: var(--shadow-lg);
}

.loading-modal .spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Character Counter */
.char-counter {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.char-counter.warning {
    color: #f6c23e;
}

.char-counter.danger {
    color: #e74a3b;
}

/* Channel Icons */
.channel-icon {
    width: 20px;
    text-align: center;
    margin-right: 0.5rem;
}

/* Collection Summary Card */
.collection-summary {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    border: 2px solid rgba(102, 126, 234, 0.2);
}

.summary-metric {
    text-align: center;
    padding: 1rem;
}

.summary-metric-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.summary-metric-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Category Risk Indicators */
.risk-indicator {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.risk-indicator.critical {
    background: var(--danger-gradient);
    color: white;
}

.risk-indicator.high {
    background: var(--warning-gradient);
    color: white;
}

.risk-indicator.medium {
    background: var(--info-gradient);
    color: white;
}

.risk-indicator.low {
    background: var(--success-gradient);
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header-modern {
        padding: 1.5rem 1rem;
        text-align: center;
    }
    
    .header-title {
        font-size: 2rem;
    }
    
    .stats-number {
        font-size: 2rem;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-card {
        margin-bottom: 1rem;
    }
}

/* Animation */
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
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Enhanced Page Header -->
    <div class="page-header-modern animate-fadeInUp">
        <div class="header-content">
            <div class="d-sm-flex align-items-center justify-content-between">
                <div>
                    <h1 class="header-title mb-0">
                        <i class="fas fa-bell me-3"></i>Payment Reminders Hub
                    </h1>
                    <p class="header-subtitle">Streamline your payment collection process with intelligent category-based reminders</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.dashboard') }}" class="text-white-50">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Payment Reminders</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="row animate-fadeInUp">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card-enhanced warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="stats-label">Pending Reminders</div>
                            <div class="stats-number">{{ number_format($stats['pending_reminders'] ?? 0) }}</div>
                            <div class="stats-change">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                {{ $stats['failed_reminders'] ?? 0 }} failed
                            </div>
                        </div>
                        <div class="stats-icon-large">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card-enhanced success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="stats-label">Sent Reminders</div>
                            <div class="stats-number">{{ number_format($stats['sent_reminders'] ?? 0) }}</div>
                            <div class="stats-change">
                                <i class="fas fa-arrow-up me-1"></i>
                                {{ number_format($stats['success_rate'] ?? 0, 1) }}% success rate
                            </div>
                        </div>
                        <div class="stats-icon-large">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card-enhanced danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="stats-label">Critical Students</div>
                            <div class="stats-number">{{ number_format($collection_efficiency['critical_defaulters'] ?? 0) }}</div>
                            <div class="stats-change">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Need attention
                            </div>
                        </div>
                        <div class="stats-icon-large">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card-enhanced primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="stats-label">Collection Rate</div>
                            <div class="stats-number">{{ number_format($collection_efficiency['collection_rate'] ?? 0, 1) }}%</div>
                            <div class="stats-change">
                                <i class="fas fa-chart-line me-1"></i>
                                {{ number_format($collection_efficiency['overdue_rate'] ?? 0, 1) }}% overdue
                            </div>
                        </div>
                        <div class="stats-icon-large">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <!-- Quick Actions -->
            <div class="modern-card animate-fadeInUp">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-bolt text-primary me-2"></i>Quick Actions
                    </h4>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <button type="button" class="action-btn-modern btn btn-primary" onclick="processReminders()">
                            <i class="fas fa-play"></i>
                            Process Pending Reminders
                        </button>
                        <a href="{{ route('admin.fee-category-analysis.index') }}" class="action-btn-modern btn btn-info">
                            <i class="fas fa-chart-pie"></i>
                            Fee Category Analysis
                        </a>
                        <a href="{{ route('admin.payment-reminders.index') }}" class="action-btn-modern btn btn-outline-primary">
                            <i class="fas fa-list"></i>
                            View All Reminders
                        </a>
                        <a href="{{ route('admin.fee-category-analysis.critical-defaulters') }}" class="action-btn-modern btn btn-outline-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Critical Students
                        </a>
                    </div>
                </div>
            </div>

            <!-- Fee Categories at Risk -->
            <div class="modern-card animate-fadeInUp">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>Categories Requiring Attention
                    </h4>
                    <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="fas fa-external-link-alt me-1"></i>View All Categories
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(isset($at_risk_categories) && $at_risk_categories && $at_risk_categories->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Total Students</th>
                                        <th>Pending Amount</th>
                                        <th>Collection Rate</th>
                                        <th>Risk Level</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($at_risk_categories as $category)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-modern">
                                                        {{ substr($category->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-bold">{{ $category->name }}</h6>
                                                        <small class="text-muted">{{ $category->category_code ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($category->is_mandatory)
                                                    <span class="badge badge-modern bg-danger">Mandatory</span>
                                                @else
                                                    <span class="badge badge-modern bg-info">Optional</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ number_format($category->total_students ?? 0) }}</div>
                                                <small class="text-muted">{{ number_format($category->pending_students ?? 0) }} pending</small>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-danger">₹{{ number_format($category->total_pending ?? 0) }}</div>
                                                @if(($category->total_overdue ?? 0) > 0)
                                                    <small class="text-danger">₹{{ number_format($category->total_overdue) }} overdue</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 50px; height: 8px;">
                                                        <div class="progress-bar bg-{{ ($category->collection_rate ?? 0) >= 80 ? 'success' : (($category->collection_rate ?? 0) >= 60 ? 'warning' : 'danger') }}" 
                                                             style="width: {{ $category->collection_rate ?? 0 }}%"></div>
                                                    </div>
                                                    <span class="fw-bold small">{{ number_format($category->collection_rate ?? 0, 1) }}%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="risk-indicator {{ strtolower($category->risk_level ?? 'low') }}">
                                                    {{ ucfirst($category->risk_level ?? 'Low') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.fee-category-analysis.show', $category->id) }}" 
                                                       class="btn btn-sm btn-outline-primary rounded-pill">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill ms-1" 
                                                            onclick="sendCategoryReminders({{ $category->id }}, '{{ $category->name }}')">
                                                        <i class="fas fa-bell me-1"></i>Remind
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="fw-bold mb-2">All Categories Performing Well</h5>
                            <p class="text-muted mb-0">No fee categories require immediate attention at this time.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Reminders -->
            <div class="modern-card animate-fadeInUp">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-history text-primary me-2"></i>Recent Reminders
                    </h4>
                    <a href="{{ route('admin.payment-reminders.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="fas fa-external-link-alt me-1"></i>View All
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(isset($recent_reminders) && $recent_reminders && $recent_reminders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Fee Component</th>
                                        <th>Type</th>
                                        <th>Channel</th>
                                        <th>Scheduled</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_reminders as $reminder)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-modern">
                                                        {{ substr($reminder->student->name ?? 'N', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-bold">{{ $reminder->student->name ?? 'N/A' }}</h6>
                                                        <small class="text-muted">{{ $reminder->student->enrollment_number ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($reminder->studentFee)
                                                    <div class="fw-medium">{{ $reminder->studentFee->feeCategory->name ?? 'N/A' }}</div>
                                                    <small class="text-muted">
                                                        @php
                                                            $remainingAmount = $reminder->studentFee->amount - ($reminder->studentFee->paid_amount ?? 0) - ($reminder->studentFee->concession_amount ?? 0);
                                                        @endphp
                                                        ₹{{ number_format($remainingAmount, 2) }} remaining
                                                    </small>
                                                @else
                                                    <span class="text-muted">All Components</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-modern bg-info">
                                                    {{ ucwords(str_replace('_', ' ', $reminder->reminder_type ?? 'General')) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="channel-icon">
                                                    <i class="fas fa-{{ ($reminder->channel ?? 'email') === 'email' ? 'envelope' : (($reminder->channel ?? 'email') === 'sms' ? 'sms' : 'comment') }}"></i>
                                                </span>
                                                {{ ucfirst($reminder->channel ?? 'Email') }}
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ ($reminder->scheduled_date ?? now())->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ ($reminder->scheduled_date ?? now())->format('H:i') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-modern status-{{ $reminder->status ?? 'pending' }}">
                                                    {{ ucfirst($reminder->status ?? 'Pending') }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(($reminder->status ?? 'pending') === 'pending')
                                                    <button type="button" class="btn btn-sm btn-primary rounded-pill" onclick="sendReminder({{ $reminder->id ?? 0 }})">
                                                        <i class="fas fa-play me-1"></i>Send Now
                                                    </button>
                                                @else
                                                    <a href="{{ route('admin.payment-reminders.show', $reminder->id ?? 0) }}" class="btn btn-sm btn-outline-info rounded-pill">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h5 class="fw-bold mb-2">No Recent Reminders</h5>
                            <p class="text-muted mb-0">No reminders have been processed recently.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <!-- Test Reminder Form -->
            <div class="modern-card animate-fadeInUp">
                <div class="card-header bg-transparent border-0">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-vial text-primary me-2"></i>Send Test Reminder
                    </h4>
                </div>
                <div class="card-body">
                    <form id="testReminderForm" class="form-modern">
                        @csrf
                        <div class="mb-3">
                            <label for="channel" class="form-label-modern">Channel</label>
                            <select class="form-select form-control-modern" id="channel" name="channel" required>
                                <option value="">Select Channel</option>
                                <option value="email">📧 Email</option>
                                <option value="sms">📱 SMS</option>
                                <option value="whatsapp">💬 WhatsApp</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="recipient" class="form-label-modern">Recipient</label>
                            <input type="text" class="form-control form-control-modern" id="recipient" name="recipient" 
                                   placeholder="Email or Phone Number" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label-modern">Message</label>
                            <textarea class="form-control form-control-modern" id="message" name="message" rows="3" 
                                      placeholder="Test reminder message" required>Dear Student, this is a test payment reminder from {{ config('app.name') }}.</textarea>
                            <div class="char-counter mt-1">
                                <span id="charCount">0</span> characters
                                <span id="charLimit" class="text-muted"></span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill">
                            <i class="fas fa-paper-plane me-2"></i>Send Test Reminder
                        </button>
                    </form>
                </div>
            </div>

            <!-- Collection Summary -->
            <div class="modern-card animate-fadeInUp">
                <div class="card-header bg-transparent border-0">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-chart-pie text-primary me-2"></i>Collection Summary
                    </h4>
                </div>
                <div class="card-body">
                    <div class="collection-summary">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="summary-metric">
                                    <div class="summary-metric-value text-primary">
                                        {{ number_format($collection_efficiency['total_fees'] ?? 0) }}
                                    </div>
                                    <div class="summary-metric-label">Total Fee Components</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="summary-metric">
                                    <div class="summary-metric-value text-success">
                                        {{ number_format($collection_efficiency['paid_fees'] ?? 0) }}
                                    </div>
                                    <div class="summary-metric-label">Paid Components</div>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="summary-metric">
                                    <div class="summary-metric-value text-danger">
                                        {{ number_format($collection_efficiency['overdue_fees'] ?? 0) }}
                                    </div>
                                    <div class="summary-metric-label">Overdue</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="summary-metric">
                                    <div class="summary-metric-value text-info">
                                        {{ number_format($collection_efficiency['collection_rate'] ?? 0, 1) }}%
                                    </div>
                                    <div class="summary-metric-label">Success Rate</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Category Quick Stats -->
            <div class="modern-card animate-fadeInUp">
                <div class="card-header bg-transparent border-0">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-tags text-primary me-2"></i>Category Quick Stats
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="summary-metric">
                                <div class="summary-metric-value text-danger">
                                    {{ number_format($collection_efficiency['critical_categories'] ?? 0) }}
                                </div>
                                <div class="summary-metric-label">Critical Categories</div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="summary-metric">
                                <div class="summary-metric-value text-warning">
                                    {{ number_format($collection_efficiency['at_risk_categories'] ?? 0) }}
                                </div>
                                <div class="summary-metric-label">At Risk Categories</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="fas fa-chart-bar me-1"></i>View Category Analysis
                        </a>
                        <a href="{{ route('admin.fee-category-analysis.critical-defaulters') }}" class="btn btn-outline-danger btn-sm rounded-pill">
                            <i class="fas fa-exclamation-triangle me-1"></i>View Critical Students
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Loading Modal -->
<div class="modal fade loading-modal" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h6 class="fw-bold mb-2">Processing Request</h6>
            <p class="text-muted mb-0 small">Please wait while we process your request...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Character counter for message
    $('#message').on('input', function() {
        const length = $(this).val().length;
        const charCountElement = $('#charCount');
        const charLimitElement = $('#charLimit');
        
        charCountElement.text(length);
        
        const channel = $('#channel').val();
        let limit = '';
        let isOverLimit = false;
        
        if (channel === 'sms') {
            limit = ' / 160 (SMS limit)';
            isOverLimit = length > 160;
        } else if (channel === 'whatsapp') {
            limit = ' / 4096 (WhatsApp limit)';
            isOverLimit = length > 4096;
        }
        
        charLimitElement.text(limit);
        
        // Update styling based on limit
        charCountElement.removeClass('text-warning text-danger');
        if (isOverLimit) {
            charCountElement.addClass('text-danger');
        } else if (channel === 'sms' && length > 140) {
            charCountElement.addClass('text-warning');
        }
    });

    // Update character limit when channel changes
    $('#channel').on('change', function() {
        $('#message').trigger('input');
        
        const channel = $(this).val();
        const recipientInput = $('#recipient');
        
        if (channel === 'email') {
            recipientInput.attr('placeholder', 'Enter email address');
            recipientInput.attr('type', 'email');
        } else {
            recipientInput.attr('placeholder', 'Enter phone number');
            recipientInput.attr('type', 'tel');
        }
    });

    // Test reminder form submission
    $('#testReminderForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            channel: $('#channel').val(),
            recipient: $('#recipient').val(),
            message: $('#message').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("admin.payment-reminders.test") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#loadingModal').modal('show');
            },
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    showAlert('success', 'Test reminder sent successfully!');
                    $('#testReminderForm')[0].reset();
                    $('#charCount').text('0');
                    $('#charLimit').text('');
                } else {
                    showAlert('danger', 'Failed to send test reminder: ' + response.error);
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                const response = xhr.responseJSON;
                let errorMsg = 'An error occurred while sending the test reminder.';
                
                if (response && response.errors) {
                    errorMsg = Object.values(response.errors).flat().join('<br>');
                } else if (response && response.error) {
                    errorMsg = response.error;
                }
                
                showAlert('danger', errorMsg);
            }
        });
    });

    // Initialize animations
    initializeAnimations();
});

// Enhanced alert function with modern styling
function showAlert(type, message) {
    const alertClasses = {
        success: 'alert-success',
        danger: 'alert-danger',
        warning: 'alert-warning',
        info: 'alert-info'
    };
    
    const iconClasses = {
        success: 'fa-check-circle',
        danger: 'fa-exclamation-triangle',
        warning: 'fa-exclamation-circle',
        info: 'fa-info-circle'
    };
    
    const alertHtml = `
        <div class="alert ${alertClasses[type]} alert-dismissible fade show modern-alert" role="alert" style="border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <div class="d-flex align-items-center">
                <i class="fas ${iconClasses[type]} me-2" style="font-size: 1.2rem;"></i>
                <div>${message}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut(400, function() {
            $(this).remove();
        });
    }, 5000);
}

// Process pending reminders
function processReminders() {
    Swal.fire({
        title: 'Process Pending Reminders?',
        text: 'This will queue all pending reminders for immediate processing.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Process Now',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        background: '#fff',
        borderRadius: '15px',
        customClass: {
            popup: 'modern-swal',
            confirmButton: 'btn-modern',
            cancelButton: 'btn-modern-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("admin.payment-reminders.process-pending") }}',
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function() {
                    $('#loadingModal').modal('show');
                },
                success: function(response) {
                    $('#loadingModal').modal('hide');
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Reminders processing started successfully!',
                            icon: 'success',
                            confirmButtonColor: '#667eea',
                            background: '#fff',
                            borderRadius: '15px'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        showAlert('danger', 'Failed to start processing: ' + response.error);
                    }
                },
                error: function(xhr) {
                    $('#loadingModal').modal('hide');
                    showAlert('danger', 'An error occurred while processing reminders.');
                }
            });
        }
    });
}

// Send category-specific reminders
function sendCategoryReminders(categoryId, categoryName) {
    Swal.fire({
        title: `Send Reminders for ${categoryName}?`,
        html: `
            <div class="text-left">
                <div class="form-group mb-3">
                    <label class="form-label">Reminder Type:</label>
                    <select id="reminderType" class="form-control">
                        <option value="gentle">Gentle Reminder</option>
                        <option value="firm">Firm Reminder</option>
                        <option value="urgent">Urgent Notice</option>
                    </select>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="overdueOnly" checked>
                    <label class="form-check-label" for="overdueOnly">Only overdue payments</label>
                </div>
                <div class="form-group">
                    <label class="form-label">Minimum Amount (₹):</label>
                    <input type="number" id="minAmount" class="form-control" value="1000" min="0">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Send Reminders',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        preConfirm: () => {
            return {
                reminder_type: document.getElementById('reminderType').value,
                include_overdue_only: document.getElementById('overdueOnly').checked,
                minimum_amount: document.getElementById('minAmount').value
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route("admin.fee-category-analysis.send-reminders", ":categoryId") }}`.replace(':categoryId', categoryId),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    ...result.value
                },
                beforeSend: function() {
                    $('#loadingModal').modal('show');
                },
                success: function(response) {
                    $('#loadingModal').modal('hide');
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#667eea'
                        });
                    } else {
                        showAlert('danger', 'Failed to send reminders: ' + response.error);
                    }
                },
                error: function(xhr) {
                    $('#loadingModal').modal('hide');
                    showAlert('danger', 'An error occurred while sending reminders.');
                }
            });
        }
    });
}

// Send individual reminder
function sendReminder(reminderId) {
    Swal.fire({
        title: 'Send Reminder Now?',
        text: 'This will queue the reminder for immediate delivery.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Send Now',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        background: '#fff',
        borderRadius: '15px'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/payment-reminders/${reminderId}/queue`,
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Reminder queued successfully!',
                            icon: 'success',
                            confirmButtonColor: '#667eea',
                            background: '#fff',
                            borderRadius: '15px',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        showAlert('danger', 'Failed to queue reminder: ' + response.error);
                    }
                },
                error: function(xhr) {
                    showAlert('danger', 'An error occurred while queuing the reminder.');
                }
            });
        }
    });
}

// Initialize animations
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all cards for animation
    document.querySelectorAll('.modern-card, .stats-card-enhanced').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

// Custom CSS for SweetAlert2
const style = document.createElement('style');
style.textContent = `
    .modern-swal {
        border-radius: 15px !important;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }
    
    .btn-modern {
        border-radius: 25px !important;
        padding: 10px 20px !important;
        font-weight: 600 !important;
    }
    
    .btn-modern-secondary {
        border-radius: 25px !important;
        padding: 10px 20px !important;
        font-weight: 600 !important;
    }
`;
document.head.appendChild(style);

console.log('Payment Reminders Dashboard initialized with Fee Category Analysis integration');
</script>

<!-- SweetAlert2 for enhanced dialogs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush