@extends('layouts.theme')

@section('title', 'Payment Reminders')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Payment Reminders</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.payment-reminders.dashboard') }}">Payment Reminders</a></li>
                        <li class="breadcrumb-item active">All Reminders</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <div class="card-body">
                    <form method="GET" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="">All Status</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="channel" class="form-label">Channel</label>
                                <select class="form-select" name="channel" id="channel">
                                    <option value="">All Channels</option>
                                    <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="sms" {{ request('channel') === 'sms' ? 'selected' : '' }}>SMS</option>
                                    <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                    <option value="phone_call" {{ request('channel') === 'phone_call' ? 'selected' : '' }}>Phone Call</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="reminder_type" class="form-label">Type</label>
                                <select class="form-select" name="reminder_type" id="reminder_type">
                                    <option value="">All Types</option>
                                    <option value="upcoming_due" {{ request('reminder_type') === 'upcoming_due' ? 'selected' : '' }}>Upcoming Due</option>
                                    <option value="overdue" {{ request('reminder_type') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                    <option value="escalation" {{ request('reminder_type') === 'escalation' ? 'selected' : '' }}>Escalation</option>
                                    <option value="final_notice" {{ request('reminder_type') === 'final_notice' ? 'selected' : '' }}>Final Notice</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" name="date_from" id="date_from"
                                       value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" name="date_to" id="date_to"
                                       value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="student_search" class="form-label">Student Search</label>
                                <input type="text" class="form-control" name="student_search" id="student_search"
                                       value="{{ request('student_search') }}"
                                       placeholder="Name or Enrollment No">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="mdi mdi-magnify me-1"></i>
                                    Apply Filters
                                </button>
                                <a href="{{ route('admin.payment-reminders.index') }}" class="btn btn-outline-secondary me-2">
                                    <i class="mdi mdi-close me-1"></i>
                                    Clear Filters
                                </a>
                                <button type="button" class="btn btn-success" onclick="processReminders()">
                                    <i class="mdi mdi-play-circle me-1"></i>
                                    Process Pending
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="card-title mb-0">Payment Reminders ({{ $reminders->total() }})</h4>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                    Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                                    Clear All
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Bulk Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="bulkSend()">
                                            <i class="mdi mdi-email-send me-2"></i>Send Selected
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="bulkCancel()">
                                            <i class="mdi mdi-close-circle me-2"></i>Cancel Selected
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="bulkDelete()">
                                            <i class="mdi mdi-delete me-2"></i>Delete Selected
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($reminders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-nowrap table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="checkAll">
                                            </div>
                                        </th>
                                        <th scope="col">Student</th>
                                        <th scope="col">Reminder Details</th>
                                        <th scope="col">Invoice Info</th>
                                        <th scope="col">Schedule</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reminders as $reminder)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="{{ $reminder->id }}" name="selected_reminders[]">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                            {{ substr($reminder->student->name ?? 'N', 0, 2) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 font-weight-normal">{{ $reminder->student->name ?? 'N/A' }}</h6>
                                                        <p class="text-muted font-size-13 mb-0">{{ $reminder->student->enrollment_number ?? 'N/A' }}</p>
                                                        @if($reminder->student->batch ?? false)
                                                            <p class="text-muted font-size-12 mb-0">{{ $reminder->student->batch->course->name ?? '' }} - {{ $reminder->student->batch->name ?? '' }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1 font-size-14">
                                                        <span class="badge bg-soft-info text-info me-1">{{ ucwords(str_replace('_', ' ', $reminder->reminder_type)) }}</span>
                                                    </h6>
                                                    <p class="text-muted font-size-13 mb-1">
                                                        <i class="mdi mdi-{{ $reminder->channel === 'email' ? 'email' : ($reminder->channel === 'sms' ? 'message-text' : ($reminder->channel === 'whatsapp' ? 'whatsapp' : 'phone')) }} me-1"></i>
                                                        {{ ucfirst($reminder->channel) }}
                                                    </p>
                                                    @if($reminder->feeCategory ?? false)
                                                        <p class="text-muted font-size-12 mb-0">{{ $reminder->feeCategory->name }}</p>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($reminder->invoice ?? false)
                                                    <div>
                                                        <h6 class="mb-0 font-size-14">₹{{ number_format($reminder->invoice->total_amount, 2) }}</h6>
                                                        <p class="text-muted font-size-12 mb-0">Invoice #{{ $reminder->invoice->invoice_number ?? 'N/A' }}</p>
                                                        <p class="text-muted font-size-12 mb-0">Due: {{ $reminder->invoice->due_date ? \Carbon\Carbon::parse($reminder->invoice->due_date)->format('M d, Y') : 'N/A' }}</p>
                                                    </div>
                                                @else
                                                    <span class="text-muted">No invoice</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>
                                                    <p class="mb-1 font-size-13">
                                                        <strong>Scheduled:</strong><br>
                                                        {{ $reminder->scheduled_date->format('M d, Y') }}<br>
                                                        <small class="text-muted">{{ $reminder->scheduled_date->format('H:i A') }}</small>
                                                    </p>
                                                    @if($reminder->sent_at ?? false)
                                                        <p class="mb-0 font-size-13 text-success">
                                                            <strong>Sent:</strong><br>
                                                            {{ $reminder->sent_at->format('M d, Y H:i A') }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusConfig = [
                                                        'pending' => ['color' => 'warning', 'icon' => 'clock'],
                                                        'sent' => ['color' => 'success', 'icon' => 'check'],
                                                        'failed' => ['color' => 'danger', 'icon' => 'close'],
                                                        'cancelled' => ['color' => 'secondary', 'icon' => 'stop'],
                                                        'processing' => ['color' => 'info', 'icon' => 'loading']
                                                    ];
                                                    $config = $statusConfig[$reminder->status] ?? ['color' => 'primary', 'icon' => 'help'];
                                                @endphp
                                                <span class="badge bg-soft-{{ $config['color'] }} text-{{ $config['color'] }}">
                                                    <i class="mdi mdi-{{ $config['icon'] }} me-1"></i>
                                                    {{ ucfirst($reminder->status) }}
                                                </span>
                                                @if($reminder->status === 'failed' && ($reminder->failure_reason ?? false))
                                                    <p class="text-danger font-size-11 mb-0 mt-1" title="{{ $reminder->failure_reason }}">
                                                        {{ Str::limit($reminder->failure_reason, 30) }}
                                                    </p>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="mdi mdi-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.payment-reminders.show', $reminder) }}">
                                                                <i class="mdi mdi-eye text-info me-2"></i>
                                                                View Details
                                                            </a>
                                                        </li>
                                                        @if($reminder->status === 'pending')
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="sendReminder({{ $reminder->id }})">
                                                                    <i class="mdi mdi-email-send text-primary me-2"></i>
                                                                    Send Now
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="queueReminder({{ $reminder->id }})">
                                                                    <i class="mdi mdi-clock text-warning me-2"></i>
                                                                    Queue for Later
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="cancelReminder({{ $reminder->id }})">
                                                                    <i class="mdi mdi-close-circle text-secondary me-2"></i>
                                                                    Cancel
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if($reminder->status === 'failed')
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="retryReminder({{ $reminder->id }})">
                                                                    <i class="mdi mdi-restart text-success me-2"></i>
                                                                    Retry
                                                                </a>
                                                            </li>
                                                        @endif
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" onclick="deleteReminder({{ $reminder->id }})">
                                                                <i class="mdi mdi-delete me-2"></i>
                                                                Delete
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        @if($reminders->hasPages())
                            <div class="row mt-4">
                                <div class="col-sm-6">
                                    <div class="dataTables_info">
                                        Showing {{ $reminders->firstItem() }} to {{ $reminders->lastItem() }} of {{ $reminders->total() }} results
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="dataTables_paginate paging_simple_numbers float-end">
                                        {{ $reminders->appends(request()->query())->links() }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <div class="avatar-md mx-auto mb-3">
                                <div class="avatar-title bg-soft-primary text-primary rounded-circle font-size-24">
                                    <i class="mdi mdi-email-outline"></i>
                                </div>
                            </div>
                            <h5 class="font-size-15 mb-2">No Reminders Found</h5>
                            <p class="text-muted mb-0">No payment reminders match your current filters.</p>
                        </div>
                    @endif
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
    // Check all functionality
    $('#checkAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('input[name="selected_reminders[]"]').prop('checked', isChecked);
    });

    // Individual checkbox change
    $('input[name="selected_reminders[]"]').on('change', function() {
        const totalCheckboxes = $('input[name="selected_reminders[]"]').length;
        const checkedCheckboxes = $('input[name="selected_reminders[]"]:checked').length;
        
        $('#checkAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
});

function selectAll() {
    $('input[name="selected_reminders[]"]').prop('checked', true);
    $('#checkAll').prop('checked', true);
}

function clearSelection() {
    $('input[name="selected_reminders[]"]').prop('checked', false);
    $('#checkAll').prop('checked', false);
}

function getSelectedReminders() {
    return $('input[name="selected_reminders[]"]:checked').map(function() {
        return $(this).val();
    }).get();
}

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
                    showAlert('success', 'Processing started successfully!');
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

function sendReminder(reminderId) {
    if (confirm('Are you sure you want to send this reminder now?')) {
        $.ajax({
        url: `/admin/payment-reminders/${reminderId}/send`,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function() {
                $('#loadingModal').modal('show');
            },
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    showAlert('success', 'Reminder sent successfully!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', 'Failed to send reminder: ' + response.error);
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                showAlert('danger', 'An error occurred while sending the reminder.');
            }
        });
    }
}

function queueReminder(reminderId) {
    if (confirm('Are you sure you want to queue this reminder?')) {
        $.ajax({
            url: `{{ route("admin.payment-reminders.queue", ["reminder" => ""]) }}/${reminderId}`,
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

function cancelReminder(reminderId) {
    if (confirm('Are you sure you want to cancel this reminder?')) {
        $.ajax({
            url: `{{ route("admin.payment-reminders.cancel", ["reminder" => ""]) }}/${reminderId}`,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Reminder cancelled successfully!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', 'Failed to cancel reminder: ' + response.error);
                }
            },
            error: function(xhr) {
                showAlert('danger', 'An error occurred while cancelling the reminder.');
            }
        });
    }
}

function retryReminder(reminderId) {
    if (confirm('Are you sure you want to retry this reminder?')) {
        sendReminder(reminderId);
    }
}

function deleteReminder(reminderId) {
    if (confirm('Are you sure you want to delete this reminder? This action cannot be undone.')) {
        $.ajax({
            url: `{{ route("admin.payment-reminders.delete", ["reminder" => ""]) }}/${reminderId}`,
            method: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Reminder deleted successfully!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', 'Failed to delete reminder: ' + response.error);
                }
            },
            error: function(xhr) {
                showAlert('danger', 'An error occurred while deleting the reminder.');
            }
        });
    }
}

function bulkSend() {
    const selected = getSelectedReminders();
    if (selected.length === 0) {
        showAlert('warning', 'Please select at least one reminder.');
        return;
    }

    if (confirm(`Send ${selected.length} selected reminder(s)?`)) {
        $.ajax({
            url: '{{ route("admin.payment-reminders.bulk-action") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                action: 'send',
                reminder_ids: selected
            },
            beforeSend: function() {
                $('#loadingModal').modal('show');
            },
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    showAlert('success', `${response.results.success} reminder(s) sent successfully.`);
                    if (response.results.failed > 0) {
                        showAlert('warning', `${response.results.failed} reminder(s) failed to send.`);
                    }
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('danger', 'Failed to send bulk reminders.');
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                showAlert('danger', 'An error occurred while sending bulk reminders.');
            }
        });
    }
}

function bulkCancel() {
    const selected = getSelectedReminders();
    if (selected.length === 0) {
        showAlert('warning', 'Please select at least one reminder.');
        return;
    }

    if (confirm(`Cancel ${selected.length} selected reminder(s)?`)) {
        $.ajax({
            url: '{{ route("admin.payment-reminders.bulk-action") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                action: 'cancel',
                reminder_ids: selected
            },
            beforeSend: function() {
                $('#loadingModal').modal('show');
            },
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    showAlert('success', `${response.results.success} reminder(s) cancelled successfully.`);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('danger', 'Failed to cancel bulk reminders.');
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                showAlert('danger', 'An error occurred while cancelling bulk reminders.');
            }
        });
    }
}

function bulkDelete() {
    const selected = getSelectedReminders();
    if (selected.length === 0) {
        showAlert('warning', 'Please select at least one reminder.');
        return;
    }

    if (confirm(`Delete ${selected.length} selected reminder(s)? This action cannot be undone.`)) {
        $.ajax({
            url: '{{ route("admin.payment-reminders.bulk-action") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                action: 'delete',
                reminder_ids: selected
            },
            beforeSend: function() {
                $('#loadingModal').modal('show');
            },
            success: function(response) {
                $('#loadingModal').modal('hide');
                if (response.success) {
                    showAlert('success', `${response.results.success} reminder(s) deleted successfully.`);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('danger', 'Failed to delete bulk reminders.');
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                showAlert('danger', 'An error occurred while deleting bulk reminders.');
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
    
    $('.alert').remove();
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endpush