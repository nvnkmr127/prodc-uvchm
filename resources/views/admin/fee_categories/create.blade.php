@extends('layouts.theme')
@section('title', 'Add Fee Category')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Fee Category</h1>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.fee-categories.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Tuition Fee, Uniform Fee" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Category</button>
            <a href="{{ route('admin.fee-categories.index') }}" class="btn btn-secondary mt-3">Cancel</a>
        </form>
    </div>
</div>
@endsection