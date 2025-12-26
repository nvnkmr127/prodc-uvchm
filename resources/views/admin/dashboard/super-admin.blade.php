@extends('layouts.theme')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-crown fa-3x"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Super Admin Dashboard</h1>
                                    <p class="mb-0">Complete system oversight and management center</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="text-end">
                                <div class="mb-2">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-clock me-2"></i>{{ now()->format('M d, Y • H:i') }}
                                    </span>
                                </div>
                                <small class="opacity-75">
                                    Last login: {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'First time' }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Primary Stats Cards Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($dashboard_data['total_students'] ?? 1250) }}</div>
                            <div class="text-success mt-2">
                                <i class="fas fa-arrow-up me-1"></i>
                                <small>{{ $dashboard_data['student_growth'] ?? 12 }}% this month</small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($dashboard_data['total_revenue'] ?? 4200000) }}</div>
                            <div class="text-{{ ($dashboard_data['revenue_growth'] ?? 8) >= 0 ? 'success' : 'danger' }} mt-2">
                                <i class="fas fa-arrow-{{ ($dashboard_data['revenue_growth'] ?? 8) >= 0 ? 'up' : 'down' }} me-1"></i>
                                <small>{{ abs($dashboard_data['revenue_growth'] ?? 8) }}% from last month</small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Courses</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $dashboard_data['active_courses'] ?? 25 }}</div>
                            <div class="text-info mt-2">
                                <i class="fas fa-layer-group me-1"></i>
                                <small>{{ $dashboard_data['total_batches'] ?? 48 }} Active Batches</small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Outstanding Fees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($dashboard_data['outstanding_fees'] ?? 850000) }}</div>
                            <div class="text-danger mt-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <small>{{ $dashboard_data['defaulters_count'] ?? 45 }} defaulters</small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Stats Cards Row -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-left-secondary shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Faculty</div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $dashboard_data['total_faculty'] ?? 85 }}</div>
                    <small class="text-success">{{ $dashboard_data['active_faculty'] ?? 78 }} Active</small>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-left-dark shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Alumni</div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $dashboard_data['total_alumni'] ?? 2150 }}</div>
                    <small class="text-info">Graduated</small>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Enquiries</div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $dashboard_data['total_enquiries'] ?? 156 }}</div>
                    <small class="text-warning">{{ $dashboard_data['pending_enquiries'] ?? 23 }} Pending</small>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Attendance</div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $dashboard_data['avg_attendance'] ?? 87 }}%</div>
                    <small class="text-success">Today's Rate</small>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Collections</div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $dashboard_data['collection_rate'] ?? 84 }}%</div>
                    <small class="text-info">Efficiency</small>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
            <div class="card border-left-danger shadow h-100">
                <div class="card-body text-center">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Admissions</div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $dashboard_data['new_admissions'] ?? 45 }}</div>
                    <small class="text-success">This Month</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Row -->
    <div class="row mb-4">
        <!-- Revenue vs Expenses Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line me-2"></i>Revenue vs Expenses (6 Months)
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#">View Details</a>
                            <a class="dropdown-item" href="#">Export Data</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueExpenseChart" style="height: 320px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Student Distribution Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Students by Course
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="studentDistributionChart" style="height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>


<hr>

