@extends('layouts.theme')
@section('title', 'Staff Activity Tracker')

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
            height: 100%;
        }

        .activity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            margin-right: 1rem;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }

        .stat-mini-pill {
            padding: 0.5rem 0.75rem;
            border-radius: 0.75rem;
            background: rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: #4b5563;
        }

        .stat-icon {
            width: 28px;
            height: 28px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .icon-calls { background: #e0e7ff; color: #4338ca; }
        .icon-fee { background: #dcfce7; color: #15803d; }
        .icon-admissions { background: #fef9c3; color: #a16207; }
        .icon-pending { background: #fee2e2; color: #b91c1c; }

        .timeline-container {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
            border-left: 2px solid #e5e7eb;
        }

        .timeline-item:last-child {
            border-left-color: transparent ;
        }

        .timeline-dot {
            position: absolute;
            left: -7px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4e73df;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #4e73df;
        }

        .date-badge {
            background: #f3f4f6;
            color: #374151;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 700;
            border: 1px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .user-avatar {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Staff Activity Tracker</h1>
            <p class="text-muted small mb-0">Monitoring administrative performance and daily logging</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <form action="{{ route('admin.staff-activity.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm border-0 shadow-sm rounded-pill px-3" onchange="this.form.submit()">
            </form>
            <div class="date-badge shadow-sm">
                <i class="fas fa-calendar-day mr-2 text-primary"></i>
                {{ \Carbon\Carbon::parse($date)->format('D, M d, Y') }}
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 border-left-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Calls TodaY</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ collect($activitiesByStaff)->sum('calls_count') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-phone-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 border-left-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Fee Collected</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format(collect($activitiesByStaff)->sum('fee_collected'), 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-indian-rupee-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 border-left-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">New Admissions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ collect($activitiesByStaff)->sum('admissions_count') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2 border-left-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Follow-ups</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ collect($activitiesByStaff)->sum('pending_tasks') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Staff Performance Sidebar -->
        <div class="col-lg-8">
            <h5 class="font-weight-bold mb-3">Staff Performance Overview</h5>
            <div class="row">
                @foreach($activitiesByStaff as $userId => $data)
                    <div class="col-md-6 mb-4">
                        <div class="activity-card p-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="user-avatar">
                                    {{ strtoupper(substr($data['user']->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h6 class="font-weight-bold mb-0 text-gray-800">{{ $data['user']->name }}</h6>
                                    <span class="badge badge-pill badge-light text-muted small">{{ ucfirst($data['user']->roles->first()?->name ?? 'Staff') }}</span>
                                </div>
                                <div class="ml-auto">
                                    <a href="{{ route('admin.staff-activity.show', $userId) }}?date={{ $date }}" class="btn btn-sm btn-light rounded-circle shadow-sm">
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-6 mb-3">
                                    <div class="stat-mini-pill">
                                        <div class="stat-icon icon-calls"><i class="fas fa-phone-alt"></i></div>
                                        <span>{{ $data['calls_count'] }} <span class="text-muted font-weight-normal ml-1">Calls</span></span>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-mini-pill">
                                        <div class="stat-icon icon-fee"><i class="fas fa-receipt"></i></div>
                                        <span>{{ $data['payments_count'] }} <span class="text-muted font-weight-normal ml-1">Fees</span></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-pill">
                                        <div class="stat-icon icon-admissions"><i class="fas fa-user-plus"></i></div>
                                        <span>{{ $data['admissions_count'] }} <span class="text-muted font-weight-normal ml-1">Adm.</span></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-pill">
                                        <div class="stat-icon icon-pending"><i class="fas fa-hourglass-half"></i></div>
                                        <span>{{ $data['pending_tasks'] }} <span class="text-muted font-weight-normal ml-1">Pend.</span></span>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4 opacity-5">

                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Amount Collected:</span>
                                <span class="font-weight-bold text-success">₹{{ number_format($data['fee_collected'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Global Activity Timeline -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm border-0" style="border-radius: 1.25rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="font-weight-bold text-gray-800">Live Activity Feed</h5>
                    <p class="small text-muted mb-0">Real-time system events</p>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-container">
                        @forelse($timeline as $activity)
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="small text-muted mb-1">{{ $activity->created_at->format('h:i A') }}</div>
                                <div class="font-weight-bold text-gray-800 small">{{ $activity->causer->name ?? 'System' }}</div>
                                <div class="text-muted small mt-1">{{ $activity->description }}</div>
                                @if(isset($activity->properties['attributes']))
                                    <div class="mt-2 text-xs bg-light p-2 rounded">
                                        @foreach($activity->properties['attributes'] as $key => $value)
                                            @if(!is_array($value))
                                                <span class="mr-2"><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-ghost fa-3x text-gray-200 mb-3"></i>
                                <p class="text-muted">No activities recorded for this date</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
