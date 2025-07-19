@extends('layouts.theme')
@section('title', 'Manage Time Slots')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Time Slots</h1>
    <a href="{{ route('admin.time-slots.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Time Slot</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Defined Time Slots</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            {{-- The id="dataTable" activates the interactive features --}}
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Duration</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($time_slots as $slot)
                        <tr>
                            <td>{{ $slot->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}</td>
                            <td>{{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}</td>
                            <td>
                                {{-- Automatically calculate and display the duration in minutes --}}
                                {{ \Carbon\Carbon::parse($slot->start_time)->diffInMinutes(\Carbon\Carbon::parse($slot->end_time)) }} minutes
                            </td>
                            <td>
                                <a href="{{ route('admin.time-slots.edit', $slot) }}" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.time-slots.destroy', $slot) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No time slots found. Add one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- This script activates the interactive table features --}}
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            // You can customize options here, for example, default sorting
            "order": [[ 1, "asc" ]] // Initially sort by the second column (Start Time)
        });
    });
</script>
@endpush
