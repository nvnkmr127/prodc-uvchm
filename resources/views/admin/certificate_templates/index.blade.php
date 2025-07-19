@extends('layouts.theme')
@section('title', 'Certificate Templates')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Certificate Templates</h1>
    <a href="{{ route('admin.certificate-templates.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Template</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Template Name</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($templates as $template)
                    <tr>
                        <td>{{ $template->name }}</td>
                        <td>
                            <a href="{{ route('admin.certificate-templates.edit', $template) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.certificate-templates.destroy', $template) }}" method="POST" class="d-inline"> @csrf @method('DELETE') <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center">No certificate templates found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection