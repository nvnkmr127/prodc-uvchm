@extends('layouts.theme')
@section('title', 'User Details')

@push('styles')
<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 1rem 1rem 0 0;
        padding: 2rem;
    }
    .user-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid rgba(255,255,255,0.3);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
    .stat-card {
        background: rgba(255,255,255,0.1);
        border-radius: 0.5rem;
        padding: 1rem;
        text-align: center;
        backdrop-filter: blur(10px);
    }
    .permission-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    .permission-category {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        padding: 1rem;
    }
    .permission-item {
        padding: 0.25rem 0;
        border-bottom: 1px solid #e3e6f0;
    }
    .permission-item:last-child {
        border-bottom: none;
    }
    .activity-timeline {
        position: relative;
        padding-left: 2rem;
    }
    .activity-item {
        position: relative;
        padding-bottom: 1.5rem;
    }
    .activity-item::before {
        content: '';
        position: absolute;
        left: -2rem;
        top: 0.25rem;
        width: 0.75rem;
        height: 0.75rem;
        background: #4e73df;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 3px #e3e6f0;
    }
    .activity-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: -1.625rem;
        top: 1rem;
        bottom: 0;
        width: 2px;
        background: #e3e6f0;
    }
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e3e6f0;
    }
    .info-item:last-child {
        border-bottom: none;
    }
    .badge-role {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
        margin: 0.2rem;
    }
    .connected-accounts {
        background: #f8f9fc;
        border-radius: 0.5rem;
        padding: 1rem;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-user mr-2"></i>User Profile
    </h1>
    <div class="d-flex gap-2">
        @can('edit users')
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning shadow-sm">
            <i class="fas fa-edit fa-sm text-white-50"></i> Edit User
        </a>
        @endcan
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Users
        </a>
    </div>
</div>

<div class="row">
    <!-- Main Profile Content -->
    <div class="col-xl-8">
        <!-- Profile Header -->
        <div class="card shadow mb-4">
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=fff&color=667eea&size=120" 
                             class="user-avatar" alt="{{ $user->name }}">
                    </div>
                    <div class="col">
                        <h2 class="mb-1">{{ $user->name }}</h2>
                        <p class="mb-2 text-white-75">{{ $user->email }}</p>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @forelse($user->roles as $role)
                                <span class="badge badge-light badge-role">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                </span>
                            @empty
                                <span class="badge badge-secondary badge-role">No roles assigned</span>
                            @endforelse
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <h4 class="mb-1">{{ $user->roles->count() }}</h4>
                                    <small class="text-white-75">Roles</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <h4 class="mb-1">{{ $user->getAllPermissions()->count() }}</h4>
                                    <small class="text-white-75">Permissions</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <h4 class="mb-1">
                                        @if($user->email_verified_at)
                                            <i class="fas fa-check-circle text-success"></i>
                                        @else
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        @endif
                                    </h4>
                                    <small class="text-white-75">
                                        {{ $user->email_verified_at ? 'Active' : 'Inactive' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Information -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle mr-2"></i>Account Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="font-weight-bold">Full Name:</span>
                            <span>{{ $user->name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="font-weight-bold">Email Address:</span>
                            <span>{{ $user->email }}</span>
                        </div>
                        <div class="info-item">
                            <span class="font-weight-bold">Account Status:</span>
                            <span>
                                @if($user->email_verified_at)
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle mr-1"></i>Active
                                    </span>
                                @else
                                    <span class="badge badge-warning">
                                        <i class="fas fa-pause-circle mr-1"></i>Inactive
                                    </span>
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="font-weight-bold">Member Since:</span>
                            <span>{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="font-weight-bold">Last Updated:</span>
                            <span>{{ $user->updated_at->diffForHumans() }}</span>
                        </div>
                        <div class="info-item">
                            <span class="font-weight-bold">Email Verified:</span>
                            <span>
                                @if($user->email_verified_at)
                                    <span class="text-success">
                                        <i class="fas fa-check mr-1"></i>
                                        {{ $user->email_verified_at->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-warning">
                                        <i class="fas fa-times mr-1"></i>
                                        Not verified
                                    </span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connected Accounts -->
        @if($user->student)
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-link mr-2"></i>Connected Accounts
                </h6>
            </div>
            <div class="card-body">
                <div class="connected-accounts">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-info mr-3">
                            <i class="fas fa-graduation-cap text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Student Account</h6>
                            <p class="mb-0 text-muted">
                                Enrollment: {{ $user->student->enrollment_number }}
                                @if($user->student->batch)
                                    | Batch: {{ $user->student->batch->name }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('admin.students.show', $user->student) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-external-link-alt mr-1"></i>View Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Permissions Overview -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-shield-alt mr-2"></i>Permissions Overview
                </h6>
                <span class="badge badge-info">{{ $user->getAllPermissions()->count() }} total permissions</span>
            </div>
            <div class="card-body">
                @if($user->getAllPermissions()->count() > 0)
                    <div class="permission-grid">
                        @php
                            $groupedPermissions = $user->getAllPermissions()->groupBy(function ($permission) {
                                $parts = explode(' ', $permission->name);
                                return count($parts) >= 2 ? $parts[1] : 'general';
                            });
                        @endphp
                        
                        @foreach($groupedPermissions as $module => $permissions)
                            <div class="permission-category">
                                <h6 class="font-weight-bold text-primary mb-3">
                                    <i class="fas fa-{{ getModuleIcon($module) }} mr-2"></i>
                                    {{ ucfirst(str_replace('_', ' ', $module)) }}
                                </h6>
                                @foreach($permissions as $permission)
                                    <div class="permission-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small">{{ $permission->name }}</span>
                                            <span class="badge badge-{{ getPermissionBadgeColor($permission->name) }} badge-sm">
                                                {{ ucfirst(explode(' ', $permission->name)[0]) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-lock fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">No permissions assigned to this user.</p>
                        @can('edit users')
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>Assign Permissions
                            </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-xl-4">
        <!-- Quick Actions -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt mr-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @can('edit users')
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit mr-2"></i>Edit User
                    </a>
                    @endcan
                    
                    @if($user->student)
                    <a href="{{ route('admin.students.show', $user->student) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-graduation-cap mr-2"></i>View Student Profile
                    </a>
                    @endif
                    
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleUserStatus()">
                        @if($user->email_verified_at)
                            <i class="fas fa-pause mr-2"></i>Deactivate Account
                        @else
                            <i class="fas fa-play mr-2"></i>Activate Account
                        @endif
                    </button>
                    
                    @if(auth()->user()->hasRole('super-admin'))
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="sendWelcomeEmail()">
                        <i class="fas fa-envelope mr-2"></i>Send Welcome Email
                    </button>
                    @endif
                    
                    @can('delete users')
                    @if($user->id !== auth()->id() && (!$user->hasRole('super-admin') || auth()->user()->hasRole('super-admin')))
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                        <i class="fas fa-trash mr-2"></i>Delete User
                    </button>
                    @endif
                    @endcan
                </div>
            </div>
        </div>

        <!-- Role Details -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users-cog mr-2"></i>Role Details
                </h6>
            </div>
            <div class="card-body">
                @forelse($user->roles as $role)
                    <div class="mb-3 p-3 border-left-{{ getRoleBadgeColor($role->name) }} border-left-4 bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</h6>
                            <span class="badge badge-{{ getRoleBadgeColor($role->name) }}">
                                {{ $role->permissions()->count() }} permissions
                            </span>
                        </div>
                        @if($role->description)
                            <p class="small text-muted mb-2">{{ $role->description }}</p>
                        @endif
                        <div class="small text-muted">
                            <i class="fas fa-clock mr-1"></i>
                            Assigned {{ $role->pivot->created_at ?? 'Unknown' }}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3">
                        <i class="fas fa-user-slash fa-2x text-gray-300 mb-2"></i>
                        <p class="text-muted mb-0">No roles assigned</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Security Information -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-shield-alt mr-2"></i>Security Information
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Password Set:</span>
                        <span class="font-weight-bold text-success">
                            <i class="fas fa-check mr-1"></i>Yes
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Two-Factor Auth:</span>
                        <span class="font-weight-bold text-warning">
                            <i class="fas fa-times mr-1"></i>Not Enabled
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Last Login:</span>
                        <span class="font-weight-bold">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Login Count:</span>
                        <span class="font-weight-bold">{{ $user->login_count ?? 0 }}</span>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="resetPassword()">
                        <i class="fas fa-key mr-1"></i>Reset Password
                    </button>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-history mr-2"></i>Recent Activity
                </h6>
            </div>
            <div class="card-body">
                <div class="activity-timeline">
                    <div class="activity-item">
                        <div class="small text-muted">{{ $user->updated_at->diffForHumans() }}</div>
                        <div class="font-weight-bold">Profile Updated</div>
                        <div class="small text-muted">User information was modified</div>
                    </div>
                    
                    @if($user->email_verified_at)
                    <div class="activity-item">
                        <div class="small text-muted">{{ $user->email_verified_at->diffForHumans() }}</div>
                        <div class="font-weight-bold">Email Verified</div>
                        <div class="small text-muted">Email address was verified</div>
                    </div>
                    @endif
                    
                    <div class="activity-item">
                        <div class="small text-muted">{{ $user->created_at->diffForHumans() }}</div>
                        <div class="font-weight-bold">Account Created</div>
                        <div class="small text-muted">User account was created</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5>Are you sure?</h5>
                    <p>You are about to delete <strong>{{ $user->name }}</strong>.</p>
                    <p class="text-danger">
                        <strong>This action cannot be undone!</strong> All associated data will be permanently removed.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

function toggleUserStatus() {
    const currentStatus = {{ $user->email_verified_at ? 'true' : 'false' }};
    const newStatus = currentStatus ? 'inactive' : 'active';
    const action = currentStatus ? 'deactivate' : 'activate';
    
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }
    
    $.ajax({
        url: `/admin/users/{{ $user->id }}/status`,
        method: 'PATCH',
        data: {
            status: newStatus,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert('error', response.message || 'Failed to update status');
        }
    });
}

function sendWelcomeEmail() {
    if (!confirm('Send a welcome email to {{ $user->name }}?')) {
        return;
    }
    
    // This would typically make an AJAX call to send the email
    showAlert('info', 'Welcome email functionality would be implemented here.');
}

function resetPassword() {
    if (!confirm('Generate a new password for {{ $user->name }}?')) {
        return;
    }
    
    // This would typically make an AJAX call to reset the password
    showAlert('info', 'Password reset functionality would be implemented here.');
}

function confirmDelete() {
    $('#deleteModal').modal('show');
}
</script>

@php
function getModuleIcon($module) {
    $icons = [
        'students' => 'user-graduate',
        'faculty' => 'chalkboard-teacher', 
        'courses' => 'book',
        'subjects' => 'book-open',
        'batches' => 'users',
        'admissions' => 'user-plus',
        'enquiries' => 'question-circle',
        'attendance' => 'calendar-check',
        'timetable' => 'calendar',
        'financials' => 'dollar-sign',
        'fees' => 'money-bill',
        'expenses' => 'receipt',
        'hr' => 'briefcase',
        'inventory' => 'boxes',
        'reports' => 'chart-bar',
        'users' => 'users',
        'roles' => 'user-shield',
        'permissions' => 'key',
        'settings' => 'cogs',
        'documents' => 'file-alt',
        'events' => 'calendar-alt',
        'visitors' => 'user-friends',
        'general' => 'cog',
        'backend' => 'desktop',
        'dashboard' => 'tachometer-alt'
    ];
    return $icons[$module] ?? 'cog';
}

function getPermissionBadgeColor($permission) {
    if (strpos($permission, 'view') !== false) return 'info';
    if (strpos($permission, 'create') !== false) return 'success';
    if (strpos($permission, 'edit') !== false) return 'warning';
    if (strpos($permission, 'delete') !== false) return 'danger';
    if (strpos($permission, 'manage') !== false) return 'primary';
    return 'secondary';
}

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
@endpush