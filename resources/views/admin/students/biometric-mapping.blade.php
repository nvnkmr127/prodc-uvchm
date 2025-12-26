@extends('layouts.theme')

@section('title', 'Biometric Mapping Management')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Biometric Mapping Management</h1>
    <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Students
    </a>
</div>

{{-- Statistics Cards --}}
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total Students</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_students'] ?? 0 }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Mapped Students</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['mapped_students'] ?? 0 }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-fingerprint fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Unmapped Students</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['unmapped_students'] ?? 0 }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Completion</div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $stats['mapping_percentage'] ?? 0 }}%</div>
                            </div>
                            <div class="col">
                                <div class="progress progress-sm mr-2">
                                    <div class="progress-bar bg-primary" role="progressbar"
                                         style="width: {{ $stats['mapping_percentage'] ?? 0 }}%"
                                         aria-valuenow="{{ $stats['mapping_percentage'] ?? 0 }}" aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Action Buttons --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="btn-toolbar" role="toolbar">
            <div class="btn-group mr-2" role="group">
                <button type="button" class="btn btn-primary shadow-sm" onclick="showImportModal()">
                    <i class="fas fa-upload fa-sm text-white-50"></i> Import Mappings
                </button>
                <a href="{{ route('admin.students.biometric-mapping.export') }}" class="btn btn-info shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50"></i> Export Unmapped
                </a>
                <button type="button" class="btn btn-success shadow-sm" onclick="autoGenerateAll()">
                    <i class="fas fa-magic fa-sm text-white-50"></i> Auto Generate All
                </button>
            </div>
            <button type="button" class="btn btn-warning shadow-sm" onclick="saveAllChanges()">
                <i class="fas fa-save fa-sm text-white-50"></i> Save All Changes
            </button>
        </div>
    </div>
</div>

