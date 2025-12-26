@extends('layouts.theme')

@section('title', 'Edit Attendance')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit text-primary"></i> Edit Attendance
        </h1>
        <div class="d-sm-flex">
            <a href="{{ route('attendance.show', $attendance) }}" class="btn btn-secondary btn-sm mr-2">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-list"></i> All Records
            </a>
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

    <div class="row">
        {{-- Edit Form --}}
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-edit mr-2"></i>Edit Attendance Record
                    </h6>
                </div>
                <div class="card-body">
                    <form id="editAttendanceForm" onsubmit="updateAttendance(event)">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="batch_id">Batch <span class="text-danger">*</span></label>
                                    <select name="batch_id" id="batch_id" class="form-control" required>
                                        @foreach($batches as $batch)
                                            <option value="{{ $batch->id }}" 
                                                {{ $attendance->batch_id == $batch->id ? 'selected' : '' }}>
                                                {{ $batch->name }} ({{ $batch->course->name ?? 'No Course' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('batch_id')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="attendance_date">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="attendance_date" id="attendance_date" 
                                           class="form-control" value="{{ $attendance->attendance_date }}" required>
                                    @error('attendance_date')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subject_id">Subject</label>
                                    <select name="subject_id" id="subject_id" class="form-control">
                                        <option value="">General Attendance</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" 
                                                {{ $attendance->subject_id == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('subject_id')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                        <label class="btn btn-outline-success {{ $attendance->status === 'present' ? 'active' : '' }}">
                                            <input type="radio" name="status" value="present" 
                                                {{ $attendance->status === 'present' ? 'checked' : '' }} 
                                                onchange="toggleLateMinutes()">
                                            <i class="fas fa-check"></i> Present
                                        </label>
                                        <label class="btn btn-outline-danger {{ $attendance->status === 'absent' ? 'active' : '' }}">
                                            <input type="radio" name="status" value="absent" 
                                                {{ $attendance->status === 'absent' ? 'checked' : '' }}
                                                onchange="toggleLateMinutes()">
                                            <i class="fas fa-times"></i> Absent
                                        </label>
                                        <label class="btn btn-outline-warning {{ $attendance->status === 'late' ? 'active' : '' }}">
                                            <input type="radio" name="status" value="late" 
                                                {{ $attendance->status === 'late' ? 'checked' : '' }}
                                                onchange="toggleLateMinutes()">
                                            <i class="fas fa-clock"></i> Late
                                        </label>
                                        <label class="btn btn-outline-info {{ $attendance->status === 'excused' ? 'active' : '' }}">
                                            <input type="radio" name="status" value="excused" 
                                                {{ $attendance->status === 'excused' ? 'checked' : '' }}
                                                onchange="toggleLateMinutes()">
                                            <i class="fas fa-info-circle"></i> Excused
                                        </label>
                                    </div>
                                    @error('status')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row" id="lateMinutesRow" style="{{ $attendance->status === 'late' ? '' : 'display: none;' }}">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="late_minutes">Late Minutes</label>
                                    <input type="number" name="late_minutes" id="late_minutes" 
                                           class="form-control" min="1" max="480" 
                                           value="{{ $attendance->late_minutes }}"
                                           placeholder="Enter minutes late">
                                    <small class="text-muted">How many minutes was the student late?</small>
                                    @error('late_minutes')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                                              placeholder="Any additional notes about this attendance record...">{{ $attendance->notes }}</textarea>
                                    <small class="text-muted">Optional notes about this attendance record</small>
                                    @error('notes')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="reason">Reason for Edit <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" id="reason" class="form-control" 
                                           placeholder="Why are you editing this attendance record?" required>
                                    <small class="text-muted">Required: Explain why this record is being modified</small>
                                    @error('reason')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save mr-2"></i>Update Attendance
                            </button>
                            <a href="{{ route('attendance.show', $attendance) }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Student Info Sidebar --}}
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user mr-2"></i>Student Information
                    </h6>
                </div>
                <div class="card-body text-center">
                    @if($attendance->student && $attendance->student->profile_photo)
                        <img src="{{ asset('storage/' . $attendance->student->profile_photo) }}" 
                             alt="Student Photo" class="rounded-circle mb-3" 
                             style="width: 80px; height: 80px; object-fit: cover;">
                    @else
                        <div class="bg-gray-200 rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x text-gray-400"></i>
                        </div>
                    @endif

                    <h6 class="font-weight-bold">{{ $attendance->student->name ?? 'Unknown Student' }}</h6>
                    <p class="text-muted mb-1">{{ $attendance->student->enrollment_number ?? 'N/A' }}</p>
                    <p class="text-muted small">{{ $attendance->student->email ?? 'No Email' }}</p>
                </div>
            </div>

            {{-- Current Record Info --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-2"></i>Current Record
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th>Original Status:</th>
                            <td>
                                <span class="badge badge-{{ $attendance->status_color }}">
                                    {{ $attendance->status_label }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td>{{ $attendance->created_at->format('M d, Y H:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Marked By:</th>
                            <td>{{ $attendance->faculty->name ?? 'System' }}</td>
                        </tr>
                        @if($attendance->updated_at != $attendance->created_at)
                        <tr>
                            <th>Last Modified:</th>
                            <td>{{ $attendance->updated_at->format('M d, Y H:i A') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Attendance Rules --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Important Notes
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <small>
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Editing Guidelines:</strong><br>
                            • Changes will be logged for audit purposes<br>
                            • A reason for editing is required<br>
                            • Student and parent notifications may be sent<br>
                            • Contact admin for attendance policy questions
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleLateMinutes() {
    const status = $('input[name="status"]:checked').val();
    const lateMinutesRow = $('#lateMinutesRow');
    const lateMinutesInput = $('#late_minutes');
    
    if (status === 'late') {
        lateMinutesRow.show();
        lateMinutesInput.prop('required', true);
        if (!lateMinutesInput.val()) {
            lateMinutesInput.val({{ config('attendance.rules.late_threshold_minutes', 15) }});
        }
    } else {
        lateMinutesRow.hide();
        lateMinutesInput.prop('required', false);
        if (status !== 'late') {
            lateMinutesInput.val('');
        }
    }
}

function updateAttendance(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('editAttendanceForm'));
    
    // Show loading state
    $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Updating...');
    
    $.ajax({
        url: '{{ route("attendance.update", $attendance) }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                  '<i class="fas fa-check-circle mr-2"></i>' + response.message +
                  '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                  '</div>').prependTo('.container-fluid');
                
                // Scroll to top
                $('html, body').animate({scrollTop: 0}, 500);
                
                // Redirect after 2 seconds
                setTimeout(function() {
                    window.location.href = '{{ route("attendance.show", $attendance) }}';
                }, 2000);
            } else {
                throw new Error(response.message || 'Unknown error occurred');
            }
        },
        error: function(xhr) {
            let message = 'Failed to update attendance';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join(', ');
            }
            
            $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
              '<i class="fas fa-exclamation-triangle mr-2"></i>' + message +
              '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
              '</div>').prependTo('.container-fluid');
            
            $('html, body').animate({scrollTop: 0}, 500);
        },
        complete: function() {
            $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Update Attendance');
        }
    });
}

$(document).ready(function() {
    // Initialize the late minutes visibility
    toggleLateMinutes();
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush

@push('styles')
<style>
.btn-group-toggle .btn.active {
    background-color: var(--primary);
    border-color: var(--primary);
    color: white;
}

.btn-outline-success.active {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
}

.btn-outline-danger.active {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.btn-outline-warning.active {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #212529 !important;
}

.btn-outline-info.active {
    background-color: #17a2b8 !important;
    border-color: #17a2b8 !important;
}

.table-borderless th,
.table-borderless td {
    border: none;
}

.badge-lg {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}
</style>
@endpush