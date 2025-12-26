@extends('layouts.theme')

@section('title', 'Take Attendance')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-check fa-sm fa-fw mr-2"></i>
            Take Attendance
        </h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.daily-attendance.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('admin.daily-attendance.show') }}" class="btn btn-info btn-sm">
                <i class="fas fa-eye"></i> Live View
            </a>
        </div>
    </div>

    <!-- Attendance Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-calendar-check mr-2"></i>
                Attendance Information
            </h6>
        </div>
        <div class="card-body">
            <form id="attendanceForm" method="POST" action="{{ route('admin.daily-attendance.store') }}">
                @csrf
                
                <!-- Basic Information -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date" class="font-weight-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="{{ $date ?? date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="batch_id" class="font-weight-bold">Batch <span class="text-danger">*</span></label>
                            <select class="form-control" id="batch_id" name="batch_id" required>
                                <option value="">Select Batch</option>
                                @foreach($batches as $batch)
                                    <option value="{{ $batch->id }}" 
                                            {{ (isset($batch) && $batch->id == ($batchId ?? '')) ? 'selected' : '' }}>
                                        {{ $batch->name }} ({{ $batch->course->name ?? 'No Course' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="subject_id" class="font-weight-bold">Subject <span class="text-danger">*</span></label>
                            <select class="form-control" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Load Students Button -->
                <div class="row mb-4">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="loadStudentsBtn">
                            <i class="fas fa-users"></i> Load Students
                        </button>
                        <small class="text-muted ml-2">Please select batch and subject first</small>
                    </div>
                </div>

                <!-- Students List -->
                <div id="studentsContainer" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-list-check mr-2"></i>
                                    Mark Attendance
                                </h6>
                                <div>
                                    <button type="button" class="btn btn-success btn-sm" onclick="markAll('present')">
                                        <i class="fas fa-check"></i> All Present
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="markAll('absent')">
                                        <i class="fas fa-times"></i> All Absent
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="30%">Student Name</th>
                                            <th width="20%">Enrollment</th>
                                            <th width="30%">Status</th>
                                            <th width="15%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentsTableBody">
                                        <!-- Students will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-muted">
                                        <small>
                                            <span id="totalStudents">0</span> students • 
                                            <span id="presentCount" class="text-success">0 present</span> • 
                                            <span id="absentCount" class="text-danger">0 absent</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-save"></i> Save Attendance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <div class="mt-2">
                    <strong>Saving attendance...</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load students when batch changes
    $('#batch_id, #subject_id').change(function() {
        if ($('#batch_id').val() && $('#subject_id').val()) {
            $('#loadStudentsBtn').prop('disabled', false);
        } else {
            $('#loadStudentsBtn').prop('disabled', true);
            $('#studentsContainer').hide();
        }
    });

    // Load students button click
    $('#loadStudentsBtn').click(function() {
        loadStudents();
    });

    // Form submission
    $('#attendanceForm').submit(function(e) {
        e.preventDefault();
        submitAttendance();
    });
});

function loadStudents() {
    const batchId = $('#batch_id').val();
    const date = $('#date').val();
    
    if (!batchId) {
        alert('Please select a batch first.');
        return;
    }

    // Show loading
    $('#loadStudentsBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Loading...');

    $.ajax({
        url: '{{ route("admin.daily-attendance.batch-students", ":batch") }}'.replace(':batch', batchId),
        method: 'GET',
        data: { date: date },
        success: function(response) {
            if (response.success) {
                displayStudents(response.students);
                $('#studentsContainer').show();
            } else {
                alert('Failed to load students: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Load students error:', error);
            alert('Failed to load students. Please try again.');
        },
        complete: function() {
            $('#loadStudentsBtn').prop('disabled', false).html('<i class="fas fa-users"></i> Load Students');
        }
    });
}

function displayStudents(students) {
    const tbody = $('#studentsTableBody');
    tbody.empty();

    if (students.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle mr-2"></i>
                    No students found in this batch
                </td>
            </tr>
        `);
        return;
    }

    students.forEach((student, index) => {
        const existingStatus = student.existing_status || 'absent';
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td>
                    <strong>${student.name}</strong>
                    ${student.student_mobile ? `<br><small class="text-muted">${student.student_mobile}</small>` : ''}
                </td>
                <td>
                    <span class="badge badge-secondary">${student.enrollment_number || 'N/A'}</span>
                </td>
                <td>
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-outline-success btn-sm ${existingStatus === 'present' ? 'active' : ''}">
                            <input type="radio" name="attendance[${student.id}][status]" value="present" ${existingStatus === 'present' ? 'checked' : ''} onchange="updateCounts()"> Present
                        </label>
                        <label class="btn btn-outline-warning btn-sm ${existingStatus === 'late' ? 'active' : ''}">
                            <input type="radio" name="attendance[${student.id}][status]" value="late" ${existingStatus === 'late' ? 'checked' : ''} onchange="updateCounts()"> Late
                        </label>
                        <label class="btn btn-outline-danger btn-sm ${existingStatus === 'absent' ? 'active' : ''}">
                            <input type="radio" name="attendance[${student.id}][status]" value="absent" ${existingStatus === 'absent' ? 'checked' : ''} onchange="updateCounts()"> Absent
                        </label>
                        <label class="btn btn-outline-info btn-sm ${existingStatus === 'excused' ? 'active' : ''}">
                            <input type="radio" name="attendance[${student.id}][status]" value="excused" ${existingStatus === 'excused' ? 'checked' : ''} onchange="updateCounts()"> Excused
                        </label>
                    </div>
                    <input type="hidden" name="attendance[${student.id}][student_id]" value="${student.id}">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addRemarks(${student.id})">
                        <i class="fas fa-comment"></i>
                    </button>
                </td>
            </tr>
        `);
    });

    updateCounts();
}

