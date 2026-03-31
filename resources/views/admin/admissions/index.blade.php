@extends('layouts.theme')
@section('title', 'Admissions Hub')

@push('styles')
    <style>
        /* --- Modern Admission Design System --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card-premium {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 1.25rem;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .stat-card-premium:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .stat-card-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .card-pending::before { background: #f59e0b; }
        .card-approved::before { background: #10b981; }
        .card-rejected::before { background: #ef4444; }
        .card-total::before { background: #4f46e5; }

        .stat-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }

        /* --- Custom Table Styling --- */
        .table-premium {
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }

        .table-premium thead th {
            border: none;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 1.25rem;
        }

        .table-premium tbody tr {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            border-radius: 1rem;
            transition: all 0.2s ease;
        }

        .table-premium tbody tr:hover {
            transform: scale(1.005);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            z-index: 10;
        }

        .table-premium tbody td {
            padding: 1.25rem;
            border: none;
            vertical-align: middle;
        }

        .table-premium tbody td:first-child { border-radius: 1rem 0 0 1rem; }
        .table-premium tbody td:last-child { border-radius: 0 1rem 1rem 0; }

        /* --- Modern Badges --- */
        .badge-premium {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending { background: #fffbeb; color: #b45309; }
        .badge-approved { background: #ecfdf5; color: #047857; }
        .badge-rejected { background: #fef2f2; color: #b91c1c; }

        /* --- Filter Area --- */
        .filter-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 1.25rem;
            padding: 1.25rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-modern {
            padding: 0.6rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Header Section --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 font-weight-bold text-gray-800">Admissions Hub</h1>
            <p class="text-muted small mb-0">Manage student applications and registration workflows</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-modern shadow-sm" data-toggle="modal" data-target="#shareFormModal">
                <i class="fas fa-share-alt mr-2"></i> Share Application Form
            </button>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="stats-grid">
        <div class="stat-card-premium card-pending">
            <div class="stat-label">Pending Review</div>
            <div class="stat-value text-warning">{{ $totalCounts['pending'] }}</div>
        </div>
        <div class="stat-card-premium card-approved">
            <div class="stat-label">Total Approved</div>
            <div class="stat-value text-success">{{ $totalCounts['approved'] }}</div>
        </div>
        <div class="stat-card-premium card-rejected">
            <div class="stat-label">Total Rejected</div>
            <div class="stat-value text-danger">{{ $totalCounts['rejected'] }}</div>
        </div>
        <div class="stat-card-premium card-total">
            <div class="stat-label">All Applications</div>
            <div class="stat-value text-primary">{{ $totalCounts['total'] }}</div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="filter-panel shadow-sm">
        <form action="{{ route('admin.admissions.index') }}" method="GET" class="row align-items-end g-3">
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <label class="small font-weight-bold text-muted mb-2 d-block">Search Applicant</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control border-0 bg-white" placeholder="Name, Email, Phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                <label class="small font-weight-bold text-muted mb-2 d-block">Course Filter</label>
                <select name="course_id" class="form-control border-0 bg-white shadow-none">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                <label class="small font-weight-bold text-muted mb-2 d-block">Status</label>
                <select name="status" class="form-control border-0 bg-white shadow-none">
                    <option value="">Any Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6 text-lg-right">
                <button type="submit" class="btn btn-primary btn-modern shadow-sm mr-2">Apply Filters</button>
                <a href="{{ route('admin.admissions.index') }}" class="btn btn-light btn-modern shadow-sm"><i class="fas fa-sync"></i></a>
            </div>
        </form>
    </div>

    {{-- Main List Section --}}
    <div class="table-responsive">
        <table class="table table-premium" id="admissionsTable">
            <thead>
                <tr>
                    <th width="25%">Applicant Profile</th>
                    <th width="20%">Course Applied</th>
                    <th width="15%">Submission Date</th>
                    <th width="15%" class="text-center">Status</th>
                    <th width="25%" class="text-right">Management</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admissions as $admission)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-3" style="width: 45px; height: 45px; flex-shrink: 0;">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-gray-800">{{ $admission->full_name }}</div>
                                    <div class="small text-muted">{{ $admission->email }}</div>
                                    <div class="small text-muted">{{ $admission->phone_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="font-weight-bold text-primary small">{{ $admission->course->name }}</span>
                        </td>
                        <td>
                            <div class="small font-weight-bold">{{ $admission->created_at->format('M d, Y') }}</div>
                            <div class="small text-muted">{{ $admission->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge-premium badge-{{ $admission->status }}">{{ strtoupper($admission->status) }}</span>
                        </td>
                        <td class="text-right">
                            <div class="btn-group shadow-sm" style="border-radius: 0.5rem; overflow: hidden;">
                                <a href="{{ route('admin.admissions.show', $admission) }}" class="btn btn-white btn-sm px-3" title="View Details">
                                    <i class="fas fa-eye text-info"></i>
                                </a>
                                @if($admission->status == 'pending')
                                    <button type="button" class="btn btn-white btn-sm px-3" title="Approve" onclick="approveAdmission({{ $admission->id }}, '{{ $admission->full_name }}')">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </button>
                                    <button type="button" class="btn btn-white btn-sm px-3" title="Reject" onclick="rejectAdmission({{ $admission->id }}, '{{ $admission->full_name }}')">
                                        <i class="fas fa-times-circle text-danger"></i>
                                    </button>
                                @endif
                                <a href="{{ route('admin.admissions.edit', $admission) }}" class="btn btn-white btn-sm px-3" title="Edit">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                            </div>

                            {{-- Hidden Action Forms --}}
                            <form id="approve-form-{{ $admission->id }}" action="{{ route('admin.admissions.approve', $admission) }}" method="POST" style="display: none;">@csrf<input type="hidden" name="create_student" value="1"></form>
                            <form id="reject-form-{{ $admission->id }}" action="{{ route('admin.admissions.reject', $admission) }}" method="POST" style="display: none;">@csrf<input type="hidden" name="rejection_reason" id="reject-reason-{{ $admission->id }}"></form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <img src="https://cdni.iconscout.com/illustration/premium/thumb/no-data-found-8867371-7276247.png" style="width: 200px; opacity: 0.6;">
                            <p class="text-muted mt-3">No admission applications found matching your criteria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $admissions->links() }}
    </div>
</div>

{{-- Share Form Modal --}}
<div class="modal fade" id="shareFormModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem;">
            <div class="modal-header border-0 pt-4 px-4 bg-light">
                <h5 class="modal-title font-weight-bold">Share Application Form</h5>
                <button class="close" type="button" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 small mb-4" style="border-radius: 1rem;">
                    Share this link with prospective students so they can apply directly through the portal.
                </div>
                
                <label class="small font-weight-bold text-muted mb-2">Direct Access Link</label>
                <div class="input-group mb-4">
                    <input type="text" id="formLink" class="form-control bg-light border-0 py-4" 
                        value="{{ route('enquiry.public.create') }}" readonly style="border-radius: 0.75rem 0 0 0.75rem;">
                    <div class="input-group-append">
                        <button class="btn btn-primary px-4" type="button" id="copyLinkBtn" style="border-radius: 0 0.75rem 0.75rem 0;">Copy</button>
                    </div>
                </div>

                <label class="small font-weight-bold text-muted mb-2">Website Embed Snippet</label>
                <textarea id="embedCode" class="form-control bg-light border-0 mb-3" rows="3" readonly style="border-radius: 0.75rem;"><iframe src="{{ route('enquiry.public.create') }}" style="width:100%; height:800px; border:none;" title="Admission Form"></iframe></textarea>
                <button class="btn btn-outline-primary btn-block btn-modern" type="button" id="copyEmbedBtn">Copy Embed Code</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function approveAdmission(id, name) {
        if(confirm(`Are you sure you want to approve ${name}? This will automatically create a student profile and active enrollment.`)) {
            document.getElementById('approve-form-' + id).submit();
        }
    }

    function rejectAdmission(id, name) {
        const reason = prompt(`Enter reason for rejecting ${name}'s application:`);
        if(reason && reason.trim() !== "") {
            document.getElementById('reject-reason-' + id).value = reason;
            document.getElementById('reject-form-' + id).submit();
        } else if (reason !== null) {
            alert("A rejection reason is required.");
        }
    }

    $(document).ready(function() {
        // Copy functionality
        function copyToClipboard(inputSelector, buttonSelector) {
            $(inputSelector).select();
            document.execCommand('copy');
            const originalText = $(buttonSelector).text();
            $(buttonSelector).text('Copied!').addClass('btn-success').removeClass('btn-primary btn-outline-primary');
            setTimeout(() => {
                $(buttonSelector).text(originalText).removeClass('btn-success').addClass('btn-primary');
            }, 2000);
        }

        $('#copyLinkBtn').on('click', () => copyToClipboard('#formLink', '#copyLinkBtn'));
        $('#copyEmbedBtn').on('click', () => copyToClipboard('#embedCode', '#copyEmbedBtn'));
    });
</script>
@endpush