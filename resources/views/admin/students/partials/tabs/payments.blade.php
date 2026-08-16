                                <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0 text-primary">
                                            <i class="fas fa-credit-card mr-2"></i> Payment History
                                        </h5>
                                        <div class="btn-group">
                                            <button type="button" class="action-btn btn-success-modern btn-sm" onclick="openPaymentModal()">
                                                <i class="fas fa-plus mr-2"></i> Record Payment
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshPaymentHistory()">
                                                <i class="fas fa-sync"></i> Refresh
                                            </button>
                                        </div>
                                    </div>

                                    @if($paymentHistory->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="payment-history-table">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th scope="col"><i class="fas fa-receipt mr-2"></i>Receipt #</th>
                                                        <th scope="col"><i class="fas fa-calendar mr-2"></i>Date</th>
                                                        <th scope="col"><i class="fas fa-rupee-sign mr-2"></i>Amount</th>
                                                        <th scope="col"><i class="fas fa-credit-card mr-2"></i>Method</th>
                                                        <th scope="col"><i class="fas fa-list mr-2"></i>Components</th>
                                                        <th scope="col"><i class="fas fa-user mr-2"></i>Created By</th>
                                                        <th scope="col"><i class="fas fa-info-circle mr-2"></i>Status</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($paymentHistory as $payment)
                                                        <tr data-payment-id="{{ $payment->id }}">
                                                            <td>
                                                                <strong
                                                                    class="text-primary">{{ $payment->receipt_number ?? 'N/A' }}</strong>
                                                                @if($payment->transaction_id ?? null)
                                                                    <small class="text-muted d-block">TXN:
                                                                        {{ $payment->transaction_id }}</small>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="font-weight-bold">{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : 'N/A' }}</span>
                                                                <small
                                                                    class="text-muted d-block">{{ $payment->created_at ? $payment->created_at->format('H:i A') : '' }}</small>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-success badge-lg"
                                                                    style="font-size: 0.9em; padding: 0.5em 0.8em;">
                                                                    ₹{{ number_format($payment->amount ?? 0, 2) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-info">
                                                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'Unknown')) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->componentItems) && $payment->componentItems->count() > 0)
                                                                    <div class="component-breakdown" style="max-width: 200px;">
                                                                        @foreach($payment->componentItems as $item)
                                                                            <small class="d-block" style="line-height: 1.3; margin-bottom: 2px;">
                                                                                <strong>{{ optional($item->studentFee)->feeCategory->name ?? 'Unknown' }}:</strong>
                                                                                ₹{{ number_format($item->amount_paid ?? 0, 2) }}
                                                                            </small>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <span class="text-muted">No components</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->createdBy) && $payment->createdBy)
                                                                    <div class="user-info" style="min-width: 120px;">
                                                                        <strong>{{ $payment->createdBy->name }}</strong>
                                                                        <small class="text-muted d-block">
                                                                            {{ $payment->created_at ? $payment->created_at->diffForHumans() : 'Unknown time' }}
                                                                        </small>
                                                                    </div>
                                                                @else
                                                                    <span class="text-muted">System</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @php
        $statusClass = match ($payment->status ?? 'completed') {
            'completed' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'refunded' => 'info',
            default => 'secondary'
        };
                                                                @endphp
                                                                <span class="badge badge-{{ $statusClass }}">
                                                                    {{ ucfirst($payment->status ?? 'completed') }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    {{-- Edit Button --}}
                                                                    @can('edit payments')
                                                                        @if(method_exists($payment, 'canBeEdited') && $payment->canBeEdited())
                                                                            <a href="{{ route('payment-edit.edit', $payment) }}"
                                                                                class="btn btn-outline-warning btn-sm" title="Edit Payment">
                                                                                <i class="fas fa-edit"></i>
                                                                            </a>
                                                                        @elseif(!method_exists($payment, 'canBeEdited'))
                                                                            <a href="{{ route('payment-edit.edit', $payment) }}"
                                                                                class="btn btn-outline-warning btn-sm" title="Edit Payment">
                                                                                <i class="fas fa-edit"></i>
                                                                            </a>
                                                                        @endif
                                                                    @endcan

                                                                    {{-- Receipt View Button --}}
                                                                    @if($payment->receipt_number ?? null)
                                                                        @if(Route::has('admin.payments.receipt'))
                                                                            <a href="{{ route('admin.payments.receipt', [$student, $payment]) }}"
                                                                                class="btn btn-outline-primary btn-sm" title="View Receipt"
                                                                                target="_blank" rel="noopener noreferrer">
                                                                                <i class="fas fa-receipt"></i>
                                                                            </a>
                                                                        @endif
                                                                    @endif

                                                                    {{-- PDF Download Button --}}
                                                                    @if($payment->receipt_number ?? null)
                                                                        @if(Route::has('admin.payments.receipt.pdf'))
                                                                            <a href="{{ route('admin.payments.receipt.pdf', [$student, $payment]) }}"
                                                                                class="btn btn-outline-success btn-sm" title="Download PDF">
                                                                                <i class="fas fa-download"></i>
                                                                            </a>
                                                                        @endif
                                                                    @endif

                                                                    {{-- View Details Button --}}
                                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                        onclick="viewPaymentDetails({{ $payment->id }})"
                                                                        title="View Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>

                                                                    {{-- Edit History Button --}}
                                                                    @can('view payment history')
                                                                        <a href="{{ route('payment-edit.history', $payment) }}"
                                                                            class="btn btn-outline-info btn-sm" title="View Edit History">
                                                                            <i class="fas fa-history"></i>
                                                                        </a>
                                                                    @endcan

                                                                    {{-- Notes Button --}}
                                                                    @if($payment->notes ?? null)
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                            title="{{ $payment->notes }}" data-toggle="tooltip"
                                                                            data-placement="top" data-html="true"
                                                                            data-original-title="{{ $payment->notes }}">
                                                                            <i class="fas fa-sticky-note"></i>
                                                                        </button>
                                                                    @endif


                                                                    {{-- Manual Webhook Trigger --}}
                                                                    @if(Route::has('admin.payments.webhook'))
                                                                        @can('edit payments')
                                                                            <form action="{{ route('admin.payments.webhook', $payment->id) }}" method="POST" class="d-inline"
                                                                                onsubmit="return confirm('Are you sure you want to resend the webhook for this payment?');">
                                                                                @csrf
                                                                                <button type="submit" class="btn btn-outline-dark btn-sm" title="Send Payment Webhook">
                                                                                    <i class="fas fa-satellite-dish"></i>
                                                                                </button>
                                                                            </form>
                                                                        @endcan
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Payment Summary Card --}}
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-3 text-center">
                                                                <h6 class="text-muted">Total Payments</h6>
                                                                <h4 class="text-primary">{{ $paymentHistory->count() }}</h4>
                                                            </div>
                                                            <div class="col-md-3 text-center">
                                                                <h6 class="text-muted">Total Paid</h6>
                                                                <h4 class="text-success">
                                                                    ₹{{ number_format($paymentHistory->sum('amount'), 2) }}</h4>
                                                            </div>
                                                            <div class="col-md-3 text-center">
                                                                <h6 class="text-muted">Last Payment</h6>
                                                                <h4 class="text-info">
                                                                    {{ $paymentHistory->first() && $paymentHistory->first()->payment_date ? $paymentHistory->first()->payment_date->format('d M Y') : 'Never' }}
                                                                </h4>
                                                            </div>
                                                            <div class="col-md-3 text-center">
                                                                <h6 class="text-muted">Payment Methods</h6>
                                                                <div class="method-badges">
                                                                    @foreach($paymentHistory->pluck('payment_method')->unique() as $method)
                                                                        <span class="badge badge-secondary"
                                                                            style="margin: 2px; font-size: 0.7em;">{{ ucfirst($method ?? 'Unknown') }}</span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Quick Actions for Payments --}}
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="card border-0">
                                                    <div class="card-body bg-light rounded">
                                                        <h6 class="text-muted mb-3">
                                                            <i class="fas fa-tools mr-2"></i>Quick Actions
                                                        </h6>
                                                        <div class="btn-group flex-wrap" role="group">
                                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                                onclick="exportPaymentHistory()">
                                                                <i class="fas fa-download mr-1"></i> Export History
                                                            </button>
                                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="printPaymentHistory()">
                                                                <i class="fas fa-print mr-1"></i> Print History
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="filterPayments()">
                                                                <i class="fas fa-filter mr-1"></i> Filter Payments
                                                            </button>
                                                            @if(Route::has('admin.payments.component-dashboard'))
                                                                <a href="{{ route('admin.payments.component-dashboard', $student) }}"
                                                                    class="btn btn-outline-success btn-sm">
                                                                    <i class="fas fa-chart-bar mr-1"></i> Payment Dashboard
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No Payment History</h6>
                                            <p class="text-muted">No payments have been recorded for this student yet.</p>
                                            <button type="button" class="action-btn btn-success-modern" onclick="openPaymentModal()">
                                                <i class="fas fa-plus mr-2"></i> Record First Payment
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                {{-- Enhanced Attendance Tab --}}
