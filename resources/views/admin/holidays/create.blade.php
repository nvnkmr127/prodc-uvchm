@extends('layouts.theme')
@section('title', 'Add Holiday')
@section('content')
<h2>Add New Holiday</h2>
<form action="{{ route('admin.holidays.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="name" class="form-label">Holiday Name</label>
        <input type="text" class="form-control" name="name" required>
    </div>
    <div class="mb-3">
        <label for="date" class="form-label">Date</label>
        <input type="date" class="form-control" name="date" required>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
@endsection