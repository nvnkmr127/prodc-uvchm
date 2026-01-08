@extends('layouts.theme')
@section('title', 'Enhanced Timetable Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-calendar-alt me-2"></i>Enhanced Timetable Management
        </h1>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-info" onclick="checkSystemReadiness()">
                <i class="fas fa-check-circle"></i> System Check
            </button>
            <button type="button" class="btn btn-sm btn-success" onclick="openWorkingDaysConfig()">
                <i class="fas fa-calendar-week"></i> Working Days
            </button>
        </div>
    </div>

    <!-- System Status Card -->
    <div class="card shadow mb-4" id="systemStatusCard">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-heartbeat me-2"></i>System Readiness Status
            </h6>
            <span class="badge badge-{{ $systemStatus['overall'] === 'ready' ? 'success' : 'warning' }}">
                {{ ucfirst($systemStatus['overall']) }}
            </span>
        </div>
        <div class="card-body">
            @if($systemStatus['overall'] === 'ready')
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>System Ready!</strong> All requirements are met for timetable generation.
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>System Check Required!</strong> Please ensure all requirements are met.
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <h6>Core Requirements</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-{{ $systemStatus['courses'] ? 'check text-success' : 'times text-danger' }}"></i> Courses: {{ $systemCounts['courses'] ?? 0 }}</li>
                        <li><i class="fas fa-{{ $systemStatus['subjects'] ? 'check text-success' : 'times text-danger' }}"></i> Subjects: {{ $systemCounts['subjects'] ?? 0 }}</li>
                        <li><i class="fas fa-{{ $systemStatus['faculty'] ? 'check text-success' : 'times text-danger' }}"></i> Faculty: {{ $systemCounts['faculties'] ?? 0 }}</li>
                        <li><i class="fas fa-{{ $systemStatus['classrooms'] ? 'check text-success' : 'times text-danger' }}"></i> Classrooms: {{ $systemCounts['classrooms'] ?? 0 }}</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Timetable Requirements</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-{{ $systemStatus['time_slots'] ? 'check text-success' : 'times text-danger' }}"></i> Time Slots: {{ $systemCounts['time_slots'] ?? 0 }}</li>
                        <li><i class="fas fa-{{ $systemStatus['practical_groups'] ? 'check text-success' : 'times text-danger' }}"></i> Practical Groups: {{ $systemCounts['practical_groups'] ?? 0 }}</li>
                        <li><i class="fas fa-{{ $systemStatus['lab_subjects'] ? 'check text-success' : 'times text-danger' }}"></i> Lab Subjects: {{ $systemCounts['lab_subjects'] ?? 0 }}/4</li>
                        <li><i class="fas fa-calendar-week text-info"></i> Working Days: {{ isset($workingDaysConfig) ? count($workingDaysConfig) : 6 }} days</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Working Days Configuration -->
    <div class="row mb-4">
        <div class="col-md-8">
            <!-- Timetable Generation Card -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-magic me-2"></i>Generate Weekly Timetable
                    </h6>
                </div>
                <div class="card-body">
                    <form id="timetableGenerationForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="course_ids">Select Courses <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="course_ids[]" id="course_ids" multiple required>
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->batches_count ?? $course->batches->count() }} batches)</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year_id">Academic Year <span class="text-danger">*</span></label>
                                    <select class="form-control" name="academic_year_id" id="academic_year_id" required>
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" {{ ($currentAcademicYear && $year->id === $currentAcademicYear->id) ? 'selected' : '' }}>
                                                {{ $year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="week_start">Week Starting Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="week_start" id="week_start" 
                                           value="{{ now()->startOfWeek()->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Generation Options</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="clear_existing" id="clear_existing" checked>
                                        <label class="form-check-label" for="clear_existing">
                                            Clear existing timetable for selected week
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="generate_labs" id="generate_labs" checked>
                                        <label class="form-check-label" for="generate_labs">
                                            Generate lab sessions (Service, Kitchen, Front Office, Housekeeping)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="generate_theory" id="generate_theory" checked>
                                        <label class="form-check-label" for="generate_theory">
                                            Generate theory classes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" id="generateBtn">
                                    <i class="fas fa-magic me-2"></i>Generate Timetable
                                </button>
                                <button type="button" class="btn btn-secondary ms-2" onclick="setupLabSubjectsHandler()">
                                    <i class="fas fa-flask me-2"></i>Setup Lab Subjects
                                </button>
                                <button type="button" class="btn btn-info ms-2" onclick="checkConflictsHandler()">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Check Conflicts
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Quick Actions Card -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickScheduleModal()">
                            <i class="fas fa-plus me-2"></i>Quick Schedule
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="bulkOperations()">
                            <i class="fas fa-tasks me-2"></i>Bulk Operations
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="exportTimetable()">
                            <i class="fas fa-download me-2"></i>Export PDF
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="viewReports()">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </button>
                    </div>

                    <hr>
                    
                    <h6>Current Working Days</h6>
                    <div class="working-days-display">
                        @if(isset($workingDaysConfig))
                            @foreach($workingDaysConfig as $day)
                                <span class="badge badge-primary me-1">{{ ucfirst($day) }}</span>
                            @endforeach
                        @else
                            <span class="badge badge-primary me-1">Mon</span>
                            <span class="badge badge-primary me-1">Tue</span>
                            <span class="badge badge-primary me-1">Wed</span>
                            <span class="badge badge-primary me-1">Thu</span>
                            <span class="badge badge-primary me-1">Fri</span>
                            <span class="badge badge-primary me-1">Sat</span>
                        @endif
                    </div>
                    <small class="text-muted">Labs: Mon-Fri | Theory: {{ (isset($workingDaysConfig) && in_array('saturday', $workingDaysConfig)) || !isset($workingDaysConfig) ? 'Mon-Sat' : 'Mon-Fri' }}</small>
                </div>
            </div>

            <!-- Lab Requirements Card -->
            <div class="card shadow mt-3">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-flask me-2"></i>Lab Requirements (FR-2, FR-3)
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @foreach(['Service Lab', 'Kitchen Lab', 'Front Office Lab', 'Housekeeping Lab'] as $labType)
                            @php
                                $exists = $systemCounts['lab_checks'][$labType] ?? false;
                            @endphp
                            <li class="mb-2">
                                <i class="fas fa-{{ $exists ? 'check text-success' : 'times text-danger' }} me-2"></i>
                                {{ $labType }}
                                @if($exists)
                                    <small class="text-muted">(Once per week)</small>
                                @else
                                    <small class="text-danger">(Missing)</small>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Timetable Calendar View -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-calendar me-2"></i>Timetable Calendar
            </h6>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshCalendar()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="filterModal()">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Calendar Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-control form-control-sm" id="filterCourse" onchange="applyFilters()">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control form-control-sm" id="filterFaculty" onchange="applyFilters()">
                        <option value="">All Faculty</option>
                        @if(isset($faculties))
                            @foreach($faculties as $faculty)
                                <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control form-control-sm" id="filterSessionType" onchange="applyFilters()">
                        <option value="">All Sessions</option>
                        <option value="lab">Lab Sessions</option>
                        <option value="regular">Theory Classes</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control form-control-sm" id="filterClassroom" onchange="applyFilters()">
                        <option value="">All Classrooms</option>
                        @if(isset($classrooms))
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <!-- FullCalendar Container -->
            <div id="timetableCalendar"></div>
        </div>
    </div>
</div>

<!-- Working Days Configuration Modal -->
<div class="modal fade" id="workingDaysModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-week me-2"></i>Configure Working Days
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="workingDaysForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Select Working Days</label>
                        <div class="working-days-checkboxes">
                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="working_days[]" 
                                           value="{{ $day }}" id="day_{{ $day }}"
                                           {{ (isset($workingDaysConfig) && in_array($day, $workingDaysConfig)) || (!isset($workingDaysConfig) && $day !== 'sunday') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="day_{{ $day }}">
                                        {{ ucfirst($day) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Saturday will be restricted to theory classes only. Lab sessions are scheduled Monday-Friday.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveWorkingDays()">
                    <i class="fas fa-save me-2"></i>Save Configuration
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Schedule Modal -->
<div class="modal fade" id="quickScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Quick Schedule Session
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickScheduleForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="quick_course_id">Course</label>
                                <select class="form-control" name="course_id" id="quick_course_id" onchange="loadBatches(this.value)">
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="quick_batch_id">Batch</label>
                                <select class="form-control" name="batch_id" id="quick_batch_id" required>
                                    <option value="">Select Course First</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="quick_subject_id">Subject</label>
                                <select class="form-control" name="subject_id" id="quick_subject_id" required>
                                    <option value="">Select Subject</option>
                                    @foreach($courses->flatMap->subjects->unique('id') as $subject)
                                        <option value="{{ $subject->id }}" data-is-lab="{{ $subject->requires_lab ? 1 : 0 }}">
                                            {{ $subject->name }} {{ $subject->requires_lab ? '(Lab)' : '(Theory)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="quick_user_id">Faculty</label>
                                <select class="form-control" name="user_id" id="quick_user_id" required>
                                    <option value="">Select Faculty</option>
                                    @if(isset($faculties))
                                        @foreach($faculties as $faculty)
                                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="quick_schedule_date">Date</label>
                                <input type="date" class="form-control" name="schedule_date" id="quick_schedule_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="quick_time_slot_id">Time Slot</label>
                                <select class="form-control" name="time_slot_id" id="quick_time_slot_id" required>
                                    <option value="">Select Time Slot</option>
                                    @if(isset($timeSlots))
                                        @foreach($timeSlots as $slot)
                                            <option value="{{ $slot->id }}">
                                                {{ $slot->start_time }} - {{ $slot->end_time }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="quick_classroom_id">Classroom</label>
                                <select class="form-control" name="classroom_id" id="quick_classroom_id" required>
                                    <option value="">Select Classroom</option>
                                    @if(isset($classrooms))
                                        @foreach($classrooms as $classroom)
                                            <option value="{{ $classroom->id }}" data-is-lab="{{ isset($classroom->is_lab) && $classroom->is_lab ? 1 : 0 }}">
                                                {{ $classroom->name }} {{ (isset($classroom->is_lab) && $classroom->is_lab) ? '(Lab)' : '' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="practicalGroupRow" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="quick_practical_group_id">Practical Group (for Lab Sessions)</label>
                                <select class="form-control" name="practical_group_id" id="quick_practical_group_id">
                                    <option value="">Select Practical Group</option>
                                    @if(isset($practicalGroups))
                                        @foreach($practicalGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_lab_session" id="is_lab_session">
                                <label class="form-check-label" for="is_lab_session">
                                    This is a lab session
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="quick_notes">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" id="quick_notes" rows="2" placeholder="Additional notes for this session"></textarea>
                    </div>

                    <input type="hidden" name="academic_year_id" value="{{ isset($currentAcademicYear) ? $currentAcademicYear->id : ($academicYears->first()->id ?? '') }}">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickSchedule()">
                    <i class="fas fa-save me-2"></i>Schedule Session
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
.working-days-display .badge {
    font-size: 0.75rem;
}
.fc-event {
    cursor: pointer;
}
.fc-event-lab {
    background-color: #17a2b8 !important;
    border-color: #17a2b8 !important;
}
.fc-event-theory {
    background-color: #007bff !important;
    border-color: #007bff !important;
}
.working-days-checkboxes {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let calendar;

$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: 'Select courses...',
        allowClear: true
    });

    // Initialize FullCalendar
    initializeCalendar();

    // Initialize form handlers
    initializeFormHandlers();
});

function initializeCalendar() {
    const calendarEl = document.getElementById('timetableCalendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        height: 600,
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5, 6], // Monday - Saturday
            startTime: '09:00',
            endTime: '17:00'
        },
        events: {
            url: '{{ route("admin.timetable.enhanced.events") }}',
            method: 'GET',
            extraParams: function() {
                return {
                    course_id: $('#filterCourse').val(),
                    faculty_id: $('#filterFaculty').val(),
                    session_type: $('#filterSessionType').val(),
                    classroom_id: $('#filterClassroom').val(),
                    academic_year_id: $('#academic_year_id').val()
                };
            }
        },
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        eventDrop: function(info) {
            moveSession(info.event, info.event.start);
        },
        eventResize: function(info) {
            updateSessionDuration(info.event);
        },
        eventClassNames: function(arg) {
            return arg.event.extendedProps.isLabSession ? 'fc-event-lab' : 'fc-event-theory';
        }
    });
    
    calendar.render();
}

function initializeFormHandlers() {
    // Timetable generation form
    $('#timetableGenerationForm').on('submit', function(e) {
        e.preventDefault();
        generateTimetable();
    });

    // Subject change handler for quick schedule
    $('#quick_subject_id').on('change', function() {
        const isLab = $(this).find('option:selected').data('is-lab');
        if (isLab) {
            $('#practicalGroupRow').show();
            $('#is_lab_session').prop('checked', true);
        } else {
            $('#practicalGroupRow').hide();
            $('#is_lab_session').prop('checked', false);
        }
        filterClassrooms(isLab);
    });
}

function generateTimetable() {
    const formData = new FormData($('#timetableGenerationForm')[0]);
    
    $('#generateBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Generating...');
    
    $.ajax({
        url: '{{ route("admin.timetable.enhanced.generate") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    html: `
                        <p>${response.message}</p>
                        <div class="mt-3">
                            <strong>Sessions Created:</strong> ${response.sessions_created || 0}<br>
                            <strong>Lab Sessions:</strong> ${response.lab_sessions || 0}<br>
                            <strong>Theory Sessions:</strong> ${response.theory_sessions || 0}
                        </div>
                    `,
                    showConfirmButton: true
                }).then(() => {
                    calendar.refetchEvents();
                });
                
                // Show generation report
                if (response.report) {
                    showGenerationReport(response.report);
                }
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            Swal.fire('Error', response.message || 'Failed to generate timetable', 'error');
        },
        complete: function() {
            $('#generateBtn').prop('disabled', false).html('<i class="fas fa-magic me-2"></i>Generate Timetable');
        }
    });
}

// ✅ FIXED: Renamed function to avoid conflicts
function setupLabSubjectsHandler() {
    const courseIds = $('#course_ids').val();
    if (!courseIds || courseIds.length === 0) {
        Swal.fire('Warning', 'Please select at least one course first', 'warning');
        return;
    }

    $.ajax({
        url: '{{ route("admin.timetable.enhanced.setup-subjects") }}',
        method: 'POST',
        data: {
            course_ids: courseIds,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Lab Subjects Setup Complete',
                    html: `<p>${response.message}</p>`,
                    showConfirmButton: true
                }).then(() => {
                    location.reload(); // Refresh to show updated system status
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            Swal.fire('Error', response.message || 'Failed to setup lab subjects', 'error');
        }
    });
}

function openWorkingDaysConfig() {
    $('#workingDaysModal').modal('show');
}

function saveWorkingDays() {
    const workingDays = [];
    $('input[name="working_days[]"]:checked').each(function() {
        workingDays.push($(this).val());
    });

    if (workingDays.length === 0) {
        Swal.fire('Warning', 'Please select at least one working day', 'warning');
        return;
    }

    $.ajax({
        url: '{{ route("admin.timetable.enhanced.update-working-days") }}',
        method: 'POST',
        data: {
            working_days: workingDays,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#workingDaysModal').modal('hide');
                Swal.fire('Success', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            Swal.fire('Error', response.message || 'Failed to update working days', 'error');
        }
    });
}

function quickScheduleModal() {
    $('#quickScheduleModal').modal('show');
    $('#quick_schedule_date').val(new Date().toISOString().split('T')[0]);
}

function submitQuickSchedule() {
    const formData = new FormData($('#quickScheduleForm')[0]);
    
    $.ajax({
        url: '{{ route("admin.timetable.enhanced.quick-schedule") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#quickScheduleModal').modal('hide');
                Swal.fire('Success', response.message, 'success');
                calendar.refetchEvents();
                $('#quickScheduleForm')[0].reset();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            if (xhr.status === 409 && response.conflicts) {
                showConflicts(response.conflicts);
            } else {
                Swal.fire('Error', response.message || 'Failed to schedule session', 'error');
            }
        }
    });
}

function loadBatches(courseId) {
    if (!courseId) {
        $('#quick_batch_id').html('<option value="">Select Course First</option>');
        return;
    }

    $.ajax({
        url: '{{ route("admin.api.timetable.courses.batches", "") }}/' + courseId,
        method: 'GET',
        success: function(batches) {
            let options = '<option value="">Select Batch</option>';
            batches.forEach(batch => {
                options += `<option value="${batch.id}">${batch.name}</option>`;
            });
            $('#quick_batch_id').html(options);
        },
        error: function() {
            $('#quick_batch_id').html('<option value="">Error loading batches</option>');
        }
    });
}

function filterClassrooms(isLab) {
    $('#quick_classroom_id option').each(function() {
        const classroomIsLab = $(this).data('is-lab');
        if (isLab && !classroomIsLab) {
            $(this).hide();
        } else if (!isLab && classroomIsLab) {
            $(this).show(); // Show all for theory, but prefer non-lab
        } else {
            $(this).show();
        }
    });
}

function applyFilters() {
    calendar.refetchEvents();
}

function refreshCalendar() {
    calendar.refetchEvents();
}

// ✅ FIXED: Renamed function to avoid conflicts
function checkConflictsHandler() {
    const courseIds = $('#course_ids').val();
    const academicYearId = $('#academic_year_id').val();
    const weekStart = $('#week_start').val();

    if (!courseIds || !academicYearId || !weekStart) {
        Swal.fire('Warning', 'Please fill in all required fields first', 'warning');
        return;
    }

    $.ajax({
        url: '{{ route("admin.timetable.enhanced.conflicts") }}',
        method: 'POST',
        data: {
            course_ids: courseIds,
            academic_year_id: academicYearId,
            week_start: weekStart,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                if (response.conflicts.length === 0) {
                    Swal.fire('Success', 'No conflicts detected for the selected parameters', 'success');
                } else {
                    showConflicts(response.conflicts);
                }
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            Swal.fire('Error', response.message || 'Failed to check conflicts', 'error');
        }
    });
}

function showConflicts(conflicts) {
    let conflictHtml = '<div class="conflicts-list">';
    
    conflicts.forEach(conflict => {
        conflictHtml += `
            <div class="alert alert-warning mb-2">
                <strong>${conflict.type}:</strong> ${conflict.datetime}<br>
                <small>Affected sessions: ${conflict.sessions.length}</small>
            </div>
        `;
    });
    
    conflictHtml += '</div>';
    
    Swal.fire({
        icon: 'warning',
        title: `${conflicts.length} Conflict(s) Detected`,
        html: conflictHtml,
        showConfirmButton: true,
        width: '600px'
    });
}

function showEventDetails(event) {
    const props = event.extendedProps;
    
    Swal.fire({
        title: event.title,
        html: `
            <div class="text-left">
                <p><strong>Type:</strong> ${props.isLabSession ? 'Lab Session' : 'Theory Class'}</p>
                <p><strong>Faculty:</strong> ${props.facultyName}</p>
                <p><strong>Classroom:</strong> ${props.classroomName}</p>
                <p><strong>Course:</strong> ${props.courseName}</p>
                <p><strong>Batch:</strong> ${props.batchName}</p>
                ${props.practicalGroup ? `<p><strong>Group:</strong> ${props.practicalGroup}</p>` : ''}
                <p><strong>Students:</strong> ${props.studentCount}</p>
                <p><strong>Time:</strong> ${event.start.toLocaleTimeString()} - ${event.end.toLocaleTimeString()}</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-edit"></i> Edit',
        cancelButtonText: '<i class="fas fa-trash"></i> Delete',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            editSession(event.id);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            deleteSession(event.id);
        }
    });
}

function editSession(sessionId) {
    // Implement edit functionality
    window.location.href = `{{ route('admin.timetable.enhanced.edit', '') }}/${sessionId}`;
}

function deleteSession(sessionId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the session',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('admin.timetable.enhanced.destroy', '') }}/${sessionId}`,
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        calendar.refetchEvents();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    Swal.fire('Error', response.message || 'Failed to delete session', 'error');
                }
            });
        }
    });
}

