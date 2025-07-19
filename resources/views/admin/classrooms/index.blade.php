@extends('layouts.theme')

@section('title', 'Manage Classrooms')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Classrooms</h1>
    <a href="{{ route('admin.classrooms.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Classroom</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name (e.g., Room 101)</th>
                        <th>Capacity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classrooms as $classroom)
                        <tr>
                            <td>{{ $classroom->id }}</td>
                            <td>{{ $classroom->name }}</td>
                            <td>{{ $classroom->capacity }}</td>
                            <td>
                                <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('admin.classrooms.destroy', $classroom) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No classrooms found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection