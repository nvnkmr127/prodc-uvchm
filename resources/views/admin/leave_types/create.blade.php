@extends('layouts.theme')
@section('title', 'Add Leave Type')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Leave Type</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.leave-types.store') }}" method="POST">
            @csrf
            <div class="form-group mb-3">
                <label for="name">Leave Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Casual Leave, Sick Leave" required>
            </div>
            <div class="form-group mb-3">
                <label for="days_per_year">Days Allotted Per Year</label>
                <input type="number" name="days_per_year" class="form-control" value="0" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Leave Type</button>
        </form>
    </div>
</div>
@endsection