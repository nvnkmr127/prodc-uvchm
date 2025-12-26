{{-- resources/views/admin/payments/reminder-settings.blade.php --}}
@extends('layouts.theme')

@section('title', 'Payment Reminder Settings')

@section('content')
<div class="container-fluid">
    
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payment Reminder Settings</h1>
        <div>
            <a href="{{ route('admin.payment-reminders.dashboard') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.payment-reminders.update') }}">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- General Settings -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-cog mr-2"></i>General Settings
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reminder_days_before_due">Reminder Days Before Due Date</label>
                                    <input type="number" class="form-control" 
                                           name="settings[reminder_days_before_due][value]" 
                                           value="{{ optional($settings['payment_reminders'] ?? collect())->where('key', 'reminder_days_before_due')->first()->value ?? '3' }}"
                                           min="1" max="30">
                                    <input type="hidden" name="settings[reminder_days_before_due][key]" value="reminder_days_before_due">
                                    <small class="form-text text-muted">Days before due date to send reminder</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="overdue_reminder_frequency">Overdue Reminder Frequency (Days)</label>
                                    <input type="number" class="form-control" 
                                           name="settings[overdue_reminder_frequency][value]" 
                                           value="{{ optional($settings['payment_reminders'] ?? collect())->where('key', 'overdue_reminder_frequency')->first()->value ?? '7' }}"
                                           min="1" max="30">
                                    <input type="hidden" name="settings[overdue_reminder_frequency][key]" value="overdue_reminder_frequency">
                                    <small class="form-text text-muted">Days between overdue reminders</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_reminder_attempts">Maximum Reminder Attempts</label>
                                    <input type="number" class="form-control" 
                                           name="settings[max_reminder_attempts][value]" 
                                           value="{{ optional($settings['payment_reminders'] ?? collect())->where('key', 'max_reminder_attempts')->first()->value ?? '5' }}"
                                           min="1" max="10">
                                    <input type="hidden" name="settings[max_reminder_attempts][key]" value="max_reminder_attempts">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="auto_send_reminders">Auto Send Reminders</label>
                                    <select class="form-control" name="settings[auto_send_reminders][value]">
                                        <option value="1" {{ (optional($settings['payment_reminders'] ?? collect())->where('key', 'auto_send_reminders')->first()->value ?? '1') == '1' ? 'selected' : '' }}>Enabled</option>
                                        <option value="0" {{ (optional($settings['payment_reminders'] ?? collect())->where('key', 'auto_send_reminders')->first()->value ?? '1') == '0' ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <input type="hidden" name="settings[auto_send_reminders][key]" value="auto_send_reminders">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Communication Settings -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-envelope mr-2"></i>Communication Settings
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="default_reminder_channel">Default Reminder Channel</label>
                                    <select class="form-control" name="settings[default_reminder_channel][value]">
                                        <option value="email" {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'email' ? 'selected' : '' }}>Email</option>
                                        <option value="sms" {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'sms' ? 'selected' : '' }}>SMS</option>
                                        <option value="whatsapp" {{ (optional($settings['communication'] ?? collect())->where('key', 'default_reminder_channel')->first()->value ?? 'email') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                    </select>
                                    <input type="hidden" name="settings[default_reminder_channel][key]" value="default_reminder_channel">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sender_email">Sender Email</label>
                                    <input type="email" class="form-control" 
                                           name="settings[sender_email][value]" 
                                           value="{{ optional($settings['communication'] ?? collect())->where('key', 'sender_email')->first()->value ?? 'noreply@college.edu' }}">
                                    <input type="hidden" name="settings[sender_email][key]" value="sender_email">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reminder_email_template">Email Template</label>
                            <textarea class="form-control" rows="5" 
                                      name="settings[reminder_email_template][value]">{{ optional($settings['communication'] ?? collect())->where('key', 'reminder_email_template')->first()->value ?? 'Dear [STUDENT_NAME], Your fee payment of ₹[AMOUNT] is due on [DUE_DATE]. Please make the payment at your earliest convenience.' }}</textarea>
                            <input type="hidden" name="settings[reminder_email_template][key]" value="reminder_email_template">
                            <small class="form-text text-muted">Available placeholders: [STUDENT_NAME], [AMOUNT], [DUE_DATE], [ENROLLMENT_NUMBER]</small>
                        </div>

                        <div class="form-group">
                            <label for="reminder_sms_template">SMS Template</label>
                            <textarea class="form-control" rows="3" 
                                      name="settings[reminder_sms_template][value]">{{ optional($settings['communication'] ?? collect())->where('key', 'reminder_sms_template')->first()->value ?? 'Dear [STUDENT_NAME], Fee payment of ₹[AMOUNT] due on [DUE_DATE]. Pay now to avoid late fees.' }}</textarea>
                            <input type="hidden" name="settings[reminder_sms_template][key]" value="reminder_sms_template">
                        </div>
                    </div>
                </div>

                <!-- Defaulter Management -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Defaulter Management
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="defaulter_grace_period">Grace Period (Days)</label>
                                    <input type="number" class="form-control" 
                                           name="settings[defaulter_grace_period][value]" 
                                           value="{{ optional($settings['defaulter_management'] ?? collect())->where('key', 'defaulter_grace_period')->first()->value ?? '15' }}"
                                           min="0" max="60">
                                    <input type="hidden" name="settings[defaulter_grace_period][key]" value="defaulter_grace_period">
                                    <small class="form-text text-muted">Days after due date to mark as defaulter</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="escalation_threshold">Escalation Threshold (₹)</label>
                                    <input type="number" class="form-control" 
                                           name="settings[escalation_threshold][value]" 
                                           value="{{ optional($settings['defaulter_management'] ?? collect())->where('key', 'escalation_threshold')->first()->value ?? '5000' }}"
                                           min="0">
                                    <input type="hidden" name="settings[escalation_threshold][key]" value="escalation_threshold">
                                    <small class="form-text text-muted">Amount above which to escalate to management</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" 
                                       name="settings[auto_block_defaulters][value]" 
                                       value="1" id="auto_block_defaulters"
                                       {{ (optional($settings['defaulter_management'] ?? collect())->where('key', 'auto_block_defaulters')->first()->value ?? '0') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="auto_block_defaulters">
                                    Auto-block defaulters from services
                                </label>
                                <input type="hidden" name="settings[auto_block_defaulters][key]" value="auto_block_defaulters">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg mr-3">
                            <i class="fas fa-save mr-2"></i>Save Settings
                        </button>
                        <a href="{{ route('admin.payment-reminders.dashboard') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar mr-2"></i>Reminder Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Reminders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_reminders'] ?? 0 }}</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sent Today</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['sent_today'] ?? 0 }}</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending'] ?? 0 }}</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['failed'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-history mr-2"></i>Recent Activity
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($recentActivity->count() > 0)
                            @foreach($recentActivity->take(5) as $activity)
                            <div class="d-flex align-items-center mb-3">
                                <div class="mr-3">
                                    <div class="icon-circle bg-{{ $activity->status === 'sent' ? 'success' : ($activity->status === 'failed' ? 'danger' : 'warning') }}">
                                        <i class="fas fa-{{ $activity->status === 'sent' ? 'check' : ($activity->status === 'failed' ? 'times' : 'clock') }} text-white"></i>
                                    </div>
                                </div>
                                <div class="small">
                                    <div class="font-weight-bold">{{ $activity->student->name ?? 'Unknown' }}</div>
                                    <div class="text-gray-500">{{ $activity->channel }} • {{ $activity->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <p class="text-muted text-center">No recent activity</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.icon-circle {
    height: 2rem;
    width: 2rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection