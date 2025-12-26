@extends('layouts.theme')

@section('title', 'Notification Details')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Notification Details</h1>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ $notification->title }}</h6>
            <span class="badge badge-{{ $notification->priority_color ?? 'secondary' }}">
                {{ ucfirst($notification->priority) }}
            </span>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <h5 class="small font-weight-bold text-muted">Message</h5>
                <p class="lead">{{ $notification->message }}</p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <span class="small font-weight-bold text-muted d-block">Category</span>
                        {{ ucfirst($notification->category) }}
                    </div>
                    <div class="mb-3">
                        <span class="small font-weight-bold text-muted d-block">Received At</span>
                        {{ $notification->created_at->format('d M Y, h:i A') }}
                    </div>
                </div>
                <div class="col-md-6">
                    @if($notification->action_url)
                    <div class="mb-3">
                        <span class="small font-weight-bold text-muted d-block">Action</span>
                        <a href="{{ $notification->action_url }}" class="btn btn-primary btn-sm mt-1">
                            {{ $notification->action_text ?? 'View Resource' }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            @if(!empty($notification->data))
            <hr>
            <div class="mb-3">
                <span class="small font-weight-bold text-muted d-block mb-2">Additional Data</span>
                <div class="bg-light p-3 rounded border">
                    <pre class="mb-0 small">{{ json_encode($notification->data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection