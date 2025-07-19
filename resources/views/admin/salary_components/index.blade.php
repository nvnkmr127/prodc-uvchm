@extends('layouts.theme')
@section('title', 'Salary Components')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Salary Components</h1>
    <a href="{{ route('admin.salary-components.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Component</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Component Name</th><th>Type</th><th style="width: 15%;">Actions</th></tr></thead>
                <tbody>
                    @forelse ($components as $component)
                        <tr>
                            <td>{{ $component->name }}</td>
                            <td>
                                <span class="badge badge-{{ $component->type == 'Earning' ? 'success' : 'danger' }}">{{ $component->type }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.salary-components.edit', $component) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.salary-components.destroy', $component) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No salary components defined yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection