@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Create Timetable Entry</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('timetable.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Batch</label>
                        <select name="batch_id" class="form-control" required>
                            <option value="">Select Batch</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Subject</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Faculty</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Select Faculty</option>
                            @foreach($faculty as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Classroom</label>
                        <select name="classroom_id" class="form-control" required>
                            <option value="">Select Classroom</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Time Slot</label>
                        <select name="time_slot_id" class="form-control" required>
                            <option value="">Select Time Slot</option>
                            @foreach($timeSlots as $slot)
                                <option value="{{ $slot->id }}">
                                    {{ $slot->start_time }} - {{ $slot->end_time }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Schedule Date</label>
                        <input type="date" name="schedule_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Create Timetable Entry
                    </button>
                    <a href="{{ route('timetable.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection