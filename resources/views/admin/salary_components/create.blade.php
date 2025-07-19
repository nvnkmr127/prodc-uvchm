@extends('layouts.theme')
@section('title', 'Add Salary Component')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Salary Component</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.salary-components.store') }}" method="POST">
            @csrf
            <div class="form-group mb-3">
                <label for="name">Component Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Basic Pay, Provident Fund" required>
            </div>
            <div class="form-group mb-3">
                <label for="type">Component Type</label>
                <select name="type" class="form-control" required>
                    <option value="Earning">Earning</option>
                    <option value="Deduction">Deduction</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Component</button>
        </form>
    </div>
</div>
@endsection