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
        .action-card {
            border: none; border-radius: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .action-card:hover { transform: translateX(5px); }
        .icon-box {
            width: 40px; height: 40px; border-radius: 10px; display: flex;
            align-items: center; justify-content: center; font-size: 1.1rem;
        }
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
                    <li class="breadcrumb-item active" aria-current="page">Staff Performance</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Performance Deep-Dive</h1>
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

    <div class="row">
        <!-- Staff Sidebar -->
        <div class="col-lg-4">
            <div class="card staff-profile-card shadow mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="mr-3" style="width: 80px; height: 80px; border-radius: 1.5rem; background: white; color: #4e73df; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 900; box-shadow: 0 8px 20px rgba(0,0,0,0.1);">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-weight-bold mb-1">{{ $user->name }}</h4>
                            <p class="mb-0 opacity-75 font-weight-bold text-uppercase small">{{ $user->roles->first()->name ?? 'Counselor' }}</p>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap mb-4">
                        <div class="activity-pill">
                            <span class="small opacity-75 d-block">First Action</span>
                            <span class="font-weight-bold">{{ $activities->last()?->created_at->format('M d, H:i') ?: '--:--' }}</span>
                        </div>
                        <div class="activity-pill">
                            <span class="small opacity-75 d-block">Last Action</span>
                            <span class="font-weight-bold">{{ $activities->first()?->created_at->format('M d, H:i') ?: '--:--' }}</span>
                        </div>
                    </div>

                    <div class="p-3 rounded" style="background: rgba(255,255,255,0.1);">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small font-weight-bold">Total Actions in Period</span>
                            <span class="badge badge-pill badge-light text-primary">{{ $activities->total() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0"><h6 class="font-weight-bold text-gray-800">Advanced Filter</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.staff-activity.show', $user->id) }}" method="GET">
                        <div class="row">
                            <div class="col-6 pr-1">
                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-muted">From</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm border-light rounded px-2" value="{{ $startDate }}">
                                </div>
                            </div>
                            <div class="col-6 pl-1">
                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-muted">To</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm border-light rounded px-2" value="{{ $endDate }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted">Action Class</label>
                            <select name="event" class="form-control form-control-sm border-light rounded-pill px-3">
                                <option value="">All Interaction Types</option>
                                @foreach($availableEvents as $e)
                                    <option value="{{ $e }}" {{ request('event') == $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-4">
                            <label class="small font-weight-bold text-muted">System Module</label>
                            <select name="subject_type" class="form-control form-control-sm border-light rounded-pill px-3">
                                <option value="">Global System Actions</option>
                                @foreach($availableModules as $m)
                                    <option value="{{ $m }}" {{ request('subject_type') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm btn-block rounded-pill py-2 shadow-sm">Refine Dataset</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 1.5rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="font-weight-bold text-gray-800">Session Activity Logs</h5>
                    <div class="small text-muted font-weight-bold">Showing entries {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }}</div>
                </div>
                <div class="card-body p-4">
                    <ul class="timeline-advanced">
                        @forelse($activities as $activity)
                            <li class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="card action-card">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-start">
                                            <div class="icon-box mr-3 {{ $activity->event == 'created' ? 'bg-success-soft text-success' : 'bg-primary-soft text-primary' }}" style="background: rgba(0,0,0,0.03);">
                                                <i class="fas {{ $activity->event == 'created' ? 'fa-plus-circle' : 'fa-edit' }}"></i>
                                            </div>
                                            <div style="flex: 1;">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="font-weight-bold text-gray-900">{{ $activity->description }}</span>
                                                    <span class="text-muted small font-weight-bold">{{ $activity->created_at->format('h:i A') }}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge badge-light py-1 px-2 border small" style="font-size: 0.65rem;">
                                                        <i class="fas fa-layer-group mr-1"></i> {{ class_basename($activity->subject_type) }}
                                                    </span>
                                                    <span class="text-muted small">#ID: {{ $activity->subject_id }}</span>
                                                </div>

                                                @if(isset($activity->properties['attributes']))
                                                    <div class="mt-3 p-2 bg-light rounded small border">
                                                        <table class="table table-sm table-borderless mb-0" style="font-size: 0.75rem;">
                                                            @foreach($activity->properties['attributes'] as $key => $value)
                                                                @if(!is_array($value) && !in_array($key, ['created_at', 'updated_at', 'id']))
                                                                    <tr>
                                                                        <td class="text-muted font-weight-bold px-0 py-1" style="width: 100px;">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                                        <td class="font-weight-bold text-gray-800 py-1">{{ $value ?: 'Null' }}</td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-gray-200 mb-3"></i>
                                <p class="text-muted">No activities found for this selection.</p>
                            </div>
                        @endforelse
                    </ul>
                    
                    <div class="mt-4">
                        {{ $activities->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
