@extends('layouts.theme')
@section('title', 'Faculty Management')

@section('content')
{{-- Header Section --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users text-primary mr-2"></i>Faculty Management
        </h1>
        <p class="text-muted mb-0">Manage faculty members, their subjects, and assignments</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.faculty.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm mr-1"></i> Add New Faculty
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
                            Total Faculty
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $faculties->count() }}</div>
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
                            With Subjects
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $faculties->filter(function($f) { return $f->subjects->count() > 0; })->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
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
                            Active Today
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $faculties->where('last_activity', '>=', now()->startOfDay())->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            Departments
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $faculties->pluck('department')->filter()->unique()->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-building fa-2x text-gray-300"></i>
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
                    <label class="form-label">Search Faculty</label>
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Name, email, employee ID...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select id="departmentFilter" class="form-control">
                        <option value="">All Departments</option>
                        @foreach($faculties->pluck('department')->filter()->unique()->sort() as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Subject Assignment</label>
                    <select id="subjectFilter" class="form-control">
                        <option value="">All Faculty</option>
                        <option value="with-subjects">With Subjects</option>
                        <option value="without-subjects">Without Subjects</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Faculty Cards/Table --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list mr-2"></i>Faculty Members
            <span class="badge badge-secondary ml-2" id="facultyCount">{{ $faculties->count() }}</span>
        </h6>
        <div class="d-flex align-items-center">
            <span class="mr-3 text-muted">View:</span>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary active" id="cardView">
                    <i class="fas fa-th-large"></i>
                </button>
                <button type="button" class="btn btn-outline-primary" id="tableView">
                    <i class="fas fa-table"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        @forelse ($faculties as $faculty)
            {{-- Card View --}}
            <div class="faculty-card faculty-item mb-3" 
                 data-name="{{ strtolower($faculty->name) }}" 
                 data-email="{{ strtolower($faculty->email) }}"
                 data-department="{{ strtolower($faculty->department ?? '') }}"
                 data-employee-id="{{ strtolower($faculty->employee_id ?? '') }}"
                 data-has-subjects="{{ $faculty->subjects->count() > 0 ? 'true' : 'false' }}">
                
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <div class="faculty-avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-user fa-lg"></i>
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-{{ $faculty->subjects->count() > 0 ? 'success' : 'warning' }}">
                                        {{ $faculty->subjects->count() }} Subject{{ $faculty->subjects->count() != 1 ? 's' : '' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="font-weight-bold text-gray-800 mb-1">
                                    {{ $faculty->name }}
                                    @if($faculty->employee_id)
                                        <small class="text-muted">({{ $faculty->employee_id }})</small>
                                    @endif
                                </h6>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-envelope fa-sm mr-1"></i>{{ $faculty->email }}
                                </p>
                                @if($faculty->phone)
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-phone fa-sm mr-1"></i>{{ $faculty->phone }}
                                    </p>
                                @endif
                                @if($faculty->department)
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-building fa-sm mr-1"></i>{{ $faculty->department }}
                                    </p>
                                @endif
                            </div>
                            
                            <div class="col-md-4">
                                @if($faculty->subjects->count() > 0)
                                    <div class="mb-2">
                                        <small class="text-muted font-weight-bold">Assigned Subjects:</small>
                                        <div class="mt-1">
                                            @foreach($faculty->subjects->take(3) as $subject)
                                                <span class="badge badge-info mr-1 mb-1">
                                                    {{ $subject->name }}
                                                    @if($subject->requires_lab)
                                                        <i class="fas fa-flask ml-1"></i>
                                                    @endif
                                                </span>
                                            @endforeach
                                            @if($faculty->subjects->count() > 3)
                                                <span class="badge badge-secondary">+{{ $faculty->subjects->count() - 3 }} more</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <a href="{{ route('admin.faculty.subjects.edit', $faculty) }}" 
                                       class="btn btn-info" title="Manage Subjects">
                                        <i class="fas fa-book mr-1"></i>Subjects
                                    </a>
                                    <a href="{{ route('admin.faculty.salary.show', $faculty) }}" 
                                       class="btn btn-success" title="Manage Salary">
                                        <i class="fas fa-dollar-sign mr-1"></i>Salary
                                    </a>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                data-toggle="dropdown" title="More Actions">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.faculty.edit', $faculty) }}">
                                                <i class="fas fa-edit mr-2"></i>Edit Details
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="viewSchedule({{ $faculty->id }})">
                                                <i class="fas fa-calendar mr-2"></i>View Schedule
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="viewAttendance({{ $faculty->id }})">
                                                <i class="fas fa-clock mr-2"></i>Attendance
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" 
                                               onclick="confirmDelete({{ $faculty->id }}, '{{ $faculty->name }}')">
                                                <i class="fas fa-trash mr-2"></i>Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table View (Hidden by default) --}}
            <tr class="faculty-table-row faculty-item d-none" 
                data-name="{{ strtolower($faculty->name) }}" 
                data-email="{{ strtolower($faculty->email) }}"
                data-department="{{ strtolower($faculty->department ?? '') }}"
                data-employee-id="{{ strtolower($faculty->employee_id ?? '') }}"
                data-has-subjects="{{ $faculty->subjects->count() > 0 ? 'true' : 'false' }}">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="faculty-avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold">{{ $faculty->name }}</div>
                            @if($faculty->employee_id)
                                <small class="text-muted">{{ $faculty->employee_id }}</small>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div>{{ $faculty->email }}</div>
                    @if($faculty->phone)
                        <small class="text-muted">{{ $faculty->phone }}</small>
                    @endif
                </td>
                <td>{{ $faculty->department ?? 'N/A' }}</td>
                <td>
                    <span class="badge badge-{{ $faculty->subjects->count() > 0 ? 'success' : 'warning' }}">
                        {{ $faculty->subjects->count() }} Subject{{ $faculty->subjects->count() != 1 ? 's' : '' }}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('admin.faculty.subjects.edit', $faculty) }}" 
                           class="btn btn-info btn-sm" title="Manage Subjects">
                            <i class="fas fa-book"></i>
                        </a>
                        <a href="{{ route('admin.faculty.salary.show', $faculty) }}" 
                           class="btn btn-success btn-sm" title="Manage Salary">
                            <i class="fas fa-dollar-sign"></i>
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                data-toggle="dropdown" title="More Actions">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ route('admin.faculty.edit', $faculty) }}">
                                <i class="fas fa-edit mr-2"></i>Edit
                            </a>
                            <a class="dropdown-item text-danger" href="#" 
                               onclick="confirmDelete({{ $faculty->id }}, '{{ $faculty->name }}')">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                <h5 class="text-gray-500">No Faculty Members Found</h5>
                <p class="text-muted mb-4">No faculty members with the 'staff' role exist yet.</p>
                <a href="{{ route('admin.faculty.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>Create Your First Faculty Member
                </a>
            </div>
        @endforelse

        {{-- Table Headers (Hidden by default) --}}
        <table class="table table-bordered d-none" id="facultyTable">
            <thead class="thead-light">
                <tr>
                    <th>Faculty</th>
                    <th>Contact</th>
                    <th>Department</th>
                    <th>Subjects</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody id="facultyTableBody">
                {{-- Table rows are populated by JavaScript --}}
            </tbody>
        </table>
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
                <p class="text-muted">Select faculty members and choose an action to perform on all selected items.</p>
                <div class="form-group">
                    <label>Select Action:</label>
                    <select class="form-control" id="bulkAction">
                        <option value="">Choose an action...</option>
                        <option value="assign-subject">Assign Subject</option>
                        <option value="remove-subject">Remove Subject</option>
                        <option value="change-department">Change Department</option>
                        <option value="export">Export Data</option>
                    </select>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    This feature is coming soon in the next update.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" disabled>Execute Action</button>
            </div>
        </div>
    </div>
</div>

{{-- Custom Styles --}}
<style>
.faculty-avatar {
    font-size: 1.2rem;
}

.faculty-card {
    transition: all 0.3s ease;
}

.faculty-card:hover {
    transform: translateY(-2px);
}

.badge {
    font-size: 0.75rem;
}

.btn-group-sm .btn {
    font-size: 0.75rem;
}

.card-body .row {
    align-items: center;
}

@media (max-width: 768px) {
    .faculty-card .row {
        text-align: center;
    }
    
    .faculty-card .col-md-4 {
        margin-top: 1rem;
    }
}
</style>

{{-- JavaScript for Filtering and View Toggle --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const departmentFilter = document.getElementById('departmentFilter');
    const subjectFilter = document.getElementById('subjectFilter');
    const statusFilter = document.getElementById('statusFilter');
    const cardViewBtn = document.getElementById('cardView');
    const tableViewBtn = document.getElementById('tableView');
    const facultyTable = document.getElementById('facultyTable');
    const facultyCards = document.querySelectorAll('.faculty-card');
    const facultyTableRows = document.querySelectorAll('.faculty-table-row');
    const facultyCount = document.getElementById('facultyCount');

    // Filter functionality
    function filterFaculty() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedDept = departmentFilter.value.toLowerCase();
        const selectedSubject = subjectFilter.value;
        const selectedStatus = statusFilter.value;

        let visibleCount = 0;

        document.querySelectorAll('.faculty-item').forEach(item => {
            const name = item.dataset.name || '';
            const email = item.dataset.email || '';
            const department = item.dataset.department || '';
            const employeeId = item.dataset.employeeId || '';
            const hasSubjects = item.dataset.hasSubjects === 'true';

            let shouldShow = true;

            // Search filter
            if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm) && !employeeId.includes(searchTerm)) {
                shouldShow = false;
            }

            // Department filter
            if (selectedDept && department !== selectedDept) {
                shouldShow = false;
            }

            // Subject filter
            if (selectedSubject === 'with-subjects' && !hasSubjects) {
                shouldShow = false;
            } else if (selectedSubject === 'without-subjects' && hasSubjects) {
                shouldShow = false;
            }

            if (shouldShow) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        facultyCount.textContent = visibleCount;
    }

    // Event listeners
    searchInput.addEventListener('input', filterFaculty);
    departmentFilter.addEventListener('change', filterFaculty);
    subjectFilter.addEventListener('change', filterFaculty);
    statusFilter.addEventListener('change', filterFaculty);

    // View toggle functionality
    cardViewBtn.addEventListener('click', function() {
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
        facultyTable.classList.add('d-none');
        facultyCards.forEach(card => card.classList.remove('d-none'));
    });

    tableViewBtn.addEventListener('click', function() {
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
        facultyTable.classList.remove('d-none');
        facultyCards.forEach(card => card.classList.add('d-none'));
        
        // Move table rows to table body
        const tableBody = document.getElementById('facultyTableBody');
        facultyTableRows.forEach(row => {
            row.classList.remove('d-none');
            tableBody.appendChild(row);
        });
    });

    // Reset filters
    window.resetFilters = function() {
        searchInput.value = '';
        departmentFilter.value = '';
        subjectFilter.value = '';
        statusFilter.value = '';
        filterFaculty();
    };

    // Delete confirmation
    window.confirmDelete = function(facultyId, facultyName) {
        if (confirm(`Are you sure you want to delete faculty member "${facultyName}"? This action cannot be undone.`)) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/faculty/${facultyId}`;
            
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
    };

    // Placeholder functions for future features
    window.viewSchedule = function(facultyId) {
        alert('Schedule view feature coming soon!');
    };

    window.viewAttendance = function(facultyId) {
        alert('Attendance view feature coming soon!');
    };
});
</script>
@endsection