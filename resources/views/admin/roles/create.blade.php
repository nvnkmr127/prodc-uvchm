@extends('layouts.theme')
@section('title', 'Create Role')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Create New Role</h1>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Roles</a>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Role Details</h6>
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
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Role Name</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="e.g., accountant" value="{{ old('name') }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
