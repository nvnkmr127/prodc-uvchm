@extends('layouts.theme')
@section('title', 'Staff Performance Deep-Dive')

@push('styles')
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
        }
        .staff-profile-card {
            background: var(--primary-gradient);
            color: white; border-radius: 2rem; overflow: hidden; position: relative;
            box-shadow: 0 15px 35px rgba(34, 74, 190, 0.2);
        }
        .staff-profile-card::after {
            content: 'COMMAND'; position: absolute; top: -20px; right: -20px;
            font-size: 6rem; font-weight: 900; color: rgba(255, 255, 255, 0.05);
            letter-spacing: -2px; pointer-events: none;
        }
        .activity-pill {
            background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 1rem; padding: 0.75rem 1rem; color: white;
            transition: all 0.2s;
        }
        .activity-pill:hover { background: rgba(255, 255, 255, 0.18); transform: translateY(-2px); }
        
        .timeline-advanced { list-style: none; padding-left: 2rem; position: relative; }
        .timeline-advanced::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; background: #eaecf4; border-radius: 10px;
        }
        .timeline-item { position: relative; margin-bottom: 2.5rem; }
        .timeline-dot {
            position: absolute; left: -2.35rem; top: 0; width: 14px; height: 14px;
            border-radius: 50%; background: #4e73df; border: 3px solid white;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2);
        }
        .metric-benchmark {
            background: #fff; border: 1px solid #f1f3f9; border-radius: 1.25rem; padding: 1.5rem;
            position: relative; overflow: hidden; transition: all 0.3s;
        }
        .metric-benchmark:hover { transform: scale(1.02); box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        .metric-benchmark .progress { height: 8px; border-radius: 20px; background: #f1f3f9; }
        .benchmark-label { font-size: 0.75rem; font-weight: 800; color: #858796; text-uppercase: uppercase; letter-spacing: 0.5px; }
        .benchmark-val { font-size: 1.25rem; font-weight: 900; color: #4e73df; }

        .breadcrumb-item + .breadcrumb-item::before { content: '→'; color: #d1d3e2; }
        .breadcrumb-item a { color: #858796; font-weight: 700; text-decoration: none; }
        .breadcrumb-item.active { color: #4e73df; font-weight: 800; }

        .timeline-scroll {
            scrollbar-width: thin;
            scrollbar-color: #d1d3e2 transparent;
        }
        .timeline-scroll::-webkit-scrollbar { width: 5px; }
        .timeline-scroll::-webkit-scrollbar-track { background: transparent; }
        .timeline-scroll::-webkit-scrollbar-thumb { background: #d1d3e2; border-radius: 10px; }
        .timeline-scroll::-webkit-scrollbar-thumb:hover { background: #b7b9cc; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb & Date -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.staff-activity.index') }}?start_date={{ $startDate }}&end_date={{ $endDate }}">Staff Intelligence</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Deep-Dive Analytics</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Intelligence Deep-Dive</h1>
        </div>
        <div class="text-right">
            <span class="badge badge-light px-3 py-2 text-primary font-weight-bold shadow-sm rounded-pill">
                <i class="fas fa-calendar-alt mr-1"></i> 
                @if($startDate == $endDate)
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}
                @else
                    {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                @endif
            </span>
        </div>
    </div>

    <!-- Analytics Dashboard Header -->
    <div class="row mb-4">
        <!-- Staff Profile Sidebar -->
        <div class="col-lg-4">
            <div class="card staff-profile-card border-0 mb-4">
                <div class="card-body p-4 pb-5">
                    <div class="d-flex align-items-center mb-5">
                        <div class="mr-3 shadow-lg" style="width: 100px; height: 100px; border-radius: 2rem; background: white; color: #4e73df; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 900; border: 4px solid rgba(255,255,255,0.2);">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-weight-extrabold mb-1" style="letter-spacing: -0.5px;">{{ $user->name }}</h3>
                            <p class="mb-0 opacity-75 font-weight-bold text-uppercase small tracking-widest">{{ $user->roles->first()->name ?? 'Counselor' }}</p>
                            <span class="badge badge-pill badge-light mt-2 px-3 py-1 text-primary font-weight-extrabold" style="font-size: 0.7rem;">COMMAND ID: #{{ $user->id }}</span>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6 pr-2">
                            <div class="activity-pill text-center">
                                <span class="small opacity-75 d-block text-uppercase mb-1" style="font-size: 0.65rem; font-weight: 800;">Commenced</span>
                                <span class="h5 font-weight-extrabold mb-0">{{ $stats['personal']['first_action'] ? $stats['personal']['first_action']->format('H:i') : '--:--' }}</span>
                            </div>
                        </div>
                        <div class="col-6 pl-2">
                            <div class="activity-pill text-center">
                                <span class="small opacity-75 d-block text-uppercase mb-1" style="font-size: 0.65rem; font-weight: 800;">Concluded</span>
                                <span class="h5 font-weight-extrabold mb-0">{{ $stats['personal']['last_action'] ? $stats['personal']['last_action']->format('H:i') : '--:--' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl shadow-inner" style="background: rgba(0,0,0,0.1); border-radius: 1.5rem;">
                         <h6 class="small font-weight-extrabold text-uppercase mb-3 opacity-75 tracking-wider">Mission Intelligence</h6>
                         <div class="d-flex justify-content-between mb-3">
                             <span class="small font-weight-bold">Primary Focus</span>
                             <span class="small font-weight-bold text-white">{{ array_key_first($activityDistribution) ?: 'Universal' }}</span>
                         </div>
                         <div class="d-flex justify-content-between">
                             <span class="small font-weight-bold">Intensity Peak</span>
                             <span class="small font-weight-bold text-white">{{ max(array_values($hourlyMap) ?: [0]) }} ops/hr</span>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Team Benchmarking Widget -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1.5rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="font-weight-extrabold text-dark text-uppercase tracking-wider mb-0" style="font-size: 0.75rem;">Tactical Comparison</h6>
                    <p class="text-xs text-muted">Against tactical average</p>
                </div>
                <div class="card-body p-4">
                    <div class="metric-benchmark mb-4">
                        <div class="d-flex justify-content-between mb-3 align-items-end">
                            <span class="benchmark-label">Operational Volume</span>
                            <div class="text-right">
                                <span class="benchmark-val">{{ $stats['personal']['calls'] }}</span>
                                <span class="d-block text-xs text-muted font-weight-bold">Avg: {{ round($stats['team_avg']['calls'], 1) }}</span>
                            </div>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" style="width: {{ min(100, ($stats['personal']['calls'] / max(1, $stats['team_avg']['calls'])) * 50) }}%"></div>
                        </div>
                    </div>
                    <div class="metric-benchmark mb-4">
                        <div class="d-flex justify-content-between mb-3 align-items-end">
                            <span class="benchmark-label">Economic Impact</span>
                            <div class="text-right">
                                <span class="benchmark-val">₹{{ number_format($stats['personal']['fees']) }}</span>
                                <span class="d-block text-xs text-muted font-weight-bold">Avg: ₹{{ number_format($stats['team_avg']['fees']) }}</span>
                            </div>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: {{ min(100, ($stats['personal']['fees'] / max(1, $stats['team_avg']['fees'])) * 50) }}%"></div>
                        </div>
                    </div>
                    <div class="metric-benchmark">
                        <div class="d-flex justify-content-between mb-3 align-items-end">
                            <span class="benchmark-label">Success Rate</span>
                            <div class="text-right">
                                <span class="benchmark-val">{{ $stats['personal']['admissions'] }}</span>
                                <span class="d-block text-xs text-muted font-weight-bold">Avg: {{ round($stats['team_avg']['admissions'], 1) }}</span>
                            </div>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" style="width: {{ min(100, ($stats['personal']['admissions'] / max(1, $stats['team_avg']['admissions'])) * 50) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Analytics Area -->
        <div class="col-lg-8">
            <div class="row">
                <!-- Hourly Pulse Chart -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 1.5rem;">
                        <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-extrabold text-gray-800 mb-0 text-uppercase tracking-wider" style="font-size: 0.75rem;">Tactical Pulse <span class="text-xs text-muted font-weight-bold ml-2">(Hourly)</span></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="hourlyPulseChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Controls & Filters -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 1.5rem;">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <h6 class="font-weight-extrabold text-gray-800 small text-uppercase tracking-wider">Analysis Filter</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.staff-activity.show', $user->id) }}" method="GET">
                                <div class="row mb-3">
                                    <div class="col-6 pr-1"><input type="date" name="start_date" class="form-control form-control-sm border-light" value="{{ $startDate }}"></div>
                                    <div class="col-6 pl-1"><input type="date" name="end_date" class="form-control form-control-sm border-light" value="{{ $endDate }}"></div>
                                </div>
                                <div class="form-group mb-2">
                                    <select name="subject_type" class="form-control form-control-sm border-light">
                                        <option value="">All Systems</option>
                                        @foreach($availableModules as $m)
                                            <option value="{{ $m }}" {{ request('subject_type') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <select name="event" class="form-control form-control-sm border-light">
                                        <option value="">All Events</option>
                                        @foreach($availableEvents as $e)
                                            <option value="{{ $e }}" {{ request('event') == $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-primary btn-sm btn-block rounded-pill font-weight-bold shadow-sm py-2">Recalibrate Analytics</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Feed -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 2rem; overflow: hidden;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-extrabold text-gray-800 mb-0">Operational Timeline</h5>
                        <p class="text-xs text-muted mb-0">Authenticated telemetry feed</p>
                    </div>
                    <div class="badge badge-primary-soft px-3 py-2 font-weight-extrabold text-uppercase" style="font-size: 0.65rem;">ENTRIES: {{ $activities->total() }}</div>
                </div>
                <div class="card-body p-0">
                    <div class="timeline-scroll px-4 pt-4" style="max-height: 800px; overflow-y: auto;">
                        <ul class="timeline-advanced" id="ajaxTimelineTarget">
                            @include('admin.staff_activity._activity_item', ['activities' => $activities])
                        </ul>
                        @if($activities->hasMorePages())
                            <div class="text-center pb-5">
                                <button id="loadMoreActivities" class="btn btn-white btn-sm rounded-pill px-5 font-weight-extrabold shadow-sm transition-all border py-2">
                                    <i class="fas fa-history mr-2 text-primary"></i> Retrieve Earlier Telemetry
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-3">{{ $activities->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <script>
        const ctx = document.getElementById('hourlyPulseChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, 'rgba(78, 115, 223, 0.2)');
        gradient.addColorStop(1, 'rgba(78, 115, 223, 0)');

        const hourlyData = @json($hourlyMap);
        const labels = Array.from({length: 24}, (_, i) => `${i}:00`);
        const values = Array.from({length: 24}, (_, i) => hourlyData[i] || 0);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'System Actions',
                    data: values,
                    borderColor: '#4e73df',
                    backgroundColor: gradient,
                    borderWidth: 4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4e73df',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{ ticks: { fontColor: '#b7b9cc', fontSize: 10, maxTicksLimit: 12, fontStyle: 'bold' }, gridLines: { display: false } }],
                    yAxes: [{ ticks: { fontColor: '#b7b9cc', fontSize: 10, beginAtZero: true, stepSize: 5, fontStyle: 'bold' }, gridLines: { color: '#f8f9fc', drawBorder: false } }]
                },
                tooltips: { backgroundColor: '#fff', titleFontColor: '#4e73df', bodyFontColor: '#858796', borderColor: '#e3e6f0', borderWidth: 1, intersect: false, mode: 'index', xPadding: 15, yPadding: 15 }
            }
        });

        // AJAX Load More Logic
        let nextPage = 2;
        let hasMore = {{ $activities->hasMorePages() ? 'true' : 'false' }};
        const activeFilters = new URLSearchParams(window.location.search);

        document.getElementById('loadMoreActivities').addEventListener('click', function(e) {
            if (!hasMore) return;
            const btn = e.target.closest('button');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Querying Database...';

            activeFilters.set('page', nextPage);
            
            fetch(`${window.location.pathname}?${activeFilters.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('ajaxTimelineTarget').insertAdjacentHTML('beforeend', data.html);
                nextPage++;
                hasMore = data.hasMore;
                btn.disabled = !hasMore;
                btn.innerHTML = hasMore ? originalText : 'End of Tactical History';
                if (!hasMore) btn.classList.add('btn-light', 'text-muted');
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = 'Query Failure (Check Connection)';
            });
        });
    </script>
@endpush
