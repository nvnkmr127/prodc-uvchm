@extends('layouts.theme')
@section('title', 'Salary Templates')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Salary Templates</h1>
    <a href="{{ route('admin.salary-templates.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Template</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr>
                    <th>Template Name</th>
                    <th>Description</th>
                    <th>Components Count</th>
                    <th style="width: 15%;">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>{{ $template->description }}</td>
                            <td>{{ $template->components_count }}</td>
                            <td>
                                <a href="{{ route('admin.salary-templates.edit', $template) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.salary-templates.destroy', $template) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No salary templates defined yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
