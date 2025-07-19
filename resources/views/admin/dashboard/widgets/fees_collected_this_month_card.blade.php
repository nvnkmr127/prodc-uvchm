<div class="card border-left-warning shadow h-100 py-2">
    <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Fees Collected (This Month)</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($widgetData['feesCollectedThisMonth'] ?? 0) }}</div>
            </div>
            <div class="col-auto"><i class="fas fa-rupee-sign fa-2x text-gray-300"></i></div>
        </div>
    </div>
</div>
