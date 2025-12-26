@extends('layouts.theme')

@section('title', 'Attendance Details')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-eye text-primary"></i> Attendance Details
        </h1>
        <div class="d-sm-flex">
            <a href="{{ route('attendance.index') }}" class="btn btn-secondary btn-sm mr-2">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            @can('edit attendance')
                <a href="{{ route('attendance.edit', $attendance) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        {{-- Main Details --}}
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-2"></i>Attendance Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Date:</th>
                                    <td>{{ $attendance->attendance_date ? \Carbon\Carbon::parse($attendance->attendance_date)->format('l, F d, Y') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge badge-{{ $attendance->status_color }} badge-lg">
                                            <i class="fas {{ $attendance->status === 'present' ? 'fa-check-circle' : ($attendance->status === 'absent' ? 'fa-times-circle' : ($attendance->status === 'late' ? 'fa-clock' : 'fa-question-circle')) }} mr-1"></i>
                                            {{ $attendance->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                @if($attendance->status === 'late' && $attendance->late_minutes)
                                <tr>
                                    <th>Late Duration:</th>
                                    <td>{{ $attendance->late_minutes }} minutes</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Subject:</th>
                                    <td>{{ $attendance->subject->name ?? 'General Attendance' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Marked By:</th>
                                    <td>{{ $attendance->faculty->name ?? 'System' }}</td>
                                </tr>
                                <tr>
                                    <th>Marked At:</th>
                                    <td>{{ $attendance->marked_at ? $attendance->marked_at->format('M d, Y H:i A') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Created:</th>
                                    <td>{{ $attendance->created_at->format('M d, Y H:i A') }}</td>
                                </tr>
                                @if($attendance->updated_at != $attendance->created_at)
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>{{ $attendance->updated_at->format('M d, Y H:i A') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($attendance->notes)
                    <div class="mt-3">
                        <h6 class="font-weight-bold">Notes:</h6>
                        <div class="bg-light p-3 rounded">
                            {{ $attendance->notes }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Student Details Sidebar --}}
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user mr-2"></i>Student Information
                    </h6>
                </div>
                <div class="card-body text-center">
                    @if($attendance->student && $attendance->student->profile_photo)
                        <img src="{{ asset('storage/' . $attendance->student->profile_photo) }}" 
                             alt="Student Photo" class="rounded-circle mb-3" 
                             style="width: 100px; height: 100px; object-fit: cover;">
                    @else
                        <div class="bg-gray-200 rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto" 
                             style="width: 100px; height: 100px;">
                            <i class="fas fa-user fa-3x text-gray-400"></i>
                        </div>
                    @endif

                    <h5 class="font-weight-bold">{{ $attendance->student->name ?? 'Unknown Student' }}</h5>
                    <p class="text-muted mb-1">{{ $attendance->student->email ?? 'No Email' }}</p>
                    <p class="text-muted">
                        <span class="badge badge-light">{{ $attendance->student->enrollment_number ?? 'N/A' }}</span>
                    </p>

                    <hr>
                    
                    <div class="text-left">
                        <strong>Batch:</strong> {{ $attendance->batch->name ?? 'No Batch' }}<br>
                        @if($attendance->batch && $attendance->batch->course)
                            <strong>Course:</strong> {{ $attendance->batch->course->name }}<br>
                        @endif
                        <strong>Phone:</strong> {{ $attendance->student->phone ?? 'N/A' }}<br>
                        <strong>Parent Phone:</strong> {{ $attendance->student->parent_phone ?? 'N/A' }}
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('admin.students.show', $attendance->student_id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>

            {{-- Attendance History --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-2"></i>Recent History
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $recentAttendances = \App\Models\Attendance\Attendance::where('student_id', $attendance->student_id)
                            ->where('id', '!=', $attendance->id)
                            ->orderBy('attendance_date', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp

                    @if($recentAttendances->count() > 0)
                        @foreach($recentAttendances as $recent)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <small class="text-muted">{{ $recent->attendance_date ? \Carbon\Carbon::parse($recent->attendance_date)->format('M d') : 'N/A' }}</small>
                                </div>
                                <div>
                                    <span class="badge badge-{{ $recent->status_color }}">
                                        {{ $recent->status_label }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('attendance.analytics.student', $attendance->student_id) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-chart-line"></i> View Analytics
                            </a>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-info-circle"></i>
                            <p class="mb-0">No recent history</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection