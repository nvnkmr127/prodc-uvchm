@extends('layouts.theme')
@section('title', 'Student Age Analysis Report')

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
        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Student Age Analysis</h1>
        <div>
            <span class="badge badge-primary px-3 py-2 shadow-sm">
                <i class="fas fa-users mr-1"></i> Data-Driven Insights
            </span>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card shadow mb-4 border-0 rounded-lg">
        <div class="card-body">
            <form id="age_report_filters" class="row align-items-end">
                <input type="hidden" name="sort_by" id="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_order" id="sort_order" value="{{ $sortOrder }}">
                
                <div class="col-md-2 mb-3 mb-md-0">
                    <label class="small font-weight-bold text-gray-600">Filter by Course</label>
                    <select name="course_id" class="form-control bg-light border-0 shadow-none select2" id="course_filter">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <label class="small font-weight-bold text-gray-600">Filter by Batch</label>
                    <select name="batch_id" class="form-control bg-light border-0 shadow-none select2" id="batch_filter">
                        <option value="">All Batches</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $batchId == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <label class="small font-weight-bold text-gray-600">Age Group</label>
                    <select name="age_group" class="form-control bg-light border-0 shadow-none select2" id="age_group_filter">
                        <option value="">All Groups</option>
                        @foreach($ageGroups as $val => $label)
                            <option value="{{ $val }}" {{ $ageGroup == $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <label class="small font-weight-bold text-gray-600">Filter by Gender</label>
                    <select name="gender" class="form-control bg-light border-0 shadow-none select2" id="gender_filter">
                        <option value="">All Genders</option>
                        <option value="Male" {{ $gender == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ $gender == 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ $gender == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-4 text-right">
                    <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm">
                        <i class="fas fa-filter mr-1"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.reports.age.index') }}" class="btn btn-light px-3 ml-2 border">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="stats-grid" id="stats_container">
        <div class="stat-card-mini border-primary">
            <div class="stat-label">Total Analyzed</div>
            <div class="stat-value" id="stat_total">{{ number_format($students->total()) }}</div>
            <i class="fas fa-user-graduate stat-icon"></i>
        </div>
        <div class="stat-card-mini border-danger">
            <div class="stat-label">DOB Not Updated</div>
            <div class="stat-value text-danger" id="stat_missing">{{ number_format($missingDobCount) }}</div>
            <i class="fas fa-exclamation-triangle stat-icon text-danger"></i>
        </div>
        <div class="stat-card-mini border-success">
            <div class="stat-label">Avg. Age (Overall)</div>
            <div class="stat-value">
                <span id="stat_avg_overall">
                    @php 
                        $totalAge = 0; $count = 0;
                        foreach($averageAgeByCourse as $c) { $totalAge += $c->avg_age; $count++; }
                        $avg = $count > 0 ? round($totalAge/$count, 1) : 0;
                    @endphp
                    {{ $avg }}
                </span>
                <small class="text-gray-500" style="font-size: 0.8rem;">Years</small>
            </div>
            <i class="fas fa-chart-line stat-icon text-success"></i>
        </div>
        <div id="gender_stats_container" style="display: contents;">
            @foreach($genderAgeDist as $g)
            <div class="stat-card-mini {{ $g->gender == 'Male' ? 'border-info' : 'border-warning' }}">
                <div class="stat-label">Avg. Age ({{ $g->gender }})</div>
                <div class="stat-value">{{ round($g->avg_age, 1) }}</div>
                <i class="fas fa-{{ strtolower($g->gender) == 'male' ? 'mars' : 'venus' }} stat-icon"></i>
            </div>
            @endforeach
        </div>
    </div>

    <div class="row">
        {{-- Distribution Chart --}}
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow border-0 rounded-lg h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-primary">Age Group Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="ageDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Course-wise Avg Age --}}
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow border-0 rounded-lg h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-primary">Average Age by Course</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="courseAvgAgeChart"></canvas>
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
            <h6 class="m-0 font-weight-bold text-primary">Detailed Age Data</h6>
            <button class="btn btn-sm btn-light border" onclick="window.print()">
                <i class="fas fa-print mr-1"></i> Print
            </button>
        </div>
        <div class="card-body p-0" id="table_container">
            @include('admin.reports.age._table')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let ageDistChart, courseAvgChart;

        function initCharts() {
            // 1. Age Distribution Chart (Doughnut)
            const ageDistCtx = document.getElementById('ageDistributionChart').getContext('2d');
            ageDistChart = new Chart(ageDistCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode(array_keys($ageBuckets)) !!},
                    datasets: [{
                        data: {!! json_encode(array_values($ageBuckets)) !!},
                        backgroundColor: [
                            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                        ],
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

            // 2. Average Age by Course Chart (Bar)
            const courseAvgCtx = document.getElementById('courseAvgAgeChart').getContext('2d');
            courseAvgChart = new Chart(courseAvgCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($averageAgeByCourse->pluck('name')) !!},
                    datasets: [{
                        label: 'Avg Age',
                        data: {!! json_encode($averageAgeByCourse->pluck('avg_age')) !!},
                        backgroundColor: 'rgba(78, 115, 223, 0.8)',
                        borderColor: '#4e73df',
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
                            ticks: { stepSize: 5 }
                        },
                        x: { grid: { display: false } }
                    },
                    animation: { duration: 500 }
                }
            });
        }

        initCharts();

        function refreshReport(url = '{{ route("admin.reports.age.index") }}') {
            $('#loading_overlay').css('display', 'flex');
            
            $.ajax({
                url: url,
                method: 'GET',
                data: $('#age_report_filters').serialize(),
                success: function(response) {
                    $('#table_container').html(response.html);
                    
                    // Update Stats
                    $('#stat_total').text(response.stats.total);
                    $('#stat_missing').text(response.stats.missing);
                    
                    // Overall Avg Age calculation
                    let totalAge = 0, count = 0;
                    response.stats.averageAgeByCourse.forEach(c => {
                        totalAge += parseFloat(c.avg_age);
                        count++;
                    });
                    $('#stat_avg_overall').text(count > 0 ? (totalAge/count).toFixed(1) : '0');

                    // Gender Stats
                    let genderHtml = '';
                    response.stats.genderAgeDist.forEach(g => {
                        let colorClass = g.gender == 'Male' ? 'border-info' : 'border-warning';
                        let icon = g.gender == 'Male' ? 'mars' : 'venus';
                        genderHtml += `
                            <div class="stat-card-mini ${colorClass}">
                                <div class="stat-label">Avg. Age (${g.gender})</div>
                                <div class="stat-value">${parseFloat(g.avg_age).toFixed(1)}</div>
                                <i class="fas fa-${icon} stat-icon"></i>
                            </div>`;
                    });
                    $('#gender_stats_container').html(genderHtml);

                    // Update Charts
                    ageDistChart.data.labels = Object.keys(response.stats.ageBuckets);
                    ageDistChart.data.datasets[0].data = Object.values(response.stats.ageBuckets);
                    ageDistChart.update();

                    courseAvgChart.data.labels = response.stats.averageAgeByCourse.map(c => c.name);
                    courseAvgChart.data.datasets[0].data = response.stats.averageAgeByCourse.map(c => c.avg_age);
                    courseAvgChart.update();

                    $('#loading_overlay').hide();
                }
            });
        }

        // Filter Form Submission
        $('#age_report_filters').on('submit', function(e) {
            e.preventDefault();
            refreshReport();
        });

        // Auto-refresh filters on change
        $('#course_filter, #batch_filter, #age_group_filter, #gender_filter').on('change', function() {
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
                currentOrder = 'asc';
            }

            $('#sort_by').val(sortBy);
            $('#sort_order').val(currentOrder);
            
            refreshReport();
        });

        // Pagination AJAX
        $(document).on('click', '.ajax-pagination a', function(e) {
            e.preventDefault();
            refreshReport($(this).attr('href'));
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
    });
</script>
@endpush
