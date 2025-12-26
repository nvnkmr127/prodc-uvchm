@extends('layouts.theme')

@section('content')
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats->getData()->total_logins_today ?? 0 }}</h3>
                        <p>Logins Today</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-sign-in"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats->getData()->active_students ?? 0 }}</h3>
                        <p>Active Students (24h)</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats->getData()->failed_logins_today ?? 0 }}</h3>
                        <p>Failed Logins Today</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $stats->getData()->suspicious_count ?? 0 }}</h3>
                        <p>Suspicious Activities (24h)</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-shield"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activities -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activities</h3>
                        <div class="card-tools">
                            <a href="{{ route('admin.student-portal-logs.index') }}" class="btn btn-sm btn-primary">
                                View All Logs
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Student</th>
                                        <th>Action</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentActivities as $log)
                                        <tr class="{{ $log->is_suspicious ? 'table-danger' : '' }}">
                                            <td>
                                                <small>{{ $log->created_at->diffForHumans() }}</small>
                                            </td>
                                            <td>
                                                {{ $log->student->name ?? 'N/A' }}
                                                @if($log->is_suspicious)
                                                    <i class="fa fa-exclamation-triangle text-danger"></i>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $badgeClass = match ($log->action) {
                                                        'login_success' => 'badge-success',
                                                        'login_failed' => 'badge-danger',
                                                        'logout' => 'badge-warning',
                                                        default => 'badge-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($log->location_data)
                                                    <small>{{ $log->location_data['city'] ?? '' }},
                                                        {{ $log->location_data['country'] ?? '' }}</small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suspicious Activities Alert -->
            <div class="col-md-4">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa fa-exclamation-triangle"></i> Suspicious Activities
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        @if($suspiciousActivities->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($suspiciousActivities as $log)
                                    <li class="list-group-item">
                                        <strong>{{ $log->student->name ?? 'Unknown' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                        <br>
                                        <small class="text-danger">{{ $log->flagged_reason }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="p-3 text-center text-muted">
                                <i class="fa fa-check-circle fa-3x mb-2"></i>
                                <p>No suspicious activities detected</p>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.student-portal-logs.index', ['suspicious' => 1]) }}"
                            class="btn btn-sm btn-danger btn-block">
                            View All Suspicious Logs
                        </a>
                    </div>
                </div>

                <!-- Top Locations -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa fa-map-marker"></i> Top Locations (24h)
                        </h3>
                    </div>
                    <div class="card-body">
                        @php
                            $topLocations = (array) ($stats->getData()->top_locations ?? []);
                        @endphp
                        @if(!empty($topLocations))
                            <ul class="list-unstyled">
                                @foreach($topLocations as $location => $count)
                                    <li class="mb-2">
                                        <strong>{{ $location }}</strong>
                                        <span class="badge badge-primary float-right">{{ $count }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted text-center">No location data available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function () {
            location.reload();
        }, 30000);
    </script>
@endsection