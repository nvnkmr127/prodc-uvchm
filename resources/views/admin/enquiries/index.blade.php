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
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card-mini {
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-left: 0.25rem solid #e3e6f0;
            transition: transform 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card-mini:hover {
            transform: translateY(-3px);
        }

        .stat-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #5a5c69;
            line-height: 1.2;
        }

        /* Status Colors */
        .status-new-border {
            border-left-color: #36b9cc !important;
        }

        .status-contacted-border {
            border-left-color: #1cc88a !important;
        }

        .status-interested-border {
            border-left-color: #f6c23e !important;
        }

        .status-followup-border {
            border-left-color: #fd7e14 !important;
        }

        .status-admitted-border {
            border-left-color: #0f6848 !important;
        }

        .status-Next-Year-border {
            border-left-color: #6f42c1 !important;
        }

        .status-dropped-border {
            border-left-color: #e74a3b !important;
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

        .table-custom tbody td {
            padding: 1rem;
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
    </style>
@endpush

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Enquiry Hub</h1>
            <button type="button" class="btn btn-primary shadow-sm font-weight-bold" data-toggle="modal"
                data-target="#addEnquiryModal">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> New Enquiry
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card-mini status-new-border">
                <a href="{{ route('admin.enquiries.index', ['status' => 'New']) }}" class="text-decoration-none">
                    <div class="stat-label text-info">New Leads</div>
                    <div class="stat-value" id="count-New">{{ $counts['New'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-Next-Year-border">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Interested Next Year']) }}"
                    class="text-decoration-none">
                    <div class="stat-label text-info">Next Year</div>
                    <div class="stat-value" id="count-Next-Year">{{ $counts['Next Year'] }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-contacted-border">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Contacted']) }}" class="text-decoration-none">
                    <div class="stat-label text-success">Contacted</div>
                    <div class="stat-value" id="count-Contacted">{{ $counts['Contacted'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-followup-border">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Follow-up']) }}" class="text-decoration-none">
                    <div class="stat-label text-warning">Follow-Up</div>
                    <div class="stat-value" id="count-Follow-up">{{ $counts['Follow-up'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-interested-border">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Interested']) }}" class="text-decoration-none">
                    <div class="stat-label text-warning">Interested</div>
                    <div class="stat-value" id="count-Interested">{{ $counts['Interested'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-admitted-border">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Admitted']) }}" class="text-decoration-none">
                    <div class="stat-label text-success">Admitted</div>
                    <div class="stat-value" id="count-Admitted">{{ $counts['Admitted'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-mini status-dropped-border">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Not Interested']) }}" class="text-decoration-none">
                    <div class="stat-label text-danger">Dropped</div>
                    <div class="stat-value" id="count-Not-Interested">{{ $counts['Not Interested'] ?? 0 }}</div>
                </a>
            </div>
        </div>



        <div class="card shadow mb-4 border-0" style="border-radius: 1rem;">
            <div class="card-body py-3">
                <form id="filterForm">
                    <div class="row align-items-center">

                        <!-- SEARCH -->
                        <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                            <div class="search-box-container">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-0 pl-3"><i
                                                class="fas fa-search text-gray-400"></i></span>
                                    </div>
                                    <input type="text" class="form-control bg-light border-0 small filter-input"
                                        name="search" id="liveSearchInput" value="{{ request('search') }}"
                                        placeholder="Name, Phone..." autocomplete="off">
                                </div>
                                <div id="liveSearchResults"></div>
                            </div>
                        </div>

                        <!-- DATE FILTERS -->
                        <div class="col-lg-3 col-md-6 mb-2 mb-lg-0 d-flex">
                            <input type="date"
                                class="form-control border-0 bg-light small font-weight-bold mr-1 filter-input"
                                name="start_date" placeholder="Start Date" title="Start Date"
                                value="{{ request('start_date') }}">
                            <input type="date"
                                class="form-control border-0 bg-light small font-weight-bold ml-1 filter-input"
                                name="end_date" placeholder="End Date" title="End Date"
                                value="{{ request('end_date') }}">
                        </div>

                        <!-- FILTERS -->
                        <div class="col-lg-2 col-md-4 mb-2 mb-lg-0">
                            <select class="form-control border-0 bg-light small font-weight-bold filter-input"
                                name="status">
                                <option value="">All Statuses</option>
                                @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Admitted', 'Interested Next Year', 'Not Interested'] as $s)
                                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 mb-2 mb-lg-0">
                            <select class="form-control border-0 bg-light small font-weight-bold filter-input"
                                name="assigned_to_user_id">
                                <option value="">All Counselors</option>
                                @foreach($counselors as $counselor)
                                    <option value="{{ $counselor->id }}" {{ request('assigned_to_user_id') == $counselor->id ? 'selected' : '' }}>
                                        {{ $counselor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- ACTIONS ROW (Inline) -->
                        <div class="col-lg-2 text-lg-right d-flex align-items-center justify-content-end">
                            <!-- Bulk Actions (Hidden by default) -->
                            <div id="bulkActionBar" style="display:none; gap:5px; margin-right:5px;">
                                <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()"
                                    title="Delete Selected">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <select class="custom-select custom-select-sm" id="bulkAssignUser" style="width: 100px;">
                                    <option value="">Assign...</option>
                                    @foreach($counselors as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-success btn-sm" onclick="bulkAssign()">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>

                            <!-- Permanent Actions -->
                            <div class="btn-group">
                                @hasanyrole('super-admin|Super-Admin')
                                <button type="button" class="btn btn-sm btn-light text-primary shadow-sm font-weight-bold"
                                    data-toggle="modal" data-target="#importEnquiryModal" title="Import CSV">
                                    <i class="fas fa-file-import mr-1"></i> Import
                                </button>
                                @endhasanyrole
                                <button type="button" onclick="resetFilters()"
                                    class="btn btn-sm btn-light text-secondary shadow-sm font-weight-bold"
                                    title="Reset Filters">
                                    <i class="fas fa-sync-alt mr-1"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Row 2: Course + Source filters --}}
                    <div class="row mt-2">
                        <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                            <select class="form-control border-0 bg-light small font-weight-bold filter-input" name="course_id">
                                <option value="">All Courses</option>
                                @foreach($courses as $id => $name)
                                    <option value="{{ $id }}" {{ request('course_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                            <select class="form-control border-0 bg-light small font-weight-bold filter-input" name="source">
                                <option value="">All Sources</option>
                                @foreach($sources as $value => $label)
                                    <option value="{{ $value }}" {{ request('source') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
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
                                <!-- Headers -->
                                <th width="23%">Student Profile</th>
                                <th width="12%">Course</th>
                                <th width="10%">Source</th>
                                <th width="16%">Counselor</th>
                                <th width="13%">Follow-up</th>
                                <th width="8%" class="text-center">Status</th>
                                <th width="14%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="enquiryTableBody">
                            @include('admin.enquiries._table_body')
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Container -->
                <div class="px-4 py-3 border-top" id="paginationContainer">
                    {{ $enquiries->links() }}
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
    <script>
        $(document).ready(function () {
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
        });

        // --- AJAX Reset ---
        function resetFilters() {
            // Reset all form fields
            $('#filterForm')[0].reset();
            // Manually reset values in state to ensure 'change' events don't fire inappropriately or to sync UI
            $('#filterForm input, #filterForm select').val('');

            // Fetch clean list
            fetchEnquiries(1);
        }

        // --- Main AJAX Fetch ---
        function fetchEnquiries(page = 1) {
            // Collect all filters
            let data = $('#filterForm').serializeArray().reduce(function (obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});

            data.page = page;

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
                    const params = new URLSearchParams(data);
                    window.history.replaceState(null, null, "?" + params.toString());
                },
                error: function () {
                    alert("Failed to load data");
                    $('#dataTable').css('opacity', '1');
                }
            });
        }

        function toggleBulkActions() {
            const count = $('.enquiry-checkbox:checked').length;
            $('#bulkActionBar').toggle(count > 0);
        }

        function bulkDelete() {
            const ids = $('.enquiry-checkbox:checked').map((_, el) => $(el).val()).get();
            if (ids.length === 0 || !confirm(`Delete ${ids.length} items?`)) return;

            $.post('{{ route("admin.enquiries.bulk-delete") }}',
                { _token: '{{ csrf_token() }}', ids: ids },
                () => fetchEnquiries() // Refresh AJAX
            );
        }

        function bulkAssign() {
            const ids = $('.enquiry-checkbox:checked').map((_, el) => $(el).val()).get();
            const user = $('#bulkAssignUser').val();
            // Get current filter to ensure consistent stats return
            const filters = $('#filterForm').serializeArray().reduce(function (obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});

            if (ids.length === 0 || !user) return alert('Select items and user');

            // Merge filters into payload
            const payload = {
                _token: '{{ csrf_token() }}',
                ids: ids,
                ...filters, // Spread filters first so they don't overwrite specific keys
                assigned_to_user_id: user, // The target for assignment
                filter_assigned_to: filters.assigned_to_user_id // Pass the filter state for stats calculation
            };

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
            const filters = $('#filterForm').serializeArray().reduce(function (obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});

            $.ajax({
                url: "{{ url('admin/enquiries') }}/" + id + "/quick-update",
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: {
                    field: field,
                    value: value,
                    ...filters // Send all active filters so stats come back correct
                },
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
                'Not Interested': 'count-Not-Interested'
            };

            for (const [key, id] of Object.entries(map)) {
                if (stats[key] !== undefined) {
                    const el = document.getElementById(id);
                    if (el) {
                        const current = parseInt(el.innerText);
                        const next = stats[key];
                        if (current !== next) {
                            el.innerText = next;
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