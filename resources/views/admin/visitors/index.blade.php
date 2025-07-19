@extends('layouts.theme')
@section('title', 'Visitor Book')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Visitor Book</h1>
    <a href="{{ route('admin.visitors.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Visitor Entry</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Visitor Name</th><th>Phone</th><th>Purpose</th><th>Check-in Time</th><th>Check-out Time</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($visitors as $visitor)
                        <tr>
                            <td>{{ $visitor->visitor_name }}</td>
                            <td>{{ $visitor->phone_number }}</td>
                            <td>{{ $visitor->purpose_of_visit }}</td>
                            <td>{{ \Carbon\Carbon::parse($visitor->check_in_time)->format('d M, Y h:i A') }}</td>
                            <td>
                                @if($visitor->check_out_time)
                                    {{ \Carbon\Carbon::parse($visitor->check_out_time)->format('d M, Y h:i A') }}
                                @else
                                    <span class="text-muted">Not Checked Out</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.visitors.edit', $visitor) }}" class="btn btn-warning btn-sm">Edit / Check Out</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No visitor records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection