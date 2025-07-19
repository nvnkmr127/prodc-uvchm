@extends('layouts.theme')
@section('title', 'Add Asset Category')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Asset Category</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.asset-categories.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Kitchen Equipment, Furniture" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Category</button>
        </form>
    </div>
</div>
@endsection