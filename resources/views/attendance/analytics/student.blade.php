{{-- resources/views/attendance/analytics/student.blade.php --}}
@extends('layouts.theme')

@section('title', 'Student Analytics - ' . $student->name)

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('attendance.analytics.index') }}">Analytics</a>
            </li>
            <li class="breadcrumb-item active">{{ $student->name }}</li>
        </ol>
    </nav>

    {{-- Student Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="student-avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px; font-size: 2rem;">
                                {{ strtoupper(substr($student->name, 0, 2)) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h2 class="h4 mb-1 font-weight-bold">{{ $student->name }}</h2>
                            <p class="text-muted mb-1">
                                <i class="fas fa-id-card mr-1"></i> {{ $student->enrollment_number }}
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-users mr-1"></i> {{ $student->batch->name ?? 'No Batch Assigned' }}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-graduation-cap mr-1"></i> {{ $student->batch->course->name ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="col-md-4">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h3 font-weight-bold text-{{ $attendanceCache && $attendanceCache->attendance_percentage >= 75 ? 'success' : 'danger' }}">
                                        {{ $attendanceCache ? number_format($attendanceCache->attendance_percentage, 1) : 'N/A' }}%
                                    </div>
                                    <div class="small text-muted">Attendance Rate</div>
                                </div>
                                <div class="col-6">
                                    <div class="h3 font-weight-bold text-info">
                                        {{ $attendanceCache ? $attendanceCache->total_classes : 0 }}
                                    </div>
                                    <div class="small text-muted">Total Classes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Performance Summary Cards --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present Days</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $studentReport['attendance_data']['present_classes'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent Days</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $studentReport['attendance_data']['absent_classes'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late Arrivals</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $studentReport['attendance_data']['late_classes'] ?? 0 }}
                            </div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Consecutive Absents</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $studentReport['attendance_data']['consecutive_absents'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row mb-4">
        {{-- Monthly Trends --}}
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance Trends</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="studentTrendsChart" height="320"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance Pattern --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance Pattern</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="studentDistributionChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2"><i class="fas fa-circle text-success"></i> Present</span>
                        <span class="mr-2"><i class="fas fa-circle text-danger"></i> Absent</span>
                        <span class="mr-2"><i class="fas fa-circle text-warning"></i> Late</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Analysis Row --}}
    <div class="row mb-4">
        {{-- Recent Attendance History --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Attendance (Last 30 Days)</h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @if($recentAttendance && count($recentAttendance) > 0)
                        @foreach($recentAttendance as $attendance)
                            <div class="d-flex align-items-center mb-3 p-2 rounded" 
                                 style="background-color: {{ $attendance->status === 'present' ? '#f0f9ff' : ($attendance->status === 'late' ? '#fefce8' : '#fef2f2') }};">
                                <div class="mr-3">
                                    <div class="icon-circle bg-{{ $attendance->status === 'present' ? 'success' : ($attendance->status === 'late' ? 'warning' : 'danger') }}">
                                        <i class="fas fa-{{ $attendance->status === 'present' ? 'check' : ($attendance->status === 'late' ? 'clock' : 'times') }} text-white"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small font-weight-bold">
                                        {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d, Y') }}
                                        <span class="text-muted">({{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }})</span>
                                    </div>
                                    <div class="small text-muted">
                                        Recorded: {{ $attendance->created_at->format('H:i A') }}
                                    </div>
                                </div>
                                <div>
                                    <span class="badge badge-{{ $attendance->status === 'present' ? 'success' : ($attendance->status === 'late' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($attendance->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <p>No attendance records found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recommendations & Actions --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recommendations & Actions</h6>
                </div>
                <div class="card-body">
                    @if(isset($studentReport['recommendations']) && count($studentReport['recommendations']) > 0)
                        @foreach($studentReport['recommendations'] as $recommendation)
                            <div class="alert alert-{{ $recommendation['type'] === 'critical' ? 'danger' : ($recommendation['type'] === 'warning' ? 'warning' : ($recommendation['type'] === 'success' ? 'success' : 'info')) }} mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="mr-3">
                                        <i class="fas fa-{{ $recommendation['type'] === 'critical' ? 'exclamation-circle' : ($recommendation['type'] === 'warning' ? 'exclamation-triangle' : ($recommendation['type'] === 'success' ? 'check-circle' : 'info-circle')) }} fa-lg"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading mb-2">{{ $recommendation['title'] }}</h6>
                                        <p class="mb-2">{{ $recommendation['message'] }}</p>
                                        @if(isset($recommendation['actions']) && count($recommendation['actions']) > 0)
                                            <ul class="mb-0 small">
                                                @foreach($recommendation['actions'] as $action)
                                                    <li>{{ $action }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-thumbs-up fa-3x mb-3 text-success"></i>
                            <p>No specific recommendations at this time. Student performance is satisfactory.</p>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="mt-4">
                        <div class="row">
                            <div class="col-6">
                                <button class="btn btn-primary btn-sm btn-block" data-toggle="modal" data-target="#contactParentModal">
                                    <i class="fas fa-phone"></i> Contact Parent
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-info btn-sm btn-block" data-toggle="modal" data-target="#generateReportModal">
                                    <i class="fas fa-file-alt"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Batch Comparison --}}
    @if(isset($studentReport['batch_comparison']) && !empty($studentReport['batch_comparison']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Batch Comparison</h6>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5>Performance vs Batch Average</h5>
                            <div class="row text-center mt-3">
                                <div class="col-4">
                                    <div class="h4 font-weight-bold text-{{ $studentReport['batch_comparison']['performance_status'] === 'above_average' ? 'success' : 'warning' }}">
                                        {{ number_format($studentReport['batch_comparison']['student_percentage'], 1) }}%
                                    </div>
                                    <div class="small text-muted">Student</div>
                                </div>
                                <div class="col-4">
                                    <div class="h4 font-weight-bold text-info">
                                        {{ number_format($studentReport['batch_comparison']['batch_average'], 1) }}%
                                    </div>
                                    <div class="small text-muted">Batch Average</div>
                                </div>
                                <div class="col-4">
                                    <div class="h4 font-weight-bold text-{{ $studentReport['batch_comparison']['difference'] >= 0 ? 'success' : 'danger' }}">
                                        {{ $studentReport['batch_comparison']['difference'] >= 0 ? '+' : '' }}{{ number_format($studentReport['batch_comparison']['difference'], 1) }}%
                                    </div>
                                    <div class="small text-muted">Difference</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: {{ $studentReport['batch_comparison']['batch_average'] }}%">
                                    Batch Avg
                                </div>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-{{ $studentReport['batch_comparison']['performance_status'] === 'above_average' ? 'success' : 'warning' }}" 
                                     role="progressbar" style="width: {{ $studentReport['batch_comparison']['student_percentage'] }}%">
                                    {{ $student->name }}
                                </div>
                            </div>
                            <div class="mt-2 small text-center">
                                Student is performing 
                                <strong class="text-{{ $studentReport['batch_comparison']['performance_status'] === 'above_average' ? 'success' : 'warning' }}">
                                    {{ $studentReport['batch_comparison']['performance_status'] === 'above_average' ? 'above' : 'below' }}
                                </strong> 
                                batch average
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Monthly Breakdown --}}
    @if(isset($studentReport['attendance_data']['analytics_data']['monthly_breakdown']) && !empty($studentReport['attendance_data']['analytics_data']['monthly_breakdown']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Total Classes</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Attendance %</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentReport['attendance_data']['analytics_data']['monthly_breakdown'] as $month => $data)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</td>
                                        <td>{{ $data['total'] }}</td>
                                        <td>{{ $data['present'] }}</td>
                                        <td>{{ $data['total'] - $data['present'] }}</td>
                                        <td>
                                            <span class="badge badge-{{ $data['percentage'] >= 75 ? 'success' : ($data['percentage'] >= 60 ? 'warning' : 'danger') }}">
                                                {{ number_format($data['percentage'], 1) }}%
                                            </span>
                                        </td>
                                        <td>
                                            @if($data['percentage'] >= 90)
                                                <i class="fas fa-star text-success" title="Excellent"></i>
                                            @elseif($data['percentage'] >= 75)
                                                <i class="fas fa-thumbs-up text-info" title="Good"></i>
                                            @elseif($data['percentage'] >= 60)
                                                <i class="fas fa-exclamation-triangle text-warning" title="Needs Improvement"></i>
                                            @else
                                                <i class="fas fa-times-circle text-danger" title="Poor"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Contact Parent Modal --}}
<div class="modal fade" id="contactParentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contact Parent - {{ $student->name }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="contactParentForm">
                    <div class="form-group">
                        <label>Contact Method</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="contact_method" id="sms" value="sms" checked>
                            <label class="form-check-label" for="sms">SMS</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="contact_method" id="email" value="email">
                            <label class="form-check-label" for="email">Email</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="contact_method" id="whatsapp" value="whatsapp">
                            <label class="form-check-label" for="whatsapp">WhatsApp</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="messageTemplate">Message Template</label>
                        <select class="form-control" id="messageTemplate" name="template">
                            <option value="low_attendance">Low Attendance Alert</option>
                            <option value="absence_concern">Absence Concern</option>
                            <option value="improvement_needed">Improvement Needed</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="messageContent">Message</label>
                        <textarea class="form-control" id="messageContent" name="message" rows="4" 
                                  placeholder="Message will be auto-generated based on template">Dear Parent, We would like to discuss {{ $student->name }}'s attendance...</textarea>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="includeReport" name="include_report">
                        <label class="form-check-label" for="includeReport">
                            Include attendance report attachment
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendContactBtn">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Generate Report Modal --}}
<div class="modal fade" id="generateReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Student Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="generateReportForm">
                    <div class="form-group">
                        <label for="reportFormat">Report Format</label>
                        <select class="form-control" id="reportFormat" name="format" required>
                            <option value="pdf">PDF Document</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="csv">CSV File</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="date" class="form-control" name="date_from" id="reportDateFrom" 
                                       value="{{ now()->subDays(30)->format('Y-m-d') }}">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" name="date_to" id="reportDateTo" 
                                       value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Include Sections</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeBasicStats" name="sections[]" value="basic_stats" checked>
                            <label class="form-check-label" for="includeBasicStats">Basic Statistics</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeTrends" name="sections[]" value="trends" checked>
                            <label class="form-check-label" for="includeTrends">Attendance Trends</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeRecommendations" name="sections[]" value="recommendations" checked>
                            <label class="form-check-label" for="includeRecommendations">Recommendations</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeBatchComparison" name="sections[]" value="batch_comparison" checked>
                            <label class="form-check-label" for="includeBatchComparison">Batch Comparison</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeParentReport" name="sections[]" value="parent_report">
                            <label class="form-check-label" for="includeParentReport">Parent-Friendly Report</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="generateReportBtn">
                    <i class="fas fa-file-download"></i> Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="d-none" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="text-center text-white">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <div class="mt-2">Processing...</div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.student-avatar {
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

.icon-circle {
    height: 2rem;
    width: 2rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chart-area {
    position: relative;
    height: 10rem;
    width: 100%;
}

.chart-pie {
    position: relative;
    height: 15rem;
    width: 100%;
}

.alert {
    border-left: 4px solid;
}

.alert-danger {
    border-left-color: #e74a3b;
}

.alert-warning {
    border-left-color: #f6c23e;
}

.alert-success {
    border-left-color: #1cc88a;
}

.alert-info {
    border-left-color: #36b9cc;
}

.progress {
    border-radius: 10px;
}

.badge {
    font-size: 0.8em;
}

.table th {
    background-color: #f8f9fc;
    border-color: #e3e6f0;
    font-weight: 600;
    font-size: 0.85rem;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let studentTrendsChart, studentDistributionChart;

$(document).ready(function() {
    // Initialize charts
    initializeStudentCharts();
    
    // Setup event handlers
    setupEventHandlers();
    
    // Setup message templates
    setupMessageTemplates();
});

function initializeStudentCharts() {
    // Student Trends Chart
    const trendsCtx = document.getElementById('studentTrendsChart').getContext('2d');
    const monthlyTrends = @json($studentReport['monthly_trends'] ?? []);
    
    studentTrendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: monthlyTrends.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Attendance %',
                data: monthlyTrends.map(item => item.attendance_percentage),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4e73df',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(context) {
                            return 'Attendance: ' + context.parsed.y.toFixed(1) + '%';
                        }
                    }
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
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Student Distribution Chart
    const distributionCtx = document.getElementById('studentDistributionChart').getContext('2d');
    const attendanceData = @json($studentReport['attendance_data'] ?? []);
    
    studentDistributionChart = new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late'],
            datasets: [{
                data: [
                    attendanceData.present_classes || 0,
                    attendanceData.absent_classes || 0,
                    attendanceData.late_classes || 0
                ],
                backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e'],
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

function setupEventHandlers() {
    // Contact parent form submission
    $('#sendContactBtn').on('click', function() {
        sendParentContact();
    });
    
    // Generate report form submission
    $('#generateReportBtn').on('click', function() {
        generateStudentReport();
    });
    
    // Message template change
    $('#messageTemplate').on('change', function() {
        updateMessageContent($(this).val());
    });
}

function setupMessageTemplates() {
    const templates = {
        'low_attendance': `Dear Parent,

We would like to inform you that {{ $student->name }}'s attendance has fallen below our minimum requirement. Current attendance: {{ $attendanceCache ? number_format($attendanceCache->attendance_percentage, 1) : 'N/A' }}%.

We request a meeting to discuss how we can support your child's regular attendance.

Best regards,
{{ config('app.name') }}`,
        
        'absence_concern': `Dear Parent,

We are concerned about {{ $student->name }}'s recent absences. Regular attendance is crucial for academic success.

Please contact us to discuss any challenges your child may be facing.

Best regards,
{{ config('app.name') }}`,
        
        'improvement_needed': `Dear Parent,

We have noticed that {{ $student->name }}'s attendance could be improved. We would like to work with you to ensure better attendance.

Please let us know if there are any issues we can help address.

Best regards,
{{ config('app.name') }}`,
        
        'custom': ''
    };
    
    // Store templates for use
    window.messageTemplates = templates;
}

function updateMessageContent(template) {
    const messageContent = window.messageTemplates[template] || '';
    $('#messageContent').val(messageContent);
    
    // Enable/disable textarea based on template
    if (template === 'custom') {
        $('#messageContent').prop('readonly', false).attr('placeholder', 'Enter your custom message here...');
    } else {
        $('#messageContent').prop('readonly', true).attr('placeholder', '');
    }
}

function sendParentContact() {
    const formData = new FormData(document.getElementById('contactParentForm'));
    formData.append('student_id', {{ $student->id }});
    
    showLoading();
    
    $.ajax({
        url: '{{ route("attendance.reports.student", $student) }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            hideLoading();
            $('#contactParentModal').modal('hide');
            showToast('Parent contact sent successfully!', 'success');
        },
        error: function(xhr) {
            hideLoading();
            let errorMessage = 'Failed to send parent contact';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showToast(errorMessage, 'error');
        }
    });
}

function generateStudentReport() {
    const formData = new FormData(document.getElementById('generateReportForm'));
    formData.append('type', 'student');
    formData.append('student_id', {{ $student->id }});
    
    showLoading();
    $('#generateReportModal').modal('hide');
    
    $.ajax({
        url: '{{ route("attendance.reports.generate") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            hideLoading();
            if (response.success && response.download_url) {
                // Create download link
                const a = document.createElement('a');
                a.href = response.download_url;
                a.download = response.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                showToast('Report generated successfully!', 'success');
            } else {
                showToast('Failed to generate report', 'error');
            }
        },
        error: function(xhr) {
            hideLoading();
            let errorMessage = 'Failed to generate report';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showToast(errorMessage, 'error');
        }
    });
}

function showLoading() {
    $('#loadingOverlay').removeClass('d-none');
}

function hideLoading() {
    $('#loadingOverlay').addClass('d-none');
}

function showToast(message, type = 'info') {
    const toastHtml = `
        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000" 
             style="position: fixed; top: 20px; right: 20px; z-index: 10000; min-width: 300px;">
            <div class="toast-header bg-${type === 'success' ? 'success' : (type === 'error' ? 'danger' : 'info')} text-white">
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle')} mr-2"></i>
                <strong class="mr-auto">${type === 'success' ? 'Success' : (type === 'error' ? 'Error' : 'Info')}</strong>
                <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    $('body').append(toastHtml);
    $('.toast').last().toast('show');
    
    $('.toast').last().on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Keyboard shortcuts
$(document).on('keydown', function(e) {
    // Ctrl+P for parent contact
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        $('#contactParentModal').modal('show');
    }
    
    // Ctrl+R for generate report  
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        $('#generateReportModal').modal('show');
    }
});

// Chart click handlers for detailed views
if (studentTrendsChart) {
    studentTrendsChart.options.onClick = function(event, elements) {
        if (elements.length > 0) {
            const index = elements[0].index;
            const monthData = @json($studentReport['monthly_trends'] ?? []);
            if (monthData[index]) {
                console.log('Clicked on month:', monthData[index].month);
                // Could navigate to detailed monthly view
            }
        }
    };
}

// Auto-refresh data every 5 minutes
setInterval(function() {
    // Refresh real-time components if needed
    location.reload();
}, 300000);
</script>
@endpush