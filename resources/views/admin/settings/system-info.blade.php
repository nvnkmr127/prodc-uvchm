@extends('layouts.theme')
@section('title', 'System Information')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">System Information</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item active">System Info</li>
            </ol>
        </nav>
    </div>
    <div>
        <button type="button" class="btn btn-sm btn-primary" onclick="refreshSystemInfo()">
            <i class="fas fa-sync mr-1"></i>Refresh
        </button>
    </div>
</div>

<div class="row">
    <!-- Application Info -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Application Information</h6>
                <i class="fas fa-info-circle text-gray-300"></i>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="font-weight-bold">Application Name:</td>
                            <td>{{ $systemInfo['application']['name'] ?? 'College Management System' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Version:</td>
                            <td>{{ $systemInfo['application']['version'] ?? '1.0.0' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Environment:</td>
                            <td>
                                @php $env = $systemInfo['application']['environment'] ?? 'unknown' @endphp
                                <span class="badge badge-{{ $env === 'production' ? 'success' : 'warning' }}">
                                    {{ ucfirst($env) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Debug Mode:</td>
                            <td>
                                @php $debug = $systemInfo['application']['debug_mode'] ?? 'Disabled' @endphp
                                <span class="badge badge-{{ str_contains($debug, 'Enabled') ? 'danger' : 'success' }}">
                                    {{ $debug }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Timezone:</td>
                            <td>{{ $systemInfo['application']['timezone'] ?? 'UTC' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">URL:</td>
                            <td>
                                @php $url = $systemInfo['application']['url'] ?? config('app.url') @endphp
                                <a href="{{ $url }}" target="_blank">{{ $url }}</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Maintenance Mode:</td>
                            <td>
                                @php $maintenance = ($systemInfo['application']['maintenance_mode'] ?? '0') === '1' @endphp
                                <span class="badge badge-{{ $maintenance ? 'warning' : 'success' }}">
                                    {{ $maintenance ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Info -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Server Information</h6>
                <i class="fas fa-server text-gray-300"></i>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="font-weight-bold">PHP Version:</td>
                            <td>{{ $systemInfo['server']['php_version'] ?? PHP_VERSION }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Laravel Version:</td>
                            <td>{{ $systemInfo['server']['laravel_version'] ?? app()->version() }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Operating System:</td>
                            <td>{{ $systemInfo['server']['operating_system'] ?? PHP_OS }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Server Software:</td>
                            <td>{{ $systemInfo['server']['server_software'] ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Server IP:</td>
                            <td>{{ $systemInfo['server']['server_ip'] ?? $_SERVER['SERVER_ADDR'] ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Memory Limit:</td>
                            <td>{{ $systemInfo['server']['memory_limit'] ?? ini_get('memory_limit') }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Memory Usage:</td>
                            <td>
                                @php 
                                    $memory = $systemInfo['server']['memory_usage'] ?? memory_get_usage(true);
                                    $memoryMB = round($memory / 1024 / 1024, 2);
                                @endphp
                                {{ $memoryMB }} MB
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Max Execution Time:</td>
                            <td>{{ $systemInfo['server']['max_execution_time'] ?? ini_get('max_execution_time') }}s</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Upload Max Size:</td>
                            <td>{{ $systemInfo['server']['upload_max_filesize'] ?? ini_get('upload_max_filesize') }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Post Max Size:</td>
                            <td>{{ $systemInfo['server']['post_max_size'] ?? ini_get('post_max_size') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Database & Storage -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Database & Storage</h6>
                <i class="fas fa-database text-gray-300"></i>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="font-weight-bold">Database Driver:</td>
                            <td>{{ ucfirst($systemInfo['database']['driver'] ?? config('database.default')) }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Database Host:</td>
                            <td>{{ $systemInfo['database']['host'] ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Database Name:</td>
                            <td>{{ $systemInfo['database']['database'] ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Total Tables:</td>
                            <td>{{ $systemInfo['database']['total_tables'] ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Total Records:</td>
                            <td>{{ $systemInfo['database']['total_records'] ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Cache Driver:</td>
                            <td>{{ ucfirst($systemInfo['cache']['default_driver'] ?? config('cache.default')) }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Cache Status:</td>
                            <td>
                                @php $cacheStatus = $systemInfo['cache']['status'] ?? 'Unknown' @endphp
                                <span class="badge badge-{{ $cacheStatus === 'Working' ? 'success' : 'warning' }}">
                                    {{ $cacheStatus }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Queue Driver:</td>
                            <td>{{ ucfirst($systemInfo['queue']['default_connection'] ?? config('queue.default')) }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Mail Driver:</td>
                            <td>{{ ucfirst($systemInfo['mail']['default_mailer'] ?? config('mail.default')) }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Mail Status:</td>
                            <td>
                                @php $mailStatus = $systemInfo['mail']['status'] ?? 'Unknown' @endphp
                                <span class="badge badge-{{ $mailStatus === 'Configured' ? 'success' : 'warning' }}">
                                    {{ $mailStatus }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Application Statistics</h6>
                <i class="fas fa-chart-bar text-gray-300"></i>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="font-weight-bold">Total Settings:</td>
                            <td>{{ number_format($systemInfo['statistics']['total_settings'] ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">College Name:</td>
                            <td>{{ $systemInfo['college']['name'] ?? 'Not Set' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">College Email:</td>
                            <td>{{ $systemInfo['college']['email'] ?? 'Not Set' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Current Academic Year:</td>
                            <td>{{ $systemInfo['academic']['current_year'] ?? 'Not Set' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Enrollment Prefix:</td>
                            <td>{{ $systemInfo['academic']['enrollment_prefix'] ?? 'Not Set' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Currency:</td>
                            <td>{{ $systemInfo['financial']['currency_symbol'] ?? '₹' }} ({{ $systemInfo['financial']['currency_code'] ?? 'INR' }})</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Last Backup:</td>
                            <td>{{ $systemInfo['statistics']['last_backup'] ?? 'Never' }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">System Uptime:</td>
                            <td>{{ $systemInfo['statistics']['uptime'] ?? 'Unknown' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Disk Usage -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Disk Usage</h6>
                <i class="fas fa-hdd text-gray-300"></i>
            </div>
            <div class="card-body">
                @if(isset($systemInfo['server']['disk_space']) && !isset($systemInfo['server']['disk_space']['error']))
                    @php $diskUsage = $systemInfo['server']['disk_space'] @endphp
                    <div class="row">
                        <div class="col-md-8">
                            <div class="progress mb-3" style="height: 25px;">
                                @php 
                                    $percentage = (float) str_replace('%', '', $diskUsage['usage_percentage'] ?? '0%');
                                    $progressColor = $percentage > 80 ? 'danger' : ($percentage > 60 ? 'warning' : 'success');
                                @endphp
                                <div class="progress-bar bg-{{ $progressColor }}" 
                                     role="progressbar" 
                                     style="width: {{ $percentage }}%"
                                     aria-valuenow="{{ $percentage }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ $percentage }}%
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-right">
                                <strong>{{ $diskUsage['used'] ?? 'Unknown' }}</strong> used of <strong>{{ $diskUsage['total'] ?? 'Unknown' }}</strong><br>
                                <small class="text-muted">{{ $diskUsage['free'] ?? 'Unknown' }} available</small>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Unable to retrieve disk usage information: {{ $systemInfo['server']['disk_space']['error'] ?? 'Unknown error' }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- System Health -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">System Health Check</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="runHealthCheck()">
                    <i class="fas fa-heartbeat mr-1"></i>Run Check
                </button>
            </div>
            <div class="card-body">
                <div id="healthCheckResults">
                    <p class="text-muted">Click "Run Check" to perform a system health check.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- PHP Extensions -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Required PHP Extensions</h6>
                <i class="fas fa-puzzle-piece text-gray-300"></i>
            </div>
            <div class="card-body">
                @if(isset($systemInfo['extensions']))
                    <div class="row">
                        @foreach($systemInfo['extensions'] as $extension => $loaded)
                            <div class="col-md-3 mb-2">
                                <span class="badge badge-{{ $loaded ? 'success' : 'danger' }} mr-2">
                                    <i class="fas fa-{{ $loaded ? 'check' : 'times' }}"></i>
                                </span>
                                {{ $extension }}
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">Extension information not available.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function refreshSystemInfo() {
    location.reload();
}

function runHealthCheck() {
    const resultsDiv = document.getElementById('healthCheckResults');
    resultsDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Running health check...</div>';

    fetch('{{ route("admin.settings.health-check") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        }
        throw new Error('Server error occurred. Status: ' + response.status);
    })
    .then(data => {
        let html = '<div class="row">';
        html += `<div class="col-12 mb-3">
            <div class="alert alert-${data.status === 'healthy' ? 'success' : 'danger'}">
                <i class="fas fa-${data.status === 'healthy' ? 'check-circle' : 'exclamation-triangle'} mr-2"></i>
                System Status: <strong>${data.status ? data.status.toUpperCase() : 'UNKNOWN'}</strong>
                <small class="float-right">${data.timestamp ? new Date(data.timestamp).toLocaleString() : 'Unknown time'}</small>
            </div>
        </div>`;

        if (data.checks && typeof data.checks === 'object') {
            Object.keys(data.checks).forEach(checkName => {
                const check = data.checks[checkName];
                const statusClass = check.status ? 'success' : 'danger';
                const statusIcon = check.status ? 'check' : 'times';
                html += `<div class="col-md-6 mb-2">
                    <div class="card border-${statusClass}">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-${statusIcon} text-${statusClass} mr-2"></i>
                                <div>
                                    <strong>${checkName.charAt(0).toUpperCase() + checkName.slice(1)}</strong><br>
                                    <small>${check.message || 'No message'}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
        }
        
        html += '</div>';
        resultsDiv.innerHTML = html;
    })
    .catch(error => {
        console.error('Health check error:', error);
        resultsDiv.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Failed to run health check: ${error.message}
        </div>`;
    });
}
</script>
@endpush