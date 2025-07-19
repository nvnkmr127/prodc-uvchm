@extends('layouts.theme')

@section('title', 'Batch Command Center')

@push('styles')
{{-- DataTables CSS --}}
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .action-btn-group .btn {
        margin-right: 5px;
    }
    .card-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
</style>
@endpush


@section('content')

{{-- 1. Page Header --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Batch Command Center</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addBatchModal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Batch
    </button>
</div>

{{-- Session Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
@endif

{{-- 2. Main Batches Card --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 card-header-flex">
        <h6 class="m-0 font-weight-bold text-primary">All Batches</h6>
        {{-- Filter Form --}}
        <form action="{{ route('admin.batches.index') }}" method="GET" class="form-inline">
            <div class="form-group mr-2">
                <label for="course_filter" class="sr-only">Filter by Course</label>
                <select name="course_id" id="course_filter" class="form-control form-control-sm">
                    <option value="">-- Filter by Course --</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="{{ route('admin.batches.index') }}" class="btn btn-secondary btn-sm ml-2">Reset</a>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Batch Name</th>
                        <th>Course</th>
                        <th class="text-center">Students</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <td><strong>{{ $batch->name }}</strong></td>
                            <td>{{ $batch->course->name }}</td>
                            <td class="text-center">{{ $batch->students_count }}</td>
                            <td>{{ \Carbon\Carbon::parse($batch->start_date)->format('d M, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($batch->end_date)->format('d M, Y') }}</td>
                            <td class="text-center action-btn-group">
                                <div class="dropdown no-arrow d-inline-block">
                                    <a class="btn btn-sm btn-secondary dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                        <a class="dropdown-item edit-batch-btn" href="#" data-toggle="modal" data-target="#editBatchModal" 
                                           data-id="{{ $batch->id }}" 
                                           data-name="{{ $batch->name }}" 
                                           data-course-id="{{ $batch->course_id }}"
                                           data-start-date="{{ \Carbon\Carbon::parse($batch->start_date)->format('Y-m-d') }}"
                                           data-end-date="{{ \Carbon\Carbon::parse($batch->end_date)->format('Y-m-d') }}"
                                           data-action="{{ route('admin.batches.update', $batch) }}">
                                            <i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Edit Batch
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('admin.batches.destroy', $batch) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this batch? This can only be done if no students are assigned.');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i>
                                                Delete Batch
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <a href="{{ route('admin.batches.manageStudents', $batch) }}" class="btn btn-info btn-sm" title="Manage Students">
                                    <i class="fas fa-users"></i> Manage
                                </a>
                                <form action="{{ route('admin.batches.graduate', $batch) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to graduate all active students in this batch?')" title="Graduate Batch">
                                        <i class="fas fa-graduation-cap"></i> Graduate
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No batches found. Click "Add New Batch" to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Batch Modal -->
<div class="modal fade" id="addBatchModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Batch</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.batches.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Course*</label>
                            <select name="course_id" class="form-control" required>
                                <option value="">-- Select a Course --</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Batch Name*</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., 2025-2026 Intake" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Start Date*</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>End Date*</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Batch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Batch Modal -->
<div class="modal fade" id="editBatchModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Batch</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editBatchForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Course*</label>
                            <select name="course_id" id="edit_course_id" class="form-control" required>
                                <option value="">-- Select a Course --</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Batch Name*</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Start Date*</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>End Date*</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Batch</button>
                    </div>
                </form>
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
    $(document).ready(function() {
        // Initialize DataTable
        $('#dataTable').DataTable({
            "order": [[ 3, "desc" ]], // Sort by Start Date descending by default
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Disable sorting on actions column
            ]
        });

        // Script to populate the edit modal with data from the clicked row
        $('.edit-batch-btn').on('click', function() {
            const button = $(this);
            const id = button.data('id');
            const name = button.data('name');
            const courseId = button.data('course-id');
            const startDate = button.data('start-date');
            const endDate = button.data('end-date');
            const action = button.data('action');

            const modal = $('#editBatchModal');
            modal.find('form').attr('action', action);
            modal.find('#edit_name').val(name);
            modal.find('#edit_course_id').val(courseId);
            modal.find('#edit_start_date').val(startDate);
            modal.find('#edit_end_date').val(endDate);
        });
    });
</script>
@endpush
