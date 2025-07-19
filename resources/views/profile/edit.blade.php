@extends('layouts.theme')
@section('title', 'Profile Settings')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Profile Settings</h1>
</div>

@if(session('status') === 'profile-updated')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>Profile updated successfully!
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <!-- Profile Information -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-user mr-2"></i>Profile Information
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')

                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        
                        @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <div class="mt-2">
                                <p class="text-sm text-warning">
                                    Your email address is unverified.
                                    <button form="send-verification" class="btn btn-link p-0 text-decoration-none">
                                        Click here to re-send the verification email.
                                    </button>
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Password -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-lock mr-2"></i>Update Password
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                               id="current_password" name="current_password">
                        @error('current_password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                               id="password" name="password">
                        @error('password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" 
                               id="password_confirmation" name="password_confirmation">
                        @error('password_confirmation', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key mr-1"></i>Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Delete Account -->
        <div class="card shadow border-danger">
            <div class="card-header py-3 bg-danger">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Delete Account
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Once your account is deleted, all of its resources and data will be permanently deleted. 
                    Before deleting your account, please download any data or information that you wish to retain.
                </p>
                
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteAccountModal">
                    <i class="fas fa-trash mr-1"></i>Delete Account
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Account Information -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Account Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Account Created</small>
                    <div class="font-weight-bold">{{ $user->created_at->format('M d, Y') }}</div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Last Updated</small>
                    <div class="font-weight-bold">{{ $user->updated_at->format('M d, Y') }}</div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Email Status</small>
                    <div>
                        @if($user->hasVerifiedEmail())
                            <span class="badge badge-success">Verified</span>
                        @else
                            <span class="badge badge-warning">Unverified</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger mr-2"></i>Delete Account
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('profile.destroy') }}">
                <div class="modal-body">
                    @csrf
                    @method('delete')
                    
                    <div class="alert alert-danger">
                        <strong>Warning:</strong> This action cannot be undone. All your data will be permanently deleted.
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Please enter your password to confirm:</label>
                        <input type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" 
                               id="password" name="password" required>
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-1"></i>Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
    <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
        @csrf
    </form>
@endif
@endsection