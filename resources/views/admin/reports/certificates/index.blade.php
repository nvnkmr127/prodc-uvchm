@extends('layouts.theme')
@section('title', 'Certificate Tracking Report')

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

        .border-primary {
            border-left-color: var(--crm-primary) !important;
        }

        .border-success {
            border-left-color: var(--crm-success) !important;
        }

        .border-info {
            border-left-color: var(--crm-info) !important;
        }

        .border-warning {
            border-left-color: var(--crm-warning) !important;
        }

        .border-danger {
            border-left-color: var(--crm-danger) !important;
        }

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

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Certificate Tracking Report</h1>
            <div>
                <span class="badge badge-primary px-3 py-2 shadow-sm">
                    <i class="fas fa-certificate mr-1"></i> Compliance Tracking
                </span>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="card shadow mb-4 border-0 rounded-lg">
            <div class="card-body">
                <form id="certificate_report_filters" class="row align-items-end">
                    <input type="hidden" name="sort_by" id="sort_by" value="{{ $sortBy }}">
                    <input type="hidden" name="sort_order" id="sort_order" value="{{ $sortOrder }}">

                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-gray-600">Filter by Course</label>
                        <select name="course_id" class="form-control bg-light border-0 shadow-none select2"
                            id="course_filter">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-gray-600">Filter by Batch</label>
                        <select name="batch_id" class="form-control bg-light border-0 shadow-none select2"
                            id="batch_filter">
                            <option value="">All Batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ $batchId == $batch->id ? 'selected' : '' }}>
                                    {{ $batch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-gray-600">Status</label>
                        <select name="status" class="form-control bg-light border-0 shadow-none select2" id="status_filter">
                            <option value="">All Statuses</option>
                            <option value="received" {{ $status == 'received' ? 'selected' : '' }}>Received</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 mb-md-0">
                        <label class="small font-weight-bold text-gray-600">Certificate Type</label>
                        <select name="certificate_type" class="form-control bg-light border-0 shadow-none select2"
                            id="type_filter">
                            <option value="">All Types</option>
                            <option value="10th" {{ $certificateType == '10th' ? 'selected' : '' }}>10th</option>
                            <option value="Inter" {{ $certificateType == 'Inter' ? 'selected' : '' }}>Intermediate</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-right">
                        <a href="{{ route('admin.reports.certificates.index') }}"
                            class="btn btn-light px-3 border btn-block">
                            <i class="fas fa-undo mr-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="stats-grid" id="stats_container">
            <div class="stat-card-mini border-primary">
                <div class="stat-label">Total Students</div>
                <div class="stat-value" id="stat_total">{{ number_format($totalStudents) }}</div>
                <i class="fas fa-users stat-icon"></i>
            </div>
            <div class="stat-card-mini border-success">
                <div class="stat-label">Certificates Received</div>
                <div class="stat-value text-success" id="stat_received">{{ number_format($receivedCount) }}</div>
                <i class="fas fa-check-circle stat-icon text-success"></i>
            </div>
            <div class="stat-card-mini border-warning">
                <div class="stat-label">Pending Submission</div>
                <div class="stat-value text-warning" id="stat_pending">{{ number_format($pendingCount) }}</div>
                <i class="fas fa-clock stat-icon text-warning"></i>
            </div>
            <div class="stat-card-mini border-info">
                <div class="stat-label">Collection Rate</div>
                <div class="stat-value text-info" id="stat_rate">
                    {{ $totalStudents > 0 ? round(($receivedCount / $totalStudents) * 100, 1) : 0 }}%
                </div>
                <i class="fas fa-percentage stat-icon text-info"></i>
            </div>
        </div>

        <div class="row">
            {{-- Distribution Chart --}}
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card shadow border-0 rounded-lg h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-primary">Status Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Course-wise Pending Chart --}}
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="card shadow border-0 rounded-lg h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-primary">Pending Certificates by Course</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="coursePendingChart"></canvas>
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
                <h6 class="m-0 font-weight-bold text-primary">Detailed Certificate Data</h6>
                <button class="btn btn-sm btn-light border" onclick="window.print()">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
            </div>
            <div class="card-body p-0" id="table_container">
                @include('admin.reports.certificates._table')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let statusChart, courseChart;

            // Passed from controller
            let typeStats = {!! json_encode($certTypeStats) !!};
            let receivedCount = {{ $receivedCount }};
            let pendingCount = {{ $pendingCount }};
            let coursePendingStats = {!! json_encode($coursePendingStats) !!};

            function initCharts() {
                // 1. Status Distribution Chart (Doughnut)
                const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
                statusChart = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Received', 'Pending'],
                        datasets: [{
                            data: [receivedCount, pendingCount],
                            backgroundColor: ['#1cc88a', '#f6c23e'],
                            hoverOffset: 10,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, padding: 20 }
                            }
                        },
                        cutout: '70%',
                        animation: { duration: 500 }
                    }
                });

                // 2. Pending by Course Chart (Bar)
                const courseCtx = document.getElementById('coursePendingChart').getContext('2d');

                // Transform data structure for chart
                const courseLabels = coursePendingStats.map(item => item.name);
                const courseData = coursePendingStats.map(item => item.count);

                courseChart = new Chart(courseCtx, {
                    type: 'bar',
                    data: {
                        labels: courseLabels,
                        datasets: [{
                            label: 'Pending Certificates',
                            data: courseData,
                            backgroundColor: 'rgba(246, 194, 62, 0.8)',
                            borderColor: '#f6c23e',
                            borderWidth: 1,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            },
                            x: { grid: { display: false } }
                        },
                        animation: { duration: 500 }
                    }
                });
            }

            initCharts();

            function refreshReport(url = '{{ route("admin.reports.certificates.index") }}') {
                $('#loading_overlay').css('display', 'flex');

                $.ajax({
                    url: url,
                    method: 'GET',
                    data: $('#certificate_report_filters').serialize(),
                    success: function (response) {
                        $('#table_container').html(response.html);

                        // Update Stats
                        const stats = response.stats;
                        $('#stat_total').text(stats.total);
                        $('#stat_received').text(stats.received);
                        $('#stat_pending').text(stats.pending);

                        const rate = stats.total > 0 ? ((stats.received / stats.total) * 100).toFixed(1) : 0;
                        $('#stat_rate').text(rate + '%');

                        // Update Status Chart
                        statusChart.data.datasets[0].data = [stats.received, stats.pending];
                        statusChart.update();

                        // Update Course Chart
                        const newLabels = stats.coursePendingStats.map(item => item.name);
                        const newData = stats.coursePendingStats.map(item => item.count);

                        courseChart.data.labels = newLabels;
                        courseChart.data.datasets[0].data = newData;
                        courseChart.update();

                        $('#loading_overlay').hide();
                    }
                });
            }

            // Filter Form Submission
            $('#certificate_report_filters').on('change', 'select', function () {
                refreshReport();
            });

            // Sorting Logic
            $(document).on('click', '.sortable', function () {
                const sortBy = $(this).data('sort');
                const currentSort = $('#sort_by').val();
                let currentOrder = $('#sort_order').val();

                if (sortBy === currentSort) {
                    currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    currentOrder = 'asc';
                }

                $('#sort_by').val(sortBy);
                $('#sort_order').val(currentOrder);

                refreshReport();
            });

            // Pagination AJAX
            $(document).on('click', '.ajax-pagination a', function (e) {
                e.preventDefault();
                refreshReport($(this).attr('href'));
            });

            // Dynamic Batch Filtering
            $('#course_filter').on('change', function () {
                const courseId = $(this).val();
                const batchSelect = $('#batch_filter');

                batchSelect.html('<option value="">All Batches</option>');
                if (courseId) {
                    $.ajax({
                        url: '{{ route("admin.students.get-batches-for-course", ":id") }}'.replace(':id', courseId),
                        method: 'GET',
                        success: function (response) {
                            $.each(response, function (index, batch) {
                                batchSelect.append(`<option value="${batch.id}">${batch.name}</option>`);
                            });
                        }
                    });
                }
            });
        });
    </script>
@endpush