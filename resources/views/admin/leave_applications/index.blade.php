@extends('layouts.theme')
@section('title', 'Leave Applications')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Manage Leave Applications</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Applicant</th><th>Type</th><th>Dates</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($applications as $app)
                <tr>
                    <td>{{ $app->user->name }}</td>
                    <td>{{ $app->leaveType->name }}</td>
                    <td>{{ $app->start_date }} to {{ $app->end_date }}</td>
                    <td>{{ $app->reason }}</td>
                    <td><span class="badge badge-{{ $app->status == 'Approved' ? 'success' : ($app->status == 'Rejected' ? 'danger' : 'warning') }}">{{ $app->status }}</span></td>
                    <td>
                        @if($app->status == 'Pending')
                        <form action="{{ route('admin.leave-applications.updateStatus', $app) }}" method="POST" class="d-inline"> @csrf <input type="hidden" name="status" value="Approved"><button type="submit" class="btn btn-success btn-sm">Approve</button></form>
                        <form action="{{ route('admin.leave-applications.updateStatus', $app) }}" method="POST" class="d-inline"> @csrf <input type="hidden" name="status" value="Rejected"><button type="submit" class="btn btn-danger btn-sm">Reject</button></form>
                        @else
                        Processed by {{ $app->approver->name ?? 'Admin' }}
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center">No pending leave applications.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection