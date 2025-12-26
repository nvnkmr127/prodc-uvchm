@extends('layouts.theme')
@section('title', 'Lab Group Allocation')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Lab Group Allocation</h1>
    @if($selectedBatch)
    <div>
        <button class="btn btn-sm btn-info shadow-sm me-2" onclick="refreshStats()">
            <i class="fas fa-chart-bar fa-sm text-white-50"></i> View Stats
        </button>
        <button class="btn btn-sm btn-success shadow-sm" data-toggle="modal" data-target="#automateModal">
            <i class="fas fa-robot fa-sm text-white-50"></i> Run Automated Allocation
        </button>
    </div>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Success!</strong><br>
        {!! nl2br(e(session('success'))) !!}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Error!</strong><br>
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Info:</strong><br>
        {{ session('info') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong><br>
        {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- Batch Selection Card --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-filter me-2"></i>Select a Batch to Manage Lab Allocation
        </h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.lab-allocation.index') }}" method="GET">
            <div class="row align-items-end">
                <div class="col-md-10">
                    <label for="batch_id" class="form-label">Choose Batch</label>
                    <select name="batch_id" id="batch_id" class="form-control" required onchange="this.form.submit()">
                        <option value="">-- Select a Batch --</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $selectedBatch && $selectedBatch->id == $batch->id ? 'selected' : '' }}>
                                {{ $batch->course->name }} - {{ $batch->name }}
                                ({{ $batch->students()->where('status', 'active')->count() }} students)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Load
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($selectedBatch)
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="h5 mb-0 text-gray-800">
            Lab Groups for: <strong>{{ $selectedBatch->name }}</strong>
        </h3>
        <p class="text-muted mb-0">
            Course: {{ $selectedBatch->course->name }} | 
            Total Students: {{ $selectedBatch->students()->where('status', 'active')->count() }}
        </p>
    </div>
    <div class="btn-group" role="group">
        <button class="btn btn-sm btn-info shadow-sm me-2" onclick="refreshStats()">
            <i class="fas fa-chart-bar fa-sm text-white-50"></i> View Stats
        </button>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle shadow-sm" data-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download fa-sm text-white-50"></i> Export Reports
            </button>
            <div class="dropdown-menu">
                <h6 class="dropdown-header">Complete Reports</h6>
                <a class="dropdown-item" href="{{ route('admin.lab-allocation.pdf.batch', $selectedBatch) }}?format=detailed">
                    <i class="fas fa-file-pdf text-danger me-2"></i>Detailed PDF Report
                </a>
                <a class="dropdown-item" href="{{ route('admin.lab-allocation.pdf.batch', $selectedBatch) }}?format=summary">
                    <i class="fas fa-file-pdf text-danger me-2"></i>Summary PDF Report
                </a>
                <div class="dropdown-divider"></div>
                <h6 class="dropdown-header">Students Only</h6>
                <a class="dropdown-item" href="{{ route('admin.lab-allocation.students-pdf.batch', $selectedBatch) }}">
                    <i class="fas fa-users text-primary me-2"></i>Students List PDF
                </a>
                <div class="dropdown-divider"></div>
                <h6 class="dropdown-header">Excel Reports</h6>
                <a class="dropdown-item" href="{{ route('admin.lab-allocation.excel.batch', $selectedBatch) }}">
                    <i class="fas fa-file-excel text-success me-2"></i>Excel Export
                </a>
                <div class="dropdown-divider"></div>
                <h6 class="dropdown-header">All Batches</h6>
                <a class="dropdown-item" href="{{ route('admin.lab-allocation.pdf.all') }}?format=detailed">
                    <i class="fas fa-file-pdf text-danger me-2"></i>All Batches - Detailed PDF
                </a>
                <a class="dropdown-item" href="{{ route('admin.lab-allocation.students-pdf.all') }}">
                    <i class="fas fa-users text-primary me-2"></i>All Batches - Students Only
                </a>
            </div>
        </div>
        <button class="btn btn-sm btn-success shadow-sm" data-toggle="modal" data-target="#automateModal">
            <i class="fas fa-robot fa-sm text-white-50"></i> Run Automated Allocation
        </button>
    </div>
</div>

{{-- Groups Display --}}
<div class="row">
    @forelse ($practicalGroups as $group)
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-{{ $group->status_color }} shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-{{ $group->status_color }} text-uppercase mb-1">
                                {{ $group->name }}
                            </div>
                            <div class="mb-1">
                                <strong>Lab:</strong> {{ $group->classroom->name }}
                            </div>
                            <div class="mb-1">
                                <strong>Academic Year:</strong> {{ $group->academicYear->name }}
                            </div>
                            <div class="mb-2">
                                <strong>Students:</strong> 
                                <span class="badge badge-{{ $group->status_color }}">
                                    {{ $group->students_count }} / {{ $group->classroom->capacity }}
                                </span>
                                <small class="text-muted">({{ $group->utilization }}% full)</small>
                            </div>
                            
                            {{-- Progress Bar --}}
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-{{ $group->status_color }}" 
                                     role="progressbar" 
                                     style="width: {{ $group->utilization }}%" 
                                     aria-valuenow="{{ $group->utilization }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <a href="{{ route('admin.lab-allocation.group.manage', $group) }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-users"></i> Manage
                                </a>
                                <button type="button" 
                                        class="btn btn-outline-danger btn-sm" 
                                        onclick="deleteGroup({{ $group->id }}, '{{ $group->name }}')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flask fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <h5>No practical groups created yet for this batch</h5>
                <p>Use the "Run Automated Allocation" button above to create groups automatically, or manage students manually after creating groups.</p>
                @if($academicYears->isEmpty())
                    <div class="alert alert-warning mt-3">
                        <strong>Note:</strong> Please create Academic Years first before running allocation.
                        <a href="{{ route('admin.academic-years.create') }}" class="btn btn-sm btn-warning ms-2">
                            <i class="fas fa-plus"></i> Create Academic Year
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endforelse
</div>

{{-- Automate Allocation Modal --}}
<div class="modal fade" id="automateModal" tabindex="-1" role="dialog" aria-labelledby="automateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="automateModalLabel">
                    <i class="fas fa-robot me-2"></i>Automate Lab Allocation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>This will automatically assign all unassigned students from "{{ $selectedBatch->name }}" into lab groups.</strong>
                </div>
                
                <form action="{{ route('admin.lab-allocation.automate') }}" method="POST" id="automateForm">
                    @csrf
                    <input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="academic_year_id" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Academic Year *
                                </label>
                                <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                                    <option value="">-- Select Academic Year --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                            {{ $year->name }}
                                            @if($year->is_current) 
                                                <span class="badge badge-success">Current Year</span>
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Students will be grouped for this academic year
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="lab_capacity" class="form-label">
                                    <i class="fas fa-users me-1"></i>Lab Capacity
                                </label>
                                <select name="lab_capacity" id="lab_capacity" class="form-control">
                                    <option value="15">15 students per lab</option>
                                    <option value="20">20 students per lab</option>
                                    <option value="25">25 students per lab</option>
                                    <option value="30" selected>30 students per lab</option>
                                    <option value="35">35 students per lab</option>
                                    <option value="40">40 students per lab</option>
                                </select>
                                <small class="form-text text-muted">
                                    Maximum students per lab group
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="force_recreate" id="force_recreate" value="1">
                            <label class="form-check-label text-warning" for="force_recreate">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Force recreate groups (this will delete existing groups for this academic year)
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Check this only if you want to recreate all groups from scratch
                        </small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-lightbulb me-2"></i>How it works:</h6>
                        <ul class="mb-0 small">
                            <li>Students will be divided into groups based on the lab capacity you select</li>
                            <li>Each group will be assigned to an available lab classroom</li>
                            <li>Group names will be auto-generated (e.g., "Batch A - Lab 1 - Group 1")</li>
                            <li>Only unassigned students for the selected academic year will be processed</li>
                        </ul>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success" id="automateBtn">
                            <i class="fas fa-robot"></i> Run Automation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Delete Group Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-trash me-2"></i>Delete Practical Group
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Are you sure you want to delete this group?</strong>
                </div>
                <p>Group: <strong id="deleteGroupName"></strong></p>
                <p>This will:</p>
                <ul>
                    <li>Remove all students from this group</li>
                    <li>Make those students available for reassignment</li>
                    <li>Permanently delete the group record</li>
                </ul>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Group
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Stats Modal --}}
<div class="modal fade" id="statsModal" tabindex="-1" role="dialog" aria-labelledby="statsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="statsModalLabel">
                    <i class="fas fa-chart-bar me-2"></i>Lab Allocation Statistics
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="statsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading statistics...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when academic year changes
    const academicYearSelect = document.getElementById('academic_year_id');
    if (academicYearSelect) {
        academicYearSelect.addEventListener('change', function() {
            // Optional: You can add logic here if needed
        });
    }
    
    // Form validation before submit
    const automateForm = document.getElementById('automateForm');
    if (automateForm) {
        automateForm.addEventListener('submit', function(e) {
            const academicYearId = document.getElementById('academic_year_id').value;
            const automateBtn = document.getElementById('automateBtn');
            
            if (!academicYearId) {
                e.preventDefault();
                alert('Please select an Academic Year before proceeding.');
                return false;
            }
            
            // Show loading state
            automateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            automateBtn.disabled = true;
            
            // Optional: Add confirmation for force recreate
            const forceRecreate = document.getElementById('force_recreate').checked;
            if (forceRecreate) {
                if (!confirm('This will delete all existing groups for this academic year. Are you sure?')) {
                    e.preventDefault();
                    automateBtn.innerHTML = '<i class="fas fa-robot"></i> Run Automation';
                    automateBtn.disabled = false;
                    return false;
                }
            }
        });
    }
});

