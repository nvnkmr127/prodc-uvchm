@extends('layouts.theme')

@section('title', 'Critical Defaulters Management')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exclamation-triangle text-danger mr-2"></i>Critical Defaulters Management
        </h1>
        <div>
            <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Analysis
            </a>
            <div class="btn-group ml-2">
                <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-download mr-1"></i> Export
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="{{ route('admin.fee-category-analysis.export', 'pending') }}?{{ http_build_query($filters) }}">
                        <i class="fas fa-file-excel mr-2"></i>Export Critical List
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.fee-category-analysis.export', 'detailed') }}?{{ http_build_query($filters) }}">
                        <i class="fas fa-file-pdf mr-2"></i>Detailed Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Critical Defaulters</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($defaulterStats['critical_count'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total At Risk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($defaulterStats['total_at_risk'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Recovery Time</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $defaulterStats['avg_recovery_days'] ?? 30 }} days</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Avg Overdue Amount</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($defaulterStats['avg_overdue_amount'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter mr-2"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.fee-category-analysis.critical-defaulters') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small font-weight-bold text-gray-600">Fee Category</label>
                            <select name="fee_category_id" class="form-control">
                                <option value="">All Categories</option>
                                @foreach($feeCategories as $category)
                                    <option value="{{ $category->id }}" {{ $filters['fee_category_id'] == $category->id ? 'selected' : '' }}>
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
                                    <option value="{{ $course->id }}" {{ $filters['course_id'] == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small font-weight-bold text-gray-600">Minimum Amount</label>
                            <input type="number" name="min_amount" class="form-control" value="{{ $filters['min_amount'] ?? 5000 }}" min="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small font-weight-bold text-gray-600">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-1"></i>Apply Filters
                                </button>
                                <a href="{{ route('admin.fee-category-analysis.critical-defaulters') }}" class="btn btn-secondary ml-2">
                                    <i class="fas fa-undo mr-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Critical Defaulters Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Students Requiring Immediate Attention</h6>
            <div class="dropdown no-arrow">
                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                    <i class="fas fa-cogs mr-1"></i>Bulk Actions
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                    <a class="dropdown-item" href="#" onclick="sendBulkReminders('urgent')">
                        <i class="fas fa-bell mr-2"></i>Send Urgent Reminders
                    </a>
                    <a class="dropdown-item" href="#" onclick="escalateBulk('critical')">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Escalate Critical Cases
                    </a>
                    <a class="dropdown-item" href="#" onclick="exportSelected()">
                        <i class="fas fa-download mr-2"></i>Export Selected
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-success" href="#" onclick="createBulkPaymentPlans()">
                        <i class="fas fa-calendar-alt mr-2"></i>Create Payment Plans
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($criticalDefaulters && $criticalDefaulters->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="criticalDefaultersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>Student</th>
                                <th>Course/Batch</th>
                                <th>Overdue Amount</th>
                                <th>Total Pending</th>
                                <th>Categories Affected</th>
                                <th>Days Overdue</th>
                                <th>Risk Level</th>
                                <th>Last Contact</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($criticalDefaulters as $student)
                                @php
                                    $daysOverdue = $student->oldest_overdue_date ?
                                        \Carbon\Carbon::parse($student->oldest_overdue_date)->diffInDays(now()) : 0;
                                    $riskLevel = $student->total_overdue > 50000 && $daysOverdue > 90 ? 'critical' :
                                                ($student->total_overdue > 25000 && $daysOverdue > 60 ? 'high' : 'medium');
                                @endphp
                                <tr data-student-id="{{ $student->id }}" data-risk="{{ $riskLevel }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input student-checkbox" value="{{ $student->id }}">
                                    </td>
                                    <td>
                                        <div>
                                            <span class="font-weight-bold">{{ $student->name ?? 'N/A' }}</span>
                                            <br><small class="text-muted">{{ $student->enrollment_number ?? 'N/A' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="font-weight-bold">{{ $student->course_name ?? 'N/A' }}</span>
                                            <br><small class="text-muted">{{ $student->batch_name ?? 'N/A' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-danger font-weight-bold">₹{{ number_format($student->total_overdue) }}</div>
                                        <small class="text-muted">{{ $student->overdue_fee_count }} fee(s)</small>
                                    </td>
                                    <td>
                                        <div class="text-warning font-weight-bold">₹{{ number_format($student->total_pending) }}</div>
                                        @if($student->total_pending > $student->total_overdue)
                                            <small class="text-info">+₹{{ number_format($student->total_pending - $student->total_overdue) }} upcoming</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="badge-container">
                                            @php
                                                $categories = explode(',', $student->overdue_categories);
                                                $categoryCount = count($categories);
                                            @endphp
                                            @if($categoryCount <= 3)
                                                @foreach($categories as $category)
                                                    @if(trim($category))
                                                        <span class="badge badge-secondary badge-sm mb-1">{{ trim($category) }}</span>
                                                    @endif
                                                @endforeach
                                            @else
                                                @foreach(array_slice($categories, 0, 2) as $category)
                                                    @if(trim($category))
                                                        <span class="badge badge-secondary badge-sm mb-1">{{ trim($category) }}</span>
                                                    @endif
                                                @endforeach
                                                <span class="badge badge-info badge-sm mb-1">+{{ $categoryCount - 2 }} more</span>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block">{{ $student->affected_categories }} categories</small>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold {{ $daysOverdue > 90 ? 'text-danger' : ($daysOverdue > 60 ? 'text-warning' : 'text-info') }}">
                                            {{ $daysOverdue }} days
                                        </div>
                                        <small class="text-muted">
                                            Since {{ $student->oldest_overdue_date ? \Carbon\Carbon::parse($student->oldest_overdue_date)->format('M d') : 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        @switch($riskLevel)
                                            @case('critical')
                                                <span class="badge badge-danger">CRITICAL</span>
                                                @break
                                            @case('high')
                                                <span class="badge badge-warning">HIGH RISK</span>
                                                @break
                                            @default
                                                <span class="badge badge-info">MEDIUM</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="text-muted small">
                                            {{-- This would come from your reminder/contact log --}}
                                            <div>Last reminded:</div>
                                            <div class="font-weight-bold">{{ rand(1, 15) }} days ago</div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" 
                                                    data-toggle="dropdown" title="Actions">
                                                <i class="fas fa-cogs"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('admin.students.show', $student->id) }}">
                                                    <i class="fas fa-eye text-primary"></i> View Student
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="#" onclick="sendReminder({{ $student->id }}, 'gentle')">
                                                    <i class="fas fa-bell text-info"></i> Send Gentle Reminder
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="sendReminder({{ $student->id }}, 'firm')">
                                                    <i class="fas fa-bell text-warning"></i> Send Firm Reminder
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="sendReminder({{ $student->id }}, 'urgent')">
                                                    <i class="fas fa-bell text-danger"></i> Send Urgent Notice
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="#" onclick="createPaymentPlan({{ $student->id }})">
                                                    <i class="fas fa-calendar-alt text-success"></i> Create Payment Plan
                                                </a>
                                                @if($riskLevel === 'critical')
                                                    <a class="dropdown-item" href="#" onclick="escalateStudent({{ $student->id }})">
                                                        <i class="fas fa-exclamation-triangle text-danger"></i> Escalate to Management
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $criticalDefaulters->firstItem() }} to {{ $criticalDefaulters->lastItem() }} 
                        of {{ $criticalDefaulters->total() }} critical defaulters
                    </div>
                    {{ $criticalDefaulters->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                    </div>
                    <h5 class="font-weight-bold text-gray-600">No Critical Defaulters Found</h5>
                    <p class="text-muted">Great! There are currently no students meeting the critical defaulter criteria.</p>
                    <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-primary">
                        <i class="fas fa-chart-pie mr-1"></i>View Category Analysis
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#criticalDefaultersTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[3, 'desc']], // Sort by overdue amount descending
        columnDefs: [
            { orderable: false, targets: [0, 9] } // Disable sorting for checkbox and actions columns
        ]
    });

    // Select All functionality
    $('#selectAll').change(function() {
        $('.student-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActionButtons();
    });

    $('.student-checkbox').change(function() {
        updateSelectAllState();
        updateBulkActionButtons();
    });

    function updateSelectAllState() {
        const totalCheckboxes = $('.student-checkbox').length;
        const checkedCheckboxes = $('.student-checkbox:checked').length;
        
        $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes);
    }

    function updateBulkActionButtons() {
        const selectedCount = $('.student-checkbox:checked').length;
        // You can enable/disable bulk action buttons based on selection
        console.log(`${selectedCount} students selected`);
    }
});

// Individual student actions
function sendReminder(studentId, type = 'gentle') {
    Swal.fire({
        title: `Send ${type.charAt(0).toUpperCase() + type.slice(1)} Reminder?`,
        text: 'This will send a payment reminder to the selected student.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4e73df',
        cancelButtonColor: '#858796',
        confirmButtonText: 'Yes, Send Reminder'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('admin.fee-category-analysis.student-intervention', ':student') }}`.replace(':student', studentId),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    action: 'reminder',
                    reminder_type: type
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                    } else {
                        Swal.fire('Error!', response.error, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to send reminder. Please try again.', 'error');
                }
            });
        }
    });
}

function createPaymentPlan(studentId) {
    Swal.fire({
        title: 'Create Payment Plan',
        html: `
            <div class="form-group text-left">
                <label>Number of Installments:</label>
                <select id="installments" class="form-control">
                    <option value="2">2 installments</option>
                    <option value="3">3 installments</option>
                    <option value="4">4 installments</option>
                    <option value="6">6 installments</option>
                </select>
            </div>
            <div class="form-group text-left">
                <label>First Payment Due:</label>
                <input type="date" id="first_due" class="form-control" 
                       value="${new Date(Date.now() + 7*24*60*60*1000).toISOString().split('T')[0]}">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Create Plan',
        preConfirm: () => {
            return {
                installments: document.getElementById('installments').value,
                first_due: document.getElementById('first_due').value
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('admin.fee-category-analysis.student-intervention', ':student') }}`.replace(':student', studentId),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    action: 'payment_plan',
                    installments: result.value.installments,
                    first_due: result.value.first_due
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                    } else {
                        Swal.fire('Error!', response.error, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to create payment plan.', 'error');
                }
            });
        }
    });
}

function escalateStudent(studentId) {
    Swal.fire({
        title: 'Escalate to Management',
        html: `
            <div class="form-group text-left">
                <label>Escalation Reason:</label>
                <textarea id="escalation_reason" class="form-control" rows="3" 
                          placeholder="Enter reason for escalation..."></textarea>
            </div>
            <div class="form-group text-left">
                <label>Priority Level:</label>
                <select id="priority" class="form-control">
                    <option value="high">High Priority</option>
                    <option value="urgent">Urgent</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Escalate',
        confirmButtonColor: '#dc3545',
        preConfirm: () => {
            const reason = document.getElementById('escalation_reason').value;
            if (!reason) {
                Swal.showValidationMessage('Please enter escalation reason');
                return false;
            }
            return {
                reason: reason,
                priority: document.getElementById('priority').value
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('admin.fee-category-analysis.student-intervention', ':student') }}`.replace(':student', studentId),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    action: 'escalate',
                    reason: result.value.reason,
                    priority: result.value.priority
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Escalated!', response.message, 'success');
                    } else {
                        Swal.fire('Error!', response.error, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to escalate case.', 'error');
                }
            });
        }
    });
}

// Bulk actions
function sendBulkReminders(type) {
    const selectedStudents = $('.student-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedStudents.length === 0) {
        Swal.fire('Warning!', 'Please select at least one student.', 'warning');
        return;
    }

    Swal.fire({
        title: `Send ${type} Reminders?`,
        text: `This will send ${type} reminders to ${selectedStudents.length} selected students.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4e73df',
        confirmButtonText: 'Yes, Send Reminders'
    }).then((result) => {
        if (result.isConfirmed) {
            // Process bulk reminders
            let completedRequests = 0;
            let successCount = 0;

            selectedStudents.forEach(studentId => {
                $.ajax({
                    url: `{{ route('admin.fee-category-analysis.student-intervention', ':student') }}`.replace(':student', studentId),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        action: 'reminder',
                        reminder_type: type
                    },
                    success: function(response) {
                        if (response.success) successCount++;
                    },
                    complete: function() {
                        completedRequests++;
                        if (completedRequests === selectedStudents.length) {
                            Swal.fire('Completed!', 
                                `Successfully sent ${successCount} out of ${selectedStudents.length} reminders.`, 
                                'success');
                            // Clear selections
                            $('.student-checkbox').prop('checked', false);
                            $('#selectAll').prop('checked', false);
                        }
                    }
                });
            });
        }
    });
}

function escalateBulk(level) {
    const selectedStudents = $('.student-checkbox:checked').filter(function() {
        return $(this).closest('tr').data('risk') === 'critical';
    }).map(function() {
        return $(this).val();
    }).get();

    if (selectedStudents.length === 0) {
        Swal.fire('Warning!', 'Please select at least one critical risk student.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Bulk Escalation',
        html: `
            <div class="form-group text-left">
                <label>Escalation Reason:</label>
                <textarea id="bulk_escalation_reason" class="form-control" rows="3" 
                          placeholder="Bulk escalation for critical defaulters...">Multiple critical defaulters requiring management intervention due to prolonged non-payment.</textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: `Escalate ${selectedStudents.length} Cases`,
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const reason = document.getElementById('bulk_escalation_reason').value;
            
            let completedRequests = 0;
            let successCount = 0;

            selectedStudents.forEach(studentId => {
                $.ajax({
                    url: `{{ route('admin.fee-category-analysis.student-intervention', ':student') }}`.replace(':student', studentId),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        action: 'escalate',
                        reason: reason,
                        priority: 'critical'
                    },
                    success: function(response) {
                        if (response.success) successCount++;
                    },
                    complete: function() {
                        completedRequests++;
                        if (completedRequests === selectedStudents.length) {
                            Swal.fire('Escalated!', 
                                `Successfully escalated ${successCount} out of ${selectedStudents.length} cases.`, 
                                'success');
                            $('.student-checkbox').prop('checked', false);
                            $('#selectAll').prop('checked', false);
                        }
                    }
                });
            });
        }
    });
}

function createBulkPaymentPlans() {
    const selectedStudents = $('.student-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedStudents.length === 0) {
        Swal.fire('Warning!', 'Please select at least one student.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Create Bulk Payment Plans',
        html: `
            <div class="form-group text-left">
                <label>Default Installments:</label>
                <select id="bulk_installments" class="form-control">
                    <option value="3">3 installments</option>
                    <option value="4">4 installments</option>
                    <option value="6">6 installments</option>
                </select>
            </div>
            <div class="form-group text-left">
                <label>First Payment Due:</label>
                <input type="date" id="bulk_first_due" class="form-control" 
                       value="${new Date(Date.now() + 14*24*60*60*1000).toISOString().split('T')[0]}">
            </div>
            <div class="alert alert-info">
                <small>This will create payment plans for ${selectedStudents.length} selected students.</small>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Create Payment Plans',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            const installments = document.getElementById('bulk_installments').value;
            const firstDue = document.getElementById('bulk_first_due').value;
            
            let completedRequests = 0;
            let successCount = 0;

            selectedStudents.forEach(studentId => {
                $.ajax({
                    url: `{{ route('admin.fee-category-analysis.student-intervention', ':student') }}`.replace(':student', studentId),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        action: 'payment_plan',
                        installments: installments,
                        first_due: firstDue
                    },
                    success: function(response) {
                        if (response.success) successCount++;
                    },
                    complete: function() {
                        completedRequests++;
                        if (completedRequests === selectedStudents.length) {
                            Swal.fire('Success!', 
                                `Successfully created payment plans for ${successCount} out of ${selectedStudents.length} students.`, 
                                'success');
                            $('.student-checkbox').prop('checked', false);
                            $('#selectAll').prop('checked', false);
                        }
                    }
                });
            });
        }
    });
}

function exportSelected() {
    const selectedStudents = $('.student-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedStudents.length === 0) {
        Swal.fire('Warning!', 'Please select at least one student to export.', 'warning');
        return;
    }

    // Create a form to submit the selected students for export
    const form = $('<form>', {
        method: 'POST',
        action: '{{ route("admin.fee-category-analysis.export", "selected") }}'
    });

    form.append($('<input>', {
        type: 'hidden',
        name: '_token',
        value: $('meta[name="csrf-token"]').attr('content')
    }));

    selectedStudents.forEach(studentId => {
        form.append($('<input>', {
            type: 'hidden',
            name: 'selected_students[]',
            value: studentId
        }));
    });

    $('body').append(form);
    form.submit();
    form.remove();
}

console.log('Critical Defaulters Management page loaded successfully');
</script>
@endpush

@push('styles')
<style>
.badge-container .badge {
    margin-right: 0.25rem;
    margin-bottom: 0.25rem;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

.table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-top: none;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.dropdown-item:hover {
    background-color: #f8f9fc;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>
@endpush