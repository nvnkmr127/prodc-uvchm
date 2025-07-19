@extends('layouts.theme')
@section('title', 'Admin Dashboard')

@section('content')
<!-- Clean Page Header -->
<div class="page-header">
    <div class="header-content">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Overview of your institution's key metrics</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-secondary">Export</button>
        <button class="btn btn-primary">New Entry</button>
    </div>
</div>

<!-- Primary Metrics -->
<div class="metrics-grid">
    <div class="metric-card primary">
        <div class="metric-header">
            <div class="metric-icon">
                <i class="fas fa-users"></i>
            </div>
            <span class="metric-trend positive">+12%</span>
        </div>
        <div class="metric-value">{{ $totalStudents }}</div>
        <div class="metric-label">Active Students</div>
    </div>

    <div class="metric-card info">
        <div class="metric-header">
            <div class="metric-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <span class="metric-trend neutral">Pending</span>
        </div>
        <div class="metric-value">{{ $pendingAdmissionsCount }}</div>
        <div class="metric-label">Pending Admissions</div>
    </div>

    <div class="metric-card success">
        <div class="metric-header">
            <div class="metric-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <span class="metric-trend positive">+8%</span>
        </div>
        <div class="metric-value">{{ setting('currency_symbol', '₹') }} {{ number_format($feesCollectedThisMonth, 2) }}</div>
        <div class="metric-label">Fees Collected (This Month)</div>
    </div>

    <div class="metric-card warning">
        <div class="metric-header">
            <div class="metric-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <span class="metric-trend negative">-5%</span>
        </div>
        <div class="metric-value">{{ setting('currency_symbol', '₹') }} {{ number_format($totalOutstandingDues, 2) }}</div>
        <div class="metric-label">Outstanding Dues</div>
    </div>
</div>

<!-- Secondary Metrics -->
<div class="secondary-metrics">
    <div class="secondary-card">
        <div class="secondary-icon">
            <i class="fas fa-book-open"></i>
        </div>
        <div class="secondary-content">
            <div class="secondary-value">{{ $totalCourses }}</div>
            <div class="secondary-label">Courses</div>
        </div>
    </div>

    <div class="secondary-card">
        <div class="secondary-icon">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="secondary-content">
            <div class="secondary-value">{{ $totalBatches }}</div>
            <div class="secondary-label">Batches</div>
        </div>
    </div>

    <div class="secondary-card">
        <div class="secondary-icon">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="secondary-content">
            <div class="secondary-value">{{ $totalFaculty }}</div>
            <div class="secondary-label">Faculty</div>
        </div>
    </div>

    <div class="secondary-card">
        <div class="secondary-icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="secondary-content">
            <div class="secondary-value">{{ $totalAlumni }}</div>
            <div class="secondary-label">Alumni</div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="charts-section">
    <!-- Financial Chart -->
    <div class="chart-card full-width">
        <div class="chart-header">
            <h3 class="chart-title">Financial Overview</h3>
            <p class="chart-subtitle">Income vs Expenses (Last 30 Days)</p>
        </div>
        <div class="chart-body">
            <canvas id="financialLineChart"></canvas>
        </div>
    </div>

    <!-- Split Charts -->
    <div class="chart-row">
        <div class="chart-card attendance">
            <div class="chart-header">
                <h3 class="chart-title">Attendance</h3>
                <p class="chart-subtitle">Last 30 Days</p>
            </div>
            <div class="chart-body">
                <canvas id="attendanceBarChart"></canvas>
            </div>
        </div>

        <div class="chart-card distribution">
            <div class="chart-header">
                <h3 class="chart-title">Course Distribution</h3>
                <p class="chart-subtitle">Student Enrollment</p>
            </div>
            <div class="chart-body">
                <canvas id="coursePieChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Activity Section -->
<div class="activity-section">
    <div class="activity-header">
        <h3 class="activity-title">Recent Activity</h3>
        <button class="btn btn-ghost">View All</button>
    </div>
    <div class="activity-list">
        @forelse($latestActivities as $activity)
        <div class="activity-item">
            <div class="activity-time">{{ $activity->created_at->diffForHumans() }}</div>
            <div class="activity-content">
                <div class="activity-description">{{ $activity->description }}</div>
                <div class="activity-user">by {{ $activity->causer->name ?? 'System' }}</div>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No recent activity</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('styles')
<style>
/* Reset and Base */
* {
    box-sizing: border-box;
}

/* Layout */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.header-content .page-title {
    font-size: 1.875rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.25rem 0;
}

