@extends('layouts.theme')
@section('title', 'Enquiry Hub')

@push('styles')
    <link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
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
        .stat-card-mini:hover { transform: translateY(-3px); }
        .stat-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: #5a5c69; line-height: 1.2; }
        
        /* Status Colors */
        .status-new-border { border-left-color: #36b9cc !important; }
        .status-contacted-border { border-left-color: #1cc88a !important; }
        .status-interested-border { border-left-color: #f6c23e !important; }
        .status-followup-border { border-left-color: #fd7e14 !important; }
        .status-admitted-border { border-left-color: #0f6848 !important; }
        .status-dropped-border { border-left-color: #e74a3b !important; }

        /* --- Live Search Dropdown --- */
        .search-box-container { position: relative; }
        #liveSearchResults {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
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
        .search-item:hover { background: #f0f2f5; }
        .search-item:last-child { border-bottom: none; }
        .search-avatar {
            width: 35px; height: 35px;
            background: #4e73df; color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px;
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
        .sort-link:hover { color: #4e73df; text-decoration: none; }
        .sort-link.active { color: #4e73df; }

        /* --- Responsive Tweaks --- */
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }

        /* --- Other Styles from previous file --- */
        .table-custom { margin: 0; width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-custom thead th { border: none; background: #f8f9fc; padding: 1rem; border-bottom: 2px solid #e3e6f0; }
        .table-custom tbody td { padding: 1rem; vertical-align: middle; border-top: 1px solid #f0f2f5; }
        .inline-edit { background: transparent; border: 1px solid transparent; padding: 0.4rem; border-radius: 0.35rem; width: 100%; cursor: pointer; }
        .inline-edit:hover, .inline-edit:focus { background: white; border-color: #d1d3e2; }
        .student-trigger { display: flex; align-items: center; cursor: pointer; }
        .student-avatar-small { width: 2.5rem; height: 2.5rem; border-radius: 50%; background: #4e73df; color: white; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem; font-weight: bold; }
        
        /* Badges */
        .badge-pill-custom { padding: 0.4em 1em; border-radius: 50px; font-weight: 700; font-size: 0.7rem; }
        .status-new { background-color: #e3f2fd; color: #36b9cc; }
        .status-contacted { background-color: #e8f5e9; color: #1cc88a; }
        .status-interested { background-color: #fff3cd; color: #f6c23e; }
        .status-follow-up { background-color: #fff3e0; color: #fd7e14; }
        .status-admitted { background-color: #d1e7dd; color: #0f6848; }
        .status-Next-Year { background-color: #d1e7dd; color: #0f6848; }
        .status-not-interested { background-color: #f8d7da; color: #e74a3b; }

        /* Urgent Row */
        .row-urgent td:first-child { border-left: 4px solid #e74a3b; }
        .text-urgent { color: #e74a3b !important; font-weight: 800; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Enquiry Hub</h1>
        <button type="button" class="btn btn-primary shadow-sm font-weight-bold" data-toggle="modal" data-target="#addEnquiryModal">
            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> New Enquiry
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card-mini status-new-border">
             <a href="{{ route('admin.enquiries.index', ['status' => 'New']) }}" class="text-decoration-none">
            <div class="stat-label text-info">New Leads</div>
            <div class="stat-value">{{ $counts['New'] ?? 0 }}</div> </a>
        </div>
                 <div class="stat-card-mini status-Next-Year-border">
             <a href="{{ route('admin.enquiries.index', ['status' => 'Interested Next Year']) }}" class="text-decoration-none">
            <div class="stat-label text-info">Next Year</div>
                <div class="stat-value" id="count-Next-Year">{{ $counts['Next Year'] }}</div></a>
        </div>
        <div class="stat-card-mini status-contacted-border">
             <a href="{{ route('admin.enquiries.index', ['status' => 'Contacted']) }}" class="text-decoration-none">
            <div class="stat-label text-success">Contacted</div>
            <div class="stat-value">{{ $counts['Contacted'] ?? 0 }}</div> </a>
        </div>
        <div class="stat-card-mini status-followup-border">
            <a href="{{ route('admin.enquiries.index', ['status' => 'Follow-up']) }}" class="text-decoration-none">
            <div class="stat-label text-warning">Follow-Up</div>
            <div class="stat-value">{{ $counts['Follow-up'] ?? 0 }}</div></a>
        </div>
        <div class="stat-card-mini status-interested-border">
             <a href="{{ route('admin.enquiries.index', ['status' => 'Interested']) }}" class="text-decoration-none">
            <div class="stat-label text-warning">Interested</div>
            <div class="stat-value">{{ $counts['Interested'] ?? 0 }}</div></a>
        </div>
        <div class="stat-card-mini status-admitted-border">
             <a href="{{ route('admin.enquiries.index', ['status' => 'Admitted']) }}" class="text-decoration-none">
            <div class="stat-label text-success">Admitted</div>
            <div class="stat-value">{{ $counts['Admitted'] ?? 0 }}</div></a>
        </div>
        <div class="stat-card-mini status-dropped-border">
              <a href="{{ route('admin.enquiries.index', ['status' => 'Not Interested']) }}" class="text-decoration-none">
            <div class="stat-label text-danger">Dropped</div>
            <div class="stat-value">{{ $counts['Not Interested'] ?? 0 }}</div></a>
        </div>
    </div>
    


    <div class="card shadow mb-4 border-0" style="border-radius: 1rem;">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.enquiries.index') }}">
                <div class="row align-items-center">
                    
                    <div class="col-lg-4 col-md-6 mb-2 mb-lg-0">
                        <div class="search-box-container">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light border-0 pl-3"><i class="fas fa-search text-gray-400"></i></span>
                                </div>
                                <input type="text" class="form-control bg-light border-0 small" 
                                       name="search" 
                                       id="liveSearchInput"
                                       value="{{ request('search') }}" 
                                       placeholder="Type name or phone to search..."
                                       autocomplete="off">
                            </div>
                            <div id="liveSearchResults"></div>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-3 mb-2 mb-lg-0">
                        <select class="form-control border-0 bg-light small font-weight-bold" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Admitted', 'Interested Next Year','Not Interested'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 mb-2 mb-lg-0">
                        <select class="form-control border-0 bg-light small font-weight-bold" name="assigned_to_user_id" onchange="this.form.submit()">
                            <option value="">All Counselors</option>
                            @foreach($counselors as $counselor)
                                <option value="{{ $counselor->id }}" {{ request('assigned_to_user_id') == $counselor->id ? 'selected' : '' }}>
                                    {{ $counselor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 text-lg-right d-flex align-items-center justify-content-end">
                        <div id="bulkActionBar" style="display:none; gap:5px; margin-right:10px;">
                            <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()" title="Delete Selected">
                                <i class="fas fa-trash"></i>
                            </button>
                            <select class="custom-select custom-select-sm" id="bulkAssignUser" style="width: 130px;">
                                <option value="">Assign...</option>
                                @foreach($counselors as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-success btn-sm" onclick="bulkAssign()">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                        
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right shadow">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#importEnquiryModal">
                                    <i class="fas fa-file-import mr-2 text-gray-400"></i> Import CSV
                                </a>
                                <a class="dropdown-item" href="{{ route('admin.enquiries.index') }}">
                                    <i class="fas fa-sync-alt mr-2 text-gray-400"></i> Reset Filters
                                </a>
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
                            <th width="5%" class="pl-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAll">
                                    <label class="custom-control-label" for="selectAll"></label>
                                </div>
                            </th>
                            
                            <th width="25%">
                                <a href="{{ route('admin.enquiries.index', array_merge(request()->all(), ['sort' => 'student_name', 'direction' => request('sort') == 'student_name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                   class="sort-link {{ request('sort') == 'student_name' ? 'active' : '' }}">
                                   Student Profile 
                                   <i class="fas {{ request('sort') == 'student_name' ? (request('direction') == 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} ml-1"></i>
                                </a>
                            </th>

                            <th width="15%">
                                <a href="{{ route('admin.enquiries.index', array_merge(request()->all(), ['sort' => 'course_name', 'direction' => request('sort') == 'course_name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                   class="sort-link {{ request('sort') == 'course_name' ? 'active' : '' }}">
                                   Course
                                   <i class="fas {{ request('sort') == 'course_name' ? (request('direction') == 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} ml-1"></i>
                                </a>
                            </th>

                            <th width="18%">
                                <a href="{{ route('admin.enquiries.index', array_merge(request()->all(), ['sort' => 'counselor_name', 'direction' => request('sort') == 'counselor_name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                   class="sort-link {{ request('sort') == 'counselor_name' ? 'active' : '' }}">
                                   Counselor
                                   <i class="fas {{ request('sort') == 'counselor_name' ? (request('direction') == 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} ml-1"></i>
                                </a>
                            </th>

                            <th width="15%">
                                <a href="{{ route('admin.enquiries.index', array_merge(request()->all(), ['sort' => 'next_follow_up_date', 'direction' => request('sort') == 'next_follow_up_date' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                   class="sort-link {{ request('sort') == 'next_follow_up_date' ? 'active' : '' }}">
                                   Follow-up
                                   <i class="fas {{ request('sort') == 'next_follow_up_date' ? (request('direction') == 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} ml-1"></i>
                                </a>
                            </th>

                            <th width="8%" class="text-center">
                                <a href="{{ route('admin.enquiries.index', array_merge(request()->all(), ['sort' => 'status', 'direction' => request('sort') == 'status' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                   class="sort-link {{ request('sort') == 'status' ? 'active' : '' }}" style="justify-content: center;">
                                   Status
                                   <i class="fas {{ request('sort') == 'status' ? (request('direction') == 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} ml-1"></i>
                                </a>
                            </th>

                            <th width="14%" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enquiries as $enquiry)
                        @php
                            $date = $enquiry->next_follow_up_date;
                            $isUrgent = false;
                            if ($date && $enquiry->status != 'Admitted') {
                                $followUpDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                $today = now()->format('Y-m-d');
                                if ($followUpDate <= $today) {
                                    $isUrgent = true;
                                }
                            }
                        @endphp
                        <tr class="{{ $isUrgent ? 'row-urgent' : '' }}">
                            <td class="pl-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input enquiry-checkbox" id="check_{{ $enquiry->id }}" value="{{ $enquiry->id }}">
                                    <label class="custom-control-label" for="check_{{ $enquiry->id }}"></label>
                                </div>
                            </td>
                            <td>
                                <div class="student-trigger" onclick="openEnquiryModal({{ $enquiry->id }})">
                                    <div class="student-avatar-small">
                                        {{ strtoupper(substr($enquiry->student_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h6 class="mb-0 font-weight-bold text-gray-800">{{ $enquiry->student_name }}</h6>
                                        <div class="small text-muted">
                                            <i class="fas fa-phone fa-xs mr-1"></i>{{ $enquiry->phone_number }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-light border text-gray-600">
                                    {{ $enquiry->course->name ?? 'General' }}
                                </span>
                            </td>
                            <td>
                                <select class="inline-edit" onchange="quickUpdate({{ $enquiry->id }}, 'assigned_to_user_id', this.value)">
                                    <option value="">Unassigned</option>
                                    @foreach($counselors as $counselor)
                                        <option value="{{ $counselor->id }}" {{ $enquiry->assigned_to_user_id == $counselor->id ? 'selected' : '' }}>
                                            {{ $counselor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="date" 
                                       class="inline-edit {{ $isUrgent ? 'text-urgent' : '' }}"
                                       value="{{ $enquiry->next_follow_up_date ? \Carbon\Carbon::parse($enquiry->next_follow_up_date)->format('Y-m-d') : '' }}"
                                       onchange="quickUpdate({{ $enquiry->id }}, 'next_follow_up_date', this.value)">
                            </td>
                            <td class="text-center">
                                <span class="badge badge-pill-custom status-{{ Str::slug($enquiry->status) }}">
                                    {{ $enquiry->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-light btn-sm btn-circle" onclick="openEnquiryModal({{ $enquiry->id }})" title="View">
                                        <i class="fas fa-eye text-primary"></i>
                                    </button>
                                    <a href="tel:{{ $enquiry->phone_number }}" class="btn btn-light btn-sm btn-circle" title="Call">
                                        <i class="fas fa-phone text-success"></i>
                                    </a>
                                    <a href="https://wa.me/{{ str_replace(['+',' ','-'], '', $enquiry->phone_number) }}" target="_blank" class="btn btn-light btn-sm btn-circle" title="WhatsApp">
                                        <i class="fab fa-whatsapp text-warning"></i>
                                    </a>
                                    <form action="{{ route('admin.enquiries.destroy', $enquiry->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm btn-circle" title="Delete">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                <h5>No enquiries found</h5>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-top">
                {{ $enquiries->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="enquiryDetailsModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <div class="modal-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="modal-title font-weight-bold text-gray-800"><i class="fas fa-user-circle mr-2 text-primary"></i>Enquiry Overview</h5>
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
                            <input type="tel" class="form-control" name="phone_number" id="createPhoneInput" required onkeyup="checkMobileCreate(this.value)" autocomplete="off">
                            <div id="createPhoneFeedback" class="small font-weight-bold mt-1" style="display:none;"></div>
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
                                <option value="Website">Website / Google</option>
                                <option value="Social Media">Social Media</option>
                                <option value="Agent">Agent</option>
                                <option value="Referrals">Referrals</option>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="referralWrapper" style="display: none;">
                            <label class="small font-weight-bold text-gray-600" id="referralLabel">Referral Name</label>
                            <input type="text" class="form-control bg-light border-0" name="referral_name" id="referralInput">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold text-gray-600">Assign Counselor</label>
                            <select class="form-control bg-light border-0" name="assigned_to_user_id">
                                <option value="">Auto-assign</option>
                                @foreach($counselors as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                          
            <div class="col-md-6 mb-3">
                <label for="address" class="small font-weight-bold text-gray-600">Address / Village</label>
                <i class="fas fa-map-marker-alt"></i>
                <input type="text"
                       class="form-control bg-light border-0"
                       id="address"
                       name="address"
                       value="{{ old('address') }}"
                       placeholder="Enter address or village">
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
                <button type="button" class="btn btn-primary shadow-sm" onclick="document.getElementById('addEnquiryForm').submit()">Save</button>
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
                <form action="{{ route('admin.enquiries.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
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
                <button type="button" class="btn btn-primary btn-block" onclick="document.getElementById('importForm').submit()">Upload</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(document).ready(function() {
    // --- Live Search Logic ---
    const searchInput = document.getElementById('liveSearchInput');
    const resultsDropdown = document.getElementById('liveSearchResults');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value;

        if (query.length < 2) {
            resultsDropdown.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            // Assuming route is defined as: Route::get('enquiries/ajax-search', ...)->name('enquiries.ajax-search');
            fetch(`{{ route('admin.enquiries.ajax-search') }}?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    resultsDropdown.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'search-item';
                            div.innerHTML = `
                                <div class="search-avatar">${item.avatar}</div>
                                <div>
                                    <div class="font-weight-bold text-dark">${item.name}</div>
                                    <div class="small text-muted">${item.phone} • <span class="badge badge-light border">${item.status}</span></div>
                                </div>
                            `;
                            div.onclick = () => {
                                openEnquiryModal(item.id);
                                resultsDropdown.style.display = 'none';
                                searchInput.value = ''; 
                            };
                            resultsDropdown.appendChild(div);
                        });
                        resultsDropdown.style.display = 'block';
                    } else {
                        resultsDropdown.innerHTML = '<div class="p-3 text-muted small text-center">No results found</div>';
                        resultsDropdown.style.display = 'block';
                    }
                })
                .catch(err => console.error('Search Error:', err));
        }, 300);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
            resultsDropdown.style.display = 'none';
        }
    });

    // --- Checkbox Logic ---
    $('#selectAll').on('change', function() {
        $('.enquiry-checkbox').prop('checked', this.checked);
        toggleBulkActions();
    });
    $('.enquiry-checkbox').on('change', toggleBulkActions);

    // --- Source/Referral Toggle ---
    $('#sourceSelect').on('change', function() {
        const val = $(this).val();
        const show = ['Agent', 'Referrals', 'Walk-in', 'Other'].includes(val);
        $('#referralWrapper').toggle(show);
        if(show) $('#referralLabel').text(val === 'Agent' ? 'Agent Name' : (val === 'Other' ? 'Specify' : 'Referral Name'));
    });
});

function toggleBulkActions() {
    const count = $('.enquiry-checkbox:checked').length;
    $('#bulkActionBar').toggle(count > 0);
}

function bulkDelete() {
    const ids = $('.enquiry-checkbox:checked').map((_, el) => $(el).val()).get();
    if(ids.length === 0 || !confirm(`Delete ${ids.length} items?`)) return;
    
    $.post('{{ route("admin.enquiries.bulk-delete") }}', 
        { _token: '{{ csrf_token() }}', ids: ids }, 
        () => window.location.reload()
    );
}

function bulkAssign() {
    const ids = $('.enquiry-checkbox:checked').map((_, el) => $(el).val()).get();
    const user = $('#bulkAssignUser').val();
    if(ids.length === 0 || !user) return alert('Select items and user');

    $.post('{{ route("admin.enquiries.bulk-assign") }}', 
        { _token: '{{ csrf_token() }}', ids: ids, assigned_to_user_id: user }, 
        () => window.location.reload()
    );
}

function openEnquiryModal(id) {
    $('#enquiryDetailsModal').modal('show');
    $('#enquiryModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
    
    $.get("{{ url('admin/enquiries') }}/" + id, function(res) {
        $('#enquiryModalBody').html(res);
    }).fail(function() {
        $('#enquiryModalBody').html('<div class="text-center text-danger py-5">Failed to load data.</div>');
    });
}

function quickUpdate(id, field, value) {
    document.body.style.cursor = 'wait';
    $.ajax({
        url: "{{ url('admin/enquiries') }}/" + id + "/quick-update",
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: { field: field, value: value },
        success: function(res) { 
            document.body.style.cursor = 'default';
            // Reload if status changed to reflect visual changes, or use toast
            if(field === 'status' || res.new_status) window.location.reload();
        },
        error: function() { 
            document.body.style.cursor = 'default';
            alert('Update failed.'); 
        }
    });
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
    
    checkTimer = setTimeout(() => {
        $.get(`{{ route('admin.enquiries.check-mobile') }}?phone=${phone}`, function(data) {
            if (data.status === 'error') {
                feedback.text(data.message).removeClass('text-info').addClass('text-danger');
                input.addClass('is-invalid');
            } else {
                feedback.text('Available').removeClass('text-info').addClass('text-success');
                input.removeClass('is-invalid').addClass('is-valid');
                setTimeout(() => feedback.fadeOut(), 2000);
            }
        });
    }, 500);
}
</script>
@endpush