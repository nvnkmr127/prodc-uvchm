@extends('layouts.theme')
@section('title', 'Edit User')

@push('styles')
<style>
    .user-edit-card {
        border: none;
        border-radius: 0.75rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.2s ease;
    }
    
    .user-edit-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .role-selection {
        background: #f8f9fc;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .role-option {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.35rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .role-option:hover {
        border-color: #4e73df;
        background: #f8f9fc;
    }
    
    .role-option.selected {
        border-color: #4e73df;
        background: #e3f2fd;
    }
    
    .user-info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    
    .btn-custom {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }
    
    .btn-custom:hover {
        background: linear-gradient(135deg, #375ac7 0%, #1e3a8a 100%);
        transform: translateY(-1px);
        color: white;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .status-suspended {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-user-edit mr-2"></i>Edit User: {{ $user->name }}
    </h1>
    <div>
        <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-info shadow-sm">
            <i class="fas fa-eye fa-sm text-white-50"></i> View User
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Users
        </a>
    </div>
</div>

<form action="{{ route('admin.users.update', $user->id) }}" method="POST" id="userEditForm">
    @csrf
    @method('PUT')
    
    <div class="row">
        <!-- Main User Information -->
        <div class="col-xl-8">
            <!-- User Information Card -->
            <div class="card user-edit-card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user mr-2"></i>User Information
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
                                <label for="name" class="font-weight-bold">Full Name *</label>
                                <input type="text" name="name" id="name" class="form-control" 
                                       value="{{ old('name', $user->name) }}" required>
                                <small class="form-text text-muted">Enter the user's full name</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="font-weight-bold">Email Address *</label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="{{ old('email', $user->email) }}" required>
                                <small class="form-text text-muted">Must be a valid email address</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="font-weight-bold">New Password</label>
                                <input type="password" name="password" id="password" class="form-control">
                                <small class="form-text text-muted">Leave blank to keep current password</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation" class="font-weight-bold">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                                <small class="form-text text-muted">Must match the new password</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status" class="font-weight-bold">Account Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="active" {{ old('status', $user->status ?? 'active') == 'active' ? 'selected' : '' }}>
                                        Active
                                    </option>
                                    <option value="inactive" {{ old('status', $user->status ?? 'active') == 'inactive' ? 'selected' : '' }}>
                                        Inactive
                                    </option>
                                    <option value="suspended" {{ old('status', $user->status ?? 'active') == 'suspended' ? 'selected' : '' }}>
                                        Suspended
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Current Status</label>
                                <div class="mt-2">
                                    <span class="status-badge status-{{ $user->status ?? 'active' }}">
                                        {{ ucfirst($user->status ?? 'active') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Assignment Card -->
            <div class="card user-edit-card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-shield mr-2"></i>Role Assignment
                    </h6>
                </div>
                <div class="card-body">
                    <div class="role-selection">
                        <label class="font-weight-bold mb-3">Assign Roles to User</label>
                        <div class="row">
                            @foreach($roles as $role)
                                <div class="col-md-6 mb-2">
                                    <div class="role-option {{ in_array($role->id, $userRoles ?? []) ? 'selected' : '' }}">
                                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                                               id="role_{{ $role->id }}" class="form-check-input mr-3"
                                               {{ in_array($role->id, $userRoles ?? []) ? 'checked' : '' }}>
                                        <div class="flex-grow-1">
                                            <label class="form-check-label font-weight-bold mb-0" for="role_{{ $role->id }}">
                                                {{ ucfirst($role->name) }}
                                            </label>
                                            <div class="text-muted small">
                                                {{ $role->permissions->count() }} permissions
                                                @if($role->description)
                                                    • {{ $role->description }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($roles->isEmpty())
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                No roles available. Please create roles first.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Information -->
        <div class="col-xl-4">
            <!-- User Summary -->
            <div class="user-info-card">
                <h6 class="font-weight-bold mb-3">
                    <i class="fas fa-user-circle mr-2"></i>User Summary
                </h6>
                <div class="mb-2">
                    <strong>User ID:</strong> #{{ $user->id }}
                </div>
                <div class="mb-2">
                    <strong>Email:</strong> {{ $user->email }}
                </div>
                <div class="mb-2">
                    <strong>Created:</strong> {{ $user->created_at->format('M d, Y') }}
                </div>
                <div class="mb-2">
                    <strong>Last Updated:</strong> {{ $user->updated_at->format('M d, Y H:i') }}
                </div>
                <div class="mb-0">
                    <strong>Email Verified:</strong> 
                    @if($user->email_verified_at)
                        <span class="badge badge-success">Verified</span>
                    @else
                        <span class="badge badge-warning">Not Verified</span>
                    @endif
                </div>
            </div>

            <!-- Current Roles -->
            <div class="card user-edit-card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shield-alt mr-2"></i>Current Roles
                    </h6>
                </div>
                <div class="card-body">
                    @if($user->roles->count() > 0)
                        @foreach($user->roles as $role)
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <div>
                                    <strong>{{ ucfirst($role->name) }}</strong>
                                    <div class="small text-muted">{{ $role->permissions->count() }} permissions</div>
                                </div>
                                <span class="badge badge-primary">Active</span>
                            </div>
                        @endforeach
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-user-slash fa-2x mb-2"></i>
                            <div>No roles assigned</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card user-edit-card shadow">
                <div class="card-body">
                    <button type="submit" class="btn btn-custom btn-block btn-lg mb-3" id="saveBtn">
                        <i class="fas fa-save mr-2"></i>Update User
                    </button>
                    
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times mr-2"></i>Cancel Changes
                    </a>
                    
                    <hr>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Changes will take effect immediately
                        </small>
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
    // Role selection styling
    $('input[name="roles[]"]').on('change', function() {
        const roleOption = $(this).closest('.role-option');
        if ($(this).is(':checked')) {
            roleOption.addClass('selected');
        } else {
            roleOption.removeClass('selected');
        }
    });
    
    // Form submission
    $('#userEditForm').on('submit', function(e) {
        // Show loading state
        $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Updating User...');
        
        // Optional: Add client-side validation
        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        
        if (!name || !email) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Update User');
            return false;
        }
        
        // Password confirmation check
        const password = $('#password').val();
        const passwordConfirm = $('#password_confirmation').val();
        
        if (password && password !== passwordConfirm) {
            e.preventDefault();
            alert('Password confirmation does not match.');
            $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Update User');
            return false;
        }
    });
    
    // Email validation
    $('#email').on('blur', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
            if (!$(this).siblings('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Please enter a valid email address.</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
    
    // Password strength indicator (optional)
    $('#password').on('keyup', function() {
        const password = $(this).val();
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        // Remove existing strength indicator
        $(this).siblings('.password-strength').remove();
        
        if (password.length > 0) {
            let strengthText = '';
            let strengthClass = '';
            
            switch(strength) {
                case 0:
                case 1:
                    strengthText = 'Very Weak';
                    strengthClass = 'text-danger';
                    break;
                case 2:
                    strengthText = 'Weak';
                    strengthClass = 'text-warning';
                    break;
                case 3:
                    strengthText = 'Fair';
                    strengthClass = 'text-info';
                    break;
                case 4:
                    strengthText = 'Good';
                    strengthClass = 'text-success';
                    break;
                case 5:
                    strengthText = 'Strong';
                    strengthClass = 'text-success font-weight-bold';
                    break;
            }
            
            $(this).after(`<small class="password-strength ${strengthClass}">Password strength: ${strengthText}</small>`);
        }
    });
});
</script>
@endpush