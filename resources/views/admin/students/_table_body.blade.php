@forelse ($students as $student)
    <tr class="student-row" data-student-id="{{ $student->id }}">
        <td>
            <input type="checkbox" class="custom-checkbox student-checkbox" value="{{ $student->id }}">
        </td>
        <td>
            <div class="student-info">
                <img class="student-avatar"
                    src="{{ \App\Http\Controllers\Admin\StudentController::getStudentPhotoUrl($student, 50) }}"
                    alt="{{ $student->name }}" loading="lazy">
                <div class="student-details">
                    <h6>
                        <a href="{{ route('admin.students.show', $student) }}">
                            {{ $student->name }}
                        </a>
                    </h6>
                    <div class="text-muted">ID: {{ $student->enrollment_number }}</div>
                </div>

            </div>
        </td>
        <td>
            <span class="badge badge-light">{{ $student->enrollment_number }}</span>
        </td>
        <td>
            @if ($student->batch)
                <div>
                    <strong>{{ $student->batch->course->name ?? 'N/A' }}</strong>
                </div>
                <small class="text-muted">{{ $student->batch->name }}</small>
                @if ($student->batch->is_on_internship)
                    <div class="mt-1">
                        <span class="badge badge-info">
                            <i class="fas fa-briefcase"></i> Internship
                        </span>
                        @if($student->batch->internship_start_date)
                            <small class="text-muted d-block">
                                Since: {{ \Carbon\Carbon::parse($student->batch->internship_start_date)->format('M Y') }}
                            </small>
                        @endif
                    </div>
                @endif
            @else
                <span class="text-muted">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    Not Assigned
                </span>
            @endif
        </td>
        <td class="contact-cell">
            <div class="contact-display">
                @if($student->student_mobile)
                    <div>
                        <i class="fas fa-mobile-alt text-primary"></i>
                        <span>{{ $student->student_mobile }}</span>
                    </div>
                @else
                    <div>
                        <i class="fas fa-mobile-alt text-muted"></i>
                        <span class="text-muted">No mobile</span>
                    </div>
                @endif
                @if($student->father_mobile)
                    <small class="text-muted">
                        <i class="fas fa-phone text-secondary"></i>
                        <span>{{ $student->father_mobile }}</span>
                    </small>
                @endif
            </div>
        </td>
        <td class="status-cell">
            <span class="status-badge-modern status-{{ $student->status }}">
                {{ ucfirst($student->status) }}
            </span>
        </td>
        <td>
            <div class="table-actions">
                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-info btn-table-action"
                    title="View Profile">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-warning btn-table-action"
                    title="Edit Student">
                    <i class="fas fa-edit"></i>
                </a>

                {{-- DROPOUT MANAGEMENT BUTTONS --}}
                @if($student->status === 'active')
                    <a href="{{ route('admin.students.confirm-dropout', $student) }}" class="btn btn-warning btn-table-action"
                        title="Mark as Dropout">
                        <i class="fas fa-user-times"></i>
                    </a>
                @elseif($student->status === 'dropout')
                    <button class="btn btn-success btn-table-action reactivate-student-btn" data-student-id="{{ $student->id }}"
                        data-student-name="{{ $student->name }}" title="Reactivate Student">
                        <i class="fas fa-user-check"></i>
                    </button>
                @endif

                @if(auth()->user()->hasRole('super-admin'))
                    <button class="btn btn-table-action btn-danger delete-student-btn" data-student-id="{{ $student->id }}"
                        data-student-name="{{ $student->name }}" title="Delete Student">
                        <i class="fas fa-trash"></i>
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center py-5">
            <div class="empty-state">
                <i class="fas fa-users-slash empty-icon"></i>
                <h5>No students found</h5>
                <p class="text-muted">Try adjusting your filters or search terms</p>
            </div>
        </td>
    </tr>
@endforelse