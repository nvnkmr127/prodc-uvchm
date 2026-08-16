            {{-- Enhanced Apply Concession Modal - REPLACE the existing one --}}
            <div class="modal fade" id="applyConcessionModal" tabindex="-1" role="dialog" aria-labelledby="applyConcessionModalLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.2);">
                        <div class="modal-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #8b4513; border-radius: 20px 20px 0 0; border: none;">
                            <h5 id="applyConcessionModalLabel" class="modal-title font-weight-bold">
                                <i class="fas fa-percent mr-2"></i>
                                Apply Concession - {{ $student->name }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" style="color: #8b4513; opacity: 0.8;" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            {{-- Gender-based Concession Info --}}
                            @if($student->gender === 'Female' && setting('womens_discount_percentage', 0) > 0)
                                <div class="alert alert-info" style="border-radius: 10px; background: linear-gradient(135deg, #e3f2fd 0%, #f1f8e9 100%); border: none;">
                                    <i class="fas fa-female mr-2 text-success"></i>
                                    <strong>Gender Concession Available:</strong> This student is eligible for automatic 
                                    {{ setting('womens_discount_percentage') }}% gender-based discount.
                                    <button type="button" class="btn btn-sm btn-success ml-2" onclick="applyAutomaticGenderConcession()" style="border-radius: 15px;">
                                        <i class="fas fa-magic"></i> Apply Auto Discount
                                    </button>
                                </div>
                            @endif

                            <form id="concessionForm" action="{{ url('admin/students/' . $student->id . '/apply-concession') }}" method="POST">
                                @csrf

                                <div class="form-group">
                                    <label for="concessionComponentSelect" class="font-weight-bold">Fee Component *</label>
                                    <select name="student_fee_id" id="concessionComponentSelect" class="form-control" required style="border-radius: 10px;">
                                        <option value="">-- Select Fee Component --</option>
                                        @if(isset($student) && $student->studentFees)
                                            @foreach($student->studentFees->whereIn('status', ['unpaid', 'partial']) as $fee)
                                                @php 
                                                    $remaining = $fee->amount - $fee->paid_amount - $fee->concession_amount; 
                                                @endphp
                                                @if($remaining > 0)
                                                    <option value="{{ $fee->id }}" data-remaining="{{ $remaining }}">
                                                        {{ $fee->feeCategory->name }} 
                                                        (Remaining: {{ setting('currency_symbol', '₹') }}{{ number_format($remaining, 2) }})
                                                    </option>
                                                @endif
                                            @endforeach
                                        @endif
                                    </select>
                                    <small class="form-text text-muted">Only components with outstanding balance are shown</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="concession_amount" class="font-weight-bold">Concession Amount *</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" style="background: #f8f9fc; border-color: #e3e6f0;">{{ setting('currency_symbol', '₹') }}</span>
                                                </div>
                                                <input type="number" step="0.01" name="concession_amount" id="concession_amount" 
                                                       class="form-control" required min="0.01" max="" style="border-radius: 0 10px 10px 0;">
                                            </div>
                                            <small class="text-muted" id="concession_amount_hint">Enter the concession amount</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-muted font-weight-bold">Quick Amounts</label>
                                        <div class="btn-group-vertical d-block" id="quickAmountButtons">
                                            {{-- Dynamic buttons will be inserted here --}}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="concession_reason" class="font-weight-bold">Reason for Concession</label>
                                    <textarea name="reason" id="concession_reason" class="form-control" rows="3" 
                                              style="border-radius: 10px;"
                                              placeholder="e.g., Merit scholarship, Financial hardship, Staff discount, Early payment discount"></textarea>
                                    <small class="form-text text-muted">Provide a brief explanation for this concession</small>
                                </div>

                                <div class="alert alert-warning" style="border-radius: 10px; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: none;">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Important:</strong> This concession will be applied immediately and cannot be undone from this interface.
                                    Please ensure the amount and reason are correct.
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer" style="border: none; background: #f8f9fc; border-radius: 0 0 20px 20px;">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 20px;">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" form="concessionForm" class="btn btn-warning" id="applyConcessionBtn" style="border-radius: 20px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border: none; color: #8b4513; font-weight: bold;">
                                <i class="fas fa-percent"></i> Apply Concession
                            </button>
                        </div>
                    </div>
                </div>
            </div>


