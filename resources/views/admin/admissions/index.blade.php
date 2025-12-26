@extends('layouts.theme')
@section('title', 'Manage Admissions')

@push('styles')
{{-- DataTables CSS --}}
@push('styles')
    {{-- DataTables CSS --}}
    <style>
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: .75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
        }

        .status-badge.pending {
            background-color: #f6c23e;
        }

        .status-badge.approved {
            background-color: #1cc88a;
        }

        .status-badge.rejected {
            background-color: #e74a3b;
        }
    </style>
@endpush

@section('content')

    {{-- 1. Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Admission Applications</h1>
        {{-- NEW: Share Form Button --}}
        <a href="#" class="btn btn-sm btn-outline-primary shadow-sm" data-toggle="modal" data-target="#shareFormModal">
            <i class="fas fa-share-alt fa-sm"></i> Share Form Link
        </a>
    </div>


    {{-- Session Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button"
                class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button"
                class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
    @endif

    {{-- Applications List Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Applications</h6>
        </div>
        <div class="card-body">
            {{-- Filter Form --}}
            <div class="mb-4">
                <form action="{{ route('admin.admissions.index') }}" method="GET" class="form-inline">
                    <div class="form-group mr-2">
                        <label for="course_filter" class="sr-only">Course</label>
                        <select name="course_id" id="course_filter" class="form-control">
                            <option value="">-- Filter by Course --</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <label for="status_filter" class="sr-only">Status</label>
                        <select name="status" id="status_filter" class="form-control">
                            <option value="">-- Filter by Status --</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.admissions.index') }}" class="btn btn-secondary ml-2">Reset</a>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Applicant Name</th>
                            <th>Email & Phone</th>
                            <th>Course Applied For</th>
                            <th>Application Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($admissions as $admission)
                            <tr>
                                <td><strong>{{ $admission->full_name }}</strong></td>
                                <td>
                                    <div>{{ $admission->email }}</div>
                                    <div class="small text-muted">{{ $admission->phone_number }}</div>
                                </td>
                                <td>{{ $admission->course->name }}</td>
                                <td>{{ $admission->created_at->format('d M, Y') }}</td>
                                <td class="text-center">
                                    <span class="status-badge {{ $admission->status }}">{{ ucfirst($admission->status) }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.admissions.show', $admission) }}" class="btn btn-info btn-sm"
                                        title="View Details"><i class="fas fa-eye"></i></a>
                                    @if($admission->status == 'pending')
                                        <form action="{{ route('admin.admissions.approve', $admission) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to approve this admission? This will create a student profile and fee plan.');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" title="Approve"><i
                                                    class="fas fa-check"></i></button>
                                        </form>
                                        <form action="{{ route('admin.admissions.reject', $admission) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to reject this admission?');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm" title="Reject"><i
                                                    class="fas fa-times"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No admission applications found matching your criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- NEW: Share Form Modal --}}
    <div class="modal fade" id="shareFormModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Admission Form</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">
                    <p>Share the link below with prospective students to allow them to apply online.</p>

                    <!-- Direct Link -->
                    <div class="form-group">
                        <label><strong>Direct Link</strong></label>
                        <div class="input-group">
                            <input type="text" id="formLink" class="form-control"
                                value="{{ route('enquiry.public.create') }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="copyLinkBtn">Copy</button>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Embed Code -->
                    <div class="form-group">
                        <label><strong>Embed on Your Website</strong></label>
                        <p class="small text-muted">Copy and paste this HTML code to show the form directly on another
                            webpage.</p>
                        <textarea id="embedCode" class="form-control" rows="4"
                            readonly><iframe src="{{ route('enquiry.public.create') }}" style="width:100%; height:800px; border:none;" title="Admission Form"></iframe></textarea>
                        <button class="btn btn-outline-secondary btn-sm mt-2" type="button" id="copyEmbedBtn">Copy Embed
                            Code</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- DataTables JS --}}
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            // Initialize DataTable
            $('#dataTable').DataTable({
                "order": [[3, "desc"]], // Sort by Application Date descending by default
                "columnDefs": [
                    { "orderable": false, "targets": [5] } // Disable sorting on actions column
                ]
            });

            // Copy to clipboard function
            function copyToClipboard(elementId, buttonId) {
                const textToCopy = document.getElementById(elementId);
                textToCopy.select();
                document.execCommand('copy');

                const originalText = $('#' + buttonId).text();
                $('#' + buttonId).text('Copied!');
                setTimeout(function () {
                    $('#' + buttonId).text(originalText);
                }, 2000);
            }

            $('#copyLinkBtn').on('click', function () {
                copyToClipboard('formLink', 'copyLinkBtn');
            });

            $('#copyEmbedBtn').on('click', function () {
                copyToClipboard('embedCode', 'copyEmbedBtn');
            });
        });
    </script>
@endpush