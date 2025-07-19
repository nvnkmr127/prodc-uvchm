@extends('layouts.theme')
@section('title', 'Add Time Slot')
@section('content')
<h2>Add New Time Slot</h2>
<form action="{{ route('admin.time-slots.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="start_time" class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="end_time" class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
@endsection