@extends('layouts.app') {{-- We use the simple layout with Bootstrap CDN --}}
@section('title', 'Take Attendance')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-2">Take Attendance</h2>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">{{ $timetable->batch->course->name }} - {{ $timetable->batch->name }}</h5>
            <p class="card-text">
                <strong>Subject:</strong> {{ $timetable->subject->name }} <br>
                <strong>Time:</strong> {{ \Carbon\Carbon::parse($timetable->timeSlot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($timetable->timeSlot->end_time)->format('h:i A') }} <br>
                <strong>Date:</strong> {{ \Carbon\Carbon::parse($timetable->schedule_date)->format('d M, Y') }}
            </p>
        </div>
    </div>

    <form action="{{ route('faculty.attendance.store') }}" method="POST">
        @csrf
        {{-- Hidden fields to identify the class --}}
        <input type="hidden" name="batch_id" value="{{ $timetable->batch_id }}">
        <input type="hidden" name="subject_id" value="{{ $timetable->subject_id }}">
        <input type="hidden" name="time_slot_id" value="{{ $timetable->time_slot_id }}">
        <input type="hidden" name="attendance_date" value="{{ $timetable->schedule_date }}">

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Enrollment #</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Absent</th>
                                <th class="text-center">Late</th>
                                <th class="text-center">Excused</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                @php
                                    // Check if attendance was already taken for this student today
                                    $todays_attendance = $existing_attendance->where('student_id', $student->id)->first();
                                @endphp
                                <tr>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->enrollment_number }}</td>
                                    <td class="text-center"><input type="radio" name="attendance[{{ $student->id }}]" value="present" class="form-check-input" {{ ($todays_attendance->status ?? 'present') == 'present' ? 'checked' : '' }}></td>
                                    <td class="text-center"><input type="radio" name="attendance[{{ $student->id }}]" value="absent" class="form-check-input" {{ ($todays_attendance->status ?? '') == 'absent' ? 'checked' : '' }}></td>
                                    <td class="text-center"><input type="radio" name="attendance[{{ $student->id }}]" value="late" class="form-check-input" {{ ($todays_attendance->status ?? '') == 'late' ? 'checked' : '' }}></td>
                                    <td class="text-center"><input type="radio" name="attendance[{{ $student->id }}]" value="excused" class="form-check-input" {{ ($todays_attendance->status ?? '') == 'excused' ? 'checked' : '' }}></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-success">Submit Attendance</button>
            </div>
        </div>
    </form>
</div>
@endsection