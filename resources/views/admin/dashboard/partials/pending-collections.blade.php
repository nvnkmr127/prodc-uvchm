                <!-- Pending Collections -->
                <div class="widget-card animate-fade-in animate-delay-300">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-clock text-warning"></i>Recently Pending
                        </h6>
                    </div>
                    <div class="widget-body p-0">
                        @if(isset($dashboard_data['pending_collections']) && count($dashboard_data['pending_collections']) > 0)
                            <div class="list-group list-group-flush">
                                @foreach(collect($dashboard_data['pending_collections'])->take(5) as $pay)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold small">{{ $pay->student->name ?? 'Unknown' }}</div>
                                            <div class="text-xs text-muted">{{ ($pay->payment_date ?? $pay->due_date)?->format('d M, Y') ?? 'N/A' }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-danger">₹{{ number_format(method_exists($pay, 'getRemainingAmount') ? $pay->getRemainingAmount() : ($pay->amount ?? 0)) }}</div>
                                            <span class="badge badge-warning text-xs">Pending</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if(count($dashboard_data['pending_collections']) > 5)
                                <div class="p-2 text-center border-top">
                                    <a href="{{ route('admin.component-payments.index', ['status' => 'pending']) }}" class="small">
                                        View All ({{ count($dashboard_data['pending_collections']) - 5 }} more)
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <div class="small text-muted">No pending collections!</div>
                            </div>
                        @endif
                    </div>
                </div>
@php
    // Real payment data instead of test data
    $user = auth()->user();

    // Get payment modes from last 30 days
    $paymentModes = \App\Models\Payment::where('payment_type', 'component')
        ->where('created_by', $user->id)
        ->whereBetween('payment_date', [now()->subDays(30), now()])
        ->selectRaw('payment_method, SUM(amount) as total')
        ->groupBy('payment_method')
        ->pluck('total', 'payment_method')
        ->toArray();

    // Set defaults for all payment methods
    $defaultModes = ['cash' => 0, 'online' => 0, 'card' => 0, 'upi' => 0];
    $paymentModes = array_merge($defaultModes, $paymentModes);

    $dashboard_data['payment_modes'] = [
        'labels' => ['Cash', 'Online', 'Card', 'UPI'],
        'values' => [
            (float) $paymentModes['cash'],
            (float) $paymentModes['online'],
            (float) $paymentModes['card'],
            (float) $paymentModes['upi'],
        ]
    ];
@endphp
