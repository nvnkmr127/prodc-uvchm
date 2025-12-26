@extends('layouts.theme')

@section('title', 'Batch Command Center')

@push('styles')
    <style>
        /* Custom Stats Cards */
        .stats-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .stats-icon-bg {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 5rem;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        /* Modern Table */
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .table thead th {
            border-top: none;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            color: #b7b9cc;
            background-color: #f8f9fc;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 0.95rem;
        }

        /* User Avatar Stack */
        .avatar-stack {
            display: flex;
            align-items: center;
        }

        .avatar-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #4e73df;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            border: 2px solid white;
            margin-left: -10px;
        }

        .avatar-circle:first-child {
            margin-left: 0;
        }

        /* Badges */
        .badge-soft-primary {
            color: #4e73df;
            background-color: rgba(78, 115, 223, 0.1);
            font-weight: 600;
            padding: 0.5em 0.8em;
        }

        .badge-soft-success {
            color: #1cc88a;
            background-color: rgba(28, 200, 138, 0.1);
            font-weight: 600;
            padding: 0.5em 0.8em;
        }

        .badge-soft-info {
            color: #36b9cc;
            background-color: rgba(54, 185, 204, 0.1);
            font-weight: 600;
            padding: 0.5em 0.8em;
        }

        .badge-soft-warning {
            color: #f6c23e;
            background-color: rgba(246, 194, 62, 0.1);
            font-weight: 600;
            padding: 0.5em 0.8em;
        }
    </style>
@endpush

