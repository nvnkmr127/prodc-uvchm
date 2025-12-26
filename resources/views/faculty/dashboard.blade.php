{{-- resources/views/faculty/dashboard.blade.php --}}
@extends('layouts.theme')

@section('title', 'Faculty Dashboard')

@push('styles')
<style>
.faculty-dashboard {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: 10px;
}

.class-card {
    border-left: 4px solid #4caf50;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.class-card:hover {
    border-left-color: #2196f3;
    transform: translateX(5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.class-time {
    background: #2196f3;
    color: white;
    border-radius: 8px;
    padding: 0.5rem;
    text-align: center;
    min-width: 80px;
}

.attendance-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 0.25rem 0.5rem;
    border-radius: 15px;
    font-size: 0.75rem;
}

.quick-action-card {
    background: linear-gradient(45deg, #ff6b6b, #feca57);
    color: white;
    border: none;
    text-align: center;
    padding: 1.5rem;
    border-radius: 10px;
    transition: transform 0.2s;
}

.quick-action-card:hover {
    transform: scale(1.05);
    color: white;
    text-decoration: none;
}

.stats-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    height: 100%;
}

.stats-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2196f3;
}

.stats-label {
    color: #666;
    font-weight: 500;
    margin-top: 0.5rem;
}

.progress-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(#4caf50 0deg, #4caf50 var(--progress), #e0e0e0 var(--progress), #e0e0e0 360deg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.progress-text {
    background: white;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #333;
}

.upcoming-class {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}

.ongoing-class {
    background: #d1ecf1;
    border-left: 4px solid #17a2b8;
}

.completed-class {
    background: #d4edda;
    border-left: 4px solid #28a745;
}

