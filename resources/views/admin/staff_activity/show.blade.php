@extends('layouts.theme')
@section('title', 'Staff Performance Deep-Dive')

@push('styles')
    <style>
        .staff-profile-card {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white; border-radius: 1.5rem; overflow: hidden; position: relative;
        }
        .staff-profile-card::after {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 150px; height: 150px; background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .activity-pill {
            background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem; padding: 0.5rem 1rem; color: white;
        }
        .timeline-advanced { list-style: none; padding-left: 1.5rem; position: relative; }
        .timeline-advanced::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 2px; background: #e3e6f0;
        }
        .timeline-item { position: relative; margin-bottom: 2rem; }
        .timeline-dot {
            position: absolute; left: -1.9rem; top: 0; width: 12px; height: 12px;
            border-radius: 50%; background: #4e73df; border: 3px solid white;
            box-shadow: 0 0 0 2px #4e73df;
        }
        .metric-benchmark {
            background: #f8f9fc; border-radius: 1rem; padding: 1.25rem;
            position: relative; overflow: hidden;
        }
        .metric-benchmark .progress { height: 6px; border-radius: 10px; }
        .benchmark-label { font-size: 0.7rem; font-weight: 800; color: #858796; }
        .benchmark-val { font-size: 1.1rem; font-weight: 800; color: #4e73df; }
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
            <div class="card staff-profile-card shadow-lg mb-4 border-0">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="mr-3 shadow-lg" style="width: 85px; height: 85px; border-radius: 1.5rem; background: white; color: #4e73df; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 900;">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-weight-bold mb-1">{{ $user->name }}</h4>
                            <p class="mb-0 opacity-75 font-weight-bold text-uppercase small">{{ $user->roles->first()->name ?? 'Counselor' }}</p>
                            <span class="badge badge-pill badge-light mt-1 px-2 py-1 text-primary small font-weight-bold" style="font-size: 0.65rem;">UID: #{{ $user->id }}</span>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6 pr-1">
                            <div class="activity-pill text-center">
                                <span class="small opacity-75 d-block text-uppercase" style="font-size: 0.6rem;">Total Period Actions</span>
                                <span class="h5 font-weight-bold mb-0">{{ $activities->total() }}</span>
                            </div>
                        </div>
                        <div class="col-6 pl-1">
                            <div class="activity-pill text-center">
                                <span class="small opacity-75 d-block text-uppercase" style="font-size: 0.6rem;">First Action Time</span>
                                <span class="h5 font-weight-bold mb-0">{{ $activities->last()?->created_at->format('H:i') ?: '--:--' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded border border-light" style="background: rgba(255,255,255,0.05);">
                         <h6 class="small font-weight-bold text-uppercase mb-3 opacity-75">Work Cycle Context</h6>
                         <div class="d-flex justify-content-between mb-2">
                             <span class="small font-weight-bold">Module Focus</span>
                             <span class="small">{{ array_key_first($activityDistribution) ?: 'Universal' }}</span>
                         </div>
                         <div class="d-flex justify-content-between">
                             <span class="small font-weight-bold">Peak Hourly Activity</span>
                             <span class="small">{{ max(array_values($hourlyMap) ?: [0]) }} ops/hr</span>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Team Benchmarking Widget -->
            <div class="card shadow-sm border-0 mb-4 rounded-xl" style="border-radius: 1.25rem;">
                <div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="font-weight-bold text-primary text-uppercase small">Benchmarking vs Team Avg</h6></div>
                <div class="card-body">
                    <div class="metric-benchmark mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="benchmark-label">Calls Made</span>
                            <span class="benchmark-val">{{ $stats['personal']['calls'] }} <small class="text-muted">vs {{ round($stats['team_avg']['calls'], 1) }}</small></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" style="width: {{ min(100, ($stats['personal']['calls'] / max(1, $stats['team_avg']['calls'])) * 50) }}%"></div>
                        </div>
                    </div>
                    <div class="metric-benchmark mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="benchmark-label">Revenue Collected</span>
                            <span class="benchmark-val">₹{{ number_format($stats['personal']['fees']) }}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: {{ min(100, ($stats['personal']['fees'] / max(1, $stats['team_avg']['fees'])) * 50) }}%"></div>
                        </div>
                        <span class="text-xs text-muted font-weight-bold">Team Avg: ₹{{ number_format($stats['team_avg']['fees']) }}</span>
                    </div>
                    <div class="metric-benchmark">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="benchmark-label">Admissions</span>
                            <span class="benchmark-val">{{ $stats['personal']['admissions'] }} <small class="text-muted">vs {{ round($stats['team_avg']['admissions'], 1) }}</small></span>
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
                    <div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 1.25rem;">
                        <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold text-gray-800 mb-0">Activity Pulse <span class="text-xs text-muted">(Hourly Intensity)</span></h6>
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
                    <div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 1.25rem;">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0"><h6 class="font-weight-bold text-gray-800 small text-uppercase">Analysis Filter</h6></div>
                        <div class="card-body">
                            <form action="{{ route('admin.staff-activity.show', $user->id) }}" method="GET">
                                <div class="row mb-3">
                                    <div class="col-6 pr-1"><input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}"></div>
                                    <div class="col-6 pl-1"><input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}"></div>
                                </div>
                                <div class="form-group mb-2">
                                    <select name="subject_type" class="form-control form-control-sm">
                                        <option value="">All Systems</option>
                                        @foreach($availableModules as $m)
                                            <option value="{{ $m }}" {{ request('subject_type') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <select name="event" class="form-control form-control-sm">
                                        <option value="">All Events</option>
                                        @foreach($availableEvents as $e)
                                            <option value="{{ $e }}" {{ request('event') == $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-primary btn-sm btn-block rounded-pill">Refresh Data</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Feed -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 1.5rem; overflow: hidden;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="font-weight-bold text-gray-800 mb-0">Operational Timeline</h5>
                    <div class="small text-muted font-weight-bold">Total entries: {{ $activities->total() }}</div>
                </div>
                <div class="card-body p-0">
                    <div class="timeline-scroll px-4 pt-4" style="max-height: 800px;">
                        <ul class="timeline-advanced" id="ajaxTimelineTarget">
                            @include('admin.staff_activity._activity_item', ['activities' => $activities])
                        </ul>
                        @if($activities->hasMorePages())
                            <div class="text-center pb-4">
                                <button id="loadMoreActivities" class="btn btn-primary-soft btn-sm rounded-pill px-4 font-weight-bold shadow-sm transition-all border">
                                    <i class="fas fa-plus-circle mr-1"></i> Load Older History
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
                    borderWidth: 3,
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
                    xAxes: [{ ticks: { fontColor: '#858796', fontSize: 10, maxTicksLimit: 12 }, gridLines: { display: false } }],
                    yAxes: [{ ticks: { fontColor: '#858796', fontSize: 10, beginAtZero: true, stepSize: 5 }, gridLines: { color: '#f8f9fc' } }]
                },
                tooltips: { backgroundColor: '#fff', titleFontColor: '#4e73df', bodyFontColor: '#858796', borderColor: '#e3e6f0', borderWidth: 1, intersect: false, mode: 'index' }
            }
        });

        // AJAX Load More Logic
        let nextPage = 2;
        let hasMore = {{ $activities->hasMorePages() ? 'true' : 'false' }};
        const activeFilters = new URLSearchParams(window.location.search);

        document.getElementById('loadMoreActivities').addEventListener('click', function(e) {
            if (!hasMore) return;
            const btn = e.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Loading Data...';

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
                btn.innerHTML = hasMore ? originalText : 'End of Timeline reached';
                if (!hasMore) btn.classList.add('btn-light', 'text-muted');
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = 'Error Loading History';
            });
        });
    </script>
@endpush
