{{-- resources/views/components/reminder-widget.blade.php --}}
@php
    // If stats not provided, get them from service
    if (!isset($stats)) {
        $reminderService = app(\App\Services\PaymentReminderService::class);
        $stats = $reminderService->getReminderStatistics();
    }
@endphp

<div class="card reminder-widget">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="mdi mdi-email-send me-2"></i>
            Payment Reminders
        </h5>
        <a href="{{ route('payment-reminders.dashboard') }}" class="btn btn-sm btn-outline-primary">
            View All
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6">
                <div class="text-center">
                    <div class="avatar-sm mx-auto mb-2">
                        <span class="avatar-title bg-soft-warning text-warning rounded-circle">
                            <i class="mdi mdi-clock-outline font-size-18"></i>
                        </span>
                    </div>
                    <h5 class="font-size-16 mb-1">{{ number_format($stats['pending_reminders']) }}</h5>
                    <p class="text-muted font-size-13 mb-0">Pending</p>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center">
                    <div class="avatar-sm mx-auto mb-2">
                        <span class="avatar-title bg-soft-success text-success rounded-circle">
                            <i class="mdi mdi-check font-size-18"></i>
                        </span>
                    </div>
                    <h5 class="font-size-16 mb-1">{{ number_format($stats['sent_today']) }}</h5>
                    <p class="text-muted font-size-13 mb-0">Sent Today</p>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center">
                    <div class="avatar-sm mx-auto mb-2">
                        <span class="avatar-title bg-soft-danger text-danger rounded-circle">
                            <i class="mdi mdi-close font-size-18"></i>
                        </span>
                    </div>
                    <h5 class="font-size-16 mb-1">{{ number_format($stats['failed_reminders']) }}</h5>
                    <p class="text-muted font-size-13 mb-0">Failed</p>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center">
                    <div class="avatar-sm mx-auto mb-2">
                        <span class="avatar-title bg-soft-info text-info rounded-circle">
                            <i class="mdi mdi-account-alert font-size-18"></i>
                        </span>
                    </div>
                    <h5 class="font-size-16 mb-1">{{ number_format($stats['total_defaulters']) }}</h5>
                    <p class="text-muted font-size-13 mb-0">Defaulters</p>
                </div>
            </div>
        </div>
        
        @if($stats['overdue_reminders'] > 0)
            <div class="alert alert-warning mt-3 mb-0" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                <strong>{{ $stats['overdue_reminders'] }}</strong> reminders are overdue and need attention.
            </div>
        @endif
        
        <div class="mt-3">
            <button type="button" class="btn btn-primary btn-sm w-100" onclick="processRemindersDashboard()">
                <i class="mdi mdi-play-circle me-1"></i>
                Process Pending Reminders
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function processRemindersDashboard() {
    if (confirm('Process all pending reminders?')) {
        $.ajax({
            url: '{{ route("payment-reminders.process-pending") }}',
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    alert('Processing started successfully!');
                    // Refresh the widget or redirect to reminders dashboard
                    window.location.href = '{{ route("payment-reminders.dashboard") }}';
                } else {
                    alert('Failed to start processing: ' + response.error);
                }
            },
            error: function(xhr) {
                alert('An error occurred while processing reminders.');
            }
        });
    }
}
</script>
@endpush