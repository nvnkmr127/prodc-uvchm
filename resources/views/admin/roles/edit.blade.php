@extends('layouts.theme')
@section('title', 'Edit Role')

@push('styles')
<style>
    /* Styles for the fixed bottom action bar */
    .fixed-bottom-bar {
        position: sticky;
        bottom: 0;
        z-index: 1020; /* Ensure it's above other content */
        background-color: rgba(255, 255, 255, 0.95);
        padding: 1rem;
        border-top: 1px solid #e3e6f0;
        box-shadow: 0 -0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    /* Style for the module toggle button */
    .btn-toggle-module {
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Role: {{ $role->name }}</h1>
    <div>
        <a href="{{ route('admin.roles.show', $role->id) }}" class="btn btn-sm btn-info shadow-sm">
            <i class="fas fa-eye fa-sm text-white-50"></i> View Role
        </a>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Roles
        </a>
    </div>
</div>

<form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Role Information</h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="form-group">
                        <label for="name">Role Name</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ old('name', $role->name) }}" required 
                               @if(in_array($role->name, ['super-admin', 'student', 'staff', 'college-admin', 'accountant'])) readonly @endif>
                        @if(in_array($role->name, ['super-admin', 'student', 'staff', 'college-admin', 'accountant']))
                            <small class="form-text text-warning">Core role names cannot be edited.</small>
                        @endif
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" 
                                  placeholder="Role description...">{{ old('description', $role->description ?? '') }}</textarea>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="font-weight-bold text-gray-800">Quick Actions</h6>
                        <div class="btn-group-vertical btn-group-sm w-100" role="group">
                            <button type="button" class="btn btn-outline-success" id="select-all-permissions">
                                <i class="fas fa-check-double"></i> Select All
                            </button>
                            <button type="button" class="btn btn-outline-warning" id="deselect-all-permissions">
                                <i class="fas fa-times"></i> Deselect All
                            </button>
                             {{-- NEW: Added toggle buttons for all modules --}}
                            <button type="button" class="btn btn-outline-info" id="expand-all-modules">
                                <i class="fas fa-plus"></i> Expand All Modules
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="collapse-all-modules">
                                <i class="fas fa-minus"></i> Collapse All Modules
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Assign Permissions</h6>
                    <span class="badge badge-info" id="selected-count">
                        {{ count($rolePermissions) }} selected
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($groupedPermissions as $module => $permissions)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-left-primary h-100">
                                     {{-- NEW: Added Bootstrap collapse attributes for the toggle view feature --}}
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center btn-toggle-module" 
                                         data-toggle="collapse" data-target="#module-body-{{$module}}">
                                        <h6 class="font-weight-bold text-primary text-uppercase mb-0" style="font-size: 0.9rem;">
                                            <i class="fas fa-{{ getModuleIcon($module) }} mr-2"></i>
                                            {{ Str::title($module) }}
                                        </h6>
                                        <i class="fas fa-chevron-down toggle-icon"></i>
                                    </div>
                                    <div class="collapse show" id="module-body-{{$module}}">
                                        <div class="card-body p-3">
                                            <div class="form-group mb-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input select-module-checkbox" 
                                                           id="select-module-{{ $module }}" data-module="{{ $module }}">
                                                    <label class="custom-control-label font-weight-bold text-success" 
                                                           for="select-module-{{ $module }}">
                                                        Select All
                                                    </label>
                                                </div>
                                            </div>
                                            <hr class="my-2">
                                            @foreach($permissions as $permission)
                                                <div class="form-group mb-2">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" name="permissions[]" 
                                                               class="custom-control-input permission-checkbox module-{{ $module }}" 
                                                               id="permission-{{ $permission->id }}" 
                                                               value="{{ $permission->name }}"
                                                               @if(in_array($permission->name, $rolePermissions)) checked @endif>
                                                        <label class="custom-control-label" for="permission-{{ $permission->id }}">
                                                            <span class="badge badge-{{ getPermissionBadgeColor($permission->name) }} mr-1">
                                                                {{ getPermissionAction($permission->name) }}
                                                            </span>
                                                            {{ getPermissionDescription($permission->name) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow mb-4 fixed-bottom-bar">
        <div class="card-body text-center py-3">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Update Role & Permissions
            </button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-lg ml-2">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </div>
</form>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Helper Functions ---

    const allPermissionCBs = document.querySelectorAll('.permission-checkbox');
    const moduleCBs = document.querySelectorAll('.select-module-checkbox');
    const countDisplay = document.getElementById('selected-count');

    const updateSelectedCount = () => {
        const selectedCount = document.querySelectorAll('.permission-checkbox:checked').length;
        if (countDisplay) {
            countDisplay.textContent = `${selectedCount} selected`;
        }
    };

    const syncModuleCheckbox = (moduleName) => {
        const header = document.getElementById(`select-module-${moduleName}`);
        if (!header) return;

        const modulePermissions = document.querySelectorAll(`.module-${moduleName}`);
        const checkedCount = document.querySelectorAll(`.module-${moduleName}:checked`).length;
        
        if (checkedCount === 0) {
            header.checked = false;
            header.indeterminate = false;
        } else if (checkedCount === modulePermissions.length) {
            header.checked = true;
            header.indeterminate = false;
        } else {
            header.checked = false;
            header.indeterminate = true;
        }
    };

    // --- Event Delegation Listener for Clicks ---

    document.addEventListener('click', (e) => {
        // Master "Select All" button
        if (e.target.closest('#select-all-permissions')) {
            allPermissionCBs.forEach(cb => cb.checked = true);
            moduleCBs.forEach(cb => { cb.checked = true; cb.indeterminate = false; });
            updateSelectedCount();
            return;
        }

        // Master "Deselect All" button
        if (e.target.closest('#deselect-all-permissions')) {
            allPermissionCBs.forEach(cb => cb.checked = false);
            moduleCBs.forEach(cb => { cb.checked = false; cb.indeterminate = false; });
            updateSelectedCount();
            return;
        }
        
        // "Expand All" modules button
        if (e.target.closest('#expand-all-modules')) {
            document.querySelectorAll('.module-body').forEach(body => body.style.display = 'block');
            document.querySelectorAll('.toggle-icon').forEach(icon => icon.classList.remove('collapsed'));
            return;
        }

        // "Collapse All" modules button
        if (e.target.closest('#collapse-all-modules')) {
            document.querySelectorAll('.module-body').forEach(body => body.style.display = 'none');
            document.querySelectorAll('.toggle-icon').forEach(icon => icon.classList.add('collapsed'));
            return;
        }

        // A module's toggle header
        const toggleHeader = e.target.closest('.btn-toggle-module');
        if (toggleHeader) {
            const body = document.getElementById(toggleHeader.dataset.target.substring(1));
            const icon = toggleHeader.querySelector('.toggle-icon');
            if (body && icon) {
                const isVisible = body.style.display === 'block';
                body.style.display = isVisible ? 'none' : 'block';
                icon.classList.toggle('collapsed', isVisible);
            }
        }
    });

    // --- Event Delegation Listener for Checkbox Changes ---

    document.addEventListener('change', (e) => {
        // An individual permission checkbox
        if (e.target.matches('.permission-checkbox')) {
            const moduleName = e.target.className.match(/module-([a-zA-Z0-9_-]+)/)[1];
            if (moduleName) {
                syncModuleCheckbox(moduleName);
            }
            updateSelectedCount();
            return;
        }

        // A module's "Select All" checkbox
        if (e.target.matches('.select-module-checkbox')) {
            const moduleName = e.target.dataset.module;
            const modulePermissions = document.querySelectorAll(`.module-${moduleName}`);
            modulePermissions.forEach(perm => perm.checked = e.target.checked);
            updateSelectedCount();
        }
    });

    // --- Initial State Setup on Page Load ---

    // Initialize module checkbox states (checked, unchecked, or indeterminate)
    moduleCBs.forEach(cb => {
        if (cb.dataset.module) {
            syncModuleCheckbox(cb.dataset.module)
        }
    });

    // Auto-collapse modules that have no permissions selected
    document.querySelectorAll('.btn-toggle-module').forEach(btn => {
        const body = document.getElementById(btn.dataset.target.substring(1));
        const icon = btn.querySelector('.toggle-icon');
        if (body.querySelectorAll('.permission-checkbox:checked').length > 0) {
             body.style.display = 'block';
             icon.classList.remove('collapsed');
        } else {
             body.style.display = 'none';
             icon.classList.add('collapsed');
        }
    });

    // Initialize the master count
    updateSelectedCount();
});
</script>
@endpush

{{-- Helper functions remain the same --}}
@php
function getModuleIcon($module) {
    $icons = [
        'students' => 'user-graduate', 'faculty' => 'chalkboard-teacher', 'courses' => 'book',
        'subjects' => 'book-open', 'batches' => 'users', 'admissions' => 'user-plus',
        'enquiries' => 'question-circle', 'attendance' => 'calendar-check', 'timetable' => 'calendar',
        'financials' => 'dollar-sign', 'fees' => 'money-bill', 'expenses' => 'receipt',
        'hr' => 'briefcase', 'inventory' => 'boxes', 'reports' => 'chart-bar',
        'users' => 'users', 'roles' => 'user-shield', 'permissions' => 'key',
        'settings' => 'cogs', 'documents' => 'file-alt', 'events' => 'calendar-alt',
        'visitors' => 'user-friends', 'general' => 'cog',
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

function getPermissionAction($permission) {
    $parts = explode(' ', $permission);
    return strtoupper($parts[0]);
}

function getPermissionDescription($permission) {
    $parts = explode(' ', $permission);
    return count($parts) > 1 ? ucwords(implode(' ', array_slice($parts, 1))) : '';
}
@endphp