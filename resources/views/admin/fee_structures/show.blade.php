@extends('layouts.theme')
@section('title', 'View Fee Structure')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Fee Structure Details</h1>
    <div>
        <a href="{{ route('admin.fee-structures.edit', $feeStructure) }}" class="btn btn-sm btn-warning shadow-sm">
            <i class="fas fa-edit fa-sm text-white-50"></i> Edit
        </a>
        <a href="{{ route('admin.fee-structures.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    @if($feeStructure->batch)
                        Fee Structure for {{ $feeStructure->batch->name }}
                        <small class="text-muted">({{ $feeStructure->batch->course->name ?? 'Unassigned Course' }})</small>
                    @else
                        <span class="text-danger">Fee Structure for an Invalid or Deleted Batch</span>
                    @endif
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-6">
                        <h5 class="font-weight-bold">Total Amount</h5>
                        <h4 class="text-success">₹{{ number_format($feeStructure->total_amount, 2) }}</h4>
                    </div>
                    {{-- REVISED: Display Payment Terms --}}
                    <div class="col-6">
                        <h5 class="font-weight-bold">Payment Terms</h5>
                        <h4 class="text-info">{{ $feeStructure->payment_terms }} Installment(s)</h4>
                    </div>
                </div>
                <hr>
                <h6 class="font-weight-bold">Fee Components Breakdown</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <tbody>
                            @forelse($feeStructure->feeCategories as $component)
                                <tr>
                                    <td><i class="fas fa-caret-right text-primary fa-fw"></i> {{ $component->name }}</td>
                                    <td class="text-right font-weight-bold">₹{{ number_format($component->pivot->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">No components have been added.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Component Distribution</h6>
            </div>
            <div class="card-body">
                @if(!empty($chartData) && $chartData->sum() > 0)
                    <div class="chart-pie pt-4" style="height: 300px;">
                        <canvas id="feeComponentPieChart"></canvas>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie fa-3x text-gray-300"></i>
                        <p class="mt-3">No data available for chart.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// This script remains the same
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById("feeComponentPieChart");
    if (ctx) {
        var chartData = {!! json_encode($chartData) !!};
        if (chartData.reduce((a, b) => a + b, 0) > 0) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        data: chartData,
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617', '#60616f', '#37383e'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    cutoutPercentage: 80,
                },
            });
        }
    }
});
</script>
@endpush