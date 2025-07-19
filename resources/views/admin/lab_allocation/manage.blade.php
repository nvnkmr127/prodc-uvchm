@extends('layouts.theme')
@section('title', 'Manage Group Students')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Students for: <strong>{{ $group->name }}</strong></h1>
    <a href="{{ route('admin.lab-allocation.index', ['batch_id' => $group->batch_id]) }}" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Allocation Hub</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <!-- Students in this group -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Students In This Group ({{ $studentsInGroup->count() }} / {{ $group->classroom->capacity }})</h6></div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($studentsInGroup as $student)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $student->name }}
                            <form action="{{ route('admin.lab-allocation.group.remove', ['group' => $group, 'student' => $student]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-times"></i></button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item">This group is empty.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Unassigned students from the same batch -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Add Students from Batch</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.lab-allocation.group.add', $group) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Select a student to add to this group:</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">-- Select Unassigned Student --</option>
                            @foreach($unassignedStudents as $student)
                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success mt-2">Add Student</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