// Delete group function
function deleteGroup(groupId, groupName) {
    document.getElementById('deleteGroupName').textContent = groupName;
    document.getElementById('deleteForm').action = `{{ route('admin.lab-allocation.index') }}/${groupId}`;
    $('#deleteModal').modal('show');
}

// Refresh stats function
function refreshStats() {
    const batchId = {{ $selectedBatch ? $selectedBatch->id : 'null' }};
    if (!batchId) {
        alert('Please select a batch first.');
        return;
    }
    
    $('#statsModal').modal('show');
    
    fetch(`/admin/lab-allocation/stats/${batchId}`)
        .then(response => response.json())
        .then(data => {
            let statsHtml = `
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 class="card-title">${data.total_students}</h2>
                                    <p class="card-text">Total Students</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 class="card-title">${data.assigned_students}</h2>
                                    <p class="card-text">Assigned Students</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 class="card-title">${data.unassigned_students}</h2>
                                    <p class="card-text">Unassigned Students</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 class="card-title">${data.total_groups}</h2>
                                    <p class="card-text">Lab Groups</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (data.groups && data.groups.length > 0) {
                statsHtml += `
                    <div class="mt-4">
                        <h5>Group Details</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Group Name</th>
                                        <th>Lab</th>
                                        <th>Students</th>
                                        <th>Capacity</th>
                                        <th>Utilization</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                data.groups.forEach(group => {
                    const statusClass = group.utilization >= 95 ? 'danger' : 
                                       group.utilization >= 80 ? 'warning' : 
                                       group.utilization >= 50 ? 'info' : 'secondary';
                    
                    statsHtml += `
                        <tr>
                            <td>${group.name}</td>
                            <td>${group.lab}</td>
                            <td>${group.students_count}</td>
                            <td>${group.capacity}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-${statusClass}" style="width: ${group.utilization}%">
                                        ${group.utilization}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-${statusClass}">
                                    ${group.utilization >= 95 ? 'Full' : 
                                      group.utilization >= 80 ? 'High' : 
                                      group.utilization >= 50 ? 'Medium' : 'Low'}
                                </span>
                            </td>
                        </tr>
                    `;
                });
                
                statsHtml += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('statsContent').innerHTML = statsHtml;
        })
        .catch(error => {
            console.error('Error fetching stats:', error);
            document.getElementById('statsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading statistics. Please try again.
                </div>
            `;
        });
}

// Auto-refresh page after successful allocation (optional)
@if(session('success'))
    // Optional: Auto-refresh stats or reload page after a few seconds
    setTimeout(() => {
        // You can uncomment this if you want auto-refresh
        // location.reload();
    }, 5000);
@endif
</script>
@endpush

@push('styles')
<style>
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f39c12 !important;
}
.border-left-info {
    border-left: 0.25rem solid #3498db !important;
}
.border-left-secondary {
    border-left: 0.25rem solid #95a5a6 !important;
}

.progress {
    background-color: #f8f9fc;
}

.card-body {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.btn-group-sm > .btn, .btn-sm {
    font-size: 0.775rem;
}

.alert {
    border-radius: 0.5rem;
}

.modal-header {
    border-radius: 0.5rem 0.5rem 0 0;
}

.badge {
    font-size: 0.7em;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
    }
}
</style>
@endpush