@extends('layouts.theme')
@section('title', 'Bulk Generate Time Slots')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Bulk Time Slot Generator</h1>
    <a href="{{ route('admin.time-slots.index') }}" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List</a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Generator Settings</h6>
    </div>
    <div class="card-body">
        <p>Use this tool to automatically create all the time slots for a full college day. The system will create slots of a specified duration with a break in between each one.</p>
        <hr>
        <form action="{{ route('admin.time-slots.generate.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-3">
                    <label for="start_time" class="form-label">College Start Time*</label>
                    <input type="time" name="start_time" class="form-control" value="09:00" required>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <label for="end_time" class="form-label">College End Time*</label>
                    <input type="time" name="end_time" class="form-control" value="17:00" required>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <label for="duration" class="form-label">Class Duration (minutes)*</label>
                    <input type="number" name="duration" class="form-control" value="60" required>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <label for="break_duration" class="form-label">Break Between Classes (minutes)</label>
                    <input type="number" name="break_duration" class="form-control" value="10">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Generate Slots</button>
        </form>
    </div>
</div>
@endsection
