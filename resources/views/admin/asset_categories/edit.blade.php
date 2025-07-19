@extends('layouts.theme')
@section('title', 'Edit Asset Category')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Asset Category</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.asset-categories.update', $assetCategory) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" class="form-control" value="{{ $assetCategory->name }}" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Update Category</button>
        </form>
    </div>
</div>
@endsection