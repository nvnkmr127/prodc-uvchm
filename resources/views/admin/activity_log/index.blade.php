@extends('layouts.theme')
@section('title', 'Activity Log')

@push('styles')
<style>
    .filter-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        color: white;
    }

    .filter-card .form-control,
    .filter-card .form-select {
        border-radius: 8px;
        border: 2px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.9);
    }

    .stats-card {
        border-radius: 10px;
        border-left: 4px solid #4e73df;
        transition: transform 0.2s;
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .activity-item {
        border-left: 3px solid #e3e6f0;
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: 5px;
        background: white;
        transition: all 0.2s;
    }

    .activity-item:hover {
        border-left-color: #4e73df;
        background: #f8f9fc;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
    }

    .badge-created { background: #1cc88a; }
    .badge-updated { background: #4e73df; }
    .badge-deleted { background: #e74a3b; }
    .badge-default { background: #858796; }

    .properties-toggle {
        cursor: pointer;
        color: #4e73df;
        font-size: 0.85rem;
        text-decoration: none;
    }
    
    .properties-toggle:hover {
        text-decoration: underline;
    }

    .properties-content {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 5px;
        padding: 1rem;
        margin-top: 0.75rem;
        font-size: 0.85rem;
    }

    /* New Styles for Diff Table */
    .diff-table {
        width: 100%;
        margin-bottom: 0;
    }
    .diff-table th {
        background-color: #f8f9fc;
        font-weight: 600;
        color: #858796;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
    .diff-table td {
        vertical-align: middle;
    }
    .old-value {
        color: #e74a3b;
        text-decoration: line-through;
        margin-right: 0.5rem;
        background-color: rgba(231, 74, 59, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
    }
    .new-value {
        color: #1cc88a;
        font-weight: 600;
        background-color: rgba(28, 200, 138, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
    }
    .field-name {
        font-weight: 600;
        color: #5a5c69;
        text-transform: capitalize;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-history"></i> Activity Log
    </h1>
    <div>
        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#cleanupModal">
            <i class="fas fa-trash"></i> Cleanup Old Logs
        </button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="activity-icon badge-created me-3">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Activities</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activities->total() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="activity-icon badge-updated me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Today</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ \Spatie\Activitylog\Models\Activity::whereDate('created_at', today())->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="activity-icon badge-default me-3">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-uppercase mb-1">This Week</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ \Spatie\Activitylog\Models\Activity::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="filter-card">
    <form method="GET" action="{{ route('admin.activity-log.index') }}" id="filterForm">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label font-weight-bold">
                    <i class="fas fa-search"></i> Search
                </label>
                <input type="text" name="search" class="form-control" placeholder="Search description..." value="{{ request('search') }}">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label font-weight-bold">
                    <i class="fas fa-user"></i> User
                </label>
                <select name="causer_id" class="form-control">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label font-weight-bold">
                    <i class="fas fa-tag"></i> Type
                </label>
                <select name="log_name" class="form-control">
                    <option value="">All Types</option>
                    @foreach($logNames as $logName)
                        <option value="{{ $logName }}" {{ request('log_name') == $logName ? 'selected' : '' }}>
                            {{ ucfirst($logName) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label font-weight-bold">
                    <i class="fas fa-calendar-alt"></i> From
                </label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label font-weight-bold">
                    <i class="fas fa-calendar-alt"></i> To
                </label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            
            <div class="col-md-2 mb-3">
    <label class="form-label font-weight-bold">
        <i class="fas fa-robot"></i> System Logs
    </label>
    <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" name="show_system" value="1" id="showSystem" {{ request('show_system') ? 'checked' : '' }}>
        <label class="form-check-label text-white" for="showSystem">
            Show System
        </label>
    </div>
</div>

            <div class="col-md-1 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-light w-100">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>

        @if(request()->hasAny(['search', 'causer_id', 'log_name', 'from_date', 'to_date']))
            <div class="text-end">
                <a href="{{ route('admin.activity-log.index') }}" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
        @endif
    </form>
</div>

<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list"></i> Recent Activities
            <span class="badge badge-primary">{{ $activities->total() }}</span>
        </h6>
    </div>
    <div class="card-body">
        @forelse ($activities as $activity)
            <div class="activity-item">
                <div class="d-flex align-items-start">
                    <div class="activity-icon {{
                        str_contains(strtolower($activity->description), 'created') ? 'badge-created' :
                        (str_contains(strtolower($activity->description), 'updated') ? 'badge-updated' :
                        (str_contains(strtolower($activity->description), 'deleted') ? 'badge-deleted' : 'badge-default'))
                    }} me-3">
                        <i class="fas fa-{{
                            str_contains(strtolower($activity->description), 'created') ? 'plus' :
                            (str_contains(strtolower($activity->description), 'updated') ? 'edit' :
                            (str_contains(strtolower($activity->description), 'deleted') ? 'trash' : 'circle'))
                        }}"></i>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1 font-weight-bold">
                                    @if($activity->subject)
                                        @if(class_basename($activity->subject_type) === 'Student')
                                            <a href="{{ route('admin.students.show', $activity->subject_id) }}" class="text-decoration-none">
                                                {{ $activity->description }}
                                            </a>
                                        @elseif(class_basename($activity->subject_type) === 'Invoice')
                                            <a href="{{ route('admin.invoices.show', $activity->subject_id) }}" class="text-decoration-none">
                                                {{ $activity->description }}
                                            </a>
                                        @else
                                            {{ $activity->description }}
                                        @endif
                                    @else
                                        {{ $activity->description }}
                                    @endif
                                </h6>
                                
                                <div class="text-muted small">
                                    <span class="me-2">
                                        <i class="fas fa-user me-1"></i>
                                        <strong>{{ $activity->causer->name ?? 'System' }}</strong>
                                    </span>

                                    @if($activity->subject_type)
                                        <span class="mx-1">•</span>
                                        <span class="me-2">
                                            <i class="fas fa-cube me-1"></i>
                                            <span class="badge badge-secondary">{{ class_basename($activity->subject_type) }}</span>
                                            @if($activity->subject_id)
                                                <small class="text-gray-500">#{{ $activity->subject_id }}</small>
                                            @endif
                                        </span>
                                    @endif

                                    @if($activity->log_name)
                                        <span class="mx-1">•</span>
                                        <span class="badge badge-info">{{ $activity->log_name }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="text-end">
                                <div class="text-muted small">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                                <div class="text-muted text-xs">
                                    {{ $activity->created_at->format('d M, Y h:i A') }}
                                </div>
                            </div>
                        </div>

                        @if($activity->properties && $activity->properties->isNotEmpty())
                            @php
                                $attributes = $activity->properties['attributes'] ?? $activity->properties;
                                $old = $activity->properties['old'] ?? [];
                                $hasChanges = !empty($old);
                            @endphp

                            <div class="mt-2">
                                <a class="properties-toggle" onclick="toggleProperties({{ $activity->id }})">
                                    @if($hasChanges)
                                        <i class="fas fa-exchange-alt me-1"></i> View Changes
                                    @else
                                        <i class="fas fa-info-circle me-1"></i> View Details
                                    @endif
                                </a>
                                
                                <div id="properties-{{ $activity->id }}" class="properties-content" style="display: none;">
                                    @if($hasChanges)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless diff-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 30%">Field</th>
                                                        <th style="width: 35%">Old Value</th>
                                                        <th style="width: 35%">New Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($attributes as $key => $value)
                                                        @if(isset($old[$key]) && $old[$key] != $value)
                                                            <tr>
                                                                <td class="field-name">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                                                                <td><span class="old-value">{{ is_array($old[$key]) ? json_encode($old[$key]) : $old[$key] }}</span></td>
                                                                <td><span class="new-value">{{ is_array($value) ? json_encode($value) : $value }}</span></td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="row">
                                            @foreach($attributes as $key => $value)
                                                @if(!in_array($key, ['password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret']))
                                                <div class="col-md-6 mb-2">
                                                    <span class="text-gray-600 font-weight-bold">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                                    <span class="text-dark ms-1">
                                                        {{ is_array($value) ? json_encode($value) : $value }}
                                                    </span>
                                                </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    <div class="mt-2 text-end">
                                        <button class="btn btn-link btn-sm text-muted p-0" type="button" data-bs-toggle="collapse" data-bs-target="#raw-{{ $activity->id }}">
                                            <small>Show Raw Data</small>
                                        </button>
                                        <div class="collapse mt-2 text-start" id="raw-{{ $activity->id }}">
                                            <pre class="bg-light p-2 rounded small mb-0">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-inbox fa-4x text-gray-300"></i>
                </div>
                <h5 class="text-gray-600">No activity logs found</h5>
                <p class="text-gray-500">Try adjusting your filters or search criteria</p>
            </div>
        @endforelse

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="cleanupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ url('admin/activity-log/cleanup') }}">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash-alt"></i> Cleanup Old Logs
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Delete activity logs older than:</p>
                    <select name="days" class="form-control">
                        <option value="30">30 days</option>
                        <option value="60">60 days</option>
                        <option value="90" selected>90 days</option>
                        <option value="180">180 days</option>
                        <option value="365">1 year</option>
                    </select>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        This action cannot be undone!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Old Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleProperties(id) {
    const element = document.getElementById('properties-' + id);
    if (element.style.display === 'none') {
        element.style.display = 'block';
    } else {
        element.style.display = 'none';
    }
}
</script>
@endpush