<div class="row mb-4">
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning">
                    <i class="fas fa-hourglass-half me-2"></i>Pending Component Payments
                </h6>
            </div>
            <div class="card-body">
                @php
                    $pendingPaymentsData = $dashboard_data['pending_component_payments'] ?? [
                        'total_pending_amount' => 1250000,
                        'overdue_amount' => 450000,
                        'total_students_with_pending' => 89,
                        'due_this_week' => 200000,
                        'category_breakdown' => [
                            'Tuition Fee' => ['total_amount' => 800000, 'student_count' => 45],
                            'Lab Fee' => ['total_amount' => 250000, 'student_count' => 32],
                            'Library Fee' => ['total_amount' => 120000, 'student_count' => 28],
                            'Exam Fee' => ['total_amount' => 80000, 'student_count' => 15]
                        ]
                    ];
                    $categoryBreakdown = $pendingPaymentsData['category_breakdown'];
                    $totalPendingAmountForPercentage = $pendingPaymentsData['total_pending_amount'] > 0 ? $pendingPaymentsData['total_pending_amount'] : 1;
                @endphp

                <div class="row text-center mb-4">
                    <div class="col-6 mb-3">
                        <div class="h4 mb-0 font-weight-bold text-warning">₹{{ number_format($pendingPaymentsData['total_pending_amount']) }}</div>
                        <div class="text-xs text-uppercase text-muted">Total Pending</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 mb-0 font-weight-bold text-danger">₹{{ number_format($pendingPaymentsData['overdue_amount']) }}</div>
                        <div class="text-xs text-uppercase text-muted">Overdue Amount</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 mb-0 font-weight-bold text-info">{{ $pendingPaymentsData['total_students_with_pending'] }}</div>
                        <div class="text-xs text-uppercase text-muted">Students</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 mb-0 font-weight-bold text-primary">₹{{ number_format($pendingPaymentsData['due_this_week']) }}</div>
                        <div class="text-xs text-uppercase text-muted">Due This Week</div>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="font-weight-bold mb-2">Pending by Category</h6>
                    @foreach($categoryBreakdown as $category => $data)
                        @php
                            $percentage = ($data['total_amount'] / $totalPendingAmountForPercentage) * 100;
                        @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">{{ $category }}</span>
                                <span class="small fw-bold">₹{{ number_format($data['total_amount']) }}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-danger">
                    <i class="fas fa-user-slash me-2"></i>Non-Paying Students
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="font-weight-bold mb-2">Defaulters by Course</h6>
                    @php
    $nonPayingData = $dashboard_data['non_paying_students'] ?? [];

    // FIX: Provide a default empty array if the key is not defined.
    $courseDefaulters = $nonPayingData['course_defaulters'] ?? []; 
    
    $maxDefaulters = !empty($courseDefaulters) ? max(array_values($courseDefaulters)) : 1;
@endphp
@foreach($courseDefaulters as $course => $count)
<div class="d-flex justify-content-between align-items-center mb-2">
    <span class="small">{{ $course }}</span>
    <div class="d-flex align-items-center">
        <div class="progress me-2" style="width: 100px; height: 8px;">
            <div class="progress-bar bg-danger" style="width: {{ ($count / $maxDefaulters) * 100 }}%"></div>
        </div>
        <span class="badge bg-danger">{{ $count }}</span>
    </div>
</div>
@endforeach
                </div>

                <div class="text-center mt-3">
                    <a href="#" class="btn btn-danger btn-sm">
                        <i class="fas fa-list me-2"></i>View All Defaulters
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row mb-4">
    <div class="col-xl-4 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users me-2"></i>Student Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="studentDistributionChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-calendar-alt me-2"></i>Monthly Collection
                </h6>
            </div>
            <div class="card-body">
                <canvas id="monthlyCollectionChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-user-check me-2"></i>Attendance Analytics
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="h3 mb-0 font-weight-bold text-info">{{ $dashboard_data['attendance_analytics']['attendance_rate'] ?? 87 }}%</div>
                    <div class="text-xs text-uppercase text-muted">Today's Overall Rate</div>
                </div>
                <canvas id="s" style="height: 25px;"></canvas>
            </div>
        </div>
    </div>
