@extends('layouts.theme')
@section('title', 'Activity Log')

@push('styles')
    <style>
        /* Timeline Styles */
        .timeline {
            position: relative;
            padding: 0;
            list-style: none;
        }

        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 30px;
            width: 2px;
            background: #e3e6f0;
            margin-left: -1.5px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }

        .timeline-marker {
            position: absolute;
            top: 0;
            left: 15px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            color: #fff;
            z-index: 1;
            box-shadow: 0 0 0 5px #f8f9fc;
        }

        .timeline-content {
            margin-left: 60px;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 1.25rem;
            position: relative;
            border-left: 4px solid transparent;
            transition: transform 0.2s ease-in-out;
        }

        .timeline-content:hover {
            transform: translateY(-2px);
        }

        .timeline-content:before {
            content: '';
            position: absolute;
            right: 100%;
            top: 15px;
            border: 7px solid transparent;
            border-right: 7px solid #fff;
        }

        /* Status Colors */
        .bg-created {
            background-color: #1cc88a;
            border-color: #1cc88a;
        }

        .bg-updated {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .bg-deleted {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }

        .bg-default {
            background-color: #858796;
            border-color: #858796;
        }

        .border-created {
            border-left-color: #1cc88a;
        }

        .border-updated {
            border-left-color: #4e73df;
        }

        .border-deleted {
            border-left-color: #e74a3b;
        }

        .border-default {
            border-left-color: #858796;
        }

        .text-created {
            color: #1cc88a;
        }

        .text-updated {
            color: #4e73df;
        }

        .text-deleted {
            color: #e74a3b;
        }

        /* Filter Card */
        .filter-wrapper {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-top: 4px solid #4e73df;
        }

        .search-input {
            border-radius: 2rem;
            padding-left: 1.5rem;
        }

        /* Change Details Table */
        .diff-table {
            font-size: 0.9rem;
        }

        .diff-table th {
            background-color: #f8f9fc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .old-val {
            background: #ffebeb;
            color: #e74a3b;
            padding: 2px 6px;
            border-radius: 4px;
            text-decoration: line-through;
            opacity: 0.8;
        }

        .new-val {
            background: #e0fcf4;
            color: #1cc88a;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-pill {
            padding-right: 0.6em;
            padding-left: 0.6em;
            border-radius: 10rem;
        }

        /* Stats Cards */
        .mini-stat-card {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            border: 1px solid #e3e6f0;
        }

        .stat-icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Activity Log</h1>
                <p class="mb-0 text-muted small">Track system events, user actions, and data changes.</p>
            </div>
            <div>
                <button type="button" class="btn btn-outline-danger btn-sm shadow-sm" data-toggle="modal"
                    data-target="#cleanupModal">
                    <i class="fas fa-trash mr-1"></i> Cleanup Logs
                </button>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="row mb-2">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="mini-stat-card border-left-primary">
                    <div class="stat-icon-circle bg-primary text-white">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Logs</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activities->total() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="mini-stat-card border-left-success">
                    <div class="stat-icon-circle bg-success text-white">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Today</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ \Spatie\Activitylog\Models\Activity::whereDate('created_at', today())->count() }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="mini-stat-card border-left-info">
                    <div class="stat-icon-circle bg-info text-white">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Users Active</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ \Spatie\Activitylog\Models\Activity::whereDate('created_at', today())->distinct('causer_id')->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="filter-wrapper">
            <form method="GET" action="{{ route('admin.activity-log.index') }}">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-gray-600 mb-1">Search Activity</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-0"><i
                                        class="fas fa-search text-gray-400"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control bg-light border-0 small"
                                placeholder="Search by description..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-gray-600 mb-1">User</label>
                        <select name="causer_id" class="form-control form-control-sm">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-gray-600 mb-1">Action Type</label>
                        <select name="log_name" class="form-control form-control-sm">
                            <option value="">All Actions</option>
                            @foreach($logNames as $logName)
                                <option value="{{ $logName }}" {{ request('log_name') == $logName ? 'selected' : '' }}>
                                    {{ ucfirst($logName) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-gray-600 mb-1">Date Range</label>
                        <div class="d-flex">
                            <input type="date" name="from_date" class="form-control form-control-sm mr-2"
                                value="{{ request('from_date') }}">
                            <input type="date" name="to_date" class="form-control form-control-sm"
                                value="{{ request('to_date') }}">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-12 mb-3 text-right">
                    <div class="custom-control custom-switch mb-2">
                        <input type="checkbox" class="custom-control-input" id="hideSystem" name="hide_system" value="1" {{ request('hide_system') ? 'checked' : '' }}>
                        <label class="custom-control-label small" for="hideSystem">Hide System Logs</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm btn-block shadow-sm">
                        <i class="fas fa-filter fa-sm"></i> Apply Filters
                    </button>
                    @if(request()->hasAny(['search', 'causer_id', 'log_name', 'from_date', 'to_date', 'hide_system']))
                        <a href="{{ route('admin.activity-log.index') }}" class="btn btn-link btn-sm btn-block text-danger" style="text-decoration: none;">
                            <small>Clear Filters</small>
                        </a>
                    @endif
                </div>
                </div>
            </form>
        </div>

        {{-- Timeline --}}
        @if($activities->count() > 0)
            <ul class="timeline">
                @foreach($activities as $activity)
                    @php
                        // Determine styling based on action
                        $actionType = 'default';
                        $icon = 'circle';

                        if (str_contains(strtolower($activity->description), 'created')) {
                            $actionType = 'created';
                            $icon = 'plus';
                        } elseif (str_contains(strtolower($activity->description), 'updated')) {
                            $actionType = 'updated';
                            $icon = 'pen';
                        } elseif (str_contains(strtolower($activity->description), 'deleted')) {
                            $actionType = 'deleted';
                            $icon = 'trash';
                        }
                    @endphp

                    <li class="timeline-item">
                        <div class="timeline-marker bg-{{ $actionType }}">
                            <i class="fas fa-{{ $icon }}"></i>
                        </div>

                        <div class="timeline-content border-{{ $actionType }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge badge-pill badge-{{ $actionType }} mr-2 text-capitalize">
                                            {{ $activity->log_name ?? 'Activity' }}
                                        </span>
                                        <small class="text-muted">
                                            <i class="far fa-clock mr-1"></i> {{ $activity->created_at->format('h:i A') }}
                                            <span class="mx-1">•</span>
                                            {{ $activity->created_at->diffForHumans() }}
                                        </small>
                                    </div>

                                    <h5 class="font-weight-bold text-gray-800 mb-1">
                                        @if($activity->subject)
                                            @php
                                                $baseName = class_basename($activity->subject_type);
                                                $modelName = Illuminate\Support\Str::kebab(Illuminate\Support\Str::plural($baseName));
                                                $routeName = 'admin.' . $modelName . '.show';
                                            @endphp

                                            @if(Route::has($routeName))
                                                <a href="{{ route($routeName, $activity->subject_id) }}" class="text-gray-800">
                                                    {{ $activity->description }}
                                                </a>
                                                <small class="text-primary ml-1">#{{ $activity->subject_id }}</small>
                                            @else
                                                {{ $activity->description }} <small class="text-muted">#{{ $activity->subject_id }}</small>
                                            @endif
                                        @else
                                            {{ $activity->description }}
                                        @endif
                                    </h5>

                                    <div class="mb-2">
                                        <span class="small text-gray-600">
                                            by <strong class="text-gray-800">{{ $activity->causer->name ?? 'System' }}</strong>
                                        </span>
                                        @if($activity->subject_type)
                                            <span class="small text-muted ml-2">
                                                on {{ Illuminate\Support\Str::headline(class_basename($activity->subject_type)) }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Changes / Properties --}}
                                    @if($activity->properties && $activity->properties->isNotEmpty())
                                        <button class="btn btn-sm btn-light mt-2" type="button" data-toggle="collapse"
                                            data-target="#details-{{ $activity->id }}">
                                            <i class="fas fa-code mr-1"></i> View Details
                                        </button>

                                        <div class="collapse mt-3" id="details-{{ $activity->id }}">
                                            @php
                                                $attributes = $activity->properties['attributes'] ?? $activity->properties;
                                                $old = $activity->properties['old'] ?? [];
                                                $hasChanges = !empty($old);
                                            @endphp

                                            @if($hasChanges)
                                                <div class="table-responsive rounded border">
                                                    <table class="table table-sm table-borderless diff-table mb-0 bg-white">
                                                        <thead>
                                                            <tr>
                                                                <th width="30%" class="pl-3">Field</th>
                                                                <th width="35%">Old Value</th>
                                                                <th width="35%">New Value</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($attributes as $key => $value)
                                                                @if(isset($old[$key]) && $old[$key] != $value)
                                                                    <tr>
                                                                        <td class="pl-3 text-dark">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                                                                        <td><span
                                                                                class="old-val">{{ is_array($old[$key]) ? json_encode($old[$key]) : $old[$key] }}</span>
                                                                        </td>
                                                                        <td><span
                                                                                class="new-val">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="table-responsive rounded border">
                                                    <table class="table table-sm table-striped mb-0 bg-white">
                                                        <tbody>
                                                            @foreach($attributes as $key => $value)
                                                                @if(!in_array($key, ['password', 'remember_token']))
                                                                    <tr>
                                                                        <td width="30%" class="pl-3 font-weight-bold small text-gray-600">
                                                                            {{ str_replace('_', ' ', ucfirst($key)) }}</td>
                                                                        <td class="small text-break">
                                                                            {{ is_array($value) ? json_encode($value) : $value }}</td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="d-flex justify-content-center mt-4">
                {{ $activities->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <span class="fa-stack fa-2x">
                        <i class="fas fa-circle fa-stack-2x text-gray-200"></i>
                        <i class="fas fa-search fa-stack-1x text-gray-400"></i>
                    </span>
                </div>
                <h5 class="text-gray-600 font-weight-bold">No activities found</h5>
                <p class="text-gray-500 mb-0">Try adjusting your filters to see more results.</p>
            </div>
        @endif

    </div>

    {{-- Cleanup Modal --}}
    <div class="modal fade" id="cleanupModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-0 shadow-lg">
                <form method="POST" action="{{ route('admin.activity-log.cleanup') }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-trash-alt mr-2"></i> Cleanup Logs
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-gray-800">Select the retention period for activity logs. Logs older than this will be
                            permanently deleted.</p>
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700">Delete logs older than:</label>
                            <select name="days" class="form-control custom-select shadow-sm">
                                <option value="0" class="text-danger font-weight-bold">Clear All Logs (Reset)</option>
                                <option value="30">30 days</option>
                                <option value="60">60 days</option>
                                <option value="90" selected>90 days</option>
                                <option value="180">180 days</option>
                                <option value="365">1 year</option>
                            </select>
                        </div>
                        <div class="alert alert-warning border-left-warning d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                            <small><strong>Warning:</strong> This action is irreversible. Deleted logs cannot be
                                recovered.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger shadow-sm">Confirm Deletion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection