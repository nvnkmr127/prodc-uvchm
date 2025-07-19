{{-- resources/views/admin/webhooks/index.blade.php - Enhanced version --}}
@extends('layouts.theme')
@section('title', 'Webhook Management')

@push('styles')
<style>
    .webhook-status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .webhook-status-indicator.active { background-color: #28a745; }
    .webhook-status-indicator.inactive { background-color: #6c757d; }
    .webhook-status-indicator.error { background-color: #dc3545; }
    .webhook-status-indicator.warning { background-color: #ffc107; }
    
    .webhook-url-cell {
        max-width: 300px;
        word-break: break-all;
    }
    
    .stats-card {
        transition: transform 0.2s;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
    }
    
    .table-actions {
        white-space: nowrap;
    }
    
    .webhook-logs-preview {
        max-height: 200px;
        overflow-y: auto;
        font-size: 0.85rem;
    }
    
    .filter-section {
        background: #f8f9fc;
        border-radius: 0.35rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .event-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .webhook-health-indicator {
        font-size: 0.75rem;
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-webhook text-primary"></i> Webhook Management
        </h1>
        <p class="mb-0 text-muted">Monitor and manage automated notifications to external systems</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#webhookDocsModal">
            <i class="fas fa-book fa-sm"></i> Documentation
        </button>
        <a href="{{ route('admin.webhooks.create') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add Webhook
        </a>
    </div>
</div>

{{-- Alert Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- Enhanced Statistics Cards --}}
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Webhooks
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ isset($webhooks) && method_exists($webhooks, 'total') ? $webhooks->total() : (isset($webhooks) ? $webhooks->count() : 0) }}
                        </div>
                        <div class="text-xs text-muted mt-1">
                            <i class="fas fa-info-circle"></i> Configured endpoints
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-webhook fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Active Webhooks
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ isset($webhooks) ? $webhooks->where('is_active', true)->count() : 0 }}
                        </div>
                        <div class="text-xs text-muted mt-1">
                            <i class="fas fa-play-circle"></i> Currently enabled
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-toggle-on fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Today's Calls
                        </div>
                        @php
                            try {
                                $recentCalls = \App\Models\WebhookCall::whereDate('created_at', today())->count();
                                $yesterdayCalls = \App\Models\WebhookCall::whereDate('created_at', today()->subDay())->count();
                                $trend = $yesterdayCalls > 0 ? (($recentCalls - $yesterdayCalls) / $yesterdayCalls) * 100 : 0;
                            } catch (\Exception $e) {
                                $recentCalls = 0;
                                $trend = 0;
                            }
                        @endphp
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($recentCalls) }}</div>
                        <div class="text-xs text-muted mt-1">
                            @if($trend > 0)
                                <i class="fas fa-arrow-up text-success"></i> +{{ number_format($trend, 1) }}%
                            @elseif($trend < 0)
                                <i class="fas fa-arrow-down text-danger"></i> {{ number_format($trend, 1) }}%
                            @else
                                <i class="fas fa-minus text-muted"></i> No change
                            @endif
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-paper-plane fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Success Rate
                        </div>
                        @php
                            try {
                                $totalToday = \App\Models\WebhookCall::whereDate('created_at', today())->count();
                                $successToday = \App\Models\WebhookCall::whereDate('created_at', today())->where('success', true)->count();
                                $successRate = $totalToday > 0 ? round(($successToday / $totalToday) * 100, 1) : 100;
                            } catch (\Exception $e) {
                                $successRate = 100;
                            }
                        @endphp
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $successRate }}%</div>
                        <div class="text-xs text-muted mt-1">
                            @if($successRate >= 95)
                                <i class="fas fa-check-circle text-success"></i> Excellent
                            @elseif($successRate >= 80)
                                <i class="fas fa-exclamation-triangle text-warning"></i> Good
                            @else
                                <i class="fas fa-times-circle text-danger"></i> Needs attention
                            @endif
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters and Search --}}
<div class="filter-section">
    <form method="GET" action="{{ route('admin.webhooks.index') }}" class="row">
        <div class="col-md-4 mb-2">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" name="search" class="form-control" placeholder="Search webhooks..." 
                       value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <select name="event_type" class="form-control">
                <option value="">All Event Types</option>
                @foreach(['user.created', 'user.updated', 'order.created', 'payment.success', 'email.sent'] as $event)
                    <option value="{{ $event }}" {{ request('event_type') == $event ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('.', ' ', $event)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <div class="btn-group w-100">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="{{ route('admin.webhooks.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </div>
    </form>
</div>

{{-- Main Webhooks Table --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Configured Webhooks</h6>
        <div class="d-flex align-items-center">
            <small class="text-muted mr-3">
                Showing {{ isset($webhooks) ? $webhooks->count() : 0 }} 
                @if(isset($webhooks) && method_exists($webhooks, 'total'))
                    of {{ $webhooks->total() }}
                @endif
                webhooks
            </small>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-cog"></i> Actions
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="bulkToggleWebhooks(true)">
                        <i class="fas fa-play text-success"></i> Enable All
                    </a>
                    <a class="dropdown-item" href="#" onclick="bulkToggleWebhooks(false)">
                        <i class="fas fa-pause text-warning"></i> Disable All
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="testAllWebhooks()">
                        <i class="fas fa-paper-plane text-info"></i> Test All Active
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if(isset($webhooks) && $webhooks->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th width="25%">
                                <i class="fas fa-bolt"></i> Event & Details
                            </th>
                            <th width="30%">
                                <i class="fas fa-link"></i> Endpoint
                            </th>
                            <th width="15%">
                                <i class="fas fa-heartbeat"></i> Health
                            </th>
                            <th width="15%">
                                <i class="fas fa-clock"></i> Last Activity
                            </th>
                            <th width="15%">
                                <i class="fas fa-cogs"></i> Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($webhooks as $webhook)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-2">
                                            <i class="{{ getEventTypeIcon($webhook->event_name) }} text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ ($webhook->event_name ?? "") }}</div>
                                            @if($webhook->description)
                                                <small class="text-muted">{{ ($webhook->description ?? "") }}</small>
                                            @endif
                                            <div class="mt-1">
                                                <span class="badge badge-light event-type-badge">
                                                    {{ ucfirst(str_replace('.', ' ', $webhook->event_name)) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="webhook-url-cell">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <code class="small">{{ $webhook->url ?? "" }}</code>
                                            @if($webhook->secret)
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="fas fa-key"></i> Secured
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary ml-2" 
                                                onclick="copyToClipboard('{{ $webhook->url }}')" 
                                                title="Copy URL">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = getStatusBadgeClass($webhook);
                                        $recentCalls = $webhook->calls()->where('created_at', '>', now()->subHours(24))->count();
                                        $successRate = $recentCalls > 0 ? 
                                            round(($webhook->calls()->where('created_at', '>', now()->subHours(24))->where('success', true)->count() / $recentCalls) * 100) : 100;
                                    @endphp
                                    
                                    <div class="d-flex align-items-center">
                                        <span class="webhook-status-indicator {{ $webhook->is_active ? 'active' : 'inactive' }}"></span>
                                        <div>
                                            @if($webhook->is_active)
                                                <span class="badge badge-{{ $badgeClass }}">
                                                    @if($badgeClass == 'success')
                                                        <i class="fas fa-check-circle"></i> Healthy
                                                    @elseif($badgeClass == 'warning')
                                                        <i class="fas fa-exclamation-triangle"></i> Unused
                                                    @else
                                                        <i class="fas fa-times-circle"></i> Issues
                                                    @endif
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-pause"></i> Inactive
                                                </span>
                                            @endif
                                            @if($recentCalls > 0)
                                                <div class="webhook-health-indicator bg-light text-dark mt-1">
                                                    {{ $successRate }}% success
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($webhook->last_called_at)
                                        <span class="small">{{ $webhook->last_called_at->diffForHumans() }}</span>
                                        <div class="text-muted small">
                                            {{ $webhook->last_called_at->format('M j, Y g:i A') }}
                                        </div>
                                    @else
                                        <span class="text-muted small">
                                            <i class="fas fa-clock"></i> Never called
                                        </span>
                                    @endif
                                </td>
                              <td class="table-actions">
    <div class="btn-group" role="group">

        {{-- Test Webhook --}}
        <button type="button" class="btn btn-sm btn-info" 
                onclick="testWebhook(this)"
                data-url="{{ route('admin.webhooks.test', $webhook) }}"
                title="Send Test Request">
            <i class="fas fa-paper-plane"></i>
        </button>

        {{-- View Logs (This is the restored button) --}}
        <a href="{{ route('admin.webhooks.logs', $webhook) }}" 
           class="btn btn-sm btn-secondary" 
           title="View Request Logs">
            <i class="fas fa-history"></i>
        </a>

        {{-- Edit --}}
        <a href="{{ route('admin.webhooks.edit', $webhook) }}" 
           class="btn btn-sm btn-primary" 
           title="Edit Webhook">
            <i class="fas fa-edit"></i>
        </a>

        {{-- Toggle Active Status --}}
        <button type="button" 
                class="btn btn-sm btn-{{ $webhook->is_active ? 'warning' : 'success' }}" 
                onclick="toggleWebhook(this)"
                data-url="{{ route('admin.webhooks.toggle', $webhook) }}"
                title="{{ $webhook->is_active ? 'Deactivate' : 'Activate' }}">
            <i class="fas fa-{{ $webhook->is_active ? 'pause' : 'play' }}"></i>
        </button>

        {{-- Delete --}}
        <button type="button" class="btn btn-sm btn-danger" 
                onclick="deleteWebhook(this)"
                data-url="{{ route('admin.webhooks.destroy', $webhook) }}"
                title="Delete Webhook">
            <i class="fas fa-trash"></i>
        </button>

    </div>
</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if(isset($webhooks) && method_exists($webhooks, 'links'))
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Showing {{ $webhooks->firstItem() }} to {{ $webhooks->lastItem() }} 
                        of {{ $webhooks->total() }} results
                    </div>
                    <div>
                        {{ $webhooks->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-webhook fa-3x text-gray-300 mb-3"></i>
                <h5 class="text-gray-500">No webhooks found</h5>
                @if(request()->hasAny(['search', 'event_type', 'status']))
                    <p class="text-gray-400 mb-4">No webhooks match your current filters.</p>
                    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-outline-primary mr-2">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                @else
                    <p class="text-gray-400 mb-4">Start by creating your first webhook to receive automated notifications.</p>
                @endif
                <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Your First Webhook
                </a>
            </div>
        @endif
    </div>
</div>

{{-- Documentation Modal --}}
<div class="modal fade" id="webhookDocsModal" tabindex="-1" role="dialog" aria-labelledby="webhookDocsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="webhookDocsModalLabel">
                    <i class="fas fa-book text-primary"></i> Webhook Documentation
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>What are Webhooks?</h6>
                <p>Webhooks are HTTP callbacks that automatically notify external systems when specific events occur in your application.</p>
                
                <h6>Available Events</h6>
                <ul>
                    <li><strong>user.created</strong> - Triggered when a new user registers</li>
                    <li><strong>user.updated</strong> - Triggered when user information is modified</li>
                    <li><strong>order.created</strong> - Triggered when a new order is placed</li>
                    <li><strong>payment.success</strong> - Triggered when a payment is successful</li>
                    <li><strong>email.sent</strong> - Triggered when an email is sent</li>
                </ul>

                <h6>Payload Structure</h6>
                <pre class="bg-light p-3 rounded"><code>{
  "event": "user.created",
  "timestamp": "2024-01-15T10:30:00Z",
  "data": {
    "user_id": 123,
    "email": "user@example.com"
  },
  "webhook_id": "webhook-uuid"
}</code></pre>

                <h6>Security</h6>
                <p>Use webhook secrets to verify request authenticity. The secret is sent in the <code>X-Webhook-Signature</code> header.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // You can add a toast notification here if you like.
        console.log('URL copied to clipboard!');
    });
}

function testWebhook(button) {
    const url = button.dataset.url; // Get URL from the button's data attribute
    if (confirm('Send a test request to this webhook?')) {
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // You can replace this with a more user-friendly toast notification
            alert(data.success ? 'Test webhook sent successfully!' : 'Failed to send test webhook: ' + data.message);
        })
        .catch(error => {
            console.error('Error sending test webhook:', error);
            alert('An error occurred while sending the test webhook.');
        });
    }
}

function toggleWebhook(button) {
    const url = button.dataset.url; // Get URL from the button's data attribute
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
         if(response.ok) {
             window.location.reload();
         } else {
             alert('Failed to update webhook status.');
         }
    });
}

function deleteWebhook(button) {
    const url = button.dataset.url; // Get URL from the button's data attribute
    if (confirm('Are you sure you want to delete this webhook? This action cannot be undone.')) {
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if(response.ok) {
                window.location.reload();
            } else {
                alert('Failed to delete webhook.');
            }
        });
    }
}
</script>
@endpush