</div>


    <!-- Fee Collection and Defaulters Analysis -->
    <div class="row mb-4">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-money-bill-wave me-2"></i>Fee Collection Overview
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $feeCollection = [
                            'total_billed' => $dashboard_data['fee_collection']['total_billed'] ?? 5000000,
                            'total_collected' => $dashboard_data['fee_collection']['total_collected'] ?? 4200000,
                            'outstanding' => $dashboard_data['fee_collection']['outstanding'] ?? 800000,
                            'collection_rate' => $dashboard_data['fee_collection']['collection_rate'] ?? 84,
                            'overdue' => $dashboard_data['fee_collection']['overdue'] ?? 350000,
                            'advance' => $dashboard_data['fee_collection']['advance'] ?? 150000
                        ];
                    @endphp
                    <div class="row text-center mb-3">
                        <div class="col-6 mb-3">
                            <div class="h5 mb-0 font-weight-bold text-success">₹{{ number_format($feeCollection['total_collected']) }}</div>
                            <div class="text-xs text-uppercase text-muted">Total Collected</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h5 mb-0 font-weight-bold text-danger">₹{{ number_format($feeCollection['outstanding']) }}</div>
                            <div class="text-xs text-uppercase text-muted">Outstanding</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h5 mb-0 font-weight-bold text-warning">₹{{ number_format($feeCollection['overdue']) }}</div>
                            <div class="text-xs text-uppercase text-muted">Overdue</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h5 mb-0 font-weight-bold text-info">₹{{ number_format($feeCollection['advance']) }}</div>
                            <div class="text-xs text-uppercase text-muted">Advance</div>
                        </div>
                    </div>
                    
                    <!-- Collection Progress -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="font-weight-bold">Collection Progress</span>
                            <span class="text-muted">{{ $feeCollection['collection_rate'] }}%</span>
                        </div>
                        <div class="progress" style="height: 12px;">
                            <div class="progress-bar bg-success" style="width: {{ $feeCollection['collection_rate'] }}%"></div>
                        </div>
                    </div>

                    <!-- Monthly Collection Chart -->
                    <canvas id="monthlyCollectionChart" style="height: 200px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Defaulters -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Payment Defaulters Analysis
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $defaultersData = [
                            'total_defaulters' => $dashboard_data['defaulters_analysis']['total_defaulters'] ?? 45,
                            'critical_defaulters' => $dashboard_data['defaulters_analysis']['critical_defaulters'] ?? 12,
                            'moderate_defaulters' => $dashboard_data['defaulters_analysis']['moderate_defaulters'] ?? 23,
                            'recent_defaulters' => $dashboard_data['defaulters_analysis']['recent_defaulters'] ?? 10,
                            'total_overdue_amount' => $dashboard_data['defaulters_analysis']['total_overdue_amount'] ?? 850000,
                            'avg_overdue_per_student' => $dashboard_data['defaulters_analysis']['avg_overdue_per_student'] ?? 18888
                        ];
                    @endphp

                    <div class="row text-center mb-4">
                        <div class="col-4">
                            <div class="h4 mb-0 font-weight-bold text-danger">{{ $defaultersData['critical_defaulters'] }}</div>
                            <div class="text-xs text-uppercase text-muted">Critical</div>
                            <small class="text-danger">>&nbsp;₹50k overdue</small>
                        </div>
                        <div class="col-4">
                            <div class="h4 mb-0 font-weight-bold text-warning">{{ $defaultersData['moderate_defaulters'] }}</div>
                            <div class="text-xs text-uppercase text-muted">Moderate</div>
                            <small class="text-warning">₹20k-50k overdue</small>
                        </div>
                        <div class="col-4">
                            <div class="h4 mb-0 font-weight-bold text-info">{{ $defaultersData['recent_defaulters'] }}</div>
                            <div class="text-xs text-uppercase text-muted">Recent</div>
                            <small class="text-info">&lt;₹20k overdue</small>
                        </div>
                    </div>

                    <!-- Defaulters by Course -->
                    <div class="mb-3">
                        <h6 class="font-weight-bold mb-2">Defaulters by Course</h6>
                        @php
                            $activityLogs = $dashboard_data['recent_activities'] ?? [
                                ['type' => 'payment', 'user' => 'John Doe', 'action' => 'Made fee payment of ₹25,000', 'time' => '2 minutes ago', 'icon' => 'fa-rupee-sign', 'color' => 'success'],
                                ['type' => 'admission', 'user' => 'Admin', 'action' => 'New student admission - Sarah Wilson', 'time' => '15 minutes ago', 'icon' => 'fa-user-plus', 'color' => 'primary'],
                                ['type' => 'system', 'user' => 'System', 'action' => 'Daily backup completed successfully', 'time' => '1 hour ago', 'icon' => 'fa-database', 'color' => 'info'],
                                ['type' => 'alert', 'user' => 'System', 'action' => 'Payment reminder sent to 45 students', 'time' => '2 hours ago', 'icon' => 'fa-bell', 'color' => 'warning'],
                                ['type' => 'attendance', 'user' => 'Prof. Johnson', 'action' => 'Attendance marked for CS-101', 'time' => '3 hours ago', 'icon' => 'fa-check-circle', 'color' => 'success'],
                                ['type' => 'enquiry', 'user' => 'Mike Chen', 'action' => 'New enquiry for Engineering course', 'time' => '4 hours ago', 'icon' => 'fa-phone', 'color' => 'info'],
                                ['type' => 'payment', 'user' => 'Emma Davis', 'action' => 'Partial payment of ₹15,000 received', 'time' => '5 hours ago', 'icon' => 'fa-rupee-sign', 'color' => 'warning'],
                                ['type' => 'system', 'user' => 'Admin', 'action' => 'System maintenance scheduled', 'time' => '6 hours ago', 'icon' => 'fa-tools', 'color' => 'secondary'],
                                ['type' => 'admission', 'user' => 'Admin', 'action' => 'Batch CS-2025 created with 30 students', 'time' => '1 day ago', 'icon' => 'fa-users', 'color' => 'primary'],
                                ['type' => 'alert', 'user' => 'System', 'action' => 'Fee defaulter list updated - 45 students', 'time' => '1 day ago', 'icon' => 'fa-exclamation-triangle', 'color' => 'danger']
                            ];
                        @endphp
                        
                        @foreach($activityLogs as $log)
                        <div class="d-flex align-items-start py-2 border-bottom">
                            <div class="bg-{{ $log['color'] }} rounded-circle text-white d-flex align-items-center justify-content-center me-3" 
                                 style="width: 35px; height: 35px; flex-shrink: 0;">
                                <i class="fas {{ $log['icon'] }} fa-sm"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold text-gray-800">{{ $log['action'] }}</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">by {{ $log['user'] }}</small>
                                    <small class="text-muted">{{ $log['time'] }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Alerts -->
    <div class="row mb-4">
        <!-- Quick Actions -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if(Route::has('admin.students.index'))
                        <div class="col-md-4 col-6 mb-3">
                            <a href="{{ route('admin.students.index') }}" class="btn btn-outline-primary btn-block text-center text-decoration-none h-100">
                                <i class="fas fa-users fa-2x mb-2"></i><br>
                                <small>Manage Students</small>
                            </a>
                        </div>
                        @endif
                        
                        @if(Route::has('admin.component-payments.index'))
                        <div class="col-md-4 col-6 mb-3">
                            <a href="{{ route('admin.component-payments.index') }}" class="btn btn-outline-success btn-block text-center text-decoration-none h-100">
                                <i class="fas fa-money-bill fa-2x mb-2"></i><br>
                                <small>Payment Records</small>
                            </a>
                        </div>
                        @endif
                        
                        @if(Route::has('admin.enquiries.index'))
                        <div class="col-md-4 col-6 mb-3">
                            <a href="{{ route('admin.enquiries.index') }}" class="btn btn-outline-warning btn-block text-center text-decoration-none h-100">
                                <i class="fas fa-phone fa-2x mb-2"></i><br>
                                <small>Enquiries</small>
                            </a>
                        </div>
                        @endif
                        
                        @if(Route::has('admin.settings.index'))
                        <div class="col-md-4 col-6 mb-3">
                            <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-block text-center text-decoration-none h-100">
                                <i class="fas fa-cogs fa-2x mb-2"></i><br>
                                <small>System Settings</small>
                            </a>
                        </div>
                        @endif

                        <div class="col-md-4 col-6 mb-3">
                            <a href="#" class="btn btn-outline-info btn-block text-center text-decoration-none h-100">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                <small>Reports</small>
                            </a>
                        </div>

                        <div class="col-md-4 col-6 mb-3">
                            <a href="#" class="btn btn-outline-danger btn-block text-center text-decoration-none h-100">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                                <small>Defaulters</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Alerts & Notifications -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-bell me-2"></i>System Alerts & Notifications
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $systemAlerts = $dashboard_data['system_alerts'] ?? [
                            ['title' => 'Fee Defaulters Alert', 'message' => '45 students have overdue payments totaling ₹8.5L', 'level' => 'danger', 'icon' => 'exclamation-triangle', 'time' => 'Now', 'action_url' => '#'],
                            ['title' => 'Storage Warning', 'message' => 'Server storage usage is at 78% capacity', 'level' => 'warning', 'icon' => 'hdd', 'time' => '2 hours ago'],
                            ['title' => 'Backup Completed', 'message' => 'Daily system backup completed successfully', 'level' => 'success', 'icon' => 'check-circle', 'time' => '6 hours ago'],
                            ['title' => 'New Enquiries', 'message' => '23 new enquiries received today', 'level' => 'info', 'icon' => 'phone', 'time' => '1 day ago'],
                            ['title' => 'System Update', 'message' => 'Security update available for installation', 'level' => 'info', 'icon' => 'download', 'time' => '2 days ago']
                        ];
                    @endphp

                    <div style="max-height: 300px; overflow-y: auto;">
                        @foreach($systemAlerts as $alert)
                        <div class="alert alert-{{ $alert['level'] }} d-flex align-items-start py-2 mb-2" role="alert">
                            <i class="fas fa-{{ $alert['icon'] }} me-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold mb-1">{{ $alert['title'] }}</div>
                                <div class="small mb-1">{{ $alert['message'] }}</div>
                                <small class="text-muted">{{ $alert['time'] }}</small>
                            </div>
                            @if(isset($alert['action_url']))
                            <div class="ms-2">
                                <a href="{{ $alert['action_url'] }}" class="btn btn-sm btn-outline-{{ $alert['level'] }}">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-sm btn-primary">
                            <i class="fas fa-bell me-2"></i>View All Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Analytics and Trends -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-chart-area me-2"></i>Financial Analytics & Trends (Last 12 Months)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 col-6 text-center">
                            <div class="h4 mb-0 font-weight-bold text-success">₹{{ number_format($dashboard_data['yearly_revenue'] ?? 42000000) }}</div>
                            <div class="text-xs text-uppercase text-muted">Yearly Revenue</div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 text-center">
                            <div class="h4 mb-0 font-weight-bold text-info">₹{{ number_format($dashboard_data['monthly_avg'] ?? 3500000) }}</div>
                            <div class="text-xs text-uppercase text-muted">Monthly Average</div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 text-center">
                            <div class="h4 mb-0 font-weight-bold text-warning">₹{{ number_format($dashboard_data['expenses'] ?? 12000000) }}</div>
                            <div class="text-xs text-uppercase text-muted">Total Expenses</div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 text-center">
                            <div class="h4 mb-0 font-weight-bold text-primary">₹{{ number_format($dashboard_data['profit'] ?? 30000000) }}</div>
                            <div class="text-xs text-uppercase text-muted">Net Profit</div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 text-center">
                            <div class="h4 mb-0 font-weight-bold text-success">{{ $dashboard_data['profit_margin'] ?? 71.4 }}%</div>
                            <div class="text-xs text-uppercase text-muted">Profit Margin</div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 text-center">
                            <div class="h4 mb-0 font-weight-bold text-info">{{ $dashboard_data['growth_rate'] ?? 15.2 }}%</div>
                            <div class="text-xs text-uppercase text-muted">YoY Growth</div>
                        </div>
                    </div>
                    <canvas id="financialTrendsChart" style="height: 350px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Footer -->
    <div class="row">
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card bg-primary text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 font-weight-bold">{{ $dashboard_data['active_users'] ?? 24 }}</div>
                    <div class="text-uppercase small">Active Users</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card bg-success text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 font-weight-bold">{{ $dashboard_data['server_uptime'] ?? '99.9%' }}</div>
                    <div class="text-uppercase small">Server Uptime</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card bg-info text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 font-weight-bold">{{ $dashboard_data['response_time'] ?? '120ms' }}</div>
                    <div class="text-uppercase small">Response Time</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card bg-warning text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 font-weight-bold">{{ $dashboard_data['storage_used'] ?? '45%' }}</div>
                    <div class="text-uppercase small">Storage Used</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card bg-secondary text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 font-weight-bold">{{ $dashboard_data['database_size'] ?? '2.4GB' }}</div>
                    <div class="text-uppercase small">Database Size</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card bg-dark text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 font-weight-bold">{{ $dashboard_data['api_calls'] ?? '12.5K' }}</div>
                    <div class="text-uppercase small">API Calls Today</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Customization Modal -->
<div class="modal fade" id="dashboardCustomizationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-palette me-2"></i>Dashboard Customization
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-desktop fa-3x text-primary mb-3"></i>
                                <h6 class="font-weight-bold">Dashboard Builder</h6>
                                <p class="text-muted small">Customize widgets and layout</p>
                                @if(Route::has('admin.dashboard-builder.index'))
                                <a href="{{ route('admin.dashboard-builder.index') }}" class="btn btn-primary btn-sm">
                                    Open Builder
                                </a>
                                @else
                                <button class="btn btn-primary btn-sm" onclick="alert('Dashboard builder coming soon!')">
                                    Open Builder
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-cog fa-3x text-secondary mb-3"></i>
                                <h6 class="font-weight-bold">System Settings</h6>
                                <p class="text-muted small">Configure system preferences</p>
                                @if(Route::has('admin.settings.index'))
                                <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary btn-sm">
                                    Open Settings
                                </a>
                                @else
                                <button class="btn btn-secondary btn-sm" onclick="alert('Settings coming soon!')">
                                    Open Settings
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-3x text-success mb-3"></i>
                                <h6 class="font-weight-bold">Analytics</h6>
                                <p class="text-muted small">View detailed reports</p>
                                <button class="btn btn-success btn-sm" onclick="alert('Advanced analytics coming soon!')">
                                    View Reports
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                                <h6 class="font-weight-bold">Notifications</h6>
                                <p class="text-muted small">Manage notification settings</p>
                                <button class="btn btn-warning btn-sm" onclick="alert('Notification settings coming soon!')">
                                    Configure
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<button class="btn btn-primary rounded-circle position-fixed" 
        style="bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1000;"
        data-bs-toggle="modal" data-bs-target="#dashboardCustomizationModal" 
        title="Customize Dashboard">
    <i class="fas fa-palette"></i>
</button>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Common colors for charts
    const colors = {
        primary: '#4e73df',
        success: '#1cc88a',
        info: '#36b9cc',
        warning: '#f6c23e',
        danger: '#e74a3b',
        secondary: '#858796'
    };

    // Helper function for number formatting
    const formatCurrency = (value) => '₹' + value.toLocaleString('en-IN');
    const formatK = (value) => '₹' + (value / 1000) + 'K';

    // Daily Payment Trends Chart
    const dailyPaymentCtx = document.getElementById('dailyPaymentTrendsChart');
    if (dailyPaymentCtx) {
        new Chart(dailyPaymentCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Daily Collection',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
                    borderColor: colors.success,
                    backgroundColor: colors.success + '1A', // 10% opacity
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (value) => formatK(value) }
                    }
                }
            }
        });
    }

    // Student Distribution Pie Chart
    const studentDistCtx = document.getElementById('studentDistributionChart');
    if (studentDistCtx) {
        new Chart(studentDistCtx, {
            type: 'doughnut',
            data: {
                labels: ['Computer Science', 'Business Admin', 'Engineering', 'Arts & Design'],
                datasets: [{
                    data: [320, 280, 350, 180],
                    backgroundColor: [colors.primary, colors.success, colors.info, colors.warning],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Monthly Collection Chart
    const monthlyCollectionCtx = document.getElementById('monthlyCollectionChart');
    if (monthlyCollectionCtx) {
        new Chart(monthlyCollectionCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Collections',
                    data: [380000, 420000, 390000, 450000, 480000, 520000],
                    backgroundColor: colors.success,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (value) => formatK(value) }
                    }
                }
            }
        });
    }

    // Attendance Trend Chart
    const attendanceTrendCtx = document.getElementById('attendanceTrendChart');
    if (attendanceTrendCtx) {
        new Chart(attendanceTrendCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Attendance %',
                    data: [88, 92, 87, 91, 89, 85],
                    borderColor: colors.info,
                    backgroundColor: colors.info + '20',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        min: 80,
                        max: 100,
                        ticks: { callback: (value) => value + '%' }
                    }
                }
            }
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart colors
    const colors = {
        primary: '#4e73df',
        success: '#1cc88a',
        info: '#36b9cc',
        warning: '#f6c23e',
        danger: '#e74a3b',
        secondary: '#858796',
        light: '#f8f9fc',
        dark: '#5a5c69'
    };

    // Revenue vs Expenses Chart
    @php
        $revenueExpenseData = $dashboard_data['revenue_expense_chart'] ?? [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'revenue' => [420000, 380000, 450000, 390000, 520000, 480000],
            'expenses' => [180000, 160000, 190000, 170000, 210000, 200000]
        ];
    @endphp
    
    const revenueExpenseCtx = document.getElementById('revenueExpenseChart');
    if (revenueExpenseCtx) {
        new Chart(revenueExpenseCtx, {
            type: 'line',
            data: {
                labels: @json($revenueExpenseData['labels']),
                datasets: [{
                    label: 'Revenue',
                    data: @json($revenueExpenseData['revenue']),
                    borderColor: colors.success,
                    backgroundColor: colors.success + '20',
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'Expenses',
                    data: @json($revenueExpenseData['expenses']),
                    borderColor: colors.danger,
                    backgroundColor: colors.danger + '20',
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₹' + (context.parsed.y/1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + (value/1000000) + 'M';
                            }
                        }
                    }
                }
            }
        });
    }

    // Real-time clock update
    function updateClock() {
        const now = new Date();
        const options = {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        const timeString = now.toLocaleDateString('en-US', options).replace(',', ' •');
        
        const clockElement = document.querySelector('.badge');
        if (clockElement && clockElement.innerHTML.includes('fa-clock')) {
            clockElement.innerHTML = `<i class="fas fa-clock me-2"></i>${timeString}`;
        }
    }

    // Update clock every minute
    setInterval(updateClock, 60000);

    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        console.log('Dashboard data refresh...');
        // Implement AJAX refresh for real-time data updates
        refreshDashboardMetrics();
    }, 300000);

    // Add smooth transitions to cards
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'all 0.3s ease';
            this.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.25)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Animate stat cards on load
    const statCards = document.querySelectorAll('.card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });

    console.log('✅ Super Admin Dashboard loaded successfully!');
    console.log('📊 Dashboard metrics initialized');
});

