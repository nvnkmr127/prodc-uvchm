{{-- resources/views/attendance/create.blade.php --}}
@extends('layouts.theme')

@section('title', 'Take Attendance')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check text-primary"></i> Take Attendance
        </h1>
        <div class="d-sm-flex">
            <a href="{{ route('attendance.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Records
            </a>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Attendance Form --}}
    <div class="row">
        {{-- Main Form --}}
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users mr-2"></i>Select Batch and Date
                    </h6>
                </div>
                <div class="card-body">
                    <form id="attendanceSetupForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <label for="batch_id" class="form-label">Batch <span class="text-danger">*</span></label>
                                <select name="batch_id" id="batch_id" class="form-control" required>
                                    <option value="">Select Batch</option>
                                    @foreach($batches as $batch)
                                        <option value="{{ $batch->id }}" 
                                            {{ (request('batch_id') == $batch->id) ? 'selected' : '' }}>
                                            {{ $batch->name }} ({{ $batch->course->name ?? 'No Course' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="attendance_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="attendance_date" id="attendance_date" 
                                       class="form-control" value="{{ request('date', date('Y-m-d')) }}" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="subject_id" class="form-label">Subject</label>
                                <select name="subject_id" id="subject_id" class="form-control">
                                    <option value="">General Attendance</option>
                                    @foreach($subjects ?? [] as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-primary btn-block" onclick="loadStudents()">
                                    <i class="fas fa-search"></i> Load Students
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Students List --}}
            <div class="card shadow mb-4" id="studentsCard" style="display: none;">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list mr-2"></i>Mark Attendance
                        <span id="studentCount" class="badge badge-secondary ml-2">0</span>
                    </h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-success" onclick="markAllPresent()">
                            <i class="fas fa-check"></i> All Present
                        </button>
                        <button type="button" class="btn btn-danger" onclick="markAllAbsent()">
                            <i class="fas fa-times"></i> All Absent
                        </button>
                        <button type="button" class="btn btn-info" onclick="toggleView()">
                            <i class="fas fa-th"></i> Toggle View
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="attendanceForm" onsubmit="submitAttendance(event)">
                        @csrf
                        <input type="hidden" name="batch_id" id="form_batch_id">
                        <input type="hidden" name="attendance_date" id="form_attendance_date">
                        <input type="hidden" name="subject_id" id="form_subject_id">
                        
                        {{-- List View --}}
                        <div id="listView">
                            <div id="studentsList">
                                <!-- Students will be loaded here -->
                            </div>
                        </div>
                        
                        {{-- Grid View --}}
                        <div id="gridView" style="display: none;">
                            <div class="row" id="studentsGrid">
                                <!-- Students will be loaded here -->
                            </div>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" style="display: none;">
                                <i class="fas fa-save mr-2"></i>Save Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Quick Stats --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-2"></i>Today's Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center" id="quickStats">
                        <div class="col-4">
                            <div class="border-right">
                                <h4 class="text-success" id="presentCount">0</h4>
                                <small class="text-muted">Present</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-right">
                                <h4 class="text-danger" id="absentCount">0</h4>
                                <small class="text-muted">Absent</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h4 class="text-warning" id="lateCount">0</h4>
                            <small class="text-muted">Late</small>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="text-center">
                            <span class="text-muted">Attendance Rate: </span>
                            <strong id="attendanceRate">0%</strong>
                        </div>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-info" id="attendanceProgress" 
                                 style="width: 0%" role="progressbar"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="loadPreviousAttendance()">
                            <i class="fas fa-history mr-1"></i> Copy Previous Day
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="autoMarkByTime()">
                            <i class="fas fa-clock mr-1"></i> Auto Mark by Time
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="saveAsDraft()">
                            <i class="fas fa-save mr-1"></i> Save as Draft
                        </button>
                    </div>
                </div>
            </div>

            {{-- Attendance Rules --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-2"></i>Attendance Rules
                    </h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <ul class="list-unstyled mb-0">
                            <li><i class="fas fa-check text-success mr-1"></i> Grace period: {{ config('attendance.rules.grace_period_minutes', 10) }} minutes</li>
                            <li><i class="fas fa-clock text-warning mr-1"></i> Late threshold: {{ config('attendance.rules.late_threshold_minutes', 15) }} minutes</li>
                            <li><i class="fas fa-percentage text-info mr-1"></i> Minimum required: {{ config('attendance.rules.minimum_percentage', 75) }}%</li>
                        </ul>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentStudents = [];
let currentView = 'list';

$(document).ready(function() {
    // Auto-load students if batch and date are provided
    if ($('#batch_id').val() && $('#attendance_date').val()) {
        loadStudents();
    }
});

function loadStudents() {
    const batchId = $('#batch_id').val();
    const date = $('#attendance_date').val();
    
    if (!batchId || !date) {
        alert('Please select both batch and date');
        return;
    }
    
    // Show loading
    $('#studentsCard').show();
    $('#studentsList').html(`
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading students...</p>
        </div>
    `);
    
    // Update form hidden fields
    $('#form_batch_id').val(batchId);
    $('#form_attendance_date').val(date);
    $('#form_subject_id').val($('#subject_id').val());
    
    // Load students via AJAX
    $.ajax({
        url: `{{ route('attendance.api.students.by-batch', ':batch') }}`.replace(':batch', batchId),
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                currentStudents = response.data;
                renderStudents();
                loadExistingAttendance(date, batchId);
            } else {
                $('#studentsList').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Failed to load students: ${response.message || 'Unknown error'}
                    </div>
                `);
            }
        },
        error: function(xhr) {
            let message = 'Failed to load students';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            $('#studentsList').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    ${message}
                </div>
            `);
        }
    });
}

function renderStudents() {
    if (currentStudents.length === 0) {
        $('#studentsList').html(`
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                No students found in this batch
            </div>
        `);
        return;
    }
    
    $('#studentCount').text(currentStudents.length);
    
    if (currentView === 'list') {
        renderListView();
    } else {
        renderGridView();
    }
    
    $('#submitBtn').show();
    updateStats();
}

function renderListView() {
    let html = '<div class="table-responsive"><table class="table table-bordered table-hover">';
    html += `
        <thead class="thead-light">
            <tr>
                <th width="5%">#</th>
                <th width="40%">Student</th>
                <th width="20%">Enrollment</th>
                <th width="35%">Status</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    currentStudents.forEach((student, index) => {
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="bg-gray-200 rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 35px; height: 35px;">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                        </div>
                        <div>
                            <div class="font-weight-bold">${student.name}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-light">${student.enrollment_number}</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="attendance[${student.id}]" 
                               id="present_${student.id}" value="present" onchange="updateStats()">
                        <label class="btn btn-outline-success" for="present_${student.id}">
                            <i class="fas fa-check"></i> Present
                        </label>
                        
                        <input type="radio" class="btn-check" name="attendance[${student.id}]" 
                               id="absent_${student.id}" value="absent" onchange="updateStats()">
                        <label class="btn btn-outline-danger" for="absent_${student.id}">
                            <i class="fas fa-times"></i> Absent
                        </label>
                        
                        <input type="radio" class="btn-check" name="attendance[${student.id}]" 
                               id="late_${student.id}" value="late" onchange="updateStats()">
                        <label class="btn btn-outline-warning" for="late_${student.id}">
                            <i class="fas fa-clock"></i> Late
                        </label>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    $('#studentsList').html(html);
}

function renderGridView() {
    let html = '';
    
    currentStudents.forEach(student => {
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-body text-center p-3">
                        <div class="mb-3">
                            <div class="bg-gray-200 rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                        </div>
                        <h6 class="card-title mb-1">${student.name}</h6>
                        <small class="text-muted">${student.enrollment_number}</small>
                        
                        <div class="mt-3">
                            <div class="btn-group-vertical btn-group-sm d-grid gap-1">
                                <input type="radio" class="btn-check" name="attendance[${student.id}]" 
                                       id="grid_present_${student.id}" value="present" onchange="updateStats()">
                                <label class="btn btn-outline-success" for="grid_present_${student.id}">
                                    <i class="fas fa-check"></i> Present
                                </label>
                                
                                <input type="radio" class="btn-check" name="attendance[${student.id}]" 
                                       id="grid_absent_${student.id}" value="absent" onchange="updateStats()">
                                <label class="btn btn-outline-danger" for="grid_absent_${student.id}">
                                    <i class="fas fa-times"></i> Absent
                                </label>
                                
                                <input type="radio" class="btn-check" name="attendance[${student.id}]" 
                                       id="grid_late_${student.id}" value="late" onchange="updateStats()">
                                <label class="btn btn-outline-warning" for="grid_late_${student.id}">
                                    <i class="fas fa-clock"></i> Late
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#studentsGrid').html(html);
}

function loadExistingAttendance(date, batchId) {
    $.ajax({
        url: `{{ route('attendance.api.by-date-batch', [':date', ':batch']) }}`.replace(':date', date).replace(':batch', batchId),
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success && response.data) {
                // Set existing attendance values
                Object.keys(response.data).forEach(studentId => {
                    const attendance = response.data[studentId];
                    const status = attendance.status;
                    
                    // Set radio button for current view
                    if (currentView === 'list') {
                        $(`input[name="attendance[${studentId}]"][value="${status}"]`).prop('checked', true);
                    } else {
                        $(`#grid_${status}_${studentId}`).prop('checked', true);
                    }
                });
                
                updateStats();
            }
        },
        error: function(xhr) {
            console.log('No existing attendance found for this date');
        }
    });
}

function toggleView() {
    currentView = currentView === 'list' ? 'grid' : 'list';
    
    // Save current selections
    const currentSelections = {};
    $('input[name^="attendance["]:checked').each(function() {
        const name = $(this).attr('name');
        const studentId = name.match(/attendance\[(\d+)\]/)[1];
        currentSelections[studentId] = $(this).val();
    });
    
    if (currentView === 'list') {
        $('#gridView').hide();
        $('#listView').show();
        renderListView();
    } else {
        $('#listView').hide();
        $('#gridView').show();
        renderGridView();
    }
    
    // Restore selections
    Object.keys(currentSelections).forEach(studentId => {
        const status = currentSelections[studentId];
        if (currentView === 'list') {
            $(`input[name="attendance[${studentId}]"][value="${status}"]`).prop('checked', true);
        } else {
            $(`#grid_${status}_${studentId}`).prop('checked', true);
        }
    });
    
    updateStats();
}

function markAllPresent() {
    $('input[name^="attendance["][value="present"]').prop('checked', true);
    updateStats();
}

function markAllAbsent() {
    $('input[name^="attendance["][value="absent"]').prop('checked', true);
    updateStats();
}

function updateStats() {
    const present = $('input[name^="attendance["][value="present"]:checked').length;
    const absent = $('input[name^="attendance["][value="absent"]:checked').length;
    const late = $('input[name^="attendance["][value="late"]:checked').length;
    const total = currentStudents.length;
    
    $('#presentCount').text(present);
    $('#absentCount').text(absent);
    $('#lateCount').text(late);
    
    const attendanceRate = total > 0 ? Math.round(((present + late) / total) * 100) : 0;
    $('#attendanceRate').text(attendanceRate + '%');
    $('#attendanceProgress').css('width', attendanceRate + '%');
}

