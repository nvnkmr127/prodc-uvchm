{{-- Top Header / Hero Section --}}
<div class="attendance-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div class="mb-3 mb-md-0">
        <div class="text-xs text-uppercase font-weight-bold text-success mb-1" style="letter-spacing: 0.08em;">
            <i class="fas fa-shield-alt mr-1"></i> Admin Portal &bull; Attendance Management
        </div>
        <h1 class="h3 font-weight-bold text-white mb-1">
            Faculty Attendance & Timing
        </h1>
        <div class="text-white-50 small d-flex flex-wrap align-items-center gap-2 mt-1">
            <span><i class="far fa-calendar-alt text-success mr-1"></i> <strong>{{ $stats['date_range_label'] }}</strong></span>
            @if($departmentFilter)
                <span class="mx-2 d-none d-sm-inline">&bull;</span>
                <span><i class="fas fa-building mr-1"></i> Dept: <strong>{{ $departmentFilter }}</strong></span>
            @endif
            <span class="mx-2 d-none d-sm-inline">&bull;</span>
            <span><i class="fas fa-users mr-1"></i> <strong>{{ $stats['total_faculty'] }}</strong> Faculty Members</span>
            <span class="mx-2 d-none d-sm-inline">&bull;</span>
            <span class="badge badge-light text-dark px-2 py-1 font-weight-bold">
                <i class="fas fa-briefcase text-success mr-1"></i>{{ $stats['total_working_days'] }} Working Days
                @if(isset($stats['elapsed_working_days']) && $stats['elapsed_working_days'] < $stats['total_working_days'])
                    ({{ $stats['elapsed_working_days'] }} to date)
                @endif
            </span>
            <span class="badge badge-warning text-dark px-2 py-1 font-weight-bold">
                <i class="fas fa-umbrella-beach text-warning mr-1"></i>{{ $stats['total_holidays'] }} Holidays
            </span>
            <span class="badge badge-secondary px-2 py-1 font-weight-bold">
                <i class="fas fa-bed mr-1"></i>{{ $stats['total_weekends'] }} Weekends
            </span>
        </div>
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

{{-- Dynamic Stats Cards (Top Overview) --}}
<div class="row mb-4">
    {{-- Card 1: Working vs Holidays & Weekends Breakdown --}}
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stats-card-modern">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-label">
                        {{ $stats['is_today'] ? 'Today\'s Attendance State' : 'Working vs Off Days' }}
                    </div>
                    <div class="stats-value text-gray-900">
                        @if($stats['is_today'])
                            {{ $stats['checked_in_today'] }}
                            <span class="badge badge-success text-xs ml-1 font-weight-bold" style="font-size: 0.75rem; vertical-align: middle;">
                                <span class="pulse-dot mr-1"></span> Active Now
                            </span>
                        @else
                            {{ $stats['total_working_days'] }}
                            <span class="text-xs text-muted font-weight-normal" style="font-size: 0.85rem;">work days</span>
                            @if(isset($stats['elapsed_working_days']) && $stats['elapsed_working_days'] < $stats['total_working_days'])
                                <div class="text-xs text-info font-weight-semibold mt-1">
                                    <i class="fas fa-history mr-1"></i>{{ $stats['elapsed_working_days'] }} elapsed to date
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="stats-subtext mt-2 d-flex flex-wrap gap-1">
                        <span class="badge badge-warning text-dark px-2 py-1 font-weight-bold" title="Institutional & < 2 logins holidays">
                            <i class="fas fa-umbrella-beach mr-1"></i>{{ $stats['total_holidays'] }} Holidays
                        </span>
                        <span class="badge badge-light border text-muted px-2 py-1 font-weight-bold" title="Saturday & Sunday weekends">
                            <i class="fas fa-bed mr-1"></i>{{ $stats['total_weekends'] }} Weekends
                        </span>
                    </div>
                </div>
                <div class="icon-wrapper {{ $stats['is_today'] ? 'icon-emerald' : 'icon-indigo' }}">
                    <i class="fas {{ $stats['is_today'] ? 'fa-sign-in-alt' : 'fa-calendar-alt' }}"></i>
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
                            <strong>{{ $stats['total_present_days'] }}</strong> present / {{ ($stats['elapsed_working_days'] ?? $stats['total_working_days']) * max(1, $stats['total_faculty']) }} person-days
                        @endif
                    </div>
                </div>
                <div class="icon-wrapper icon-emerald">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
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
                            Total absent: <strong>{{ $stats['absent_count'] }}</strong> ({{ $stats['leave_count'] }} leaves)
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

