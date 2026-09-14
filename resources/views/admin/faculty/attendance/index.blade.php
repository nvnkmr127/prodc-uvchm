@extends('layouts.theme')

@section('title', 'Faculty Attendance & Timing')

@push('styles')
<style>
    /* Modern Dashboard Styling for Faculty Attendance */
    .attendance-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.75rem;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
    }
    
    .stats-card-modern {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 4px 15px -2px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease-in-out;
        position: relative;
        overflow: hidden;
        height: 100%;
    }
    .stats-card-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }
    
    .stats-card-modern .icon-wrapper {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    
    .icon-emerald { background: #ecfdf5; color: #059669; }
    .icon-amber { background: #fffbeb; color: #d97706; }
    .icon-rose { background: #fff1f2; color: #e11d48; }
    .icon-indigo { background: #eef2ff; color: #4f46e5; }
    .icon-sky { background: #f0f9ff; color: #0284c7; }
    .icon-purple { background: #faf5ff; color: #9333ea; }
    
    .stats-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .stats-label {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .stats-subtext {
        font-size: 0.775rem;
        color: #94a3b8;
    }
    
    /* Modern Holiday Banner */
    .holiday-banner {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 1px solid #f59e0b;
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        color: #92400e;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
    }
    .holiday-banner .holiday-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #f59e0b;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    /* Filter Card */
    .filter-card-modern {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        margin-bottom: 1.75rem;
    }
    .filter-card-modern .card-header {
        background: transparent;
        border-bottom: 1px solid #f1f5f9;
        padding: 1rem 1.5rem;
    }
    
    /* Modern Badges */
    .badge-modern {
        padding: 0.35rem 0.65rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.02em;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .badge-modern-present { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-modern-late { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-modern-halfday { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-modern-absent { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
    .badge-modern-leave { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
    .badge-modern-holiday { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    
    /* Table Styling */
    .modern-table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.9rem 1rem;
        border-top: none;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .modern-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.875rem;
    }
    .modern-table tbody tr:hover {
        background-color: #f8fafc;
    }
    
    /* Avatar Circle */
    .avatar-initials {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.25);
    }
    
    /* Pulse Live Dot */
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #10b981;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        70% {
            box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4">

    {{-- Top Header / Hero Section --}}
    <div class="attendance-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div class="mb-3 mb-md-0">
            <div class="text-xs text-uppercase font-weight-bold text-success mb-1" style="letter-spacing: 0.08em;">
                <i class="fas fa-shield-alt mr-1"></i> Admin Portal &bull; Attendance Management
            </div>
            <h1 class="h3 font-weight-bold text-white mb-1">
                Faculty Attendance & Timing
            </h1>
            <p class="text-white-50 small mb-0">
                <span><i class="far fa-calendar-alt text-success mr-1"></i> <strong>{{ $stats['date_range_label'] }}</strong></span>
                @if($departmentFilter)
                    <span class="mx-2">&bull;</span>
                    <span><i class="fas fa-building mr-1"></i> Dept: <strong>{{ $departmentFilter }}</strong></span>
                @endif
                <span class="mx-2">&bull;</span>
                <span><i class="fas fa-users mr-1"></i> <strong>{{ $stats['total_faculty'] }}</strong> Faculty Members</span>
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="dropdown mr-2 mb-2 mb-md-0">
                <button class="btn btn-sm btn-light dropdown-toggle font-weight-bold shadow-sm" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-download text-success mr-1"></i> Export Data
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow border-0" aria-labelledby="exportDropdown">
                    <a class="dropdown-item py-2" href="javascript:void(0)" onclick="exportData('xlsx')">
                        <i class="fas fa-file-excel text-success mr-2"></i> Export to Excel (.xlsx)
                    </a>
                    <a class="dropdown-item py-2" href="javascript:void(0)" onclick="exportData('csv')">
                        <i class="fas fa-file-csv text-info mr-2"></i> Export to CSV (.csv)
                    </a>
                </div>
            </div>
            <a href="{{ route('admin.attendance.settings') }}" class="btn btn-sm btn-outline-light font-weight-bold mb-2 mb-md-0 shadow-sm">
                <i class="fas fa-sliders-h mr-1"></i> Policy & Timings
            </a>
        </div>
    </div>

    {{-- Holiday Alert Banner --}}
    @if($stats['is_holiday_today'])
        <div class="holiday-banner">
            <div class="holiday-icon">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <div class="flex-grow-1">
                <div class="font-weight-bold" style="font-size: 1rem;">
                    {{ $stats['holiday_reason'] ?? 'Declared College Holiday / Non-Working Day' }}
                </div>
                <div class="small mt-1 text-brown">
                    @if(str_contains(strtolower($stats['holiday_reason'] ?? ''), 'login'))
                        Fewer than 2 faculty logins were detected today. The system automatically categorizes this day as a <strong>Holiday</strong>. Automated attendance alert emails are suppressed and faculty are not penalized as absent.
                    @else
                        This date is marked as an official institution holiday. Automated attendance alert emails are suppressed.
                    @endif
                </div>
            </div>
            <div class="ml-3 d-none d-md-block">
                <span class="badge badge-warning px-3 py-2 text-uppercase font-weight-bold" style="border-radius: 20px;">
                    <i class="fas fa-check-circle mr-1"></i> Holiday Active
                </span>
            </div>
        </div>
    @endif

    {{-- Dynamic Stats Cards (Working accurately for Today & Date Ranges) --}}
    <div class="row mb-4">
        {{-- Card 1: Faculty Scope or Real-Time Active --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card-modern">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stats-label">
                            {{ $stats['is_today'] ? 'Currently On Site' : 'Faculty Scope' }}
                        </div>
                        <div class="stats-value text-gray-900">
                            @if($stats['is_today'])
                                {{ $stats['checked_in_today'] }}
                                <span class="badge badge-success text-xs ml-1 font-weight-bold" style="font-size: 0.75rem; vertical-align: middle;">
                                    <span class="pulse-dot mr-1"></span> Active
                                </span>
                            @else
                                {{ $stats['total_faculty'] }}
                                <span class="text-xs text-muted font-weight-normal" style="font-size: 0.85rem;">faculty</span>
                            @endif
                        </div>
                        <div class="stats-subtext mt-1">
                            @if($stats['is_today'])
                                Out of <strong>{{ $stats['total_faculty'] }}</strong> total faculty active
                            @else
                                <strong>{{ $stats['total_working_days'] }}</strong> Working Days ({{ $stats['total_holidays'] }} Holidays)
                            @endif
                        </div>
                    </div>
                    <div class="icon-wrapper {{ $stats['is_today'] ? 'icon-emerald' : 'icon-indigo' }}">
                        <i class="fas {{ $stats['is_today'] ? 'fa-sign-in-alt' : 'fa-users' }}"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Attendance Rate & Present Count --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card-modern">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stats-label">
                            {{ $stats['is_single_day'] ? 'Present Count' : 'Attendance Rate' }}
                        </div>
                        <div class="stats-value text-emerald-600" style="color: #059669;">
                            @if($stats['is_single_day'])
                                {{ $stats['present_count'] }}
                                <span class="text-muted font-weight-normal" style="font-size: 0.85rem;">/ {{ $stats['total_faculty'] }}</span>
                            @else
                                {{ $stats['attendance_percentage'] }}%
                            @endif
                        </div>
                        <div class="stats-subtext mt-1">
                            @if($stats['is_single_day'])
                                <strong>{{ $stats['attendance_percentage'] }}%</strong> turn-out rate
                            @else
                                <strong>{{ $stats['total_present_days'] }}</strong> total present person-days
                            @endif
                        </div>
                    </div>
                    <div class="icon-wrapper icon-emerald">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                {{-- Mini progress bar --}}
                <div class="progress mt-2" style="height: 4px; border-radius: 2px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $stats['attendance_percentage'] }}%;" aria-valuenow="{{ $stats['attendance_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        {{-- Card 3: Late Check-Ins --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card-modern">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stats-label">Late Arrivals</div>
                        <div class="stats-value text-amber-600" style="color: #d97706;">
                            {{ $stats['late_count'] }}
                        </div>
                        <div class="stats-subtext mt-1">
                            @if($stats['is_single_day'])
                                Checked in after official cutoff
                            @else
                                Total late arrivals in period
                            @endif
                        </div>
                    </div>
                    <div class="icon-wrapper icon-amber">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4: Absences & Approved Leaves / Working Hours --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card-modern">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stats-label">
                            {{ $stats['is_single_day'] ? 'Absent / On Leave' : 'Avg Daily Working Hours' }}
                        </div>
                        <div class="stats-value">
                            @if($stats['is_single_day'])
                                <span style="color: #e11d48;">{{ $stats['absent_count'] }}</span>
                                <span class="text-muted font-weight-normal" style="font-size: 1rem;">absent</span>
                                @if($stats['leave_count'] > 0)
                                    <span class="text-muted" style="font-size: 0.85rem;">/ {{ $stats['leave_count'] }} lv</span>
                                @endif
                            @else
                                <span style="color: #0284c7;">{{ number_format($stats['avg_working_hours'], 1) }}</span>
                                <span class="text-muted font-weight-normal" style="font-size: 0.95rem;">hrs/day</span>
                            @endif
                        </div>
                        <div class="stats-subtext mt-1">
                            @if($stats['is_single_day'])
                                Avg daily work: <strong>{{ number_format($stats['avg_working_hours'], 1) }} hrs</strong>
                            @else
                                Total absent days: <strong>{{ $stats['absent_count'] }}</strong> ({{ $stats['leave_count'] }} leaves)
                            @endif
                        </div>
                    </div>
                    <div class="icon-wrapper {{ $stats['is_single_day'] ? 'icon-rose' : 'icon-sky' }}">
                        <i class="fas {{ $stats['is_single_day'] ? 'fa-user-times' : 'fa-hourglass-half' }}"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <span class="mr-2 text-success"><i class="fas fa-filter"></i></span>
                <span class="font-weight-bold text-gray-800" style="font-size: 0.95rem;">Filter & Search Attendance</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="resetFilters()">
                <i class="fas fa-redo-alt mr-1"></i> Reset Filters
            </button>
        </div>
        <div class="card-body p-3 p-md-4">
            <form id="filterForm" method="GET" action="{{ route('admin.faculty-attendance.index') }}">
                <div class="form-row">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Search Faculty</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control border-left-0" 
                                   placeholder="Name, ID, or Email..." value="{{ $searchQuery }}">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Department</label>
                        <select name="department" class="form-control form-control-sm custom-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ $departmentFilter == $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Status</label>
                        <select name="status" class="form-control form-control-sm custom-select">
                            <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="present" {{ $statusFilter == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="late" {{ $statusFilter == 'late' ? 'selected' : '' }}>Late Arrival</option>
                            <option value="half_day" {{ $statusFilter == 'half_day' ? 'selected' : '' }}>Half Day</option>
                            <option value="absent" {{ $statusFilter == 'absent' ? 'selected' : '' }}>Absent</option>
                            <option value="excused" {{ $statusFilter == 'excused' ? 'selected' : '' }}>On Leave</option>
                            <option value="holiday" {{ $statusFilter == 'holiday' ? 'selected' : '' }}>Holiday / Off</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Date Range</label>
                        <select name="date_range" id="dateRangePreset" class="form-control form-control-sm custom-select" onchange="handleDatePresetChange()">
                            <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="this_week" {{ $dateRange == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="last_week" {{ $dateRange == 'last_week' ? 'selected' : '' }}>Last Week</option>
                            <option value="this_month" {{ $dateRange == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $dateRange == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-12 mb-3" id="customDateInputs" style="display: {{ $dateRange == 'custom' ? 'block' : 'none' }};">
                        <div class="form-row">
                            <div class="col-6">
                                <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Start Date</label>
                                <input type="date" name="start_date" id="startDateInput" class="form-control form-control-sm" 
                                       value="{{ $startDate ? $startDate->toDateString() : '' }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">End Date</label>
                                <input type="date" name="end_date" id="endDateInput" class="form-control form-control-sm" 
                                       value="{{ $endDate ? $endDate->toDateString() : '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end align-items-center mt-2">
                    <button type="submit" class="btn btn-success btn-sm px-4 font-weight-bold shadow-sm">
                        <i class="fas fa-search mr-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Content Grid: Attendance Records + Live Active Faculty --}}
    <div class="row">
        {{-- Attendance Records Table --}}
        <div class="col-xl-9 col-lg-8 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <h6 class="m-0 font-weight-bold text-gray-900">
                            <i class="fas fa-clipboard-list text-success mr-2"></i>Attendance Logs
                        </h6>
                        <small class="text-muted">Detailed records for {{ $stats['date_range_label'] }}</small>
                    </div>
                    <span class="badge badge-light border text-gray-700 px-3 py-2 font-weight-bold" style="border-radius: 20px;">
                        {{ $records instanceof \Illuminate\Pagination\LengthAwarePaginator ? $records->total() : count($records) }} records
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table modern-table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Faculty Member</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Work Duration</th>
                                    <th>Status</th>
                                    <th>Notes / Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $rec)
                                    @php
                                        // Generate initials
                                        $names = explode(' ', trim($rec['faculty_name']));
                                        $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                        
                                        // Colors based on status
                                        $badgeClass = match($rec['status']) {
                                            'present' => 'badge-modern-present',
                                            'late' => 'badge-modern-late',
                                            'half_day' => 'badge-modern-halfday',
                                            'absent' => 'badge-modern-absent',
                                            'excused' => 'badge-modern-leave',
                                            'holiday' => 'badge-modern-holiday',
                                            default => 'badge-modern-holiday'
                                        };
                                        $statusLabel = match($rec['status']) {
                                            'present' => 'Present',
                                            'late' => 'Late Arrival',
                                            'half_day' => 'Half Day',
                                            'absent' => 'Absent',
                                            'excused' => 'Approved Leave',
                                            'holiday' => 'Holiday / Off',
                                            default => 'Holiday / Off'
                                        };
                                        $statusIcon = match($rec['status']) {
                                            'present' => 'fa-check',
                                            'late' => 'fa-clock',
                                            'half_day' => 'fa-adjust',
                                            'absent' => 'fa-times',
                                            'excused' => 'fa-calendar-minus',
                                            'holiday' => 'fa-umbrella-beach',
                                            default => 'fa-calendar-times'
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initials mr-3">
                                                    {{ $initials }}
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold text-gray-900" style="font-size: 0.9rem;">
                                                        {{ $rec['faculty_name'] }}
                                                    </div>
                                                    <div class="text-muted small">
                                                        <span class="mr-2"><i class="far fa-id-badge text-gray-400 mr-1"></i>{{ $rec['employee_id'] ?? 'ID: ' . $rec['faculty_id'] }}</span>
                                                        @if(!empty($rec['faculty_email']))
                                                            <span class="d-none d-md-inline text-gray-400">&bull; {{ $rec['faculty_email'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border text-gray-700 px-2 py-1 font-weight-normal" style="font-size: 0.775rem;">
                                                {{ $rec['department'] ?? 'General' }}
                                            </span>
                                        </td>
                                        <td class="font-weight-semibold text-gray-700">
                                            {{ \Carbon\Carbon::parse($rec['date'])->format('d M Y') }}
                                            <div class="text-xs text-muted">{{ \Carbon\Carbon::parse($rec['date'])->format('l') }}</div>
                                        </td>
                                        <td>
                                            @if($rec['check_in'])
                                                <div class="text-success font-weight-bold">
                                                    <i class="fas fa-sign-in-alt text-xs mr-1"></i>{{ \Carbon\Carbon::parse($rec['check_in'])->format('h:i A') }}
                                                </div>
                                                @if(!empty($rec['late_minutes']) && $rec['late_minutes'] > 0)
                                                    <div class="text-xs text-warning">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>+{{ $rec['late_minutes'] }}m late
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted font-italic">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($rec['check_out'])
                                                <div class="text-info font-weight-bold">
                                                    <i class="fas fa-sign-out-alt text-xs mr-1"></i>{{ \Carbon\Carbon::parse($rec['check_out'])->format('h:i A') }}
                                                </div>
                                            @elseif($rec['check_in'] && \Carbon\Carbon::parse($rec['date'])->isToday())
                                                <span class="badge badge-success text-xs px-2 py-1" style="border-radius: 12px;">
                                                    <span class="pulse-dot mr-1" style="width:6px;height:6px;"></span> Active On-Site
                                                </span>
                                            @else
                                                <span class="text-muted font-italic">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($rec['working_hours'] !== null)
                                                <span class="badge badge-light border font-weight-bold text-gray-800 px-2 py-1" style="border-radius: 8px;">
                                                    <i class="far fa-clock text-gray-400 mr-1"></i>{{ number_format($rec['working_hours'], 2) }} hrs
                                                </span>
                                            @else
                                                <span class="text-muted font-italic">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge-modern {{ $badgeClass }}">
                                                <i class="fas {{ $statusIcon }}"></i>
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(!empty($rec['notes']))
                                                <small class="text-muted" title="{{ $rec['notes'] }}">
                                                    {{ \Illuminate\Support\Str::limit($rec['notes'], 35) }}
                                                </small>
                                            @else
                                                <span class="text-muted font-italic">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-calendar-times fa-3x mb-3 text-gray-300 d-block"></i>
                                                <h6 class="font-weight-bold text-gray-700">No Attendance Records Found</h6>
                                                <p class="small text-muted mb-0">Try changing your search term, department, or date range filter.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
                        <div class="card-footer bg-white border-top py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center">
                            <div class="text-muted small mb-2 mb-md-0">
                                Showing <strong>{{ $records->firstItem() }}</strong> to <strong>{{ $records->lastItem() }}</strong> of <strong>{{ $records->total() }}</strong> records
                            </div>
                            <div>
                                {{ $records->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Live Real-time Monitor Drawer --}}
        <div class="col-xl-3 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header bg-gradient-success text-white py-3 px-3 d-flex justify-content-between align-items-center">
                    <div class="font-weight-bold" style="font-size: 0.95rem;">
                        <span class="pulse-dot mr-2 bg-white" style="box-shadow: 0 0 0 0 rgba(255,255,255,0.7);"></span>
                        Live Active Faculty
                    </div>
                    <span class="badge badge-light text-success font-weight-bold px-2 py-1" style="border-radius: 12px;">
                        {{ count($realtimeCheckedIn) }} On-Site
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 520px; overflow-y: auto;">
                        @forelse($realtimeCheckedIn as $active)
                            @php
                                $actNames = explode(' ', trim($active['faculty']->name ?? 'Faculty'));
                                $actInitials = strtoupper(substr($actNames[0], 0, 1) . (isset($actNames[1]) ? substr($actNames[1], 0, 1) : ''));
                            @endphp
                            <div class="list-group-item d-flex align-items-center justify-content-between p-3 border-bottom">
                                <div class="d-flex align-items-center overflow-hidden mr-2">
                                    <div class="avatar-initials mr-2" style="width: 32px; height: 32px; font-size: 0.75rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                        {{ $actInitials }}
                                    </div>
                                    <div class="text-truncate">
                                        <div class="font-weight-bold text-gray-900 text-truncate" style="font-size: 0.85rem;">
                                            {{ $active['faculty']->name ?? 'Unknown' }}
                                        </div>
                                        <div class="text-muted text-xs text-truncate">
                                            {{ $active['faculty']->department ?? 'Faculty' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="badge badge-modern-present font-weight-bold" style="font-size: 0.75rem;">
                                        <i class="far fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($active['check_in'])->format('h:i A') }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 px-3 text-muted">
                                <i class="fas fa-user-clock fa-2x mb-2 text-gray-300 d-block"></i>
                                <div class="font-weight-bold small text-gray-600">No Faculty Checked In Right Now</div>
                                <div class="text-xs text-muted mt-1">Staff will appear here as soon as they log into the biometric or web portal today.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-2 border-top">
                    <small class="text-muted font-italic">
                        <i class="fas fa-sync-alt fa-spin mr-1 text-success"></i> Auto-refreshed upon page reload
                    </small>
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
            $('#customDateInputs').show();
            $('#startDateInput, #endDateInput').prop('required', true);
        } else {
            $('#customDateInputs').hide();
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