{{-- Students Table Card --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Students Biometric Mapping</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>Name</th>
                        <th>Enrollment Number</th>
                        <th>Batch</th>
                        <th>Course</th>
                        <th>Current Code</th>
                        <th width="20%">New Biometric Code</th>
                        <th width="10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students ?? [] as $student)
                    <tr data-student-id="{{ $student['id'] }}">
                        <td>
                            <input type="checkbox" class="student-checkbox form-check-input" value="{{ $student['id'] }}">
                        </td>
                        <td>{{ $student['name'] }}</td>
                        <td>{{ $student['enrollment_number'] }}</td>
                        <td>{{ $student['batch_name'] }}</td>
                        <td>{{ $student['course_name'] }}</td>
                        <td>
                            @if($student['biometric_code'])
                                <span class="badge badge-success">{{ $student['biometric_code'] }}</span>
                            @else
                                <span class="badge badge-warning">Not Set</span>
                            @endif
                        </td>
                        <td>
                            <input type="text" 
                                   class="form-control form-control-sm biometric-input" 
                                   data-student-id="{{ $student['id'] }}"
                                   value="{{ $student['biometric_code'] ?? $student['suggested_code'] ?? '' }}"
                                   placeholder="Enter biometric code">
                        </td>
                        <td>
                            <button type="button" 
                                    class="btn btn-sm btn-success save-single" 
                                    data-student-id="{{ $student['id'] }}"
                                    title="Save this student's mapping">
                                <i class="fas fa-save"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <i class="fas fa-info-circle"></i> No students found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.students.biometric-mapping.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload mr-2"></i>Import Biometric Mappings
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="importFile">Choose Excel/CSV File</label>
                        <input type="file" class="form-control-file" id="importFile" name="file" accept=".xlsx,.csv,.xls" required>
                        <small class="form-text text-muted">
                            File should contain columns: student_id, enrollment_number, biometric_code
                        </small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Format:</strong> Download the sample file to see the expected format.
                        <br>
                        <a href="{{ route('admin.students.biometric-mapping.sample') }}" class="btn btn-sm btn-outline-info mt-2">
                            <i class="fas fa-download"></i> Download Sample
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add refresh button for manual refresh
    addRefreshButton();

    // Initialize DataTable
    $('#studentsTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[1, 'asc']], // Sort by name
        columnDefs: [
            { orderable: false, targets: [0, 7] } // Disable sorting on checkbox and actions
        ]
    });

    // Select all functionality
    $('#selectAll').change(function() {
        $('.student-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Save single student
    $(document).on('click', '.save-single', function() {
        var studentId = $(this).data('student-id');
        var biometricCode = $(`.biometric-input[data-student-id="${studentId}"]`).val().trim();
        saveBiometricMapping([{
            student_id: parseInt(studentId),
            biometric_code: biometricCode
        }], 'single');
    });

    // Enter key to save individual row
    $(document).on('keypress', '.biometric-input', function(e) {
        if (e.which === 13) { // Enter key
            var studentId = $(this).data('student-id');
            $(`.save-single[data-student-id="${studentId}"]`).click();
        }
    });
});

function showImportModal() {
    $('#importModal').modal('show');
}

function saveAllChanges() {
    var mappings = [];
    
    $('.biometric-input').each(function() {
        var studentId = parseInt($(this).data('student-id'));
        var biometricCode = $(this).val().trim();
        
        mappings.push({
            student_id: studentId,
            biometric_code: biometricCode
        });
    });

    if (mappings.length === 0) {
        showAlert('info', 'No changes to save');
        return;
    }

    // Show confirmation for bulk save
    if (confirm(`Save biometric mappings for ${mappings.length} students?`)) {
        saveBiometricMapping(mappings, 'bulk');
    }
}

function saveBiometricMapping(mappings, type) {
    // Show loading using button state
    $('.save-single, button').prop('disabled', true);
    var loadingText = type === 'single' ? 'Saving...' : 'Saving all...';
    
    if (type === 'bulk') {
        $('button:contains("Save All Changes")').html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    }

    $.ajax({
        url: '{{ route("admin.students.biometric-mapping.bulk") }}',
        method: 'POST',
        data: {
            mappings: mappings,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                
                // Update the UI instead of reloading
                if (response.results) {
                    updateUIWithResults(mappings, response.results);
                    
                    // Show additional info if there were errors
                    if (response.results.error_count > 0) {
                        setTimeout(function() {
                            showAlert('warning', `Completed with ${response.results.error_count} errors. Check individual rows.`);
                        }, 1000);
                    }
                } else {
                    // If no detailed results, just update the badges
                    updateBadgesForSavedMappings(mappings);
                }
                
                // Update statistics
                updateStatistics();
                
            } else {
                showAlert('error', response.message || 'Failed to save mappings');
            }
        },
        error: function(xhr, status, error) {
            var errorMessage = 'Failed to save biometric mappings';
            
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = Object.values(xhr.responseJSON.errors).flat();
                errorMessage = 'Validation Error: ' + errors.join(', ');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 0) {
                errorMessage = 'Network error - check connection';
            } else if (xhr.status >= 500) {
                errorMessage = 'Server error - check Laravel logs';
            }
            
            showAlert('error', errorMessage);
        },
        complete: function() {
            // Re-enable buttons and restore text
            $('.save-single, button').prop('disabled', false);
            $('button:contains("Saving...")').html('<i class="fas fa-save fa-sm text-white-50"></i> Save All Changes');
        }
    });
}

function autoGenerateAll() {
    if (!confirm('Auto-generate biometric codes for all unmapped students based on their enrollment numbers?')) {
        return;
    }

    // Show loading
    $('button').prop('disabled', true);
    $('button:contains("Auto Generate All")').html('<i class="fas fa-spinner fa-spin"></i> Generating...');

    $.ajax({
        url: '{{ route("admin.students.biometric-mapping.auto-generate") }}',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                
                // Update the UI instead of reloading
                if (response.results) {
                    // Show additional info if there were errors
                    if (response.results.error_count > 0) {
                        setTimeout(function() {
                            showAlert('warning', `Generated codes with ${response.results.error_count} warnings`);
                        }, 1000);
                    }
                    
                    // Update suggested codes for all unmapped students
                    updateSuggestedCodes();
                }
                
                // Update statistics
                updateStatistics();
                
            } else {
                showAlert('error', response.message || 'Failed to generate codes');
            }
        },
        error: function(xhr, status, error) {
            var errorMessage = 'Failed to auto-generate codes';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            showAlert('error', errorMessage);
        },
        complete: function() {
            // Re-enable buttons and restore text
            $('button').prop('disabled', false);
            $('button:contains("Generating...")').html('<i class="fas fa-magic fa-sm text-white-50"></i> Auto Generate All');
        }
    });
}