function moveSession(event, newStart) {
    const newDate = newStart.toISOString().split('T')[0];
    const newTime = newStart.toTimeString().split(' ')[0];
    
    // Find closest time slot
    let closestSlotId = null;
    let minDiff = Infinity;
    
    @if(isset($timeSlots))
        @foreach($timeSlots as $slot)
            const slotTime{{ $loop->index }} = '{{ $slot->start_time }}';
            const diff{{ $loop->index }} = Math.abs(new Date('1970-01-01T' + newTime) - new Date('1970-01-01T' + slotTime{{ $loop->index }}));
            if (diff{{ $loop->index }} < minDiff) {
                minDiff = diff{{ $loop->index }};
                closestSlotId = {{ $slot->id }};
            }
        @endforeach
    @endif

    if (!closestSlotId) {
        Swal.fire('Error', 'No suitable time slot found', 'error');
        calendar.refetchEvents();
        return;
    }

    $.ajax({
        url: '{{ route("admin.timetable.enhanced.move") }}',
        method: 'POST',
        data: {
            timetable_id: event.id,
            new_date: newDate,
            new_time_slot_id: closestSlotId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', 'Session moved successfully', 'success');
                calendar.refetchEvents();
            } else {
                Swal.fire('Error', response.message, 'error');
                calendar.refetchEvents(); // Revert
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            if (xhr.status === 409 && response.conflicts) {
                showConflicts(response.conflicts);
            } else {
                Swal.fire('Error', response.message || 'Failed to move session', 'error');
            }
            calendar.refetchEvents(); // Revert
        }
    });
}

