            {{-- Enhanced Payment Recording Modal --}}
            <div class="modal fade payment-modal" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="paymentModalLabel">
                                    <i class="fas fa-credit-card mr-2"></i> Record Payment for {{ $student->name }}
                                </h5>
                                <small class="text-light opacity-75">{{ $student->enrollment_number }} • {{ $student->batch->name ?? 'N/A' }}</small>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="paymentForm" action="{{ route('admin.component-payments.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">

                                {{-- Payment Details Section --}}
                                <div class="payment-form-section">
                                    <h6 class="font-weight-bold text-primary mb-3">
                                        <i class="fas fa-file-invoice-dollar mr-2"></i> Payment Details
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment_amount" class="font-weight-bold">Payment Amount *</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">₹</span>
                                                    </div>
                                                    <input type="number" step="0.01" name="total_amount" id="payment_amount" 
                                                           class="form-control form-control-lg" required min="0.01" placeholder="0.00">
                                                </div>
                                                <small class="text-muted">Enter the total payment amount</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment_method" class="font-weight-bold">Payment Method *</label>
                                                <select name="payment_method" id="payment_method" class="form-control form-control-lg" required>
                                                    <option value="">Select Method</option>
                                                    <option value="cash">Cash</option>
                                                    <option value="card">Card</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                    <option value="upi">UPI</option>
                                                    <option value="cheque">Cheque</option>
                                                    <option value="online">Online</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment_date" class="font-weight-bold">Payment Date *</label>
                                                <input type="date" name="payment_date" id="payment_date" 
                                                       class="form-control form-control-lg" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="transaction_id" class="font-weight-bold">Transaction Reference</label>
                                                <input type="text" name="transaction_id" id="transaction_id" 
                                                       class="form-control" placeholder="Transaction ID / Reference Number">
                                                <small class="text-muted">Optional for cash payments</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="notes" class="font-weight-bold">Notes</label>
                                                <textarea name="notes" id="notes" class="form-control" rows="2" 
                                                          placeholder="Additional notes or comments"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Fee Components Selection --}}
                                <div class="payment-form-section">
                                    <h6 class="font-weight-bold text-primary mb-3">
                                        <i class="fas fa-list-check mr-2"></i> Allocate Payment to Components
                                    </h6>
                                    <div id="fee-components-list">
                                        @if(isset($studentFees) && $studentFees->count() > 0)
                                            @foreach($studentFees as $studentFee)
                                                @php
        $dueAmount = $studentFee->amount - $studentFee->paid_amount;
                                                @endphp
                                                @if($dueAmount > 0)
                                                    <div class="payment-component-item" data-fee-id="{{ $studentFee->id }}" data-max-amount="{{ $dueAmount }}">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-1">
                                                                <div class="custom-control custom-checkbox">
                                                                    <input type="checkbox" class="custom-control-input component-checkbox" 
                                                                           id="component_{{ $studentFee->id }}" name="components[{{ $studentFee->id }}][selected]" value="1">
                                                                    <label class="custom-control-label" for="component_{{ $studentFee->id }}"></label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <div class="font-weight-bold">{{ $studentFee->feeCategory->name }}</div>
                                                                <small class="text-muted">{{ $studentFee->feeCategory->description ?? 'Standard fee component' }}</small>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="text-center">
                                                                    <div class="font-weight-bold text-primary">₹{{ number_format((float) $studentFee->amount, 0) }}</div>
                                                                    <small class="text-muted">Total Fee</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group mb-0">
                                                                    <label class="small font-weight-bold">Payment Amount</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">₹</span>
                                                                        </div>
                                                                        <input type="number" step="0.01" 
                                                                               name="components[{{ $studentFee->id }}][amount]" 
                                                                               class="form-control component-amount" 
                                                                               placeholder="0.00" min="0" max="{{ $dueAmount }}" disabled>
                                                                    </div>
                                                                    <small class="text-muted">Max: ₹{{ number_format($dueAmount, 0) }}</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="text-center py-4">
                                                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                                <h6>No Fee Components Found</h6>
                                                <p class="text-muted">This student doesn't have any pending fee components.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Payment Summary --}}
                                <div class="payment-summary">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="font-weight-bold mb-2">
                                                <i class="fas fa-calculator mr-2"></i> Payment Summary
                                            </h6>
                                            <div id="payment-validation-message" class="text-muted">
                                                Enter payment amount and select components to allocate the payment.
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <div class="row">
                                                <div class="col-12 mb-2">
                                                    <small class="text-muted d-block">Payment Amount</small>
                                                    <div class="h5 font-weight-bold text-primary" id="payment-total-display">₹0.00</div>
                                                </div>
                                                <div class="col-12">
                                                    <small class="text-muted d-block">Allocated Amount</small>
                                                    <div class="h5 font-weight-bold text-success" id="allocated-total-display">₹0.00</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </button>
                            <button type="submit" form="paymentForm" class="action-btn btn-success-modern" id="submit-payment-btn" disabled>
                                <i class="fas fa-credit-card mr-2"></i> Record Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>

