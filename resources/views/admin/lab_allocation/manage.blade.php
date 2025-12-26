@extends('layouts.theme')
@section('title', 'Manage Group Students')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Manage Students for: <strong>{{ $group->name }}</strong></h1>
        <p class="text-muted mb-0">
            Lab: {{ $group->classroom->name }} | 
            Capacity: {{ $group->classroom->capacity }} students |
            @if($group->academicYear ?? false)
                Academic Year: {{ $group->academicYear->name }}
            @endif
        </p>
    </div>
    <a href="{{ route('admin.lab-allocation.index', ['batch_id' => $group->batch_id]) }}" 
       class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Allocation Hub
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <!-- Students Currently in This Group -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users me-2"></i>Students In This Group 
                    <span class="badge badge-primary">{{ $studentsInGroup->count() }} / {{ $group->classroom->capacity }}</span>
                </h6>
            </div>
            <div class="card-body">
                @if($studentsInGroup->count() > 0)
                    <div class="mb-3">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" 
                                 role="progressbar" 
                                 style="width: {{ ($studentsInGroup->count() / $group->classroom->capacity) * 100 }}%" 
                                 aria-valuenow="{{ $studentsInGroup->count() }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="{{ $group->classroom->capacity }}">
                            </div>
                        </div>
                        <small class="text-muted">
                            Utilization: {{ round(($studentsInGroup->count() / $group->classroom->capacity) * 100, 1) }}%
                        </small>
                    </div>
                @endif

                <div class="list-group">
                    @forelse($studentsInGroup as $student)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $student->name }}</h6>
                                <small class="text-muted">
                                    {{ $student->enrollment_number ?? 'No Enrollment' }}
                                    @if($student->student_mobile)
                                        | {{ $student->student_mobile }}
                                    @endif
                                </small>
                            </div>
                            <form action="{{ route('admin.lab-allocation.group.remove', ['group' => $group, 'student' => $student]) }}" 
                                  method="POST" 
                                  onsubmit="return confirm('Are you sure you want to remove {{ $student->name }} from this group?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Remove from group">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted">
                            <i class="fas fa-users fa-2x mb-2 text-gray-300"></i>
                            <p class="mb-0">This group is empty.</p>
                            <small>Use the panel on the right to add students.</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Add Students from Batch -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-user-plus me-2"></i>Add Students to Group
                    <span class="badge badge-success">{{ $unassignedStudents->count() }} Available</span>
                </h6>
            </div>
            <div class="card-body">
                @if($unassignedStudents->count() > 0)
                    <form action="{{ route('admin.lab-allocation.group.add', $group) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="student_id" class="form-label">
                                Select a student to add to this group:
                            </label>
                            <select name="student_id" id="student_id" class="form-control" required>
                                <option value="">-- Choose Unassigned Student --</option>
                                @foreach($unassignedStudents as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->name }}
                                        @if($student->enrollment_number)
                                            ({{ $student->enrollment_number }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Only students not assigned to any lab group for this academic year are shown.
                            </small>
                        </div>
                        
                        @if($studentsInGroup->count() >= $group->classroom->capacity)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This lab is at full capacity. You can still add students, but it will exceed the recommended capacity.
                            </div>
                        @endif
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Student to Group
                        </button>
                    </form>
                @else
                    <div class="text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 text-gray-300"></i>
                        <p class="mb-0">All students from this batch are already assigned to groups.</p>
                        <small>Great job! Everyone has been allocated to a lab group.</small>
                    </div>
                @endif
                
                <!-- Quick Stats -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="text-muted">Quick Stats</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-right">
                                <h4 class="text-primary">{{ $group->batch->students()->where('status', 'active')->count() }}</h4>
                                <small class="text-muted">Total in Batch</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-right">
                                <h4 class="text-success">{{ $studentsInGroup->count() }}</h4>
                                <small class="text-muted">In This Group</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h4 class="text-warning">{{ $unassignedStudents->count() }}</h4>
                            <small class="text-muted">Unassigned</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Group Information -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-info-circle me-2"></i>Group Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Group Name:</strong><br>
                        <span class="text-muted">{{ $group->name }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Lab/Classroom:</strong><br>
                        <span class="text-muted">{{ $group->classroom->name }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Batch:</strong><br>
                        <span class="text-muted">{{ $group->batch->name }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Course:</strong><br>
                        <span class="text-muted">{{ $group->batch->course->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-right {
    border-right: 1px solid #e3e6f0 !important;
}

.list-group-item {
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background-color: #f8f9fc;
}

.progress {
    background-color: #f8f9fc;
}

.badge {
    font-size: 0.7em;
}

@media (max-width: 768px) {
    .border-right {
        border-right: none !important;
        border-bottom: 1px solid #e3e6f0 !important;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
}
</style>
@endpush