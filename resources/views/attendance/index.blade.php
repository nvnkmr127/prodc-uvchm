@extends('layouts.theme')

@section('title', 'Attendance Records')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list text-primary"></i> Attendance Records
        </h1>
        <div class="d-sm-flex">
            @can('take attendance')
                <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-sm mr-2">
                    <i class="fas fa-plus"></i> Take Attendance
                </a>
            @endcan
            @can('manage attendance')
                <a href="{{ route('attendance.analytics.index') }}" class="btn btn-info btn-sm mr-2">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
                <a href="{{ route('attendance.reports.index') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
            @endcan
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Filters Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter mr-2"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('attendance.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <label for="batch_id" class="form-label">Batch</label>
                        <select name="batch_id" id="batch_id" class="form-control">
                            <option value="">All Batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" 
                                    {{ (request('batch_id') == $batch->id) ? 'selected' : '' }}>
                                    {{ $batch->name }} ({{ $batch->course->name ?? 'No Course' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-control">
                            <option value="">All Subjects</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" 
                                    {{ (request('subject_id') == $subject->id) ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="present" {{ (request('status') == 'present') ? 'selected' : '' }}>Present</option>
                            <option value="absent" {{ (request('status') == 'absent') ? 'selected' : '' }}>Absent</option>
                            <option value="late" {{ (request('status') == 'late') ? 'selected' : '' }}>Late</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" 
                               value="{{ request('date_from') }}">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" 
                               value="{{ request('date_to') }}">
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Clear Filters
                        </a>
                        @can('manage attendance')
                            <button type="button" class="btn btn-success" onclick="exportAttendance()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        @endcan
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Attendance Records Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table mr-2"></i>Attendance Records 
                <span class="badge badge-secondary">{{ $attendances->total() }} total</span>
            </h6>
        </div>
        <div class="card-body">
            @if($attendances->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th width="15%">Date</th>
                                <th width="20%">Student</th>
                                <th width="15%">Enrollment</th>
                                <th width="15%">Batch</th>
                                <th width="10%">Subject</th>
                                <th width="10%">Status</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendances as $attendance)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="attendance_ids[]" 
                                               value="{{ $attendance->id }}" class="form-check-input attendance-checkbox">
                                    </td>
                                    <td>
                                        {{ $attendance->attendance_date ? \Carbon\Carbon::parse($attendance->attendance_date)->format('d M Y') : 'N/A' }}
                                        <br>
                                        <small class="text-muted">
                                            {{ $attendance->created_at ? $attendance->created_at->format('H:i A') : '' }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                @if($attendance->student && $attendance->student->profile_photo)
                                                    <img src="{{ asset('storage/' . $attendance->student->profile_photo) }}" 
                                                         alt="Student Photo" class="rounded-circle" 
                                                         style="width: 40px; height: 40px; object-fit: cover;">
                                                @else
                                                    <div class="bg-gray-200 rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="fas fa-user text-gray-400"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">
                                                    {{ $attendance->student->name ?? 'Unknown Student' }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ $attendance->student->email ?? 'No Email' }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light">
                                            {{ $attendance->student->enrollment_number ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $attendance->batch->name ?? 'No Batch' }}</strong>
                                            @if($attendance->batch && $attendance->batch->course)
                                                <br>
                                                <small class="text-muted">{{ $attendance->batch->course->name }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $attendance->subject->name ?? 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColor = match($attendance->status) {
                                                'present' => 'success',
                                                'absent' => 'danger',
                                                'late' => 'warning',
                                                default => 'secondary'
                                            };
                                            $statusIcon = match($attendance->status) {
                                                'present' => 'fa-check-circle',
                                                'absent' => 'fa-times-circle',
                                                'late' => 'fa-clock',
                                                default => 'fa-question-circle'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $statusColor }}">
                                            <i class="fas {{ $statusIcon }} mr-1"></i>
                                            {{ ucfirst($attendance->status) }}
                                        </span>
                                        @if($attendance->late_minutes && $attendance->status === 'late')
                                            <br>
                                            <small class="text-muted">{{ $attendance->late_minutes }} min late</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            @can('view attendance')
                                                <button type="button" class="btn btn-outline-info btn-sm" 
                                                        onclick="viewAttendance({{ $attendance->id }})" 
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endcan
                                            
                                            @can('edit attendance')
                                                <button type="button" class="btn btn-outline-warning btn-sm" 
                                                        onclick="editAttendance({{ $attendance->id }})" 
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endcan
                                            
                                            @can('delete attendance')
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteAttendance({{ $attendance->id }})" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <p class="text-muted">
                            Showing {{ $attendances->firstItem() }} to {{ $attendances->lastItem() }} 
                            of {{ $attendances->total() }} results
                        </p>
                    </div>
                    <div>
                        {{ $attendances->appends(request()->query())->links() }}
                    </div>
                </div>

                {{-- Bulk Actions --}}
                @can('manage attendance')
                    <div class="row mt-3" id="bulkActions" style="display: none;">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <div class="d-flex align-items-center">
                                        <span class="mr-3">
                                            <strong id="selectedCount">0</strong> records selected
                                        </span>
                                        <button type="button" class="btn btn-sm btn-warning mr-2" onclick="bulkEdit()">
                                            <i class="fas fa-edit"></i> Bulk Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger mr-2" onclick="bulkDelete()">
                                            <i class="fas fa-trash"></i> Bulk Delete
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="bulkExport()">
                                            <i class="fas fa-download"></i> Export Selected
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan
            @else
                {{-- No Data State --}}
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-clipboard-list fa-3x text-gray-300"></i>
                    </div>
                    <h5 class="text-gray-500">No Attendance Records Found</h5>
                    <p class="text-gray-400 mb-4">
                        @if(request()->hasAny(['batch_id', 'subject_id', 'status', 'date_from', 'date_to']))
                            No records match your current filters. Try adjusting your search criteria.
                        @else
                            No attendance records have been created yet.
                        @endif
                    </p>
                    
                    @can('take attendance')
                        <a href="{{ route('attendance.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Take First Attendance
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modals --}}
@include('attendance.modals.view')
@include('attendance.modals.edit')
@include('attendance.modals.delete')
@include('attendance.modals.bulk-edit')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Handle select all checkbox
    $('#selectAll').change(function() {
        $('.attendance-checkbox').prop('checked', this.checked);
        updateBulkActions();
    });

    // Handle individual checkboxes
    $('.attendance-checkbox').change(function() {
        updateBulkActions();
        
        // Update "select all" checkbox state
        const total = $('.attendance-checkbox').length;
        const checked = $('.attendance-checkbox:checked').length;
        
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });

    // Auto-submit form on filter change (optional)
    $('#batch_id, #subject_id, #status').change(function() {
        // Uncomment the line below if you want auto-submit on filter change
        // $('#filterForm').submit();
    });
    
    // Handle edit form submission
    $('#editAttendanceForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    $('#editAttendanceModal').modal('hide');
                    // Show success message
                    $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>${response.message || 'Attendance updated successfully'}
                            <button type="button" class="close" data-dismiss="alert">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `);
                    // Reload page to show updated data
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    alert(response.message || 'Failed to update attendance');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Server error occurred';
                alert('Error: ' + message);
            },
            complete: function() {
                 // Reset button state
                 submitBtn.html(originalText).prop('disabled', false);
             }
         });
     });
     
     // Handle bulk edit form submission
     $('#bulkEditForm').on('submit', function(e) {
         e.preventDefault();
         
         const form = $(this);
         const submitBtn = form.find('button[type="submit"]');
         const originalText = submitBtn.html();
         const attendanceIds = $('#bulkEditIds').val().split(',');
         
         // Show loading state
         submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
         
         $.ajax({
             url: '{{ route("attendance.bulk.update-status") }}',
              method: 'POST',
              data: {
                  _token: '{{ csrf_token() }}',
                  attendance_ids: attendanceIds,
                  status: $('#bulkStatus').val(),
                  remarks: $('#bulkRemarks').val()
              },
             headers: {
                 'X-Requested-With': 'XMLHttpRequest',
                 'Accept': 'application/json'
             },
             success: function(response) {
                 if (response.success) {
                     $('#bulkEditModal').modal('hide');
                     
                     // Show success message
                     $('body').prepend(`
                         <div class="alert alert-success alert-dismissible fade show" role="alert">
                             <i class="fas fa-check-circle mr-2"></i>${response.message || 'Attendance records updated successfully!'}
                             <button type="button" class="close" data-dismiss="alert">
                                 <span aria-hidden="true">&times;</span>
                             </button>
                         </div>
                     `);
                     
                     // Reload page to show updated data
                     setTimeout(() => window.location.reload(), 1500);
                 } else {
                     alert(response.message || 'Bulk update failed');
                 }
             },
             error: function(xhr) {
                 const message = xhr.responseJSON?.message || 'Failed to update attendance records';
                 alert('Error: ' + message);
             },
             complete: function() {
                 // Reset button state
                 submitBtn.html(originalText).prop('disabled', false);
             }
         });
     });
});

function updateBulkActions() {
    const selectedCount = $('.attendance-checkbox:checked').length;
    $('#selectedCount').text(selectedCount);
    
    if (selectedCount > 0) {
        $('#bulkActions').show();
    } else {
        $('#bulkActions').hide();
    }
}

function viewAttendance(id) {
    // Show modal and loading state
    $('#viewAttendanceModal').modal('show');
    $('#attendanceDetails').html(`
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading attendance details...</p>
        </div>
    `);
    
    // Load attendance details via AJAX
    $.ajax({
        url: `/attendance/${id}`,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success && response.data) {
                const attendance = response.data;
                $('#attendanceDetails').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Student Information</h6>
                            <p><strong>Name:</strong> ${attendance.student?.name || 'N/A'}</p>
                            <p><strong>Enrollment:</strong> ${attendance.student?.enrollment_number || 'N/A'}</p>
                            <p><strong>Email:</strong> ${attendance.student?.email || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Attendance Details</h6>
                            <p><strong>Date:</strong> ${attendance.attendance_date}</p>
                            <p><strong>Status:</strong> <span class="badge badge-${attendance.status_color}">${attendance.status_label}</span></p>
                            <p><strong>Check-in Time:</strong> ${attendance.check_in_time || 'N/A'}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-primary">Additional Information</h6>
                            <p><strong>Batch:</strong> ${attendance.batch?.name || 'N/A'}</p>
                            <p><strong>Subject:</strong> ${attendance.subject?.name || 'General'}</p>
                            <p><strong>Remarks:</strong> ${attendance.remarks || 'No remarks'}</p>
                        </div>
                    </div>
                `);
            } else {
                $('#attendanceDetails').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Failed to load attendance details.
                    </div>
                `);
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Server error occurred';
            $('#attendanceDetails').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Error: ${message}
                </div>
            `);
        }
    });
}

