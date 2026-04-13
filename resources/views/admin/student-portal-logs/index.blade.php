@push('styles')
    <style>
        .filter-panel {
            background: #f8f9fc; border-radius: 1rem; border: 1px solid #e3e6f0;
            padding: 1.5rem; margin-bottom: 2rem;
        }
        .log-table thead th {
            background: #f8f9fc; text-transform: uppercase; font-size: 0.7rem;
            letter-spacing: 0.5px; font-weight: 800; border-bottom: 2px solid #e3e6f0;
        }
        .log-row { transition: all 0.2s; }
        .log-row:hover { background-color: rgba(78, 115, 223, 0.02); }
        .badge-soc {
            font-weight: 800; text-transform: uppercase; font-size: 0.65rem;
            padding: 0.4rem 0.75rem; border-radius: 0.5rem;
        }
        .text-xs-bold { font-size: 0.75rem; font-weight: 800; }
        .suspicious-row { background-color: #fff5f5 !important; }
        .modal-content { border-radius: 1.25rem; border: none; }
        .modal-header { border-bottom: 1px solid #f1f3f9; padding: 1.5rem; }
        pre.metadata-block {
            background: #2d3436; color: #fab1a0; padding: 1rem;
            border-radius: 0.75rem; font-size: 0.85rem; overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('admin.student-portal-logs.dashboard') }}">SOC Dashboard</a></li>
                        <li class="breadcrumb-item active">Operational Logs</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Intelligence Database</h1>
            </div>
            <div>
                <a href="{{ route('admin.student-portal-logs.export', request()->query()) }}" class="btn btn-success shadow-sm font-weight-bold px-4 rounded-pill">
                    <i class="fas fa-file-export mr-2"></i> Export Telemetry (.CSV)
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow border-0" style="border-radius: 1.5rem; overflow: hidden;">
                    <div class="card-body p-4">
                        <!-- Advanced Filtering -->
                        <div class="filter-panel shadow-sm">
                            <form method="GET" action="{{ route('admin.student-portal-logs.index') }}">
                                <div class="row align-items-end">
                                    <div class="col-md-3">
                                        <label class="text-xs-bold text-gray-600 text-uppercase mb-2 d-block">System Operation</label>
                                        <select name="action" class="form-control form-control-sm border-0 bg-white shadow-none" style="border-radius: 0.5rem;">
                                            <option value="">All Operations</option>
                                            @foreach($actions as $action)
                                                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                                    {{ ucwords(str_replace('_', ' ', $action)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="text-xs-bold text-gray-600 text-uppercase mb-2 d-block">Period Start</label>
                                        <input type="date" name="date_from" class="form-control form-control-sm border-0 bg-white shadow-none" value="{{ request('date_from') }}" style="border-radius: 0.5rem;">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="text-xs-bold text-gray-600 text-uppercase mb-2 d-block">Period End</label>
                                        <input type="date" name="date_to" class="form-control form-control-sm border-0 bg-white shadow-none" value="{{ request('date_to') }}" style="border-radius: 0.5rem;">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="text-xs-bold text-gray-600 text-uppercase mb-2 d-block">Security Status</label>
                                        <div class="custom-control custom-switch pb-2">
                                            <input type="checkbox" name="suspicious" value="1" class="custom-control-input" id="suspiciousFilter" {{ request('suspicious') == '1' ? 'checked' : '' }}>
                                            <label class="custom-control-label text-xs-bold text-danger" for="suspiciousFilter">ANOMALIES ONLY</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 font-weight-bold shadow-sm rounded-pill mr-2">RUN QUERY</button>
                                        <a href="{{ route('admin.student-portal-logs.index') }}" class="btn btn-light btn-sm px-3 font-weight-bold border rounded-pill">RESET</a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Telemetry Grid -->
                        <div class="table-responsive">
                            <table class="table log-table table-borderless">
                                <thead>
                                    <tr>
                                        <th class="px-3">Protocol ID</th>
                                        <th>Student Subject</th>
                                        <th>Operational Event</th>
                                        <th>Network Telemetry</th>
                                        <th>Geolocation</th>
                                        <th>Timestamp</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logs as $log)
                                    <tr class="log-row {{ $log->is_suspicious ? 'suspicious-row' : '' }}">
                                        <td class="px-3 py-4 align-middle">
                                            <span class="text-xs-bold text-muted">#{{ $log->id }}</span>
                                            @if($log->is_suspicious)
                                                <i class="fas fa-exclamation-triangle text-danger ml-2" title="{{ $log->flagged_reason }}"></i>
                                            @endif
                                        </td>
                                        <td class="py-4 align-middle">
                                            @if($log->student)
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-gray-200 d-flex align-items-center justify-content-center mr-2 mb-0" style="width: 30px; height: 30px; font-weight: 800; font-size: 0.7rem;">
                                                        {{ substr($log->student->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-weight-bold text-gray-900 small">{{ $log->student->name }}</div>
                                                        <div class="text-xs text-muted">{{ $log->student->enrollment_number }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="badge badge-secondary">UNREGISTERED</span>
                                            @endif
                                        </td>
                                        <td class="py-4 align-middle">
                                            @php
                                                $badgeClass = match ($log->action) {
                                                    'login_success' => 'bg-success text-white',
                                                    'login_failed' => 'bg-danger text-white',
                                                    'logout' => 'bg-warning text-dark',
                                                    'profile_update_request' => 'bg-info text-white',
                                                    default => 'bg-dark text-white'
                                                };
                                            @endphp
                                            <span class="badge badge-soc {{ $badgeClass }}">
                                                {{ str_replace('_', ' ', $log->action) }}
                                            </span>
                                        </td>
                                        <td class="py-4 align-middle">
                                            <code class="text-primary font-weight-bold" style="font-size: 0.75rem;">{{ $log->ip_address ?? '0.0.0.0' }}</code>
                                            <div class="text-xs text-muted mt-1">{{ $log->mobile_number ?? 'NO MOBILE' }}</div>
                                        </td>
                                        <td class="py-4 align-middle">
                                            @if($log->location_data)
                                                <div class="small font-weight-bold text-gray-700">
                                                    <i class="fas fa-map-pin mr-1 text-primary"></i>
                                                    {{ $log->location_data['city'] ?? 'Unknown' }}
                                                </div>
                                                <div class="text-xs text-muted">{{ $log->location_data['country'] ?? 'N/A' }}</div>
                                            @else
                                                <span class="text-xs text-muted opacity-50">SHIELDED</span>
                                            @endif
                                        </td>
                                        <td class="py-4 align-middle">
                                            <div class="font-weight-bold text-gray-800 small">{{ $log->created_at->format('M d, H:i') }}</div>
                                            <div class="text-xs text-muted">{{ $log->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="py-4 align-middle text-right">
                                            @if($log->metadata && count($log->metadata) > 0)
                                                <button class="btn btn-sm btn-white border shadow-sm rounded-circle" data-toggle="modal" data-target="#metadataModal{{ $log->id }}" style="width: 32px; height: 32px; padding: 0;">
                                                    <i class="fas fa-microchip text-primary fa-xs"></i>
                                                </button>

                                                <!-- Metadata Modal -->
                                                <div class="modal fade" id="metadataModal{{ $log->id }}" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content shadow-lg">
                                                            <div class="modal-header bg-light">
                                                                <h6 class="modal-title font-weight-bold text-primary text-uppercase small tracking-wider">Payload Analytics</h6>
                                                                <button type="button" class="close" data-dismiss="modal">
                                                                    <span>&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body p-4 bg-white">
                                                                <div class="text-xs text-uppercase font-weight-extrabold text-muted mb-3">Event RAW Metadata</div>
                                                                <pre class="metadata-block shadow-inner">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                                                            </div>
                                                            <div class="modal-footer bg-light border-0">
                                                                <button type="button" class="btn btn-secondary btn-sm rounded-pill font-weight-bold px-4" data-dismiss="modal">DISMISS</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <i class="fas fa-lock text-muted opacity-25"></i>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="opacity-25 mb-3">
                                                    <i class="fas fa-database fa-3x"></i>
                                                </div>
                                                <h6 class="font-weight-bold text-muted">DATABASE EMPTY</h6>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <div class="small text-muted font-weight-bold">SHOWING {{ $logs->firstItem() }}-{{ $logs->lastItem() }} OF {{ $logs->total() }} LOG ENTRIES</div>
                            {{ $logs->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection