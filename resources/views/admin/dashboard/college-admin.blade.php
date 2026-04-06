@extends('layouts.theme')

@section('title', 'Academic Dashboard')

@push('styles')
    <style>
        /* Dashboard Color Scheme */
        :root {
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #7c3aed 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="1.5" fill="white" opacity="0.1"/></svg>');
            opacity: 0.3;
        }

        .dashboard-header .content {
            position: relative;
            z-index: 1;
        }

        /* Stats Grid */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.enrollment {
            background: var(--primary-color);
        }

        .stat-icon.payments {
            background: var(--success-color);
        }

        .stat-icon.attendance {
            background: var(--info-color);
        }

        .stat-icon.activity {
            background: var(--warning-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-trend {
            margin-top: 0.75rem;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
        }

        .trend-positive {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .trend-negative {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        /* Time Period Buttons */
        .period-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .period-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .period-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .period-btn:hover:not(.active) {
            border-color: var(--primary-color);
            background: #f8faff;
        }

        /* Dashboard Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Widget Cards */
        .widget-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .widget-header {
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid #f1f3f4;
            background: #fafbfc;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .widget-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .widget-title i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        .widget-body {
            padding: 1.5rem;
        }

        /* Payment Stats Grid */
        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-stat {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .payment-stat-amount {
            font-size: 1.375rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .payment-stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        /* Activity Log */
        .activity-log {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background-color: #f9fafb;
            margin: 0 -1rem;
            padding-left: 1rem;
            padding-right: 1rem;
            border-radius: 6px;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .activity-icon.payment {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .activity-icon.login {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
        }

        .activity-icon.update {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .activity-icon.delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 0.875rem;
            color: #374151;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .activity-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-left: auto;
            flex-shrink: 0;
        }

        /* Attendance Overview */
        .attendance-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .attendance-stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .attendance-percentage {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .attendance-percentage.excellent {
            color: var(--success-color);
        }

        .attendance-percentage.good {
            color: var(--info-color);
        }

        .attendance-percentage.average {
            color: var(--warning-color);
        }

        .attendance-percentage.poor {
            color: var(--danger-color);
        }

        .attendance-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }

            .payment-stats {
                grid-template-columns: 1fr;
            }

            .period-selector {
                justify-content: center;
            }

            .period-btn {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }
        }

        /* Custom Scrollbar */
        .activity-log::-webkit-scrollbar {
            width: 6px;
        }

        .activity-log::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .activity-log::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .activity-log::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Loading States */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            flex-direction: column;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="container">
                <div class="content">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2">
                                <i class="fas fa-graduation-cap mr-3"></i> Welcome back,
                                <strong>{{ $dashboard_data['user_name'] ?? auth()->user()->name }}</strong>
                            </h1>
                            <p class="mb-0 opacity-75">
                                ! Here's your academic overview.
                            </p>
                        </div>
                        <div class="col-md-4 text-md-right">
                            <div class="dashboard-date">
                                <div class="text-sm opacity-75">Academic Year</div>
                                <div class="h5 mb-1">{{ $dashboard_data['academic_year'] ?? '2024-25' }}</div>
                                <div class="text-sm opacity-75" id="current-time">
                                    {{ $dashboard_data['current_date'] ?? now()->format('d M Y') }} •
                                    <span
                                        id="live-time">{{ $dashboard_data['current_time'] ?? now()->format('H:i:s') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Birthday Celebrations -->
        <style>
            .birthday-glass-card {
                background: white;
                border-radius: 20px;
                border: 1px solid rgba(0, 0, 0, 0.05);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                overflow: hidden;
                margin-bottom: 2rem;
            }

            .bday-accent {
                background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%);
                color: white;
                padding: 2.5rem 2rem;
                display: flex;
                flex-direction: column;
                justify-content: center;
                position: relative;
                overflow: hidden;
            }

            .bday-accent::after {
                content: '🎈';
                position: absolute;
                font-size: 6rem;
                right: -1rem;
                bottom: -1rem;
                opacity: 0.15;
                transform: rotate(-15deg);
            }

            .bday-list-section {
                padding: 1.5rem;
                border-right: 1px solid rgba(0, 0, 0, 0.05);
            }

            .bday-list-section:last-child {
                border-right: none;
            }

            .bday-student-card {
                background: #f8fafc;
                padding: 0.75rem;
                border-radius: 12px;
                margin-bottom: 0.75rem;
                transition: all 0.3s ease;
                border: 1px solid transparent;
            }

            .bday-student-card:hover {
                transform: translateX(5px);
                border-color: #DD2476;
                background: white;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }

            .bday-empty-state {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                min-height: 150px;
                opacity: 0.6;
            }

            .pulse-indicator {
                width: 10px;
                height: 10px;
                background: #fff;
                border-radius: 50%;
                display: inline-block;
                margin-right: 8px;
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
                animation: pulse-white 2s infinite;
            }

            @keyframes pulse-white {
                0% {
                    transform: scale(0.95);
                    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
                }

                70% {
                    transform: scale(1);
                    box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
                }

                100% {
                    transform: scale(0.95);
                    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
                }
            }

            @media (max-width: 1200px) {
                .bday-list-section {
                    border-right: none;
                    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                }

                .bday-accent {
                    text-align: center;
                }

                .birthday-glass-card {
                    margin-left: 1rem;
                    margin-right: 1rem;
                }
            }
        </style>

        <div class="birthday-glass-card animate-fade-in mt-n4 position-relative zindex-1 mx-3 sticky-top-mobile"
            style="top: 0px;">
            <div class="row g-0">
                <!-- Spotlight -->
                <div class="col-xl-3 bday-accent">
                    <div class="mb-2"><span class="pulse-indicator"></span><span
                            class="small fw-bold text-uppercase">Today</span></div>
                    <h2 class="fw-bold mb-4">Happy Birthday!</h2>
                    <div class="today-list" style="max-height: 180px; overflow-y: auto; padding-right: 5px;">
                        @forelse($dashboard_data['birthdays']['today'] as $student)
                            <div
                                class="d-flex align-items-center gap-4 mb-3 justify-content-center justify-content-xl-start text-left">
                                <img src="{{ $student->photo_url }}" class="rounded-circle border border-white shadow-sm"
                                    style="width: 50px; height: 50px; object-fit: cover;" alt="">
                                <div class="ms-4">

                                    <div class="fw-bold small text-white">{{ $student->name }}</div>
                                    <div class="text-xs opacity-100 fw-bold">{{ $student->batch->course->code ?? 'N/A' }}</div>
                                    <div class="text-xs opacity-75">{{ $student->batch->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="bday-empty-state">
                                <i class="fas fa-gift fa-2x mb-2"></i>
                                <p class="small italic mb-0">No cakes today!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Other Days -->
                <div class="col-xl-9">
                    <div class="row g-0 h-100">
                        <div class="col-md-4 bday-list-section">
                            <div class="text-xs fw-bold text-muted text-uppercase mb-3"><i
                                    class="fas fa-calendar-day text-primary mr-2"></i> Tomorrow</div>
                            <div class="scroll-container" style="max-height: 180px; overflow-y: auto;">
                                @forelse($dashboard_data['birthdays']['tomorrow'] as $student)
                                    <div class="bday-student-card d-flex align-items-center gap-3">
                                        <img src="{{ $student->photo_url }}" class="rounded-circle border"
                                            style="width: 32px; height: 32px; object-fit: cover;" alt="">
                                        <div>
                                            <div class="text-xs fw-bold text-dark">{{ $student->name }}</div>
                                            <div class="text-xs text-muted fw-bold">{{ $student->batch->course->code ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-muted">{{ $student->batch->name ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="bday-empty-state">
                                        <p class="small italic mb-0">Quiet tomorrow</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-md-4 bday-list-section">
                            <div class="text-xs fw-bold text-muted text-uppercase mb-3"><i
                                    class="fas fa-forward text-info mr-2"></i> Next 3 Days</div>
                            <div class="scroll-container" style="max-height: 180px; overflow-y: auto;">
                                @forelse($dashboard_data['birthdays']['upcoming_3_days'] as $student)
                                    <div class="bday-student-card d-flex align-items-center gap-3">
                                        <img src="{{ $student->photo_url }}" class="rounded-circle border"
                                            style="width: 32px; height: 32px; object-fit: cover;" alt="">
                                        <div>
                                            <div class="text-xs fw-bold text-dark">{{ $student->name }}</div>
                                            <div class="text-xs text-info fw-bold">{{ $student->dob->format('d M') }}</div>
                                            <div class="text-xs text-muted">{{ $student->batch->course->code ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="bday-empty-state">
                                        <p class="small italic mb-0">Nothing upcoming</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-md-4 bday-list-section">
                            <div class="text-xs fw-bold text-muted text-uppercase mb-3"><i
                                    class="fas fa-history text-secondary mr-2"></i> Just Passed</div>
                            <div class="scroll-container" style="max-height: 180px; overflow-y: auto;">
                                @forelse($dashboard_data['birthdays']['last_3_days'] as $student)
                                    <div class="bday-student-card d-flex align-items-center gap-3 opacity-75">
                                        <img src="{{ $student->photo_url }}" class="rounded-circle border"
                                            style="width: 32px; height: 32px; object-fit: cover;" alt="">
                                        <div>
                                            <div class="text-xs fw-bold text-dark">{{ $student->name }}</div>
                                            <div class="text-xs text-muted fw-bold">{{ $student->dob->format('d M') }}</div>
                                            <div class="text-xs text-muted">{{ $student->batch->course->code ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="bday-empty-state">
                                        <p class="small italic mb-0">No recent ones</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon enrollment">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="stat-number">{{ $dashboard_data['my_students_count'] ?? 0 }}</div>
                <div class="stat-label">My Students</div>
                <div class="stat-trend trend-positive">
                    <i class="fas fa-arrow-up mr-1"></i>
                    {{ $dashboard_data['students_growth'] ?? 0 }}% this month
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon payments">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="stat-number" id="my-collections-today">
                    ₹{{ number_format($dashboard_data['my_collections']['today'] ?? 0) }}</div>
                <div class="stat-label">My Collections Today</div>
                <div class="stat-trend trend-positive">
                    <i class="fas fa-arrow-up mr-1"></i>
                    {{ $dashboard_data['collections_growth'] ?? 0 }}% vs yesterday
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon attendance">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-number">{{ $dashboard_data['avg_attendance'] ?? 0 }}%</div>
                <div class="stat-label">Avg Attendance</div>
                <div
                    class="stat-trend {{ ($dashboard_data['attendance_trend'] ?? 0) >= 0 ? 'trend-positive' : 'trend-negative' }}">
                    <i class="fas fa-arrow-{{ ($dashboard_data['attendance_trend'] ?? 0) >= 0 ? 'up' : 'down' }} mr-1"></i>
                    {{ abs($dashboard_data['attendance_trend'] ?? 0) }}% this week
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon activity">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-number">{{ $dashboard_data['my_activities_count'] ?? 0 }}</div>
                <div class="stat-label">My Activities</div>
                <div class="stat-trend trend-positive">
                    <i class="fas fa-clock mr-1"></i>
                    Last {{ $dashboard_data['last_activity_time'] ?? 'unknown' }}
                </div>
            </div>
        <!-- Enquiry Metrics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="widget-card">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-funnel-dollar"></i>Enquiry & Admission Funnel
                        </h6>
                    </div>
                    <div class="widget-body">
                        <div class="row">
                            <div class="col-md-3 border-right">
                                <div class="text-center py-2">
                                    <div class="h3 font-weight-bold text-primary">{{ $dashboard_data['enquiry_stats']['today_count'] ?? 0 }}</div>
                                    <div class="small text-muted text-uppercase">New Today</div>
                                </div>
                            </div>
                            <div class="col-md-3 border-right">
                                <div class="text-center py-2">
                                    <div class="h3 font-weight-bold text-info">{{ $dashboard_data['enquiry_stats']['new_count'] ?? 0 }}</div>
                                    <div class="small text-muted text-uppercase">Fresh Leads</div>
                                </div>
                            </div>
                            <div class="col-md-3 border-right">
                                <div class="text-center py-2">
                                    <div class="h3 font-weight-bold text-warning">{{ $dashboard_data['enquiry_stats']['followup_today'] ?? 0 }}</div>
                                    <div class="small text-muted text-uppercase">Due Follow-ups</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center py-2">
                                    <div class="h3 font-weight-bold text-success">{{ $dashboard_data['enquiry_stats']['admitted_count'] ?? 0 }}</div>
                                    <div class="small text-muted text-uppercase">Total Admitted</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Collections Section -->
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">
                    <i class="fas fa-chart-bar"></i>My Payment Collections
                </h6>
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                        Export
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#" onclick="exportPaymentData('pdf')">PDF Report</a>
                        <a class="dropdown-item" href="#" onclick="exportPaymentData('excel')">Excel Sheet</a>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                <!-- Time Period Selector -->
                <div class="period-selector">
                    <button class="period-btn active" data-period="today">Today</button>
                    <button class="period-btn" data-period="yesterday">Yesterday</button>
                    <button class="period-btn" data-period="this_week">This Week</button>
                    <button class="period-btn" data-period="last_7_days">Last 7 Days</button>
                    <button class="period-btn" data-period="this_month">This Month</button>
                    <button class="period-btn" data-period="last_30_days">Last 30 Days</button>
                </div>

                <!-- Payment Stats Grid -->
                <div class="payment-stats" id="payment-stats">
                    <div class="payment-stat">
                        <div class="payment-stat-amount text-success" id="total-collected">
                            ₹{{ number_format($dashboard_data['my_collections']['today'] ?? 0) }}
                        </div>
                        <div class="payment-stat-label">Total Collected</div>
                    </div>
                    <div class="payment-stat">
                        <div class="payment-stat-amount text-primary" id="transactions-count">
                            {{ $dashboard_data['my_collections']['transactions'] ?? 0 }}
                        </div>
                        <div class="payment-stat-label">Transactions</div>
                    </div>
                    <div class="payment-stat">
                        <div class="payment-stat-amount text-info" id="avg-payment">
                            ₹{{ number_format($dashboard_data['my_collections']['avg_amount'] ?? 0) }}
                        </div>
                        <div class="payment-stat-label">Average Payment</div>
                    </div>
                    <div class="payment-stat">
                        <div class="payment-stat-amount text-warning" id="online-percentage">
                            {{ $dashboard_data['my_collections']['online_percentage'] ?? 0 }}%
                        </div>
                        <div class="payment-stat-label">Online Payments</div>
                    </div>
                </div>

                <!-- Payment Charts -->
                <div class="chart-container">
                    <canvas id="paymentTrendsChart"></canvas>
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-chart-area"></i>Student Attendance Analytics
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                                data-toggle="dropdown">
                                View
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('daily')">Daily
                                    View</a>
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('weekly')">Weekly View</a>
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('monthly')">Monthly View</a>
                            </div>
                        </div>
                    </div>
                    <div class="widget-body">
                        <!-- Attendance Overview Stats -->
                        <div class="attendance-overview">
                            <div class="attendance-stat">
                                <div class="attendance-percentage excellent">
                                    {{ $dashboard_data['attendance_stats']['excellent'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Excellent (90%+)</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-percentage good">
                                    {{ $dashboard_data['attendance_stats']['good'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Good (75-89%)</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-percentage average">
                                    {{ $dashboard_data['attendance_stats']['average'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Average (60-74%)</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-percentage poor">
                                    {{ $dashboard_data['attendance_stats']['poor'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Needs Attention</div>
                            </div>
                        </div>

                        <!-- Attendance Chart -->
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Payment Mode Distribution -->
                <div class="widget-card">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-credit-card"></i>Payment Mode Distribution
                        </h6>
                    </div>
                    <div class="widget-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="paymentModeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Activity and Quick Info -->
            <div class="sidebar-content">
                <!-- My Activity Log -->
                <div class="widget-card">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-history"></i>My Activity Log
                        </h6>
                        <a href="{{ route('admin.activity-log.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <div class="widget-body">
                        <div class="activity-log" id="activity-list">
                            @if(isset($dashboard_data['my_activities']) && count($dashboard_data['my_activities']) > 0)
                                @foreach($dashboard_data['my_activities'] as $activity)
                                    <div class="activity-item">
                                        <div class="activity-icon {{ $activity['type'] ?? 'update' }}">
                                            <i class="fas fa-{{ $activity['icon'] ?? 'edit' }}"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                {{ $activity['description'] ?? 'No description provided.' }}
                                            </div>
                                            <div class="activity-meta">
                                                @if(isset($activity['student_name']))
                                                    Student: {{ $activity['student_name'] }}
                                                @endif
                                                @if(isset($activity['amount']))
                                                    • Amount: ₹{{ number_format($activity['amount']) }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="activity-time">
                                            @if(isset($activity['created_at']))
                                                {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="loading-spinner">
                                    <div class="spinner"></div>
                                    <p class="mt-2 text-muted">Loading your activities...</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="widget-card">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-bolt"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="widget-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.component-payments.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>Collect Payment
                            </a>
                            <a href="{{ route('admin.students.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-users mr-2"></i>Manage Students
                            </a>
                            <a href="{{ route('admin.daily-attendance.create') }}" class="btn btn-outline-success">
                                <i class="fas fa-user-check mr-2"></i>Mark Attendance
                            </a>
                            <a href="{{ route('admin.reports.attendance.index') }}" class="btn btn-outline-info">
                                <i class="fas fa-chart-bar mr-2"></i>Attendance Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Today's Pending Collections -->
                <div class="widget-card">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-exclamation-triangle"></i>Pending Collections
                        </h6>
                    </div>
                    <div class="widget-body">
                        @if(isset($dashboard_data['pending_collections']) && count($dashboard_data['pending_collections']) > 0)
                            @foreach(array_slice($dashboard_data['pending_collections'], 0, 5) as $pending)
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                    <div>
                                        <div class="font-weight-bold">{{ $pending['student_name'] }}</div>
                                        <small class="text-muted">{{ $pending['course'] ?? 'N/A' }}</small>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-danger font-weight-bold">
                                            ₹{{ number_format($pending['amount']) }}</div>
                                        <small class="text-muted">{{ $pending['due_date'] ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            @endforeach
                            @if(count($dashboard_data['pending_collections']) > 5)
                                <div class="text-center mt-2">
                                    <a href="{{ route('admin.payment-defaulters.index') }}" class="btn btn-outline-primary btn-sm">
                                        View All ({{ count($dashboard_data['pending_collections']) - 5 }} more)
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <div class="text-muted">No pending collections!</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@php
    // Real payment data instead of test data
    $user = auth()->user();

    // Get payment modes from last 30 days (more likely to have data)
    $paymentModes = \App\Models\Payment::where('payment_type', 'component')
        ->where('created_by', $user->id)
        ->whereBetween('payment_date', [now()->subDays(30), now()])
        ->selectRaw('payment_method, SUM(amount) as total')
        ->groupBy('payment_method')
        ->pluck('total', 'payment_method')
        ->toArray();

    // Set defaults for all payment methods
    $defaultModes = ['cash' => 0, 'online' => 0, 'card' => 0, 'upi' => 0];
    $paymentModes = array_merge($defaultModes, $paymentModes);

    // If no real data found, keep test data for demo
    if (array_sum($paymentModes) == 0) {
        $paymentModes = ['cash' => 25000, 'online' => 35000, 'card' => 15000, 'upi' => 20000];
    }

    $dashboard_data['payment_modes'] = [
        'labels' => ['Cash', 'Online', 'Card', 'UPI'],
        'values' => [
            (float) $paymentModes['cash'],
            (float) $paymentModes['online'],
            (float) $paymentModes['card'],
            (float) $paymentModes['upi'],
        ]
    ];

    // Debug what we found
    \Log::info('Payment modes data for user ' . $user->id . ':', $paymentModes);
@endphp
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

    <script>
        // Store chart data in JavaScript variables to avoid PHP-JS conflicts
        const dashboardData = {
            paymentTrends: @json($dashboard_data['payment_trends'] ?? null),
            attendanceChart: @json($dashboard_data['attendance_chart'] ?? null),
            paymentModes: @json($dashboard_data['payment_modes'] ?? null)
        };
        console.log('=== Initial Data Check ===');
        console.log('Payment modes from PHP:', @json($dashboard_data['payment_modes'] ?? 'NOT_SET'));
        console.log('Dashboard data payment modes:', dashboardData.paymentModes);



        // Provide fallback data if null
        if (!dashboardData.paymentTrends) {
            dashboardData.paymentTrends = {
                labels: ['No Data'],
                amounts: [0],
                counts: [0]
            };
        }

        if (!dashboardData.attendanceChart) {
            dashboardData.attendanceChart = {
                labels: ['No Data'],
                present: [0],
                absent: [0],
                late: [0]
            };
        }

        if (!dashboardData.paymentModes) {
            dashboardData.paymentModes = {
                labels: ['Cash', 'Online', 'Card', 'UPI'],
                values: [0, 0, 0, 0]
            };
        }

        // Global chart variables
        let paymentTrendsChart = null;
        let attendanceChart = null;
        let paymentModeChart = null;

        // Initialize all charts when DOM is ready
        document.addEventListener('DOMContentLoaded', function () {
            initializePaymentTrendsChart();
            initializeAttendanceChart();
            initializePaymentModeChart();
            setupPeriodButtons();
            startLiveTimeUpdate();
            loadUserActivities();


            // Sync server time every 5 minutes
            setInterval(syncServerTime, 300000);
        });




        // Update your DOMContentLoaded event
        document.addEventListener('DOMContentLoaded', function () {
            console.log('=== Dashboard Initialization ===');

            initializePaymentTrendsChart();
            initializeAttendanceChart();
            // Remove the direct call and use API instead
            initializePaymentModeChart();

            setupPeriodButtons();
            startLiveTimeUpdate();
            loadUserActivities();

            // Sync server time every 5 minutes
            setInterval(syncServerTime, 300000);
        });


        // Payment Trends Chart
        function initializePaymentTrendsChart() {
            const ctx = document.getElementById('paymentTrendsChart');
            if (!ctx) return;

            // Destroy existing chart if it exists
            if (paymentTrendsChart) {
                paymentTrendsChart.destroy();
            }

            const chartData = dashboardData.paymentTrends;

            paymentTrendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels || ['No Data'],
                    datasets: [
                        {
                            label: 'Collections (₹)',
                            data: chartData.amounts || [0],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Transactions',
                            data: chartData.counts || [0],
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            fill: false,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function (context) {
                                    if (context.datasetIndex === 0) {
                                        return 'Collections: ₹' + new Intl.NumberFormat('en-IN').format(context.parsed.y);
                                    } else {
                                        return 'Transactions: ' + context.parsed.y;
                                    }
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Time Period'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Amount (₹)'
                            },
                            ticks: {
                                callback: function (value) {
                                    return '₹' + new Intl.NumberFormat('en-IN').format(value);
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Transaction Count'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });
        }

        // Attendance Chart
        function initializeAttendanceChart() {
            const ctx = document.getElementById('attendanceChart');
            if (!ctx) return;

            // Destroy existing chart if it exists
            if (attendanceChart) {
                attendanceChart.destroy();
            }

            const attendanceData = dashboardData.attendanceChart;

            attendanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: attendanceData.labels || ['No Data'],
                    datasets: [
                        {
                            label: 'Present',
                            data: attendanceData.present || [0],
                            backgroundColor: '#10b981',
                            borderColor: '#059669',
                            borderWidth: 1
                        },
                        {
                            label: 'Absent',
                            data: attendanceData.absent || [0],
                            backgroundColor: '#ef4444',
                            borderColor: '#dc2626',
                            borderWidth: 1
                        },
                        {
                            label: 'Late',
                            data: attendanceData.late || [0],
                            backgroundColor: '#f59e0b',
                            borderColor: '#d97706',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                footer: function (tooltipItems) {
                                    let total = 0;
                                    tooltipItems.forEach(function (tooltipItem) {
                                        total += tooltipItem.parsed.y;
                                    });
                                    return 'Total: ' + total + ' students';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            title: {
                                display: true,
                                text: 'Days'
                            }
                        },
                        y: {
                            stacked: true,
                            title: {
                                display: true,
                                text: 'Number of Students'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Payment Mode Chart
        function initializePaymentModeChart() {
            console.log('Initializing payment mode chart...');

            const ctx = document.getElementById('paymentModeChart');
            if (!ctx) {
                console.error('Payment mode chart canvas not found!');
                return;
            }

            // Destroy existing chart if it exists
            if (paymentModeChart) {
                paymentModeChart.destroy();
            }

            // Use test data if no real data available
            let paymentModeData = dashboardData.paymentModes;

            // If no data from backend, use test data
            if (!paymentModeData || !paymentModeData.values) {
                console.log('No payment mode data from backend, using test data');
                paymentModeData = {
                    labels: ['Cash', 'Online', 'Card', 'UPI'],
                    values: [25000, 35000, 15000, 20000] // Test data
                };
            }

            const values = Array.isArray(paymentModeData.values) ? paymentModeData.values : [25000, 35000, 15000, 20000];
            const hasData = values.some(value => value > 0);

            console.log('Chart data:', { labels: paymentModeData.labels, values, hasData });

            try {
                paymentModeChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: paymentModeData.labels || ['Cash', 'Online', 'Card', 'UPI'],
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                '#10b981',
                                '#4f46e5',
                                '#f59e0b',
                                '#06b6d4'
                            ],
                            borderColor: [
                                '#059669',
                                '#3730a3',
                                '#d97706',
                                '#0891b2'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        if (total === 0) return context.label + ': ₹0 (0%)';
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        return context.label + ': ' + percentage + '% (₹' + new Intl.NumberFormat('en-IN').format(context.parsed) + ')';
                                    }
                                }
                            }
                        }
                    }
                });

                console.log('Payment mode chart created successfully');

            } catch (error) {
                console.error('Error creating payment mode chart:', error);
            }
        }

        // Period Button Setup
        function setupPeriodButtons() {
            const periodButtons = document.querySelectorAll('.period-btn');

            if (periodButtons.length === 0) return;

            // Set today as active by default
            periodButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.period === 'today') {
                    btn.classList.add('active');
                }
            });

            // Load today's data immediately
            fetchPaymentData('today');

            periodButtons.forEach(button => {
                button.addEventListener('click', function () {
                    // Remove active class from all buttons
                    periodButtons.forEach(btn => btn.classList.remove('active'));

                    // Add active class to clicked button
                    this.classList.add('active');

                    // Fetch data for selected period
                    const period = this.dataset.period;
                    fetchPaymentData(period);
                });
            });
        }

        // Fetch Payment Data
        function fetchPaymentData(period) {
            const paymentStats = document.getElementById('payment-stats');

            if (!paymentStats) return;

            // Show loading state
            paymentStats.style.opacity = '0.6';

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                paymentStats.style.opacity = '1';
                return;
            }

            fetch(`/api/dashboard/my-payment-data?period=${period}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updatePaymentStats(data);
                        updatePaymentChart(data);

                        // Log successful data fetch
                        console.log(`Payment data for ${period} loaded successfully:`, data);
                    } else {
                        throw new Error(data.message || 'Failed to fetch payment data');
                    }
                    paymentStats.style.opacity = '1';
                })
                .catch(error => {
                    console.error('Error fetching payment data:', error);
                    paymentStats.style.opacity = '1';
                    showErrorMessage('Failed to load payment data: ' + error.message);
                });
        }

        // Update Payment Stats
        function updatePaymentStats(data) {
            const totalCollected = document.getElementById('total-collected');
            const transactionsCount = document.getElementById('transactions-count');
            const avgPayment = document.getElementById('avg-payment');
            const onlinePercentage = document.getElementById('online-percentage');
            const myCollectionsToday = document.getElementById('my-collections-today');

            if (totalCollected) totalCollected.textContent = `₹${numberFormat(data.total_collected || 0)}`;
            if (transactionsCount) transactionsCount.textContent = data.transactions_count || 0;
            if (avgPayment) avgPayment.textContent = `₹${numberFormat(data.avg_amount || 0)}`;
            if (onlinePercentage) onlinePercentage.textContent = `${data.online_percentage || 0}%`;

            // Update main stat if period is 'today'
            const activeButton = document.querySelector('.period-btn.active');
            if (activeButton && activeButton.dataset.period === 'today' && myCollectionsToday) {
                myCollectionsToday.textContent = `₹${numberFormat(data.total_collected || 0)}`;
            }
        }

        // Enhanced update functions for comparison charts

        function updatePaymentChart(data) {
            if (!paymentTrendsChart) return;

            // Check if we have comparison data
            if (data.chart_data && data.comparison) {
                // Update with comparison data
                paymentTrendsChart.data.labels = data.chart_data.labels;

                // Update amounts dataset with comparison colors
                paymentTrendsChart.data.datasets[0].data = data.chart_data.amounts;
                paymentTrendsChart.data.datasets[0].backgroundColor = [
                    'rgba(239, 68, 68, 0.1)',    // Comparison period (red)
                    'rgba(16, 185, 129, 0.1)'     // Current period (green)
                ];
                paymentTrendsChart.data.datasets[0].borderColor = [
                    '#ef4444',  // Comparison period
                    '#10b981'   // Current period
                ];

                // Update transactions dataset
                paymentTrendsChart.data.datasets[1].data = data.chart_data.counts;
                paymentTrendsChart.data.datasets[1].backgroundColor = [
                    'rgba(239, 68, 68, 0.1)',
                    'rgba(79, 70, 229, 0.1)'
                ];
                paymentTrendsChart.data.datasets[1].borderColor = [
                    '#ef4444',
                    '#4f46e5'
                ];

                // Change chart type to bar for better comparison
                if (paymentTrendsChart.config.type !== 'bar') {
                    paymentTrendsChart.config.type = 'bar';
                    paymentTrendsChart.data.datasets[0].fill = false;
                    paymentTrendsChart.data.datasets[1].fill = false;
                }

            } else {
                // Fallback to single period data (line chart)
                paymentTrendsChart.config.type = 'line';
                paymentTrendsChart.data.labels = [getPeriodLabel(data.period)];
                paymentTrendsChart.data.datasets[0].data = [data.total_collected || 0];
                paymentTrendsChart.data.datasets[1].data = [data.transactions_count || 0];

                // Reset colors to original
                paymentTrendsChart.data.datasets[0].backgroundColor = 'rgba(16, 185, 129, 0.1)';
                paymentTrendsChart.data.datasets[0].borderColor = '#10b981';
                paymentTrendsChart.data.datasets[1].backgroundColor = 'rgba(79, 70, 229, 0.1)';
                paymentTrendsChart.data.datasets[1].borderColor = '#4f46e5';
                paymentTrendsChart.data.datasets[0].fill = true;
            }

            paymentTrendsChart.update();

            // Update growth indicators
            if (data.growth) {
                updateGrowthIndicators(data);
                addComparisonSummary(data);
            }

            console.log('Payment chart updated with comparison data:', data);
        }

        function updateGrowthIndicators(data) {
            // Update the main stat card growth indicator
            const statCard = document.querySelector('.stat-card .stat-trend');
            if (statCard && data.growth) {
                const isPositive = data.growth.is_positive;
                const percentage = Math.abs(data.growth.amount_growth);

                statCard.className = `stat-trend ${isPositive ? 'trend-positive' : 'trend-negative'}`;
                statCard.innerHTML = `
                                                                <i class="fas fa-arrow-${isPositive ? 'up' : 'down'} mr-1"></i>
                                                                ${percentage}% ${data.growth.comparison_label}
                                                            `;
            }

            // Update individual stat elements with growth indicators
            updateStatWithGrowth('total-collected', data.total_collected, data.growth.amount_growth);
            updateStatWithGrowth('transactions-count', data.transactions_count, data.growth.transaction_growth);
            updateStatWithGrowth('avg-payment', data.avg_amount, data.growth.avg_amount_growth, '₹');
            updateStatWithGrowth('online-percentage', data.online_percentage, data.growth.online_percentage_change, '', '%');
        }

        function updateStatWithGrowth(elementId, value, growth, prefix = '', suffix = '') {
            const element = document.getElementById(elementId);
            if (!element) return;

            // Update the main value
            element.textContent = `${prefix}${numberFormat(value)}${suffix}`;

            // Add or update growth indicator
            let growthElement = element.parentElement.querySelector('.growth-indicator');
            if (!growthElement) {
                growthElement = document.createElement('div');
                growthElement.className = 'growth-indicator';
                growthElement.style.cssText = 'font-size: 0.7rem; margin-top: 2px;';
                element.parentElement.appendChild(growthElement);
            }

            const isPositive = growth >= 0;
            const absGrowth = Math.abs(growth);

            if (absGrowth > 0) {
                growthElement.innerHTML = `
                                                                <span style="color: ${isPositive ? '#10b981' : '#ef4444'}">
                                                                    <i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i>
                                                                    ${absGrowth.toFixed(1)}%
                                                                </span>
                                                            `;
                growthElement.style.display = 'block';
            } else {
                growthElement.style.display = 'none';
            }
        }

        function addComparisonSummary(data) {
            // Add or update comparison summary below the chart
            let summaryElement = document.getElementById('comparison-summary');
            if (!summaryElement) {
                summaryElement = document.createElement('div');
                summaryElement.id = 'comparison-summary';
                summaryElement.className = 'mt-3 p-3 bg-light rounded';

                // Insert after the chart container
                const chartContainer = document.querySelector('.chart-container');
                if (chartContainer && chartContainer.parentNode) {
                    chartContainer.parentNode.insertBefore(summaryElement, chartContainer.nextSibling);
                }
            }

            const growth = data.growth;
            const comparison = data.comparison;

            if (growth && comparison) {
                summaryElement.innerHTML = `
                                                                <h6 class="mb-2"><i class="fas fa-chart-line mr-2"></i>Period Comparison</h6>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="comparison-item">
                                                                            <small class="text-muted">Current Period (${data.period})</small>
                                                                            <div class="font-weight-bold">₹${numberFormat(data.total_collected)} (${data.transactions_count} transactions)</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="comparison-item">
                                                                            <small class="text-muted">Previous Period ${growth.comparison_label}</small>
                                                                            <div class="font-weight-bold">₹${numberFormat(comparison.total_collected)} (${comparison.transactions_count} transactions)</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="mt-2">
                                                                    <span class="badge badge-${growth.is_positive ? 'success' : 'danger'}">
                                                                        <i class="fas fa-arrow-${growth.is_positive ? 'up' : 'down'} mr-1"></i>
                                                                        ${growth.summary}
                                                                    </span>
                                                                </div>
                                                            `;
            }
        }

        function getPeriodLabel(period) {
            const labels = {
                'today': 'Today',
                'yesterday': 'Yesterday',
                'this_week': 'This Week',
                'this_month': 'This Month',
                'last_7_days': 'Last 7 Days',
                'last_30_days': 'Last 30 Days'
            };
            return labels[period] || 'Current Period';
        }

        // Enhanced fetchPaymentData to request comparison data
        function fetchPaymentData(period) {
            const paymentStats = document.getElementById('payment-stats');

            if (!paymentStats) return;

            // Show loading state
            paymentStats.style.opacity = '0.6';

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                paymentStats.style.opacity = '1';
                return;
            }

            // Request data with comparison enabled
            fetch(`/api/dashboard/my-payment-data?period=${period}&compare=true`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updatePaymentStats(data);
                        updatePaymentChart(data);

                        console.log(`Payment data with comparison for ${period} loaded:`, data);
                    } else {
                        throw new Error(data.message || 'Failed to fetch payment data');
                    }
                    paymentStats.style.opacity = '1';
                })
                .catch(error => {
                    console.error('Error fetching payment data:', error);
                    paymentStats.style.opacity = '1';
                    showErrorMessage('Failed to load payment data: ' + error.message);
                });
        }

        // Add CSS for growth indicators (add this to your stylesheet)
        const growthStyles = `
                                                    .growth-indicator {
                                                        font-size: 0.7rem;
                                                        margin-top: 2px;
                                                    }

                                                    .comparison-item {
                                                        padding: 0.5rem 0;
                                                        border-bottom: 1px solid #e9ecef;
                                                    }

                                                    .comparison-item:last-child {
                                                        border-bottom: none;
                                                    }

                                                    #comparison-summary {
                                                        background: #f8f9fa;
                                                        border: 1px solid #e9ecef;
                                                        border-radius: 8px;
                                                    }

                                                    .badge {
                                                        font-size: 0.8rem;
                                                    }
                                                    `;

        // Inject styles
        const styleSheet = document.createElement('style');
        styleSheet.textContent = growthStyles;
        document.head.appendChild(styleSheet);

        // Live Time Update with Server Sync
        let serverTimeOffset = 0;
        let timeUpdateInterval;

        function startLiveTimeUpdate() {
            const liveTimeElement = document.getElementById('live-time');
            if (!liveTimeElement) return;

            // Try to get server time, fallback to client time
            getServerTime().then(serverTime => {
                const clientTime = new Date();
                serverTimeOffset = serverTime.getTime() - clientTime.getTime();

                console.log('Server time synchronized. Offset:', serverTimeOffset + 'ms');
                startTimeUpdate();
            }).catch(error => {
                console.warn('Using client time:', error);
                serverTimeOffset = 0;
                startTimeUpdate();
            });

            function startTimeUpdate() {
                function updateTime() {
                    const now = new Date(Date.now() + serverTimeOffset);
                    const timeString = now.toLocaleTimeString('en-GB', {
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    liveTimeElement.textContent = timeString;
                }

                // Update immediately
                updateTime();

                // Clear any existing interval
                if (timeUpdateInterval) {
                    clearInterval(timeUpdateInterval);
                }

                // Update every second
                timeUpdateInterval = setInterval(updateTime, 1000);
            }
        }

        // Get server time
        async function getServerTime() {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const response = await fetch('/api/server-time', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : ''
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    return new Date(data.timestamp);
                } else {
                    throw new Error('Server time API not available');
                }
            } catch (error) {
                throw error;
            }
        }

        // Sync server time periodically (every 5 minutes)
        function syncServerTime() {
            getServerTime().then(serverTime => {
                const clientTime = new Date();
                serverTimeOffset = serverTime.getTime() - clientTime.getTime();
                console.log('Server time re-synchronized. Offset:', serverTimeOffset + 'ms');
            }).catch(error => {
                console.warn('Failed to sync server time:', error);
            });
        }

        // Load User Activities
        function loadUserActivities() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) return;

            fetch('/api/dashboard/my-activities', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(data => {
                    updateActivityList(data.activities);
                })
                .catch(error => {
                    console.error('Error loading activities:', error);
                });
        }

        // Update Activity List
        function updateActivityList(activities) {
            const activityList = document.getElementById('activity-list');
            if (!activityList) return;

            if (!activities || activities.length === 0) {
                activityList.innerHTML = `
                                                                <div class="text-center py-3">
                                                                    <i class="fas fa-inbox fa-2x text-gray-300 mb-2"></i>
                                                                    <div class="text-muted">No recent activities</div>
                                                                </div>
                                                            `;
                return;
            }

            activityList.innerHTML = activities.map(activity => `
                                                            <div class="activity-item">
                                                                <div class="activity-icon ${activity.type || 'update'}">
                                                                    <i class="fas fa-${activity.icon || 'edit'}"></i>
                                                                </div>
                                                                <div class="activity-content">
                                                                    <div class="activity-text">${activity.description || 'No description provided.'}</div>
                                                                    <div class="activity-meta">
                                                                        ${activity.student_name ? `Student: ${activity.student_name}` : ''}
                                                                        ${activity.amount ? `• Amount: ₹${numberFormat(activity.amount)}` : ''}
                                                                    </div>
                                                                </div>
                                                                <div class="activity-time">
                                                                    ${activity.created_at ? timeAgo(activity.created_at) : ''}
                                                                </div>
                                                            </div>
                                                        `).join('');
        }

        // Change Attendance View
        function changeAttendanceView(view) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) return;

            fetch(`/api/dashboard/attendance-data?view=${view}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(data => {
                    // Update the attendance chart with new data
                    updateAttendanceChart(data);
                })
                .catch(error => {
                    console.error('Error changing attendance view:', error);
                });
        }

        // Update Attendance Chart
        function updateAttendanceChart(data) {
            if (!attendanceChart || !data) return;

            // Update chart data
            attendanceChart.data.labels = data.labels || ['No Data'];
            attendanceChart.data.datasets[0].data = data.present || [0];
            attendanceChart.data.datasets[1].data = data.absent || [0];
            attendanceChart.data.datasets[2].data = data.late || [0];

            attendanceChart.update();
        }

        // Export Payment Data
        function exportPaymentData(format) {
            const activeButton = document.querySelector('.period-btn.active');
            const period = activeButton ? activeButton.dataset.period : 'today';
            const url = `/admin/reports/my-payments/export?format=${format}&period=${period}`;

            // Create a temporary link and trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = `my_payments_${period}.${format}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Refresh Dashboard Data
        function refreshDashboardData() {
            // Refresh all dashboard components
            const activeButton = document.querySelector('.period-btn.active');
            const activePeriod = activeButton ? activeButton.dataset.period : 'today';
            fetchPaymentData(activePeriod);
            loadUserActivities();

            // Show a subtle notification
            showSuccessMessage('Dashboard data refreshed', 2000);
        }

        // Utility Functions
        function numberFormat(value) {
            if (isNaN(value)) return 0;
            return new Intl.NumberFormat('en-IN').format(value);
        }

        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
            return `${Math.floor(diffInSeconds / 86400)}d ago`;
        }

        function showSuccessMessage(message, duration = 3000) {
            // Create and show a toast notification
            const toast = document.createElement('div');
            toast.className = 'toast-notification success';
            toast.innerHTML = `
                                                            <i class="fas fa-check-circle mr-2"></i>
                                                            ${message}
                                                        `;
            toast.style.cssText = `
                                                            position: fixed;
                                                            top: 20px;
                                                            right: 20px;
                                                            background: #10b981;
                                                            color: white;
                                                            padding: 12px 20px;
                                                            border-radius: 8px;
                                                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                                                            z-index: 9999;
                                                            opacity: 0;
                                                            transform: translateX(100%);
                                                            transition: all 0.3s ease;
                                                        `;

            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            }, 100);

            // Animate out and remove
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, duration);
        }

        function showErrorMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification error';
            toast.innerHTML = `
                                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                                            ${message}
                                                        `;
            toast.style.cssText = `
                                                            position: fixed;
                                                            top: 20px;
                                                            right: 20px;
                                                            background: #ef4444;
                                                            color: white;
                                                            padding: 12px 20px;
                                                            border-radius: 8px;
                                                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                                                            z-index: 9999;
                                                            opacity: 0;
                                                            transform: translateX(100%);
                                                            transition: all 0.3s ease;
                                                        `;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // Ctrl + R: Refresh dashboard
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshDashboardData();
            }

            // Ctrl + E: Export current period data
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportPaymentData('excel');
            }
        });

        // Cleanup function to destroy charts when page unloads
        window.addEventListener('beforeunload', function () {
            if (paymentTrendsChart) paymentTrendsChart.destroy();
            if (attendanceChart) attendanceChart.destroy();
            if (paymentModeChart) paymentModeChart.destroy();
            if (timeUpdateInterval) clearInterval(timeUpdateInterval);
        });
    </script>
@endpush