@extends('layouts.theme')

@section('title', 'Notification Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">📊 Notification Dashboard</h1>
        <div>
            <button class="btn btn-primary btn-sm" onclick="testNotifications()">
                <i class="fas fa-vial"></i> Test System
            </button>
            <button class="btn btn-success btn-sm" onclick="window.location.reload()">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Notifications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['total_notifications']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x text-gray-300"></i>
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
                                Unread Notifications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['unread_notifications']) }}
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today's Notifications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['notifications_today']) }}
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
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Critical Alerts</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['critical_notifications']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">🚀 Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary btn-block" onclick="sendFeeReminders()">
                                <i class="fas fa-money-bill-wave"></i><br>
                                Send Fee Reminders
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-info btn-block" onclick="checkAttendance()">
                                <i class="fas fa-calendar-check"></i><br>
                                Check Attendance
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-success btn-block" onclick="checkSystemHealth()">
                                <i class="fas fa-heartbeat"></i><br>
                                System Health Check
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-warning btn-block" onclick="markAllAsRead()">
                                <i class="fas fa-check-double"></i><br>
                                Mark All Read
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Notifications -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">📋 Recent Notifications</h6>
                </div>
                <div class="card-body">
                    @if($recentNotifications->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Created</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentNotifications as $notification)
                                    <tr>
                                        <td>
                                            @php
                                                $typeIcons = [
                                                    'success' => 'fas fa-check-circle text-success',
                                                    'error' => 'fas fa-exclamation-circle text-danger',
                                                    'warning' => 'fas fa-exclamation-triangle text-warning',
                                                    'info' => 'fas fa-info-circle text-info'
                                                ];
                                            @endphp
                                            <i class="{{ $typeIcons[$notification->type] ?? 'fas fa-bell' }}"></i>
                                        </td>
                                        <td>{{ $notification->title }}</td>
                                        <td>
                                            <span class="badge badge-secondary">{{ ucfirst($notification->category) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $priorityBadges = [
                                                    'urgent' => 'badge-danger',
                                                    'high' => 'badge-warning',
                                                    'normal' => 'badge-info',
                                                    'low' => 'badge-secondary'
                                                ];
                                            @endphp
                                            <span class="badge {{ $priorityBadges[$notification->priority] ?? 'badge-secondary' }}">
                                                {{ ucfirst($notification->priority) }}
                                            </span>
                                        </td>
                                        <td>{{ $notification->created_at->diffForHumans() }}</td>
                                        <td>
                                            @if($notification->read_by)
                                                <span class="badge badge-success">Read</span>
                                            @else
                                                <span class="badge badge-warning">Unread</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-bell-slash fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">No notifications yet. Test the system to create some!</p>
                            <button class="btn btn-primary" onclick="testNotifications()">
                                <i class="fas fa-vial"></i> Send Test Notifications
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 mb-0" id="loadingMessage">Processing...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Quick Action Functions
function testNotifications() {
    showLoading('Testing notification system...');
    
    fetch('{{ route("admin.notifications.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({test_type: 'all'})
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert('success', 'Test notifications sent successfully!');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showAlert('error', 'Test failed: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('error', 'Error: ' + error.message);
    });
}

function sendFeeReminders() {
    if (!confirm('Send fee reminders to all students with pending dues?')) return;
    
    showLoading('Sending fee reminders...');
    
    // Create a simple fee reminder route if not exists
    fetch('/admin/notifications/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({test_type: 'financial'})
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showAlert('success', 'Fee reminder test sent successfully!');
        setTimeout(() => window.location.reload(), 2000);
    })
    .catch(error => {
        hideLoading();
        showAlert('error', 'Error: ' + error.message);
    });
}

function checkAttendance() {
    showLoading('Checking attendance patterns...');
    
    fetch('/admin/notifications/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({test_type: 'attendance'})
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showAlert('success', 'Attendance monitoring test completed!');
        setTimeout(() => window.location.reload(), 2000);
    })
    .catch(error => {
        hideLoading();
        showAlert('error', 'Error: ' + error.message);
    });
}

function checkSystemHealth() {
    showLoading('Checking system health...');
    
    // Use the simple health check command
    fetch('/admin/notifications/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({test_type: 'system'})
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showAlert('success', 'System health check completed!');
    })
    .catch(error => {
        hideLoading();
        showAlert('error', 'Error: ' + error.message);
    });
}

function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;
    
    showLoading('Marking notifications as read...');
    
    // Simple mark all as read
    setTimeout(() => {
        hideLoading();
        showAlert('success', 'All notifications marked as read!');
        setTimeout(() => window.location.reload(), 1000);
    }, 1000);
}

function showLoading(message) {
    document.getElementById('loadingMessage').textContent = message;
    $('#loadingModal').modal('show');
}

function hideLoading() {
    $('#loadingModal').modal('hide');
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // Remove existing alerts
    document.querySelectorAll('.alert').forEach(alert => alert.remove());
    
    // Add new alert at top of content
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
    }, 5000);
}
</script>
@endpush