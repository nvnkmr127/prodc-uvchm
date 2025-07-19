@extends('layouts.theme')
@section('title', 'Scheduled Events')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Scheduled Events</h1>
    <a href="{{ route('admin.events.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Schedule New Event</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Classroom</th>
                        <th>Faculty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>{{ $event->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($event->event_date)->format('d M, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($event->end_time)->format('h:i A') }}</td>
                            <td>{{ $event->classroom->name ?? 'N/A' }}</td>
                            <td>{{ $event->user->name ?? 'N/A' }}</td>
                            <td>
                                <form action="{{ route('admin.events.destroy', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No events scheduled.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection