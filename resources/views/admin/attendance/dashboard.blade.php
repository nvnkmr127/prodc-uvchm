{{-- resources/views/admin/attendance/dashboard.blade.php --}}
@extends('layouts.theme')

@section('title', 'Attendance Dashboard')

@push('styles')
<style>
    .activity-item:hover {
        background-color: #f8f9fc;
    }
    
    .activity-avatar .avatar-title {
        width: 32px;
        height: 32px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .avatar .avatar-title {
        width: 40px;
        height: 40px;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .contact-buttons .btn {
        margin: 1px;
    }
    
    #activity-indicator {
        animation: pulse 2s infinite;
        border-radius: 50%;
        font-size: 8px;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .activity-feed {
        scrollbar-width: thin;
        scrollbar-color: #d1d3e2 transparent;
    }
    
    .activity-feed::-webkit-scrollbar {
        width: 6px;
    }
    
    .activity-feed::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .activity-feed::-webkit-scrollbar-thumb {
        background-color: #d1d3e2;
        border-radius: 3px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt text-primary"></i> Attendance Dashboard
            <small class="text-muted">{{ $selectedDate->format('F j, Y') }}</small>
        </h1>
        <div class="d-sm-flex">
            <button class="btn btn-primary btn-sm mr-2" onclick="refreshDashboard()">
                <i class="fas fa-sync" id="refresh-icon"></i> Refresh
            </button>
            <span class="badge badge-info" id="last-updated">
                Last updated: {{ now()->format('H:i:s') }}
            </span>
        </div>
    </div>

    {{-- Date and Filters --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.attendance.dashboard') }}" class="row">
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" name="date" id="date" class="form-control" 
                           value="{{ $selectedDate->format('Y-m-d') }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label for="course_id" class="form-label">Course</label>
                    <select name="course_id" id="course_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="batch_id" class="form-label">Batch</label>
                    <select name="batch_id" id="batch_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All Batches</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $batchId == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }} ({{ $batch->course->name ?? 'No Course' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex">
                        <a href="{{ route('admin.daily-attendance.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus mr-1"></i>Mark Attendance
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Today's Statistics --}}
    <div class="row mb-4" id="stats-cards">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Students
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-students">
                                {{ $todayStats['students']['total'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Present Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="present-students">
                                {{ $todayStats['students']['present'] }}
                            </div>
                            <div class="small text-success" id="present-percentage">
                                {{ $todayStats['students']['percentage'] }}% of total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Absent Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="absent-students">
                                {{ $todayStats['students']['absent'] }}
                            </div>
                            <div class="small text-danger" id="absent-percentage">
                                {{ 100 - $todayStats['students']['percentage'] }}% of total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Late Arrivals
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="late-students">
                                {{ $todayStats['students']['late'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Dashboard Content --}}
    <div class="row">
        {{-- Absent Students List --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-user-times mr-2"></i>Absent Students Today
                        <span class="badge badge-danger ml-2" id="absent-count">{{ $absentStudents->count() }}</span>
                    </h6>
                    <div class="dropdown no-arrow">
                        <button class="btn btn-link btn-sm dropdown-toggle" type="button" 
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                            <button class="dropdown-item" onclick="selectAllAbsent()">
                                <i class="fas fa-check-square fa-sm fa-fw mr-2 text-gray-400"></i>
                                Select All
                            </button>
                            <button class="dropdown-item" onclick="markSelectedPresent()">
                                <i class="fas fa-user-check fa-sm fa-fw mr-2 text-gray-400"></i>
                                Mark Selected Present
                            </button>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item" onclick="exportAbsentList()">
                                <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                                Export List
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="absent-students-container" style="max-height: 600px; overflow-y: auto;">
                        @if($absentStudents->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="30">
                                                <input type="checkbox" id="select-all-absent">
                                            </th>
                                            <th>Student</th>
                                            <th>Batch</th>
                                            <th>Contact</th>
                                            <th>Last Present</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="absent-students-tbody">
                                        @foreach($absentStudents as $student)
                                            <tr id="absent-row-{{ $student['id'] }}">
                                                <td>
                                                    <input type="checkbox" class="absent-checkbox" 
                                                           value="{{ $student['id'] }}">
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm mr-3">
                                                            <div class="avatar-title bg-danger text-white rounded-circle">
                                                                {{ substr($student['name'], 0, 1) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="font-weight-bold">{{ $student['name'] }}</div>
                                                            <div class="small text-muted">{{ $student['enrollment_number'] }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>{{ $student['batch_name'] }}</div>
                                                    <small class="text-muted">{{ $student['course_name'] }}</small>
                                                </td>
                                                <td>
                                                    <div class="contact-buttons">
                                                        @if($student['student_mobile'])
                                                            <a href="tel:{{ $student['student_mobile'] }}" 
                                                               class="btn btn-sm btn-outline-primary mr-1" 
                                                               title="Student: {{ $student['student_mobile'] }}">
                                                                <i class="fas fa-phone"></i>
                                                            </a>
                                                        @endif
                                                        @if($student['father_mobile'])
                                                            <a href="tel:{{ $student['father_mobile'] }}" 
                                                               class="btn btn-sm btn-outline-secondary" 
                                                               title="Father: {{ $student['father_mobile'] }}">
                                                                <i class="fas fa-phone"></i> F
                                                            </a>
                                                        @endif
                                                    </div>
                                                    <div class="small text-muted mt-1">
                                                        @if($student['student_mobile'])
                                                            <div>S: {{ $student['student_mobile'] }}</div>
                                                        @endif
                                                        @if($student['father_mobile'])
                                                            <div>F: {{ $student['father_mobile'] }}</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($student['last_attendance'])
                                                        <span class="badge badge-secondary">
                                                            {{ \Carbon\Carbon::parse($student['last_attendance'])->diffForHumans() }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-warning">Never</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-success" 
                                                            onclick="markStudentPresent({{ $student['id'] }}, '{{ $student['name'] }}')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-success">All Students Present!</h5>
                                <p class="text-muted">No absent students found for {{ $selectedDate->format('F j, Y') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Attendance Activity --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list mr-2"></i>Live Attendance Activity
                        <span class="badge badge-primary" id="activity-indicator">●</span>
                    </h6>
                    <div class="dropdown no-arrow">
                        <button class="btn btn-link btn-sm dropdown-toggle" type="button" 
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                            <button class="dropdown-item" onclick="toggleAutoRefresh()">
                                <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                                <span id="auto-refresh-text">Enable Auto Refresh</span>
                            </button>
                            <button class="dropdown-item" onclick="clearActivity()">
                                <i class="fas fa-eraser fa-sm fa-fw mr-2 text-gray-400"></i>
                                Clear Activity
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="activity-feed" id="activity-feed" style="max-height: 600px; overflow-y: auto;">
                        @if($recentActivity->count() > 0)
                            @foreach($recentActivity as $activity)
                                <div class="activity-item border-bottom p-3" data-activity-id="{{ $activity['id'] }}">
                                    <div class="d-flex align-items-center">
                                        <div class="activity-avatar mr-3">
                                            <div class="avatar-title bg-{{ $activity['status'] == 'present' ? 'success' : ($activity['status'] == 'late' ? 'warning' : 'danger') }} text-white rounded-circle">
                                                <i class="fas fa-{{ $activity['status'] == 'present' ? 'check' : ($activity['status'] == 'late' ? 'clock' : 'times') }}"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="font-weight-bold">{{ $activity['student_name'] }}</div>
                                            <div class="small text-muted">{{ $activity['enrollment_number'] }} • {{ $activity['batch_name'] }}</div>
                                            <div class="small">
                                                <span class="badge badge-{{ $activity['status'] == 'present' ? 'success' : ($activity['status'] == 'late' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($activity['status']) }}
                                                </span>
                                                @if($activity['check_in_time'])
                                                    • {{ \Carbon\Carbon::parse($activity['check_in_time'])->format('H:i') }}
                                                @endif
                                                @if($activity['late_minutes'] > 0)
                                                    <span class="text-warning">({{ $activity['late_minutes'] }}m late)</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                {{ \Carbon\Carbon::parse($activity['marked_at'])->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4" id="no-activity">
                                <i class="fas fa-list fa-2x text-muted mb-3"></i>
                                <p class="text-muted">No attendance activity yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Weekly Attendance Trend --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>Weekly Attendance Trend
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="weeklyTrendChart" width="400" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Modal --}}
    <div class="modal fade" id="bulkActionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Mark Present</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark <span id="selected-count">0</span> students as present?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        This action will mark the selected students as present for {{ $selectedDate->format('F j, Y') }}.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="confirmBulkMarkPresent()">
                        <i class="fas fa-check mr-2"></i>Mark Present
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Include required libraries --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<script>
// Wait for jQuery and DOM to be ready
$(document).ready(function() {
    console.log('Dashboard script loaded');
    
    // Auto refresh setup
    let autoRefreshInterval = null;
    let isAutoRefreshEnabled = false;
    
    // Initialize chart
    initWeeklyTrendChart();
    
    // Select all functionality
    $('#select-all-absent').change(function() {
        $('.absent-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });
    
    $(document).on('change', '.absent-checkbox', function() {
        updateSelectedCount();
        updateSelectAllState();
    });
    
    function updateSelectedCount() {
        const selectedCount = $('.absent-checkbox:checked').length;
        $('#selected-count').text(selectedCount);
    }
    
    function updateSelectAllState() {
        const totalCheckboxes = $('.absent-checkbox').length;
        const checkedCheckboxes = $('.absent-checkbox:checked').length;
        
        $('#select-all-absent').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#select-all-absent').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
    }
    
    // Auto refresh toggle
    window.toggleAutoRefresh = function() {
        if (isAutoRefreshEnabled) {
            clearInterval(autoRefreshInterval);
            isAutoRefreshEnabled = false;
            $('#auto-refresh-text').text('Enable Auto Refresh');
            $('#activity-indicator').removeClass('text-success').addClass('text-primary');
        } else {
            autoRefreshInterval = setInterval(function() {
                refreshAttendanceData();
            }, 30000); // Refresh every 30 seconds
            isAutoRefreshEnabled = true;
            $('#auto-refresh-text').text('Disable Auto Refresh');
            $('#activity-indicator').removeClass('text-primary').addClass('text-success');
        }
    };
    
    // Refresh dashboard
    window.refreshDashboard = function() {
        $('#refresh-icon').addClass('fa-spin');
        refreshAttendanceData();
    };
    
    function refreshAttendanceData() {
        const params = {
            date: $('#date').val(),
            batch_id: $('#batch_id').val(),
            course_id: $('#course_id').val()
        };
        
        console.log('Refreshing data with params:', params);
        
        // Refresh stats
        $.get("{{ route('admin.attendance.dashboard.stats.ajax') }}", params)
            .done(function(response) {
                console.log('Stats response:', response);
                if (response.success) {
                    updateStatsCards(response.data);
                    $('#last-updated').text('Last updated: ' + response.last_updated);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Stats refresh failed:', error);
            });
        
        // Refresh absent students
        $.get("{{ route('admin.attendance.dashboard.absent.ajax') }}", params)
            .done(function(response) {
                console.log('Absent students response:', response);
                if (response.success) {
                    updateAbsentStudents(response.data);
                    $('#absent-count').text(response.count);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Absent students refresh failed:', error);
            });
        
        // Refresh activity
        $.get("{{ route('admin.attendance.dashboard.activity.ajax') }}", params)
            .done(function(response) {
                console.log('Activity response:', response);
                if (response.success) {
                    updateRecentActivity(response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Activity refresh failed:', error);
            })
            .always(function() {
                $('#refresh-icon').removeClass('fa-spin');
            });
    }
    
    function updateStatsCards(stats) {
        // Update the statistics cards
        $('#total-students').text(stats.students.total);
        $('#present-students').text(stats.students.present);
        $('#present-percentage').text(stats.students.percentage + '% of total');
        $('#absent-students').text(stats.students.absent);
        $('#absent-percentage').text((100 - stats.students.percentage) + '% of total');
        $('#late-students').text(stats.students.late);
    }
    
    function updateAbsentStudents(students) {
        if (students.length === 0) {
            $('#absent-students-container').html(`
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-success">All Students Present!</h5>
                    <p class="text-muted">No absent students found for ${$('#date').val()}</p>
                </div>
            `);
            return;
        }
        
        let tbody = '';
        students.forEach(function(student) {
            tbody += `
                <tr id="absent-row-${student.id}">
                    <td><input type="checkbox" class="absent-checkbox" value="${student.id}"></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm mr-3">
                                <div class="avatar-title bg-danger text-white rounded-circle">
                                    ${student.name.charAt(0)}
                                </div>
                            </div>
                            <div>
                                <div class="font-weight-bold">${student.name}</div>
                                <div class="small text-muted">${student.enrollment_number}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>${student.batch_name}</div>
                        <small class="text-muted">${student.course_name}</small>
                    </td>
                    <td>
                        <div class="contact-buttons">
                            ${student.student_mobile ? `<a href="tel:${student.student_mobile}" class="btn btn-sm btn-outline-primary mr-1" title="Student: ${student.student_mobile}"><i class="fas fa-phone"></i></a>` : ''}
                            ${student.father_mobile ? `<a href="tel:${student.father_mobile}" class="btn btn-sm btn-outline-secondary" title="Father: ${student.father_mobile}"><i class="fas fa-phone"></i> F</a>` : ''}
                        </div>
                        <div class="small text-muted mt-1">
                            ${student.student_mobile ? `<div>S: ${student.student_mobile}</div>` : ''}
                            ${student.father_mobile ? `<div>F: ${student.father_mobile}</div>` : ''}
                        </div>
                    </td>
                    <td>
                        ${student.last_attendance ? 
                            `<span class="badge badge-secondary">${moment(student.last_attendance).fromNow()}</span>` : 
                            '<span class="badge badge-warning">Never</span>'
                        }
                    </td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="markStudentPresent(${student.id}, '${student.name}')">
                            <i class="fas fa-check"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        $('#absent-students-tbody').html(tbody);
        
        // Reattach event handlers
        $('.absent-checkbox').off('change').on('change', function() {
            updateSelectedCount();
            updateSelectAllState();
        });
    }
    
    function updateRecentActivity(activities) {
        if (activities.length === 0) {
            $('#activity-feed').html(`
                <div class="text-center py-4" id="no-activity">
                    <i class="fas fa-list fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No attendance activity yet</p>
                </div>
            `);
            return;
        }
        
        let activityHtml = '';
        activities.forEach(function(activity) {
            const statusColor = activity.status === 'present' ? 'success' : (activity.status === 'late' ? 'warning' : 'danger');
            const statusIcon = activity.status === 'present' ? 'check' : (activity.status === 'late' ? 'clock' : 'times');
            
            activityHtml += `
                <div class="activity-item border-bottom p-3" data-activity-id="${activity.id}">
                    <div class="d-flex align-items-center">
                        <div class="activity-avatar mr-3">
                            <div class="avatar-title bg-${statusColor} text-white rounded-circle">
                                <i class="fas fa-${statusIcon}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">${activity.student_name}</div>
                            <div class="small text-muted">${activity.enrollment_number} • ${activity.batch_name}</div>
                            <div class="small">
                                <span class="badge badge-${statusColor}">
                                    ${activity.status.charAt(0).toUpperCase() + activity.status.slice(1)}
                                </span>
                                ${activity.check_in_time ? '• ' + moment(activity.check_in_time, 'HH:mm:ss').format('HH:mm') : ''}
                                ${activity.late_minutes > 0 ? `<span class="text-warning">(${activity.late_minutes}m late)</span>` : ''}
                            </div>
                            <div class="small text-muted">
                                ${moment(activity.marked_at).fromNow()}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#activity-feed').html(activityHtml);
    }
    
    // Mark student present
    window.markStudentPresent = function(studentId, studentName) {
        if (confirm(`Mark ${studentName} as present?`)) {
            $.post("{{ route('admin.attendance.dashboard.mark.present') }}", {
                student_id: studentId,
                date: $('#date').val(),
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    $(`#absent-row-${studentId}`).fadeOut(300, function() {
                        $(this).remove();
                        updateSelectedCount();
                        updateSelectAllState();
                    });
                    
                    // Update absent count
                    const currentCount = parseInt($('#absent-count').text());
                    $('#absent-count').text(Math.max(0, currentCount - 1));
                    
                    // Show success message
                    showNotification('success', response.message);
                    
                    // Refresh activity
                    refreshAttendanceData();
                } else {
                    showNotification('error', 'Failed to mark student as present');
                }
            })
            .fail(function() {
                showNotification('error', 'Failed to mark student as present');
            });
        }
    };
    
    // Select all absent students
    window.selectAllAbsent = function() {
        $('.absent-checkbox').prop('checked', true);
        updateSelectedCount();
        updateSelectAllState();
    };
    
    // Mark selected students present
    window.markSelectedPresent = function() {
        const selectedStudents = $('.absent-checkbox:checked');
        if (selectedStudents.length === 0) {
            showNotification('warning', 'Please select at least one student');
            return;
        }
        
        $('#selected-count').text(selectedStudents.length);
        $('#bulkActionModal').modal('show');
    };
    
    window.confirmBulkMarkPresent = function() {
        const selectedIds = $('.absent-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        $.post("{{ route('admin.attendance.dashboard.bulk.mark.present') }}", {
            student_ids: selectedIds,
            date: $('#date').val(),
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                // Remove marked students from the list
                selectedIds.forEach(function(id) {
                    $(`#absent-row-${id}`).fadeOut(300, function() {
                        $(this).remove();
                    });
                });
                
                // Update absent count
                const currentCount = parseInt($('#absent-count').text());
                $('#absent-count').text(Math.max(0, currentCount - selectedIds.length));
                
                $('#bulkActionModal').modal('hide');
                showNotification('success', response.message);
                
                // Refresh data
                refreshAttendanceData();
            } else {
                showNotification('error', 'Failed to mark students as present');
            }
        })
        .fail(function() {
            showNotification('error', 'Failed to mark students as present');
        });
    };
    
    // Export absent list
    window.exportAbsentList = function() {
        const params = new URLSearchParams({
            date: $('#date').val(),
            batch_id: $('#batch_id').val() || '',
            course_id: $('#course_id').val() || '',
            export: 'csv'
        });
        
        window.open(`{{ route('admin.attendance.dashboard') }}?${params.toString()}`);
    };
    
    // Clear activity
    window.clearActivity = function() {
        if (confirm('Clear all activity from the feed?')) {
            $('#activity-feed').html(`
                <div class="text-center py-4" id="no-activity">
                    <i class="fas fa-list fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Activity cleared</p>
                </div>
            `);
        }
    };
    
    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'warning' ? 'alert-warning' : 'alert-danger';
        
        const notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.alert('close');
        }, 5000);
    }
    
    function initWeeklyTrendChart() {
        const ctx = document.getElementById('weeklyTrendChart');
        if (!ctx) {
            console.error('Chart canvas not found');
            return;
        }
        
        const weeklyData = @json($weeklyTrend);
        console.log('Weekly data:', weeklyData);
        
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not loaded');
            return;
        }
        
        try {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: weeklyData.map(d => d.day),
                    datasets: [{
                        label: 'Attendance %',
                        data: weeklyData.map(d => d.percentage),
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Chart initialization error:', error);
        }
    }
    
    // Test AJAX routes on page load
    setTimeout(function() {
        console.log('Testing AJAX routes...');
        refreshAttendanceData();
    }, 2000);
    
    // Auto-enable refresh after 5 seconds
    setTimeout(function() {
        if (!isAutoRefreshEnabled) {
            console.log('Auto-enabling refresh...');
            toggleAutoRefresh();
        }
    }, 5000);
    
    // Add scroll to top functionality for table
    window.scrollTableToTop = function() {
        $('.table-responsive').animate({scrollTop: 0}, 300);
    };
    
    // Add keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl + R to refresh
        if (e.ctrlKey && e.keyCode === 82) {
            e.preventDefault();
            refreshDashboard();
        }
        
        // Ctrl + A to select all absent students (when focus is on table)
        if (e.ctrlKey && e.keyCode === 65 && $('.table-responsive:focus').length > 0) {
            e.preventDefault();
            selectAllAbsent();
        }
        
        // ESC to clear selections
        if (e.keyCode === 27) {
            $('.absent-checkbox').prop('checked', false);
            $('#select-all-absent').prop('checked', false);
            updateSelectedCount();
        }
    });
    
    // Make table focusable for keyboard shortcuts
    $('.table-responsive').attr('tabindex', '0');
    
    // Add loading state for table updates
    function showTableLoading() {
        $('#absent-students-container').addClass('table-loading');
    }
    
    function hideTableLoading() {
        $('#absent-students-container').removeClass('table-loading');
    }
    
    // Update refresh function to show loading
    const originalRefreshAttendanceData = refreshAttendanceData;
    refreshAttendanceData = function() {
        showTableLoading();
        originalRefreshAttendanceData();
        setTimeout(hideTableLoading, 1000); // Hide loading after 1 second
    };
    
    // Add scroll indicators
    function addScrollIndicators() {
        const tableContainer = $('.table-responsive');
        
        tableContainer.on('scroll', function() {
            const scrollTop = $(this).scrollTop();
            const scrollHeight = this.scrollHeight;
            const height = $(this).height();
            
            // Show scroll to top button if scrolled down
            if (scrollTop > 100) {
                if (!$('.scroll-to-top-table').length) {
                    $(this).append(`
                        <button class="btn btn-sm btn-primary scroll-to-top-table" 
                                onclick="scrollTableToTop()" 
                                style="position: absolute; bottom: 10px; right: 10px; z-index: 20; border-radius: 50%; width: 40px; height: 40px;">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                    `);
                }
            } else {
                $('.scroll-to-top-table').remove();
            }
        });
    }
    
    // Initialize scroll indicators
    setTimeout(addScrollIndicators, 1000);
});
</script>
@endpush