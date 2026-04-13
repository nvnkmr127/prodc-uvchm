@extends('layouts.theme')
@section('title', 'Student Portal Security Monitor')

@push('styles')
    <style>
        :root {
            --security-red: #e74a3b;
            --security-green: #1cc88a;
            --security-blue: #4e73df;
            --glass-card: rgba(255, 255, 255, 0.9);
        }
        .monitor-card {
            border: none; border-radius: 1.25rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        .monitor-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); }
        
        .stat-value { font-size: 1.8rem; font-weight: 800; line-height: 1.2; }
        .stat-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; }
        
        .live-indicator {
            display: inline-block; width: 8px; height: 8px; border-radius: 50%;
            background: #1cc88a; margin-right: 5px; animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(28, 200, 138, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(28, 200, 138, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(28, 200, 138, 0); }
        }

        .activity-feed { max-height: 600px; overflow-y: auto; scrollbar-width: thin; }
        .activity-feed::-webkit-scrollbar { width: 4px; }
        .activity-feed::-webkit-scrollbar-thumb { background: #d1d3e2; border-radius: 10px; }

        .suspicious-item {
            border-left: 4px solid var(--security-red); background: #fff5f5;
            margin-bottom: 10px; border-radius: 0.5rem; transition: background 0.2s;
        }
        .suspicious-item:hover { background: #fee2e2; }

        .location-bar { height: 8px; border-radius: 4px; background: #eaecf4; overflow: hidden; }
        .location-progress { height: 100%; background: var(--security-blue); transition: width 1s ease-in-out; }

        .badge-security { font-weight: 800; text-transform: uppercase; font-size: 0.65rem; padding: 0.35rem 0.65rem; border-radius: 0.5rem; }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Dashboard Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Security Operations Center</h1>
                <p class="mb-0 text-muted small font-weight-bold">
                    <span class="live-indicator"></span> Student Portal Activity Monitoring - Live Session
                </p>
            </div>
            <div class="text-right">
                <p class="small text-muted mb-0 font-weight-bold">LAST TELEMETRY UPDATE</p>
                <span class="badge badge-light shadow-sm text-primary font-weight-bold py-2 px-3">
                    <i class="fas fa-clock mr-1"></i> {{ now()->format('H:i:s') }}
                </span>
            </div>
        </div>

        @php
            $statsData = $stats->getData();
        @endphp

        <!-- Key Metrics Row -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card monitor-card border-left-success h-100 p-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label text-success mb-1">Total Logins (Today)</div>
                                <div class="stat-value text-gray-800">{{ $statsData->total_logins_today ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card monitor-card border-left-info h-100 p-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label text-info mb-1">Active Users (24h)</div>
                                <div class="stat-value text-gray-800">{{ $statsData->active_students ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card monitor-card border-left-warning h-100 p-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label text-warning mb-1">Failed Attempts</div>
                                <div class="stat-value text-gray-800">{{ $statsData->failed_logins_today ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card monitor-card border-left-danger h-100 p-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="stat-label text-danger mb-1">Anomalies Detected</div>
                                <div class="stat-value text-gray-800">{{ $statsData->suspicious_count ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        @if(($statsData->suspicious_count ?? 0) > 0)
                            <div class="mt-2">
                                <span class="badge badge-danger-soft text-danger font-weight-bold text-xs">
                                    <i class="fas fa-exclamation-circle mr-1"></i> HIGH ALERT
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Traffic Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card monitor-card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                        <h6 class="m-0 font-weight-bold text-primary text-uppercase small tracking-wider">Traffic Pulse (24h Intensity)</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 250px;">
                            <canvas id="trafficPulseChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities Table -->
                <div class="card monitor-card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                        <h6 class="m-0 font-weight-bold text-primary text-uppercase small tracking-wider">Authenticated Activity Stream</h6>
                        <a href="{{ route('admin.student-portal-logs.index') }}" class="btn btn-sm btn-light border font-weight-bold text-xs">
                            FULL DATABASE ACCESS
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive activity-feed">
                            <table class="table mb-0 align-middle">
                                <thead class="bg-light text-uppercase font-weight-bold small" style="font-size: 0.65rem;">
                                    <tr>
                                        <th class="border-0 px-4">Timestamp</th>
                                        <th class="border-0">Student Entity</th>
                                        <th class="border-0">Operation</th>
                                        <th class="border-0">Geolocation</th>
                                        <th class="border-0 text-right pr-4">Profile</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentActivities as $log)
                                        <tr class="{{ $log->is_suspicious ? 'bg-danger-light' : '' }}">
                                            <td class="px-4 py-3">
                                                <div class="font-weight-bold text-gray-800 small">{{ $log->created_at->format('H:i:s') }}</div>
                                                <div class="text-xs text-muted">{{ $log->created_at->diffForHumans() }}</div>
                                            </td>
                                            <td class="py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-gray-200 d-flex align-items-center justify-content-center mr-2 shadow-sm" style="width: 32px; height: 32px; font-weight: 800; font-size: 0.75rem;">
                                                        {{ substr($log->student->name ?? '?', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-weight-bold text-gray-900 small">{{ $log->student->name ?? 'UNKNOWN ENTITY' }}</div>
                                                        <div class="text-xs text-muted">UID: #{{ $log->student_id }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                @php
                                                    $badgeClass = match ($log->action) {
                                                        'login_success' => 'bg-success text-white',
                                                        'login_failed' => 'bg-danger text-white',
                                                        'logout' => 'bg-warning text-dark',
                                                        default => 'bg-secondary text-white'
                                                    };
                                                @endphp
                                                <span class="badge badge-security {{ $badgeClass }}">
                                                    {{ str_replace('_', ' ', $log->action) }}
                                                </span>
                                            </td>
                                            <td class="py-3">
                                                @if($log->location_data)
                                                    <div class="small font-weight-bold text-gray-800">
                                                        <i class="fas fa-map-marker-alt text-primary mr-1"></i>
                                                        {{ $log->location_data['city'] ?? 'Unknown City' }}
                                                    </div>
                                                    <div class="text-xs text-muted">{{ $log->location_data['country'] ?? 'N/A' }} • {{ $log->ip_address }}</div>
                                                @else
                                                    <span class="text-xs text-muted opacity-50">LOCATION DATA NOT RESOLVED</span>
                                                @endif
                                            </td>
                                            <td class="text-right pr-4 py-3">
                                                <a href="{{ route('admin.student-portal-logs.show', $log->id) }}" class="btn btn-circle btn-sm btn-white border shadow-sm">
                                                    <i class="fas fa-chevron-right text-primary fa-xs"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidbear Monitoring -->
            <div class="col-xl-4 col-lg-5">
                <!-- Anomaly Alerts Card -->
                <div class="card monitor-card shadow mb-4">
                    <div class="card-header py-3 bg-danger">
                        <h6 class="m-0 font-weight-bold text-white text-uppercase small tracking-wider">
                            <i class="fas fa-biohazard mr-1"></i> Security Anomaly Detected
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        @if($suspiciousActivities->count() > 0)
                            <div class="activity-feed" style="max-height: 350px;">
                                @foreach($suspiciousActivities as $log)
                                    <div class="suspicious-item p-3 border shadow-sm">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="font-weight-extrabold text-gray-900 small">{{ $log->student->name ?? 'UNKNOWN' }}</span>
                                            <span class="text-xs font-weight-bold text-danger">{{ $log->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="text-xs text-danger font-weight-bold mb-2">
                                            <i class="fas fa-shield-virus mr-1"></i> {{ $log->flagged_reason }}
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-xs text-muted font-weight-bold">{{ $log->ip_address }}</span>
                                            <a href="{{ route('admin.student-portal-logs.show', $log->id) }}" class="btn btn-xs btn-danger px-2 border-0 font-weight-bold shadow-sm" style="font-size: 0.6rem;">ANALYZE</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="rounded-circle bg-success-light text-success mx-auto mb-3 d-flex align-items-center justify-content-center shadow-inner" style="width: 80px; height: 80px;">
                                    <i class="fas fa-shield-check fa-3x"></i>
                                </div>
                                <h6 class="font-weight-bold text-gray-800">Clear Skies</h6>
                                <p class="text-xs text-muted mb-0 font-weight-bold">PERIMETER IS SECURE</p>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-top">
                        <a href="{{ route('admin.student-portal-logs.index', ['suspicious' => 1]) }}" class="btn btn-block btn-outline-danger btn-sm font-weight-bold rounded-pill">
                            SECURITY ARCHIVE ACCESS
                        </a>
                    </div>
                </div>

                <!-- Geographic Distribution Card -->
                <div class="card monitor-card shadow mb-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-primary text-uppercase small tracking-wider">Geographic Distribution</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $topLocations = (array) ($statsData->top_locations ?? []);
                            $maxCount = !empty($topLocations) ? max((array)$topLocations) : 1;
                        @endphp
                        @if(!empty($topLocations))
                            @foreach($topLocations as $location => $count)
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2 align-items-end">
                                        <span class="small font-weight-bold text-gray-700 text-uppercase tracking-tighter">{{ $location }}</span>
                                        <span class="small font-weight-extrabold text-gray-900">{{ $count }} OPS</span>
                                    </div>
                                    <div class="location-bar">
                                        <div class="location-progress" style="width: {{ ($count / $maxCount) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4 opacity-50">
                                <i class="fas fa-globe-americas fa-3x mb-2"></i>
                                <p class="small font-weight-bold mb-0">TRIANGULATING DATA...</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Support -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <script>
        // Traffic Pulse Chart
        const ctx = document.getElementById('trafficPulseChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, 'rgba(78, 115, 223, 0.2)');
        gradient.addColorStop(1, 'rgba(78, 115, 223, 0)');

        const hourlyData = @json($statsData->hourly_activity ?? []);
        const labels = Array.from({length: 24}, (_, i) => `${i}:00`);
        const values = Array.from({length: 24}, (_, i) => hourlyData[i] || 0);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'System Access Events',
                    data: values,
                    borderColor: '#4e73df',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointRadius: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4e73df',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#4e73df',
                    pointHoverBorderColor: '#fff',
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                scales: {
                    xAxes: [{ ticks: { fontColor: '#b7b9cc', fontSize: 10, maxTicksLimit: 12, fontStyle: 'bold' }, gridLines: { display: false, drawBorder: false } }],
                    yAxes: [{ ticks: { fontColor: '#b7b9cc', fontSize: 10, beginAtZero: true, fontStyle: 'bold', padding: 10, maxTicksLimit: 5 }, gridLines: { color: 'rgb(234, 236, 244)', zeroLineColor: 'rgb(234, 236, 244)', drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } }]
                },
                legend: { display: false },
                tooltips: { backgroundColor: '#fff', titleFontColor: '#6e707e', bodyFontColor: '#858796', borderColor: '#dddfeb', borderWidth: 1, xPadding: 15, yPadding: 15, displayColors: false, intersect: false, mode: 'index', caretPadding: 10 }
            }
        });

        // Auto-refresh logic (30s)
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
@endsection