@extends('layouts.theme')

@section('title', 'Faculty Attendance')

@section('content')
<div class="container-fluid">
    {{-- Page Heading --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-calendar-check text-success mr-2"></i>Faculty Attendance
            </h1>
            <p class="text-muted mb-0">Monitor faculty attendance, working hours, and timing cutoffs</p>
        </div>
        <div class="btn-group shadow-sm" role="group">
            <button type="button" class="btn btn-success" onclick="exportData('xlsx')">
                <i class="fas fa-file-excel mr-1"></i> Export Excel
            </button>
            <button type="button" class="btn btn-outline-success" onclick="exportData('csv')">
                <i class="fas fa-file-csv mr-1"></i> Export CSV
            </button>
            <a href="{{ route('admin.attendance.settings') }}" class="btn btn-outline-secondary">
                <i class="fas fa-cog mr-1"></i> Settings
            </a>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Currently Checked In
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['checked_in_today'] }}</div>
                            <small class="text-muted">Faculty on site today</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Present Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['present_today'] }}</div>
                            <small class="text-muted">Excludes absent and leave</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Late Arrivals Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['late_today'] }}</div>
                            <small class="text-muted">Checked in after start time</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Avg. Daily Working Hours
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['avg_working_hours'], 2) }} hrs</div>
                            <small class="text-muted">For completed check-outs</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-success">
                <i class="fas fa-filter mr-1"></i> Search & Filters
            </h6>
            <button class="btn btn-sm btn-link text-success p-0 font-weight-bold" onclick="resetFilters()">
                <i class="fas fa-undo mr-1"></i> Reset
            </button>
        </div>
        <div class="card-body">
            <form id="filterForm" method="GET" action="{{ route('admin.faculty-attendance.index') }}">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Search Faculty</label>
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="Name, ID, Email..." value="{{ $searchQuery }}">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-control form-control-sm">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ $departmentFilter == $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="present" {{ $statusFilter == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="late" {{ $statusFilter == 'late' ? 'selected' : '' }}>Late</option>
                            <option value="half_day" {{ $statusFilter == 'half_day' ? 'selected' : '' }}>Half Day</option>
                            <option value="absent" {{ $statusFilter == 'absent' ? 'selected' : '' }}>Absent</option>
                            <option value="excused" {{ $statusFilter == 'excused' ? 'selected' : '' }}>On Leave</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label">Date Preset</label>
                        <select name="date_range" id="dateRangePreset" class="form-control form-control-sm" onchange="handleDatePresetChange()">
                            <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="this_week" {{ $dateRange == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="last_week" {{ $dateRange == 'last_week' ? 'selected' : '' }}>Last Week</option>
                            <option value="this_month" {{ $dateRange == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $dateRange == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3 d-flex gap-2 align-items-end" id="customDateInputs" style="display: {{ $dateRange == 'custom' ? 'flex' : 'none' }} !important;">
                        <div>
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="startDateInput" class="form-control form-control-sm" 
                                   value="{{ $startDate ? $startDate->toDateString() : '' }}">
                        </div>
                        <div>
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="endDateInput" class="form-control form-control-sm" 
                                   value="{{ $endDate ? $endDate->toDateString() : '' }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-sm btn-success px-4">
                            <i class="fas fa-search mr-1"></i> Search Records
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        {{-- Attendance Records --}}
        <div class="col-lg-9 col-md-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-list mr-1"></i> Attendance Records
                    </h6>
                    <span class="badge badge-secondary">{{ $records instanceof \Illuminate\Pagination\LengthAwarePaginator ? $records->total() : count($records) }} Records found</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-items-center">
                            <thead class="bg-light text-uppercase text-xs font-weight-bold">
                                <tr>
                                    <th class="px-4 py-3">Faculty Member</th>
                                    <th>Employee ID</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Working Hours</th>
                                    <th>Status</th>
                                    <th class="px-4">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @forelse($records as $rec)
                                    <tr>
                                        <td class="px-4 font-weight-bold text-gray-800">{{ $rec['faculty_name'] }}</td>
                                        <td><code>{{ $rec['employee_id'] ?? 'N/A' }}</code></td>
                                        <td>{{ $rec['department'] ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($rec['date'])->format('d M Y') }}</td>
                                        <td class="text-success font-weight-bold">
                                            @if($rec['check_in'])
                                                {{ \Carbon\Carbon::parse($rec['check_in'])->format('h:i A') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-info font-weight-bold">
                                            @if($rec['check_out'])
                                                {{ \Carbon\Carbon::parse($rec['check_out'])->format('h:i A') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($rec['working_hours'] !== null)
                                                <span class="badge badge-light px-2 py-1 font-weight-bold">
                                                    {{ number_format($rec['working_hours'], 2) }} hrs
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match($rec['status']) {
                                                    'present' => 'badge-success',
                                                    'late' => 'badge-warning',
                                                    'half_day' => 'badge-info',
                                                    'absent' => 'badge-danger',
                                                    'excused' => 'badge-primary',
                                                    default => 'badge-secondary'
                                                };
                                                $label = match($rec['status']) {
                                                    'present' => 'Present',
                                                    'late' => 'Late',
                                                    'half_day' => 'Half Day',
                                                    'absent' => 'Absent',
                                                    'excused' => 'On Leave',
                                                    default => 'Weekend/Holiday'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }} px-2 py-1 text-uppercase text-xs font-weight-bold">
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td class="px-4 text-xs text-muted">{{ $rec['notes'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fas fa-info-circle mr-2 fa-2x mb-3 text-gray-300 d-block"></i>
                                            No attendance records found matching filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
                        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                            <div class="text-muted text-xs">
                                Showing {{ $records->firstItem() }} to {{ $records->lastItem() }} of {{ $records->total() }} records
                            </div>
                            <div class="pagination-sm">
                                {{ $records->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Real-time Checked In Side Monitor --}}
        <div class="col-lg-3 col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-gradient-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-broadcast-tower mr-1"></i> Live Active Faculty
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($realtimeCheckedIn as $active)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <h6 class="mb-0 text-sm font-weight-bold text-gray-800">
                                        <span class="spinner-grow spinner-grow-sm text-success mr-2" style="width: 8px; height: 8px;"></span>
                                        {{ $active['faculty']->name }}
                                    </h6>
                                    <small class="text-muted ml-4">
                                        {{ $active['faculty']->department ?? 'Staff' }}
                                    </small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-success font-weight-bold">
                                        {{ \Carbon\Carbon::parse($active['check_in'])->format('h:i A') }}
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-5 text-muted">
                                <i class="fas fa-fingerprint fa-2x mb-2 text-gray-300 d-block"></i>
                                No faculty currently checked in.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleDatePresetChange() {
        const preset = $('#dateRangePreset').val();
        if (preset === 'custom') {
            $('#customDateInputs').attr('style', 'display: flex !important;');
            $('#startDateInput, #endDateInput').prop('required', true);
        } else {
            $('#customDateInputs').attr('style', 'display: none !important;');
            $('#startDateInput, #endDateInput').prop('required', false);
        }
    }

    function resetFilters() {
        window.location.href = "{{ route('admin.faculty-attendance.index') }}";
    }

    function exportData(format) {
        const searchParams = new URLSearchParams($('#filterForm').serialize());
        window.location.href = "{{ route('admin.faculty-attendance.export', ['format' => ':format']) }}".replace(':format', format) + '?' + searchParams.toString();
    }
</script>
@endpush
