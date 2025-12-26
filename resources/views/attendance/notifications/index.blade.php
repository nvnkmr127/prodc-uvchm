@extends('layouts.theme')

@section('title', 'Attendance Notifications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-bell"></i> Attendance Notifications
                    </h1>
                    <p class="mb-0 text-muted">Manage your attendance alerts and notifications</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" id="markAllRead">
                        <i class="fas fa-check-double"></i> Mark All Read
                    </button>
                    <a href="{{ route('attendance.notifications.preferences') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-cog"></i> Preferences
                    </a>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createNotificationModal">
                        <i class="fas fa-plus"></i> Create Alert
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Notification Stats --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Unread Notifications
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="unreadCount">
                                @if(isset($notifications))
                                    {{ $notifications->filter(function($notification) { 
                                        $readBy = json_decode($notification->read_by ?? '[]');
                                        return !in_array(auth()->id(), $readBy ?? []);
                                    })->count() }}
                                @else
                                    0
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                High Priority
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if(isset($notifications))
                                    {{ $notifications->whereIn('priority', ['high', 'urgent'])->count() }}
                                @else
                                    0
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today's Alerts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if(isset($notifications))
                                    {{ $notifications->where('created_at', '>=', today())->count() }}
                                @else
                                    0
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Notifications
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ isset($notifications) ? $notifications->total() : 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="card shadow mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="notificationTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab">
                        <i class="fas fa-list"></i> All Notifications
                        <span class="badge badge-secondary ml-1">{{ isset($notifications) ? $notifications->total() : 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="unread-tab" data-toggle="tab" href="#unread" role="tab">
                        <i class="fas fa-envelope"></i> Unread
                        <span class="badge badge-info ml-1" id="unreadBadge">
                            @if(isset($notifications))
                                {{ $notifications->filter(function($notification) { 
                                    $readBy = json_decode($notification->read_by ?? '[]');
                                    return !in_array(auth()->id(), $readBy ?? []);
                                })->count() }}
                            @else
                                0
                            @endif
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="high-priority-tab" data-toggle="tab" href="#high-priority" role="tab">
                        <i class="fas fa-exclamation-triangle"></i> High Priority
                        <span class="badge badge-warning ml-1">
                            @if(isset($notifications))
                                {{ $notifications->whereIn('priority', ['high', 'urgent'])->count() }}
                            @else
                                0
                            @endif
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                        <i class="fas fa-user-check"></i> Attendance Alerts
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="notificationTabContent">
                {{-- All Notifications Tab --}}
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    @if(isset($notifications) && $notifications->count() > 0)
                        <div class="notification-list">
                            @foreach($notifications as $notification)
                                @php
                                    $readBy = json_decode($notification->read_by ?? '[]', true) ?? [];
                                    $isRead = in_array(auth()->id(), $readBy);
                                    $priorityClass = match($notification->priority) {
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        'normal' => 'primary',
                                        default => 'secondary'
                                    };
                                    $typeClass = match($notification->type) {
                                        'success' => 'success',
                                        'error' => 'danger',
                                        'warning' => 'warning',
                                        default => 'info'
                                    };
                                @endphp
                                
                                <div class="card mb-3 notification-item {{ $isRead ? '' : 'border-left-primary' }}" 
                                     data-notification-id="{{ $notification->id }}">
                                    <div class="card-body py-3">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <div class="notification-icon">
                                                    @if($notification->category === 'attendance')
                                                        <i class="fas fa-user-check fa-2x text-{{ $typeClass }}"></i>
                                                    @elseif($notification->category === 'system')
                                                        <i class="fas fa-cogs fa-2x text-{{ $typeClass }}"></i>
                                                    @elseif($notification->category === 'financial')
                                                        <i class="fas fa-dollar-sign fa-2x text-{{ $typeClass }}"></i>
                                                    @else
                                                        <i class="fas fa-bell fa-2x text-{{ $typeClass }}"></i>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 {{ $isRead ? 'text-muted' : 'font-weight-bold' }}">
                                                            {{ $notification->title }}
                                                            @if(!$isRead)
                                                                <span class="badge badge-primary badge-sm ml-2">NEW</span>
                                                            @endif
                                                        </h6>
                                                        <p class="mb-2 {{ $isRead ? 'text-muted' : '' }}">
                                                            {{ $notification->message }}
                                                        </p>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge badge-{{ $typeClass }} mr-2">
                                                                {{ ucfirst($notification->type) }}
                                                            </span>
                                                            <span class="badge badge-{{ $priorityClass }} mr-2">
                                                                {{ ucfirst($notification->priority) }}
                                                            </span>
                                                            <span class="badge badge-light mr-2">
                                                                {{ ucfirst($notification->category) }}
                                                            </span>
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock"></i>
                                                                {{ $notification->created_at->diffForHumans() }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="notification-actions ml-3">
                                                        @if(!$isRead)
                                                            <button class="btn btn-outline-primary btn-sm mark-read-btn" 
                                                                    data-id="{{ $notification->id }}" title="Mark as Read">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        @endif
                                                        @if($notification->action_url)
                                                            <a href="{{ $notification->action_url }}" 
                                                               class="btn btn-outline-success btn-sm" 
                                                               title="{{ $notification->action_text ?? 'View' }}">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        @endif
                                                        <button class="btn btn-outline-danger btn-sm delete-notification-btn" 
                                                                data-id="{{ $notification->id }}" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Pagination --}}
                        <div class="d-flex justify-content-center mt-4">
                            {{ $notifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-4x text-muted mb-4"></i>
                            <h4>No Notifications</h4>
                            <p class="text-muted mb-4">You have no attendance notifications at this time.</p>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#createNotificationModal">
                                <i class="fas fa-plus"></i> Create Your First Alert
                            </button>
                        </div>
                    @endif
                </div>
                
                {{-- Other tabs content would be filtered versions of the same notifications --}}
                <div class="tab-pane fade" id="unread" role="tabpanel">
                    <div class="text-center py-4">
                        <i class="fas fa-filter fa-2x text-muted mb-2"></i>
                        <p>Filtered view for unread notifications will appear here.</p>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="high-priority" role="tabpanel">
                    <div class="text-center py-4">
                        <i class="fas fa-filter fa-2x text-muted mb-2"></i>
                        <p>Filtered view for high priority notifications will appear here.</p>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="attendance" role="tabpanel">
                    <div class="text-center py-4">
                        <i class="fas fa-filter fa-2x text-muted mb-2"></i>
                        <p>Filtered view for attendance-specific notifications will appear here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Notification Modal --}}
<div class="modal fade" id="createNotificationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bell-plus"></i> Create Attendance Alert
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="createNotificationForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="alertTitle">Alert Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="alertTitle" class="form-control" 
                                       placeholder="e.g., Low Attendance Warning" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="alertType">Alert Type <span class="text-danger">*</span></label>
                                <select name="type" id="alertType" class="form-control" required>
                                    <option value="">Select Alert Type</option>
                                    <option value="attendance_threshold">Attendance Threshold</option>
                                    <option value="absence_streak">Consecutive Absences</option>
                                    <option value="late_arrivals">Late Arrival Pattern</option>
                                    <option value="batch_performance">Batch Performance</option>
                                    <option value="custom">Custom Alert</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="alertMessage">Alert Message <span class="text-danger">*</span></label>
                        <textarea name="message" id="alertMessage" class="form-control" rows="3" 
                                  placeholder="Describe what this alert should monitor..." required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="priority">Priority</label>
                                <select name="priority" id="priority" class="form-control">
                                    <option value="low">Low</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="threshold">Threshold (%)</label>
                                <input type="number" name="threshold" id="threshold" class="form-control" 
                                       min="0" max="100" placeholder="e.g., 75">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="timeframe">Timeframe</label>
                                <select name="timeframe" id="timeframe" class="form-control">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="semester">Semester</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="recipients">Recipients</label>
                        <select name="recipients[]" id="recipients" class="form-control" multiple>
                            <option value="admin">Admin Team</option>
                            <option value="faculty">Faculty</option>
                            <option value="hod">Head of Department</option>
                            <option value="principal">Principal</option>
                            <option value="parents">Parents</option>
                        </select>
                        <small class="form-text text-muted">Select who should receive this alert</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="email_notification" id="emailNotification" checked>
                            <label class="form-check-label" for="emailNotification">
                                Send email notifications
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sms_notification" id="smsNotification">
                            <label class="form-check-label" for="smsNotification">
                                Send SMS notifications
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="play_sound" id="playSound">
                            <label class="form-check-label" for="playSound">
                                Play notification sound
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-bell"></i> Create Alert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Notification Details Modal --}}
<div class="modal fade" id="notificationDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="notificationDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.notification-item {
    transition: all 0.3s ease;
}
.notification-item:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}
.notification-icon {
    text-align: center;
    width: 50px;
}
.notification-actions .btn {
    margin-left: 0.25rem;
}
.badge-sm {
    font-size: 0.65em;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Mark all as read
    $('#markAllRead').click(function() {
        if (confirm('Are you sure you want to mark all notifications as read?')) {
            $.ajax({
                url: '{{ route("attendance.notifications.mark-all-read") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    alert('Failed to mark notifications as read. Please try again.');
                }
            });
        }
    });
    
    // Mark individual notification as read
    $('.mark-read-btn').click(function() {
        const notificationId = $(this).data('id');
        const button = $(this);
        
        $.ajax({
            url: `/attendance/notifications/${notificationId}/read`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    button.closest('.notification-item').removeClass('border-left-primary');
                    button.closest('.notification-item').find('.font-weight-bold').removeClass('font-weight-bold').addClass('text-muted');
                    button.closest('.notification-item').find('.badge-primary:contains("NEW")').remove();
                    button.remove();
                    
                    // Update counters
                    updateNotificationCounters();
                }
            },
            error: function() {
                alert('Failed to mark notification as read. Please try again.');
            }
        });
    });
    
    // Delete notification
    $('.delete-notification-btn').click(function() {
        if (confirm('Are you sure you want to delete this notification?')) {
            const notificationId = $(this).data('id');
            const notificationItem = $(this).closest('.notification-item');
            
            // Here you would make an AJAX call to delete the notification
            notificationItem.fadeOut(300, function() {
                $(this).remove();
                updateNotificationCounters();
            });
        }
    });
    
    // Create notification form
    $('#createNotificationForm').submit(function(e) {
        e.preventDefault();
        
        // Here you would submit the form data
        alert('Alert created successfully!');
        $('#createNotificationModal').modal('hide');
        
        // Optionally reload the page or add the new notification to the list
        setTimeout(() => location.reload(), 1000);
    });
    
    // Auto-refresh notifications every 30 seconds
    setInterval(function() {
        updateNotificationCounters();
    }, 30000);
});

function updateNotificationCounters() {
    // Update the unread count
    $.ajax({
        url: '{{ route("attendance.notifications.unread-count") }}',
        method: 'GET',
        success: function(response) {
            $('#unreadCount').text(response.count);
            $('#unreadBadge').text(response.count);
            
            if (response.count > 0) {
                $('#unreadBadge').removeClass('badge-secondary').addClass('badge-info');
            } else {
                $('#unreadBadge').removeClass('badge-info').addClass('badge-secondary');
            }
        }
    });
}

function showNotificationDetails(notificationId) {
    // Load and show notification details
    $('#notificationDetailsModal').modal('show');
}
</script>
@endpush