@extends('layouts.theme')
@section('title', 'Edit Time Slot')
@section('content')
<h2>Edit Time Slot</h2>
<form action="{{ route('admin.time-slots.update', $time_slot) }}" method="POST">
    @csrf
    @method('PATCH')
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="start_time" class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" value="{{ $time_slot->start_time }}" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="end_time" class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" value="{{ $time_slot->end_time }}" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
</form>
@endsection