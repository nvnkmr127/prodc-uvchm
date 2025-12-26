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
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn-modern:hover::before {
        left: 100%;
    }

    .btn-primary-modern { background: var(--primary-gradient); color: white; }
    .btn-success-modern { background: var(--success-gradient); color: white; }
    .btn-info-modern { background: var(--info-gradient); color: white; }
    .btn-secondary-modern { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; }

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

    .status-active { background: #d1fae5; color: #065f46; }
    .status-graduated { background: #dbeafe; color: #1e40af; }
    .status-dropout { background: #fee2e2; color: #991b1b; }

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

    /* Load More Button */
    #loadMoreContainer {
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .status-inactive { background: #f3f4f6; color: #4b5563; }

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
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
            <div class="stat-number">{{ $stats['active'] ?? 0 }}</div>
            <div class="stat-label">Active Students</div>
            <i class="fas fa-user-check stat-icon"></i>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['graduated'] ?? 0 }}</div>
            <div class="stat-label">Graduated</div>
            <i class="fas fa-graduation-cap stat-icon"></i>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['dropout'] ?? 0 }}</div>
            <div class="stat-label">Dropouts</div>
            <i class="fas fa-user-times stat-icon"></i>
        </div>
        <div class="stat-card">
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
            <input type="text" class="search-input" id="globalSearch" placeholder="Search students by name, enrollment, or mobile...">
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
                @forelse ($students as $student)
                    <tr class="student-row" data-student-id="{{ $student->id }}">
                        <td>
                            <input type="checkbox" class="custom-checkbox student-checkbox" value="{{ $student->id }}">
                        </td>
                        <td>
                            <div class="student-info">
                                <img class="student-avatar" 
                                     src="{{ \App\Http\Controllers\Admin\StudentController::getStudentPhotoUrl($student, 50) }}" 
                                     alt="{{ $student->name }}"
                                     loading="lazy">
                             <div class="student-details">
    <h6>
        <a href="{{ route('admin.students.show', $student) }}">
            {{ $student->name }}
        </a>
    </h6>
    <div class="text-muted">ID: {{ $student->enrollment_number }}</div>
</div>

                            </div>
                        </td>
                        <td>
                            <span class="badge badge-light">{{ $student->enrollment_number }}</span>
                        </td>
                        <td>
                            @if ($student->batch)
                                <div>
                                    <strong>{{ $student->batch->course->name ?? 'N/A' }}</strong>
                                </div>
                                <small class="text-muted">{{ $student->batch->name }}</small>
                            @else
                                <span class="text-muted">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    Not Assigned
                                </span>
                            @endif
                        </td>
                        <td class="contact-cell" data-student-id="{{ $student->id }}">
                            <div class="contact-display">
                                @if($student->student_mobile)
                                    <div class="editable-field" data-field="student_mobile" data-value="{{ $student->student_mobile }}">
                                        <i class="fas fa-mobile-alt text-primary"></i>
                                        <span class="field-value">{{ $student->student_mobile }}</span>
                                        <i class="fas fa-pencil-alt text-muted edit-icon" style="font-size: 0.7rem; cursor: pointer;"></i>
                                    </div>
                                @else
                                    <div class="editable-field" data-field="student_mobile" data-value="">
                                        <i class="fas fa-mobile-alt text-muted"></i>
                                        <span class="field-value text-muted">Add mobile</span>
                                        <i class="fas fa-plus text-muted edit-icon" style="font-size: 0.7rem; cursor: pointer;"></i>
                                    </div>
                                @endif
                                @if($student->father_mobile)
                                    <small class="text-muted editable-field" data-field="father_mobile" data-value="{{ $student->father_mobile }}">
                                        <i class="fas fa-phone text-secondary"></i>
                                        <span class="field-value">{{ $student->father_mobile }}</span>
                                        <i class="fas fa-pencil-alt text-muted edit-icon" style="font-size: 0.6rem; cursor: pointer;"></i>
                                    </small>
                                @endif
                            </div>
                        </td>
                        <td class="status-cell" data-student-id="{{ $student->id }}">
                            <span class="status-badge-modern status-{{ $student->status }} editable-status"
                                  data-field="status"
                                  data-value="{{ $student->status }}"
                                  style="cursor: pointer;"
                                  title="Click to change status">
                                {{ ucfirst($student->status) }}
                                <i class="fas fa-pencil-alt" style="font-size: 0.6rem; margin-left: 3px;"></i>
                            </span>
                        </td>
                      <td>
    <div class="table-actions">
        <a href="{{ route('admin.students.show', $student) }}" 
           class="btn btn-info btn-table-action" 
           title="View Profile">
            <i class="fas fa-eye"></i>
        </a>
        <a href="{{ route('admin.students.edit', $student) }}" 
           class="btn btn-warning btn-table-action" 
           title="Edit Student">
            <i class="fas fa-edit"></i>
        </a>
        
        {{-- DROPOUT MANAGEMENT BUTTONS --}}
        @if($student->status === 'active')
            <a href="{{ route('admin.students.confirm-dropout', $student) }}" 
               class="btn btn-warning btn-table-action" 
               title="Mark as Dropout">
                <i class="fas fa-user-times"></i>
            </a>
        @elseif($student->status === 'dropout')
            <button class="btn btn-success btn-table-action reactivate-student-btn" 
                    data-student-id="{{ $student->id }}"
                    data-student-name="{{ $student->name }}"
                    title="Reactivate Student">
                <i class="fas fa-user-check"></i>
            </button>
        @endif
        
        @if(auth()->user()->hasRole('super-admin'))
        <button class="btn btn-table-action btn-danger delete-student-btn" 
                data-student-id="{{ $student->id }}"
                data-student-name="{{ $student->name }}"
                title="Delete Student">
            <i class="fas fa-trash"></i>
        </button>
        @endif
    </div>
