                                <div class="tab-pane fade" id="fees" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0 text-primary">
                                            <i class="fas fa-file-invoice-dollar mr-2"></i> Fee Components
                                        </h5>
                                        <div class="btn-group">
                                            <button class="action-btn btn-success-modern btn-sm" onclick="openPaymentModal()">
                                                <i class="fas fa-plus mr-2"></i> Record Payment
                                            </button>
                                            <button class="btn btn-primary btn-sm" data-toggle="modal"
                                                data-target="#addFeeComponentModal">
                                                <i class="fas fa-plus-circle mr-2"></i> Add Fee Component
                                            </button>
                                        </div>
                                    </div>

                                    @if($studentFees->count() > 0)
                                        @foreach($studentFees as $studentFee)
                                            @php
        $paidAmount = $studentFee->paid_amount ?? 0;
        $concessionAmount = $studentFee->concession_amount ?? 0;
        $totalAmount = $studentFee->amount ?? 0;
        $remainingAmount = $totalAmount - $paidAmount - $concessionAmount;

        $paymentPercentage = ($totalAmount > 0) ?
            round((($paidAmount + $concessionAmount) / $totalAmount) * 100, 1) : 0;

        if ($remainingAmount <= 0) {
            $statusClass = 'success';
            $statusText = 'Fully Paid';
            $progressClass = 'bg-success';
        } elseif ($paidAmount > 0 || $concessionAmount > 0) {
            $statusClass = 'warning';
            $statusText = 'Partially Paid';
            $progressClass = 'bg-warning';
        } else {
            $statusClass = 'danger';
            $statusText = 'Unpaid';
            $progressClass = 'bg-danger';
        }
                                            @endphp

                                            <div class="fee-component-card">
                                                <div class="fee-component-header">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6">
                                                            <h6 class="font-weight-bold mb-1">
                                                                <i class="fas fa-file-invoice mr-2 text-primary"></i>
                                                                {{ optional($studentFee->feeCategory)->name ?? 'Unknown Category' }}
                                                                @if($studentFee->total_installments > 1)
                                                                    <span class="badge badge-light border ml-2">
                                                                        Installment {{ $studentFee->installment_number }}/{{ $studentFee->total_installments }}
                                                                    </span>
                                                                @endif
                                                            </h6>
                                                            <small
                                                                class="text-muted">{{ optional($studentFee->feeCategory)->description ?? 'Standard fee component' }}</small>
                                                        </div>
                                                        <div class="col-md-3 text-center">
                                                            <div class="h5 font-weight-bold text-primary">
                                                                ₹{{ number_format($totalAmount, 0) }}</div>
                                                            <small class="text-muted">Total Amount</small>
                                                        </div>
                                                        <div class="col-md-3 text-center">
                                                            <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                                                            @if($concessionAmount > 0)
                                                                <br><small class="text-success mt-1">
                                                                    <i class="fas fa-percent"></i> ₹{{ number_format($concessionAmount, 0) }}
                                                                    concession
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <div class="mb-3">
                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                    <small class="font-weight-bold">Payment Progress</small>
                                                                    <small class="text-muted">{{ $paymentPercentage }}% completed</small>
                                                                </div>
                                                                <div class="payment-progress">
                                                                    <div class="payment-progress-bar {{ $progressClass }}"
                                                                        style="width: {{ $paymentPercentage }}%"></div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <small class="text-muted d-block">Paid</small>
                                                                    <span
                                                                        class="font-weight-bold text-success">₹{{ number_format($paidAmount, 0) }}</span>
                                                                </div>
                                                                <div class="col-4">
                                                                    <small class="text-muted d-block">Concession</small>
                                                                    <span
                                                                        class="font-weight-bold text-info">₹{{ number_format($concessionAmount, 0) }}</span>
                                                                </div>
                                                                <div class="col-4">
                                                                    <small class="text-muted d-block">Due</small>
                                                                    <span
                                                                        class="font-weight-bold text-danger">₹{{ number_format(max(0, $remainingAmount), 0) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 text-right">
                                                            @if($remainingAmount > 0)
                                                                <button class="btn btn-success btn-sm mb-2"
                                                                    onclick="openPaymentModal({{ $studentFee->id }}, '{{ optional($studentFee->feeCategory)->name ?? 'Unknown' }}', {{ $remainingAmount }})">
                                                                    <i class="fas fa-credit-card mr-1"></i> Pay
                                                                    ₹{{ number_format($remainingAmount, 0) }}
                                                                </button>
                                                                <br>
                                                                <button class="btn btn-warning btn-sm"
                                                                    onclick="openConcessionModal({{ $studentFee->id }}, '{{ optional($studentFee->feeCategory)->name ?? 'Unknown' }}', {{ $remainingAmount }})">
                                                                    <i class="fas fa-percent mr-1"></i> Apply Concession
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No Fee Components Assigned</h6>
                                            <p class="text-muted">This student doesn't have any fee components assigned yet.</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Payment History Tab --}}
