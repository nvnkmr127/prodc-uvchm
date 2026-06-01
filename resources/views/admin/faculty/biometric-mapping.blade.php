@extends('layouts.theme')

@section('title', 'Faculty Biometric Mapping Management')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Faculty Biometric Mapping Management</h1>
    <a href="{{ route('admin.faculty.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Faculty
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
                            Total Faculty</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_faculty'] ?? 0 }}</div>
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
                            Mapped Faculty</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['mapped_faculty'] ?? 0 }}</div>
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
                            Unmapped Faculty</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['unmapped_faculty'] ?? 0 }}</div>
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
                <a href="{{ route('admin.faculty.biometric-mapping.export') }}" class="btn btn-info shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50"></i> Export Unmapped
                </a>
                <button type="button" class="btn btn-success shadow-sm" onclick="autoGenerateAll()">
                    <i class="fas fa-magic fa-sm text-white-50"></i> Auto Generate All
                </button>
            </div>
            <div class="btn-group mr-2" role="group">
                <button type="button" class="btn btn-warning shadow-sm" onclick="saveAllChanges()">
                    <i class="fas fa-save fa-sm text-white-50"></i> Save All Changes
                </button>
                <button type="button" class="btn btn-outline-danger shadow-sm" onclick="clearSelected()">
                    <i class="fas fa-trash fa-sm"></i> Clear Selected
                </button>
            </div>
            
            <form action="{{ route('admin.faculty.biometric-mapping') }}" method="GET" class="form-inline ml-auto">
                <div class="input-group">
                    <input type="search" name="search" class="form-control bg-light border-0 small" placeholder="Search faculty..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Faculty Table Card --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Faculty Biometric Mapping</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="facultyTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>Name</th>
                        <th>Employee ID</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Current Code</th>
                        <th width="20%">New Biometric Code</th>
                        <th width="10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faculties ?? [] as $faculty)
                    <tr data-faculty-id="{{ $faculty['id'] }}">
                        <td>
                            <input type="checkbox" class="faculty-checkbox form-check-input" value="{{ $faculty['id'] }}">
                        </td>
                        <td>{{ $faculty['name'] }}</td>
                        <td>{{ $faculty['employee_id'] }}</td>
                        <td>{{ $faculty['department'] }}</td>
                        <td>{{ $faculty['phone'] }}</td>
                        <td>
                            @if($faculty['biometric_code'])
                                <span class="badge badge-success">{{ $faculty['biometric_code'] }}</span>
                            @else
                                <span class="badge badge-warning">Not Set</span>
                            @endif
                        </td>
                        <td>
                            <input type="text" 
                                   class="form-control form-control-sm biometric-input" 
                                   data-faculty-id="{{ $faculty['id'] }}"
                                   value="{{ $faculty['biometric_code'] ?? $faculty['suggested_code'] ?? '' }}"
                                   placeholder="Enter biometric code">
                        </td>
                        <td>
                            <button type="button" 
                                    class="btn btn-sm btn-success save-single" 
                                    data-faculty-id="{{ $faculty['id'] }}"
                                    title="Save this faculty member's mapping">
                                <i class="fas fa-save"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <i class="fas fa-info-circle"></i> No faculty found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $facultiesFetch->links() }}
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.faculty.biometric-mapping.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload mr-2"></i>Import Faculty Biometric Mappings
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
                            File should contain columns: faculty_id, employee_id, biometric_code
                        </small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Format:</strong> Download the sample file to see the expected format.
                        <br>
                        <a href="{{ route('admin.faculty.biometric-mapping.sample') }}" class="btn btn-sm btn-outline-info mt-2">
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
    $('#facultyTable').DataTable({
        responsive: true,
        paging: false, // Server side handles paging
        info: true,
        searching: false, // Server side handles search via form
        order: [[1, 'asc']], // Sort by name
        columnDefs: [
            { orderable: false, targets: [0, 7] } // Disable sorting on checkbox and actions
        ]
    });

    // Select all functionality
    $('#selectAll').change(function() {
        $('.faculty-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Save single faculty member
    $(document).on('click', '.save-single', function() {
        var facultyId = $(this).data('faculty-id');
        var biometricCode = $(`.biometric-input[data-faculty-id="${facultyId}"]`).val().trim();
        saveBiometricMapping([{
            faculty_id: parseInt(facultyId),
            biometric_code: biometricCode
        }], 'single');
    });

    // Enter key to save individual row
    $(document).on('keypress', '.biometric-input', function(e) {
        if (e.which === 13) { // Enter key
            var facultyId = $(this).data('faculty-id');
            $(`.save-single[data-faculty-id="${facultyId}"]`).click();
        }
    });
});

function logToServer(level, message) {
    console.log(`[${level}] ${message}`);
}

function showImportModal() {
    $('#importModal').modal('show');
}

function saveAllChanges() {
    var mappings = [];
    
    $('.biometric-input').each(function() {
        var facultyId = parseInt($(this).data('faculty-id'));
        var biometricCode = $(this).val().trim();
        
        mappings.push({
            faculty_id: facultyId,
            biometric_code: biometricCode
        });
    });

    if (mappings.length === 0) {
        showAlert('info', 'No changes to save');
        return;
    }

    if (confirm(`Save biometric mappings for ${mappings.length} faculty members?`)) {
        saveBiometricMapping(mappings, 'bulk');
    }
}

function saveBiometricMapping(mappings, type) {
    $('.save-single, button').prop('disabled', true);
    var loadingText = type === 'single' ? 'Saving...' : 'Saving all...';
    
    if (type === 'bulk') {
        $('button:contains("Save All Changes")').html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    }

    $.ajax({
        url: '{{ route("admin.faculty.biometric-mapping.bulk") }}',
        method: 'POST',
        data: {
            mappings: mappings,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                
                if (response.results) {
                    updateUIWithResults(mappings, response.results);
                    
                    if (response.results.error_count > 0) {
                        setTimeout(function() {
                            showAlert('warning', `Completed with ${response.results.error_count} errors. Check individual rows.`);
                        }, 1000);
                    }
                } else {
                    updateBadgesForSavedMappings(mappings);
                }
                
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
            $('.save-single, button').prop('disabled', false);
            $('button:contains("Saving...")').html('<i class="fas fa-save fa-sm text-white-50"></i> Save All Changes');
        }
    });
}

function autoGenerateAll() {
    if (!confirm('Auto-generate biometric codes for all unmapped faculty members based on their employee IDs?')) {
        return;
    }

    $('button').prop('disabled', true);
    $('button:contains("Auto Generate All")').html('<i class="fas fa-spinner fa-spin"></i> Generating...');

    $.ajax({
        url: '{{ route("admin.faculty.biometric-mapping.auto-generate") }}',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                
                if (response.results) {
                    if (response.results.error_count > 0) {
                        setTimeout(function() {
                            showAlert('warning', `Generated codes with ${response.results.error_count} warnings`);
                        }, 1000);
                    }
                    
                    updateSuggestedCodes();
                }
                
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
            $('button').prop('disabled', false);
            $('button:contains("Generating...")').html('<i class="fas fa-magic fa-sm text-white-50"></i> Auto Generate All');
        }
    });
}

