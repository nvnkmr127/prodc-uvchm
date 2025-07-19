@extends('layouts.theme')
@section('title', 'Edit History - Invoice #' . $invoice->invoice_number)

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-history"></i> Edit History - Invoice #{{ $invoice->invoice_number }}
    </h1>
    <div>
        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Invoice
        </a>
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-list fa-sm text-white-50"></i> All Invoices
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif

<div class="row">
    <div class="col-lg-4">
        {{-- Invoice Summary Card --}}
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">Invoice Summary</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Student:</strong> {{ $invoice->student->name }}<br>
                    <strong>Enrollment:</strong> {{ $invoice->student->enrollment_number }}<br>
                    <strong>Batch:</strong> {{ $invoice->student->batch->name ?? 'N/A' }}<br>
                    <strong>Course:</strong> {{ $invoice->student->batch->course->name ?? 'N/A' }}
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Current Amount</div>
                        <div class="h6 mb-0 font-weight-bold">₹{{ number_format($invoice->total_amount, 2) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Status</div>
                        <div class="h6 mb-0">
                            <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'partial' ? 'warning' : 'danger') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit Summary Stats --}}
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Edit Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Edits</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $editLogs->total() }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Reverts</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $editLogs->where('action', 'revert')->count() }}</div>
                    </div>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-12">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Last Modified</div>
                        <div class="small font-weight-bold text-gray-800">
                            {{ $editLogs->first() ? $editLogs->first()->created_at->format('d M, Y H:i A') : 'Never' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        {{-- Edit History Timeline --}}
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-timeline"></i> Complete Edit Timeline
                </h6>
                <div>
                    <span class="badge badge-info">{{ $editLogs->total() }} total changes</span>
                </div>
            </div>
            <div class="card-body">
                @if($editLogs->count() > 0)
                    <div class="timeline">
                        @foreach($editLogs as $index => $log)
                        <div class="timeline-item border-left border-{{ $log->action_badge_class }} pl-4 pb-4 mb-4">
                            {{-- Timeline marker --}}
                            <div class="timeline-marker bg-{{ $log->action_badge_class }}"></div>
                            
                            {{-- Edit header --}}
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge badge-{{ $log->action_badge_class }} mb-2">
                                        <i class="{{ $log->action_icon }}"></i> {{ ucfirst($log->action) }} #{{ $editLogs->total() - $editLogs->firstItem() - $index + 1 }}
                                    </span>
                                    <div class="text-muted small">
                                        <i class="fas fa-user"></i> {{ $log->user_name }} • 
                                        <i class="fas fa-clock"></i> {{ $log->created_at->format('d M, Y H:i A') }} •
                                        <i class="fas fa-map-marker-alt"></i> {{ $log->ip_address }}
                                    </div>
                                </div>
                                
                                {{-- Revert button --}}
                                @if($log->canRevert() && $log->action === 'edit')
                                    <form action="{{ route('admin.invoices.revert', [$invoice, $log]) }}" method="POST" 
                                          onsubmit="return confirm('Are you sure you want to revert to this state? This will undo all changes made after {{ $log->created_at->format('d M, Y H:i') }}.')" 
                                          class="ml-2">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Revert to this state">
                                            <i class="fas fa-undo"></i> Revert
                                        </button>
                                    </form>
                                @endif
                            </div>

                            {{-- Edit reason/notes --}}
                            @if($log->notes)
                                <div class="alert alert-light border-left border-{{ $log->action_badge_class }} mb-3">
                                    <strong><i class="fas fa-comment"></i> Reason:</strong> {{ $log->notes }}
                                </div>
                            @endif

                            {{-- Changes summary --}}
                            @if($log->has_significant_changes)
                                <div class="bg-light p-3 rounded mb-3">
                                    <h6 class="text-primary mb-2"><i class="fas fa-exchange-alt"></i> Significant Changes</h6>
                                    
                                    {{-- Amount changes --}}
                                    @if($log->total_amount_change)
                                        <div class="mb-2">
                                            <span class="badge badge-info">Amount Change</span>
                                            <div class="mt-1">
                                                <strong>From:</strong> ₹{{ number_format($log->total_amount_change['from'], 2) }} →
                                                <strong>To:</strong> ₹{{ number_format($log->total_amount_change['to'], 2) }}
                                                @if($log->total_amount_change['difference'] != 0)
                                                    <span class="badge badge-{{ $log->total_amount_change['difference'] > 0 ? 'success' : 'danger' }} ml-2">
                                                        {{ $log->total_amount_change['difference'] > 0 ? '+' : '' }}₹{{ number_format(abs($log->total_amount_change['difference']), 2) }}
                                                        ({{ $log->total_amount_change['percentage'] > 0 ? '+' : '' }}{{ $log->total_amount_change['percentage'] }}%)
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Other changes --}}
                                    @php
                                        $changesSummary = $log->getChangesSummary();
                                    @endphp
                                    
                                    @if($changesSummary['items_changed'])
                                        <div class="mb-2">
                                            <span class="badge badge-warning">Items Modified</span>
                                        </div>
                                    @endif
                                    
                                    @if($changesSummary['dates_changed'])
                                        <div class="mb-2">
                                            <span class="badge badge-info">Dates Updated</span>
                                        </div>
                                    @endif
                                    
                                    @if($changesSummary['concession_changed'])
                                        <div class="mb-2">
                                            <span class="badge badge-success">Concession Applied</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Detailed changes --}}
                            <div class="card">
                                <div class="card-header py-2">
                                    <h6 class="m-0 small">
                                        <i class="fas fa-list"></i> Detailed Changes 
                                        <span class="badge badge-secondary">{{ count($log->changes) }} fields</span>
                                    </h6>
                                </div>
                                <div class="card-body py-2">
                                    @if(!empty($log->changes))
                                        <div class="small">
                                            {!! $log->formatted_changes !!}
                                        </div>
                                    @else
                                        <div class="text-muted small">No detailed changes recorded</div>
                                    @endif
                                </div>
                            </div>

                            {{-- State comparison (for important edits) --}}
                            @if($log->action === 'edit' && ($log->previous_state && $log->new_state))
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" 
                                            data-target="#stateComparison{{ $log->id }}" aria-expanded="false">
                                        <i class="fas fa-code"></i> View Raw State Changes
                                    </button>
                                    <div class="collapse mt-2" id="stateComparison{{ $log->id }}">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-danger">Previous State</h6>
                                                <pre class="bg-light p-2 small" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->previous_state, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-success">New State</h6>
                                                <pre class="bg-light p-2 small" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->new_state, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-center">
                        {{ $editLogs->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Edit History</h5>
                        <p class="text-muted">This invoice has not been edited yet.</p>
                        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Invoice
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
}

.timeline-item {
    position: relative;
    border-left: 3px solid #dee2e6 !important;
}

.timeline-marker {
    position: absolute;
    left: -8px;
    top: 8px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-item:last-child {
    border-left: none !important;
}

.timeline-item:last-child::after {
    content: '';
    position: absolute;
    left: -1.5px;
    bottom: 0;
    width: 3px;
    height: 20px;
    background: linear-gradient(to bottom, #dee2e6, transparent);
}

.border-primary { border-color: #007bff !important; }
.border-warning { border-color: #ffc107 !important; }
.border-success { border-color: #28a745 !important; }
.border-danger { border-color: #dc3545 !important; }

.bg-primary { background-color: #007bff !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-success { background-color: #28a745 !important; }
.bg-danger { background-color: #dc3545 !important; }

pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-scroll to specific edit if hash is present
    if (window.location.hash) {
        const target = $(window.location.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    }
    
    // Smooth scroll for timeline navigation
    $('.timeline-item').click(function(e) {
        if (!$(e.target).is('button, a, input')) {
            $(this).find('.collapse').collapse('toggle');
        }
    });
});
</script>
@endpush
@endsection