function exportTimetable() {
    const courseId = $('#filterCourse').val();
    const weekStart = $('#week_start').val();
    
    let url = '{{ route("admin.timetable.enhanced.pdf") }}?';
    if (courseId) url += `course_id=${courseId}&`;
    if (weekStart) url += `week_start=${weekStart}&`;
    
    window.open(url, '_blank');
}

function showGenerationReport(report) {
    Swal.fire({
        title: 'Generation Report',
        html: `<pre class="text-left" style="max-height: 400px; overflow-y: auto;">${report}</pre>`,
        showConfirmButton: true,
        width: '800px'
    });
}

function checkSystemReadiness() {
    // Refresh the page to show updated system status
    location.reload();
}

function bulkOperations() {
    Swal.fire({
        title: 'Bulk Operations',
        html: `
            <div class="d-grid gap-2">
                <button class="btn btn-outline-danger" onclick="bulkDeleteSessions()">
                    <i class="fas fa-trash me-2"></i>Bulk Delete Sessions
                </button>
                <button class="btn btn-outline-warning" onclick="bulkMoveSessions()">
                    <i class="fas fa-arrows-alt me-2"></i>Bulk Move Sessions
                </button>
                <button class="btn btn-outline-info" onclick="bulkExportData()">
                    <i class="fas fa-download me-2"></i>Bulk Export
                </button>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true
    });
}

function bulkDeleteSessions() {
    // Implementation for bulk delete
    Swal.fire('Info', 'Bulk delete functionality will be implemented here', 'info');
}

function bulkMoveSessions() {
    // Implementation for bulk move
    Swal.fire('Info', 'Bulk move functionality will be implemented here', 'info');
}

function bulkExportData() {
    // Implementation for bulk export
    Swal.fire('Info', 'Bulk export functionality will be implemented here', 'info');
}

function viewReports() {
    window.location.href = '{{ route("admin.timetable.enhanced.reports.utilization") }}';
}

function filterModal() {
    // Implementation for advanced filter modal
    Swal.fire('Info', 'Advanced filter modal will be implemented here', 'info');
}

function updateSessionDuration(event) {
    // Implementation for session duration update
    console.log('Session duration updated:', event);
}

// Prevent notification errors by checking if functions exist
$(document).ready(function() {
    // Disable notification system if it's causing errors
    if (typeof EnhancedNotificationSystem !== 'undefined') {
        try {
            // Initialize only if endpoints exist
            // EnhancedNotificationSystem.init();
        } catch (e) {
            console.log('Notification system disabled due to missing endpoints');
        }
    }
});
</script>
@endpush