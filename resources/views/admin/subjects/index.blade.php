@extends('layouts.theme')
@section('title', 'Subject Management')

@section('content')
{{-- Header Section --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-book text-primary mr-2"></i>Subject Management
        </h1>
        <p class="text-muted mb-0">Manage subjects, faculty assignments, and course allocations</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm mr-1"></i> Add New Subject
        </a>
        <button class="btn btn-outline-secondary" data-toggle="modal" data-target="#bulkActionsModal">
            <i class="fas fa-tasks mr-1"></i> Bulk Actions
        </button>
    </div>
</div>

{{-- Success/Error Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
@endif

{{-- Statistics Cards --}}
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Subjects
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $subjects->count() }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
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
                            With Faculty
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $subjects->filter(function($s) { return $s->users && $s->users->count() > 0; })->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Lab Subjects
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $subjects->where('requires_lab', true)->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-flask fa-2x text-gray-300"></i>
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
                            In Courses
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $subjects->filter(function($s) { return isset($s->courses_count) && $s->courses_count > 0; })->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters and Search --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter mr-2"></i>Search & Filter
                </h6>
            </div>
            <div class="col-md-6 text-right">
                <button class="btn btn-sm btn-outline-primary" onclick="resetFilters()">
                    <i class="fas fa-undo mr-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Search Subjects</label>
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Name, code...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="form-label">Lab Required</label>
                    <select id="labFilter" class="form-control">
                        <option value="">All</option>
                        <option value="yes">Lab Required</option>
                        <option value="no">Theory Only</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Faculty Assignment</label>
                    <select id="facultyFilter" class="form-control">
                        <option value="">All Subjects</option>
                        <option value="with-faculty">With Faculty</option>
                        <option value="without-faculty">Without Faculty</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Course Assignment</label>
                    <select id="courseFilter" class="form-control">
                        <option value="">All Subjects</option>
                        <option value="in-courses">Assigned to Courses</option>
                        <option value="not-in-courses">Not in Courses</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Subjects Table --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list mr-2"></i>All Subjects
            <span class="badge badge-secondary ml-2" id="subjectCount">{{ $subjects->count() }}</span>
        </h6>
        <div class="d-flex align-items-center">
            <span class="mr-3 text-muted">View:</span>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary active" id="tableView">
                    <i class="fas fa-table"></i>
                </button>
                <button type="button" class="btn btn-outline-primary" id="cardView">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Table View --}}
        <div class="table-responsive" id="tableContainer">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 25%;">Subject Details</th>
                        <th style="width: 15%;">Type</th>
                        <th style="width: 25%;">Faculty Assigned</th>
                        <th style="width: 15%;">Usage Stats</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subjects as $subject)
                        <tr class="subject-item" 
                            data-name="{{ strtolower($subject->name) }}" 
                            data-code="{{ strtolower($subject->code ?? '') }}"
                            data-lab="{{ $subject->requires_lab ? 'yes' : 'no' }}"
                            data-has-faculty="{{ ($subject->users && $subject->users->count() > 0) ? 'true' : 'false' }}"
                            data-in-courses="{{ (isset($subject->courses_count) && $subject->courses_count > 0) ? 'true' : 'false' }}">
                            
                            <td>
                                <div>
                                    <strong class="text-gray-800">{{ $subject->name }}</strong>
                                    @if($subject->code)
                                        <br><small class="text-muted">Code: {{ $subject->code }}</small>
                                    @endif
                                    @if($subject->description)
                                        <br><small class="text-muted">{{ Str::limit($subject->description, 50) }}</small>
                                    @endif
                                </div>
                            </td>
                            
                            <td class="text-center">
                                @if($subject->requires_lab)
                                    <span class="badge badge-info px-3 py-2">
                                        <i class="fas fa-flask mr-1"></i>Lab Required
                                    </span>
                                @else
                                    <span class="badge badge-secondary px-3 py-2">
                                        <i class="fas fa-chalkboard-teacher mr-1"></i>Theory Only
                                    </span>
                                @endif
                            </td>
                            
                            <td>
                                <div class="faculty-section">
                                    @if($subject->users && $subject->users->count() > 0)
                                        <div class="mb-2">
                                            @foreach($subject->users->take(2) as $faculty)
                                                <span class="badge badge-success mr-1 mb-1">
                                                    <i class="fas fa-user mr-1"></i>{{ $faculty->name }}
                                                </span>
                                            @endforeach
                                            @if($subject->users->count() > 2)
                                                <span class="badge badge-primary">+{{ $subject->users->count() - 2 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">
                                            <i class="fas fa-user-slash mr-1"></i>No faculty assigned
                                        </span>
                                    @endif
                                    
                                    <div class="mt-2">
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="manageFaculty({{ $subject->id }}, '{{ $subject->name }}')"
                                                data-toggle="modal" data-target="#facultyModal">
                                            <i class="fas fa-users mr-1"></i>Manage Faculty
                                        </button>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <small class="d-block">
                                    <i class="fas fa-graduation-cap mr-1"></i>
                                    Courses: <strong>{{ $subject->courses_count ?? 0 }}</strong>
                                </small>
                                <small class="d-block">
                                    <i class="fas fa-users mr-1"></i>
                                    Faculty: <strong>{{ $subject->users ? $subject->users->count() : 0 }}</strong>
                                </small>
                                @if($subject->requires_lab)
                                    <small class="d-block">
                                        <i class="fas fa-flask mr-1"></i>
                                        Lab Sessions: <strong>{{ $subject->lab_sessions_count ?? 0 }}</strong>
                                    </small>
                                @endif
                            </td>
                            
                            <td>
                                <div class="btn-group-vertical btn-group-sm w-100" role="group">
                                    <a href="{{ route('admin.subjects.edit', $subject) }}" 
                                       class="btn btn-warning btn-sm mb-1" title="Edit Subject">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    
                                    <button class="btn btn-info btn-sm mb-1" 
                                            onclick="viewSubjectDetails({{ $subject->id }})"
                                            title="View Details">
                                        <i class="fas fa-eye mr-1"></i>Details
                                    </button>
                                    
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                data-toggle="dropdown" title="More Actions">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#" onclick="duplicateSubject({{ $subject->id }})">
                                                <i class="fas fa-copy mr-2"></i>Duplicate
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="viewSchedule({{ $subject->id }})">
                                                <i class="fas fa-calendar mr-2"></i>Schedule
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" 
                                               onclick="confirmDelete({{ $subject->id }}, '{{ $subject->name }}')">
                                                <i class="fas fa-trash mr-2"></i>Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-book fa-3x text-gray-300 mb-3"></i>
                                <h5 class="text-gray-500">No Subjects Found</h5>
                                <p class="text-muted mb-4">Start by creating your first subject.</p>
                                <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus mr-2"></i>Create Subject
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Card View (Hidden by default) --}}
        <div id="cardContainer" class="d-none">
            <div class="row">
                @foreach($subjects as $subject)
                    <div class="col-lg-6 col-xl-4 mb-4 subject-card-item" 
                         data-name="{{ strtolower($subject->name) }}" 
                         data-code="{{ strtolower($subject->code ?? '') }}"
                         data-lab="{{ $subject->requires_lab ? 'yes' : 'no' }}"
                         data-has-faculty="{{ ($subject->users && $subject->users->count() > 0) ? 'true' : 'false' }}"
                         data-in-courses="{{ (isset($subject->courses_count) && $subject->courses_count > 0) ? 'true' : 'false' }}">
                        
                        <div class="card border-left-primary h-100">
                            <div class="card-header py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">{{ $subject->name }}</h6>
                                    @if($subject->requires_lab)
                                        <span class="badge badge-info">
                                            <i class="fas fa-flask mr-1"></i>Lab
                                        </span>
                                    @endif
                                </div>
                                @if($subject->code)
                                    <small class="text-muted">{{ $subject->code }}</small>
                                @endif
                            </div>
                            
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong class="text-gray-700">Faculty Assigned:</strong>
                                    <div class="mt-1">
                                        @if($subject->users && $subject->users->count() > 0)
                                            @foreach($subject->users->take(2) as $faculty)
                                                <span class="badge badge-success mr-1 mb-1">{{ $faculty->name }}</span>
                                            @endforeach
                                            @if($subject->users->count() > 2)
                                                <span class="badge badge-primary">+{{ $subject->users->count() - 2 }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">None assigned</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="text-xs text-muted">Courses</div>
                                        <div class="font-weight-bold text-primary">{{ $subject->courses_count ?? 0 }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-xs text-muted">Faculty</div>
                                        <div class="font-weight-bold text-success">{{ $subject->users ? $subject->users->count() : 0 }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-xs text-muted">Type</div>
                                        <div class="font-weight-bold text-info">{{ $subject->requires_lab ? 'Lab' : 'Theory' }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <button class="btn btn-outline-primary" 
                                            onclick="manageFaculty({{ $subject->id }}, '{{ $subject->name }}')"
                                            data-toggle="modal" data-target="#facultyModal">
                                        <i class="fas fa-users"></i>
                                    </button>
                                    <a href="{{ route('admin.subjects.edit', $subject) }}" 
                                       class="btn btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-outline-info" onclick="viewSubjectDetails({{ $subject->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="confirmDelete({{ $subject->id }}, '{{ $subject->name }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Faculty Management Modal --}}
<div class="modal fade" id="facultyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-users mr-2"></i>Manage Faculty for <span id="modalSubjectName"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="facultyAssignmentForm">
                    <input type="hidden" id="modalSubjectId" name="subject_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Available Faculty</h6>
                            <div id="availableFaculty" style="max-height: 300px; overflow-y: auto;">
                                {{-- Populated by JavaScript --}}
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-success">Assigned Faculty</h6>
                            <div id="assignedFaculty" style="max-height: 300px; overflow-y: auto;">
                                {{-- Populated by JavaScript --}}
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveFacultyAssignments()">
                    <i class="fas fa-save mr-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Bulk Actions Modal --}}
<div class="modal fade" id="bulkActionsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tasks mr-2"></i>Bulk Actions
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Select subjects and choose an action to perform on all selected items.</p>
                <div class="form-group">
                    <label>Select Action:</label>
                    <select class="form-control" id="bulkAction">
                        <option value="">Choose an action...</option>
                        <option value="assign-faculty">Assign Faculty</option>
                        <option value="toggle-lab">Toggle Lab Requirement</option>
                        <option value="export">Export Data</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Bulk actions feature is coming soon.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" disabled>Execute Action</button>
            </div>
        </div>
    </div>
</div>

{{-- Custom CSS --}}
<style>
.subject-item:hover {
    background-color: #f8f9fc;
}

.faculty-section {
    min-height: 80px;
}

.badge {
    font-size: 0.75rem;
}

.btn-group-vertical .btn {
    border-radius: 0.25rem !important;
}

.card-footer {
    background-color: #f8f9fc;
}

@media (max-width: 768px) {
    .btn-group-vertical {
        flex-direction: row;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
}
</style>

{{-- JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const labFilter = document.getElementById('labFilter');
    const facultyFilter = document.getElementById('facultyFilter');
    const courseFilter = document.getElementById('courseFilter');
    const tableViewBtn = document.getElementById('tableView');
    const cardViewBtn = document.getElementById('cardView');
    const subjectCount = document.getElementById('subjectCount');

    // Filter functionality
    function filterSubjects() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedLab = labFilter.value;
        const selectedFaculty = facultyFilter.value;
        const selectedCourse = courseFilter.value;

        let visibleCount = 0;
        const items = document.querySelectorAll('.subject-item, .subject-card-item');

        items.forEach(item => {
            const name = item.dataset.name || '';
            const code = item.dataset.code || '';
            const lab = item.dataset.lab || '';
            const hasFaculty = item.dataset.hasFaculty === 'true';
            const inCourses = item.dataset.inCourses === 'true';

            let shouldShow = true;

            // Search filter
            if (searchTerm && !name.includes(searchTerm) && !code.includes(searchTerm)) {
                shouldShow = false;
            }

            // Lab filter
            if (selectedLab && lab !== selectedLab) {
                shouldShow = false;
            }

            // Faculty filter
            if (selectedFaculty === 'with-faculty' && !hasFaculty) {
                shouldShow = false;
            } else if (selectedFaculty === 'without-faculty' && hasFaculty) {
                shouldShow = false;
            }

            // Course filter
            if (selectedCourse === 'in-courses' && !inCourses) {
                shouldShow = false;
            } else if (selectedCourse === 'not-in-courses' && inCourses) {
                shouldShow = false;
            }

            if (shouldShow) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        subjectCount.textContent = visibleCount;
    }

    // Event listeners
    searchInput.addEventListener('input', filterSubjects);
    labFilter.addEventListener('change', filterSubjects);
    facultyFilter.addEventListener('change', filterSubjects);
    courseFilter.addEventListener('change', filterSubjects);

    // View toggle
    tableViewBtn.addEventListener('click', function() {
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
        document.getElementById('tableContainer').classList.remove('d-none');
        document.getElementById('cardContainer').classList.add('d-none');
    });

    cardViewBtn.addEventListener('click', function() {
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
        document.getElementById('tableContainer').classList.add('d-none');
        document.getElementById('cardContainer').classList.remove('d-none');
    });

    // Reset filters
    window.resetFilters = function() {
        searchInput.value = '';
        labFilter.value = '';
        facultyFilter.value = '';
        courseFilter.value = '';
        filterSubjects();
    };
});

// Faculty management functions
function manageFaculty(subjectId, subjectName) {
    document.getElementById('modalSubjectId').value = subjectId;
    document.getElementById('modalSubjectName').textContent = subjectName;
    
    // Load faculty data via AJAX
    loadFacultyData(subjectId);
}

function loadFacultyData(subjectId) {
    // This would typically be an AJAX call to get faculty data
    // For now, we'll show a loading message
    document.getElementById('availableFaculty').innerHTML = '<p class="text-muted">Loading available faculty...</p>';
    document.getElementById('assignedFaculty').innerHTML = '<p class="text-muted">Loading assigned faculty...</p>';
    
    // You would implement the actual AJAX call here
    // fetch(`/admin/subjects/${subjectId}/faculty`)...
}

function saveFacultyAssignments() {
    const subjectId = document.getElementById('modalSubjectId').value;
    
    // This would typically be an AJAX call to save assignments
    alert('Faculty assignments saved! (This would be implemented with actual AJAX)');
    $('#facultyModal').modal('hide');
    location.reload(); // Refresh to show updated data
}

// Other functions
function viewSubjectDetails(subjectId) {
    alert(`View details for subject ${subjectId} (implement as needed)`);
}

function duplicateSubject(subjectId) {
    if (confirm('Create a copy of this subject?')) {
        // Implement duplication logic
        alert('Subject duplication feature coming soon!');
    }
}

function viewSchedule(subjectId) {
    alert(`View schedule for subject ${subjectId} (implement as needed)`);
}

function confirmDelete(subjectId, subjectName) {
    if (confirm(`Are you sure you want to delete "${subjectName}"? This action cannot be undone.`)) {
        // Create and submit delete form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/subjects/${subjectId}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

{{-- Additional JavaScript for Faculty Management --}}
<script>
// Enhanced Faculty Management Functions
function loadFacultyData(subjectId) {
    // Show loading state
    document.getElementById('availableFaculty').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    document.getElementById('assignedFaculty').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    // Fetch faculty data using Laravel route helper
    const url = `{{ route('admin.subjects.faculty-data', ['subject' => ':id']) }}`.replace(':id', subjectId);
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Faculty data received:', data); // Debug log
            if (data.success) {
                populateFacultyLists(data);
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error loading faculty data:', error);
            document.getElementById('availableFaculty').innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            document.getElementById('assignedFaculty').innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}

function populateFacultyLists(data) {
    const availableContainer = document.getElementById('availableFaculty');
    const assignedContainer = document.getElementById('assignedFaculty');
    
    // Clear containers
    availableContainer.innerHTML = '';
    assignedContainer.innerHTML = '';
    
    // Populate available faculty
    if (data.available && data.available.length > 0) {
        data.available.forEach(faculty => {
            const facultyItem = createFacultyItem(faculty, 'available');
            availableContainer.appendChild(facultyItem);
        });
    } else {
        availableContainer.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>No available faculty members. All staff are either assigned to this subject or don\'t have the staff role.</div>';
    }
    
    // Populate assigned faculty
    if (data.assigned && data.assigned.length > 0) {
        data.assigned.forEach(faculty => {
            const facultyItem = createFacultyItem(faculty, 'assigned');
            assignedContainer.appendChild(facultyItem);
        });
    } else {
        assignedContainer.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>No faculty assigned to this subject yet.</div>';
    }
    
    // Debug info (remove this in production)
    if (data.debug) {
        console.log('Debug info:', data.debug);
    }
}

function createFacultyItem(faculty, type) {
    const div = document.createElement('div');
    div.className = 'faculty-item p-2 mb-2 border rounded';
    
    const actionButton = type === 'available' 
        ? `<button class="btn btn-sm btn-success" onclick="assignFaculty(${faculty.id})">
             <i class="fas fa-plus"></i> Assign
           </button>`
        : `<button class="btn btn-sm btn-danger" onclick="removeFaculty(${faculty.id})">
             <i class="fas fa-minus"></i> Remove
           </button>`;
    
    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>${faculty.name}</strong>
                <br><small class="text-muted">${faculty.email}</small>
                ${faculty.department ? `<br><small class="text-muted">${faculty.department}</small>` : ''}
            </div>
            <div>
                ${actionButton}
            </div>
        </div>
    `;
    
    return div;
}

function assignFaculty(facultyId) {
    const subjectId = document.getElementById('modalSubjectId').value;
    const url = `{{ route('admin.subjects.assign-faculty', ['subject' => ':id']) }}`.replace(':id', subjectId);
    
    // Make AJAX call to assign faculty
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            faculty_id: facultyId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload faculty data to reflect changes
            loadFacultyData(subjectId);
            showToast(data.message || 'Faculty assigned successfully', 'success');
        } else {
            showToast(data.message || 'Error assigning faculty', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error assigning faculty', 'error');
    });
}

function removeFaculty(facultyId) {
    const subjectId = document.getElementById('modalSubjectId').value;
    const url = `{{ route('admin.subjects.remove-faculty', ['subject' => ':id']) }}`.replace(':id', subjectId);
    
    // Make AJAX call to remove faculty
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            faculty_id: facultyId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload faculty data to reflect changes
            loadFacultyData(subjectId);
            showToast(data.message || 'Faculty removed successfully', 'success');
        } else {
            showToast(data.message || 'Error removing faculty', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error removing faculty', 'error');
    });
}

function saveFacultyAssignments() {
    // Close modal and refresh page to show updated data
    $('#facultyModal').modal('hide');
    showToast('Faculty assignments updated successfully', 'success');
    
    // Reload the page after a short delay to show updates
    setTimeout(() => {
        location.reload();
    }, 1500);
}

// Toast notification function
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'} mr-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Show toast and auto-remove after 3 seconds
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Initialize tooltips and other Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

@endsection