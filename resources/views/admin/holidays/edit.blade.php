@extends('layouts.theme')
@section('title', 'Edit Holiday')
@section('content')
<h2>Edit Holiday</h2>
<form action="{{ route('admin.holidays.update', $holiday) }}" method="POST">
    @csrf
    @method('PATCH')
    <div class="mb-3">
        <label for="name" class="form-label">Holiday Name</label>
        <input type="text" class="form-control" name="name" value="{{ $holiday->name }}" required>
    </div>
    <div class="mb-3">
        <label for="date" class="form-label">Date</label>
        <input type="date" class="form-control" name="date" value="{{ $holiday->date }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
</form>
@endsection