{{-- resources/views/admin/payments/reminder-settings.blade.php --}}
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
        display: flex;
        flex-direction: column;
        justify-content: center;
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
        background: rgba(255,255,255,0.2);
        color: white;
        font-size: 1.5rem;
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
    .channel-card.active i, .channel-card.active strong, .channel-card.active small {
        color: white !important;
    }

    .icon-circle {
        height: 2.5rem;
        width: 2.5rem;
        border-radius: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
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
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-2 mb-md-0">
                            <h2 class="mb-2">
                                <i class="fas fa-bell mr-3"></i>
                                Payment Reminder Center
                            </h2>
                            <p class="mb-0 opacity-75">Automate and manage payment notifications efficiently</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-test btn-lg shadow-sm" id="testReminderBtn">
                                <i class="fas fa-rocket mr-2"></i> Test System
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <!-- Statistics Dashboard -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card info h-100 p-3">
                <div class="text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-1 font-weight-bold">{{ $stats['pending'] ?? 0 }}</h3>
                    <p class="mb-0 small text-uppercase font-weight-bold">Pending Reminders</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card success h-100 p-3">
                <div class="text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h3 class="mb-1 font-weight-bold">{{ $stats['sent_today'] ?? 0 }}</h3>
                    <p class="mb-0 small text-uppercase font-weight-bold">Sent Today</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card warning h-100 p-3">
                <div class="text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="mb-1 font-weight-bold">{{ $stats['failed'] ?? 0 }}</h3>
                    <p class="mb-0 small text-uppercase font-weight-bold">Failed Attempts</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card primary h-100 p-3">
                <div class="text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="mb-1 font-weight-bold">{{ $stats['success_rate'] ?? 0 }}%</h3>
                    <p class="mb-0 small text-uppercase font-weight-bold">Success Rate</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="{{ route('admin.payment-reminders.settings.update') }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- General Settings -->
            <div class="col-lg-8 mb-4">
                <!-- General Configuration -->
                <div class="settings-card h-100 mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h5 class="mb-0 text-primary font-weight-bold">
                            <i class="fas fa-cogs mr-2"></i>General Configuration
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input type="checkbox" class="custom-control-input" 
                                       id="auto_send_reminders" name="settings[auto_send_reminders][value]" value="1"
                                       {{ (optional($settings['payment_reminders'] ?? collect())->where('key', 'auto_send_reminders')->first()->value ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="auto_send_reminders">
                                    Enable Auto-Send Reminders
                                </label>
                                <div class="text-muted small mt-1">Master switch for the automated reminder system</div>
                                <input type="hidden" name="settings[auto_send_reminders][key]" value="auto_send_reminders">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reminder_days_before_due" class="font-weight-bold text-gray-800">Days Before Due</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="settings[reminder_days_before_due][value]" 
                                               value="{{ optional($settings['payment_reminders'] ?? collect())->where('key', 'reminder_days_before_due')->first()->value ?? '3' }}"
                                               min="1" max="30">
                                        <div class="input-group-append">
                                            <span class="input-group-text">days</span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Send first reminder this many days before the due date</small>
                                    <input type="hidden" name="settings[reminder_days_before_due][key]" value="reminder_days_before_due">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="overdue_reminder_frequency" class="font-weight-bold text-gray-800">Overdue Frequency</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="settings[overdue_reminder_frequency][value]" 
                                               value="{{ optional($settings['payment_reminders'] ?? collect())->where('key', 'overdue_reminder_frequency')->first()->value ?? '7' }}"
                                               min="1" max="30">
                                        <div class="input-group-append">
                                            <span class="input-group-text">days</span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">How often to send reminders after the due date</small>
                                    <input type="hidden" name="settings[overdue_reminder_frequency][key]" value="overdue_reminder_frequency">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_reminder_attempts" class="font-weight-bold text-gray-800">Max Attempts</label>
                                    <input type="number" class="form-control" name="settings[max_reminder_attempts][value]" 
                                           value="{{ optional($settings['payment_reminders'] ?? collect())->where('key', 'max_reminder_attempts')->first()->value ?? '5' }}"
                                           min="1" max="10">
                                    <input type="hidden" name="settings[max_reminder_attempts][key]" value="max_reminder_attempts">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="defaulter_grace_period" class="font-weight-bold text-gray-800">Defaulter Grace Period</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="settings[defaulter_grace_period][value]" 
                                               value="{{ optional($settings['defaulter_management'] ?? collect())->where('key', 'defaulter_grace_period')->first()->value ?? '15' }}"
                                               min="0" max="60">
                                        <div class="input-group-append">
                                            <span class="input-group-text">days</span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="settings[defaulter_grace_period][key]" value="defaulter_grace_period">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Communication Settings -->
                <div class="settings-card h-100 mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h5 class="mb-0 text-success font-weight-bold">
                            <i class="fas fa-envelope mr-2"></i>Communication Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="default_reminder_channel" class="font-weight-bold text-gray-800">Default Channel</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="channel-card {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'email' ? 'active' : '' }}">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" class="custom-control-input" id="channel_email" name="settings[default_reminder_channel][value]" value="email"
                                                {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'email' ? 'checked' : '' }}>
                                            <label class="custom-control-label w-100" for="channel_email">
                                                <i class="fas fa-envelope mr-1"></i> Email
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="channel-card {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'sms' ? 'active' : '' }}">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" class="custom-control-input" id="channel_sms" name="settings[default_reminder_channel][value]" value="sms"
                                                {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'sms' ? 'checked' : '' }}>
                                            <label class="custom-control-label w-100" for="channel_sms">
                                                <i class="fas fa-sms mr-1"></i> SMS
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="channel-card {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'whatsapp' ? 'active' : '' }}">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" class="custom-control-input" id="channel_whatsapp" name="settings[default_reminder_channel][value]" value="whatsapp"
                                                {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'whatsapp' ? 'checked' : '' }}>
                                            <label class="custom-control-label w-100" for="channel_whatsapp">
                                                <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="settings[default_reminder_channel][key]" value="default_reminder_channel">
                        </div>

                        <div class="form-group">
                            <label for="reminder_email_template" class="font-weight-bold text-gray-800">Email Template</label>
                            <textarea class="form-control" rows="5" name="settings[reminder_email_template][value]">{{ optional($settings['communication'] ?? collect())->where('key', 'reminder_email_template')->first()->value ?? 'Dear [STUDENT_NAME], Your fee payment of ₹[AMOUNT] is due on [DUE_DATE]. Please make the payment at your earliest convenience.' }}</textarea>
                            <input type="hidden" name="settings[reminder_email_template][key]" value="reminder_email_template">
                            <small class="form-text text-muted">Variables: [STUDENT_NAME], [AMOUNT], [DUE_DATE], [ENROLLMENT_NUMBER]</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <!-- Recent Activity -->
                <div class="settings-card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-history mr-2"></i>Recent Activity
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @if(isset($recentActivity) && $recentActivity->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentActivity->take(5) as $activity)
                                <div class="list-group-item d-flex align-items-center border-left-0 border-right-0">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-light text-{{ $activity->status === 'sent' ? 'success' : ($activity->status === 'failed' ? 'danger' : 'warning') }}">
                                            <i class="fas fa-{{ $activity->status === 'sent' ? 'check' : ($activity->status === 'failed' ? 'times' : 'clock') }}"></i>
                                        </div>
                                    </div>
                                    <div class="text-truncate">
                                        <div class="font-weight-bold text-dark">{{ $activity->student->name ?? 'Unknown Student' }}</div>
                                        <div class="small text-muted">
                                            <span class="badge badge-light border">{{ ucfirst($activity->channel) }}</span>
                                            <span class="ml-1">{{ $activity->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="text-muted mb-2"><i class="fas fa-inbox fa-3x opacity-50"></i></div>
                                <p class="text-muted mb-0">No recent activity found</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="settings-card shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-gradient btn-block btn-lg mb-3">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.payment-reminders.dashboard') }}" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Test Reminder Modal -->
<div class="modal fade" id="testReminderModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-rocket mr-2"></i>Test Payment Reminder System
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="testReminderForm">
                <div class="modal-body p-4">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-gray-800 mb-3">Select Communication Channel</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="channel-card" data-channel="email">
                                    <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                    <br><strong>Email</strong>
                                    <br><small class="text-muted">Test email delivery</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="channel-card" data-channel="sms">
                                    <i class="fas fa-sms fa-2x text-success mb-2"></i>
                                    <br><strong>SMS</strong>
                                    <br><small class="text-muted">Test SMS sending</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="channel-card" data-channel="whatsapp">
                                    <i class="fab fa-whatsapp fa-2x text-success mb-2"></i>
                                    <br><strong>WhatsApp</strong>
                                    <br><small class="text-muted">Test WhatsApp API</small>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="test_channel" name="channel" required>
                    </div>

                    <div class="form-group">
                        <label for="test_recipient" class="font-weight-bold">
                            <i class="fas fa-user mr-2 text-muted"></i> Recipient Details
                        </label>
                        <input type="text" class="form-control form-control-lg bg-light" id="test_recipient" name="recipient" 
                               placeholder="Select a channel first" required readonly>
                    </div>

                    <div class="form-group mb-0">
                        <label for="test_message" class="font-weight-bold">
                            <i class="fas fa-comment mr-2 text-muted"></i> Test Message
                        </label>
                        <textarea class="form-control bg-light" id="test_message" name="message" rows="3" 
                                  required>This is a test payment reminder from your college management system.</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-paper-plane mr-2"></i> Send Test
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
    // Channel card visual selection in settings
    $('.channel-card input[type="radio"]').change(function() {
        $('.channel-card').removeClass('active');
        $(this).closest('.channel-card').addClass('active');
    });

    // Test Modal Handling
    $('.channel-card[data-channel]').click(function() {
        $('.channel-card[data-channel]').removeClass('active');
        $(this).addClass('active');
        
        const channel = $(this).data('channel');
        $('#test_channel').val(channel);
        
        const recipientInput = $('#test_recipient');
        recipientInput.prop('readonly', false).removeClass('bg-light');
        
        if (channel === 'email') {
            recipientInput.attr('placeholder', 'Enter email address...').attr('type', 'email');
        } else {
            recipientInput.attr('placeholder', 'Enter phone number with country code...').attr('type', 'tel');
        }
    });

    $('#testReminderBtn').click(function() {
        $('#testReminderModal').modal('show');
    });

    $('#testReminderForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!$('#test_channel').val()) {
            Swal.fire('Error', 'Please select a communication channel', 'error');
            return;
        }

        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Sending...');

        $.ajax({
            url: '{{ route("admin.payment-reminders.test") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                channel: $('#test_channel').val(),
                recipient: $('#test_recipient').val(),
                test_message: $('#test_message').val()
            },
            success: function(response) {
                Swal.fire('Success', response.message, 'success');
                $('#testReminderModal').modal('hide');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to send test reminder', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush