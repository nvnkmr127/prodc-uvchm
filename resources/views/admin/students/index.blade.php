@extends('layouts.theme')
@section('title', 'Students Management')

@push('styles')
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Modern Page Header */
        .page-header-modern {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin: -1.5rem -1.5rem 2rem -1.5rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .header-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-modern {
            border-radius: 25px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-primary-modern {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-success-modern {
            background: var(--success-gradient);
            color: white;
        }

        .btn-info-modern {
            background: var(--info-gradient);
            color: white;
        }

        .btn-secondary-modern {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }

        /* Enhanced Cards */
        .card-modern {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-modern:hover {
            box-shadow: var(--hover-shadow);
            transform: translateY(-2px);
        }

        /* Quick Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .stat-icon {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.1;
        }

        /* Modern Search & Filters */
        .search-filter-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
        }

        .search-bar {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 3rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            position: relative;
        }

        .filter-select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            background: white;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .quick-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .quick-filter-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            color: #6c757d;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quick-filter-btn.active,
        .quick-filter-btn:hover {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
        }

        /* Enhanced Table */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table-modern {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-modern thead th {
            background: #f8f9fc;
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: #5a5c69;
            border-bottom: 2px solid #e3e6f0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-modern tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-modern tbody tr:hover {
            background: #f8f9fc;
            transform: scale(1.001);
        }

        .table-modern td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }

        /* Student Profile Previews */
        .student-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .student-avatar:hover {
            border-color: #667eea;
            transform: scale(1.1);
        }

        .student-details h6 {
            margin: 0;
            font-weight: 600;
            color: #2d3748;
        }

        .student-details .text-muted {
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }

        /* Modern Status Badges */
        .status-badge-modern {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-graduated {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-dropout {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Pagination Styles */
        .pagination-info {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .pagination-controls .pagination {
            margin: 0;
        }

        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: 1px solid #e2e8f0;
            color: #667eea;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            border-color: transparent;
        }



        .status-inactive {
            background: #f3f4f6;
            color: #4b5563;
        }

        /* Enhanced Actions */
        .table-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-table-action {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 0.8rem;
        }

        .btn-table-action:hover {
            transform: scale(1.1);
        }

        /* Bulk Actions */
        .bulk-actions-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 2px solid rgba(102, 126, 234, 0.1);
        }

        .bulk-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .bulk-action-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .bulk-action-item:hover,
        .bulk-action-item.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            transform: translateY(-2px);
        }

        /* Selection Counter */
        .selection-counter {
            background: var(--primary-gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 1rem;
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .selection-counter.show {
            display: flex;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: #adb5bd;
        }

        /* Custom Checkbox */
        .custom-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header-modern {
                padding: 1.5rem 1rem;
                text-align: center;
            }

            .header-title {
                font-size: 1.8rem;
            }

            .header-actions {
                flex-direction: column;
                gap: 0.5rem;
                margin-top: 1rem;
            }

            .stats-number {
                font-size: 1.8rem;
            }

            .bulk-actions-grid {
                grid-template-columns: 1fr;
            }

            .table-modern {
                font-size: 0.85rem;
            }

            .student-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .table-actions {
                flex-direction: column;
                gap: 0.25rem;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Loading States */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            border-radius: var(--border-radius);
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

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
                            <button class="btn btn-light btn-modern" id="showImportModalBtn">
                                <i class="fas fa-file-import"></i> Import
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
            <div class="stat-card">
                <div class="stat-number" id="stat-active">{{ $stats['active'] ?? 0 }}</div>
                <div class="stat-label">Active Students</div>
                <i class="fas fa-user-check stat-icon"></i>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-graduated">{{ $stats['graduated'] ?? 0 }}</div>
                <div class="stat-label">Graduated</div>
                <i class="fas fa-graduation-cap stat-icon"></i>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-dropout">{{ $stats['dropout'] ?? 0 }}</div>
                <div class="stat-label">Dropouts</div>
                <i class="fas fa-user-times stat-icon"></i>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-on_internship">{{ $stats['on_internship'] ?? 0 }}</div>
                <div class="stat-label">On Internship</div>
                <i class="fas fa-briefcase stat-icon"></i>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-total">{{ $stats['total'] ?? 0 }}</div>
                <div class="stat-label">Total Students</div>
                <i class="fas fa-users stat-icon"></i>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate-fade-in">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate-fade-in">
                <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
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
                <button class="quick-filter-btn" data-filter="dropout">Dropout</button>
                <button class="quick-filter-btn" data-filter="recent">Recently Added</button>
                <button class="quick-filter-btn" data-filter="no-contact">Missing Contact</button>
            </div>
        </div>

        <!-- Bulk Actions Panel -->
        <div class="selection-counter" id="selectionCounter">
            <i class="fas fa-check-circle"></i>
            <span id="selectedCount">0</span> students selected
            <div class="ml-auto">
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

        <!-- Total Count Info & Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4 flex-wrap">
            <div class="d-flex align-items-center mb-2 mb-md-0">
                Showing <strong id="visibleCount">{{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }}</strong> of <strong
                    id="totalCount">{{ $students->total() }}</strong> students
            </div>
            <div id="paginationContainer">
                {{ $students->links('pagination::bootstrap-4') }}
            </div>
        </div>


        <!-- Bulk Actions Modal -->
        <div class="modal fade" id="bulkActionsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-tasks mr-2"></i>Bulk Actions
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
                                                <select class="custom-select" id="bulkCourseSelect">
                                                    <option value="">Select Course</option>
                                                    @foreach($courses as $course)
                                                        <option value="{{ $course->id }}">{{ $course->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Batch</label>
                                                <select class="custom-select" id="bulkBatchSelect" disabled>
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
                            <i class="fas fa-file-import mr-2"></i>Import Students
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle mr-2"></i>Import Instructions</h6>
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
                                    <select class="custom-select" id="importCourseSelect" required>
                                        <option value="">Select Course</option>
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}">{{ $course->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Batch *</label>
                                    <select class="custom-select" id="importBatchSelect" disabled required>
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
                            <i class="fas fa-trash mr-2"></i>Confirm Deletion
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
                            <i class="fas fa-user-check mr-2"></i>Reactivate Student
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
                    $('.filter-select').select2({ width: '100%' });
                }

                // Bind filter change events
                ['courseFilter', 'batchFilter'].forEach(id => {
                    $(`#${id}`).on('change', () => this.applyFilters());
                });

                $('#statusFilter').on('change', (e) => {
                    const val = $(e.currentTarget).val();
                    $('.quick-filter-btn').removeClass('active');
                    if (val) {
                        $(`.quick-filter-btn[data-filter="${val}"]`).addClass('active');
                    } else {
                        $('.quick-filter-btn[data-filter="all"]').addClass('active');
                    }
                    this.applyFilters();
                });

                // Clear filters button
                $('#clearFiltersBtn').on('click', () => this.clearFilters());

                // Quick filter buttons in header
                $('.quick-filter-btn').on('click', (e) => {
                    const filter = $(e.currentTarget).data('filter');
                    this.applyQuickFilter(filter);
                });

                // Search input with debounce
                let timeout;
                $('#globalSearch').on('input', (e) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => this.applyFilters(), 500);
                });

                // Handle browser back/forward
                window.onpopstate = () => this.loadFiltersFromUrl();

                // Bind Pagination Clicks (Delegation)
                $(document).on('click', '#paginationContainer a', (e) => {
                    e.preventDefault();
                    const url = $(e.currentTarget).attr('href');
                    if (url) this.loadUrl(url);
                });

                // Bind Delete Student Clicks (Delegation)
                $(document).on('click', '.delete-student-btn', (e) => {
                    e.preventDefault();
                    const btn = $(e.currentTarget);
                    this.deleteStudent(btn.data('student-id'), btn.data('student-name'));
                });

                // Bind Reactivate Student Clicks (Delegation)
                $(document).on('click', '.reactivate-student-btn', (e) => {
                    e.preventDefault();
                    const btn = $(e.currentTarget);
                    this.showReactivationModal(btn.data('student-id'), btn.data('student-name'));
                });

                // Bind Confirm Reactivate Button
                $('#confirmReactivateBtn').on('click', () => {
                    this.executeReactivation();
                });

                // Bulk Selection Bindings
                $('#selectAll').on('change', (e) => this.toggleAllSelection(e));
                $(document).on('change', '.student-checkbox', () => this.updateSelectionCount());
                $('#showBulkActionsBtn').on('click', () => this.showBulkActionsModal());
                $('#clearSelectionBtn').on('click', () => this.clearSelection());

                // Bulk Action Modal Bindings
                $('.bulk-action-item').on('click', (e) => {
                    $('.bulk-action-item').removeClass('active');
                    $(e.currentTarget).addClass('active');
                    this.handleBulkActionSelection($(e.currentTarget).data('action'));
                });

                $('#bulkCourseSelect').on('change', (e) => this.loadBatchesForBulkAction($(e.currentTarget).val()));
                $('#executeBulkAction').on('click', () => this.executeBulkAction());

                // Import Bindings
                $('#showImportModalBtn').on('click', () => this.showImportModal());
                $('#downloadTemplateBtn').on('click', () => this.downloadTemplate());
                $('#importSubmitBtn').on('click', () => this.importStudents());
                $('#importCourseSelect').on('change', (e) => this.loadBatchesForImport($(e.currentTarget).val()));
            }

            // Load specific URL (for pagination)
            loadUrl(url, pushState = true) {
                this.showLoading(true);

                // Update browser history
                if (pushState) {
                    window.history.pushState({}, '', url);
                }

                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    success: (data) => this.handleResponse(data),
                    error: (xhr) => this.handleError(xhr),
                    complete: () => this.showLoading(false)
                });
            }

            applyFilters(pushState = true, targetPage = 1) {
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

                const quickFilter = $('.quick-filter-btn.active').data('filter');
                if (quickFilter === 'recent' || quickFilter === 'no-contact') {
                    params.append('quick_filter', quickFilter);
                }

                // Append target page
                params.append('page', targetPage);

                const queryString = params.toString();
                const url = `${window.location.pathname}?${queryString}`;

                this.loadUrl(url, pushState); // Reuse loadUrl
            }

            // Refactored response handler
            handleResponse(data) {
                if (data.success) {
                    this.updateTable(data.html);
                    this.updateStats(data.stats);
                    this.updateCount(data.count, data.pagination, data.firstItem, data.lastItem);
                }
            }

            handleError(xhr) {
                console.error('Error:', xhr);
                this.showToast('Failed to load data', 'error');
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
                if (stats.active !== undefined) document.getElementById('stat-active').textContent = stats.active;
                if (stats.graduated !== undefined) document.getElementById('stat-graduated').textContent = stats.graduated;
                if (stats.dropout !== undefined) document.getElementById('stat-dropout').textContent = stats.dropout;
                if (stats.on_internship !== undefined) document.getElementById('stat-on_internship').textContent = stats.on_internship;
                if (stats.total !== undefined) document.getElementById('stat-total').textContent = stats.total;
            }

            updateCount(count, paginationHtml, firstItem, lastItem) {
                if (firstItem !== undefined && lastItem !== undefined) {
                    $('#visibleCount').text(`${firstItem} to ${lastItem}`);
                } else {
                    $('#visibleCount').text(document.querySelectorAll('#studentsTable tbody tr').length);
                }
                $('#totalCount').text(count); // Total from server
                
                if (paginationHtml) {
                    $('#paginationContainer').html(paginationHtml); // Replace pagination links
                }

                $('#selectedCount').text('0'); // Reset selection
                $('.student-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false);
            }

            toggleAllSelection(e) {
                const isChecked = $(e.currentTarget).prop('checked');
                $('.student-checkbox').prop('checked', isChecked);
                this.updateSelectionCount();
            }

            updateSelectionCount() {
                const count = $('.student-checkbox:checked').length;
                $('#selectedCount').text(count);
                if (count > 0) {
                    $('#selectionCounter').removeClass('d-none');
                } else {
                    $('#selectionCounter').addClass('d-none');
                }
            }

            clearSelection() {
                $('.student-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false);
                this.updateSelectionCount();
            }

            getSelectedStudentIds() {
                return $('.student-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
            }

            showBulkActionsModal() {
                const selectedIds = this.getSelectedStudentIds();
                if (selectedIds.length === 0) {
                    this.showToast('Please select at least one student.', 'error');
                    return;
                }
                $('#bulkSelectedCount').text(selectedIds.length);
                $('#bulkActionsModal').modal('show');
            }

            handleBulkActionSelection(action) {
                $('.bulk-action-options').addClass('d-none');
                if (action === 'batch') {
                    $('#batchChangeOptions').removeClass('d-none');
                }
            }

            loadBatchesForBulkAction(courseId) {
                if (!courseId) {
                    $('#bulkBatchSelect').prop('disabled', true).html('<option value="">Select Course First</option>');
                    return;
                }
                
                $.get(`/admin/get-batches-for-course/${courseId}`, (data) => {
                    let options = '<option value="">Select Batch</option>';
                    data.forEach(batch => {
                        options += `<option value="${batch.id}">${batch.name}</option>`;
                    });
                    $('#bulkBatchSelect').prop('disabled', false).html(options);
                }).fail(() => {
                    this.showToast('Failed to load batches', 'error');
                });
            }
            loadBatchesForImport(courseId) {
                if (!courseId) {
                    $('#importBatchSelect').prop('disabled', true).html('<option value="">Select Course First</option>');
                    return;
                }
                
                $.get(`/admin/get-batches-for-course/${courseId}`, (data) => {
                    let options = '<option value="">Select Batch</option>';
                    data.forEach(batch => {
                        options += `<option value="${batch.id}">${batch.name}</option>`;
                    });
                    $('#importBatchSelect').prop('disabled', false).html(options);
                }).fail(() => {
                    this.showToast('Failed to load batches', 'error');
                });
            }

            executeBulkAction() {
                const selectedActionItem = $('.bulk-action-item.active');
                if (selectedActionItem.length === 0) {
                    this.showToast('Please select a bulk action.', 'error');
                    return;
                }
                
                let action = selectedActionItem.data('action');
                let value = selectedActionItem.data('value');
                
                if (action === 'batch') {
                    action = 'assign_batch';
                    value = $('#bulkBatchSelect').val();
                    if (!value) {
                        this.showToast('Please select a target batch.', 'error');
                        return;
                    }
                } else if (action === 'status') {
                    action = 'change_status';
                }

                const selectedIds = this.getSelectedStudentIds();
                if (selectedIds.length === 0) return;

                if (selectedActionItem.data('action') === 'export') {
                    const params = new URLSearchParams(window.location.search);
                    selectedIds.forEach(id => params.append('student_ids[]', id));
                    window.location.href = `/admin/students/export?${params.toString()}`;
                    $('#bulkActionsModal').modal('hide');
                    this.clearSelection();
                    return;
                }
                
                const btn = $('#executeBulkAction');
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                $.post('/admin/students/bulk-actions', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    action: action,
                    value: value,
                    batch_id: action === 'assign_batch' ? value : null,
                    status: action === 'change_status' ? value : null,
                    student_ids: selectedIds
                }, (res) => {
                    if (res.success) {
                        this.showToast(res.message || 'Bulk action completed successfully.', 'success');
                        $('#bulkActionsModal').modal('hide');
                        this.clearSelection();
                        this.refreshData();
                    } else {
                        this.showToast(res.message || 'Bulk action failed.', 'error');
                    }
                }).fail((xhr) => {
                    this.showToast(xhr.responseJSON?.message || 'Error executing bulk action.', 'error');
                }).always(() => {
                    btn.prop('disabled', false).html(originalHtml);
                });
            }

            clearFilters() {
                $('#courseFilter').val('').trigger('change.select2');
                $('#batchFilter').val('').trigger('change.select2');
                $('#statusFilter').val('').trigger('change');
                $('#globalSearch').val('');
                this.applyFilters();
            }

            applyQuickFilter(filterType) {
                $('.quick-filter-btn').removeClass('active');
                $(`.quick-filter-btn[data-filter="${filterType}"]`).addClass('active');

                if (filterType === 'all') {
                    $('#statusFilter').val('');
                } else if (filterType === 'active' || filterType === 'graduated' || filterType === 'dropout') {
                    $('#statusFilter').val(filterType);
                } else {
                    $('#statusFilter').val('');
                }
                
                this.applyFilters();
            }

            loadFiltersFromUrl() {
                const params = new URLSearchParams(window.location.search);
                if (params.has('course_id')) $('#courseFilter').val(params.get('course_id')).trigger('change');
                if (params.has('batch_id')) $('#batchFilter').val(params.get('batch_id')).trigger('change');
                
                $('.quick-filter-btn').removeClass('active');
                if (params.has('quick_filter')) {
                    $(`.quick-filter-btn[data-filter="${params.get('quick_filter')}"]`).addClass('active');
                    $('#statusFilter').val('');
                } else if (params.has('status')) {
                    $(`.quick-filter-btn[data-filter="${params.get('status')}"]`).addClass('active');
                    $('#statusFilter').val(params.get('status'));
                } else {
                    $(`.quick-filter-btn[data-filter="all"]`).addClass('active');
                    $('#statusFilter').val('');
                }

                if (params.has('search')) $('#globalSearch').val(params.get('search'));

                let targetPage = 1;
                if (params.has('page')) {
                    targetPage = parseInt(params.get('page')) || 1;
                }

                this.applyFilters(false, targetPage);
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
                toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} mr-2"></i>${message}`;
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