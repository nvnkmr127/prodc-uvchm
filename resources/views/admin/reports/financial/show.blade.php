@extends('layouts.theme')
@section('title', 'Financial Reports')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Financial Reports</h1>

{{-- Filter Form --}}
<div class="card shadow mb-4 no-print">
    <div class="card-body">
        <form action="{{ route('admin.reports.financial.show') }}" method="GET" id="reportForm">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label>Report Type</label>
                    <select name="report_type" class="form-control">
                        <option value="summary" {{ request('report_type', 'summary') == 'summary' ? 'selected' : '' }}>Income & Expense Summary</option>
                        <option value="collections" {{ request('report_type') == 'collections' ? 'selected' : '' }}>Fee Collection Report</option>
                        <option value="defaulters" {{ request('report_type') == 'defaulters' ? 'selected' : '' }}>Fee Defaulter List</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Date Range</label>
                   <select name="date_range" id="date_range_select" class="form-control">
                        <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ request('date_range') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ request('date_range') == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="last_week" {{ request('date_range') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                        <option value="last_7_days" {{ request('date_range') == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="last_30_days" {{ request('date_range') == 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_month" {{ request('date_range', 'this_month') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_year" {{ request('date_range') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="last_year" {{ request('date_range') == 'last_year' ? 'selected' : '' }}>Last Year</option>
                        <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Custom Period</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3" id="custom_date_container" style="display: {{ request('date_range') == 'custom' ? 'flex' : 'none' }};">
                    <div class="row">
                        <div class="col-6"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date', $displayStartDate) }}"></div>
                        <div class="col-6"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date', $displayEndDate) }}"></div>
                    </div>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Report Results --}}
@if(isset($reportData))
<div class="card shadow mb-4 printable">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Report Results</h6>
        <div class="no-print">
            {{-- NEW PRINT BUTTON ADDED HERE --}}
            <button onclick="window.print()" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-print"></i> Print Report</button>
            <a href="{{ route('admin.reports.financial.show', array_merge($filterParams, ['export' => 'xlsx'])) }}" class="btn btn-sm btn-success shadow-sm"><i class="fas fa-file-excel"></i> Export to Excel</a>
        </div>
    </div>
    {{-- Conditionally display the Summary Report --}}
    @if($reportType == 'summary')
        <div class="row">
            <div class="col-xl-4 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Income ({{$displayStartDate}} to {{$displayEndDate}})</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ setting('currency_symbol','₹') }} {{ number_format($reportData['total_income'], 2) }}</div></div></div></div>
            <div class="col-xl-4 col-md-6 mb-4"><div class="card border-left-danger shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Expenses</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ setting('currency_symbol','₹') }} {{ number_format($reportData['total_expenses'], 2) }}</div></div></div></div>
            <div class="col-xl-4 col-md-6 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Net Profit / Loss</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($reportData['net_profit'], 2) }}</div></div></div></div>
        </div>
        <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Expenses by Category</h6></div><div class="card-body"><canvas id="expensePieChart" style="max-height: 300px;"></canvas></div></div>
    @endif

    {{-- Conditionally display the Collections Report --}}
    @if($reportType == 'collections')
        <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Fee Collection Report from {{ $displayStartDate }} to {{ $displayEndDate }}</h6></div>
            <div class="card-body">
                <div class="table-responsive">
    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
        <thead class="thead-light">
            <tr>
                <th>Student Name</th>
                <th>Course</th>
                <th>Mobile No.</th>
                <th>Admission No.</th>
                <th>Father Name</th>
                <th class="text-right">Total Fees (₹)</th>
                <th class="text-right">Paid Fees (₹)</th>
                <th class="text-right">Concession (₹)</th>
                <th class="text-right">Balance (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $payment)
                @php
                    // For convenience, let's get the invoice and student object
                    $invoice = $payment->invoice;
                    $student = $invoice->student;
                @endphp
                <tr>
                    <td>
                        {{-- The student name is now a link to their profile --}}
                        <a href="{{ route('admin.students.show', $student) }}" target="_blank">
                            <strong>{{ $student->name }}</strong>
                        </a>
                        <small class="d-block text-muted">Paid on: {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</small>
                    </td>
                    <td>{{ $student->batch->course->name ?? 'N/A' }}</td>
                    <td>{{ $student->student_mobile }}</td>
                    <td>{{ $student->enrollment_number }}</td>
                    <td>{{ $student->father_name }}</td>
                    <td class="text-right">{{ number_format($invoice->total_amount, 2) }}</td>
                    <td class="text-right text-success font-weight-bold">{{ number_format($invoice->paid_amount, 2) }}</td>
                    <td class="text-right text-warning">{{ number_format($invoice->concession_amount, 2) }}</td>
                    <td class="text-right text-danger font-weight-bold">{{ number_format($invoice->due_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No fee collections found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
            </div>
        </div> </div>
    @endif

    {{-- Conditionally display the Defaulters Report --}}
    @if($reportType == 'defaulters')
        <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Fee Defaulter Report</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Student Name</th><th>Enrollment #</th><th>Course / Batch</th><th class="text-right">Total Amount Due</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse($reportData as $student)
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->enrollment_number }}</td>
                                <td>{{ $student->batch->course->name ?? 'N/A' }} - {{ $student->batch->name ?? 'N/A' }}</td>
                                <td class="text-right text-danger font-weight-bold">{{ number_format($student->total_due, 2) }}</td>
                                <td><a href="{{ route('admin.financials.student.ledger', $student) }}" class="btn btn-sm btn-info">View Ledger</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">No students with outstanding dues found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endif
@endsection

@push('scripts')
<script>
    document.getElementById('date_range_select').addEventListener('change', function() {
        var customDateContainer = document.getElementById('custom_date_container');
        if (this.value === 'custom') {
            customDateContainer.style.display = 'flex';
        } else {
            customDateContainer.style.display = 'none';
        }
    });
</script>
@if(isset($reportData) && $reportType == 'summary')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctxPie = document.getElementById("expensePieChart");
    if (ctxPie) {
        new Chart(ctxPie, {type: 'pie', data: {labels: {!! json_encode($reportData['expense_chart_labels']) !!}, datasets: [{ data: {!! json_encode($reportData['expense_chart_data']) !!}, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'], }],}, options: { responsive: true, maintainAspectRatio: false },});
    }
});
</script>
@endif
@endpush