@extends('layouts.theme')
@section('title', 'Edit Invoice #' . $invoice->invoice_number)

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-edit"></i> Edit Invoice #{{ $invoice->invoice_number }}
    </h1>
    <div>
        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Invoice
        </a>
        @if($editHistory->count() > 0)
            <a href="{{ route('admin.invoices.edit-history', $invoice) }}" class="btn btn-sm btn-info shadow-sm">
                <i class="fas fa-history fa-sm text-white-50"></i> View History ({{ $editHistory->count() }})
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        {{-- Main Edit Form Card --}}
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-dark">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-exclamation-triangle"></i> Edit Invoice - Requires Careful Review
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Important:</strong> Editing this invoice will create an audit trail. Please provide a clear reason for the changes.
                    @if($invoice->paid_amount > 0)
                        <br><strong>Note:</strong> This invoice has payments totaling ₹{{ number_format($invoice->paid_amount, 2) }}. Changes may affect the balance.
                    @endif
                </div>

                <form action="{{ route('admin.invoices.update', $invoice) }}" method="POST" id="editInvoiceForm">
                    @csrf
                    @method('PUT')

                    {{-- Basic Invoice Information --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="issue_date" class="form-label">Issue Date*</label>
                            <input type="date" class="form-control" name="issue_date" 
                                   value="{{ old('issue_date', $invoice->issue_date) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date*</label>
                            <input type="date" class="form-control" name="due_date" 
                                   value="{{ old('due_date', $invoice->due_date) }}" required>
                        </div>
                    </div>

                    {{-- Invoice Items --}}
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h6 class="m-0">Invoice Items</h6>
                            <button type="button" class="btn btn-sm btn-light" onclick="addInvoiceItem()">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="invoice-items">
                                @foreach($invoice->items as $index => $item)
                                <div class="invoice-item border-bottom pb-3 mb-3" data-index="{{ $index }}">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label class="form-label">Fee Category*</label>
                                            <select name="items[{{ $index }}][fee_category_id]" class="form-control fee-category-select" required>
                                                <option value="">Select Fee Category</option>
                                                @foreach($feeCategories as $category)
                                                    <option value="{{ $category->id }}" 
                                                            {{ $item->fee_category_id == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Amount*</label>
                                            <input type="number" step="0.01" min="0" 
                                                   name="items[{{ $index }}][amount]" 
                                                   class="form-control item-amount" 
                                                   value="{{ old('items.'.$index.'.amount', $item->amount) }}" 
                                                   required onchange="calculateTotal()">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Description</label>
                                            <input type="text" name="items[{{ $index }}][description]" 
                                                   class="form-control" 
                                                   value="{{ old('items.'.$index.'.description', $item->description) }}" 
                                                   placeholder="Optional">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            @if($index > 0 || count($invoice->items) > 1)
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeInvoiceItem(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            {{-- Total Calculation --}}
                            <div class="row mt-3">
                                <div class="col-md-8 offset-md-4">
                                    <div class="bg-light p-3 rounded">
                                        <div class="d-flex justify-content-between">
                                            <strong>Subtotal:</strong>
                                            <strong id="subtotal">₹{{ number_format($invoice->items->sum('amount'), 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Concession Section --}}
                    <div class="card border-success mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="m-0"><i class="fas fa-percent"></i> Concession/Discount</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="concession_amount" class="form-label">Concession Amount</label>
                                    <input type="number" step="0.01" min="0" class="form-control" 
                                           name="concession_amount" id="concession_amount"
                                           value="{{ old('concession_amount', $invoice->concession_amount) }}" 
                                           onchange="calculateTotal()">
                                    <small class="text-muted">Enter amount to deduct from total</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="concession_notes" class="form-label">Concession Notes</label>
                                    <input type="text" class="form-control" name="concession_notes" 
                                           value="{{ old('concession_notes', $invoice->concession_notes) }}" 
                                           placeholder="e.g., Scholarship, Staff discount">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Final Total --}}
                    <div class="card border-primary mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 offset-md-6">
                                    <div class="bg-primary text-white p-3 rounded">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="m-0">Final Total:</h5>
                                            <h5 class="m-0" id="final-total">₹{{ number_format($invoice->total_amount, 2) }}</h5>
                                        </div>
                                        <small>Paid: ₹{{ number_format($invoice->paid_amount, 2) }} | 
                                               Balance: ₹<span id="balance-due">{{ number_format($invoice->due_amount, 2) }}</span></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Reason (Required) --}}
                    <div class="card border-warning mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="m-0"><i class="fas fa-comment"></i> Edit Reason (Required)</h6>
                        </div>
                        <div class="card-body">
                            <textarea name="edit_notes" class="form-control" rows="3" required 
                                      placeholder="Please provide a detailed reason for editing this invoice. This will be recorded in the audit trail.">{{ old('edit_notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                        <div>
                            <button type="button" class="btn btn-info" onclick="previewChanges()">
                                <i class="fas fa-eye"></i> Preview Changes
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Current Invoice Summary --}}
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Current Invoice Summary</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Student:</strong> {{ $invoice->student->name }}<br>
                    <strong>Enrollment:</strong> {{ $invoice->student->enrollment_number }}<br>
                    <strong>Batch:</strong> {{ $invoice->student->batch->name ?? 'N/A' }}
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-12 mb-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Current Status</div>
                        <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'partial' ? 'warning' : 'danger') }}" style="font-size: 0.9rem;">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Paid</div>
                        <div class="h6 mb-0">₹{{ number_format($invoice->paid_amount, 2) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Due</div>
                        <div class="h6 mb-0">₹{{ number_format($invoice->due_amount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Edit History --}}
        @if($editHistory->count() > 0)
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recent Edits</h6>
                <span class="badge badge-info">{{ $editHistory->count() }}</span>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                @foreach($editHistory->take(5) as $log)
                <div class="mb-3 border-left border-{{ $log->action_badge_class }} pl-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge badge-{{ $log->action_badge_class }} badge-sm">{{ ucfirst($log->action) }}</span>
                            <div class="small text-muted">{{ $log->created_at->format('d M, H:i') }} by {{ $log->user_name }}</div>
                            @if($log->notes)
                                <div class="small mt-1">{{ Str::limit($log->notes, 60) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
                
                @if($editHistory->count() > 5)
                <div class="text-center">
                    <a href="{{ route('admin.invoices.edit-history', $invoice) }}" class="btn btn-sm btn-outline-primary">
                        View All {{ $editHistory->count() }} Edits
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Available Fee Categories --}}
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Available Fee Categories</h6>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                @foreach($feeCategories as $category)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">{{ $category->name }}</span>
                    <button type="button" class="btn btn-xs btn-outline-primary" 
                            onclick="quickAddCategory({{ $category->id }}, '{{ $category->name }}')">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Preview Changes Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Changes</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="preview-content">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="submitForm()">Confirm & Save Changes</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let itemIndex = {{ count($invoice->items) }};
const originalPaidAmount = {{ $invoice->paid_amount }};

function addInvoiceItem() {
    const container = document.getElementById('invoice-items');
    const newItem = document.createElement('div');
    newItem.className = 'invoice-item border-bottom pb-3 mb-3';
    newItem.setAttribute('data-index', itemIndex);
    
    newItem.innerHTML = `
        <div class="row">
            <div class="col-md-5">
                <label class="form-label">Fee Category*</label>
                <select name="items[${itemIndex}][fee_category_id]" class="form-control fee-category-select" required>
                    <option value="">Select Fee Category</option>
                    @foreach($feeCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount*</label>
                <input type="number" name="items[${itemIndex}][amount]" class="form-control item-amount" 
                       step="0.01" min="0" placeholder="0.00" required onchange="calculateTotal()">
            </div>
            <div class="col-md-3">
                <label class="form-label">Description</label>
                <input type="text" name="items[${itemIndex}][description]" class="form-control" placeholder="Optional">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeInvoiceItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(newItem);
    
    // Add event listener to the new amount input for calculations
    const newAmountInput = newItem.querySelector('.item-amount');
    newAmountInput.addEventListener('input', calculateTotal);
    
    itemIndex++;
    calculateTotal();
}

function removeInvoiceItem(button) {
    const item = button.closest('.invoice-item');
    const container = document.getElementById('invoice-items');
    
    // Don't allow removing the last item
    if (container.children.length > 1) {
        item.remove();
        calculateTotal();
    } else {
        alert('At least one item is required for the invoice.');
    }
}

function quickAddCategory(categoryId, categoryName) {
    addInvoiceItem();
    const newItem = document.querySelector('.invoice-item:last-child');
    const select = newItem.querySelector('.fee-category-select');
    select.value = categoryId;
}

function calculateTotal() {
    let subtotal = 0;
    
    // Calculate subtotal from all items
    document.querySelectorAll('.item-amount').forEach(function(input) {
        const amount = parseFloat(input.value) || 0;
        subtotal += amount;
    });
    
    // Get concession amount
    const concessionAmount = parseFloat(document.getElementById('concession_amount').value) || 0;
    
    // Calculate final total
    const finalTotal = Math.max(0, subtotal - concessionAmount);
    
    // Calculate new balance due
    const balanceDue = Math.max(0, finalTotal - originalPaidAmount);
    
    // Update display with proper number formatting
    document.getElementById('subtotal').textContent = '₹' + subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    document.getElementById('final-total').textContent = '₹' + finalTotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    document.getElementById('balance-due').textContent = balanceDue.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    
    // Update balance due color based on amount
    const balanceElement = document.getElementById('balance-due').parentElement;
    if (balanceDue > originalPaidAmount) {
        balanceElement.className = 'text-danger';
    } else if (balanceDue < originalPaidAmount) {
        balanceElement.className = 'text-success';
    } else {
        balanceElement.className = '';
    }
}

function previewChanges() {
    const form = document.getElementById('editInvoiceForm');
    const formData = new FormData(form);
    
    let previewContent = '<div class="container-fluid">';
    
    // Current vs New comparison
    previewContent += '<h6 class="text-primary mb-3">Summary of Changes</h6>';
    
    // Calculate new totals
    let newSubtotal = 0;
    const items = [];
    let itemIndex = 0;
    
    // Collect all items
    document.querySelectorAll('.invoice-item').forEach(function(item) {
        const categorySelect = item.querySelector('.fee-category-select');
        const amountInput = item.querySelector('.item-amount');
        const descInput = item.querySelector('input[name*="[description]"]');
        
        if (categorySelect.value && amountInput.value) {
            const amount = parseFloat(amountInput.value);
            newSubtotal += amount;
            items.push({
                category: categorySelect.options[categorySelect.selectedIndex].text,
                amount: amount,
                description: descInput.value
            });
        }
    });
    
    const newConcession = parseFloat(document.getElementById('concession_amount').value) || 0;
    const newTotal = Math.max(0, newSubtotal - newConcession);
    const newBalance = Math.max(0, newTotal - originalPaidAmount);
    
    // Financial Summary
    previewContent += '<div class="row mb-4">';
    previewContent += '<div class="col-md-6">';
    previewContent += '<h6 class="text-danger">Current</h6>';
    previewContent += '<ul class="list-unstyled">';
    previewContent += '<li><strong>Total Amount:</strong> ₹{{ number_format($invoice->total_amount, 2) }}</li>';
    previewContent += '<li><strong>Paid Amount:</strong> ₹{{ number_format($invoice->paid_amount, 2) }}</li>';
    previewContent += '<li><strong>Balance Due:</strong> ₹{{ number_format($invoice->due_amount, 2) }}</li>';
    previewContent += '</ul>';
    previewContent += '</div>';
    previewContent += '<div class="col-md-6">';
    previewContent += '<h6 class="text-success">New</h6>';
    previewContent += '<ul class="list-unstyled">';
    previewContent += '<li><strong>Total Amount:</strong> ₹' + newTotal.toFixed(2) + '</li>';
    previewContent += '<li><strong>Paid Amount:</strong> ₹{{ number_format($invoice->paid_amount, 2) }}</li>';
    previewContent += '<li><strong>Balance Due:</strong> ₹' + newBalance.toFixed(2) + '</li>';
    previewContent += '</ul>';
    previewContent += '</div>';
    previewContent += '</div>';
    
    // Items comparison
    previewContent += '<h6 class="text-primary mb-3">Invoice Items</h6>';
    previewContent += '<div class="table-responsive">';
    previewContent += '<table class="table table-sm table-bordered">';
    previewContent += '<thead><tr><th>Fee Category</th><th>Amount</th><th>Description</th></tr></thead>';
    previewContent += '<tbody>';
    
    items.forEach(function(item) {
        previewContent += '<tr>';
        previewContent += '<td>' + item.category + '</td>';
        previewContent += '<td>₹' + item.amount.toFixed(2) + '</td>';
        previewContent += '<td>' + (item.description || '-') + '</td>';
        previewContent += '</tr>';
    });
    
    previewContent += '</tbody>';
    previewContent += '<tfoot>';
    previewContent += '<tr><th>Subtotal</th><th>₹' + newSubtotal.toFixed(2) + '</th><th></th></tr>';
    if (newConcession > 0) {
        previewContent += '<tr><th>Concession</th><th class="text-success">-₹' + newConcession.toFixed(2) + '</th><th></th></tr>';
    }
    previewContent += '<tr class="table-primary"><th>Final Total</th><th>₹' + newTotal.toFixed(2) + '</th><th></th></tr>';
    previewContent += '</tfoot>';
    previewContent += '</table>';
    previewContent += '</div>';
    
    // Edit reason
    const editReason = document.querySelector('textarea[name="edit_notes"]').value;
    if (editReason) {
        previewContent += '<h6 class="text-primary mb-3">Edit Reason</h6>';
        previewContent += '<div class="alert alert-warning">' + editReason + '</div>';
    }
    
    previewContent += '</div>';
    
    document.getElementById('preview-content').innerHTML = previewContent;
    $('#previewModal').modal('show');
}

function submitForm() {
    document.getElementById('editInvoiceForm').submit();
}

// Form validation
document.getElementById('editInvoiceForm').addEventListener('submit', function(e) {
    const items = document.querySelectorAll('.invoice-item');
    let hasValidItem = false;
    
    items.forEach(function(item) {
        const categorySelect = item.querySelector('.fee-category-select');
        const amountInput = item.querySelector('.item-amount');
        
        if (categorySelect.value && amountInput.value && parseFloat(amountInput.value) > 0) {
            hasValidItem = true;
        }
    });
    
    if (!hasValidItem) {
        e.preventDefault();
        alert('Please add at least one valid invoice item with a fee category and amount.');
        return false;
    }
    
    const editNotes = document.querySelector('textarea[name="edit_notes"]').value.trim();
    if (!editNotes) {
        e.preventDefault();
        alert('Please provide a reason for editing this invoice.');
        document.querySelector('textarea[name="edit_notes"]').focus();
        return false;
    }
    
    return confirm('Are you sure you want to save these changes? This action will be recorded in the audit trail.');
});

// Auto-calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
    
    // Add change listeners to all amount inputs
    document.querySelectorAll('.item-amount').forEach(function(input) {
        input.addEventListener('input', calculateTotal);
    });
    
    document.getElementById('concession_amount').addEventListener('input', calculateTotal);
});

// Prevent accidental page leave
let formChanged = false;
document.getElementById('editInvoiceForm').addEventListener('input', function() {
    formChanged = true;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Remove the warning when form is submitted
document.getElementById('editInvoiceForm').addEventListener('submit', function() {
    formChanged = false;
});
</script>
@endpush

@push('styles')
<style>
.invoice-item {
    transition: all 0.3s ease;
}

.invoice-item:hover {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 10px;
    margin: 5px 0;
}

.fee-category-select:focus,
.item-amount:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.border-primary { border-color: #007bff !important; }
.border-warning { border-color: #ffc107 !important; }
.border-success { border-color: #28a745 !important; }

.text-danger { color: #dc3545 !important; }
.text-success { color: #28a745 !important; }

@media (max-width: 768px) {
    .invoice-item .row > div {
        margin-bottom: 10px;
    }
    
    .invoice-item .col-md-1 {
        text-align: center;
    }
}
</style>
@endpush
@endsection