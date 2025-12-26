{{-- resources/views/student/dashboard.blade.php --}}
@extends('layouts.theme')

@section('title', 'Student Dashboard')

@push('styles')
<style>
.student-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: 10px;
}

.student-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.student-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.attendance-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: conic-gradient(
        #4caf50 0deg, 
        #4caf50 var(--attendance-deg), 
        #e0e0e0 var(--attendance-deg), 
        #e0e0e0 360deg
    );
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.attendance-inner {
    width: 90px;
    height: 90px;
    background: white;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.attendance-percent {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.attendance-label {
    font-size: 0.75rem;
    color: #666;
}

.grade-badge {
    background: linear-gradient(45deg, #ff6b6b, #feca57);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: bold;
    font-size: 1.2rem;
}

.subject-card {
    background: white;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.subject-card:hover {
    transform: translateX(5px);
}

.fee-status-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
}

.fee-progress {
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    height: 8px;
    overflow: hidden;
}

.fee-progress-bar {
    background: white;
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s ease;
}

.class-schedule-item {
    background: white;
    border-left: 4px solid #2196f3;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.class-time-badge {
    background: #2196f3;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 500;
}

.notification-item {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-left: 4px solid #ffc107;
    transition: all 0.2s;
}

.notification-item:hover {
    border-left-color: #2196f3;
    transform: translateX(3px);
}

.notification-unread {
    border-left-color: #f44336;
    background: #fff5f5;
}

.quick-action-btn {
    background: linear-gradient(45deg, #ff6b6b, #feca57);
    border: none;
    color: white;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    transition: all 0.3s;
    display: block;
    text-decoration: none;
    margin-bottom: 1rem;
}

.quick-action-btn:hover {
    transform: scale(1.05);
    color: white;
    text-decoration: none;
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-ring-circle {
    transition: stroke-dasharray 0.5s ease-in-out;
}

@media (max-width: 768px) {
    .student-header {
        margin: 0 -15px 2rem;
        border-radius: 0;
    }
    
    .attendance-circle {
        width: 100px;
        height: 100px;
    }
    
    .attendance-inner {
        width: 75px;
        height: 75px;
    }
    
    .attendance-percent {
        font-size: 1.2rem;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Student Welcome Header --}}
    <div class="student-header">
        <div class="container">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-day mr-2"></i>Today's Classes
                    </h6>
                    <span class="badge badge-primary">{{ now()->format('M j, Y') }}</span>
                </div>
                <div class="card-body">
                    @forelse($todays_schedule as $class)
                    <div class="class-schedule-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="class-time-badge">{{ $class['start_time'] }}</span>
                            </div>
                            <div class="col">
                                <h6 class="mb-1">{{ $class['subject'] }}</h6>
                                <p class="mb-1 text-muted">{{ $class['faculty'] }}</p>
                                <small class="text-info">
                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $class['classroom'] }}
                                </small>
                            </div>
                            <div class="col-auto">
                                @if($class['attendance_status'] === 'present')
                                    <span class="badge badge-success">
                                        <i class="fas fa-check mr-1"></i>Present
                                    </span>
                                @elseif($class['attendance_status'] === 'absent')
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times mr-1"></i>Absent
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-clock mr-1"></i>Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times text-gray-300 fa-3x mb-3"></i>
                        <h5 class="text-gray-500">No classes scheduled for today</h5>
                        <p class="text-gray-400">Enjoy your free day!</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Subject-wise Performance --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar mr-2"></i>Subject Performance
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($academic_summary['subjects_enrolled'] ?? [] as $subject)
                    <div class="subject-card">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h6 class="mb-1">{{ $subject['name'] }}</h6>
                                <small class="text-muted">{{ $subject['code'] }}</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <span class="badge badge-primary">{{ $subject['credits'] }} Credits</span>
                            </div>
                            <div class="col-md-3">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: 85%"></div>
                                </div>
                                <small class="text-muted">85% Progress</small>
                            </div>
                            <div class="col-md-2 text-center">
                                <strong class="text-success">A</strong>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="fas fa-book-open text-gray-300 fa-3x mb-3"></i>
                        <p class="text-gray-500">No subjects enrolled</p>
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
                    <a href="{{ route('student.fee-payment') }}" class="quick-action-btn">
                        <i class="fas fa-credit-card fa-2x mb-2 d-block"></i>
                        <strong>Pay Fees</strong>
                    </a>
                    <a href="{{ route('student.my-attendance') }}" class="quick-action-btn">
                        <i class="fas fa-chart-pie fa-2x mb-2 d-block"></i>
                        <strong>View Attendance</strong>
                    </a>
                    <a href="{{ route('student.my-report') }}" class="quick-action-btn">
                        <i class="fas fa-download fa-2x mb-2 d-block"></i>
                        <strong>Download Report</strong>
                    </a>
                </div>
            </div>

            {{-- Attendance Trend --}}
            @if(isset($my_attendance['weekly_trend']) && count($my_attendance['weekly_trend']) > 0)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>Attendance Trend
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="attendance-trend-chart" width="400" height="200"></canvas>
                    <div class="mt-3">
                        @if(isset($my_attendance['days_since_absent']) && $my_attendance['days_since_absent'])
                        <small class="text-success">
                            <i class="fas fa-check-circle mr-1"></i>
                            {{ $my_attendance['days_since_absent'] }} days since last absence
                        </small>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Upcoming Events --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar mr-2"></i>Upcoming Events
                    </h6>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    @forelse($upcoming_events as $event)
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <div class="mr-3">
                            <div class="bg-{{ $event['type'] === 'exam' ? 'danger' : ($event['type'] === 'assignment' ? 'warning' : 'info') }} text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <i class="fas fa-{{ $event['type'] === 'exam' ? 'graduation-cap' : ($event['type'] === 'assignment' ? 'file-alt' : 'calendar') }}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ $event['title'] }}</div>
                            <small class="text-muted">{{ $event['description'] }}</small>
                            <div class="small text-primary">{{ $event['date']->format('M j, Y') }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-3">
                        <i class="fas fa-calendar-check text-gray-300 fa-2x mb-2"></i>
                        <p class="text-gray-500 small">No upcoming events</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Notifications --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bell mr-2"></i>Notifications
                    </h6>
                    @if(count(array_filter($recent_notifications, fn($n) => !$n['read'])) > 0)
                    <span class="badge badge-danger">
                        {{ count(array_filter($recent_notifications, fn($n) => !$n['read'])) }} new
                    </span>
                    @endif
                </div>
                <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                    @forelse($recent_notifications as $notification)
                    <div class="notification-item {{ !$notification['read'] ? 'notification-unread' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $notification['title'] }}</div>
                                <p class="mb-1 small">{{ $notification['message'] }}</p>
                                <small class="text-muted">{{ $notification['created_at']->diffForHumans() }}</small>
                            </div>
                            <div class="ml-2">
                                <span class="badge badge-{{ $notification['priority'] === 'high' ? 'danger' : 'info' }}">
                                    {{ ucfirst($notification['priority']) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-3">
                        <i class="fas fa-bell-slash text-gray-300 fa-2x mb-2"></i>
                        <p class="text-gray-500 small">No notifications</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Payments --}}
    @if(isset($my_fees['recent_payments']) && count($my_fees['recent_payments']) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-2"></i>Recent Payments
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($my_fees['recent_payments'] as $payment)
                                <tr>
                                    <td>{{ $payment['date'] }}</td>
                                    <td>{{ $payment['type'] }}</td>
                                    <td>₹{{ number_format($payment['amount']) }}</td>
                                    <td>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check mr-1"></i>Paid
                                        </span>
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

{{-- Fee Payment Reminder Modal --}}
@if(isset($my_fees['days_until_due']) && $my_fees['days_until_due'] <= 7 && $my_fees['days_until_due'] > 0)
<div class="modal fade" id="feeReminderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Fee Payment Reminder
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Your fee payment is due in <strong>{{ $my_fees['days_until_due'] }} days</strong>.</p>
                <p>Amount due: <strong>₹{{ number_format($my_fees['pending_amount']) }}</strong></p>
                <p>Due date: <strong>{{ \Carbon\Carbon::parse($my_fees['next_due_date'])->format('F j, Y') }}</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Remind Later</button>
                <a href="{{ route('student.fee-payment') }}" class="btn btn-primary">
                    <i class="fas fa-credit-card mr-2"></i>Pay Now
                </a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
$(document).ready(function() {
    initializeAttendanceTrendChart();
    setupNotifications();
    startRealTimeUpdates();
    showFeeReminder();
});

function initializeAttendanceTrendChart() {
    const canvas = document.getElementById('attendance-trend-chart');
    if (!canvas) return;

    const trendData = @json($my_attendance['weekly_trend'] ?? []);
    
    if (trendData.length === 0) return;

    new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: trendData.map(item => item.week),
            datasets: [{
                label: 'Attendance %',
                data: trendData.map(item => item.percentage),
                borderColor: '#4caf50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#4caf50',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function setupNotifications() {
    // Mark notifications as read when clicked
    $('.notification-item').on('click', function() {
        $(this).removeClass('notification-unread');
        // Here you would make an AJAX call to mark as read
    });
}

function startRealTimeUpdates() {
    // Update attendance status every 5 minutes during class hours
    const now = new Date().getHours();
    if (now >= 8 && now <= 18) { // During college hours
        setInterval(function() {
            updateTodaysAttendance();
        }, 300000); // 5 minutes
    }
}

function updateTodaysAttendance() {
    $.ajax({
        url: '{{ route("api.dashboard.student-metrics") }}',
        method: 'GET',
        success: function(response) {
            if (response.todays_attendance) {
                // Update attendance badges
                $('.class-schedule-item').each(function() {
                    const classElement = $(this);
                    // Update based on response data
                });
            }
        },
        error: function() {
            // Silently fail for real-time updates
        }
    });
}

function showFeeReminder() {
    @if(isset($my_fees['days_until_due']) && $my_fees['days_until_due'] <= 7 && $my_fees['days_until_due'] > 0)
    // Show modal after 3 seconds if fee is due soon
    setTimeout(function() {
        $('#feeReminderModal').modal('show');
    }, 3000);
    @endif
}

// Progress ring animation
function animateProgressRing(element, percentage) {
    const radius = element.r.baseVal.value;
    const circumference = radius * 2 * Math.PI;
    const offset = circumference - percentage / 100 * circumference;
    
    element.style.strokeDasharray = `${circumference} ${circumference}`;
    element.style.strokeDashoffset = offset;
}

// Keyboard shortcuts for students
$(document).keydown(function(e) {
    // Alt+F for fee payment
    if (e.altKey && e.keyCode === 70) {
        e.preventDefault();
        window.location.href = '{{ route("student.fee-payment") }}';
    }
    
    // Alt+A for attendance view
    if (e.altKey && e.keyCode === 65) {
        e.preventDefault();
        window.location.href = '{{ route("student.my-attendance") }}';
    }
    
    // Alt+R for reports
    if (e.altKey && e.keyCode === 82) {
        e.preventDefault();
        window.location.href = '{{ route("student.my-report") }}';
    }
});

// Smooth scroll to sections
$('a[href^="#"]').on('click', function(e) {
    e.preventDefault();
    const target = $($(this).attr('href'));
    if (target.length) {
        $('html, body').animate({
            scrollTop: target.offset().top - 100
        }, 500);
    }
});

// Save user preferences
function savePreference(key, value) {
    localStorage.setItem(`student_dashboard_${key}`, JSON.stringify(value));
}

function getPreference(key, defaultValue = null) {
    const stored = localStorage.getItem(`student_dashboard_${key}`);
    return stored ? JSON.parse(stored) : defaultValue;
}

// Auto-hide notifications after reading
$('.notification-item').on('mouseenter', function() {
    if ($(this).hasClass('notification-unread')) {
        const $this = $(this);
        setTimeout(function() {
            $this.removeClass('notification-unread');
        }, 2000);
    }
});

// Responsive adjustments
function adjustForMobile() {
    if ($(window).width() <= 768) {
        $('.quick-action-btn').removeClass('mb-1').addClass('mb-2');
        $('.student-card').css('margin-bottom', '1rem');
    }
}

$(window).on('resize', adjustForMobile);
$(document).ready(adjustForMobile);
</script>
@endpush