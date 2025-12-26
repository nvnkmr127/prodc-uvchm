@extends('layouts.theme')
@section('title', 'Webhook Health Dashboard')

@push('styles')
    <style>
        .card-premium {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            background: #fff;
            transition: transform 0.2s;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .pill-success {
            background: #d1fae5;
            color: #065f46;
        }

        .pill-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .pill-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-health th {
            border-top: none;
            background: #f9fafb;
            color: #6b7280;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .tip-card {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">System Health</h1>
                <p class="mb-0 text-muted">Real-time status of webhook delivery infrastructure.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.location.reload()" class="btn btn-primary shadow-sm">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh Status
                </button>
                <a href="{{ route('admin.webhooks.index') }}" class="btn btn-light border shadow-sm text-gray-700">
                    <i class="fas fa-list mr-2"></i>Webhook List
                </a>
            </div>
        </div>

        @php
            $totalWebhooks = count($results);
            $healthyCount = collect($results)->where('health_status', 'healthy')->count();
            $warningCount = collect($results)->where('health_status', 'warning')->count();
            $criticalCount = collect($results)->where('health_status', 'critical')->count();
        @endphp

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-premium h-100 py-2 border-left-primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Monitored Endpoints
                                </div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $totalWebhooks }}</div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon bg-primary-light text-primary">
                                    <i class="fas fa-satellite-dish"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-premium h-100 py-2 border-left-success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Operational</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $healthyCount }}</div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon bg-success-light text-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-premium h-100 py-2 border-left-warning">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Degraded</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $warningCount }}</div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon bg-warning-light text-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-premium h-100 py-2 border-left-danger">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Down / Critical</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $criticalCount }}</div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon bg-danger-light text-danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Table -->
            <div class="col-lg-8">
                <div class="card card-premium shadow mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-gray-800">Endpoint Status</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-health align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="pl-4">Endpoint</th>
                                    <th>Latency</th>
                                    <th>Status</th>
                                    <th>Last Success</th>
                                    <th class="text-right pr-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $result)
                                    @php
                                        $statusClass = match ($result['health_status']) {
                                            'healthy' => 'pill-success',
                                            'warning' => 'pill-warning',
                                            'critical' => 'pill-danger',
                                            default => 'badge-secondary'
                                        };
                                        $icon = match ($result['health_status']) {
                                            'healthy' => 'fa-check',
                                            'warning' => 'fa-exclamation',
                                            'critical' => 'fa-times',
                                            default => 'fa-question'
                                        };
                                    @endphp
                                    <tr>
                                        <td class="pl-4">
                                            <div class="font-weight-bold text-dark">{{ Str::limit($result['url'], 40) }}</div>
                                            <div class="small text-muted">{{ $result['event_name'] }}</div>
                                        </td>
                                        <td>
                                            @if($result['is_reachable'])
                                                <span class="text-success small"><i class="fas fa-signal mr-1"></i>Reachable</span>
                                            @else
                                                <span class="text-danger small"><i class="fas fa-ban mr-1"></i>Unreachable</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $statusClass }}">
                                                <i class="fas {{ $icon }} mr-1"></i> {{ ucfirst($result['health_status']) }}
                                            </span>
                                            @if($result['consecutive_failures'] > 0)
                                                <div class="small text-danger mt-1">{{ $result['consecutive_failures'] }} failures
                                                </div>
                                            @endif
                                        </td>
                                        <td class="small text-gray-600">
                                            {{ $result['last_success'] ?? 'Never' }}
                                        </td>
                                        <td class="text-right pr-4">
                                            <a href="{{ route('admin.webhooks.show', $result['id']) }}"
                                                class="btn btn-sm btn-light border text-primary">
                                                Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-gray-500">
                                            No active configurations to monitor.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card card-premium mb-4">
                    <div class="card-body">
                        <h6 class="font-weight-bold text-gray-800 mb-3">Diagnostic Guide</h6>

                        <div class="mb-3 p-3 rounded bg-warning-light border border-warning">
                            <div class="text-warning font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>
                                Warning State</div>
                            <p class="small text-dark mb-0">Endpoint is returning errors (4xx/5xx) intermittently. Check
                                your server logs.</p>
                        </div>

                        <div class="mb-3 p-3 rounded bg-danger-light border border-danger">
                            <div class="text-danger font-weight-bold mb-1"><i class="fas fa-times-circle mr-1"></i> Critical
                                State</div>
                            <p class="small text-dark mb-0">Endpoint is completely unreachable or timing out. Webhooks may
                                be auto-disabled soon.</p>
                        </div>

                        <div class="mb-0 p-3 rounded bg-success-light border border-success">
                            <div class="text-success font-weight-bold mb-1"><i class="fas fa-check-double mr-1"></i> Optimal
                            </div>
                            <p class="small text-dark mb-0">Latency is low and all deliveries are succeeding.</p>
                        </div>
                    </div>
                </div>

                <div class="card card-premium">
                    <div class="card-body text-center">
                        <i class="fas fa-life-ring fa-2x text-primary mb-2"></i>
                        <p class="small text-muted">Need help debugging?</p>
                        <a href="https://webhook.site" target="_blank" class="btn btn-sm btn-outline-primary">Use Webhook
                            Tester</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection