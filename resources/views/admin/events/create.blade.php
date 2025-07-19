@extends('layouts.theme')
@section('title', 'Schedule Event')
@section('content')
<h2>Schedule New Event (Exam, Workshop, etc.)</h2>
<form action="{{ route('admin.events.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="name" class="form-label">Event Name</label>
            <input type="text" class="form-control" name="name" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="event_date" class="form-label">Date</label>
            <input type="date" class="form-control" name="event_date" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="start_time" class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="end_time" class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="classroom_id" class="form-label">Classroom / Lab</label>
            <select name="classroom_id" class="form-control">
                <option value="">-- Select a Classroom (Optional) --</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="user_id" class="form-label">Faculty</label>
            <select name="user_id" class="form-control">
                <option value="">-- Select a Faculty Member (Optional) --</option>
                @foreach($faculties as $faculty)
                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                @endforeach
            </select>
        </div>
        {{-- We can add Course and Subject dropdowns if needed --}}
    </div>
    <button type="submit" class="btn btn-primary">Save Event</button>
</form>
@endsection