@media (max-width: 768px) {
    .faculty-dashboard {
        margin: 0 -15px 2rem;
        border-radius: 0;
    }
    
    .stats-number {
        font-size: 2rem;
    }
    
    .class-card {
        margin-bottom: 0.5rem;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Welcome Header --}}
    <div class="faculty-dashboard">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">Welcome back, {{ auth()->user()->name }}!</h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-calendar-day mr-2"></i>
                        {{ now()->format('l, F j, Y') }}
                    </p>
                </div>
                <div class="col-md-4 text-md-right">
                    <div class="mt-3 mt-md-0">
                        <a href="{{ route('faculty.attendance.create') }}" class="btn btn-light btn-lg">
                            <i class="fas fa-check mr-2"></i>Take Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats Row --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="stats-number">{{ $todays_schedule ? count($todays_schedule) : 0 }}</div>
                <div class="stats-label">Today's Classes</div>
                <small class="text-muted">{{ now()->format('M j') }}</small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="progress-circle" style="--progress: {{ ($attendance_stats['completion_rate'] ?? 0) * 3.6 }}deg">
                    <div class="progress-text">{{ round($attendance_stats['completion_rate'] ?? 0) }}%</div>
                </div>
                <div class="stats-label">Attendance Completion</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="stats-number">{{ $attendance_stats['total_student_records'] ?? 0 }}</div>
                <div class="stats-label">Students Under Me</div>
                <small class="text-success">
                    <i class="fas fa-chart-line mr-1"></i>{{ round($attendance_stats['average_attendance'] ?? 0, 1) }}% avg attendance
                </small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="stats-number text-warning">{{ count($pending_tasks['attendance_pending'] ?? []) }}</div>
                <div class="stats-label">Pending Tasks</div>
                <small class="text-muted">Attendance to be taken</small>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row">
        {{-- Today's Schedule --}}
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-day mr-2"></i>Today's Schedule
                    </h6>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshSchedule()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <a href="{{ route('faculty.dashboard.my-classes') }}" class="btn btn-sm btn-primary">
                            View All Classes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($todays_schedule as $class)
                    <div class="class-card card position-relative {{ $this->getClassStatusClass($class) }}" data-class-id="{{ $class['id'] }}">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="class-time">
                                        <strong>{{ $class['start_time'] }}</strong>
                                        <br>
                                        <small>{{ $class['end_time'] }}</small>
                                    </div>
                                </div>
                                <div class="col">
                                    <h5 class="card-title mb-1">{{ $class['subject'] }}</h5>
                                    <p class="card-text mb-1">
                                        <strong>{{ $class['course'] }}</strong> - {{ $class['batch'] }}
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $class['classroom'] }}
                                        <i class="fas fa-users ml-3 mr-1"></i>{{ $class['student_count'] }} students
                                    </small>
                                </div>
                                <div class="col-auto">
                                    @if($class['attendance_taken'])
                                        <span class="attendance-badge bg-success">
                                            <i class="fas fa-check mr-1"></i>Completed
                                        </span>
                                    @else
                                        <a href="{{ route('faculty.attendance.create', $class['id']) }}" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-check mr-1"></i>Take Attendance
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times text-gray-300 fa-3x mb-3"></i>
                        <h5 class="text-gray-500">No classes scheduled for today</h5>
                        <p class="text-gray-400">Enjoy your free day! Check tomorrow's schedule.</p>
                        <a href="{{ route('faculty.dashboard.my-classes') }}" class="btn btn-outline-primary">
                            <i class="fas fa-calendar mr-2"></i>View Weekly Schedule
                        </a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <a href="{{ route('faculty.my-leave.index') }}" class="quick-action-card d-block text-decoration-none">
                                <i class="fas fa-calendar-minus fa-2x mb-2"></i>
                                <div>Apply Leave</div>
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="{{ route('faculty.student-lookup') }}" class="quick-action-card d-block text-decoration-none">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <div>Find Student</div>
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="{{ route('faculty.my-reports') }}" class="quick-action-card d-block text-decoration-none">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                <div>My Reports</div>
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="{{ route('faculty.analytics') }}" class="quick-action-card d-block text-decoration-none">
                                <i class="fas fa-analytics fa-2x mb-2"></i>
                                <div>Analytics</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Summary --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>My Performance
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-sm font-weight-bold">Classes Taught</span>
                            <span class="badge badge-primary">{{ $student_performance['classes_taught'] ?? 0 }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-sm font-weight-bold">Attendance Rate</span>
                            <span class="badge badge-success">{{ round($attendance_stats['average_attendance'] ?? 0, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" 
                                 style="width: {{ $attendance_stats['average_attendance'] ?? 0 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-sm font-weight-bold">Student Satisfaction</span>
                            <span class="badge badge-info">4.8/5</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: 96%"></div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ route('faculty.dashboard.attendance-overview') }}" class="btn btn-sm btn-outline-primary">
                            Detailed Analytics
                        </a>
                    </div>
                </div>
            </div>

            {{-- Upcoming Classes --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock mr-2"></i>Upcoming Classes
                    </h6>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    @forelse($upcoming_classes ?? [] as $class)
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <div class="mr-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <small>{{ $class['days_until'] }}d</small>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ $class['subject'] }}</div>
                            <small class="text-muted">{{ $class['course'] }} - {{ $class['batch'] }}</small>
                            <div class="small text-primary">{{ $class['date'] }} at {{ $class['time'] }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-3">
                        <i class="fas fa-calendar-check text-gray-300 fa-2x mb-2"></i>
                        <p class="text-gray-500 small">No upcoming classes</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Quick Attendance Entry --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-stopwatch mr-2"></i>Quick Attendance
                    </h6>
                </div>
                <div class="card-body">
                    <form id="quick-attendance-form">
                        @csrf
                        <div class="form-group">
                            <label for="class-select">Select Class</label>
                            <select class="form-control" id="class-select" name="timetable_id">
                                <option value="">Choose a class...</option>
                                @foreach($todays_schedule as $class)
                                    @if(!$class['attendance_taken'])
                                    <option value="{{ $class['id'] }}">
                                        {{ $class['subject'] }} - {{ $class['start_time'] }}
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-check mr-2"></i>Take Attendance
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Student Performance Overview --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users mr-2"></i>Student Performance Overview
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="border-right">
                                <div class="h4 font-weight-bold text-primary">{{ $student_performance['students_count'] ?? 0 }}</div>
                                <div class="text-muted">Total Students</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="border-right">
                                <div class="h4 font-weight-bold text-success">{{ round($student_performance['average_performance'] ?? 0, 1) }}%</div>
                                <div class="text-muted">Average Performance</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="border-right">
                                <div class="h4 font-weight-bold text-info">{{ count($student_performance['top_performers'] ?? []) }}</div>
                                <div class="text-muted">Top Performers</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 font-weight-bold text-warning">{{ $student_performance['low_performers'] ?? 0 }}</div>
                            <div class="text-muted">Need Attention</div>
                        </div>
                    </div>
                    
                    @if(isset($student_performance['top_performers']) && count($student_performance['top_performers']) > 0)
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-success">Top Performers</h6>
                            @foreach(array_slice($student_performance['top_performers'], 0, 3) as $performer)
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span>Student #{{ $performer['student_id'] }}</span>
                                <span class="badge badge-success">{{ $performer['attendance_percentage'] }}%</span>
                            </div>
                            @endforeach
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <canvas id="performance-chart" width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
$(document).ready(function() {
    initializePerformanceChart();
    setupQuickAttendance();
    startLiveUpdates();
});

function initializePerformanceChart() {
    const ctx = document.getElementById('performance-chart');
    if (!ctx) return;

    const performanceData = @json($student_performance ?? []);
    
    new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['High Performers', 'Average', 'Need Attention'],
            datasets: [{
                data: [
                    performanceData.top_performers?.length || 0,
                    Math.max(0, (performanceData.total_students || 0) - (performanceData.top_performers?.length || 0) - (performanceData.low_performers || 0)),
                    performanceData.low_performers || 0
                ],
                backgroundColor: ['#28a745', '#17a2b8', '#ffc107'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 10
                    }
                }
            }
        }
    });
}

function setupQuickAttendance() {
    $('#quick-attendance-form').on('submit', function(e) {
        e.preventDefault();
        
        const timetableId = $('#class-select').val();
        if (!timetableId) {
            alert('Please select a class');
            return;
        }

        // Redirect to attendance page
        window.location.href = `{{ route('faculty.attendance.create', '') }}/${timetableId}`;
    });
}

function refreshSchedule() {
    const button = $('button[onclick="refreshSchedule()"]');
    const originalText = button.html();
    
    button.html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
    button.prop('disabled', true);
    
    $.ajax({
        url: '{{ route("api.dashboard.faculty-metrics") }}',
        method: 'GET',
        success: function(response) {
            showNotification('Schedule refreshed successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        },
        error: function() {
            showNotification('Failed to refresh schedule', 'error');
        },
        complete: function() {
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
}

function startLiveUpdates() {
    // Update attendance status every 2 minutes
    setInterval(function() {
        updateAttendanceStatus();
    }, 120000);
}

function updateAttendanceStatus() {
    $('.class-card').each(function() {
        const classId = $(this).data('class-id');
        const card = $(this);
        
        $.ajax({
            url: '{{ route("api.dashboard.class-analytics") }}',
            method: 'GET',
            data: { class_id: classId },
            success: function(response) {
                if (response.attendance_taken) {
                    card.find('.btn-primary').replaceWith(
                        '<span class="attendance-badge bg-success"><i class="fas fa-check mr-1"></i>Completed</span>'
                    );
                }
            }
        });
    });
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'error' ? 'alert-danger' : 'alert-info';
    
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('body').append(notification);
    
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

// Helper function to get class status CSS class
function getClassStatusClass(classItem) {
    const now = new Date();
    const classTime = new Date(); // This should be properly calculated
    
    // This is a simplified version - implement proper time comparison
    return 'upcoming-class'; // or 'ongoing-class' or 'completed-class'
}

// Keyboard shortcuts for faculty
$(document).keydown(function(e) {
    // Alt+A for quick attendance
    if (e.altKey && e.keyCode === 65) {
        e.preventDefault();
        $('#class-select').focus();
    }
    
    // Alt+R for refresh
    if (e.altKey && e.keyCode === 82) {
        e.preventDefault();
        refreshSchedule();
    }
});

// Auto-save form data
$(document).on('change', 'input, select', function() {
    const formData = $(this).closest('form').serialize();
    localStorage.setItem('faculty_dashboard_form', formData);
});

// Restore form data on page load
$(document).ready(function() {
    const savedData = localStorage.getItem('faculty_dashboard_form');
    if (savedData) {
        // Restore form values if needed
    }
});
</script>
@endpush

@php
// Helper function for class status (move this to a helper class in production)
function getClassStatusClass($class) {
    $now = now();
    $classStart = \Carbon\Carbon::parse($class['start_time']);
    $classEnd = \Carbon\Carbon::parse($class['end_time']);
    
    if ($now->lt($classStart)) {
        return 'upcoming-class';
    } elseif ($now->between($classStart, $classEnd)) {
        return 'ongoing-class';
    } else {
        return 'completed-class';
    }
}
@endphp