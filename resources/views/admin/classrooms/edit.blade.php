@extends('layouts.theme')
@section('title', 'Edit Classroom')
@section('content')
<h2>Edit Classroom</h2>
<form action="{{ route('admin.classrooms.update', $classroom) }}" method="POST">
    @csrf
    @method('PATCH')
    <div class="mb-3">
        <label for="name" class="form-label">Name (e.g., Room 101)</label>
        <input type="text" class="form-control" name="name" value="{{ $classroom->name }}" required>
    </div>
    <div class="mb-3">
        <label for="type" class="form-label">Type</label>
        <select name="type" id="type" class="form-control">
            <option value="lecture" @if($classroom->type == 'lecture') selected @endif>Lecture</option>
            <option value="lab" @if($classroom->type == 'lab') selected @endif>Lab</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="capacity" class="form-label">Capacity</label>
        <input type="number" class="form-control" name="capacity" value="{{ $classroom->capacity }}">
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
</form>
@endsection