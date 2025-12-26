@forelse ($students as $student)
    <tr>
        <td><input type="checkbox" name="student_ids[]" class="student-checkbox" value="{{ $student->id }}"></td>
        <td>
            <a href="{{ route('admin.students.show', $student) }}" class="d-flex align-items-center">
                <img class="img-profile rounded-circle mr-3" 
                     src="{{ \App\Http\Controllers\Admin\StudentController::getStudentPhotoUrl($student, 40) }}" 
                     alt="{{$student->name}}"
                     style="width: 40px; height: 40px;">
                <div>
                    <div class="font-weight-bold">{{ $student->name }}</div>
                    <div class="small text-gray-500">{{ $student->email ?? 'No Email' }}</div>
                </div>
            </a>
        </td>
        <td>{{ $student->enrollment_number }}</td>
        <td>
            @if ($student->batch)
                <div><strong>{{ $student->batch->course->name ?? 'N/A' }}</strong></div>
                <div class="small text-muted">{{ $student->batch->name }}</div>
            @else
                <span class="text-danger">No Batch Assigned</span>
            @endif
        </td>
        <td>
            <div>📱 {{ $student->student_mobile ?? 'N/A' }}</div>
            @if($student->father_mobile)
                <div class="small text-muted">👨‍👦 {{ $student->father_mobile }}</div>
            @endif
        </td>
        <td>
            @if($student->status == 'active')
                <span class="badge badge-success">Active</span>
            @elseif($student->status == 'graduated')
                <span class="badge badge-primary">Graduated</span>
            @elseif($student->status == 'dropout')
                <span class="badge badge-danger">Dropout</span>
            @else
                <span class="badge badge-secondary">{{ ucfirst($student->status) }}</span>
            @endif
        </td>
        <td class="text-center">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.students.show', $student) }}" 
                   class="btn btn-info btn-sm" title="View Details">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('admin.students.edit', $student) }}" 
                   class="btn btn-warning btn-sm" title="Edit">
                    <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn btn-danger btn-sm" 
                        onclick="confirmDelete('{{ $student->id }}', '{{ $student->name }}')" 
                        title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center py-4">
            <div class="text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <p class="mb-0">No students found matching your criteria.</p>
                <small>Try adjusting your filters or <a href="{{ route('admin.students.index') }}">reset all filters</a>.</small>
            </div>
        </td>
    </tr>
@endforelse