@extends('layouts.theme')
@section('title', 'Create Permission')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Create New Permission</h1>
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
                <form action="{{ route('admin.permissions.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Permission Name</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="e.g., manage students" value="{{ old('name') }}" required>
                        <small class="form-text text-muted">Use the format "action resource" (e.g., "view reports", "delete users").</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Permission</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
