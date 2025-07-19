@extends('layouts.app')
@section('title', 'My Leave Applications')

@section('content')
<div class="container">
    <h2 class="mb-4">My Leave Dashboard</h2>

    {{-- Leave Balances --}}
    <div class="row mb-4">
        @foreach($leaveBalances as $balance)
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-header">{{ $balance->leaveType->name }}</div>
                <div class="card-body">
                    <h3 class="card-title">{{ $balance->remaining_days }} / {{ $balance->leaveType->days_per_year }}</h3>
                    <p class="card-text">Days Remaining</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Apply for Leave Form --}}
    <div class="card mb-4">
        <div class="card-header">Apply for New Leave</div>
        <div class="card-body">
            <form action="{{ route('faculty.my-leave.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Leave Type</label><select name="leave_type_id" class="form-control" required>@foreach($leaveTypes as $type)<option value="{{$type->id}}">{{$type->name}}</option>@endforeach</select></div>
                    <div class="col-md-4 mb-3"><label>Start Date</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>End Date</label><input type="date" name="end_date" class="form-control" required></div>
                    <div class="col-md-12 mb-3"><label>Reason</label><textarea name="reason" class="form-control" required></textarea></div>
                </div>
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </form>
        </div>
    </div>

    {{-- My Application History --}}
    <div class="card">
        <div class="card-header">My Application History</div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Type</th><th>Dates</th><th>Reason</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($applications as $app)
                    <tr>
                        <td>{{ $app->leaveType->name }}</td>
                        <td>{{ $app->start_date }} to {{ $app->end_date }}</td>
                        <td>{{ $app->reason }}</td>
                        <td><span class="badge bg-{{ $app->status == 'Approved' ? 'success' : ($app->status == 'Rejected' ? 'danger' : 'warning') }}">{{ $app->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4">You have not applied for any leave yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection