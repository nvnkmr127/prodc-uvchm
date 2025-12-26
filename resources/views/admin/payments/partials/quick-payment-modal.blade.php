{{-- resources/views/admin/payments/partials/quick-payment-modal.blade.php --}}
<div class="modal fade" id="quickPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-success text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card mr-2"></i>Quick Payment
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="quickPaymentForm">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                    <input type="hidden" name="student_fee_id" id="modal_student_fee_id">
                    
                    <!-- Student Info Card -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Student</h6>
                                    <p class="mb-0 font-weight-bold">{{ $student->name }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Component</h6>
                                    <p class="mb-0 font-weight-bold" id="modal_component_name">-</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Enrollment</h6>
                                    <p class="mb-0">{{ $student->enrollment_number }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Remaining Amount</h6>
                                    <p class="mb-0 text-danger font-weight-bold">
                                        {{ setting('currency_symbol','₹') }} <span id="modal_remaining_amount">0.00</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Form -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_amount" class="font-weight-bold">
                                    Amount <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light">{{ setting('currency_symbol','₹') }}</span>
                                    </div>
                                    <input type="number" step="0.01" name="amount" id="modal_amount" 
                                           class="form-control form-control-lg" required 
                                           placeholder="0.00">
                                </div>
                                <small class="text-muted" id="modal_amount_hint">Enter amount to pay</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_payment_method" class="font-weight-bold">
                                    Payment Method <span class="text-danger">*</span>
                                </label>
                                <select name="payment_method" id="modal_payment_method" 
                                        class="form-control form-control-lg" required>
                                    <option value="">Select Method</option>
                                    <option value="cash">💵 Cash</option>
                                    <option value="card">💳 Card</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                    <option value="cheque">📝 Cheque</option>
                                    <option value="online">🌐 Online Payment</option>
                                    <option value="upi">📱 UPI</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_payment_date" class="font-weight-bold">
                                    Payment Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="payment_date" id="modal_payment_date" 
                                       class="form-control form-control-lg" 
                                       value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_transaction_id" class="font-weight-bold">
                                    Transaction ID
                                </label>
                                <input type="text" name="transaction_id" id="modal_transaction_id" 
                                       class="form-control form-control-lg" 
                                       placeholder="Optional reference number">
                                <small class="text-muted">For digital payments</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_notes" class="font-weight-bold">Notes</label>
                        <textarea name="notes" id="modal_notes" class="form-control" rows="3" 
                                  placeholder="Add any additional notes (optional)"></textarea>
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div class="text-center mt-4" id="quickAmountSection" style="display: none;">
                        <h6 class="text-muted mb-3">Quick Amounts</h6>
                        <div class="btn-group flex-wrap" id="quickAmountButtons">
                            <!-- Dynamic buttons will be added here -->
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light btn-lg px-4" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-lg px-4" id="submitPaymentBtn">
                        <i class="fas fa-check mr-2"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Enhanced quick payment modal functionality
$('#quickPaymentModal').on('shown.bs.modal', function() {
    $('#modal_amount').focus();
    generateQuickAmountButtons();
});

function generateQuickAmountButtons() {
    const remainingAmount = parseFloat($('#modal_remaining_amount').text().replace(/,/g, ''));
    if (!remainingAmount || remainingAmount <= 0) return;
    
    const percentages = [25, 50, 75, 100];
    let buttonsHtml = '';
    
    percentages.forEach(percentage => {
        const amount = (remainingAmount * percentage) / 100;
        if (amount > 0) {
            buttonsHtml += `
                <button type="button" class="btn btn-outline-primary btn-sm m-1" 
                        onclick="setQuickAmount(${amount})">
                    ${percentage}% (₹${amount.toLocaleString()})
                </button>
            `;
        }
    });
    
    if (buttonsHtml) {
        $('#quickAmountButtons').html(buttonsHtml);
        $('#quickAmountSection').show();
    } else {
        $('#quickAmountSection').hide();
    }
}

function setQuickAmount(amount) {
    $('#modal_amount').val(amount.toFixed(2));
    $('#modal_amount').trigger('input');
}

// Enhanced amount validation
$('#modal_amount').on('input', function() {
    const amount = parseFloat($(this).val());
    const maxAmount = parseFloat($('#modal_remaining_amount').text().replace(/,/g, ''));
    const hint = $('#modal_amount_hint');
    
    if (isNaN(amount) || amount <= 0) {
        hint.text('Enter a valid amount').removeClass('text-success text-danger').addClass('text-muted');
        return;
    }
    
    if (amount > maxAmount) {
        $(this).val(maxAmount.toFixed(2));
        hint.text('Amount adjusted to maximum balance').removeClass('text-muted text-success').addClass('text-danger');
    } else {
        hint.text(`Amount: ₹${amount.toLocaleString()}`).removeClass('text-muted text-danger').addClass('text-success');
    }
});

// Payment method change handler
$('#modal_payment_method').on('change', function() {
    const method = $(this).val();
    const transactionField = $('#modal_transaction_id');
    const transactionLabel = $('label[for="modal_transaction_id"]');
    
    if (['cash'].includes(method)) {
        transactionField.attr('placeholder', 'Cash receipt number (optional)');
        transactionLabel.html('Receipt Number');
    } else if (['card', 'online', 'upi'].includes(method)) {
        transactionField.attr('placeholder', 'Transaction ID or reference');
        transactionLabel.html('Transaction ID <span class="text-primary">*</span>');
    } else if (method === 'cheque') {
        transactionField.attr('placeholder', 'Cheque number');
        transactionLabel.html('Cheque Number');
    } else {
        transactionField.attr('placeholder', 'Reference number');
        transactionLabel.html('Transaction ID');
    }
});

// Reset modal on close
$('#quickPaymentModal').on('hidden.bs.modal', function() {
    $('#quickPaymentForm')[0].reset();
    $('#modal_amount_hint').text('Enter amount to pay').removeClass('text-success text-danger').addClass('text-muted');
    $('#quickAmountSection').hide();
});
</script>