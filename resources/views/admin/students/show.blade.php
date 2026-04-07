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
@push('styles')
    <style>
        /* Modern Card Styling */
        .modern-card {
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .modern-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        /* Modern Profile Header Styles */
        .profile-cover {
            height: 130px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }

        .profile-cover::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.1), transparent);
        }

        .student-avatar-container {
            margin-top: -65px;
            position: relative;
            display: inline-block;
        }

        .student-avatar-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid #ffffff;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .status-indicator {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid #fff;
        }

        .meta-pill {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.85rem;
            color: #5a5c69;
            display: inline-flex;
            align-items: center;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .meta-pill i {
            margin-right: 6px;
            opacity: 0.7;
        }

        .biometric-badge {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        /* Enhanced Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            border-radius: 8px 8px 0 0;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: rotate(45deg);
        }

        .profile-content {
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        /* Action Button Enhancements */
        .action-btn {
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin: 0.25rem;
            text-decoration: none;
            display: inline-block;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success-modern {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #2d5a27;
        }

        .btn-info-modern {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-warning-modern {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #8b4513;
        }

        .btn-secondary-modern {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #495057;
        }

        /* Statistics Cards */
        .stat-card {
            border-left: 4px solid;
            border-radius: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
        }

        .stat-card.border-left-primary {
            border-left-color: #4e73df;
        }

        .stat-card.border-left-success {
            border-left-color: #1cc88a;
        }

        .stat-card.border-left-danger {
            border-left-color: #e74a3b;
        }

        .stat-card.border-left-info {
            border-left-color: #36b9cc;
        }

        /* Navigation Tabs */
        .nav-tabs-modern {
            border-bottom: 3px solid #e9ecef;
            margin-bottom: 2rem;
        }

        .nav-tabs-modern .nav-link {
            border: none;
            border-radius: 8px 8px 0 0;
            padding: 15px 25px;
            font-weight: 600;
            background: #f8f9fc;
            color: #858796;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .nav-tabs-modern .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .nav-tabs-modern .nav-link:hover:not(.active) {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        /* Fee Component Cards */
        .fee-component-card {
            border-radius: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            background: white;
        }

        .fee-component-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .fee-component-header {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border-radius: 12px 12px 0 0;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .payment-progress {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
        }

        .payment-progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5em 1em;
            font-size: .8em;
            font-weight: 700;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.paid {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #2d5a27;
        }

        .status-badge.unpaid {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #8b4513;
        }

        .status-badge.partial {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #8b4513;
        }

        /* Quick Action Cards */
        .quick-action-card {
            border-radius: 15px;
            border: 1px solid #e9ecef;
            background: white;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quick-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #4e73df;
        }

        .quick-action-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        /* Payment Modal Styling */
        .payment-modal .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .payment-modal .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }

        .payment-modal .close {
            color: white;
            opacity: 0.8;
        }

        .payment-modal .close:hover {
            opacity: 1;
        }

        /* Payment Form Styling */
        .payment-form-section {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .payment-component-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .payment-component-item.selected {
            border-color: #4e73df;
            background: #f1f3ff;
        }

        .payment-summary {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1.5rem;
            border: 2px solid #dee2e6;
        }

        /* Calendar Styling */
        .calendar-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .calendar-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-table td {
            height: 80px;
            vertical-align: middle;
            text-align: center;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .calendar-table td:hover {
            background: #f8f9fc;
            transform: scale(1.05);
        }

        /* Modern Calendar Grid Styling */
        .comprehensive-calendar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .calendar-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }

        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .calendar-grid {
            padding: 1rem;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e9ecef;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
        }

        .weekday {
            background: #f8f9fc;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e9ecef;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }

        .calendar-day {
            background: white;
            min-height: 80px;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            transition: all 0.3s ease;
            position: relative;
        }

        .calendar-day:hover:not(.empty) {
            background: #f8f9fc;
            transform: scale(1.02);
        }

        .calendar-day.empty {
            background: #f8f9fc;
            opacity: 0.5;
        }

        .calendar-day .day-number {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }

        .calendar-day .attendance-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-day .attendance-status.present {
            background: #d4edda;
            color: #155724;
        }

        .calendar-day .attendance-status.absent {
            background: #f8d7da;
            color: #721c24;
        }

        .calendar-day .attendance-status.late {
            background: #fff3cd;
            color: #856404;
        }

        .calendar-day .attendance-status.excused {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Monthly Summary Styling */
        .monthly-summary {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .summary-stat {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .summary-stat .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #495057;
        }

        .summary-stat .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-header {
                padding: 1.5rem 1rem;
            }

            .action-btn {
                display: block;
                width: 100%;
                margin: 0.25rem 0;
            }

            .stat-card {
                margin-bottom: 1rem;
            }

            .fee-component-card {
                margin-bottom: 0.5rem;
            }

            /* Mobile Calendar Adjustments */
            .calendar-grid {
                padding: 0.5rem;
            }

            .weekday {
                padding: 0.5rem;
                font-size: 0.75rem;
            }

            .calendar-day {
                min-height: 60px;
                padding: 0.25rem;
            }

            .calendar-day .day-number {
                font-size: 0.875rem;
            }

            .calendar-day .attendance-status {
                font-size: 0.625rem;
                padding: 0.125rem 0.25rem;
            }

            .summary-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .calendar-header {
                padding: 1rem;
            }

            .calendar-legend {
                gap: 0.5rem;
            }

            .legend-item {
                font-size: 0.75rem;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.5;
            pointer-events: none;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4e73df;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Payment Summary Animations */
        .amount-counter {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
    </style>
@endpush

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
                                {{-- Overview Tab --}}
                                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                    <div class="row">
                                        {{-- Personal Information Column --}}
                                        <div class="col-md-6">
                                            <div class="card shadow-sm mb-4 border-left-primary h-100">
                                                <div class="card-header bg-white py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary">
                                                        <i class="fas fa-user-circle mr-2"></i> Personal Details
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-borderless table-sm">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="font-weight-bold w-40">
                                                                        <i class="fas fa-venus-mars text-muted mr-2"></i>Gender:
                                                                    </td>
                                                                    <td>{{ $student->gender ?? 'Not specified' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-calendar-alt text-muted mr-2"></i>Date of Birth:
                                                                    </td>
                                                                    <td>
                                                                        {{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M, Y') : 'Not recorded' }}
                                                                    </td>
                                                                </tr>
                                                                @if($student->age)
                                                                    <tr>
                                                                        <td class="font-weight-bold">
                                                                            <i class="fas fa-hourglass-half text-muted mr-2"></i>Age:
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge badge-info">{{ $student->age }}</span>
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-user-tie text-muted mr-2"></i>Father's Name:
                                                                    </td>
                                                                    <td>{{ $student->father_name ?? 'Not provided' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-phone text-muted mr-2"></i>Mobile:
                                                                    </td>
                                                                    <td>{{ $student->student_mobile ?? 'Not provided' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-phone-alt text-muted mr-2"></i>Father's Mobile:
                                                                    </td>
                                                                    <td>{{ $student->father_mobile ?? 'Not provided' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-map-marker-alt text-muted mr-2"></i>Address:
                                                                    </td>
                                                                    <td>
                                                                        {{ $student->admission->address ?? $student->village ?? 'Not provided' }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Academic & Source Information Column --}}
                                        <div class="col-md-6">
                                            {{-- Academic Info --}}
                                            <div class="card shadow-sm mb-4 border-left-info">
                                                <div class="card-header bg-white py-3">
                                                    <h6 class="m-0 font-weight-bold text-info">
                                                        <i class="fas fa-graduation-cap mr-2"></i> Academic Details
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-borderless table-sm">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="font-weight-bold w-40">
                                                                        <i class="fas fa-book text-muted mr-2"></i>Course:
                                                                    </td>
                                                                    <td>{{ optional($student->batch)->course->name ?? 'Not assigned' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-chalkboard-teacher text-muted mr-2"></i>Batch:
                                                                    </td>
                                                                    <td>{{ optional($student->batch)->name ?? 'Not assigned' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-calendar-check text-muted mr-2"></i>Admission Date:
                                                                    </td>
                                                                    <td>
                                                                        {{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d M, Y') : 'Not recorded' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-info-circle text-muted mr-2"></i>Status:
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduated' ? 'info' : 'danger') }}">
                                                                            {{ ucfirst($student->status) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Source Info --}}
                                            <div class="card shadow-sm mb-4 border-left-warning">
                                                <div class="card-header bg-white py-3">
                                                    <h6 class="m-0 font-weight-bold text-warning">
                                                        <i class="fas fa-bullhorn mr-2"></i> Source Information
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-borderless table-sm">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="font-weight-bold w-40">
                                                                        <i class="fas fa-share-alt text-muted mr-2"></i>Source:
                                                                    </td>
                                                                    <td>
                                                                        @if($student->source ?? null)
                                                                            <span class="badge badge-primary px-2 py-1">{{ ucfirst($student->source) }}</span>
                                                                        @else
                                                                            <span class="text-muted">Not provided</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-user-tag text-muted mr-2"></i>Referral Name:
                                                                    </td>
                                                                    <td>
                                                                        @if($student->referral_name ?? null)
                                                                            <span class="font-weight-bold text-dark">
                                                                                {{ $student->referral_name }}
                                                                            </span>
                                                                            @if($student->referrer)
                                                                                <div class="small text-muted mt-1">
                                                                                    <i class="fas fa-id-badge mr-1"></i> {{ $student->referrer->enrollment_number }}
                                                                                    <span class="mx-1">|</span>
                                                                                    <i class="fas fa-layer-group mr-1"></i> {{ optional($student->referrer->batch)->name ?? 'No Batch' }}
                                                                                </div>
                                                                            @endif
                                                                        @else
                                                                            <span class="text-muted">Not provided</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                {{-- Fee Components Tab --}}
                                <div class="tab-pane fade" id="fees" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0 text-primary">
                                            <i class="fas fa-file-invoice-dollar mr-2"></i> Fee Components
                                        </h5>
                                        <div class="btn-group">
                                            <button class="action-btn btn-success-modern btn-sm" onclick="openPaymentModal()">
                                                <i class="fas fa-plus mr-2"></i> Record Payment
                                            </button>
                                            <button class="btn btn-primary btn-sm" data-toggle="modal"
                                                data-target="#addFeeComponentModal">
                                                <i class="fas fa-plus-circle mr-2"></i> Add Fee Component
                                            </button>
                                        </div>
                                    </div>

                                    @if($studentFees->count() > 0)
                                        @foreach($studentFees as $studentFee)
                                            @php
        $paidAmount = $studentFee->paid_amount ?? 0;
        $concessionAmount = $studentFee->concession_amount ?? 0;
        $totalAmount = $studentFee->amount ?? 0;
        $remainingAmount = $totalAmount - $paidAmount - $concessionAmount;

        $paymentPercentage = ($totalAmount > 0) ?
            round((($paidAmount + $concessionAmount) / $totalAmount) * 100, 1) : 0;

        if ($remainingAmount <= 0) {
            $statusClass = 'success';
            $statusText = 'Fully Paid';
            $progressClass = 'bg-success';
        } elseif ($paidAmount > 0 || $concessionAmount > 0) {
            $statusClass = 'warning';
            $statusText = 'Partially Paid';
            $progressClass = 'bg-warning';
        } else {
            $statusClass = 'danger';
            $statusText = 'Unpaid';
            $progressClass = 'bg-danger';
        }
                                            @endphp

                                            <div class="fee-component-card">
                                                <div class="fee-component-header">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6">
                                                            <h6 class="font-weight-bold mb-1">
                                                                <i class="fas fa-file-invoice mr-2 text-primary"></i>
                                                                {{ optional($studentFee->feeCategory)->name ?? 'Unknown Category' }}
                                                            </h6>
                                                            <small
                                                                class="text-muted">{{ optional($studentFee->feeCategory)->description ?? 'Standard fee component' }}</small>
                                                        </div>
                                                        <div class="col-md-3 text-center">
                                                            <div class="h5 font-weight-bold text-primary">
                                                                ₹{{ number_format($totalAmount, 0) }}</div>
                                                            <small class="text-muted">Total Amount</small>
                                                        </div>
                                                        <div class="col-md-3 text-center">
                                                            <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                                                            @if($concessionAmount > 0)
                                                                <br><small class="text-success mt-1">
                                                                    <i class="fas fa-percent"></i> ₹{{ number_format($concessionAmount, 0) }}
                                                                    concession
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <div class="mb-3">
                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                    <small class="font-weight-bold">Payment Progress</small>
                                                                    <small class="text-muted">{{ $paymentPercentage }}% completed</small>
                                                                </div>
                                                                <div class="payment-progress">
                                                                    <div class="payment-progress-bar {{ $progressClass }}"
                                                                        style="width: {{ $paymentPercentage }}%"></div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <small class="text-muted d-block">Paid</small>
                                                                    <span
                                                                        class="font-weight-bold text-success">₹{{ number_format($paidAmount, 0) }}</span>
                                                                </div>
                                                                <div class="col-4">
                                                                    <small class="text-muted d-block">Concession</small>
                                                                    <span
                                                                        class="font-weight-bold text-info">₹{{ number_format($concessionAmount, 0) }}</span>
                                                                </div>
                                                                <div class="col-4">
                                                                    <small class="text-muted d-block">Due</small>
                                                                    <span
                                                                        class="font-weight-bold text-danger">₹{{ number_format(max(0, $remainingAmount), 0) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 text-right">
                                                            @if($remainingAmount > 0)
                                                                <button class="btn btn-success btn-sm mb-2"
                                                                    onclick="openPaymentModal({{ $studentFee->id }}, '{{ optional($studentFee->feeCategory)->name ?? 'Unknown' }}', {{ $remainingAmount }})">
                                                                    <i class="fas fa-credit-card mr-1"></i> Pay
                                                                    ₹{{ number_format($remainingAmount, 0) }}
                                                                </button>
                                                                <br>
                                                                <button class="btn btn-warning btn-sm"
                                                                    onclick="openConcessionModal({{ $studentFee->id }}, '{{ optional($studentFee->feeCategory)->name ?? 'Unknown' }}', {{ $remainingAmount }})">
                                                                    <i class="fas fa-percent mr-1"></i> Apply Concession
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No Fee Components Assigned</h6>
                                            <p class="text-muted">This student doesn't have any fee components assigned yet.</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Payment History Tab --}}
                                <div class="tab-pane fade" id="payments" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0 text-primary">
                                            <i class="fas fa-credit-card mr-2"></i> Payment History
                                        </h5>
                                        <div class="btn-group">
                                            <button class="action-btn btn-success-modern btn-sm" onclick="openPaymentModal()">
                                                <i class="fas fa-plus mr-2"></i> Record Payment
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" onclick="refreshPaymentHistory()">
                                                <i class="fas fa-sync"></i> Refresh
                                            </button>
                                        </div>
                                    </div>

                                    @if($paymentHistory->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="payment-history-table">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th><i class="fas fa-receipt mr-2"></i>Receipt #</th>
                                                        <th><i class="fas fa-calendar mr-2"></i>Date</th>
                                                        <th><i class="fas fa-rupee-sign mr-2"></i>Amount</th>
                                                        <th><i class="fas fa-credit-card mr-2"></i>Method</th>
                                                        <th><i class="fas fa-list mr-2"></i>Components</th>
                                                        <th><i class="fas fa-user mr-2"></i>Created By</th>
                                                        <th><i class="fas fa-info-circle mr-2"></i>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($paymentHistory as $payment)
                                                        <tr data-payment-id="{{ $payment->id }}">
                                                            <td>
                                                                <strong
                                                                    class="text-primary">{{ $payment->receipt_number ?? 'N/A' }}</strong>
                                                                @if($payment->transaction_id ?? null)
                                                                    <small class="text-muted d-block">TXN:
                                                                        {{ $payment->transaction_id }}</small>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="font-weight-bold">{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : 'N/A' }}</span>
                                                                <small
                                                                    class="text-muted d-block">{{ $payment->created_at ? $payment->created_at->format('H:i A') : '' }}</small>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-success badge-lg"
                                                                    style="font-size: 0.9em; padding: 0.5em 0.8em;">
                                                                    ₹{{ number_format($payment->amount ?? 0, 2) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-info">
                                                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'Unknown')) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->componentItems) && $payment->componentItems->count() > 0)
                                                                    <div class="component-breakdown" style="max-width: 200px;">
                                                                        @foreach($payment->componentItems as $item)
                                                                            <small class="d-block" style="line-height: 1.3; margin-bottom: 2px;">
                                                                                <strong>{{ optional($item->studentFee)->feeCategory->name ?? 'Unknown' }}:</strong>
                                                                                ₹{{ number_format($item->amount_paid ?? 0, 2) }}
                                                                            </small>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <span class="text-muted">No components</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->createdBy) && $payment->createdBy)
                                                                    <div class="user-info" style="min-width: 120px;">
                                                                        <strong>{{ $payment->createdBy->name }}</strong>
                                                                        <small class="text-muted d-block">
                                                                            {{ $payment->created_at ? $payment->created_at->diffForHumans() : 'Unknown time' }}
                                                                        </small>
                                                                    </div>
                                                                @else
                                                                    <span class="text-muted">System</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @php
        $statusClass = match ($payment->status ?? 'completed') {
            'completed' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'refunded' => 'info',
            default => 'secondary'
        };
                                                                @endphp
                                                                <span class="badge badge-{{ $statusClass }}">
                                                                    {{ ucfirst($payment->status ?? 'completed') }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    {{-- Edit Button --}}
                                                                    @can('edit payments')
                                                                        @if(method_exists($payment, 'canBeEdited') && $payment->canBeEdited())
                                                                            <a href="{{ route('payment-edit.edit', $payment) }}"
                                                                                class="btn btn-outline-warning btn-sm" title="Edit Payment">
                                                                                <i class="fas fa-edit"></i>
                                                                            </a>
                                                                        @elseif(!method_exists($payment, 'canBeEdited'))
                                                                            <a href="{{ route('payment-edit.edit', $payment) }}"
                                                                                class="btn btn-outline-warning btn-sm" title="Edit Payment">
                                                                                <i class="fas fa-edit"></i>
                                                                            </a>
                                                                        @endif
                                                                    @endcan

                                                                    {{-- Receipt View Button --}}
                                                                    @if($payment->receipt_number ?? null)
                                                                        @if(Route::has('admin.payments.receipt'))
                                                                            <a href="{{ route('admin.payments.receipt', [$student, $payment]) }}"
                                                                                class="btn btn-outline-primary btn-sm" title="View Receipt"
                                                                                target="_blank">
                                                                                <i class="fas fa-receipt"></i>
                                                                            </a>
                                                                        @endif
                                                                    @endif

                                                                    {{-- PDF Download Button --}}
                                                                    @if($payment->receipt_number ?? null)
                                                                        @if(Route::has('admin.payments.receipt.pdf'))
                                                                            <a href="{{ route('admin.payments.receipt.pdf', [$student, $payment]) }}"
                                                                                class="btn btn-outline-success btn-sm" title="Download PDF">
                                                                                <i class="fas fa-download"></i>
                                                                            </a>
                                                                        @endif
                                                                    @endif

                                                                    {{-- View Details Button --}}
                                                                    <button class="btn btn-outline-secondary btn-sm"
                                                                        onclick="viewPaymentDetails({{ $payment->id }})"
                                                                        title="View Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>

                                                                    {{-- Edit History Button --}}
                                                                    @can('view payment history')
                                                                        <a href="{{ route('payment-edit.history', $payment) }}"
                                                                            class="btn btn-outline-info btn-sm" title="View Edit History">
                                                                            <i class="fas fa-history"></i>
                                                                        </a>
                                                                    @endcan

                                                                    {{-- Notes Button --}}
                                                                    @if($payment->notes ?? null)
                                                                        <button class="btn btn-outline-secondary btn-sm"
                                                                            title="{{ $payment->notes }}" data-toggle="tooltip"
                                                                            data-placement="top" data-html="true"
                                                                            data-original-title="{{ $payment->notes }}">
                                                                            <i class="fas fa-sticky-note"></i>
                                                                        </button>
                                                                    @endif


                                                                    {{-- Manual Webhook Trigger --}}
                                                                    <form action="{{ route('admin.payments.webhook', $payment->id) }}" method="POST" class="d-inline"
                                                                        onsubmit="return confirm('Are you sure you want to resend the webhook for this payment?');">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-outline-dark btn-sm" title="Send Payment Webhook">
                                                                            <i class="fas fa-satellite-dish"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Payment Summary Card --}}
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-3 text-center">
                                                                <h6 class="text-muted">Total Payments</h6>
                                                                <h4 class="text-primary">{{ $paymentHistory->count() }}</h4>
                                                            </div>
                                                            <div class="col-md-3 text-center">
                                                                <h6 class="text-muted">Total Paid</h6>
                                                                <h4 class="text-success">
                                                                    ₹{{ number_format($paymentHistory->sum('amount'), 2) }}</h4>
                                                            </div>
                                                            <div class="col-md-3 text-center">
                                                                <h6 class="text-muted">Last Payment</h6>
                                                                <h4 class="text-info">
                                                                    {{ $paymentHistory->first() ? $paymentHistory->first()->payment_date->format('d M Y') : 'Never' }}
                                                                </h4>
                                                            </div>
                                                            <div class="col-md-3 text-center">
                                                                <h6 class="text-muted">Payment Methods</h6>
                                                                <div class="method-badges">
                                                                    @foreach($paymentHistory->pluck('payment_method')->unique() as $method)
                                                                        <span class="badge badge-secondary"
                                                                            style="margin: 2px; font-size: 0.7em;">{{ ucfirst($method ?? 'Unknown') }}</span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Quick Actions for Payments --}}
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="card border-0">
                                                    <div class="card-body bg-light rounded">
                                                        <h6 class="text-muted mb-3">
                                                            <i class="fas fa-tools mr-2"></i>Quick Actions
                                                        </h6>
                                                        <div class="btn-group flex-wrap" role="group">
                                                            <button class="btn btn-outline-primary btn-sm"
                                                                onclick="exportPaymentHistory()">
                                                                <i class="fas fa-download mr-1"></i> Export History
                                                            </button>
                                                            <button class="btn btn-outline-info btn-sm" onclick="printPaymentHistory()">
                                                                <i class="fas fa-print mr-1"></i> Print History
                                                            </button>
                                                            <button class="btn btn-outline-secondary btn-sm" onclick="filterPayments()">
                                                                <i class="fas fa-filter mr-1"></i> Filter Payments
                                                            </button>
                                                            @if(Route::has('admin.payments.component-dashboard'))
                                                                <a href="{{ route('admin.payments.component-dashboard', $student) }}"
                                                                    class="btn btn-outline-success btn-sm">
                                                                    <i class="fas fa-chart-bar mr-1"></i> Payment Dashboard
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No Payment History</h6>
                                            <p class="text-muted">No payments have been recorded for this student yet.</p>
                                            <button class="action-btn btn-success-modern" onclick="openPaymentModal()">
                                                <i class="fas fa-plus mr-2"></i> Record First Payment
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                {{-- Enhanced Attendance Tab --}}
                                <div class="tab-pane fade" id="attendance" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-12">
                                            {{-- Month Selector --}}
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <h5 class="text-primary mb-0">
                                                    <i class="fas fa-calendar-check mr-2"></i>Attendance Overview
                                                </h5>
                                                <div class="form-group mb-0">
                                                    <select class="form-control" id="attendanceMonth"
                                                        onchange="loadAttendanceData()">
                                                        @for($i = 0; $i < 12; $i++)
                                                            @php
    $month = now()->subMonths($i);
                                                            @endphp
                                                            <option value="{{ $month->format('Y-m') }}" {{ $i === 0 ? 'selected' : '' }}>
                                                                {{ $month->format('F Y') }}
                                                            </option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Loading State --}}
                                            <div id="attendanceLoading" class="text-center py-5">
                                                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                                                <p class="text-muted">Loading attendance data...</p>
                                            </div>

                                            {{-- Attendance Content --}}
                                            <div id="attendanceContent" style="display: none;">

                                                {{-- Summary Cards --}}
                                                <div class="row mb-4">
                                                    {{-- Working Days Card (NEW) --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-primary shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                                            Working Days
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="totalWorkingDays">
                                                                            0
                                                                        </div>
                                                                        <small class="text-muted" style="font-size: 0.65rem;">(Till
                                                                            Date)</small>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Percentage Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-info shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                                            Attendance
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="tabAttendancePercentage">
                                                                            0%
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Present Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-success shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                                            Present
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="presentDays">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-check fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Absent Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-danger shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                                            Absent
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="absentDays">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-times fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Late Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-warning shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                                            Late
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="lateDays">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Holidays Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-secondary shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                                            Holidays
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="holidayDays">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-umbrella-beach fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Attendance Status Alert --}}
                                                <div class="alert" id="attendanceStatusAlert" style="display: none;">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-info-circle mr-2"></i>
                                                        <div>
                                                            <strong id="attendanceStatusTitle">Attendance Status</strong>
                                                            <div class="small" id="attendanceStatusMessage"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Monthly Calendar View --}}
                                                <div class="card shadow-sm">
                                                    <div class="card-header py-3">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-calendar mr-2"></i>
                                                            <span id="calendarTitle">Monthly Attendance</span>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div id="attendanceCalendar">
                                                            {{-- Calendar will be populated via JavaScript --}}
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Recent Records Table --}}
                                                <div class="card shadow-sm mt-4">
                                                    <div class="card-header py-3">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-history mr-2"></i>Recent Attendance Records
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm" id="attendanceRecordsTable">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Date</th>
                                                                        <th>Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    {{-- Records will be populated via JavaScript --}}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Error State --}}
                                            <div id="attendanceError" style="display: none;">
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    <strong>Error loading attendance data</strong>
                                                    <p class="mb-0 mt-2" id="attendanceErrorMessage"></p>
                                                    <button class="btn btn-sm btn-outline-danger mt-2"
                                                        onclick="loadAttendanceData()">
                                                        <i class="fas fa-redo mr-1"></i>Retry
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                </div>
            </div>



            {{-- Payment Filter Modal --}}
            <div class="modal fade" id="paymentFilterModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-filter mr-2"></i>Filter Payment History
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="paymentFilterForm">
                                <div class="form-group">
                                    <label>Payment Method</label>
                                    <select class="form-control" id="filterMethod">
                                        <option value="">All Methods</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="upi">UPI</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="online">Online</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date Range</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="date" class="form-control" id="filterStartDate" placeholder="Start Date">
                                        </div>
                                        <div class="col-6">
                                            <input type="date" class="form-control" id="filterEndDate" placeholder="End Date">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Amount Range</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="number" class="form-control" id="filterMinAmount" placeholder="Min Amount">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control" id="filterMaxAmount" placeholder="Max Amount">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Created By</label>
                                    <select class="form-control" id="filterCreatedBy">
                                        <option value="">All Users</option>
                                        @if($paymentHistory->count() > 0)
                                            @foreach($paymentHistory->pluck('createdBy.name')->unique()->filter() as $creator)
                                                <option value="{{ $creator }}">{{ $creator }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="applyPaymentFilter()">Apply Filter</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearPaymentFilter()">Clear</button>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Add Fee Component Modal --}}
            <div class="modal fade" id="addFeeComponentModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content" style="border-radius: 20px;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px 20px 0 0;">
                            <h5 class="modal-title font-weight-bold">
                                <i class="fas fa-plus-circle mr-2"></i>
                                Add Fee Component to {{ $student->name }}
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="loadingFeeComponents" class="text-center py-4" style="display: none;">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                                <p class="text-muted">Loading available fee components...</p>
                            </div>

                            <div id="feeComponentsList">
                                {{-- Components will be loaded here via AJAX --}}
                            </div>

                            <div id="noFeeComponents" class="text-center py-4" style="display: none;">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h6 class="text-muted">All Fee Components Assigned</h6>
                                <p class="text-muted">This student has been assigned all available fee components.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                            </div>
                        </div>
                    </div>
                </div>


            </div>
            {{-- Enhanced Apply Concession Modal - REPLACE the existing one --}}
            <div class="modal fade" id="applyConcessionModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.2);">
                        <div class="modal-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #8b4513; border-radius: 20px 20px 0 0; border: none;">
                            <h5 class="modal-title font-weight-bold">
                                <i class="fas fa-percent mr-2"></i>
                                Apply Concession - {{ $student->name }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" style="color: #8b4513; opacity: 0.8;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            {{-- Gender-based Concession Info --}}
                            @if($student->gender === 'Female' && setting('womens_discount_percentage', 0) > 0)
                                <div class="alert alert-info" style="border-radius: 10px; background: linear-gradient(135deg, #e3f2fd 0%, #f1f8e9 100%); border: none;">
                                    <i class="fas fa-female mr-2 text-success"></i>
                                    <strong>Gender Concession Available:</strong> This student is eligible for automatic 
                                    {{ setting('womens_discount_percentage') }}% gender-based discount.
                                    <button type="button" class="btn btn-sm btn-success ml-2" onclick="applyAutomaticGenderConcession()" style="border-radius: 15px;">
                                        <i class="fas fa-magic"></i> Apply Auto Discount
                                    </button>
                                </div>
                            @endif

                            <form id="concessionForm" action="{{ url('admin/students/' . $student->id . '/apply-concession') }}" method="POST">
                                @csrf

                                <div class="form-group">
                                    <label for="concessionComponentSelect" class="font-weight-bold">Fee Component *</label>
                                    <select name="student_fee_id" id="concessionComponentSelect" class="form-control" required style="border-radius: 10px;">
                                        <option value="">-- Select Fee Component --</option>
                                        @if(isset($student) && $student->studentFees)
                                            @foreach($student->studentFees->whereIn('status', ['unpaid', 'partial']) as $fee)
                                                @php 
                                                    $remaining = $fee->amount - $fee->paid_amount - $fee->concession_amount; 
                                                @endphp
                                                @if($remaining > 0)
                                                    <option value="{{ $fee->id }}" data-remaining="{{ $remaining }}">
                                                        {{ $fee->feeCategory->name }} 
                                                        (Remaining: {{ setting('currency_symbol', '₹') }}{{ number_format($remaining, 2) }})
                                                    </option>
                                                @endif
                                            @endforeach
                                        @endif
                                    </select>
                                    <small class="form-text text-muted">Only components with outstanding balance are shown</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="concession_amount" class="font-weight-bold">Concession Amount *</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" style="background: #f8f9fc; border-color: #e3e6f0;">{{ setting('currency_symbol', '₹') }}</span>
                                                </div>
                                                <input type="number" step="0.01" name="concession_amount" id="concession_amount" 
                                                       class="form-control" required min="0.01" max="" style="border-radius: 0 10px 10px 0;">
                                            </div>
                                            <small class="text-muted" id="concession_amount_hint">Enter the concession amount</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted font-weight-bold">Quick Amounts</label>
                                        <div class="btn-group-vertical d-block" id="quickAmountButtons">
                                            {{-- Dynamic buttons will be inserted here --}}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="concession_reason" class="font-weight-bold">Reason for Concession</label>
                                    <textarea name="reason" id="concession_reason" class="form-control" rows="3" 
                                              style="border-radius: 10px;"
                                              placeholder="e.g., Merit scholarship, Financial hardship, Staff discount, Early payment discount"></textarea>
                                    <small class="form-text text-muted">Provide a brief explanation for this concession</small>
                                </div>

                                <div class="alert alert-warning" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: none;">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Important:</strong> This concession will be applied immediately and cannot be undone from this interface.
                                    Please ensure the amount and reason are correct.
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer" style="border: none; background: #f8f9fc; border-radius: 0 0 20px 20px;">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 20px;">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" form="concessionForm" class="btn btn-warning" id="applyConcessionBtn" style="border-radius: 20px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border: none; color: #8b4513; font-weight: bold;">
                                <i class="fas fa-percent"></i> Apply Concession
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Enhanced Payment Recording Modal --}}
            <div class="modal fade payment-modal" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="paymentModalLabel">
                                    <i class="fas fa-credit-card mr-2"></i> Record Payment for {{ $student->name }}
                                </h5>
                                <small class="text-light opacity-75">{{ $student->enrollment_number }} • {{ $student->batch->name ?? 'N/A' }}</small>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="paymentForm" action="{{ route('admin.component-payments.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">

                                {{-- Payment Details Section --}}
                                <div class="payment-form-section">
                                    <h6 class="font-weight-bold text-primary mb-3">
                                        <i class="fas fa-file-invoice-dollar mr-2"></i> Payment Details
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment_amount" class="font-weight-bold">Payment Amount *</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">₹</span>
                                                    </div>
                                                    <input type="number" step="0.01" name="total_amount" id="payment_amount" 
                                                           class="form-control form-control-lg" required min="0.01" placeholder="0.00">
                                                </div>
                                                <small class="text-muted">Enter the total payment amount</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment_method" class="font-weight-bold">Payment Method *</label>
                                                <select name="payment_method" id="payment_method" class="form-control form-control-lg" required>
                                                    <option value="">Select Method</option>
                                                    <option value="cash">Cash</option>
                                                    <option value="card">Card</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                    <option value="upi">UPI</option>
                                                    <option value="cheque">Cheque</option>
                                                    <option value="online">Online</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment_date" class="font-weight-bold">Payment Date *</label>
                                                <input type="date" name="payment_date" id="payment_date" 
                                                       class="form-control form-control-lg" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="transaction_id" class="font-weight-bold">Transaction Reference</label>
                                                <input type="text" name="transaction_id" id="transaction_id" 
                                                       class="form-control" placeholder="Transaction ID / Reference Number">
                                                <small class="text-muted">Optional for cash payments</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="notes" class="font-weight-bold">Notes</label>
                                                <textarea name="notes" id="notes" class="form-control" rows="2" 
                                                          placeholder="Additional notes or comments"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Fee Components Selection --}}
                                <div class="payment-form-section">
                                    <h6 class="font-weight-bold text-primary mb-3">
                                        <i class="fas fa-list-check mr-2"></i> Allocate Payment to Components
                                    </h6>
                                    <div id="fee-components-list">
                                        @if(isset($studentFees) && $studentFees->count() > 0)
                                            @foreach($studentFees as $studentFee)
                                                @php
        $dueAmount = $studentFee->amount - $studentFee->paid_amount;
                                                @endphp
                                                @if($dueAmount > 0)
                                                    <div class="payment-component-item" data-fee-id="{{ $studentFee->id }}" data-max-amount="{{ $dueAmount }}">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-1">
                                                                <div class="custom-control custom-checkbox">
                                                                    <input type="checkbox" class="custom-control-input component-checkbox" 
                                                                           id="component_{{ $studentFee->id }}" name="components[{{ $studentFee->id }}][selected]" value="1">
                                                                    <label class="custom-control-label" for="component_{{ $studentFee->id }}"></label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <div class="font-weight-bold">{{ $studentFee->feeCategory->name }}</div>
                                                                <small class="text-muted">{{ $studentFee->feeCategory->description ?? 'Standard fee component' }}</small>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="text-center">
                                                                    <div class="font-weight-bold text-primary">₹{{ number_format((float) $studentFee->amount, 0) }}</div>
                                                                    <small class="text-muted">Total Fee</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group mb-0">
                                                                    <label class="small font-weight-bold">Payment Amount</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">₹</span>
                                                                        </div>
                                                                        <input type="number" step="0.01" 
                                                                               name="components[{{ $studentFee->id }}][amount]" 
                                                                               class="form-control component-amount" 
                                                                               placeholder="0.00" min="0" max="{{ $dueAmount }}" disabled>
                                                                    </div>
                                                                    <small class="text-muted">Max: ₹{{ number_format($dueAmount, 0) }}</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="text-center py-4">
                                                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                                <h6>No Fee Components Found</h6>
                                                <p class="text-muted">This student doesn't have any pending fee components.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Payment Summary --}}
                                <div class="payment-summary">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="font-weight-bold mb-2">
                                                <i class="fas fa-calculator mr-2"></i> Payment Summary
                                            </h6>
                                            <div id="payment-validation-message" class="text-muted">
                                                Enter payment amount and select components to allocate the payment.
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <div class="row">
                                                <div class="col-12 mb-2">
                                                    <small class="text-muted d-block">Payment Amount</small>
                                                    <div class="h5 font-weight-bold text-primary" id="payment-total-display">₹0.00</div>
                                                </div>
                                                <div class="col-12">
                                                    <small class="text-muted d-block">Allocated Amount</small>
                                                    <div class="h5 font-weight-bold text-success" id="allocated-total-display">₹0.00</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </button>
                            <button type="submit" form="paymentForm" class="action-btn btn-success-modern" id="submit-payment-btn" disabled>
                                <i class="fas fa-credit-card mr-2"></i> Record Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>

@endsection
{{-- Activity Timeline Styles --}}
<style>
.activity-timeline {
    position: relative;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #007bff, #6f42c1, #17a2b8);
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    padding-left: 70px;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 1;
}

.timeline-content {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    border-left: 4px solid #007bff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.timeline-content:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.timeline-header h6 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.timeline-meta {
    flex-shrink: 0;
    min-width: 100px;
}

.toggle-details {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

.toggle-details:focus {
    box-shadow: none;
}

.toggle-details[aria-expanded="true"] i {
    transform: rotate(180deg);
}

.timeline-details .card {
    border: none;
    font-size: 0.85rem;
}

.properties-list small {
    line-height: 1.6;
}

/* Filter animations */
.timeline-item {
    transition: all 0.3s ease;
}

.timeline-item.filtered-out {
    opacity: 0;
    transform: scale(0.95);
    margin-bottom: 0;
    height: 0;
    overflow: hidden;
    padding: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .activity-timeline::before {
        left: 20px;
    }
    
    .timeline-item {
        padding-left: 50px;
    }
    
    .timeline-marker {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .timeline-content {
        padding: 1rem;
    }
    
    .timeline-meta {
        min-width: auto;
        margin-top: 0.5rem;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column !important;
    }
}
</style>
@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeActivityLogs();
    });

    function initializeActivityLogs() {
        // Activity filter functionality
        const filterLinks = document.querySelectorAll('.activity-filter');
        const timelineItems = document.querySelectorAll('.timeline-item');

        filterLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const filterType = this.getAttribute('data-type');

                // Update active filter
                filterLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                // Filter timeline items
                timelineItems.forEach(item => {
                    const itemType = item.getAttribute('data-type');

                    if (filterType === 'all' || itemType === filterType) {
                        item.classList.remove('filtered-out');
                    } else {
                        item.classList.add('filtered-out');
                    }
                });
            });
        });

        // Toggle details functionality
        const toggleButtons = document.querySelectorAll('.toggle-details');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const isExpanded = this.getAttribute('aria-expanded') === 'true';

                // Rotate icon
                if (isExpanded) {
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    icon.style.transform = 'rotate(0deg)';
                }
            });
        });

        // Load more functionality
        const loadMoreBtn = document.getElementById('loadMoreActivity');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                const studentId = this.getAttribute('data-student-id');
                loadMoreActivities(studentId);
            });
        }

        // Auto-refresh activity logs every 30 seconds
        setInterval(function() {
            refreshActivityLogs();
        }, 30000);
    }

    function loadMoreActivities(studentId) {
        const loadMoreBtn = document.getElementById('loadMoreActivity');
        const originalText = loadMoreBtn.innerHTML;

        loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
        loadMoreBtn.disabled = true;

        fetch(`/admin/students/${studentId}/activity-logs?offset=${document.querySelectorAll('.timeline-item').length}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.activities.length > 0) {
                    appendActivities(data.activities);

                    if (data.activities.length < 10) {
                        loadMoreBtn.style.display = 'none';
                    }
                } else {
                    loadMoreBtn.innerHTML = 'No more activities';
                    loadMoreBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error loading activities:', error);
                showNotification('Failed to load more activities', 'error');
            })
            .finally(() => {
                if (!loadMoreBtn.disabled) {
                    loadMoreBtn.innerHTML = originalText;
                    loadMoreBtn.disabled = false;
                }
            });
    }

    function appendActivities(activities) {
        const timeline = document.getElementById('activityTimeline');
        const currentCount = document.querySelectorAll('.timeline-item').length;

        activities.forEach((activity, index) => {
            const timelineItem = createTimelineItem(activity, currentCount + index);
            timeline.appendChild(timelineItem);
        });
    }

    function createTimelineItem(activity, index) {
        const div = document.createElement('div');
        div.className = 'timeline-item';
        div.setAttribute('data-type', activity.type);

        div.innerHTML = `
            <div class="timeline-marker bg-${activity.color}">
                <i class="fas ${activity.icon}"></i>
            </div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="timeline-header">
                        <h6 class="mb-1 font-weight-bold">${activity.title}</h6>
                        <p class="text-muted mb-1">${activity.description}</p>
                    </div>
                    <div class="timeline-meta text-right">
                        <small class="text-muted d-block">${formatDate(activity.timestamp)}</small>
                        <small class="text-muted">${formatTime(activity.timestamp)}</small>
                    </div>
                </div>

                ${activity.properties && Object.keys(activity.properties).length > 0 ? `
                    <div class="timeline-details">
                        <button class="btn btn-sm btn-outline-secondary toggle-details" type="button" data-toggle="collapse" data-target="#details-${index}">
                            <i class="fas fa-chevron-down"></i> Details
                        </button>
                        <div class="collapse mt-2" id="details-${index}">
                            <div class="card card-body bg-light">
                                ${formatProperties(activity.properties, activity.type)}
                            </div>
                        </div>
                    </div>
                ` : ''}

                <div class="timeline-footer mt-2">
                    <small class="text-muted">
                        <i class="fas fa-user mr-1"></i>${activity.user}
                        <span class="mx-2">•</span>
                        <i class="fas fa-clock mr-1"></i>${activity.timestamp_human}
                    </small>
                </div>
            </div>
        `;

        return div;
    }

    function formatProperties(properties, type) {
        if (type === 'payment') {
            return `
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>Amount:</strong> ₹${Number(properties.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</small><br>
                        <small><strong>Method:</strong> ${properties.method}</small>
                    </div>
                    <div class="col-md-6">
                        <small><strong>Receipt:</strong> ${properties.receipt}</small><br>
                        <small><strong>Components:</strong> ${properties.components} items</small>
                    </div>
                </div>
            `;
        } else if (type === 'concession') {
            return `
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>Amount:</strong> ₹${Number(properties.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</small><br>
                        <small><strong>Status:</strong> ${properties.status}</small>
                    </div>
                    <div class="col-md-6">
                        ${properties.reason ? `<small><strong>Reason:</strong> ${properties.reason}</small>` : ''}
                    </div>
                </div>
            `;
        } else {
            let html = '<div class="properties-list">';
            for (const [key, value] of Object.entries(properties)) {
                if (typeof value !== 'object') {
                    html += `<small><strong>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}:</strong> ${value}</small><br>`;
                }
            }
            html += '</div>';
            return html;
        }
    }

    function formatDate(timestamp) {
        return new Date(timestamp).toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }

    function formatTime(timestamp) {
        return new Date(timestamp).toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    }

    function refreshActivityLogs() {
        // Silently refresh the activity count or highlight new activities
        const studentId = document.querySelector('#loadMoreActivity')?.getAttribute('data-student-id');
        if (!studentId) return;

        fetch(`/admin/students/${studentId}/activity-logs/count`)
            .then(response => response.json())
            .then(data => {
                if (data.new_count > 0) {
                    showNotification(`${data.new_count} new activities available`, 'info');
                }
            })
            .catch(error => {
                console.error('Error checking for new activities:', error);
            });
    }

    function showNotification(message, type = 'info') {
        // Use your existing notification system
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: message,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            // Fallback to console
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
    </script>
    <script>
    // Enhanced Concession Modal JavaScript - ADD THIS TO YOUR EXISTING SCRIPT SECTION
    document.addEventListener('DOMContentLoaded', function () {
        initializeConcessionModal();
    });

    function initializeConcessionModal() {
        const concessionComponentSelect = document.getElementById('concessionComponentSelect');
        const concessionAmountInput = document.getElementById('concession_amount');
        const concessionAmountHint = document.getElementById('concession_amount_hint');
        const concessionForm = document.getElementById('concessionForm');
        const applyConcessionBtn = document.getElementById('applyConcessionBtn');
        const quickAmountButtons = document.getElementById('quickAmountButtons');

        let currentSelectedFee = null;

        function updateConcessionInput() {
            const selectedOption = concessionComponentSelect.options[concessionComponentSelect.selectedIndex];
            const remainingAmount = selectedOption.getAttribute('data-remaining');

            if (remainingAmount && parseFloat(remainingAmount) > 0) {
                const maxAmount = parseFloat(remainingAmount);
                currentSelectedFee = {
                    id: selectedOption.value,
                    name: selectedOption.text.split('(')[0].trim(),
                    remaining: maxAmount
                };

                concessionAmountInput.max = maxAmount;
                concessionAmountHint.innerHTML = `Maximum: {{ setting('currency_symbol', '₹') }}${maxAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
                concessionAmountInput.disabled = false;
                applyConcessionBtn.disabled = false;

                // Generate quick amount buttons
                generateQuickAmountButtons(maxAmount);
            } else {
                currentSelectedFee = null;
                concessionAmountInput.max = '';
                concessionAmountInput.value = '';
                concessionAmountHint.textContent = 'Select a fee component first';
                concessionAmountInput.disabled = true;
                applyConcessionBtn.disabled = true;
                quickAmountButtons.innerHTML = '';
            }
        }

    document.addEventListener('DOMContentLoaded', function() {
        // Refresh statistics after successful concession
        @if(session('success') && str_contains(session('success'), 'Concession'))
            console.log('✅ Concession applied, statistics should reflect changes');

            // Update the amount counters with animation
            setTimeout(() => {
                updateAmountCounters();
            }, 1000);
        @endif
    });

    function updateAmountCounters() {
        // Re-animate the counters with new values
        $('.amount-counter').each(function() {
            const element = $(this);
            const finalValue = element.text().replace(/[₹,]/g, '');

            if (!isNaN(finalValue) && finalValue !== '') {
                element.text('₹0');
                $({ counter: 0 }).animate({ counter: parseFloat(finalValue) }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() {
                        element.text('₹' + Math.ceil(this.counter).toLocaleString());
                    },
                    complete: function() {
                        element.text('₹' + parseFloat(finalValue).toLocaleString());
                    }
                });
            }
        });
    }

    // Debug function to check current values
    function debugFinancialSummary() {
        const summary = {
            total_fee: {{ isset($financialSummary['total_amount']) ? $financialSummary['total_amount'] : 0 }},
            total_paid: {{ isset($financialSummary['paid_amount']) ? $financialSummary['paid_amount'] : 0 }},
            total_concession: {{ isset($financialSummary['concession_amount']) ? $financialSummary['concession_amount'] : 0 }},
            total_due: {{ isset($financialSummary['remaining_amount']) ? $financialSummary['remaining_amount'] : 0 }},
            completion_percentage: {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}
        };

        console.log('💰 Current Financial Summary:', summary);
        return summary;
    }


        function generateQuickAmountButtons(maxAmount) {
            const percentages = [10, 25, 50, 100];
            let buttonsHtml = '';

            percentages.forEach(percentage => {
                const amount = (maxAmount * percentage) / 100;
                if (amount <= maxAmount) {
                    buttonsHtml += `
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-1" 
                                style="border-radius: 15px; font-size: 0.75rem;"
                                onclick="setQuickConcessionAmount(${amount})">
                            ${percentage}% (₹${amount.toLocaleString('en-IN', {minimumFractionDigits: 0})})
                        </button>
                    `;
                }
            });

            // Add gender-based amount if applicable
            @if($student->gender === 'Female' && setting('womens_discount_percentage', 0) > 0)
                const genderPercentage = {{ setting('womens_discount_percentage', 0) }};
                if (genderPercentage > 0) {
                    const genderAmount = (maxAmount * genderPercentage) / 100;
                    buttonsHtml += `
                        <button type="button" class="btn btn-outline-success btn-sm mb-1" 
                                style="border-radius: 15px; font-size: 0.75rem;"
                                onclick="setQuickConcessionAmount(${genderAmount})">
                            Gender ${genderPercentage}% (₹${genderAmount.toLocaleString('en-IN', {minimumFractionDigits: 0})})
                        </button>
                    `;
                }
            @endif

            quickAmountButtons.innerHTML = buttonsHtml;
        }

        // Initialize
        if (concessionComponentSelect) {
            updateConcessionInput();
            concessionComponentSelect.addEventListener('change', updateConcessionInput);
        }

        // Form validation and submission
        if (concessionForm) {
            concessionForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const selectedComponent = concessionComponentSelect.value;
                const amount = parseFloat(concessionAmountInput.value);
                const maxAmount = parseFloat(concessionAmountInput.max);

                // Validation
                if (!selectedComponent) {
                    showNotification('Please select a fee component.', 'error');
                    return;
                }

                if (!amount || amount <= 0) {
                    showNotification('Please enter a valid concession amount.', 'error');
                    return;
                }

                if (amount > maxAmount) {
                    showNotification(`Concession amount cannot exceed {{ setting('currency_symbol', '₹') }}${maxAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}`, 'error');
                    return;
                }

                // Show confirmation
                const componentName = currentSelectedFee ? currentSelectedFee.name : 'selected component';
                if (confirm(`Apply concession of {{ setting('currency_symbol', '₹') }}${amount.toLocaleString('en-IN', {minimumFractionDigits: 2})} to ${componentName}?\n\nThis action cannot be undone.`)) {

                    // Show loading state
                    applyConcessionBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                    applyConcessionBtn.disabled = true;

                    // Submit form
                    this.submit();
                }
            });
        }

        // Reset modal when hidden
        $('#applyConcessionModal').on('hidden.bs.modal', function () {
            if (concessionForm) {
                concessionForm.reset();
            }
            if (applyConcessionBtn) {
                applyConcessionBtn.innerHTML = '<i class="fas fa-percent"></i> Apply Concession';
                applyConcessionBtn.disabled = false;
            }
            updateConcessionInput();
        });
    }

    // Helper functions for concession
    function setQuickConcessionAmount(amount) {
        const concessionInput = document.getElementById('concession_amount');
        if (concessionInput) {
            concessionInput.value = amount.toFixed(2);
        }
    }

    function applyAutomaticGenderConcession() {
        if (!confirm('Apply automatic gender-based concession to all eligible fee components?\n\nThis action cannot be undone.')) {
            return;
        }

        // Show loading state
        const originalButton = document.querySelector('[onclick="applyAutomaticGenderConcession()"]');
        if (originalButton) {
            const originalContent = originalButton.innerHTML;
            originalButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            originalButton.disabled = true;

            fetch('{{ url("admin/students/" . $student->id . "/apply-auto-gender-concession") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    $('#applyConcessionModal').modal('hide');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error applying automatic concession: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button
                if (originalButton) {
                    originalButton.innerHTML = originalContent;
                    originalButton.disabled = false;
                }
            });
        }
    }
    </script>
    <script>
    $(document).ready(function() {
        // Initialize payment modal functionality
        initializePaymentModal();

        // Tab switching animations
        $('#profileTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });

        // Enhanced hover effects
        $('.stat-card').hover(
            function() { $(this).find('.fa-3x').addClass('fa-spin'); },
            function() { $(this).find('.fa-3x').removeClass('fa-spin'); }
        );

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Counter animation for amounts
        animateCounters();
    });

    function initializePaymentModal() {
        const paymentAmountInput = $('#payment_amount');
        const componentCheckboxes = $('.component-checkbox');
        const componentAmounts = $('.component-amount');
        const submitBtn = $('#submit-payment-btn');
        const validationMessage = $('#payment-validation-message');
        const paymentTotalDisplay = $('#payment-total-display');
        const allocatedTotalDisplay = $('#allocated-total-display');

        // Payment amount change handler
        paymentAmountInput.on('input', function() {
            updatePaymentSummary();
            distributePaymentAmount();
        });

        // Component checkbox change handler
        componentCheckboxes.on('change', function() {
            const amountInput = $(this).closest('.payment-component-item').find('.component-amount');
            amountInput.prop('disabled', !this.checked);

            if (this.checked) {
                amountInput.focus();
            } else {
                amountInput.val('');
            }

            updateComponentSelection();
            updatePaymentSummary();
        });

        // Component amount change handler
        componentAmounts.on('input', function() {
            updatePaymentSummary();
        });

        function updatePaymentSummary() {
            const paymentAmount = parseFloat(paymentAmountInput.val()) || 0;
            let allocatedAmount = 0;

            $('.component-checkbox:checked').each(function() {
                const amountInput = $(this).closest('.payment-component-item').find('.component-amount');
                allocatedAmount += parseFloat(amountInput.val()) || 0;
            });

            paymentTotalDisplay.text('₹' + paymentAmount.toLocaleString('en-IN', {minimumFractionDigits: 2}));
            allocatedTotalDisplay.text('₹' + allocatedAmount.toLocaleString('en-IN', {minimumFractionDigits: 2}));

            // Validation
            if (paymentAmount === 0) {
                validationMessage.html('<i class="fas fa-info-circle mr-2"></i>Enter payment amount and select components.');
                validationMessage.removeClass('text-success text-danger').addClass('text-muted');
                submitBtn.prop('disabled', true);
            } else if (allocatedAmount === 0) {
                validationMessage.html('<i class="fas fa-exclamation-triangle mr-2"></i>Please select at least one component to allocate payment.');
                validationMessage.removeClass('text-success text-muted').addClass('text-warning');
                submitBtn.prop('disabled', true);
            } else if (allocatedAmount > paymentAmount) {
                validationMessage.html('<i class="fas fa-times-circle mr-2"></i>Allocated amount exceeds payment amount!');
                validationMessage.removeClass('text-success text-muted').addClass('text-danger');
                submitBtn.prop('disabled', true);
            } else if (allocatedAmount < paymentAmount) {
                const difference = paymentAmount - allocatedAmount;
                validationMessage.html(`<i class="fas fa-info-circle mr-2"></i>₹${difference.toFixed(2)} remaining to allocate.`);
                validationMessage.removeClass('text-success text-danger').addClass('text-info');
                submitBtn.prop('disabled', false);
            } else {
                validationMessage.html('<i class="fas fa-check-circle mr-2"></i>Payment allocation is perfect!');
                validationMessage.removeClass('text-danger text-muted').addClass('text-success');
                submitBtn.prop('disabled', false);
            }
        }

        function distributePaymentAmount() {
            const paymentAmount = parseFloat(paymentAmountInput.val()) || 0;
            const checkedComponents = $('.component-checkbox:checked');

            if (checkedComponents.length === 0 || paymentAmount === 0) return;

            const amountPerComponent = paymentAmount / checkedComponents.length;

            checkedComponents.each(function() {
                const componentItem = $(this).closest('.payment-component-item');
                const amountInput = componentItem.find('.component-amount');
                const maxAmount = parseFloat(componentItem.data('max-amount'));

                const allocatedAmount = Math.min(amountPerComponent, maxAmount);
                amountInput.val(allocatedAmount.toFixed(2));
            });

            updatePaymentSummary();
        }

        function updateComponentSelection() {
            $('.payment-component-item').each(function() {
                const checkbox = $(this).find('.component-checkbox');
                if (checkbox.is(':checked')) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
        }
    }

    function openPaymentModal(feeId = null, feeName = null, dueAmount = null) {
        $('#paymentModal').modal('show');

        // If specific fee component is selected
        if (feeId) {
            setTimeout(() => {
                $(`#component_${feeId}`).prop('checked', true).trigger('change');
                if (dueAmount) {
                    $('#payment_amount').val(dueAmount).trigger('input');
                }
            }, 500);
        }
    }

    function viewReceipt(paymentId) {
        // Implement receipt viewing
        alert('Receipt viewing for payment ID: ' + paymentId);
    }

    function animateCounters() {
        $('.amount-counter').each(function() {
            const element = $(this);
            const finalValue = element.text().replace(/[₹,]/g, '');
            if (!isNaN(finalValue) && finalValue !== '') {
                element.text('₹0');
                $({ counter: 0 }).animate({ counter: parseFloat(finalValue) }, {
                    duration: 1500,
                    easing: 'swing',
                    step: function() {
                        element.text('₹' + Math.ceil(this.counter).toLocaleString());
                    },
                    complete: function() {
                        element.text('₹' + parseFloat(finalValue).toLocaleString());
                    }
                });
            }
        });
    }

    // Payment form validation
    $('#paymentForm').on('submit', function(e) {
        const paymentAmount = parseFloat($('#payment_amount').val()) || 0;
        let allocatedAmount = 0;
        let hasSelectedComponents = false;

        $('.component-checkbox:checked').each(function() {
            hasSelectedComponents = true;
            const amountInput = $(this).closest('.payment-component-item').find('.component-amount');
            const amount = parseFloat(amountInput.val()) || 0;
            const maxAmount = parseFloat($(this).closest('.payment-component-item').data('max-amount'));

            if (amount <= 0) {
                e.preventDefault();
                alert('Please enter valid amounts for all selected components.');
                amountInput.focus();
                return false;
            }

            if (amount > maxAmount) {
                e.preventDefault();
                alert(`Amount for ${$(this).closest('.payment-component-item').find('.font-weight-bold').first().text()} cannot exceed ₹${maxAmount}`);
                amountInput.focus();
                return false;
            }

            allocatedAmount += amount;
        });

        if (!hasSelectedComponents) {
            e.preventDefault();
            alert('Please select at least one fee component.');
            return false;
        }

        if (allocatedAmount > paymentAmount) {
            e.preventDefault();
            alert('Total allocated amount cannot exceed the payment amount.');
            return false;
        }

        // Show loading state
        $('#submit-payment-btn').html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing Payment...').prop('disabled', true);

        return true;
    });

    // Payment method change handler
    $('#payment_method').on('change', function() {
        const method = $(this).val();
        const transactionField = $('#transaction_id');
        const transactionLabel = $('label[for="transaction_id"]');

        if (method === 'cash') {
            transactionField.attr('placeholder', 'Cash receipt number (optional)');
            transactionLabel.html('Transaction Reference <small class="text-muted">(Optional)</small>');
        } else if (['upi', 'online', 'bank_transfer'].includes(method)) {
            transactionField.attr('placeholder', 'Transaction ID or reference number');
            transactionLabel.html('Transaction Reference <small class="text-danger">*</small>');
        } else {
            transactionField.attr('placeholder', 'Reference number');
            transactionLabel.html('Transaction Reference');
        }
    });

    // Auto-fill payment amount when component is selected
    $(document).on('change', '.component-checkbox', function() {
        const isChecked = $(this).is(':checked');
        const componentItem = $(this).closest('.payment-component-item');
        const maxAmount = parseFloat(componentItem.data('max-amount'));
        const amountInput = componentItem.find('.component-amount');

        if (isChecked && $('#payment_amount').val() === '') {
            $('#payment_amount').val(maxAmount).trigger('input');
        }
    });

    // Quick payment buttons for common amounts
    function addQuickPaymentButtons() {
        const quickAmounts = [500, 1000, 2000, 5000, 10000];
        let buttonsHtml = '<div class="mt-2"><small class="text-muted">Quick amounts: </small>';

        quickAmounts.forEach(amount => {
            buttonsHtml += `<button type="button" class="btn btn-outline-primary btn-sm mr-1 mb-1" onclick="setPaymentAmount(${amount})">₹${amount}</button>`;
        });

        buttonsHtml += '</div>';
        $('#payment_amount').after(buttonsHtml);
    }

    function setPaymentAmount(amount) {
        $('#payment_amount').val(amount).trigger('input');
    }

    // Initialize quick payment buttons
    setTimeout(addQuickPaymentButtons, 100);

    // Enhanced notification system
    function showNotification(message, type = 'info', duration = 5000) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };

        const icon = {
            'success': 'fa-check-circle',
            'error': 'fa-times-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };

        const notification = $(`
            <div class="alert ${alertClass[type]} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <i class="fas ${icon[type]} mr-2"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);

        $('body').append(notification);

        setTimeout(() => {
            notification.alert('close');
        }, duration);
    }

    // Check for success messages from localStorage
    if (localStorage.getItem('payment_success')) {
        showNotification(localStorage.getItem('payment_success'), 'success');
        localStorage.removeItem('payment_success');
    }

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl + P for payment modal
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            openPaymentModal();
        }

        // Ctrl + E for edit
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            window.location.href = "{{ route('admin.students.edit', $student) }}";
        }

        // Ctrl + D for dashboard
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            window.location.href = "{{ route('admin.payments.component-dashboard', $student) }}";
        }
    });

    // Progressive enhancement for mobile
    if (window.innerWidth <= 768) {
        $('.action-btn').addClass('btn-block mb-2');
        $('.quick-action-card').addClass('mb-3');
    }

    // Auto-save form data to localStorage (draft)
    let formDraftTimer;
    $('#paymentForm input, #paymentForm select, #paymentForm textarea').on('input change', function() {
        clearTimeout(formDraftTimer);
        formDraftTimer = setTimeout(saveFormDraft, 1000);
    });

    function saveFormDraft() {
        const formData = {
            payment_amount: $('#payment_amount').val(),
            payment_method: $('#payment_method').val(),
            payment_date: $('#payment_date').val(),
            transaction_id: $('#transaction_id').val(),
            notes: $('#notes').val(),
            selected_components: []
        };

        $('.component-checkbox:checked').each(function() {
            const componentItem = $(this).closest('.payment-component-item');
            formData.selected_components.push({
                fee_id: componentItem.data('fee-id'),
                amount: componentItem.find('.component-amount').val()
            });
        });

        localStorage.setItem('payment_form_draft', JSON.stringify(formData));
    }

    function loadFormDraft() {
        const draft = localStorage.getItem('payment_form_draft');
        if (draft) {
            try {
                const formData = JSON.parse(draft);
                $('#payment_amount').val(formData.payment_amount);
                $('#payment_method').val(formData.payment_method);
                $('#payment_date').val(formData.payment_date);
                $('#transaction_id').val(formData.transaction_id);
                $('#notes').val(formData.notes);

                formData.selected_components.forEach(component => {
                    $(`input[data-fee-id="${component.fee_id}"]`).prop('checked', true).trigger('change');
                    $(`.payment-component-item[data-fee-id="${component.fee_id}"] .component-amount`).val(component.amount);
                });

                showNotification('Previous draft loaded', 'info', 3000);
            } catch (e) {
                console.warn('Could not load form draft:', e);
            }
        }
    }

    // Clear draft on successful submission
    $('#paymentForm').on('submit', function() {
        localStorage.removeItem('payment_form_draft');
    });

    // Load draft when modal opens
    $('#paymentModal').on('shown.bs.modal', function() {
        setTimeout(loadFormDraft, 100);
    });

    // Clear draft button
    $('#paymentModal .modal-header').append(`
        <button type="button" class="btn btn-sm btn-outline-light mr-2" onclick="clearFormDraft()" title="Clear Draft">
            <i class="fas fa-eraser"></i>
        </button>
    `);

    function clearFormDraft() {
        localStorage.removeItem('payment_form_draft');
        $('#paymentForm')[0].reset();
        $('.component-checkbox').prop('checked', false).trigger('change');
        showNotification('Form draft cleared', 'info', 2000);
    }

    // Enhanced print functionality
    window.addEventListener('beforeprint', function() {
        document.title = 'Student Profile - {{ $student->name }}';
        $('.action-btn, .quick-action-card, .col-lg-4').hide();
        $('.col-lg-8').removeClass('col-lg-8').addClass('col-12');
        $('body').addClass('printing');
    });

    window.addEventListener('afterprint', function() {
        $('.action-btn, .quick-action-card, .col-lg-4').show();
        $('.col-12').removeClass('col-12').addClass('col-lg-8');
        $('body').removeClass('printing');
    });


    // Enhanced payment filtering
    function filterPayments() {
        $('#paymentFilterModal').modal('show');
    }

    function applyPaymentFilter() {
        const method = $('#filterMethod').val();
        const startDate = $('#filterStartDate').val();
        const endDate = $('#filterEndDate').val();
        const minAmount = $('#filterMinAmount').val();
        const maxAmount = $('#filterMaxAmount').val();
        const createdBy = $('#filterCreatedBy').val();

        const table = document.getElementById('payment-history-table');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            let showRow = true;
            const cells = row.querySelectorAll('td');

            // Filter by payment method
            if (method && showRow) {
                const methodCell = cells[3];
                if (methodCell) {
                    const rowMethod = methodCell.textContent.toLowerCase().trim();
                    if (!rowMethod.includes(method.toLowerCase())) {
                        showRow = false;
                    }
                }
            }

            // Filter by date range
            if ((startDate || endDate) && showRow && cells[1]) {
                const dateCell = cells[1];
                const dateSpan = dateCell.querySelector('span');
                if (dateSpan) {
                    const dateText = dateSpan.textContent;
                    const rowDate = new Date(dateText);

                    if (startDate && rowDate < new Date(startDate)) {
                        showRow = false;
                    }
                    if (endDate && rowDate > new Date(endDate)) {
                        showRow = false;
                    }
                }
            }

            // Filter by amount range
            if ((minAmount || maxAmount) && showRow && cells[2]) {
                const amountCell = cells[2];
                const amountText = amountCell.textContent.replace(/[₹,]/g, '');
                const rowAmount = parseFloat(amountText);

                if (minAmount && rowAmount < parseFloat(minAmount)) {
                    showRow = false;
                }
                if (maxAmount && rowAmount > parseFloat(maxAmount)) {
                    showRow = false;
                }
            }

            // Filter by created by
            if (createdBy && showRow && cells[5]) {
                const createdByCell = cells[5];
                const creatorElement = createdByCell.querySelector('strong');
                const creator = creatorElement ? creatorElement.textContent : '';
                if (!creator.toLowerCase().includes(createdBy.toLowerCase())) {
                    showRow = false;
                }
            }

            row.style.display = showRow ? '' : 'none';
        });

        $('#paymentFilterModal').modal('hide');
        updateFilteredSummary();
    }

    function clearPaymentFilter() {
        const form = document.getElementById('paymentFilterForm');
        if (form) {
            form.reset();
        }

        const rows = document.querySelectorAll('#payment-history-table tbody tr');
        rows.forEach(row => {
            row.style.display = '';
        });

        $('#paymentFilterModal').modal('hide');
        updateFilteredSummary();
    }

    function updateFilteredSummary() {
        const visibleRows = document.querySelectorAll('#payment-history-table tbody tr:not([style*="display: none"])');
        let totalAmount = 0;

        visibleRows.forEach(row => {
            const amountCell = row.querySelectorAll('td')[2];
            if (amountCell) {
                const amountText = amountCell.textContent.replace(/[₹,]/g, '');
                totalAmount += parseFloat(amountText) || 0;
            }
        });

        // Update summary if elements exist
        const totalCountElements = document.querySelectorAll('.text-primary h4');
        const totalAmountElements = document.querySelectorAll('.text-success h4');

        if (totalCountElements.length > 0) {
            totalCountElements[totalCountElements.length - 1].textContent = visibleRows.length;
        }
        if (totalAmountElements.length > 0) {
            totalAmountElements[totalAmountElements.length - 1].textContent = '₹' + totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
        }
    }

    // Export payment history
    function exportPaymentHistory() {
        const table = document.getElementById('payment-history-table');
        if (!table) {
            showNotification('Payment table not found', 'error');
            return;
        }

        const rows = table.querySelectorAll('tbody tr:not([style*="display: none"])');

        const csvData = [];
        csvData.push(['Receipt Number', 'Date', 'Amount', 'Method', 'Components', 'Created By', 'Status']);

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7) {
                const rowData = [
                    cells[0].querySelector('strong')?.textContent || '',
                    cells[1].querySelector('span')?.textContent || '',
                    cells[2].textContent.replace(/[₹,]/g, '').trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.replace(/\n/g, '; ').trim(),
                    cells[5].querySelector('strong')?.textContent || '',
                    cells[6].textContent.trim()
                ];
                csvData.push(rowData);
            }
        });

        let csvContent = "data:text/csv;charset=utf-8,";
        csvData.forEach(function(rowArray) {
            let row = rowArray.map(field => '"' + field.replace(/"/g, '""') + '"').join(",");
            csvContent += row + "\r\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "payment_history_{{ $student->enrollment_number }}_" + new Date().toISOString().split('T')[0] + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotification('Payment history exported successfully!', 'success');
    }

    // Print payment history
    function printPaymentHistory() {
        const printContent = generatePrintablePaymentHistory();

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

    function generatePrintablePaymentHistory() {
        const table = document.getElementById('payment-history-table');
        if (!table) {
            return '<html><body><h1>Payment table not found</h1></body></html>';
        }

        const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
        let tableContent = '';

        visibleRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7) {
                tableContent += `
                    <tr>
                        <td>${cells[0].querySelector('strong')?.textContent || ''}</td>
                        <td>${cells[1].querySelector('span')?.textContent || ''}</td>
                        <td>${cells[2].textContent.trim()}</td>
                        <td>${cells[3].textContent.trim()}</td>
                        <td>${cells[4].textContent.replace(/\s+/g, ' ').trim()}</td>
                        <td>${cells[5].querySelector('strong')?.textContent || ''}</td>
                        <td>${cells[6].textContent.trim()}</td>
                    </tr>
                `;
            }
        });

        return `
            <html>
            <head>
                <title>Payment History - {{ $student->name }}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .summary { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
                    @media print { 
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Payment History Report</h1>
                    <h3>{{ $student->name }} ({{ $student->enrollment_number }})</h3>
                    <p>{{ $student->batch->course->name ?? 'N/A' }} • {{ $student->batch->name ?? 'N/A' }}</p>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Components</th>
                            <th>Created By</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableContent}
                    </tbody>
                </table>

                <div class="summary">
                    <h3>Summary</h3>
                    <p><strong>Total Payments:</strong> ${visibleRows.length}</p>
                    <p><strong>Report Generated By:</strong> {{ auth()->user()->name ?? 'System' }}</p>
                    <p><strong>Generated At:</strong> ${new Date().toLocaleString()}</p>
                </div>
            </body>
            </html>
        `;
    }

    // Enhanced refresh functionality
    function refreshPaymentHistory() {
        const refreshBtn = document.querySelector('button[onclick="refreshPaymentHistory()"]');
        if (!refreshBtn) return;

        const originalContent = refreshBtn.innerHTML;

        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        refreshBtn.disabled = true;

        // Simply reload the page for now - you can implement AJAX later
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    // Enhanced payment details view
    function viewPaymentDetails(paymentId) {
        // Create a modal to show payment details
        const payment = findPaymentById(paymentId);

        if (!payment) {
            showNotification('Payment details not found', 'error');
            return;
        }

        const modalHtml = `
            <div class="modal fade" id="paymentDetailsModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-receipt mr-2"></i>Payment Details
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" id="paymentDetailsContent">
                            ${generatePaymentDetailsHtml(payment)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="printPaymentModal()">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $('#paymentDetailsModal').remove();

        // Add modal to body
        $('body').append(modalHtml);

        // Show modal
        $('#paymentDetailsModal').modal('show');
    }

    // Find payment by ID from the table data
    function findPaymentById(paymentId) {
        const paymentRow = $(`#payment-history-table tbody tr[data-payment-id="${paymentId}"]`);

        if (paymentRow.length === 0) {
            return null;
        }

        // Extract data from the row
        const cells = paymentRow.find('td');

        return {
            id: paymentId,
            receipt_number: $(cells[0]).find('strong').text(),
            transaction_id: $(cells[0]).find('small').text().replace('TXN: ', '') || '',
            date: $(cells[1]).find('span').text(),
            time: $(cells[1]).find('small').text(),
            amount: $(cells[2]).find('.badge').text(),
            method: $(cells[3]).find('.badge').text(),
            components: extractComponentsFromCell(cells[4]),
            created_by: $(cells[5]).find('strong').text(),
            created_time: $(cells[5]).find('small').text(),
            status: $(cells[6]).find('.badge').text(),
            notes: $(cells[7]).find('[data-original-title]').attr('data-original-title') || ''
        };
    }

    // Extract components from the table cell
    function extractComponentsFromCell(cell) {
        const components = [];
        $(cell).find('.component-breakdown small').each(function() {
            const text = $(this).text();
            const parts = text.split(':');
            if (parts.length === 2) {
                components.push({
                    name: parts[0].trim(),
                    amount: parts[1].trim()
                });
            }
        });
        return components;
    }

    // Generate HTML for payment details
    function generatePaymentDetailsHtml(payment) {
        return `
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Payment Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="font-weight-bold">Receipt Number:</td>
                                    <td>${payment.receipt_number}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Amount:</td>
                                    <td class="text-success font-weight-bold">${payment.amount}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Payment Date:</td>
                                    <td>${payment.date} ${payment.time}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Payment Method:</td>
                                    <td><span class="badge badge-info">${payment.method}</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Status:</td>
                                    <td><span class="badge badge-success">${payment.status}</span></td>
                                </tr>
                                ${payment.transaction_id ? `
                                <tr>
                                    <td class="font-weight-bold">Transaction ID:</td>
                                    <td><code>${payment.transaction_id}</code></td>
                                </tr>
                                ` : ''}
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-list mr-2"></i>Fee Components</h6>
                        </div>
                        <div class="card-body">
                            ${payment.components.length > 0 ? `
                                <table class="table table-borderless table-sm">
                                    ${payment.components.map(component => `
                                        <tr>
                                            <td>${component.name}</td>
                                            <td class="text-right font-weight-bold">${component.amount}</td>
                                        </tr>
                                    `).join('')}
                                </table>
                            ` : '<p class="text-muted">No component breakdown available</p>'}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-user mr-2"></i>Audit Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Created By:</strong> ${payment.created_by}<br>
                                    <small class="text-muted">${payment.created_time}</small>
                                </div>
                                <div class="col-md-6">
                                    ${payment.notes ? `
                                        <strong>Notes:</strong><br>
                                        <div class="alert alert-info">${payment.notes}</div>
                                    ` : '<em class="text-muted">No notes</em>'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Print modal content
    function printPaymentModal() {
        const modalContent = document.getElementById('paymentDetailsContent');
        if (!modalContent) return;

        const printContent = `
            <html>
            <head>
                <title>Payment Details</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { margin: 20px; }
                    @media print { 
                        body { margin: 0; }
                        .btn { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="container-fluid">
                    <h2 class="text-center mb-4">Payment Details</h2>
                    ${modalContent.innerHTML}
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

    // Initialize payment history enhancements
    $(document).ready(function() {
        // Initialize tooltips for payment history
        $('[data-toggle="tooltip"]').tooltip();

        // Add hover effect to payment rows
        $('#payment-history-table tbody tr').hover(
            function() {
                $(this).addClass('table-active');
            },
            function() {
                $(this).removeClass('table-active');
            }
        );

        // Enhanced payment row click functionality
        $('#payment-history-table tbody tr').on('click', function(e) {
            // Don't trigger on button clicks
            if (!$(e.target).closest('.btn-group').length) {
                const paymentId = $(this).data('payment-id');
                if (paymentId) {
                    viewPaymentDetails(paymentId);
                }
            }
        });

        // Keyboard shortcuts for payment history
        $(document).on('keydown', function(e) {
            // Ctrl + F for filter
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                filterPayments();
            }

            // Ctrl + E for export  
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportPaymentHistory();
            }

            // Ctrl + R for refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshPaymentHistory();
            }
        });
    });

    // Success/error notification after form submissions
    @if(session('success'))
        showNotification('{{ session('success') }}', 'success');
    @endif

    @if(session('error'))
        showNotification('{{ session('error') }}', 'error');
    @endif

    // Payment history debug info (only in development)
    @if(config('app.debug'))
        console.log('💰 Payment History Debug:', {
            total_payments: {{ isset($paymentHistory) ? $paymentHistory->count() : 0 }},
            student_id: {{ $student->id }},
            @if(isset($paymentHistory) && $paymentHistory->count() > 0)
                recent_payments: [
                    @foreach($paymentHistory->take(3) as $payment)
                        {
                            id: {{ $payment->id }},
                            receipt: '{{ $payment->receipt_number }}',
                            amount: {{ $payment->amount }},
                            created_by: '{{ $payment->createdBy ? $payment->createdBy->name : 'null' }}'
                        }{{ $loop->last ? '' : ',' }}
                    @endforeach
                ]
            @endif
        });
    @endif


    // Add print styles
    $('<style>').prop('type', 'text/css').html(`
        @media print {
            .printing .modern-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
            .printing .profile-header {
                background: #f8f9fa !important;
                color: #333 !important;
            }
            .printing .nav-tabs-modern {
                display: none !important;
            }
            .printing .tab-content .tab-pane {
                display: block !important;
                opacity: 1 !important;
            }
            .printing .payment-progress-bar {
                background: #6c757d !important;
            }
        }
    `).appendTo('head');
    </script>
    <script>

    // Attendance functionality
    function loadAttendanceData() {
        const month = $('#attendanceMonth').val();

        // UI Updates
        $('#attendanceLoading').show();
        $('#attendanceContent').hide();
        $('#attendanceError').hide();

        $.ajax({
            url: `{{ route('admin.students.attendance-data', $student) }}`,
            method: 'GET',
            data: { month: month },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    populateAttendanceData(response.data);
                    $('#attendanceLoading').hide();
                    $('#attendanceContent').fadeIn();
                } else {
                    showAttendanceError(response.message);
                }
            },
            error: function(xhr) {
                console.error('Attendance Load Error:', xhr);
                let msg = 'Failed to load data.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showAttendanceError(msg);
            }
        });
    }

    function populateAttendanceData(data) {
        // Debug logging
        console.log('Received attendance data:', data);

        // Calculate Counts from Calendar Data
        let holidayCount = 0;
        let workingDaysCount = 0;

        if (data.calendar) {
            Object.values(data.calendar).forEach(day => {
                const s = day.status;

                // Count Holidays
                if (s === 'holiday') {
                    holidayCount++;
                }

                // Count Working Days (Till Date)
                // We count days that are NOT holidays, NOT weekends, and NOT 'none' (future/before joining)
                // This effectively counts: present, absent, late, excused, internship
                if (s !== 'holiday' && s !== 'weekend' && s !== 'none') {
                    workingDaysCount++;
                }
            });
        }

        // Update summary cards
        const percentage = (data.summary?.overall_percentage || 0) + '%';

        $('#headerAttendancePercentage').text(percentage);
        $('#tabAttendancePercentage').text(percentage);

        // Update Counters
        $('#totalWorkingDays').text(workingDaysCount); // New Counter
        $('#presentDays').text(data.monthly?.present_days || 0);
        $('#absentDays').text(data.monthly?.absent_days || 0);
        $('#lateDays').text(data.monthly?.late_days || 0);
        $('#holidayDays').text(holidayCount);

        // Update status alert
        updateAttendanceStatus(data.summary || {});

        // Update calendar title
        $('#calendarTitle').text((data.monthly?.month_name || 'Current Month') + ' Attendance');

        // Generate calendar with biometric data
        generateAttendanceCalendar(data.calendar || {}, data.monthly?.month_name || 'Current Month', data.biometric_summary || {}, data.attendance_patterns || {});

        // Populate recent records table
        populateAttendanceTable(data.monthly?.records || []);

        // Re-initialize tooltips
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
            $('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]').tooltip('dispose').tooltip();
        }
    }

    function updateAttendanceStatus(summary) {
        const statusConfig = {
            'excellent': {
                class: 'alert-success',
                title: 'Excellent Attendance!',
                message: 'Keep up the great work! Your attendance is outstanding.'
            },
            'good': {
                class: 'alert-info', 
                title: 'Good Attendance',
                message: 'Your attendance is good. Try to maintain this level.'
            },
            'satisfactory': {
                class: 'alert-warning',
                title: 'Satisfactory Attendance',
                message: 'Your attendance meets minimum requirements but could be improved.'
            },
            'needs_improvement': {
                class: 'alert-danger',
                title: 'Attendance Needs Improvement',
                message: 'Your attendance is below the required minimum. Please attend classes regularly.'
            }
        };

        const config = statusConfig[summary.status || 'needs_improvement'] || statusConfig['needs_improvement'];

        $('#attendanceStatusAlert')
            .removeClass('alert-success alert-info alert-warning alert-danger')
            .addClass(config.class)
            .show();

        $('#attendanceStatusTitle').text(config.title);
        $('#attendanceStatusMessage').text(config.message);
    }

    function generateAttendanceCalendar(calendarData, monthName, biometricSummary) {
        const selectedMonthDate = new Date(monthName);
        const year = selectedMonthDate.getFullYear();
        const month = selectedMonthDate.getMonth();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDay = new Date(year, month, 1).getDay();

        // Generate monthly summary
        const summaryHtml = generateMonthlySummary(biometricSummary, monthName);

        // Generate calendar header
        let calendarHtml = `
            <div class="comprehensive-calendar">
                ${summaryHtml}
                <div class="calendar-header text-center mb-3">
                    <h5 class="mb-0">${monthName}</h5>
                    <div class="calendar-legend mt-2">
                        <span class="legend-item"><span class="badge badge-success">Present</span></span>
                        <span class="legend-item"><span class="badge badge-warning">Late</span></span>
                        <span class="legend-item"><span class="badge badge-danger">Absent</span></span>
                        <span class="legend-item"><span class="badge badge-info">Excused</span></span>
                    </div>
                </div>
                <div class="calendar-grid">
                    <div class="calendar-weekdays">
                        <div class="weekday">Sun</div>
                        <div class="weekday">Mon</div>
                        <div class="weekday">Tue</div>
                        <div class="weekday">Wed</div>
                        <div class="weekday">Thu</div>
                        <div class="weekday">Fri</div>
                        <div class="weekday">Sat</div>
                    </div>
                    <div class="calendar-days">
        `;

        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            calendarHtml += '<div class="calendar-day empty"></div>';
        }

        // Generate calendar days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const attendance = calendarData[dateStr];

            calendarHtml += generateCalendarDay(day, dateStr, attendance);
        }

        calendarHtml += `
                    </div>
                </div>
            </div>
        `;

        $('#attendanceCalendar').html(calendarHtml);

        // Initialize tooltips for the new calendar
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            // Dispose existing tooltips first (both BS4 and BS5 selectors)
            const tooltipSelectors = '[data-bs-toggle="tooltip"], [data-toggle="tooltip"]';
            document.querySelectorAll(tooltipSelectors).forEach(el => {
                try {
                    // Try to get and dispose existing tooltip
                    if (typeof bootstrap.Tooltip.getInstance === 'function') {
                        const tooltip = bootstrap.Tooltip.getInstance(el);
                        if (tooltip) tooltip.dispose();
                    } else {
                        // Fallback for older Bootstrap versions
                        $(el).tooltip('dispose');
                    }
                } catch (e) {
                    // Ignore errors during disposal
                }
            });

            // Initialize new tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll(tooltipSelectors));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                try {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                } catch (e) {
                    // Fallback to jQuery tooltip for compatibility
                    $(tooltipTriggerEl).tooltip();
                }
            });
        } else if (typeof $ !== 'undefined' && $.fn.tooltip) {
            // jQuery fallback for both BS4 and BS5 attributes
            $('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]').tooltip();
        }
    }

    function generateMonthlySummary(biometricSummary, monthName) {
        if (!biometricSummary) return '';

        return `

        `;
    }

    function generateCalendarDay(day, dateStr, attendance) {
        console.log(`Generating calendar day for ${dateStr}:`, attendance);
        const statusColors = {
            'present': 'success',
            'absent': 'danger', 
            'late': 'warning',
            'excused': 'info'
        };

        const isToday = dateStr === new Date().toISOString().split('T')[0];
        const todayClass = isToday ? 'today' : '';

        if (!attendance) {
            return `
                <div class="calendar-day no-record ${todayClass}" data-date="${dateStr}">
                    <div class="day-number">${day}</div>
                    <div class="attendance-status">No Record</div>
                </div>
            `;
        }

        const workingHours = attendance.working_hours ? `${attendance.working_hours}h` : 'N/A';
        const checkInTime = attendance.check_in_time || 'N/A';
        const checkOutTime = attendance.check_out_time || 'N/A';

        const tooltipData = {
            date: dateStr,
            status: attendance.status,
            checkIn: checkInTime,
            checkOut: checkOutTime,
            workingHours: workingHours,
            isLate: attendance.is_late_arrival,
            isEarly: attendance.is_early_departure,
            subject: attendance.subject || 'General',
            remarks: attendance.remarks || ''
        };

        return `
            <div class="calendar-day status-${attendance.status} ${todayClass}" 
                 data-date="${dateStr}">
                <div class="day-number">${day}</div>
                <div class="attendance-status ${attendance.status}">${attendance.status.charAt(0).toUpperCase() + attendance.status.slice(1)}</div>
                ${attendance.is_late_arrival ? '<i class="fas fa-clock text-warning" style="font-size: 0.7rem;"></i>' : ''}
                ${attendance.is_early_departure ? '<i class="fas fa-sign-out-alt text-info" style="font-size: 0.7rem;"></i>' : ''}
            </div>
        `;
    }

    function populateAttendanceTable(records, showAll = false) {
        // console.log('Populating attendance table with records:', records);
        let tableHtml = '';
        const limit = 5;

        // Determine records to display: first 5 or all
        const displayRecords = (records && records.length > 0) 
            ? (showAll ? records : records.slice(0, limit)) 
            : [];

        if (displayRecords.length > 0) {
            displayRecords.forEach(record => {
                const statusBadge = getStatusBadge(record.status);

                // Format the date properly
                const formattedDate = record.attendance_date ? 
                    new Date(record.attendance_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    }) : 'N/A';

                // Format check-in time
                let checkInTime = 'N/A';
                if (record.check_in_time) {
                    if (typeof record.check_in_time === 'string') {
                        const timeParts = record.check_in_time.split(':');
                        if (timeParts.length >= 2) {
                            const hour = parseInt(timeParts[0]);
                            const minute = timeParts[1];
                            const ampm = hour >= 12 ? 'PM' : 'AM';
                            const displayHour = hour % 12 || 12;
                            checkInTime = `${displayHour}:${minute} ${ampm}`;
                        }
                    } else {
                        checkInTime = new Date('1970-01-01T' + record.check_in_time).toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                    }
                }

                tableHtml += `
                    <tr>
                        <td>${formattedDate}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });
        } else {
            tableHtml = '<tr><td colspan="2" class="text-center text-muted">No attendance records found</td></tr>';
        }

        $('#attendanceRecordsTable tbody').html(tableHtml);

        // Handle "Load More" Button Logic
        const container = $('#attendanceRecordsTable').closest('.table-responsive');

        // Remove existing button first to prevent duplicates
        $('#btnLoadMoreAttendanceContainer').remove();

        if (!showAll && records && records.length > limit) {
            const remaining = records.length - limit;
            const loadMoreHtml = `
                <div class="text-center mt-2" id="btnLoadMoreAttendanceContainer">
                    <button class="btn btn-sm btn-outline-primary shadow-sm" id="btnLoadMoreAttendance">
                        <i class="fas fa-chevron-down mr-1"></i> View Full History (${remaining} more)
                    </button>
                </div>
            `;

            container.after(loadMoreHtml);

            // Reset container styles (no scroll)
            container.css({
                'max-height': '',
                'overflow-y': ''
            });

            // Bind Click Event
            $('#btnLoadMoreAttendance').off('click').on('click', function() {
                // Remove the button
                $('#btnLoadMoreAttendanceContainer').remove();

                // Show all records
                populateAttendanceTable(records, true);

                // Apply scroll styles to container
                container.css({
                    'max-height': '400px',
                    'overflow-y': 'auto',
                    'border': '1px solid #e9ecef',
                    'border-radius': '8px'
                });
            });
        } else if (showAll) {
             // If showing all, ensure container maintains scroll style
             container.css({
                'max-height': '400px',
                'overflow-y': 'auto',
                'border': '1px solid #e9ecef',
                'border-radius': '8px'
            });
        }
    }

    // Filtering and Export Functions
    function applyFilters() {
        const dateRange = $('#dateRangeFilter').val();
        const viewType = $('#viewTypeFilter').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        // Show loading state
        $('#attendanceLoading').show();
        $('#attendanceContent').hide();

        // Prepare filter parameters
        const filterParams = {
            date_range: dateRange,
            view_type: viewType
        };

        if (dateRange === 'custom' && startDate && endDate) {
            filterParams.start_date = startDate;
            filterParams.end_date = endDate;
        }

        // Make AJAX request with filters
        $.ajax({
            url: `{{ route('admin.students.attendance-data', $student) }}`,
            method: 'GET',
            data: filterParams,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    populateAttendanceData(response.data);
                    $('#attendanceLoading').hide();
                    $('#attendanceContent').show();
                } else {
                    showAttendanceError('Failed to load filtered attendance data');
                }
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'Server error occurred';
                showAttendanceError(errorMessage);
            }
        });
    }

    function exportToPDF() {
        const filterParams = getFilterParams();
        const exportUrl = `{{ route('admin.students.attendance-export', ['student' => $student, 'format' => 'pdf']) }}`;

        // Create form and submit for PDF download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = exportUrl;
        form.target = '_blank';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = $('meta[name="csrf-token"]').attr('content');
        form.appendChild(csrfInput);

        // Add filter parameters
        Object.keys(filterParams).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = filterParams[key];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function exportToExcel() {
        const filterParams = getFilterParams();
        const exportUrl = `{{ route('admin.students.attendance-export', ['student' => $student, 'format' => 'excel']) }}`;

        // Create form and submit for Excel download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = exportUrl;
        form.target = '_blank';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = $('meta[name="csrf-token"]').attr('content');
        form.appendChild(csrfInput);

        // Add filter parameters
        Object.keys(filterParams).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = filterParams[key];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function getFilterParams() {
        const dateRange = $('#dateRangeFilter').val();
        const viewType = $('#viewTypeFilter').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        const params = {
            date_range: dateRange,
            view_type: viewType
        };

        if (dateRange === 'custom' && startDate && endDate) {
            params.start_date = startDate;
            params.end_date = endDate;
        }

        return params;
    }

    // Event listeners for filter controls
    $(document).ready(function() {
        // Show/hide custom date range inputs
        $('#dateRangeFilter').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#customDateRange').show();
            } else {
                $('#customDateRange').hide();
            }
        });

        // Auto-apply filters when date range changes (except custom)
        $('#dateRangeFilter, #viewTypeFilter').on('change', function() {
            if ($('#dateRangeFilter').val() !== 'custom') {
                applyFilters();
            }
        });

        // Apply filters when custom dates are selected
        $('#startDate, #endDate').on('change', function() {
            if ($('#dateRangeFilter').val() === 'custom' && $('#startDate').val() && $('#endDate').val()) {
                applyFilters();
            }
        });
    });

    // Helper function to get status badge HTML
    function getStatusBadge(status) {
        const badges = {
            'present': '<span class="badge badge-success">Present</span>',
            'absent': '<span class="badge badge-danger">Absent</span>',
            'late': '<span class="badge badge-warning">Late</span>',
            'excused': '<span class="badge badge-info">Excused</span>'
        };
        return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
    }

    // Function to show attendance error
    function showAttendanceError(message) {
        $('#attendanceLoading').hide();
        $('#attendanceContent').hide();
        $('#attendanceError').show();
        $('#attendanceErrorMessage').text(message);
    }

    // Initialize calendar on page load
    $(document).ready(function() {
        loadAttendanceData();
    });

    // Missing function implementations
    function openPaymentModal() {
        // Check if payment modal exists
        if ($('#paymentModal').length) {
            $('#paymentModal').modal('show');
        } else {
            // Fallback: redirect to payment page or show alert
            alert('Payment modal not found. Please ensure the payment modal is properly loaded.');
            console.error('Payment modal element not found');
        }
    }



    // End of attendance calendar functionality
    </script>

    <script>
    $(document).ready(function() {
        // Load unassigned fee components when modal opens
        $('#addFeeComponentModal').on('show.bs.modal', function() {
            console.log('Modal opening...');
            loadUnassignedFeeComponents();
        });
    });

    function loadUnassignedFeeComponents() {
        console.log('Loading unassigned fee components...');

        $('#loadingFeeComponents').show();
        $('#feeComponentsList').html('');
        $('#noFeeComponents').hide();

        var url = "{{ route('admin.students.unassigned-fee-components', $student->id) }}";
        console.log('Fetching from:', url);

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('=== FULL RESPONSE ===');
                console.log(JSON.stringify(response, null, 2));
                console.log('=== END RESPONSE ===');

                $('#loadingFeeComponents').hide();

                // Check if response has components
                if (!response.success) {
                    $('#feeComponentsList').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                    return;
                }

                if (!response.components || response.components.length === 0) {
                    console.log('No components in response');
                    $('#noFeeComponents').show();
                    return;
                }

                console.log('Processing ' + response.components.length + ' components');

                var html = '<div class="list-group">';

                $.each(response.components, function(index, component) {
                    console.log('Component ' + index + ':', component);

                    var description = component.description ? '<p class="mb-1 text-muted small">' + component.description + '</p>' : '';
                    var amount = component.amount > 0 ? parseFloat(component.amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '0.00';
                    var warning = component.warning ? '<br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' + component.warning + '</small>' : '';
                    var btnDisabled = component.amount <= 0 ? 'disabled title="No fee structure defined"' : '';
                    var btnClass = component.amount <= 0 ? 'btn-secondary' : 'btn-success';

                    html += '<div class="list-group-item list-group-item-action fee-component-select-item" ' +
                            'style="border-radius: 10px; margin-bottom: 10px; transition: all 0.3s ease;" ' +
                            'data-component-id="' + component.id + '">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                                '<div class="flex-grow-1">' +
                                    '<h6 class="mb-1 font-weight-bold">' + component.name + '</h6>' +
                                    description +
                                    warning +
                                '</div>' +
                                '<div class="text-right ml-3">' +
                                    '<div class="h5 mb-0 text-primary font-weight-bold">₹' + amount + '</div>' +
                                    '<button class="btn btn-sm ' + btnClass + ' mt-2 assign-component-btn" ' +
                                            'data-component-id="' + component.id + '" ' +
                                            'data-component-name="' + component.name + '" ' +
                                            'data-component-amount="' + component.amount + '" ' +
                                            btnDisabled + '>' +
                                        '<i class="fas fa-plus mr-1"></i> Assign' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                });

                html += '</div>';

                console.log('Setting HTML, length:', html.length);
                $('#feeComponentsList').html(html);

                // Add hover effects
                $('.fee-component-select-item').hover(
                    function() {
                        $(this).css({'background-color': '#f8f9fc', 'box-shadow': '0 5px 15px rgba(0,0,0,0.1)'});
                    },
                    function() {
                        $(this).css({'background-color': '', 'box-shadow': ''});
                    }
                );

                console.log('Components rendered successfully');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });

                $('#loadingFeeComponents').hide();
                var errorMessage = 'Failed to load fee components';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                $('#feeComponentsList').html(
                    '<div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-triangle mr-2"></i>' +
                        errorMessage +
                        '<br><small class="mt-2 d-block">Status: ' + xhr.status + '</small>' +
                        '<br><small>Response: ' + xhr.responseText + '</small>' +
                    '</div>'
                );
            }
        });
    }

    // Handle component assignment
    $(document).on('click', '.assign-component-btn', function(e) {
        e.stopPropagation();
        var componentId = $(this).data('component-id');
        var componentName = $(this).data('component-name');
        var componentAmount = $(this).data('component-amount');

        // If amount is 0 or not set, prompt for it
        if (!componentAmount || componentAmount == 0) {
            var amount = prompt('Enter the fee amount for ' + componentName + ':');
            if (amount === null || amount === '' || isNaN(amount) || parseFloat(amount) < 0) {
                showNotification('Please enter a valid amount', 'error');
                return;
            }
            componentAmount = parseFloat(amount);
        }

        if (confirm('Assign "' + componentName + '" (₹' + parseFloat(componentAmount).toLocaleString('en-IN') + ') to this student?')) {
            assignFeeComponent(componentId, componentName, componentAmount);
        }
    });

    function assignFeeComponent(componentId, componentName, amount) {
        var button = $('.assign-component-btn[data-component-id="' + componentId + '"]');
        var originalHtml = button.html();

        button.html('<i class="fas fa-spinner fa-spin"></i> Assigning...').prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.students.assign-fee-component', $student->id) }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                fee_category_id: componentId,
                amount: amount  // ← Make sure this is being sent
            },
            success: function(response) {
                if (response.success) {
                    showNotification(componentName + ' has been assigned successfully!', 'success');
                    $('#addFeeComponentModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.message || 'Failed to assign fee component', 'error');
                    button.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                var errorMessage = 'Error assigning fee component';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showNotification(errorMessage, 'error');
                button.html(originalHtml).prop('disabled', false);
            }
        });
    }
    </script>
@endpush