.header-content .page-subtitle {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

/* Buttons */
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-primary {
    background-color: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background-color: #2563eb;
}

.btn-secondary {
    background-color: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background-color: #e5e7eb;
}

.btn-ghost {
    background-color: transparent;
    color: #6b7280;
    padding: 0.25rem 0.5rem;
}

.btn-ghost:hover {
    color: #374151;
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    position: relative;
}

.metric-card.primary { border-left: 4px solid #3b82f6; }
.metric-card.info { border-left: 4px solid #06b6d4; }
.metric-card.success { border-left: 4px solid #10b981; }
.metric-card.warning { border-left: 4px solid #f59e0b; }

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.metric-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
}

.metric-card.primary .metric-icon { background-color: #dbeafe; color: #3b82f6; }
.metric-card.info .metric-icon { background-color: #cffafe; color: #06b6d4; }
.metric-card.success .metric-icon { background-color: #d1fae5; color: #10b981; }
.metric-card.warning .metric-icon { background-color: #fef3c7; color: #f59e0b; }

.metric-trend {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.metric-trend.positive { background-color: #d1fae5; color: #065f46; }
.metric-trend.negative { background-color: #fee2e2; color: #991b1b; }
.metric-trend.neutral { background-color: #f3f4f6; color: #374151; }

.metric-value {
    font-size: 1.875rem;
    font-weight: 600;
    color: #111827;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.metric-label {
    color: #6b7280;
    font-size: 0.875rem;
}

/* Secondary Metrics */
.secondary-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.secondary-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.secondary-icon {
    width: 2rem;
    height: 2rem;
    background-color: #f3f4f6;
    color: #6b7280;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

.secondary-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
    line-height: 1;
}

.secondary-label {
    color: #6b7280;
    font-size: 0.875rem;
}

/* Charts */
.charts-section {
    margin-bottom: 2rem;
}

.chart-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}

.chart-card.full-width {
    margin-bottom: 1.5rem;
}

.chart-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}

.chart-header {
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.chart-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.25rem 0;
}

.chart-subtitle {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
}

.chart-body {
    padding: 1.5rem;
    height: 300px;
    position: relative;
}

.chart-card.full-width .chart-body {
    height: 350px;
}

/* Activity */
.activity-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}

.activity-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.activity-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.activity-list {
    padding: 1rem 1.5rem;
}

.activity-item {
    display: flex;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-time {
    color: #6b7280;
    font-size: 0.75rem;
    min-width: 5rem;
    padding-top: 0.125rem;
}

.activity-description {
    color: #111827;
    font-size: 0.875rem;
    line-height: 1.4;
}

.activity-user {
    color: #6b7280;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .header-actions {
        justify-content: flex-start;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .activity-item {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .activity-time {
        min-width: auto;
    }
}

@media (max-width: 640px) {
    .secondary-metrics {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-body {
        height: 250px;
    }
}
</style>
@endpush

@push('scripts')
<!-- Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Helper function for number formatting
    function number_format(number) { return new Intl.NumberFormat('en-IN').format(number); }

    // Clean chart defaults
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#6b7280';

    // Financial Overview Line Chart
    var ctxLine = document.getElementById("financialLineChart");
    if(ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: {!! json_encode($financialLabels) !!},
                datasets: [{
                    label: "Income",
                    fill: false,
                    borderColor: "#10b981",
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: "#10b981",
                    pointBorderColor: "#ffffff",
                    pointBorderWidth: 2,
                    data: {!! json_encode($incomeData) !!},
                }, {
                    label: "Expenses",
                    fill: false,
                    borderColor: "#f59e0b",
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: "#f59e0b",
                    pointBorderColor: "#ffffff",
                    pointBorderWidth: 2,
                    data: {!! json_encode($expenseData) !!},
                }],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 11 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: {
                            color: '#f3f4f6',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '₹' + number_format(value);
                            },
                            padding: 12,
                            font: { size: 10 }
                        }
                    },
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: {
                            padding: 12,
                            font: { size: 10 }
                        }
                    }
                },
                elements: {
                    point: { hoverRadius: 4 },
                    line: { tension: 0.1 }
                }
            }
        });
    }
    
    // Attendance Bar Chart
    var ctxBar = document.getElementById("attendanceBarChart");
    if(ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: {!! json_encode($attendanceLabels) !!},
                datasets: [{
                    label: "Present",
                    backgroundColor: "#3b82f6",
                    data: {!! json_encode($presentData) !!},
                }, {
                    label: "Absent",
                    backgroundColor: "#e5e7eb",
                    data: {!! json_encode($absentData) !!},
                }],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 11 }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        border: { display: false },
                        grid: { display: false },
                        ticks: {
                            padding: 12,
                            font: { size: 10 }
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        border: { display: false },
                        grid: {
                            color: '#f3f4f6',
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0,
                            padding: 12,
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    }

    // Course Distribution Pie Chart
    var ctxPie = document.getElementById("coursePieChart");
    if (ctxPie) { 
        new Chart(ctxPie, { 
            type: 'doughnut', 
            data: { 
                labels: {!! json_encode($courseLabels) !!}, 
                datasets: [{ 
                    data: {!! json_encode($courseData) !!}, 
                    backgroundColor: ['#3b82f6', '#10b981', '#06b6d4', '#f59e0b', '#ef4444', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }], 
            }, 
            options: { 
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { 
                        display: true, 
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 10 }
                        }
                    }
                },
                cutout: '60%'
            }
        }); 
    }
});
</script>
@endpush