@extends('layouts.theme')

@section('title', 'Attendance Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-cogs mr-2"></i>
            Attendance Settings
        </h1>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" onclick="loadBiometricStats()">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
            <a href="{{ route('admin.attendance.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-chart-line mr-1"></i> Dashboard
            </a>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-success dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-download mr-1"></i> Export
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="exportTodayData('xlsx')">
                        <i class="fas fa-file-excel mr-2"></i>Today's Data (Excel)
                    </a>
                    <a class="dropdown-item" href="#" onclick="exportTodayData('csv')">
                        <i class="fas fa-file-csv mr-2"></i>Today's Data (CSV)
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="exportResults()">
                        <i class="fas fa-cog mr-2"></i>Custom Export
                    </a>
                    <a class="dropdown-item" href="#" onclick="exportSyncLogs()">
                        <i class="fas fa-history mr-2"></i>Sync Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <!-- ETimeOffice Integration Settings -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock mr-2"></i>
                        ETimeOffice Integration
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="etimeofficeDropdown" data-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                            <a class="dropdown-item" href="#" id="testConnection">
                                <i class="fas fa-plug fa-sm fa-fw mr-2 text-gray-400"></i>
                                Test Connection
                            </a>
                            <a class="dropdown-item" href="#" id="syncNow">
                                <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                                Sync Now
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="validateAndShowProgress()">
                                <i class="fas fa-tasks fa-sm fa-fw mr-2 text-gray-400"></i>
                                Validation Wizard
                            </a>
                            <a class="dropdown-item" href="#" onclick="loadSetupRecommendations()">
                                <i class="fas fa-lightbulb fa-sm fa-fw mr-2 text-gray-400"></i>
                                Setup Tips
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Integration Status -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-xs font-weight-bold text-uppercase tracking-wide">Integration Status</span>
                            <span class="badge badge-secondary" id="integrationStatus">Loading...</span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-success" id="integrationProgress" style="width: 0%"></div>
                        </div>
                        <small class="text-muted" id="integrationDetails">Checking ETimeOffice connection...</small>
                    </div>

                    <!-- ETimeOffice Configuration Form -->
                    <form id="etimeofficeForm">
                        @csrf
                        
                        <!-- Enable Integration -->
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="etimeoffice_enabled" name="etimeoffice_enabled">
                                <label class="custom-control-label" for="etimeoffice_enabled">
                                    <strong>Enable ETimeOffice Integration</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Automatically sync attendance data from ETimeOffice biometric devices
                            </small>
                        </div>

                        <!-- API Configuration (shown when enabled) -->
                        <div id="etimeofficeConfig" style="display: none;">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="alert alert-info">
                                        <i class="fas fa-lightbulb mr-2"></i>
                                        <strong>Note:</strong> Only ETimeOffice biometric devices are supported. All other biometric methods have been removed for security and simplicity.
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="etimeoffice_api_url" class="form-label">
                                            <i class="fas fa-link mr-1"></i> API URL
                                        </label>
                                        <input type="url" class="form-control form-control-sm" id="etimeoffice_api_url" 
                                               name="etimeoffice_api_url" placeholder="https://api.etimeoffice.com/api">
                                        <small class="form-text text-muted">ETimeOffice API base URL</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="etimeoffice_corporate_id" class="form-label">
                                            <i class="fas fa-building mr-1"></i> Corporate ID
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="etimeoffice_corporate_id" 
                                               name="etimeoffice_corporate_id" placeholder="Your corporate ID">
                                        <small class="form-text text-muted">Provided by ETimeOffice</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="etimeoffice_username" class="form-label">
                                            <i class="fas fa-user mr-1"></i> API Username
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="etimeoffice_username" 
                                               name="etimeoffice_username" placeholder="API username">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="etimeoffice_password" class="form-label">
                                            <i class="fas fa-lock mr-1"></i> API Password
                                        </label>
                                        <input type="password" class="form-control form-control-sm" id="etimeoffice_password" 
                                               name="etimeoffice_password" placeholder="API password">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="etimeoffice_sync_frequency" class="form-label">
                                            <i class="fas fa-clock mr-1"></i> Sync Frequency
                                        </label>
                                        <select class="form-control form-control-sm" id="etimeoffice_sync_frequency" name="etimeoffice_sync_frequency">
                                            <option value="5">Every 5 minutes</option>
                                            <option value="15" selected>Every 15 minutes</option>
                                            <option value="30">Every 30 minutes</option>
                                            <option value="60">Every hour</option>
                                            <option value="120">Every 2 hours</option>
                                            <option value="360">Every 6 hours</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch mt-4">
                                            <input type="checkbox" class="custom-control-input" id="biometric_auto_generate_codes" 
                                                   name="biometric_auto_generate_codes" checked>
                                            <label class="custom-control-label" for="biometric_auto_generate_codes">
                                                Auto-generate biometric codes
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">Automatically create biometric codes from enrollment numbers</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                Save Settings
                            </button>
                            <button type="button" class="btn btn-outline-secondary ml-2" onclick="resetForm()">
                                <i class="fas fa-undo mr-2"></i>
                                Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ETimeOffice Data Puller Section -->
            <div class="card shadow mb-4" id="dataPullerSection">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-download mr-2"></i>ETimeOffice Data Puller
                    </h6>
                    <div class="d-flex align-items-center">
                        <span id="syncStatus" class="badge badge-secondary mr-2">Checking...</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshSyncStatus()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Quick Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-muted mb-3">Quick Actions</h6>
                            <div class="btn-group-toggle" data-toggle="buttons">
                                <button type="button" class="btn btn-primary mr-2 mb-2" onclick="quickPull('today')">
                                    <i class="fas fa-calendar-day mr-2"></i>Pull Today's Data
                                </button>
                                <button type="button" class="btn btn-secondary mr-2 mb-2" onclick="quickPull('yesterday')">
                                    <i class="fas fa-calendar-minus mr-2"></i>Pull Yesterday's Data
                                </button>
                                <button type="button" class="btn btn-info mr-2 mb-2" onclick="quickPull('last_7_days')">
                                    <i class="fas fa-calendar-week mr-2"></i>Pull Last 7 Days
                                </button>
                                <button type="button" class="btn btn-warning mr-2 mb-2" onclick="quickPull('last_30_days')">
                                    <i class="fas fa-calendar-alt mr-2"></i>Pull Last 30 Days
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Data Puller Form -->
                    <form id="dataPullerForm">
                        @csrf
                        <div class="row">
                            <!-- Date Range Selection -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dateRange" class="font-weight-bold">Date Range</label>
                                    <select id="dateRange" name="date_range" class="form-control" onchange="toggleCustomDateRange()">
                                        <option value="today">Today</option>
                                        <option value="yesterday">Yesterday</option>
                                        <option value="last_3_days">Last 3 Days</option>
                                        <option value="last_7_days">Last 7 Days</option>
                                        <option value="last_30_days">Last 30 Days</option>
                                        <option value="this_week">This Week</option>
                                        <option value="last_week">Last Week</option>
                                        <option value="this_month">This Month</option>
                                        <option value="last_month">Last Month</option>
                                        <option value="custom">Custom Date Range</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Test Mode Toggle -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Options</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="testMode" name="test_mode">
                                        <label class="custom-control-label" for="testMode">
                                            Test Mode (Preview Only)
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Enable to preview what data would be pulled without actually creating records
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Date Range (Hidden by default) -->
                        <div class="row" id="customDateRange" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="startDate" class="font-weight-bold">Start Date</label>
                                    <input type="date" id="startDate" name="start_date" class="form-control" max="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="endDate" class="font-weight-bold">End Date</label>
                                    <input type="date" id="endDate" name="end_date" class="form-control" max="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Employee Code Selection -->
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="employeeCodes" class="font-weight-bold">Employee Codes (Optional)</label>
                                    <input type="text" id="employeeCodes" name="employee_codes" class="form-control" 
                                           placeholder="Leave empty for all employees, or enter specific codes (comma-separated)">
                                    <small class="form-text text-muted">
                                        Example: EMP001,EMP002 or leave empty to pull data for all employees
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Pull Data Button -->
                        <div class="row">
                            <div class="col-12">
                                <button type="button" class="btn btn-success btn-lg" onclick="pullAttendanceData()">
                                    <i class="fas fa-download mr-2"></i>Pull Attendance Data
                                </button>
                                <button type="button" class="btn btn-outline-secondary ml-2" onclick="resetDataPullerForm()">
                                    <i class="fas fa-undo mr-2"></i>Reset
                                </button>
                                <button type="button" class="btn btn-outline-info ml-2" onclick="exportResults()" id="exportResultsBtn" style="display: none;">
                                    <i class="fas fa-file-export mr-2"></i>Export Results
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Progress Bar -->
                    <div class="mt-4" id="progressSection" style="display: none;">
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                                 style="width: 0%" id="progressBar"></div>
                        </div>
                        <div id="progressText" class="text-center text-muted">Initializing...</div>
                    </div>

                    <!-- Results Section -->
                    <div class="mt-4" id="resultsSection" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold mb-0">Pull Results</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportLastResults()">
                                <i class="fas fa-download mr-1"></i>Export
                            </button>
                        </div>
                        <div id="resultsContent"></div>
                    </div>
                </div>
            </div>

            <!-- Sync History Section -->
            <div class="card shadow mb-4" id="syncHistorySection">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-2"></i>Recent Sync History
                    </h6>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSyncHistory()">
                            <i class="fas fa-sync-alt mr-1"></i>Refresh
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="exportSyncLogs()">
                            <i class="fas fa-download mr-1"></i>Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="syncHistoryTable">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Range</th>
                                    <th>Records</th>
                                    <th>Status</th>
                                    <th>Duration</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading sync history...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Webhook Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-webhook mr-2"></i>
                        Webhook Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-info-circle mr-2"></i>ETimeOffice Webhook URL</h6>
                        <p class="mb-2">Configure your ETimeOffice device to send data to:</p>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ url('/api/etimeoffice/webhook') }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyWebhookUrl()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>Alternative Endpoints:</h6>
                            <ul class="list-unstyled">
                                <li><code>/api/etimeoffice/attendance</code></li>
                                <li><code>/api/etimeoffice/punch-data</code></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Supported Methods:</h6>
                            <span class="badge badge-success">POST</span>
                            <span class="badge badge-info">JSON</span>
                            <span class="badge badge-warning">Form Data</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics and Status -->
        <div class="col-xl-4 col-lg-5">
           <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Student Attendance Timing</h5>
                    <span id="timingSaveStatus" class="badge badge-light transition-all"></span>
                </div>
                <div class="card-body">
                    <form id="attendanceTimingForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">College Start Time</label>
                                <input type="time" name="student_college_start_time" 
                                       class="form-control" 
                                       value="{{ $settings['student_college_start_time'] ?? '09:30' }}"
                                       onchange="saveAttendanceTiming()">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold text-primary">Present Cutoff Time</label>
                                <input type="time" name="student_present_cutoff_time" 
                                       class="form-control border-primary" 
                                       value="{{ $settings['student_present_cutoff_time'] ?? '11:00' }}"
                                       onchange="saveAttendanceTiming()">
                                <small class="text-muted">Triggers absent webhook.</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Late Entry Cutoff</label>
                                <input type="time" name="student_late_cutoff_time" 
                                       class="form-control" 
                                       value="{{ $settings['student_late_cutoff_time'] ?? '11:30' }}"
                                       onchange="saveAttendanceTiming()">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Biometric Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-chart-pie mr-2"></i>
                        Biometric Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div id="biometricStats" class="text-center">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading statistics...</p>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-server mr-2"></i>
                        System Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-sm">ETimeOffice API</span>
                        <span class="badge badge-secondary" id="apiStatus">Checking...</span>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-sm">Last Sync</span>
                        <span class="text-sm text-muted" id="lastSync">Never</span>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-sm">Active Devices</span>
                        <span class="badge badge-primary" id="activeDevices">0</span>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-sm">Today's Records</span>
                        <span class="badge badge-success" id="todayRecords">0</span>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-sm">Sync Health</span>
                        <span class="badge badge-info" id="syncHealth">Good</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-clock mr-2"></i>
                        Recent Activity
                    </h6>
                </div>
                <div class="card-body">
                    <div id="recentActivity" class="text-center">
                        <div class="spinner-border spinner-border-sm text-success" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading recent activity...</p>
                    </div>
                </div>
            </div>

            <!-- Migration Notice -->
            <div class="card border-left-warning shadow mb-4">
                <div class="card-body">
                    <div class="text-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Migration Complete</strong>
                    </div>
                    <p class="small mb-0 mt-2">
                        All biometric methods except ETimeOffice have been removed. 
                        Your system now exclusively uses ETimeOffice for attendance tracking.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Results Modal -->
    <div class="modal fade" id="testResultModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plug mr-2"></i>
                        Connection Test Results
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="testResultContent">
                    <!-- Test results will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-primary" onclick="copyTestResults()">
                        <i class="fas fa-copy mr-1"></i>Copy Details
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Global variables
let pullInProgress = false;
let pullAbortController = null;
let lastResultsData = null;

