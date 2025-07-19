@extends('layouts.theme')
@section('title', 'Webhook Health Check')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Webhook Health Check</h1>
        <p class="mb-0 text-muted">Monitor the health and reachability of all webhook endpoints</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="refreshHealthCheck()" class="btn btn-sm btn-primary">
            <i class="fas fa-sync"></i> Refresh Check
        </button>
        <a href="{{ route('admin.webhooks.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

{{-- Health Summary --}}
<div class="row mb-4">
    @php
        $totalWebhooks = count($results);
        $healthyCount = collect($results)->where('health_status', 'healthy')->count();
        $warningCount = collect($results)->where('health_status', 'warning')->count();
        $criticalCount = collect($results)->where('health_status', 'critical')->count();
        $reachableCount = collect($results)->where('is_reachable', true)->count();
    @endphp

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Webhooks
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalWebhooks }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-webhook fa-2x text-gray-300"></i>
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
                            Healthy
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $healthyCount }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-heart fa-2x text-gray-300"></i>
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
                            Warning
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $warningCount }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                            Critical
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $criticalCount }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Health Check Results --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Health Check Results</h6>
        <div class="text-muted small">
            Last checked: {{ now()->format('M d, Y H:i:s') }}
        </div>
    </div>
    <div class="card-body">
        @if(count($results) > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th width="25%">Webhook URL</th>
                            <th width="15%">Event Type</th>
                            <th width="12%">Reachable</th>
                            <th width="12%">Health Status</th>
                            <th width="12%">Failures</th>
                            <th width="14%">Last Success</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            @php
                                $healthBadgeClass = match($result['health_status']) {
                                    'healthy' => 'badge-success',
                                    'warning' => 'badge-warning',
                                    'critical' => 'badge-danger',
                                    default => 'badge-secondary'
                                };
                                
                                $reachableBadgeClass = $result['is_reachable'] ? 'badge-success' : 'badge-danger';
                            @endphp
                            <tr class="{{ $result['health_status'] === 'critical' ? 'table-danger' : ($result['health_status'] === 'warning' ? 'table-warning' : '') }}">
                                <td>
                                    <div class="small">
                                        <code>{{ Str::limit($result['url'], 40) }}</code>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $result['event_name'] }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $reachableBadgeClass }}">
                                        <i class="fas fa-{{ $result['is_reachable'] ? 'check' : 'times' }}"></i>
                                        {{ $result['is_reachable'] ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $healthBadgeClass }}">
                                        {{ ucfirst($result['health_status']) }}
                                    </span>
                                </td>
                                <td>
                                    @if($result['consecutive_failures'] > 0)
                                        <span class="badge badge-danger">{{ $result['consecutive_failures'] }}</span>
                                    @else
                                        <span class="badge badge-success">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($result['last_success'])
                                        <span class="small text-success">{{ $result['last_success'] }}</span>
                                    @else
                                        <span class="small text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.webhooks.show', $result['id']) }}" 
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.webhooks.test', $result['id']) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-info" title="Test">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-heartbeat fa-3x text-gray-300 mb-3"></i>
                <h5 class="text-gray-500">No active webhooks found</h5>
                <p class="text-gray-400 mb-4">Create some webhooks to monitor their health status.</p>
                <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Your First Webhook
                </a>
            </div>
        @endif
    </div>
</div>

{{-- Health Check Information --}}
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">Health Status Levels</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge badge-success mr-2">Healthy</span>
                        <span>Working normally with good success rate</span>
                    </div>
                    <small class="text-muted ml-4">
                        • Less than 3 consecutive failures<br>
                        • Endpoint is reachable<br>
                        • Recent successful calls
                    </small>
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge badge-warning mr-2">Warning</span>
                        <span>Some issues detected, requires attention</span>
                    </div>
                    <small class="text-muted ml-4">
                        • 3-9 consecutive failures<br>
                        • Endpoint may be slow or intermittent<br>
                        • Monitor closely
                    </small>
                </div>

                <div class="mb-0">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge badge-danger mr-2">Critical</span>
                        <span>Not working, immediate action required</span>
                    </div>
                    <small class="text-muted ml-4">
                        • 10+ consecutive failures<br>
                        • Endpoint unreachable or disabled<br>
                        • May be auto-disabled
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">Troubleshooting Tips</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="font-weight-bold">🔴 Not Reachable</h6>
                    <ul class="small text-muted mb-3">
                        <li>Check if the URL is correct and accessible</li>
                        <li>Verify SSL certificates for HTTPS URLs</li>
                        <li>Ensure firewall allows outbound connections</li>
                        <li>Test the endpoint manually with curl/Postman</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6 class="font-weight-bold">⚠️ High Failure Rate</h6>
                    <ul class="small text-muted mb-3">
                        <li>Check webhook endpoint logs for errors</li>
                        <li>Verify payload format is being handled correctly</li>
                        <li>Ensure endpoint responds with 200 OK status</li>
                        <li>Check timeout settings (current: 30 seconds)</li>
                    </ul>
                </div>

                <div class="mb-0">
                    <h6 class="font-weight-bold">🔧 Quick Fixes</h6>
                    <ul class="small text-muted mb-0">
                        <li>Test the webhook manually using the Test button</li>
                        <li>Regenerate secret key if authentication fails</li>
                        <li>Reset failure count by toggling webhook off/on</li>
                        <li>Check webhook logs for detailed error messages</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshHealthCheck() {
    // Show loading state
    const refreshBtn = document.querySelector('[onclick="refreshHealthCheck()"]');
    const originalText = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    refreshBtn.disabled = true;
    
    // Reload the page to refresh the health check
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Auto-refresh every 2 minutes
setInterval(() => {
    window.location.reload();
}, 120000);

// Add tooltip for reachability check
document.addEventListener('DOMContentLoaded', function() {
    // You can add tooltips here if using Bootstrap tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endsection