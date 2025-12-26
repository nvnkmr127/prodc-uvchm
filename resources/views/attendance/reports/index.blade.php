@extends('layouts.theme')

@section('title', 'Attendance Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-file-alt"></i> Attendance Reports
                </h1>
                <div class="btn-group">
                    <a href="{{ route('attendance.reports.schedule') }}" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-alt"></i> Scheduled Reports
                    </a>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#generateReportModal">
                        <i class="fas fa-plus"></i> Generate New Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Today's Reports
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">12</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-pdf fa-2x text-gray-300"></i>
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
                                Scheduled Reports
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">8</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Downloads This Month
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">156</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-download fa-2x text-gray-300"></i>
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
                                Pending Reports
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">3</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Report Types --}}
    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card h-100 shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i> Student Reports
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Generate detailed attendance reports for individual students including trends, statistics, and improvement suggestions.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> Individual attendance tracking</li>
                        <li><i class="fas fa-check text-success"></i> Performance trends</li>
                        <li><i class="fas fa-check text-success"></i> Parent notifications</li>
                        <li><i class="fas fa-check text-success"></i> Custom date ranges</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#studentReportModal">
                        <i class="fas fa-plus"></i> Generate Student Report
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100 shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Batch Reports
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Generate comprehensive reports for entire batches showing class-wise attendance patterns and comparisons.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> Batch-wise statistics</li>
                        <li><i class="fas fa-check text-success"></i> Subject-wise breakdown</li>
                        <li><i class="fas fa-check text-success"></i> Faculty insights</li>
                        <li><i class="fas fa-check text-success"></i> Comparative analysis</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <button class="btn btn-success btn-block" data-toggle="modal" data-target="#batchReportModal">
                        <i class="fas fa-plus"></i> Generate Batch Report
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100 shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Summary Reports
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Generate executive summary reports with key metrics, trends, and institutional attendance overview.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> Institution-wide metrics</li>
                        <li><i class="fas fa-check text-success"></i> Department comparisons</li>
                        <li><i class="fas fa-check text-success"></i> Trend analysis</li>
                        <li><i class="fas fa-check text-success"></i> Executive dashboards</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info btn-block" data-toggle="modal" data-target="#summaryReportModal">
                        <i class="fas fa-plus"></i> Generate Summary Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Reports --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Recent Reports</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    <div class="dropdown-header">Actions:</div>
                    <a class="dropdown-item" href="#" id="refreshReports">
                        <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                        Refresh
                    </a>
                    <a class="dropdown-item" href="{{ route('attendance.reports.schedule') }}">
                        <i class="fas fa-calendar fa-sm fa-fw mr-2 text-gray-400"></i>
                        View Scheduled
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reportsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Report Name</th>
                            <th>Type</th>
                            <th>Date Range</th>
                            <th>Generated By</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Monthly Attendance Summary - July 2025</td>
                            <td><span class="badge badge-info">Summary</span></td>
                            <td>Jul 01 - Jul 31, 2025</td>
                            <td>Admin User</td>
                            <td>2 hours ago</td>
                            <td><span class="badge badge-success">Ready</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-outline-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>CS Batch A - Weekly Report</td>
                            <td><span class="badge badge-success">Batch</span></td>
                            <td>Jul 28 - Aug 03, 2025</td>
                            <td>Faculty User</td>
                            <td>5 hours ago</td>
                            <td><span class="badge badge-warning">Processing</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary" disabled title="Processing">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>John Doe - Semester Report</td>
                            <td><span class="badge badge-primary">Student</span></td>
                            <td>Jan 01 - Jul 31, 2025</td>
                            <td>Admin User</td>
                            <td>1 day ago</td>
                            <td><span class="badge badge-success">Ready</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-outline-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Generate Report Modal --}}
