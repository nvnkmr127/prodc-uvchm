<!-- Commission Payment Modal -->
<div class="modal fade" id="paymentCommissionModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentCommissionModalLabel">Record Commission / Reward Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="small text-muted">Referral For Student:</div>
                    <div class="font-weight-bold h5 text-gray-800" id="modalStudentName"></div>
                    <div class="small text-muted mt-1">Referred By: <span class="text-primary font-weight-bold"
                            id="modalReferralName"></span></div>
                </div>

                <form id="commissionForm">
                    <div class="form-group">
                        <label for="amount" class="font-weight-bold">Commission Amount <span
                                class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">₹</span>
                            </div>
                            <input type="number" class="form-control" name="amount" id="amount"
                                placeholder="Enter amount" required min="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_mode" class="font-weight-bold">Payment Mode <span
                                class="text-danger">*</span></label>
                        <select class="form-control" name="payment_mode" id="payment_mode" required>
                            <option value="">Select Mode</option>
                            <option value="Cash">Cash</option>
                            <option value="Online">Online / UPI</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Fee Discount" class="d-none" id="optionFeeDiscount">Fee Discount (Reward)
                            </option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="remarks" class="font-weight-bold">Remarks / Transaction ID</label>
                        <textarea class="form-control" name="remarks" id="remarks" rows="2"
                            placeholder="Optional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCommissionBtn"
                    onclick="submitCommissionPayment()">
                    <i class="fas fa-check mr-1"></i> Save Payment
                </button>
            </div>
        </div>
    </div>
</div>