// Utility functions for dashboard management
function refreshDashboard() {
    const fab = document.querySelector('.position-fixed');
    const originalHTML = fab.innerHTML;
    fab.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function refreshDashboardMetrics() {
    // AJAX call to refresh specific metrics without page reload
    fetch('/admin/dashboard/metrics', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Update specific dashboard metrics
        updateDashboardValues(data);
        console.log('Dashboard metrics refreshed');
    })
    .catch(error => {
        console.log('Dashboard refresh failed:', error);
    });
}

function updateDashboardValues(data) {
    // Update stat cards with new values
    if (data.total_students) {
        document.querySelector('[data-metric="total_students"]').textContent = 
            new Intl.NumberFormat().format(data.total_students);
    }
    
    if (data.total_revenue) {
        document.querySelector('[data-metric="total_revenue"]').textContent = 
            '₹' + new Intl.NumberFormat().format(data.total_revenue);
    }
    
    // Add more metric updates as needed
}

function exportDashboardData(format = 'pdf') {
    // Export dashboard data in various formats
    const url = `/admin/dashboard/export?format=${format}`;
    window.open(url, '_blank');
}

function scheduleDashboardReport() {
    // Schedule automated dashboard reports
    alert('Dashboard report scheduling will be available soon!');
}

// Keyboard shortcuts for power users
document.addEventListener('keydown', function(e) {
    // Ctrl + R for refresh
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshDashboard();
    }
    
    // Ctrl + D for dashboard builder
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        const builderLink = document.querySelector('a[href*="dashboard-builder"]');
        if (builderLink) {
            window.location.href = builderLink.href;
        }
    }
    
    // Ctrl + E for export
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        exportDashboardData();
    }
    
    // Ctrl + M for metrics refresh
    if (e.ctrlKey && e.key === 'm') {
        e.preventDefault();
        refreshDashboardMetrics();
    }
});