<div class="modal fade" id="generateReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt"></i> Generate Attendance Report
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('attendance.reports.generate') }}" method="POST" id="generateReportForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reportType">Report Type <span class="text-danger">*</span></label>
                                <select name="type" id="reportType" class="form-control" required>
                                    <option value="">Select Report Type</option>
                                    <option value="student">Student Report</option>
                                    <option value="batch">Batch Report</option>
                                    <option value="summary">Summary Report</option>
                                    <option value="daily">Daily Report</option>
                                    <option value="custom">Custom Report</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dateRange">Date Range <span class="text-danger">*</span></label>
                                <select name="date_range" id="dateRange" class="form-control" required>
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="week">This Week</option>
                                    <option value="last_week">Last Week</option>
                                    <option value="month">This Month</option>
                                    <option value="last_month">Last Month</option>
                                    <option value="semester">This Semester</option>
                                    <option value="academic_year">Academic Year</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="customDateRange" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="startDate">Start Date</label>
                                <input type="date" name="start_date" id="startDate" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endDate">End Date</label>
                                <input type="date" name="end_date" id="endDate" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="format">Output Format</label>
                                <select name="format" id="format" class="form-control">
                                    <option value="pdf">PDF Document</option>
                                    <option value="excel">Excel Spreadsheet</option>
                                    <option value="csv">CSV File</option>
                                    <option value="html">HTML Preview</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="batch" id="batchLabel" style="display: none;">Select Batch</label>
                                <select name="batch_id" id="batch" class="form-control" style="display: none;">
                                    <option value="">Select Batch</option>
                                    @if(isset($batches))
                                        @foreach($batches as $batch)
                                            <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                
                                <label for="student" id="studentLabel" style="display: none;">Select Student</label>
                                <select name="student_id" id="student" class="form-control" style="display: none;">
                                    <option value="">Select Student</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reportName">Report Name</label>
                        <input type="text" name="report_name" id="reportName" class="form-control" 
                               placeholder="Leave blank for auto-generated name">
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_charts" id="includeCharts" checked>
                            <label class="form-check-label" for="includeCharts">
                                Include Charts and Graphs
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="email_notification" id="emailNotification">
                            <label class="form-check-label" for="emailNotification">
                                Send email notification when ready
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-cog fa-spin" id="loadingIcon" style="display: none;"></i>
                        <i class="fas fa-file-alt" id="generateIcon"></i>
                        Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Student Report Modal --}}
<div class="modal fade" id="studentReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Student Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('attendance.reports.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="student">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Student</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Choose a student...</option>
                            {{-- Students will be loaded via AJAX --}}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <select name="date_range" class="form-control">
                            <option value="month">This Month</option>
                            <option value="semester">This Semester</option>
                            <option value="academic_year">Academic Year</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Batch Report Modal --}}
<div class="modal fade" id="batchReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Batch Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('attendance.reports.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="batch">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Batch</label>
                        <select name="batch_id" class="form-control" required>
                            <option value="">Choose a batch...</option>
                            @if(isset($batches))
                                @foreach($batches as $batch)
                                    <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <select name="date_range" class="form-control">
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="semester">This Semester</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Summary Report Modal --}}
<div class="modal fade" id="summaryReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Summary Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('attendance.reports.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="summary">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Report Scope</label>
                        <select name="scope" class="form-control" required>
                            <option value="institution">Entire Institution</option>
                            <option value="department">By Department</option>
                            <option value="course">By Course</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <select name="date_range" class="form-control">
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="semester">This Semester</option>
                            <option value="academic_year">Academic Year</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Generate Report</button>
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
    // Handle report type changes
    $('#reportType').change(function() {
        const type = $(this).val();
        $('#batch, #student, #batchLabel, #studentLabel').hide();
        
        if (type === 'batch') {
            $('#batch, #batchLabel').show();
        } else if (type === 'student') {
            $('#student, #studentLabel').show();
            loadStudents();
        }
    });
    
    // Handle date range changes
    $('#dateRange').change(function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
        }
    });
    
    // Handle form submission
    $('#generateReportForm').submit(function() {
        $('#loadingIcon').show();
        $('#generateIcon').hide();
        $(this).find('button[type="submit"]').prop('disabled', true);
    });
    
    // Load students for batch
    $('#batch').change(function() {
        const batchId = $(this).val();
        if (batchId) {
            loadStudentsByBatch(batchId);
        }
    });
    
    // Refresh reports
    $('#refreshReports').click(function(e) {
        e.preventDefault();
        location.reload();
    });
});

function loadStudents() {
    $.ajax({
        url: '/api/students',
        method: 'GET',
        success: function(data) {
            let options = '<option value="">Select Student</option>';
            data.forEach(function(student) {
                options += `<option value="${student.id}">${student.name} (${student.student_id})</option>`;
            });
            $('#student').html(options);
        }
    });
}

function loadStudentsByBatch(batchId) {
    $.ajax({
        url: `/api/students/${batchId}`,
        method: 'GET',
        success: function(data) {
            let options = '<option value="">Select Student</option>';
            data.forEach(function(student) {
                options += `<option value="${student.id}">${student.name} (${student.student_id})</option>`;
            });
            $('#student').html(options);
        }
    });
}
</script>
@endpush