function submitAttendance(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('attendanceForm'));
    
    // Add attendance data
    const attendanceData = {};
    $('input[name^="attendance["]:checked').each(function() {
        const name = $(this).attr('name');
        const studentId = name.match(/attendance\[(\d+)\]/)[1];
        attendanceData[studentId] = $(this).val();
    });
    
    formData.append('attendance_data', JSON.stringify(attendanceData));
    
    // Show loading
    $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
    
    $.ajax({
        url: '{{ route("attendance.store") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                  '<i class="fas fa-check-circle mr-2"></i>' + response.message +
                  '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                  '</div>').prependTo('.container-fluid');
                
                // Scroll to top
                $('html, body').animate({scrollTop: 0}, 500);
                
                // Reset form after 2 seconds
                setTimeout(function() {
                    window.location.href = '{{ route("attendance.index") }}';
                }, 2000);
            } else {
                throw new Error(response.message || 'Unknown error occurred');
            }
        },
        error: function(xhr) {
            let message = 'Failed to save attendance';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join(', ');
            }
            
            $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
              '<i class="fas fa-exclamation-triangle mr-2"></i>' + message +
              '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
              '</div>').prependTo('.container-fluid');
            
            $('html, body').animate({scrollTop: 0}, 500);
        },
        complete: function() {
            $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Save Attendance');
        }
    });
}

