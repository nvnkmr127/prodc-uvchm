@extends('layouts.theme')
@section('title', 'Create New User')

@push('styles')
<style>
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .form-group label {
        font-weight: 600;
        color: #5a5c69;
    }
    .required::after {
        content: " *";
        color: #e74a3b;
    }
    .role-selection {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #d1d3e2;
        border-radius: 0.35rem;
        padding: 0.75rem;
        background: #f8f9fc;
    }
    .role-item {
        padding: 0.5rem;
        margin-bottom: 0.5rem;
        border-radius: 0.25rem;
        transition: background-color 0.2s;
    }
    .role-item:hover {
        background: #e3e6f0;
    }
    .role-item.selected {
        background: #4e73df;
        color: white;
    }
    .password-strength {
        height: 5px;
        border-radius: 3px;
        margin-top: 5px;
        transition: all 0.3s;
    }
    .strength-weak { background: #e74a3b; }
    .strength-medium { background: #f6c23e; }
    .strength-strong { background: #1cc88a; }
    .preview-card {
        background: #f8f9fc;
        border: 2px dashed #d1d3e2;
        border-radius: 0.35rem;
        padding: 1.5rem;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-user-plus mr-2"></i>Create New User
    </h1>
    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Users
    </a>
</div>

<form action="{{ route('admin.users.store') }}" method="POST" id="createUserForm">
    @csrf
    <div class="row">
        <!-- Main Form -->
        <div class="col-xl-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-user-edit mr-2"></i>User Information
                    </h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <div class="font-weight-bold mb-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Please correct the following errors:
                            </div>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="required">Full Name</label>
                                <input type="text" name="name" id="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="required">Email Address</label>
                                <input type="email" name="email" id="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="required">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                                <small class="form-text text-muted">
                                    Password must be at least 8 characters long
                                </small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation" class="required">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" 
                                       class="form-control @error('password_confirmation') is-invalid @enderror" 
                                       required>
                                <div id="passwordMatch" class="mt-1"></div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Account Status</label>
                                <div class="d-flex">
                                    <div class="form-check mr-4">
                                        <input class="form-check-input" type="radio" name="status" 
                                               id="status_active" value="active" 
                                               {{ old('status', 'active') == 'active' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="status_active">
                                            <i class="fas fa-check-circle text-success mr-1"></i>Active
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" 
                                               id="status_inactive" value="inactive"
                                               {{ old('status') == 'inactive' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="status_inactive">
                                            <i class="fas fa-pause-circle text-warning mr-1"></i>Inactive
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="form-group">
                        <label class="required">Assign Roles</label>
                        <div class="role-selection">
                            @foreach($roles as $role)
                                <div class="form-check role-item">
                                    <input class="form-check-input" type="checkbox" name="roles[]" 
                                           value="{{ $role->name }}" id="role_{{ $role->id }}"
                                           {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label w-100" for="role_{{ $role->id }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ ucfirst(str_replace('-', ' ', $role->name)) }}</strong>
                                                @if($role->description)
                                                    <div class="small text-muted">{{ $role->description }}</div>
                                                @endif
                                            </div>
                                            <span class="badge badge-{{ getRoleBadgeColor($role->name) }}">
                                                {{ $role->permissions()->count() }} permissions
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('roles')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select one or more roles for this user. Multiple roles will combine permissions.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview/Summary -->
        <div class="col-xl-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-eye mr-2"></i>User Preview
                    </h6>
                </div>
                <div class="card-body">
                    <div class="preview-card" id="userPreview">
                        <i class="fas fa-user fa-3x text-gray-300 mb-3"></i>
                        <h5 id="previewName" class="text-gray-500">Enter user details</h5>
                        <p id="previewEmail" class="text-muted">user@example.com</p>
                        <div id="previewRoles" class="mt-3">
                            <span class="badge badge-secondary">No roles selected</span>
                        </div>
                        <div id="previewStatus" class="mt-2">
                            <span class="badge badge-success">Active</span>
                        </div>
                    </div>

                    <hr>

                    <h6 class="font-weight-bold mb-3">
                        <i class="fas fa-shield-alt mr-2"></i>Security Checklist
                    </h6>
                    <div class="security-checklist">
                        <div class="d-flex align-items-center mb-2" id="checkEmail">
                            <i class="fas fa-times text-danger mr-2"></i>
                            <span class="small">Valid email address</span>
                        </div>
                        <div class="d-flex align-items-center mb-2" id="checkPassword">
                            <i class="fas fa-times text-danger mr-2"></i>
                            <span class="small">Strong password</span>
                        </div>
                        <div class="d-flex align-items-center mb-2" id="checkPasswordMatch">
                            <i class="fas fa-times text-danger mr-2"></i>
                            <span class="small">Passwords match</span>
                        </div>
                        <div class="d-flex align-items-center mb-2" id="checkRoles">
                            <i class="fas fa-times text-danger mr-2"></i>
                            <span class="small">At least one role assigned</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block" id="submitBtn" disabled>
                        <i class="fas fa-save mr-2"></i>Create User
                    </button>
                    <div class="text-center mt-2">
                        <small class="text-muted">Complete the security checklist to enable creation</small>
                    </div>
                </div>
            </div>

            <!-- Quick Role Info -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-info-circle mr-2"></i>Role Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="role-descriptions">
                        @foreach($roles as $role)
                            <div class="mb-3 p-2 border-left-{{ getRoleBadgeColor($role->name) }} border-left-4 bg-light">
                                <h6 class="mb-1">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</h6>
                                <small class="text-muted">
                                    @switch($role->name)
                                        @case('super-admin')
                                            Full system access with all permissions
                                            @break
                                        @case('admin')
                                            Administrative access to most features
                                            @break
                                        @case('college-admin')
                                            College-level administrative access
                                            @break
                                        @case('staff')
                                            Faculty and teaching staff access
                                            @break
                                        @case('student')
                                            Student portal access
                                            @break
                                        @case('accountant')
                                            Financial and accounting access
                                            @break
                                        @default
                                            {{ $role->description ?? 'Custom role with specific permissions' }}
                                    @endswitch
                                </small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Password toggle
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const passwordFieldType = passwordField.attr('type');
        const icon = $(this).find('i');
        
        if (passwordFieldType === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Password strength checker
    $('#password').on('keyup', function() {
        const password = $(this).val();
        const strength = calculatePasswordStrength(password);
        const $strengthBar = $('#passwordStrength');
        
        $strengthBar.removeClass('strength-weak strength-medium strength-strong');
        
        if (password.length === 0) {
            $strengthBar.css('width', '0%');
            return;
        }
        
        if (strength < 30) {
            $strengthBar.addClass('strength-weak').css('width', '33%');
        } else if (strength < 60) {
            $strengthBar.addClass('strength-medium').css('width', '66%');
        } else {
            $strengthBar.addClass('strength-strong').css('width', '100%');
        }
        
        updateSecurityChecklist();
    });

    // Password confirmation checker
    $('#password_confirmation').on('keyup', function() {
        const password = $('#password').val();
        const confirmation = $(this).val();
        const $match = $('#passwordMatch');
        
        if (confirmation.length === 0) {
            $match.html('');
            return;
        }
        
        if (password === confirmation) {
            $match.html('<small class="text-success"><i class="fas fa-check mr-1"></i>Passwords match</small>');
        } else {
            $match.html('<small class="text-danger"><i class="fas fa-times mr-1"></i>Passwords do not match</small>');
        }
        
        updateSecurityChecklist();
    });

    // Live preview updates
    $('#name').on('keyup', function() {
        const name = $(this).val() || 'Enter user details';
        $('#previewName').text(name);
    });

    $('#email').on('keyup', function() {
        const email = $(this).val() || 'user@example.com';
        $('#previewEmail').text(email);
        updateSecurityChecklist();
    });

    // Role selection updates
    $('input[name="roles[]"]').on('change', function() {
        updateRolePreview();
        updateSecurityChecklist();
    });

    // Status selection updates
    $('input[name="status"]').on('change', function() {
        const status = $(this).val();
        const $previewStatus = $('#previewStatus');
        
        if (status === 'active') {
            $previewStatus.html('<span class="badge badge-success">Active</span>');
        } else {
            $previewStatus.html('<span class="badge badge-warning">Inactive</span>');
        }
    });

    // Form submission
    $('#createUserForm').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            showAlert('error', 'Please complete all required fields correctly.');
            return false;
        }

        // Disable submit button to prevent double submission
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Creating User...');
    });

    // Functions
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength += 20;
        if (password.length >= 12) strength += 10;
        if (/[a-z]/.test(password)) strength += 15;
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^A-Za-z0-9]/.test(password)) strength += 25;
        
        return strength;
    }

    function updateRolePreview() {
        const selectedRoles = $('input[name="roles[]"]:checked');
        const $previewRoles = $('#previewRoles');
        
        if (selectedRoles.length === 0) {
            $previewRoles.html('<span class="badge badge-secondary">No roles selected</span>');
            return;
        }
        
        let rolesHtml = '';
        selectedRoles.each(function() {
            const roleName = $(this).val();
            const badgeColor = getRoleBadgeColor(roleName);
            rolesHtml += `<span class="badge badge-${badgeColor} mr-1 mb-1">${roleName.charAt(0).toUpperCase() + roleName.slice(1).replace('-', ' ')}</span>`;
        });
        
        $previewRoles.html(rolesHtml);
    }

    function updateSecurityChecklist() {
        // Check email
        const email = $('#email').val();
        const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        updateChecklistItem('checkEmail', emailValid);
        
        // Check password strength
        const password = $('#password').val();
        const passwordStrong = calculatePasswordStrength(password) >= 60;
        updateChecklistItem('checkPassword', passwordStrong);
        
        // Check password match
        const passwordConfirmation = $('#password_confirmation').val();
        const passwordsMatch = password === passwordConfirmation && password.length > 0;
        updateChecklistItem('checkPasswordMatch', passwordsMatch);
        
        // Check roles
        const rolesSelected = $('input[name="roles[]"]:checked').length > 0;
        updateChecklistItem('checkRoles', rolesSelected);
        
        // Enable/disable submit button
        const allChecked = emailValid && passwordStrong && passwordsMatch && rolesSelected;
        $('#submitBtn').prop('disabled', !allChecked);
    }

    function updateChecklistItem(itemId, isValid) {
        const $item = $(`#${itemId} i`);
        if (isValid) {
            $item.removeClass('fas fa-times text-danger').addClass('fas fa-check text-success');
        } else {
            $item.removeClass('fas fa-check text-success').addClass('fas fa-times text-danger');
        }
    }

    function validateForm() {
        const email = $('#email').val();
        const password = $('#password').val();
        const passwordConfirmation = $('#password_confirmation').val();
        const roles = $('input[name="roles[]"]:checked').length;
        
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) &&
               calculatePasswordStrength(password) >= 60 &&
               password === passwordConfirmation &&
               roles > 0;
    }

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

    // Initialize preview
    updateRolePreview();
    updateSecurityChecklist();
});
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
@endpush