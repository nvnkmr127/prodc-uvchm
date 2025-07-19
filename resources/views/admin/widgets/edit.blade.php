@extends('layouts.theme')
@section('title', 'Edit Widget')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Widget</h1>
    <a href="{{ route('admin.widgets.index') }}" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Widget Library</a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Widget Details</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.widgets.update', $widget) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group mb-3">
                <label for="name">Widget Name*</label>
                <input type="text" name="name" class="form-control" value="{{ $widget->name }}" required>
            </div>
            <div class="form-group mb-3">
                <label for="description">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ $widget->description }}</textarea>
            </div>
            <div class="form-group mb-3">
                <label for="view_path">View Path*</label>
                <input type="text" name="view_path" class="form-control" value="{{ $widget->view_path }}" required readonly>
                <small class="form-text text-muted">The view path is set automatically and should not be changed.</small>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Update Widget</button>
        </form>
    </div>
</div>
@endsection
