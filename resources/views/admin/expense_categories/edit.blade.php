@extends('layouts.theme')
@section('title', 'Edit Expense Category')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Expense Category</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.expense-categories.update', $expenseCategory) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" class="form-control" value="{{ $expenseCategory->name }}" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Update Category</button>
            <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-secondary mt-3">Cancel</a>
        </form>
    </div>
</div>
@endsection