function updateUIWithResults(mappings, results) {
    mappings.forEach(function(mapping) {
        var row = $(`tr[data-faculty-id="${mapping.faculty_id}"]`);
        var badge = row.find('td:nth-child(6) .badge');
        var input = row.find('.biometric-input');
        
        if (mapping.biometric_code && mapping.biometric_code.trim() !== '') {
            badge.removeClass('badge-warning').addClass('badge-success').text(mapping.biometric_code);
            input.val('');
            row.addClass('table-success');
            setTimeout(function() {
                row.removeClass('table-success');
            }, 3000);
        } else if (mapping.biometric_code === '' || mapping.biometric_code === null) {
            badge.removeClass('badge-success').addClass('badge-warning').text('Not Set');
            input.val('');
            row.addClass('table-info');
            setTimeout(function() {
                row.removeClass('table-info');
            }, 3000);
        }
    });
}

function updateBadgesForSavedMappings(mappings) {
    mappings.forEach(function(mapping) {
        var row = $(`tr[data-faculty-id="${mapping.faculty_id}"]`);
        var badge = row.find('td:nth-child(6) .badge');
        var input = row.find('.biometric-input');
        var currentValue = input.val().trim();
        
        if (currentValue !== '') {
            badge.removeClass('badge-warning').addClass('badge-success').text(currentValue);
            input.val('');
            row.addClass('table-success');
            setTimeout(function() {
                row.removeClass('table-success');
            }, 3000);
        }
    });
}

function updateSuggestedCodes() {
    // For auto-generation, we reload to get correct suggestions if they want,
    // or just let the refresh page update it
    refreshPageData();
}

function updateStatistics() {
    logToServer('INFO', 'Updating statistics after save operation');
    
    var totalFaculty = $('.faculty-checkbox').length;
    var mappedFaculty = $('.badge-success').length;
    var unmappedFaculty = totalFaculty - mappedFaculty;
    var percentage = totalFaculty > 0 ? Math.round((mappedFaculty / totalFaculty) * 100) : 0;
    
    $('.border-left-info .h5').text(totalFaculty);
    $('.border-left-success .h5').text(mappedFaculty);
    $('.border-left-warning .h5').text(unmappedFaculty);
    $('.border-left-primary .h5').text(percentage + '%');
    $('.progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
}

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

function clearSelected() {
    var mappings = [];
    $('.faculty-checkbox:checked').each(function() {
        mappings.push({
            faculty_id: parseInt($(this).val()),
            biometric_code: ''
        });
    });

    if (mappings.length === 0) {
        showAlert('warning', 'Please select faculty to clear their biometric codes');
        return;
    }

    if (confirm(`Clear biometric codes for ${mappings.length} selected faculty members?`)) {
        saveBiometricMapping(mappings, 'bulk');
    }
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
    
    setTimeout(function() {
        $('.alert-dismissible').fadeOut();
    }, 5000);
}

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

@if(session('import_errors') && count(session('import_errors')) > 0)
    @foreach(session('import_errors') as $error)
        setTimeout(function() {
            showAlert('error', '{{ $error }}');
        }, {{ $loop->index * 1000 }});
    @endforeach
@endif
</script>
@endpush
