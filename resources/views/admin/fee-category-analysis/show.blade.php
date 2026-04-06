@extends('layouts.theme')

@section('title', 'Analysis: ' . $feeCategory->name)

@section('content')
<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Analysis: {{ $feeCategory->name }}</h1>
        <div class="btn-group">
            <a href="{{ route('admin.fee-category-analysis.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
            </a>
            <div class="dropdown ml-2">
                <button class="btn btn-primary btn-sm dropdown-toggle shadow-sm" type="button" data-toggle="dropdown">
                    <i class="fas fa-download fa-sm text-white-50"></i> Export
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    <a class="dropdown-item" href="#" onclick="exportFilteredList()">
                        <i class="fas fa-file-excel mr-2"></i>Export List (Filtered)
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Pre-calculate percentages for initial load to avoid division by zero --}}
    @php
        $totalBase = $stats['total'] > 0 ? $stats['total'] : 1;
        $paidPercent = ($stats['paid'] / $totalBase) * 100;
        $pendingPercent = ($stats['pending'] / $totalBase) * 100;
        $concessionPercent = ($stats['concession'] / $totalBase) * 100;
    @endphp

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Expected</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="statTotal">₹{{ number_format($stats['total'], 2) }}</div>
                    <small class="text-muted"><span id="statCount">{{ $stats['count'] }}</span> Students</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Collected</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statPaid">₹{{ number_format($stats['paid'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <span id="statPaidPercent">{{ number_format($paidPercent, 1) }}</span>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statPending">₹{{ number_format($stats['pending'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <span id="statPendingPercent">{{ number_format($pendingPercent, 1) }}</span>%
                            </div>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Concession</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statConcession">₹{{ number_format($stats['concession'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <span id="statConcessionPercent">{{ number_format($concessionPercent, 1) }}</span>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow border-0">
        <div class="card-body bg-light rounded">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small font-weight-bold">Search</label>
                    <input type="text" id="searchInput" name="search" class="form-control shadow-sm" placeholder="Name / ID / Enrollment...">
                </div>

                <div class="col-md-2">
                    <label class="form-label small font-weight-bold">Academic Year</label>
                    <select name="academic_year_filter" class="form-control shadow-sm">
                        <option value="">All Years</option>
                        @php
                            $academicYears = \App\Models\AcademicYear::orderBy('name', 'desc')->get();
                            $selectedYearId = request('academic_year_filter', session('selected_academic_year_id'));
                        @endphp
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small font-weight-bold">Course</label>
                    <select id="courseFilter" name="course_id" class="form-control shadow-sm">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small font-weight-bold">Batch</label>
                    <select id="batchFilter" name="batch_id" class="form-control shadow-sm">
                        <option value="">All Batches</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }} data-course="{{ $batch->course_id }}">
                                {{ $batch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small font-weight-bold">Payment Status</label>
                    <select id="statusFilter" name="status" class="form-control shadow-sm">
                        <option value="">All Statuses</option>
                        <option value="paid">Fully Paid</option>
                        <option value="partial">Partially Paid</option>
                        <option value="unpaid">Unpaid</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Student</th>
                            <th>Course & Batch</th>
                            <th>Total</th>
                            <th>Concession</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        @include('admin.fee-category-analysis._student_table_rows')
                    </tbody>
                </table>
            </div>
            <div id="paginationLinks" class="mt-3">
                {{ $studentFees->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// --- GLOBAL FUNCTIONS (Available to onclick attributes) ---

/**
 * Export the list with current filters applied
 */
function exportFilteredList() {
    // 1. Get current filter values from the form
    const formData = $('#filterForm').serialize();
    
    // 2. Construct the Export URL
    const baseUrl = "{{ route('admin.fee-category-analysis.export', 'detailed') }}";
    const feeCategoryId = "{{ $feeCategory->id }}";
    
    // 3. Redirect to download
    window.location.href = `${baseUrl}?fee_category_id=${feeCategoryId}&${formData}`;
}

/**
 * Generate PDF Report Modal
 */
function generateReport() {
    Swal.fire({
        title: 'Generate PDF Report',
        html: `
            <div class="text-left">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="includeCharts" checked>
                    <label class="form-check-label" for="includeCharts">Include Performance Charts</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="includeTrends" checked>
                    <label class="form-check-label" for="includeTrends">Include Payment Trends</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="includeStudentList" checked>
                    <label class="form-check-label" for="includeStudentList">Include Student List</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Generate Report',
        confirmButtonColor: '#4e73df',
        cancelButtonColor: '#858796',
        preConfirm: () => {
            return {
                includeCharts: document.getElementById('includeCharts').checked,
                includeTrends: document.getElementById('includeTrends').checked,
                includeStudentList: document.getElementById('includeStudentList').checked
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const options = result.value;
            const params = new URLSearchParams({
                fee_category_id: {{ $feeCategory->id }},
                include_charts: options.includeCharts,
                include_trends: options.includeTrends,
                include_student_list: options.includeStudentList
            });
            
            window.open(`{{ route('admin.fee-category-analysis.export', 'detailed') }}?${params.toString()}`, '_blank');
        }
    });
}

// --- DOCUMENT READY LOGIC (Runs when page loads) ---
$(document).ready(function() {
    
    // 1. Batch Filtering by Course
    $('#courseFilter').on('change', function() {
        const courseId = $(this).val();
        if(!courseId) {
            $('#batchFilter option').show();
        } else {
            $('#batchFilter option').each(function() {
                const optCourseId = $(this).data('course');
                if(!optCourseId || optCourseId == courseId) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            // Reset batch selection if now hidden
            if($('#batchFilter option:selected').css('display') === 'none') {
                $('#batchFilter').val('');
            }
        }
    });

    // 2. AJAX Search & Filter Logic
    let timer;

    // Listen for changes on inputs and selects
    $('#filterForm input, #filterForm select').on('change keyup', function() {
        clearTimeout(timer);
        timer = setTimeout(() => fetchResults(), 400); // Reset to page 1 on filter change
    });

    // Handle Pagination clicks via AJAX
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        const url = new URL($(this).attr('href'));
        const page = url.searchParams.get('page');
        fetchResults(page);
    });

    function fetchResults(page = 1) {
        // Show visual feedback (fade out table)
        $('#studentTableBody').css('opacity', '0.5');
        
        const targetUrl = "{{ route('admin.fee-category-analysis.show', $feeCategory->id) }}";
        const formData = $('#filterForm').serialize() + '&page=' + page;

        $.ajax({
            url: targetUrl,
            data: formData,
            success: function(response) {
                // A. Update Table Rows
                $('#studentTableBody').html(response.html).css('opacity', '1');
                $('#paginationLinks').html(response.pagination);

                // B. Update Summary Cards
                if(response.stats) {
                    $('#statTotal').text('₹' + Number(response.stats.total || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                    $('#statPaid').text('₹' + Number(response.stats.paid || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                    $('#statPending').text('₹' + Number(response.stats.pending || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                    $('#statConcession').text('₹' + Number(response.stats.concession || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                    $('#statCount').text(response.stats.count || 0);

                    // Update percentages
                    const total = response.stats.total > 0 ? response.stats.total : 1;
                    $('#statPaidPercent').text(((response.stats.paid / total) * 100).toFixed(1));
                    $('#statPendingPercent').text(((response.stats.pending / total) * 100).toFixed(1));
                    $('#statConcessionPercent').text(((response.stats.concession / total) * 100).toFixed(1));
                }
            },
            error: function(xhr) {
                console.error('AJAX Filter Error', xhr);
                $('#studentTableBody').css('opacity', '1');
                // Optional: show error message to user
            }
        });
    }
});
</script>
@endpush