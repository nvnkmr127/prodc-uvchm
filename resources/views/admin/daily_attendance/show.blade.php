@extends('layouts.theme')
@section('title', 'Daily Attendance')

@push('styles')
{{-- Custom styles for the modern attendance buttons --}}
<style>
    .btn-check {
        position: absolute;
        clip: rect(0,0,0,0);
        pointer-events: none;
    }
    .btn-check:checked + .btn-outline-success,
    .btn-check:checked + .btn-outline-danger {
        color: #fff;
        background-color: #1cc88a;
        border-color: #1cc88a;
    }
    .btn-check:checked + .btn-outline-danger {
        background-color: #e74a3b;
        border-color: #e74a3b;
    }
</style>
@endpush

@section('content')
<h1 class="h3 mb-4 text-gray-800">Daily Attendance</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Filter Form --}}
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Select Batch and Date</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.daily-attendance.show') }}" method="GET">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label>Batch</label>
                    <select name="batch_id" class="form-control" required>
                        <option value="">-- Select a Batch --</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->course->name }} - {{ $batch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Load Students</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(isset($students))
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Mark Attendance for {{ $selectedBatch->name }} on {{ request('date') }}</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.daily-attendance.store') }}" method="POST">
            @csrf
            <input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
            <input type="hidden" name="attendance_date" value="{{ request('date') }}">
            
            {{-- Bulk Action Buttons --}}
            <div class="mb-3">
                <button type="button" id="markAllPresent" class="btn btn-sm btn-success">Mark All Present</button>
                <button type="button" id="markAllAbsent" class="btn btn-sm btn-danger">Mark All Absent</button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Student Name</th>
                            <th>Enrollment #</th>
                            <th class="text-center">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td>{{$student->name}}</td>
                            <td>{{$student->enrollment_number}}</td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="attendance[{{ $student->id }}]" id="present_{{$student->id}}" value="present" autocomplete="off" {{ ($student->todays_attendance_status ?? 'present') == 'present' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-success" for="present_{{$student->id}}">Present</label>

                                    <input type="radio" class="btn-check" name="attendance[{{ $student->id }}]" id="absent_{{$student->id}}" value="absent" autocomplete="off" {{ ($student->todays_attendance_status ?? '') == 'absent' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-danger" for="absent_{{$student->id}}">Absent</label>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-success mt-3">Submit Attendance</button>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize the interactive DataTable
    $('#dataTable').DataTable({
        "paging": false,
        "info": false
    });

    // Logic for "Mark All" buttons
    $('#markAllPresent').on('click', function() {
        $('input[type=radio][value=present]').prop('checked', true);
    });

    $('#markAllAbsent').on('click', function() {
        $('input[type=radio][value=absent]').prop('checked', true);
    });
});
</script>
@endpush
