@extends('layouts.theme')
@section('title', 'Attendance Data Report')

@push('styles')
    <style>
        :root {
            --crm-primary: #4e73df;
            --crm-secondary: #858796;
            --crm-success: #1cc88a;
            --crm-info: #36b9cc;
            --crm-warning: #f6c23e;
            --crm-danger: #e74a3b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card-mini {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-left: 0.25rem solid #e3e6f0;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card-mini:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--crm-secondary);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #5a5c69;
            line-height: 1;
        }

        .stat-icon {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2rem;
            opacity: 0.1;
        }

        .border-primary { border-left-color: var(--crm-primary) !important; }
        .border-success { border-left-color: var(--crm-success) !important; }
        .border-info { border-left-color: var(--crm-info) !important; }
        .border-warning { border-left-color: var(--crm-warning) !important; }
        .border-danger { border-left-color: var(--crm-danger) !important; }

        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        .table-custom {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom thead th {
            background: #f8f9fc;
            padding: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: var(--crm-secondary);
            border-bottom: 2px solid #e3e6f0;
        }

        .table-custom tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f2f5;
        }

        .sortable {
            cursor: pointer;
            position: relative;
            transition: background 0.2s;
        }

        .sortable:hover {
            background: #eaecf4;
        }

        .sortable i {
            font-size: 0.7rem;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            z-index: 10;
            display: none;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Attendance Analysis Report</h1>
        <div>
            <span class="badge badge-primary px-3 py-2 shadow-sm">
                <i class="fas fa-calendar-check mr-1"></i> Performance Insights
            </span>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card shadow mb-4 border-0 rounded-lg">
        <div class="card-body">
            <form id="attendance_report_filters" class="row align-items-end">
                <input type="hidden" name="sort_by" id="sort_by" value="{{ $sortBy ?? 'attendance_percentage' }}">
                <input type="hidden" name="sort_order" id="sort_order" value="{{ $sortOrder ?? 'desc' }}">
                
                <div class="col-md-3 mb-3">
                    <label class="small font-weight-bold text-gray-600">Course</label>
                    <select name="course_id" class="form-control bg-light border-0 shadow-none select2" id="course_filter">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ ($courseId ?? '') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="small font-weight-bold text-gray-600">Batch</label>
                    <select name="batch_id" class="form-control bg-light border-0 shadow-none select2" id="batch_filter">
                        <option value="">All Batches</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ ($batchId ?? '') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="small font-weight-bold text-gray-600">Start Date</label>
                    <input type="date" name="start_date" class="form-control bg-light border-0 shadow-none" value="{{ $startDate ?? '' }}" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="small font-weight-bold text-gray-600">End Date</label>
                    <input type="date" name="end_date" class="form-control bg-light border-0 shadow-none" value="{{ $endDate ?? '' }}" required>
                </div>
                <div class="col-md-2 mb-3 text-right">
                    <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm btn-block">
                        <i class="fas fa-filter mr-1"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="stats-grid" id="stats_container">
        <div class="stat-card-mini border-primary">
            <div class="stat-label">Total Students</div>
            <div class="stat-value" id="stat_total">{{ isset($stats) ? $stats['total_students'] : 0 }}</div>
            <i class="fas fa-users stat-icon"></i>
        </div>
        <div class="stat-card-mini border-success">
            <div class="stat-label">Avg. Attendance</div>
            <div class="stat-value"><span id="stat_avg_attendance">{{ isset($stats) ? $stats['avg_attendance'] : 0 }}</span><small>%</small></div>
            <i class="fas fa-chart-line stat-icon text-success"></i>
        </div>
        <div class="stat-card-mini border-info">
            <div class="stat-label">Avg. Present Days</div>
            <div class="stat-value" id="stat_avg_present">{{ isset($stats) ? $stats['avg_present'] : 0 }}</div>
            <i class="fas fa-check-circle stat-icon text-info"></i>
        </div>
        <div class="stat-card-mini border-danger">
            <div class="stat-label">Avg. Absent Days</div>
            <div class="stat-value text-danger" id="stat_avg_absent">{{ isset($stats) ? $stats['avg_absent'] : 0 }}</div>
            <i class="fas fa-times-circle stat-icon text-danger"></i>
        </div>
    </div>

    <div class="row">
        {{-- Attendance Buckets Chart --}}
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow border-0 rounded-lg h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance Distribution (Students)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="attendanceDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Overall Daily Status Chart --}}
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow border-0 rounded-lg h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-primary">Overall Status Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Table --}}
    <div class="card shadow border-0 rounded-lg mb-4" id="table_card">
        <div class="loading-overlay" id="loading_overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Detailed Attendance Data</h6>
            <div>
                <button class="btn btn-sm btn-success shadow-sm mr-2" onclick="exportReport()">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </button>
                <button class="btn btn-sm btn-light border mr-2" onclick="window.print()">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body p-0" id="table_container">
            {{-- Initial state or loaded data --}}
            @if(isset($students) && count($students) > 0)
                @include('admin.reports.attendance._table')
            @else
                <div class="text-center py-5">
                    <img src="{{ asset('img/undraw_no_data.svg') }}" style="width: 150px; opacity: 0.5;">
                    <p class="mt-3 text-gray-500">Select criteria and click Generate to view report.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let distChart, statusChart;

        function initCharts() {
            // 1. Distribution Chart (Bar)
            const distCtx = document.getElementById('attendanceDistributionChart').getContext('2d');
            distChart = new Chart(distCtx, {
                type: 'bar',
                data: {
                    labels: ['< 50%', '50% - 74%', '75% - 89%', '90% +'],
                    datasets: [{
                        label: 'Number of Students',
                        data: {!! isset($stats) ? json_encode(array_values($stats['distribution'])) : '[0, 0, 0, 0]' !!}, // Initial Data support
                        backgroundColor: [
                            'rgba(231, 74, 59, 0.8)', // Red
                            'rgba(246, 194, 62, 0.8)', // Yellow
                            'rgba(54, 185, 204, 0.8)', // Info/Blue
                            'rgba(28, 200, 138, 0.8)'  // Green
                        ],
                        borderWidth: 1,
                        borderRadius: 5
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 2. Status Breakdown Chart (Doughnut)
            const statusCtx = document.getElementById('statusBreakdownChart').getContext('2d');
            statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent'],
                    datasets: [{
                        // Use present_days and absent_days from stats. Check against controller return.
                        // Controller returns 'total_present' and 'total_absent' in stats array.
                        data: {!! isset($stats) ? json_encode([$stats['total_present'], $stats['total_absent']]) : '[0, 0]' !!},
                        backgroundColor: ['#1cc88a', '#e74a3b'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                    },
                    cutout: '60%'
                }
            });
        }

        initCharts();

        function refreshReport(url = '{{ route("admin.reports.attendance.index") }}') {
            $('#loading_overlay').css('display', 'flex');
            
            // Serialize form data
            let params = $('#attendance_report_filters').serialize();

            $.ajax({
                url: url,
                method: 'GET',
                data: params,
                success: function(response) {
                    if(response.error) {
                        alert(response.error);
                        $('#loading_overlay').hide();
                        return;
                    }

                    $('#table_container').html(response.html);
                    
                    // Update Stats
                    if(response.stats) {
                        $('#stat_total').text(response.stats.total_students);
                        $('#stat_avg_attendance').text(response.stats.avg_attendance);
                        $('#stat_avg_present').text(response.stats.avg_present);
                        $('#stat_avg_absent').text(response.stats.avg_absent);

                        // Update Charts
                        // 1. Distribution
                        distChart.data.datasets[0].data = Object.values(response.stats.distribution);
                        distChart.update();

                        // 2. Status Breakdown
                        statusChart.data.datasets[0].data = [
                            response.stats.total_present, 
                            response.stats.total_absent
                        ];
                        statusChart.update();
                    }

                    $('#loading_overlay').hide();
                },
                error: function(xhr) {
                    $('#loading_overlay').hide();
                    console.error('Error fetching report', xhr);
                }
            });
        }

        // Filter Form Submission
        $('#attendance_report_filters').on('submit', function(e) {
            e.preventDefault();
            refreshReport();
        });

        // Sorting Logic
        $(document).on('click', '.sortable', function() {
            const sortBy = $(this).data('sort');
            const currentSort = $('#sort_by').val();
            let currentOrder = $('#sort_order').val();

            if (sortBy === currentSort) {
                currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentOrder = 'asc'; // Default to ascending for new sort
            }

            $('#sort_by').val(sortBy);
            $('#sort_order').val(currentOrder);
            
            refreshReport();
        });

        // Pagination AJAX
        $(document).on('click', '.ajax-pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            refreshReport(url);
        });

        // Dynamic Batch Filtering
        $('#course_filter').on('change', function() {
            const courseId = $(this).val();
            const batchSelect = $('#batch_filter');
            
            batchSelect.html('<option value="">All Batches</option>');
            if (courseId) {
                $.ajax({
                    url: '{{ route("admin.students.get-batches-for-course", ":id") }}'.replace(':id', courseId),
                    method: 'GET',
                    success: function(response) {
                        $.each(response, function(index, batch) {
                            batchSelect.append(`<option value="${batch.id}">${batch.name}</option>`);
                        });
                    }
                });
            }
        });

        // Export Function attached to window for global access
        window.exportReport = function() {
            let params = $('#attendance_report_filters').serialize();
            let url = "{{ route('admin.reports.attendance.export') }}";
            window.location.href = url + "?" + params;
        };
    });
</script>
@endpush