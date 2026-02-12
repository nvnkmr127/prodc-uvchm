@extends('layouts.theme')

@section('title', 'Payment Reminder Settings')

@push('styles')
<style>
    .settings-card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.3s ease;
    }
    
    .settings-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }
    
    .settings-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px 10px 0 0;
        padding: 1.5rem;
    }
    
    .stats-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border: none;
        border-radius: 15px;
        color: white;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    
    .stats-card.info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stats-card.success {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .stats-card.warning {
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        color: #333;
    }
    
    .stats-card.primary {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        color: #333;
    }
    
    .form-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #667eea;
    }
    
    .custom-switch .custom-control-label::before {
        border-radius: 50px;
        background: #e9ecef;
    }
    
    .custom-switch .custom-control-input:checked~.custom-control-label::before {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
    }
    
    .custom-switch .custom-control-label::after {
        border-radius: 50px;
    }
    
    .btn-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 10px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-test {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border: none;
        border-radius: 20px;
        color: white;
        font-weight: 500;
    }
    
    .btn-test:hover {
        background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
        color: white;
        transform: scale(1.05);
    }
    
    .feature-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 1.5rem;
    }
    
    .setup-step {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border-left: 4px solid #28a745;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .setup-step.pending {
        border-left-color: #ffc107;
    }
    
    .setup-step.completed {
        border-left-color: #28a745;
    }
    
    .progress-indicator {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    
    .progress-bar-animated {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        animation: progress-animation 2s ease-in-out;
    }
    
    @keyframes progress-animation {
        0% { width: 0%; }
        100% { width: var(--progress-width); }
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }
    
    .channel-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-bottom: 1rem;
    }
    
    .channel-card:hover {
        border-color: #667eea;
        background: #f8f9fa;
    }
    
    .channel-card.active {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="settings-card">
                <div class="settings-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">
                                <i class="fas fa-bell-slash mr-3"></i>
                                Payment Reminder Center
                            </h2>
                            <p class="mb-0 opacity-75">Automate and manage payment notifications efficiently</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-test btn-lg" id="testReminderBtn">
                                <i class="fas fa-rocket mr-2"></i> Test System
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($error))
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info border-0" style="border-radius: 15px; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                    <div class="d-flex align-items-center">
                        <div class="feature-icon mr-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 text-primary">Setup in Progress</h5>
                            <p class="mb-0">{{ $error }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Dashboard -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card info h-100">
                <div class="card-body text-center">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-2">{{ $stats['pending_reminders'] ?? 0 }}</h3>
                    <p class="mb-0">Pending Reminders</p>
                    <small class="opacity-75">Awaiting Processing</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card success h-100">
                <div class="card-body text-center">
                    <div class="feature-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h3 class="mb-2">{{ $stats['sent_today'] ?? 0 }}</h3>
                    <p class="mb-0">Sent Today</p>
                    <small class="opacity-75">Successfully Delivered</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card warning h-100">
                <div class="card-body text-center">
                    <div class="feature-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="mb-2">{{ $stats['failed_reminders'] ?? 0 }}</h3>
                    <p class="mb-0">Failed Attempts</p>
                    <small class="opacity-75">Need Attention</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card primary h-100">
                <div class="card-body text-center">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="mb-2">{{ $stats['success_rate'] ?? 0 }}%</h3>
                    <p class="mb-0">Success Rate</p>
                    <small class="opacity-75">Overall Performance</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="{{ route('admin.settings.payment-reminders.update') }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- General Settings -->
            <div class="col-lg-6 mb-4">
                <div class="settings-card h-100">
                    <div class="card-header bg-primary text-white" style="border-radius: 10px 10px 0 0;">
                        <h4 class="mb-0">
                            <i class="fas fa-cogs mr-2"></i>
                            General Configuration
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <div class="custom-control custom-switch custom-switch-lg mb-3">
                                <input type="checkbox" class="custom-control-input" 
                                       id="payment_reminders_enabled" name="payment_reminders_enabled" value="1"
                                       {{ (isset($settings['payment_reminders']) && $settings['payment_reminders']->where('key', 'payment_reminders_enabled')->first() && $settings['payment_reminders']->where('key', 'payment_reminders_enabled')->first()->value == '1') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="payment_reminders_enabled">
                                    <strong>Enable Payment Reminders</strong>
                                    <br><small class="text-muted">Master switch for the entire reminder system</small>
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reminder_days_before" class="font-weight-bold">
                                        <i class="fas fa-calendar-alt text-primary mr-1"></i>
                                        Days Before Due
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="reminder_days_before" 
                                           name="reminder_days_before" min="1" max="30"
                                           value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'reminder_days_before')->first()->value ?? 7 : 7 }}">
                                    <small class="form-text text-muted">First reminder timing</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reminder_days_urgent" class="font-weight-bold">
                                        <i class="fas fa-exclamation text-warning mr-1"></i>
                                        Urgent Reminder
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="reminder_days_urgent" 
                                           name="reminder_days_urgent" min="1" max="7"
                                           value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'reminder_days_urgent')->first()->value ?? 3 : 3 }}">
                                    <small class="form-text text-muted">Urgent notification timing</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="overdue_reminder_frequency" class="font-weight-bold">
                                        <i class="fas fa-redo text-danger mr-1"></i>
                                        Overdue Frequency
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="overdue_reminder_frequency" 
                                           name="overdue_reminder_frequency" min="1" max="30"
                                           value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'overdue_reminder_frequency')->first()->value ?? 7 : 7 }}">
                                    <small class="form-text text-muted">Days between overdue reminders</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="escalation_days" class="font-weight-bold">
                                        <i class="fas fa-arrow-up text-danger mr-1"></i>
                                        Escalation Trigger
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="escalation_days" 
                                           name="escalation_days" min="1" max="90"
                                           value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'escalation_days')->first()->value ?? 30 : 30 }}">
                                    <small class="form-text text-muted">Days before escalation</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="final_notice_days" class="font-weight-bold">
                                        <i class="fas fa-gavel text-dark mr-1"></i>
                                        Final Notice
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="final_notice_days" 
                                           name="final_notice_days" min="1" max="180"
                                           value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'final_notice_days')->first()->value ?? 45 : 45 }}">
                                    <small class="form-text text-muted">Days before final action</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reminder_time" class="font-weight-bold">
                                        <i class="fas fa-clock text-info mr-1"></i>
                                        Send Time
                                    </label>
                                    <input type="time" class="form-control form-control-lg" id="reminder_time" 
                                           name="reminder_time"
                                           value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'reminder_time')->first()->value ?? '09:00' : '09:00' }}">
                                    <small class="form-text text-muted">Daily sending time</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Communication Channels -->
            <div class="col-lg-6 mb-4">
                <div class="settings-card h-100">
                    <div class="card-header bg-success text-white" style="border-radius: 10px 10px 0 0;">
                        <h4 class="mb-0">
                            <i class="fas fa-broadcast-tower mr-2"></i>
                            Communication Channels
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="channel-card {{ (isset($settings['communication']) && $settings['communication']->where('key', 'email_reminders_enabled')->first() && $settings['communication']->where('key', 'email_reminders_enabled')->first()->value == '1') ? 'active' : '' }}">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" 
                                       id="email_reminders_enabled" name="email_reminders_enabled" value="1"
                                       {{ (isset($settings['communication']) && $settings['communication']->where('key', 'email_reminders_enabled')->first() && $settings['communication']->where('key', 'email_reminders_enabled')->first()->value == '1') ? 'checked' : 'checked' }}>
                                <label class="custom-control-label w-100" for="email_reminders_enabled">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-envelope fa-2x mr-3 text-primary"></i>
                                        <div class="text-left">
                                            <strong>Email Notifications</strong>
                                            <br><small>Primary communication channel</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="channel-card {{ (isset($settings['communication']) && $settings['communication']->where('key', 'sms_reminders_enabled')->first() && $settings['communication']->where('key', 'sms_reminders_enabled')->first()->value == '1') ? 'active' : '' }}">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" 
                                       id="sms_reminders_enabled" name="sms_reminders_enabled" value="1"
                                       {{ (isset($settings['communication']) && $settings['communication']->where('key', 'sms_reminders_enabled')->first() && $settings['communication']->where('key', 'sms_reminders_enabled')->first()->value == '1') ? 'checked' : 'checked' }}>
                                <label class="custom-control-label w-100" for="sms_reminders_enabled">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-sms fa-2x mr-3 text-success"></i>
                                        <div class="text-left">
                                            <strong>SMS Messages</strong>
                                            <br><small>Instant text notifications</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="channel-card {{ (isset($settings['communication']) && $settings['communication']->where('key', 'whatsapp_reminders_enabled')->first() && $settings['communication']->where('key', 'whatsapp_reminders_enabled')->first()->value == '1') ? 'active' : '' }}">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" 
                                       id="whatsapp_reminders_enabled" name="whatsapp_reminders_enabled" value="1"
                                       {{ (isset($settings['communication']) && $settings['communication']->where('key', 'whatsapp_reminders_enabled')->first() && $settings['communication']->where('key', 'whatsapp_reminders_enabled')->first()->value == '1') ? 'checked' : '' }}>
                                <label class="custom-control-label w-100" for="whatsapp_reminders_enabled">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fab fa-whatsapp fa-2x mr-3 text-success"></i>
                                        <div class="text-left">
                                            <strong>WhatsApp Business</strong>
                                            <br><small>Modern messaging platform</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="font-weight-bold mb-3">
                                <i class="fas fa-tasks mr-2"></i>
                                Setup Progress
                            </h6>
                            <div class="progress-indicator">
                                <div class="progress-bar progress-bar-animated" style="--progress-width: 40%; width: 40%;"></div>
                            </div>
                            
                            <div class="setup-step completed">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success mr-3"></i>
                                    <div>
                                        <strong>Settings Interface</strong>
                                        <br><small class="text-muted">Configuration panel is ready</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setup-step pending">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-warning mr-3"></i>
                                    <div>
                                        <strong>Database Models</strong>
                                        <br><small class="text-muted">Create PaymentReminder model & migration</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setup-step pending">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-warning mr-3"></i>
                                    <div>
                                        <strong>Provider Integration</strong>
                                        <br><small class="text-muted">Configure SMS/WhatsApp services</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-gradient btn-lg mr-3">
                    <i class="fas fa-save mr-2"></i> Save Configuration
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Enhanced Test Reminder Modal -->
<div class="modal fade" id="testReminderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-rocket mr-2"></i>
                    Test Payment Reminder System
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="testReminderForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="font-weight-bold mb-3">Select Communication Channel</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="channel-card" data-channel="email">
                                        <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                        <br><strong>Email</strong>
                                        <br><small>Test email delivery</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="channel-card" data-channel="sms">
                                        <i class="fas fa-sms fa-2x text-success mb-2"></i>
                                        <br><strong>SMS</strong>
                                        <br><small>Test SMS sending</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="channel-card" data-channel="whatsapp">
                                        <i class="fab fa-whatsapp fa-2x text-success mb-2"></i>
                                        <br><strong>WhatsApp</strong>
                                        <br><small>Test WhatsApp API</small>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="test_channel" name="channel" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="test_recipient" class="font-weight-bold">
                            <i class="fas fa-user mr-2"></i>
                            Recipient Details
                        </label>
                        <input type="text" class="form-control form-control-lg" id="test_recipient" name="recipient" 
                               placeholder="Enter email address or phone number" required>
                        <small class="form-text text-muted">We'll send a test notification to this address</small>
                    </div>

                    <div class="form-group">
                        <label for="test_message" class="font-weight-bold">
                            <i class="fas fa-comment mr-2"></i>
                            Test Message
                        </label>
                        <textarea class="form-control" id="test_message" name="message" rows="4" 
                                  placeholder="Enter test message..." required>Dear Test User, this is a test payment reminder from your college management system. The reminder system is working perfectly! Please disregard this test message.</textarea>
                        <small class="form-text text-muted">Maximum 500 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-gradient">
                        <i class="fas fa-rocket mr-2"></i> Launch Test
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Channel selection for switches
    $('.custom-control-input').change(function() {
        const card = $(this).closest('.channel-card');
        if ($(this).is(':checked')) {
            card.addClass('active');
        } else {
            card.removeClass('active');
        }
    });

    // Test modal channel selection
    $('.channel-card[data-channel]').click(function() {
        $('.channel-card[data-channel]').removeClass('active');
        $(this).addClass('active');
        const channel = $(this).data('channel');
        $('#test_channel').val(channel);
        
        // Update recipient placeholder
        const recipientInput = $('#test_recipient');
        if (channel === 'email') {
            recipientInput.attr('placeholder', 'Enter email address (e.g., test@example.com)').attr('type', 'email');
        } else if (channel === 'sms' || channel === 'whatsapp') {
            recipientInput.attr('placeholder', 'Enter phone number (e.g., +1234567890)').attr('type', 'tel');
        }
    });

    // Test Reminder Modal
    $('#testReminderBtn').click(function() {
        $('#testReminderModal').modal('show');
        // Reset form
        $('.channel-card[data-channel]').removeClass('active');
        $('#test_channel').val('');
        $('#testReminderForm')[0].reset();
    });

    // Test Reminder Form Submission
    $('#testReminderForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!$('#test_channel').val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Select Channel',
                text: 'Please select a communication channel first!'
            });
            return;
        }
        
        const formData = {
            channel: $('#test_channel').val(),
            recipient: $('#test_recipient').val(),
            message: $('#test_message').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("admin.settings.payment-reminders.test") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#testReminderModal .modal-footer button[type="submit"]').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin mr-2"></i> Launching Test...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Test Successful!',
                        text: response.message,
                        timer: 4000,
                        showConfirmButton: false
                    });
                    $('#testReminderModal').modal('hide');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Test Failed',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                let message = 'An error occurred while sending the test reminder.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Test Failed',
                    text: message
                });
            },
            complete: function() {
                $('#testReminderModal .modal-footer button[type="submit"]').prop('disabled', false)
                    .html('<i class="fas fa-rocket mr-2"></i> Launch Test');
            }
        });
    });

    // Form submission with loading state
    $('form').on('submit', function(e) {
        const submitBtn = $(this).find('button[type="submit"]');
        const originalHtml = submitBtn.html();
        
        submitBtn.prop('disabled', true)
                 .html('<i class="fas fa-spinner fa-spin mr-2"></i> Saving...');
        
        // Re-enable after 3 seconds to prevent permanent disable on validation errors
        setTimeout(function() {
            submitBtn.prop('disabled', false).html(originalHtml);
        }, 3000);
    });

    // Animate stats cards on load
    $('.stats-card').each(function(index) {
        $(this).delay(index * 100).animate({
            opacity: 1
        }, 500);
    });

    // Tooltip initialization
    $('[data-toggle="tooltip"]').tooltip();

    // Progress bar animation
    $('.progress-bar-animated').each(function() {
        const width = $(this).data('width') || $(this).css('--progress-width');
        $(this).animate({
            width: width
        }, 2000);
    });

    // Character counter for message textarea
    $('#test_message').on('input', function() {
        const maxLength = 500;
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;
        
        let counterHtml = `<small class="form-text ${remaining < 50 ? 'text-danger' : 'text-muted'}">
            ${remaining} characters remaining
        </small>`;
        
        $(this).next('.form-text').remove();
        $(this).after(counterHtml);
        
        if (remaining < 0) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Success message auto-hide
    if ($('.alert-success').length) {
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 5000);
    }

    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if( target.length ) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });

    // Add ripple effect to buttons
    $('.btn').on('click', function(e) {
        const btn = $(this);
        const ripple = $('<span class="ripple"></span>');
        
        btn.prepend(ripple);
        
        const btnOffset = btn.offset();
        const xPos = e.pageX - btnOffset.left;
        const yPos = e.pageY - btnOffset.top;
        
        ripple.css({
            top: yPos + 'px',
            left: xPos + 'px'
        }).addClass('ripple-effect');
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});