@push('styles')
<style>
/* Additional responsive enhancements */
@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        border-radius: 0.25rem !important;
    }
    
    .filter-section .row > div {
        margin-bottom: 0.5rem;
    }
    
    .d-sm-flex {
        flex-direction: column;
        align-items: stretch !important;
    }
    
    .d-sm-flex > div:last-child {
        margin-top: 1rem;
    }
}

@media (max-width: 576px) {
    .card-header .d-flex {
        flex-direction: column;
        align-items: stretch;
    }
    
    .card-header .d-flex > * {
        margin-bottom: 0.5rem;
    }
    
    .table-actions .btn-group {
        flex-wrap: wrap;
    }
    
    .webhook-url-cell {
        max-width: 150px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .filter-section {
        background: #2d3748;
        color: #e2e8f0;
    }
    
    .webhook-status-indicator.active {
        background-color: #68d391;
    }
    
    .webhook-status-indicator.inactive {
        background-color: #a0aec0;
    }
    
    .webhook-status-indicator.error {
        background-color: #fc8181;
    }
    
    .webhook-status-indicator.warning {
        background-color: #f6e05e;
    }
}

/* Print styles */
@media print {
    .btn, .btn-group, .dropdown, .filter-section {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 12px;
    }
    
    .badge {
        border: 1px solid #000;
        color: #000 !important;
        background: transparent !important;
    }
}

/* Accessibility improvements */
.btn:focus,
.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    outline: none;
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .badge {
        border: 2px solid;
    }
    
    .webhook-status-indicator {
        border: 2px solid #000;
    }
}

