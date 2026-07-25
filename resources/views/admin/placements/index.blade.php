@extends('layouts.placement')

@section('title', 'Job Placements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Job Placements</h1>
            <p class="text-muted mb-0">Manage and export student placement records.</p>
        </div>
        <div>
            <a href="{{ route('placement-portal.export', request()->all()) }}" class="btn btn-success" id="exportReportBtn">
                <i class="fas fa-file-excel mr-1"></i> Export Report
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div id="placementPortalMainContent">
        <!-- Analytics & Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2" style="border-radius: 14px;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-primary opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-success shadow h-100 py-2" style="border-radius: 14px;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Placed (Jobs)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['job']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-briefcase fa-2x text-success opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-info shadow h-100 py-2" style="border-radius: 14px;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Internships</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['internship']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-graduate fa-2x text-info opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-warning shadow h-100 py-2" style="border-radius: 14px;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Placement Rate</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['placement_rate'] }}%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-warning opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Performance Breakdown -->
        @if(count($courseStats) > 0)
        <div class="card shadow mb-4" style="border-radius: 16px;">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-bar mr-1"></i> Course-Wise Placement Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($courseStats as $cStat)
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="p-3 border rounded bg-light shadow-sm" style="border-radius: 12px !important;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="font-weight-bold text-dark text-truncate" style="max-width: 70%;" title="{{ $cStat->name }}">{{ $cStat->name }}</span>
                                    <span class="badge badge-primary px-2 py-1">{{ $cStat->placement_rate }}%</span>
                                </div>
                                <div class="progress mb-2" style="height: 6px; border-radius: 4px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $cStat->placement_rate }}%" aria-valuenow="{{ $cStat->placement_rate }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Placed: <strong class="text-dark">{{ $cStat->placed_students }}</strong></span>
                                    <span>Total: <strong class="text-dark">{{ $cStat->total_students }}</strong></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="card shadow mb-4" style="border-radius: 16px;">
            <div class="card-header py-3 bg-white d-flex flex-wrap align-items-center justify-content-between" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <form id="filterForm" action="{{ route('placement-portal.index') }}" method="GET" class="form-inline flex-wrap mr-3">
                    <div class="form-group mr-2 mb-2">
                        <input type="text" name="search" id="searchInput" class="form-control" placeholder="Search student or company..." value="{{ request('search') }}">
                    </div>
                    <div class="form-group mr-2 mb-2">
                        <select name="course_id" id="courseFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2 mb-2">
                        <select name="batch_id" id="batchFilter" class="form-control" style="max-width: 220px;">
                            <option value="">All Batches</option>
                            @foreach($courses as $course)
                                <optgroup label="{{ $course->name }}" data-course-id="{{ $course->id }}">
                                    @foreach($course->batches as $batch)
                                        <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                            {{ $batch->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2 mb-2">
                        <select name="placement_status" id="statusFilter" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Not Placed" {{ request('placement_status') == 'Not Placed' ? 'selected' : '' }}>Not Placed</option>
                            <option value="Training" {{ request('placement_status') == 'Training' ? 'selected' : '' }}>Training</option>
                            <option value="Internship" {{ request('placement_status') == 'Internship' ? 'selected' : '' }}>Internship</option>
                            <option value="Job" {{ request('placement_status') == 'Job' ? 'selected' : '' }}>Job</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search mr-1"></i> Filter</button>
                    <a href="{{ route('placement-portal.index') }}" class="btn btn-secondary ml-2 mb-2" id="clearFiltersBtn">Clear</a>
                </form>

                <div class="d-flex align-items-center mb-2">
                    <button type="button" class="btn btn-outline-danger mr-2 d-none" id="clearSelectionBtn">
                        <i class="fas fa-times-circle mr-1"></i> Clear Selected (<span id="selectedCountBadge">0</span>)
                    </button>
                    <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#bulkUpdateModal" id="bulkUpdateButton" disabled>
                        <i class="fas fa-edit mr-1"></i> Bulk Update (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAll">
                                        <label class="custom-control-label" for="selectAll"></label>
                                    </div>
                                </th>
                                <th>Student</th>
                                <th>Contact</th>
                                <th>Batch</th>
                                <th>Placement Status</th>
                                <th>Company / Placed At</th>
                                <th>Designation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input student-checkbox" id="student{{ $student->id }}" value="{{ $student->id }}">
                                            <label class="custom-control-label" for="student{{ $student->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $student->photo_url }}" class="rounded-circle mr-2" width="40" height="40" style="object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&size=40&background=4e73df&color=fff&rounded=true&bold=true'">
                                            <div>
                                                <div class="font-weight-bold">{{ $student->name }}</div>
                                                <small class="text-muted">{{ $student->enrollment_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><i class="fas fa-phone fa-sm mr-1"></i> {{ $student->student_mobile }}</div>
                                        @if($student->email)
                                        <div><i class="fas fa-envelope fa-sm mr-1"></i> {{ $student->email }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $student->batch->name ?? 'N/A' }}<br>
                                        <small class="text-muted">{{ $student->batch->course->name ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if($student->placement_status == 'Job')
                                            <span class="badge badge-success">Job</span>
                                        @elseif($student->placement_status == 'Internship')
                                            <span class="badge badge-info">Internship</span>
                                        @elseif($student->placement_status == 'Training')
                                            <span class="badge badge-warning">Training</span>
                                        @else
                                            <span class="badge badge-secondary">Not Placed</span>
                                        @endif
                                    </td>
                                    <td>{{ $student->placed_at ?? '-' }}</td>
                                    <td>{{ $student->placement_designation ?? '-' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary shadow-sm rounded-pill px-3 btn-update-placement" 
                                            data-toggle="modal" 
                                            data-target="#placementUpdateModal"
                                            data-id="{{ $student->id }}"
                                            data-name="{{ e($student->name) }}"
                                            data-enrollment="{{ $student->enrollment_number }}"
                                            data-photo="{{ $student->photo_url }}"
                                            data-batch="{{ $student->batch->name ?? 'N/A' }}"
                                            data-course="{{ $student->batch->course->name ?? '' }}"
                                            data-status="{{ $student->placement_status ?? 'Not Placed' }}"
                                            data-placed-at="{{ $student->placed_at ?? '' }}"
                                            data-designation="{{ $student->placement_designation ?? '' }}"
                                            data-update-url="{{ route('placement-portal.update', $student) }}">
                                            <i class="fas fa-edit mr-1"></i> Update
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">No student records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4" id="paginationContainer">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modern-modal-content">
            <div class="modern-modal-header header-bulk">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-users-cog text-info"></i> Bulk Update Placements
                    </h5>
                    <div class="modal-subtitle">Batch update status and placement records</div>
                </div>
                <button type="button" class="close-modern" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="bulkUpdateForm" action="{{ route('placement-portal.bulk-update') }}" method="POST">
                @csrf
                <div class="modal-body modern-modal-body">
                    <div class="student-quick-info">
                        <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-circle mr-2" style="width: 42px; height: 42px; flex-shrink: 0;">
                            <i class="fas fa-user-check fa-lg"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-dark">Selected Students</div>
                            <small class="text-muted"><strong id="modalSelectedCount" class="text-primary font-weight-bold">0</strong> students will be updated simultaneously.</small>
                        </div>
                    </div>

                    <div id="hiddenInputsContainer"></div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1">Placement Status</label>
                        <div class="status-pills-container" id="bulkStatusPills">
                            <button type="button" class="status-pill-btn active" data-status="Not Placed">
                                <i class="fas fa-minus-circle"></i> Not Placed
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Training">
                                <i class="fas fa-chalkboard-teacher"></i> Training
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Internship">
                                <i class="fas fa-briefcase"></i> Internship
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Job">
                                <i class="fas fa-check-circle"></i> Job
                            </button>
                        </div>
                        <input type="hidden" name="placement_status" id="bulk_placement_status" value="Not Placed" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1"><i class="fas fa-building text-muted mr-1"></i> Company / Placed At</label>
                        <input type="text" name="placed_at" class="form-control" style="border-radius: 10px;" placeholder="e.g. Google, TCS, Local Hospital">
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark mb-1"><i class="fas fa-user-tag text-muted mr-1"></i> Designation</label>
                        <input type="text" name="placement_designation" class="form-control" style="border-radius: 10px;" placeholder="e.g. Software Engineer, Trainee">
                    </div>
                </div>
                <div class="modern-modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-save mr-1"></i> Apply Bulk Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modern Single Student Placement Update Modal -->
<div class="modal fade" id="placementUpdateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modern-modal-content">
            <div class="modern-modal-header">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-user-graduate"></i> Update Placement Record
                    </h5>
                    <div class="modal-subtitle">Modify placement status and company details</div>
                </div>
                <button type="button" class="close-modern" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="singlePlacementForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body modern-modal-body">
                    <!-- Student Summary Card -->
                    <div class="student-quick-info">
                        <img id="modalStudentPhoto" src="" alt="Student Photo" onerror="this.src='https://ui-avatars.com/api/?name=Student&size=40&background=4e73df&color=fff&rounded=true&bold=true'">
                        <div class="flex-grow-1">
                            <div id="modalStudentName" class="font-weight-bold text-dark h6 mb-0"></div>
                            <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                <span id="modalStudentEnrollment"></span> &bull; 
                                <span id="modalStudentBatch"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1">Placement Status</label>
                        <div class="status-pills-container" id="singleStatusPills">
                            <button type="button" class="status-pill-btn" data-status="Not Placed">
                                <i class="fas fa-minus-circle"></i> Not Placed
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Training">
                                <i class="fas fa-chalkboard-teacher"></i> Training
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Internship">
                                <i class="fas fa-briefcase"></i> Internship
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Job">
                                <i class="fas fa-check-circle"></i> Job
                            </button>
                        </div>
                        <input type="hidden" name="placement_status" id="single_placement_status" value="Not Placed" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1"><i class="fas fa-building text-muted mr-1"></i> Company / Placed At</label>
                        <input type="text" name="placed_at" id="modalPlacedAt" class="form-control" style="border-radius: 10px;" placeholder="e.g. Google, TCS, Local Hospital">
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark mb-1"><i class="fas fa-user-tag text-muted mr-1"></i> Designation</label>
                        <input type="text" name="placement_designation" id="modalDesignation" class="form-control" style="border-radius: 10px;" placeholder="e.g. Software Engineer, Trainee">
                    </div>
                </div>
                <div class="modern-modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-save mr-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Persistent Checked IDs Store
        let selectedStudentIds = new Set(JSON.parse(sessionStorage.getItem('placement_selected_ids') || '[]'));

        const bulkUpdateButton = document.getElementById('bulkUpdateButton');
        const selectedCountSpan = document.getElementById('selectedCount');
        const modalSelectedCount = document.getElementById('modalSelectedCount');
        const clearSelectionBtn = document.getElementById('clearSelectionBtn');
        const selectedCountBadge = document.getElementById('selectedCountBadge');
        const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');
        const bulkUpdateForm = document.getElementById('bulkUpdateForm');

        function saveSelectedIds() {
            sessionStorage.setItem('placement_selected_ids', JSON.stringify(Array.from(selectedStudentIds)));
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = selectedStudentIds.size;
            if (selectedCountSpan) selectedCountSpan.textContent = count;
            if (modalSelectedCount) modalSelectedCount.textContent = count;
            if (selectedCountBadge) selectedCountBadge.textContent = count;

            if (bulkUpdateButton) {
                if (count > 0) {
                    bulkUpdateButton.removeAttribute('disabled');
                    if (clearSelectionBtn) clearSelectionBtn.classList.remove('d-none');
                } else {
                    bulkUpdateButton.setAttribute('disabled', 'disabled');
                    if (clearSelectionBtn) clearSelectionBtn.classList.add('d-none');
                }
            }

            const exportBtn = document.getElementById('exportReportBtn');
            if (exportBtn) {
                const currentUrl = new URL(window.location.href);
                if (count > 0) {
                    currentUrl.searchParams.set('student_ids', Array.from(selectedStudentIds).join(','));
                } else {
                    currentUrl.searchParams.delete('student_ids');
                }
                exportBtn.href = "{{ route('placement-portal.export') }}" + currentUrl.search;
            }
        }

        function syncCheckboxesWithStore() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = selectedStudentIds.has(cb.value);
            });

            const selectAll = document.getElementById('selectAll');
            if (selectAll && checkboxes.length > 0) {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                selectAll.checked = allChecked;
            }
            updateSelectedCount();
        }

        // Initialize checkboxes on load
        syncCheckboxesWithStore();

        // Delegate Checkbox Actions
        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('student-checkbox')) {
                const val = e.target.value;
                if (e.target.checked) {
                    selectedStudentIds.add(val);
                } else {
                    selectedStudentIds.delete(val);
                }
                saveSelectedIds();
                syncCheckboxesWithStore();
            }

            if (e.target && e.target.id === 'selectAll') {
                const checkboxes = document.querySelectorAll('.student-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                    if (e.target.checked) {
                        selectedStudentIds.add(cb.value);
                    } else {
                        selectedStudentIds.delete(cb.value);
                    }
                });
                saveSelectedIds();
            }
        });

        // Clear All Selections Button
        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function () {
                selectedStudentIds.clear();
                saveSelectedIds();
                syncCheckboxesWithStore();
            });
        }

        // Prepare Bulk Form Submission with ALL Accumulated IDs
        if (bulkUpdateForm) {
            bulkUpdateForm.addEventListener('submit', function (e) {
                hiddenInputsContainer.innerHTML = '';
                selectedStudentIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'student_ids[]';
                    input.value = id;
                    hiddenInputsContainer.appendChild(input);
                });
                // Clear store after bulk update submit
                sessionStorage.removeItem('placement_selected_ids');
            });
        }

        // AJAX Loading Engine for Live Search, Filters & Pagination
        function loadPlacementsAjax(url) {
            const mainContent = document.getElementById('placementPortalMainContent');
            if (!mainContent) return;

            mainContent.style.opacity = '0.5';

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('placementPortalMainContent');
                if (newContent) {
                    mainContent.innerHTML = newContent.innerHTML;
                }
                mainContent.style.opacity = '1';
                syncCheckboxesWithStore();

                // Update Export Link URL to match active query
                const exportBtn = document.getElementById('exportReportBtn');
                if (exportBtn) {
                    const urlObj = new URL(url, window.location.origin);
                    exportBtn.href = "{{ route('placement-portal.export') }}" + urlObj.search;
                }

                // Dynamic Batch Filter Options Sync
                const activeCourseId = document.getElementById('courseFilter')?.value || '';
                filterBatchOptions(activeCourseId);

                window.history.pushState({}, '', url);
            })
            .catch(err => {
                mainContent.style.opacity = '1';
                window.location.href = url;
            });
        }

        function filterBatchOptions(courseId) {
            const batchSelect = document.getElementById('batchFilter');
            if (!batchSelect) return;
            const optgroups = batchSelect.querySelectorAll('optgroup');
            optgroups.forEach(group => {
                if (!courseId || group.getAttribute('data-course-id') === String(courseId)) {
                    group.style.display = '';
                    Array.from(group.options).forEach(opt => opt.disabled = false);
                } else {
                    group.style.display = 'none';
                    Array.from(group.options).forEach(opt => opt.disabled = true);
                }
            });
            const selectedOption = batchSelect.options[batchSelect.selectedIndex];
            if (selectedOption && selectedOption.disabled) {
                batchSelect.value = '';
            }
        }

        // Initialize batch filter options on page load
        filterBatchOptions(document.getElementById('courseFilter')?.value || '');

        // Filter Form AJAX Handlers
        let searchDebounceTimer;
        document.addEventListener('input', function (e) {
            if (e.target && (e.target.id === 'searchInput' || e.target.name === 'search')) {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function () {
                    const form = document.getElementById('filterForm');
                    if (form) {
                        const formData = new FormData(form);
                        const params = new URLSearchParams(formData);
                        loadPlacementsAjax(form.action + '?' + params.toString());
                    }
                }, 300);
            }
        });

        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'courseFilter') {
                filterBatchOptions(e.target.value);
            }

            if (e.target && e.target.form && e.target.form.id === 'filterForm' && e.target.name !== 'search') {
                const form = e.target.form;
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                loadPlacementsAjax(form.action + '?' + params.toString());
            }
        });

        document.addEventListener('submit', function (e) {
            if (e.target && e.target.id === 'filterForm') {
                e.preventDefault();
                const formData = new FormData(e.target);
                const params = new URLSearchParams(formData);
                loadPlacementsAjax(e.target.action + '?' + params.toString());
            }
        });

        // Intercept Clear Filters Button Click for AJAX Full Clear
        document.addEventListener('click', function (e) {
            const clearBtn = e.target.closest('#clearFiltersBtn');
            if (clearBtn) {
                e.preventDefault();
                const form = document.getElementById('filterForm');
                if (form) {
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) searchInput.value = '';
                    const courseFilter = document.getElementById('courseFilter');
                    if (courseFilter) courseFilter.value = '';
                    const batchFilter = document.getElementById('batchFilter');
                    if (batchFilter) batchFilter.value = '';
                    const statusFilter = document.getElementById('statusFilter');
                    if (statusFilter) statusFilter.value = '';
                    filterBatchOptions('');
                }
                loadPlacementsAjax(clearBtn.href);
            }
        });

        // Intercept Pagination Link Clicks
        document.addEventListener('click', function (e) {
            const link = e.target.closest('#paginationContainer a, .pagination a');
            if (link && link.href) {
                e.preventDefault();
                loadPlacementsAjax(link.href);
            }
        });

        // Status Pill Selection Helper
        function setupStatusPills(containerId, hiddenInputId) {
            const container = document.getElementById(containerId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!container || !hiddenInput) return;

            const pillButtons = container.querySelectorAll('.status-pill-btn');
            pillButtons.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    pillButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    hiddenInput.value = this.getAttribute('data-status');
                });
            });
        }

        function setStatusPillValue(containerId, hiddenInputId, statusValue) {
            const container = document.getElementById(containerId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!container || !hiddenInput) return;

            const targetStatus = statusValue || 'Not Placed';
            hiddenInput.value = targetStatus;

            const pillButtons = container.querySelectorAll('.status-pill-btn');
            pillButtons.forEach(btn => {
                if (btn.getAttribute('data-status') === targetStatus) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        setupStatusPills('bulkStatusPills', 'bulk_placement_status');
        setupStatusPills('singleStatusPills', 'single_placement_status');

        function populateModalFromBtn(btn) {
            if (!btn || !btn.length) return;
            const modal = $('#placementUpdateModal');
            const form = modal.find('#singlePlacementForm');

            form.attr('action', btn.data('update-url'));
            modal.find('#modalStudentName').text(btn.data('name'));
            modal.find('#modalStudentEnrollment').text(btn.data('enrollment') || 'No Reg #');
            modal.find('#modalStudentBatch').text((btn.data('batch') || '') + (btn.data('course') ? ' - ' + btn.data('course') : ''));
            modal.find('#modalStudentPhoto').attr('src', btn.data('photo'));
            modal.find('#modalPlacedAt').val(btn.data('placed-at') || '');
            modal.find('#modalDesignation').val(btn.data('designation') || '');

            setStatusPillValue('singleStatusPills', 'single_placement_status', btn.data('status'));
        }

        $(document).on('click', '.btn-update-placement', function () {
            populateModalFromBtn($(this));
        });

        $('#placementUpdateModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            if (button && button.length) {
                populateModalFromBtn(button);
            }
        });
    });
</script>
@endpush

