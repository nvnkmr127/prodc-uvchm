@extends('layouts.theme')
@section('title', 'Financial Dashboard')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Financial Dashboard & Reports</h1>
</div>

{{-- Filter Form --}}
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">View Report for Period</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.reports.financial.show') }}" method="GET">
            <div class="row">
                <div class="col-md-5"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date', \Carbon\Carbon::now()->startOfMonth()->toDateString()) }}" required></div>
                <div class="col-md-5"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date', \Carbon\Carbon::now()->endOfMonth()->toDateString()) }}" required></div>
                <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Generate</button></div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Income (Selected Period)</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ setting('currency_symbol','₹') }} {{ number_format($totalIncome, 2) }}</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-danger shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Expenses (Selected Period)</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ setting('currency_symbol','₹') }} {{ number_format($totalExpenses, 2) }}</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-info shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Net Profit / Loss</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($netProfit, 2) }}</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Outstanding Dues</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalOutstanding, 2) }}</div></div></div></div>
</div>

<div class="row">
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Income (Last 12 Months)</h6></div>
            <div class="card-body"><div class="chart-area"><canvas id="incomeChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Expenses by Category (Selected Period)</h6></div>
            <div class="card-body"><div class="chart-pie pt-4"><canvas id="expensePieChart"></canvas></div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Expense Pie Chart
    var ctxPie = document.getElementById("expensePieChart");
    var expensePieChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($expenseChartLabels) !!},
            datasets: [{ data: {!! json_encode($expenseChartData) !!}, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'], }],
        },
        options: { maintainAspectRatio: false, legend: { position: 'bottom' } },
    });

    // Income Line Chart
    var ctxLine = document.getElementById("incomeChart");
    var incomeChart = new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: {!! json_encode($incomeChartLabels) !!},
            datasets: [{
                label: "Income",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                data: {!! json_encode($incomeChartData) !!},
            }],
        },
        options: { maintainAspectRatio: false, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } },
    });
});
</script>
@endpush