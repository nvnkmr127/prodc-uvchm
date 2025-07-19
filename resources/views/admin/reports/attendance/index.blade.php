@extends('layouts.theme')
@section('title', 'Attendance Reports')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Attendance Reports</h1>

{{-- Filter Form --}}
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Filter Report</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.reports.attendance.index') }}" method="GET">
            <div class="row">
                <div class="col-md-4">
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
                <div class="col-md-3">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" required>
                </div>
                <div class="col-md-3">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Report Results --}}
@if(isset($reportData))
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Report Results</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Enrollment #</th>
                        <th class="text-center">Total Working Days</th>
                        <th class="text-center">Days Present</th>
                        <th class="text-center">Days Absent</th>
                        <th class="text-center">Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData as $data)
                    <tr>
                        <td>{{ $data['student_name'] }}</td>
                        <td>{{ $data['enrollment_number'] }}</td>
                        <td class="text-center">{{ $data['total_working_days'] }}</td>
                        <td class="text-center">{{ $data['present_days'] }}</td>
                        <td class="text-center">{{ $data['absent_days'] }}</td>
                        <td class="text-center">
                            @php
                                $percentage = $data['attendance_percentage'];
                                $color = $percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                            @endphp
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-{{$color}}" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $percentage }}%</div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection