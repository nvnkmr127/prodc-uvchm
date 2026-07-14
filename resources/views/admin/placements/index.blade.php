@extends('layouts.placement')

@section('title', 'Job Placements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Job Placements</h1>
            <p class="text-muted mb-0">Manage and export student placement records.</p>
        </div>
        <div>
            <a href="{{ route('placement-portal.export', request()->all()) }}" class="btn btn-success">
                <i class="fas fa-file-excel mr-1"></i> Export Report
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form action="{{ route('placement-portal.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Search students..." value="{{ request('search') }}">
                </div>
                <div class="form-group mr-2">
                    <select name="course_id" class="form-control">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2">
                    <select name="batch_id" class="form-control">
                        <option value="">All Batches</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2">
                    <select name="placement_status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="Not Placed" {{ request('placement_status') == 'Not Placed' ? 'selected' : '' }}>Not Placed</option>
                        <option value="Training" {{ request('placement_status') == 'Training' ? 'selected' : '' }}>Training</option>
                        <option value="Internship" {{ request('placement_status') == 'Internship' ? 'selected' : '' }}>Internship</option>
                        <option value="Job" {{ request('placement_status') == 'Job' ? 'selected' : '' }}>Job</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('placement-portal.index') }}" class="btn btn-secondary ml-2">Clear</a>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Student</th>
                            <th>Contact</th>
                            <th>Batch</th>
                            <th>Placement Status</th>
                            <th>Company / Placed At</th>
                            <th>Designation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $student->photo_url }}" class="rounded-circle mr-2" width="40" height="40">
                                        <div>
                                            <div class="font-weight-bold">{{ $student->name }}</div>
                                            <small class="text-muted">{{ $student->enrollment_number }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="fas fa-phone fa-sm mr-1"></i> {{ $student->student_mobile }}</div>
                                    @if($student->email)
                                    <div><i class="fas fa-envelope fa-sm mr-1"></i> {{ $student->email }}</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $student->batch->name ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $student->batch->course->name ?? '' }}</small>
                                </td>
                                <td>
                                    @if($student->placement_status == 'Job')
                                        <span class="badge badge-success">Job</span>
                                    @elseif($student->placement_status == 'Internship')
                                        <span class="badge badge-info">Internship</span>
                                    @elseif($student->placement_status == 'Training')
                                        <span class="badge badge-warning">Training</span>
                                    @else
                                        <span class="badge badge-secondary">Not Placed</span>
                                    @endif
                                </td>
                                <td>{{ $student->placed_at ?? '-' }}</td>
                                <td>{{ $student->placement_designation ?? '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#placementModal{{ $student->id }}">
                                        Update
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal -->
                            <div class="modal fade" id="placementModal{{ $student->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Placement for {{ $student->name }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="{{ route('placement-portal.update', $student) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Placement Status</label>
                                                    <select name="placement_status" class="form-control" required>
                                                        <option value="Not Placed" {{ $student->placement_status == 'Not Placed' ? 'selected' : '' }}>Not Placed</option>
                                                        <option value="Training" {{ $student->placement_status == 'Training' ? 'selected' : '' }}>Training</option>
                                                        <option value="Internship" {{ $student->placement_status == 'Internship' ? 'selected' : '' }}>Internship</option>
                                                        <option value="Job" {{ $student->placement_status == 'Job' ? 'selected' : '' }}>Job</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Company / Placed At</label>
                                                    <input type="text" name="placed_at" class="form-control" value="{{ $student->placed_at }}" placeholder="e.g. Google, TCS, Local Hospital">
                                                </div>
                                                <div class="form-group">
                                                    <label>Designation</label>
                                                    <input type="text" name="placement_designation" class="form-control" value="{{ $student->placement_designation }}" placeholder="e.g. Software Engineer, Trainee">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No student records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
