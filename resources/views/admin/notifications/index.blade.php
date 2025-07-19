@extends('layouts.theme')

@section('title', 'All Notifications')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">📋 All Notifications</h1>
        <a href="{{ route('admin.notifications.dashboard') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">🔍 Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                    {{ ucfirst($category) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="priority" class="form-control">
                            <option value="">All Priorities</option>
                            @foreach($priorities as $priority)
                                <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>
                                    {{ ucfirst($priority) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-2">
                            <input type="checkbox" name="unread_only" value="1" class="form-check-input" {{ request('unread_only') ? 'checked' : '' }}>
                            <label class="form-check-label">Unread Only</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('notifications.index') }}" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card shadow">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">📧 Notifications</h6>
        </div>
        <div class="card-body">
            @if($notifications->count() > 0)
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                            <tr class="{{ $notification->read_by ? '' : 'table-warning' }}">
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
                                <td>
                                    <a href="{{ route('notifications.show', $notification) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(!$notification->read_by)
                                        <button onclick="markAsRead({{ $notification->id }})" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{ $notifications->links() }}
            @else
                <div class="text-center py-4">
                    <i class="fas fa-bell-slash fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">No notifications found.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch(`/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to mark as read');
        }
    });
}
</script>
@endsection