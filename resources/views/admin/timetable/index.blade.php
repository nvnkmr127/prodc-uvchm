@extends('layouts.theme')
@section('title', 'Timetable List')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Timetable Entries</h1>
        <div>
            <a href="{{ route('admin.timetable.hub') }}" class="btn btn-info shadow-sm">
                <i class="fas fa-calendar-alt text-white-50"></i> Timetable Hub
            </a>
            <a href="{{ route('admin.timetable.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus text-white-50"></i> Add New Entry
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Scheduled Classes</h6>

            {{-- Filters (Basic) --}}
            <form action="{{ route('admin.timetable.index') }}" method="GET" class="form-inline">
                <select name="batch_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">-- All Batches --</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                            {{ $batch->name }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="date_from" class="form-control form-control-sm mr-2"
                    value="{{ request('date_from') }}" placeholder="From Date">
                <input type="date" name="date_to" class="form-control form-control-sm mr-2" value="{{ request('date_to') }}"
                    placeholder="To Date">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Batch</th>
                            <th>Subject</th>
                            <th>Faculty</th>
                            <th>Classroom</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($timetables as $timetable)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($timetable->schedule_date)->format('d M Y') }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($timetable->timeSlot->start_time)->format('h:i A') }} -
                                    {{ \Carbon\Carbon::parse($timetable->timeSlot->end_time)->format('h:i A') }}
                                </td>
                                <td>{{ $timetable->batch->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $timetable->subject->name ?? 'N/A' }}
                                    @if($timetable->is_lab_session) <span class="badge badge-info ms-1">Lab</span> @endif
                                </td>
                                <td>{{ $timetable->user->name ?? 'Unassigned' }}</td>
                                <td>{{ $timetable->classroom->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-{{ $timetable->status == 'scheduled' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($timetable->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.timetable.edit', $timetable->id) }}"
                                            class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.timetable.destroy', $timetable->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this class?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No scheduled classes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">
                {{ $timetables->links() }}
            </div>
        </div>
    </div>
@endsection