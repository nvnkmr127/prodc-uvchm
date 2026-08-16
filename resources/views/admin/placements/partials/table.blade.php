<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-light">
            <tr>
                <th style="width: 40px;">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="selectAll">
                        <label class="custom-control-label" for="selectAll"></label>
                    </div>
                </th>
                <th>Student</th>
                <th>Contact</th>
                <th>Batch</th>
                <th>Placement Status</th>
                <th>Company / Placed At</th>
                <th>Designation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input student-checkbox" id="student{{ $student->id }}" value="{{ $student->id }}">
                            <label class="custom-control-label" for="student{{ $student->id }}"></label>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img alt="{{ ($student->name ?? 'Student') }} photo" src="{{ $student->photo_url }}" class="rounded-circle mr-2" width="40" height="40" style="object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&size=40&background=4e73df&color=fff&rounded=true&bold=true'">
                            <div>
                                <div class="font-weight-bold">{{ $student->name }}</div>
                                <small class="text-muted">{{ $student->enrollment_number }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div><i class="fas fa-phone fa-sm mr-1"></i> {{ $student->student_mobile }}</div>
                        @if($student->email)
                        <div><i class="fas fa-envelope fa-sm mr-1"></i> {{ $student->email }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $student->batch->name ?? 'N/A' }}<br>
                        <small class="text-muted">{{ $student->batch->course->name ?? '' }}</small>
                    </td>
                    <td>
                        @if($student->placement_status == 'Job')
                            <span class="badge badge-success">Job</span>
                        @elseif($student->placement_status == 'Internship')
                            <span class="badge badge-info">Internship</span>
                        @elseif($student->placement_status == 'Training')
                            <span class="badge badge-warning">Training</span>
                        @else
                            <span class="badge badge-secondary">Not Placed</span>
                        @endif
                    </td>
                    <td>{{ $student->placed_at ?? '-' }}</td>
                    <td>{{ $student->placement_designation ?? '-' }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary shadow-sm rounded-pill px-3 btn-update-placement" 
                            data-toggle="modal" 
                            data-target="#placementUpdateModal"
                            data-id="{{ $student->id }}"
                            data-name="{{ e($student->name) }}"
                            data-enrollment="{{ $student->enrollment_number }}"
                            data-photo="{{ $student->photo_url }}"
                            data-batch="{{ $student->batch->name ?? 'N/A' }}"
                            data-course="{{ $student->batch->course->name ?? '' }}"
                            data-status="{{ $student->placement_status ?? 'Not Placed' }}"
                            data-placed-at="{{ $student->placed_at ?? '' }}"
                            data-designation="{{ $student->placement_designation ?? '' }}"
                            data-update-url="{{ route('placement-portal.update', $student) }}">
                            <i class="fas fa-edit mr-1"></i> Update
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4">No student records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4" id="paginationContainer">
    {{ $students->links() }}
</div>
