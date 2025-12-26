@extends('layouts.theme')

@section('title', 'Scheduled Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-calendar-alt"></i> Scheduled Reports
                    </h1>
                    <p class="mb-0 text-muted">Manage automated attendance report generation</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('attendance.reports.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Reports
                    </a>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scheduleReportModal">
                        <i class="fas fa-plus"></i> Schedule New Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Active Schedules
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ isset($scheduledReports) ? collect($scheduledReports)->where('status', 'active')->count() : 5 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-play-circle fa-2x text-gray-300"></i>
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
                                Reports This Week
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">24</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Next Report
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">2 hours</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Failed Reports
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">2</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scheduled Reports Table --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Scheduled Reports</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    <div class="dropdown-header">Bulk Actions:</div>
                    <a class="dropdown-item" href="#" id="enableAll">
                        <i class="fas fa-play fa-sm fa-fw mr-2 text-gray-400"></i>
                        Enable All
                    </a>
                    <a class="dropdown-item" href="#" id="disableAll">
                        <i class="fas fa-pause fa-sm fa-fw mr-2 text-gray-400"></i>
                        Disable All
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" id="exportSchedules">
                        <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                        Export Schedules
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(isset($scheduledReports) && count($scheduledReports) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="scheduledReportsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Report Name</th>
                                <th>Type</th>
                                <th>Frequency</th>
                                <th>Recipients</th>
                                <th>Next Run</th>
                                <th>Last Run</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scheduledReports as $report)
                            <tr>
                                <td>
                                    <input type="checkbox" class="report-checkbox" value="{{ $report['id'] ?? '' }}">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-alt text-primary mr-2"></i>
                                        <div>
                                            <div class="font-weight-bold">{{ $report['name'] ?? 'Unnamed Report' }}</div>
                                            <small class="text-muted">{{ $report['description'] ?? 'No description' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $report['type'] === 'student' ? 'primary' : ($report['type'] === 'batch' ? 'success' : 'info') }}">
                                        {{ ucfirst($report['type'] ?? 'summary') }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span class="font-weight-bold">{{ ucfirst($report['frequency'] ?? 'weekly') }}</span>
                                        @if(isset($report['frequency_details']))
                                            <br><small class="text-muted">{{ $report['frequency_details'] }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @if(isset($report['recipients']) && is_array($report['recipients']))
                                            @foreach(array_slice($report['recipients'], 0, 2) as $recipient)
                                                <span class="badge badge-light">{{ $recipient }}</span>
                                            @endforeach
                                            @if(count($report['recipients']) > 2)
                                                <span class="badge badge-secondary">+{{ count($report['recipients']) - 2 }} more</span>
                                            @endif
                                        @else
                                            <span class="text-muted">No recipients</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="font-weight-bold">{{ $report['next_run'] ?? 'Not scheduled' }}</span>
                                        @if(isset($report['time_until_next']))
                                            <br><small class="text-muted">{{ $report['time_until_next'] }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if(isset($report['last_run']))
                                        <div>
                                            <span>{{ $report['last_run'] }}</span>
                                            @if(isset($report['last_status']))
                                                <br><span class="badge badge-{{ $report['last_status'] === 'success' ? 'success' : 'danger' }} badge-sm">
                                                    {{ ucfirst($report['last_status']) }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">Never run</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $status = $report['status'] ?? 'inactive';
                                        $statusClass = $status === 'active' ? 'success' : ($status === 'paused' ? 'warning' : 'secondary');
                                    @endphp
                                    <span class="badge badge-{{ $statusClass }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="editSchedule({{ $report['id'] ?? 0 }})" 
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-{{ $status === 'active' ? 'warning' : 'success' }} btn-sm" 
                                                onclick="toggleSchedule({{ $report['id'] ?? 0 }})" 
                                                title="{{ $status === 'active' ? 'Pause' : 'Activate' }}">
                                            <i class="fas fa-{{ $status === 'active' ? 'pause' : 'play' }}"></i>
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="runNow({{ $report['id'] ?? 0 }})" 
                                                title="Run Now">
                                            <i class="fas fa-play-circle"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteSchedule({{ $report['id'] ?? 0 }})" 
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                    <h4>No Scheduled Reports</h4>
                    <p class="text-muted mb-4">You haven't scheduled any automated reports yet. Set up your first schedule to automate attendance reporting.</p>
                    <button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#scheduleReportModal">
                        <i class="fas fa-plus"></i> Schedule Your First Report
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Executions --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Executions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>Executed</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Recipients</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Weekly Batch Summary</td>
                            <td>2 hours ago</td>
                            <td>1m 34s</td>
                            <td><span class="badge badge-success">Success</span></td>
                            <td>5 recipients</td>
                            <td>
                                <button class="btn btn-outline-primary btn-sm" title="View Report">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" title="View Log">
                                    <i class="fas fa-list"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>Daily Attendance Summary</td>
                            <td>6 hours ago</td>
                            <td>45s</td>
                            <td><span class="badge badge-success">Success</span></td>
                            <td>12 recipients</td>
                            <td>
                                <button class="btn btn-outline-primary btn-sm" title="View Report">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" title="View Log">
                                    <i class="fas fa-list"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>Monthly Faculty Report</td>
                            <td>1 day ago</td>
                            <td>3m 12s</td>
                            <td><span class="badge badge-danger">Failed</span></td>
                            <td>8 recipients</td>
                            <td>
                                <button class="btn btn-outline-warning btn-sm" title="Retry">
                                    <i class="fas fa-redo"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" title="View Log">
                                    <i class="fas fa-list"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Schedule Report Modal --}}
<div class="modal fade" id="scheduleReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus"></i> Schedule New Report
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="scheduleReportForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="scheduleName">Report Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="scheduleName" class="form-control" 
                                       placeholder="e.g., Weekly Attendance Summary" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="scheduleType">Report Type <span class="text-danger">*</span></label>
                                <select name="type" id="scheduleType" class="form-control" required>
                                    <option value="">Select Report Type</option>
                                    <option value="student">Student Report</option>
                                    <option value="batch">Batch Report</option>
                                    <option value="summary">Summary Report</option>
                                    <option value="daily">Daily Summary</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="scheduleDescription">Description</label>
                        <textarea name="description" id="scheduleDescription" class="form-control" rows="2" 
                                  placeholder="Brief description of this scheduled report"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="frequency">Frequency <span class="text-danger">*</span></label>
                                <select name="frequency" id="frequency" class="form-control" required>
                                    <option value="">Select Frequency</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="scheduleTime">Time <span class="text-danger">*</span></label>
                                <input type="time" name="time" id="scheduleTime" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="frequencyOptions" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="weekDay">Day of Week</label>
                                <select name="week_day" id="weekDay" class="form-control">
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                    <option value="0">Sunday</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monthDay">Day of Month</label>
                                <input type="number" name="month_day" id="monthDay" class="form-control" 
                                       min="1" max="31" placeholder="1-31">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="recipients">Recipients <span class="text-danger">*</span></label>
                        <select name="recipients[]" id="recipients" class="form-control" multiple required>
                            <option value="admin@college.edu">Admin Team</option>
                            <option value="faculty@college.edu">Faculty Group</option>
                            <option value="principal@college.edu">Principal</option>
                            <option value="hod@college.edu">HOD</option>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple recipients</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="format">Output Format</label>
                                <select name="format" id="format" class="form-control">
                                    <option value="pdf">PDF Document</option>
                                    <option value="excel">Excel Spreadsheet</option>
                                    <option value="both">Both PDF & Excel</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dateRange">Date Range</label>
                                <select name="date_range" id="dateRange" class="form-control">
                                    <option value="last_week">Last Week</option>
                                    <option value="last_month">Last Month</option>
                                    <option value="current_month">Current Month</option>
                                    <option value="semester">Current Semester</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_charts" id="includeCharts" checked>
                            <label class="form-check-label" for="includeCharts">
                                Include charts and visualizations
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="auto_email" id="autoEmail" checked>
                            <label class="form-check-label" for="autoEmail">
                                Automatically email to recipients
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="store_report" id="storeReport" checked>
                            <label class="form-check-label" for="storeReport">
                                Store report in system for download
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Schedule Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Schedule Modal --}}
<div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Scheduled Report
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editScheduleForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <!-- Same form fields as create modal -->
                    <p class="text-muted">Edit form will be populated here...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Handle frequency changes
    $('#frequency').change(function() {
        const frequency = $(this).val();
        $('#frequencyOptions').hide();
        
        if (frequency === 'weekly') {
            $('#weekDay').closest('.col-md-6').show();
            $('#monthDay').closest('.col-md-6').hide();
            $('#frequencyOptions').show();
        } else if (frequency === 'monthly' || frequency === 'quarterly') {
            $('#weekDay').closest('.col-md-6').hide();
            $('#monthDay').closest('.col-md-6').show();
            $('#frequencyOptions').show();
        }
    });
    
    // Handle form submission
    $('#scheduleReportForm').submit(function(e) {
        e.preventDefault();
        // Handle schedule creation
        alert('Schedule created successfully!');
        $('#scheduleReportModal').modal('hide');
    });
    
    // Select all checkbox
    $('#selectAll').change(function() {
        $('.report-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Individual checkbox changes
    $('.report-checkbox').change(function() {
        const total = $('.report-checkbox').length;
        const checked = $('.report-checkbox:checked').length;
        $('#selectAll').prop('checked', total === checked);
    });
});

function editSchedule(id) {
    // Load schedule data and show edit modal
    $('#editScheduleModal').modal('show');
}

function toggleSchedule(id) {
    if (confirm('Are you sure you want to toggle this schedule?')) {
        // Handle toggle
        location.reload();
    }
}

function runNow(id) {
    if (confirm('Are you sure you want to run this report now?')) {
        // Handle immediate execution
        alert('Report generation started. You will be notified when complete.');
    }
}

function deleteSchedule(id) {
    if (confirm('Are you sure you want to delete this scheduled report? This action cannot be undone.')) {
        // Handle deletion
        location.reload();
    }
}
</script>
@endpush