function markAll(status) {
    $(`input[value="${status}"]`).prop('checked', true).closest('label').addClass('active');
    $(`input[value!="${status}"]`).closest('label').removeClass('active');
    updateCounts();
}

function updateCounts() {
    const total = $('input[name*="[status]"]').length / 4; // 4 radio buttons per student
    const present = $('input[value="present"]:checked').length;
    const absent = $('input[value="absent"]:checked').length;
    
    $('#totalStudents').text(total);
    $('#presentCount').text(present + ' present');
    $('#absentCount').text(absent + ' absent');
}

function addRemarks(studentId) {
    const remarks = prompt('Add remarks for this student:');
    if (remarks !== null) {
        // Add hidden input for remarks
        $(`input[name="attendance[${studentId}][student_id]"]`).after(`
            <input type="hidden" name="attendance[${studentId}][remarks]" value="${remarks}">
        `);
    }
}

function submitAttendance() {
    const formData = new FormData();
    const batchId = $('#batch_id').val();
    const date = $('#date').val();
    const subjectId = $('#subject_id').val();

    // Validate required fields
    if (!batchId || !date || !subjectId) {
        alert('Please fill in all required fields (Date, Batch, Subject).');
        return;
    }

    // Check if any students are loaded
    const attendanceInputs = $('input[name*="[status]"]:checked');
    if (attendanceInputs.length === 0) {
        alert('Please load students and mark their attendance first.');
        return;
    }

    // Collect attendance data
    const attendanceData = [];
    $('input[name*="[student_id]"]').each(function() {
        const studentId = $(this).val();
        const status = $(`input[name="attendance[${studentId}][status]"]:checked`).val();
        const remarks = $(`input[name="attendance[${studentId}][remarks]"]`).val();
        
        if (status) {
            attendanceData.push({
                student_id: studentId,
                status: status,
                remarks: remarks || null
            });
        }
    });

    // Prepare form data
    formData.append('_token', $('input[name="_token"]').val());
    formData.append('batch_id', batchId);
    formData.append('date', date);
    formData.append('subject_id', subjectId);
    attendanceData.forEach((item, index) => {
        formData.append(`attendance[${index}][student_id]`, item.student_id);
        formData.append(`attendance[${index}][status]`, item.status);
        if (item.remarks) {
            formData.append(`attendance[${index}][remarks]`, item.remarks);
        }
    });

    // Show loading modal
    $('#loadingModal').modal('show');

    // Submit the form
    $.ajax({
        url: '{{ route("admin.daily-attendance.store") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#loadingModal').modal('hide');
            if (response.success) {
                alert('Attendance saved successfully!');
                window.location.href = '{{ route("admin.daily-attendance.index") }}';
            } else {
                alert('Failed to save attendance: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            $('#loadingModal').modal('hide');
            console.error('Submit Error:', xhr.responseText);
            
            let message = 'Failed to save attendance. Please try again.';
            if (xhr.status === 422) {
                // Validation errors
                const errors = xhr.responseJSON.errors;
                if (errors) {
                    message = 'Validation errors:\n';
                    Object.keys(errors).forEach(key => {
                        message += '- ' + errors[key][0] + '\n';
                    });
                }
            }
            alert(message);
        }
    });
}

function showSuccess(message) {
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    $('.container-fluid').prepend(alertHtml);
}

function showError(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    $('.container-fluid').prepend(alertHtml);
}
</script>
@endpush

@push('styles')
<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-group-toggle .btn {
    border-radius: 0.25rem !important;
    margin-right: 2px;
}

.btn-group-toggle .btn.active {
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.table td {
    vertical-align: middle;
}

.card {
    transition: all 0.3s ease;
}

#studentsContainer {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush