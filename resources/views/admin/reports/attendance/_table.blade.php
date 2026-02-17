<div class="table-responsive">
    <table class="table table-custom mb-0" id="attendance_report_table">
        <thead>
            <tr>
                <th class="sortable {{ $sortBy === 'name' ? 'text-primary' : '' }}" data-sort="name">
                    Student Name
                    <i
                        class="fas fa-sort{{ $sortBy === 'name' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'name' ? '' : 'opacity-50' }}"></i>
                </th>
                <th>Enrollment #</th>
                <th class="text-center">Total Working Days</th>
                <th class="text-center">Days Present</th>
                <th class="text-center">Days Absent</th>
                <th class="text-center">Holidays</th>
                <th class="text-center sortable {{ $sortBy === 'attendance_percentage' ? 'text-primary' : '' }}"
                    data-sort="attendance_percentage">
                    Attendance %
                    <i
                        class="fas fa-sort{{ $sortBy === 'attendance_percentage' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'attendance_percentage' ? '' : 'opacity-50' }}"></i>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="bg-light rounded-circle p-2 mr-3"
                                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i
                                    class="fas fa-user {{ $student->attendance_percentage >= 75 ? 'text-success' : ($student->attendance_percentage >= 50 ? 'text-warning' : 'text-danger') }}"></i>
                            </div>
                            <a href="{{ route('admin.students.show', $student->id) }}"
                                class="font-weight-bold text-gray-800 hover-primary">
                                {{ $student->student_name }}
                            </a>
                        </div>
                    </td>
                    <td>{{ $student->enrollment_number }}</td>
                    <td class="text-center">{{ $student->total_working_days }}</td>
                    <td class="text-center text-success font-weight-bold">{{ $student->present_days }}</td>
                    <td class="text-center text-danger">{{ $student->absent_days }}</td>
                    <td class="text-center text-info">{{ $student->holidays }}</td>
                    <td class="text-center">
                        @php
                            $percentage = $student->attendance_percentage;
                            $color = $percentage >= 75 ? 'success' : ($percentage >= 60 ? 'info' : ($percentage >= 50 ? 'warning' : 'danger'));
                        @endphp
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="mr-2 font-weight-bold text-{{ $color }}">{{ $percentage }}%</div>
                            <div class="progress" style="height: 6px; width: 60px;">
                                <div class="progress-bar bg-{{ $color }}" role="progressbar"
                                    style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                    aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
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