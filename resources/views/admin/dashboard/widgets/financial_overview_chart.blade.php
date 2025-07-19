<div class="card shadow h-100">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Financial Overview (Last 30 Days)</h6>
    </div>
    <div class="card-body">
        <div class="chart-area">
            <canvas id="financialOverviewLineChart-{{ $widget->id }}"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById("financialOverviewLineChart-{{ $widget->id }}");
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($widgetData['financialLabels'] ?? []) !!},
                datasets: [{
                    label: "Income",
                    borderColor: "rgba(78, 115, 223, 1)",
                    data: {!! json_encode($widgetData['incomeData'] ?? []) !!},
                }, {
                    label: "Expense",
                    borderColor: "rgba(231, 74, 59, 1)",
                    data: {!! json_encode($widgetData['expenseData'] ?? []) !!},
                }],
            },
            options: { maintainAspectRatio: false, legend: { display: false } }
        });
    }
});
</script>
@endpush