</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Total Count Info -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div class="pagination-info">
            Showing <strong>{{ $students->count() }}</strong> students total
        </div>
    </div>

    @if($students->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-users"></i>
            </div>
            <h5>No Students Found</h5>
            @if(request()->hasAny(['course_id', 'batch_id', 'status']))
                <p>No students match your current filters.</p>
                <button class="btn btn-primary-modern btn-modern" id="clearFiltersBtn">
                    Clear Filters
                </button>
            @else
                <p>Start by adding your first student or importing student data.</p>
                <div class="mt-3">
                    <a href="{{ route('admin.students.create') }}" class="btn btn-primary-modern btn-modern me-2">
                        Add Student
                    </a>
                    <button class="btn btn-success-modern btn-modern" id="showImportModalBtn">
                        Import Students
                    </button>
                </div>
            @endif
        </div>
    @endif

    <!-- Bulk Actions Modal -->
    <div class="modal fade" id="bulkActionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tasks me-2"></i>Bulk Actions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                        <strong>Note:</strong> This will generate new enrollment numbers and fee structures.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                <input type="file" class="form-control" id="importFile" accept=".xlsx,.xls,.csv" required>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                        <h6>Are you sure you want to delete this student?</h6>
                        <p class="text-muted" id="deleteStudentName">This action cannot be undone.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                    <p class="text-muted" id="reactivateStudentName">This will restore the student to active status.</p>
                    
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
        this.selectedStudents = new Set();
        this.currentAction = null;
        this.currentFilters = {
            search: '',
            course: '',
            batch: '',
            status: ''
        };
        this.init();
    }

    init() {
        this.initEventListeners();
        this.initDataTable();
        this.loadSavedFilters();
        this.fixModalCloseButtons();
    }

    // Fix Bootstrap 5 syntax in modal close buttons
    fixModalCloseButtons() {
        // Fix all modal close buttons to use Bootstrap 4 syntax
        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.setAttribute('data-dismiss', 'modal');
            btn.removeAttribute('data-bs-dismiss');
        });
        
        // Fix button classes
        document.querySelectorAll('.btn-close').forEach(btn => {
            btn.innerHTML = '<span aria-hidden="true">&times;</span>';
            btn.className = btn.className.replace('btn-close', 'close');
        });
    }

    initEventListeners() {
        // Basic action buttons
        const refreshBtn = document.getElementById('refreshDataBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshData());
        }

        const exportBtn = document.getElementById('exportDataBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportData());
        }

        // Filter buttons
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => this.clearFilters());
        }

        const showImportModalBtn = document.getElementById('showImportModalBtn');
        if (showImportModalBtn) {
            showImportModalBtn.addEventListener('click', () => this.showImportModal());
        }

        const downloadTemplateBtn = document.getElementById('downloadTemplateBtn');
        if (downloadTemplateBtn) {
            downloadTemplateBtn.addEventListener('click', () => this.downloadTemplate());
        }

        // Selection counter buttons
        const clearSelectionBtn = document.getElementById('clearSelectionBtn');
        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', () => this.clearSelection());
        }

        const showBulkActionsBtn = document.getElementById('showBulkActionsBtn');
        if (showBulkActionsBtn) {
            showBulkActionsBtn.addEventListener('click', () => this.showBulkActions());
        }

        // FIXED: Single event listener for all student action buttons
        document.addEventListener('click', (e) => {
            // Handle delete buttons
            if (e.target.closest('.delete-student-btn')) {
                const deleteBtn = e.target.closest('.delete-student-btn');
                const studentId = deleteBtn.dataset.studentId;
                const studentName = deleteBtn.dataset.studentName;
                this.deleteStudent(studentId, studentName);
                return;
            }
            
            // Handle reactivation buttons
            if (e.target.closest('.reactivate-student-btn')) {
                const reactivateBtn = e.target.closest('.reactivate-student-btn');
                const studentId = reactivateBtn.dataset.studentId;
                const studentName = reactivateBtn.dataset.studentName;
                this.showReactivationModal(studentId, studentName);
                return;
            }
        });

        // Search functionality with debouncing
        let searchTimeout;
        const globalSearch = document.getElementById('globalSearch');
        if (globalSearch) {
            globalSearch.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentFilters.search = e.target.value;
                    this.filterTable();
                }, 300);
            });
        }

        // Filter controls
        const courseFilter = document.getElementById('courseFilter');
        if (courseFilter) {
            courseFilter.addEventListener('change', (e) => {
                this.currentFilters.course = e.target.value;
                this.loadBatchesForCourse(e.target.value, 'batchFilter');
            });
        }

        const batchFilter = document.getElementById('batchFilter');
        if (batchFilter) {
            batchFilter.addEventListener('change', (e) => {
                this.currentFilters.batch = e.target.value;
            });
        }

        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.currentFilters.status = e.target.value;
            });
        }

        // Quick filters
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.quick-filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.applyQuickFilter(e.target.dataset.filter);
            });
        });

        // Checkbox selection
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                this.selectAllStudents(e.target.checked);
            });
        }

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('student-checkbox')) {
                this.toggleStudentSelection(e.target.value, e.target.checked);
            }
        });

        // Bulk actions
        document.querySelectorAll('.bulk-action-item').forEach(item => {
            item.addEventListener('click', (e) => {
                this.selectBulkAction(e.currentTarget);
            });
        });

        // Modal event handlers
        this.initModalHandlers();

        // Course selection for bulk operations
        const bulkCourseSelect = document.getElementById('bulkCourseSelect');
        if (bulkCourseSelect) {
            bulkCourseSelect.addEventListener('change', (e) => {
                this.loadBatchesForCourse(e.target.value, 'bulkBatchSelect');
                
                // Reset execute button when course changes
                const executeBtn = document.getElementById('executeBulkAction');
                if (executeBtn && this.currentAction?.type === 'batch') {
                    executeBtn.disabled = true;
                    executeBtn.textContent = 'Select Batch First';
                }
            });
        }

        const importCourseSelect = document.getElementById('importCourseSelect');
        if (importCourseSelect) {
            importCourseSelect.addEventListener('change', (e) => {
                this.loadBatchesForCourse(e.target.value, 'importBatchSelect');
            });
        }

        // Import functionality
        const importFile = document.getElementById('importFile');
        const importSubmitBtn = document.getElementById('importSubmitBtn');
        if (importFile && importSubmitBtn) {
            importFile.addEventListener('change', (e) => {
                importSubmitBtn.disabled = !e.target.files.length;
            });
        }

        if (importSubmitBtn) {
            importSubmitBtn.addEventListener('click', () => {
                this.submitImport();
            });
        }

        // Bulk batch selection handler
        $(document).on('change', '#bulkBatchSelect', () => {
            const executeBtn = document.getElementById('executeBulkAction');
            const batchSelect = document.getElementById('bulkBatchSelect');
            
            if (executeBtn && batchSelect && this.currentAction?.type === 'batch') {
                if (batchSelect.value) {
                    executeBtn.disabled = false;
                    executeBtn.textContent = 'Execute: Assign to Batch';
                } else {
                    executeBtn.disabled = true;
                    executeBtn.textContent = 'Select Batch First';
                }
            }
        });

        // FIXED: Add reactivation confirmation handler
        const confirmReactivateBtn = document.getElementById('confirmReactivateBtn');
        if (confirmReactivateBtn) {
            confirmReactivateBtn.addEventListener('click', () => {
                this.executeReactivation();
            });
        }
    }

    initModalHandlers() {
        // Bulk Actions Modal
        const bulkActionsModal = document.getElementById('bulkActionsModal');
        if (bulkActionsModal) {
            $(bulkActionsModal).on('show.bs.modal', () => {
                const bulkSelectedCount = document.getElementById('bulkSelectedCount');
                if (bulkSelectedCount) {
                    bulkSelectedCount.textContent = this.selectedStudents.size;
                }
                
                // Reset action selection
                this.currentAction = null;
                document.querySelectorAll('.bulk-action-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                const batchAssignmentSection = document.getElementById('batchAssignmentSection');
                if (batchAssignmentSection) {
                    batchAssignmentSection.style.display = 'none';
                }
                
                const executeBulkAction = document.getElementById('executeBulkAction');
                if (executeBulkAction) {
                    executeBulkAction.disabled = true;
                    executeBulkAction.textContent = 'Select an Action';
                }
            });
            
            // Fix accessibility issues after modal is shown
            $(bulkActionsModal).on('shown.bs.modal', function() {
                $(this).removeAttr('aria-hidden');
                $(this).find('button, input, select, textarea, [tabindex]').removeAttr('aria-hidden');
                
                // Override Bootstrap's accessibility blocking
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'aria-hidden') {
                            if ($(bulkActionsModal).hasClass('show')) {
                                $(bulkActionsModal).removeAttr('aria-hidden');
                                $(bulkActionsModal).find('button, input, select, textarea, [tabindex]').removeAttr('aria-hidden');
                            }
                        }
                    });
                });
                
                observer.observe(bulkActionsModal, {
                    attributes: true,
                    attributeFilter: ['aria-hidden'],
                    subtree: true
                });
                
                $(bulkActionsModal).data('aria-observer', observer);
            });
            
            // Clean up observer when modal is hidden
            $(bulkActionsModal).on('hidden.bs.modal', function() {
                const observer = $(this).data('aria-observer');
                if (observer) {
                    observer.disconnect();
                    $(this).removeData('aria-observer');
                }
            });
        }

        // Execute bulk action button
        const executeBulkAction = document.getElementById('executeBulkAction');
        if (executeBulkAction) {
            executeBulkAction.addEventListener('click', () => {
                this.executeBulkAction();
            });
        }
    }

    initDataTable() {
        this.enhanceTable();
    }

    enhanceTable() {
        const table = document.getElementById('studentsTable');
        if (!table) return;
        
        // Add sorting to headers
        table.querySelectorAll('th').forEach((th, index) => {
            if (index > 0 && index < 6) { // Skip checkbox and actions columns
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => this.sortTable(index));
            }
        });
    }

    sortTable(columnIndex) {
        const table = document.getElementById('studentsTable');
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const isAscending = table.dataset.sortDirection !== 'asc';
        table.dataset.sortDirection = isAscending ? 'asc' : 'desc';
        
        rows.sort((a, b) => {
            if (!a.cells[columnIndex] || !b.cells[columnIndex]) return 0;
            
            const aText = a.cells[columnIndex].textContent.trim();
            const bText = b.cells[columnIndex].textContent.trim();
            
            if (isAscending) {
                return aText.localeCompare(bText);
            } else {
                return bText.localeCompare(aText);
            }
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }

    loadBatchesForCourse(courseId, targetSelectId) {
        const select = document.getElementById(targetSelectId);
        if (!select) return;
        
        if (!courseId) {
            select.innerHTML = '<option value="">Select Course First</option>';
            select.disabled = true;
            return;
        }

        select.innerHTML = '<option value="">Loading...</option>';
        select.disabled = true;

        // FIXED: Use correct route path
        $.ajax({
            url: `/admin/get-batches-for-course/${courseId}`,
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: (data) => {
                select.innerHTML = '<option value="">Select Batch</option>';
                if (Array.isArray(data)) {
                    data.forEach(batch => {
                        select.innerHTML += `<option value="${batch.id}">${batch.name}</option>`;
                    });
                } else if (data.batches && Array.isArray(data.batches)) {
                    data.batches.forEach(batch => {
                        select.innerHTML += `<option value="${batch.id}">${batch.name}</option>`;
                    });
                }
                select.disabled = false;
                
                // Trigger change event for bulk batch select to update execute button
                if (targetSelectId === 'bulkBatchSelect') {
                    $(select).trigger('change');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error loading batches:', error, xhr.responseText);
                select.innerHTML = '<option value="">Error loading batches</option>';
                select.disabled = false;
                this.showToast('Failed to load batches', 'error');
            }
        });
    }

    filterTable() {
        const rows = document.querySelectorAll('.student-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            let visible = true;
            
            // Search filter
            if (this.currentFilters.search) {
                const searchText = row.textContent.toLowerCase();
                visible = visible && searchText.includes(this.currentFilters.search.toLowerCase());
            }
            
            // Course filter
            if (this.currentFilters.course) {
                const courseCell = row.cells[3] ? row.cells[3].textContent : '';
                visible = visible && courseCell.includes(this.getCourseNameById(this.currentFilters.course));
            }
            
            // Status filter
            if (this.currentFilters.status) {
                const statusCell = row.cells[5] ? row.cells[5].textContent.toLowerCase() : '';
                visible = visible && statusCell.includes(this.currentFilters.status);
            }
            
            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });
        
        this.updateResultCount(visibleCount);
    }

    applyQuickFilter(filter) {
        const rows = document.querySelectorAll('.student-row');
        
        rows.forEach(row => {
            let visible = true;
            
            switch (filter) {
                case 'all':
                    visible = true;
                    break;
                case 'active':
                    visible = row.cells[5] && row.cells[5].textContent.toLowerCase().includes('active');
                    break;
                case 'graduated':
                    visible = row.cells[5] && row.cells[5].textContent.toLowerCase().includes('graduated');
                    break;
                case 'recent':
                    // Logic for recently added students - placeholder
                    visible = true;
                    break;
                case 'no-contact':
                    visible = row.cells[4] && row.cells[4].textContent.includes('No Contact');
                    break;
            }
            
            row.style.display = visible ? '' : 'none';
        });
    }

    selectAllStudents(checked) {
        const visibleCheckboxes = Array.from(document.querySelectorAll('.student-checkbox'))
            .filter(cb => cb.closest('tr').style.display !== 'none');
        
        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = checked;
            this.toggleStudentSelection(checkbox.value, checked);
        });
    }

    toggleStudentSelection(studentId, selected) {
        if (selected) {
            this.selectedStudents.add(studentId);
        } else {
            this.selectedStudents.delete(studentId);
        }
        
        this.updateSelectionCounter();
    }

    updateSelectionCounter() {
        const counter = document.getElementById('selectionCounter');
        const count = this.selectedStudents.size;
        
        const selectedCount = document.getElementById('selectedCount');
        if (selectedCount) {
            selectedCount.textContent = count;
        }
        
        if (counter) {
            if (count > 0) {
                counter.classList.add('show');
            } else {
                counter.classList.remove('show');
            }
        }
    }

    clearSelection() {
        this.selectedStudents.clear();
        document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.checked = false;
        }
        this.updateSelectionCounter();
    }

    showBulkActions() {
        if (this.selectedStudents.size === 0) {
            this.showToast('Please select at least one student', 'warning');
            return;
        }
        
        const bulkSelectedCount = document.getElementById('bulkSelectedCount');
        if (bulkSelectedCount) {
            bulkSelectedCount.textContent = this.selectedStudents.size;
        }
        
        const modal = $('#bulkActionsModal');
        if (modal.length) {
            modal.on('shown.bs.modal', function() {
                $(this).removeAttr('aria-hidden');
                $(this).find('button, input, select, textarea').removeAttr('aria-hidden');
            });
            
            modal.modal({
                backdrop: 'static',
                keyboard: true,
                focus: false
            });
            
            modal.modal('show');
        }
    }

    selectBulkAction(actionElement) {
        document.querySelectorAll('.bulk-action-item').forEach(item => {
            item.classList.remove('active');
        });
        
        actionElement.classList.add('active');
        this.currentAction = {
            type: actionElement.dataset.action,
            value: actionElement.dataset.value
        };
        
        const batchSection = document.getElementById('batchAssignmentSection');
        const executeBtn = document.getElementById('executeBulkAction');
        
        if (this.currentAction.type === 'batch') {
            if (batchSection) {
                batchSection.style.display = 'block';
            }
            if (executeBtn) {
                executeBtn.disabled = true;
                executeBtn.textContent = 'Select Batch First';
            }
            
            const bulkBatchSelect = document.getElementById('bulkBatchSelect');
            if (bulkBatchSelect) {
                bulkBatchSelect.addEventListener('change', () => {
                    if (executeBtn && bulkBatchSelect.value) {
                        executeBtn.disabled = false;
                        executeBtn.textContent = 'Execute: Assign to Batch';
                    }
                });
            }
        } else {
            if (batchSection) {
                batchSection.style.display = 'none';
            }
            if (executeBtn) {
                executeBtn.disabled = false;
                const actionTitle = actionElement.querySelector('h6');
                executeBtn.textContent = `Execute: ${actionTitle ? actionTitle.textContent : 'Action'}`;
            }
        }
    }

    executeBulkAction() {
        if (!this.currentAction || this.selectedStudents.size === 0) {
            this.showToast('No action selected or no students selected', 'error');
            return;
        }
        
        // Handle export action separately
        if (this.currentAction.type === 'export') {
            this.exportSelectedStudents();
            return;
        }
        
        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            student_ids: Array.from(this.selectedStudents)
        };
        
        if (this.currentAction.type === 'status') {
            formData.action = 'change_status';
            formData.status = this.currentAction.value;
        } else if (this.currentAction.type === 'batch') {
            const batchSelect = document.getElementById('bulkBatchSelect');
            const batchId = batchSelect ? batchSelect.value : '';
            if (!batchId) {
                this.showToast('Please select a batch', 'error');
                return;
            }
            formData.action = 'assign_batch';
            formData.batch_id = batchId;
        } else if (this.currentAction.type === 'delete') {
            if (!confirm('Are you sure you want to delete the selected students? This action cannot be undone.')) {
                return;
            }
            formData.action = 'delete';
        }
        
        this.showLoading(true);
        
        $.ajax({
            url: '/admin/students/bulk-actions',
            method: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: (result) => {
                this.showLoading(false);
                if (result.success) {
                    this.showToast(result.message || 'Action completed successfully', 'success');
                    $('#bulkActionsModal').modal('hide');
                    setTimeout(() => {
                        $('#bulkActionsModal').removeAttr('aria-hidden');
                    }, 300);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToast(result.message || 'Action failed', 'error');
                }
            },
            error: (xhr, status, error) => {
                this.showLoading(false);
                let errorMessage = 'An error occurred while processing the action';
                
                if (xhr.status === 419) {
                    errorMessage = 'Session expired. Please refresh the page and try again.';
                } else if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        errorMessage = Object.values(errors).flat().join('\n');
                    }
                } else if (xhr.responseJSON?.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                this.showToast(errorMessage, 'error');
                console.error('Bulk action error:', error, xhr.responseText);
            }
        });
    }

    exportSelectedStudents() {
        const studentIds = Array.from(this.selectedStudents).join(',');
        const url = `/admin/students/export?student_ids=${studentIds}`;
        window.open(url, '_blank');
        
        $('#bulkActionsModal').modal('hide');
        setTimeout(() => {
            $('#bulkActionsModal').removeAttr('aria-hidden');
        }, 300);
    }

    submitImport() {
        const form = document.getElementById('importForm');
        if (!form) return;
        
        const courseSelect = document.getElementById('importCourseSelect');
        const batchSelect = document.getElementById('importBatchSelect'); 
        const fileInput = document.getElementById('importFile');
        
        if (!courseSelect?.value || !batchSelect?.value || !fileInput?.files[0]) {
            this.showToast('Please fill all required fields', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('course_id', courseSelect.value);
        formData.append('batch_id', batchSelect.value);
        formData.append('import_file', fileInput.files[0]);
        
        const submitBtn = document.getElementById('importSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            const spinner = submitBtn.querySelector('.fa-spinner');
            const uploadIcon = submitBtn.querySelector('.fa-upload');
            if (spinner) spinner.classList.remove('d-none');
            if (uploadIcon) uploadIcon.classList.add('d-none');
        }
        
        $.ajax({
            url: '/admin/students/import',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: (result) => {
                if (result.success) {
                    this.showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToast(result.message || 'Import failed', 'error');
                }
            },
            error: (xhr, status, error) => {
                let errorMessage = 'Import failed';
                if (xhr.responseJSON?.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        errorMessage = Object.values(errors).flat().join('\n');
                    }
                }
                this.showToast(errorMessage, 'error');
                console.error('Import error:', error, xhr.responseText);
            },
            complete: () => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    const spinner = submitBtn.querySelector('.fa-spinner');
                    const uploadIcon = submitBtn.querySelector('.fa-upload');
                    if (spinner) spinner.classList.add('d-none');
                    if (uploadIcon) uploadIcon.classList.remove('d-none');
                }
            }
        });
    }

    // FIXED: Add reactivation modal functionality
    showReactivationModal(studentId, studentName) {
        const reactivateStudentName = document.getElementById('reactivateStudentName');
        if (reactivateStudentName) {
            reactivateStudentName.textContent = `Student: ${studentName}`;
        }
        
        // Clear previous reason
        const reactivationReason = document.getElementById('reactivationReason');
        if (reactivationReason) {
            reactivationReason.value = '';
        }
        
        // Store student info for later use
        this.currentReactivationStudent = {
            id: studentId,
            name: studentName
        };
        
        const modal = $('#reactivateModal');
        if (modal.length) {
            modal.removeAttr('aria-hidden');
            modal.modal({
                backdrop: 'static',
                keyboard: true,
                focus: true
            });
            modal.modal('show');
        }
    }

    executeReactivation() {
        if (!this.currentReactivationStudent) {
            this.showToast('No student selected for reactivation', 'error');
            return;
        }

        const reason = document.getElementById('reactivationReason')?.value || 'Reactivated from student list';
        const studentId = this.currentReactivationStudent.id;
        
        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            reason: reason
        };
        
        // Show loading state
        const confirmBtn = document.getElementById('confirmReactivateBtn');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reactivating...';
        }
        
        $.ajax({
            url: `/admin/students/${studentId}/reactivate`,
            method: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: (result) => {
                if (result.success) {
                    this.showToast(result.message, 'success');
                    $('#reactivateModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showToast(result.message || 'Reactivation failed', 'error');
                }
            },
            error: (xhr, status, error) => {
                let errorMessage = 'Reactivation failed';
                if (xhr.responseJSON?.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                this.showToast(errorMessage, 'error');
                console.error('Reactivation error:', error, xhr.responseText);
            },
            complete: () => {
                // Reset button state
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-user-check"></i> Reactivate Student';
                }
            }
        });
    }

    showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('d-none', !show);
        }
    }

    // FIXED: Improve toast implementation
    showToast(message, type = 'info') {
        // Remove any existing toasts first
        document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        const alertClass = type === 'error' ? 'danger' : type;
        toast.className = `alert alert-${alertClass} alert-dismissible fade show position-fixed toast-notification`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
        
        const icon = type === 'success' ? 'check-circle' : 
                     type === 'error' ? 'exclamation-triangle' : 
                     type === 'warning' ? 'exclamation-circle' : 'info-circle';
        
        toast.innerHTML = `
            <i class="fas fa-${icon} me-2"></i>
            ${message}
            <button type="button" class="close" onclick="this.parentElement.remove()">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }

    updateResultCount(count) {
        console.log(`Showing ${count} students`);
    }

    getCourseNameById(courseId) {
        const courseSelect = document.getElementById('courseFilter');
        if (!courseSelect) return '';
        
        const option = courseSelect.querySelector(`option[value="${courseId}"]`);
        return option ? option.textContent : '';
    }

    loadSavedFilters() {
        // Load any saved filter preferences
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('course_id')) {
            this.currentFilters.course = urlParams.get('course_id');
        }
        if (urlParams.get('status')) {
            this.currentFilters.status = urlParams.get('status');
        }
    }

    // Public methods for global access
    refreshData() {
        location.reload();
    }

    exportData() {
        window.location.href = '/admin/students/export';
    }

    applyFilters() {
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = window.location.pathname;
        
        const filters = ['courseFilter', 'batchFilter', 'statusFilter'];
        filters.forEach(id => {
            const select = document.getElementById(id);
            if (select && select.value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = id.replace('Filter', '_id').replace('status_id', 'status');
                input.value = select.value;
                form.appendChild(input);
            }
        });
        
        document.body.appendChild(form);
        form.submit();
    }

    clearFilters() {
        window.location.href = window.location.pathname;
    }

    showImportModal() {
        const modal = $('#importModal');
        if (modal.length) {
            modal.removeAttr('aria-hidden');
            modal.modal({
                backdrop: 'static',
                keyboard: true,
                focus: true
            });
            modal.modal('show');
        }
    }

    downloadTemplate() {
        window.location.href = '/admin/students/import/sample';
    }

    deleteStudent(studentId, studentName) {
        const deleteStudentName = document.getElementById('deleteStudentName');
        if (deleteStudentName) {
            deleteStudentName.textContent = `Student: ${studentName}`;
        }
        
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.onclick = () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/students/${studentId}`;
                
                const csrfToken = $('meta[name="csrf-token"]').attr('content');
                if (csrfToken) {
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = csrfToken;
                    form.appendChild(tokenInput);
                }
                
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            };
        }
        
        const modal = $('#deleteModal');
        if (modal.length) {
            modal.removeAttr('aria-hidden');
            modal.modal({
                backdrop: 'static',
                keyboard: true,
                focus: true
            });
            modal.modal('show');
        }
    }
}