// Initialize everything when page loads
$(document).ready(function() {
    // Load initial data
    loadETimeOfficeSettings();
    loadBiometricStats();
    refreshSyncStatus();
    loadSyncHistory();
    
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    $('#endDate').val(today);
    $('#startDate').val(getPreviousDate(today, 7));

    // Auto-refresh stats every 2 minutes
    setInterval(loadBiometricStats, 120000);
    setInterval(refreshSyncStatus, 60000);

    // Event handlers
    $('#etimeoffice_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#etimeofficeConfig').slideDown();
        } else {
            $('#etimeofficeConfig').slideUp();
        }
    });

    $('#etimeofficeForm').submit(function(e) {
        e.preventDefault();
        saveETimeOfficeSettings();
    });

    $('#testConnection').click(function(e) {
        e.preventDefault();
        testETimeOfficeConnection();
    });

    $('#syncNow').click(function(e) {
        e.preventDefault();
        triggerManualSync();
    });

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            if (!pullInProgress) {
                pullAttendanceData();
            }
        }
    });
});

// Helper functions
function getPreviousDate(dateString, daysBack) {
    const date = new Date(dateString);
    date.setDate(date.getDate() - daysBack);
    return date.toISOString().split('T')[0];
}

function formatDateTime(dateString) {
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

// Load ETimeOffice settings
function loadETimeOfficeSettings() {
    $.ajax({
        url: '{{ route("admin.attendance.settings.etimeoffice.get") }}',
        method: 'GET',
        timeout: 10000, // 10 second timeout
        success: function(response) {
            if (response && response.success) {
                populateETimeOfficeForm(response.data || {});
                updateIntegrationStatus('success', 'Connected', response.data?.etimeoffice_enabled ? 100 : 0);
            } else {
                updateIntegrationStatus('danger', 'Configuration Error', 0);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load ETimeOffice settings:', error);
            updateIntegrationStatus('danger', 'Connection Failed', 0);
            
            if (xhr.status === 403) {
                showAlert('Access denied. Please check your permissions.', 'error');
            } else if (xhr.status === 500) {
                showAlert('Server error. Please try again later.', 'error');
            }
        }
    });
}

// Populate form with settings
function populateETimeOfficeForm(data) {
    // Safe checkbox handling
    const enabledCheckbox = $('#etimeoffice_enabled');
    if (enabledCheckbox.length) {
        enabledCheckbox.prop('checked', data.etimeoffice_enabled || false);
    }
    
    // Safe input field handling
    const fields = {
        '#etimeoffice_api_url': data.etimeoffice_api_url || 'https://api.etimeoffice.com/api',
        '#etimeoffice_corporate_id': data.etimeoffice_corporate_id || '',
        '#etimeoffice_username': data.etimeoffice_username || '',
        '#etimeoffice_password': data.etimeoffice_password || '',
        '#etimeoffice_sync_frequency': data.etimeoffice_sync_frequency || 15
    };
    
    Object.entries(fields).forEach(([selector, value]) => {
        const element = $(selector);
        if (element.length) {
            element.val(value);
        }
    });
    
    // Safe checkbox handling for biometric codes
    const biometricCheckbox = $('#biometric_auto_generate_codes');
    if (biometricCheckbox.length) {
        biometricCheckbox.prop('checked', data.biometric_auto_generate_codes || false);
    }
    
    // Show/hide config section
    if (data.etimeoffice_enabled && enabledCheckbox.length) {
        $('#etimeofficeConfig').show();
    }
}

// Save settings
function saveETimeOfficeSettings() {
    const formData = new FormData($('#etimeofficeForm')[0]);
    
    $.ajax({
        url: '{{ route("admin.attendance.settings.etimeoffice.update") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadBiometricStats();
            } else {
                showAlert(response.message || 'Failed to save settings', 'error');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Failed to save settings';
            showAlert(message, 'error');
        }
    });
}

// Test connection with enhanced results
function testETimeOfficeConnection() {
    const button = $('#testConnection');
    const originalText = button.html();
    
    button.html('<i class="fas fa-spinner fa-spin mr-2"></i>Testing...');
    button.prop('disabled', true);
    
    $.ajax({
        url: '{{ route("admin.attendance.settings.etimeoffice.test") }}',
        method: 'POST',
        data: $('#etimeofficeForm').serialize(),
        success: function(response) {
            let resultClass = response.success ? 'text-success' : 'text-danger';
            let resultIcon = response.success ? 'fas fa-check-circle' : 'fas fa-times-circle';
            
            let contentHtml = `
                <div class="${resultClass}">
                    <i class="${resultIcon} mr-2"></i>
                    <strong>${response.success ? 'Connection Successful' : 'Connection Failed'}</strong>
                </div>
                <p class="mt-3">${response.message}</p>
            `;
            
            if (response.data) {
                contentHtml += `
                    <div class="mt-3">
                        <h6>Connection Details:</h6>
                        <ul class="list-unstyled">
                            <li><strong>API URL:</strong> ${response.data.api_url || 'Not available'}</li>
                            <li><strong>Corporate ID:</strong> ${response.data.corporate_id || 'Not available'}</li>
                            <li><strong>Test Time:</strong> ${response.data.test_timestamp || 'Not available'}</li>
                            <li><strong>Records Found:</strong> ${response.data.punch_records_found || 0}</li>
                        </ul>
                    </div>
                `;
            }
            
            $('#testResultContent').html(contentHtml);
            $('#testResultModal').modal('show');
        },
        error: function(xhr) {
            $('#testResultContent').html(`
                <div class="text-danger">
                    <i class="fas fa-times-circle mr-2"></i>
                    <strong>Connection Failed</strong>
                </div>
                <p class="mt-3">${xhr.responseJSON?.message || 'Unable to test connection'}</p>
                ${xhr.status ? `<small class="text-muted">HTTP Status: ${xhr.status}</small>` : ''}
            `);
            $('#testResultModal').modal('show');
        },
        complete: function() {
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
}

// Manual sync
function triggerManualSync() {
    const button = $('#syncNow');
    const originalText = button.html();
    
    button.html('<i class="fas fa-spinner fa-spin mr-2"></i>Syncing...');
    button.prop('disabled', true);
    
    $.ajax({
        url: '{{ route("admin.attendance.settings.etimeoffice.sync") }}',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                showAlert('Sync completed successfully!', 'success');
                loadBiometricStats();
                refreshSyncStatus();
                loadSyncHistory();
            } else {
                showAlert(response.message || 'Sync failed', 'error');
            }
        },
        error: function(xhr) {
            showAlert(xhr.responseJSON?.message || 'Sync failed', 'error');
        },
        complete: function() {
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
}

// Load biometric statistics
function loadBiometricStats() {
    $.ajax({
        url: '{{ route("admin.attendance.settings.etimeoffice.biometric.stats") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateBiometricStats(response.data);
            }
        },
        error: function() {
            $('#biometricStats').html(`
                <div class="text-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Failed to load statistics
                </div>
            `);
        }
    });
}

// Update biometric statistics display
function updateBiometricStats(data) {
    $('#biometricStats').html(`
        <div class="row">
            <div class="col-6">
                <div class="text-center">
                    <div class="h4 font-weight-bold text-primary">${data.total_devices || 0}</div>
                    <div class="text-xs text-muted">Total Devices</div>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center">
                    <div class="h4 font-weight-bold text-success">${data.today_punches || 0}</div>
                    <div class="text-xs text-muted">Today's Records</div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-6">
                <div class="text-center">
                    <div class="h4 font-weight-bold text-info">${data.active_devices || 0}</div>
                    <div class="text-xs text-muted">Active Devices</div>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center">
                    <div class="h4 font-weight-bold text-warning">${data.unique_users_today || 0}</div>
                    <div class="text-xs text-muted">Unique Users</div>
                </div>
            </div>
        </div>
    `);
    
    // Update status indicators
    $('#apiStatus').removeClass().addClass('badge').addClass(data.sync_status === 'enabled' ? 'badge-success' : 'badge-secondary')
        .text(data.sync_status === 'enabled' ? 'Active' : 'Inactive');
    $('#lastSync').text(data.last_sync || 'Never');
    $('#activeDevices').text(data.active_devices || 0);
    $('#todayRecords').text(data.today_punches || 0);
    $('#syncHealth').removeClass().addClass('badge').addClass(
        data.sync_health === 'good' ? 'badge-success' : 
        data.sync_health === 'fair' ? 'badge-warning' : 'badge-danger'
    ).text(data.sync_health || 'Unknown');
}

// Update integration status
function updateIntegrationStatus(type, status, progress) {
    $('#integrationStatus').removeClass().addClass(`badge badge-${type}`).text(status);
    $('#integrationProgress').css('width', progress + '%');
    $('#integrationDetails').text(status === 'Connected' ? 'ETimeOffice integration is active' : 'Check configuration settings');
}

// Data Puller Functions
function toggleCustomDateRange() {
    const dateRange = $('#dateRange').val();
    const customContainer = $('#customDateRange');
    
    if (dateRange === 'custom') {
        customContainer.slideDown(300);
        $('#startDate, #endDate').prop('required', true);
        
        // Set smart defaults if empty
        if (!$('#endDate').val()) {
            $('#endDate').val(new Date().toISOString().split('T')[0]);
        }
        if (!$('#startDate').val()) {
            $('#startDate').val(getPreviousDate($('#endDate').val(), 7));
        }
    } else {
        customContainer.slideUp(300);
        $('#startDate, #endDate').prop('required', false);
    }
}

function quickPull(range) {
    if (pullInProgress) {
        showAlert('A data pull is already in progress. Please wait.', 'warning');
        return;
    }
    
    // Highlight selected quick action
    $('.btn-group-toggle .btn').removeClass('active');
    $(`button[onclick="quickPull('${range}')"]`).addClass('active');
    
    $('#dateRange').val(range);
    toggleCustomDateRange();
    $('#testMode').prop('checked', false);
    
    // Add slight delay for better UX
    setTimeout(() => {
        pullAttendanceData();
    }, 200);
}

function validatePullForm() {
    const dateRange = $('#dateRange').val();
    const errors = [];
    
    if (dateRange === 'custom') {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (!startDate) errors.push('Start date is required for custom range');
        if (!endDate) errors.push('End date is required for custom range');
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const today = new Date();
            
            if (start > end) {
                errors.push('Start date must be before or equal to end date');
            }
            
            if (end > today) {
                errors.push('End date cannot be in the future');
            }
            
            const daysDiff = (end - start) / (1000 * 60 * 60 * 24);
            if (daysDiff > 90) {
                errors.push('Date range cannot exceed 90 days for performance reasons');
            }
        }
    }
    
    // Validate employee codes format
    const employeeCodes = $('#employeeCodes').val().trim();
    if (employeeCodes) {
        const codes = employeeCodes.split(',');
        const invalidCodes = codes.filter(code => {
            const cleanCode = code.trim();
            return cleanCode.length === 0 || cleanCode.length > 20 || !/^[A-Za-z0-9_-]+$/.test(cleanCode);
        });
        
        if (invalidCodes.length > 0) {
            errors.push('Invalid employee codes format. Use alphanumeric characters, underscores, and hyphens only.');
        }
    }
    
    if (errors.length > 0) {
        showAlert(errors.join('<br>'), 'error');
        return false;
    }
    
    return true;
}

function pullAttendanceData() {
    if (pullInProgress) {
        showAlert('A data pull is already in progress', 'warning');
        return;
    }
    
    if (!validatePullForm()) {
        return;
    }
    
    pullInProgress = true;
    pullAbortController = new AbortController();
    
    // Update UI state
    const pullButton = $('button[onclick="pullAttendanceData()"]');
    pullButton.prop('disabled', true)
             .html('<i class="fas fa-spinner fa-spin mr-2"></i>Pulling Data...');
    
    // Show progress section with cancel button
    $('#progressSection').show();
    $('#resultsSection').hide();
    showProgressWithCancel(0, 'Initializing data pull...');
    
    // Prepare form data
    const formData = new FormData($('#dataPullerForm')[0]);
    
    // Handle employee codes properly
    const employeeCodes = $('#employeeCodes').val().trim();
    if (employeeCodes) {
        const codes = employeeCodes.split(',').map(code => code.trim()).filter(code => code);
        formData.delete('employee_codes');
        codes.forEach(code => formData.append('employee_codes[]', code));
    }
    
    // Track progress stages
    const progressStages = [
        { percent: 10, message: 'Connecting to ETimeOffice API...' },
        { percent: 30, message: 'Authenticating with server...' },
        { percent: 50, message: 'Fetching attendance data...' },
        { percent: 70, message: 'Processing records...' },
        { percent: 90, message: 'Saving to database...' },
        { percent: 100, message: 'Data pull completed!' }
    ];
    
    let currentStage = 0;
    const progressTimer = setInterval(() => {
        if (currentStage < progressStages.length - 1 && pullInProgress) {
            const stage = progressStages[currentStage];
            updateProgress(stage.percent, stage.message);
            currentStage++;
        }
    }, 2000);
    
    $.ajax({
        url: '{{ route("admin.attendance.settings.etimeoffice.pull-data") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 300000, // 5 minutes timeout
        success: function(response) {
            clearInterval(progressTimer);
            updateProgress(100, 'Data pull completed successfully!');
            
            // Store results for export
            lastResultsData = response;
            
            setTimeout(() => {
                $('#progressSection').hide();
                displayResults(response);
                refreshSyncStatus();
                loadSyncHistory();
                $('#exportResultsBtn').show();
            }, 1500);
        },
        error: function(xhr) {
            clearInterval(progressTimer);
            
            let message = 'Data pull failed';
            if (xhr.status === 0) {
                message = 'Request was cancelled or connection lost';
            } else if (xhr.responseJSON?.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.status === 500) {
                message = 'Server error occurred during data pull';
            } else if (xhr.status === 422) {
                message = 'Invalid request parameters';
            }
            
            updateProgress(0, 'Error: ' + message);
            
            setTimeout(() => {
                $('#progressSection').hide();
                showAlert(message, 'error');
            }, 3000);
        },
        complete: function() {
            clearInterval(progressTimer);
            pullInProgress = false;
            pullAbortController = null;
            
            // Reset UI state
            pullButton.prop('disabled', false)
                     .html('<i class="fas fa-download mr-2"></i>Pull Attendance Data');
            
            // Remove active states
            $('.btn-group-toggle .btn').removeClass('active');
        }
    });
}

function showProgressWithCancel(percentage, text) {
    $('#progressBar').css('width', percentage + '%');
    $('#progressText').html(`
        ${text}
        <div class="mt-2">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelDataPull()">
                <i class="fas fa-times mr-1"></i>Cancel
            </button>
        </div>
    `);
}

function cancelDataPull() {
    if (pullAbortController) {
        pullAbortController.abort();
        pullInProgress = false;
        
        updateProgress(0, 'Data pull cancelled by user');
        
        setTimeout(() => {
            $('#progressSection').hide();
            showAlert('Data pull was cancelled', 'info');
        }, 1000);
    }
}

function updateProgress(percentage, text) {
    $('#progressBar').css('width', percentage + '%');
    $('#progressText').html(text);
}

function displayResults(response) {
    $('#resultsSection').show();
    
    let resultHtml = ''; // FIXED: Initialize the variable
    
    if (response && response.success) {
        const data = response.data || {};
        resultHtml = `
            <div class="alert alert-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><i class="fas fa-check-circle mr-2"></i>${response.message || 'Operation completed successfully'}</h6>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h5 class="text-primary">${data.total_records || 0}</h5>
                            <small class="text-muted">Total Records</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h5 class="text-success">${data.created_records || 0}</h5>
                            <small class="text-muted">Created</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h5 class="text-info">${data.updated_records || 0}</h5>
                            <small class="text-muted">Updated</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h5 class="text-warning">${data.skipped_records || 0}</h5>
                            <small class="text-muted">Skipped</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Show detailed breakdown if available
        if (data.date_range) {
            resultHtml += `
                <div class="mt-3">
                    <h6>Date Range Processed</h6>
                    <p class="text-muted">
                        From: ${formatDateTime(data.date_range.start)}<br>
                        To: ${formatDateTime(data.date_range.end)}
                    </p>
                </div>
            `;
        }
        
        // Show errors if any
        if (data.errors && Array.isArray(data.errors) && data.errors.length > 0) {
            const errorLimit = 10;
            const visibleErrors = data.errors.slice(0, errorLimit);
            const remainingErrors = data.errors.length - errorLimit;
            
            resultHtml += `
                <div class="mt-3">
                    <h6 class="text-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Issues Found (${data.errors.length})
                    </h6>
                    <div class="alert alert-warning">
                        <ul class="mb-0">
                            ${visibleErrors.map(error => `<li>${error}</li>`).join('')}
                            ${remainingErrors > 0 ? `<li class="text-muted">... and ${remainingErrors} more issues</li>` : ''}
                        </ul>
                    </div>
                </div>
            `;
        }
        
        if (data.test_mode) {
            resultHtml = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle mr-2"></i>Test Mode Results</h6>
                    <p>This was a preview run. No actual data was saved to the database.</p>
                </div>
            ` + resultHtml;
        }
        
    } else {
        resultHtml = `
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-triangle mr-2"></i>Pull Failed</h6>
                <p>${response ? response.message : 'Unknown error occurred'}</p>
                ${response && response.debug_info ? `<small class="text-muted">Debug info available in browser console.</small>` : ''}
            </div>
        `;
        
        if (response && response.debug_info) {
            console.error('ETimeOffice Pull Debug Info:', response.debug_info);
        }
    }
    
    $('#resultsContent').html(resultHtml);
}

// Sync Status and History Functions
function refreshSyncStatus() {
    $.get('{{ route("admin.attendance.settings.etimeoffice.sync-status") }}')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                let statusClass = 'badge-success';
                let statusText = 'Active';
                
                if (!data.is_enabled) {
                    statusClass = 'badge-secondary';
                    statusText = 'Disabled';
                } else if (data.sync_health === 'poor') {
                    statusClass = 'badge-danger';
                    statusText = 'Issues';
                } else if (data.sync_health === 'fair') {
                    statusClass = 'badge-warning';
                    statusText = 'Fair';
                }
                
                $('#syncStatus')
                    .removeClass('badge-secondary badge-success badge-warning badge-danger')
                    .addClass(statusClass)
                    .text(statusText);
            }
        })
        .fail(function() {
            $('#syncStatus')
                .removeClass('badge-secondary badge-success badge-warning badge-danger')
                .addClass('badge-danger')
                .text('Error');
        });
}

