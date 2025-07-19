@extends('layouts.theme')
@section('title', 'Role Details')

@push('styles')
<style>
    /* Custom styles for a cleaner details page */
    .details-card .card-body strong {
        display: block;
        color: #4e73df;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .details-card .card-body p {
        margin-bottom: 1.25rem;
    }
    .permission-group {
        border-left: 3px solid #1cc88a;
        transition: all 0.2s ease-in-out;
    }
    .permission-group:hover {
        border-left-width: 5px;
        background-color: #f8f9fc;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-user-shield text-gray-400 mr-2"></i>Role Details: <span class="text-primary">{{ $role->name }}</span>
    </h1>
    <div>
        @can('edit roles')
            <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50 mr-1"></i> Edit Role
            </a>
        @endcan
        <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Roles
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow mb-4 details-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Role Information</h6>
            </div>
            <div class="card-body">
                <strong>Name:</strong>
                <p><span class="badge badge-primary">{{ $role->name }}</span></p>

                <strong>Description:</strong>
                <p class="text-muted">{{ $role->description ?? 'No description provided.' }}</p>

                <strong>Users with this Role:</strong>
                <p><span class="badge badge-info">{{ $role->users->count() }} Users</span></p>

                <strong>Assigned Permissions:</strong>
                <p><span class="badge badge-success">{{ $role->permissions->count() }} Permissions</span></p>

                <strong>Created At:</strong>
                <p class="mb-0"><small>{{ $role->created_at->format('M d, Y, h:i A') }}</small></p>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Users with this Role</h6>
            </div>
            <div class="card-body">
                @forelse($role->users as $user)
                    <div class="d-flex align-items-center @if(!$loop->last) mb-3 @endif">
                        <i class="fas fa-user-circle fa-2x text-gray-400 mr-3"></i>
                        <div>
                            {{ $user->name }}
                            <small class="d-block text-muted">{{ $user->email }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted">No users are assigned to this role.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Assigned Permissions</h6>
            </div>
            <div class="card-body">
                @if($groupedPermissions && count($groupedPermissions) > 0)
                    <div class="row">
                        @foreach($groupedPermissions as $module => $permissions)
                            <div class="col-md-6 mb-3">
                                <div class="card permission-group h-100">
                                    <div class="card-body py-3 px-3">
                                        <h6 class="font-weight-bold text-success text-uppercase mb-3 small">
                                            {{ Str::title(str_replace('_', ' ', $module)) }}
                                        </h6>
                                        @foreach($permissions as $permission)
                                            <div class="mb-1">
                                                <i class="fas fa-check-circle text-success fa-sm mr-1"></i>
                                                <span>{{ Str::title(str_replace('-', ' ', $permission->name)) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-lock fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">No permissions have been assigned to this role.</p>
                        @can('edit roles')
                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i> Assign Permissions
                            </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection