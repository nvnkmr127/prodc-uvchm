{{-- resources/views/components/collection-efficiency-widget.blade.php --}}
@php
    // If efficiency not provided, get it from service
    if (!isset($efficiency)) {
        $reminderService = app(\App\Services\PaymentReminderService::class);
        $efficiency = $reminderService->getCollectionEfficiency();
    }
@endphp

<div class="card collection-efficiency-widget">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="mdi mdi-chart-line me-2"></i>
            Collection Efficiency
        </h5>
        <a href="{{ route('payment-reminders.defaulters') }}" class="btn btn-sm btn-outline-info">
            View Details
        </a>
    </div>
    <div class="card-body">
        {{-- Collection Rate Progress --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="font-size-14 mb-0">Collection Rate</h6>
                <span class="badge bg-soft-success text-success font-size-12">
                    {{ number_format($efficiency['collection_rate'], 1) }}%
                </span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: {{ $efficiency['collection_rate'] }}%">
                </div>
            </div>
        </div>

        {{-- Overdue Rate Progress --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="font-size-14 mb-0">Overdue Rate</h6>
                <span class="badge bg-soft-danger text-danger font-size-12">
                    {{ number_format($efficiency['overdue_rate'], 1) }}%
                </span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-danger" role="progressbar" 
                     style="width: {{ $efficiency['overdue_rate'] }}%">
                </div>
            </div>
        </div>

        {{-- Invoice Statistics --}}
        <div class="row text-center">
            <div class="col-4">
                <div class="p-2">
                    <h5 class="font-size-16 mb-1 text-primary">{{ number_format($efficiency['total_invoices']) }}</h5>
                    <p class="text-muted font-size-12 mb-0">Total</p>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2">
                    <h5 class="font-size-16 mb-1 text-success">{{ number_format($efficiency['paid_invoices']) }}</h5>
                    <p class="text-muted font-size-12 mb-0">Paid</p>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2">
                    <h5 class="font-size-16 mb-1 text-danger">{{ number_format($efficiency['overdue_invoices']) }}</h5>
                    <p class="text-muted font-size-12 mb-0">Overdue</p>
                </div>
            </div>
        </div>

        {{-- Performance Indicator --}}
        <div class="mt-3">
            @if($efficiency['collection_rate'] >= 90)
                <div class="alert alert-success py-2 mb-0" role="alert">
                    <i class="mdi mdi-check-circle me-1"></i>
                    <small><strong>Excellent</strong> collection performance!</small>
                </div>
            @elseif($efficiency['collection_rate'] >= 75)
                <div class="alert alert-warning py-2 mb-0" role="alert">
                    <i class="mdi mdi-information me-1"></i>
                    <small><strong>Good</strong> collection rate. Room for improvement.</small>
                </div>
            @else
                <div class="alert alert-danger py-2 mb-0" role="alert">
                    <i class="mdi mdi-alert me-1"></i>
                    <small><strong>Low</strong> collection rate. Immediate attention needed.</small>
                </div>
            @endif
        </div>
    </div>
</div>