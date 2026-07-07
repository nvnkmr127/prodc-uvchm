@extends('layouts.theme')
@section('title', 'Referral & Source Tracking Report')

@push('styles')
    <style>
        :root {
            --crm-primary: #4e73df;
            --crm-success: #1cc88a;
            --crm-info: #36b9cc;
            --crm-warning: #f6c23e;
            --crm-danger: #e74a3b;
            --crm-secondary: #858796;
        }

        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-left: 0.25rem solid var(--crm-primary);
            transition: transform 0.2s;
        }

        .stats-card.source-card {
            border-left-color: var(--crm-success);
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .table-custom {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
        }

        .table-custom thead th {
            background: #f8f9fc;
            border: none;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #858796;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-custom tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border-top: 1px solid #f0f2f5;
            font-size: 0.9rem;
        }

        .table-scrollable {
            max-height: 400px;
            overflow-y: auto;
            border-radius: 0 0 1rem 1rem;
        }

        .table-scrollable::-webkit-scrollbar {
            width: 8px;
        }

        .table-scrollable::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .table-scrollable::-webkit-scrollbar-thumb {
            background: #d1d3e2;
            border-radius: 4px;
        }

        .table-scrollable::-webkit-scrollbar-thumb:hover {
            background: #a0a2b9;
        }

        .badge-referrer,
        .badge-source {
            font-weight: 600;
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.8rem;
        }

        .badge-referrer {
            background-color: #eaecf4;
            color: #4e73df;
        }

        .badge-source {
            background-color: #e6fffa;
            color: #007c62;
        }

        /* Loader */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            border-radius: 1rem;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Referral & Source Tracking</h1>
                <p class="text-muted small mb-0">Analysis by Source and Referral (All Academic Years)</p>
            </div>
            <div>
                <button type="button" onclick="downloadExcel()" class="btn btn-success shadow-sm">
                    <i class="fas fa-file-excel fa-sm text-white-50 mr-1"></i> Export to Excel
                </button>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
            <div class="card-body py-3">
                <form id="filterForm">
                    <!-- Quick Date Presets -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <span class="small font-weight-bold text-gray-600 mr-2">Quick Dates:</span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary date-preset" data-range="this_month">This Month</button>
                                <button type="button" class="btn btn-outline-primary date-preset" data-range="last_month">Last Month</button>
                                <button type="button" class="btn btn-outline-primary date-preset" data-range="this_year">This Year</button>
                                <button type="button" class="btn btn-outline-primary date-preset" data-range="all_time">All Time</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row align-items-end">
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control bg-light border-0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control bg-light border-0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600">Academic Year</label>
                            <select name="academic_year_id" id="academic_year_id" class="form-control bg-light border-0">
                                <option value="">All Years</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600">Course</label>
                            <select name="course_id" id="course_id" class="form-control bg-light border-0">
                                <option value="">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600">Batch</label>
                            <select name="batch_id" id="batch_id" class="form-control bg-light border-0" disabled>
                                <option value="">All Batches</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600">Source</label>
                            <select name="source" id="source" class="form-control bg-light border-0">
                                <option value="">All Sources</option>
                                @foreach($uniqueSources as $src)
                                    <option value="{{ $src }}">{{ ucfirst($src) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600">Student Status</label>
                            <select name="status" id="status" class="form-control bg-light border-0">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600">Commission Status</label>
                            <select name="commission_status" id="commission_status" class="form-control bg-light border-0">
                                <option value="">All</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold text-gray-600">Search</label>
                            <input type="text" name="search" id="search" class="form-control bg-light border-0"
                                placeholder="Type to search by Student Name, Referral, Source, or Enrollment No...">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold text-gray-600 d-block">&nbsp;</label>
                            <button type="button" class="btn btn-secondary w-100" id="resetBtn">
                                <i class="fas fa-undo mr-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="row mb-4" id="statsRow">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="stats-card">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Referrals</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800" id="totalReferrals">-</div>
                    <div class="mt-2 small text-muted">Students with a Referral Name</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card source-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Students</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800" id="totalStudents">-</div>
                    <div class="mt-2 small text-muted">All Students in Filter</div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="row mb-4">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 1rem;">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Admissions by Source</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 300px; position: relative;">
                            <canvas id="sourcePieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 1rem;">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Referrers</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar" style="height: 300px; position: relative;">
                            <canvas id="referralBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Source Breakdown Table --}}
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 1rem; position: relative;">
                    <div class="loading-overlay" id="loadingSource" style="display: none;">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-success">Source Performance</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-scrollable">
                            <table class="table table-custom m-0">
                                <thead>
                                    <tr>
                                        <th>Source Name</th>
                                        <th class="text-center">Students</th>
                                        <th>Share</th>
                                    </tr>
                                </thead>
                                <tbody id="sourceTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Referrer Breakdown Table --}}
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 1rem; position: relative;">
                    <div class="loading-overlay" id="loadingReferral" style="display: none;">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Referrers</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-scrollable">
                            <table class="table table-custom m-0">
                                <thead>
                                    <tr>
                                        <th>Referrer Name</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Paid</th>
                                        <th class="text-center text-danger">Pending</th>
                                        <th class="text-right">Total Payout</th>
                                        <th>Share</th>
                                    </tr>
                                </thead>
                                <tbody id="referralTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed List --}}
        <div class="card shadow-sm border-0" style="border-radius: 1rem; position: relative;">
            <div class="loading-overlay" id="loadingDetailed" style="display: none;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-gray-800">Detailed Student List (With Financials)</h6>
                <div>
                    <button class="btn btn-sm btn-primary shadow-sm mr-2" id="bulkPayBtn" style="display: none;" onclick="openBulkPaymentModal()">
                        <i class="fas fa-money-bill-wave fa-sm"></i> Bulk Pay Selected (<span id="bulkPayCount">0</span>)
                    </button>
                    <button class="btn btn-sm btn-success shadow-sm" onclick="downloadExcel()">
                        <i class="fas fa-file-excel fa-sm"></i> Download Excel
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-scrollable">
                    <table class="table table-custom m-0" id="detailedTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAllCheckbox">
                                        <label class="custom-control-label" for="selectAllCheckbox"></label>
                                    </div>
                                </th>
                                <th>Adm Date</th>
                                <th>Student Name</th>
                                <th>Course / Batch</th>
                                <th>Source / Referrer</th>
                                <th>Fee Status</th>
                                <th>Referral Eligibility</th>
                                <th>Financials</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="detailedTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Commission Payout History Table --}}
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem;">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-gray-800">Commission / Reward Payout History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-scrollable">
                    <table class="table table-custom m-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Referrer</th>
                                <th>Student</th>
                                <th>Mode</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="payoutTableBody"></tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="4" class="text-right">Total Paid:</td>
                                <td class="text-right" id="totalPayoutFooter">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('admin.reports.referrals.partials.payment_modal')

    <!-- Bulk Payment Modal -->
    <div class="modal fade" id="bulkPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
                <div class="modal-header bg-primary text-white" style="border-radius: 1rem 1rem 0 0;">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-money-bill-wave mr-2"></i>Bulk Commission Payout</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="bulkPaymentForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-info">
                            You are about to mark <strong id="bulkSelectedCount">0</strong> students as having their referral commission paid.
                        </div>
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-gray-800">Payment Mode (for all selected) <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-control" required>
                                <option value="">Select Mode...</option>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI (Google Pay / PhonePe)</option>
                                <option value="Bank Transfer">Bank Transfer (NEFT/IMPS)</option>
                                <option value="Adjusted in Fees">Adjusted in Fees</option>
                                <option value="Other">Other / Gift</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-800">Notes / Reference (Optional)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Transaction IDs or general notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light" style="border-radius: 0 0 1rem 1rem;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" id="submitBulkPaymentBtn">
                            Confirm Bulk Payout
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Batches Data passed from controller
        const batchesByCourse = @json($batches);
        
        let sourcePieChart = null;
        let referralBarChart = null;

        $(document).ready(function () {
            // Initial Fetch
            fetchReportData();

            // Filter Change Events
            $('#filterForm input, #filterForm select').on('change', function () {
                if (this.id === 'course_id' || this.id === 'academic_year_id') {
                    updateBatchDropdown($('#course_id').val(), $('#academic_year_id').val());
                }
                fetchReportData();
            });

            // Date Preset Buttons
            $('.date-preset').click(function() {
                const range = $(this).data('range');
                const today = new Date();
                let start = '';
                let end = '';

                if (range === 'this_month') {
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                } else if (range === 'last_month') {
                    start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    end = new Date(today.getFullYear(), today.getMonth(), 0);
                } else if (range === 'this_year') {
                    start = new Date(today.getFullYear(), 0, 1);
                    end = new Date(today.getFullYear(), 11, 31);
                }

                if (start && end) {
                    // Format as YYYY-MM-DD
                    const startStr = start.toISOString().split('T')[0];
                    const endStr = end.toISOString().split('T')[0];
                    $('#start_date').val(startStr);
                    $('#end_date').val(endStr);
                } else {
                    $('#start_date').val('');
                    $('#end_date').val('');
                }
                
                fetchReportData();
            });

            // Debounced Search Input
            let timeout = null;
            $('#search').on('keyup', function () {
                clearTimeout(timeout);
                timeout = setTimeout(fetchReportData, 500);
            });

            // Reset Button Handler
            $('#resetBtn').click(function () {
                // Reset all form fields
                $('#filterForm')[0].reset();

                // Explicitly clear the date fields (reset() only restores default values)
                $('#start_date').val('');
                $('#end_date').val('');
                $('#academic_year_id').val('');

                // Disable batch select and reset it
                $('#batch_id').prop('disabled', true).empty().append('<option value="">All Batches</option>');

                // Fetch fresh data
                fetchReportData();
            });
        });

        function updateBatchDropdown(courseId, academicYearId) {
            const batchSelect = $('#batch_id');
            batchSelect.empty().append('<option value="">All Batches</option>');

            if (courseId && batchesByCourse[courseId]) {
                batchSelect.prop('disabled', false);
                batchesByCourse[courseId].forEach(batch => {
                    if (!academicYearId || batch.academic_year_id == academicYearId) {
                        batchSelect.append(`<option value="${batch.id}">${batch.name}</option>`);
                    }
                });
            } else {
                batchSelect.prop('disabled', true);
            }
        }

        function fetchReportData() {
            // Show Loaders
            $('.loading-overlay').show();

            const formData = $('#filterForm').serialize();

            $.ajax({
                url: "{{ route('admin.reports.referrals.index') }}",
                type: "GET",
                data: formData,
                success: function (response) {
                    updateStats(response);
                    updateSourceTable(response.source_stats);
                    updateReferralTable(response.referral_stats);
                    updateDetailedTable(response.students);
                    updatePayoutTable(response.students);
                    updateCharts(response.source_stats, response.referral_stats);
                },
                error: function (xhr) {
                    console.error("Error fetching data:", xhr);
                    alert("Failed to load report data. Please try again.");
                },
                complete: function () {
                    $('.loading-overlay').hide();
                }
            });
        }
        
        function updateCharts(sourceStats, referralStats) {
            // Update Pie Chart
            const sourceLabels = sourceStats.map(s => s.source);
            const sourceData = sourceStats.map(s => s.total_admissions);
            const sourceColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'];
            
            if (sourcePieChart) {
                sourcePieChart.destroy();
            }
            const ctxPie = document.getElementById('sourcePieChart');
            if (ctxPie) {
                sourcePieChart = new Chart(ctxPie, {
                    type: 'pie',
                    data: {
                        labels: sourceLabels,
                        datasets: [{
                            data: sourceData,
                            backgroundColor: sourceColors.slice(0, sourceLabels.length),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }

            // Update Bar Chart (Top 10 Referrers)
            const topReferrals = referralStats.slice(0, 10);
            const refLabels = topReferrals.map(r => r.referral_name);
            const refData = topReferrals.map(r => r.total_admissions);
            
            if (referralBarChart) {
                referralBarChart.destroy();
            }
            const ctxBar = document.getElementById('referralBarChart');
            if (ctxBar) {
                referralBarChart = new Chart(ctxBar, {
                    type: 'bar',
                    data: {
                        labels: refLabels,
                        datasets: [{
                            label: 'Admissions',
                            data: refData,
                            backgroundColor: '#4e73df',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }
        }

        function updateStats(data) {
            $('#totalReferrals').text(data.total_referrals);
            $('#totalStudents').text(data.total_students);
        }

        function updateSourceTable(stats) {
            const tbody = $('#sourceTableBody');
            tbody.empty();

            if (stats.length === 0) {
                tbody.append('<tr><td colspan="3" class="text-center text-muted py-4">No data</td></tr>');
                return;
            }

            stats.forEach(stat => {
                tbody.append(`
                                                                                <tr>
                                                                                    <td><span class="badge-source">${stat.source}</span></td>
                                                                                    <td class="text-center font-weight-bold">${stat.total_admissions}</td>
                                                                                    <td>
                                                                                        <div class="d-flex align-items-center">
                                                                                            <div class="progress flex-grow-1 mr-2" style="height: 8px;">
                                                                                                <div class="progress-bar bg-success" role="progressbar" style="width: ${stat.share}%"></div>
                                                                                            </div>
                                                                                            <small class="font-weight-bold text-success">${stat.share}%</small>
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                            `);
            });
        }

        function updateReferralTable(stats) {
            const tbody = $('#referralTableBody');
            tbody.empty();

            if (stats.length === 0) {
                tbody.append('<tr><td colspan="6" class="text-center text-muted py-4">No data</td></tr>');
                return;
            }

            stats.forEach(stat => {
                // Ensure values exist to prevent JS errors if undefined
                const paid = stat.paid_count || 0;
                const remaining = stat.remaining_count || 0;
                const totalPayout = stat.total_payout || 0;

                tbody.append(`
                                        <tr>
                                            <td><span class="badge-referrer">${stat.referral_name}</span></td>
                                            <td class="text-center font-weight-bold">${stat.total_admissions}</td>
                                            <td class="text-center text-success font-weight-bold">${paid}</td>
                                            <td class="text-center text-danger font-weight-bold">${remaining}</td>
                                            <td class="text-right font-weight-bold">${new Intl.NumberFormat().format(totalPayout)}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 mr-2" style="height: 8px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: ${stat.share}%"></div>
                                                    </div>
                                                    <small class="font-weight-bold text-primary">${stat.share}%</small>
                                                </div>
                                            </td>
                                        </tr>
                                    `);
            });
        }

        function updatePayoutTable(students) {
            const tbody = $('#payoutTableBody');
            const footerTotal = $('#totalPayoutFooter');
            tbody.empty();

            // Filter only paid commissions
            const paidStudents = students.filter(s => s.is_commission_paid);

            if (paidStudents.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center text-muted py-4">No commission payouts yet</td></tr>');
                footerTotal.text('-');
                return;
            }

            let totalPayoutSum = 0;

            paidStudents.forEach(std => {
                const amount = parseFloat(std.commission_amount || 0);
                totalPayoutSum += amount;

                tbody.append(`
                                        <tr>
                                            <td class="small text-muted">${std.commission_paid_at}</td>
                                            <td><span class="badge-referrer">${std.referral_name}</span></td>
                                            <td class="font-weight-bold">${std.student_name}</td>
                                            <td><span class="badge badge-light border">${std.payment_mode || '-'}</span></td>
                                            <td class="text-right font-weight-bold text-success">${new Intl.NumberFormat('en-IN').format(amount)}</td>
                                        </tr>
                                    `);
            });

            footerTotal.text(new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(totalPayoutSum));
        }

        function updateDetailedTable(students) {
            const tbody = $('#detailedTableBody');
            tbody.empty();
            
            // Reset bulk pay state
            $('#selectAllCheckbox').prop('checked', false);
            updateBulkPayButton();

            if (students.length === 0) {
                tbody.append('<tr><td colspan="9" class="text-center text-muted py-5">No records found matching criteria</td></tr>');
                return;
            }

            students.forEach(std => {
                let statusClass = 'badge-secondary';
                if (std.status === 'Active') statusClass = 'badge-success';
                if (std.status === 'Dropout') statusClass = 'badge-danger';
                if (std.status === 'Graduated') statusClass = 'badge-info';

                let checkboxHtml = '';
                if (std.referral_name !== '-' && std.is_eligible && !std.is_commission_paid) {
                    checkboxHtml = `
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input student-checkbox" id="chk_${std.student_id}" value="${std.student_id}">
                            <label class="custom-control-label" for="chk_${std.student_id}"></label>
                        </div>
                    `;
                }

                tbody.append(`
                                                                                <tr>
                                                                                    <td class="text-center">${checkboxHtml}</td>
                                                                                    <td class="small text-muted">${std.admission_date}</td>
                                                                                    <td>
                                                                                        <a href="/admin/students/${std.student_id}" target="_blank" class="font-weight-bold text-primary text-decoration-none" title="View Student Profile">
                                                                                            ${std.student_name} <i class="fas fa-external-link-alt small ml-1 text-muted"></i>
                                                                                        </a>
                                                                                    </td>
                                                                                    <td>
                                                                                        <div class="small font-weight-bold">${std.course_name}</div>
                                                                                        <div class="small text-muted">${std.batch_name}</div>
                                                                                    </td>
                                                                                    <td>
                                                                                        <div class="mb-1"><span class="badge-source">${std.source}</span></div>
                                                                                        ${std.referral_name !== '-' ? `<span class="badge-referrer">${std.referral_name}</span>` : ''}
                                                                                    </td>
                                                                                    <td>
                                                                                        <div class="small font-weight-bold text-dark">${std.percentage_paid}% Paid</div>
                                                                                        <div class="progress mt-1" style="height: 6px;">
                                                                                            <div class="progress-bar ${std.percentage_paid >= 30 ? 'bg-success' : 'bg-warning'}" 
                                                                                                 role="progressbar" style="width: ${Math.min(std.percentage_paid, 100)}%"></div>
                                                                                        </div>
                                                                                    </td>
                                                                                    <td>
                                                                                        ${getCommissionStatusHtml(std)}
                                                                                    </td>
                                                                                    <td>
                                                                                        <div class="small">
                                                                                            <div class="d-flex justify-content-between"><span>Total:</span> <span class="font-weight-bold">${std.total_amount}</span></div>
                                                                                            <div class="d-flex justify-content-between text-info"><span>Concession:</span> <span>${std.concession}</span></div>
                                                                                            <div class="d-flex justify-content-between text-success"><span>Paid:</span> <span>${std.paid}</span></div>
                                                                                            <div class="d-flex justify-content-between text-danger" style="border-top:1px dashed #ddd"><span>Due:</span> <span class="font-weight-bold">${std.remaining}</span></div>
                                                                                        </div>
                                                                                    </td>
                                                                                    <td><span class="badge ${statusClass}">${std.status}</span></td>
                                                                                </tr>
                                                                            `);
            });
            
            // Re-bind checkbox events
            bindCheckboxEvents();
        }
        
        function bindCheckboxEvents() {
            // Select All Checkbox
            $('#selectAllCheckbox').off('change').on('change', function() {
                $('.student-checkbox').prop('checked', $(this).prop('checked'));
                updateBulkPayButton();
            });

            // Individual Checkboxes
            $('.student-checkbox').off('change').on('change', function() {
                const totalCheckboxes = $('.student-checkbox').length;
                const checkedCheckboxes = $('.student-checkbox:checked').length;
                
                $('#selectAllCheckbox').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
                updateBulkPayButton();
            });
        }
        
        function updateBulkPayButton() {
            const checkedCount = $('.student-checkbox:checked').length;
            $('#bulkPayCount').text(checkedCount);
            
            if (checkedCount > 0) {
                $('#bulkPayBtn').fadeIn(200);
            } else {
                $('#bulkPayBtn').fadeOut(200);
            }
        }

        function getCommissionStatusHtml(std) {
            // If no referral, not applicable
            if (std.referral_name === '-') {
                return '<span class="badge badge-light text-muted">N/A</span>';
            }

            // If already paid
            if (std.is_commission_paid) {
                return `
                                                        <div class="text-center">
                                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Paid</span>
                                                            <div class="small text-muted mt-1" style="font-size: 0.7rem;">${std.commission_paid_at}</div>
                                                        </div>
                                                    `;
            }

            // Checks eligibility
            if (std.is_eligible) {
                return `
                                                        <button class="btn btn-sm btn-info shadow-sm py-0" style="font-size: 0.8rem;" 
                                                            onclick="openPaymentModal(${std.student_id}, '${std.student_name}', '${std.referral_name || ''}', '${std.source}')">
                                                        Pay / Reward
                                                    </button>
                                                    `;
            } else {
                return `
                                                       <span class="badge badge-warning text-dark" title="Needs 30% paid">Pending (${std.percentage_paid}%)</span>
                                                    `;
            }
        }

        // Initialize Payment Modal
        let currentStudentId = null;

        function openPaymentModal(studentId, studentName, referralName, source) {
            currentStudentId = studentId;
            $('#modalStudentName').text(studentName);
            $('#modalReferralName').text(referralName);

            // Toggle Fee Discount Option based on Source
            const feeDiscountOption = $('#optionFeeDiscount');
            if (source === 'Student Refer') {
                feeDiscountOption.removeClass('d-none');
            } else {
                feeDiscountOption.addClass('d-none');
                // If it was selected, reset to empty or Cash
                if ($('#payment_mode').val() === 'Fee Discount') {
                    $('#payment_mode').val('');
                }
            }

            $('#paymentCommissionModal').modal('show');
            $('#commissionForm')[0].reset();
        }

        function submitCommissionPayment() {
            if (!currentStudentId) return;

            const form = $('#commissionForm');
            const btn = $('#saveCommissionBtn');

            // Basic Validation
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            const formData = form.serialize();

            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            const urlTemplate = "{{ route('admin.reports.referrals.mark-commission-paid', ['student' => ':student_id']) }}";
            const finalUrl = urlTemplate.replace(':student_id', currentStudentId);

            $.ajax({
                url: finalUrl,
                type: 'POST',
                data: formData + '&_token={{ csrf_token() }}',
                success: function (response) {
                    if (response.success) {
                        $('#paymentCommissionModal').modal('hide');
                        let msg = response.message;
                        if (response.warning) {
                            msg += '\n\nWARNING: ' + response.warning;
                        }
                        alert(msg);
                        // Refresh data to update all tables
                        fetchReportData();
                    }
                },
                error: function (xhr) {
                    console.error(xhr);
                    alert('Error marking commission as paid. Please check inputs.');
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        }

        function downloadExcel() {
            const formData = $('#filterForm').serialize();
            // Append export=excel to the query params
            const url = "{{ route('admin.reports.referrals.index') }}?" + formData + "&export=excel";
            window.location.href = url;
        }
        // Bulk Pay Functions
        function openBulkPaymentModal() {
            const checkedCount = $('.student-checkbox:checked').length;
            if (checkedCount === 0) return;
            
            $('#bulkSelectedCount').text(checkedCount);
            $('#bulkPaymentForm')[0].reset();
            $('#bulkPaymentModal').modal('show');
        }

        $('#bulkPaymentForm').on('submit', function(e) {
            e.preventDefault();
            
            const btn = $('#submitBulkPaymentBtn');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');
            
            // Collect selected student IDs
            const studentIds = [];
            $('.student-checkbox:checked').each(function() {
                studentIds.push($(this).val());
            });
            
            const formData = {
                _token: '{{ csrf_token() }}',
                student_ids: studentIds,
                payment_mode: $('select[name="payment_mode"]', this).val(),
                notes: $('textarea[name="notes"]', this).val()
            };

            $.ajax({
                url: "{{ route('admin.reports.referrals.bulk_pay') }}",
                type: "POST",
                data: formData,
                success: function (response) {
                    $('#bulkPaymentModal').modal('hide');
                    alert(response.message || 'Successfully marked selected students as paid!');
                    fetchReportData();
                },
                error: function (xhr) {
                    console.error("Error processing bulk payment:", xhr);
                    let errMsg = "Failed to process bulk payment.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    alert(errMsg);
                },
                complete: function () {
                    btn.prop('disabled', false).html('Confirm Bulk Payout');
                }
            });
        });
    </script>
@endpush