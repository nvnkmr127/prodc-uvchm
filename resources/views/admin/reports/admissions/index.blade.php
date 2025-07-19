@extends('layouts.theme')
@section('title', 'Admissions Funnel Analytics')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Admissions Funnel Analytics</h1>

{{-- Filter Form --}}
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET">
            <div class="row align-items-end">
                <div class="col-md-5"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ $startDate }}"></div>
                <div class="col-md-5"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ $endDate }}"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Stats Cards Row -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Applications</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalApplications }}</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ $approvedCount }}</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-info shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Approval Rate</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ $approvalRate }}%</div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Review</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingCount }}</div></div></div></div>
</div>

<!-- Chart and Breakdown Table Row -->
<div class="row">
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Applications by Source</h6></div>
            <div class="card-body"><div class="chart-pie pt-4"><canvas id="funnelPieChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-8 col-lg-7">
         <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Source Performance</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Source</th><th>Total Apps</th><th>Approved</th><th>Conversion Rate</th></tr></thead>
                        <tbody>
                            @foreach($admissionsBySource as $source)
                            <tr>
                                <td>{{ $source->source ?? 'N/A' }}</td>
                                <td>{{ $source->total }}</td>
                                <td class="text-success">{{ $source->approved }}</td>
                                <td><div class="progress"><div class="progress-bar" role="progressbar" style="width: {{ $source->conversion_rate }}%;">{{ $source->conversion_rate }}%</div></div></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctxPie = document.getElementById("funnelPieChart");
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($funnelLabels) !!},
                datasets: [{ data: {!! json_encode($funnelData) !!}, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'], }],
            },
            options: { maintainAspectRatio: false, legend: { position: 'bottom' } },
        });
    }
});
</script>
@endpush