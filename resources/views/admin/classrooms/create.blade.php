@extends('layouts.theme')
@section('title', 'Add Classroom')
@section('content')
<h2>Add New Classroom</h2>
<form action="{{ route('admin.classrooms.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="name" class="form-label">Name (e.g., Room 101)</label>
        <input type="text" class="form-control" name="name" required>
    </div>
    <div class="mb-3">
        <label for="type" class="form-label">Type</label>
        <select name="type" id="type" class="form-control">
            <option value="lecture">Lecture</option>
            <option value="lab">Lab</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="capacity" class="form-label">Capacity</label>
        <input type="number" class="form-control" name="capacity">
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
@endsection