function loadPreviousAttendance() {
    const batchId = $('#batch_id').val();
    const currentDate = $('#attendance_date').val();
    
    if (!batchId || !currentDate) {
        alert('Please select batch and date first');
        return;
    }
    
    // Calculate previous day
    const date = new Date(currentDate);
    date.setDate(date.getDate() - 1);
    const previousDate = date.toISOString().split('T')[0];
    
    $.ajax({
        url: `{{ route('attendance.api.by-date-batch', [':date', ':batch']) }}`.replace(':date', previousDate).replace(':batch', batchId),
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success && response.data) {
                let copiedCount = 0;
                
                Object.keys(response.data).forEach(studentId => {
                    const attendance = response.data[studentId];
                    const status = attendance.status;
                    
                    // Set radio button
                    if (currentView === 'list') {
                        $(`input[name="attendance[${studentId}]"][value="${status}"]`).prop('checked', true);
                    } else {
                        $(`#grid_${status}_${studentId}`).prop('checked', true);
                    }
                    copiedCount++;
                });
                
                updateStats();
                
                if (copiedCount > 0) {
                    $('<div class="alert alert-info alert-dismissible fade show" role="alert">' +
                      `<i class="fas fa-info-circle mr-2"></i>Copied attendance for ${copiedCount} students from ${previousDate}` +
                      '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                      '</div>').prependTo('.container-fluid');
                } else {
                    alert('No previous attendance found to copy');
                }
            } else {
                alert('No previous attendance found for this batch');
            }
        },
        error: function() {
            alert('Failed to load previous attendance');
        }
    });
}

function autoMarkByTime() {
    // This would implement auto-marking based on current time and timetable
    alert('Auto-mark by time feature coming soon!');
}

function saveAsDraft() {
    // This would save current selections as draft
    alert('Save as draft feature coming soon!');
}
</script>
@endpush

@push('styles')
<style>
.btn-check:checked + .btn {
    background-color: var(--bs-btn-bg);
    border-color: var(--bs-btn-border-color);
    color: var(--bs-btn-color);
}

.btn-outline-success:checked,
.btn-check:checked + .btn-outline-success {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.btn-outline-danger:checked,
.btn-check:checked + .btn-outline-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.btn-outline-warning:checked,
.btn-check:checked + .btn-outline-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    color: #5a5c69;
    background-color: #f8f9fc;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,.075);
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-group-vertical .btn {
    border-radius: 0.25rem !important;
    margin-bottom: 0.25rem;
}

.btn-group-vertical .btn:last-child {
    margin-bottom: 0;
}

.progress {
    height: 8px;
}

.border-right {
    border-right: 1px solid #dee2e6 !important;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem !important;
        margin-bottom: 0.25rem;
    }
    
    .btn-group .btn:last-child {
        margin-bottom: 0;
    }
}
</style>
@endpush