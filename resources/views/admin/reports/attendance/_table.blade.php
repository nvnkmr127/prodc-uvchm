<div class="table-responsive">
    <table class="table table-custom mb-0" id="attendance_report_table">
        <thead>
            <tr class="bg-light">
                <th colspan="2" class="text-center font-weight-bold border-bottom">Student Info</th>
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
                <th>Enroll #</th>

                @foreach($months as $month)
                    <th class="text-center border-left small">Work</th>
                    <th class="text-center small">Pres</th>
                    <th class="text-center small">OJT</th>
                    <th class="text-center small">Abs</th>
                    <th class="text-center small text-info">Exc</th>
                    <th class="text-center small font-weight-bold border-right">%</th>
                @endforeach

                <th class="text-center border-left bg-gray-100">Working</th>
                <th class="text-center bg-gray-100">Present</th>
                <th class="text-center bg-gray-100 text-info">OJT</th>
                <th class="text-center bg-gray-100">Absent</th>
                <th class="text-center bg-gray-100 text-secondary">Excused</th>
                <th class="text-center sortable bg-gray-100 {{ $sortBy === 'attendance_percentage' ? 'text-primary' : '' }}"
                    data-sort="attendance_percentage">
                    Att %
                    <i
                        class="fas fa-sort{{ $sortBy === 'attendance_percentage' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'attendance_percentage' ? '' : 'opacity-50' }}"></i>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr class="{{ $student->attendance_percentage < 50 ? 'bg-light-danger' : '' }}">
                    <td>
                        <div class="d-flex align-items-center">
                            <a href="{{ route('admin.students.show', $student->id) }}"
                                class="font-weight-bold text-gray-800 hover-primary">
                                {{ $student->student_name }}
                            </a>
                        </div>
                    </td>
                    <td><small>{{ $student->enrollment_number }}</small></td>

                    @foreach($months as $month)
                        @php
                            $mKey = $month->format('M_Y');
                            $mStats = $student->monthly_stats[$mKey] ?? null;
                        @endphp
                        @if($mStats)
                            <td class="text-center border-left small">{{ $mStats['working_days'] }}</td>
                            <td class="text-center text-success small">{{ ($mStats['present'] ?? 0) + ($mStats['late'] ?? 0) }}</td>
                            <td class="text-center text-info small">{{ $mStats['internship'] }}</td>
                            <td class="text-center text-danger small">{{ $mStats['absent'] }}</td>
                            <td class="text-center text-muted small">{{ $mStats['excused'] }}</td>
                            <td class="text-center small font-weight-bold border-right">{{ intval($mStats['percentage']) }}%</td>
                        @else
                            <td colspan="6" class="text-center border-left text-muted small">N/A</td>
                        @endif
                    @endforeach

                    <td class="text-center border-left bg-gray-100">{{ $student->total_working_days }}</td>
                    <td class="text-center text-success font-weight-bold bg-gray-100">{{ ($student->present_days ?? 0) + ($student->late_days ?? 0) }}</td>
                    <td class="text-center text-info bg-gray-100">{{ $student->internship_days }}</td>
                    <td class="text-center text-danger bg-gray-100">{{ $student->absent_days }}</td>
                    <td class="text-center text-muted bg-gray-100">{{ $student->excused_days }}</td>
                    <td class="text-center bg-gray-100">
                        @php
                            $percentage = $student->attendance_percentage;
                            $color = $percentage >= 75 ? 'success' : ($percentage >= 60 ? 'info' : ($percentage >= 50 ? 'warning' : 'danger'));
                        @endphp
                        <span class="font-weight-bold text-{{ $color }}">{{ $percentage }}%</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($months) * 6 + 7 }}" class="text-center py-5">
                        <i class="fas fa-search fa-3x text-gray-300 mb-3"></i>
                        <p class="mt-3 text-gray-500">No attendance data found for the selected criteria.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($pagination)
    <div class="px-4 py-3 ajax-pagination">
        {{ $pagination->links() }}
    </div>
@endif