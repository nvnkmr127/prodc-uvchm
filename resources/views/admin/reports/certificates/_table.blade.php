<div class="table-responsive">
    <table class="table table-custom mb-0" id="certificate_report_table">
        <thead>
            <tr>
                <th class="sortable {{ $sortBy === 'enrollment_number' ? 'text-primary' : '' }}"
                    data-sort="enrollment_number">
                    Enrollment No
                    <i
                        class="fas fa-sort{{ $sortBy === 'enrollment_number' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'enrollment_number' ? '' : 'opacity-50' }}"></i>
                </th>
                <th class="sortable {{ $sortBy === 'name' ? 'text-primary' : '' }}" data-sort="name">
                    Student Name
                    <i
                        class="fas fa-sort{{ $sortBy === 'name' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'name' ? '' : 'opacity-50' }}"></i>
                </th>
                <th class="sortable {{ $sortBy === 'course' ? 'text-primary' : '' }}" data-sort="course">
                    Course
                    <i
                        class="fas fa-sort{{ $sortBy === 'course' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'course' ? '' : 'opacity-50' }}"></i>
                </th>
                <th class="sortable {{ $sortBy === 'batch' ? 'text-primary' : '' }}" data-sort="batch">
                    Batch
                    <i
                        class="fas fa-sort{{ $sortBy === 'batch' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'batch' ? '' : 'opacity-50' }}"></i>
                </th>
                <th class="text-center">Certificate Status</th>
                <th class="text-center">Type</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>{{ $student->enrollment_number }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="{{ \App\Http\Controllers\Admin\StudentController::getStudentPhotoUrl($student, 40) }}"
                                class="rounded-circle mr-2" width="40" height="40" alt="">
                            <a href="{{ route('admin.students.show', $student->id) }}"
                                class="font-weight-bold text-gray-800 hover-primary">
                                {{ $student->name }}
                            </a>
                            <small class="text-muted d-block ml-2">
                                <i class="fas fa-phone fa-xs mr-1"></i> {{ $student->student_mobile ?? 'N/A' }}
                            </small>
                        </div>
                    </td>
                    <td>{{ $student->batch->course->name ?? 'N/A' }}</td>
                    <td>{{ $student->batch->name ?? 'N/A' }}</td>
                    <td class="text-center">
                        @if($student->is_certificate_received)
                            <span class="badge badge-pill badge-success border border-success px-3">
                                <i class="fas fa-check-circle mr-1"></i> Received
                            </span>
                        @else
                            <span class="badge badge-pill badge-warning border border-warning px-3">
                                <i class="fas fa-clock mr-1"></i> Pending
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($student->certificate_type)
                            <span class="font-weight-bold text-dark">{{ $student->certificate_type }}</span>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.students.edit', $student) }}"
                            class="btn btn-sm btn-light border hover-primary" title="Edit Student">
                            <i class="fas fa-edit text-primary"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <img src="{{ asset('img/undraw_no_data.svg') }}" style="width: 150px; opacity: 0.5;">
                        <p class="mt-3 text-gray-500">No student data matching the filters.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="px-4 py-3 ajax-pagination">
    {{ $students->links() }}
</div>