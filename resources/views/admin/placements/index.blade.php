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
        @include('admin.placements.partials.stats')

        <!-- Course Performance Breakdown -->
        @include('admin.placements.partials.course_stats')

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
            <div class="card-body" id="tableCardBody">
                @include('admin.placements.partials.table')
            </div>
        </div>
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
            const tableCardBody = document.getElementById('tableCardBody');
            if (tableCardBody) tableCardBody.style.opacity = '0.5';

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                if (data.table_html && tableCardBody) {
                    tableCardBody.innerHTML = data.table_html;
                    tableCardBody.style.opacity = '1';
                }

                const analyticsContainer = document.getElementById('analyticsStatsContainer');
                if (analyticsContainer && data.stats_html) {
                    analyticsContainer.outerHTML = data.stats_html;
                }

                const courseStatsContainer = document.getElementById('courseStatsContainer');
                if (courseStatsContainer && data.course_stats_html) {
                    courseStatsContainer.outerHTML = data.course_stats_html;
                }

                syncCheckboxesWithStore();

                const exportBtn = document.getElementById('exportReportBtn');
                if (exportBtn) {
                    const urlObj = new URL(url, window.location.origin);
                    if (selectedStudentIds.size > 0) {
                        urlObj.searchParams.set('student_ids', Array.from(selectedStudentIds).join(','));
                    }
                    exportBtn.href = "{{ route('placement-portal.export') }}" + urlObj.search;
                }

                const activeCourseId = document.getElementById('courseFilter')?.value || '';
                filterBatchOptions(activeCourseId);

                window.history.pushState({}, '', url);
            })
            .catch(err => {
                console.error('AJAX Load Failed:', err);
                if (tableCardBody) tableCardBody.style.opacity = '1';
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

