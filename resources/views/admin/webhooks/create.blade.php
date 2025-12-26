@extends('layouts.theme')
@section('title', 'Add New Webhook')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Add New Webhook Endpoint</h1>
    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Webhook Configuration</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.webhooks.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-group mb-3">
                        <label for="url" class="form-label font-weight-bold">Endpoint URL <span class="text-danger">*</span></label>
                        <input type="url" name="url" id="url" class="form-control @error('url') is-invalid @enderror" 
                               placeholder="https://your-app.com/webhook-receiver" 
                               value="{{ old('url') }}" required>
                        <small class="form-text text-muted">
                            The complete URL that will receive POST requests when events occur.
                        </small>
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="event_name" class="form-label font-weight-bold">Event Type <span class="text-danger">*</span></label>
                        <select name="event_name" id="event_name" class="form-control @error('event_name') is-invalid @enderror" required>
                            <option value="">-- Select an Event Type --</option>
                            
                            <optgroup label="💰 Payment & Financial Events">
                                <option value="payment.created" {{ old('event_name') == 'payment.created' ? 'selected' : '' }}>
                                    Payment Recorded
                                </option>
                                <option value="invoice.generated" {{ old('event_name') == 'invoice.generated' ? 'selected' : '' }}>
                                    Invoice Generated
                                </option>
                                <option value="receipt.generated" {{ old('event_name') == 'receipt.generated' ? 'selected' : '' }}>
                                    Receipt Generated
                                </option>
                                <option value="fee.reminder.sent" {{ old('event_name') == 'fee.reminder.sent' ? 'selected' : '' }}>
                                    Fee Reminder Sent
                                </option>
                                <option value="daily.summary" {{ old('event_name') == 'daily.summary' ? 'selected' : '' }}>
                                    Daily Summary Report (5:00 PM weekdays)
                                </option>
                            </optgroup>

                            <optgroup label="👨‍🎓 Student Management">
                                <option value="student.created" {{ old('event_name') == 'student.created' ? 'selected' : '' }}>
                                    Student Created
                                </option>
                                <option value="admission.created" {{ old('event_name') == 'admission.created' ? 'selected' : '' }}>
                                    Admission Created
                                </option>
                                <option value="admission.approved" {{ old('event_name') == 'admission.approved' ? 'selected' : '' }}>
                                    Admission Approved
                                </option>
                                <option value="certificate.generated" {{ old('event_name') == 'certificate.generated' ? 'selected' : '' }}>
                                    Certificate Generated
                                </option>
                                
                                {{-- [NEW] Added Daily Absent Report --}}
                                <option value="attendance.daily_absent" {{ old('event_name') == 'attendance.daily_absent' ? 'selected' : '' }}>
                                    Daily Absent Report (Once Daily)
                                </option>
                            </optgroup>

                            <optgroup label="📞 Lead Management">
                                <option value="enquiry.created" {{ old('event_name') == 'enquiry.created' ? 'selected' : '' }}>
                                    Enquiry Submitted
                                </option>
                            </optgroup>

                            <optgroup label="📅 Attendance & Leave">
                                <option value="attendance.marked" {{ old('event_name') == 'attendance.marked' ? 'selected' : '' }}>
                                    Attendance Marked
                                </option>
                                <option value="leave.application.created" {{ old('event_name') == 'leave.application.created' ? 'selected' : '' }}>
                                    Leave Application Created
                                </option>
                                <option value="leave.application.status.changed" {{ old('event_name') == 'leave.application.status.changed' ? 'selected' : '' }}>
                                    Leave Status Changed
                                </option>
                            </optgroup>
                        </select>
                        <small class="form-text text-muted">
                            Choose which event should trigger this webhook notification.
                        </small>
                        @error('event_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="description" class="form-label font-weight-bold">Description (Optional)</label>
                        <textarea name="description" id="description" class="form-control" rows="3" 
                                  placeholder="Brief description of what this webhook is used for...">{{ old('description') }}</textarea>
                        <small class="form-text text-muted">
                            Help your team understand the purpose of this webhook.
                        </small>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" 
                               {{ old('is_active', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            <strong>Active</strong> - Start sending notifications immediately
                        </label>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.webhooks.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Webhook
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">📖 Webhook Guide</h6>
            </div>
            <div class="card-body">
                <h6 class="font-weight-bold">What are webhooks?</h6>
                <p class="small text-muted mb-3">
                    Webhooks automatically notify your external systems when important events happen in your application.
                </p>

                <h6 class="font-weight-bold">Security</h6>
                <p class="small text-muted mb-3">
                    Each webhook includes an <code>X-App-Signature</code> header that you can verify using HMAC-SHA256.
                </p>

                <h6 class="font-weight-bold">Payload Format</h6>
                <p class="small text-muted mb-3">
                    All webhooks send JSON data with event details, student information, and relevant attachments.
                </p>

                <h6 class="font-weight-bold">Retry Policy</h6>
                <p class="small text-muted mb-0">
                    Failed deliveries are automatically retried. Check the logs to monitor webhook health.
                </p>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">🔗 Integration Examples</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="font-weight-bold small">CRM Integration</h6>
                    <p class="small text-muted">Send enquiries to your CRM system automatically.</p>
                </div>
                
                <div class="mb-3">
                    <h6 class="font-weight-bold small">Accounting Software</h6>
                    <p class="small text-muted">Sync payments with QuickBooks, Xero, or Tally.</p>
                </div>
                
                <div class="mb-3">
                    <h6 class="font-weight-bold small">Communication Tools</h6>
                    <p class="small text-muted">Send notifications to Slack, Discord, or email systems.</p>
                </div>
                
                <div>
                    <h6 class="font-weight-bold small">Analytics</h6>
                    <p class="small text-muted mb-0">Track events in Google Analytics or custom dashboards.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('event_name').addEventListener('change', function() {
    const eventDescriptions = {
        'payment.created': 'Triggered when a student makes a payment. Includes payment details, invoice, and receipt.',
        'student.created': 'Triggered when a new student is added to the system.',
        'enquiry.created': 'Triggered when someone submits an enquiry form on your website.',
        'admission.created': 'Triggered when a new admission is created.',
        'admission.approved': 'Triggered when an admission is approved.',
        'invoice.generated': 'Triggered when a new invoice is generated for a student.',
        'receipt.generated': 'Triggered when a payment receipt is generated.',
        'fee.reminder.sent': 'Triggered when fee reminder notifications are sent.',
        'attendance.marked': 'Triggered when attendance is marked for students.',
        'leave.application.created': 'Triggered when a staff member applies for leave.',
        'leave.application.status.changed': 'Triggered when leave application status changes.',
        'certificate.generated': 'Triggered when certificates are generated for students.',
        'daily.summary': 'Automated daily report with payment totals and attendance summary. Sent at 5:00 PM on working days (Monday-Saturday). Includes payment amounts, student counts, and attendance percentages.',
        // [NEW] Added Absent Report Description
        'attendance.daily_absent': 'Triggers once daily after the "Present Cutoff Time". Sends a list of all students who have not marked attendance.'
    };
    
    const description = eventDescriptions[this.value];
    if (description) {
        document.getElementById('description').placeholder = description;
        document.getElementById('description').value = description; // Auto-fill value too
    }
});
</script>
@endsection