// Advanced dashboard features
function initializeRealTimeUpdates() {
    // WebSocket connection for real-time updates
    if (typeof io !== 'undefined') {
        const socket = io();
        
        socket.on('dashboard_update', function(data) {
            updateDashboardValues(data);
        });
        
        socket.on('new_alert', function(alert) {
            showDashboardAlert(alert);
        });
    }
}

function showDashboardAlert(alert) {
    // Show toast notification for new alerts
    const toastHtml = `
        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-${alert.icon} text-${alert.level} me-2"></i>
                <strong class="me-auto">${alert.title}</strong>
                <small class="text-muted">now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${alert.message}
            </div>
        </div>
    `;
    
    // Add toast to container and show
    const toastContainer = document.querySelector('.toast-container') || 
                          document.createElement('div');
    if (!document.querySelector('.toast-container')) {
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toast = new bootstrap.Toast(toastContainer.lastElementChild);
    toast.show();
}

// Initialize advanced features
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize real-time updates
    setTimeout(initializeRealTimeUpdates, 2000);
    
    // Performance monitoring
    const observer = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
            console.log('Performance:', entry.name, entry.duration);
        }
    });
    observer.observe({entryTypes: ['measure', 'navigation']});
});

// Dashboard customization functions
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('dashboard_theme', 
        document.body.classList.contains('dark-mode') ? 'dark' : 'light');
}

function saveDashboardLayout() {
    const layout = {
        timestamp: new Date().toISOString(),
        version: '1.0',
        widgets: []
    };
    
    document.querySelectorAll('.card').forEach((card, index) => {
        layout.widgets.push({
            id: card.id || `widget_${index}`,
            position: index,
            visible: !card.classList.contains('d-none')
        });
    });
    
    localStorage.setItem('dashboard_layout', JSON.stringify(layout));
    console.log('Dashboard layout saved');
}

function loadDashboardLayout() {
    const savedLayout = localStorage.getItem('dashboard_layout');
    if (savedLayout) {
        try {
            const layout = JSON.parse(savedLayout);
            console.log('Loading dashboard layout:', layout);
            // Implement layout restoration logic here
        } catch (e) {
            console.error('Failed to load dashboard layout:', e);
        }
    }
}

// Load saved preferences on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load theme preference
    const savedTheme = localStorage.getItem('dashboard_theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
    
    // Load layout preference
    loadDashboardLayout();
});
</script>
@endpush