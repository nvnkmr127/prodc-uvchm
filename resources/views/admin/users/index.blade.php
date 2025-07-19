@extends('layouts.theme')
@section('title', 'User Management')

@push('styles')
<style>
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .role-badge {
        font-size: 0.7rem;
        margin: 0.1rem;
        padding: 0.2rem 0.4rem;
    }
    .action-buttons .btn {
        margin: 0 0.1rem;
        padding: 0.25rem 0.5rem;
    }
    .bulk-actions-bar {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        padding: 1rem;
        margin-bottom: 1rem;
        display: none;
    }
    .filters-card {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .table-responsive {
        border-radius: 0.35rem;
        overflow: hidden;
    }
    .status-toggle {
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-users mr-2"></i>User Management
    </h1>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" id="export-btn">
            <i class="fas fa-download fa-sm text-gray-600"></i> Export
        </button>
        @can('create users')
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New User
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<!-- Filters -->
<div class="filters-card">
    <form method="GET" id="filters-form">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label small font-weight-bold">Search Users</label>
                <input type="text" name="search" id="search" class="form-control form-control-sm" 
                       placeholder="Name or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label for="role" class="form-label small font-weight-bold">Filter by Role</label>
                <select name="role" id="role" class="form-control form-control-sm">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small font-weight-bold">Filter by Status</label>
                <select name="status" id="status" class="form-control form-control-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- Bulk Actions Bar -->
<div class="bulk-actions-bar" id="bulk-actions">
    <div class="row align-items-center">
        <div class="col-md-6">
            <span class="font-weight-bold">
                <span id="selected-count">0</span> users selected
            </span>
        </div>
        <div class="col-md-6 text-right">
            <div class="btn-group">
                <button class="btn btn-sm btn-success" onclick="bulkAction('activate')">
                    <i class="fas fa-check"></i> Activate
                </button>
                <button class="btn btn-sm btn-warning" onclick="bulkAction('deactivate')">
                    <i class="fas fa-pause"></i> Deactivate
                </button>
                <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            Users List ({{ $users->total() }} total)
        </h6>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="select-all">
            <label class="form-check-label" for="select-all">
                Select All
            </label>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select-all-header">
                            </div>
                        </th>
                        <th>User</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr data-user-id="{{ $user->id }}">
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input user-checkbox" type="checkbox" 
                                           value="{{ $user->id }}" data-user-name="{{ $user->name }}">
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=4e73df&color=fff&size=40" 
                                         class="user-avatar mr-3" alt="{{ $user->name }}">
                                    <div>
                                        <div class="font-weight-bold">{{ $user->name }}</div>
                                        <div class="text-muted small">{{ $user->email }}</div>
                                        @if($user->student)
                                            <div class="text-info small">
                                                <i class="fas fa-graduation-cap"></i> 
                                                Student: {{ $user->student->enrollment_number }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @forelse($user->roles as $role)
                                    <span class="badge badge-{{ getRoleBadgeColor($role->name) }} role-badge">
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @empty
                                    <span class="text-muted">No roles assigned</span>
                                @endforelse
                            </td>
                            <td>
                                <div class="status-toggle" data-user-id="{{ $user->id }}" 
                                     data-status="{{ $user->email_verified_at ? 'active' : 'inactive' }}">
                                    @if($user->email_verified_at)
                                        <span class="badge badge-success status-badge">
                                            <i class="fas fa-check-circle"></i> Active
                                        </span>
                                    @else
                                        <span class="badge badge-secondary status-badge">
                                            <i class="fas fa-pause-circle"></i> Inactive
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    {{ $user->created_at->format('M d, Y') }}
                                    <div class="text-muted">{{ $user->created_at->diffForHumans() }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    @can('view users')
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="btn btn-sm btn-outline-info" 
                                       data-toggle="tooltip" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('edit users')
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="btn btn-sm btn-outline-warning" 
                                       data-toggle="tooltip" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('delete users')
                                    @if($user->id !== auth()->id() && (!$user->hasRole('super-admin') || auth()->user()->hasRole('super-admin')))
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-user" 
                                            data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                                            data-toggle="tooltip" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <p class="mb-0">No users found.</p>
                                    @if(request()->hasAny(['search', 'role', 'status']))
                                        <a href="{{ route('admin.users.index') }}" class="btn btn-link">Clear filters to see all users</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($users->hasPages())
    <div class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
            </div>
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the user <strong id="delete-user-name"></strong>?</p>
                <p class="text-danger small">
                    <i class="fas fa-exclamation-triangle"></i>
                    This action cannot be undone. All associated data will be removed.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="delete-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Export Form -->
<form id="export-form" method="GET" action="{{ route('admin.users.export') }}" style="display: none;">
    <input type="hidden" name="role" value="{{ request('role') }}">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <input type="hidden" name="search" value="{{ request('search') }}">
</form>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Select all functionality
    $('#select-all, #select-all-header').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.user-checkbox').prop('checked', isChecked);
        $('#select-all, #select-all-header').prop('checked', isChecked);
        updateBulkActions();
    });

    // Individual checkbox change
    $(document).on('change', '.user-checkbox', function() {
        updateSelectAllState();
        updateBulkActions();
    });

    // Update select all state
    function updateSelectAllState() {
        const totalCheckboxes = $('.user-checkbox').length;
        const checkedCheckboxes = $('.user-checkbox:checked').length;
        
        const selectAllChecked = checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0;
        const selectAllIndeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
        
        $('#select-all, #select-all-header').prop('checked', selectAllChecked);
        $('#select-all, #select-all-header').prop('indeterminate', selectAllIndeterminate);
    }

    // Update bulk actions visibility
    function updateBulkActions() {
        const selectedCount = $('.user-checkbox:checked').length;
        $('#selected-count').text(selectedCount);
        
        if (selectedCount > 0) {
            $('#bulk-actions').slideDown();
        } else {
            $('#bulk-actions').slideUp();
        }
    }

    // Status toggle
    $(document).on('click', '.status-toggle', function() {
        const userId = $(this).data('user-id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const $toggle = $(this);

        // Prevent toggling current user's status to inactive
        if (userId == {{ auth()->id() }} && newStatus === 'inactive') {
            showAlert('error', 'You cannot deactivate your own account.');
            return;
        }

        $.ajax({
            url: `/admin/users/${userId}/status`,
            method: 'PATCH',
            data: {
                status: newStatus,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Update the toggle
                    $toggle.data('status', newStatus);
                    if (newStatus === 'active') {
                        $toggle.html('<span class="badge badge-success status-badge"><i class="fas fa-check-circle"></i> Active</span>');
                    } else {
                        $toggle.html('<span class="badge badge-secondary status-badge"><i class="fas fa-pause-circle"></i> Inactive</span>');
                    }
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('error', response.message || 'Failed to update status');
            }
        });
    });

    // Delete user
    $(document).on('click', '.delete-user', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        
        $('#delete-user-name').text(userName);
        $('#delete-form').attr('action', `/admin/users/${userId}`);
        $('#deleteModal').modal('show');
    });

    // Export functionality
    $('#export-btn').on('click', function() {
        $('#export-form').submit();
    });

    // Auto-submit filters on change
    $('#role, #status').on('change', function() {
        $('#filters-form').submit();
    });

    // Search with debounce
    let searchTimeout;
    $('#search').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $('#filters-form').submit();
        }, 500);
    });
});

