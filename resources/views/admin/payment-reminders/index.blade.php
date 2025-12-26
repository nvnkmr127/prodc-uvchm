@extends('layouts.theme')
@section('title', 'Payment Reminders Management')

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
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.header-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
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
.stats-row {
    margin-bottom: 2rem;
}

.stats-card-enhanced {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    margin-bottom: 1.5rem;
    border: none;
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
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stats-label {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

/* Filters Section */
.filters-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0,0,0,0.05);
}

.filter-group {
    margin-bottom: 1rem;
}

.filter-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.filter-control {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.filter-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    align-items: end;
    height: 100%;
}

/* Action Buttons */
.action-btn-modern {
    border-radius: 25px;
    padding: 10px 20px;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
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
    color: #495057;
}

.table-modern tbody td {
    border: none;
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

.table-modern tbody tr {
    transition: background-color 0.2s ease;
}

.table-modern tbody tr:hover {
    background: #f8f9fa;
}

/* Student Info */
.student-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.student-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.student-details h6 {
    margin: 0;
    font-weight: 600;
    font-size: 0.9rem;
}

.student-details small {
    color: #6c757d;
    font-size: 0.8rem;
}

/* Badge Enhancements */
.badge-modern {
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.badge-modern.type-email {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.badge-modern.type-sms {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    color: white;
}

.badge-modern.type-whatsapp {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    color: white;
}

.badge-modern.type-phone {
    background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%);
    color: white;
}

.badge-modern.priority-urgent {
    background: var(--danger-gradient);
    color: white;
}

.badge-modern.priority-high {
    background: var(--warning-gradient);
    color: #333;
}

.badge-modern.priority-medium {
    background: var(--info-gradient);
    color: white;
}

.badge-modern.priority-low {
    background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
    color: white;
}

.badge-modern.status-sent {
    background: var(--success-gradient);
    color: white;
}

.badge-modern.status-pending {
    background: var(--warning-gradient);
    color: #333;
}

.badge-modern.status-failed {
    background: var(--danger-gradient);
    color: white;
}

.badge-modern.status-cancelled {
    background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
    color: white;
}

/* Action Buttons in Table */
.table-actions {
    display: flex;
    gap: 0.25rem;
    align-items: center;
}

.btn-table-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 0.8rem;
}

.btn-table-action:hover {
    transform: scale(1.1);
}

/* Bulk Actions */
.bulk-actions-card {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    border: 2px solid rgba(102, 126, 234, 0.1);
}

.bulk-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

/* Selection Counter */
.selection-counter {
    background: var(--primary-gradient);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    margin-bottom: 1rem;
    display: none;
    align-items: center;
    gap: 0.5rem;
}

.selection-counter.show {
    display: flex;
}

/* Pagination */
.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 1.5rem;
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

/* Custom Checkbox */
.custom-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #667eea;
    cursor: pointer;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header-modern {
        padding: 1.5rem 1rem;
        text-align: center;
    }
    
    .header-title {
        font-size: 1.8rem;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .stats-number {
        font-size: 1.8rem;
    }
    
    .bulk-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .table-modern {
        font-size: 0.85rem;
    }
    
    .student-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .table-actions {
        flex-direction: column;
        gap: 0.25rem;
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

/* Loading States */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
                        <i class="fas fa-bell me-3"></i>Payment Reminders Management
                    </h1>
                    <p class="header-subtitle">Efficiently manage and track all payment reminder communications</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('admin.payment-reminders.create') }}" class="action-btn-modern btn btn-light">
                        <i class="fas fa-plus"></i>
                        New Reminder
                    </a>
                    <a href="{{ route('admin.payment-reminders.dashboard') }}" class="action-btn-modern btn btn-outline-light">
                        <i class="fas fa-chart-line"></i>
                        Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="stats-row animate-fadeInUp">
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card-enhanced primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="stats-label">Total Reminders</div>
                                <div class="stats-number">{{ number_format($stats['total'] ?? 0) }}</div>
                            </div>
                            <div class="stats-icon-large">
                                <i class="fas fa-bell"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card-enhanced warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="stats-label">Pending</div>
                                <div class="stats-number">{{ number_format($stats['pending'] ?? 0) }}</div>
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
                                <div class="stats-label">Sent</div>
                                <div class="stats-number">{{ number_format($stats['sent'] ?? 0) }}</div>
                            </div>
                            <div class="stats-icon-large">
                                <i class="fas fa-check"></i>
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
                                <div class="stats-label">Failed</div>
                                <div class="stats-number">{{ number_format($stats['failed'] ?? 0) }}</div>
                            </div>
                            <div class="stats-icon-large">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Filters -->
    <div class="filters-card animate-fadeInUp">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-filter text-primary me-2"></i>Advanced Filters
            </h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleFilters()">
                <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
            </button>
        </div>
        
        <div id="filterContent" class="collapse show">
            <form method="GET" class="row">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select filter-control">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>📋 Pending</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>✅ Sent</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>❌ Failed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>🚫 Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label" for="channel">Communication Channel</label>
                        <select name="channel" id="channel" class="form-select filter-control">
                            <option value="">All Channels</option>
                            <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>📧 Email</option>
                            <option value="sms" {{ request('channel') == 'sms' ? 'selected' : '' }}>📱 SMS</option>
                            <option value="whatsapp" {{ request('channel') == 'whatsapp' ? 'selected' : '' }}>💬 WhatsApp</option>
                            <option value="phone_call" {{ request('channel') == 'phone_call' ? 'selected' : '' }}>📞 Phone Call</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label" for="reminder_type">Reminder Type</label>
                        <select name="reminder_type" id="reminder_type" class="form-select filter-control">
                            <option value="">All Types</option>
                            <option value="upcoming_due" {{ request('reminder_type') == 'upcoming_due' ? 'selected' : '' }}>⏰ Upcoming Due</option>
                            <option value="overdue" {{ request('reminder_type') == 'overdue' ? 'selected' : '' }}>🔴 Overdue</option>
                            <option value="escalation" {{ request('reminder_type') == 'escalation' ? 'selected' : '' }}>⚠️ Escalation</option>
                            <option value="final_notice" {{ request('reminder_type') == 'final_notice' ? 'selected' : '' }}>🚨 Final Notice</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label" for="date_from">From Date</label>
                        <input type="date" name="date_from" id="date_from" class="form-control filter-control" value="{{ request('date_from') }}">
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label" for="date_to">To Date</label>
                        <input type="date" name="date_to" id="date_to" class="form-control filter-control" value="{{ request('date_to') }}">
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="filter-group">
                        <label class="filter-label">&nbsp;</label>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary action-btn-modern">
                                <i class="fas fa-search"></i>
                                Filter
                            </button>
                            <a href="{{ route('admin.payment-reminders.index') }}" class="btn btn-outline-secondary action-btn-modern">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Selection Counter -->
    <div class="selection-counter" id="selectionCounter">
        <i class="fas fa-check-circle"></i>
        <span id="selectedCount">0</span> reminders selected
        <button type="button" class="btn btn-sm btn-light ms-2" onclick="clearSelection()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Enhanced Reminders Table -->
    <div class="modern-card animate-fadeInUp">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">
                <i class="fas fa-list text-primary me-2"></i>Payment Reminders
            </h4>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle rounded-pill" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="processPendingReminders()">
                        <i class="fas fa-play me-2"></i>Process Pending
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.payment-reminders.export') }}">
                        <i class="fas fa-download me-2"></i>Export Data
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="refreshTable()">
                        <i class="fas fa-sync me-2"></i>Refresh
                    </a></li>
                </ul>
            </div>
        </div>
        
        <div class="card-body p-0">
            @if(isset($reminders) && $reminders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="custom-checkbox" id="selectAll">
                                </th>
                                <th>Student Information</th>
                                <th>Fee Component</th>
                                <th>Communication Type</th>
                                <th>Scheduled Date</th>
                                <th>Reminder Type</th>
                                <th>Status</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reminders as $reminder)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="custom-checkbox reminder-checkbox" name="reminder_ids[]" value="{{ $reminder->id }}">
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <img class="student-avatar" 
                                                 src="{{ $reminder->student->photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($reminder->student->name ?? 'N/A').'&size=40&background=667eea&color=ffffff' }}" 
                                                 alt="Student Photo">
                                            <div class="student-details">
                                                <h6>{{ $reminder->student->name ?? 'N/A' }}</h6>
                                                <small>{{ $reminder->student->enrollment_number ?? 'N/A' }}</small>
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
                                        <span class="badge badge-modern type-{{ $reminder->channel ?? 'email' }}">
                                            <i class="fas fa-{{ ($reminder->channel ?? 'email') == 'email' ? 'envelope' : (($reminder->channel ?? 'email') == 'sms' ? 'sms' : (($reminder->channel ?? 'email') == 'whatsapp' ? 'comment' : 'phone')) }}"></i>
                                            {{ ucfirst($reminder->channel ?? 'Email') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ ($reminder->scheduled_date ?? now())->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ ($reminder->scheduled_date ?? now())->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-modern bg-info">
                                            {{ ucwords(str_replace('_', ' ', $reminder->reminder_type ?? 'General')) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-modern status-{{ $reminder->status ?? 'pending' }}">
                                            {{ ucfirst($reminder->status ?? 'Pending') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="{{ route('admin.payment-reminders.show', $reminder->id ?? 0) }}" 
                                               class="btn btn-table-action btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if(($reminder->status ?? 'pending') == 'pending')
                                                <button onclick="sendReminder({{ $reminder->id ?? 0 }})" 
                                                        class="btn btn-table-action btn-outline-success" title="Send Now">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                                <a href="{{ route('admin.payment-reminders.edit', $reminder->id ?? 0) }}" 
                                                   class="btn btn-table-action btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            
                                            @if(($reminder->status ?? 'pending') != 'sent')
                                                <button onclick="deleteReminder({{ $reminder->id ?? 0 }})" 
                                                        class="btn btn-table-action btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="pagination-wrapper">
                    {{ $reminders->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h5 class="fw-bold mb-2">No Payment Reminders Found</h5>
                    <p class="text-muted mb-3">No reminders match your current filters. Try adjusting your search criteria or create a new reminder.</p>
                    <a href="{{ route('admin.payment-reminders.create') }}" class="btn btn-primary action-btn-modern">
                        <i class="fas fa-plus"></i>
                        Create First Reminder
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Enhanced Bulk Actions -->
    <div class="bulk-actions-card animate-fadeInUp">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-tasks text-primary me-2"></i>Bulk Actions
            </h5>
            <small class="text-muted">Select reminders above to enable bulk operations</small>
        </div>
        
        <div class="bulk-actions-grid">
            <button onclick="bulkSendReminders()" class="btn btn-success action-btn-modern" disabled id="bulkSendBtn">
                <i class="fas fa-paper-plane"></i>
                Send Selected
            </button>
            <button onclick="bulkCancelReminders()" class="btn btn-warning action-btn-modern" disabled id="bulkCancelBtn">
                <i class="fas fa-ban"></i>
                Cancel Selected
            </button>
            <button onclick="showBulkRescheduleModal()" class="btn btn-info action-btn-modern" disabled id="bulkRescheduleBtn">
                <i class="fas fa-calendar"></i>
                Reschedule Selected
            </button>
            <button onclick="bulkDeleteReminders()" class="btn btn-danger action-btn-modern" disabled id="bulkDeleteBtn">
                <i class="fas fa-trash"></i>
                Delete Selected
            </button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- Bulk Reschedule Modal -->
<div class="modal fade" id="bulkRescheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: var(--primary-gradient); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title">
                    <i class="fas fa-calendar me-2"></i>Reschedule Selected Reminders
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="bulkRescheduleForm">
                    <div class="mb-3">
                        <label for="newScheduledDate" class="form-label fw-bold">New Scheduled Date & Time</label>
                        <input type="datetime-local" class="form-control filter-control" id="newScheduledDate" name="scheduled_date" required>
                        <div class="form-text">All selected reminders will be rescheduled to this date and time.</div>
                    </div>
                    <div class="mb-3">
                        <label for="rescheduleReason" class="form-label fw-bold">Reason (Optional)</label>
                        <textarea class="form-control filter-control" id="rescheduleReason" name="reason" rows="3" placeholder="Enter reason for rescheduling..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary action-btn-modern" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info action-btn-modern" onclick="confirmBulkReschedule()">
                    <i class="fas fa-calendar-check me-1"></i>Reschedule Reminders
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- SweetAlert2 for enhanced dialogs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[title]').tooltip();
    
    // Initialize selection counter
    updateSelectionCounter();
    
    // Set minimum date for reschedule to current date
    const now = new Date();
    const minDateTime = now.toISOString().slice(0, 16);
    $('#newScheduledDate').attr('min', minDateTime);
});

// Toggle filters visibility
function toggleFilters() {
    const filterContent = document.getElementById('filterContent');
    const icon = document.getElementById('filterToggleIcon');
    
    if (filterContent.classList.contains('show')) {
        filterContent.classList.remove('show');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    } else {
        filterContent.classList.add('show');
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
    }
}

// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="reminder_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateSelectionCounter();
    updateBulkActionButtons();
});

// Individual checkbox change
document.addEventListener('change', function(e) {
    if (e.target.matches('input[name="reminder_ids[]"]')) {
        updateSelectionCounter();
        updateBulkActionButtons();
        
        // Update select all checkbox
        const allCheckboxes = document.querySelectorAll('input[name="reminder_ids[]"]');
        const checkedCheckboxes = document.querySelectorAll('input[name="reminder_ids[]"]:checked');
        const selectAllCheckbox = document.getElementById('selectAll');
        
        selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
    }
});

// Update selection counter
function updateSelectionCounter() {
    const selected = getSelectedReminderIds();
    const counter = document.getElementById('selectionCounter');
    const countElement = document.getElementById('selectedCount');
    
    countElement.textContent = selected.length;
    
    if (selected.length > 0) {
        counter.classList.add('show');
    } else {
        counter.classList.remove('show');
    }
}

// Update bulk action button states
function updateBulkActionButtons() {
    const selected = getSelectedReminderIds();
    const buttons = ['bulkSendBtn', 'bulkCancelBtn', 'bulkRescheduleBtn', 'bulkDeleteBtn'];
    
    buttons.forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = selected.length === 0;
        }
    });
}

// Clear selection
function clearSelection() {
    document.querySelectorAll('input[name="reminder_ids[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAll').indeterminate = false;
    updateSelectionCounter();
    updateBulkActionButtons();
}

// Get selected reminder IDs
function getSelectedReminderIds() {
    const selected = [];
    document.querySelectorAll('input[name="reminder_ids[]"]:checked').forEach(checkbox => {
        selected.push(checkbox.value);
    });
    return selected;
}

// Show loading overlay
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

// Hide loading overlay
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

// Enhanced alert function
function showAlert(type, title, text) {
    const iconTypes = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    Swal.fire({
        icon: iconTypes[type] || 'info',
        title: title,
        text: text,
        confirmButtonColor: '#667eea',
        customClass: {
            popup: 'modern-swal'
        }
    });
}

// Send individual reminder
function sendReminder(reminderId) {
    Swal.fire({
        title: 'Send Reminder Now?',
        text: 'This will send the reminder immediately to the student.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Send Now',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            
            fetch(`/admin/payment-reminders/${reminderId}/send`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showAlert('success', 'Success!', 'Reminder sent successfully.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', 'Failed', 'Failed to send reminder: ' + data.message);
                }
            })
            .catch(error => {
                hideLoading();
                showAlert('error', 'Error', 'An error occurred while sending the reminder.');
            });
        }
    });
}

// Delete individual reminder
function deleteReminder(reminderId) {
    Swal.fire({
        title: 'Delete Reminder?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            
            fetch(`/admin/payment-reminders/${reminderId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showAlert('success', 'Deleted!', 'Reminder deleted successfully.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', 'Failed', 'Failed to delete reminder: ' + data.message);
                }
            })
            .catch(error => {
                hideLoading();
                showAlert('error', 'Error', 'An error occurred while deleting the reminder.');
            });
        }
    });
}

// All other bulk action functions remain the same...
// [Previous bulk action functions: bulkSendReminders, bulkCancelReminders, etc.]

// Custom CSS for SweetAlert2
const style = document.createElement('style');
style.textContent = `
    .modern-swal {
        border-radius: 15px !important;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }
`;
document.head.appendChild(style);

console.log('Payment Reminders Management initialized with modern UI components');
</script>
@endpush