/* Reduce motion for users who prefer it */
@media (prefers-reduced-motion: reduce) {
    .stats-card,
    .btn,
    * {
        transition: none !important;
        animation: none !important;
    }
}

/* Custom scrollbar for webkit browsers */
.webhook-logs-preview::-webkit-scrollbar {
    width: 6px;
}

.webhook-logs-preview::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.webhook-logs-preview::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.webhook-logs-preview::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Loading states */
.btn.loading {
    position: relative;
    color: transparent !important;
}

.btn.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* Enhanced focus indicators */
.table tbody tr:focus-within {
    background-color: rgba(0, 123, 255, 0.1);
    outline: 2px solid rgba(0, 123, 255, 0.5);
    outline-offset: -2px;
}

/* Improved badge visibility */
.badge {
    font-weight: 600;
    letter-spacing: 0.025em;
}

/* Enhanced card hover effects */
.stats-card:hover .card-body {
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
}

/* Tooltip enhancements */
.tooltip {
    font-size: 0.875rem;
}

.tooltip-inner {
    max-width: 300px;
    text-align: left;
}

/* Form enhancements */
.form-control {
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:hover:not(:focus) {
    border-color: #80bdff;
}

/* Table enhancements */
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

.table th {
    border-top: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: #6c757d;
}

/* Icon enhancements */
.fas, .far {
    width: 1em;
    text-align: center;
}

/* Alert enhancements */
.alert {
    border: none;
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #28a745;
    background-color: rgba(40, 167, 69, 0.1);
}

.alert-danger {
    border-left-color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
}

.alert-info {
    border-left-color: #17a2b8;
    background-color: rgba(23, 162, 184, 0.1);
}
</style>
@endpush
@endsection