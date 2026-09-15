@extends('layouts.theme')

@section('title', 'Mentor Allocation Management')

@push('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: none;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .badge-pill-custom {
        padding: 6px 12px;
        font-weight: 600;
        font-size: 0.78rem;
    }
    .mentor-chip {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #e3e6f0;
        border-radius: 20px;
        padding: 5px 12px;
        display: inline-flex;
        align-items: center;
        margin: 3px;
        background: #fff;
        font-size: 0.85rem;
    }
    .mentor-chip:hover, .mentor-chip.active {
        background: #4e73df;
        color: #fff;
        border-color: #4e73df;
    }
    .mentor-chip.active .badge-count {
        background: #fff;
        color: #4e73df;
    }
    .badge-count {
        background: #eaecf4;
        color: #4e73df;
        font-weight: 700;
        border-radius: 12px;
        padding: 2px 7px;
        font-size: 0.75rem;
        margin-left: 6px;
    }
    .bulk-bar {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        padding: 12px 16px;
    }
    .student-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
    }
    .student-avatar-placeholder {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #eaecf4;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #5a5c69;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <!-- Header & Academic Year Banner -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                <i class="fas fa-chalkboard-teacher text-primary mr-2"></i>Mentor Allocation
            </h1>
            <p class="text-muted mb-0 small">Assign and manage student mentors across academic years. Any staff or faculty member can be a mentor.</p>
        </div>
        <div class="d-flex align-items-center mt-3 mt-sm-0">
            <!-- Academic Year Filter Dropdown -->
            <form method="GET" action="{{ route('admin.mentor-allocations.index') }}" class="form-inline mr-2">
                @foreach(request()->except(['academic_year_id', 'page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-primary text-white border-0 font-weight-bold small">
                            <i class="fas fa-calendar-alt mr-1"></i> Academic Year
                        </span>
                    </div>
                    <select name="academic_year_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()">
                        @foreach($allAcademicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedAcademicYearId == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }} {{ $ay->is_current ? '(Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <button type="button" class="btn btn-sm btn-primary shadow-sm font-weight-bold" data-toggle="modal" data-target="#createMentorGroupModal">
                <i class="fas fa-plus mr-1"></i> New Mentor Group
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Statistic KPI Cards -->
    <div class="row mb-4">
        <!-- Total Students -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students ({{ $selectedAcademicYear?->name ?? 'All' }})</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($totalStudents) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned to Mentors -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card shadow-sm border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Assigned to Mentors</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($assignedCount) }}
                                <span class="text-xs font-weight-normal text-muted ml-1">
                                    ({{ $totalStudents > 0 ? round(($assignedCount / $totalStudents) * 100) : 0 }}%)
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unassigned Students -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card shadow-sm border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Unassigned Students</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($unassignedCount) }}
                                @if($unassignedCount > 0)
                                    <span class="badge badge-danger ml-1">Needs Action</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Mentors -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card shadow-sm border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Mentors</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($activeMentorsCount) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $potentialMentors->count() }} total available staff/users</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mentor Groups (Multi-Department) Overview Card -->
    <div class="card shadow-sm mb-4 border-left-primary">
        <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
            <div>
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-layer-group mr-1"></i> Mentor Groups (Multi-Department) &mdash; {{ $selectedAcademicYear?->name }}
                </h6>
                <div class="small text-muted mt-1">
                    Groups can contain students from <strong>all departments and courses</strong>. Each group is assigned a <strong>Faculty Mentor</strong> and a <strong>Counselor</strong>.
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-primary font-weight-bold shadow-sm" data-toggle="modal" data-target="#createMentorGroupModal">
                <i class="fas fa-plus mr-1"></i> Create Group
            </button>
        </div>
        <div class="card-body py-3">
            @if($mentorGroups->isEmpty())
                <div class="text-center py-3 text-muted small">
                    <i class="fas fa-layer-group fa-2x text-gray-300 mb-2"></i>
                    <div>No mentor groups created yet for this academic year. Click <strong>"Create Group"</strong> to start grouping students across departments.</div>
                </div>
            @else
                <div class="row">
                    @foreach($mentorGroups as $group)
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100 shadow-none border {{ request('mentor_group_id') == $group->id ? 'border-primary bg-light' : '' }}">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="font-weight-bold text-gray-900 mb-0">
                                            <i class="fas fa-users-cog text-primary mr-1"></i> {{ $group->name }}
                                        </h6>
                                        <span class="badge badge-pill badge-primary">
                                            {{ $group->students_count }} students
                                        </span>
                                    </div>
                                    <div class="small mb-1">
                                        <span class="text-muted"><i class="fas fa-chalkboard-teacher fa-fw text-success mr-1"></i>Faculty:</span>
                                        <strong class="text-gray-900">{{ $group->faculty?->name ?? 'None' }}</strong>
                                        @if($group->faculty)
                                            <span class="text-muted" style="font-size:0.75rem;">({{ $group->faculty->roles->pluck('name')->first() ?? 'Staff' }})</span>
                                        @endif
                                    </div>
                                    <div class="small mb-2">
                                        <span class="text-muted"><i class="fas fa-user-shield fa-fw text-info mr-1"></i>Counselor:</span>
                                        <strong class="text-gray-900">{{ $group->counselor?->name ?? 'None' }}</strong>
                                        @if($group->counselor)
                                            <span class="text-muted" style="font-size:0.75rem;">({{ $group->counselor->roles->pluck('name')->first() ?? 'Staff' }})</span>
                                        @endif
                                    </div>
                                    @if($group->description)
                                        <p class="small text-muted mb-2 font-italic">{{ \Illuminate\Support\Str::limit($group->description, 70) }}</p>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                        @if(request('mentor_group_id') == $group->id)
                                            <a href="{{ request()->fullUrlWithQuery(['mentor_group_id' => null, 'page' => 1]) }}"
                                               class="btn btn-xs btn-primary font-weight-bold">
                                                <i class="fas fa-check mr-1"></i> Filtered (Clear)
                                            </a>
                                        @else
                                            <a href="{{ request()->fullUrlWithQuery(['mentor_group_id' => $group->id, 'page' => 1]) }}"
                                               class="btn btn-xs btn-outline-primary">
                                                <i class="fas fa-filter mr-1"></i> View Students
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ route('mentor-groups.destroy', $group->id) }}" onsubmit="return confirm('Delete group {{ addslashes($group->name) }}? (Students will be detached from this group)');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete Group">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Active Mentors Quick Filter Strip -->
    @if($mentorSummaries->isNotEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-header py-2 bg-white d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary small">
                    <i class="fas fa-users mr-1"></i> Current Mentors in {{ $selectedAcademicYear?->name }} (Click to filter):
                </h6>
                <a href="{{ request()->fullUrlWithQuery(['allocation_status' => 'all', 'page' => 1]) }}" class="small text-primary font-weight-bold">
                    Show All
                </a>
            </div>
            <div class="card-body py-2">
                <div class="d-flex flex-wrap">
                    <a href="{{ request()->fullUrlWithQuery(['allocation_status' => 'unassigned', 'page' => 1]) }}"
                       class="mentor-chip text-decoration-none {{ request('allocation_status') === 'unassigned' ? 'active' : '' }}">
                        <i class="fas fa-exclamation-circle text-danger mr-1"></i> Unassigned Students
                        <span class="badge-count badge-danger text-white">{{ $unassignedCount }}</span>
                    </a>
                    @foreach($mentorSummaries as $ms)
                        <a href="{{ request()->fullUrlWithQuery(['allocation_status' => $ms->id, 'page' => 1]) }}"
                           class="mentor-chip text-decoration-none {{ request('allocation_status') == $ms->id ? 'active' : '' }}">
                            <span>{{ $ms->name }}</span>
                            <span class="text-muted ml-1" style="font-size:0.75rem;">({{ $ms->roles->pluck('name')->first() ?? 'Staff' }})</span>
                            <span class="badge-count">{{ $ms->mentees_count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Search & Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-gray-800">
                <i class="fas fa-filter text-primary mr-1"></i> Filter Students
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mentor-allocations.index') }}">
                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">
                <div class="row">
                    <!-- Course -->
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">Course</label>
                        <select name="course_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Courses</option>
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Batch -->
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">Batch</label>
                        <select name="batch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Batches</option>
                            @foreach($batches as $b)
                                <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mentor Group (Multi-Department) -->
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Mentor Group (All Depts)</label>
                        <select name="mentor_group_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Mentor Groups</option>
                            @foreach($mentorGroups as $mg)
                                <option value="{{ $mg->id }}" {{ request('mentor_group_id') == $mg->id ? 'selected' : '' }}>
                                    {{ $mg->name }} ({{ $mg->students_count }} students)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Allocation Status / Specific Mentor -->
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">Mentor Status</label>
                        <select name="allocation_status" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="all" {{ request('allocation_status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="unassigned" {{ request('allocation_status') == 'unassigned' ? 'selected' : '' }}>Unassigned Only</option>
                            <option value="assigned" {{ request('allocation_status') == 'assigned' ? 'selected' : '' }}>Assigned Only</option>
                            <optgroup label="Specific Mentor">
                                @foreach($potentialMentors as $pm)
                                    <option value="{{ $pm->id }}" {{ request('allocation_status') == $pm->id ? 'selected' : '' }}>
                                        {{ $pm->name }} ({{ $pm->roles->pluck('name')->first() ?? 'User' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <!-- Search Box -->
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Search</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Name, Roll No, Phone..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                                @if(request()->hasAny(['course_id', 'batch_id', 'mentor_group_id', 'allocation_status', 'search']))
                                    <a href="{{ route('admin.mentor-allocations.index', ['academic_year_id' => $selectedAcademicYearId]) }}" class="btn btn-secondary" title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Allocation Workstation -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-gray-800">
                <i class="fas fa-user-friends text-primary mr-1"></i> Student Allocation List
                <span class="badge badge-light border ml-2">{{ $students->total() }} Students Found</span>
            </h6>
        </div>

        <div class="card-body p-0">
            <!-- Bulk Action Toolbar -->
            <form id="bulkActionForm" method="POST">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">

                <div class="bulk-bar m-3 d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex align-items-center my-1">
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="selectAllCheckbox">
                            <label class="custom-control-label font-weight-bold small text-gray-700" for="selectAllCheckbox">Select All</label>
                        </div>
                        <span id="selectedCountBadge" class="badge badge-primary badge-pill-custom">0 selected</span>
                    </div>

                    <div class="d-flex align-items-center my-1 flex-wrap">
                        <!-- Group Assignment (Multi-Department) -->
                        <div class="d-flex align-items-center mr-3 my-1">
                            <label class="small font-weight-bold text-gray-700 mr-2 mb-0">Group:</label>
                            <select id="bulkGroupSelect" class="form-control form-control-sm mr-2" style="min-width: 220px;">
                                <option value="">-- Choose Mentor Group (All Depts) --</option>
                                @foreach($mentorGroups as $mg)
                                    <option value="{{ $mg->id }}">
                                        {{ $mg->name }} (Faculty: {{ $mg->faculty?->name ?? 'None' }} | Counselor: {{ $mg->counselor?->name ?? 'None' }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-success mr-2 shadow-sm font-weight-bold" onclick="submitBulkGroupAssign()">
                                <i class="fas fa-layer-group mr-1"></i> Assign Group
                            </button>
                        </div>

                        <!-- Individual Mentor Assignment -->
                        <div class="d-flex align-items-center my-1">
                            <label class="small font-weight-bold text-gray-700 mr-2 mb-0">Mentor:</label>
                            <select id="bulkMentorSelect" class="form-control form-control-sm mr-2" style="min-width: 200px;">
                                <option value="">-- Individual Mentor --</option>
                                @foreach($potentialMentors as $pm)
                                    <option value="{{ $pm->id }}">
                                        {{ $pm->name }} ({{ $pm->roles->pluck('name')->first() ?? 'Staff' }}{{ $pm->department ? ' - ' . $pm->department : '' }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-primary mr-2 shadow-sm font-weight-bold" onclick="submitBulkAssign()">
                                <i class="fas fa-user-plus mr-1"></i> Assign
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger shadow-sm font-weight-bold" onclick="submitBulkUnassign()">
                                <i class="fas fa-user-minus mr-1"></i> Unassign
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Student Roster Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th>Student</th>
                                <th>Course & Batch</th>
                                <th>Mentor Group & Guidance Team</th>
                                <th>Parent Contacts</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="custom-control-input student-checkbox" id="student_check_{{ $student->id }}">
                                            <label class="custom-control-label" for="student_check_{{ $student->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($student->photo)
                                                <img src="{{ asset('storage/' . $student->photo) }}" class="student-avatar mr-2" alt="{{ $student->name }}">
                                            @else
                                                <div class="student-avatar-placeholder mr-2">
                                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <a href="{{ route('admin.students.show', $student->id) }}" class="font-weight-bold text-gray-900 text-decoration-none">
                                                    {{ $student->name }}
                                                </a>
                                                <div class="small text-muted">Roll: {{ $student->enrollment_number ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-gray-800 small">{{ $student->batch?->course?->name ?? 'No Course' }}</div>
                                        <span class="badge badge-light border text-muted small">{{ $student->batch?->name ?? 'No Batch' }}</span>
                                    </td>
                                    <td>
                                        @if($student->mentorGroup)
                                            <div class="mb-1">
                                                <span class="badge badge-primary">
                                                    <i class="fas fa-layer-group mr-1"></i> {{ $student->mentorGroup->name }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="small">
                                            <div>
                                                <span class="text-muted"><i class="fas fa-chalkboard-teacher fa-fw text-success mr-1"></i>Faculty:</span>
                                                @if($student->mentor)
                                                    <strong class="text-gray-900">{{ $student->mentor->name }}</strong>
                                                    <span class="text-muted" style="font-size:0.75rem;">({{ $student->mentor->roles->pluck('name')->first() ?? 'Staff' }})</span>
                                                @else
                                                    <span class="badge badge-pill badge-warning text-dark">Unassigned</span>
                                                @endif
                                            </div>
                                            <div class="mt-1">
                                                <span class="text-muted"><i class="fas fa-user-shield fa-fw text-info mr-1"></i>Counselor:</span>
                                                @if($student->counselor)
                                                    <strong class="text-gray-900">{{ $student->counselor->name }}</strong>
                                                @else
                                                    <span class="text-muted">None</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            @if($student->father_name)
                                                <span class="text-muted">Father:</span> <span class="font-weight-bold">{{ $student->father_name }}</span>
                                            @endif
                                            @if($student->father_mobile)
                                                <div>
                                                    <a href="tel:{{ $student->father_mobile }}" class="text-primary text-decoration-none">
                                                        <i class="fas fa-phone-alt fa-xs mr-1"></i>{{ $student->father_mobile }}
                                                    </a>
                                                    <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $student->father_mobile) }}" target="_blank" class="text-success ml-1" title="WhatsApp Father">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>
                                                </div>
                                            @elseif($student->mother_mobile)
                                                <div>
                                                    <span class="text-muted">Mother:</span> {{ $student->mother_mobile }}
                                                </div>
                                            @else
                                                <span class="text-muted small">No contact recorded</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm"
                                                onclick="openSingleAssignModal('{{ $student->id }}', '{{ addslashes($student->name) }}', '{{ $student->mentor_id }}', '{{ $student->mentor_group_id }}')">
                                            <i class="fas fa-edit mr-1"></i> {{ ($student->mentor_id || $student->mentor_group_id) ? 'Change' : 'Assign' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-user-graduate fa-3x text-gray-300 mb-3"></i>
                                        <p class="text-muted font-weight-bold">No students found matching your filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

        @if($students->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                <span class="small text-muted">
                    Showing {{ $students->firstItem() }} to {{ $students->lastItem() }} of {{ $students->total() }} students
                </span>
                <div>
                    {{ $students->links() }}
                </div>
            </div>
        @endif
    </div>

</div>

<!-- Single Assign Modal -->
<div class="modal fade" id="singleAssignModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-users-cog mr-1"></i> Guidance Allocation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    Student: <strong class="text-gray-900" id="singleStudentName"></strong>
                    <br><span class="small text-muted">Academic Year: {{ $selectedAcademicYear?->name }}</span>
                </p>

                <!-- Nav Tabs -->
                <ul class="nav nav-pills nav-fill mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active font-weight-bold small" id="pills-group-tab" data-toggle="pill" href="#pills-group" role="tab">
                            <i class="fas fa-layer-group mr-1"></i> Mentor Group
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link font-weight-bold small" id="pills-individual-tab" data-toggle="pill" href="#pills-individual" role="tab">
                            <i class="fas fa-user-tie mr-1"></i> Individual Mentor
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">
                    <!-- Assign to Group Tab -->
                    <div class="tab-pane fade show active" id="pills-group" role="tabpanel">
                        <form method="POST" action="{{ route('mentor-allocations.assign-group') }}">
                            @csrf
                            <input type="hidden" name="student_ids[]" id="singleGroupStudentId">
                            <div class="form-group">
                                <label class="font-weight-bold small">Select Mentor Group (Multi-Department)</label>
                                <select name="mentor_group_id" id="singleGroupSelect" class="form-control" required>
                                    <option value="">-- Choose Mentor Group --</option>
                                    @foreach($mentorGroups as $mg)
                                        <option value="{{ $mg->id }}">
                                            {{ $mg->name }} (Faculty: {{ $mg->faculty?->name ?? 'None' }} | Counselor: {{ $mg->counselor?->name ?? 'None' }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Assigns both the group's Faculty Mentor and Counselor to the student.
                                </small>
                            </div>
                            <div class="text-right mt-3">
                                <button type="submit" class="btn btn-success btn-sm font-weight-bold">
                                    <i class="fas fa-check mr-1"></i> Save to Group
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Assign Individual Mentor Tab -->
                    <div class="tab-pane fade" id="pills-individual" role="tabpanel">
                        <form method="POST" action="{{ route('admin.mentor-allocations.assign') }}">
                            @csrf
                            <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">
                            <input type="hidden" name="student_ids[]" id="singleStudentId">

                            <div class="form-group">
                                <label class="font-weight-bold small">Select Faculty / Staff Mentor</label>
                                <select name="mentor_id" id="singleMentorSelect" class="form-control" required>
                                    <option value="">-- Choose Mentor --</option>
                                    @foreach($potentialMentors as $pm)
                                        <option value="{{ $pm->id }}">
                                            {{ $pm->name }} ({{ $pm->roles->pluck('name')->first() ?? 'Staff' }}{{ $pm->department ? ' - ' . $pm->department : '' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold small">Remarks / Notes (Optional)</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="e.g., Focus on attendance improvement">
                            </div>

                            <div class="text-right mt-3">
                                <button type="submit" class="btn btn-primary btn-sm font-weight-bold">
                                    <i class="fas fa-check mr-1"></i> Save Mentor
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Mentor Group Modal -->
<div class="modal fade" id="createMentorGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="{{ route('mentor-groups.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-layer-group mr-1"></i> Create Mentor Group
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle mr-1"></i>
                        Mentor Groups can contain students from <strong>all departments and courses</strong>. Each group has a Faculty Mentor and a Counselor.
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Group Name*</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Group A - Alpha or Guidance Squad 1" required>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Academic Year*</label>
                        <select name="academic_year_id" class="form-control" required>
                            @foreach($allAcademicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $selectedAcademicYearId == $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }} {{ $ay->is_current ? '(Current)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">
                            <i class="fas fa-chalkboard-teacher text-success mr-1"></i> Faculty Mentor (Teacher)
                        </label>
                        <select name="faculty_id" class="form-control">
                            <option value="">-- Select Faculty Mentor --</option>
                            @foreach($potentialMentors as $pm)
                                <option value="{{ $pm->id }}">
                                    {{ $pm->name }} ({{ $pm->roles->pluck('name')->first() ?? 'Staff' }}{{ $pm->department ? ' - ' . $pm->department : '' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">
                            <i class="fas fa-user-shield text-info mr-1"></i> Counselor (Staff)
                        </label>
                        <select name="counselor_id" class="form-control">
                            <option value="">-- Select Counselor --</option>
                            @foreach($potentialMentors as $pm)
                                <option value="{{ $pm->id }}">
                                    {{ $pm->name }} ({{ $pm->roles->pluck('name')->first() ?? 'Staff' }}{{ $pm->department ? ' - ' . $pm->department : '' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief note about the group focus or notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold">
                        <i class="fas fa-plus mr-1"></i> Create Group
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Checkbox selection management
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    const selectedCountBadge = document.getElementById('selectedCountBadge');
    const bulkForm = document.getElementById('bulkActionForm');

    function updateSelectedCount() {
        const selected = document.querySelectorAll('.student-checkbox:checked').length;
        selectedCountBadge.innerText = `${selected} selected`;
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            studentCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateSelectedCount();
        });
    }

    studentCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    function submitBulkGroupAssign() {
        const selected = document.querySelectorAll('.student-checkbox:checked');
        if (selected.length === 0) {
            alert('Please select at least one student.');
            return;
        }

        const groupId = document.getElementById('bulkGroupSelect').value;
        if (!groupId) {
            alert('Please select a Mentor Group to assign the selected students to.');
            return;
        }

        // Add or set mentor_group_id hidden input
        let groupInput = bulkForm.querySelector('input[name="mentor_group_id"]');
        if (!groupInput) {
            groupInput = document.createElement('input');
            groupInput.type = 'hidden';
            groupInput.name = 'mentor_group_id';
            bulkForm.appendChild(groupInput);
        }
        groupInput.value = groupId;

        bulkForm.action = "{{ route('mentor-allocations.assign-group') }}";
        bulkForm.submit();
    }

    function submitBulkAssign() {
        const selected = document.querySelectorAll('.student-checkbox:checked');
        if (selected.length === 0) {
            alert('Please select at least one student.');
            return;
        }

        const mentorId = document.getElementById('bulkMentorSelect').value;
        if (!mentorId) {
            alert('Please select a mentor to assign to the selected students.');
            return;
        }

        bulkForm.action = "{{ route('admin.mentor-allocations.assign') }}";
        bulkForm.submit();
    }

    function submitBulkUnassign() {
        const selected = document.querySelectorAll('.student-checkbox:checked');
        if (selected.length === 0) {
            alert('Please select at least one student.');
            return;
        }

        if (confirm(`Are you sure you want to unassign mentors from ${selected.length} students?`)) {
            bulkForm.action = "{{ route('admin.mentor-allocations.unassign') }}";
            bulkForm.submit();
        }
    }

    function openSingleAssignModal(studentId, studentName, currentMentorId, currentGroupId) {
        document.getElementById('singleStudentId').value = studentId;
        document.getElementById('singleGroupStudentId').value = studentId;
        document.getElementById('singleStudentName').innerText = studentName;
        document.getElementById('singleMentorSelect').value = currentMentorId || '';
        document.getElementById('singleGroupSelect').value = currentGroupId || '';

        // If student already has a group, default to group tab, otherwise keep group tab active
        if (currentGroupId) {
            $('#pills-group-tab').tab('show');
        } else if (currentMentorId) {
            $('#pills-individual-tab').tab('show');
        } else {
            $('#pills-group-tab').tab('show');
        }

        $('#singleAssignModal').modal('show');
    }
</script>
@endpush
