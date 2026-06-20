@if(isset($isSingleDay) && $isSingleDay)
<div class="table-responsive">
    <table class="table table-custom mb-0" id="attendance_report_table">
        <thead>
            <tr class="bg-light">
                <th class="sortable {{ $sortBy === 'name' ? 'text-primary' : '' }}" data-sort="name">
                    Name
                    <i class="fas fa-sort{{ $sortBy === 'name' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'name' ? '' : 'opacity-50' }}"></i>
                </th>
                <th>Bio/Emp ID</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($faculties as $faculty)
                @php
                    $record = $faculty->daily_record ?? null;
                    $status = $record ? strtolower(trim($record->status)) : 'absent';
                    $checkIn = $record && $record->check_in_time ? \Carbon\Carbon::parse($record->check_in_time)->format('h:i A') : '--';
                    $checkOut = $record && $record->check_out_time ? \Carbon\Carbon::parse($record->check_out_time)->format('h:i A') : '--';
                    $notes = $record->notes ?? '';
                    
                    if ($status === 'present') $badge = 'success';
                    elseif ($status === 'late') $badge = 'warning';
                    elseif ($status === 'half_day') $badge = 'info';
                    elseif ($status === 'excused') $badge = 'secondary';
                    else $badge = 'danger';
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <a href="{{ route('admin.faculty.edit', $faculty->id) }}" class="font-weight-bold text-gray-800 hover-primary">
                                {{ $faculty->faculty_name }}
                            </a>
                        </div>
                        <div class="small text-muted">{{ $faculty->department }}</div>
                    </td>
                    <td><small>{{ $faculty->biometric_code }}</small></td>
                    <td class="font-weight-bold">{{ $checkIn }}</td>
                    <td class="font-weight-bold">{{ $checkOut }}</td>
                    <td>
                        <span class="badge badge-{{ $badge }} px-2 py-1">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    </td>
                    <td class="small text-muted">{{ $notes }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="fas fa-search fa-3x text-gray-300 mb-3"></i>
                        <p class="mt-3 text-gray-500">No faculty attendance data found for the selected date.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@else
<div class="table-responsive">
    <table class="table table-custom mb-0" id="attendance_report_table">
        <thead>
            <tr class="bg-light">
                <th colspan="2" class="text-center font-weight-bold border-bottom">Faculty Info</th>
                @foreach($months as $month)
                    <th colspan="6" class="text-center font-weight-bold border-bottom border-left">
                        {{ $month->format('F Y') }}
                    </th>
                @endforeach
                <th colspan="6" class="text-center font-weight-bold border-bottom border-left bg-gray-100">Overall
                    Summary</th>
            </tr>
            <tr>
                <th class="sortable {{ $sortBy === 'name' ? 'text-primary' : '' }}" data-sort="name">
                    Name
                    <i
                        class="fas fa-sort{{ $sortBy === 'name' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'name' ? '' : 'opacity-50' }}"></i>
                </th>
                <th>Bio/Emp ID</th>

                @foreach($months as $month)
                    <th class="text-center border-left small">Work</th>
                    <th class="text-center small">Pres</th>
                    <th class="text-center small">Late</th>
                    <th class="text-center small">Half</th>
                    <th class="text-center small">Abs</th>
                    <th class="text-center small font-weight-bold border-right">%</th>
                @endforeach

                <th class="text-center border-left bg-gray-100">Working</th>
                <th class="text-center bg-gray-100">Present</th>
                <th class="text-center bg-gray-100 text-warning">Late</th>
                <th class="text-center bg-gray-100 text-info">Half</th>
                <th class="text-center bg-gray-100">Absent</th>
                <th class="text-center sortable bg-gray-100 {{ $sortBy === 'attendance_percentage' ? 'text-primary' : '' }}"
                    data-sort="attendance_percentage">
                    Att %
                    <i
                        class="fas fa-sort{{ $sortBy === 'attendance_percentage' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'attendance_percentage' ? '' : 'opacity-50' }}"></i>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($faculties as $faculty)
                <tr class="{{ $faculty->attendance_percentage < 60 ? 'bg-light-danger' : '' }}">
                    <td>
                        <div class="d-flex align-items-center">
                            <a href="{{ route('admin.faculty.edit', $faculty->id) }}"
                                class="font-weight-bold text-gray-800 hover-primary">
                                {{ $faculty->faculty_name }}
                            </a>
                        </div>
                        <div class="small text-muted">{{ $faculty->department }}</div>
                    </td>
                    <td><small>{{ $faculty->biometric_code }}</small></td>

                    @foreach($months as $month)
                        @php
                            $mKey = $month->format('M_Y');
                            $mStats = $faculty->monthly_stats[$mKey] ?? null;
                        @endphp
                        @if($mStats)
                            <td class="text-center border-left small">{{ $mStats['working_days'] }}</td>
                            <td class="text-center text-success small">{{ $mStats['present'] }}</td>
                            <td class="text-center text-warning small">{{ $mStats['late'] }}</td>
                            <td class="text-center text-info small">{{ $mStats['half_days'] }}</td>
                            <td class="text-center text-danger small">{{ $mStats['absent'] }}</td>
                            <td class="text-center small font-weight-bold border-right">{{ intval($mStats['percentage']) }}%</td>
                        @else
                            <td colspan="6" class="text-center border-left text-muted small">N/A</td>
                        @endif
                    @endforeach

                    <td class="text-center border-left bg-gray-100">{{ $faculty->total_working_days }}</td>
                    <td class="text-center text-success font-weight-bold bg-gray-100">{{ $faculty->present_days }}</td>
                    <td class="text-center text-warning bg-gray-100">{{ $faculty->late_days }}</td>
                    <td class="text-center text-info bg-gray-100">{{ $faculty->half_days }}</td>
                    <td class="text-center text-danger bg-gray-100">{{ $faculty->absent_days }}</td>
                    <td class="text-center bg-gray-100">
                        @php
                            $percentage = $faculty->attendance_percentage;
                            $color = $percentage >= 90 ? 'success' : ($percentage >= 75 ? 'info' : ($percentage >= 60 ? 'warning' : 'danger'));
                        @endphp
                        <span class="font-weight-bold text-{{ $color }}">{{ $percentage }}%</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($months) * 6 + 8 }}" class="text-center py-5">
                        <i class="fas fa-search fa-3x text-gray-300 mb-3"></i>
                        <p class="mt-3 text-gray-500">No faculty attendance data found for the selected criteria.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endif

@if($pagination)
    <div class="px-4 py-3 ajax-pagination">
        {{ $pagination->links() }}
    </div>
@endif