// Enhanced initialization with better error handling and jQuery check
document.addEventListener('DOMContentLoaded', function() {
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

// ============================================
// INLINE EDITING FUNCTIONALITY
// ============================================

class InlineEditor {
    constructor() {
        this.init();
    }

    init() {
        // Handle status click
        $(document).on('click', '.editable-status', (e) => {
            this.editStatus(e.currentTarget);
        });

        // Handle contact field click
        $(document).on('click', '.editable-field', (e) => {
            this.editField(e.currentTarget);
        });
    }

    editStatus(element) {
        const $element = $(element);
        const currentValue = $element.data('value');
        const studentId = $element.closest('td').data('student-id');

        // Create dropdown
        const statusOptions = [
            { value: 'active', label: 'Active', class: 'status-active' },
            { value: 'inactive', label: 'Inactive', class: 'status-inactive' },
            { value: 'graduated', label: 'Graduated', class: 'status-graduated' },
            { value: 'dropout', label: 'Dropout', class: 'status-dropout' }
        ];

        let optionsHtml = statusOptions.map(opt =>
            `<option value="${opt.value}" ${opt.value === currentValue ? 'selected' : ''}>${opt.label}</option>`
        ).join('');

        const $select = $(`
            <select class="form-select form-select-sm inline-edit-select" style="width: auto; display: inline-block;">
                ${optionsHtml}
            </select>
        `);

        // Replace badge with select
        const originalHtml = $element.parent().html();
        $element.replaceWith($select);
        $select.focus();

        // Handle change
        $select.on('change', () => {
            const newValue = $select.val();
            this.saveField(studentId, 'status', newValue, $select, originalHtml);
        });

        // Handle blur
        $select.on('blur', () => {
            setTimeout(() => {
                if ($select.parent().length) {
                    $select.parent().html(originalHtml);
                }
            }, 200);
        });
    }

    editField(element) {
        const $element = $(element);
        const field = $element.data('field');
        const currentValue = $element.data('value') || '';
        const studentId = $element.closest('td').data('student-id');
        const $valueSpan = $element.find('.field-value');

        // Create input
        const $input = $(`
            <input type="text"
                   class="form-control form-control-sm inline-edit-input"
                   value="${currentValue}"
                   placeholder="Enter ${field.replace('_', ' ')}"
                   style="width: 120px; display: inline-block; padding: 2px 6px; font-size: 0.85rem;">
        `);

        // Store original HTML
        const originalHtml = $element.html();

        // Replace text with input
        $valueSpan.replaceWith($input);
        $input.focus().select();

        // Handle enter key
        $input.on('keypress', (e) => {
            if (e.which === 13) {
                const newValue = $input.val().trim();
                this.saveField(studentId, field, newValue, $element, originalHtml);
            }
        });

        // Handle escape key
        $input.on('keydown', (e) => {
            if (e.which === 27) {
                $element.html(originalHtml);
            }
        });

        // Handle blur
        $input.on('blur', () => {
            setTimeout(() => {
                const newValue = $input.val().trim();
                if (newValue !== currentValue) {
                    this.saveField(studentId, field, newValue, $element, originalHtml);
                } else {
                    $element.html(originalHtml);
                }
            }, 200);
        });
    }

    saveField(studentId, field, value, $element, originalHtml) {
        // Show loading
        if ($element.is('select')) {
            $element.prop('disabled', true);
        } else {
            $element.html('<i class="fas fa-spinner fa-spin text-primary"></i> Saving...');
        }

        $.ajax({
            url: `/admin/students/${studentId}/inline-update`,
            method: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                field: field,
                value: value
            },
            success: (response) => {
                if (response.success) {
                    // Show success feedback
                    this.showFeedback('success', response.message || 'Updated successfully');

                    // Update the display
                    if (field === 'status') {
                        const statusClass = `status-${value}`;
                        const newHtml = `
                            <span class="status-badge-modern ${statusClass} editable-status"
                                  data-field="status"
                                  data-value="${value}"
                                  style="cursor: pointer;"
                                  title="Click to change status">
                                ${response.display_value || value.charAt(0).toUpperCase() + value.slice(1)}
                                <i class="fas fa-pencil-alt" style="font-size: 0.6rem; margin-left: 3px;"></i>
                            </span>
                        `;
                        $element.parent().html(newHtml);
                    } else {
                        // For contact fields
                        const icon = field === 'student_mobile' ? 'mobile-alt' : 'phone';
                        const iconClass = field === 'student_mobile' ? 'text-primary' : 'text-secondary';
                        const newHtml = `
                            <i class="fas fa-${icon} ${iconClass}"></i>
                            <span class="field-value">${value}</span>
                            <i class="fas fa-pencil-alt text-muted edit-icon" style="font-size: 0.7rem; cursor: pointer;"></i>
                        `;
                        $element.html(newHtml);
                        $element.data('value', value);
                    }
                } else {
                    this.showFeedback('error', response.message || 'Update failed');
                    $element.html(originalHtml);
                }
            },
            error: (xhr) => {
                let errorMessage = 'Update failed';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                this.showFeedback('error', errorMessage);
                $element.html(originalHtml);
            }
        });
    }

    showFeedback(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';

        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                 style="top: 80px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas fa-${icon} me-2"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);

        $('body').append($alert);

        setTimeout(() => {
            $alert.fadeOut(() => $alert.remove());
        }, 3000);
    }
}

// Initialize inline editor
document.addEventListener('DOMContentLoaded', function() {
    window.inlineEditor = new InlineEditor();
});

</script>
@endpush