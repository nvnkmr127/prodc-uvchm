@extends('layouts.theme')
@section('title', 'Enquiry Hub')

@push('styles')
    <style>
        /* --- Modern CRM Design System --- */
        :root {
            --crm-primary: #4e73df;
            --crm-primary-light: #f8f9fc;
            --crm-success: #1cc88a;
            --crm-info: #36b9cc;
            --crm-warning: #f6c23e;
            --crm-danger: #e74a3b;
            --crm-secondary: #858796;
            --crm-gray-100: #f8f9fc;
            --crm-gray-200: #eaecf4;
            --crm-gray-800: #5a5c69;
        }

        /* --- Stats Grid System --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card-modern {
            background: white;
            padding: 1.25rem;
            border-radius: 1rem;
            border: 1px solid var(--crm-gray-200);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100px;
        }

        .stat-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            border-color: var(--crm-primary);
        }

        .stat-card-modern .stat-icon {
            position: absolute;
            right: -10px;
            top: -10px;
            font-size: 3rem;
            opacity: 0.05;
            transform: rotate(-15deg);
            transition: all 0.3s;
        }

        .stat-card-modern:hover .stat-icon {
            opacity: 0.1;
            transform: rotate(0deg) scale(1.1);
        }

        .stat-card-modern .stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--crm-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-card-modern .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--crm-gray-800);
            line-height: 1;
        }

        .stat-card-modern.active {
            background: var(--crm-primary);
            border-color: var(--crm-primary);
        }

        .stat-card-modern.active .stat-label,
        .stat-card-modern.active .stat-value {
            color: white;
        }

        /* Border Accents */
        .border-left-primary { border-left: 4px solid var(--crm-primary) !important; }
        .border-left-success { border-left: 4px solid var(--crm-success) !important; }
        .border-left-info { border-left: 4px solid var(--crm-info) !important; }
        .border-left-warning { border-left: 4px solid var(--crm-warning) !important; }
        .border-left-danger { border-left: 4px solid var(--crm-danger) !important; }

        /* --- Enhanced Table --- */
        .table-custom {
            margin: 0;
            width: 100%;
            border-spacing: 0;
        }

        .table-custom thead th {
            background: var(--crm-gray-100);
            color: var(--crm-secondary);
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 1.25rem 1rem;
            border-top: none;
            border-bottom: 2px solid var(--crm-gray-200);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .hover-row:hover {
            background-color: rgba(78, 115, 223, 0.02) !important;
        }

        .inline-edit {
            border: 1px solid transparent;
            background: transparent;
            padding: 0.35rem 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            max-width: 100%;
        }

        .inline-edit:hover {
            background: white;
            border-color: var(--crm-gray-200);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .inline-edit:focus {
            outline: none;
            background: white;
            border-color: var(--crm-primary);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
        }

        /* Badge Soft Colors */
        .badge-success-soft { background-color: #e8f5e9; color: #2e7d32; }
        .badge-info-soft { background-color: #e3f2fd; color: #1565c0; }
        .badge-warning-soft { background-color: #fff8e1; color: #e65100; }
        .badge-danger-soft { background-color: #fbe9e7; color: #d84315; }
        .badge-primary-soft { background-color: #e8eaf6; color: #283593; }

        /* Action Buttons */
        .btn-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            padding: 0;
            transition: all 0.2s;
        }

        .btn-light-primary { background: #eef2ff; color: #4e73df; border: none; }
        .btn-light-primary:hover { background: #4e73df; color: white; }
        
        .btn-light-secondary { background: #f8f9fc; color: #858796; border: none; }
        .btn-light-secondary:hover { background: #eaecf4; color: #5a5c69; }

        /* Status Pills (Enhanced) */
        .badge-pill-custom {
            padding: 0.5rem 1rem;
            font-size: 0.7rem;
            letter-spacing: 0.3px;
            border-radius: 2rem;
        }

        .status-new { background-color: #e0f7fa; color: #00838f; }
        .status-contacted { background-color: #e8f5e9; color: #2e7d32; }
        .status-interested { background-color: #fff8e1; color: #f57f17; }
        .status-follow-up { background-color: #fff3e0; color: #e65100; }
        .status-admitted { background-color: #f1f8e9; color: #33691e; }
        .status-interested-next-year { background-color: #f3e5f5; color: #7b1fa2; }
        .status-not-interested { background-color: #ffeef0; color: #d32f2f; }

        /* Filter Modernization */
        .filter-input-group {
            background: var(--crm-gray-100);
            border-radius: 0.75rem;
            padding: 0.25rem 0.75rem;
            border: 1px solid var(--crm-gray-200);
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }

        .filter-input-group:focus-within {
            border-color: var(--crm-primary);
            background: white;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
        }

        .filter-input-group i { color: var(--crm-secondary); }

        /* Avatar */
        .student-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Custom Scrollbar */
        .table-responsive::-webkit-scrollbar { height: 6px; width: 6px; }
        .table-responsive::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 10px; }
        .table-responsive::-webkit-scrollbar-track { background: #f7fafc; }


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
        /* Loading Overlay */
        #tableLoader {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            backdrop-filter: blur(2px);
        }

        .gap-1 { gap: 0.25rem; }
        .gap-2 { gap: 0.5rem; }
        
        .btn-icon i { font-size: 0.85rem; }
    </style>
@endpush

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                @if($isFacebookView ?? false)
                    <i class="fab fa-facebook text-primary mr-2"></i> Facebook Leads
                @else
                    Enquiry Hub
                @endif
            </h1>
            <div class="d-flex gap-2">
                @hasanyrole('super-admin|Super-Admin')
                <button type="button" class="btn btn-light text-primary shadow-sm font-weight-bold mr-2"
                    data-toggle="modal" data-target="#importEnquiryModal">
                    <i class="fas fa-file-import fa-sm mr-1"></i> Import
                </button>
                @endhasanyrole
                <button type="button" class="btn btn-primary shadow-sm font-weight-bold px-4" data-toggle="modal"
                    data-target="#addEnquiryModal">
                    <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> New Enquiry
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card-modern border-left-info {{ request('status') == 'New' ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', ['status' => 'New']) }}" class="text-decoration-none h-100 d-flex flex-column justify-content-between">
                    <i class="fas fa-star stat-icon"></i>
                    <div class="stat-label">New Leads</div>
                    <div class="stat-value" id="count-New">{{ $counts['New'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-modern border-left-primary {{ request('status') == 'Interested Next Year' ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Interested Next Year']) }}" class="text-decoration-none h-100 d-flex flex-column justify-content-between">
                    <i class="fas fa-calendar-alt stat-icon"></i>
                    <div class="stat-label">Next Year</div>
                    <div class="stat-value" id="count-Next-Year">{{ $counts['Next Year'] }}</div>
                </a>
            </div>
            <div class="stat-card-modern border-left-success {{ request('status') == 'Contacted' ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Contacted']) }}" class="text-decoration-none h-100 d-flex flex-column justify-content-between">
                    <i class="fas fa-phone-alt stat-icon"></i>
                    <div class="stat-label">Contacted</div>
                    <div class="stat-value" id="count-Contacted">{{ $counts['Contacted'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-modern border-left-warning {{ request('status') == 'Follow-up' ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Follow-up']) }}" class="text-decoration-none h-100 d-flex flex-column justify-content-between">
                    <i class="fas fa-clock stat-icon"></i>
                    <div class="stat-label">Follow-Up</div>
                    <div class="stat-value" id="count-Follow-up">{{ $counts['Follow-up'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-modern border-left-warning {{ request('status') == 'Interested' ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Interested']) }}" class="text-decoration-none h-100 d-flex flex-column justify-content-between">
                    <i class="fas fa-heart stat-icon"></i>
                    <div class="stat-label">Interested</div>
                    <div class="stat-value" id="count-Interested">{{ $counts['Interested'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-modern border-left-success {{ request('status') == 'Admitted' ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Admitted']) }}" class="text-decoration-none h-100 d-flex flex-column justify-content-between">
                    <i class="fas fa-user-check stat-icon"></i>
                    <div class="stat-label">Admitted</div>
                    <div class="stat-value" id="count-Admitted">{{ $counts['Admitted'] ?? 0 }}</div>
                </a>
            </div>
            <div class="stat-card-modern border-left-danger {{ request('status') == 'Not Interested' ? 'active' : '' }}">
                <a href="{{ route('admin.enquiries.index', ['status' => 'Not Interested']) }}" class="text-decoration-none h-100 d-flex flex-column justify-content-between">
                    <i class="fas fa-user-times stat-icon"></i>
                    <div class="stat-label">Dropped</div>
                    <div class="stat-value" id="count-Not-Interested">{{ $counts['Not Interested'] ?? 0 }}</div>
                </a>
            </div>
        </div>



        <div class="card shadow-sm mb-4 border-0" style="border-radius: 1rem;">
            <div class="card-body p-3">
                <form id="filterForm">
                    <div class="row g-2 align-items-center">

                        <!-- SEARCH -->
                        <div class="col-lg-3 col-md-6">
                            <div class="search-box-container">
                                <div class="filter-input-group">
                                    <i class="fas fa-search mr-2"></i>
                                    <input type="text" class="form-control border-0 bg-transparent small filter-input"
                                        name="search" id="liveSearchInput" value="{{ request('search') }}"
                                        placeholder="Search name or phone..." autocomplete="off">
                                </div>
                                <div id="liveSearchResults"></div>
                            </div>
                        </div>

                        <!-- DATE FILTERS -->
                        <div class="col-lg-3 col-md-6 d-flex gap-2">
                            <div class="filter-input-group flex-fill mr-1">
                                <i class="fas fa-calendar-day mr-2"></i>
                                <input type="date" class="form-control border-0 bg-transparent small font-weight-bold filter-input"
                                    name="start_date" title="Start Date" value="{{ request('start_date') }}">
                            </div>
                            <div class="filter-input-group flex-fill ml-1">
                                <i class="fas fa-calendar-check mr-2"></i>
                                <input type="date" class="form-control border-0 bg-transparent small font-weight-bold filter-input"
                                    name="end_date" title="End Date" value="{{ request('end_date') }}">
                            </div>
                        </div>

                        <!-- STATUS & COUNSELOR -->
                        <div class="col-lg-2 col-md-4">
                            <div class="filter-input-group">
                                <i class="fas fa-layer-group mr-2"></i>
                                <select class="form-control border-0 bg-transparent small font-weight-bold filter-input" name="status">
                                    <option value="">All Statuses</option>
                                    @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Admitted', 'Interested Next Year', 'Not Interested'] as $s)
                                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <div class="filter-input-group">
                                <i class="fas fa-user-tie mr-2"></i>
                                <select class="form-control border-0 bg-transparent small font-weight-bold filter-input" name="assigned_to_user_id">
                                    <option value="">All Counselors</option>
                                    @foreach($counselors as $counselor)
                                        <option value="{{ $counselor->id }}" {{ request('assigned_to_user_id') == $counselor->id ? 'selected' : '' }}>
                                            {{ $counselor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- ACTIONS -->
                        <div class="col-lg-2 text-lg-right d-flex align-items-center justify-content-end">
                             <!-- Bulk Actions -->
                             <div id="bulkActionBar" style="display:none; gap:5px; margin-right:5px;">
                                <button type="button" class="btn btn-danger btn-sm rounded-circle" onclick="bulkDelete()" title="Delete Selected">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <div class="input-group input-group-sm" style="width: 140px;">
                                    <select class="custom-select" id="bulkAssignUser">
                                        <option value="">Assign to...</option>
                                        @foreach($counselors as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button class="btn btn-success" type="button" onclick="bulkAssign()"><i class="fas fa-check"></i></button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" onclick="resetFilters()" class="btn btn-light btn-sm text-secondary font-weight-bold" title="Reset Filters">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row mt-2 g-2">
                        <div class="col-lg-3 col-md-6 text-xs text-muted font-weight-bold mb-1">ADVANCED FILTERS:</div>
                        <div class="w-100"></div>
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-input-group">
                                <i class="fas fa-book mr-2"></i>
                                <select class="form-control border-0 bg-transparent small font-weight-bold filter-input" name="course_id">
                                    <option value="">All Courses</option>
                                    @foreach($courses as $id => $name)
                                        <option value="{{ $id }}" {{ request('course_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-input-group">
                                <i class="fas fa-link mr-2"></i>
                                <select class="form-control border-0 bg-transparent small font-weight-bold filter-input" name="source">
                                    <option value="">All Sources</option>
                                    @foreach($sources as $value => $label)
                                        <option value="{{ $value }}" {{ request('source') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0" style="border-radius: 1rem; overflow: hidden; position: relative;">
            <div id="tableLoader">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
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
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
                <div class="modal-header border-bottom-0 pt-4 px-4 pb-0">
                    <div>
                        <h4 class="modal-title font-weight-bold text-gray-800">Log New Enquiry</h4>
                        <p class="text-muted small mb-0">Fill in the details to create a new lead.</p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addEnquiryForm" action="{{ route('admin.enquiries.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="small font-weight-bold text-gray-700 mb-1">Student Name <span class="text-danger">*</span></label>
                                <div class="filter-input-group">
                                    <i class="fas fa-user mr-2"></i>
                                    <input type="text" class="form-control border-0 bg-transparent" name="student_name" placeholder="Full name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="small font-weight-bold text-gray-700 mb-1">Phone Number <span class="text-danger">*</span></label>
                                <div class="filter-input-group">
                                    <i class="fas fa-phone mr-2"></i>
                                    <input type="tel" class="form-control border-0 bg-transparent" name="phone_number" id="createPhoneInput" 
                                        placeholder="10-digit mobile" required onkeyup="checkMobileCreate(this.value)" autocomplete="off">
                                </div>
                                <div id="createPhoneFeedback" class="small font-weight-bold mt-1" style="display:none;"></div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="small font-weight-bold text-gray-700 mb-1">Course Interest</label>
                                <div class="filter-input-group">
                                    <i class="fas fa-graduation-cap mr-2"></i>
                                    <select class="form-control border-0 bg-transparent" name="course_id">
                                        <option value="">Select Course</option>
                                        @foreach($courses as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="small font-weight-bold text-gray-700 mb-1">Source</label>
                                <div class="filter-input-group">
                                    <i class="fas fa-bullhorn mr-2"></i>
                                    <select class="form-control border-0 bg-transparent" name="source" id="sourceSelect">
                                        <option value="">-- Select Source --</option>
                                        @foreach($sources as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 mb-4" id="referralWrapper" style="display: none;">
                                <label class="small font-weight-bold text-gray-700 mb-1" id="referralLabel">Referral Name</label>
                                <div class="filter-input-group">
                                    <i class="fas fa-handshake mr-2"></i>
                                    <input type="text" class="form-control border-0 bg-transparent" name="referral_name" id="referralInput" placeholder="Specify details">
                                </div>
                            </div>
                            <div class="col-md-12 mb-4">
                                <label class="small font-weight-bold text-gray-700 mb-1">Address / Location</label>
                                <div class="filter-input-group">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <input type="text" class="form-control border-0 bg-transparent" name="address" placeholder="Enter village or city">
                                </div>
                            </div>
                            <div class="col-12 mb-0">
                                <label class="small font-weight-bold text-gray-700 mb-1">Notes</label>
                                <textarea class="form-control bg-light border-0" name="notes" rows="3" placeholder="Any specific requirements or context..." style="border-radius: 0.75rem;"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4 pt-0">
                    <button type="button" class="btn btn-light font-weight-bold px-4" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary font-weight-bold px-5 shadow-sm"
                        onclick="document.getElementById('addEnquiryForm').submit()">Create Lead</button>
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

            // Show Loading Indicator
            $('#tableLoader').css('display', 'flex');

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
                    $('#tableLoader').css('display', 'none');

                    // Update URL (Push State)
                    const params = new URLSearchParams(data);
                    window.history.replaceState(null, null, "?" + params.toString());
                },
                error: function () {
                    alert("Failed to load data");
                    $('#tableLoader').css('display', 'none');
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