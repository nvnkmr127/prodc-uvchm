@extends('layouts.theme')

@section('title', 'Notification Details')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">📧 Notification Details</h1>
        <div>
            <a href="{{ route('notifications.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            @if(!$notification->isReadBy(auth()->id()))
                <button onclick="markAsRead()" class="btn btn-success btn-sm">
                    <i class="fas fa-check"></i> Mark as Read
                </button>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">{{ $notification->title }}</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Type:</strong>
                            @php
                                $typeIcons = [
                                    'success' => 'fas fa-check-circle text-success',
                                    'error' => 'fas fa-exclamation-circle text-danger',
                                    'warning' => 'fas fa-exclamation-triangle text-warning',
                                    'info' => 'fas fa-info-circle text-info'
                                ];
                            @endphp
                            <i class="{{ $typeIcons[$notification->type] ?? 'fas fa-bell' }}"></i>
                            {{ ucfirst($notification->type) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Category:</strong>
                            <span class="badge badge-secondary">{{ ucfirst($notification->category) }}</span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Priority:</strong>
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
                        </div>
                        <div class="col-md-6">
                            <strong>Created:</strong>
                            {{ $notification->created_at->format('M d, Y H:i:s') }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Message:</strong>
                        <div class="mt-2 p-3 bg-light rounded">
                            {{ $notification->message }}
                        </div>
                    </div>

                    @if($notification->action_url)
                        <div class="mb-3">
                            <a href="{{ $notification->action_url }}" class="btn btn-primary">
                                {{ $notification->action_text ?? 'Take Action' }}
                            </a>
                        </div>
                    @endif

                    @if($notification->data)
                        <div class="mb-3">
                            <strong>Additional Data:</strong>
                            <pre class="bg-light p-3 rounded mt-2"><code>{{ json_encode($notification->data, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">📊 Status</h6>
                </div>
                <div class="card-body">
                    @if($notification->read_by)
                        <p><strong>Status:</strong> <span class="badge badge-success">Read</span></p>
                        <p><strong>Read by:</strong> {{ count($notification->read_by) }} user(s)</p>
                    @else
                        <p><strong>Status:</strong> <span class="badge badge-warning">Unread</span></p>
                    @endif
                    
                    @if($notification->expires_at)
                        <p><strong>Expires:</strong> {{ $notification->expires_at->diffForHumans() }}</p>
                    @endif
                    
                    @if($notification->requires_action)
                        <p><strong>Requires Action:</strong> <span class="badge badge-warning">Yes</span></p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markAsRead() {
    fetch(`{{ route('notifications.mark-read', $notification) }}`, {
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