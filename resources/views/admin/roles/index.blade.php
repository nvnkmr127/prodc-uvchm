@extends('layouts.theme')
@section('title', 'Roles Management')

@push('styles')
<style>
    /* Simple animation for rows */
    .table-row-hover:hover {
        background-color: #f8f9fc;
        transition: background-color 0.2s ease-in-out;
    }
    .action-buttons .btn {
        margin: 0 2px;
    }
</style>
@endpush

@section('content')

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-user-shield mr-2"></i>Roles Management
        </h6>
        @can('create roles')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Create New Role
            </a>
        @endcan
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="roleSearch" class="form-control" placeholder="Search for roles...">
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="rolesTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Role Name</th>
                        <th class="text-center">Permissions</th>
                        <th class="text-center">Users</th>
                        <th>Last Updated</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr class="table-row-hover">
                            <td>
                                <a href="{{ route('admin.roles.show', $role->id) }}" class="font-weight-bold">{{ $role->name }}</a>
                                @if($role->description)
                                    <p class="small text-muted mb-0">{{ $role->description }}</p>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-success">{{ $role->permissions_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-info">{{ $role->users_count }}</span>
                            </td>
                            <td>{{ $role->updated_at->diffForHumans() }}</td>
                            <td class="text-center action-buttons">
                                @can('view roles')
                                    <a href="{{ route('admin.roles.show', $role->id) }}" class="btn btn-sm btn-outline-info" data-toggle="tooltip" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endcan
                                @can('edit roles')
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-warning" data-toggle="tooltip" title="Edit Role">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('delete roles')
                                    @if(!in_array($role->name, ['super-admin', 'student', 'staff', 'college-admin', 'accountant']))
                                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?');" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-toggle="tooltip" title="Delete Role">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-exclamation-circle fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No roles found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Live search functionality
    const searchInput = document.getElementById('roleSearch');
    const tableRows = document.querySelectorAll('#rolesTable tbody tr');

    searchInput.addEventListener('keyup', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        tableRows.forEach(row => {
            const roleName = row.querySelector('td a').textContent.toLowerCase();
            const roleDescription = row.querySelector('td p') ? row.querySelector('td p').textContent.toLowerCase() : '';

            if (roleName.includes(searchTerm) || roleDescription.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
@endpush