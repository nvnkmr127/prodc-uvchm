<div class="table-responsive">
    <table class="table table-custom mb-0" id="age_report_table">
        <thead>
            <tr>
                <th class="sortable {{ $sortBy === 'name' ? 'text-primary' : '' }}" data-sort="name">
                    Student Name 
                    <i class="fas fa-sort{{ $sortBy === 'name' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'name' ? '' : 'opacity-50' }}"></i>
                </th>
                <th class="sortable {{ $sortBy === 'course' ? 'text-primary' : '' }}" data-sort="course">
                    Course 
                    <i class="fas fa-sort{{ $sortBy === 'course' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'course' ? '' : 'opacity-50' }}"></i>
                </th>
                <th class="sortable {{ $sortBy === 'batch' ? 'text-primary' : '' }}" data-sort="batch">
                    Batch 
                    <i class="fas fa-sort{{ $sortBy === 'batch' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'batch' ? '' : 'opacity-50' }}"></i>
                </th>
                <th class="text-center">Gender</th>
                <th class="text-center">DOB</th>
                <th class="text-center sortable {{ $sortBy === 'current_age' ? 'text-primary' : '' }}" data-sort="current_age">
                    Age (Years) 
                    <i class="fas fa-sort{{ $sortBy === 'current_age' ? ($sortOrder === 'asc' ? '-up' : '-down') : '' }} ml-1 {{ $sortBy === 'current_age' ? '' : 'opacity-50' }}"></i>
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
                                <i class="fas fa-user text-primary"></i>
                            </div>
                            <a href="{{ route('admin.students.show', $student->id) }}"
                                class="font-weight-bold text-gray-800 hover-primary">
                                {{ $student->name }}
                            </a>
                        </div>
                    </td>
                    <td>{{ $student->course->name ?? 'N/A' }}</td>
                    <td>{{ $student->batch->name ?? 'N/A' }}</td>
                    <td class="text-center">
                        <span class="badge badge-pill badge-light border">
                            <i
                                class="fas fa-{{ strtolower($student->gender) == 'male' ? 'mars text-info' : 'venus text-warning' }} mr-1"></i>
                            {{ $student->gender }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($student->dob)
                            {{ $student->dob->format('M d, Y') }}
                        @else
                            <span class="badge badge-warning">Missing Info</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($student->dob)
                            <span class="font-weight-bold text-primary h5 mb-0">{{ $student->current_age }}</span>
                        @else
                            <span class="text-muted small">Not Set</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
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