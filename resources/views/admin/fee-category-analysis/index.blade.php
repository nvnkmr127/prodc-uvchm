@extends('layouts.theme')

@section('title', 'Fee Category Analysis')

@push('styles')
    <style>
        /* Gradient Cards */
        .bg-gradient-primary-soft {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
        }

        .bg-gradient-success-soft {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: white;
        }

        .bg-gradient-danger-soft {
            background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
            color: white;
        }

        .bg-gradient-info-soft {
            background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
            color: white;
        }

        .card-stat-icon {
            opacity: 0.3;
            font-size: 3rem;
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Table & Badges */
        .table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: #f8f9fc;
            border-top: none;
        }

        .badge-pill-soft {
            padding: 0.4em 0.8em;
            border-radius: 10rem;
            font-weight: 600;
        }

        .badge-soft-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-soft-warning {
            background-color: #fff3cd;
            color: #664d03;
        }

        .badge-soft-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        .badge-soft-info {
            background-color: #cff4fc;
            color: #055160;
        }

        /* Progress Bars */
        .progress-thick {
            height: 0.75rem;
            border-radius: 1rem;
            background-color: #e9ecef;
        }

        /* Hover Effects */
        .hover-lift {
            transition: transform 0.2s;
        }

        .hover-lift:hover {
            transform: translateY(-3px);
        }

        /* Custom Button Styles */
        .btn-white {
            background-color: #fff;
            border-color: #e3e6f0;
        }

        .hover-primary:hover {
            background-color: #4e73df;
            color: #fff !important;
            border-color: #4e73df;
        }

        .hover-warning:hover {
            background-color: #f6c23e;
            color: #fff !important;
            border-color: #f6c23e;
        }

        .hover-success:hover {
            background-color: #1cc88a;
            color: #fff !important;
            border-color: #1cc88a;
        }
        .hover-primary-link:hover {
            color: #4e73df !important;
            text-decoration: underline !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Header --}}
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Fee Analysis</h1>
                <p class="mb-0 text-muted">Comprehensive breakdown of fee collections and dues.</p>
            </div>

            <div class="btn-group shadow-sm">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-download fa-sm text-white-50 mr-2"></i> Export Reports
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                    <a class="dropdown-item"
                        href="{{ route('admin.fee-category-analysis.export', 'overview') }}?{{ http_build_query($filters) }}">
                        <i class="fas fa-chart-pie fa-fw mr-2 text-gray-400"></i>Overview Report
                    </a>
                    <a class="dropdown-item"
                        href="{{ route('admin.fee-category-analysis.export', 'detailed') }}?{{ http_build_query($filters) }}">
                        <i class="fas fa-list fa-fw mr-2 text-gray-400"></i>Detailed Report
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item"
                        href="{{ route('admin.fee-category-analysis.export', 'pending') }}?{{ http_build_query($filters) }}">
                        <i class="fas fa-exclamation-triangle fa-fw mr-2 text-danger"></i>Pending Dues Report
                    </a>
                </div>
            </div>
        </div>

        {{-- Stats Cards Overview --}}
        <div class="row">
            {{-- Total Billed --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 py-2 bg-white hover-lift border-left-primary">
                    <div class="card-body position-relative">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Billed</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800" id="stats-total-billed">
                                    ₹{{ number_format(isset($summaryStats['total_billed']) ? $summaryStats['total_billed'] : 0) }}
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    <span class="text-success mr-1" id="stats-total-count"><i class="fas fa-arrow-up"></i>
                                        {{ number_format($summaryStats['total_fees']) }}</span> records
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-invoice-dollar card-stat-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Collected --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 py-2 bg-white hover-lift border-left-success">
                    <div class="card-body position-relative">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Collected</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800" id="stats-total-collected">
                                    ₹{{ number_format(isset($summaryStats['total_collected']) ? $summaryStats['total_collected'] : 0) }}
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    Efficiency: <span
                                        class="font-weight-bold text-success" id="stats-efficiency">{{ $summaryStats['collection_efficiency'] }}%</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-hand-holding-usd card-stat-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Pending --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 py-2 bg-white hover-lift border-left-warning">
                    <div class="card-body position-relative">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Dues</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800" id="stats-total-pending">
                                    ₹{{ number_format($summaryStats['total_pending']) }}</div>
                                <div class="mt-2 text-xs text-muted">
                                    <span
                                        class="text-warning font-weight-bold" id="stats-students-pending">{{ $summaryStats['students_with_pending'] }}</span>
                                    students
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-hourglass-half card-stat-icon text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Overdue --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 py-2 bg-white hover-lift border-left-danger">
                    <div class="card-body position-relative">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Critical Overdue</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800" id="stats-total-overdue">
                                    ₹{{ number_format(isset($summaryStats['total_overdue']) ? $summaryStats['total_overdue'] : 0) }}
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    Needs immediate attention
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-circle card-stat-icon text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body bg-light rounded">
                <form method="GET" action="{{ route('admin.fee-category-analysis.index') }}" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="small font-weight-bold text-uppercase text-gray-600">Category</label>
                            <select name="fee_category_id" class="form-control custom-select shadow-sm border-0">
                                <option value="">All Categories</option>
                                @foreach($feeCategories as $category)
                                    <option value="{{ $category->id }}" {{ isset($filters['fee_category_id']) && $filters['fee_category_id'] == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="small font-weight-bold text-uppercase text-gray-600">Course / Batch</label>
                            <select name="filter_entity" class="form-control custom-select shadow-sm border-0">
                                <option value="">All Courses & Batches</option>
                                @foreach($courses as $course)
                                    <option value="course_{{ $course->id }}" {{ (isset($filters['filter_entity']) && $filters['filter_entity'] == 'course_' . $course->id) || (!isset($filters['filter_entity']) && isset($filters['course_id']) && $filters['course_id'] == $course->id && !isset($filters['batch_id'])) ? 'selected' : '' }} class="font-weight-bold">
                                        {{ $course->name }} (All Batches)
                                    </option>
                                    @if($course->batches)
                                        @foreach($course->batches as $batch)
                                            <option value="batch_{{ $batch->id }}" {{ (isset($filters['filter_entity']) && $filters['filter_entity'] == 'batch_' . $batch->id) || (!isset($filters['filter_entity']) && isset($filters['batch_id']) && $filters['batch_id'] == $batch->id) ? 'selected' : '' }}>
                                                &nbsp;&nbsp;&nbsp; - {{ $batch->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="small font-weight-bold text-uppercase text-gray-600">Date Range</label>
                            <div class="input-group shadow-sm">
                                <input type="date" name="start_date" class="form-control border-0"
                                    value="{{ isset($filters['start_date']) ? $filters['start_date'] : '' }}">
                                <div class="input-group-prepend input-group-append">
                                    <span class="input-group-text bg-white border-0">to</span>
                                </div>
                                <input type="date" name="end_date" class="form-control border-0"
                                    value="{{ isset($filters['end_date']) ? $filters['end_date'] : '' }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary btn-block shadow-sm mr-2">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-light shadow-sm"
                                    title="Reset">
                                    <i class="fas fa-redo text-gray-600"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Analysis Table --}}
        <div class="card shadow mb-4 border-0">
            <div class="card-header py-3 bg-white border-bottom-0 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Detailed Breakdown</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="categoryAnalysisTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="border-0 pl-4">Category</th>
                                <th class="border-0">Student Stats</th>
                                <th class="border-0">Billing</th>
                                <th class="border-0">Efficiency</th>
                                <th class="border-0">Balance</th>
                                <th class="border-0 text-right pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="analysisTableBody">
                            @include('admin.fee-category-analysis._table_rows', ['categoryAnalysis' => $categoryAnalysis])
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Bottom Highlights (Top/Worst) --}}
        <div id="highlights-container">
            @include('admin.fee-category-analysis._highlights', ['summaryStats' => $summaryStats])
        </div>

    </div>

    {{-- Modal for Pending Students --}}
    <div class="modal fade" id="pendingStudentsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient-danger-soft text-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-user-clock mr-2"></i>Pending Students</h5>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-light mr-3 shadow-sm" id="downloadPendingExcelBtn" onclick="downloadPendingExcel()">
                            <i class="fas fa-file-excel text-success mr-1"></i> Download Excel
                        </button>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body bg-light">
                    <div id="pendingStudentsContent" class="bg-white rounded shadow-sm p-3">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-3 text-muted">Fetching student list...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-white">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            // Initialize DataTable with clean config
            $('#categoryAnalysisTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[3, 'asc']], // Order by Collection Rate
                columnDefs: [
                    { orderable: false, targets: [5] } // Actions column
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Search categories...",
                },
                dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
            });

            $('.dataTables_filter input').addClass('form-control bg-light border-0 small');

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // AJAX Filter
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                let form = $(this);
                let btn = form.find('button[type="submit"]');
                let originalText = btn.html();

                // Loading State
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                $('#analysisTableBody').css('opacity', '0.5');

                $.ajax({
                    url: form.attr('action'),
                    method: 'GET',
                    data: form.serialize(),
                    success: function (response) {
                        // Update Table
                        $('#analysisTableBody').html(response.html).css('opacity', '1');

                        // Update Stats
                        if (response.stats) {
                            $('#stats-total-billed').text('₹' + Number(response.stats.total_billed || 0).toLocaleString());
                            $('#stats-total-count').html('<i class="fas fa-arrow-up"></i> ' + Number(response.stats.total_fees || 0).toLocaleString());
                            
                            $('#stats-total-collected').text('₹' + Number(response.stats.total_collected || 0).toLocaleString());
                            $('#stats-efficiency').text(response.stats.collection_efficiency + '%');
                            
                            $('#stats-total-pending').text('₹' + Number(response.stats.total_pending || 0).toLocaleString());
                            $('#stats-students-pending').text(Number(response.stats.students_with_pending || 0).toLocaleString());
                            
                            $('#stats-total-overdue').text('₹' + Number(response.stats.total_overdue || 0).toLocaleString());
                        }

                        // Update Highlights
                        if (response.highlights_html) {
                            $('#highlights-container').html(response.highlights_html);
                        } else {
                            $('#highlights-container').empty();
                        }

                        // Re-initialize plugins
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to filter data. Please try again.'
                        });
                        $('#analysisTableBody').css('opacity', '1');
                    },
                    complete: function () {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

            // Auto-trigger on change
            $('#filterForm').find('select, input').on('change', function() {
                $('#filterForm').submit();
            });
        });

        var currentPendingCategoryId = null;

        function showPendingStudents(categoryId) {
            currentPendingCategoryId = categoryId;
            $('#pendingStudentsModal').modal('show');

            // Use current form data instead of window.location.search to ensure AJAX changes are captured
            var formData = $('#filterForm').serialize();

            // Clear previous content and show loading
            $('#pendingStudentsContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-gray-300"></i><p class="mt-2 text-muted">Loading pending students...</p></div>');

            $.ajax({
                url: '/admin/fee-category-analysis/' + categoryId + '/pending-students?' + formData,
                method: 'GET',
                success: function (response) {
                    if (!response.students || response.students.length === 0) {
                        $('#pendingStudentsContent').html('<div class="text-center py-4 text-muted">No pending students found.</div>');
                        return;
                    }

                    var html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
                    html += '<thead class="bg-light"><tr><th class="border-0">Student</th><th class="border-0">Contact</th><th class="border-0">Amount</th><th class="border-0">Status</th></tr></thead><tbody>';

                    response.students.forEach(function (fee) {
                        var pending = fee.amount - fee.concession_amount - fee.paid_amount;
                        var dueDate = new Date(fee.due_date);
                        var isOverdue = dueDate < new Date();
                        
                        var studentMobile = fee.student.student_mobile ? '<div><i class="fas fa-phone-alt fa-xs text-muted mr-1"></i> ' + fee.student.student_mobile + ' <span class="text-muted text-xs">(S)</span></div>' : '';
                        var fatherMobile = fee.student.father_mobile ? '<div><i class="fas fa-phone-alt fa-xs text-muted mr-1"></i> ' + fee.student.father_mobile + ' <span class="text-muted text-xs">(F)</span></div>' : '';
                        var contact = studentMobile + fatherMobile || '<span class="text-muted small">No contact</span>';

                        html += '<tr>';
                        html += '<td><div class="font-weight-bold">' + fee.student.name + '</div><div class="small text-muted">' + fee.student.enrollment_number + '</div></td>';
                        html += '<td>' + contact + '</td>';
                        html += '<td class="text-danger font-weight-bold">₹' + pending.toLocaleString() + '</td>';
                        html += '<td><span class="badge badge-pill badge-soft-' + (isOverdue ? 'danger' : 'warning') + '">' + (isOverdue ? 'Overdue' : 'Due Soon') + '</span></td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                    $('#pendingStudentsContent').html(html);
                },
                error: function () {
                    $('#pendingStudentsContent').html('<div class="alert alert-danger">Failed to load data.</div>');
                }
            });
        }

        function downloadPendingExcel() {
            if (!currentPendingCategoryId) return;
            
            var formData = $('#filterForm').serialize();
            var url = '/admin/fee-category-analysis/export/pending_simple?fee_category_id=' + currentPendingCategoryId + '&' + formData;
            
            window.location.href = url;
        }

        function sendReminders(categoryId) {
            Swal.fire({
                title: 'Send Reminders?',
                text: 'Send payment reminders to all pending students in this category?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4e73df',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Yes, Send All'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mock success for UI feedback since actual endpoint might need verification
                    // But generally assuming the controller logic from before exists
                    $.ajax({
                        url: '/admin/payment-reminders/send-category-reminders/' + categoryId,
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function () {
                            Swal.fire('Sent!', 'Reminders have been queued.', 'success');
                        },
                        error: function () {
                            Swal.fire('Error', 'Could not send reminders.', 'error');
                        }
                    });
                }
            });
        }
    </script>
@endpush