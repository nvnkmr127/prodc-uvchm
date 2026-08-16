            {{-- Payment Filter Modal --}}
            <div class="modal fade" id="paymentFilterModal" tabindex="-1" role="dialog" aria-labelledby="paymentFilterModalLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="paymentFilterModalLabel" class="modal-title">
                                <i class="fas fa-filter mr-2"></i>Filter Payment History
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="paymentFilterForm">
                                <div class="form-group">
                                    <label>Payment Method</label>
                                    <select class="form-control" id="filterMethod">
                                        <option value="">All Methods</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="upi">UPI</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="online">Online</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date Range</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="date" class="form-control" id="filterStartDate" placeholder="Start Date">
                                        </div>
                                        <div class="col-6">
                                            <input type="date" class="form-control" id="filterEndDate" placeholder="End Date">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Amount Range</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="number" class="form-control" id="filterMinAmount" placeholder="Min Amount">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control" id="filterMaxAmount" placeholder="Max Amount">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Created By</label>
                                    <select class="form-control" id="filterCreatedBy">
                                        <option value="">All Users</option>
                                        @if($paymentHistory->count() > 0)
                                            @foreach($paymentHistory->pluck('createdBy.name')->unique()->filter() as $creator)
                                                <option value="{{ $creator }}">{{ $creator }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="applyPaymentFilter()">Apply Filter</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearPaymentFilter()">Clear</button>
                        </div>
                    </div>
                </div>
            </div>


