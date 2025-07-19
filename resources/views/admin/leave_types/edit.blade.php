@extends('layouts.theme')
@section('title', 'Edit Leave Type')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Leave Type</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.leave-types.update', $leaveType) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group mb-3">
                <label for="name">Leave Name</label>
                <input type="text" name="name" class="form-control" value="{{ $leaveType->name }}" required>
            </div>
            <div class="form-group mb-3">
                <label for="days_per_year">Days Allotted Per Year</label>
                <input type="number" name="days_per_year" class="form-control" value="{{ $leaveType->days_per_year }}" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Update Leave Type</button>
        </form>
    </div>
</div>
@endsection