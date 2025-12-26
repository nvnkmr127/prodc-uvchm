@extends('layouts.theme')

@section('title', 'Create Payment Reminder')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.channel-option {
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}
.channel-option:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}
.channel-option.selected {
    border-color: #007bff;
    background-color: #e3f2fd;
}
.reminder-preview {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 0.5rem;
}

/* Enhanced Category Selection */
.category-stats {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin-top: 0.5rem;
    border-left: 4px solid #007bff;
}

.risk-indicator-small {
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.risk-indicator-small.critical { background: #dc3545; color: white; }
.risk-indicator-small.high { background: #fd7e14; color: white; }
.risk-indicator-small.medium { background: #ffc107; color: #000; }
.risk-indicator-small.low { background: #198754; color: white; }

.bulk-reminder-section {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border-radius: 0.75rem;
    padding: 1.5rem;
    border: 2px solid #2196f3;
    margin-bottom: 1rem;
}

.student-risk-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 8px;
    font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus mr-2"></i>Create Payment Reminder
        </h1>
        <div>
            <a href="{{ route('admin.payment-reminders.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Reminders
            </a>
            <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-info btn-sm ml-2">
                <i class="fas fa-chart-pie mr-1"></i> Category Analysis
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Bulk Category Reminder Section -->
    <div class="bulk-reminder-section">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="mb-1 text-primary">
                    <i class="fas fa-broadcast-tower mr-2"></i>Category-Based Bulk Reminders
                </h5>
                <p class="text-muted mb-0 small">Send reminders to all students in specific fee categories</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#bulkReminderModal">
                <i class="fas fa-users mr-1"></i>Create Bulk Reminder
            </button>
        </div>
        
        <div class="row">
            @if(isset($categoryStats) && $categoryStats->count() > 0)
                @foreach($categoryStats->take(3) as $category)
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 font-weight-bold">{{ $category->name }}</h6>
                                <span class="risk-indicator-small {{ strtolower($category->risk_level ?? 'low') }}">
                                    {{ ucfirst($category->risk_level ?? 'Low') }}
                                </span>
                            </div>
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-muted d-block">Pending</small>
                                    <strong class="text-danger">{{ $category->pending_students ?? 0 }}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Amount</small>
                                    <strong class="text-warning">₹{{ number_format($category->total_pending ?? 0) }}</strong>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-2" 
                                    onclick="createCategoryReminder({{ $category->id }}, '{{ $category->name }}')">
                                <i class="fas fa-bell mr-1"></i>Send Category Reminders
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle mr-2"></i>
                        No category statistics available. <a href="{{ route('admin.fee-category-analysis.index') }}">View Fee Category Analysis</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user mr-2"></i>Individual Student Reminder
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.payment-reminders.store') }}" method="POST" id="reminderForm">
                        @csrf
                        
                        <!-- Student and Fee Component Selection -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">
                                    <i class="fas fa-user text-primary mr-1"></i>Student <span class="text-danger">*</span>
                                </label>
                                <select name="student_id" id="student_id" class="form-control select2" required>
                                    <option value="">Select Student</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" 
                                                data-email="{{ $student->email }}" 
                                                data-phone="{{ $student->student_mobile ?? $student->phone }}"
                                                data-father-mobile="{{ $student->father_mobile }}"
                                                data-risk="{{ $student->risk_level ?? 'low' }}"
                                                data-overdue="{{ $student->overdue_amount ?? 0 }}"
                                                {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                            {{ $student->name }} ({{ $student->enrollment_number }})
                                            @if(($student->overdue_amount ?? 0) > 0)
                                                - ₹{{ number_format($student->overdue_amount) }} overdue
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="student_fee_id" class="form-label">
                                    <i class="fas fa-money-bill text-primary mr-1"></i>Fee Component
                                </label>
                                <select name="student_fee_id" id="student_fee_id" class="form-control" disabled>
                                    <option value="">First select a student</option>
                                </select>
                                <small class="form-text text-muted">Leave empty to remind about all overdue fees</small>
                            </div>
                        </div>

                        <!-- Reminder Type and Category -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="reminder_type" class="form-label">
                                    <i class="fas fa-tag text-primary mr-1"></i>Reminder Type <span class="text-danger">*</span>
                                </label>
                                <select name="reminder_type" id="reminder_type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="gentle">Gentle Reminder</option>
                                    <option value="firm">Firm Reminder</option>
                                    <option value="urgent">Urgent Notice</option>
                                    <option value="final_notice">Final Notice</option>
                                    <option value="escalation">Escalation</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="fee_category_id" class="form-label">
                                    <i class="fas fa-tags text-primary mr-1"></i>Fee Category Filter
                                </label>
                                <select name="fee_category_id" id="fee_category_id" class="form-control">
                                    <option value="">All Categories</option>
                                    @foreach($feeCategories as $category)
                                        <option value="{{ $category->id }}" 
                                                data-pending="{{ $category->pending_students ?? 0 }}"
                                                data-risk="{{ $category->risk_level ?? 'low' }}"
                                                {{ request('fee_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                            @if(($category->pending_students ?? 0) > 0)
                                                ({{ $category->pending_students }} pending)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Filter by specific fee category</small>
                            </div>
                        </div>

                        <!-- Communication Channel -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-satellite-dish text-primary mr-1"></i>Communication Channel <span class="text-danger">*</span>
                            </label>
                            <div class="row">
                                @php
                                $channels = [
                                    'email' => ['icon' => 'envelope', 'label' => 'Email'],
                                    'sms' => ['icon' => 'sms', 'label' => 'SMS'],
                                    'whatsapp' => ['icon' => 'whatsapp', 'label' => 'WhatsApp'],
                                    'phone_call' => ['icon' => 'phone', 'label' => 'Phone Call'],
                                    'physical_notice' => ['icon' => 'file-alt', 'label' => 'Physical Notice']
                                ];
                                @endphp
                                
                                @foreach($channels as $channel => $config)
                                <div class="col-md-4 mb-2">
                                    <div class="channel-option" data-channel="{{ $channel }}">
                                        <div class="text-center">
                                            <i class="fab fa-{{ $config['icon'] }} fa-2x mb-2"></i>
                                            <div class="font-weight-bold">{{ $config['label'] }}</div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <input type="hidden" name="channel" id="selected_channel" value="" required>
                        </div>

                        <!-- Message Template -->
                        <div class="mb-3">
                            <label for="message_template" class="form-label">
                                <i class="fas fa-templates text-primary mr-1"></i>Message Template
                            </label>
                            <select id="message_template" class="form-control">
                                <option value="">Select a template</option>
                                <option value="gentle_reminder">Gentle Payment Reminder</option>
                                <option value="firm_reminder">Firm Payment Notice</option>
                                <option value="urgent_notice">Urgent Payment Notice</option>
                                <option value="final_notice">Final Payment Notice</option>
                                <option value="escalation_notice">Escalation Notice</option>
                                <option value="category_specific">Category-Specific Reminder</option>
                            </select>
                        </div>

                        <!-- Message Content -->
                        <div class="mb-3">
                            <label for="message_content" class="form-label">
                                <i class="fas fa-comment text-primary mr-1"></i>Message Content <span class="text-danger">*</span>
                            </label>
                            <div class="template-variables mb-2">
                                <small>
                                    <strong>Available variables:</strong> 
                                    {student_name}, {enrollment_number}, {amount}, {due_date}, {college_name}, {fee_type}, {days_overdue}, {category_name}, {total_pending}
                                </small>
                            </div>
                            <textarea name="message_content" id="message_content" class="form-control" rows="4" 
                                      placeholder="Enter your reminder message here..." required></textarea>
                            
                            <div class="reminder-preview" id="message_preview" style="display: none;">
                                <strong>Preview:</strong>
                                <div id="preview_content" class="mt-2"></div>
                            </div>
                        </div>

                        <!-- Scheduling -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">
                                    <i class="fas fa-calendar text-primary mr-1"></i>Scheduled Date & Time <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" name="scheduled_date" id="scheduled_date" class="form-control"
                                       value="{{ now()->format('Y-m-d\TH:i') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">
                                    <i class="fas fa-flag text-primary mr-1"></i>Initial Status
                                </label>
                                <select name="status" id="status" class="form-control">
                                    <option value="pending">Pending</option>
                                    <option value="scheduled">Scheduled</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Create Reminder
                                </button>
                                
                                <button type="submit" name="send_now" value="1" class="btn btn-success">
                                    <i class="fas fa-paper-plane mr-1"></i> Create & Send Now
                                </button>
                            </div>
                            
                            <div>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo mr-1"></i> Reset Form
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Student Info Preview -->
            <div class="card shadow mb-4" id="student-info" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user mr-1"></i>Student Information
                    </h6>
                </div>
                <div class="card-body">
                    <div id="student-details"></div>
                </div>
            </div>

            <!-- Fee Component Info -->
            <div class="card shadow mb-4" id="fee-info" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-money-bill mr-1"></i>Fee Component Details
                    </h6>
                </div>
                <div class="card-body">
                    <div id="fee-details"></div>
                </div>
            </div>

            <!-- Category Statistics -->
            <div class="card shadow mb-4" id="category-stats" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-chart-bar mr-1"></i>Category Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div id="category-details"></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-bolt mr-1"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.fee-category-analysis.critical-defaulters') }}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-exclamation-triangle mr-1"></i>View Critical Students
                        </a>
                        <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-chart-pie mr-1"></i>Category Analysis
                        </a>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadHighRiskStudents()">
                            <i class="fas fa-search mr-1"></i>Load High-Risk Students
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-lightbulb mr-1"></i>Enhanced Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <small>Use category-based reminders for bulk operations</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <small>Check student risk levels before sending reminders</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <small>Schedule reminders during optimal hours (9 AM - 6 PM)</small>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success mr-2"></i>
                            <small>Monitor category performance in Fee Analysis</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Reminder Modal -->
<div class="modal fade" id="bulkReminderModal" tabindex="-1" role="dialog" aria-labelledby="bulkReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkReminderModalLabel">
                    <i class="fas fa-users mr-2"></i>Create Bulk Category Reminder
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="bulkReminderForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="bulk_category_id">Fee Category <span class="text-danger">*</span></label>
                                <select id="bulk_category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    @foreach($feeCategories as $category)
                                        <option value="{{ $category->id }}" 
                                                data-pending="{{ $category->pending_students ?? 0 }}"
                                                data-amount="{{ $category->total_pending ?? 0 }}">
                                            {{ $category->name }} ({{ $category->pending_students ?? 0 }} pending)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="bulk_reminder_type">Reminder Type <span class="text-danger">*</span></label>
                                <select id="bulk_reminder_type" class="form-control" required>
                                    <option value="gentle">Gentle Reminder</option>
                                    <option value="firm">Firm Reminder</option>
                                    <option value="urgent">Urgent Notice</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="overdue_only" checked>
                                <label class="form-check-label" for="overdue_only">
                                    Only overdue payments
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="min_amount">Minimum Amount (₹)</label>
                                <input type="number" id="min_amount" class="form-control" value="1000" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_message">Message Template</label>
                        <textarea id="bulk_message" class="form-control" rows="4" 
                                  placeholder="Dear {student_name}, this is a reminder about your {category_name} payment..."></textarea>
                    </div>
                    
                    <div id="bulk_preview" class="category-stats" style="display: none;">
                        <h6>Preview:</h6>
                        <div id="bulk_preview_content"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i>Create Bulk Reminders
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: "Select Student",
        allowClear: true,
        templateResult: formatStudentOption,
        templateSelection: formatStudentSelection
    });

    // Custom formatting for student options
    function formatStudentOption(student) {
        if (!student.id) return student.text;
        
        const $student = $(student.element);
        const risk = $student.data('risk') || 'low';
        const overdue = $student.data('overdue') || 0;
        
        let $result = $('<span></span>');
        $result.text(student.text);
        
        if (overdue > 0) {
            $result.append(`<span class="student-risk-badge bg-danger text-white ml-2">₹${Number(overdue).toLocaleString()} overdue</span>`);
        }
        
        return $result;
    }

    function formatStudentSelection(student) {
        return student.text;
    }

    // Enhanced message templates
    const templates = {
        'gentle_reminder': "Dear {student_name}, this is a friendly reminder that your {fee_type} payment of ₹{amount} is due. Please make the payment at your earliest convenience. Thank you. - {college_name}",
        'firm_reminder': "Dear {student_name}, your {fee_type} payment of ₹{amount} is now overdue. Please make the payment immediately to avoid any inconvenience. - {college_name}",
        'urgent_notice': "URGENT: Dear {student_name}, your {fee_type} payment of ₹{amount} is {days_overdue} days overdue. Immediate payment is required. Please contact us if you need assistance. - {college_name}",
        'final_notice': "FINAL NOTICE: Dear {student_name}, this is your final notice for the overdue payment of ₹{amount} for {fee_type}. Please pay immediately to avoid further action. - {college_name}",
        'escalation_notice': "Dear {student_name}, your payment matter has been escalated due to non-payment of ₹{amount} for {fee_type}. Please contact the accounts office immediately. - {college_name}",
        'category_specific': "Dear {student_name}, we notice that your {category_name} payment of ₹{amount} is pending. Please complete this payment to continue enjoying our services. - {college_name}"
    };

    // Template selection
    $('#message_template').change(function() {
        const template = $(this).val();
        if (template && templates[template]) {
            $('#message_content').val(templates[template]);
            updateMessagePreview();
        }
    });

    // Channel selection
    $('.channel-option').click(function() {
        $('.channel-option').removeClass('selected');
        $(this).addClass('selected');
        $('#selected_channel').val($(this).data('channel'));
        updateMessagePreview();
    });

    // Student selection
    $('#student_id').change(function() {
        const studentId = $(this).val();
        
        if (studentId) {
            const selectedOption = $(this).find('option:selected');
            const risk = selectedOption.data('risk') || 'low';
            const overdue = selectedOption.data('overdue') || 0;
            
            // Show student info with risk assessment
            const studentInfo = `
                <div class="mb-2">
                    <strong>Name:</strong><br>
                    <span class="text-muted">${selectedOption.text().split(' (')[0]}</span>
                </div>
                <div class="mb-2">
                    <strong>Risk Level:</strong><br>
                    <span class="risk-indicator-small ${risk}">${risk.toUpperCase()}</span>
                </div>
                <div class="mb-0">
                    <strong>Students Pending:</strong><br>
                    <span class="text-warning font-weight-bold">${pending} students</span>
                </div>
            `;
            $('#category-details').html(categoryInfo);
            $('#category-stats').show();
        } else {
            $('#category-stats').hide();
        }
        
        updateMessagePreview();
    });

    // Student fee selection
    $('#student_fee_id').change(function() {
        const feeId = $(this).val();
        
        if (feeId) {
            const selectedOption = $(this).find('option:selected');
            const feeInfo = `
                <div class="mb-2">
                    <strong>Category:</strong><br>
                    <span class="text-muted">${selectedOption.data('category')}</span>
                </div>
                <div class="mb-2">
                    <strong>Amount:</strong><br>
                    <span class="text-success font-weight-bold">₹${selectedOption.data('amount')}</span>
                </div>
                <div class="mb-2">
                    <strong>Remaining:</strong><br>
                    <span class="text-danger font-weight-bold">₹${selectedOption.data('remaining')}</span>
                </div>
                <div class="mb-0">
                    <strong>Due Date:</strong><br>
                    <span class="text-muted">${selectedOption.data('due-date')}</span>
                </div>
            `;
            $('#fee-details').html(feeInfo);
            $('#fee-info').show();
        } else {
            $('#fee-info').hide();
        }
        
        updateMessagePreview();
    });

    // Message content change
    $('#message_content, #reminder_type').on('input change', function() {
        updateMessagePreview();
    });

    // Bulk reminder form
    $('#bulkReminderForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            fee_category_id: $('#bulk_category_id').val(),
            reminder_type: $('#bulk_reminder_type').val(),
            include_overdue_only: $('#overdue_only').is(':checked'),
            minimum_amount: $('#min_amount').val(),
            message_content: $('#bulk_message').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("admin.fee-category-analysis.send-reminders", ":categoryId") }}'.replace(':categoryId', formData.fee_category_id),
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#bulkReminderModal').modal('hide');
                    alert('Bulk reminders created successfully! ' + response.message);
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function() {
                alert('An error occurred while creating bulk reminders.');
            }
        });
    });

    function loadStudentFees(studentId) {
        $.get(`/admin/api/students/${studentId}/unpaid-fees`, function(data) {
            let options = '<option value="">All unpaid fee components</option>';
            
            data.forEach(function(fee) {
                const remainingAmount = fee.amount - (fee.paid_amount || 0) - (fee.concession_amount || 0);
                options += `<option value="${fee.id}" 
                                   data-category="${fee.fee_category.name}"
                                   data-amount="${fee.amount}"
                                   data-remaining="${remainingAmount}"
                                   data-due-date="${fee.due_date_formatted}">
                               ${fee.fee_category.name} - ₹${remainingAmount.toFixed(2)} remaining
                           </option>`;
            });
            
            $('#student_fee_id').prop('disabled', false).html(options);
        }).fail(function() {
            $('#student_fee_id').prop('disabled', true).html('<option value="">Error loading fees</option>');
        });
    }

    function updateMessagePreview() {
        const messageContent = $('#message_content').val();
        const studentId = $('#student_id').val();
        const feeId = $('#student_fee_id').val();
        const categoryId = $('#fee_category_id').val();
        
        if (messageContent && studentId) {
            let previewText = messageContent;
            
            // Get student data
            const selectedStudent = $('#student_id option:selected');
            const studentName = selectedStudent.text().split(' (')[0];
            const enrollmentNumber = selectedStudent.text().match(/\(([^)]+)\)/)?.[1] || '';
            
            // Get fee data if selected
            let amount = '5,000.00';
            let feeType = 'Fee';
            let dueDate = new Date().toLocaleDateString();
            let categoryName = 'General';
            let totalPending = selectedStudent.data('overdue') || '5,000.00';
            
            if (feeId) {
                const selectedFee = $('#student_fee_id option:selected');
                amount = selectedFee.data('remaining') || amount;
                feeType = selectedFee.data('category') || feeType;
                dueDate = selectedFee.data('due-date') || dueDate;
                categoryName = selectedFee.data('category') || categoryName;
            } else if (categoryId) {
                const selectedCategory = $('#fee_category_id option:selected');
                categoryName = selectedCategory.text().split(' (')[0];
                feeType = categoryName;
            }
            
            // Replace variables
            previewText = previewText
                .replace(/{student_name}/g, studentName)
                .replace(/{enrollment_number}/g, enrollmentNumber)
                .replace(/{amount}/g, amount)
                .replace(/{due_date}/g, dueDate)
                .replace(/{college_name}/g, '{{ config("app.name") }}')
                .replace(/{fee_type}/g, feeType)
                .replace(/{category_name}/g, categoryName)
                .replace(/{total_pending}/g, totalPending)
                .replace(/{days_overdue}/g, '5'); // Sample value

            $('#preview_content').text(previewText);
            $('#message_preview').show();
        } else {
            $('#message_preview').hide();
        }
    }

    // Form validation
    $('#reminderForm').submit(function(e) {
        if (!$('#selected_channel').val()) {
            e.preventDefault();
            alert('Please select a communication channel.');
            return false;
        }
        
        if (!$('#student_id').val()) {
            e.preventDefault();
            alert('Please select a student.');
            return false;
        }
    });

    // Auto-select first student if pre-selected
    if ($('#student_id').val()) {
        $('#student_id').trigger('change');
    }

    // Auto-select category if pre-selected
    if ($('#fee_category_id').val()) {
        $('#fee_category_id').trigger('change');
    }
});

