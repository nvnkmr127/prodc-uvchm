@extends('layouts.theme')

@section('title', 'Address Insights Report')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Address Insights & Analytics</h1>
            <p class="text-muted small mb-0">Unified geographical distribution of students and leads</p>
        </div>
        <div class="d-flex">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary shadow-sm mr-2">
                <i class="fas fa-print fa-sm mr-1"></i> Print PDF
            </button>
            <button id="exportExcel" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-file-excel fa-sm mr-1"></i> Export Data
            </button>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 border-0 glass-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Interactions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 border-0 glass-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Enrolled Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['students']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 border-0 glass-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Enquiries</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['enquiries']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tag fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 border-0 glass-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Top Coverage Area</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['top_addresses']->first()->address ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Analytics Row -->
    <div class="row mb-4 d-print-none">
        <div class="col-lg-8">
            <div class="card shadow border-0 mb-4 h-100">
                <div class="card-header py-3 bg-white d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Top 5 Catchment Areas</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="areaChart" style="height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow border-0 mb-4 h-100">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Capture Source Ratio</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="sourcePieChart" style="height: 200px;"></canvas>
                    </div>
                    <div class="mt-4 text-center small font-weight-bold">
                        <span class="mr-2"><i class="fas fa-circle text-success"></i> Students</span>
                        <span class="mr-2"><i class="fas fa-circle text-info"></i> Enquiries</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Data Refinement</h6>
            <button class="btn btn-sm btn-link text-gray-500" type="button" data-toggle="collapse" data-target="#filterBody">
                <i class="fas fa-sliders-h mr-1"></i> Toggle Filters
            </button>
        </div>
        <div id="filterBody" class="collapse show">
            <div class="card-body bg-light">
                <form action="{{ route('admin.reports.address.index') }}" method="GET" class="row">
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold">Target Course</label>
                        <select name="course_id" class="form-control select2 custom-select-sm">
                            <option value="">All Academic Programs</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="small font-weight-bold">Entry Type</label>
                        <select name="type" class="form-control custom-select-sm">
                            <option value="">Mixed Records</option>
                            <option value="Student" {{ $type == 'Student' ? 'selected' : '' }}>Enrolled Students</option>
                            <option value="Enquiry" {{ $type == 'Enquiry' ? 'selected' : '' }}>Leads / Enquiries</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="small font-weight-bold">Summarize By</label>
                        <select name="group_by" class="form-control custom-select-sm">
                            <option value="none" {{ $groupBy == 'none' ? 'selected' : '' }}>Independent Records</option>
                            <option value="address" {{ $groupBy == 'address' ? 'selected' : '' }}>Geographical Area</option>
                            <option value="course" {{ $groupBy == 'course' ? 'selected' : '' }}>Course Batch</option>
                            <option value="type" {{ $groupBy == 'type' ? 'selected' : '' }}>Record Type</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold">Keyword Search</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Area, name, phone..." value="{{ $search }}">
                        </div>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm btn-block shadow-sm">
                            <i class="fas fa-filter fa-sm mr-1"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4 border-0 card-results">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-items-center mb-0" id="addressTable">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">#</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Identification</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Classification</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Contact Terminal</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Primary Location</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Academic Stream</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($groupBy !== 'none')
                            @php $count = 1; @endphp
                            @forelse($results as $group => $items)
                                <tr class="group-header bg-soft-primary" style="cursor: pointer;" onclick="toggleGroup('grp-{{ $loop->index }}')">
                                    <td colspan="7" class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-chevron-right mr-2 transition-icon" id="icon-grp-{{ $loop->index }}"></i>
                                            <span class="h6 mb-0 font-weight-bold text-primary">{{ $group ?: 'Not Specified' }}</span>
                                            <span class="badge badge-pill badge-primary ml-3 px-3">{{ count($items) }} Records</span>
                                        </div>
                                    </td>
                                </tr>
                                @foreach($items as $item)
                                    <tr class="grp-{{ $parentLoop = $loop->parent->index }} d-none">
                                        <td class="px-4 border-0 small">{{ $count++ }}</td>
                                        <td class="border-0 font-weight-bold text-dark">{{ $item->name }}</td>
                                        <td class="border-0">
                                            <span class="badge badge-dot mr-4">
                                                <i class="bg-{{ $item->entity_type == 'Student' ? 'success' : 'info' }}"></i>
                                                <span class="status">{{ $item->entity_type }}</span>
                                            </span>
                                        </td>
                                        <td class="border-0 small">{{ $item->phone }}</td>
                                        <td class="border-0 small"><i class="fas fa-map-marker-alt text-muted mr-1"></i> {{ $item->address ?: 'N/A' }}</td>
                                        <td class="border-0 small">{{ $item->course_name ?: 'Global' }}</td>
                                        <td class="border-0">
                                            <span class="badge badge-light border rounded-pill px-3">{{ ucfirst($item->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No geographical data matches your criteria</td>
                                </tr>
                            @endforelse
                        @else
                            @forelse($results as $index => $item)
                                <tr>
                                    <td class="px-4 small">{{ $index + 1 }}</td>
                                    <td class="font-weight-bold text-dark">{{ $item->name }}</td>
                                    <td>
                                        <span class="badge badge-dot mr-4">
                                            <i class="bg-{{ $item->entity_type == 'Student' ? 'success' : 'info' }}"></i>
                                            <span class="status">{{ $item->entity_type }}</span>
                                        </span>
                                    </td>
                                    <td class="small">{{ $item->phone }}</td>
                                    <td class="small"><i class="fas fa-map-marker-alt text-muted mr-1"></i> {{ $item->address ?: 'N/A' }}</td>
                                    <td class="small">{{ $item->course_name ?: 'Global' }}</td>
                                    <td>
                                        <span class="badge badge-light border rounded-pill px-3">{{ ucfirst($item->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No geographical data matches your criteria</td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
    .bg-soft-primary { background: #f0f7ff !important; }
    .badge-dot i { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .text-xxs { font-size: 0.65rem; }
    .transition-icon { transition: transform 0.3s ease; }
    .rotate-90 { transform: rotate(90deg); }
    .card-results { border-top: 3px solid #4e73df; }
    
    @media print {
        .d-print-none, .btn, .collapse, .navbar, .sidebar, form { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .container-fluid { padding: 0 !important; }
        .table tr.d-none { display: table-row !important; }
        .group-header i { display: none; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function toggleGroup(groupId) {
        document.querySelectorAll('.' + groupId).forEach(el => el.classList.toggle('d-none'));
        document.getElementById('icon-' + groupId).classList.toggle('rotate-90');
    }

    // Area Distribution Chart
    const areaCtx = document.getElementById('areaChart').getContext('2d');
    new Chart(areaCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($stats['top_addresses']->pluck('address')) !!},
            datasets: [{
                label: 'Reach Count',
                data: {!! json_encode($stats['top_addresses']->pluck('count')) !!},
                backgroundColor: '#4e73df',
                borderRadius: 5
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });

    // Score Ratio Chart
    const sourceCtx = document.getElementById('sourcePieChart').getContext('2d');
    new Chart(sourceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Enquiries'],
            datasets: [{
                data: [{{ $stats['students'] }}, {{ $stats['enquiries'] }}],
                backgroundColor: ['#1cc88a', '#36b9cc'],
                borderWidth: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: { legend: { display: false } }
        }
    });
</script>
@endpush
@endsection
