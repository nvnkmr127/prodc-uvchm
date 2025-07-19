@extends('layouts.theme')

@section('title', 'Student Profile: ' . $student->name)

@push('styles')
<style>
    .profile-head {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    .profile-head .img-thumbnail {
        width: 100px;
        height: 100px;
        object-fit: cover;
    }
    .detail-item {
        margin-bottom: 1rem;
    }
    .detail-item strong {
        display: block;
        color: #858796;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .detail-item span {
        font-size: 1rem;
        color: #5a5c69;
    }
    .status-badge {
        padding: 0.35em 0.65em;
        font-size: .75em;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
    }
    .status-badge.paid { background-color: #1cc88a; }
    .status-badge.unpaid { background-color: #e74a3b; }
    .status-badge.partial { background-color: #f6c23e; }

    /* New styles for attendance cards */
    .stat-card {
        border-left: 4px solid;
        border-radius: .35rem;
        transition: all 0.3s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .stat-card .card-body {
        padding: 1.25rem;
    }
    .stat-card.border-left-primary { border-left-color: #4e73df; }
    .stat-card.border-left-success { border-left-color: #1cc88a; }
    .stat-card.border-left-danger { border-left-color: #e74a3b; }
    .stat-card.border-left-info { border-left-color: #36b9cc; }
    
    .calendar-table td {
        height: 80px;
        vertical-align: middle!important;
        text-align: center;
    }
    /* Style for loading state */
    #attendance-content.loading {
        opacity: 0.5;
        pointer-events: none;
    }
</style>
@endpush

@section('content')

@php
    // Safety check: Ensure all attendance variables are defined
    $presentDays = $presentDays ?? 0;
    $absentDays = $absentDays ?? 0;
    $totalWorkingDays = $totalWorkingDays ?? 0;
    $attendancePercentage = $attendancePercentage ?? 0;

    // Ensure $attendanceData is defined
    if (!isset($attendanceData)) {
        $attendanceData = [
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'total_working_days' => $totalWorkingDays,
            'attendance_percentage' => $attendancePercentage,
            'month_name' => \Carbon\Carbon::parse($month ?? now())->format('F Y'),
            'late_days' => 0,
            'excused_days' => 0,
        ];
    }

    // Calculate overall attendance if not provided
    if (!isset($overallAttendanceData)) {
        try {
            $allAttendances = \App\Models\Attendance::where('student_id', $student->id)->get();
            $overallPresentDays = $allAttendances->where('status', 'present')->count();
            $overallTotalDays = $allAttendances->count();
            
            $overallAttendanceData = [
                'present_days' => $overallPresentDays,
                'absent_days' => $allAttendances->where('status', 'absent')->count(),
                'total_days' => $overallTotalDays,
                'attendance_percentage' => ($overallTotalDays > 0) ? round(($overallPresentDays / $overallTotalDays) * 100, 1) : 0,
                'late_days' => $allAttendances->where('status', 'late')->count(),
                'excused_days' => $allAttendances->where('status', 'excused')->count(),
            ];
        } catch (\Exception $e) {
            $overallAttendanceData = [
                'present_days' => 0,
                'absent_days' => 0,
                'total_days' => 0,
                'attendance_percentage' => 0,
                'late_days' => 0,
                'excused_days' => 0,
            ];
        }
    }
@endphp

{{-- 1. Page Header --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Student Profile</h1>
    <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-light shadow-sm"><i class="fas fa-arrow-left fa-sm text-gray-600"></i> Back to List</a>
</div>

{{-- Main Profile Header Card --}}
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="profile-head">
            <img src="{{ \App\Http\Controllers\Admin\StudentController::getStudentPhotoUrl($student, 100) }}" class="img-thumbnail rounded-circle" alt="Student Photo">
            <div>
                <h4 class="font-weight-bold mb-0">{{ $student->name }}</h4>
                <p class="text-muted mb-1">{{ $student->enrollment_number }}</p>
                <div class="mt-2">
                    <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-primary">Edit Profile</a>
                    <button class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#statusModal">Change Status</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Attendance Statistics Cards --}}
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present Days ({{ $attendanceData['month_name'] }})</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $attendanceData['present_days'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2 stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent Days</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $attendanceData['absent_days'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Attendance %</div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $attendanceData['attendance_percentage'] }}%</div>
                            </div>
                            <div class="col">
                                <div class="progress progress-sm mr-2">
                                    <div class="progress-bar 
                                        @if($attendanceData['attendance_percentage'] >= 75) bg-success 
                                        @elseif($attendanceData['attendance_percentage'] >= 50) bg-warning 
                                        @else bg-danger @endif" 
                                        role="progressbar" 
                                        style="width: {{ $attendanceData['attendance_percentage'] }}%" 
                                        aria-valuenow="{{ $attendanceData['attendance_percentage'] }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Working Days</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $attendanceData['total_working_days'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        {{-- Tabbed Content Card --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                 <ul class="nav nav-pills" id="myTab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab">Profile Details</a></li>
                    <li class="nav-item"><a class="nav-link" id="fees-tab" data-toggle="tab" href="#fees" role="tab">Finances</a></li>
                    <li class="nav-item"><a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">Attendance</a></li>
                    <li class="nav-item"><a class="nav-link" id="activity-tab" data-toggle="tab" href="#activity" role="tab">Activity</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="myTabContent">
                    {{-- Profile Tab --}}
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <h5 class="mb-4">Personal & Contact Information</h5>
                        <div class="row">
                            <div class="col-md-6 detail-item"><strong>Father's Name</strong> <span>{{ $student->father_name ?? 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item"><strong>Gender</strong> <span>{{ $student->gender ?? 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item"><strong>Email Address</strong> <span>{{ $student->email ?? 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item"><strong>Student Mobile</strong> <span>{{ $student->student_mobile ?? 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item"><strong>Father Mobile</strong> <span>{{ $student->father_mobile ?? 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item"><strong>Address</strong> <span>{{ $student->village ?? 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item">
            <strong>Referral Source</strong>
            <span>{{ $student->source ?? 'N/A' }}</span>
        </div>
        <div class="col-md-6 detail-item">
            <strong>Referral Name</strong>
            <span>{{ $student->referral_name ?? 'N/A' }}</span>
        </div>
                        </div>

                        <hr>
                        <h5 class="mb-4">Academic Information</h5>
                        <div class="row">
                            <div class="col-md-6 detail-item"><strong>Course</strong> <span>{{ $student->batch->course->name ?? 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item"><strong>Batch</strong> <span>{{ $student->batch->name ?? 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item"><strong>Admission Date</strong> <span>{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d M, Y') : 'N/A' }}</span></div>
                            <div class="col-md-6 detail-item"><strong>Status</strong> <span class="badge badge-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduated' ? 'info' : 'danger') }}">{{ ucfirst($student->status) }}</span></div>
                        </div>
                    </div>

                    {{-- Fees Tab --}}
                    <div class="tab-pane fade" id="fees" role="tabpanel">
                        <h5 class="mb-4">Invoices & Payments</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Issue Date</th>
                                        <th>Total</th>
                                        <th>Paid</th>
                                        <th>Due</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($student->invoices as $invoice)
                                        <tr>
                                            <td><a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                            <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d M, Y') }}</td>
                                            <td>₹{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>₹{{ number_format($invoice->paid_amount, 2) }}</td>
                                            <td class="text-danger">₹{{ number_format($invoice->due_amount ?? ($invoice->total_amount - $invoice->paid_amount), 2) }}</td>
                                            <td class="text-center"><span class="status-badge {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center">No invoices found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Attendance Tab --}}
                    <div class="tab-pane fade" id="attendance" role="tabpanel">
                        <div id="attendance-content">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0">Attendance Calendar</h5>
                                <form method="GET" class="form-inline">
                                    <label for="month" class="mr-2">Month:</label>
                                    <input type="month" name="month" value="{{ $month ?? \Carbon\Carbon::now()->format('Y-m') }}" class="form-control form-control-sm mr-2">
                                    <button type="submit" class="btn btn-sm btn-primary">Load</button>
                                </form>
                            </div>

                            {{-- Calendar View --}}
                            <div class="table-responsive">
                                <table class="table table-bordered calendar-table">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $currentDate = \Carbon\Carbon::parse($month ?? now());
                                            $startOfMonth = $currentDate->copy()->startOfMonth();
                                            $endOfMonth = $currentDate->copy()->endOfMonth();
                                            $daysInMonth = $endOfMonth->day;
                                            $firstDayOfWeek = $startOfMonth->dayOfWeek; // 1 = Monday, 7 = Sunday
                                            $firstDayOfWeek = $firstDayOfWeek == 0 ? 7 : $firstDayOfWeek; // Convert Sunday from 0 to 7
                                        @endphp
                                        <tr>
                                        @for ($i = 1; $i < $firstDayOfWeek; $i++)
                                            <td></td>
                                        @endfor
                                        @for ($day = 1; $day <= $daysInMonth; $day++)
                                            @php
                                                $dateString = $currentDate->copy()->day($day)->format('Y-m-d');
                                                $dayOfWeek = $currentDate->copy()->day($day)->dayOfWeek;
                                                
                                                if (isset($holidays[$dateString])) {
                                                    $status = 'Holiday';
                                                    $bgColor = 'bg-info text-white';
                                                } elseif ($dayOfWeek == 0) { // Sunday
                                                    $status = 'Sunday';
                                                    $bgColor = 'bg-secondary text-white';
                                                } elseif (isset($attendances[$dateString])) {
                                                    $status = ucfirst($attendances[$dateString]->status);
                                                    if ($status == 'Present') $bgColor = 'bg-success text-white';
                                                    elseif ($status == 'Absent') $bgColor = 'bg-danger text-white';
                                                    else { $bgColor = 'bg-warning text-dark'; }
                                                } else {
                                                    $status = 'N/A';
                                                    $bgColor = 'bg-light';
                                                }
                                            @endphp
                                            <td class="{{ $bgColor }}">
                                                <strong>{{ $day }}</strong><br>
                                                <small>{{ $status }}</small>
                                            </td>
                                            @if (($day + $firstDayOfWeek - 1) % 7 == 0 && $day != $daysInMonth) 
                                                </tr><tr> 
                                            @endif
                                        @endfor
                                        @while (($day + $firstDayOfWeek - 2) % 7 != 6) 
                                            <td></td>
                                            @php $day++; @endphp 
                                        @endwhile
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Activity Log Tab --}}
                    <div class="tab-pane fade" id="activity" role="tabpanel">
                         <h5 class="mb-4">Recent Account Activity</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    @forelse($activities as $activity)
                                        <tr>
                                            <td>
                                                <div>
                                                    <i class="fas fa-fw {{ $activity->properties->get('icon') ?? 'fa-history' }} text-gray-500 mr-2"></i>
                                                    {{ $activity->description }}
                                                    <div class="small text-muted mt-1">
                                                        by {{ optional($activity->causer)->name ?? 'System' }} • {{ $activity->created_at->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-center">No recent activity found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Sidebar --}}
    <div class="col-lg-4">
        {{-- Academic Info Card --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Academic Summary</h6>
            </div>
            <div class="card-body">
                <div class="detail-item">
                    <strong>Course</strong>
                    <span>{{ $student->batch->course->name ?? 'Not Assigned' }}</span>
                </div>
                <div class="detail-item">
                    <strong>Batch</strong>
                    <span>{{ $student->batch->name ?? 'Not Assigned' }}</span>
                </div>
                <div class="detail-item">
                    <strong>Overall Attendance</strong>
                    <span>{{ $overallAttendanceData['attendance_percentage'] }}% ({{ $overallAttendanceData['present_days'] }}/{{ $overallAttendanceData['total_days'] }} days)</span>
                </div>
                <div class="detail-item">
                    <strong>Status</strong>
                    <span class="badge badge-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduated' ? 'info' : 'danger') }}">{{ ucfirst($student->status) }}</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions Card --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-primary btn-block mb-2">
                    <i class="fas fa-edit mr-1"></i> Edit Profile
                </a>
                <a href="{{ route('admin.financials.student.ledger', $student) }}" class="btn btn-info btn-block mb-2">
                    <i class="fas fa-file-invoice mr-1"></i> View Ledger
                </a>
                <button class="btn btn-warning btn-block mb-2" data-toggle="modal" data-target="#statusModal">
                    <i class="fas fa-user-edit mr-1"></i> Change Status
                </button>
                <button class="btn btn-secondary btn-block" onclick="window.print()">
                    <i class="fas fa-print mr-1"></i> Print Profile
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Status Change Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.students.updateStatus', $student) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Change Student Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status">Select New Status:</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="active" {{ $student->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="graduated" {{ $student->status === 'graduated' ? 'selected' : '' }}>Graduated</option>
                            <option value="dropout" {{ $student->status === 'dropout' ? 'selected' : '' }}>Dropout</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Changing the status will be logged in the activity history.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-refresh attendance when month changes
    $('input[name="month"]').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Add loading state to attendance content
    $('#attendance-content form').on('submit', function() {
        $('#attendance-content').addClass('loading');
    });
    
    // Print functionality
    window.addEventListener('beforeprint', function() {
        document.title = 'Student Profile - {{ $student->name }}';
    });
});
</script>
@endpush