// Bulk actions
function bulkAction(action) {
    const selectedUsers = $('.user-checkbox:checked');
    
    if (selectedUsers.length === 0) {
        showAlert('warning', 'Please select at least one user.');
        return;
    }

    const userIds = selectedUsers.map(function() {
        return $(this).val();
    }).get();

    const userNames = selectedUsers.map(function() {
        return $(this).data('user-name');
    }).get();

    let confirmMessage = '';
    switch(action) {
        case 'activate':
            confirmMessage = `Are you sure you want to activate ${selectedUsers.length} user(s)?`;
            break;
        case 'deactivate':
            confirmMessage = `Are you sure you want to deactivate ${selectedUsers.length} user(s)?`;
            break;
        case 'delete':
            confirmMessage = `Are you sure you want to delete ${selectedUsers.length} user(s)?\n\nThis action cannot be undone.`;
            break;
    }

    if (!confirm(confirmMessage)) {
        return;
    }

    // Show loading state
    const $bulkActions = $('#bulk-actions');
    $bulkActions.find('button').prop('disabled', true);
    showAlert('info', 'Processing bulk action...');

    $.ajax({
        url: '{{ route("admin.users.bulk-actions") }}',
        method: 'POST',
        data: {
            action: action,
            users: userIds,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                
                // Reload page after short delay
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert('error', response.message || 'Bulk action failed');
        },
        complete: function() {
            $bulkActions.find('button').prop('disabled', false);
        }
    });
}

// Helper function to get role badge color
function getRoleBadgeColor(roleName) {
    const colors = {
        'super-admin': 'danger',
        'admin': 'primary',
        'college-admin': 'info',
        'staff': 'success',
        'student': 'secondary',
        'accountant': 'warning'
    };
    return colors[roleName] || 'secondary';
}
</script>

@php
function getRoleBadgeColor($roleName) {
    $colors = [
        'super-admin' => 'danger',
        'admin' => 'primary', 
        'college-admin' => 'info',
        'staff' => 'success',
        'student' => 'secondary',
        'accountant' => 'warning'
    ];
    return $colors[$roleName] ?? 'secondary';
}
@endphp