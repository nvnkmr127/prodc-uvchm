{{-- resources/views/attendance/analytics/index.blade.php --}}

@extends('layouts.theme')

@section('title', 'Attendance Analytics')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-chart-line text-primary"></i> Attendance Analytics
                </h1>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="refreshData()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                    <a href="{{ route('attendance.reports.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's Overview Cards --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $todayAttendance['present_count'] ?? 0 }}
                            </div>
                            <div class="text-xs text-muted">
                                {{ $todayAttendance['present_percentage'] ?? 0 }}% of total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $todayAttendance['absent_count'] ?? 0 }}
                            </div>
                            <div class="text-xs text-muted">
                                {{ $todayAttendance['absent_percentage'] ?? 0 }}% of total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $todayAttendance['late_count'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $todayAttendance['total_students'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Weekly Trends Chart --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>Weekly Attendance Trends
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="weeklyTrendsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Batch Statistics Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table mr-2"></i>Batch-wise Attendance
                    </h6>
                </div>
                <div class="card-body">
                    @if(!empty($batchStats))
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Batch</th>
                                        <th>Course</th>
                                        <th>Total Students</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Attendance Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($batchStats as $stat)
                                        <tr>
                                            <td>{{ $stat['batch_name'] }}</td>
                                            <td>{{ $stat['course_name'] }}</td>
                                            <td>{{ $stat['total_students'] }}</td>
                                            <td>
                                                <span class="badge badge-success">{{ $stat['present_count'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-danger">{{ $stat['absent_count'] }}</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" 
                                                         style="width: {{ $stat['attendance_percentage'] }}%">
                                                        {{ $stat['attendance_percentage'] }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar fa-3x text-gray-300 mb-3"></i>
                            <h5 class="text-gray-500">No Batch Data Available</h5>
                            <p class="text-gray-400">Create batches and add students to see analytics.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initWeeklyTrendsChart();
});

function initWeeklyTrendsChart() {
    const ctx = document.getElementById('weeklyTrendsChart').getContext('2d');
    const weeklyData = @json($weeklyTrends);
    
    const labels = weeklyData.map(item => item.day);
    const presentData = weeklyData.map(item => item.percentage);
    const absentData = weeklyData.map(item => 100 - item.percentage);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Present %',
                data: presentData,
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 2,
                fill: true
            }, {
                label: 'Absent %',
                data: absentData,
                backgroundColor: 'rgba(231, 74, 59, 0.1)',
                borderColor: 'rgba(231, 74, 59, 1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                        }
                    }
                }
            }
        }
    });
}

function refreshData() {
    window.location.reload();
}
</script>
@endpush