// Quick action functions
function createCategoryReminder(categoryId, categoryName) {
    // Pre-fill the bulk reminder modal
    $('#bulk_category_id').val(categoryId);
    $('#bulk_message').val(`Dear {student_name}, this is a reminder about your ${categoryName} payment of ₹{amount}. Please complete this payment at your earliest convenience. Thank you. - {college_name}`);
    $('#bulkReminderModal').modal('show');
}

function loadHighRiskStudents() {
    // Filter students by high risk
    const $studentSelect = $('#student_id');
    const $options = $studentSelect.find('option');
    
    $options.each(function() {
        const $option = $(this);
        const risk = $option.data('risk');
        const overdue = $option.data('overdue') || 0;
        
        if (risk === 'critical' || risk === 'high' || overdue > 10000) {
            $option.show();
        } else if ($option.val() !== '') {
            $option.hide();
        }
    });
    
    $studentSelect.select2('destroy').select2({
        placeholder: "High-risk students only",
        allowClear: true,
        templateResult: formatStudentOption,
        templateSelection: formatStudentSelection
    });
    
    alert('Student list filtered to show only high-risk students with significant overdue amounts.');
}

// Bulk reminder preview update
$('#bulk_category_id, #bulk_message').on('change input', function() {
    const categoryId = $('#bulk_category_id').val();
    const message = $('#bulk_message').val();
    
    if (categoryId && message) {
        const selectedCategory = $('#bulk_category_id option:selected');
        const pending = selectedCategory.data('pending') || 0;
        const amount = selectedCategory.data('amount') || 0;
        
        let previewText = message
            .replace(/{student_name}/g, 'John Doe')
            .replace(/{category_name}/g, selectedCategory.text().split(' (')[0])
            .replace(/{amount}/g, '5,000.00')
            .replace(/{college_name}/g, '{{ config("app.name") }}');
        
        $('#bulk_preview_content').html(`
            <div class="mb-2"><strong>Will send to:</strong> ${pending} students</div>
            <div class="mb-2"><strong>Total amount:</strong> ₹${Number(amount).toLocaleString()}</div>
            <div class="mb-0"><strong>Sample message:</strong><br><em>${previewText}</em></div>
        `);
        $('#bulk_preview').show();
    } else {
        $('#bulk_preview').hide();
    }
});

console.log('Enhanced Payment Reminder Creator with Fee Category Analysis integration loaded');
</script>
@endpush