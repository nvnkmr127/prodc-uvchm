@extends('layouts.theme')
@section('title', 'Staff Activity Detail')

@push('styles')
    <style>
        .timeline-item {
            position: relative;
            padding-left: 2.5rem;
            padding-bottom: 2rem;
            border-left: 2px solid #e9ecef;
        }
        .timeline-item:last-child {
            border-left-color: transparent ;
        }
        .timeline-marker {
            position: absolute;
            left: -9px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #4e73df;
            border: 3px solid white;
            box-shadow: 0 0 0 1px #4e73df;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('admin.staff-activity.index', ['date' => $date]) }}" class="btn btn-sm btn-link text-muted p-0 mb-2">
            <i class="fas fa-arrow-left mr-1"></i> Back to All Staff
        </a>
        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">{{ $user->name }}'s Activity</h1>
        <p class="text-muted small">{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="font-weight-bold text-gray-800">Detailed Action Log</h5>
                </div>
                <div class="card-body p-4">
                    @forelse($activities as $activity)
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge badge-primary mr-2">{{ $activity->created_at->format('h:i:s A') }}</span>
                                <span class="font-weight-bold text-gray-800">{{ $activity->description }}</span>
                            </div>
                            <div class="bg-light p-3 rounded-lg border-0 small mt-2">
                                <div class="mb-1"><strong>Action:</strong> {{ ucfirst($activity->event) }}</div>
                                <div class="mb-1"><strong>Module:</strong> {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</div>
                                @if(isset($activity->properties['attributes']))
                                    <div class="mt-2 border-top pt-2">
                                        <div class="small text-muted mb-1 font-weight-bold">Changes:</div>
                                        @foreach($activity->properties['attributes'] as $key => $value)
                                            @if(!is_array($value))
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                    <span class="font-weight-bold text-gray-700">{{ $value ?: 'N/A' }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                
                                @if(isset($activity->properties['old']))
                                    <div class="mt-2 border-top pt-2">
                                        <div class="small text-danger mb-1 font-weight-bold">Previous Values:</div>
                                        @foreach($activity->properties['old'] as $key => $value)
                                            @if(!is_array($value))
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted small">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                    <span class="text-gray-600 small">{{ $value ?: 'N/A' }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-gray-200 mb-3"></i>
                            <p class="text-muted">No specific activities logged for this day.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
                <div class="card-body p-4">
                    <h5 class="font-weight-bold text-gray-800 mb-4">Staff Profile</h5>
                    <div class="text-center mb-4">
                        <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem; border-radius: 50%; background: #4e73df; color: white; display: flex; align-items: center; justify-content: center;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <h5 class="font-weight-bold mb-1">{{ $user->name }}</h5>
                        <p class="text-muted small mb-0">{{ $user->email }}</p>
                        <span class="badge badge-pill badge-info px-3 py-2 mt-2">{{ $user->roles->first()?->name }}</span>
                    </div>
                    
                    <div class="border-top pt-4">
                        <h6 class="font-weight-bold text-gray-800 mb-3">Today's Summary</h6>
                        <div class="list-group list-group-flush border-0">
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-2">
                                <span class="text-muted small">Total Actions:</span>
                                <span class="font-weight-bold text-primary">{{ $activities->count() }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-2">
                                <span class="text-muted small">First Action:</span>
                                <span class="font-weight-bold">{{ $activities->last()?->created_at->format('h:i A') ?: 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-2">
                                <span class="text-muted small">Last Action:</span>
                                <span class="font-weight-bold">{{ $activities->first()?->created_at->format('h:i A') ?: 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