{{-- Top Attendance Leaderboard Section --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; overflow: hidden;">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="m-0 font-weight-bold text-gray-900">
                <i class="fas fa-trophy text-warning mr-2"></i>Top Attendance Leaderboard
            </h6>
            <small class="text-muted">Ranked by attendance rate, days present, and timeliness for <strong>{{ $stats['date_range_label'] }}</strong></small>
        </div>
        <span class="badge badge-light border text-gray-700 px-3 py-2 font-weight-bold" style="border-radius: 20px;">
            Top Performers
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table modern-table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 80px;" class="text-center">Rank</th>
                        <th>Faculty Member</th>
                        <th>Department</th>
                        <th class="text-center">Days Present</th>
                        <th class="text-center">Late Check-Ins</th>
                        <th class="text-center">Half Days</th>
                        <th class="text-center">Absent</th>
                        <th class="text-right pr-4">Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(array_slice($leaderboard, 0, 5) as $index => $lb)
                        @php
                            $rank = $index + 1;
                            $medal = match($rank) {
                                1 => '<span class="badge badge-warning text-dark font-weight-bold px-2 py-1"><i class="fas fa-crown mr-1"></i>1st</span>',
                                2 => '<span class="badge badge-secondary text-white font-weight-bold px-2 py-1"><i class="fas fa-medal mr-1"></i>2nd</span>',
                                3 => '<span class="badge font-weight-bold px-2 py-1" style="background:#d97706;color:#fff;"><i class="fas fa-award mr-1"></i>3rd</span>',
                                default => '<span class="badge badge-light border font-weight-bold px-2 py-1">#' . $rank . '</span>'
                            };

                            $names = explode(' ', trim($lb['faculty_name']));
                            $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                        @endphp
                        <tr>
                            <td class="text-center align-middle">
                                {!! $medal !!}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-initials mr-3" style="background: {{ $rank == 1 ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }};">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <div class="font-weight-bold text-gray-900" style="font-size: 0.9rem;">
                                            {{ $lb['faculty_name'] }}
                                        </div>
                                        <div class="text-muted small">
                                            <i class="far fa-id-badge mr-1"></i>{{ $lb['employee_id'] }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-light border text-gray-700 px-2 py-1 font-weight-normal" style="font-size: 0.775rem;">
                                    {{ $lb['department'] }}
                                </span>
                            </td>
                            <td class="text-center font-weight-bold text-success">
                                <i class="fas fa-check-circle text-xs mr-1"></i>{{ $lb['present_days'] }} days
                            </td>
                            <td class="text-center font-weight-bold {{ $lb['late_days'] > 0 ? 'text-warning' : 'text-muted' }}">
                                @if($lb['late_days'] > 0)
                                    <i class="fas fa-user-clock text-xs mr-1"></i>{{ $lb['late_days'] }} late
                                @else
                                    <span class="text-muted font-italic">0</span>
                                @endif
                            </td>
                            <td class="text-center font-weight-bold {{ $lb['half_day_days'] > 0 ? 'text-info' : 'text-muted' }}">
                                @if($lb['half_day_days'] > 0)
                                    <i class="fas fa-adjust text-xs mr-1"></i>{{ $lb['half_day_days'] }}
                                @else
                                    <span class="text-muted font-italic">0</span>
                                @endif
                            </td>
                            <td class="text-center font-weight-bold {{ $lb['absent_days'] > 0 ? 'text-danger' : 'text-muted' }}">
                                @if($lb['absent_days'] > 0)
                                    {{ $lb['absent_days'] }}
                                @else
                                    <span class="text-muted font-italic">0</span>
                                @endif
                            </td>
                            <td class="text-right pr-4">
                                <div class="d-inline-flex align-items-center">
                                    <span class="font-weight-bold text-gray-900 mr-2" style="font-size: 0.95rem;">
                                        {{ $lb['attendance_rate'] }}%
                                    </span>
                                    <div class="progress" style="width: 70px; height: 6px; border-radius: 3px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $lb['attendance_rate'] }}%;" aria-valuenow="{{ $lb['attendance_rate'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-users-slash fa-2x mb-2 text-gray-300 d-block"></i>
                                No faculty records available to rank.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Main Grid: Attendance Table & Live Active Faculty Drawer --}}
<div class="row">
    {{-- Attendance Records Table --}}
    <div class="col-xl-9 col-lg-8 mb-4">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h6 class="m-0 font-weight-bold text-gray-900">
                        <i class="fas fa-clipboard-list text-success mr-2"></i>Attendance Logs
                    </h6>
                    <small class="text-muted">Showing records matching active filters (Mail IDs & Notes removed)</small>
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $rec)
                                @php
                                    $names = explode(' ', trim($rec['faculty_name']));
                                    $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                    
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
                                                    <i class="far fa-id-badge text-gray-400 mr-1"></i>{{ $rec['employee_id'] ?? ('ID: ' . $rec['faculty_id']) }}
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
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
                    <div class="card-footer bg-white border-top py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center ajax-pagination-wrapper">
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
                    <i class="fas fa-sync-alt fa-spin mr-1 text-success"></i> Auto-refreshed with AJAX updates
                </small>
            </div>
        </div>
    </div>
</div>
