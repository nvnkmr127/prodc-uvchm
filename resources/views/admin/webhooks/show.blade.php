@extends('layouts.theme')
@section('title', 'Webhook Details')
@php
function ($value, $default = '' ?? "") {
    if (is_null($value) || $value === '' || is_array($value) || is_object($value)) {
        return $default;
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
@endphp
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Webhook Details</h1>
        <p class="mb-0 text-muted">{{ $eventInfo['name'] }} - {{ $eventInfo['description'] }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.webhooks.edit', $webhook) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="{{ route('admin.webhooks.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="row">
    {{-- Webhook Configuration --}}
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Configuration</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td class="font-weight-bold" width="25%">Event Type:</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="{{ isset($eventInfo['icon']) && is_string($eventInfo['icon']) ? $eventInfo['icon'] : 'fas fa-bell' }} text-muted mr-2"></i>
                                <span class="badge badge-info">{{ $eventInfo['name'] }}</span>
                                <span class="ml-2 text-muted small">{{ $eventInfo['category'] }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Endpoint URL:</td>
                        <td><code>{{ $webhook->url }}</code></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Status:</td>
                        <td>
                            @if($webhook->is_active)
                                <span class="badge badge-success"><i class="fas fa-play"></i> Active</span>
                            @else
                                <span class="badge badge-secondary"><i class="fas fa-pause"></i> Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @if($webhook->description)
                    <tr>
                        <td class="font-weight-bold">Description:</td>
                        <td>{{ $webhook->description }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="font-weight-bold">Timeout:</td>
                        <td>{{ $webhook->timeout_seconds }} seconds</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Auto-disable:</td>
                        <td>
                            @if($webhook->auto_disable_after_failures)
                                <span class="text-success">Yes</span> (after {{ $webhook->max_failures_before_disable }} failures)
                            @else
                                <span class="text-muted">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Secret Key:</td>
                        <td>
                            <code class="small">{{ substr($webhook->signing_secret, 0, 20) }}...</code>
                            <button class="btn btn-sm btn-outline-secondary ml-2" onclick="regenerateSecret()">
                                <i class="fas fa-sync"></i> Regenerate
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Created:</td>
                        <td>{{ $webhook->created_at->format('M d, Y \a\t g:i A') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Recent Calls --}}
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recent Calls</h6>
                <a href="{{ route('admin.webhooks.logs', $webhook) }}" class="btn btn-sm btn-outline-primary">
                    View All Logs
                </a>
            </div>
            <div class="card-body">
                @if($webhook->calls->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Response Time</th>
                                    <th>HTTP Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($webhook->calls->take(10) as $call)
                                    <tr>
                                        <td>
                                            @if($call->success)
                                                <span class="badge badge-success badge-sm">Success</span>
                                            @else
                                                <span class="badge badge-danger badge-sm">Failed</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $call->created_at->format('M d, g:i A') }}</td>
                                        <td class="small">{{ $call->execution_time_ms }}ms</td>
                                        <td class="small">{{ $call->status_code ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-2x text-gray-300 mb-2"></i>
                        <p class="text-muted">No webhook calls yet</p>
                        <form action="{{ route('admin.webhooks.test', $webhook) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-paper-plane"></i> Send Test Call
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Health Stats --}}
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-{{ $healthStatus['status'] === 'healthy' ? 'success' : ($healthStatus['status'] === 'warning' ? 'warning' : 'danger') }}">
                    Health Status
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    @php
                        $statusIcon = match($healthStatus['status']) {
                            'healthy' => 'fas fa-heart text-success',
                            'warning' => 'fas fa-exclamation-triangle text-warning',
                            'critical' => 'fas fa-skull-crossbones text-danger',
                            default => 'fas fa-question text-muted'
                        };
                    @endphp
                    <i class="{{ $statusIcon }} fa-3x mb-2"></i>
                    <h4 class="text-{{ $healthStatus['status'] === 'healthy' ? 'success' : ($healthStatus['status'] === 'warning' ? 'warning' : 'danger') }}">
                        {{ ucfirst($healthStatus['status']) }}
                    </h4>
                </div>

                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="font-weight-bold">Success Rate:</td>
                        <td class="text-right">
                            <span class="badge badge-{{ $healthStatus['success_rate'] >= 90 ? 'success' : ($healthStatus['success_rate'] >= 70 ? 'warning' : 'danger') }}">
                                {{ $healthStatus['success_rate'] }}%
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Total Calls:</td>
                        <td class="text-right">{{ $healthStatus['total_calls'] }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Successful:</td>
                        <td class="text-right text-success">{{ $healthStatus['successful_calls'] }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Failed:</td>
                        <td class="text-right text-danger">{{ $healthStatus['failed_calls'] }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Consecutive Failures:</td>
                        <td class="text-right">
                            @if($healthStatus['consecutive_failures'] > 0)
                                <span class="text-danger">{{ $healthStatus['consecutive_failures'] }}</span>
                            @else
                                <span class="text-success">0</span>
                            @endif
                        </td>
                    </tr>
                    @if($healthStatus['last_called'])
                    <tr>
                        <td class="font-weight-bold">Last Called:</td>
                        <td class="text-right small">{{ $healthStatus['last_called'] }}</td>
                    </tr>
                    @endif
                    @if($healthStatus['last_success'])
                    <tr>
                        <td class="font-weight-bold">Last Success:</td>
                        <td class="text-right small text-success">{{ $healthStatus['last_success'] }}</td>
                    </tr>
                    @endif
                    @if($healthStatus['last_failure'])
                    <tr>
                        <td class="font-weight-bold">Last Failure:</td>
                        <td class="text-right small text-danger">{{ $healthStatus['last_failure'] }}</td>
                    </tr>
                    @endif
                </table>

                @if($healthStatus['consecutive_failures'] >= 3)
                    <div class="alert alert-warning alert-sm">
                        <i class="fas fa-exclamation-triangle"></i>
                        This webhook is experiencing consecutive failures. Consider checking the endpoint.
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form action="{{ route('admin.webhooks.test', $webhook) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info btn-block btn-sm">
                            <i class="fas fa-paper-plane"></i> Send Test Request
                        </button>
                    </form>

                    <a href="{{ route('admin.webhooks.toggle', $webhook) }}" 
                       class="btn btn-{{ $webhook->is_active ? 'warning' : 'success' }} btn-block btn-sm">
                        <i class="fas fa-{{ $webhook->is_active ? 'pause' : 'play' }}"></i>
                        {{ $webhook->is_active ? 'Deactivate' : 'Activate' }}
                    </a>

                    <a href="{{ route('admin.webhooks.logs', $webhook) }}" class="btn btn-secondary btn-block btn-sm">
                        <i class="fas fa-history"></i> View All Logs
                    </a>

                    <button onclick="regenerateSecret()" class="btn btn-outline-primary btn-block btn-sm">
                        <i class="fas fa-key"></i> Regenerate Secret
                    </button>

                    <div class="dropdown-divider"></div>

                    <button onclick="deleteWebhook()" class="btn btn-danger btn-block btn-sm">
                        <i class="fas fa-trash"></i> Delete Webhook
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Hidden Forms --}}
<form id="regenerateSecretForm" method="POST" action="{{ route('admin.webhooks.regenerate-secret', $webhook) }}" style="display: none;">
    @csrf
</form>

<form id="deleteWebhookForm" method="POST" action="{{ route('admin.webhooks.destroy', $webhook) }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function regenerateSecret() {
    if (confirm('Are you sure you want to regenerate the secret key? You will need to update your webhook endpoint with the new key.')) {
        document.getElementById('regenerateSecretForm').submit();
    }
}

function deleteWebhook() {
    if (confirm('Are you sure you want to delete this webhook? This action cannot be undone.')) {
        document.getElementById('deleteWebhookForm').submit();
    }
}
</script>
@endsection