
@extends('layouts.theme')

@section('title', 'Payment History - ' . $payment->receipt_number)

@push('styles')
<style>
    .history-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .payment-summary-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .history-item {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-left: 5px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .history-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .history-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fc;
    }
    
    .action-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
    }
    
    .action-created { background: #d4edda; color: #155724; }
    .action-update { background: #fff3cd; color: #856404; }
    .action-revert { background: #f8d7da; color: #721c24; }
    
    .changes-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin: 1rem 0;
    }
    
    .change-item {
        background: #f8f9fc;
        padding: 1rem;
        border-radius: 8px;
        border-left: 3px solid #667eea;
    }
    
    .old-value {
        background: #f8d7da;
        color: #721c24;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
    }
    
    .new-value {
        background: #d4edda;
        color: #155724;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
    }
    
    .metadata-section {
        background: #f8f9fc;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    
    .revert-form {
        background: linear-gradient(135deg, #ffe6e6 0%, #fff0f0 100%);
        border: 2px solid #dc3545;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid my-4">
    <div class="history-container">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Payment Edit History</h2>
                <p class="text-muted">{{ $payment->receipt_number }} • {{ $payment->student->name }}</p>
            </div>
            <div>
                <a href="{{ route('admin.students.show', $payment->student) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Student
                </a>
                <a href="{{ route('admin.payments.receipt', [$payment->student, $payment]) }}" class="btn btn-info">
    <i class="fas fa-receipt mr-2"></i> View Receipt
</a>
                @can('edit payments')
                    @if($payment->canBeEdited())
                        <a href="{{ route('admin.payment-edit.edit', $payment) }}" class="btn btn-warning">
                            <i class="fas fa-edit mr-2"></i> Edit Payment
                        </a>
                    @endif
                @endcan
            </div>
        </div>

        {{-- Current Payment Summary --}}
        <div class="payment-summary-card">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-3">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> Current Payment Status
                    </h4>
                    <div class="row">
                        <div class="col-sm-6">
                            <p><strong>Student:</strong> {{ $payment->student->name }}</p>
                            <p><strong>Receipt:</strong> {{ $payment->receipt_number }}</p>
                            <p><strong>Date:</strong> {{ $payment->payment_date->format('d M, Y') }}</p>
                            <p><strong>Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p><strong>Created By:</strong> {{ $payment->createdBy->name ?? 'System' }}</p>
                            <p><strong>Created:</strong> {{ $payment->created_at->format('d M, Y H:i A') }}</p>
                            @if($payment->updatedBy)
                                <p><strong>Last Updated By:</strong> {{ $payment->updatedBy->name }}</p>
                                <p><strong>Updated:</strong> {{ $payment->updated_at->format('d M, Y H:i A') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="display-4 mb-2">₹{{ number_format($payment->amount, 2) }}</div>
                    <p class="mb-0 opacity-75">Current Amount</p>
                    <span class="badge badge-light badge-lg mt-2">
                        {{ ucfirst($payment->status ?? 'completed') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Edit History --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history mr-2"></i> Edit History & Audit Log
                </h5>
            </div>
            <div class="card-body">
                @if($editHistory->count() > 0)
                    @foreach($editHistory as $log)
                        <div class="history-item">
                            <div class="history-header">
                                <div class="d-flex align-items-center">
                                    <span class="action-badge action-{{ $log->action }}">
                                        {{ ucfirst($log->action) }}
                                    </span>
                                    <div class="ml-3">
                                        <h6 class="mb-1">{{ $log->changes_summary ?: 'Payment ' . $log->action }}</h6>
                                        <small class="text-muted">
                                            {{ $log->created_at->format('d M, Y \a\t H:i A') }} • 
                                            by {{ $log->user->name ?? 'System' }}
                                        </small>
                                    </div>
                                </div>
                                <div class="ml-auto">
                                    @can('revert payments')
                                        @if($log->action !== 'revert' && $payment->canBeEdited())
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="showRevertForm({{ $log->id }}, '{{ $log->created_at->format('Y-m-d H:i:s') }}')">
                                                <i class="fas fa-undo mr-1"></i> Revert to This
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </div>

                            {{-- Edit Reason --}}
                            @if($log->edit_reason)
                                <div class="mb-3">
                                    <strong>Reason:</strong> {{ $log->edit_reason }}
                                </div>
                            @endif

                            {{-- Changes Details --}}
                            @if($log->old_values && $log->new_values)
                                <div class="changes-grid">
                                    @foreach($log->new_values as $field => $newValue)
                                        @if(isset($log->old_values[$field]) && $log->old_values[$field] != $newValue)
                                            <div class="change-item">
                                                <strong>{{ ucfirst(str_replace('_', ' ', $field)) }}:</strong><br>
                                                <span class="old-value">{{ $log->old_values[$field] ?: 'Empty' }}</span>
                                                <i class="fas fa-arrow-right mx-2"></i>
                                                <span class="new-value">{{ $newValue ?: 'Empty' }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Component Changes --}}
                            @if(isset($log->old_values['components']) || isset($log->new_values['components']))
                                <div class="mt-3">
                                    <h6><i class="fas fa-list mr-2"></i>Component Changes:</h6>
                                    <div class="row">
                                        @if(isset($log->old_values['components']))
                                            <div class="col-md-6">
                                                <h6 class="text-danger">Previous Components:</h6>
                                                @foreach($log->old_values['components'] as $component)
                                                    <div class="small mb-1">
                                                        <strong>{{ $component['fee_category_name'] ?? 'Unknown' }}:</strong>
                                                        ₹{{ number_format($component['amount_paid'] ?? 0, 2) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(isset($log->new_values['components']))
                                            <div class="col-md-6">
                                                <h6 class="text-success">New Components:</h6>
                                                @foreach($log->new_values['components'] as $component)
                                                    <div class="small mb-1">
                                                        <strong>{{ $component['fee_category_name'] ?? 'Unknown' }}:</strong>
                                                        ₹{{ number_format($component['amount_paid'] ?? 0, 2) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Metadata --}}
                            @if($log->metadata)
                                <div class="metadata-section">
                                    <h6><i class="fas fa-info-circle mr-2"></i>Additional Information:</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small>
                                                <strong>IP Address:</strong> {{ $log->ip_address ?: 'Not recorded' }}<br>
                                                <strong>Payment Type:</strong> {{ $log->metadata['payment_type'] ?? 'N/A' }}<br>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <small>
                                                <strong>User Agent:</strong> {{ Str::limit($log->user_agent ?: 'Not recorded', 50) }}<br>
                                                <strong>Student ID:</strong> {{ $log->metadata['student_id'] ?? 'N/A' }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Revert Form (Initially Hidden) --}}
                            <div id="revertForm{{ $log->id }}" class="revert-form" style="display: none;">
                                <h6 class="text-danger mb-3">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> Revert Payment to Previous State
                                </h6>
                                <form action="{{ route('admin.payment-edit.revert', $payment) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="log_id" value="{{ $log->id }}">
                                    
                                    <div class="form-group">
                                        <label for="revert_reason{{ $log->id }}" class="font-weight-bold">Reason for Reverting *</label>
                                        <textarea name="revert_reason" id="revert_reason{{ $log->id }}" 
                                                  class="form-control" rows="3" required
                                                  placeholder="Please provide a detailed reason for reverting this payment..."></textarea>
                                        <small class="form-text text-muted">This action will be logged for audit purposes</small>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to revert this payment? This action cannot be undone.')">
                                                <i class="fas fa-undo mr-2"></i> Confirm Revert
                                            </button>
                                            <button type="button" class="btn btn-secondary ml-2" 
                                                    onclick="hideRevertForm({{ $log->id }})">
                                                <i class="fas fa-times mr-2"></i> Cancel
                                            </button>
                                        </div>
                                        <small class="text-muted">
                                            Reverting to state from: <strong>{{ $log->created_at->format('Y-m-d H:i:s') }}</strong>
                                        </small>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-center">
                        {{ $editHistory->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Edit History</h5>
                        <p class="text-muted">This payment has not been modified since creation.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Current Component Breakdown --}}
        @if($payment->componentItems && $payment->componentItems->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list-alt mr-2"></i> Current Component Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fee Category</th>
                                    <th>Description</th>
                                    <th class="text-right">Amount Paid</th>
                                    <th class="text-right">Total Fee</th>
                                    <th class="text-right">Remaining</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->componentItems as $item)
                                    @php
                                        $studentFee = $item->studentFee;
                                        $remaining = $studentFee->amount - $studentFee->paid_amount - $studentFee->concession_amount;
                                    @endphp
                                    <tr>
                                        <td class="font-weight-bold">
                                            {{ $studentFee->feeCategory->name ?? 'Unknown Category' }}
                                        </td>
                                        <td class="text-muted">
                                            {{ $studentFee->feeCategory->description ?? 'Standard fee component' }}
                                        </td>
                                        <td class="text-right font-weight-bold text-success">
                                            ₹{{ number_format($item->amount_paid, 2) }}
                                        </td>
                                        <td class="text-right">
                                            ₹{{ number_format($studentFee->amount, 2) }}
                                        </td>
                                        <td class="text-right {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                                            ₹{{ number_format($remaining, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-active">
                                    <td colspan="2" class="font-weight-bold text-right">Total:</td>
                                    <td class="text-right font-weight-bold h6 text-success">
                                        ₹{{ number_format($payment->amount, 2) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function showRevertForm(logId, timestamp) {
    // Hide all other revert forms
    $('[id^="revertForm"]').hide();
    
    // Show the specific revert form
    $('#revertForm' + logId).slideDown();
    
    // Focus on the reason textarea
    $('#revert_reason' + logId).focus();
}

function hideRevertForm(logId) {
    $('#revertForm' + logId).slideUp();
}

// Enhanced hover effects for history items
$(document).ready(function() {
    $('.history-item').hover(
        function() {
            $(this).find('.action-badge').addClass('shadow-sm');
        },
        function() {
            $(this).find('.action-badge').removeClass('shadow-sm');
        }
    );
    
    // Tooltip initialization
    $('[data-toggle="tooltip"]').tooltip();
    
    // Auto-expand latest change if it's recent
    @if($editHistory->count() > 0 && $editHistory->first()->created_at->diffInHours() < 24)
        $('.history-item:first').addClass('border-warning');
        setTimeout(() => {
            $('.history-item:first').removeClass('border-warning');
        }, 3000);
    @endif
});

// Keyboard shortcuts
$(document).on('keydown', function(e) {
    // Escape key to close all revert forms
    if (e.key === 'Escape') {
        $('[id^="revertForm"]').slideUp();
    }
    
    // Ctrl + E to edit payment
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        @can('edit payments')
            @if($payment->canBeEdited())
                window.location.href = "{{ route('admin.payment-edit.edit', $payment) }}";
            @endif
        @endcan
    }
});

// Form submission confirmation
$('form[action*="revert"]').on('submit', function(e) {
    const reason = $(this).find('textarea[name="revert_reason"]').val().trim();
    
    if (reason.length < 10) {
        e.preventDefault();
        alert('Please provide a more detailed reason for reverting (at least 10 characters).');
        return false;
    }
    
    if (!confirm('This will permanently revert the payment to a previous state. Are you absolutely sure?')) {
        e.preventDefault();
        return false;
    }
    
    // Show loading state
    $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin mr-2"></i>Reverting...')
                                          .prop('disabled', true);
});

// Auto-refresh functionality (optional)
function refreshHistory() {
    window.location.reload();
}

// Export history to CSV (optional enhancement)
function exportHistory() {
    const csvData = [];
    csvData.push(['Date', 'Action', 'User', 'Reason', 'Amount Change']);
    
    @foreach($editHistory as $log)
        csvData.push([
            '{{ $log->created_at->format('Y-m-d H:i:s') }}',
            '{{ $log->action }}',
            '{{ $log->user->name ?? 'System' }}',
            '{{ addslashes($log->edit_reason ?: '') }}',
            '{{ isset($log->new_values['amount']) ? '₹' . number_format($log->new_values['amount'], 2) : '' }}'
        ]);
    @endforeach
    
    let csvContent = "data:text/csv;charset=utf-8,";
    csvData.forEach(function(rowArray) {
        let row = rowArray.map(field => '"' + field + '"').join(",");
        csvContent += row + "\r\n";
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "payment_history_{{ $payment->receipt_number }}.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endpush