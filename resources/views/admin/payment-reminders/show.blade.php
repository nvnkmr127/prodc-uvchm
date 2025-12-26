@extends('layouts.theme')
@section('title', 'Payment Reminder Details')

@push('styles')
<style>
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .status-sent { background-color: #d1ecf1; color: #0c5460; border: 1px solid #81ecec; }
    .status-failed { background-color: #f8d7da; color: #721c24; border: 1px solid #fab1a0; }
    .status-cancelled { background-color: #d4edda; color: #155724; border: 1px solid #a8e6cf; }
    .status-processing { background-color: #e2e3e5; color: #383d41; border: 1px solid #b8b9bc; }

    .info-card {
        border-left: 4px solid #007bff;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }
    .info-card.success { border-left-color: #28a745; }
    .info-card.warning { border-left-color: #ffc107; }
    .info-card.danger { border-left-color: #dc3545; }

    .timeline {
        position: relative;
        padding-left: 2rem;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 1rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
    }
    .timeline-marker {
        position: absolute;
        left: -2rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 0 0 3px #dee2e6;
    }
    .timeline-marker.sent { background-color: #28a745; box-shadow: 0 0 0 3px #28a745; }
    .timeline-marker.failed { background-color: #dc3545; box-shadow: 0 0 0 3px #dc3545; }
    .timeline-marker.scheduled { background-color: #007bff; box-shadow: 0 0 0 3px #007bff; }
    .timeline-marker.cancelled { background-color: #6c757d; box-shadow: 0 0 0 3px #6c757d; }

    .message-preview {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1.5rem;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        line-height: 1.6;
    }
    .channel-indicator {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background: #e9ecef;
        border-radius: 1rem;
        font-size: 0.875rem;
    }
    .channel-indicator.email { background: #cce5ff; color: #0056b3; }
    .channel-indicator.sms { background: #d4edda; color: #155724; }
    .channel-indicator.whatsapp { background: #d4edda; color: #155724; }
    .channel-indicator.phone_call { background: #fff3cd; color: #856404; }

    .metric-card {
        text-align: center;
        padding: 1.5rem;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        margin-bottom: 1rem;
    }
    .metric-value {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .metric-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-bell text-primary mr-2"></i>Payment Reminder Details
    </h1>
    <div>
        @if(in_array($reminder->status, ['pending', 'scheduled', 'failed']))
            <a href="{{ route('admin.payment-reminders.edit', $reminder) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit mr-1"></i> Edit Reminder
            </a>
        @endif
        <a href="{{ route('admin.payment-reminders.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Reminders
        </a>
    </div>
</div>

<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Reminder Overview -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle mr-1"></i>Reminder Overview
                </h6>
                <span class="status-badge status-{{ $reminder->status }}">{{ ucfirst($reminder->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="font-weight-bold text-gray-800">Reminder Type:</label>
                            <div class="mt-1">
                                <span class="badge badge-info badge-lg">{{ ucwords(str_replace('_', ' ', $reminder->reminder_type)) }}</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="font-weight-bold text-gray-800">Communication Channel:</label>
                            <div class="mt-1">
                                <span class="channel-indicator {{ $reminder->channel }}">
                                    @if($reminder->channel === 'email')
                                        <i class="fas fa-envelope mr-1"></i> Email
                                    @elseif($reminder->channel === 'sms')
                                        <i class="fas fa-sms mr-1"></i> SMS
                                    @elseif($reminder->channel === 'whatsapp')
                                        <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                    @elseif($reminder->channel === 'phone_call')
                                        <i class="fas fa-phone mr-1"></i> Phone Call
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="font-weight-bold text-gray-800">Scheduled Date:</label>
                            <div class="mt-1">
                                <i class="fas fa-calendar text-muted mr-1"></i>
                                {{ $reminder->scheduled_date->format('d M Y') }}
                                <span class="text-muted">at {{ $reminder->scheduled_date->format('h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        @if($reminder->sent_at)
                            <div class="mb-3">
                                <label class="font-weight-bold text-gray-800">Sent Date:</label>
                                <div class="mt-1">
                                    <i class="fas fa-check-circle text-success mr-1"></i>
                                    {{ $reminder->sent_at->format('d M Y, h:i A') }}
                                </div>
                            </div>
                        @endif

                        @if($reminder->retry_count > 0)
                            <div class="mb-3">
                                <label class="font-weight-bold text-gray-800">Retry Count:</label>
                                <div class="mt-1">
                                    <span class="badge badge-warning">{{ $reminder->retry_count }} attempts</span>
                                    @if($reminder->last_retry_at)
                                        <br><small class="text-muted">Last retry: {{ $reminder->last_retry_at->format('d M Y, h:i A') }}</small>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($reminder->feeCategory)
                            <div class="mb-3">
                                <label class="font-weight-bold text-gray-800">Fee Category:</label>
                                <div class="mt-1">
                                    <span class="badge badge-secondary">{{ $reminder->feeCategory->name }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if($reminder->error_message)
                    <div class="alert alert-danger mt-3">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Error Details
                        </h6>
                        <p class="mb-0">{{ $reminder->error_message }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Message Content -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-comment mr-1"></i>Message Content
                </h6>
            </div>
            <div class="card-body">
                @if($reminder->message_content)
                    <div class="message-preview">
                        {{ $reminder->message_content }}
                    </div>
                @else
                    <div class="text-muted text-center py-4">
                        <i class="fas fa-comment-slash fa-2x mb-2"></i>
                        <p>No custom message content. Default template was used.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recipient Details -->
        @if($reminder->recipient_details)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-address-book mr-1"></i>Recipient Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @php $details = is_string($reminder->recipient_details) ? json_decode($reminder->recipient_details, true) : $reminder->recipient_details; @endphp
                        
                        @if(isset($details['email']))
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Email Address:</label>
                                <div class="mt-1">
                                    <i class="fas fa-envelope text-muted mr-1"></i>
                                    <a href="mailto:{{ $details['email'] }}">{{ $details['email'] }}</a>
                                </div>
                            </div>
                        @endif

                        @if(isset($details['phone']))
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Phone Number:</label>
                                <div class="mt-1">
                                    <i class="fas fa-phone text-muted mr-1"></i>
                                    <a href="tel:{{ $details['phone'] }}">{{ $details['phone'] }}</a>
                                </div>
                            </div>
                        @endif

                        @if(isset($details['student_name']))
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Student Name:</label>
                                <div class="mt-1">{{ $details['student_name'] }}</div>
                            </div>
                        @endif

                        @if(isset($details['enrollment_number']))
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Enrollment Number:</label>
                                <div class="mt-1">{{ $details['enrollment_number'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Activity Timeline -->
        @if($reminder->logs && $reminder->logs->count() > 0)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-1"></i>Activity Timeline
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($reminder->logs->sortByDesc('created_at') as $log)
                            <div class="timeline-item">
                                <div class="timeline-marker {{ $log->action }}"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst($log->action) }}</h6>
                                            @if($log->details)
                                                <p class="text-muted mb-2">{{ $log->details }}</p>
                                            @endif
                                            <small class="text-muted">
                                                <i class="fas fa-clock mr-1"></i>
                                                {{ $log->created_at->format('d M Y, h:i A') }}
                                                @if($log->performedBy)
                                                    by <strong>{{ $log->performedBy->name }}</strong>
                                                @endif
                                            </small>
                                        </div>
                                        @if($log->metadata)
                                            <button class="btn btn-sm btn-outline-info" data-toggle="collapse" data-target="#metadata-{{ $log->id }}">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        @endif
                                    </div>
                                    
                                    @if($log->metadata)
                                        <div class="collapse mt-2" id="metadata-{{ $log->id }}">
                                            <div class="card card-body bg-light">
                                                <small><pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre></small>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Student Information -->
        <div class="card shadow mb-4 info-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-user mr-1"></i>Student Information
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    @if($reminder->student->photo)
                        <img src="{{ asset('storage/' . $reminder->student->photo) }}" alt="Student Photo" class="rounded-circle" width="80" height="80">
                    @else
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                    @endif
                </div>

                <div class="mb-2">
                    <strong>Name:</strong><br>
                    <span class="text-muted">{{ $reminder->student->name }}</span>
                </div>
                <div class="mb-2">
                    <strong>Enrollment Number:</strong><br>
                    <span class="text-muted">{{ $reminder->student->enrollment_number }}</span>
                </div>
                <div class="mb-2">
                    <strong>Email:</strong><br>
                    <span class="text-muted">
                        @if($reminder->student->email)
                            <a href="mailto:{{ $reminder->student->email }}">{{ $reminder->student->email }}</a>
                        @else
                            Not available
                        @endif
                    </span>
                </div>
                <div class="mb-2">
                    <strong>Phone:</strong><br>
                    <span class="text-muted">
                        @if($reminder->student->student_mobile || $reminder->student->father_mobile)
                            <a href="tel:{{ $reminder->student->student_mobile ?: $reminder->student->father_mobile }}">
                                {{ $reminder->student->student_mobile ?: $reminder->student->father_mobile }}
                            </a>
                        @else
                            Not available
                        @endif
                    </span>
                </div>
                @if($reminder->student->batch)
                    <div class="mb-2">
                        <strong>Batch:</strong><br>
                        <span class="text-muted">{{ $reminder->student->batch->name }}</span>
                        @if($reminder->student->batch->course)
                            <br><small class="text-muted">{{ $reminder->student->batch->course->name }}</small>
                        @endif
                    </div>
                @endif
                <div class="mb-0">
                    <strong>Status:</strong><br>
                    <span class="badge badge-{{ $reminder->student->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($reminder->student->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ✅ FIXED: Fee Component Information (replacing invoice information) -->
        @if($reminder->studentFee)
            <div class="card shadow mb-4 info-card success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-money-bill mr-1"></i>Fee Component Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Fee Category:</strong><br>
                        <span class="text-muted">{{ $reminder->studentFee->feeCategory->name ?? 'N/A' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Total Amount:</strong><br>
                        <span class="text-success font-weight-bold">₹{{ number_format($reminder->studentFee->amount, 2) }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Paid Amount:</strong><br>
                        <span class="text-info font-weight-bold">₹{{ number_format($reminder->studentFee->paid_amount ?? 0, 2) }}</span>
                    </div>
                    @if($reminder->studentFee->concession_amount > 0)
                    <div class="mb-2">
                        <strong>Concession:</strong><br>
                        <span class="text-warning font-weight-bold">₹{{ number_format($reminder->studentFee->concession_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="mb-2">
                        <strong>Remaining Amount:</strong><br>
                        @php
                            $remainingAmount = $reminder->studentFee->amount - ($reminder->studentFee->paid_amount ?? 0) - ($reminder->studentFee->concession_amount ?? 0);
                        @endphp
                        <span class="text-{{ $remainingAmount > 0 ? 'danger' : 'success' }} font-weight-bold">
                            ₹{{ number_format($remainingAmount, 2) }}
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>Due Date:</strong><br>
                        <span class="text-muted">{{ $reminder->studentFee->due_date->format('d M Y') }}</span>
                        @if($reminder->studentFee->due_date < now() && $remainingAmount > 0)
                            <span class="badge badge-danger ml-1">Overdue</span>
                        @endif
                    </div>
                    <div class="mb-0">
                        <strong>Status:</strong><br>
                        <span class="badge badge-{{ $reminder->studentFee->status === 'paid' ? 'success' : ($reminder->studentFee->status === 'partial' ? 'warning' : 'danger') }}">
                            {{ ucfirst($reminder->studentFee->status) }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Quick Actions -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt mr-1"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                @if($reminder->status === 'pending')
                    <form action="{{ route('admin.payment-reminders.send', $reminder) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm btn-block" onclick="return confirm('Send this reminder now?')">
                            <i class="fas fa-paper-plane mr-1"></i> Send Now
                        </button>
                    </form>

                    <form action="{{ route('admin.payment-reminders.queue', $reminder) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-info btn-sm btn-block" onclick="return confirm('Queue this reminder for later?')">
                            <i class="fas fa-clock mr-1"></i> Queue for Later
                        </button>
                    </form>
                @endif

                @if($reminder->status === 'failed')
                    <form action="{{ route('admin.payment-reminders.send', $reminder) }}" method="POST" class="mb-2">
                        @csrf
                        <input type="hidden" name="retry" value="1">
                        <button type="submit" class="btn btn-warning btn-sm btn-block" onclick="return confirm('Retry sending this reminder?')">
                            <i class="fas fa-redo mr-1"></i> Retry Sending
                        </button>
                    </form>
                @endif

                @if(in_array($reminder->status, ['pending', 'scheduled']))
                    <form action="{{ route('admin.payment-reminders.cancel', $reminder) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm btn-block" onclick="return confirm('Cancel this reminder?')">
                            <i class="fas fa-times mr-1"></i> Cancel Reminder
                        </button>
                    </form>
                @endif

                @if(in_array($reminder->status, ['pending', 'scheduled', 'failed']))
                    <a href="{{ route('admin.payment-reminders.edit', $reminder) }}" class="btn btn-primary btn-sm btn-block mb-2">
                        <i class="fas fa-edit mr-1"></i> Edit Reminder
                    </a>
                @endif

                <div class="dropdown-divider my-3"></div>

                <a href="{{ route('admin.payment-reminders.create') }}?student_id={{ $reminder->student->id }}" class="btn btn-outline-primary btn-sm btn-block mb-2">
                    <i class="fas fa-plus mr-1"></i> Create New Reminder
                </a>

                <a href="{{ route('admin.financials.student.ledger', $reminder->student) }}" class="btn btn-outline-info btn-sm btn-block mb-2">
                    <i class="fas fa-file-invoice mr-1"></i> View Student Ledger
                </a>

                @if($reminder->student->defaulterRecord)
                    <a href="{{ route('admin.payment-defaulters.show', $reminder->student) }}" class="btn btn-outline-warning btn-sm btn-block">
                        <i class="fas fa-exclamation-triangle mr-1"></i> View Defaulter Details
                    </a>
                @endif
            </div>
        </div>

        <!-- Statistics -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-chart-bar mr-1"></i>Reminder Statistics
                </h6>
            </div>
            <div class="card-body">
                @php
                    $studentReminders = $reminder->student->paymentReminders;
                    $totalReminders = $studentReminders->count();
                    $sentReminders = $studentReminders->where('status', 'sent')->count();
                    $failedReminders = $studentReminders->where('status', 'failed')->count();
                    $pendingReminders = $studentReminders->where('status', 'pending')->count();
                @endphp

                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="metric-card bg-primary">
                            <div class="metric-value">{{ $totalReminders }}</div>
                            <div class="metric-label">Total Reminders</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="metric-card bg-success">
                            <div class="metric-value">{{ $sentReminders }}</div>
                            <div class="metric-label">Sent</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric-card bg-danger">
                            <div class="metric-value">{{ $failedReminders }}</div>
                            <div class="metric-label">Failed</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric-card bg-warning">
                            <div class="metric-value">{{ $pendingReminders }}</div>
                            <div class="metric-label">Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Reminders -->
        @if($reminder->student->paymentReminders->where('id', '!=', $reminder->id)->count() > 0)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">
                        <i class="fas fa-list mr-1"></i>Other Reminders for this Student
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($reminder->student->paymentReminders->where('id', '!=', $reminder->id)->sortByDesc('created_at')->take(5) as $otherReminder)
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <small class="text-muted">{{ $otherReminder->scheduled_date->format('d M Y') }}</small><br>
                                <span class="badge badge-sm badge-{{ $otherReminder->status === 'sent' ? 'success' : ($otherReminder->status === 'failed' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($otherReminder->status) }}
                                </span>
                                <small class="ml-1">{{ ucwords(str_replace('_', ' ', $otherReminder->reminder_type)) }}</small>
                            </div>
                            <a href="{{ route('admin.payment-reminders.show', $otherReminder) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    @endforeach
                    
                    @if($reminder->student->paymentReminders->where('id', '!=', $reminder->id)->count() > 5)
                        <div class="text-center mt-2">
                            <a href="{{ route('admin.payment-reminders.index') }}?student_id={{ $reminder->student->id }}" class="btn btn-sm btn-link">
                                View All Reminders for this Student
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Action Confirmation Modal -->
<div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="actionMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Handle action confirmations
    let actionForm = null;
    
    $('.btn[onclick]').each(function() {
        const originalOnclick = $(this).attr('onclick');
        $(this).removeAttr('onclick');
        
        $(this).click(function(e) {
            e.preventDefault();
            
            const actionText = $(this).text().trim();
            const confirmText = originalOnclick.match(/confirm\('([^']+)'\)/);
            
            if (confirmText) {
                $('#actionMessage').text(confirmText[1]);
                actionForm = $(this).closest('form');
                $('#actionModal').modal('show');
            }
        });
    });
    
    $('#confirmAction').click(function() {
        if (actionForm) {
            actionForm.submit();
        }
        $('#actionModal').modal('hide');
    });

    // Auto-refresh status if reminder is processing
    @if($reminder->status === 'processing')
        setInterval(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    @endif

    // Tooltip initialization
    $('[data-toggle="tooltip"]').tooltip();

    // Copy to clipboard functionality
    $('.copy-btn').click(function() {
        const text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
            const btn = $(this);
            const originalText = btn.html();
            btn.html('<i class="fas fa-check"></i> Copied!');
            setTimeout(function() {
                btn.html(originalText);
            }, 2000);
        });
    });

    // Expand/collapse metadata
    $('.metadata-toggle').click(function() {
        const target = $(this).data('target');
        $(target).toggleClass('show');
        const icon = $(this).find('i');
        icon.toggleClass('fa-chevron-down fa-chevron-up');
    });
});

// Print functionality
function printReminder() {
    window.print();
}

// Export functionality
function exportReminder() {
    const reminderData = {
        id: {{ $reminder->id }},
        student: '{{ $reminder->student->name }}',
        type: '{{ $reminder->reminder_type }}',
        channel: '{{ $reminder->channel }}',
        status: '{{ $reminder->status }}',
        scheduled_date: '{{ $reminder->scheduled_date->toISOString() }}',
        message: '{{ addslashes($reminder->message_content) }}'
    };
    
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(reminderData, null, 2));
    const downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "reminder_{{ $reminder->id }}.json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
}
</script>

<!-- Print styles -->
<style media="print">
    .card-header, .btn, .sidebar, .navbar, .footer {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .col-lg-8 {
        width: 100% !important;
    }
</style>
@endpush