@section('content')

    {{-- 1. Calculations --}}
    @php
        $totalBatches = $batches->count();
        $totalStudents = $batches->sum('students_count');
        $internshipBatches = $batches->where('is_on_internship', true)->count();
        $uniqueCourses = $batches->unique('course_id')->count();
    @endphp

    {{-- 2. Header & Quick Actions --}}
    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Batch Command Center</h1>
            <p class="mb-0 text-muted small mt-1">Manage academic batches, enrollments, and statuses.</p>
        </div>
        <div>
            <button class="btn btn-primary shadow-sm rounded-pill px-4" data-toggle="modal" data-target="#addBatchModal">
                <i class="fas fa-plus fa-sm text-white-50 mr-2"></i>Create New Batch
            </button>
        </div>
    </div>

    {{-- 3. Stats Dashboard --}}
    <div class="row mb-4">
        {{-- Total Batches --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center position-relative">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Batches</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalBatches }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="p-3 bg-primary text-white rounded-circle shadow-sm">
                                <i class="fas fa-layer-group fa-lg"></i>
                            </div>
                        </div>
                        <i class="fas fa-layer-group stats-icon-bg text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Students --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center position-relative">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalStudents) }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="p-3 bg-success text-white rounded-circle shadow-sm">
                                <i class="fas fa-user-graduate fa-lg"></i>
                            </div>
                        </div>
                        <i class="fas fa-user-graduate stats-icon-bg text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- On Internship --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center position-relative">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">On Internship</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $internshipBatches }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="p-3 bg-info text-white rounded-circle shadow-sm">
                                <i class="fas fa-briefcase fa-lg"></i>
                            </div>
                        </div>
                        <i class="fas fa-briefcase stats-icon-bg text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Courses --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center position-relative">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Courses</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $uniqueCourses }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="p-3 bg-warning text-white rounded-circle shadow-sm">
                                <i class="fas fa-book-open fa-lg"></i>
                            </div>
                        </div>
                        <i class="fas fa-book-open stats-icon-bg text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Session Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-left-success" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-left-danger" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
        </div>
    @endif

    {{-- 4. Main Batches Table --}}
    <div class="card table-card mb-4">
        <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">All Batches List</h6>

            {{-- Filter Form --}}
            <form action="{{ route('admin.batches.index') }}" method="GET" class="form-inline">
                <div class="input-group">
                    <select name="course_id" class="custom-select custom-select-sm bg-light border-0 small"
                        onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @if(request('course_id'))
                        <div class="input-group-append">
                            <a href="{{ route('admin.batches.index') }}" class="btn btn-sm btn-secondary" title="Clear Filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th class="pl-4">Batch Info</th>
                            <th>Status/Type</th>
                            <th>Students</th>
                            <th>Timeline</th>
                            <th class="text-right pr-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            <tr>
                                <td class="pl-4">
                                    <div class="font-weight-bold text-gray-800">{{ $batch->name }}</div>
                                    <div class="small text-muted">{{ $batch->course->name }}</div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input toggle-internship" 
                                            id="internship_switch_{{ $batch->id }}" 
                                            data-id="{{ $batch->id }}"
                                            data-url="{{ route('admin.batches.toggleInternship', $batch) }}"
                                            {{ $batch->is_on_internship ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="internship_switch_{{ $batch->id }}">
                                            @if($batch->is_on_internship)
                                                <span class="badge badge-soft-info badge-pill ml-2" id="badge_text_{{ $batch->id }}">
                                                    <i class="fas fa-briefcase mr-1"></i> On Internship
                                                </span>
                                            @else
                                                <span class="badge badge-soft-success badge-pill ml-2" id="badge_text_{{ $batch->id }}">
                                                    <i class="fas fa-check-circle mr-1"></i> In College
                                                </span>
                                            @endif
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-light text-primary border-primary">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <span class="ml-2 font-weight-bold">{{ $batch->students_count }}</span>
                                        <span class="small text-muted ml-1">enrolled</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="text-success"><i class="fas fa-play-circle mr-1"></i>
                                            {{ \Carbon\Carbon::parse($batch->start_date)->format('M d, Y') }}</div>
                                        <div class="text-danger mt-1"><i class="fas fa-flag-checkered mr-1"></i>
                                            {{ \Carbon\Carbon::parse($batch->end_date)->format('M d, Y') }}</div>
                                    </div>
                                </td>
                                <td class="text-right pr-4">
                                    <div class="dropdown no-arrow">
                                        <button class="btn btn-light btn-sm rounded-circle shadow-sm dropdown-toggle"
                                            type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm text-gray-400"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuButton">
                                            <div class="dropdown-header">Batch Actions:</div>

                                            <a class="dropdown-item edit-batch-btn" href="#" data-toggle="modal"
                                                data-target="#editBatchModal" data-id="{{ $batch->id }}"
                                                data-name="{{ $batch->name }}" data-course-id="{{ $batch->course_id }}"
                                                data-academic-year-id="{{ $batch->academic_year_id }}"
                                                data-start-date="{{ \Carbon\Carbon::parse($batch->start_date)->format('Y-m-d') }}"
                                                data-end-date="{{ \Carbon\Carbon::parse($batch->end_date)->format('Y-m-d') }}"
                                                data-status="{{ $batch->status }}"
                                                data-is-on-internship="{{ $batch->is_on_internship ? 1 : 0 }}"
                                                data-action="{{ route('admin.batches.update', $batch) }}">
                                                <i class="fas fa-edit fa-fw mr-2 text-warning"></i> Edit Details
                                            </a>

                                            <a class="dropdown-item" href="{{ route('admin.batches.manageStudents', $batch) }}">
                                                <i class="fas fa-users-cog fa-fw mr-2 text-info"></i> Manage Students
                                            </a>

                                            <div class="dropdown-divider"></div>

                                            <form action="{{ route('admin.batches.graduate', $batch) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item"
                                                    onclick="return confirm('Are you sure you want to graduate all active students in this batch?')">
                                                    <i class="fas fa-graduation-cap fa-fw mr-2 text-success"></i> Graduate Batch
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.batches.destroy', $batch) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this batch? This can only be done if no students are assigned.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash-alt fa-fw mr-2"></i> Delete Batch
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-gray-500 mb-3">
                                        <i class="fas fa-layer-group fa-3x mb-3 text-gray-300"></i>
                                        <p class="mb-0">No batches found matching your criteria.</p>
                                        <button class="btn btn-sm btn-primary mt-3" data-toggle="modal"
                                            data-target="#addBatchModal">
                                            Create First Batch
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Batch Modal -->
    <div class="modal fade" id="addBatchModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-plus-circle mr-2"></i>Add New Batch
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <form action="{{ route('admin.batches.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700">Academic Year <span
                                    class="text-danger">*</span></label>
                            <select name="academic_year_id" class="form-control" required>
                                <option value="">-- Select Year --</option>
                                @if(isset($academicYears))
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ (session('selected_academic_year_id', $year->is_current ? $year->id : null) == $year->id) ? 'selected' : '' }}>
                                            {{ $year->name }} {{ $year->is_current ? '(Current)' : '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700">Course <span class="text-danger">*</span></label>
                            <select name="course_id" class="form-control" required>
                                <option value="">-- Select Course --</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700">Batch Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Spring 2025 Intake"
                                required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-gray-700">Start Date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-gray-700">End Date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="text-right mt-4">
                            <button type="button" class="btn btn-light text-secondary font-weight-bold mr-2"
                                data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary font-weight-bold px-4">Create Batch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Batch Modal -->
    <div class="modal fade" id="editBatchModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-edit mr-2"></i>Edit Batch
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <form id="editBatchForm" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700">Academic Year</label>
                            <select name="academic_year_id" id="edit_academic_year_id" class="form-control" required>
                                <option value="">-- Select Year --</option>
                                @if(isset($academicYears))
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}">
                                            {{ $year->name }} {{ $year->is_current ? '(Current)' : '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700">Course</label>
                            <select name="course_id" id="edit_course_id" class="form-control" required>
                                <option value="">-- Select Course --</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700">Batch Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-gray-700">Start Date</label>
                                <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-gray-700">End Date</label>
                                <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700">Status</label>
                            <select name="status" id="edit_status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <div class="text-right mt-4">
                            <button type="button" class="btn btn-light text-secondary font-weight-bold mr-2"
                                data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning font-weight-bold px-4 text-white">Save
                                Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- DataTables JS --}}
    <script>
        $(document).ready(function () {
            // Initialize DataTable
            $('#dataTable').DataTable({
                "order": [], // Disable initial sort to keep card layout clean
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search batches...",
                    "emptyTable": "No batches found",
                },
                "dom": '<"row"<"col-md-6"l><"col-md-6"f>>rtip',
                "columnDefs": [
                    { "orderable": false, "targets": 4 } // Disable sorting on actions column
                ]
            });

            // AJAX Toggle for Internship
            $('body').on('change', '.toggle-internship', function () {
                const checkbox = $(this);
                const url = checkbox.data('url');
                const id = checkbox.data('id');
                const badgeContainer = $('#badge_text_' + id);
                
                // Optimistically update UI? Or wait? Let's wait for safety, but disable input
                checkbox.prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        checkbox.prop('disabled', false);
                        if (response.success) {
                            // Update Badge
                            if (response.is_on_internship) {
                                badgeContainer
                                    .removeClass('badge-soft-success')
                                    .addClass('badge-soft-info')
                                    .html('<i class="fas fa-briefcase mr-1"></i> On Internship');
                            } else {
                                badgeContainer
                                    .removeClass('badge-soft-info')
                                    .addClass('badge-soft-success')
                                    .html('<i class="fas fa-check-circle mr-1"></i> In College');
                            }
                            // Optional: Toast notification
                            // toastr.success(response.message); 
                        }
                    },
                    error: function (xhr) {
                        checkbox.prop('disabled', false);
                        checkbox.prop('checked', !checkbox.prop('checked')); // Revert
                        alert('Something went wrong. Please try again.');
                    }
                });
            });

            // Script to populate the edit modal with data from the clicked row
            $('body').on('click', '.edit-batch-btn', function () {
                const button = $(this);
                const id = button.data('id');
                const name = button.data('name');
                const courseId = button.data('course-id');
                const startDate = button.data('start-date');
                const endDate = button.data('end-date');
                const action = button.data('action');

                const status = button.data('status');

                const academicYearId = $(this).data('academic-year-id');
                const modal = $('#editBatchModal');
                modal.find('form').attr('action', action);
                modal.find('#edit_name').val(name);
                modal.find('#edit_course_id').val(courseId);
                modal.find('#edit_academic_year_id').val(academicYearId);
                modal.find('#edit_start_date').val(startDate);
                modal.find('#edit_end_date').val(endDate);
                modal.find('#edit_status').val(status);
            });
        });
    </script>
@endpush