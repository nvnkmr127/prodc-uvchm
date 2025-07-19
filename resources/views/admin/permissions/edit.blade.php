@extends('layouts.theme')
@section('title', 'Edit Permission')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Permission</h1>
    <a href="{{ route('admin.permissions.index') }}" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Permissions</a>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Permission Details</h6>
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
                <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="name">Permission Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $permission->name) }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Permission</button>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Roles with this Permission</h6>
            </div>
            <div class="card-body">
                @if($permission->roles->isNotEmpty())
                    <div class="list-group">
                        @foreach($permission->roles as $role)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $role->name }}
                                {{-- Note: Role removal logic would be handled in the Role edit page for a better UX --}}
                            </div>
                        @endforeach
                    </div>
                @else
                    <p>No roles are currently assigned this permission.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
