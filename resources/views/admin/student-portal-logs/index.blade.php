@extends('layouts.theme')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Student Portal Activity Logs</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" action="{{ route('admin.student-portal-logs.index') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Action Type</label>
                                    <select name="action" class="form-control">
                                        <option value="">All Actions</option>
                                        @foreach($actions as $action)
                                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                                {{ ucwords(str_replace('_', ' ', $action)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Date From</label>
                                    <input type="date" name="date_from" class="form-control"
                                        value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-2">
                                    <label>Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-2">
                                    <label>Suspicious Only</label>
                                    <div class="custom-control custom-checkbox mt-2">
                                        <input type="checkbox" name="suspicious" value="1" class="custom-control-input"
                                            id="suspiciousFilter" {{ request('suspicious') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="suspiciousFilter">Show Only</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('admin.student-portal-logs.index') }}"
                                            class="btn btn-secondary">Reset</a>
                                        <a href="{{ route('admin.student-portal-logs.export', request()->query()) }}"
                                            class="btn btn-success">
                                            <i class="fa fa-download"></i> Export CSV
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Logs Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student</th>
                                        <th>Action</th>
                                        <th>IP Address</th>
                                        <th>Location</th>
                                        <th>Mobile</th>
                                        <th>Timestamp</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logs as $log)
                                    <tr class="{{ $log->is_suspicious ? 'table-danger' : '' }}">
                                        <td>
                                            {{ $log->id }}
                                            @if($log->is_suspicious)
                                                <i class="fa fa-exclamation-triangle text-danger" title="{{ $log->flagged_reason }}"></i>
                                            @endif
                                        </td>
                                            <td>
                                                @if($log->student)
                                                    <strong>{{ $log->student->name }}</strong><br>
                                                    <small class="text-muted">{{ $log->student->enrollment_number }}</small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $badgeClass = match ($log->action) {
                                                        'login_success' => 'badge-success',
                                                        'login_failed' => 'badge-danger',
                                                        'logout' => 'badge-warning',
                                                        'profile_update_request' => 'badge-info',
                                                        default => 'badge-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <code>{{ $log->ip_address ?? 'N/A' }}</code>
                                            </td>
                                            <td>
                                                @if($log->location_data)
                                                    <i class="fa fa-map-marker"></i>
                                                    {{ $log->location_data['city'] ?? '' }},
                                                    {{ $log->location_data['country'] ?? '' }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ $log->mobile_number ?? 'N/A' }}</td>
                                            <td>
                                                <small>{{ $log->created_at->format('d M Y, h:i A') }}</small>
                                            </td>
                                            <td>
                                                @if($log->metadata && count($log->metadata) > 0)
                                                    <button class="btn btn-sm btn-info" data-toggle="modal"
                                                        data-target="#metadataModal{{ $log->id }}">
                                                        <i class="fa fa-info-circle"></i> View
                                                    </button>

                                                    <!-- Metadata Modal -->
                                                    <div class="modal fade" id="metadataModal{{ $log->id }}" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Activity Details</h5>
                                                                    <button type="button" class="close" data-dismiss="modal">
                                                                        <span>&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No activity logs found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $logs->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection