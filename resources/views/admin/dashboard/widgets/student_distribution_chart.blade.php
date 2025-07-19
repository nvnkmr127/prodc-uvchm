<div class="card shadow h-100">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Student Distribution by Course</h6>
    </div>
    <div class="card-body d-flex align-items-center justify-content-center">
        <div class="chart-pie" style="height: 250px; width: 100%;">
            <canvas id="studentDistributionPieChart-{{ $widget->id }}"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById("studentDistributionPieChart-{{ $widget->id }}");
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($widgetData['courseLabels'] ?? []) !!},
                datasets: [{
                    data: {!! json_encode($widgetData['courseData'] ?? []) !!},
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                }],
            },
            options: { maintainAspectRatio: false, legend: { display: true, position: 'bottom' } },
        });
    }
});
</script>
@endpush