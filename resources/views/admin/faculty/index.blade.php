@extends('layouts.theme')
@section('title', 'Manage Faculty')
@section('content')

{{-- ADD THIS NEW HEADER SECTION --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Faculty</h1>
    <a href="{{ route('admin.faculty.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Faculty</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Faculty Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faculties as $faculty)
                        <tr>
                            <td>{{ $faculty->name }}</td>
                            <td>{{ $faculty->email }}</td>
                            <td>
                                <a href="{{ route('admin.faculty.subjects.edit', $faculty) }}" class="btn btn-info btn-sm">Manage Subjects</a>
                                    <a href="{{ route('admin.faculty.salary.show', $faculty) }}" class="btn btn-success btn-sm">Manage Salary</a>

                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No staff found. Please create a user with the 'staff' role.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection