@extends('layouts.theme')
@section('title', 'Faculty Management')

@section('content')
<div class="container-fluid">
    {{-- Header Section --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users-cog text-primary mr-2"></i>Faculty Management
            </h1>
            <p class="text-muted mb-0">Manage university staff members, subject assignments, and timing rules</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('admin.faculty.create') }}" class="btn btn-primary px-4 shadow-sm btn-hover-scale">
                <i class="fas fa-plus mr-1"></i> Add New Faculty
            </a>
            <a href="{{ route('admin.faculty-attendance.index') }}" class="btn btn-outline-success px-4 ml-2 btn-hover-scale">
                <i class="fas fa-calendar-check mr-1"></i> Attendance Dashboard
            </a>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-left-success shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-2 text-success"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-left-danger shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle mr-2 text-danger"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    {{-- Statistics Row --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-body gradient-primary text-white position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase opacity-75 mb-1">Total Faculty</div>
                            <div class="h3 mb-0 font-weight-bold">{{ $faculties->count() }}</div>
                        </div>
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-users fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-body gradient-success text-white position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase opacity-75 mb-1">Salary Template Setup</div>
                            <div class="h3 mb-0 font-weight-bold">
                                {{ $faculties->filter(function($f) { return $f->salaryTemplate !== null; })->count() }}
                            </div>
                        </div>
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-file-invoice-dollar fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-body gradient-info text-white position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase opacity-75 mb-1">Subjects Assigned</div>
                            <div class="h3 mb-0 font-weight-bold">
                                {{ $faculties->pluck('subjects')->flatten()->unique('id')->count() }}
                            </div>
                        </div>
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-book-reader fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-body gradient-warning text-white position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase opacity-75 mb-1">Active Departments</div>
                            <div class="h3 mb-0 font-weight-bold">
                                {{ $faculties->pluck('department')->filter()->unique()->count() }}
                            </div>
                        </div>
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-university fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm mb-4 glass-card">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                    <div class="search-box position-relative">
                        <input type="text" id="searchInput" class="form-control border-light-subtle rounded-pill pl-4 text-sm" 
                               placeholder="Search name, email, employee ID...">
                        <i class="fas fa-search search-icon text-muted"></i>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                    <select id="departmentFilter" class="form-control rounded-pill border-light-subtle text-sm">
                        <option value="">All Departments</option>
                        @foreach($faculties->pluck('department')->filter()->unique()->sort() as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                    <select id="templateFilter" class="form-control rounded-pill border-light-subtle text-sm">
                        <option value="">All Payroll Templates</option>
                        <option value="with-template">Payroll Setup Done</option>
                        <option value="without-template">Payroll Pending</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                    <select id="subjectFilter" class="form-control rounded-pill border-light-subtle text-sm">
                        <option value="">All Subjects</option>
                        <option value="with-subject">Subjects Assigned</option>
                        <option value="without-subject">No Subjects Assigned</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-12 text-right d-flex align-items-center justify-content-end gap-2">
                    <button class="btn btn-light rounded-pill px-3 text-sm" onclick="resetFilters()">
                        <i class="fas fa-undo mr-1 text-xs"></i> Reset
                    </button>
                    <div class="btn-group btn-group-sm rounded-pill overflow-hidden border ml-2 shadow-sm" role="group">
                        <button type="button" class="btn btn-white border-0 px-3 py-2 active" id="cardView" title="Grid View">
                            <i class="fas fa-th-large text-primary"></i>
                        </button>
                        <button type="button" class="btn btn-white border-0 px-3 py-2" id="tableView" title="List View">
                            <i class="fas fa-list-ul text-muted"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Body --}}
    @if ($faculties->isEmpty())
        <div class="card border-0 shadow-sm py-5 text-center glass-card">
            <div class="card-body">
                <i class="fas fa-user-friends fa-4x text-gray-300 mb-3"></i>
                <h4 class="font-weight-bold text-gray-700">No Faculty Registered</h4>
                <p class="text-muted mb-4">You have not registered any faculty members under the 'staff' role yet.</p>
                <a href="{{ route('admin.faculty.create') }}" class="btn btn-primary px-4 shadow-sm btn-hover-scale">
                    <i class="fas fa-plus mr-1"></i> Register First Faculty
                </a>
            </div>
        </div>
    @else
        {{-- Grid Container --}}
        <div id="facultyCardsContainer" class="row">
            @foreach ($faculties as $faculty)
                @php
                    $words = explode(' ', $faculty->name);
                    $initials = '';
                    foreach ($words as $w) {
                        $initials .= strtoupper(substr($w, 0, 1));
                        if (strlen($initials) >= 2) break;
                    }
                    // Color HSL generation based on name hash
                    $hash = md5($faculty->name);
                    $hue1 = hexdec(substr($hash, 0, 2)) % 360;
                    $hue2 = ($hue1 + 40) % 360;
                    $avatarBg = "linear-gradient(135deg, hsl({$hue1}, 70%, 50%), hsl({$hue2}, 75%, 42%))";
                @endphp
                <div class="col-xl-4 col-md-6 mb-4 faculty-card faculty-item"
                     data-name="{{ strtolower($faculty->name) }}" 
                     data-email="{{ strtolower($faculty->email) }}"
                     data-department="{{ strtolower($faculty->department ?? '') }}"
                     data-employee-id="{{ strtolower($faculty->employee_id ?? '') }}"
                     data-has-template="{{ $faculty->salaryTemplate ? 'true' : 'false' }}"
                     data-has-subject="{{ $faculty->subjects->count() > 0 ? 'true' : 'false' }}">
                    
                    <div class="card h-100 border-0 shadow-sm hover-card overflow-hidden">
                        {{-- Top color strip based on payroll template --}}
                        <div class="card-strip {{ $faculty->salaryTemplate ? 'bg-success' : 'bg-warning' }}" style="height: 4px;"></div>
                        
                        <div class="card-body position-relative">
                            {{-- Header Content --}}
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-circle mr-3 text-white font-weight-bold d-flex align-items-center justify-content-center"
                                     style="background: {{ $avatarBg }}; width: 52px; height: 52px; font-size: 1.15rem; border-radius: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    {{ $initials }}
                                </div>
                                <div class="overflow-hidden">
                                    <h5 class="font-weight-bold text-gray-800 text-truncate mb-0" title="{{ $faculty->name }}">
                                        {{ $faculty->name }}
                                    </h5>
                                    @if($faculty->employee_id)
                                        <code class="text-xs text-primary font-weight-bold">{{ $faculty->employee_id }}</code>
                                    @else
                                        <span class="text-xs text-muted">ID: N/A</span>
                                    @endif
                                </div>
                                <div class="ml-auto dropdown">
                                    <button class="btn btn-link text-muted p-1" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right shadow-sm border-0">
                                        <a class="dropdown-item" href="{{ route('admin.faculty.edit', $faculty) }}">
                                            <i class="fas fa-edit mr-2 text-muted"></i> Edit Details
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="viewAttendance('{{ $faculty->name }}')">
                                            <i class="fas fa-clock mr-2 text-muted"></i> View Attendance Logs
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="#" 
                                           onclick="confirmDelete({{ $faculty->id }}, '{{ $faculty->name }}')">
                                            <i class="fas fa-trash mr-2"></i> Delete Faculty
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Detailed Metadata --}}
                            <div class="faculty-metadata text-sm text-muted mb-3 space-y-1">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-envelope text-muted mr-2" style="width: 16px;"></i>
                                    <span class="text-truncate">{{ $faculty->email }}</span>
                                </div>
                                @if($faculty->phone)
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-phone text-muted mr-2" style="width: 16px;"></i>
                                        <span>{{ $faculty->phone }}</span>
                                    </div>
                                @endif
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-building text-muted mr-2" style="width: 16px;"></i>
                                    <span>{{ $faculty->department ?? 'General Staff' }}</span>
                                </div>
                            </div>

                            {{-- Assigned Subjects --}}
                            <div class="mb-3">
                                <small class="text-muted font-weight-bold d-block mb-1">Assigned Subjects:</small>
                                @if($faculty->subjects->count() > 0)
                                    <div class="d-flex flex-wrap gap-1" style="max-height: 58px; overflow: hidden;">
                                        @foreach($faculty->subjects->take(4) as $subject)
                                            <span class="badge badge-light-blue text-xs px-2 py-1 mr-1 mb-1">{{ $subject->name }}</span>
                                        @endforeach
                                        @if($faculty->subjects->count() > 4)
                                            <span class="badge badge-light text-xs px-2 py-1 mr-1 mb-1">+{{ $faculty->subjects->count() - 4 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <small class="text-warning italic d-flex align-items-center">
                                        <i class="fas fa-exclamation-circle text-xs mr-1"></i> No subjects assigned
                                    </small>
                                @endif
                            </div>

                            {{-- Payroll Integration Badge --}}
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <div>
                                    <small class="text-muted d-block">Payroll System</small>
                                    @if($faculty->salaryTemplate)
                                        <span class="text-xs text-success font-weight-bold d-flex align-items-center">
                                            <span class="dot-indicator bg-success mr-1"></span> {{ $faculty->salaryTemplate->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-warning font-weight-bold d-flex align-items-center">
                                            <span class="dot-indicator bg-warning mr-1"></span> Setup Pending
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.faculty.subjects.edit', $faculty) }}" 
                                       class="btn btn-xs btn-outline-info rounded-pill px-3" title="Edit Subjects">
                                        <i class="fas fa-book text-xs mr-1"></i> Subjects
                                    </a>
                                    <a href="{{ route('admin.faculty.salary.show', $faculty) }}" 
                                       class="btn btn-xs btn-outline-success rounded-pill px-3" title="Edit Salary">
                                        <i class="fas fa-dollar-sign text-xs mr-1"></i> Salary
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Table View Container (Hidden by default) --}}
        <div id="facultyTableContainer" class="card border-0 shadow-sm overflow-hidden d-none mb-4 glass-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-items-center">
                        <thead class="bg-light text-uppercase text-xs font-weight-bold">
                            <tr>
                                <th class="px-4 py-3">Faculty Member</th>
                                <th>Contact Information</th>
                                <th>Department</th>
                                <th>Assigned Subjects</th>
                                <th>Payroll Template</th>
                                <th class="px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @foreach ($faculties as $faculty)
                                @php
                                    $words = explode(' ', $faculty->name);
                                    $initials = '';
                                    foreach ($words as $w) {
                                        $initials .= strtoupper(substr($w, 0, 1));
                                        if (strlen($initials) >= 2) break;
                                    }
                                    $hash = md5($faculty->name);
                                    $hue = hexdec(substr($hash, 0, 2)) % 360;
                                    $avBg = "linear-gradient(135deg, hsl({$hue}, 65%, 52%), hsl(" . (($hue+30)%360) . ", 65%, 45%))";
                                @endphp
                                <tr class="faculty-table-row faculty-item"
                                    data-name="{{ strtolower($faculty->name) }}" 
                                    data-email="{{ strtolower($faculty->email) }}"
                                    data-department="{{ strtolower($faculty->department ?? '') }}"
                                    data-employee-id="{{ strtolower($faculty->employee_id ?? '') }}"
                                    data-has-template="{{ $faculty->salaryTemplate ? 'true' : 'false' }}"
                                    data-has-subject="{{ $faculty->subjects->count() > 0 ? 'true' : 'false' }}">
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle mr-3 text-white text-xs font-weight-bold d-flex align-items-center justify-content-center"
                                                 style="background: {{ $avBg }}; width: 36px; height: 36px; border-radius: 10px;">
                                                {{ $initials }}
                                            </div>
                                            <div>
                                                <div class="font-weight-bold text-gray-800">{{ $faculty->name }}</div>
                                                @if($faculty->employee_id)
                                                    <code class="text-xs text-primary">{{ $faculty->employee_id }}</code>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><i class="fas fa-envelope text-xs text-muted mr-1"></i>{{ $faculty->email }}</div>
                                        @if($faculty->phone)
                                            <div class="text-muted text-xs"><i class="fas fa-phone text-xs text-muted mr-1"></i>{{ $faculty->phone }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light border px-2 py-1">{{ $faculty->department ?? 'General Staff' }}</span>
                                    </td>
                                    <td>
                                        @if($faculty->subjects->count() > 0)
                                            @foreach($faculty->subjects->take(2) as $subj)
                                                <span class="badge badge-light-blue mr-1 mb-1">{{ $subj->name }}</span>
                                            @endforeach
                                            @if($faculty->subjects->count() > 2)
                                                <span class="badge badge-light">+{{ $faculty->subjects->count() - 2 }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted text-xs italic">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($faculty->salaryTemplate)
                                            <span class="badge badge-success px-2 py-1 text-xs font-weight-bold">
                                                <i class="fas fa-check-circle mr-1"></i>{{ $faculty->salaryTemplate->name }}
                                            </span>
                                        @else
                                            <span class="badge badge-warning px-2 py-1 text-xs font-weight-bold">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 text-right">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.faculty.subjects.edit', $faculty) }}" 
                                               class="btn btn-light border" title="Subjects">
                                                <i class="fas fa-book text-info"></i>
                                            </a>
                                            <a href="{{ route('admin.faculty.salary.show', $faculty) }}" 
                                               class="btn btn-light border" title="Salary">
                                                <i class="fas fa-dollar-sign text-success"></i>
                                            </a>
                                            <a href="{{ route('admin.faculty.edit', $faculty) }}" 
                                               class="btn btn-light border" title="Edit">
                                                <i class="fas fa-edit text-primary"></i>
                                            </a>
                                            <button type="button" class="btn btn-light border text-danger" 
                                                    onclick="confirmDelete({{ $faculty->id }}, '{{ $faculty->name }}')" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Custom CSS Styles --}}
<style>
/* Stat Cards Gradients */
.gradient-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}
.gradient-success {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
}
.gradient-info {
    background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
}
.gradient-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
}

