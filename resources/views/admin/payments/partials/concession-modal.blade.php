{{-- resources/views/admin/payments/partials/concession-modal.blade.php --}}
<div class="modal fade" id="applyConcessionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-warning text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-percent mr-2"></i>Apply Concession
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="concessionForm" action="{{ url('admin/students/' . $student->id . '/apply-concession') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    
                    <!-- Student Info -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="text-muted mb-1">Student</h6>
                                    <p class="mb-0 font-weight-bold">{{ $student->name }}</p>
                                    <small class="text-muted">{{ $student->enrollment_number }}</small>
                                </div>
                                <div class="col-md-4 text-right">
                                    @if($student->gender === 'Female' && setting('womens_discount_percentage', 0) > 0)
                                        <span class="badge badge-info">
                                            <i class="fas fa-female mr-1"></i>
                                            {{ setting('womens_discount_percentage') }}% Eligible
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gender-based Auto Concession -->
                    @if($student->gender === 'Female' && setting('womens_discount_percentage', 0) > 0)
                        <div class="alert alert-info border-0 mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <i class="fas fa-magic mr-2"></i>Automatic Gender-Based Discount
                                    </h6>
                                    <p class="mb-0">Apply {{ setting('womens_discount_percentage') }}% discount to all eligible components automatically.</p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button type="button" class="btn btn-success btn-sm" onclick="applyAutomaticGenderConcession()">
                                        <i class="fas fa-magic mr-1"></i>Apply Auto Discount
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Manual Concession Form -->
                    <h6 class="mb-3">Manual Concession Application</h6>
                    
                    <div class="form-group">
                        <label for="concessionComponentSelect" class="font-weight-bold">
                            Fee Component <span class="text-danger">*</span>
                        </label>
                        <select name="student_fee_id" id="concessionComponentSelect" 
                                class="form-control form-control-lg" required>
                            <option value="">-- Select Fee Component --</option>
                            @if(isset($student) && $student->studentFees)
                                @foreach($student->studentFees->whereIn('status', ['unpaid', 'partial']) as $fee)
                                    @php 
                                        $remaining = $fee->amount - $fee->paid_amount - $fee->concession_amount; 
                                    @endphp
                                    @if($remaining > 0)
                                        <option value="{{ $fee->id }}" 
                                                data-remaining="{{ $remaining }}"
                                                data-category="{{ $fee->feeCategory->name }}">
                                            {{ $fee->feeCategory->name }} 
                                            (Balance: {{ setting('currency_symbol', '₹') }}{{ number_format($remaining, 2) }})
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
                                <label for="concession_amount" class="font-weight-bold">
                                    Concession Amount <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light">{{ setting('currency_symbol', '₹') }}</span>
                                    </div>
                                    <input type="number" step="0.01" name="concession_amount" 
                                           id="concession_amount" class="form-control form-control-lg" 
                                           required min="0.01" placeholder="0.00">
                                </div>
                                <small class="text-muted" id="concession_amount_hint">Enter the concession amount</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="font-weight-bold text-muted">Quick Percentages</label>
                            <div class="d-flex flex-column" id="quickConcessionButtons">
                                <!-- Dynamic buttons will be inserted here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="concession_reason" class="font-weight-bold">Reason for Concession</label>
                        <textarea name="reason" id="concession_reason" class="form-control" rows="3" 
                                  placeholder="e.g., Merit scholarship, Financial hardship, Staff discount, Early payment discount"></textarea>
                        <small class="form-text text-muted">Provide a brief explanation for this concession</small>
                    </div>

                    <!-- Concession Preview -->
                    <div class="card bg-light border-0" id="concessionPreview" style="display: none;">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Concession Preview</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Component</small>
                                    <p class="mb-0 font-weight-bold" id="preview_component">-</p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Current Balance</small>
                                    <p class="mb-0 font-weight-bold" id="preview_current">₹0.00</p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">After Concession</small>
                                    <p class="mb-0 font-weight-bold text-success" id="preview_after">₹0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning border-0 mt-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Important:</strong> This concession will be applied immediately and cannot be easily reversed.
                    </div>
                </div>
                
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light btn-lg px-4" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning btn-lg px-4" id="applyConcessionBtn" disabled>
                        <i class="fas fa-percent mr-2"></i>Apply Concession
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Enhanced Concession Modal JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const concessionComponentSelect = document.getElementById('concessionComponentSelect');
    const concessionAmountInput = document.getElementById('concession_amount');
    const concessionAmountHint = document.getElementById('concession_amount_hint');
    const applyConcessionBtn = document.getElementById('applyConcessionBtn');
    const quickConcessionButtons = document.getElementById('quickConcessionButtons');
    const concessionPreview = document.getElementById('concessionPreview');

    let currentSelectedFee = null;

    function updateConcessionInput() {
        const selectedOption = concessionComponentSelect.options[concessionComponentSelect.selectedIndex];
        const remainingAmount = selectedOption.getAttribute('data-remaining');
        const categoryName = selectedOption.getAttribute('data-category');

        if (remainingAmount && parseFloat(remainingAmount) > 0) {
            const maxAmount = parseFloat(remainingAmount);
            currentSelectedFee = {
                id: selectedOption.value,
                name: categoryName || selectedOption.text.split('(')[0].trim(),
                remaining: maxAmount
            };

            concessionAmountInput.max = maxAmount;
            concessionAmountHint.innerHTML = `Maximum: {{ setting('currency_symbol', '₹') }}${maxAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
            concessionAmountInput.disabled = false;
            applyConcessionBtn.disabled = false;

            // Generate quick percentage buttons
            generateQuickConcessionButtons(maxAmount);
            
            // Update preview
            updatePreview();
        } else {
            currentSelectedFee = null;
            concessionAmountInput.max = '';
            concessionAmountInput.value = '';
            concessionAmountHint.textContent = 'Select a fee component first';
            concessionAmountInput.disabled = true;
            applyConcessionBtn.disabled = true;
            quickConcessionButtons.innerHTML = '';
            concessionPreview.style.display = 'none';
        }
    }

    function generateQuickConcessionButtons(maxAmount) {
        const percentages = [10, 25, 50, 75, 100];
        let buttonsHtml = '';

        percentages.forEach(percentage => {
            const amount = (maxAmount * percentage) / 100;
            if (amount <= maxAmount) {
                buttonsHtml += `
                    <button type="button" class="btn btn-outline-warning btn-sm mb-1" 
                            onclick="setQuickConcessionAmount(${amount})">
                        ${percentage}% (₹${amount.toLocaleString('en-IN', {minimumFractionDigits: 0})})
                    </button>
                `;
            }
        });

        // Add gender-based amount if applicable
        @if($student->gender === 'Female' && setting('womens_discount_percentage', 0) > 0)
            const genderPercentage = {{ setting('womens_discount_percentage', 0) }};
            const genderAmount = (maxAmount * genderPercentage) / 100;
            if (genderAmount <= maxAmount) {
                buttonsHtml += `
                    <button type="button" class="btn btn-outline-success btn-sm mb-1" 
                            onclick="setQuickConcessionAmount(${genderAmount})">
                        Gender ${genderPercentage}% (₹${genderAmount.toLocaleString('en-IN', {minimumFractionDigits: 0})})
                    </button>
                `;
            }
        @endif

        quickConcessionButtons.innerHTML = buttonsHtml;
    }

    function updatePreview() {
        if (!currentSelectedFee) {
            concessionPreview.style.display = 'none';
            return;
        }

        const concessionAmount = parseFloat(concessionAmountInput.value) || 0;
        const currentBalance = currentSelectedFee.remaining;
        const afterConcession = currentBalance - concessionAmount;

        document.getElementById('preview_component').textContent = currentSelectedFee.name;
        document.getElementById('preview_current').textContent = '₹' + currentBalance.toLocaleString();
        document.getElementById('preview_after').textContent = '₹' + Math.max(0, afterConcession).toLocaleString();

        concessionPreview.style.display = concessionAmount > 0 ? 'block' : 'none';
    }

    // Event listeners
    if (concessionComponentSelect) {
        concessionComponentSelect.addEventListener('change', updateConcessionInput);
    }

    if (concessionAmountInput) {
        concessionAmountInput.addEventListener('input', function() {
            updatePreview();
            
            const amount = parseFloat(this.value);
            const maxAmount = parseFloat(this.max);
            
            if (amount > maxAmount) {
                this.value = maxAmount;
                updatePreview();
            }
        });
    }

    // Form validation and submission
    const concessionForm = document.getElementById('concessionForm');
    if (concessionForm) {
        concessionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const selectedComponent = concessionComponentSelect.value;
            const amount = parseFloat(concessionAmountInput.value);
            const maxAmount = parseFloat(concessionAmountInput.max);
            
            // Validation
            if (!selectedComponent) {
                showAlert('error', 'Please select a fee component.');
                return;
            }
            
            if (!amount || amount <= 0) {
                showAlert('error', 'Please enter a valid concession amount.');
                return;
            }
            
            if (amount > maxAmount) {
                showAlert('error', `Concession amount cannot exceed {{ setting('currency_symbol', '₹') }}${maxAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}`);
                return;
            }

            // Show confirmation
            const componentName = currentSelectedFee ? currentSelectedFee.name : 'selected component';
            if (confirm(`Apply concession of {{ setting('currency_symbol', '₹') }}${amount.toLocaleString('en-IN', {minimumFractionDigits: 2})} to ${componentName}?\n\nThis action cannot be easily undone.`)) {
                
                // Show loading state
                applyConcessionBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                applyConcessionBtn.disabled = true;
                
                // Submit form
                this.submit();
            }
        });
    }

    // Initialize on modal show
    $('#applyConcessionModal').on('shown.bs.modal', function () {
        updateConcessionInput();
    });

    // Reset modal when hidden
    $('#applyConcessionModal').on('hidden.bs.modal', function () {
        if (concessionForm) {
            concessionForm.reset();
        }
        applyConcessionBtn.innerHTML = '<i class="fas fa-percent mr-2"></i>Apply Concession';
        applyConcessionBtn.disabled = true;
        concessionPreview.style.display = 'none';
        updateConcessionInput();
    });
});

// Global helper functions
function setQuickConcessionAmount(amount) {
    const input = document.getElementById('concession_amount');
    if (input) {
        input.value = amount.toFixed(2);
        input.dispatchEvent(new Event('input'));
    }
}

function applyAutomaticGenderConcession() {
    if (!confirm('Apply automatic gender-based concession to all eligible fee components?\n\nThis action cannot be undone.')) {
        return;
    }

    // Show loading state
    const loadingAlert = `
        <div class="alert alert-info">
            <i class="fas fa-spinner fa-spin mr-2"></i>Processing automatic gender-based concession...
        </div>
    `;
    
    $('.modal-body').prepend(loadingAlert);

    fetch('{{ url("admin/students/" . $student->id . "/apply-auto-gender-concession") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        $('.alert-info').remove();
        
        if (data.success) {
            showAlert('success', data.message);
            $('#applyConcessionModal').modal('hide');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        $('.alert-info').remove();
        showAlert('error', 'Error applying automatic concession: ' + error.message);
    });
}
</script>