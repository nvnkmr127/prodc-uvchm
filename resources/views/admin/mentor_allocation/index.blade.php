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
            <form method="GET" action="{{ route('admin.mentor-allocations.index') }}" class="form-inline">
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
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Course</label>
                        <select name="course_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Courses</option>
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Batch -->
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Batch</label>
                        <select name="batch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Batches</option>
                            @foreach($batches as $b)
                                <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Allocation Status / Specific Mentor -->
                    <div class="col-md-3 mb-2">
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
                                @if(request()->hasAny(['course_id', 'batch_id', 'allocation_status', 'search']))
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

                    <div class="d-flex align-items-center my-1">
                        <label class="small font-weight-bold text-gray-700 mr-2 mb-0">Assign to Mentor:</label>
                        <select name="mentor_id" id="bulkMentorSelect" class="form-control form-control-sm mr-2" style="min-width: 220px;">
                            <option value="">-- Choose Any Mentor / Staff --</option>
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

                <!-- Student Roster Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th>Student</th>
                                <th>Course & Batch</th>
                                <th>Current Mentor</th>
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
                                        @if($student->mentor)
                                            <div class="d-flex align-items-center">
                                                <div class="badge badge-pill badge-success mr-1">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold text-gray-800 small">{{ $student->mentor->name }}</div>
                                                    <div class="text-muted" style="font-size: 0.75rem;">
                                                        {{ $student->mentor->roles->pluck('name')->first() ?? 'Staff' }}
                                                        @if($student->mentor->phone) &bull; {{ $student->mentor->phone }} @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="badge badge-pill badge-warning">
                                                <i class="fas fa-exclamation-circle mr-1"></i> Not Assigned
                                            </span>
                                        @endif
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
                                                onclick="openSingleAssignModal('{{ $student->id }}', '{{ addslashes($student->name) }}', '{{ $student->mentor_id }}')">
                                            <i class="fas fa-edit mr-1"></i> {{ $student->mentor_id ? 'Change' : 'Assign' }}
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
        <form method="POST" action="{{ route('admin.mentor-allocations.assign') }}">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">
            <input type="hidden" name="student_ids[]" id="singleStudentId">

            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-chalkboard-teacher mr-1"></i> Assign Mentor
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Assign mentor for: <strong class="text-gray-900" id="singleStudentName"></strong>
                        <br><span class="small text-muted">Academic Year: {{ $selectedAcademicYear?->name }}</span>
                    </p>

                    <div class="form-group">
                        <label class="font-weight-bold small">Select Mentor (Any User/Staff)</label>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold">
                        <i class="fas fa-check mr-1"></i> Save Mentor
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

    function openSingleAssignModal(studentId, studentName, currentMentorId) {
        document.getElementById('singleStudentId').value = studentId;
        document.getElementById('singleStudentName').innerText = studentName;
        document.getElementById('singleMentorSelect').value = currentMentorId || '';
        $('#singleAssignModal').modal('show');
    }
</script>
@endpush
