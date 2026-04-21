@extends('layouts.theme')

@section('title', 'Advanced Address Intelligence')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Advanced Address Intelligence</h1>
            <p class="text-muted small mb-0">Deep-dive analysis of geographical distribution and capture sources</p>
        </div>
        <div class="d-flex">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary shadow-sm mr-2">
                <i class="fas fa-print fa-sm mr-1"></i> Print PDF
            </button>
            <a href="{{ route('admin.reports.address.export', request()->all()) }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-file-excel fa-sm mr-1"></i> Export Data
            </a>
        </div>
    </div>

    <!-- Quick Stats Row (Dashboard) -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 border-0 glass-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Impact Reach</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-globe-asia fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Enrolled Base</div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Lead Pipeline</div>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Dominant Area</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['top_addresses']->first()->address ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marked-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section (Expanded for new requirements) -->
    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Advanced Filtering Suite</h6>
        </div>
        <div class="card-body bg-light border-bottom">
            <form action="{{ route('admin.reports.address.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold text-muted">Academic Program</label>
                        <select name="course_id" class="form-control select2">
                            <option value="">All Programs</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold text-muted">Capture Source</label>
                        <select name="source" class="form-control">
                            <option value="">All Sources</option>
                            @foreach($sources as $src)
                                <option value="{{ $src }}" {{ $source == $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="small font-weight-bold text-muted">Entry Type</label>
                        <select name="type" class="form-control">
                            <option value="">Mixed Records</option>
                            <option value="Student" {{ $type == 'Student' ? 'selected' : '' }}>Students Only</option>
                            <option value="Enquiry" {{ $type == 'Enquiry' ? 'selected' : '' }}>Enquiries Only</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="small font-weight-bold text-muted">Summarize By</label>
                        <select name="group_by" class="form-control text-primary font-weight-bold">
                            <option value="none" {{ $groupBy == 'none' ? 'selected' : '' }}>No Grouping</option>
                            <option value="address" {{ $groupBy == 'address' ? 'selected' : '' }}>Area/Village</option>
                            <option value="course" {{ $groupBy == 'course' ? 'selected' : '' }}>Course Name</option>
                            <option value="type" {{ $groupBy == 'type' ? 'selected' : '' }}>Record Type</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="small font-weight-bold text-muted">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block shadow-sm">
                            <i class="fas fa-search fa-sm mr-1"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="row pt-2 border-top mt-2">
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted">District</label>
                        <input type="text" name="district" class="form-control form-control-sm" placeholder="Enter District" value="{{ $district }}">
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted">Mandal</label>
                        <input type="text" name="mandal" class="form-control form-control-sm" placeholder="Enter Mandal" value="{{ $mandal }}">
                    </div>
                    <div class="col-md-6">
                        <label class="small font-weight-bold text-muted">Global Search (Name, Mobile, Area)</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search anything..." value="{{ $search }}">
                            <div class="input-group-append">
                                <a href="{{ route('admin.reports.address.index') }}" class="btn btn-outline-secondary btn-sm">Clear All</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-dark">Data Distribution - Page {{ $paginatedResults->currentPage() }}</h6>
            <span class="small text-muted">Showing {{ $paginatedResults->firstItem() }} to {{ $paginatedResults->lastItem() }} of {{ $stats['total'] }} records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-items-center mb-0">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">#</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name & Contact</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Classification</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Source</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Exact Location</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Academic Stream</th>
                            <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($groupBy !== 'none')
                            @php $count = $paginatedResults->firstItem(); @endphp
                            @forelse($results as $group => $items)
                                <tr class="group-header bg-light" style="cursor: pointer;" onclick="toggleGroup('grp-{{ $loop->index }}')">
                                    <td colspan="7" class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-chevron-down mr-2 transition-icon" id="icon-grp-{{ $loop->index }}"></i>
                                            <span class="h6 mb-0 font-weight-bold text-primary">{{ $group ?: 'N/A' }}</span>
                                            <span class="badge badge-pill badge-primary-soft ml-3 px-3 border border-primary text-primary">{{ count($items) }} on this page</span>
                                        </div>
                                    </td>
                                </tr>
                                @foreach($items as $item)
                                    <tr class="grp-{{ $parentLoop = $loop->parent->index }}">
                                        <td class="px-4 small">{{ $count++ }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="font-weight-bold text-dark">{{ $item->name }}</span>
                                                <span class="text-xs text-muted"><i class="fas fa-phone-alt mr-1"></i> {{ $item->phone }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-pill {{ $item->entity_type == 'Student' ? 'bg-success-soft text-success' : 'bg-info-soft text-info' }} px-3">
                                                <i class="fas {{ $item->entity_type == 'Student' ? 'fa-user-graduate' : 'fa-user-edit' }} mr-1"></i>
                                                {{ $item->entity_type }}
                                            </span>
                                        </td>
                                        <td class="small text-muted">{{ $item->source ?: 'Direct' }}</td>
                                        <td>
                                            <div class="small">
                                                <i class="fas fa-map-marker-alt text-danger opacity-5 mr-1"></i>
                                                {{ $item->address ?: 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="small font-weight-bold">{{ $item->course_name ?: 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-light border rounded-pill px-3 py-1">{{ ucfirst($item->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="7" class="text-center py-5">No records found</td></tr>
                            @endforelse
                        @else
                            @php $count = $paginatedResults->firstItem(); @endphp
                            @forelse($paginatedResults as $item)
                                <tr>
                                    <td class="px-4 small">{{ $count++ }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="font-weight-bold text-dark">{{ $item->name }}</span>
                                            <span class="text-xs text-muted"><i class="fas fa-phone-alt mr-1"></i> {{ $item->phone }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-pill {{ $item->entity_type == 'Student' ? 'bg-success-soft text-success' : 'bg-info-soft text-info' }} px-3">
                                            <i class="fas {{ $item->entity_type == 'Student' ? 'fa-user-graduate' : 'fa-user-edit' }} mr-1"></i>
                                            {{ $item->entity_type }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $item->source ?: 'Direct' }}</td>
                                    <td>
                                        <div class="small">
                                            <i class="fas fa-map-marker-alt text-danger opacity-5 mr-1"></i>
                                            {{ $item->address ?: 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="small font-weight-bold">{{ $item->course_name ?: 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-light border rounded-pill px-3 py-1">{{ ucfirst($item->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-5">No records found</td></tr>
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
    .glass-card { background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(255, 255, 255, 0.2); }
    .bg-success-soft { background-color: #e6fffa; }
    .bg-info-soft { background-color: #e6f6ff; }
    .badge-primary-soft { background-color: #f0f4ff; }
    .text-xxs { font-size: 0.65rem; }
    .transition-icon { transition: transform 0.2s ease; }
    .rotate-180 { transform: rotate(-180deg); }
    
    .pagination .page-item.active .page-link {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    @media print {
        .d-print-none, .btn, .card-header, .card-footer, form, .pagination { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .container-fluid { padding: 0 !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    function toggleGroup(groupId) {
        document.querySelectorAll('.' + groupId).forEach(el => el.classList.toggle('d-none'));
        document.getElementById('icon-' + groupId).classList.toggle('rotate-180');
    }
</script>
@endpush
@endsection