function loadSyncHistory() {
    $.get('{{ route("admin.attendance.settings.etimeoffice.sync-history") }}')
        .done(function(response) {
            if (response.success && response.data.length > 0) {
                let historyHtml = '';
                response.data.forEach(function(sync) {
                    const statusBadge = sync.status === 'success' 
                        ? '<span class="badge badge-success">Success</span>'
                        : sync.status === 'failed'
                        ? '<span class="badge badge-danger">Failed</span>'
                        : '<span class="badge badge-warning">Partial</span>';
                    
                    const successRate = sync.success_rate !== undefined 
                        ? `${sync.success_rate}%` 
                        : 'N/A';
                    
                    historyHtml += `
                        <tr>
                            <td>${formatDateTime(sync.date)}</td>
                            <td>${sync.range}</td>
                            <td>${sync.records || 0}</td>
                            <td>${statusBadge}</td>
                            <td>${sync.duration || 'N/A'}</td>
                            <td><span class="badge badge-info">${successRate}</span></td>
                        </tr>
                    `;
                });
                $('#syncHistoryTable tbody').html(historyHtml);
            } else {
                $('#syncHistoryTable tbody').html(`
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            No sync history found
                        </td>
                    </tr>
                `);
            }
        })
        .fail(function() {
            $('#syncHistoryTable tbody').html(`
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        Failed to load sync history
                    </td>
                </tr>
            `);
        });
}

