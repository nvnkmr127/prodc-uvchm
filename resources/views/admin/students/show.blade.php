@extends('layouts.theme')

@section('title', 'Student Profile: ' . $student->name)

@include('admin.students.partials.styles')

@section('content')

    @php
// Safety check: Ensure all variables are defined
$presentDays = $presentDays ?? 0;
$absentDays = $absentDays ?? 0;
$totalWorkingDays = $totalWorkingDays ?? 0;
$attendancePercentage = $attendancePercentage ?? 0;

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
    @endphp

    {{-- Modern Cover Profile Header --}}
    <div class="card modern-card mb-4 border-0 overflow-hidden">
        <div class="profile-cover"></div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="row">
                <div class="col-lg-auto text-center text-lg-left">
                    <div class="student-avatar-container">
                        @php
$photoUrl = \App\Models\Student::getStudentPhotoUrl($student, 200);

// 3. Status Color Logic
$statusColor = match ($student->status) {
    'active' => '#1cc88a', // Green
    'graduated' => '#36b9cc', // Info Blue
    'dropout' => '#e74a3b', // Red
    default => '#858796' // Grey
};
                        @endphp

                        <img src="{{ $photoUrl }}" class="student-avatar-img" alt="Student Photo">

                        <span class="status-indicator" style="background-color: {{ $statusColor }};"
                            title="Status: {{ ucfirst($student->status) }}" data-toggle="tooltip"></span>
                    </div>
                </div>

                <div class="col-lg pt-3 pl-lg-4">
                    <div class="row">
                        <div class="col-lg-8 mb-3">
                            <h2 class="font-weight-bold text-gray-800 mb-1">
                                {{ $student->name }}
                                <small
                                    class="text-muted h6 ml-2 font-weight-normal">({{ $student->enrollment_number }})</small>
                            </h2>
                            <p class="text-muted mb-3">
                                {{ optional($student->batch)->course->name ?? 'No Course' }}
                                <i class="fas fa-chevron-right fa-xs mx-2 text-gray-400"></i>
                                {{ optional($student->batch)->name ?? 'No Batch' }}
                            </p>

                            <div class="d-flex flex-wrap">
                                <div class="meta-pill biometric-badge mb-2" title="Biometric Device ID">
                                    <i class="fas fa-fingerprint"></i>
                                    <strong>Biometric ID: {{ $student->biometric_employee_code ?? 'Not Assigned' }}</strong>
                                </div>

                                <div class="meta-pill mb-2" title="Certificate Status">
                                    <i
                                        class="fas fa-certificate {{ $student->is_certificate_received ? 'text-success' : 'text-warning' }}"></i>
                                    <strong>{{ $student->is_certificate_received ? ($student->certificate_type . ' Certificate Received') : 'Certificate Pending' }}</strong>
                                </div>

                                <div class="meta-pill mb-2">
                                    <i class="fas fa-user-tie text-primary"></i>
                                    {{ $student->father_name ?? 'N/A' }}
                                    <span class="text-gray-400 mx-1">|</span>
                                    {{ $student->father_mobile ?? '' }}
                                </div>

                                <div class="meta-pill mb-2">
                                    <i class="fas fa-phone text-success"></i> {{ $student->student_mobile ?? 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 text-lg-right d-flex align-items-end justify-content-lg-end">
                            <div class="action-bar pb-3">
                                {{-- Primary Action --}}
                                <button type="button" onclick="openPaymentModal()"
                                    class="btn btn-success shadow-sm font-weight-bold mb-2">
                                    <i class="fas fa-credit-card mr-1"></i> Pay Fee
                                </button>

                                {{-- Secondary Actions (Visible) --}}
                                @if(Route::has('admin.payments.component-dashboard'))
                                    <a href="{{ route('admin.payments.component-dashboard', $student) }}"
                                        class="btn btn-info shadow-sm mb-2">
                                        <i class="fas fa-file-invoice-dollar mr-1"></i> Ledger
                                    </a>
                                @endif

                                <button type="button" class="btn btn-warning shadow-sm mb-2" data-toggle="modal"
                                    data-target="#applyConcessionModal">
                                    <i class="fas fa-percent mr-1"></i>
                                </button>

                                {{-- Tertiary Actions (Icons) --}}
                                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-light border mb-2"
                                    title="Edit Profile">
                                    <i class="fas fa-pen text-gray-600"></i>
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    {{-- Enhanced Statistics Cards with Concession Support --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Attendance ({{ $attendanceData['month_name'] }})
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="headerAttendancePercentage">
                                {{ $attendanceData['attendance_percentage'] }}%
                            </div>

                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-3x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Fees</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800 amount-counter">
                                ₹{{ number_format(isset($financialSummary['total_amount']) ? $financialSummary['total_amount'] : 0, 0) }}
                            </div>
                            <div class="text-xs text-muted">
                                {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}%
                                completed
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rupee-sign fa-3x text-info opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Paid Amount</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800 amount-counter">
                                ₹{{ number_format(isset($financialSummary['paid_amount']) ? $financialSummary['paid_amount'] : 0, 0) }}
                            </div>
                            @if(isset($financialSummary['concession_amount']) && $financialSummary['concession_amount'] > 0)
                                <div class="text-xs text-success">
                                    <i
                                        class="fas fa-percent mr-1"></i>₹{{ number_format($financialSummary['concession_amount'], 0) }}
                                    concession
                                </div>
                            @endif
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-3x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div
                class="card border-left-{{ isset($financialSummary['remaining_amount']) && $financialSummary['remaining_amount'] > 0 ? 'danger' : 'success' }} shadow h-100 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div
                                class="text-xs font-weight-bold text-{{ isset($financialSummary['remaining_amount']) && $financialSummary['remaining_amount'] > 0 ? 'danger' : 'success' }} text-uppercase mb-1">
                                Outstanding Due
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800 amount-counter">
                                ₹{{ number_format(isset($financialSummary['remaining_amount']) ? $financialSummary['remaining_amount'] : 0, 0) }}
                            </div>
                            @if(isset($financialSummary['remaining_amount']) && $financialSummary['remaining_amount'] <= 0)
                                <div class="text-xs text-success">
                                    <i class="fas fa-check mr-1"></i>Fully settled
                                </div>
                            @endif
                        </div>
                        <div class="col-auto">
                            <i
                                class="fas fa-{{ isset($financialSummary['remaining_amount']) && $financialSummary['remaining_amount'] > 0 ? 'exclamation-triangle' : 'check-circle' }} fa-3x text-{{ isset($financialSummary['remaining_amount']) && $financialSummary['remaining_amount'] > 0 ? 'danger' : 'success' }} opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================================================================
                 Enhanced Financial Summary Card (add this after the statistics cards)
                 ============================================================================= -->

    {{-- Detailed Financial Breakdown Card --}}
            @if(isset($financialSummary['total_amount']) && $financialSummary['total_amount'] > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card modern-card">
                            <div class="card-header bg-white">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-bar mr-2"></i> Financial Summary Breakdown
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="font-weight-bold">Fee Settlement Progress</small>
                                                <small
                                                    class="text-muted">{{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}%
                                                    completed</small>
                                            </div>
                                            <div class="progress mb-2" style="height: 20px;">
                                                @if(isset($financialSummary['paid_amount']) && $financialSummary['paid_amount'] > 0)
                                                    <div class="progress-bar bg-success" role="progressbar"
                                                        style="width: {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}%"
                                                        title="Paid: ₹{{ number_format($financialSummary['paid_amount'], 0) }}">
                                                        {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}%
                                                    </div>
                                                @endif
                                                @if(isset($financialSummary['concession_amount']) && $financialSummary['concession_amount'] > 0)
                                                    @php
        $concessionPercentage = isset($financialSummary['total_amount']) && $financialSummary['total_amount'] > 0 ? round(($financialSummary['concession_amount'] / $financialSummary['total_amount']) * 100, 1) : 0;
                                                    @endphp
                                                    <div class="progress-bar bg-warning" role="progressbar"
                                                        style="width: {{ $concessionPercentage }}%"
                                                        title="Concession: ₹{{ number_format($financialSummary['concession_amount'], 0) }}">
                                                        {{ $concessionPercentage }}%
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="d-flex justify-content-between small text-muted">
                                                <span><i class="fas fa-square text-success mr-1"></i>Paid</span>
                                                <span><i class="fas fa-square text-warning mr-1"></i>Concession</span>
                                                <span><i class="fas fa-square text-light mr-1"></i>Outstanding</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="p-2 rounded bg-light">
                                                    <div class="text-success font-weight-bold">
                                                        ₹{{ number_format(isset($financialSummary['paid_amount']) ? $financialSummary['paid_amount'] : 0, 0) }}
                                                    </div>
                                                    <div class="small text-muted">Paid</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 rounded bg-light">
                                                    <div class="text-warning font-weight-bold">
                                                        ₹{{ number_format(isset($financialSummary['concession_amount']) ? $financialSummary['concession_amount'] : 0, 0) }}
                                                    </div>
                                                    <div class="small text-muted">Concession</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 rounded bg-light">
                                                    <div
                                                        class="text-{{ isset($financialSummary['remaining_amount']) && $financialSummary['remaining_amount'] > 0 ? 'danger' : 'success' }} font-weight-bold">
                                                        ₹{{ number_format(isset($financialSummary['remaining_amount']) ? $financialSummary['remaining_amount'] : 0, 0) }}
                                                    </div>
                                                    <div class="small text-muted">Due</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main Content with Organized Tabs --}}
            <div class="row">
                <div class="col-lg-8">
                    <div class="card modern-card">
                        <div class="card-header bg-white py-3">
                            <ul class="nav nav-tabs nav-tabs-modern" id="profileTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab">
                                        <i class="fas fa-tachometer-alt mr-2"></i> Overview
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="fees-tab" data-toggle="tab" href="#fees" role="tab">
                                        <i class="fas fa-money-bill-wave mr-2"></i> Fee Components
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="payments-tab" data-toggle="tab" href="#payments" role="tab">
                                        <i class="fas fa-credit-card mr-2"></i> Payment History
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                                        <i class="fas fa-calendar-check mr-2"></i> Attendance
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="profileTabContent">
                                @include('admin.students.partials.tabs.overview')
                                @include('admin.students.partials.tabs.fees')
                                @include('admin.students.partials.tabs.payments')
                                @include('admin.students.partials.tabs.attendance')
                            </div>
                        </div>
                    </div>
                    <br>
                    {{-- Recent Activity Timeline (Optional) --}}
                    @if($recentActivity->count() > 0)
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-history mr-2"></i>Recent Activity Timeline
                                </h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item activity-filter" href="#" data-type="all">
                                            <i class="fas fa-list mr-2"></i>All Activities
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item activity-filter" href="#" data-type="payment">
                                            <i class="fas fa-money-bill-wave mr-2"></i>Payments Only
                                        </a>
                                        <a class="dropdown-item activity-filter" href="#" data-type="concession">
                                            <i class="fas fa-percent mr-2"></i>Concessions Only
                                        </a>
                                        <a class="dropdown-item activity-filter" href="#" data-type="attendance">
                                            <i class="fas fa-user-check mr-2"></i>Attendance Only
                                        </a>
                                        <a class="dropdown-item activity-filter" href="#" data-type="spatie_log">
                                            <i class="fas fa-cogs mr-2"></i>System Changes
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline" id="activityTimeline" style="max-height: 500px; overflow-y: auto;">
                                    @foreach($recentActivity as $activity)
                                        <div class="timeline-item" data-type="{{ $activity['type'] ?? 'general' }}">
                                            <div class="timeline-marker bg-{{ $activity['color'] ?? 'primary' }}">
                                                <i class="fas {{ $activity['icon'] ?? 'fa-info-circle' }}"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="timeline-header">
                                                        <h6 class="mb-1 font-weight-bold">{{ $activity['title'] ?? 'Activity' }}</h6>
                                                        <p class="text-muted mb-1">
                                                            {{ $activity['description'] ?? 'No description available' }}</p>
                                                    </div>
                                                    <div class="timeline-meta text-right">
                                                        <small
                                                            class="text-muted d-block">{{ $activity['timestamp']->format('M d, Y') }}</small>
                                                        <small class="text-muted">{{ $activity['timestamp']->format('h:i A') }}</small>
                                                    </div>
                                                </div>

                                                @if(!empty($activity['properties'] ?? []))
                                                    <div class="timeline-details">
                                                        <button class="btn btn-sm btn-outline-secondary toggle-details" type="button"
                                                            data-toggle="collapse" data-target="#details-{{ $loop->index }}">
                                                            <i class="fas fa-chevron-down"></i> Details
                                                        </button>
                                                        <div class="collapse mt-2" id="details-{{ $loop->index }}">
                                                            <div class="card card-body bg-light">
                                                                @if(($activity['type'] ?? '') === 'payment')
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <small><strong>Amount:</strong>
                                                                                ₹{{ number_format($activity['properties']['amount'] ?? 0, 2) }}</small><br>
                                                                            <small><strong>Method:</strong>
                                                                                {{ ucfirst($activity['properties']['method'] ?? 'Unknown') }}</small>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <small><strong>Receipt:</strong>
                                                                                {{ $activity['properties']['receipt'] ?? 'N/A' }}</small><br>
                                                                            <small><strong>Components:</strong>
                                                                                {{ $activity['properties']['components'] ?? 0 }} items</small>
                                                                        </div>
                                                                    </div>
                                                                @elseif(($activity['type'] ?? '') === 'concession')
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <small><strong>Amount:</strong>
                                                                                ₹{{ number_format($activity['properties']['amount'] ?? 0, 2) }}</small><br>
                                                                            <small><strong>Status:</strong>
                                                                                {{ ucfirst($activity['properties']['status'] ?? 'Unknown') }}</small>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            @if(!empty($activity['properties']['reason'] ?? null))
                                                                                <small><strong>Reason:</strong>
                                                                                    {{ $activity['properties']['reason'] }}</small>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div class="properties-list">
                                                                        @foreach(($activity['properties'] ?? []) as $key => $value)
                                                                            @if(!is_array($value))
                                                                                <small><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                                                    {{ $value }}</small><br>
                                                                            @endif
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="timeline-footer mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user mr-1"></i>{{ $activity['user'] ?? 'System' }}
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-clock mr-1"></i>{{ $activity['timestamp']->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Load More Button --}}

                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right Sidebar - Quick Actions --}}
                <div class="col-lg-4">
                    {{-- Financial Summary Card --}}
                    <div class="card modern-card mb-4">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-chart-pie mr-2"></i> Financial Summary
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div class="h4 font-weight-bold text-gray-800 amount-counter">
                                    ₹{{ number_format(isset($financialSummary['total_amount']) ? $financialSummary['total_amount'] : 0, 0) }}
                                </div>
                                <small class="text-muted">Total Fee Amount</small>
                            </div>

                            <div class="progress mb-3" style="height: 15px;">
                                <div class="progress-bar 
                                    @if(isset($financialSummary['payment_percentage']) && $financialSummary['payment_percentage'] >= 75) bg-success 
                                    @elseif(isset($financialSummary['payment_percentage']) && $financialSummary['payment_percentage'] >= 50) bg-warning 
                                    @else bg-danger @endif" role="progressbar"
                                    style="width: {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}%">
                                    {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}%
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light">
                                        <div class="text-success font-weight-bold">
                                            ₹{{ number_format(isset($financialSummary['paid_amount']) ? $financialSummary['paid_amount'] : 0, 0) }}
                                        </div>
                                        <div class="small text-muted">Paid</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light">
                                        <div class="text-danger font-weight-bold">
                                            ₹{{ number_format(isset($financialSummary['remaining_amount']) ? $financialSummary['remaining_amount'] : 0, 0) }}
                                        </div>
                                        <div class="small text-muted">Due</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions Card --}}
                    <div class="card modern-card">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-bolt mr-2"></i> Quick Actions
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="quick-action-card" onclick="openPaymentModal()">
                                <div class="d-flex align-items-center">
                                    <div class="quick-action-icon bg-success text-white">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">Record Payment</div>
                                        <small class="text-muted">Add new payment entry</small>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-action-card" data-toggle="modal" data-target="#applyConcessionModal">
                                <div class="d-flex align-items-center">
                                    <div class="quick-action-icon bg-warning text-white">
                                        <i class="fas fa-percent"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">Apply Concession</div>
                                        <small class="text-muted">
                                            Discount fee components
                                            @if(($student->gender ?? null) === 'Female' && setting('womens_discount_percentage', 0) > 0)
                                                <br><span class="badge badge-success mt-1">
                                                    <i class="fas fa-female"></i> {{ setting('womens_discount_percentage') }}% Eligible
                                                </span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-action-card"
                                onclick="window.location.href='{{ route('admin.students.edit', $student) }}'">
                                <div class="d-flex align-items-center">
                                    <div class="quick-action-icon bg-primary text-white">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">Edit Profile</div>
                                        <small class="text-muted">Update student information</small>
                                    </div>
                                </div>
                            </div>

                            @if(Route::has('admin.payments.component-dashboard'))
                                <div class="quick-action-card"
                                    onclick="window.location.href='{{ route('admin.payments.component-dashboard', $student) }}'">
                                    <div class="d-flex align-items-center">
                                        <div class="quick-action-icon bg-info text-white">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">Fee Dashboard</div>
                                            <small class="text-muted">Detailed fee management</small>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="quick-action-card" onclick="window.print()">
                                <div class="d-flex align-items-center">
                                    <div class="quick-action-icon bg-secondary text-white">
                                        <i class="fas fa-print"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">Print Profile</div>
                                        <small class="text-muted">Generate printable version</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- /col-lg-4 -->
            </div> <!-- /row -->
            @include('admin.students.partials.modals.payment-filter')
            @include('admin.students.partials.modals.add-fee-component')
            @include('admin.students.partials.modals.apply-concession')
            @include('admin.students.partials.modals.payment')
@endsection
@include('admin.students.partials.styles-timeline')
@include('admin.students.partials.scripts')
