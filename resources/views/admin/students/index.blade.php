@extends('layouts.theme')
@section('title', 'Manage Students')

@push('styles')
<style>
    .status-badge {
        padding: 0.35em 0.65em;
        font-size: .75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
        color: #fff;
    }
    .status-badge.active { background-color: #1cc88a; }
    .status-badge.graduated { background-color: #36b9cc; }
    .status-badge.dropout { background-color: #e74a3b; }
    
    .filter-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
    }
    .filter-card .card-header:hover { text-decoration: none; color: white; }
    
    .list-group-item[data-action] {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .list-group-item[data-action]:hover {
        background-color: #f8f9fc;
    }
    .list-group-item.active {
        background-color: #4e73df;
        border-color: #4e73df;
        color: white;
    }
    .list-group-item.active i {
        color: white !important;
    }
    
    #batchSelection .alert {
        border-left: 4px solid #1cc88a;
    }
    
    .img-profile {
        width: 2.5rem;
        height: 2.5rem;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
{{-- 1. Page Header --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Students</h1>
    <div>
        <button class="btn btn-sm btn-info shadow-sm" id="bulk-actions-btn" disabled>
            <i class="fas fa-tasks fa-sm text-white-50"></i> Bulk Actions
        </button>
        <a href="{{ route('admin.students.create') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add Student
        </a>
        <button class="btn btn-sm btn-success shadow-sm" data-toggle="modal" data-target="#importStudentsModal">
            <i class="fas fa-file-import fa-sm text-white-50"></i> Bulk Import
        </button>
        <a href="{{ route('admin.students.export') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Export
        </a>
    </div>
</div>

{{-- 2. Alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- 3. Collapsible Filters Card --}}
<div class="card shadow mb-4 filter-card">
    <a href="#collapseFilterCard" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseFilterCard">
        <h6 class="m-0 font-weight-bold"><i class="fas fa-filter mr-2"></i>Filter Students</h6>
    </a>
    <div class="collapse show" id="collapseFilterCard">
        <div class="card-body">
            <form action="{{ route('admin.students.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="course_id_filter">Course</label>
                        <select name="course_id" id="course_id_filter" class="form-control">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="batch_id_filter">Batch</label>
                        <select name="batch_id" id="batch_id_filter" class="form-control">
                            <option value="">All Batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>{{ $batch->name }} ({{$batch->course->name}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="status_filter">Status</label>
                        <select name="status" id="status_filter" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                            <option value="dropout" {{ request('status') == 'dropout' ? 'selected' : '' }}>Dropout</option>
                        </select>
                    </div>
                </div>
                <div class="text-right">
                    <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Reset</a>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 4. Student Data Table Card --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Student List 
            <span class="badge badge-info ml-2">{{ $students->count() }} students</span>
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 20px;"><input type="checkbox" id="select-all"></th>
                        <th>Student</th>
                        <th>Enrollment #</th>
                        <th>Course & Batch</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td><input type="checkbox" name="student_ids[]" class="student-checkbox" value="{{ $student->id }}"></td>
                            <td>
                                <a href="{{ route('admin.students.show', $student) }}" class="d-flex align-items-center">
                                    <img class="img-profile rounded-circle mr-3" src="{{ \App\Http\Controllers\Admin\StudentController::getStudentPhotoUrl($student, 40) }}" alt="{{$student->name}}">
                                    <div>
                                        <div class="font-weight-bold">{{ $student->name }}</div>
                                        <div class="small text-gray-500">{{ $student->email ?? 'No Email' }}</div>
                                    </div>
                                </a>
                            </td>
                            <td>{{ $student->enrollment_number }}</td>
                            <td>
                                @if ($student->batch)
                                    <div>{{ $student->batch->course->name ?? 'N/A' }}</div>
                                    <strong class="small">{{ $student->batch->name }}</strong>
                                @else
                                    <span class="text-muted">Not Assigned</span>
                                @endif
                            </td>
                            <td>
                                @if($student->student_mobile)
                                    <div>Student: {{ $student->student_mobile }}</div>
                                @endif
                                @if($student->father_mobile)
                                    <div class="small text-muted">Father: {{ $student->father_mobile }}</div>
                                @endif
                                @if(!$student->student_mobile && !$student->father_mobile)
                                    <span class="text-muted">No Contact</span>
                                @endif
                            </td>
                            <td><span class="status-badge {{ $student->status }}">{{ ucfirst($student->status) }}</span></td>
                            <td class="text-center">
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink{{ $student->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink{{ $student->id }}">
                                        <a class="dropdown-item" href="{{ route('admin.students.show', $student) }}">
                                            <i class="fas fa-eye fa-sm fa-fw mr-2 text-gray-400"></i>View Profile
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.students.edit', $student) }}">
                                            <i class="fas fa-pencil-alt fa-sm fa-fw mr-2 text-gray-400"></i>Edit
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                                <i class="fas fa-trash fa-sm fa-fw mr-2"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-gray-500">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <h5>No students found</h5>
                                    @if(request()->hasAny(['course_id', 'batch_id', 'status']))
                                        <p>No students match your current filters.</p>
                                        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-primary">Clear Filters</a>
                                    @else
                                        <p>Start by adding your first student or importing student data.</p>
                                        <a href="{{ route('admin.students.create') }}" class="btn btn-primary mr-2">Add Student</a>
                                        <button class="btn btn-success" data-toggle="modal" data-target="#importStudentsModal">Import Students</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Enhanced Bulk Actions Modal --}}
<div class="modal fade" id="bulkActionsModal" tabindex="-1" role="dialog" aria-labelledby="bulkActionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkActionsModalLabel">
                    <i class="fas fa-tasks mr-2"></i>Bulk Actions
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="selected-count" class="mb-3">No students selected</p>
                
                <!-- Bulk Action Form -->
                <form id="bulk-actions-form" action="{{ route('admin.students.bulk-actions') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" id="bulk-action-input">
                    <input type="hidden" name="new_status" id="bulk-status-input">
                    <input type="hidden" name="batch_id" id="bulk-batch-input">
                    <div id="selected-students-input"></div>

                    <div class="form-group">
                        <label class="font-weight-bold">Select Action:</label>
                        <div class="list-group">
                            <!-- Status Changes -->
                            <button type="button" class="list-group-item list-group-item-action" data-action="change_status" data-status="active">
                                <i class="fas fa-check-circle text-success mr-2"></i>
                                Mark as Active
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" data-action="change_status" data-status="graduated">
                                <i class="fas fa-graduation-cap text-info mr-2"></i>
                                Mark as Graduated
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" data-action="change_status" data-status="dropout">
                                <i class="fas fa-times-circle text-warning mr-2"></i>
                                Mark as Dropout
                            </button>
                            
                            <!-- Batch Assignment -->
                            <button type="button" class="list-group-item list-group-item-action" data-action="assign_batch" data-toggle="collapse" data-target="#batchSelection">
                                <i class="fas fa-users text-primary mr-2"></i>
                                Assign to Batch
                                <i class="fas fa-chevron-down float-right"></i>
                            </button>
                            
                            <!-- Delete -->
                            <button type="button" class="list-group-item list-group-item-action text-danger" data-action="delete">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Students
                            </button>
                        </div>
                    </div>

                    <!-- Batch Selection (Collapsible) -->
                    <div class="collapse" id="batchSelection">
                        <div class="card card-body">
                            <div class="form-group">
                                <label for="bulk-course-select">Select Course:</label>
                                <select id="bulk-course-select" class="form-control">
                                    <option value="">-- Select a Course --</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bulk-batch-select">Select Batch:</label>
                                <select id="bulk-batch-select" class="form-control" disabled>
                                    <option value="">-- Select a course first --</option>
                                </select>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> Assigning students to a new batch will:
                                <ul class="mb-0 mt-2">
                                    <li>Generate new enrollment numbers</li>
                                    <li>Create new invoices based on the batch's fee structure</li>
                                    <li>Remove any existing unpaid invoices</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="execute-bulk-action" disabled>Execute Action</button>
            </div>
        </div>
    </div>
</div>

{{-- Import Students Modal --}}
<div class="modal fade" id="importStudentsModal" tabindex="-1" role="dialog" aria-labelledby="importStudentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.students.import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="importStudentsModalLabel">
                        <i class="fas fa-file-import mr-2"></i>Bulk Import Students
                    </h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle mr-2"></i>Instructions:</h6>
                        <ol class="mb-0">
                            <li>Download the sample template to see the required format</li>
                            <li>Fill in your student data following the exact column structure</li>
                            <li>Select the target course and batch for all imported students</li>
                            <li>Upload your completed file</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="import_course_id">Target Course *</label>
                                <select name="course_id" id="import_course_id" class="form-control" required>
                                    <option value="">-- Select a Course --</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="import_batch_id">Target Batch *</label>
                                <select name="batch_id" id="import_batch_id" class="form-control" disabled required>
                                    <option value="">-- Select a course first --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="import_file">Upload File (Excel/CSV)</label>
                        <div class="custom-file">
                            <input type="file" name="import_file" class="custom-file-input" id="import_file" accept=".xlsx,.xls,.csv" required>
                            <label class="custom-file-label" for="import_file">Choose file...</label>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ route('admin.students.import.sample') }}" class="btn btn-outline-info">
                            <i class="fas fa-download mr-1"></i>Download Sample Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="importSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <i class="fas fa-upload"></i>
                        <span class="text">Import Students</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
// COMPLETE FIXED JAVASCRIPT - Resolving all conflicts
<script>
$(document).ready(function() {
    let selectedStudents = [];
    let selectedAction = null;
    console.log('Script loaded - fixing JavaScript conflicts');
    
    // CRITICAL FIX 1: Initialize DataTable with proper error handling
    try {
        if ($.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable().destroy();
        }
        
        $('#dataTable').DataTable({
            "pageLength": 25,
            "responsive": true,
            "columnDefs": [
                { "orderable": false, "targets": [0, 6] },
                { "searchable": false, "targets": [0, 6] }
            ],
            "order": [[ 1, "asc" ]],
            "drawCallback": function() {
                // CRITICAL FIX 2: Re-attach event handlers after DataTable redraws
                console.log('DataTable redrawn - reattaching checkbox handlers');
                attachCheckboxHandlers();
            }
        });
    } catch (error) {
        console.error('DataTable initialization failed:', error);
    }
    
    // CRITICAL FIX 3: Separate function for checkbox handlers to avoid conflicts
    function attachCheckboxHandlers() {
        // Remove existing handlers to prevent duplicates
        $('.student-checkbox').off('change.studentSelect');
        $('#select-all').off('change.selectAll');
        
        // Attach handlers with namespaces
        $('.student-checkbox').on('change.studentSelect', function() {
            updateSelectedStudents();
        });
        
        $('#select-all').on('change.selectAll', function() {
            $('.student-checkbox').prop('checked', this.checked);
            updateSelectedStudents();
        });
    }
    
    // Initial attachment
    attachCheckboxHandlers();
    
    function updateSelectedStudents() {
        selectedStudents = [];
        $('.student-checkbox:checked').each(function() {
            selectedStudents.push($(this).val());
        });
        
        $('#selected-count').html(`<strong>${selectedStudents.length}</strong> student(s) selected`);
        $('#bulk-actions-btn').prop('disabled', selectedStudents.length === 0);
        updateSelectedStudentsInput();
    }
    
    function updateSelectedStudentsInput() {
        const container = $('#selected-students-input');
        container.empty();
        selectedStudents.forEach(function(studentId) {
            container.append(`<input type="hidden" name="student_ids[]" value="${studentId}">`);
        });
    }
    
    // CRITICAL FIX 4: Use event delegation to avoid conflicts with Bootstrap collapse
    $(document).on('click', '.list-group-item[data-action]', function(e) {
        console.log('Action clicked via delegation:', $(this).data('action'));
        
        // CRITICAL: Stop all propagation that might interfere with collapse
        e.preventDefault();
        e.stopImmediatePropagation();
        
        // Remove active class from all items
        $('.list-group-item[data-action]').removeClass('active');
        $(this).addClass('active');
        
        selectedAction = $(this).data('action');
        const status = $(this).data('status');
        
        console.log('Selected action:', selectedAction);
        
        // Update hidden inputs
        $('#bulk-action-input').val(selectedAction);
        $('#bulk-status-input').val(status || '');
        
        // Handle different actions
        if (selectedAction === 'assign_batch') {
            console.log('Showing batch selection panel');
            
            // CRITICAL FIX 5: Force show the collapse without relying on data-toggle
            setTimeout(function() {
                $('#batchSelection').collapse('show');
            }, 100);
            
            // Reset form state
            $('#bulk-course-select').val('').trigger('change');
            $('#bulk-batch-input').val('');
            $('#execute-bulk-action').prop('disabled', true).text('Select Course & Batch');
        } else {
            $('#batchSelection').collapse('hide');
            $('#execute-bulk-action').prop('disabled', false).text(`Execute: ${$(this).text().trim()}`);
        }
    });
    
    // CRITICAL FIX 6: Use event delegation for course selection to avoid modal conflicts
    $(document).on('change', '#bulk-course-select, #import_course_id', function() {
        const courseId = $(this).val();
        const isImport = $(this).attr('id') === 'import_course_id';
        const batchSelect = isImport ? $('#import_batch_id') : $('#bulk-batch-select');
        
        console.log('Course selected:', courseId, 'isImport:', isImport);
        
        // Clear batch selection
        if (!isImport) {
            $('#bulk-batch-input').val('');
        }
        
        if (courseId) {
            batchSelect.html('<option value="">Loading batches...</option>').prop('disabled', true);
            
            // CRITICAL FIX 7: Add better error handling and timeout
            $.ajax({
                url: `/admin/get-batches-for-course/${courseId}`,
                type: 'GET',
                timeout: 10000,
                cache: false, // Prevent caching issues
                success: function(data) {
                    console.log('AJAX Success - Batches received:', data);
                    
                    batchSelect.html('<option value="">-- Select a Batch --</option>');
                    
                    if (data && Array.isArray(data) && data.length > 0) {
                        $.each(data, function(index, batch) {
                            if (batch && batch.id && batch.name) {
                                batchSelect.append(`<option value="${batch.id}">${batch.name}</option>`);
                            }
                        });
                        batchSelect.prop('disabled', false);
                        console.log('Batch dropdown enabled with', data.length, 'options');
                        
                        // Force a visual update
                        batchSelect[0].style.backgroundColor = '#fff';
                        batchSelect[0].style.pointerEvents = 'auto';
                    } else {
                        batchSelect.html('<option value="">-- No batches found --</option>');
                        console.log('No batches found for course', courseId);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error Details:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        readyState: xhr.readyState,
                        responseText: xhr.responseText,
                        error: error
                    });
                    
                    batchSelect.html('<option value="">-- Error loading batches --</option>').prop('disabled', true);
                    
                    // Show user-friendly error
                    if (xhr.status === 404) {
                        console.error('Route not found - check your routes/web.php');
                    } else if (xhr.status === 500) {
                        console.error('Server error - check your controller method');
                    }
                }
            });
            
            // Update execute button for bulk actions
            if (!isImport && selectedAction === 'assign_batch') {
                $('#execute-bulk-action').prop('disabled', true).text('Select a Batch');
            }
        } else {
            batchSelect.html('<option value="">-- Select a course first --</option>').prop('disabled', true);
            
            if (!isImport && selectedAction === 'assign_batch') {
                $('#execute-bulk-action').prop('disabled', true).text('Select Course & Batch');
            }
        }
    });
    
    // CRITICAL FIX 8: Use event delegation for batch selection
    $(document).on('change', '#bulk-batch-select', function() {
        const batchId = $(this).val();
        console.log('Batch selected:', batchId);
        
        $('#bulk-batch-input').val(batchId);
        
        if (selectedAction === 'assign_batch') {
            if (batchId) {
                $('#execute-bulk-action').prop('disabled', false).text('Assign to Batch');
                console.log('Execute button enabled');
            } else {
                $('#execute-bulk-action').prop('disabled', true).text('Select a Batch');
            }
        }
    });
    
    // CRITICAL FIX 9: Modal event handlers with proper cleanup
    $('#bulkActionsModal').on('shown.bs.modal', function() {
        console.log('Bulk actions modal opened');
        
        // Test element accessibility
        console.log('Modal elements check:', {
            courseSelect: $('#bulk-course-select').length,
            batchSelect: $('#bulk-batch-select').length,
            batchPanel: $('#batchSelection').length,
            executeBtn: $('#execute-bulk-action').length
        });
        
        // Force focus to ensure modal is active
        $(this).focus();
    });
    
    $('#bulkActionsModal').on('hidden.bs.modal', function() {
        console.log('Modal closing - cleanup');
        
        // Complete cleanup
        $('.list-group-item[data-action]').removeClass('active');
        $('#execute-bulk-action').prop('disabled', true).text('Execute Action');
        $('#batchSelection').collapse('hide');
        $('#bulk-course-select').val('');
        $('#bulk-batch-select').html('<option value="">-- Select a course first --</option>').prop('disabled', true);
        $('#bulk-batch-input').val('');
        $('#bulk-status-input').val('');
        selectedAction = null;
    });
    
    // Execute bulk action
    $(document).on('click', '#execute-bulk-action', function() {
        console.log('Execute button clicked');
        
        if (selectedStudents.length === 0) {
            alert('Please select at least one student.');
            return;
        }
        
        if (!selectedAction) {
            alert('Please select an action.');
            return;
        }
        
        // Validation for batch assignment
        if (selectedAction === 'assign_batch') {
            const courseId = $('#bulk-course-select').val();
            const batchId = $('#bulk-batch-select').val();
            
            console.log('Validating batch assignment:', { courseId, batchId });
            
            if (!courseId) {
                alert('Please select a course.');
                return;
            }
            
            if (!batchId) {
                alert('Please select a batch.');
                return;
            }
            
            $('#bulk-batch-input').val(batchId);
        }
        
        // Clear irrelevant fields
        if (selectedAction !== 'assign_batch') {
            $('#bulk-batch-input').val('');
        }
        if (selectedAction !== 'change_status') {
            $('#bulk-status-input').val('');
        }
        
        // Confirmation
        let confirmMessage = `Are you sure you want to execute this action on ${selectedStudents.length} student(s)?`;
        
        if (selectedAction === 'delete') {
            confirmMessage = `Are you sure you want to PERMANENTLY DELETE ${selectedStudents.length} student(s)? This action cannot be undone.`;
        } else if (selectedAction === 'assign_batch') {
            const courseName = $('#bulk-course-select option:selected').text();
            const batchName = $('#bulk-batch-select option:selected').text();
            confirmMessage = `Are you sure you want to assign ${selectedStudents.length} student(s) to ${courseName} - ${batchName}? This will generate new enrollment numbers and invoices.`;
        }
        
        if (confirm(confirmMessage)) {
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            
            console.log('Submitting form with data:', {
                action: $('#bulk-action-input').val(),
                status: $('#bulk-status-input').val(),
                batch_id: $('#bulk-batch-input').val(),
                student_ids: selectedStudents
            });
            
            $('#bulk-actions-form').submit();
        }
    });
    
    // Bulk actions button
    $(document).on('click', '#bulk-actions-btn', function() {
        if (selectedStudents.length === 0) {
            alert('Please select at least one student.');
            return;
        }
        $('#bulkActionsModal').modal('show');
    });
    
    // Import modal functionality
    $(document).on('change', '.custom-file-input', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
    
    $(document).on('submit', '#importForm', function() {
        const btn = $('#importSubmitBtn');
        btn.prop('disabled', true);
        btn.find('.spinner-border').removeClass('d-none');
        btn.find('.text').text('Importing...');
        btn.find('.fa-upload').addClass('d-none');
    });
    
    // CRITICAL FIX 10: Force CSS to ensure dropdowns work in modals
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .modal {
                z-index: 1050 !important;
            }
            
            .modal-backdrop {
                z-index: 1040 !important;
            }
            
            #bulk-batch-select:disabled {
                pointer-events: none !important;
                background-color: #e9ecef !important;
                opacity: 0.65 !important;
                cursor: not-allowed !important;
            }
            
            #bulk-batch-select:not(:disabled) {
                pointer-events: auto !important;
                background-color: #fff !important;
                opacity: 1 !important;
                cursor: pointer !important;
            }
            
            /* Fix for Bootstrap modal select conflicts */
            .modal select {
                position: relative !important;
                z-index: 1060 !important;
            }
            
            /* Ensure collapse panels work in modals */
            .modal .collapse {
                z-index: 1055 !important;
            }
        `)
        .appendTo('head');
    
    console.log('JavaScript initialization complete - all conflicts resolved');
});
</script>
@endpush