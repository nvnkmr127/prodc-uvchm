@extends('layouts.theme')

@section('title', 'Fee Category Analysis')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Fee Category Analysis</h1>
        <div class="btn-group">
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm dropdown-toggle" data-toggle="dropdown">
                <i class="fas fa-download fa-sm text-white-50"></i> Export Reports
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                <a class="dropdown-item" href="{{ route('admin.fee-category-analysis.export', 'overview') }}?{{ http_build_query($filters) }}">
                    <i class="fas fa-chart-pie fa-sm fa-fw mr-2 text-gray-400"></i>Overview Report
                </a>
                <a class="dropdown-item" href="{{ route('admin.fee-category-analysis.export', 'detailed') }}?{{ http_build_query($filters) }}">
                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>Detailed Report
                </a>
                <a class="dropdown-item" href="{{ route('admin.fee-category-analysis.export', 'pending') }}?{{ http_build_query($filters) }}">
                    <i class="fas fa-exclamation-triangle fa-sm fa-fw mr-2 text-gray-400"></i>Pending Report
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Statistics Row -->
    <div class="row">
        <!-- Total Categories Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ isset($summaryStats['total_categories']) ? $summaryStats['total_categories'] : 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Billed Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Billed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format(isset($summaryStats['total_bills_amount']) ? $summaryStats['total_bills_amount'] : 0, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Collected Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Collected</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format(isset($summaryStats['total_collected']) ? $summaryStats['total_collected'] : 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pending Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($summaryStats['total_pending']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics Row -->
    <div class="row">
        <!-- Collection Efficiency Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Collection Efficiency</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $summaryStats['collection_efficiency'] }}%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ isset($summaryStats['collection_efficiency']) ? $summaryStats['collection_efficiency'] : 0 }}%"></div>
                                                </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students with Pending Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Students with Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ isset($summaryStats['pending_students']) ? $summaryStats['pending_students'] : 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Overdue Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Overdue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format(isset($summaryStats['total_overdue']) ? $summaryStats['total_overdue'] : 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty Card for Layout -->
        <div class="col-xl-3 col-md-6 mb-4">
            <!-- Placeholder for balance -->
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter mr-2"></i>Filters & Actions
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.fee-category-analysis.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small font-weight-bold text-gray-600">Fee Category</label>
                            <select name="fee_category_id" class="form-control">
                                <option value="">All Categories</option>
                                @foreach($feeCategories as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ isset($filters['fee_category_id']) && $filters['fee_category_id'] == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small font-weight-bold text-gray-600">Course</label>
                            <select name="course_id" class="form-control">
                                <option value="">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" 
                                        {{ isset($filters['course_id']) && $filters['course_id'] == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small font-weight-bold text-gray-600">Start Date</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="{{ isset($filters['start_date']) ? $filters['start_date'] : '' }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small font-weight-bold text-gray-600">End Date</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="{{ isset($filters['end_date']) ? $filters['end_date'] : '' }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-1"></i>Apply Filters
                        </button>
                        <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-undo mr-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Analysis Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table mr-2"></i>Fee Category Analysis
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="categoryAnalysisTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                           
                            <th>Students</th>
                            <th>Total Billed</th>
                            <th>Collected</th>
                            <th>Pending</th>
                            <th>Collection Rate</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoryAnalysis as $category)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            @if($category->is_mandatory)
                                                <span class="badge badge-danger">Mandatory</span>
                                            @else
                                                <span class="badge badge-info">Optional</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ $category->name }}</div>
                                            @if($category->category_code)
                                                <small class="text-muted">{{ $category->category_code }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                       
                                <td>
                                    <div class="text-xs text-gray-600">Total: <span class="font-weight-bold">{{ number_format(isset($category->total_students) ? $category->total_students : 0) }}</span></div>
                                    <div class="text-xs text-success">Paid: {{ number_format(isset($category->paid_students) ? $category->paid_students : 0) }}</div>
                                    <div class="text-xs text-warning">Pending: {{ number_format(isset($category->pending_students) ? $category->pending_students : 0) }}</div>
                                </td>
                                <td>
                                    <div class="font-weight-bold">₹{{ number_format(isset($category->total_billed) ? $category->total_billed : 0) }}</div>
                                    @if(isset($category->total_concessions) && $category->total_concessions > 0)
                                        <small class="text-info">Concessions: ₹{{ number_format($category->total_concessions) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-success font-weight-bold">₹{{ number_format(isset($category->total_collected) ? $category->total_collected : 0) }}</span>
                                </td>
                                <td>
                                    <div class="text-danger font-weight-bold">₹{{ number_format(isset($category->total_pending) ? $category->total_pending : 0) }}</div>
                                    @if(isset($category->total_overdue) && $category->total_overdue > 0)
                                        <small class="text-danger">Overdue: ₹{{ number_format($category->total_overdue) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress mr-2" style="width: 70px; height: 20px;">
                                            <div class="progress-bar 
                                                @if(isset($category->collection_rate) && $category->collection_rate >= 80) bg-success 
                                                @elseif(isset($category->collection_rate) && $category->collection_rate >= 60) bg-warning 
                                                @else bg-danger @endif" 
                                                style="width: {{ isset($category->collection_rate) ? $category->collection_rate : 0 }}%"></div>
                                        </div>
                                        <span class="font-weight-bold text-xs
                                            @if($category->collection_rate >= 80) text-success 
                                            @elseif($category->collection_rate >= 60) text-warning 
                                            @else text-danger @endif">
                                            {{ isset($category->collection_rate) ? $category->collection_rate : 0 }}%
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if(isset($category->overdue_rate) && $category->overdue_rate > 20)
                                        <span class="badge badge-danger">Critical</span>
                                    @elseif(isset($category->overdue_rate) && $category->overdue_rate > 10)
                                        <span class="badge badge-warning">Warning</span>
                                    @else
                                        <span class="badge badge-success">Good</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.fee-category-analysis.show', $category->id) }}" 
                                           class="btn btn-outline-primary btn-sm" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-info btn-sm" 
                                                onclick="showPendingStudents({{ $category->id }})" title="Pending Students">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm" 
                                                onclick="sendReminders({{ $category->id }})" title="Send Reminders">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-gray-600">
                                        <i class="fas fa-search fa-2x mb-2"></i>
                                        <p class="mb-0">No fee categories found with the current filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Performers Section -->
    @if($summaryStats['top_performing_category'] || $summaryStats['most_pending_category'])
    <div class="row">
        @if($summaryStats['top_performing_category'])
        <div class="col-lg-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-trophy mr-2"></i>Top Performing Category
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="text-success font-weight-bold">{{ $summaryStats['top_performing_category']->name }}</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">Collection Rate</div>
                            <div class="h6 mb-0 text-success">{{ round($summaryStats['top_performing_category']->collection_rate, 2) }}%</div>
                        </div>
                        <div class="col-6">
                            <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">Total Collected</div>
                            <div class="h6 mb-0">₹{{ number_format($summaryStats['top_performing_category']->total_collected) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($summaryStats['most_pending_category'])
        <div class="col-lg-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-header py-3 bg-warning text-dark">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-clock mr-2"></i>Most Pending Category
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="text-warning font-weight-bold">{{ $summaryStats['most_pending_category']->name }}</h5>
                    <div class="row">
                        <div class="col-12">
                            <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">Pending Amount</div>
                            <div class="h6 mb-0 text-danger">₹{{ number_format($summaryStats['most_pending_category']->pending_amount) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>

<!-- Pending Students Modal -->
<div class="modal fade" id="pendingStudentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pendingStudentsModalLabel">Pending Students</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="pendingStudentsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x text-gray-400"></i>
                        <p class="mt-2 text-gray-600">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable with error handling
    var categoryTable = $('#categoryAnalysisTable');
    if (categoryTable.length) {
        try {
            categoryTable.DataTable({
                responsive: true,
                pageLength: 25,
                order: [[6, 'asc']], // Sort by collection rate ascending (worst first)
                columnDefs: [
                    { orderable: false, targets: [8] } // Disable sorting for actions column
                ],
                initComplete: function(settings, json) {
                    console.log('Category Analysis Table initialized');
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTable initialization error:', error);
                    alert('Failed to load fee category data. Please refresh the page.');
                }
            });
        } catch (error) {
            console.error('DataTable initialization failed:', error);
        }
    }
});

function showPendingStudents(categoryId) {
    $('#pendingStudentsModal').modal('show');
    
    // Get current filters
    var filters = new URLSearchParams(window.location.search);
    filters.set('fee_category_id', categoryId);
    
    $.ajax({
        url: '/admin/fee-category-analysis/' + categoryId + '/pending-students?' + filters.toString(),
        method: 'GET',
        success: function(response) {
            var html = '<div class="table-responsive">';
            html += '<table class="table table-bordered table-sm">';
            html += '<thead class="thead-light"><tr>';
            html += '<th>Student</th><th>Course</th><th>Pending Amount</th><th>Due Date</th><th>Status</th>';
            html += '</tr></thead><tbody>';
            
            if (response.students && response.students.length > 0) {
                response.students.forEach(function(fee) {
                    var pendingAmount = fee.amount - fee.concession_amount - fee.paid_amount;
                    var dueDateClass = new Date(fee.due_date) < new Date() ? 'text-danger' : 'text-warning';
                    
                    html += '<tr>';
                    html += '<td><strong>' + fee.student.name + '</strong><br><small class="text-muted">' + fee.student.enrollment_number + '</small></td>';
                    html += '<td>' + (fee.student.batch && fee.student.batch.course ? fee.student.batch.course.name : 'N/A') + '</td>';
                    html += '<td class="text-danger font-weight-bold">₹' + pendingAmount.toLocaleString() + '</td>';
                    html += '<td class="' + dueDateClass + '">' + new Date(fee.due_date).toLocaleDateString() + '</td>';
                    html += '<td><span class="badge badge-' + (fee.status === 'unpaid' ? 'danger' : 'warning') + '">' + fee.status + '</span></td>';
                    html += '</tr>';
                });
            } else {
                html += '<tr><td colspan="5" class="text-center text-gray-600">No pending students found</td></tr>';
            }
            
            html += '</tbody></table></div>';
            
            if (response.pagination && response.pagination.total > response.pagination.per_page) {
                html += '<div class="d-flex justify-content-between align-items-center mt-3">';
                html += '<small class="text-muted">Showing ' + response.pagination.per_page + ' of ' + response.pagination.total + ' students</small>';
                html += '<small class="text-info">Use detailed report for complete list</small>';
                html += '</div>';
            }
            
            $('#pendingStudentsContent').html(html);
        },
        error: function() {
            $('#pendingStudentsContent').html('<div class="alert alert-danger">Error loading pending students</div>');
        }
    });
}

function sendReminders(categoryId) {
    Swal.fire({
        title: 'Send Payment Reminders?',
        text: 'This will send reminders to all students with pending payments in this category.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4e73df',
        cancelButtonColor: '#858796',
        confirmButtonText: 'Yes, Send Reminders'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/payment-reminders/send-category-reminders/' + categoryId,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire('Success!', 'Reminders have been queued for sending.', 'success');
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to send reminders. Please try again.', 'error');
                }
            });
        }
    });
}

// Auto-refresh every 5 minutes
var refreshInterval = setInterval(function() {
    if (document.hidden) return; // Don't refresh if tab is not visible
    
    // Only refresh if no modals are open
    if (!$('.modal.show').length) {
        location.reload();
    }
}, 300000); // 5 minutes

// Clear interval on page unload
$(window).on('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
@endpush

@push('styles')
<style>
.progress-sm {
    height: 0.5rem;
}

.table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-top: none;
}

.badge {
    font-size: 0.7rem;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.text-xs {
    font-size: 0.7rem;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
}
</style>
@endpush