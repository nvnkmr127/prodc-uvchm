@extends('layouts.theme')
@section('title', 'Edit Salary Component')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Salary Component</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.salary-components.update', $salaryComponent) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group mb-3">
                <label for="name">Component Name</label>
                <input type="text" name="name" class="form-control" value="{{ $salaryComponent->name }}" required>
            </div>
            <div class="form-group mb-3">
                <label for="type">Component Type</label>
                <select name="type" class="form-control" required>
                    <option value="Earning" @if($salaryComponent->type == 'Earning') selected @endif>Earning</option>
                    <option value="Deduction" @if($salaryComponent->type == 'Deduction') selected @endif>Deduction</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Update Component</button>
        </form>
    </div>
</div>
@endsection