// Update UI functions instead of page reload
function updateUIWithResults(mappings, results) {
    // Update the current biometric code badges for successful saves
    mappings.forEach(function(mapping) {
        var row = $(`tr[data-student-id="${mapping.student_id}"]`);
        var badge = row.find('td:nth-child(6) .badge');
        var input = row.find('.biometric-input');
        
        if (mapping.biometric_code && mapping.biometric_code.trim() !== '') {
            // Update badge to show new code
            badge.removeClass('badge-warning').addClass('badge-success').text(mapping.biometric_code);
            // Clear the input since it's now saved
            input.val('');
            // Add visual feedback
            row.addClass('table-success');
            setTimeout(function() {
                row.removeClass('table-success');
            }, 3000);
        } else {
            // Code was cleared
            badge.removeClass('badge-success').addClass('badge-warning').text('Not Set');
        }
    });
}

function updateBadgesForSavedMappings(mappings) {
    mappings.forEach(function(mapping) {
        var row = $(`tr[data-student-id="${mapping.student_id}"]`);
        var badge = row.find('td:nth-child(6) .badge');
        var input = row.find('.biometric-input');
        var currentValue = input.val().trim();
        
        if (currentValue !== '') {
            // Update badge to show new code
            badge.removeClass('badge-warning').addClass('badge-success').text(currentValue);
            // Clear the input since it's now saved
            input.val('');
            // Add visual feedback
            row.addClass('table-success');
            setTimeout(function() {
                row.removeClass('table-success');
            }, 3000);
        }
    });
}

function updateSuggestedCodes() {
    // For auto-generation, we need to refresh the suggested codes
    // This would ideally come from the server response, but as a fallback:
    $('.biometric-input').each(function() {
        var currentBadge = $(this).closest('tr').find('td:nth-child(6) .badge');
        if (currentBadge.hasClass('badge-warning')) {
        }
    });
}

function updateStatistics() {
    logToServer('INFO', 'Updating statistics after save operation');
    
    // Count current mapped vs unmapped
    var totalStudents = $('.student-checkbox').length;
    var mappedStudents = $('.badge-success').length;
    var unmappedStudents = totalStudents - mappedStudents;
    var percentage = totalStudents > 0 ? Math.round((mappedStudents / totalStudents) * 100) : 0;
    
    // Update the statistics cards
    $('.border-left-info .h5').text(totalStudents);
    $('.border-left-success .h5').text(mappedStudents);
    $('.border-left-warning .h5').text(unmappedStudents);
    $('.border-left-primary .h5').text(percentage + '%');
    $('.progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
    
}

// Add refresh button for when users want to see server state
function addRefreshButton() {
    var refreshButton = `
        <button type="button" class="btn btn-outline-secondary shadow-sm ml-2" onclick="refreshPageData()" title="Refresh data from server">
            <i class="fas fa-sync-alt fa-sm"></i> Refresh
        </button>
    `;
    $('.btn-toolbar').append(refreshButton);
}

function refreshPageData() {
    showAlert('info', 'Refreshing data...');
    setTimeout(function() {
        window.location.reload();
    }, 500);
}
function showAlert(type, message) {
    var alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    var icon = {
        'success': 'fa-check-circle',
        'error': 'fa-times-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    // Remove existing alerts
    $('.alert-dismissible').remove();
    
    var alertHtml = `
        <div class="alert ${alertClass[type]} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 350px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas ${icon[type]} mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeOut();
    }, 5000);
}

// Show flash messages if any
@if(session('success'))
    showAlert('success', '{{ session('success') }}');
@endif

@if(session('error'))
    showAlert('error', '{{ session('error') }}');
@endif

@if(session('warning'))
    showAlert('warning', '{{ session('warning') }}');
@endif

@if(session('info'))
    showAlert('info', '{{ session('info') }}');
@endif

// Show import errors if any
@if(session('import_errors') && count(session('import_errors')) > 0)
    @foreach(session('import_errors') as $error)
        setTimeout(function() {
            showAlert('error', '{{ $error }}');
        }, {{ $loop->index * 1000 }});
    @endforeach
@endif
</script>
@endpush