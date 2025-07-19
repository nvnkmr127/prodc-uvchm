@extends('layouts.theme')

@section('title', 'Payment Reminders Dashboard')

@push('styles')
<style>
.card-animate {
    transition: all 0.3s ease;
}
.card-animate:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.reminder-widget .avatar-title {
    width: 40px;
    height: 40px;
    line-height: 40px;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Payment Reminders Dashboard</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Payment Reminders</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2">Pending Reminders</p>
                            <h4 class="mb-2">{{ number_format($stats['pending_reminders']) }}</h4>
                            <p class="text-muted mb-0">
                                <span class="text-warning fw-bold font-size-12">
                                    <i class="mdi mdi-clock-outline me-1"></i>
                                    {{ $stats['overdue_reminders'] }} overdue
                                </span>
                            </p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-primary rounded-3">
                                <i class="mdi mdi-timer-sand font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2">Sent Today</p>
                            <h4 class="mb-2">{{ number_format($stats['sent_today']) }}</h4>
                            <p class="text-muted mb-0">
                                <span class="text-success fw-bold font-size-12">
                                    <i class="mdi mdi-arrow-up me-1"></i>
                                    {{ $stats['sent_this_week'] }} this week
                                </span>
                            </p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-success rounded-3">
                                <i class="mdi mdi-email-send font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2">Total Defaulters</p>
                            <h4 class="mb-2">{{ number_format($stats['total_defaulters']) }}</h4>
                            <p class="text-muted mb-0">
                                <span class="text-danger fw-bold font-size-12">
                                    <i class="mdi mdi-alert-circle-outline me-1"></i>
                                    {{ $stats['chronic_defaulters'] }} chronic
                                </span>
                            </p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-danger rounded-3">
                                <i class="mdi mdi-account-alert font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2">Collection Rate</p>
                            <h4 class="mb-2">{{ number_format($collectionEfficiency['collection_rate'], 1) }}%</h4>
                            <p class="text-muted mb-0">
                                <span class="text-info fw-bold font-size-12">
                                    <i class="mdi mdi-trending-up me-1"></i>
                                    {{ number_format($collectionEfficiency['overdue_rate'], 1) }}% overdue
                                </span>
                            </p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-info rounded-3">
                                <i class="mdi mdi-chart-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary w-100" onclick="processReminders()">
                                <i class="mdi mdi-play-circle me-1"></i>
                                Process Pending Reminders
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-info w-100" onclick="updateDefaulters()">
                                <i class="mdi mdi-refresh me-1"></i>
                                Update Defaulter Records
                            </button>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.payment-reminders.index') }}" class="btn btn-outline-primary w-100">
                                <i class="mdi mdi-format-list-bulleted me-1"></i>
                                View All Reminders
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.payment-reminders.defaulters') }}" class="btn btn-outline-danger w-100">
                                <i class="mdi mdi-account-search me-1"></i>
                                View Defaulters
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Recent Reminders</h4>
                    <a href="{{ route('admin.payment-reminders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($recentReminders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-nowrap table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Type</th>
                                        <th>Channel</th>
                                        <th>Scheduled</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReminders as $reminder)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3">
                                                        <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                            {{ substr($reminder->student->name ?? 'N', 0, 1) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 font-size-14">{{ $reminder->student->name ?? 'N/A' }}</h6>
                                                        <p class="text-muted font-size-12 mb-0">{{ $reminder->student->enrollment_number ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-soft-info text-info">{{ ucwords(str_replace('_', ' ', $reminder->reminder_type)) }}</span>
                                            </td>
                                            <td>
                                                <i class="mdi mdi-{{ $reminder->channel === 'email' ? 'email' : ($reminder->channel === 'sms' ? 'message-text' : 'whatsapp') }} me-1"></i>
                                                {{ ucfirst($reminder->channel) }}
                                            </td>
                                            <td>{{ $reminder->scheduled_date->format('M d, Y H:i') }}</td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'warning',
                                                        'sent' => 'success',
                                                        'failed' => 'danger',
                                                        'cancelled' => 'secondary'
                                                    ];
                                                @endphp
                                                <span class="badge bg-soft-{{ $statusColors[$reminder->status] ?? 'primary' }} text-{{ $statusColors[$reminder->status] ?? 'primary' }}">
                                                    {{ ucfirst($reminder->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($reminder->status === 'pending')
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="sendReminder({{ $reminder->id }})">
                                                        Send Now
                                                    </button>
                                                @else
                                                    <a href="{{ route('admin.payment-reminders.show', $reminder) }}" class="btn btn-sm btn-outline-info">
                                                        View
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="avatar-md mx-auto mb-3">
                                <div class="avatar-title bg-soft-primary text-primary rounded-circle font-size-24">
                                    <i class="mdi mdi-email-outline"></i>
                                </div>
                            </div>
                            <h5 class="font-size-15 mb-2">No Recent Reminders</h5>
                            <p class="text-muted mb-0">No reminders have been processed recently.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Send Test Reminder</h4>
                </div>
                <div class="card-body">
                    <form id="testReminderForm">
                        @csrf
                        <div class="mb-3">
                            <label for="channel" class="form-label">Channel</label>
                            <select class="form-select" id="channel" name="channel" required>
                                <option value="">Select Channel</option>
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                                <option value="whatsapp">WhatsApp</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="recipient" class="form-label">Recipient</label>
                            <input type="text" class="form-select" id="recipient" name="recipient" 
                                   placeholder="Email or Phone Number" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="3" 
                                      placeholder="Test reminder message" required>Dear Student, this is a test payment reminder from {{ config('app.name') }}.</textarea>
                            <div class="form-text">
                                <span id="charCount">0</span> characters
                                <span id="charLimit" class="text-muted"></span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="mdi mdi-send me-1"></i>
                            Send Test Reminder
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Collection Summary</h4>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="mt-4">
                                <p class="mb-2 text-truncate">Total Invoices</p>
                                <h5 class="font-size-16 mb-0">{{ number_format($collectionEfficiency['total_invoices']) }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mt-4">
                                <p class="mb-2 text-truncate">Paid Invoices</p>
                                <h5 class="font-size-16 mb-0 text-success">{{ number_format($collectionEfficiency['paid_invoices']) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="mt-4">
                                <p class="mb-2 text-truncate">Overdue</p>
                                <h5 class="font-size-16 mb-0 text-danger">{{ number_format($collectionEfficiency['overdue_invoices']) }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mt-4">
                                <p class="mb-2 text-truncate">Success Rate</p>
                                <h5 class="font-size-16 mb-0 text-info">{{ number_format($collectionEfficiency['collection_rate'], 1) }}%</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Processing...</p>
            </div>
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
        $('#charCount').text(length);
        
        const channel = $('#channel').val();
        let limit = '';
        
        if (channel === 'sms') {
            limit = ' / 160 (SMS limit)';
            if (length > 160) {
                $('#charLimit').addClass('text-danger');
            } else {
                $('#charLimit').removeClass('text-danger');
            }
        } else if (channel === 'whatsapp') {
            limit = ' / 4096 (WhatsApp limit)';
        }
        
        $('#charLimit').text(limit);
    });

    // Update character limit when channel changes
    $('#channel').on('change', function() {
        $('#message').trigger('input');
        
        const channel = $(this).val();
        if (channel === 'email') {
            $('#recipient').attr('placeholder', 'Enter email address');
            $('#recipient').attr('type', 'email');
        } else {
            $('#recipient').attr('placeholder', 'Enter phone number');
            $('#recipient').attr('type', 'tel');
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
});

function processReminders() {
    if (confirm('Are you sure you want to process all pending reminders?')) {
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
                    showAlert('success', 'Reminders processing started successfully!');
                    setTimeout(() => location.reload(), 2000);
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
}

function updateDefaulters() {
    if (confirm('Are you sure you want to update defaulter records?')) {
        $.ajax({
            url: '{{ route("admin.payment-reminders.defaulters.update") }}',
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function() {
                $('#loadingModal').modal('show');
            },
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    showAlert('success', 'Defaulter records updated successfully!');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('danger', 'Failed to update defaulters: ' + response.error);
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                showAlert('danger', 'An error occurred while updating defaulter records.');
            }
        });
    }
}

function sendReminder(reminderId) {
    if (confirm('Are you sure you want to send this reminder now?')) {
        $.ajax({
        url: `/admin/payment-reminders/${reminderId}/queue`,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Reminder queued successfully!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', 'Failed to queue reminder: ' + response.error);
                }
            },
            error: function(xhr) {
                showAlert('danger', 'An error occurred while queuing the reminder.');
            }
        });
    }
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endpush