// Export Functions
function exportResults() {
    const modal = `
        <div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-download mr-2"></i>Export Attendance Data
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="exportForm">
                            <div class="form-group">
                                <label>Export Format</label>
                                <select class="form-control" id="exportFormat" name="format">
                                    <option value="xlsx">Excel (.xlsx)</option>
                                    <option value="csv">CSV (.csv)</option>
                                    <option value="pdf">PDF (.pdf)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Date Range</label>
                                <select class="form-control" id="exportDateRange" name="date_range" onchange="toggleExportCustomRange()">
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="last_7_days">Last 7 Days</option>
                                    <option value="last_30_days">Last 30 Days</option>
                                    <option value="this_month">This Month</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                            
                            <div class="row" id="exportCustomRange" style="display: none;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" id="exportStartDate" name="start_date">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" id="exportEndDate" name="end_date">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="includeSummary" name="include_summary" checked>
                                    <label class="custom-control-label" for="includeSummary">
                                        Include Summary Statistics
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Filter by Status</label>
                                <select class="form-control" name="filter_status">
                                    <option value="all">All Records</option>
                                    <option value="present">Present Only</option>
                                    <option value="absent">Absent Only</option>
                                    <option value="late">Late Only</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="startExport()">
                            <i class="fas fa-download mr-2"></i>Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#exportModal').remove();
    
    // Add modal to body
    $('body').append(modal);
    
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    $('#exportEndDate').val(today);
    $('#exportStartDate').val(getPreviousDate(today, 7));
    
    // Show modal
    $('#exportModal').modal('show');
}

function toggleExportCustomRange() {
    const dateRange = $('#exportDateRange').val();
    if (dateRange === 'custom') {
        $('#exportCustomRange').show();
        $('#exportStartDate, #exportEndDate').prop('required', true);
    } else {
        $('#exportCustomRange').hide();
        $('#exportStartDate, #exportEndDate').prop('required', false);
    }
}

// Auto-save Attendance Timings
function saveAttendanceTiming() {
    const form = $('#attendanceTimingForm');
    const statusBadge = $('#timingSaveStatus');
    
    // Show saving state
    statusBadge.removeClass('badge-success badge-danger').addClass('badge-warning').text('Saving...');
    
    $.ajax({
        url: '{{ route("admin.attendance.settings.update") }}', // Ensure this route exists in web.php
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                // Show success
                statusBadge.removeClass('badge-warning').addClass('badge-success').text('Saved');
                
                // Clear "Saved" message after 2 seconds
                setTimeout(() => {
                    statusBadge.fadeOut(500, function() {
                        $(this).text('').show().removeClass('badge-success');
                    });
                }, 2000);
            } else {
                statusBadge.removeClass('badge-warning').addClass('badge-danger').text('Error');
                showAlert(response.message || 'Failed to save settings', 'error');
            }
        },
        error: function(xhr) {
            statusBadge.removeClass('badge-warning').addClass('badge-danger').text('Failed');
            let errorMsg = 'Failed to save settings';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showAlert(errorMsg, 'error');
        }
    });
}

function startExport() {
    const formData = new FormData($('#exportForm')[0]);
    
    // Show loading state
    const exportButton = $('.modal-footer .btn-primary');
    const originalText = exportButton.html();
    exportButton.html('<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...').prop('disabled', true);
    
    // Create a temporary form for file download
    const form = $('<form>', {
        'method': 'POST',
        'action': '{{ route("admin.attendance.export.custom") }}'
    });
    
    // Add CSRF token
    form.append($('<input>', {
        'type': 'hidden',
        'name': '_token',
        'value': '{{ csrf_token() }}'
    }));
    
    // Add form data
    for (let [key, value] of formData.entries()) {
        form.append($('<input>', {
            'type': 'hidden',
            'name': key,
            'value': value
        }));
    }
    
    // Submit form
    form.appendTo('body').submit().remove();
    
    // Reset button after a delay
    setTimeout(() => {
        exportButton.html(originalText).prop('disabled', false);
        $('#exportModal').modal('hide');
        showAlert('Export initiated. Download should start shortly.', 'success');
    }, 2000);
}

function exportTodayData(format = 'xlsx') {
    window.location.href = `{{ route("admin.attendance.export.today") }}?format=${format}`;
    showAlert('Export initiated. Download should start shortly.', 'success');
}

function exportSyncLogs(days = 30, format = 'xlsx') {
    window.location.href = `{{ route("admin.attendance.export.sync-logs") }}?days=${days}&format=${format}`;
    showAlert('Sync logs export initiated. Download should start shortly.', 'success');
}

function exportLastResults() {
    if (!lastResultsData) {
        showAlert('No recent results to export. Please run a data pull first.', 'warning');
        return;
    }
    exportResults();
}

// Configuration and validation functions
function validateAndShowProgress() {
    $.get('{{ route("admin.attendance.settings.etimeoffice.validate-config") }}')
        .done(function(response) {
            if (response.success) {
                showConfigurationProgress(response.data);
            }
        })
        .fail(function() {
            showAlert('Failed to validate configuration', 'error');
        });
}

function showConfigurationProgress(validation) {
    const progressHtml = `
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="m-0">
                    <i class="fas fa-tasks mr-2"></i>Configuration Progress
                    <span class="float-right">
                        ${validation.overall.completed_steps}/${validation.overall.total_steps} 
                        (${validation.overall.completion_percentage}%)
                    </span>
                </h6>
            </div>
            <div class="card-body">
                <div class="progress mb-3">
                    <div class="progress-bar" style="width: ${validation.overall.completion_percentage}%"></div>
                </div>
                
                <div class="setup-steps">
                    ${validation.steps.map((step, index) => `
                        <div class="setup-step ${step.completed ? 'completed' : 'pending'}">
                            <div class="step-icon">
                                <i class="fas ${step.completed ? 'fa-check-circle text-success' : 'fa-circle text-muted'}"></i>
                            </div>
                            <div class="step-content">
                                <h6>${step.title}</h6>
                                <p class="text-muted">${step.description}</p>
                                <small>Current: ${step.current_value}</small>
                            </div>
                            ${!step.completed && step.field !== 'test_connection' ? `
                                <div class="step-action">
                                    <button class="btn btn-sm btn-outline-primary" onclick="focusField('${step.field}')">
                                        Configure
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
                
                ${validation.overall.next_step ? `
                    <div class="alert alert-info mt-3">
                        <strong>Next Step:</strong> ${validation.overall.next_step.title}
                        <br><small>${validation.overall.next_step.description}</small>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
    
    // Add CSS for setup steps
    if (!$('#setup-steps-css').length) {
        $('<style id="setup-steps-css">').text(`
            .setup-step {
                display: flex;
                align-items: center;
                padding: 15px 0;
                border-bottom: 1px solid #eee;
            }
            .setup-step:last-child {
                border-bottom: none;
            }
            .step-icon {
                margin-right: 15px;
                font-size: 20px;
            }
            .step-content {
                flex: 1;
            }
            .step-content h6 {
                margin-bottom: 5px;
            }
            .step-content p {
                margin-bottom: 5px;
                font-size: 14px;
            }
            .step-action {
                margin-left: 15px;
            }
        `).appendTo('head');
    }
    
    // Show progress after the main form
    $('#etimeofficeConfig').after(progressHtml);
}

function focusField(fieldName) {
    const fieldMap = {
        'etimeoffice_api_url': '#etimeoffice_api_url',
        'etimeoffice_corporate_id': '#etimeoffice_corporate_id',
        'etimeoffice_username': '#etimeoffice_username',
        'etimeoffice_password': '#etimeoffice_password',
        'credentials': '#etimeoffice_username',
        'etimeoffice_enabled': '#etimeoffice_enabled'
    };
    
    const field = $(fieldMap[fieldName]);
    if (field.length) {
        field.focus().get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Highlight the field briefly
        field.addClass('border-primary');
        setTimeout(() => field.removeClass('border-primary'), 2000);
    }
}

function loadSetupRecommendations() {
    $.get('{{ route("admin.attendance.settings.etimeoffice.setup-recommendations") }}')
        .done(function(response) {
            if (response.success && response.data.recommendations.length > 0) {
                showRecommendations(response.data);
            }
        });
}

function showRecommendations(data) {
    const recommendationsHtml = `
        <div class="card mt-3 border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="m-0">
                    <i class="fas fa-lightbulb mr-2"></i>Setup Recommendations
                    <span class="badge badge-dark float-right">${data.total_count}</span>
                </h6>
            </div>
            <div class="card-body">
                ${data.recommendations.map(rec => `
                    <div class="alert alert-${rec.type === 'error' ? 'danger' : rec.type === 'warning' ? 'warning' : 'info'} alert-sm">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="alert-heading">${rec.title}</h6>
                                <p class="mb-0">${rec.message}</p>
                            </div>
                            <span class="badge badge-${rec.priority === 'high' ? 'danger' : rec.priority === 'medium' ? 'warning' : 'info'}">
                                ${rec.priority}
                            </span>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    $('#etimeofficeConfig').after(recommendationsHtml);
}

// Utility functions
function copyWebhookUrl() {
    const url = '{{ url("/api/etimeoffice/webhook") }}';
    navigator.clipboard.writeText(url).then(() => {
        showAlert('Webhook URL copied to clipboard!', 'success');
    });
}

function copyTestResults() {
    const content = $('#testResultContent').text();
    navigator.clipboard.writeText(content).then(() => {
        showAlert('Test results copied to clipboard!', 'success');
    });
}

function resetForm() {
    if (confirm('Are you sure you want to reset all settings?')) {
        loadETimeOfficeSettings();
    }
}

function resetDataPullerForm() {
    $('#dataPullerForm')[0].reset();
    $('#dateRange').val('today');
    toggleCustomDateRange();
    $('#resultsSection').hide();
    $('#progressSection').hide();
    $('#exportResultsBtn').hide();
    lastResultsData = null;
}

// Enhanced alert system
function showAlert(message, type = 'info') {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const alertIcon = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-triangle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    }[type] || 'fa-info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${alertIcon} mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').not('.alert-info, .alert-success').remove();
    
    // Add new alert to top of container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').not('.alert-info, .alert-success').fadeOut();
    }, 5000);
}

// Initialize enhanced features when page loads
$(document).ready(function() {
    // Load initial data with error handling
    loadETimeOfficeSettings();
    loadBiometricStats();
    
    // Safe event handlers with null checks
    const enabledCheckbox = $('#etimeoffice_enabled');
    if (enabledCheckbox.length) {
        enabledCheckbox.change(function() {
            if ($(this).is(':checked')) {
                $('#etimeofficeConfig').slideDown();
            } else {
                $('#etimeofficeConfig').slideUp();
            }
        });
    }
    
    // Form submission with safe handling
    const form = $('#etimeofficeForm');
    if (form.length) {
        form.submit(function(e) {
            e.preventDefault();
            saveETimeOfficeSettings();
        });
    }
    
    // Safe button handlers
    const testButton = $('#testConnection');
    if (testButton.length) {
        testButton.click(function(e) {
            e.preventDefault();
            testETimeOfficeConnection();
        });
    }
    
    const syncButton = $('#syncNow');
    if (syncButton.length) {
        syncButton.click(function(e) {
            e.preventDefault();
            triggerManualSync();
        });
    }
    
    // Auto-refresh with error handling
    setInterval(function() {
        try {
            loadBiometricStats();
        } catch (e) {
            console.error('Error in auto-refresh:', e);
        }
    }, 120000);
    
    // Load additional features with delay and error handling
    setTimeout(() => {
        try {
            validateAndShowProgress();
            loadSetupRecommendations();
        } catch (e) {
            console.error('Error loading additional features:', e);
        }
    }, 1000);
});
</script>
<script>
// Override problematic button.js behavior
window.addEventListener('DOMContentLoaded', function() {
    // Prevent null reference errors by ensuring elements exist
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (!checkbox.hasAttribute('data-safe')) {
            checkbox.setAttribute('data-safe', 'true');
        }
    });
});
</script>
@endpush