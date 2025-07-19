@extends('layouts.theme')
@section('title', 'Manage ID Card Templates')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage ID Card Templates</h1>
    <a href="{{ route('admin.id-card-templates.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Template</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Available Templates</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Template Name</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>
                                <a href="{{ route('admin.id-card-templates.edit', $template) }}" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.id-card-templates.destroy', $template) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">No ID card templates found. Add one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
