@extends('layouts.theme')

@section('title', 'Student Profile: ' . $student->name)
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"
        style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 10px;">
        <i class="fas fa-check-circle mr-2"></i>
        <strong>Success!</strong><br>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
@endif
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
// 1. Determine Photo URL with robust fallback
$photoUrl = null;

if ($student->photo) {
    $photoPath = $student->photo;

    // Try multiple methods to find the photo
    if (\Storage::disk('public')->exists($photoPath)) {
        $photoUrl = asset('storage/' . $photoPath);
    } elseif (!str_contains($photoPath, '/')) {
        // Check if it needs student_photos prefix
        $prefixedPath = 'student_photos/' . $photoPath;
        if (\Storage::disk('public')->exists($prefixedPath)) {
            $photoUrl = asset('storage/' . $prefixedPath);
        }
    } else {
        // Direct filesystem check
        $fullPath = storage_path('app/public/' . $photoPath);
        if (file_exists($fullPath)) {
            $photoUrl = asset('storage/' . $photoPath);
        } else {
            // Check with student_photos prefix on filesystem
            $fullPathWithPrefix = storage_path('app/public/student_photos/' . basename($photoPath));
            if (file_exists($fullPathWithPrefix)) {
                $photoUrl = asset('storage/student_photos/' . basename($photoPath));
            }
        }
    }
}

// 2. Fallback to UI Avatars if photo not found
if (!$photoUrl) {
    $name = urlencode($student->name);
    $photoUrl = "https://ui-avatars.com/api/?name={$name}&background=random&color=fff&size=200&font-size=0.33&bold=true";
}

// 3. Status Color Logic
$statusColor = match ($student->status) {
    'active' => '#1cc88a', // Green
    'graduated' => '#36b9cc', // Info Blue
    'dropped' => '#e74a3b', // Red
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
                                <button onclick="openPaymentModal()"
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

                                <button class="btn btn-warning shadow-sm mb-2" data-toggle="modal"
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



            @include('admin.students.partials.modals.payment-filter')
            @include('admin.students.partials.modals.add-fee-component')
            @include('admin.students.partials.modals.apply-concession')
            @include('admin.students.partials.modals.payment')
@endsection
@include('admin.students.partials.styles-timeline')
@include('admin.students.partials.scripts')