function editAttendance(id) {
    // Show modal and loading state
    $('#editAttendanceModal').modal('show');
    $('#editFormContent').html(`
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading attendance data...</p>
        </div>
    `);
    
    // Load attendance details for editing
    $.ajax({
        url: `/attendance/${id}/edit`,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success && response.data) {
                const attendance = response.data;
                $('#editFormContent').html(`
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="PUT">
                    <div class="form-group">
                        <label>Student</label>
                        <input type="text" class="form-control" value="${attendance.student?.name || 'N/A'}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="attendance_date" class="form-control" value="${attendance.attendance_date}" required>
                    </div>
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="present" ${attendance.status === 'present' ? 'selected' : ''}>Present</option>
                            <option value="absent" ${attendance.status === 'absent' ? 'selected' : ''}>Absent</option>
                            <option value="late" ${attendance.status === 'late' ? 'selected' : ''}>Late</option>
                            <option value="excused" ${attendance.status === 'excused' ? 'selected' : ''}>Excused</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Check-in Time</label>
                        <input type="time" name="check_in_time" class="form-control" value="${attendance.check_in_time || ''}">
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3">${attendance.remarks || ''}</textarea>
                    </div>
                `);
                
                // Update form action
                $('#editAttendanceForm').attr('action', `/attendance/${id}`);
            } else {
                $('#editFormContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Failed to load attendance data for editing.
                    </div>
                `);
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Server error occurred';
            $('#editFormContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Error: ${message}
                </div>
            `);
        }
    });
}

function deleteAttendance(id) {
    // Show delete modal
    $('#deleteAttendanceModal').modal('show');
    
    // Load attendance details for confirmation
    $.ajax({
        url: `/attendance/${id}`,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success && response.data) {
                const attendance = response.data;
                $('#deleteAttendanceDetails').html(`
                    <div class="card bg-light mt-3">
                        <div class="card-body">
                            <h6 class="card-title">Record Details:</h6>
                            <p class="mb-1"><strong>Student:</strong> ${attendance.student?.name || 'N/A'}</p>
                            <p class="mb-1"><strong>Date:</strong> ${attendance.attendance_date}</p>
                            <p class="mb-0"><strong>Status:</strong> <span class="badge badge-${attendance.status_color}">${attendance.status_label}</span></p>
                        </div>
                    </div>
                `);
            }
        },
        error: function() {
            $('#deleteAttendanceDetails').html(`
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Could not load record details.
                </div>
            `);
        }
    });
    
    // Update form action
    $('#deleteAttendanceForm').attr('action', `/attendance/${id}`);
}

function bulkEdit() {
    const selectedIds = $('.attendance-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        alert('Please select at least one record to edit.');
        return;
    }
    
    // Show bulk edit modal with selected IDs
    $('#bulkEditModal').modal('show');
    $('#bulkEditIds').val(selectedIds.join(','));
    $('#bulkEditCount').text(selectedIds.length);
}

function bulkDelete() {
    const selectedIds = $('.attendance-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        alert('Please select at least one record to delete.');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${selectedIds.length} attendance records?`)) {
        // Create form for bulk delete
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("attendance.bulk.delete") }}';
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Add selected IDs
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

function bulkExport() {
    const selectedIds = $('.attendance-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        alert('Please select at least one record to export.');
        return;
    }
    
    // Create export URL with selected IDs
    const exportUrl = '{{ route("attendance.export") }}?' + 
                     selectedIds.map(id => `ids[]=${id}`).join('&') + 
                     '&type=selected';
    
    // Show loading indicator
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    button.disabled = true;
    
    // Create a temporary link for download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `attendance_selected_${new Date().toISOString().split('T')[0]}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Reset button after delay
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}

function exportAttendance() {
    // Get current filter parameters
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);
    
    // Create export URL with current filters
    const exportUrl = '{{ route("attendance.export") }}?' + params.toString();
    window.open(exportUrl, '_blank');
}

function refreshPage() {
    window.location.reload();
}
</script>
@endpush

@push('styles')
<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    color: #5a5c69;
    background-color: #f8f9fc;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,.075);
}

.attendance-checkbox, #selectAll {
    transform: scale(1.2);
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.badge {
    font-size: 0.75rem;
}

.rounded-circle {
    border: 2px solid #e3e6f0;
}
</style>
@endpush