// Add CSS for ripple effect
const rippleCSS = `
    <style>
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        .ripple-effect {
            animation: ripple-animation 0.6s linear;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .stats-card {
            opacity: 0;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .bounce-in {
            animation: bounceIn 0.8s ease-out;
        }
        
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
`;

$('head').append(rippleCSS);
</script>
@endpush

@section('title', 'Payment Reminder Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i>
                        Payment Reminder Settings
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-info" id="testReminderBtn">
                            <i class="fas fa-paper-plane"></i> Test Reminder
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($error))
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ $error }}
                            <br><small>Please create the PaymentReminder models and run migrations to enable full functionality.</small>
                        </div>
                    @endif

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $stats['pending_reminders'] ?? 0 }}</h3>
                                    <p>Pending Reminders</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $stats['sent_today'] ?? 0 }}</h3>
                                    <p>Sent Today</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $stats['failed_reminders'] ?? 0 }}</h3>
                                    <p>Failed Reminders</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3>{{ $stats['success_rate'] ?? 0 }}%</h3>
                                    <p>Success Rate</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.payment-reminders.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Payment Reminder Settings -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">General Settings</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="payment_reminders_enabled" name="payment_reminders_enabled" value="1"
                                                       {{ (isset($settings['payment_reminders']) && $settings['payment_reminders']->where('key', 'payment_reminders_enabled')->first() && $settings['payment_reminders']->where('key', 'payment_reminders_enabled')->first()->value == '1') ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="payment_reminders_enabled">
                                                    Enable Payment Reminders
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="reminder_days_before">Days Before Due Date</label>
                                            <input type="number" class="form-control" id="reminder_days_before" 
                                                   name="reminder_days_before" min="1" max="30"
                                                   value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'reminder_days_before')->first()->value ?? 7 : 7 }}">
                                            <small class="form-text text-muted">Send first reminder this many days before due date</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="reminder_days_urgent">Urgent Reminder Days</label>
                                            <input type="number" class="form-control" id="reminder_days_urgent" 
                                                   name="reminder_days_urgent" min="1" max="7"
                                                   value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'reminder_days_urgent')->first()->value ?? 3 : 3 }}">
                                            <small class="form-text text-muted">Send urgent reminder this many days before due date</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="overdue_reminder_frequency">Overdue Reminder Frequency (Days)</label>
                                            <input type="number" class="form-control" id="overdue_reminder_frequency" 
                                                   name="overdue_reminder_frequency" min="1" max="30"
                                                   value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'overdue_reminder_frequency')->first()->value ?? 7 : 7 }}">
                                        </div>

                                        <div class="form-group">
                                            <label for="escalation_days">Escalation After Days</label>
                                            <input type="number" class="form-control" id="escalation_days" 
                                                   name="escalation_days" min="1" max="90"
                                                   value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'escalation_days')->first()->value ?? 30 : 30 }}">
                                        </div>

                                        <div class="form-group">
                                            <label for="final_notice_days">Final Notice After Days</label>
                                            <input type="number" class="form-control" id="final_notice_days" 
                                                   name="final_notice_days" min="1" max="180"
                                                   value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'final_notice_days')->first()->value ?? 45 : 45 }}">
                                        </div>

                                        <div class="form-group">
                                            <label for="reminder_time">Daily Reminder Time</label>
                                            <input type="time" class="form-control" id="reminder_time" 
                                                   name="reminder_time"
                                                   value="{{ isset($settings['payment_reminders']) ? $settings['payment_reminders']->where('key', 'reminder_time')->first()->value ?? '09:00' : '09:00' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Communication Settings -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Communication Channels</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="email_reminders_enabled" name="email_reminders_enabled" value="1"
                                                       {{ (isset($settings['communication']) && $settings['communication']->where('key', 'email_reminders_enabled')->first() && $settings['communication']->where('key', 'email_reminders_enabled')->first()->value == '1') ? 'checked' : 'checked' }}>
                                                <label class="custom-control-label" for="email_reminders_enabled">
                                                    <i class="fas fa-envelope"></i> Enable Email Reminders
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="sms_reminders_enabled" name="sms_reminders_enabled" value="1"
                                                       {{ (isset($settings['communication']) && $settings['communication']->where('key', 'sms_reminders_enabled')->first() && $settings['communication']->where('key', 'sms_reminders_enabled')->first()->value == '1') ? 'checked' : 'checked' }}>
                                                <label class="custom-control-label" for="sms_reminders_enabled">
                                                    <i class="fas fa-sms"></i> Enable SMS Reminders
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="whatsapp_reminders_enabled" name="whatsapp_reminders_enabled" value="1"
                                                       {{ (isset($settings['communication']) && $settings['communication']->where('key', 'whatsapp_reminders_enabled')->first() && $settings['communication']->where('key', 'whatsapp_reminders_enabled')->first()->value == '1') ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="whatsapp_reminders_enabled">
                                                    <i class="fab fa-whatsapp"></i> Enable WhatsApp Reminders
                                                </label>
                                            </div>
                                        </div>

                                        <div class="alert alert-info">
                                            <h5><i class="fas fa-info-circle"></i> Setup Information</h5>
                                            <p class="mb-0">
                                                <strong>Current Status:</strong> Settings interface is ready.<br>
                                                <strong>Next Steps:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li>Create PaymentReminder model and migration</li>
                                                    <li>Run migrations to create database tables</li>
                                                    <li>Configure email/SMS providers</li>
                                                    <li>Enable automated reminder scheduling</li>
                                                </ul>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Reminder Modal -->
<div class="modal fade" id="testReminderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Payment Reminder</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="testReminderForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="test_channel">Channel</label>
                        <select class="form-control" id="test_channel" name="channel" required>
                            <option value="">Select Channel</option>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="test_recipient">Recipient</label>
                        <input type="text" class="form-control" id="test_recipient" name="recipient" 
                               placeholder="Email address or phone number" required>
                    </div>

                    <div class="form-group">
                        <label for="test_message">Test Message</label>
                        <textarea class="form-control" id="test_message" name="message" rows="4" 
                                  placeholder="Enter test message..." required>Dear Test User, this is a test payment reminder from your college management system. Please disregard this message.</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Test
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Test Reminder Modal
    $('#testReminderBtn').click(function() {
        $('#testReminderModal').modal('show');
    });

    // Test Reminder Form
    $('#testReminderForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            channel: $('#test_channel').val(),
            recipient: $('#test_recipient').val(),
            message: $('#test_message').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("admin.settings.payment-reminders.test") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#testReminderModal .modal-footer button[type="submit"]').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i> Sending...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    $('#testReminderModal').modal('hide');
                    $('#testReminderForm')[0].reset();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                let message = 'An error occurred while sending the test reminder.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                alert('Error: ' + message);
            },
            complete: function() {
                $('#testReminderModal .modal-footer button[type="submit"]').prop('disabled', false)
                    .html('<i class="fas fa-paper-plane"></i> Send Test');
            }
        });
    });

    // Update recipient placeholder based on channel
    $('#test_channel').change(function() {
        const channel = $(this).val();
        const recipientInput = $('#test_recipient');
        
        if (channel === 'email') {
            recipientInput.attr('placeholder', 'Enter email address').attr('type', 'email');
        } else if (channel === 'sms' || channel === 'whatsapp') {
            recipientInput.attr('placeholder', 'Enter phone number').attr('type', 'tel');
        } else {
            recipientInput.attr('placeholder', 'Email address or phone number').attr('type', 'text');
        }
    });
});
</script>
@endsection