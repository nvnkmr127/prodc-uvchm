@extends('layouts.theme')
@section('title', 'Webhook Details')

@push('styles')
    <style>
        .card-premium {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.8em;
            border-radius: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .detail-label {
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            color: #111827;
            font-weight: 500;
            font-size: 1rem;
        }

        .code-box {
            font-family: 'Monaco', monospace;
            background: #f3f4f6;
            padding: 0.5rem;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #374151;
            word-break: break-all;
        }

        .logs-table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: #6b7280;
            border-top: none;
        }
    </style>
@endpush

@section('content')
    @php
        // Ensure eventInfo values are strings to prevent htmlspecialchars errors
        $eventInfo['name'] = is_string($eventInfo['name'] ?? null) ? $eventInfo['name'] : 'Unknown Event';
        $eventInfo['description'] = is_string($eventInfo['description'] ?? null) ? $eventInfo['description'] : '';
        $eventInfo['category'] = is_string($eventInfo['category'] ?? null) ? $eventInfo['category'] : ($eventInfo['category']['name'] ?? 'General');
        $eventInfo['icon'] = is_string($eventInfo['icon'] ?? null) ? $eventInfo['icon'] : 'fas fa-bolt';
    @endphp

    <div class="container-fluid">

        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('admin.webhooks.index') }}">Webhooks</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Details</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">{{ $eventInfo['name'] }}</h1>
                <p class="mb-0 text-muted">{{ $webhook->url }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.webhooks.edit', $webhook) }}" class="btn btn-primary shadow-sm">
                    <i class="fas fa-edit mr-2"></i>Edit Configuration
                </a>
                <form action="{{ route('admin.webhooks.destroy', $webhook) }}" method="POST"
                    onsubmit="return confirm('Delete this webhook?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger shadow-sm ml-2">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success shadow-sm rounded-lg mb-4 border-0 border-left-success">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif

        <div class="row">
            <!-- Main Details -->
            <div class="col-lg-8">
                <div class="card card-premium mb-4">
                    <div class="card-body p-4">
                        <h6 class="font-weight-bold text-gray-800 mb-4 pb-2 border-bottom">Configuration</h6>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Status</div>
                                    <div>
                                        @if($webhook->is_active)
                                            <span class="status-badge status-success"><i class="fas fa-check mr-1"></i>
                                                Active</span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-pause mr-1"></i>
                                                Inactive</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Event Type</div>
                                    <div class="detail-value">
                                        <i class="{{ $eventInfo['icon'] ?? 'fas fa-bolt' }} text-primary mr-2"></i>
                                        {{ $eventInfo['name'] }}
                                    </div>
                                    <small class="text-muted">{{ $eventInfo['category'] }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label">Signing Secret</div>
                                    <div class="d-flex align-items-center">
                                        <div class="code-box text-muted mr-2">
                                            {{ substr($webhook->signing_secret, 0, 12) }}••••••••
                                        </div>
                                        <button class="btn btn-sm btn-link pl-0" onclick="copySecret()"
                                            title="Copy Full Secret">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label">Retry Policy</div>
                                    <div class="detail-value">{{ $webhook->retry_attempts ?? 3 }} Attempts</div>
                                </div>
                            </div>
                        </div>

                        @if($webhook->description)
                            <div class="mb-0">
                                <div class="detail-label">Description</div>
                                <p class="text-gray-700 bg-light p-3 rounded mb-0">{{ $webhook->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Logs -->
                <div class="card card-premium shadow-sm">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-gray-800">Recent Deliveries</h6>
                        <a href="{{ route('admin.webhooks.logs', $webhook) }}" class="small font-weight-bold">View History
                            &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table logs-table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="pl-4">Outcome</th>
                                    <th>Time</th>
                                    <th>Duration</th>
                                    <th>Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($webhook->calls->take(5) as $call)
                                    <tr>
                                        <td class="pl-4">
                                            @if($call->success)
                                                <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>
                                                    Success</span>
                                            @else
                                                <span class="text-danger font-weight-bold"><i class="fas fa-times-circle mr-1"></i>
                                                    Failed</span>
                                            @endif
                                        </td>
                                        <td class="text-gray-600">{{ $call->created_at->diffForHumans() }}</td>
                                        <td class="text-gray-600">{{ $call->execution_time_ms }}ms</td>
                                        <td>
                                            <span class="badge badge-light border">{{ $call->status_code ?? '---' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No recent activity.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 30-Day Activity -->
                <div class="card card-premium shadow-sm mt-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-gray-800">30-Day Activity</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table logs-table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="pl-4">Date</th>
                                    <th>Total Calls</th>
                                    <th>Successful</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($callStats as $stat)
                                    @php
                                        $rate = $stat->total_calls > 0 ? round(($stat->successful_calls / $stat->total_calls) * 100, 1) : 0;
                                        $rateClass = $rate >= 95 ? 'text-success' : ($rate >= 80 ? 'text-warning' : 'text-danger');
                                    @endphp
                                    <tr>
                                        <td class="pl-4 text-gray-700 font-weight-500">
                                            {{ \Carbon\Carbon::parse($stat->date)->format('M d, Y') }}
                                        </td>
                                        <td class="text-gray-600">{{ number_format($stat->total_calls) }}</td>
                                        <td class="text-success">{{ number_format($stat->successful_calls) }}</td>
                                        <td>
                                            <span class="font-weight-bold {{ $rateClass }}">{{ $rate }}%</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No activity in the last 30 days.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar Stats -->
            <div class="col-lg-4">
                <!-- Overall Health -->
                <div
                    class="card card-premium mb-4 border-left-{{ $healthStatus['status'] === 'healthy' ? 'success' : 'danger' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div
                                class="btn btn-circle btn-lg btn-light text-{{ $healthStatus['status'] === 'healthy' ? 'success' : 'danger' }} mr-3">
                                <i
                                    class="{{ $healthStatus['status'] === 'healthy' ? 'fas fa-heartbeat' : 'fas fa-exclamation-triangle' }}"></i>
                            </div>
                            <div>
                                <div class="small text-uppercase text-gray-500 font-weight-bold">Health Score</div>
                                <div class="h4 mb-0 font-weight-bold">{{ $healthStatus['success_rate'] }}%</div>
                            </div>
                        </div>

                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-{{ $healthStatus['status'] === 'healthy' ? 'success' : 'danger' }}"
                                role="progressbar" style="width: {{ $healthStatus['success_rate'] }}%"></div>
                        </div>

                        <div class="row text-center mt-4">
                            <div class="col-6 border-right">
                                <div class="h5 font-weight-bold text-success mb-0">{{ $healthStatus['successful_calls'] }}
                                </div>
                                <small class="text-muted text-uppercase" style="font-size:0.7rem;">Verified</small>
                            </div>
                            <div class="col-6">
                                <div class="h5 font-weight-bold text-danger mb-0">{{ $healthStatus['failed_calls'] }}</div>
                                <small class="text-muted text-uppercase" style="font-size:0.7rem;">Failures</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card card-premium">
                    <div class="card-body">
                        <h6 class="font-weight-bold text-gray-800 mb-3">Utility</h6>
                        <form action="{{ route('admin.webhooks.test', $webhook) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-light btn-block border text-primary font-weight-bold">
                                <i class="fas fa-paper-plane mr-2"></i> Send Test Event
                            </button>
                        </form>
                        <a href="{{ route('admin.webhooks.logs', $webhook) }}" class="btn btn-light btn-block border">
                            <i class="fas fa-history mr-2"></i> Audit Logs
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function copySecret() {
            navigator.clipboard.writeText("{{ $webhook->signing_secret }}");
            alert('Secret copied to clipboard!');
        }
    </script>
@endsection