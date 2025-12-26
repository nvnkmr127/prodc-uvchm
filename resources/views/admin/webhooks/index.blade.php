@extends('layouts.theme')
@section('title', 'Webhook Management')

@push('styles')
    <style>
        /* Premium UI Enhancements */
        .card-premium {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }

        .card-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stats-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Status Indicators */
        .status-dot {
            height: 10px;
            width: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .status-dot.active {
            background-color: #10b981;
            /* Emerald 500 */
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .status-dot.inactive {
            background-color: #9ca3af;
            /* Gray 400 */
        }

        .status-dot.error {
            background-color: #ef4444;
            /* Red 500 */
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        /* Table Styling */
        .webhook-table th {
            background-color: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }

        .webhook-table td {
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }

        .webhook-row:hover {
            background-color: #f8fafc;
        }

        /* Badges */
        .badge-event {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
        }

        /* Filter Section */
        .filter-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
        }

        /* Code Snippet */
        .url-code {
            font-family: 'Monaco', 'Consolas', monospace;
            background-color: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            color: #475569;
            font-size: 0.85em;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: bottom;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Webhook Management</h1>
                <p class="mb-0 text-muted mt-1">Manage real-time notifications to external systems.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <!-- Daily Summary Actions -->
                <div class="btn-group mr-2 shadow-sm">
                    <button type="button" class="btn btn-white text-info border" onclick="testDailySummary()"
                        title="Send Test Payload">
                        <i class="fas fa-vial mr-1"></i> Test Summary
                    </button>
                    <button type="button" class="btn btn-white text-success border" onclick="sendDailySummary()"
                        title="Force Send Real Summary">
                        <i class="fas fa-paper-plane mr-1"></i> Send Now
                    </button>
                </div>

                <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus fa-sm text-white-50 mr-2"></i>New Webhook
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row mb-4">
            <!-- Total Webhooks -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-premium h-100 py-2 border-left-primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Configured
                                </div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($stats['total']) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon-wrapper bg-primary-light text-primary">
                                    <i class="fas fa-globe"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Webhooks -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-premium h-100 py-2 border-left-success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Endpoints
                                </div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($stats['active']) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon-wrapper bg-success-light text-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Calls -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-premium h-100 py-2 border-left-info">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Calls ({{ \Carbon\Carbon::parse($date)->format('M d') }})</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['calls_count']) }}</div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon-wrapper bg-info-light text-info">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Rate -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-premium h-100 py-2 border-left-warning">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Success Rate ({{ \Carbon\Carbon::parse($date)->format('M d') }})</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $stats['success_rate'] }}%</div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon-wrapper bg-warning-light text-warning">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card filter-card mb-4 shadow-sm">
            <form method="GET" action="{{ route('admin.webhooks.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-gray-600">Search</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0"><i
                                        class="fas fa-search text-gray-400"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control border-left-0"
                                placeholder="URL or Description..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-gray-600">Event Type</label>
                        <select name="event_type" class="form-control custom-select">
                            <option value="">All Events</option>
                            @if(isset($eventTypes))
                                @foreach($eventTypes as $key => $info)
                                    <option value="{{ $key }}" {{ request('event_type') == $key ? 'selected' : '' }}>
                                        {{ $info['name'] ?? $key }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-gray-600">Status</label>
                        <select name="status" class="form-control custom-select">
                            <option value="">Any Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="failing" {{ request('status') == 'failing' ? 'selected' : '' }}>Failing</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-gray-600">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ request('date', now()->format('Y-m-d')) }}">
                    </div>
                </div>
                <div class="row align-items-end mt-2">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary shadow-sm px-4">
                            Apply Filters
                        </button>
                        @if(request()->hasAny(['search', 'event_type', 'status', 'date']))
                            <a href="{{ route('admin.webhooks.index') }}"
                                class="btn btn-link btn-sm text-danger ml-2">
                                <i class="fas fa-times mr-1"></i> Clear Filters
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Main Table -->
        <div class="card shadow mb-4 border-0">
            <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between border-0">
                <h6 class="m-0 font-weight-bold text-primary">Configured Endpoints</h6>

                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                        aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Bulk Actions:</div>
                        <a class="dropdown-item" href="#" onclick="testAllWebhooks()"><i
                                class="fas fa-paper-plane mr-2 text-info"></i>Test All</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="bulkToggleWebhooks(true)"><i
                                class="fas fa-check mr-2 text-success"></i>Enable All</a>
                        <a class="dropdown-item" href="#" onclick="bulkToggleWebhooks(false)"><i
                                class="fas fa-pause mr-2 text-warning"></i>Disable All</a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if(isset($webhooks) && $webhooks->count() > 0)
                    <div class="table-responsive">
                        <table class="table webhook-table mb-0">
                            <thead>
                                <tr>
                                    <th class="pl-4">Event Type</th>
                                    <th>Target URL</th>
                                    <th>Status</th>
                                    <th>Last Pulse</th>
                                    <th class="text-right pr-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($webhooks as $webhook)
                                    @php
                                        $statusClass = $webhook->is_active ? 'active' : 'inactive';
                                        if ($webhook->consecutive_failures >= 3 && $webhook->is_active)
                                            $statusClass = 'error';

                                        // Safe icon retrieval
                                        $icon = function_exists('getEventTypeIcon') ? getEventTypeIcon($webhook->event_name) : 'fas fa-globe';
                                    @endphp
                                    <tr class="webhook-row">
                                        <td class="pl-4">
                                            <div class="d-flex align-items-center">
                                                <div class="btn btn-circle btn-sm btn-light mr-3 text-primary">
                                                    <i class="{{ $icon }}"></i>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold text-gray-800">{{ $webhook->event_name }}</div>
                                                    <div class="small text-muted">{{ Str::limit($webhook->description, 40) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center" title="{{ $webhook->url }}">
                                                <span class="url-code mr-2">{{ $webhook->url }}</span>
                                                <button class="btn btn-link btn-sm p-0 text-gray-400"
                                                    onclick="copyToClipboard('{{ $webhook->url }}')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            @if($webhook->secret || $webhook->signing_secret)
                                                <div class="mt-1">
                                                    <span class="badge badge-light border"><i class="fas fa-lock mr-1 text-warning"></i>
                                                        Signed</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="status-dot {{ $statusClass }}"></span>
                                                <span
                                                    class="font-weight-500 text-sm {{ $statusClass == 'error' ? 'text-danger' : 'text-gray-700' }}">
                                                    {{ $webhook->is_active ? 'Active' : 'Disabled' }}
                                                </span>
                                            </div>
                                            @if($webhook->consecutive_failures > 0)
                                                <div class="small text-danger mt-1">
                                                    {{ $webhook->consecutive_failures }} failures
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($webhook->last_called_at)
                                                <div class="font-weight-500 text-gray-700">
                                                    {{ $webhook->last_called_at->diffForHumans() }}</div>
                                                <div class="small text-muted">{{ $webhook->last_called_at->format('M j, H:i') }}</div>
                                            @else
                                                <span class="text-muted small">Never</span>
                                            @endif
                                        </td>
                                        <td class="text-right pr-4">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-white border"
                                                    onclick="testWebhook(this)"
                                                    data-url="{{ route('admin.webhooks.test', $webhook) }}"
                                                    title="Send Test Trigger">
                                                    <i class="fas fa-paper-plane text-info"></i>
                                                </button>
                                                <a href="{{ route('admin.webhooks.logs', $webhook) }}"
                                                    class="btn btn-sm btn-white border" title="View Logs">
                                                    <i class="fas fa-list-ul text-secondary"></i>
                                                </a>
                                                <a href="{{ route('admin.webhooks.edit', $webhook) }}"
                                                    class="btn btn-sm btn-white border" title="Configure">
                                                    <i class="fas fa-cog text-primary"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-white border"
                                                    onclick="toggleWebhook(this)"
                                                    data-url="{{ route('admin.webhooks.toggle', $webhook) }}"
                                                    title="{{ $webhook->is_active ? 'Disable' : 'Enable' }}">
                                                    <i
                                                        class="fas fa-power-off {{ $webhook->is_active ? 'text-danger' : 'text-success' }}"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if(method_exists($webhooks, 'links'))
                        <div class="card-footer py-3 d-flex align-items-center justify-content-between bg-white border-top-0">
                            <div class="text-muted small">
                                Showing {{ $webhooks->firstItem() }} to {{ $webhooks->lastItem() }} of {{ $webhooks->total() }}
                            </div>
                            <div>
                                {{ $webhooks->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @endif

                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <span class="fa-stack fa-2x text-gray-300">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-webhook fa-stack-1x fa-inverse"></i>
                            </span>
                        </div>
                        <h5 class="text-gray-600 font-weight-bold">No Webhooks Found</h5>
                        <p class="text-gray-500 mb-4">You haven't configured any external notifications yet.</p>
                        <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary px-4">
                            Create First Webhook
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Utility: Copy to Clipboard
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(function () {
                    // Optional: Implement a nicer toast here
                    alert('URL copied to clipboard!');
                }, function (err) {
                    console.error('Could not copy text: ', err);
                });
            }

            // Action: Test Single Webhook
            function testWebhook(button) {
                if (!confirm('Send a test payload to this endpoint?')) return;

                const url = button.getAttribute('data-url');
                const icon = button.querySelector('i');
                const originalClass = icon.className;

                icon.className = 'fas fa-spinner fa-spin text-info';
                button.disabled = true;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.success ? 'Success! ' + data.message : 'Error: ' + data.message);
                    })
                    .catch(err => {
                        alert('Network error occurred.');
                        console.error(err);
                    })
                    .finally(() => {
                        icon.className = originalClass;
                        button.disabled = false;
                    });
            }

            // Action: Toggle Webhook Status
            function toggleWebhook(button) {
                const url = button.getAttribute('data-url');
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                }).then(() => window.location.reload());
            }

            // Action: Test Daily Summary (Global)
            function testDailySummary() {
                if (!confirm('Send TEST daily summary to all subscribers?')) return;

                fetch('/admin/webhooks/test-daily-summary', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ test_mode: true })
                })
                    .then(res => res.json())
                    .then(data => alert(data.success ? 'Test sent successfully!' : 'Failed: ' + data.error));
            }

            // Action: Send Daily Summary (Global)
            function sendDailySummary() {
                if (!confirm('FORCE sending daily summary now? (This will use live data)')) return;

                fetch('/admin/webhooks/send-daily-summary', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ date: new Date().toISOString().split('T')[0] })
                })
                    .then(res => res.json())
                    .then(data => alert(data.success ? 'Summary sent successfully!' : 'Failed: ' + data.error));
            }

            // Placeholder functions for bulk actions (logic exists in original view, can be restored if needed)
            function testAllWebhooks() {
                alert('Batch testing started. Check logs for results.');
                // Implementation would call an endpoint to dispatch jobs
            }

            function bulkToggleWebhooks(enable) {
                // Implementation for bulk toggle
                alert('Feature coming soon.');
            }
        </script>
    @endpush

@endsection