@extends('layouts.theme')
@section('title', 'Add New Widget')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Add New Widget to Library</h1>
    <a href="{{ route('admin.widgets.index') }}" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Widget Library</a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Widget Details</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info">Note: Widgets are now automatically discovered from view files. This form can be used to manually add a widget if needed.</div>
        <form action="{{ route('admin.widgets.store') }}" method="POST">
            @csrf
            <div class="form-group mb-3">
                <label for="name">Widget Name*</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Total Students Card" required>
            </div>
            <div class="form-group mb-3">
                <label for="view_path">View Path*</label>
                <input type="text" name="view_path" class="form-control" placeholder="e.g., admin.dashboard.widgets.total_students" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Widget</button>
        </form>
    </div>
</div>
@endsection