.stat-card {
    border-radius: 16px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
}
.stat-icon-wrapper {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}

/* Glassmorphism Effect */
.glass-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 16px;
}

/* Faculty Cards */
.hover-card {
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.hover-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.06) !important;
}

.badge-light-blue {
    background-color: #ebf5ff;
    color: #1e429f;
    border-radius: 6px;
    font-weight: 600;
}

.dot-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}

/* Scale Animations */
.btn-hover-scale {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.btn-hover-scale:hover {
    transform: scale(1.03);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.search-box .search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.85rem;
}
.search-box input {
    padding-left: 35px !important;
}
</style>

{{-- JS Scripts --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const departmentFilter = document.getElementById('departmentFilter');
    const templateFilter = document.getElementById('templateFilter');
    const subjectFilter = document.getElementById('subjectFilter');
    const cardViewBtn = document.getElementById('cardView');
    const tableViewBtn = document.getElementById('tableView');
    const facultyCardsContainer = document.getElementById('facultyCardsContainer');
    const facultyTableContainer = document.getElementById('facultyTableContainer');
    const facultyCountBadge = document.getElementById('facultyCount');

    // Filter Logic
    function filterFaculty() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedDept = departmentFilter.value.toLowerCase();
        const selectedTemplate = templateFilter.value;
        const selectedSubject = subjectFilter.value;

        let visibleCount = 0;

        document.querySelectorAll('.faculty-item').forEach(item => {
            const name = item.dataset.name || '';
            const email = item.dataset.email || '';
            const department = item.dataset.department || '';
            const employeeId = item.dataset.employeeId || '';
            const hasTemplate = item.dataset.hasTemplate === 'true';
            const hasSubject = item.dataset.hasSubject === 'true';

            let shouldShow = true;

            // Search query match
            if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm) && !employeeId.includes(searchTerm)) {
                shouldShow = false;
            }

            // Department filter match
            if (selectedDept && department !== selectedDept) {
                shouldShow = false;
            }

            // Payroll Template match
            if (selectedTemplate === 'with-template' && !hasTemplate) {
                shouldShow = false;
            } else if (selectedTemplate === 'without-template' && hasTemplate) {
                shouldShow = false;
            }

            // Subject Assigned match
            if (selectedSubject === 'with-subject' && !hasSubject) {
                shouldShow = false;
            } else if (selectedSubject === 'without-subject' && hasSubject) {
                shouldShow = false;
            }

            if (shouldShow) {
                item.style.display = '';
                // Only count unique users (since same user is rendered in grid and table list)
                if (item.classList.contains('faculty-card')) {
                    visibleCount++;
                }
            } else {
                item.style.display = 'none';
            }
        });

        if (facultyCountBadge) {
            facultyCountBadge.textContent = visibleCount;
        }
    }

    // Bind inputs
    if (searchInput) searchInput.addEventListener('input', filterFaculty);
    if (departmentFilter) departmentFilter.addEventListener('change', filterFaculty);
    if (templateFilter) templateFilter.addEventListener('change', filterFaculty);
    if (subjectFilter) subjectFilter.addEventListener('change', filterFaculty);

    // Toggle Views
    if (cardViewBtn && tableViewBtn) {
        cardViewBtn.addEventListener('click', function() {
            cardViewBtn.classList.add('active');
            cardViewBtn.querySelector('i').classList.replace('text-muted', 'text-primary');
            tableViewBtn.classList.remove('active');
            tableViewBtn.querySelector('i').classList.replace('text-primary', 'text-muted');
            
            if (facultyCardsContainer) facultyCardsContainer.classList.remove('d-none');
            if (facultyTableContainer) facultyTableContainer.classList.add('d-none');
        });

        tableViewBtn.addEventListener('click', function() {
            tableViewBtn.classList.add('active');
            tableViewBtn.querySelector('i').classList.replace('text-muted', 'text-primary');
            cardViewBtn.classList.remove('active');
            cardViewBtn.querySelector('i').classList.replace('text-primary', 'text-muted');
            
            if (facultyCardsContainer) facultyCardsContainer.classList.add('d-none');
            if (facultyTableContainer) facultyTableContainer.classList.remove('d-none');
        });
    }

    // Reset Filtering
    window.resetFilters = function() {
        if (searchInput) searchInput.value = '';
        if (departmentFilter) departmentFilter.value = '';
        if (templateFilter) templateFilter.value = '';
        if (subjectFilter) subjectFilter.value = '';
        filterFaculty();
    };

    // Navigate to Attendance logs with search pre-filled
    window.viewAttendance = function(facultyName) {
        window.location.href = "{{ route('admin.faculty-attendance.index') }}?search=" + encodeURIComponent(facultyName);
    };

    // Confirm Faculty deletion
    window.confirmDelete = function(facultyId, facultyName) {
        if (confirm(`Are you sure you want to delete "${facultyName}"? This action is permanent and will remove their subjects, logs, and parameters.`)) {
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
});
</script>
@endpush
@endsection