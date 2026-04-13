@extends('layouts.theme')
@section('title', 'Staff Intelligence Command Center')

@push('styles')
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.4);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --success-gradient: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            --warning-gradient: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            --danger-gradient: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
        }

        .activity-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 1.5rem;
            box-shadow: var(--glass-shadow);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
        }

        .activity-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 20px 40px rgba(31, 38, 135, 0.1);
        }

        .summary-stat-card {
            border-radius: 1.25rem;
            border: none;
            overflow: hidden;
            transition: all 0.3s;
            background: white;
        }
        .summary-stat-card:hover { 
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
        }

        .user-avatar {
            width: 56px;
            height: 56px;
            border-radius: 1.25rem;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 800;
            box-shadow: 0 6px 15px rgba(78, 115, 223, 0.25);
        }

        .stat-mini-pill {
            padding: 0.5rem 0.85rem;
            border-radius: 0.85rem;
            background: rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 700;
            transition: background 0.2s;
        }
        .stat-mini-pill:hover { background: rgba(0, 0, 0, 0.05); }

        .trend-indicator {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 50px;
            margin-left: 5px;
        }
        .trend-up { background: #e6fffa; color: #1cc88a; }
        .trend-down { background: #fff5f5; color: #e74a3b; }

        .pulse-online {
            animation: pulse-green 2s infinite;
            box-shadow: 0 0 0 0 rgba(28, 200, 138, 0.7);
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(28, 200, 138, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(28, 200, 138, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(28, 200, 138, 0); }
        }

        .progress-slim { height: 10px; border-radius: 20px; background-color: #f1f3f9; border: 1px solid #edf0f5; }
        
        .leader-badge {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 900;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .refresh-sync { animation: spin 3s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .chart-container { position: relative; height: 250px; width: 100%; }
        
        .work-session-tag {
            font-size: 0.75rem;
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            color: #4e73df;
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 700;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .timeline-scroll {
            max-height: 650px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        .timeline-scroll::-webkit-scrollbar { width: 5px; }
        .timeline-scroll::-webkit-scrollbar-thumb { background: #e3e6f0; border-radius: 10px; }

        .activity-indicator {
            width: 10px; height: 10px; border-radius: 50%;
            display: inline-block; margin-right: 10px;
            border: 2px solid white;
            box-shadow: 0 0 0 1px rgba(0,0,0,0.05);
        }

        .bg-primary-soft { background: #eef2ff; color: #4e73df; }
        .bg-success-soft { background: #ecfdf5; color: #10b981; }
        .bg-warning-soft { background: #fffbeb; color: #f59e0b; }
        .bg-danger-soft { background: #fef2f2; color: #ef4444; }
        .bg-info-soft { background: #f0f9ff; color: #0ea5e9; }

        .score-display {
            font-size: 1.5rem;
            letter-spacing: -1px;
        }

        .insight-card {
            border-left: 4px solid;
            transition: all 0.2s;
        }
        .insight-card:hover { transform: translateX(5px); }
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
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 1.5rem; background: #fff;">
                <div class="card-body p-0">
                    <div class="d-flex align-items-center p-3 text-primary" style="background: #f8faff; border-bottom: 1px solid #edf2f7;">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 32px; height: 32px;">
                            <i class="fas fa-robot fa-sm"></i>
                        </div>
                        <span class="text-xs font-weight-bold text-uppercase tracking-wider">Strategic Intelligence Briefing</span>
                        <span class="ml-auto text-xs text-muted font-weight-normal italic">Updated live based on period behavior</span>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            @foreach($insights as $insight)
                                <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                                    <div class="insight-card p-3 rounded bg-{{ $insight['type'] }}-soft border-{{ $insight['type'] }} h-100">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-box mr-3 bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="min-width: 35px; height: 35px; color: inherit;">
                                                <i class="fas {{ $insight['icon'] }}"></i>
                                            </div>
                                            <p class="mb-0 small text-gray-800" style="line-height: 1.5;">{!! $insight['text'] !!}</p>
                                        </div>
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
            <div class="card border-left-primary shadow-sm h-100 py-2 summary-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Calls</div>
                            <div class="d-flex align-items-baseline">
                                <span class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total_calls'] ?? 0) }}</span>
                                <span class="trend-indicator {{ $comparisons['calls'] >= 0 ? 'trend-up' : 'trend-down' }}">
                                    <i class="fas fa-caret-{{ $comparisons['calls'] >= 0 ? 'up' : 'down' }} mr-1"></i>{{ abs(round($comparisons['calls'])) }}%
                                </span>
                            </div>
                            <div class="text-xs text-muted mt-1">{{ round($summary['total_calls'] / $daysCount, 1) }} avg/day</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-phone-alt fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow-sm h-100 py-2 summary-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Fees Collected</div>
                            <div class="d-flex align-items-baseline">
                                <span class="h4 mb-0 font-weight-bold text-gray-800">₹{{ number_format($summary['total_fees'] ?? 0) }}</span>
                                <span class="trend-indicator {{ $comparisons['fees'] >= 0 ? 'trend-up' : 'trend-down' }}">
                                    <i class="fas fa-caret-{{ $comparisons['fees'] >= 0 ? 'up' : 'down' }} mr-1"></i>{{ abs(round($comparisons['fees'])) }}%
                                </span>
                            </div>
                            <div class="text-xs text-muted mt-1">₹{{ number_format($summary['total_fees'] / $daysCount) }} avg/day</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow-sm h-100 py-2 summary-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Admissions</div>
                            <div class="d-flex align-items-baseline">
                                <span class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total_admissions'] ?? 0) }}</span>
                                <span class="trend-indicator {{ $comparisons['admissions'] >= 0 ? 'trend-up' : 'trend-down' }}">
                                    <i class="fas fa-caret-{{ $comparisons['admissions'] >= 0 ? 'up' : 'down' }} mr-1"></i>{{ abs(round($comparisons['admissions'])) }}%
                                </span>
                            </div>
                            <div class="text-xs text-muted mt-1">{{ round($summary['total_admissions'] / $daysCount, 2) }} avg/day</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-graduation-cap fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm h-100 py-2 summary-stat-card bg-primary text-white">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <p class="small mb-2 font-weight-bold text-white-50">Peak Performance Window</p>
                    <h6 class="font-weight-bold mb-3">{{ $summary['peak_hour'] }}</h6>
                    <a href="{{ route('admin.staff-activity.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-light btn-sm rounded-pill px-3 shadow-sm mx-auto font-weight-bold text-primary">
                        <i class="fas fa-download mr-1"></i> Export Command Report
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
                            <div class="d-flex align-items-center mb-4">
                                <div class="position-relative mr-3" title="Last seen: {{ $data['last_seen'] }}">
                                    <div class="user-avatar text-uppercase shadow-lg">
                                        {{ substr($data['user']->name, 0, 1) }}{{ str_contains($data['user']->name, ' ') ? substr(explode(' ', $data['user']->name)[1], 0, 1) : '' }}
                                    </div>
                                    @if($data['is_online'])
                                        <span class="position-absolute pulse-online" style="bottom: 2px; right: 2px; height: 16px; width: 16px; background: #1cc88a; border-radius: 50%; border: 3px solid white; z-index: 2;"></span>
                                    @endif
                                    
                                    @if($leaderboard['admissions']->first()['user']->id == $userId)
                                        <div class="position-absolute" style="top: -12px; right: -12px; z-index: 3;" title="Top Admission Closer">
                                            <span class="badge badge-warning rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 26px; height: 26px; border: 2px solid white;">🏆</span>
                                        </div>
                                    @elseif($leaderboard['calls']->first()['user']->id == $userId)
                                        <div class="position-absolute" style="top: -12px; right: -12px; z-index: 3;" title="Most Active Caller">
                                            <span class="badge badge-info rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 26px; height: 26px; border: 2px solid white;">📞</span>
                                        </div>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h6 class="font-weight-extrabold text-gray-900 mb-0 text-truncate" style="font-size: 1.1rem;">{{ $data['user']->name }}</h6>
                                    <div class="d-flex align-items-center mt-1">
                                        <span class="badge bg-primary-soft text-xs py-1 px-2 font-weight-bold">{{ $data['user']->roles->first()->name ?? 'Counselor' }}</span>
                                        <span class="mx-2 text-gray-300">•</span>
                                        <span class="text-xs {{ $data['is_online'] ? 'text-success' : 'text-muted' }} font-weight-bold">
                                            {{ $data['is_online'] ? 'ACTIVE NOW' : 'OFFLINE' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-auto">
                                    <a href="{{ route('admin.staff-activity.show', $userId) }}?start_date={{ $startDate }}&end_date={{ $endDate }}" class="btn btn-white btn-circle shadow-sm border">
                                        <i class="fas fa-arrow-right text-primary fa-sm"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Score Analysis -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="p-3 rounded bg-light border-0 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small font-weight-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Productivity Index</div>
                                            <div class="score-display font-weight-bold {{ $data['score'] >= 80 ? 'text-success' : ($data['score'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                                {{ $data['score'] }}<span class="small font-weight-normal text-muted" style="font-size: 0.8rem;">/100</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="small font-weight-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Conv. Efficiency</div>
                                            <div class="h5 mb-0 font-weight-bold text-primary">{{ $data['conversion_rate'] }}%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Metrics Progress -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-xs font-weight-bold text-gray-600">Period Reach (Calls)</span>
                                    <span class="text-xs font-weight-bold text-dark">{{ $data['calls_count'] }} <span class="text-muted font-weight-normal">of {{ $targets['calls'] }}</span></span>
                                </div>
                                <div class="progress progress-slim"><div class="progress-bar bg-primary" role="progressbar" style="width: {{ $data['progress']['calls'] }}%"></div></div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-xs font-weight-bold text-gray-600">Financial Impact</span>
                                    <span class="text-xs font-weight-bold text-dark">₹{{ number_format($data['fee_collected']) }}</span>
                                </div>
                                <div class="progress progress-slim"><div class="progress-bar bg-success" role="progressbar" style="width: {{ $data['progress']['fee'] }}%"></div></div>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <div class="stat-mini-pill bg-warning-soft">
                                        <i class="fas fa-check-circle text-warning"></i> <span><strong>{{ $data['admissions_count'] }}</strong> Success</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-pill bg-info-soft">
                                        <i class="fas fa-tasks text-info"></i> <span><strong>{{ $data['pending_tasks'] }}</strong> Open</span>
                                    </div>
                                </div>
                            </div>

                            @if($data['first_action'])
                                <div class="pt-3 border-top text-center">
                                    <div class="work-session-tag">
                                        <i class="far fa-clock mr-1"></i> Shift: {{ $data['first_action']->format('H:i') }} — {{ $data['last_action']->format('H:i') }}
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
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 1.5rem; background: #fff;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="font-weight-extrabold text-gray-800 text-uppercase tracking-wider" style="font-size: 0.75rem;">Command Vanguard</h6>
                    <p class="text-xs text-muted mb-0">Elite performers this period</p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-graduation-cap text-warning mr-2"></i>
                            <span class="small font-weight-bold text-muted text-uppercase" style="font-size: 0.65rem;">Conversion Leaders</span>
                        </div>
                        @foreach($leaderboard['admissions'] as $l)
                            <div class="d-flex align-items-center mb-3">
                                <div class="leader-badge bg-warning-soft text-warning mr-3" style="min-width: 28px;">{{ $loop->iteration }}</div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="small font-weight-bold text-dark text-truncate">{{ $l['user']->name }}</div>
                                    <div class="text-xs text-muted">{{ $l['admissions_count'] }} admissions</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-wallet text-success mr-2"></i>
                            <span class="small font-weight-bold text-muted text-uppercase" style="font-size: 0.65rem;">Revenue Champions</span>
                        </div>
                        @foreach($leaderboard['fees'] as $l)
                            <div class="d-flex align-items-center mb-3">
                                <div class="leader-badge bg-success-soft text-success mr-3" style="min-width: 28px;">{{ $loop->iteration }}</div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="small font-weight-bold text-dark text-truncate">{{ $l['user']->name }}</div>
                                    <div class="text-xs text-muted">₹{{ number_format($l['fee_collected']) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Live Feed Card -->
            <div class="card border-0 shadow-sm" style="border-radius: 1.5rem; background: #fff;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="font-weight-extrabold text-gray-800 text-uppercase tracking-wider mb-0" style="font-size: 0.75rem;">Intelligence Stream</h6>
                        <p class="text-xs text-muted mb-0">Live system telemetry</p>
                    </div>
                    <span class="refresh-sync text-success"><i class="fas fa-sync-alt fa-xs"></i></span>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-scroll pr-2">
                        @forelse($timeline as $activity)
                            <div class="mb-4 pb-3 border-light border-bottom position-relative">
                                <span class="activity-indicator {{ $activity->event == 'created' ? 'bg-success' : 'bg-primary' }}"></span>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="font-weight-bold text-dark" style="font-size: 0.8rem;">{{ $activity->causer->name ?? 'System' }}</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">{{ $activity->created_at->diffForHumans(null, true) }}</span>
                                </div>
                                <p class="text-muted mb-0" style="font-size: 0.75rem; line-height: 1.5; font-weight: 500;">{{ $activity->description }}</p>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-satellite fa-lg text-gray-300"></i>
                                </div>
                                <p class="text-muted small font-weight-bold">Scanning for activities...</p>
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

