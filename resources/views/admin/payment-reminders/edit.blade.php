@extends('layouts.theme')

@section('title', 'Edit Payment Reminder')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.channel-option {
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}
.channel-option:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}
.channel-option.selected {
    border-color: #007bff;
    background-color: #e3f2fd;
}
.status-badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
}
.status-pending { background-color: #ffc107; color: #212529; }
.status-sent { background-color: #28a745; color: white; }
.status-failed { background-color: #dc3545; color: white; }
.status-cancelled { background-color: #6c757d; color: white; }
.reminder-preview {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 0.5rem;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit mr-2"></i>Edit Payment Reminder
        </h1>
        <div>
            <a href="{{ route('admin.payment-reminders.show', $reminder) }}" class="btn btn-info btn-sm">
                <i class="fas fa-eye mr-1"></i> View Details
            </a>
            <a href="{{ route('admin.payment-reminders.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Reminders
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Reminder Details</h6>
                    <span class="status-badge status-{{ $reminder->status }}">{{ ucfirst($reminder->status) }}</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.payment-reminders.update', $reminder) }}" method="POST" id="reminderForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Current Student & Fee Component Info (Read-only) -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-user text-primary mr-1"></i>Student
                                </label>
                                <div class="form-control-static p-2 bg-light rounded">
                                    <strong>{{ $reminder->student->name }}</strong><br>
                                    <small class="text-muted">{{ $reminder->student->enrollment_number }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-money-bill text-primary mr-1"></i>Fee Component
                                </label>
                                <div class="form-control-static p-2 bg-light rounded">
                                    @if($reminder->studentFee)
                                        <strong>{{ $reminder->studentFee->feeCategory->name ?? 'N/A' }}</strong><br>
                                        @php
                                            $remainingAmount = $reminder->studentFee->amount - ($reminder->studentFee->paid_amount ?? 0) - ($reminder->studentFee->concession_amount ?? 0);
                                        @endphp
                                        <small class="text-muted">₹{{ number_format($remainingAmount, 2) }} remaining - {{ ucfirst($reminder->studentFee->status) }}</small>
                                    @else
                                        <span class="text-muted">All Fee Components</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Message Content -->
                        <div class="mb-3">
                            <label for="message_content" class="form-label">
                                <i class="fas fa-comment text-primary mr-1"></i>Message Content
                            </label>
                            <div class="template-variables mb-2">
                                <small>
                                    <strong>Available variables:</strong> 
                                    {student_name}, {enrollment_number}, {amount}, {due_date}, {college_name}, {fee_type}
                                </small>
                            </div>
                            <textarea name="message_content" id="message_content" class="form-control" rows="4" 
                                      {{ $reminder->status === 'sent' ? 'readonly' : '' }}>{{ $reminder->message_content }}</textarea>
                            
                            <div class="reminder-preview" id="message_preview" style="display: none;">
                                <strong>Preview:</strong>
                                <div id="preview_content" class="mt-2"></div>
                            </div>
                            
                            @if($reminder->status === 'sent')
                                <small class="text-muted">Message cannot be edited for sent reminders</small>
                            @endif
                        </div>

                        <!-- Retry Information (if applicable) -->
                        @if($reminder->retry_count > 0 || $reminder->error_message)
                            <div class="mb-3">
                                <label class="form-label text-warning">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Retry Information
                                </label>
                                <div class="alert alert-warning">
                                    @if($reminder->retry_count > 0)
                                        <p class="mb-1"><strong>Retry Count:</strong> {{ $reminder->retry_count }}</p>
                                        @if($reminder->last_retry_at)
                                            <p class="mb-1"><strong>Last Retry:</strong> {{ $reminder->last_retry_at->format('d M Y, h:i A') }}</p>
                                        @endif
                                    @endif
                                    
                                    @if($reminder->error_message)
                                        <p class="mb-0"><strong>Error:</strong> {{ $reminder->error_message }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Communication Channel -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-satellite-dish text-primary mr-1"></i>Communication Channel
                            </label>
                            <div class="row">
                                @php
                                $channels = [
                                    'email' => ['icon' => 'envelope', 'label' => 'Email'],
                                    'sms' => ['icon' => 'sms', 'label' => 'SMS'],
                                    'whatsapp' => ['icon' => 'whatsapp', 'label' => 'WhatsApp'],
                                    'phone_call' => ['icon' => 'phone', 'label' => 'Phone Call'],
                                    'physical_notice' => ['icon' => 'file-alt', 'label' => 'Physical Notice']
                                ];
                                @endphp
                                
                                @foreach($channels as $channel => $config)
                                <div class="col-md-4 mb-2">
                                    <div class="channel-option {{ $reminder->channel === $channel ? 'selected' : '' }}" 
                                         data-channel="{{ $channel }}"
                                         {{ $reminder->status === 'sent' ? 'style=pointer-events:none;opacity:0.6;' : '' }}>
                                        <div class="text-center">
                                            <i class="fab fa-{{ $config['icon'] }} fa-2x mb-2"></i>
                                            <div class="font-weight-bold">{{ $config['label'] }}</div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <input type="hidden" name="channel" id="selected_channel" value="{{ $reminder->channel }}">
                            
                            @if($reminder->status === 'sent')
                                <small class="text-muted">Channel cannot be changed for sent reminders</small>
                            @endif
                        </div>

                        <!-- Scheduling -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">
                                    <i class="fas fa-calendar text-primary mr-1"></i>Scheduled Date
                                </label>
                                <input type="datetime-local" name="scheduled_date" id="scheduled_date" class="form-control"
                                       value="{{ $reminder->scheduled_date->format('Y-m-d\TH:i') }}"
                                       {{ $reminder->status === 'sent' ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label for="reminder_type" class="form-label">
                                    <i class="fas fa-tag text-primary mr-1"></i>Reminder Type
                                </label>
                                <select name="reminder_type" id="reminder_type" class="form-control"
                                        {{ $reminder->status === 'sent' ? 'disabled' : '' }}>
                                    <option value="upcoming_due" {{ $reminder->reminder_type === 'upcoming_due' ? 'selected' : '' }}>Upcoming Due</option>
                                    <option value="overdue" {{ $reminder->reminder_type === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                    <option value="escalation" {{ $reminder->reminder_type === 'escalation' ? 'selected' : '' }}>Escalation</option>
                                    <option value="final_notice" {{ $reminder->reminder_type === 'final_notice' ? 'selected' : '' }}>Final Notice</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            @if($reminder->status !== 'sent')
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Update Reminder
                                    </button>
                                    
                                    @if(in_array($reminder->status, ['pending', 'scheduled']))
                                        <button type="submit" name="send_now" value="1" class="btn btn-success">
                                            <i class="fas fa-paper-plane mr-1"></i> Update & Send Now
                                        </button>
                                    @endif
                                </div>
                            @else
                                <div>
                                    <span class="text-muted">This reminder has been sent and cannot be modified.</span>
                                </div>
                            @endif
                            
                            @if($reminder->status === 'failed' && $reminder->canRetry())
                                <div>
                                    <button type="submit" name="retry" value="1" class="btn btn-warning">
                                        <i class="fas fa-redo mr-1"></i> Retry Sending
                                    </button>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt mr-1"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    @if($reminder->status === 'failed' && $reminder->canRetry())
                        <form action="{{ route('admin.payment-reminders.retry', $reminder) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm btn-block" onclick="return confirm('Retry sending this reminder?')">
                                <i class="fas fa-redo mr-1"></i> Retry Sending
                            </button>
                        </form>
                    @endif
                    
                    @if(in_array($reminder->status, ['pending', 'scheduled']))
                        <form action="{{ route('admin.payment-reminders.cancel', $reminder) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm btn-block mb-2" onclick="return confirm('Cancel this reminder?')">
                                <i class="fas fa-times mr-1"></i> Cancel Reminder
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('admin.payment-reminders.create') }}?student_id={{ $reminder->student->id }}" class="btn btn-info btn-sm btn-block mb-2">
                        <i class="fas fa-plus mr-1"></i> Create New Reminder
                    </a>

                    <a href="{{ route('admin.financials.student.ledger', $reminder->student) }}" class="btn btn-secondary btn-sm btn-block">
                        <i class="fas fa-file-invoice mr-1"></i> View Student Ledger
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Channel selection (only if not sent)
    @if($reminder->status !== 'sent')
    $('.channel-option').click(function() {
        if (!$(this).hasClass('selected')) {
            $('.channel-option').removeClass('selected');
            $(this).addClass('selected');
            $('#selected_channel').val($(this).data('channel'));
            updateMessagePreview();
        }
    });
    @endif

    // Message content change
    $('#message_content, #reminder_type').on('input change', function() {
        updateMessagePreview();
    });

    // Initial preview
    updateMessagePreview();

    function updateMessagePreview() {
        const messageContent = $('#message_content').val();
        const reminderType = $('#reminder_type').val();
        
        if (messageContent) {
            let previewText = messageContent;
            
            // Replace variables with actual data for preview
            previewText = previewText
                .replace(/{student_name}/g, '{{ $reminder->student->name }}')
                .replace(/{enrollment_number}/g, '{{ $reminder->student->enrollment_number }}')
                .replace(/{amount}/g, '{{ $reminder->studentFee ? number_format($remainingAmount ?? 0, 2) : "5,000.00" }}')
                .replace(/{due_date}/g, '{{ $reminder->studentFee ? $reminder->studentFee->due_date->format("d M Y") : now()->format("d M Y") }}')
                .replace(/{college_name}/g, '{{ config("app.name") }}')
                .replace(/{fee_type}/g, '{{ $reminder->studentFee ? $reminder->studentFee->feeCategory->name ?? "Tuition Fee" : "Fee" }}');

            $('#preview_content').text(previewText);
            $('#message_preview').show();
        } else {
            $('#message_preview').hide();
        }
    }

    // Form validation
    $('#reminderForm').submit(function(e) {
        if (!$('#selected_channel').val()) {
            e.preventDefault();
            alert('Please select a communication channel.');
            return false;
        }
    });

    // Confirmation for actions that might affect sent reminders
    $('button[name="send_now"], button[name="retry"]').click(function(e) {
        const action = $(this).attr('name') === 'send_now' ? 'send' : 'retry';
        if (!confirm(`Are you sure you want to ${action} this reminder now?`)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush