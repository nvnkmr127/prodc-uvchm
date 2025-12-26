@extends('layouts.theme')

@section('title', 'Super Admin Dashboard')

@push('styles')
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --success-gradient: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            --info-gradient: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
            --warning-gradient: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            --danger-gradient: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
            --secondary-gradient: linear-gradient(135deg, #858796 0%, #60616f 100%);
            --glass-white: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15);
            --border-radius-lg: 1rem;
            --border-radius-md: 0.75rem;
            --transition-base: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        /* General Layout */
        .container-fluid {
            padding: 1.5rem 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .welcome-card {
            background: var(--primary-gradient);
            border-radius: var(--border-radius-lg);
            color: white;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(78, 115, 223, 0.3);
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 0;
        }

        .welcome-card::after {
            content: '';
            position: absolute;
            bottom: -30%;
            right: 10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            z-index: 0;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        /* Modern Cards */
        .card-modern {
            background: var(--glass-white);
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--card-shadow);
            transition: var(--transition-base);
            height: 100%;
            overflow: hidden;
        }

        .card-modern:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header-modern {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Stat Cards */
        .stat-card {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            border-left: 4px solid transparent;
            /* Replaces border-left-* classes */
        }

        .stat-card.primary {
            border-left-color: #4e73df;
        }

        .stat-card.success {
            border-left-color: #1cc88a;
        }

        .stat-card.info {
            border-left-color: #36b9cc;
        }

        .stat-card.warning {
            border-left-color: #f6c23e;
        }

        .stat-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            transition: var(--transition-base);
        }

        .card-modern:hover .stat-icon-wrapper {
            transform: scale(1.1) rotate(5deg);
        }

        .bg-soft-primary {
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
        }

        .bg-soft-success {
            background: rgba(28, 200, 138, 0.1);
            color: #1cc88a;
        }

        .bg-soft-info {
            background: rgba(54, 185, 204, 0.1);
            color: #36b9cc;
        }

        .bg-soft-warning {
            background: rgba(246, 194, 62, 0.1);
            color: #f6c23e;
        }

        .bg-soft-danger {
            background: rgba(231, 74, 59, 0.1);
            color: #e74a3b;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #2d3436;
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #8898aa;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Tables */
        .table-modern thead th {
            background-color: #f8f9fc;
            color: #8898aa;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            border-bottom: 2px solid #eaecf4;
            padding: 1rem 1.5rem;
            font-weight: 700;
        }

        .table-modern tbody td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            color: #525f7f;
            border-bottom: 1px solid #f6f9fc;
        }

        .table-modern tbody tr:last-child td {
            border-bottom: none;
        }

        .table-modern tbody tr:hover {
            background-color: #f8f9fc;
        }

        /* Lists */
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.875rem;
        }

        /* Quick Action Buttons */
        .btn-quick-action {
            background: white;
            border: 2px solid #eaecf4;
            color: #6e707e;
            padding: 1.25rem 1rem;
            border-radius: var(--border-radius-md);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            height: 100%;
            transition: var(--transition-base);
            text-decoration: none;
        }

        .btn-quick-action i {
            font-size: 1.75rem;
            transition: transform 0.3s ease;
        }

        .btn-quick-action span {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-quick-action:hover {
            border-color: #4e73df;
            color: #4e73df;
            background: rgba(78, 115, 223, 0.05);
            transform: translateY(-3px);
        }

        .btn-quick-action:hover i {
            transform: scale(1.2);
        }

        /* Helpers */
        .avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-md {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Animation classes */
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-delay-100 {
            animation-delay: 100ms;
        }

        .animate-delay-200 {
            animation-delay: 200ms;
        }

        .animate-delay-300 {
            animation-delay: 300ms;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">

        <!-- Welcome Header -->
        <div class="row mb-4 animate-fade-in">
            <div class="col-12">
                <div class="welcome-card">
                    <div class="welcome-content d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h2 class="mb-1 text-white fw-bold">Welcome back, Super Admin!</h2>
                            <p class="mb-0 text-white-50">Here's your comprehensive system overview for today.</p>
                        </div>
                        <div class="text-end">
                            <div class="h5 mb-0 text-white" id="clock-time">{{ now()->format('H:i') }}</div>
                            <div class="small text-white-50">{{ now()->format('l, F d, Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Stats Row -->
        <div class="row mb-4">
            <!-- Students -->
            <div class="col-xl-3 col-md-6 mb-4 animate-fade-in animate-delay-100">
                <div class="card-modern">
                    <div class="stat-card primary">
                        <div>
                            <div class="stat-label">Total Students</div>
                            <div class="stat-value" data-metric="total_students">
                                {{ number_format($dashboard_data['total_students'] ?? 0) }}
                            </div>
                            <div class="stat-trend text-success">
                                <i class="fas fa-arrow-up"></i>
                                <span>{{ $dashboard_data['student_growth'] ?? 0 }}% this month</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <div class="stat-icon-wrapper bg-soft-primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue -->
            <div class="col-xl-3 col-md-6 mb-4 animate-fade-in animate-delay-200">
                <div class="card-modern">
                    <div class="stat-card success">
                        <div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value text-success" data-metric="total_revenue">
                                ₹{{ number_format(($dashboard_data['total_revenue'] ?? 0) / 100000, 1) }}L</div>
                            <div
                                class="stat-trend {{ ($dashboard_data['revenue_growth'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <i
                                    class="fas fa-arrow-{{ ($dashboard_data['revenue_growth'] ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                                <span>{{ abs($dashboard_data['revenue_growth'] ?? 0) }}% vs last month</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <div class="stat-icon-wrapper bg-soft-success">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Courses -->
            <div class="col-xl-3 col-md-6 mb-4 animate-fade-in animate-delay-300">
                <div class="card-modern">
                    <div class="stat-card info">
                        <div>
                            <div class="stat-label">Active Courses</div>
                            <div class="stat-value">{{ $dashboard_data['active_courses'] ?? 0 }}</div>
                            <div class="stat-trend text-info">
                                <i class="fas fa-layer-group"></i>
                                <span>{{ $dashboard_data['total_batches'] ?? 0 }} Active Batches</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <div class="stat-icon-wrapper bg-soft-info">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outstanding Fees -->
            <div class="col-xl-3 col-md-6 mb-4 animate-fade-in animate-delay-300">
                <div class="card-modern">
                    <div class="stat-card warning">
                        <div>
                            <div class="stat-label">Outstanding Fees</div>
                            <div class="stat-value text-danger">
                                ₹{{ number_format(($dashboard_data['outstanding_fees'] ?? 0) / 100000, 1) }}L</div>
                            <div class="stat-trend text-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>{{ $dashboard_data['defaulters_count'] ?? 0 }} defaulters</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <div class="stat-icon-wrapper bg-soft-warning">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Module Overview Row -->
        <h6 class="mb-3 text-muted fw-bold text-uppercase small ls-1">Module Overview</h6>
        <div class="row mb-4">
            <!-- Inventory -->
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="avatar-md bg-light rounded-circle text-primary me-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div>
                            <div class="small text-muted fw-bold">INVENTORY</div>
                            <div class="h6 mb-0">{{ $dashboard_data['inventory_stats']['total_assets'] ?? 0 }} Assets</div>
                            <small
                                class="text-success">₹{{ number_format(($dashboard_data['inventory_stats']['total_value'] ?? 0) / 1000) }}k
                                Value</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- HR -->
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="avatar-md bg-light rounded-circle text-info me-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div>
                            <div class="small text-muted fw-bold">HR & PAYROLL</div>
                            <div class="h6 mb-0">{{ $dashboard_data['hr_stats']['today_leaves'] ?? 0 }} On Leave</div>
                            <small class="text-warning">{{ $dashboard_data['hr_stats']['pending_leaves'] ?? 0 }} Pending
                                Req</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Enquiries -->
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="avatar-md bg-light rounded-circle text-warning me-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div>
                            <div class="small text-muted fw-bold">ENQUIRIES</div>
                            <div class="h6 mb-0">{{ $dashboard_data['enquiry_stats']['today_new'] ?? 0 }} New Today</div>
                            <small class="text-primary">{{ $dashboard_data['enquiry_stats']['conversion_rate'] ?? 0 }}%
                                Conversion</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Academics -->
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="avatar-md bg-light rounded-circle text-danger me-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <div class="small text-muted fw-bold">ACADEMICS</div>
                            <div class="h6 mb-0">{{ $dashboard_data['academic_stats']['total_subjects'] ?? 0 }} Subjects
                            </div>
                            <small class="text-muted">Curriculum Active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Charts Row -->
        <div class="row mb-4">
            <!-- Revenue Chart -->
            <div class="col-xl-8 col-lg-7 mb-4 animate-fade-in animate-delay-200">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6 class="m-0 text-primary"><i class="fas fa-chart-line me-2"></i>Financial Performance</h6>
                        <div class="dropdown no-arrow">
                            <a href="#" class="btn btn-sm btn-link text-muted" data-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow border-0">
                                <a class="dropdown-item" href="#">View Full Report</a>
                                <a class="dropdown-item" href="#">Export PDF</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 320px;">
                            <canvas id="revenueExpenseChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Distribution -->
            <div class="col-xl-4 col-lg-5 mb-4 animate-fade-in animate-delay-300">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6 class="m-0 text-info"><i class="fas fa-chart-pie me-2"></i>Students by Course</h6>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="chart-pie" style="height: 250px;">
                            <canvas id="studentDistributionChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="me-2">
                                <i class="fas fa-circle text-primary"></i> CS
                            </span>
                            <span class="me-2">
                                <i class="fas fa-circle text-success"></i> Business
                            </span>
                            <span class="me-2">
                                <i class="fas fa-circle text-info"></i> Engg
                            </span>
                            <span>
                                <i class="fas fa-circle text-warning"></i> Arts
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats & Actions -->
        <div class="row mb-4">
            <!-- Pending Component Payments -->
            <div class="col-lg-4 mb-4 animate-fade-in animate-delay-100">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6 class="m-0 text-warning"><i class="fas fa-hourglass-half me-2"></i>Pending Payments</h6>
                        <button class="btn btn-sm btn-light rounded-circle"><i class="fas fa-sync-alt"></i></button>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-4 bg-light border-bottom">
                            @php
                                $pending = $dashboard_data['pending_component_payments'] ?? [
                                    'total_pending_amount' => 0,
                                    'overdue_amount' => 0,
                                    'category_breakdown' => []
                                ];
                            @endphp
                            <div class="row text-center">
                                <div class="col-6 border-end">
                                    <div class="h4 mb-0 font-weight-bold text-dark">
                                        ₹{{ number_format($pending['total_pending_amount'] / 100000, 2) }}L</div>
                                    <div class="text-xs text-uppercase text-muted fw-bold">Total Pending</div>
                                </div>
                                <div class="col-6">
                                    <div class="h4 mb-0 font-weight-bold text-danger">
                                        ₹{{ number_format($pending['overdue_amount'] / 100000, 2) }}L</div>
                                    <div class="text-xs text-uppercase text-muted fw-bold">Overdue</div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            @php
                                $categories = $dashboard_data['pending_component_payments']['category_breakdown'] ?? [];
                            @endphp
                            @foreach(collect($categories)->take(4) as $cat => $data)
                                <div
                                    class="list-group-item d-flex align-items-center justify-content-between px-4 py-3 border-light">
                                    <div>
                                        <h6 class="mb-0 text-sm font-weight-bold">{{ $cat }}</h6>
                                        <small class="text-muted">{{ $data['student_count'] }} Students</small>
                                    </div>
                                    <span class="text-sm font-weight-bold">₹{{ number_format($data['total_amount']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-center py-3">
                        <a href="{{ route('admin.component-payments.index') }}"
                            class="text-primary text-decoration-none text-sm fw-bold">
                            View All Payments <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity / System Alerts -->
            <div class="col-lg-4 mb-4 animate-fade-in animate-delay-200">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6 class="m-0 text-secondary"><i class="fas fa-bell me-2"></i>System Alerts</h6>
                        <span class="badge bg-danger rounded-pill">3 New</span>
                    </div>
                    <div class="card-body p-0">
                        <div style="max-height: 400px; overflow-y: auto;">
                            @php
                                $alerts = $dashboard_data['system_alerts'] ?? [];
                            @endphp
                            @foreach($alerts as $alert)
                                <div class="p-3 border-bottom d-flex gap-3">
                                    <div class="activity-icon bg-soft-{{ $alert['level'] }} text-{{ $alert['level'] }}">
                                        <i class="fas fa-{{ $alert['icon'] }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 text-sm font-weight-bold text-dark">{{ $alert['title'] }}</h6>
                                            <small class="text-muted" style="font-size: 0.7rem;">{{ $alert['time'] }}</small>
                                        </div>
                                        <p class="mb-0 text-muted small">{{ $alert['message'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-center py-3">
                        <a href="#" class="text-secondary text-decoration-none text-sm fw-bold">
                            View All Alerts <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Grid -->
            <div class="col-lg-4 mb-4 animate-fade-in animate-delay-300">
                <div class="card-modern h-100">
                    <div class="card-header-modern">
                        <h6 class="m-0 text-dark"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 h-100">
                            <div class="col-6">
                                <a href="{{ route('admin.students.index') }}" class="btn-quick-action">
                                    <i class="fas fa-user-graduate text-primary"></i>
                                    <span>Students</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('admin.component-payments.index') }}" class="btn-quick-action">
                                    <i class="fas fa-credit-card text-success"></i>
                                    <span>Payments</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('admin.enquiries.index') }}" class="btn-quick-action">
                                    <i class="fas fa-headset text-warning"></i>
                                    <span>Enquiries</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="#" class="btn-quick-action">
                                    <i class="fas fa-file-alt text-info"></i>
                                    <span>Reports</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // -----------------------------------------------------------
            // CLOCK UPDATE
            // -----------------------------------------------------------
            function updateClock() {
                const now = new Date();
                const display = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                const el = document.getElementById('clock-time');
                if (el) el.textContent = display;
            }
            setInterval(updateClock, 1000); // Pulse every second
            updateClock();

            // -----------------------------------------------------------
            // CHART CONFIGS
            // -----------------------------------------------------------
            Chart.defaults.font.family = "'Nunito', sans-serif";
            Chart.defaults.color = '#858796';

            const colors = {
                primary: '#4e73df',
                success: '#1cc88a',
                info: '#36b9cc',
                warning: '#f6c23e',
                danger: '#e74a3b',
                secondary: '#858796'
            };

            // 1. Revenue Vs Expenses Chart
            const ctxRevenue = document.getElementById("revenueExpenseChart");
            if (ctxRevenue) {
                new Chart(ctxRevenue, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($dashboard_data['revenue_expense_chart']['labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']) !!},
                        datasets: [{
                            label: "Revenue",
                            lineTension: 0.3,
                            backgroundColor: "rgba(78, 115, 223, 0.05)",
                            borderColor: colors.primary,
                            pointRadius: 3,
                            pointBackgroundColor: colors.primary,
                            pointBorderColor: colors.primary,
                            pointHoverRadius: 3,
                            pointHoverBackgroundColor: colors.primary,
                            pointHoverBorderColor: colors.primary,
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            data: {!! json_encode($dashboard_data['revenue_expense_chart']['revenue'] ?? []) !!},
                        }, {
                            label: "Expenses",
                            lineTension: 0.3,
                            backgroundColor: "rgba(231, 74, 59, 0.05)",
                            borderColor: colors.danger,
                            pointRadius: 3,
                            pointBackgroundColor: colors.danger,
                            pointBorderColor: colors.danger,
                            data: {!! json_encode($dashboard_data['revenue_expense_chart']['expenses'] ?? []) !!},
                            borderDash: [5, 5]
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                        scales: {
                            x: { grid: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 7 } },
                            y: {
                                ticks: { maxTicksLimit: 5, padding: 10, callback: function (value) { return '₹' + value / 1000 + 'k'; } },
                                grid: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] }
                            },
                        },
                        plugins: {
                            legend: { display: true, position: 'top' },
                            tooltip: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyColor: "#858796",
                                titleColor: '#6e707e',
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                intersect: false,
                                mode: 'index',
                                caretPadding: 10,
                            }
                        }
                    }
                });
            }

            // 2. Student Distribution Chart
            const ctxPie = document.getElementById("studentDistributionChart");
            if (ctxPie) {
                new Chart(ctxPie, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode(array_keys($dashboard_data['student_distribution'] ?? [])) !!},
                        datasets: [{
                            data: {!! json_encode(array_values($dashboard_data['student_distribution'] ?? [])) !!},
                            backgroundColor: [colors.primary, colors.success, colors.info, colors.warning],
                            hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyColor: "#858796",
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                caretPadding: 10,
                            }
                        },
                    },
                });
            }
        });
    </script>
@endpush