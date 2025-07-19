<!-- index.blade.php - Fixed and Enhanced -->
@extends('layouts.theme')
@section('title', 'Enquiry Hub')

@push('styles')
    <link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .modern-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: white;
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .gradient-card {
            background: var(--primary-gradient);
            color: white;
            border: none;
        }

        .gradient-card.warning {
            background: var(--warning-gradient);
        }

        .gradient-card.success {
            background: var(--success-gradient);
        }

        .gradient-card.info {
            background: var(--info-gradient);
        }

        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .status-badge {
            padding: 0.4em 0.8em;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .status-new { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .status-contacted { 
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }
        
        .status-interested { 
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }
        
        .status-follow-up { 
            background: linear-gradient(135deg, #ffecd2, #fcb69f);
            color: #8B4513;
        }
        
        .status-admitted { 
            background: linear-gradient(135deg, #a8edea, #fed6e3);
            color: #2d5a27;
        }
        
        .status-not-interested { 
            background: linear-gradient(135deg, #d299c2, #fef9d7);
            color: #8B0000;
        }

        .table-modern {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .table-modern thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .table-modern th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.75rem;
        }

        .table-modern td {
            vertical-align: middle;
            border-color: #f1f3f4;
        }

        .table-modern tbody tr:hover {
            background-color: #f8f9fc;
            transform: scale(1.01);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .btn-modern {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .btn-primary.btn-modern {
            background: var(--primary-gradient);
        }

        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }

        .follow-up-highlight {
            background: linear-gradient(135deg, #fff3cd, #ffeeba) !important;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }

        .modal-modern .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-modern .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .student-info {
            padding: 0.5rem 0;
        }

        .student-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .student-meta {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }

        .priority-high {
            border-left: 4px solid #e74c3c;
        }

        .priority-medium {
            border-left: 4px solid #f39c12;
        }

        .priority-low {
            border-left: 4px solid #27ae60;
        }
    </style>
@endpush

@section('content')
<div class="page-header modern-card">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between">
            <div>
                <h1 class="h2 mb-0">🎯 Enquiries Command Center</h1>
                <p class="mb-0 mt-2 opacity-75">Manage and track all student enquiries efficiently</p>
            </div>
            <button class="btn btn-light btn-modern" data-toggle="modal" data-target="#addEnquiryModal">
                <i class="fas fa-plus fa-sm"></i> Add New Enquiry
            </button>
        </div>
    </div>
</div>

<!-- Enhanced Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card modern-card gradient-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">New Enquiries</div>
                        <div class="h3 mb-0 font-weight-bold">{{ $newEnquiriesCount }}</div>
                        <div class="small opacity-75 mt-1">
                            <i class="fas fa-arrow-up"></i> Needs attention
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card modern-card gradient-card warning">
            <div class="card-body p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Follow-ups Today</div>
                        <div class="h3 mb-0 font-weight-bold">{{ $todaysFollowUpsCount }}</div>
                        <div class="small opacity-75 mt-1">
                            <i class="fas fa-clock"></i> Due today
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card modern-card gradient-card success">
            <div class="card-body p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Converted (7 Days)</div>
                        <div class="h3 mb-0 font-weight-bold">{{ $recentlyAdmittedCount }}</div>
                        <div class="small opacity-75 mt-1">
                            <i class="fas fa-chart-line"></i> Great progress
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card modern-card gradient-card info">
            <div class="card-body p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 opacity-75">Total Active</div>
                        <div class="h3 mb-0 font-weight-bold">{{ $enquiries->count() }}</div>
                        <div class="small opacity-75 mt-1">
                            <i class="fas fa-users"></i> In pipeline
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Enquiries Table -->
<div class="card modern-card mb-4">
    <div class="card-header py-3" style="background: var(--primary-gradient); color: white; border-radius: 15px 15px 0 0;">
        <h6 class="m-0 font-weight-bold">📋 All Active Enquiries</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-modern mb-0" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Student Details</th>
                        <th>Contact</th>
                        <th>Course Interest</th>
                        <th>Assigned To</th>
                        <th>Next Follow-up</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enquiries as $enquiry)
                        <tr class="{{ $enquiry->next_follow_up_date == now()->toDateString() ? 'follow-up-highlight' : '' }}">
                            <td>
                                <div class="student-info">
                                    <div class="student-name">{{ $enquiry->student_name }}</div>
                                    <div class="student-meta">
                                        <i class="fas fa-tag fa-sm"></i> {{ $enquiry->source ?? 'No source' }}
                                        @if($enquiry->next_follow_up_date == now()->toDateString())
                                            <span class="badge badge-warning ml-2">
                                                <i class="fas fa-bell"></i> Due Today
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <i class="fas fa-phone fa-sm text-muted"></i> {{ $enquiry->phone_number }}
                                </div>
                                @if($enquiry->email)
                                    <div class="small text-muted mt-1">
                                        <i class="fas fa-envelope fa-sm"></i> {{ $enquiry->email }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-light">
                                    {{ $enquiry->course->name ?? 'Not Specified' }}
                                </span>
                            </td>
                            <td>
                                @if($enquiry->assignedTo)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center mr-2">
                                            <span class="text-white font-weight-bold">
                                                {{ substr($enquiry->assignedTo->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <span>{{ $enquiry->assignedTo->name }}</span>
                                    </div>
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-user-slash"></i> Unassigned
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($enquiry->next_follow_up_date)
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-alt fa-sm text-muted mr-2"></i>
                                        <span class="{{ $enquiry->next_follow_up_date <= now()->toDateString() ? 'text-danger font-weight-bold' : '' }}">
                                            {{ \Carbon\Carbon::parse($enquiry->next_follow_up_date)->format('d M, Y') }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-muted small">
                                        <i class="fas fa-calendar-times"></i> Not Set
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="status-badge status-{{ Str::slug($enquiry->status) }}">
                                    {{ $enquiry->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.enquiries.edit', $enquiry) }}" 
                                   class="btn btn-info btn-action" 
                                   title="Manage Enquiry"
                                   data-toggle="tooltip">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-success btn-action" 
                                        title="Quick Call"
                                        data-toggle="tooltip"
                                        onclick="window.open('tel:{{ $enquiry->phone_number }}')">
                                    <i class="fas fa-phone"></i>
                                </button>
                                <button class="btn btn-warning btn-action" 
                                        title="Send WhatsApp"
                                        data-toggle="tooltip"
                                        onclick="window.open('https://wa.me/{{ $enquiry->phone_number }}')">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <h5>No active enquiries found</h5>
                                    <p>Click "Add New Enquiry" to get started</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Enhanced Add Enquiry Modal -->
<div class="modal fade modal-modern" id="addEnquiryModal" tabindex="-1" role="dialog" aria-labelledby="addEnquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEnquiryModalLabel">
                    <i class="fas fa-user-plus"></i> Add New Enquiry
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @include('admin.enquiries.create')
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
    // Initialize DataTable with enhanced features
    $('#dataTable').DataTable({
        "order": [[ 4, "asc" ]], // Sort by Next Follow Up Date ascending
        "pageLength": 25,
        "responsive": true,
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "language": {
            "search": "🔍 Search enquiries:",
            "lengthMenu": "Show _MENU_ enquiries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ enquiries",
            "infoEmpty": "No enquiries found",
            "infoFiltered": "(filtered from _MAX_ total enquiries)"
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Auto-refresh every 5 minutes to check for new enquiries
    setInterval(function() {
        // You can add AJAX call here to refresh data
        console.log('Checking for new enquiries...');
    }, 300000); // 5 minutes

    // Add row click handler for better UX
    $('#dataTable tbody').on('click', 'tr', function() {
        if (!$(this).find('td').hasClass('no-data')) {
            $(this).toggleClass('table-active');
        }
    });

    // Add keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+N to add new enquiry
        if (e.ctrlKey && e.keyCode === 78) {
            e.preventDefault();
            $('#addEnquiryModal').modal('show');
        }
    });
});
</script>
@endpush