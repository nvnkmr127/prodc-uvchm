@extends('layouts.theme')
@section('title', 'Staff Intelligence Command Center')

@push('styles')
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }

        .activity-card {
            background: var(--glass-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--glass-border);
            border-radius: 1.25rem;
            box-shadow: var(--glass-shadow);
            transition: all 0.3s ease;
        }

        .activity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .stat-mini-pill {
            padding: 0.35rem 0.6rem;
            border-radius: 0.5rem;
            background: rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .icon-calls { background: #e0e7ff; color: #4338ca; }
        .icon-fee { background: #dcfce7; color: #15803d; }
        .icon-admissions { background: #fef9c3; color: #a16207; }

        .progress-slim { height: 6px; border-radius: 10px; }
        
        .leader-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .refresh-sync {
            animation: spin 2s linear infinite;
            display: none;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .chart-container { position: relative; height: 180px; width: 100%; }
        
        .work-session-tag {
            font-size: 0.7rem;
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            padding: 2px 8px;
            border-radius: 50px;
            font-weight: bold;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col-lg-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Intelligence Command Center <i class="fas fa-sync-alt refresh-sync" id="refreshIcon"></i></h1>
            <p class="text-muted small mb-0">Staff Performance & Real-time Activity Monitoring</p>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm border-0" style="border-radius: 1rem;">
                <div class="card-body p-2">
                    <form action="{{ route('admin.staff-activity.index') }}" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm border-0 bg-light rounded-pill px-3" placeholder="Search staff...">
                        </div>
                        <div class="col-md-2">
                            <select name="role" class="form-control form-control-sm border-0 bg-light rounded-pill px-3">
                                <option value="">All Roles</option>
                                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="college-admin" {{ request('role') == 'college-admin' ? 'selected' : '' }}>College Admin</option>
                                <option value="counselor" {{ request('role') == 'counselor' ? 'selected' : '' }}>Counselor</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm border-0 bg-light rounded-pill px-3">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-grow-1 shadow-sm"><i class="fas fa-search"></i> Filter</button>
                            <a href="{{ route('admin.staff-activity.export', ['date' => $date]) }}" class="btn btn-light btn-sm rounded-pill shadow-sm"><i class="fas fa-file-csv"></i> Export</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Trends Section -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 1.25rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="font-weight-bold text-gray-800">Weekly Performance Trends</h5>
                    <span class="badge badge-pill badge-light text-primary small font-weight-bold">Last 7 Days Data</span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius: 1.25rem; height: 100%;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="font-weight-bold text-gray-800"><i class="fas fa-trophy text-warning mr-2"></i>Daily Leaders</h5>
                </div>
                <div class="card-body p-4">
                    <div class="leader-section mb-3">
                        <div class="small font-weight-bold text-muted mb-2 text-uppercase">Top in Admissions</div>
                        @foreach($leaderboard['admissions'] as $data)
                            <div class="d-flex align-items-center mb-2">
                                <div class="leader-badge bg-warning text-white mr-2">{{ $loop->iteration }}</div>
                                <div class="small font-weight-bold text-gray-800">{{ $data['user']->name }}</div>
                                <div class="ml-auto small font-weight-bold text-success">{{ $data['admissions_count'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="leader-section">
                        <div class="small font-weight-bold text-muted mb-2 text-uppercase">Top Collectors</div>
                        @foreach($leaderboard['fees'] as $data)
                            <div class="d-flex align-items-center mb-2">
                                <div class="leader-badge bg-success text-white mr-2">{{ $loop->iteration }}</div>
                                <div class="small font-weight-bold text-gray-800">{{ $data['user']->name }}</div>
                                <div class="ml-auto small font-weight-bold text-success">₹{{ number_format($data['fee_collected'], 0) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Staff Grid -->
        <div class="col-lg-9">
            <div class="row">
                @foreach($activitiesByStaff as $userId => $data)
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="activity-card p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="position-relative">
                                    <div class="user-avatar mr-3">
                                        {{ strtoupper(substr($data['user']->name, 0, 1)) }}
                                    </div>
                                    @if($data['is_online'])
                                        <span class="position-absolute" style="bottom: 0; right: 12px; height: 12px; width: 12px; background: #1cc88a; border-radius: 50%; border: 2px solid white;"></span>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h6 class="font-weight-bold mb-0 text-gray-800 truncate">{{ $data['user']->name }}</h6>
                                    <div class="d-flex gap-2 align-items-center mt-1">
                                        <div class="badge badge-pill {{ $data['score'] > 70 ? 'badge-success' : ($data['score'] > 40 ? 'badge-warning' : 'badge-light') }} small py-1 px-2">
                                            Score: {{ $data['score'] }}%
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-2">
                                    <a href="{{ route('admin.staff-activity.show', $userId) }}?date={{ $date }}" class="btn btn-sm btn-light rounded-circle shadow-sm">
                                        <i class="fas fa-external-link-alt text-muted small"></i>
                                    </a>
                                </div>
                            </div>
                            
                            @if($data['first_action'])
                                <div class="mb-3">
                                    <div class="small text-muted font-weight-bold mb-1"><i class="fas fa-user-clock mr-1"></i> Working Window:</div>
                                    <div class="work-session-tag">
                                        Start: {{ $data['first_action']->format('h:i A') }} | Last: {{ $data['last_action']->format('h:i A') }}
                                    </div>
                                </div>
                            @endif

                            <!-- Target Progress Bars -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-xs font-weight-bold text-muted">Calls (Target: {{ $targets['calls'] }})</span>
                                    <span class="text-xs font-weight-bold text-indigo">{{ $data['calls_count'] }}</span>
                                </div>
                                <div class="progress progress-slim">
                                    <div class="progress-bar bg-primary" style="width: {{ $data['progress']['calls'] }}%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-xs font-weight-bold text-muted">Collection (Target: ₹{{ number_format($targets['fee']) }})</span>
                                    <span class="text-xs font-weight-bold text-success">₹{{ number_format($data['fee_collected']) }}</span>
                                </div>
                                <div class="progress progress-slim">
                                    <div class="progress-bar bg-success" style="width: {{ $data['progress']['fee'] }}%"></div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <div class="stat-mini-pill icon-admissions flex-grow-1">
                                    <i class="fas fa-user-plus"></i> Admissions: <strong>{{ $data['admissions_count'] }}</strong>
                                </div>
                                <div class="stat-mini-pill bg-light flex-grow-1">
                                    <i class="fas fa-clock text-warning"></i> Pending: <strong>{{ $data['pending_tasks'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Real-time Timeline Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm" style="border-radius: 1.25rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between">
                    <h6 class="font-weight-bold text-gray-800">Live Feed</h6>
                    <div class="spinner-grow spinner-grow-sm text-success" role="status"></div>
                </div>
                <div class="card-body p-3">
                    <div class="timeline-container" style="max-height: 80vh; font-size: 0.75rem;">
                        @forelse($timeline as $activity)
                            <div class="timeline-item mb-3 pb-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="font-weight-bold text-primary">{{ $activity->causer->name ?? 'System' }}</span>
                                    <span class="text-muted">{{ $activity->created_at->diffForHumans(null, true) }}</span>
                                </div>
                                <div class="text-gray-700">{{ $activity->description }}</div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">No actions today</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin_theme/vendor/chart.js/Chart.min.js') }}"></script>
    <script>
        // Weekly Trend Chart Implementation
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($trends['labels']) !!},
                datasets: [
                    {
                        label: 'Calls',
                        data: {!! json_encode($trends['calls']) !!},
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderWidth: 3,
                        pointRadius: 3,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Admissions',
                        data: {!! json_encode($trends['admissions']) !!},
                        borderColor: '#f6c23e',
                        borderWidth: 3,
                        pointRadius: 3,
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                legend: { display: true, position: 'top', labels: { boxWidth: 10, fontSize: 10 } },
                scales: {
                    yAxes: [{ gridLines: { display: false }, ticks: { fontSize: 10 } }],
                    xAxes: [{ gridLines: { display: false }, ticks: { fontSize: 10 } }]
                }
            }
        });

        // Auto-Refresh System (Every 30 Seconds)
        let refreshTimer;
        function startAutoRefresh() {
            refreshTimer = setInterval(() => {
                const icon = document.getElementById('refreshIcon');
                icon.style.display = 'inline-block';
                
                // Fetch current URL to get refreshed stats
                window.location.reload();
            }, 30000); // 30 seconds
        }

        // Start polling on load
        document.addEventListener('DOMContentLoaded', startAutoRefresh);
    </script>
@endpush
ion
