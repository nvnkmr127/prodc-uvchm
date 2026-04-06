@extends('layouts.theme')
@section('title', 'Enquiry Hub')

@push('styles')
    <style>
        /* --- Modern CRM Design System --- */
        :root {
            --crm-primary: #4e73df;
            --crm-secondary: #858796;
        }

        /* --- Stats Grid System --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card-mini {
            background: rgba(255, 255, 255, 0.85); /* Semi-transparent for glass effect */
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 1.25rem;
            border-radius: 1rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05); /* Softer, deeper shadow */
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-left: 0.35rem solid #e3e6f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .stat-card-mini:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.95);
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }

        /* Status Colors - Glass Accents */
        .status-Next-Year-border { border-left-color: #8b5cf6 !important; background: rgba(139, 92, 246, 0.03); }
        .status-Entrance-Exam-border { border-left-color: #f43f5e !important; background: rgba(244, 63, 94, 0.03); }
        .status-Admitted-border { border-left-color: #10b981 !important; background: rgba(16, 185, 129, 0.03); }
        .status-Interested-border { border-left-color: #f59e0b !important; background: rgba(245, 158, 11, 0.03); }
        .status-Follow-up-border { border-left-color: #f97316 !important; background: rgba(249, 115, 22, 0.03); }
        .status-Contacted-border { border-left-color: #0ea5e9 !important; background: rgba(14, 165, 233, 0.03); }
        .status-Not-Interested-border { border-left-color: #ef4444 !important; background: rgba(239, 68, 68, 0.03); }
        .status-Total-border { border-left-color: #4f46e5 !important; background: rgba(79, 70, 229, 0.03); }

        .stat-card-mini.active {
            border: 2px solid var(--crm-primary);
            background: #fff;
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(78, 115, 223, 0.15);
        }

        /* --- Live Search Dropdown --- */
        .search-box-container {
            position: relative;
        }

        #liveSearchResults {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            z-index: 1000;
            max-height: 350px;
            overflow-y: auto;
            display: none;
        }

        .search-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fc;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background 0.1s;
        }

        .search-item:hover {
            background: #f0f2f5;
        }

        .search-item:last-child {
            border-bottom: none;
        }

        .search-avatar {
            width: 35px;
            height: 35px;
            background: #4e73df;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            margin-right: 12px;
        }

        /* --- Sortable Headers --- */
        .sort-link {
            color: #858796;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .sort-link:hover {
            color: #4e73df;
            text-decoration: none;
        }

        .sort-link.active {
            color: #4e73df;
        }

        /* --- Responsive Tweaks --- */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* --- Other Styles from previous file --- */
        .table-custom {
            margin: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom thead th {
            border: none;
            background: #f8f9fc;
            padding: 1rem;
            border-bottom: 2px solid #e3e6f0;
        }

        .table-custom tbody tr {
            transition: background-color 0.2s ease;
        }

        .table-custom tbody tr:hover {
            background-color: rgba(79, 70, 229, 0.03);
        }

        .table-custom tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            border-top: 1px solid #f0f2f5;
        }

        .inline-edit {
            background: transparent;
            border: 1px solid transparent;
            padding: 0.4rem;
            border-radius: 0.35rem;
            width: 100%;
            cursor: pointer;
        }

        .inline-edit:hover,
        .inline-edit:focus {
            background: white;
            border-color: #d1d3e2;
        }

        .student-trigger {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .student-avatar-small {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: #4e73df;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-weight: bold;
        }

        /* Badges */
        .badge-pill-custom {
            padding: 0.4em 1em;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.7rem;
        }

        .status-new { background-color: #e0f2fe; color: #0369a1; }
        .status-contacted { background-color: #f0fdf4; color: #15803d; }
        .status-interested { background-color: #fffbeb; color: #b45309; }
        .status-follow-up { background-color: #fff7ed; color: #c2410c; }
        .status-admitted { background-color: #f0fdfa; color: #0d9488; }
        .status-interested-next-year { background-color: #f5f3ff; color: #6d28d9; }
        .status-next-entrance-exam { background-color: #fff1f2; color: #e11d48; }
        .status-not-interested { background-color: #fef2f2; color: #b91c1c; }

        /* Urgent Row */
        .row-urgent td:first-child {
            border-left: 4px solid #e74a3b;
        }

        /* Due today (follow-up is today, not yet past) */
        .row-due-today td:first-child {
            border-left: 4px solid #fd7e14;
        }

        .text-urgent {
            color: #e74a3b !important;
            font-weight: 800;
        }

        .text-due-today {
            color: #fd7e14 !important;
            font-weight: 800;
        }

        /* Source Badges */
        .source-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15em 0.55em;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .source-website      { background: #e3f2fd; color: #1565c0; }
        .source-social-media { background: #f3e5f5; color: #6a1b9a; }
        .source-agent        { background: #e8f5e9; color: #2e7d32; }
        .source-referrals    { background: #fff8e1; color: #e65100; }
        .source-student-refer{ background: #fff3cd; color: #856404; }
        .source-walk-in      { background: #fce4ec; color: #880e4f; }
        .source-bulk-import  { background: #e0f2f1; color: #004d40; }
        .source-other        { background: #f5f5f5; color: #424242; }

        /* Bulk Action Bar Styling */
        #bulkActionBar {
            background: #fdf2f2;
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px dashed #e74a3b;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table-responsive {
            max-height: 70vh; /* Optional: Vertical scroll */
            overflow-y: auto;
            overflow-x: auto;
            scrollbar-width: thin;
        }

        /* Select2 Custom Styling to match theme */
        .select2-container--default .select2-selection--multiple {
            background-color: #f8f9fc !important;
            border: none !important;
            border-radius: 0.35rem !important;
            min-height: 38px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #4e73df !important;
            border: none !important;
            color: white !important;
            border-radius: 4px !important;
            padding: 2px 8px !important;
            margin-top: 6px !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white !important;
            margin-right: 5px !important;
        }
        .select2-container {
            width: 100% !important;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

@endpush

@section('content')
    <div class="container-fluid">
        <!-- Duplicate Enquiries Modal (Pop up on import) -->
        @if(session('import_duplicates'))
            <div class="modal fade" id="duplicatesModal" tabindex="-1" role="dialog" aria-labelledby="duplicatesModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg shadow-lg" role="document">
                    <div class="modal-content border-0">
                        <div class="modal-header bg-warning text-white py-3">
                            <h5 class="modal-title font-weight-bold" id="duplicatesModalLabel">
                                <i class="fas fa-exclamation-triangle mr-2"></i> Duplicate Records Found
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="alert alert-info border-0 rounded-0 mb-0">
                                <i class="fas fa-info-circle mr-2"></i> The following <strong>{{ count(session('import_duplicates')) }}</strong> records were skipped because they already exist in the system.
                            </div>
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-top-0">Name from CSV</th>
                                            <th class="border-top-0">Phone Number</th>
                                            <th class="border-top-0">System Match</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('import_duplicates') as $dup)
                                            <tr>
                                                <td class="align-middle font-weight-bold">{{ $dup['name'] }}</td>
                                                <td class="align-middle"><code>{{ $dup['phone'] }}</code></td>
                                                <td class="align-middle small text-muted">{{ $dup['reason'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary px-4" onclick="window.print()">
                                <i class="fas fa-print mr-1"></i> Print List
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @push('scripts')
                <script>
                    $(document).ready(function() {
                        $('#duplicatesModal').modal('show');
                    });
                </script>
            @endpush
        @endif

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                @if($isFacebookView ?? false)
                    <i class="fab fa-facebook text-primary mr-2"></i> Facebook Leads
                @else
                    Enquiry Hub
                @endif
            </h1>
            <button type="button" class="btn btn-primary shadow-sm font-weight-bold" data-toggle="modal"
                data-target="#addEnquiryModal">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> New Enquiry
            </button>
        </div>

        <div class="stats-grid">
            @php 
                $currentFilters = request()->except(['status', 'page', 'test_attended']); 
            @endphp
            <div class="stat-card-mini status-Total-border {{ !request('status') && !request('test_attended') ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', $currentFilters) }}" class="text-decoration-none h-100 d-block stat-filter-link" data-status="" data-test="">
                    <div class="stat-label text-primary">Total Enquiries</div>
                    <div class="stat-value" id="count-Total">{{ $counts['Total'] ?? 0 }}</div>
                </a>
            </div>
            @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Interested Next Year', 'Admitted', 'Next Entrance Exam', 'Not Interested'] as $status)
                @php 
                    $statKey = $status == 'Interested Next Year' ? 'Next Year' : ($status == 'Next Entrance Exam' ? 'Entrance Exam' : $status);
                    $cssKey = str_replace(' ', '-', $statKey);
                    $isActive = request('status') == $status;
                @endphp
                <div class="stat-card-mini status-{{ $cssKey }}-border {{ $isActive ? 'active' : '' }}">
                    <a href="{{ route('admin.enquiries.index', array_merge($currentFilters, ['status' => $status])) }}" 
                       class="text-decoration-none h-100 d-block stat-filter-link" data-status="{{ $status }}">
                        <div class="stat-label text-gray-700">{{ $status == 'Next Entrance Exam' ? 'Entrance Exam' : ($status == 'Interested Next Year' ? 'Next Year' : $status) }}</div>
                        <div class="stat-value" id="count-{{ $cssKey }}">{{ $counts[$statKey == 'Entrance Exam' ? 'Next Entrance Exam' : $statKey] ?? 0 }}</div>
                    </a>
                </div>
            @endforeach
            <div class="stat-card-mini status-Contacted-border {{ request('test_attended') === '1' ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', array_merge($currentFilters, ['test_attended' => 1])) }}" 
                   class="text-decoration-none h-100 d-block stat-filter-link" data-test="1">
                    <div class="stat-label text-primary">Test Attended</div>
                    <div class="stat-value" id="count-TestAttended">{{ $counts['Test Attended'] ?? 0 }}</div>
                </a>
            </div>
        </div>

        <div class="card shadow mb-4 border-0" style="border-radius: 1rem;">
            <div class="card-body py-3">
                <form id="filterForm" onsubmit="event.preventDefault(); fetchEnquiries(1);">
                    <input type="hidden" name="sort" id="sortField" value="{{ request('sort', 'next_follow_up_date') }}">
                    <input type="hidden" name="direction" id="sortDirection" value="{{ request('direction', 'asc') }}">
                    <input type="hidden" name="per_page" id="perPageField" value="{{ request('per_page', 25) }}">
                    <div class="row">
                        <!-- Column 1: Discovery -->
                        <div class="col-lg-4 col-md-12 border-right">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="font-weight-bold text-primary mb-0 small text-uppercase">
                                    <i class="fas fa-search mr-2"></i> Discovery
                                </h6>
                                @if(request()->anyFilled(['search', 'assigned_to_user_id', 'course_id', 'status', 'start_date', 'end_date', 'test_attended']))
                                    <a href="javascript:void(0)" onclick="resetAllFilters()" class="small text-danger font-weight-bold">
                                        <i class="fas fa-times-circle mr-1"></i>Clear all
                                    </a>
                                @endif
                            </div>
                            <div class="mb-3 position-relative">
                                <div class="input-group bg-light rounded" style="padding: 2px;">
                                    <input type="text" class="form-control bg-light border-0 small"
                                        name="search" id="liveSearchInput" value="{{ request('search') }}"
                                        placeholder="Name, Phone, Village..." autocomplete="off">
                                    <div id="searchSpinner" class="position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%); display: none; z-index: 5;">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="small text-muted font-weight-bold">Counselor Assignment</label>
                                <select class="form-control select2-multiple filter-input"
                                    name="assigned_to_user_id[]" multiple data-placeholder="All Counselors">
                                    @foreach($counselors as $counselor)
                                        <option value="{{ $counselor->id }}" {{ in_array($counselor->id, (array) request('assigned_to_user_id')) ? 'selected' : '' }}>
                                            {{ $counselor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Column 2: Qualification -->
                        <div class="col-lg-4 col-md-12 border-right">
                            <h6 class="font-weight-bold text-info mb-3 small text-uppercase">
                                <i class="fas fa-filter mr-2"></i> Qualification
                            </h6>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="small text-muted font-weight-bold">Status</label>
                                    <select class="form-control select2-multiple filter-input"
                                        name="status[]" multiple data-placeholder="All Statuses">
                                        @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Admitted', 'Interested Next Year', 'Next Entrance Exam', 'Not Interested'] as $s)
                                            <option value="{{ $s }}" {{ in_array($s, (array) request('status')) ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted font-weight-bold">Source</label>
                                    <select class="form-control select2-multiple filter-input" name="source[]" multiple data-placeholder="All Sources">
                                        @foreach($sources as $value => $label)
                                            <option value="{{ $value }}" {{ in_array($value, (array) request('source')) ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="small text-muted font-weight-bold">Course Interest</label>
                                <select class="form-control select2-multiple filter-input" name="course_id[]" multiple data-placeholder="All Courses">
                                    @foreach($courses as $id => $name)
                                        <option value="{{ $id }}" {{ in_array($id, (array) request('course_id')) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mt-3">
                                <label class="small text-muted font-weight-bold">Entrance Test</label>
                                <select class="form-control filter-input" name="test_attended">
                                    <option value="">All</option>
                                    <option value="1" {{ request('test_attended') === '1' ? 'selected' : '' }}>Attended</option>
                                    <option value="0" {{ request('test_attended') === '0' ? 'selected' : '' }}>Not Attended</option>
                                </select>
                            </div>
                        </div>

                        <!-- Column 3: Timeline & Tools -->
                        <div class="col-lg-4 col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="font-weight-bold text-success mb-0 small text-uppercase">
                                    <i class="fas fa-calendar-alt mr-2"></i> Timeline
                                </h6>
                                <div class="btn-group">
                                    @hasanyrole('super-admin|Super-Admin')
                                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm font-weight-bold"
                                        data-toggle="modal" data-target="#importEnquiryModal" title="Import CSV">
                                        <i class="fas fa-file-import"></i>
                                    </button>
                                    @endhasanyrole
                                    <button type="button" onclick="exportFilteredResults()"
                                        class="btn btn-sm btn-outline-success shadow-sm font-weight-bold"
                                        title="Export CSV">
                                        <i class="fas fa-file-export"></i>
                                    </button>
                                    <a href="{{ route('admin.enquiries.index') }}"
                                        class="btn btn-sm btn-outline-secondary shadow-sm font-weight-bold"
                                        title="Reset Filters">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-8">
                                    <label class="small text-muted font-weight-bold d-block">Created Date Range</label>
                                    <div class="d-flex">
                                        <input type="date"
                                            class="form-control border-0 bg-light small font-weight-bold mr-1 filter-input"
                                            name="start_date" placeholder="Start Date" title="Start Date"
                                            value="{{ request('start_date') }}">
                                        <input type="date"
                                            class="form-control border-0 bg-light small font-weight-bold ml-1 filter-input"
                                            name="end_date" placeholder="End Date" title="End Date"
                                            value="{{ request('end_date') }}">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label class="small text-muted font-weight-bold">Records</label>
                                    <select class="form-control bg-light border-0 small filter-input" id="perPageSelectTop" 
                                        onchange="updatePerPage(this.value)">
                                        <option value="10" {{ $enquiries->perPage() == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ $enquiries->perPage() == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $enquiries->perPage() == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $enquiries->perPage() == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary btn-block shadow-sm font-weight-bold">
                                    <i class="fas fa-search mr-2"></i> Apply Filters
                                </button>
                            </div>

                            <!-- Bulk Actions Overlay -->
                            <div id="bulkActionBar" style="display:none; flex-direction: column; gap:10px; padding: 12px; background: #fff5f5; border-radius: 8px; border: 1px dashed #e74a3b; margin-top: 10px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small font-weight-bold text-danger"><i class="fas fa-layer-group mr-1"></i> Bulk Actions</span>
                                    <button type="button" class="btn btn-danger btn-sm rounded-circle" onclick="bulkDelete()" title="Delete Selected">
                                        <i class="fas fa-trash fa-xs"></i>
                                    </button>
                                </div>
                                <div class="input-group input-group-sm">
                                    <select class="custom-select" id="bulkAssignUser">
                                        <option value="">Assign To...</option>
                                        @foreach($counselors as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-success" onclick="bulkAssign()">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4 border-0" style="border-radius: 1rem; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="4%" class="pl-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAll">
                                        <label class="custom-control-label" for="selectAll"></label>
                                    </div>
                                </th>
                                <th width="20%">
                                    @php $s = 'student_name'; $isS = request('sort') == $s; @endphp
                                    <a href="javascript:void(0)" onclick="sortList('{{ $s }}')" class="sort-link {{ $isS ? 'active' : '' }}">
                                        Student Profile <i class="fas fa-sort{{ $isS ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th>
                                    @php $s = 'course_name'; $isS = request('sort') == $s; @endphp
                                    <a href="javascript:void(0)" onclick="sortList('{{ $s }}')" class="sort-link {{ $isS ? 'active' : '' }}">
                                        Course <i class="fas fa-sort{{ $isS ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th>
                                    @php $s = 'counselor_name'; $isS = request('sort') == $s; @endphp
                                    <a href="javascript:void(0)" onclick="sortList('{{ $s }}')" class="sort-link {{ $isS ? 'active' : '' }}">
                                        Counselor <i class="fas fa-sort{{ $isS ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th>
                                    @php $s = 'status'; $isS = request('sort') == $s; @endphp
                                    <a href="javascript:void(0)" onclick="sortList('{{ $s }}')" class="sort-link {{ $isS ? 'active' : '' }}">
                                        Status <i class="fas fa-sort{{ $isS ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th>
                                    @php $s = 'next_follow_up_date'; $isS = request('sort', $s) == $s; @endphp
                                    <a href="javascript:void(0)" onclick="sortList('{{ $s }}')" class="sort-link {{ $isS ? 'active' : '' }}">
                                        Next Date <i class="fas fa-sort{{ $isS ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th width="12%">
                                    @php $s = 'source'; $isS = request('sort') == $s; @endphp
                                    <a href="javascript:void(0)" onclick="sortList('{{ $s }}')" class="sort-link {{ $isS ? 'active' : '' }}">
                                        Source <i class="fas fa-sort{{ $isS ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th width="15%" class="text-right pr-4">Action</th>
                            </tr>
                        </thead>
                        <tbody id="enquiryTableBody">
                            @include('admin.enquiries._table_body')
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Container -->
                <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between" id="paginationWrapper">
                    <div class="d-flex align-items-center">
                        <span class="small text-muted mr-2">Show</span>
                        <select class="custom-select custom-select-sm" id="perPageSelectBottom" style="width: 70px;" onchange="updatePerPage(this.value)">
                            <option value="10" {{ $enquiries->perPage() == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ $enquiries->perPage() == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $enquiries->perPage() == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $enquiries->perPage() == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <span class="small text-muted ml-2">entries</span>
                    </div>
                    <div id="paginationContainer">
                        {{ $enquiries->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="enquiryDetailsModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static"
        data-keyboard="false" style="z-index: 1051;">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
                <div class="modal-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="modal-title font-weight-bold text-gray-800"><i
                            class="fas fa-user-circle mr-2 text-primary"></i>Enquiry Overview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" id="enquiryModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addEnquiryModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>New Enquiry</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addEnquiryForm" action="{{ route('admin.enquiries.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="small font-weight-bold text-gray-600">Student Name *</label>
                                <input type="text" class="form-control bg-light border-0" name="student_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="small font-weight-bold text-gray-600">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone_number" id="createPhoneInput" required
                                    onkeyup="checkMobileCreate(this.value)" autocomplete="off">
                                <div id="createPhoneFeedback" class="small font-weight-bold mt-1" style="display:none;">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="small font-weight-bold text-gray-600">Course Interest</label>
                                <select class="form-control bg-light border-0" name="course_id">
                                    <option value="">Select Course</option>
                                    @foreach($courses as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="small font-weight-bold text-gray-600">Source</label>
                                <select class="form-control bg-light border-0" name="source" id="sourceSelect">
                                    <option value="">-- Select Source --</option>
                                    @foreach($sources as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="referralWrapper" style="display: none;">
                                <label class="small font-weight-bold text-gray-600" id="referralLabel">Referral Name</label>
                                <input type="text" class="form-control bg-light border-0" name="referral_name"
                                    id="referralInput">
                            </div>
                            <!-- Manual Assignment Hidden for Auto-Assign -->

                            <div class="col-md-6 mb-3">
                                <label for="address" class="small font-weight-bold text-gray-600">Address / Village</label>
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" class="form-control bg-light border-0" id="address" name="address"
                                    value="{{ old('address') }}" placeholder="Enter address or village">
                            </div>

                            <!-- Entrance Test Fields -->
                            <div class="col-md-4 mb-3">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input type="checkbox" name="test_attended" value="1" class="custom-control-input" id="createTestAttended">
                                    <label class="custom-control-label font-weight-bold" for="createTestAttended">Attended Test</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="small font-weight-bold text-gray-600">Marks</label>
                                <input type="number" name="test_marks" class="form-control bg-light border-0" placeholder="Marks">
                            </div>
                            <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Total Fee Offered (₹)</label>
                                    <input type="number" name="agreed_fee" class="form-control border-primary" placeholder="Enter Total Fee">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Discount Offered (₹)</label>
                                    <input type="number" name="discount_offered" class="form-control" placeholder="Discount if any">
                                </div>
                            </div>
                        </div>

                            <!-- Uniform & Books -->
                            <div class="col-md-6 mb-3">
                                <div class="form-check custom-control custom-checkbox ml-2">
                                    <input type="checkbox" name="include_uniform" value="1" class="custom-control-input" id="createUniformCheck">
                                    <label class="custom-control-label font-weight-bold" for="createUniformCheck">Uniform Included</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check custom-control custom-checkbox ml-2">
                                    <input type="checkbox" name="include_books" value="1" class="custom-control-input" id="createBooksCheck">
                                    <label class="custom-control-label font-weight-bold" for="createBooksCheck">Books Included</label>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="small font-weight-bold text-gray-600">Notes</label>
                                <textarea class="form-control bg-light border-0" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary shadow-sm" id="saveEnquiryBtn">
                        <span id="saveEnquirySpinner" class="spinner-border spinner-border-sm mr-1" role="status" style="display:none;"></span>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importEnquiryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Import Data</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-4">
                    <form action="{{ route('admin.enquiries.import') }}" method="POST" enctype="multipart/form-data"
                        id="importForm">
                        @csrf
                        <div class="form-group">
                            <label>Assign To:</label>
                            <select class="form-control" name="assigned_to_user_id">
                                <option value="">Auto Assign</option>
                                @foreach($counselors as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Default Source (optional):</label>
                            <select class="form-control" name="default_source">
                                <option value="">Use source from sheet / fallback to Bulk Import</option>
                                @foreach($sources as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                                <option value="Bulk Import">Bulk Import</option>
                            </select>
                            <small class="form-text text-muted">If source column is empty in a row, this value will be used.</small>
                        </div>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" name="file" required>
                            <label class="custom-file-label">Choose CSV/Excel...</label>
                        </div>
                    </form>
                    <div class="text-center">
                        <a href="{{ route('admin.enquiries.import.sample') }}" class="small">Download Sample</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-block"
                        onclick="document.getElementById('importForm').submit()">Upload</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize Select2
            $('.select2-multiple').select2({
                theme: 'default',
                allowClear: true,
                closeOnSelect: false
            });

            // Checkbox logic for bulk actions
            $('#selectAll').on('change', function () {
                $('.enquiry-checkbox').prop('checked', this.checked);
                toggleBulkActions();
            });
            $(document).on('change', '.enquiry-checkbox', toggleBulkActions);

            // --- Add Enquiry Modal: AJAX submit (so duplicate-check 422 response surfaces) ---
            $('#saveEnquiryBtn').on('click', function () {
                const $form   = $('#addEnquiryForm');
                const $btn    = $(this);
                const $spin   = $('#saveEnquirySpinner');

                $btn.prop('disabled', true);
                $spin.show();

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        if (res.success && res.redirect) {
                            window.location.href = res.redirect;
                        } else {
                            // Shouldn't happen for a 200, but handle gracefully
                            $('#addEnquiryModal').modal('hide');
                            fetchEnquiries(1);
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spin.hide();
                        const data = xhr.responseJSON;
                        if (xhr.status === 422 && data && data.message) {
                            // Duplicate or validation error — show inline
                            const $feedback = $('#createPhoneFeedback');
                            $feedback.text(data.message).removeClass('text-success text-info').addClass('text-danger').show();
                            $('#createPhoneInput').addClass('is-invalid');
                        } else if (xhr.status === 422 && data && data.errors) {
                            // Laravel validation errors
                            const firstError = Object.values(data.errors)[0][0];
                            alert('Validation Error: ' + firstError);
                        } else {
                            alert('Unexpected error. Please try again.');
                        }
                    }
                });
            });
        });

        // --- AJAX Fetcher Implementation ---
        let fetchTimer;
        function fetchEnquiries(page = 1) {
            const $form = $('#filterForm');
            const data = $form.serialize() + '&page=' + page;
            const $container = $('#enquiryTableBody');
            const $pagination = $('#paginationContainer');
            const url = "{{ route('admin.enquiries.index') }}";
            
            console.log("[Enquiry-Debug] Starting Fetch...", { page, data: $form.serializeArray() });

            // Show loading state
            $container.css('opacity', '0.5');
            $('#searchSpinner').show();
            document.body.style.cursor = 'wait';

            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    console.log("[Enquiry-Debug] Search Success! Rows returned:", $(res.html).filter('tr').length);
                    $container.html(res.html).css('opacity', '1');
                    $pagination.html(res.pagination);
                    if (res.stats) updateStats(res.stats);
                    document.body.style.cursor = 'default';
                    $('#searchSpinner').hide();
                    
                    // Update URL without reload to preserve state
                    const newUrl = url + '?' + data;
                    window.history.pushState({ path: newUrl }, '', newUrl);
                },
                error: function (xhr, status, error) {
                    console.error("[Enquiry-Debug] Search Failed:", { status, error, response: xhr.responseText });
                    $container.css('opacity', '1');
                    $('#searchSpinner').hide();
                    document.body.style.cursor = 'default';
                    alert('Search failed. Check browser console for details.');
                }
            });
        }

        // resetAllFilters AJAX
        function resetAllFilters() {
            console.log("[Enquiry-Debug] Resetting all filters...");
            const form = $('#filterForm');
            form[0].reset();
            
            // Special handling for Select2
            $('.select2-multiple').val(null).trigger('change.select2');
            
            // Clear search field manually if needed
            $('#liveSearchInput').val('');
            
            // Fetch clean list
            fetchEnquiries(1);
        }

        // Live search with debounce (Switch to 'input' for better "X" button handling)
        $('#liveSearchInput').on('input', function() {
            clearTimeout(fetchTimer);
            fetchTimer = setTimeout(() => {
                fetchEnquiries(1);
            }, 400); // Slightly faster debounce
        });

        // Filter triggers
        $(document).on('change', '.filter-input', function() {
            fetchEnquiries(1);
        });

        // Handle pagination clicks
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            // Use URLSearchParams to safely extract the page number from the href
            try {
                const pageUrl = new URL($(this).attr('href'), window.location.origin);
                const page = pageUrl.searchParams.get('page') || 1;
                fetchEnquiries(page);
            } catch (err) {
                // Fallback for relative URLs without origin
                const match = $(this).attr('href').match(/[?&]page=(\d+)/);
                fetchEnquiries(match ? match[1] : 1);
            }
        });

        // Handle stats card clicks
        $(document).on('click', '.stat-filter-link', function(e) {
            e.preventDefault();
            const status = $(this).data('status');
            const test = $(this).data('test');

            // Reset other filters or preserve? Let's preserve current form but override these two
            // For simplicity, let's just clear specific select multiples and set the status if it exists
            if (status !== undefined) {
                // Handle multiple select logic for AJAX fetch
                // Clear existing and set new if it's a single status click
                $('select[name="status[]"]').val(status ? [status] : []).trigger('change.select2');
            }
            if (test !== undefined) {
                $('select[name="test_attended"]').val(test).trigger('change');
            }
            
            // Set active class visually
            $('.stat-card-mini').removeClass('active');
            $(this).parent().addClass('active');

            fetchEnquiries(1);
        });

        // Handle browser back/forward
        window.onpopstate = function(event) {
            if (event.state && event.state.path) {
                window.location.reload(); // Simple reload on back to keep integrity
            }
        };

        function sortList(field) {
            let currentField = $('#sortField').val();
            let currentDirection = $('#sortDirection').val();
            let newDirection = 'asc';

            if (currentField === field) {
                newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            }

            $('#sortField').val(field);
            $('#sortDirection').val(newDirection);
            fetchEnquiries(1);
        }

        function updatePerPage(value) {
            $('#perPageField').val(value);
            fetchEnquiries(1);
        }


        function toggleBulkActions() {
            const count = $('.enquiry-checkbox:checked').length;
            if (count > 0) {
                $('#bulkActionBar').css('display', 'flex');
            } else {
                $('#bulkActionBar').hide();
            }
        }


        function bulkDelete() {
            const ids = $('.enquiry-checkbox:checked').map((_, el) => $(el).val()).get();
            if (ids.length === 0 || !confirm(`Delete ${ids.length} items?`)) return;

            const filters = $('#filterForm').serialize();
            const payload = filters + '&_token={{ csrf_token() }}&' + $.param({ids: ids});

            $.post('{{ route("admin.enquiries.bulk-delete") }}', payload)
                .done(function(res) {
                    if (!res.success) {
                        alert('Error: ' + (res.message || 'Delete failed.'));
                    }
                    fetchEnquiries();
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Delete failed. Check your permissions.';
                    alert('Error: ' + msg);
                    fetchEnquiries();
                });
        }

        function bulkAssign() {
            const ids = $('.enquiry-checkbox:checked').map((_, el) => $(el).val()).get();
            const user = $('#bulkAssignUser').val();
            const filters = $('#filterForm').serialize();

            if (ids.length === 0 || !user) return alert('Select items and user');

            const payload = filters + '&_token={{ csrf_token() }}&target_user_id=' + user + '&' + $.param({ids: ids});

            $.post('{{ route("admin.enquiries.bulk-assign") }}', payload)
                .done(function(res) {
                    if (res.stats) updateStats(res.stats);
                    fetchEnquiries();
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Assign failed. Check your permissions.';
                    alert('Error: ' + msg);
                });
        }

        function openEnquiryModal(id) {
            $('#enquiryDetailsModal').modal('show');
            $('#enquiryModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

            $.get("{{ url('admin/enquiries') }}/" + id, function (res) {
                $('#enquiryModalBody').html(res);
            }).fail(function () {
                $('#enquiryModalBody').html('<div class="text-center text-danger py-5">Failed to load data.</div>');
            });
        }

        function quickUpdate(id, field, value) {
            document.body.style.cursor = 'wait';

            // Get all current filters
            let filters = $('#filterForm').serialize();

            $.ajax({
                url: "{{ url('admin/enquiries') }}/" + id + "/quick-update",
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: filters + '&field=' + field + '&value=' + value,
                success: function (res) {
                    document.body.style.cursor = 'default';
                    if (res.stats) updateStats(res.stats);

                    // If status status changed, we might need to remove the row if filtered by status
                    // Simplest approach: Reload list
                    if (field === 'status' || res.new_status) {
                        fetchEnquiries();
                    }
                },
                error: function () {
                    document.body.style.cursor = 'default';
                    alert('Update failed.');
                }
            });
        }

        function updateStats(stats) {
            if (!stats) return;
            const map = {
                'New': 'count-New',
                'Next Year': 'count-Next-Year',
                'Contacted': 'count-Contacted',
                'Follow-up': 'count-Follow-up',
                'Interested': 'count-Interested',
                'Admitted': 'count-Admitted',
                'Next Entrance Exam': 'count-Entrance-Exam',
                'Not Interested': 'count-Not-Interested',
                'Total': 'count-Total',
                'Test Attended': 'count-TestAttended',
                'Total Discount': 'count-TotalDiscount',
                'Avg Marks': 'count-AvgMarks',
                'Uniform': 'count-Uniform',
                'Books': 'count-Books'
            };

            for (const [key, id] of Object.entries(map)) {
                if (stats[key] !== undefined) {
                    const el = document.getElementById(id);
                    if (el) {
                        let next = stats[key];
                        let formattedNext = next;
                        
                        // Handle formatting
                        if (key === 'Total Discount') {
                            formattedNext = '₹' + parseInt(next).toLocaleString();
                        }

                        if (el.innerText != formattedNext) {
                            el.innerText = formattedNext;
                            el.style.color = '#4e73df';
                            setTimeout(() => el.style.color = '', 500);
                        }
                    }
                }
            }
        }

        let checkTimer;
        function checkMobileCreate(phone) {
            clearTimeout(checkTimer);
            const feedback = $('#createPhoneFeedback');
            const input    = $('#createPhoneInput');

            if (phone.length < 10) {
                feedback.hide();
                input.removeClass('is-invalid is-valid');
                return;
            }

            feedback.show().text('Checking...').removeClass('text-danger text-success').addClass('text-info');

            checkTimer = setTimeout(function () {
                $.get('{{ route("admin.enquiries.check-mobile") }}?phone=' + encodeURIComponent(phone), function (data) {
                    if (data.status === 'error') {
                        feedback.text(data.message).removeClass('text-info').addClass('text-danger');
                        input.addClass('is-invalid');
                    } else {
                        feedback.text('✓ Available').removeClass('text-info text-danger').addClass('text-success');
                        input.removeClass('is-invalid').addClass('is-valid');
                        setTimeout(() => feedback.fadeOut(), 2000);
                    }
                });
            }, 500);
        }

        /**
         * AJAX row-level delete — replaces the old full-page <form> POST,
         * keeping the AJAX table intact after deletion.
         */
        function deleteEnquiry(id, btn) {
            if (!confirm('Delete this enquiry? This cannot be undone.')) return;

            const $btn = $(btn);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '{{ url("admin/enquiries") }}/' + id,
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: '{{ csrf_token() }}'
                },
                success: function () {
                    fetchEnquiries();
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    const msg = xhr.responseJSON?.message || 'Delete failed. Check your permissions.';
                    alert('Error: ' + msg);
                }
            });
        }
    </script>
@endpush