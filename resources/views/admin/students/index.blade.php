@extends('layouts.theme')
@section('title', 'Students Management')



@section('content')
    <div class="container-fluid">
        <!-- Modern Page Header -->
        <div class="page-header-modern">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="header-title">Students Management</h1>
                        <p class="header-subtitle">Manage all your students efficiently with powerful tools</p>
                    </div>
                    <div class="col-md-4">
                        <div class="header-actions">
                            <button class="btn btn-light btn-modern" id="refreshDataBtn">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button class="btn btn-light btn-modern" id="exportDataBtn">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <a href="{{ route('admin.students.create') }}" class="btn btn-success-modern btn-modern">
                                <i class="fas fa-plus"></i> Add Student
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid animate-fade-in">
            <div class="stat-card-simple">
                <div class="stat-number">{{ $stats['active'] ?? 0 }}</div>
                <div class="stat-label">Active Students</div>
                <i class="fas fa-user-check stat-icon"></i>
            </div>
            <div class="stat-card-simple">
                <div class="stat-number">{{ $stats['graduated'] ?? 0 }}</div>
                <div class="stat-label">Graduated</div>
                <i class="fas fa-graduation-cap stat-icon"></i>
            </div>
            <div class="stat-card-simple">
                <div class="stat-number">{{ $stats['dropout'] ?? 0 }}</div>
                <div class="stat-label">Dropouts</div>
                <i class="fas fa-user-times stat-icon"></i>
            </div>
            <div class="stat-card-simple">
                <div class="stat-number">{{ $stats['on_internship'] ?? 0 }}</div>
                <div class="stat-label">On Internship</div>
                <i class="fas fa-briefcase stat-icon"></i>
            </div>
            <div class="stat-card-simple">
                <div class="stat-number">{{ $stats['total'] ?? 0 }}</div>
                <div class="stat-label">Total Students</div>
                <i class="fas fa-users stat-icon"></i>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate-fade-in">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate-fade-in">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Search & Filters -->
        <div class="search-filter-container animate-fade-in">
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="globalSearch"
                    placeholder="Search students by name, enrollment, or mobile...">
            </div>

            <div class="filters-grid">
                <div class="filter-group">
                    <label class="form-label">Course</label>
                    <select class="filter-select" id="courseFilter">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="form-label">Batch</label>
                    <select class="filter-select" id="batchFilter">
                        <option value="">All Batches</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }} ({{ $batch->course->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="form-label">Status</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                        <option value="dropout" {{ request('status') == 'dropout' ? 'selected' : '' }}>Dropout</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="filter-group d-flex align-items-end">
                    <button class="btn btn-primary-modern btn-modern w-100" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </div>

            <!-- Quick Filters -->
            <div class="quick-filters">
                <button class="quick-filter-btn active" data-filter="all">All Students</button>
                <button class="quick-filter-btn" data-filter="active">Active Only</button>
                <button class="quick-filter-btn" data-filter="graduated">Graduated</button>
                <button class="quick-filter-btn" data-filter="recent">Recently Added</button>
                <button class="quick-filter-btn" data-filter="no-contact">Missing Contact</button>
            </div>
        </div>

        <!-- Bulk Actions Panel -->
        <div class="selection-counter" id="selectionCounter">
            <i class="fas fa-check-circle"></i>
            <span id="selectedCount">0</span> students selected
            <div class="ms-auto">
                <button class="btn btn-sm btn-light" id="clearSelectionBtn">Clear</button>
                <button class="btn btn-sm btn-primary" id="showBulkActionsBtn">Actions</button>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-container animate-fade-in" style="position: relative;">
            <div class="loading-overlay d-none" id="loadingOverlay">
                <div class="spinner"></div>
            </div>

            <table class="table table-modern" id="studentsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <input type="checkbox" class="custom-checkbox" id="selectAll">
                        </th>
                        <th>Student Details</th>
                        <th>Enrollment #</th>
                        <th>Course & Batch</th>
                        <th>Contact Info</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @include('admin.students._table_body')
                </tbody>
            </table>
        </div>

        <!-- Total Count Info -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <div class="pagination-info">
                Showing <strong id="visibleCount">{{ $students->count() }}</strong> students total
            </div>
        </div>


        <!-- Bulk Actions Modal -->
        <div class="modal fade" id="bulkActionsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-tasks me-2"></i>Bulk Actions
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="bulk-actions-card">
                            <div class="mb-3">
                                <strong id="bulkSelectedCount">0</strong> students selected for bulk action
                            </div>

                            <div class="bulk-actions-grid">
                                <div class="bulk-action-item" data-action="status" data-value="active">
                                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                    <h6>Mark as Active</h6>
                                </div>
                                <div class="bulk-action-item" data-action="status" data-value="graduated">
                                    <i class="fas fa-graduation-cap text-info fa-2x mb-2"></i>
                                    <h6>Mark as Graduated</h6>
                                </div>
                                <div class="bulk-action-item" data-action="status" data-value="dropout">
                                    <i class="fas fa-times-circle text-warning fa-2x mb-2"></i>
                                    <h6>Mark as Dropout</h6>
                                </div>
                                <div class="bulk-action-item" data-action="batch">
                                    <i class="fas fa-users text-primary fa-2x mb-2"></i>
                                    <h6>Assign to Batch</h6>
                                </div>
                                <div class="bulk-action-item" data-action="export">
                                    <i class="fas fa-download text-secondary fa-2x mb-2"></i>
                                    <h6>Export Selected</h6>
                                </div>
                                @if(auth()->user()->hasRole('super-admin'))
                                    <div class="bulk-action-item" data-action="delete">
                                        <i class="fas fa-trash text-danger fa-2x mb-2"></i>
                                        <h6>Delete Students</h6>
                                    </div>
                                @endif
                            </div>

                            <!-- Batch Assignment Section -->
                            <div id="batchAssignmentSection" class="mt-3" style="display: none;">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Select Target Batch</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Course</label>
                                                <select class="form-select" id="bulkCourseSelect">
                                                    <option value="">Select Course</option>
                                                    @foreach($courses as $course)
                                                        <option value="{{ $course->id }}">{{ $course->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Batch</label>
                                                <select class="form-select" id="bulkBatchSelect" disabled>
                                                    <option value="">Select Course First</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Note:</strong> This will generate new enrollment numbers and fee
                                            structures.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="executeBulkAction" disabled>
                            Execute Action
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Students Modal -->
        <div class="modal fade" id="importModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-file-import me-2"></i>Import Students
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Import Instructions</h6>
                            <ol class="mb-0">
                                <li>Download the sample template</li>
                                <li>Fill in student data following the format</li>
                                <li>Select target course and batch</li>
                                <li>Upload your completed file</li>
                            </ol>
                        </div>

                        <form id="importForm" enctype="multipart/form-data">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Target Course *</label>
                                    <select class="form-select" id="importCourseSelect" required>
                                        <option value="">Select Course</option>
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}">{{ $course->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Batch *</label>
                                    <select class="form-select" id="importBatchSelect" disabled required>
                                        <option value="">Select Course First</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload File (Excel/CSV) *</label>
                                <div class="file-upload-area">
                                    <input type="file" class="form-control" id="importFile" accept=".xlsx,.xls,.csv"
                                        required>
                                    <div class="file-upload-hint">
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                        <p>Choose Excel or CSV file</p>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="button" class="btn btn-outline-info" id="downloadTemplateBtn">
                                    <i class="fas fa-download"></i> Download Template
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="importSubmitBtn" disabled>
                            <i class="fas fa-spinner fa-spin d-none"></i>
                            <i class="fas fa-upload"></i>
                            Import Students
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-trash me-2"></i>Confirm Deletion
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                            <h6>Are you sure you want to delete this student?</h6>
                            <p class="text-muted" id="deleteStudentName">This action cannot be undone.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                            <i class="fas fa-trash"></i>
                            Delete Student
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Reactivation Confirmation Modal -->
        <div class="modal fade" id="reactivateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-success">
                            <i class="fas fa-user-check me-2"></i>Reactivate Student
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-question-circle text-info fa-3x mb-3"></i>
                            <h6>Are you sure you want to reactivate this dropout student?</h6>
                            <p class="text-muted" id="reactivateStudentName">This will restore the student to active status.
                            </p>

                            <div class="form-group mt-3">
                                <label for="reactivationReason">Reason for Reactivation</label>
                                <textarea class="form-control" id="reactivationReason" rows="3"
                                    placeholder="Please provide reason for reactivation..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirmReactivateBtn">
                            <i class="fas fa-user-check"></i>
                            Reactivate Student
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Fixed and Updated JavaScript for Students Management Page

        class StudentsManager {
            constructor() {
                this.init();
            }

            init() {
                // Initialize Select2 if available
                if ($.fn.select2) {
                    $('.select2').select2({ width: '100%' });
                }

                // Bind filter change events
                ['courseFilter', 'batchFilter', 'statusFilter'].forEach(id => {
                    $(`#${id}`).on('change', () => this.applyFilters());
                });

                // Clear filters button
                $('#clearFiltersBtn').on('click', () => this.clearFilters());

                // Quick filter buttons in header
                $('.quick-filter-btn').on('click', (e) => {
                    const filter = $(e.currentTarget).data('filter');
                    this.applyQuickFilter(filter);
                });

                // Delete student button handler (delegated)
                $(document).on('click', '.delete-student-btn', (e) => {
                    const btn = $(e.currentTarget);
                    this.deleteStudent(btn.data('student-id'), btn.data('student-name'));
                });

                // Reactivate student button handler (delegated)
                $(document).on('click', '.reactivate-student-btn', (e) => {
                    const btn = $(e.currentTarget);
                    this.showReactivationModal(btn.data('student-id'), btn.data('student-name'));
                });

                // Search input with debounce
                let timeout;
                $('#globalSearch').on('input', (e) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => this.applyFilters(), 500);
                });

                // Handle browser back/forward
                window.onpopstate = () => this.loadFiltersFromUrl();
            }

            applyFilters(pushState = true) {
                this.showLoading(true);

                const params = new URLSearchParams();

                // Get filter values
                const courseId = $('#courseFilter').val();
                if (courseId) params.append('course_id', courseId);

                const batchId = $('#batchFilter').val();
                if (batchId) params.append('batch_id', batchId);

                const status = $('#statusFilter').val();
                if (status) params.append('status', status);

                const search = $('#globalSearch').val();
                if (search) params.append('search', search);

                const queryString = params.toString();
                const url = `${window.location.pathname}?${queryString}`;

                if (pushState) {
                    window.history.pushState({}, '', url);
                }

                // AJAX Request
                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: (data) => {
                        if (data.success) {
                            this.updateTable(data.html);
                            this.updateStats(data.stats);
                            this.updateCount(data.count);
                        }
                    },
                    error: (xhr) => {
                        console.error('Filtering error:', xhr);
                        this.showToast('Failed to load students', 'error');
                    },
                    complete: () => {
                        this.showLoading(false);
                    }
                });
            }

            updateTable(html) {
                const tbody = document.querySelector('#studentsTable tbody');
                if (tbody) {
                    tbody.innerHTML = html;
                    // Re-initialize any plugins or events for new rows if needed
                    // For example, if there were any specific row scripts (checkboxes etc)
                }
            }

            updateStats(stats) {
                // Update stats cards by targeting unique labels or indexes
                // Stat 1: Active
                const cards = document.querySelectorAll('.stat-card');
                if (cards.length >= 5) {
                    // Update stats based on verified order in view
                    // Active (0), Graduated (1), Dropout (2), Internship (3), Total (4)
                    if (stats.active !== undefined) cards[0].querySelector('.stat-number').textContent = stats.active;
                    if (stats.graduated !== undefined) cards[1].querySelector('.stat-number').textContent = stats.graduated;
                    if (stats.dropout !== undefined) cards[2].querySelector('.stat-number').textContent = stats.dropout;
                    if (stats.on_internship !== undefined) cards[3].querySelector('.stat-number').textContent = stats.on_internship;
                    if (stats.total !== undefined) cards[4].querySelector('.stat-number').textContent = stats.total;
                }
            }

            updateCount(count) {
                $('#visibleCount').text(count);
                $('#selectedCount').text('0'); // Reset selection
                $('.student-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false);
            }

            clearFilters() {
                $('#courseFilter').val('').trigger('change.select2');
                $('#batchFilter').val('').trigger('change.select2');
                $('#statusFilter').val('').trigger('change');
                $('#globalSearch').val('');
                this.applyFilters();
            }

            applyQuickFilter(filterType) {
                if (filterType === 'active' || filterType === 'graduated') {
                    $('#statusFilter').val(filterType).trigger('change');
                }
            }

            loadFiltersFromUrl() {
                const params = new URLSearchParams(window.location.search);
                if (params.has('course_id')) $('#courseFilter').val(params.get('course_id')).trigger('change.select2');
                if (params.has('batch_id')) $('#batchFilter').val(params.get('batch_id')).trigger('change.select2');
                if (params.has('status')) $('#statusFilter').val(params.get('status')).trigger('change');
                if (params.has('search')) $('#globalSearch').val(params.get('search'));

                this.applyFilters(false);
            }

            showLoading(show) {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.toggle('d-none', !show);
            }

            refreshData() {
                this.applyFilters();
            }

            exportData() {
                window.location.href = '/admin/students/export' + window.location.search;
            }

            showToast(message, type = 'info') {
                const toast = document.createElement('div');
                const alertClass = type === 'error' ? 'danger' : type;
                toast.className = `alert alert-${alertClass} alert-dismissible fade show position-fixed toast-notification`;
                toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
                toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>${message}`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 5000);
            }

            deleteStudent(studentId, studentName) {
                $('#deleteStudentName').text(`Student: ${studentName}`);
                const modal = $('#deleteModal');

                $('#confirmDeleteBtn').off('click').on('click', () => {
                    const form = $(`<form action="/admin/students/${studentId}" method="POST">
                                <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                                <input type="hidden" name="_method" value="DELETE">
                            </form>`);
                    $('body').append(form);
                    form.submit();
                });

                modal.modal('show');
            }

            showReactivationModal(studentId, studentName) {
                this.currentReactivationStudent = { id: studentId, name: studentName };
                $('#reactivateStudentName').text(`Student: ${studentName}`);
                $('#reactivationReason').val('');
                $('#reactivateModal').modal('show');
            }

            executeReactivation() {
                if (!this.currentReactivationStudent) return;
                const studentId = this.currentReactivationStudent.id;
                const reason = $('#reactivationReason').val() || 'Reactivated from list';

                // Show loading
                const btn = $('#confirmReactivateBtn');
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Reactivating...');

                $.post(`/admin/students/${studentId}/reactivate`, {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    reason: reason
                }, (res) => {
                    if (res.success) {
                        this.showToast('Student reactivated successfully', 'success');
                        $('#reactivateModal').modal('hide');
                        this.refreshData();
                    } else {
                        this.showToast(res.message || 'Error', 'error');
                    }
                })
                    .fail(() => this.showToast('Reactivation failed', 'error'))
                    .always(() => btn.prop('disabled', false).html(originalHtml));
            }

            showImportModal() {
                $('#importModal').modal('show');
            }

            importStudents() {
                const form = new FormData($('#importForm')[0]);
                // Basic validation
                if (!$('#importFile')[0].files[0]) {
                    this.showToast('Please select a file', 'error');
                    return;
                }

                const btn = $('#importSubmitBtn');
                btn.prop('disabled', true);

                $.ajax({
                    url: '/admin/students/import',
                    method: 'POST',
                    data: form,
                    processData: false,
                    contentType: false,
                    success: (res) => {
                        if (res.success) {
                            this.showToast(res.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            this.showToast(res.message || 'Error', 'error');
                        }
                    },
                    error: (xhr) => this.showToast(xhr.responseJSON?.message || 'Import failed', 'error'),
                    complete: () => btn.prop('disabled', false)
                });
            }
            downloadTemplate() {
                window.location.href = '/admin/students/import/sample';
            }
        }

        // Enhanced initialization with better error handling and jQuery check
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM loaded, initializing Students Manager...');

            // Check if jQuery is available
            if (typeof $ === 'undefined') {
                console.error('jQuery is not loaded. Students Manager requires jQuery.');
                alert('jQuery is required but not loaded. Please refresh the page.');
                return;
            }

            try {
                // Wait a bit for all elements to be ready
                setTimeout(() => {
                    window.studentsManager = new StudentsManager();
                    console.log('Students Manager initialized successfully');
                }, 100);

            } catch (error) {
                console.error('Failed to initialize Students Manager:', error);

                // Absolute minimal fallback
                window.studentsManager = {
                    refreshData: () => window.location.reload(),
                    exportData: () => console.log('Export not available'),
                    showToast: (msg) => alert(msg),
                    applyFilters: () => console.log('Filters not available'),
                    clearFilters: () => window.location.href = window.location.pathname,
                    showReactivationModal: (id, name) => console.log('Reactivation not available'),
                    deleteStudent: (id, name) => console.log('Delete not available')
                };
            }
        });

        // Global functions for backward compatibility
        function refreshData() {
            if (window.studentsManager) {
                window.studentsManager.refreshData();
            } else {
                window.location.reload();
            }
        }

        function exportData() {
            if (window.studentsManager) {
                window.studentsManager.exportData();
            } else {
                console.log('Export not available');
            }
        }

        function applyFilters() {
            if (window.studentsManager) {
                window.studentsManager.applyFilters();
            } else {
                console.log('Filters not available');
            }
        }

        // Additional global functions for dropout management
        function showReactivationModal(studentId, studentName) {
            if (window.studentsManager) {
                window.studentsManager.showReactivationModal(studentId, studentName);
            } else {
                console.log('Reactivation not available');
            }
        }

        function deleteStudent(studentId, studentName) {
            if (window.studentsManager) {
                window.studentsManager.deleteStudent(studentId, studentName);
            } else {
                console.log('Delete not available');
            }
        }


    </script>
@endpush