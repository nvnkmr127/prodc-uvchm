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
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 1.25rem;
            box-shadow: var(--glass-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .activity-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 45px rgba(31, 38, 135, 0.12);
        }

        .summary-stat-card {
            border-radius: 1rem;
            border: none;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .summary-stat-card:hover { transform: scale(1.02); }

        .user-avatar {
            width: 52px;
            height: 52px;
            border-radius: 1rem;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.3);
        }

        .stat-mini-pill {
            padding: 0.45rem 0.75rem;
            border-radius: 0.75rem;
            background: rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .pulse-online {
            animation: pulse-green 2s infinite;
            box-shadow: 0 0 0 0 rgba(28, 200, 138, 0.7);
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(28, 200, 138, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(28, 200, 138, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(28, 200, 138, 0); }
        }

        .progress-slim { height: 8px; border-radius: 20px; background-color: #eaecf4; }
        
        .leader-badge {
            width: 26px; height: 26px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 900;
        }

        .refresh-sync { animation: spin 2s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .chart-container { position: relative; height: 200px; width: 100%; }
        
        .work-session-tag {
            font-size: 0.75rem;
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            color: #4e73df;
            padding: 4px 12px;
            border-radius: 50px;
            font-weight: 700;
            display: inline-block;
        }

        .timeline-scroll {
            max-height: 600px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        .timeline-scroll::-webkit-scrollbar { width: 4px; }
        .timeline-scroll::-webkit-scrollbar-thumb { background: #d1d3e2; border-radius: 10px; }

        .activity-indicator {
            width: 8px; height: 8px; border-radius: 50%;
            display: inline-block; margin-right: 8px;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header & Filter -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Intelligence Command Center</h1>
            <div class="d-flex align-items-center mt-1">
                @if($startDate == $endDate)
                    <span class="badge badge-primary rounded-pill px-3 py-1 mr-2" style="font-size: 0.7rem;">TODAY</span>
                @else
                    <span class="badge badge-secondary rounded-pill px-3 py-1 mr-2" style="font-size: 0.7rem;">{{ $daysCount }} DAYS PERIOD</span>
                @endif
                <p class="text-muted small mb-0">{{ $summary['online_staff'] }} Staff Online Currently</p>
            </div>
        </div>
        <div class="col-lg-8">
            <form action="{{ route('admin.staff-activity.index') }}" method="GET" class="card shadow-sm border-0 rounded-pill px-2 py-1">
                <div class="card-body p-0 d-flex align-items-center">
                    <div class="flex-grow-1 border-right px-3">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm border-0 bg-transparent" placeholder="Staff Name...">
                    </div>
                    <div class="px-3 border-right d-flex align-items-center">
                        <label class="text-xs font-weight-bold text-muted mr-2 mb-0">FROM</label>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="form-control form-control-sm border-0 bg-transparent">
                    </div>
                    <div class="px-3 border-right d-flex align-items-center">
                        <label class="text-xs font-weight-bold text-muted mr-2 mb-0">TO</label>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="form-control form-control-sm border-0 bg-transparent">
                    </div>
                    <div class="px-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">Analysis</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Commander's Briefing (AI Heuristic Insights) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 1.25rem; background: linear-gradient(90deg, #f8f9fc 0%, #ffffff 100%);">
                <div class="card-body p-0">
                    <div class="d-flex align-items-center p-3 bg-primary text-white" style="width: fit-content; border-bottom-right-radius: 20px;">
                        <i class="fas fa-robot mr-2"></i>
                        <span class="text-xs font-weight-bold text-uppercase tracking-wider">Commander's Intelligence Briefing</span>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            @foreach($insights as $insight)
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <div class="icon-box mr-3 bg-{{ $insight['type'] }}-soft text-{{ $insight['type'] }}" style="min-width: 40px;">
                                            <i class="fas {{ $insight['icon'] }}"></i>
                                        </div>
                                        <p class="mb-0 small text-gray-700">{!! $insight['text'] !!}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Period Summary Insights -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 summary-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Calls</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total_calls'] ?? 0) }}</div>
                            <div class="text-xs text-muted mt-1">{{ round($summary['total_calls'] / $daysCount, 1) }} avg/day</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-phone-alt fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 summary-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Fees Collected</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($summary['total_fees'] ?? 0) }}</div>
                            <div class="text-xs text-muted mt-1">₹{{ number_format($summary['total_fees'] / $daysCount) }} avg/day</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 summary-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Admissions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total_admissions'] ?? 0) }}</div>
                            <div class="text-xs text-muted mt-1">{{ round($summary['total_admissions'] / $daysCount, 2) }} avg/day</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-graduation-cap fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 summary-stat-card">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <p class="small text-muted mb-2 font-weight-bold">Peak Hour: <span class="text-primary">{{ $summary['peak_hour'] }}</span></p>
                    <a href="{{ route('admin.staff-activity.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm mx-auto">
                        <i class="fas fa-download mr-1"></i> Export Data
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Performance & Grid -->
        <div class="col-lg-9">
            <!-- Weekly Chart -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 1.5rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="font-weight-bold text-gray-800 mb-0">System-wide Performance Trends</h5>
                    <div class="dropdown no-arrow">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Staff Members Grid -->
            <div class="row">
                @foreach($activitiesByStaff as $userId => $data)
                    <div class="col-xl-4 col-lg-6 mb-4">
                        <div class="activity-card p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="position-relative mr-3" title="Last seen: {{ $data['last_seen'] }}">
                                    <div class="user-avatar text-uppercase shadow-lg">
                                        {{ substr($data['user']->name, 0, 1) }}{{ str_contains($data['user']->name, ' ') ? substr(explode(' ', $data['user']->name)[1], 0, 1) : '' }}
                                    </div>
                                    @if($data['is_online'])
                                        <span class="position-absolute pulse-online" style="bottom: 2px; right: 2px; height: 14px; width: 14px; background: #1cc88a; border-radius: 50%; border: 3px solid white; z-index: 2;"></span>
                                    @endif
                                    
                                    {{-- NEW: Top Performer Badge --}}
                                    @if($leaderboard['admissions']->first()['user']->id == $userId)
                                        <div class="position-absolute" style="top: -10px; right: -10px; z-index: 3;" title="Top Admission Closer">
                                            <span class="badge badge-warning rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 22px; height: 22px; border: 2px solid white;">🏆</span>
                                        </div>
                                    @elseif($leaderboard['calls']->first()['user']->id == $userId)
                                        <div class="position-absolute" style="top: -10px; right: -10px; z-index: 3;" title="Most Active Caller">
                                            <span class="badge badge-info rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 22px; height: 22px; border: 2px solid white;">📞</span>
                                        </div>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h6 class="font-weight-bold text-gray-900 mb-0 text-truncate">{{ $data['user']->name }}</h6>
                                    <p class="text-xs text-muted mb-0 font-weight-bold text-uppercase">{{ $data['user']->roles->first()->name ?? 'Counselor' }}</p>
                                </div>
                                <div class="ml-auto d-flex flex-column align-items-end">
                                    <span class="text-xs {{ $data['is_online'] ? 'text-success' : 'text-muted' }} font-weight-bold mb-1">
                                        {{ $data['is_online'] ? 'ONLINE' : 'Seen ' . $data['last_seen'] }}
                                    </span>
                                    <a href="{{ route('admin.staff-activity.show', $userId) }}?start_date={{ $startDate }}&end_date={{ $endDate }}" class="btn btn-primary btn-circle btn-sm shadow-sm">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Performance Row -->
                            <div class="row mb-3 align-items-center">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light border-left-{{ $data['score'] >= 80 ? 'success' : ($data['score'] >= 50 ? 'warning' : 'danger') }}" style="border-width: 3px;">
                                        <div class="small font-weight-bold text-gray-600 mb-0">Score</div>
                                        <div class="h5 mb-0 font-weight-bold {{ $data['score'] >= 80 ? 'text-success' : ($data['score'] >= 50 ? 'text-warning' : 'text-danger') }}">{{ $data['score'] }}%</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light border-left-info" style="border-width: 3px;">
                                        <div class="small font-weight-bold text-gray-600 mb-0">Conv. Rate</div>
                                        <div class="h5 mb-0 font-weight-bold text-info">{{ $data['conversion_rate'] }}%</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detailed Metrics -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-xs font-weight-bold text-muted">Calls in Period</span>
                                    <span class="text-xs font-weight-bold text-primary">{{ $data['calls_count'] }} / {{ $targets['calls'] }}</span>
                                </div>
                                <div class="progress progress-slim"><div class="progress-bar bg-primary" role="progressbar" style="width: {{ $data['progress']['calls'] }}%"></div></div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-xs font-weight-bold text-muted">Fee Collection</span>
                                    <span class="text-xs font-weight-bold text-success">₹{{ number_format($data['fee_collected']) }}</span>
                                </div>
                                <div class="progress progress-slim"><div class="progress-bar bg-success" role="progressbar" style="width: {{ $data['progress']['fee'] }}%"></div></div>
                            </div>

                            <div class="row g-2 mt-4">
                                <div class="col-6">
                                    <div class="stat-mini-pill bg-warning-soft">
                                        <i class="fas fa-user-plus text-warning"></i> <span><strong>{{ $data['admissions_count'] }}</strong> Adm.</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-pill bg-info-soft">
                                        <i class="fas fa-clock text-info"></i> <span><strong>{{ $data['pending_tasks'] }}</strong> Pend.</span>
                                    </div>
                                </div>
                            </div>

                            @if($data['first_action'])
                                <div class="mt-4 text-center">
                                    <div class="work-session-tag" title="First and last action in selected period">
                                        <i class="far fa-calendar-alt mr-1"></i> {{ $data['first_action']->format('M d, H:i') }} — {{ $data['last_action']->format('M d, H:i') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Right Side: Sidebar & Leaders -->
        <div class="col-lg-3">
            <!-- Daily Leaders Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 1.5rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="font-weight-bold text-gray-800">Top Performers <span class="text-xs text-muted">(This Period)</span></h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="small font-weight-bold text-muted text-uppercase mb-2">Admissions</div>
                        @foreach($leaderboard['admissions'] as $l)
                            <div class="d-flex align-items-center mb-2">
                                <div class="leader-badge bg-warning text-white mr-2">{{ $loop->iteration }}</div>
                                <div class="small font-weight-bold text-dark">{{ $l['user']->name }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <div class="small font-weight-bold text-muted text-uppercase mb-2">Collection</div>
                        @foreach($leaderboard['fees'] as $l)
                            <div class="d-flex align-items-center mb-2">
                                <div class="leader-badge bg-success text-white mr-2">{{ $loop->iteration }}</div>
                                <div class="small font-weight-bold text-dark">{{ $l['user']->name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Live Feed Card -->
            <div class="card border-0 shadow-sm" style="border-radius: 1.5rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-bold text-gray-800">System Live Feed</h6>
                    <span class="refresh-sync text-success"><i class="fas fa-sync-alt fa-xs"></i></span>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-scroll">
                        @forelse($timeline as $activity)
                            <div class="mb-4 pb-3 border-bottom position-relative">
                                <span class="activity-indicator {{ $activity->event == 'created' ? 'bg-success' : 'bg-primary' }}"></span>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="font-weight-bold text-dark" style="font-size: 0.8rem;">{{ $activity->causer->name ?? 'System' }}</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">{{ $activity->created_at->diffForHumans(null, true) }}</span>
                                </div>
                                <p class="text-muted mb-0" style="font-size: 0.75rem; line-height: 1.4;">{{ $activity->description }}</p>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-ghost fa-3x text-gray-200 mb-3"></i>
                                <p class="text-muted small">Quiet day so far...</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <script>
        // Performance Trends
        const ctx = document.getElementById('trendChart').getContext('2d');
        const gradientPrimary = ctx.createLinearGradient(0, 0, 0, 225);
        gradientPrimary.addColorStop(0, 'rgba(78, 115, 223, 0.15)');
        gradientPrimary.addColorStop(1, 'rgba(78, 115, 223, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($trends['labels']) !!},
                datasets: [
                    {
                        label: 'Calls Made',
                        data: {!! json_encode($trends['calls']) !!},
                        borderColor: '#4e73df',
                        backgroundColor: gradientPrimary,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#4e73df',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Admissions',
                        data: {!! json_encode($trends['admissions']) !!},
                        borderColor: '#f6c23e',
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#f6c23e',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Fees (₹)',
                        data: {!! json_encode($trends['fees']) !!},
                        borderColor: '#1cc88a',
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#1cc88a',
                        tension: 0.4,
                        fill: false,
                        hidden: true // Start hidden because values might be much higher
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                legend: { position: 'top', labels: { boxWidth: 12, fontStyle: 'bold' } },
                scales: {
                    yAxes: [{ ticks: { fontColor: '#858796', fontSize: 11, beginAtZero: true }, gridLines: { color: '#f8f9fc', drawBorder: false } }],
                    xAxes: [{ ticks: { fontColor: '#858796', fontSize: 11 }, gridLines: { display: false } }]
                },
                tooltips: { backgroundColor: '#fff', titleFontColor: '#4e73df', bodyFontColor: '#858796', borderColor: '#e3e6f0', borderWidth: 1, xPadding: 15, yPadding: 15, displayColors: false, intersect: false, mode: 'index', caretPadding: 10 }
            }
        });

        // Auto-Refresh (60s)
        setInterval(() => {
            // Only auto-refresh if looking at "today"
            const params = new URLSearchParams(window.location.search);
            const start = params.get('start_date');
            const end = params.get('end_date');
            const today = new Date().toISOString().split('T')[0];
            
            if (!start || (start === today && (!end || end === today))) {
                window.location.reload();
            }
        }, 60000);
    </script>
@endpush

