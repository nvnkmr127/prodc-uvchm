@extends('layouts.theme')

@section('title', 'Advanced Address Intelligence Hub')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Advanced Address Intelligence Hub</h1>
            <p class="text-muted small mb-0">Interactive geographical visualization & source analytics</p>
        </div>
        <div class="d-flex">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary shadow-sm mr-2">
                <i class="fas fa-print fa-sm mr-1"></i> Print PDF
            </button>
            <a href="{{ route('admin.reports.address.export', request()->all()) }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-file-excel fa-sm mr-1"></i> Export Dataset
            </a>
        </div>
    </div>

    <!-- Analytical Charts Row -->
    <div class="row mb-4 d-print-none">
        <div class="col-lg-7">
            <div class="card shadow border-0 mb-4 h-100">
                <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Regional Catchment (Top 7)</h6>
                    <span class="badge badge-primary-soft text-primary">Based on {{ $stats['total'] }} records</span>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:280px;">
                        <canvas id="regionalBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow border-0 mb-4 h-100">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Academic Distribution Share</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:220px;">
                        <canvas id="courseDoughnutChart"></canvas>
                    </div>
                    <div id="courseLegend" class="mt-4 text-center small font-weight-bold row no-gutters"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Summary Row -->
    <div class="row mb-4">
        @php
            $statCards = [
                ['title' => 'Global Interactions', 'value' => $stats['total'], 'icon' => 'fa-users', 'color' => 'primary'],
                ['title' => 'Enrolled Base', 'value' => $stats['students'], 'icon' => 'fa-graduation-cap', 'color' => 'success'],
                ['title' => 'Lead Pipeline', 'value' => $stats['enquiries'], 'icon' => 'fa-funnel-dollar', 'color' => 'info'],
                ['title' => 'Primary Area', 'value' => $stats['top_addresses']->first()->address ?? 'N/A', 'icon' => 'fa-place-of-worship', 'color' => 'warning'],
            ];
        @endphp
        @foreach($statCards as $card)
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-{{ $card['color'] }} shadow-sm h-100 py-2 border-0 stats-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xxs font-weight-bold text-{{ $card['color'] }} text-uppercase mb-1">{{ $card['title'] }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas {{ $card['icon'] }} fa-2x text-gray-200"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Search & Filters Toolset -->
    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 bg-white border-bottom-0">
            <h6 class="m-0 font-weight-bold text-dark">Refinement Terminal</h6>
        </div>
        <div class="card-body bg-light border-top border-bottom">
            <form action="{{ route('admin.reports.address.index') }}" method="GET" id="filterForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="small text-muted font-weight-bold">Academic Stream</label>
                        <select name="course_id" class="form-control select2 custom-select-sm" onchange="this.form.submit()">
                            <option value="">All Programs</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ ($courseId ?? '') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted font-weight-bold">Lead Source</label>
                        <select name="source" class="form-control custom-select-sm" onchange="this.form.submit()">
                            <option value="">All Discovery Sources</option>
                            @foreach($sources as $src)
                                <option value="{{ $src }}" {{ ($source ?? '') == $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted font-weight-bold">Record Pivot</label>
                        <select name="group_by" class="form-control custom-select-sm font-weight-bold text-primary" onchange="this.form.submit()">
                            <option value="none" {{ ($groupBy ?? '') == 'none' ? 'selected' : '' }}>Independent View</option>
                            <option value="address" {{ ($groupBy ?? '') == 'address' ? 'selected' : '' }}>Group by Area</option>
                            <option value="course" {{ ($groupBy ?? '') == 'course' ? 'selected' : '' }}>Group by Course</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted font-weight-bold">Show Records</label>
                        <select name="per_page" class="form-control custom-select-sm" onchange="this.form.submit()">
                            @foreach([10, 25, 50, 100, 200] as $size)
                                <option value="{{ $size }}" {{ ($perPage ?? 25) == $size ? 'selected' : '' }}>{{ $size }} per page</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm btn-block shadow-sm">Apply Filters</button>
                    </div>
                </div>
                <div class="row small-filter-row">
                    <div class="col-md-2">
                        <input type="text" name="district" class="form-control form-control-sm" placeholder="Filter District..." value="{{ $district ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="mandal" class="form-control form-control-sm" placeholder="Filter Mandal..." value="{{ $mandal ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Scan names, mobiles, or specific address details..." value="{{ $search ?? '' }}">
                            <div class="input-group-append">
                                <a href="{{ route('admin.reports.address.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Enhanced Data Table -->
    <div class="card shadow mb-4 border-0 results-container">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <span class="small font-weight-bold text-muted">Page {{ $paginatedResults->currentPage() }} | Record {{ $paginatedResults->firstItem() }} to {{ $paginatedResults->lastItem() }}</span>
            <div class="status-indicator d-flex small font-weight-bold">
                <span class="mr-3"><i class="fas fa-circle text-success mr-1"></i> Students</span>
                <span><i class="fas fa-circle text-info mr-1"></i> Enquiries</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-items-center mb-0">
                    <thead class="bg-gray-100">
                        @php
                            function sortLink($col, $label, $currentSort, $currentDir) {
                                $dir = ($currentSort == $col && $currentDir == 'asc') ? 'desc' : 'asc';
                                $icon = $currentSort == $col ? ($currentDir == 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
                                $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_dir' => $dir]);
                                return "<a href=\"{$url}\" class=\"text-secondary d-flex justify-content-between align-items-center\">
                                            <span>{$label}</span>
                                            <i class=\"fas {$icon} ml-1\" style=\"opacity: 0.5\"></i>
                                        </a>";
                            }
                        @endphp
                        <tr>
                            <th class="px-4 py-3 text-uppercase text-xxs font-weight-bolder opacity-7">#</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">{!! sortLink('name', 'Identification', $sortBy, $sortDir) !!}</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">Class</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">{!! sortLink('phone', 'Contact', $sortBy, $sortDir) !!}</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">{!! sortLink('address', 'Location', $sortBy, $sortDir) !!}</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">{!! sortLink('course_name', 'Course', $sortBy, $sortDir) !!}</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">{!! sortLink('status', 'Outcome', $sortBy, $sortDir) !!}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($groupBy !== 'none')
                            @php $count = $paginatedResults->firstItem(); @endphp
                            @forelse($results as $group => $items)
                                <tr class="group-strip bg-white border-bottom" style="cursor: pointer;" onclick="toggleGroup('grp-{{ $loop->index }}')">
                                    <td colspan="7" class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-dot-circle text-primary mr-2"></i>
                                            <span class="h6 mb-0 font-weight-bold text-dark">{{ $group ?: 'N/A' }}</span>
                                            <span class="badge badge-light border rounded-pill ml-3 px-3">{{ count($items) }} Batch Records</span>
                                            <i class="fas fa-chevron-down ml-auto transition-icon" id="icon-grp-{{ $loop->index }}"></i>
                                        </div>
                                    </td>
                                </tr>
                                @foreach($items as $item)
                                    <tr class="grp-{{ $parentLoop = $loop->parent->index }} d-none record-row">
                                        <td class="px-4 small text-muted">{{ $count++ }}</td>
                                        <td class="font-weight-bold text-darker">{{ $item->name }}</td>
                                        <td>
                                            <span class="badge badge-dot-lg bg-{{ $item->entity_type == 'Student' ? 'success' : 'info' }}"></span>
                                            <span class="small text-muted">{{ $item->entity_type }}</span>
                                        </td>
                                        <td class="small">{{ $item->phone }}</td>
                                        <td class="small"><i class="fas fa-map-pin text-danger opacity-5 mr-1"></i> {{ $item->address ?: 'N/A' }}</td>
                                        <td class="small font-weight-bold">{{ $item->course_name ?: 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-outline rounded-pill px-3 py-1 small">{{ ucfirst($item->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="7" class="text-center py-5">No geographical matches found</td></tr>
                            @endforelse
                        @else
                            @php $count = $paginatedResults->firstItem(); @endphp
                            @forelse($paginatedResults as $item)
                                <tr>
                                    <td class="px-4 small text-muted">{{ $count++ }}</td>
                                    <td class="font-weight-bold text-darker">{{ $item->name }}</td>
                                    <td>
                                        <span class="badge badge-dot-lg bg-{{ $item->entity_type == 'Student' ? 'success' : 'info' }}"></span>
                                        <span class="small text-muted">{{ $item->entity_type }}</span>
                                    </td>
                                    <td class="small">{{ $item->phone }}</td>
                                    <td class="small"><i class="fas fa-map-pin text-danger opacity-5 mr-1"></i> {{ $item->address ?: 'N/A' }}</td>
                                    <td class="small font-weight-bold">{{ $item->course_name ?: 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-outline rounded-pill px-3 py-1 small">{{ ucfirst($item->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-5">No geographical matches found</td></tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-4 d-flex justify-content-center">
            {{ $paginatedResults->links() }}
        </div>
    </div>
</div>

@push('styles')
<style>
    .stats-card { transition: transform 0.2s; cursor: default; }
    .stats-card:hover { transform: translateY(-3px); }
    .text-darker { color: #2e384d; }
    .badge-dot-lg { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .badge-outline { border: 1px solid #e3e6f0; color: #858796; }
    .transition-icon { transition: transform 0.3s; }
    .rotate-180 { transform: rotate(-180deg); }
    .bg-primary-soft { background-color: #f0f7ff; }
    .record-row:hover { background-color: #fbfcfe; }
    .text-xxs { font-size: 0.6rem; }
    
    @media print {
        .d-print-none, .card-footer, .card-header button, form { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table tr.d-none { display: table-row !important; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function toggleGroup(groupId) {
        document.querySelectorAll('.' + groupId).forEach(el => el.classList.toggle('d-none'));
        document.getElementById('icon-' + groupId).classList.toggle('rotate-180');
    }

    const chartColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'];

    // 1. Regional Bar Chart (3D Effect with shadow)
    new Chart(document.getElementById('regionalBarChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($stats['top_addresses']->pluck('address')) !!},
            datasets: [{
                label: 'Reach Intensity',
                data: {!! json_encode($stats['top_addresses']->pluck('count')) !!},
                backgroundColor: chartColors[0] + 'CC',
                borderColor: chartColors[0],
                borderWidth: 1,
                borderRadius: 8,
                hoverBackgroundColor: chartColors[0]
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: { 
                y: { beginAtZero: true, grid: { borderDash: [2], color: '#f0f0f0' } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 2. Course Doughnut Chart
    const courseLabels = {!! json_encode($stats['course_dist']->pluck('course_name')) !!};
    const courseData = {!! json_encode($stats['course_dist']->pluck('count')) !!};

    const courseChart = new Chart(document.getElementById('courseDoughnutChart'), {
        type: 'doughnut',
        data: {
            labels: courseLabels,
            datasets: [{
                data: courseData,
                backgroundColor: chartColors,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });

    // Generate Custom Legend
    const legendContainer = document.getElementById('courseLegend');
    courseLabels.forEach((label, i) => {
        const div = document.createElement('div');
        div.className = 'col-6 col-md-4 mb-2 d-flex align-items-center';
        div.innerHTML = `<i class="fas fa-circle mr-1" style="color: ${chartColors[i % chartColors.length]}"></i> ${label} (${courseData[i]})`;
        legendContainer.appendChild(div);
    });
</script>
@endpush
@endsection
