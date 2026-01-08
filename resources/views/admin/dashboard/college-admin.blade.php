@extends('layouts.theme')

@section('title', 'Academic Dashboard')

@push('scripts')
    <script>
        // Initialize helpers if needed
    </script>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Dashboard Header -->
        <div class="dashboard-header-modern">
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

        <!-- Quick Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card-interactive">
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

            <div class="stat-card-interactive">
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

            <div class="stat-card-interactive">
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

            <div class="stat-card-interactive">
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
        </div>

        <!-- Payment Collections Section -->
        <div class="card-modern">
            <div class="card-header-modern bg-light">
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
            <div class="p-4">
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
                <div class="payment-stats-grid" id="payment-stats">
                    <div class="payment-stat-box">
                        <div class="payment-stat-amount text-success" id="total-collected">
                            ₹{{ number_format($dashboard_data['my_collections']['today'] ?? 0) }}
                        </div>
                        <div class="payment-stat-label">Total Collected</div>
                    </div>
                    <div class="payment-stat-box">
                        <div class="payment-stat-amount text-primary" id="transactions-count">
                            {{ $dashboard_data['my_collections']['transactions'] ?? 0 }}
                        </div>
                        <div class="payment-stat-label">Transactions</div>
                    </div>
                    <div class="payment-stat-box">
                        <div class="payment-stat-amount text-info" id="avg-payment">
                            ₹{{ number_format($dashboard_data['my_collections']['avg_amount'] ?? 0) }}
                        </div>
                        <div class="payment-stat-label">Average Payment</div>
                    </div>
                    <div class="payment-stat-box">
                        <div class="payment-stat-amount text-warning" id="online-percentage">
                            {{ $dashboard_data['my_collections']['online_percentage'] ?? 0 }}%
                        </div>
                        <div class="payment-stat-label">Online Payments</div>
                    </div>
                </div>

                <!-- Payment Charts -->
                <div class="chart-container">
                    <canvas id="paymentTrendsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left Column - Charts and Analytics -->
            <div class="main-content">
                <!-- Student Attendance Analytics -->
                <div class="card-modern">
                    <div class="card-header-modern bg-light">
                        <h6 class="widget-title">
                            <i class="fas fa-chart-area"></i>Student Attendance Analytics
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                                data-toggle="dropdown">
                                View
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('daily')">Daily View</a>
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('weekly')">Weekly View</a>
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('monthly')">Monthly View</a>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <!-- Attendance Overview Stats -->
                        <div class="attendance-overview-grid">
                            <div class="attendance-stat-box">
                                <div class="attendance-percentage excellent">
                                    {{ $dashboard_data['attendance_stats']['excellent'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Excellent (90%+)</div>
                            </div>
                            <div class="attendance-stat-box">
                                <div class="attendance-percentage good">
                                    {{ $dashboard_data['attendance_stats']['good'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Good (75-89%)</div>
                            </div>
                            <div class="attendance-stat-box">
                                <div class="attendance-percentage average">
                                    {{ $dashboard_data['attendance_stats']['average'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Average (60-74%)</div>
                            </div>
                            <div class="attendance-stat-box">
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
                <div class="card-modern">
                    <div class="card-header-modern bg-light">
                        <h6 class="widget-title">
                            <i class="fas fa-credit-card"></i>Payment Mode Distribution
                        </h6>
                    </div>
                    <div class="p-4">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="paymentModeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Activity and Quick Info -->
            <div class="sidebar-content">
                <!-- My Activity Log -->
                <div class="card-modern">
                    <div class="card-header-modern bg-light">
                        <h6 class="widget-title">
                            <i class="fas fa-history"></i>My Activity Log
                        </h6>
                        <a href="{{ route('admin.activity-log.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <div class="p-4">
                        <div class="scrollable-log" id="activity-list">
                            @if(isset($dashboard_data['my_activities']) && count($dashboard_data['my_activities']) > 0)
                                @foreach($dashboard_data['my_activities'] as $activity)
                                    <div class="activity-item">
                                        <div class="activity-icon {{ $activity['type'] ?? 'update' }}">
                                            <i class="fas fa-{{ $activity['icon'] ?? 'edit' }}"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">{{ $activity['description'] ?? 'No description provided.' }}
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
                <div class="card-modern">
                    <div class="card-header-modern bg-light">
                        <h6 class="widget-title">
                            <i class="fas fa-bolt"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="p-4">
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
                <div class="card-modern">
                    <div class="card-header-modern bg-light">
                        <h6 class="widget-title">
                            <i class="fas fa-exclamation-triangle"></i>Pending Collections
                        </h6>
                    </div>
                    <div class="p-4">
                        @if(isset($dashboard_data['pending_collections']) && count($dashboard_data['pending_collections']) > 0)
                            @foreach(array_slice($dashboard_data['pending_collections'], 0, 5) as $pending)
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                    <div>
                                        <div class="font-weight-bold">{{ $pending['student_name'] }}</div>
                                        <small class="text-muted">{{ $pending['course'] ?? 'N/A' }}</small>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-danger font-weight-bold">₹{{ number_format($pending['amount']) }}</div>
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