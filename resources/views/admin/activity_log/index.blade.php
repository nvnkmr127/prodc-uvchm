@extends('layouts.theme')
@section('title', 'Activity Log')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Application Activity Log</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>User</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr>
                            <td>{{ $activity->description }}</td>
                            <td>{{ $activity->causer->name ?? 'System' }}</td>
                            <td>{{ $activity->created_at->format('d M, Y h:i A') }}</td>
                        </tr>
                    @empty
                            <tr><td colspan="4" class="text-center">No activity found matching your criteria.</td></tr>

                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection