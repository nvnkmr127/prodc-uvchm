@extends('layouts.theme')
@section('title', 'Manage Permissions')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Permissions</h1>
    <a href="{{ route('admin.permissions.create') }}" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Permission
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="row">
    @forelse ($groupedPermissions as $group => $permissions)
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ Str::title($group) }} Management</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach ($permissions as $permission)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-key fa-fw text-gray-400 mr-2"></i>{{ $permission->name }}
                        </span>
                        <div>
                            <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn-warning py-0 px-1"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger py-0 px-1" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                <p>No permissions found. Get started by adding one.</p>
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection
