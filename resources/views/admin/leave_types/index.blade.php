@extends('layouts.theme')
@section('title', 'Manage Leave Types')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Leave Types</h1>
    <a href="{{ route('admin.leave-types.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Leave Type</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Leave Name</th><th>Days Allotted per Year</th><th style="width: 15%;">Actions</th></tr></thead>
                <tbody>
                    @forelse ($leaveTypes as $type)
                        <tr>
                            <td>{{ $type->name }}</td>
                            <td>{{ $type->days_per_year }}</td>
                            <td>
                                <a href="{{ route('admin.leave-types.edit', $type) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.leave-types.destroy', $type) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No leave types defined yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection