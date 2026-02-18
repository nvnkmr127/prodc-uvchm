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

                    <!-- Mode Selection -->
                    <div class="row mb-4">
                        <div class="col-12 text-center">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-primary active" id="modeClassBtn" onclick="toggleMode('class')">
                                    <input type="radio" name="mode" value="class" checked> Class Attendance
                                </label>
                                <label class="btn btn-outline-primary" id="modeStudentBtn" onclick="toggleMode('student')">
                                    <input type="radio" name="mode" value="student"> Student Bulk View
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- CLASS MODE SECTION -->
                    <div id="classModeSection">

                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date" class="font-weight-bold">Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date" name="date"
                                        value="{{ $date ?? date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="batch_id" class="font-weight-bold">Batch <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="batch_id" name="batch_id" required>
                                        <option value="">Select Batch</option>
                                        @foreach($batches as $batch)
                                            <option value="{{ $batch->id }}" {{ (isset($batch) && $batch->id == ($batchId ?? '')) ? 'selected' : '' }}>
                                                {{ $batch->name }} ({!! $batch->course->name ?? 'No Course' !!})
                                            </option>
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
                                            <button type="button" class="btn btn-success btn-sm"
                                                onclick="markAll('present')">
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
                        <!-- END CLASS MODE SECTION -->

                        <!-- STUDENT MODE SECTION -->
                        <div id="studentModeSection" style="display: none;">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sm_batch_id" class="font-weight-bold">Batch <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" id="sm_batch_id"
                                            onchange="loadStudentsForBatch(this.value)">
                                            <option value="">Select Batch</option>
                                            @foreach($batches as $batch)
                                                <option value="{{ $batch->id }}">{{ $batch->name }}
                                                    ({{ $batch->course->name ?? 'No Course' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sm_student_id" class="font-weight-bold">Student <span
                                                class="text-danger">*</span></label>
                                        <!-- Search Input -->
                                        <input type="text" class="form-control form-control-sm mb-2" id="studentSearch"
                                            placeholder="Search student..." onkeyup="filterStudents(this.value)" disabled>
                                        <select class="form-control" id="sm_student_id" disabled
                                            onchange="loadStudentCalendar()">
                                            <option value="">Select Batch First</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sm_month" class="font-weight-bold">Month <span
                                                class="text-danger">*</span></label>
                                        <input type="month" class="form-control" id="sm_month" value="{{ date('Y-m') }}"
                                            onchange="loadStudentCalendar()">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4" id="calendarActions" style="display:none;">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="font-weight-bold text-primary mb-0">Select dates to mark:</h6>
                                        <small class="text-muted">Click dates to select/deselect</small>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success btn-sm"
                                            onclick="bulkMarkStudent('present')">
                                            <i class="fas fa-check"></i> Mark Selected Present
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="bulkMarkStudent('absent')">
                                            <i class="fas fa-times"></i> Mark Selected Absent
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm"
                                            onclick="bulkMarkStudent('late')">
                                            <i class="fas fa-clock"></i> Mark Selected Late
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm"
                                            onclick="bulkMarkStudent('excused')">
                                            <i class="fas fa-notes-medical"></i> Mark Selected Excused
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="clearDateSelection()">
                                            <i class="fas fa-undo"></i> Clear Selection
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Calendar Container -->
                            <div id="calendarContainer" class="row">
                                <!-- Calendar will be generated here -->
                                <div class="col-12 text-center text-muted py-5">
                                    <i class="fas fa-calendar fa-3x mb-3 text-gray-300"></i>
                                    <p>Select a student to view their attendance calendar</p>
                                </div>
                            </div>

                            <div class="row mt-4" id="studentSaveAction" style="display:none;">
                                <div class="col-12 text-right">
                                    <button type="button" class="btn btn-primary" onclick="saveStudentBulkAttendance()">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- END STUDENT MODE SECTION -->
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
        $(document).ready(function () {
            // Load students when batch changes
            $('#batch_id').change(function () {
                if ($('#batch_id').val()) {
                    $('#loadStudentsBtn').prop('disabled', false);
                } else {
                    $('#loadStudentsBtn').prop('disabled', true);
                    $('#studentsContainer').hide();
                }
            });

            // Load students button click
            $('#loadStudentsBtn').click(function () {
                loadStudents();
            });

            // Form submission
            $('#attendanceForm').submit(function (e) {
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
                success: function (response) {
                    if (response.success) {
                        displayStudents(response.students);
                        $('#studentsContainer').show();
                    } else {
                        alert('Failed to load students: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Load students error:', error);
                    alert('Failed to load students. Please try again.');
                },
                complete: function () {
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

            // Validate required fields
            if (!batchId || !date) {
                alert('Please fill in all required fields (Date, Batch).');
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
            $('input[name*="[student_id]"]').each(function () {
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
            formData.append('attendance_date', date);
            attendanceData.forEach((item, index) => {
                formData.append(`attendances[${index}][student_id]`, item.student_id);
                formData.append(`attendances[${index}][status]`, item.status);
                if (item.remarks) {
                    formData.append(`attendances[${index}][remarks]`, item.remarks);
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
                success: function (response) {
                    $('#loadingModal').modal('hide');
                    if (response.success) {
                        alert('Attendance saved successfully!');
                        window.location.href = '{{ route("admin.daily-attendance.index") }}';
                    } else {
                        alert('Failed to save attendance: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function (xhr, status, error) {
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

        // Student Mode Variables
        let selectedDates = new Set();
        let studentChanges = {}; // Stores pending changes: date -> {status, remarks}
        let currentMonthData = {}; // Stores fetched existing data

        function toggleMode(mode) {
            if (mode === 'class') {
                $('#classModeSection').show();
                $('#studentModeSection').hide();
            } else {
                $('#classModeSection').hide();
                $('#studentModeSection').show();
            }
        }

        // Stores all students for filtering
        let allBatchStudents = [];

        function loadStudentsForBatch(batchId) {
            const studentSelect = $('#sm_student_id');
            const searchInput = $('#studentSearch');

            studentSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            searchInput.prop('disabled', true);

            if (!batchId) {
                studentSelect.html('<option value="">Select Batch First</option>');
                return;
            }

            $.ajax({
                url: '{{ route("admin.daily-attendance.batch-students", ":batch") }}'.replace(':batch', batchId),
                method: 'GET',
                success: function (response) {
                    allBatchStudents = response.students || [];
                    populateStudentDropdown(allBatchStudents);
                    studentSelect.prop('disabled', false);
                    searchInput.prop('disabled', false);
                },
                error: function (err) {
                    console.error(err);
                    alert('Failed to load students');
                    studentSelect.html('<option value="">Error loading students</option>');
                }
            });
        }

        function filterStudents(query) {
            const lowerQuery = query.toLowerCase();
            const filtered = allBatchStudents.filter(student =>
                student.name.toLowerCase().includes(lowerQuery) ||
                (student.enrollment_number && student.enrollment_number.toLowerCase().includes(lowerQuery))
            );
            populateStudentDropdown(filtered);
        }

        function populateStudentDropdown(students) {
            const studentSelect = $('#sm_student_id');
            studentSelect.empty().append('<option value="">Select Student</option>');

            if (students.length === 0) {
                studentSelect.append('<option value="">No students found</option>');
                return;
            }

            students.forEach(student => {
                studentSelect.append(`<option value="${student.id}">${student.name} (${student.enrollment_number || 'N/A'})</option>`);
            });

            // Maintain selection if possible (rare case while typing)
            // Actually, usually typing clears selection context, so default to empty is fine.
        }

        function loadStudentCalendar() {
            const studentId = $('#sm_student_id').val();
            const batchId = $('#sm_batch_id').val();
            const month = $('#sm_month').val();

            if (!studentId || !month) {
                $('#calendarContainer').html('<div class="col-12 text-center text-muted py-5"><i class="fas fa-calendar fa-3x mb-3 text-gray-300"></i><p>Select a student and month</p></div>');
                $('#calendarActions').hide();
                $('#studentSaveAction').hide();
                return;
            }

            // Reset state
            selectedDates.clear();
            studentChanges = {};

            // Fetch existing attendance
            $.ajax({
                url: '{{ route("admin.daily-attendance.student-month", ":student") }}'.replace(':student', studentId),
                data: { month: month, batch_id: batchId },
                success: function (response) {
                    currentMonthData = response.data;
                    renderCalendar(month, currentMonthData);
                    $('#calendarActions').show();
                    $('#studentSaveAction').show();
                },
                error: function (err) {
                    console.error(err);
                    alert('Failed to load attendance data');
                }
            });
        }

        function renderCalendar(monthStr, attendanceData) {
            const [year, month] = monthStr.split('-').map(Number);
            const daysInMonth = new Date(year, month, 0).getDate();
            const firstDay = new Date(year, month - 1, 1).getDay(); // 0 = Sunday

            let html = '<div class="col-12"><div class="calendar-grid">';

            // Headers
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            days.forEach(day => html += `<div class="calendar-day-header">${day}</div>`);

            // Empty cells
            for (let i = 0; i < firstDay; i++) {
                html += '<div></div>';
            }

            // Days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const data = attendanceData[dateStr] || {};
                const status = studentChanges[dateStr]?.status || data.status || ''; // Check pending changes first
                const statusClass = status ? `status-${status}` : '';
                const isSelected = selectedDates.has(dateStr) ? 'selected' : '';

                html += `
                            <div class="calendar-day ${statusClass} ${isSelected}" onclick="toggleDateSelection(this, '${dateStr}')" data-date="${dateStr}">
                                <div class="day-number">${day}</div>
                                ${status ? `<span class="day-status badge badge-light">${status}</span>` : ''}
                            </div>
                        `;
            }

            html += '</div></div>';
            $('#calendarContainer').html(html);
        }

        function toggleDateSelection(el, dateStr) {
            if (selectedDates.has(dateStr)) {
                selectedDates.delete(dateStr);
                $(el).removeClass('selected');
            } else {
                selectedDates.add(dateStr);
                $(el).addClass('selected');
            }
        }

        function clearDateSelection() {
            selectedDates.clear();
            $('.calendar-day.selected').removeClass('selected');
        }

        function bulkMarkStudent(status) {
            if (selectedDates.size === 0) {
                alert('Please select dates from the calendar first.');
                return;
            }

            selectedDates.forEach(date => {
                studentChanges[date] = { status: status, remarks: null };
            });

            // Re-render to show changes
            renderCalendar($('#sm_month').val(), currentMonthData);

            // Clear selection
            selectedDates.clear();
        }

        function saveStudentBulkAttendance() {
            const studentId = $('#sm_student_id').val();
            const batchId = $('#sm_batch_id').val();

            // Optional subject from main form
            let subjectId = $('#subject_id').val();

            if (Object.keys(studentChanges).length === 0) {
                alert('No changes to save.');
                return;
            }

            const attendances = Object.keys(studentChanges).map(date => ({
                date: date,
                status: studentChanges[date].status,
                remarks: studentChanges[date].remarks
            }));

            // Show loading
            const btn = $('#studentSaveAction button');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

            // Build payload
            const payload = {
                _token: '{{ csrf_token() }}',
                student_id: studentId,
                batch_id: batchId,
                attendances: attendances
            };

            if (subjectId) {
                payload.subject_id = subjectId;
            }

            $.ajax({
                url: '{{ route("admin.daily-attendance.student-bulk-store") }}',
                method: 'POST',
                data: payload,
                success: function (response) {
                    alert(response.message);
                    studentChanges = {};
                    loadStudentCalendar(); // Refresh
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to save';
                    alert(msg);
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
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
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
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
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Calendar Styles */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
            background: #f8f9fc;
            /* Light background for constraint contrast */
            padding: 15px;
            border-radius: 8px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 800;
            /* Bolder */
            color: #4e73df;
            /* Primary color */
            padding: 10px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .calendar-day {
            aspect-ratio: 1;
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            /* Softer corners */
            padding: 5px;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            /* Subtle shadow */
        }

        .calendar-day:hover {
            background-color: #eaecf4;
            border-color: #4e73df;
            transform: translateY(-2px);
            /* Lift effect */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .calendar-day.selected {
            border: 2px solid #4e73df;
            background-color: #d1dffd;
            /* Lighter primary */
            transform: scale(1.05);
            z-index: 2;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2);
        }

        .calendar-day.status-present {
            background-color: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }

        .calendar-day.status-absent {
            background-color: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .calendar-day.status-late {
            background-color: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }

        .calendar-day.status-excused {
            background-color: #e0f2fe;
            border-color: #0ea5e9;
            color: #075985;
        }

        .day-number {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .day-status {
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }
    </style>
@endpush