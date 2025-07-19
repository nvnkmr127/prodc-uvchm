@extends('layouts.app') {{-- We use the simple layout with Bootstrap CDN --}}

@section('content')
<div class="container">
    <h2 class="mb-4">Faculty Dashboard</h2>
    <p>Welcome, {{ Auth::user()->name }}. Here are your scheduled classes for today, <strong>{{ date('d M, Y') }}</strong>.</p>

    <div class="card">
        <div class="card-header">My Schedule for Today</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Batch</th>
                        <th>Subject</th>
                        <th>Classroom</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myClassesToday as $class)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($class->timeSlot->start_time)->format('h:i A') }}</td>
                            <td>{{ $class->batch->name }}</td>
                            <td>{{ $class->subject->name }}</td>
                            <td>{{ $class->classroom->name }}</td>
                            <td>
                                {{-- This link won't work yet --}}
                               <a href="{{ route('faculty.attendance.create', $class) }}" class="btn btn-primary">Take Attendance</a>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">You have no classes scheduled for today.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection