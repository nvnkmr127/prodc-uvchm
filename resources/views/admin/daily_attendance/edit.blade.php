{{-- File: resources/views/admin/daily-attendance/edit.blade.php --}}

@extends('layouts.theme')

@section('title', 'Edit Attendance Record')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit fa-sm fa-fw mr-2"></i>
            Edit Attendance Record
        </h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.daily-attendance.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('admin.daily-attendance.show') }}" class="btn btn-info btn-sm">
                <i class="fas fa-eye"></i> Live View
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-edit mr-2"></i>
                        Edit Attendance Information
                    </h6>
                </div>
                <div class="card-body">
                    <form id="editAttendanceForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information (Read-only) -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Student/Faculty Name</label>
                                    <div class="form-control-plaintext bg-light p-2 rounded">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm mr-2">
                                                <div class="avatar-title rounded-circle bg-primary text-white">
                                                    {{ substr(($attendance->student->name ?? $attendance->faculty->name ?? 'Unknown'), 0, 1) }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">
                                                    {{ $attendance->student->name ?? $attendance->faculty->name ?? 'Unknown' }}
                                                </div>
                                                <small class="text-muted">
                                                    @if($attendance->student)
                                                        Student • {{ $attendance->student->enrollment_number ?? 'No Enrollment' }}
                                                    @else
                                                        Faculty • {{ $attendance->faculty->employee_id ?? 'No Employee ID' }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Batch/Department</label>
                                    <div class="form-control-plaintext bg-light p-2 rounded">
                                        @if($attendance->batch)
                                            <span class="badge badge-primary">{{ $attendance->batch->name }}</span>
                                            <small class="text-muted ml-2">{{ $attendance->batch->course->name ?? 'No Course' }}</small>
                                        @else
                                            <span class="badge badge-secondary">{{ $attendance->faculty->department ?? 'No Department' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Date</label>
                                    <div class="form-control-plaintext bg-light p-2 rounded">
                                        {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l, d M Y') }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Subject</label>
                                    <div class="form-control-plaintext bg-light p-2 rounded">
                                        {{ $attendance->subject->name ?? 'General' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Editable Fields -->
                        <hr class="my-4">
                        <h6 class="font-weight-bold text-secondary mb-3">Editable Information</h6>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="font-weight-bold">Status <span class="text-danger">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="present" {{ $attendance->status == 'present' ? 'selected' : '' }}>Present</option>
                                        <option value="late" {{ $attendance->status == 'late' ? 'selected' : '' }}>Late</option>
                                        <option value="absent" {{ $attendance->status == 'absent' ? 'selected' : '' }}>Absent</option>
                                        <option value="excused" {{ $attendance->status == 'excused' ? 'selected' : '' }}>Excused</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="check_in_time" class="font-weight-bold">Check-in Time</label>
                                    <input type="time" class="form-control" id="check_in_time" name="check_in_time" 
                                           value="{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '' }}">
                                    <small class="form-text text-muted">Leave empty for system default</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="remarks" class="font-weight-bold">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                                              placeholder="Add any additional notes or remarks..."
                                              maxlength="500">{{ $attendance->remarks }}</textarea>
                                    <small class="form-text text-muted">Maximum 500 characters</small>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-danger" id="deleteBtn">
                                        <i class="fas fa-trash mr-2"></i>Delete Record
                                    </button>
                                    <div>
                                        <a href="{{ route('admin.daily-attendance.index') }}" class="btn btn-secondary mr-2">
                                            <i class="fas fa-times mr-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary" id="updateBtn">
                                            <i class="fas fa-save mr-2"></i>Update Attendance
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Information Panel -->
        <div class="col-lg-4">
            <!-- Current Status Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle mr-2"></i>Current Status
                    </h6>
                </div>
                <div class="card-body text-center">
                    @php
                        $statusColors = [
                            'present' => 'success',
                            'late' => 'warning',
                            'absent' => 'danger',
                            'excused' => 'info'
                        ];
                        $color = $statusColors[$attendance->status] ?? 'secondary';
                    @endphp
                    
                    <div class="status-indicator mb-3">
                        <i class="fas fa-user-check fa-3x text-{{ $color }}"></i>
                    </div>
                    <h4 class="text-{{ $color }} mb-2">{{ ucfirst($attendance->status) }}</h4>
                    @if($attendance->check_in_time)
                        <p class="text-muted mb-0">
                            Check-in: {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') }}
                        </p>
                    @endif
                </div>
            </div>

            <!-- Audit Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">
                        <i class="fas fa-history mr-2"></i>Audit Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="audit-item mb-3">
                        <strong>Originally Marked By:</strong>
                        <p class="text-muted mb-0">{{ $attendance->markedBy->name ?? 'System' }}</p>
                    </div>
                    
                    <div class="audit-item mb-3">
                        <strong>Created:</strong>
                        <p class="text-muted mb-0">{{ $attendance->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    
                    @if($attendance->updated_at != $attendance->created_at)
                    <div class="audit-item mb-3">
                        <strong>Last Updated:</strong>
                        <p class="text-muted mb-0">{{ $attendance->updated_at->format('d M Y, h:i A') }}</p>
                        @if($attendance->updatedBy)
                            <small class="text-muted">by {{ $attendance->updatedBy->name }}</small>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="quickSetStatus('present')">
                            <i class="fas fa-check mr-2"></i>Mark Present
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="quickSetStatus('late')">
                            <i class="fas fa-clock mr-2"></i>Mark Late
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="quickSetStatus('absent')">
                            <i class="fas fa-times mr-2"></i>Mark Absent
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="quickSetStatus('excused')">
                            <i class="fas fa-user-shield mr-2"></i>Mark Excused
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger mr-2"></i>
                    Confirm Delete
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this attendance record?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. The attendance record will be permanently removed from the system.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash mr-2"></i>Delete Record
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3 mb-0">Processing request...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Status change handler
    $('#status').change(function() {
        updateStatusIndicator($(this).val());
    });

    // Form submission
    $('#editAttendanceForm').submit(function(e) {
        e.preventDefault();
        updateAttendance();
    });

    // Delete button
    $('#deleteBtn').click(function() {
        $('#deleteModal').modal('show');
    });

    // Confirm delete
    $('#confirmDelete').click(function() {
        deleteAttendance();
    });

    // Character counter for remarks
    $('#remarks').on('input', function() {
        const current = $(this).val().length;
        const max = 500;
        const remaining = max - current;
        
        // Update character count display if it exists, or create one
        let counter = $(this).siblings('.char-counter');
        if (counter.length === 0) {
            counter = $('<small class="form-text text-muted char-counter"></small>');
            $(this).after(counter);
        }
        
        counter.text(`${current}/${max} characters`);
        
        if (remaining < 50) {
            counter.addClass('text-warning');
        } else {
            counter.removeClass('text-warning');
        }
    });

    // Initialize character counter
    $('#remarks').trigger('input');
});

function quickSetStatus(status) {
    $('#status').val(status).trigger('change');
    
    // Set appropriate check-in time if not already set
    if (!$('#check_in_time').val() && (status === 'present' || status === 'late')) {
        const now = new Date();
        const timeString = now.toTimeString().slice(0, 5);
        $('#check_in_time').val(timeString);
    }
}

function updateStatusIndicator(status) {
    const statusColors = {
        'present': 'success',
        'late': 'warning',
        'absent': 'danger',
        'excused': 'info'
    };
    
    const color = statusColors[status] || 'secondary';
    const icon = $('.status-indicator i');
    const heading = $('.status-indicator').siblings('h4');
    
    // Remove existing color classes
    icon.removeClass('text-success text-warning text-danger text-info text-secondary');
    heading.removeClass('text-success text-warning text-danger text-info text-secondary');
    
    // Add new color class
    icon.addClass(`text-${color}`);
    heading.addClass(`text-${color}`).text(status.charAt(0).toUpperCase() + status.slice(1));
}

function updateAttendance() {
    const formData = {
        _token: '{{ csrf_token() }}',
        _method: 'PUT',
        status: $('#status').val(),
        check_in_time: $('#check_in_time').val(),
        remarks: $('#remarks').val()
    };

    // Validate required fields
    if (!formData.status) {
        showError('Please select a status.');
        return;
    }

    $('#loadingModal').modal('show');

    $.ajax({
        url: '{{ route("admin.daily-attendance.update", $attendance->id) }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                showSuccess('Attendance record updated successfully!');
                setTimeout(function() {
                    window.location.href = '{{ route("admin.daily-attendance.index") }}';
                }, 1500);
            } else {
                showError('Failed to update attendance: ' + response.message);
            }
        },
        error: function(xhr) {
            let message = 'Failed to update attendance. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = Object.values(xhr.responseJSON.errors).flat();
                message = errors.join('<br>');
            }
            showError(message);
        },
        complete: function() {
            $('#loadingModal').modal('hide');
        }
    });
}

function deleteAttendance() {
    $('#deleteModal').modal('hide');
    $('#loadingModal').modal('show');

    $.ajax({
        url: '{{ route("admin.daily-attendance.destroy", $attendance->id) }}',
        method: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showSuccess('Attendance record deleted successfully!');
                setTimeout(function() {
                    window.location.href = '{{ route("admin.daily-attendance.index") }}';
                }, 1500);
            } else {
                showError('Failed to delete attendance: ' + response.message);
            }
        },
        error: function(xhr) {
            let message = 'Failed to delete attendance. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        },
        complete: function() {
            $('#loadingModal').modal('hide');
        }
    });
}

function showSuccess(message) {
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

function showError(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Keyboard shortcuts
$(document).keydown(function(e) {
    // Ctrl+S to save
    if (e.ctrlKey && e.which === 83) {
        e.preventDefault();
        $('#editAttendanceForm').submit();
        return false;
    }
    
    // Ctrl+Delete to delete
    if (e.ctrlKey && e.which === 46) {
        e.preventDefault();
        $('#deleteBtn').click();
        return false;
    }
    
    // Number keys for quick status change
    if (e.which >= 49 && e.which <= 52) { // 1-4 keys
        e.preventDefault();
        const statuses = ['present', 'late', 'absent', 'excused'];
        const status = statuses[e.which - 49];
        quickSetStatus(status);
        return false;
    }
});

// Prevent accidental page refresh
window.addEventListener('beforeunload', function(e) {
    const originalStatus = '{{ $attendance->status }}';
    const originalRemarks = '{{ $attendance->remarks }}';
    const originalTime = '{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format("H:i") : "" }}';
    
    const currentStatus = $('#status').val();
    const currentRemarks = $('#remarks').val();
    const currentTime = $('#check_in_time').val();
    
    const hasChanges = (currentStatus !== originalStatus) || 
                      (currentRemarks !== originalRemarks) || 
                      (currentTime !== originalTime);
    
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});
</script>
@endpush

@push('styles')
<style>
.avatar-sm {
    width: 40px;
    height: 40px;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 600;
}

.form-control-plaintext {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    margin-bottom: 0;
    line-height: 1.5;
    color: #495057;
    background-color: transparent;
    border: solid transparent;
    border-width: 1px 0;
}

.status-indicator {
    transition: all 0.3s ease;
}

.audit-item {
    border-left: 3px solid #e3e6f0;
    padding-left: 1rem;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.badge {
    font-size: 0.8rem;
    padding: 0.4em 0.8em;
}

.btn-outline-success:hover,
.btn-outline-warning:hover,
.btn-outline-danger:hover,
.btn-outline-info:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}

.position-fixed {
    position: fixed !important;
}

.char-counter {
    text-align: right;
    font-size: 0.75rem;
}

.char-counter.text-warning {
    color: #856404 !important;
    font-weight: 600;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.modal-content {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
    padding: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1.5rem;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.font-weight-bold {
    font-weight: 600 !important;
}

.text-xs {
    font-size: 0.75rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .d-sm-flex {
        flex-direction: column;
        align-items: stretch !important;
    }
    
    .btn-group {
        margin-top: 1rem;
        width: 100%;
    }
    
    .avatar-sm {
        width: 32px;
        height: 32px;
    }
    
    .avatar-title {
        font-size: 0.875rem;
    }
    
    .d-grid .btn {
        font-size: 0.875rem;
        padding: 0.5rem;
    }
}

/* Animation for status changes */
@keyframes statusChange {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.status-indicator.changed {
    animation: statusChange 0.3s ease-out;
}

/* Keyboard shortcut hints */
.quick-actions .btn {
    position: relative;
}

.quick-actions .btn:hover::after {
    content: attr(data-shortcut);
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 1000;
}

/* Print styles */
@media print {
    .btn, .card-header, .alert, .modal {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .col-lg-4 {
        display: none !important;
    }
    
    .col-lg-8 {
        width: 100% !important;
    }
}
</style>
@endpush