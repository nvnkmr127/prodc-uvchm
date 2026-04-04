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
        .status-new-border { border-left-color: #0ea5e9 !important; background: rgba(14, 165, 233, 0.03); }
        .status-contacted-border { border-left-color: #10b981 !important; background: rgba(16, 185, 129, 0.03); }
        .status-interested-border { border-left-color: #f59e0b !important; background: rgba(245, 158, 11, 0.03); }
        .status-followup-border { border-left-color: #f97316 !important; background: rgba(249, 115, 22, 0.03); }
        .status-admitted-border { border-left-color: #059669 !important; background: rgba(5, 150, 105, 0.03); }
        .status-Next-Year-border { border-left-color: #8b5cf6 !important; background: rgba(139, 92, 246, 0.03); }
        .status-next-entrance-exam-border { border-left-color: #f43f5e !important; background: rgba(244, 63, 94, 0.03); }
        .status-dropped-border { border-left-color: #ef4444 !important; background: rgba(239, 68, 68, 0.03); }
        .border-left-primary { border-left-color: #4f46e5 !important; background: rgba(79, 70, 229, 0.03); }

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

        .status-new {
            background-color: #e3f2fd;
            color: #36b9cc;
        }

        .status-contacted {
            background-color: #e8f5e9;
            color: #1cc88a;
        }

        .status-interested {
            background-color: #fff3cd;
            color: #f6c23e;
        }

        .status-follow-up {
            background-color: #fff3e0;
            color: #fd7e14;
        }

        .status-admitted {
            background-color: #d1e7dd;
            color: #0f6848;
        }

        .status-Next-Year {
            background-color: #d1e7dd;
            color: #0f6848;
        }

        .status-next-entrance-exam {
            background-color: #fff1f2;
            color: #e11d48;
        }

        .status-not-interested {
            background-color: #f8d7da;
            color: #e74a3b;
        }

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
            <div class="stat-card-mini status-new-border">
                <a href="javascript:void(0)" class="text-decoration-none stat-card-link" data-status="New">
                    <div class="stat-label text-info">New Leads</div>
                    <div class="stat-value" id="count-New">{{ $counts['New'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-Next-Year-border">
                <a href="javascript:void(0)" class="text-decoration-none stat-card-link" data-status="Interested Next Year">
                    <div class="stat-label text-info">Next Year</div>
                    <div class="stat-value" id="count-Next-Year">{{ $counts['Next Year'] }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-next-entrance-exam-border">
                <a href="javascript:void(0)" class="text-decoration-none stat-card-link" data-status="Next Entrance Exam">
                    <div class="stat-label text-danger">Entrance Exam</div>
                    <div class="stat-value" id="count-Entrance-Exam">{{ $counts['Next Entrance Exam'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-contacted-border">
                <a href="javascript:void(0)" class="text-decoration-none stat-card-link" data-status="Contacted">
                    <div class="stat-label text-success">Contacted</div>
                    <div class="stat-value" id="count-Contacted">{{ $counts['Contacted'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-followup-border">
                <a href="javascript:void(0)" class="text-decoration-none stat-card-link" data-status="Follow-up">
                    <div class="stat-label text-warning">Follow-Up</div>
                    <div class="stat-value" id="count-Follow-up">{{ $counts['Follow-up'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-interested-border">
                <a href="javascript:void(0)" class="text-decoration-none stat-card-link" data-status="Interested">
                    <div class="stat-label text-warning">Interested</div>
                    <div class="stat-value" id="count-Interested">{{ $counts['Interested'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-admitted-border">
                <a href="javascript:void(0)" class="text-decoration-none stat-card-link" data-status="Admitted">
                    <div class="stat-label text-success">Admitted</div>
                    <div class="stat-value" id="count-Admitted">{{ $counts['Admitted'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-dropped-border">
                <a href="javascript:void(0)" class="text-decoration-none stat-card-link" data-status="Not Interested">
                    <div class="stat-label text-danger">Dropped</div>
                    <div class="stat-value" id="count-Not-Interested">{{ $counts['Not Interested'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini border-left-primary">
                <div class="stat-label text-primary">Total Enquiries</div>
                <div class="stat-value" id="count-Total">{{ $counts['Total'] ?? 0 }}</div>
            </div>
            <div class="stat-card-mini status-contacted-border">
                <div class="stat-label text-primary">Test Attended</div>
                <div class="stat-value" id="count-TestAttended">{{ $counts['Test Attended'] ?? 0 }}</div>
            </div>
            <div class="stat-card-mini status-interested-border">
                <div class="stat-label text-success">Total Discount</div>
                <div class="stat-value" id="count-TotalDiscount">₹{{ number_format($counts['Total Discount'] ?? 0, 0) }}</div>
            </div>
            <div class="stat-card-mini border-left-primary">
                <div class="stat-label text-info">Avg Marks</div>
                <div class="stat-value" id="count-AvgMarks">{{ number_format($counts['Avg Marks'] ?? 0, 1) }}</div>
            </div>
        </div>



        <div class="card shadow mb-4 border-0" style="border-radius: 1rem;">
            <div class="card-body py-3">
                <form id="filterForm">
                    <input type="hidden" name="sort" id="sortField" value="{{ request('sort', 'next_follow_up_date') }}">
                    <input type="hidden" name="direction" id="sortDirection" value="{{ request('direction', 'asc') }}">
                    <div class="row">
                        <!-- Column 1: Discovery -->
                        <div class="col-lg-4 col-md-12 border-right">
                            <h6 class="font-weight-bold text-primary mb-3 small text-uppercase">
                                <i class="fas fa-search mr-2"></i> Discovery
                            </h6>
                            <div class="search-box-container mb-3">
                                <label class="small text-muted font-weight-bold">Search Students</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-gray-400"></i></span>
                                    </div>
                                    <input type="text" class="form-control bg-light border-0 small filter-input"
                                        name="search" id="liveSearchInput" value="{{ request('search') }}"
                                        placeholder="Name, Phone..." autocomplete="off">
                                </div>
                                <div id="liveSearchResults"></div>
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
                                    <button type="button" onclick="resetFilters()"
                                        class="btn btn-sm btn-outline-secondary shadow-sm font-weight-bold"
                                        title="Reset Filters">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
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
                                    <select class="form-control bg-light border-0 small filter-input" id="perPageSelect" 
                                        onchange="$('#perPageField').val(this.value); applyFilters();">
                                        <option value="10" {{ $enquiries->perPage() == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ $enquiries->perPage() == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $enquiries->perPage() == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $enquiries->perPage() == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Bulk Actions Overlay (Specific context) -->
                            <div id="bulkActionBar" style="display:none; flex-direction: column; gap:10px; padding: 12px; background: #fff5f5; border-radius: 8px; border: 1px dashed #e74a3b;">
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
                    <input type="hidden" name="per_page" id="perPageField" value="{{ $enquiries->perPage() }}">
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
                                <!-- Headers -->
                                <th width="23%">
                                    <a href="javascript:void(0)" onclick="sortList('student_name')" class="sort-link {{ request('sort') == 'student_name' ? 'active' : '' }}">
                                        Student Profile
                                        <i class="fas fa-sort{{ request('sort') == 'student_name' ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th width="12%">
                                    <a href="javascript:void(0)" onclick="sortList('course_name')" class="sort-link {{ request('sort') == 'course_name' ? 'active' : '' }}">
                                        Course
                                        <i class="fas fa-sort{{ request('sort') == 'course_name' ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th width="10%">
                                    <a href="javascript:void(0)" onclick="sortList('source')" class="sort-link {{ request('sort') == 'source' ? 'active' : '' }}">
                                        Source
                                        <i class="fas fa-sort{{ request('sort') == 'source' ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th width="16%">
                                    <a href="javascript:void(0)" onclick="sortList('counselor_name')" class="sort-link {{ request('sort') == 'counselor_name' ? 'active' : '' }}">
                                        Counselor
                                        <i class="fas fa-sort{{ request('sort') == 'counselor_name' ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th width="13%">
                                    <a href="javascript:void(0)" onclick="sortList('next_follow_up_date')" class="sort-link {{ request('sort', 'next_follow_up_date') == 'next_follow_up_date' ? 'active' : '' }}">
                                        Follow-up
                                        <i class="fas fa-sort{{ request('sort', 'next_follow_up_date') == 'next_follow_up_date' ? (request('direction', 'asc') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th width="8%" class="text-center">
                                    <a href="javascript:void(0)" onclick="sortList('status')" class="sort-link {{ request('sort') == 'status' ? 'active' : '' }}">
                                        Status
                                        <i class="fas fa-sort{{ request('sort') == 'status' ? (request('direction') == 'asc' ? '-up' : '-down') : '' }} ml-1"></i>
                                    </a>
                                </th>
                                <th width="12%">Entrance Test</th>
                                <th width="14%" class="text-center">Actions</th>
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
                        <select class="custom-select custom-select-sm" id="perPageSelect" style="width: 70px;" onchange="updatePerPage(this.value)">
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
        data-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
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
        <div class="modal-dialog modal-lg" role="document">
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
                            <div class="col-md-4 mb-3">
                                <label class="small font-weight-bold text-gray-600">Discount Offered</label>
                                <input type="number" name="discount_offered" class="form-control bg-light border-0" placeholder="Amount">
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
                    <button type="button" class="btn btn-primary shadow-sm"
                        onclick="document.getElementById('addEnquiryForm').submit()">Save</button>
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

            // --- Filter Logic (Main AJAX) ---
            let debounceTimer;
            $('.filter-input').on('change keyup', function (e) {
                // For text inputs (search), debounce
                if (this.type === 'text') {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => fetchEnquiries(), 500);
                } else {
                    // Selects/Dates trigger immediately
                    fetchEnquiries();
                }
            });

            // Pagination Interception
            $(document).on('click', '#paginationContainer a', function (e) {
                e.preventDefault();
                let url = $(this).attr('href');
                let page = url.split('page=')[1] || 1;
                fetchEnquiries(page);
            });

            // --- Source/Referral Toggle (New Enquiry modal) ---
            $('#sourceSelect').on('change', function () {
                const val = $(this).val();
                const show = ['Agent', 'Referrals', 'Student Refer', 'Walk-in', 'Other'].includes(val);
                $('#referralWrapper').toggle(show);
                const labels = { 'Agent': 'Agent Name', 'Other': 'Specify Source', 'Walk-in': 'Referred By' };
                $('#referralLabel').text(labels[val] || 'Referral Name');
            });

            // --- Checkbox Logic ---
            $('#selectAll').on('change', function () {
                $('.enquiry-checkbox').prop('checked', this.checked);
                toggleBulkActions();
            });
            $(document).on('change', '.enquiry-checkbox', toggleBulkActions);

            // --- Stat Card Link Clicks (AJAX) ---
            $(document).on('click', '.stat-card-link', function (e) {
                e.preventDefault();
                const status = $(this).data('status');
                
                // Set the status filter in the form
                const statusSelect = $('select[name="status[]"]');
                statusSelect.val([status]).trigger('change');
                
                // fetchEnquiries will be called by the change trigger above
                // but to ensure it happens correctly:
                fetchEnquiries(1);
            });
        });

        // --- Export ---
        function exportFilteredResults() {
            const formData = $('#filterForm').serialize();
            const url = "{{ route('admin.enquiries.export') }}?" + formData;
            window.location.href = url;
        }

        // --- Sorting logic ---
        function sortList(field) {
            let currentField = $('#sortField').val();
            let currentDirection = $('#sortDirection').val();
            let newDirection = 'asc';

            if (currentField === field) {
                newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            }

            $('#sortField').val(field);
            $('#sortDirection').val(newDirection);

            // Update UI icons immediately (optional but smoother)
            $('.sort-link').removeClass('active').find('i').attr('class', 'fas fa-sort ml-1');
            let $activeLink = $(`a[onclick="sortList('${field}')"]`);
            $activeLink.addClass('active');
            $activeLink.find('i').attr('class', `fas fa-sort-${newDirection === 'asc' ? 'up' : 'down'} ml-1`);

            fetchEnquiries(1);
        }

        // --- AJAX Reset ---
        function resetFilters() {
            // Reset all form fields
            $('#filterForm')[0].reset();
            
            // Explicitly clear select2 and trigger change
            $('.select2-multiple').val(null).trigger('change');
            
            // Reset Sort to default
            $('#sortField').val('next_follow_up_date');
            $('#sortDirection').val('asc');
            $('.sort-link').removeClass('active').find('i').attr('class', 'fas fa-sort ml-1');
            $(`a[onclick="sortList('next_follow_up_date')"]`).addClass('active').find('i').attr('class', 'fas fa-sort-up ml-1');

            // Fetch clean list
            fetchEnquiries(1);
        }

        // --- Main AJAX Fetch ---
        function fetchEnquiries(page = 1) {
            // Collect all filters
            let data = $('#filterForm').serialize();

            // Append page manually as serialize() doesn't include it unless it's in a hidden field
            data += '&page=' + page;

            // Show Loading Indicator (Optional: Add a spinner or opacity)
            $('#dataTable').css('opacity', '0.5');

            $.ajax({
                url: "{{ route('admin.enquiries.index') }}",
                type: "GET",
                data: data,
                success: function (response) {
                    $('#enquiryTableBody').html(response.html);
                    $('#paginationContainer').html(response.pagination);
                    if (response.stats) {
                        updateStats(response.stats);
                    }
                    $('#dataTable').css('opacity', '1');

                    // Update URL (Push State)
                    // window.history.replaceState(null, null, "?" + data); // Removed as per request
                },
                error: function () {
                    alert("Failed to load data");
                    $('#dataTable').css('opacity', '1');
                }
            });
        }

        // --- Update Per Page ---
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

            $.post('{{ route("admin.enquiries.bulk-delete") }}',
                payload,
                () => fetchEnquiries() // Refresh AJAX
            );
        }

        function bulkAssign() {
            const ids = $('.enquiry-checkbox:checked').map((_, el) => $(el).val()).get();
            const user = $('#bulkAssignUser').val();
            // Get current filter to ensure consistent stats return
            const filters = $('#filterForm').serialize();

            if (ids.length === 0 || !user) return alert('Select items and user');

            // Construct payload: Merge filters + IDs + assignment
            const payload = filters + '&_token={{ csrf_token() }}&assigned_to_user_id=' + user + '&' + $.param({ids: ids});

            $.post('{{ route("admin.enquiries.bulk-assign") }}',
                payload,
                function (res) {
                    if (res.stats) updateStats(res.stats);
                    fetchEnquiries(); // Refresh List
                }
            );
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
                'Avg Marks': 'count-AvgMarks'
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
            const input = $('#createPhoneInput');

            if (phone.length < 10) {
                feedback.hide();
                input.removeClass('is-invalid is-valid');
                return;
            }

            feedback.show().text('Checking...').removeClass('text-danger text-success').addClass('text-info');
            checkTimer = setTimeout(() => { $.get(`{{ route('admin.enquiries.check-mobile') }}?phone=${phone}`, function (data) { if (data.status === 'error') { feedback.text(data.message).removeClass('text-info').addClass('text-danger'); input.addClass('is-invalid'); } else { feedback.text('Available').removeClass('text-info').addClass('text-success'); input.removeClass('is-invalid').addClass('is-valid'); setTimeout(() => feedback.fadeOut(), 2000); } }); }, 500);
        }
    </script>
@endpush