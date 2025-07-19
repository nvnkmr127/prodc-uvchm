{{-- resources/views/admin/webhooks/edit.blade.php - FIXED VERSION --}}
@extends('layouts.theme')
@section('title', 'Edit Webhook')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    .form-section {
        background: #f8f9fc;
        border-radius: 0.35rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .form-section h6 {
        color: #5a5c69;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e3e6f0;
    }
    
    .event-preview {
        background: #e8f4f8;
        border: 1px solid #bee5eb;
        border-radius: 0.25rem;
        padding: 1rem;
        margin-top: 0.5rem;
    }
    
    .config-item {
        padding: 0.75rem;
        border-left: 3px solid #e3e6f0;
        margin-bottom: 0.5rem;
        background: #f8f9fc;
        border-radius: 0 0.25rem 0.25rem 0;
    }
    
    .config-item.active {
        border-left-color: #1cc88a;
        background: #f0f9f5;
    }
    
    .config-item.inactive {
        border-left-color: #858796;
        background: #f5f5f5;
    }
    
    .security-warning {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 0.25rem;
        padding: 0.75rem;
        margin-bottom: 1rem;
    }
    
    .integration-code {
        background: #2d2d2d;
        color: #f8f8f2;
        border-radius: 0.375rem;
        padding: 1rem;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        font-size: 0.875rem;
        overflow-x: auto;
    }
    
    .validation-feedback {
        display: block;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .validation-feedback.positive {
        color: #28a745;
    }

    .validation-feedback.negative {
        color: #dc3545;
    }
    
    .btn-test {
        position: relative;
        overflow: hidden;
    }
    
    .btn-test.testing::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: shimmer 1.5s infinite;
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    
    .tooltip-custom {
        position: relative;
        cursor: help;
    }
    
    .tooltip-custom::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s;
        z-index: 1000;
    }
    
    .tooltip-custom:hover::after {
        opacity: 1;
    }
    
    .stats-mini {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .stats-mini .stat {
        text-align: center;
        flex: 1;
        padding: 0.5rem;
        background: #f8f9fc;
        border-radius: 0.25rem;
    }
    
    .stats-mini .stat .number {
        font-size: 1.25rem;
        font-weight: bold;
        color: #5a5c69;
    }
    
    .stats-mini .stat .label {
        font-size: 0.75rem;
        color: #858796;
        text-transform: uppercase;
    }

    .timeline {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .timeline-item {
        border-left: 2px solid #e3e6f0;
        padding-left: 1.5rem;
        margin-left: 0.5rem;
        padding-bottom: 1.5rem;
        position: relative;
    }

    .timeline-item:last-child {
        border-left: none;
        padding-bottom: 0;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 5px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #e3e6f0;
        border: 2px solid #fff;
    }

    .timeline-item.success::before {
        background: #28a745;
    }

    .timeline-item.error::before {
        background: #dc3545;
    }

    @media (max-width: 768px) {
        .form-section {
            padding: 1rem;
        }
        
        .stats-mini {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .config-item {
            padding: 0.5rem;
        }
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit text-primary"></i> Edit Webhook
        </h1>
        <p class="mb-0 text-muted">Modify webhook configuration, security, and behavior.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-info btn-test" id="testBtn">
            <i class="fas fa-paper-plane"></i> Test Webhook
        </button>
        <a href="{{ route('admin.webhooks.show', $webhook) }}" class="btn btn-sm btn-info">
            <i class="fas fa-eye"></i> View Details
        </a>
        <a href="{{ route('admin.webhooks.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

{{-- Alert Messages Placeholder --}}
<div id="alert-container"></div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('admin.webhooks.update', $webhook) }}" method="POST" id="webhookForm">
            @csrf
            @method('PUT')
            
            {{-- Basic Configuration --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog"></i> Basic Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-section">
                        <h6><i class="fas fa-link"></i> Endpoint Configuration</h6>
                        
                        <div class="form-group mb-3">
                            <label for="url" class="form-label font-weight-bold">
                                Endpoint URL 
                                <span class="text-danger">*</span>
                                <i class="fas fa-info-circle tooltip-custom" 
                                   data-tooltip="The complete URL that will receive POST requests"></i>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                </div>
                               <input type="url" 
       name="url" 
       id="url" 
       class="form-control @error('url') is-invalid @enderror" 
       value="{{ old('url', $webhook->url ?? '') }}" 
       required
       placeholder="https://your-domain.com/webhook-endpoint"
       onkeyup="validateUrl()">
                            </div>
                            <div id="urlValidation" class="validation-feedback"></div>
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb"></i> 
                                Must be a valid HTTPS URL for security.
                            </small>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="event_name" class="form-label font-weight-bold">
                                Event Type 
                                <span class="text-danger">*</span>
                                <i class="fas fa-info-circle tooltip-custom" 
                                   data-tooltip="Choose which event should trigger this webhook"></i>
                            </label>
                           <select name="event_name" id="event_name" class="form-control @error('event_name') is-invalid @enderror" required>
    <option value="">-- Select an Event Type --</option>
    
    @if(isset($eventCategories) && is_array($eventCategories))
        @foreach($eventCategories as $category => $data)
            @if(is_array($data) && isset($data['events']) && is_array($data['events']))
                <optgroup label="{{ is_string($category) ? $category : 'Category' }}">
                    @foreach($data['events'] as $eventKey => $eventInfo)
                        @if(is_string($eventKey) && is_array($eventInfo))
                            <option value="{{ $eventKey }}" 
                                    data-description="{{ isset($eventInfo['description']) && is_string($eventInfo['description']) ? $eventInfo['description'] : '' }}"
                                    {{ old('event_name', ($webhook->event_name ?? '')) === $eventKey ? 'selected' : '' }}>
                                {{ isset($eventInfo['name']) && is_string($eventInfo['name']) ? $eventInfo['name'] : $eventKey }}
                            </option>
                        @endif
                    @endforeach
                </optgroup>
            @endif
        @endforeach
    @endif
</select>
                            <div id="eventPreview" class="event-preview" style="display: none;">
                                <p id="eventDescription" class="mb-0"></p>
                            </div>
                            @error('event_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label font-weight-bold">
                                Description 
                                <span class="text-muted">(Optional)</span>
                            </label>
                           <textarea name="description" 
          id="description" 
          class="form-control" 
          rows="3" 
          maxlength="500"
          placeholder="Brief description of what this webhook is used for...">{{ old('description', ($webhook->description ?? '')) }}</textarea>

                            <div class="d-flex justify-content-between">
                                <small class="form-text text-muted">
                                    <i class="fas fa-users"></i> Help your team understand this webhook's purpose.
                                </small>
                                <small class="text-muted" id="charCount">0/500</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Security Settings --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-shield-alt"></i> Security
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-section">
                        <h6><i class="fas fa-key"></i> Signing Secret</h6>
                        <p class="text-muted small">We sign webhook events with this secret to verify their authenticity. It's crucial to keep this secret secure.</p>
                        
                        <div class="form-group">
                            <label for="signing_secret" class="font-weight-bold">Secret Key</label>
                             <div class="input-group">
                                <input type="password" id="signing_secret" class="form-control" value="{{ $webhook->signing_secret ?? '••••••••••••••••••••' }}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleSecretBtn" title="Show/Hide Secret">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="copySecretBtn" title="Copy Secret">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button class="btn btn-outline-warning" type="button" id="regenerateSecretBtn" title="Regenerate Secret">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Use this key to verify the `X-Webhook-Signature` header in received payloads.</small>
                        </div>
                         <div class="security-warning mt-3">
                            <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong> Regenerating the key will invalidate the current one. Update your endpoint handler immediately.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Advanced Settings --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-sliders-h"></i> Advanced Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-section">
                        <h6><i class="fas fa-toggle-on"></i> Status & Behavior</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', $webhook->is_active ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong><i class="fas fa-power-off"></i> Active</strong>
                                        <br><small class="text-muted">Enable to send notifications.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="verify_ssl" id="verify_ssl" class="form-check-input" value="1" {{ old('verify_ssl', $webhook->verify_ssl ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="verify_ssl">
                                        <strong><i class="fas fa-lock"></i> Verify SSL</strong>
                                        <br><small class="text-muted">Validate endpoint SSL certificate.</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6><i class="fas fa-clock"></i> Performance & Reliability</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="timeout_seconds" class="form-label font-weight-bold">
                                        Timeout 
                                        <i class="fas fa-info-circle tooltip-custom" 
                                           data-tooltip="How long to wait for a response from your endpoint"></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               name="timeout_seconds" 
                                               id="timeout_seconds" 
                                               class="form-control @error('timeout_seconds') is-invalid @enderror" 
                                               value="{{ old('timeout_seconds', $webhook->timeout_seconds ?? 30) }}" 
                                               min="5" 
                                               max="120"
                                               step="5">
                                        <div class="input-group-append">
                                            <span class="input-group-text">seconds</span>
                                        </div>
                                    </div>
                                    @error('timeout_seconds')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="retry_attempts" class="form-label font-weight-bold">
                                        Retry Attempts 
                                        <i class="fas fa-info-circle tooltip-custom" 
                                           data-tooltip="Number of retry attempts for failed requests"></i>
                                    </label>
                                    <select name="retry_attempts" id="retry_attempts" class="form-control">
                                        <option value="0" {{ old('retry_attempts', $webhook->retry_attempts ?? 3) == 0 ? 'selected' : '' }}>No retries</option>
                                        <option value="1" {{ old('retry_attempts', $webhook->retry_attempts ?? 3) == 1 ? 'selected' : '' }}>1 retry</option>
                                        <option value="3" {{ old('retry_attempts', $webhook->retry_attempts ?? 3) == 3 ? 'selected' : '' }}>3 retries (Recommended)</option>
                                        <option value="5" {{ old('retry_attempts', $webhook->retry_attempts ?? 3) == 5 ? 'selected' : '' }}>5 retries</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="card shadow mb-4">
                <div class="card-body text-right">
                    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg" id="updateBtn">
                        <i class="fas fa-save"></i> Update Webhook
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        {{-- Current Configuration Summary --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-info-circle"></i> Current Configuration
                </h6>
            </div>
            <div class="card-body">
                <div class="config-item {{ ($webhook->is_active ?? false) ? 'active' : 'inactive' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold">Status:</span>
                        @if($webhook->is_active ?? false)
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                        @else
                            <span class="badge badge-secondary"><i class="fas fa-pause-circle"></i> Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="config-item">
    <div class="font-weight-bold mb-1">Event Type:</div>
    <div class="text-muted">
        @if(isset($currentEventInfo) && is_array($currentEventInfo))
            {{ isset($currentEventInfo['name']) && is_string($currentEventInfo['name']) ? $currentEventInfo['name'] : 'Unknown Event' }}
            @if(isset($currentEventInfo['category']) && is_string($currentEventInfo['category']))
                <br><small>Category: {{ $currentEventInfo['category'] }}</small>
            @endif
        @else
            {{ isset($webhook->event_name) && is_string($webhook->event_name) ? $webhook->event_name : 'No event selected' }}
        @endif
    </div>
</div>
                <div class="config-item">
    <div class="font-weight-bold mb-1">Endpoint:</div>
    <div class="text-muted small" style="word-break: break-all;">
        {{ isset($webhook->url) && is_string($webhook->url) ? $webhook->url : 'No URL set' }}
    </div>
</div>
                <div class="config-item">
                    <div class="d-flex justify-content-between">
                        <span class="font-weight-bold">Created:</span>
                        <span class="text-muted">{{ optional($webhook->created_at)->format('M d, Y') ?? 'Unknown' }}</span>
                    </div>
                </div>
                <div class="config-item">
                    <div class="d-flex justify-content-between">
                        <span class="font-weight-bold">Last Updated:</span>
                        <span class="text-muted">{{ optional($webhook->updated_at)->diffForHumans() ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Recent Activity/Logs --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                 <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-history"></i> Recent Deliveries
                </h6>
            </div>
            <div class="card-body">
                @if($recentDeliveries && $recentDeliveries->count() > 0)
                <ul class="timeline">
                    @foreach($recentDeliveries as $delivery)
                    <li class="timeline-item {{ ($delivery->status_code ?? 0) >= 200 && ($delivery->status_code ?? 0) < 300 ? 'success' : 'error' }}">
                        <strong class="d-block">
                            @if(($delivery->status_code ?? 0) >= 200 && ($delivery->status_code ?? 0) < 300)
                                <i class="fas fa-check-circle text-success"></i> Success ({{ $delivery->status_code ?? 'Unknown' }})
                            @else
                                <i class="fas fa-exclamation-triangle text-danger"></i> Failed ({{ $delivery->status_code ?? 'Unknown' }})
                            @endif
                        </strong>
                        <small class="text-muted">{{ optional($delivery->created_at)->diffForHumans() ?? 'Unknown time' }}</small>
                        <a href="#" class="float-right small">View Log</a>
                    </li>
                    @endforeach
                </ul>
                <div class="text-center mt-3">
                    <a href="{{ route('admin.webhooks.show', $webhook) }}#logs" class="btn btn-sm btn-outline-primary">View All Logs</a>
                </div>
                @else
                <div class="text-center text-muted">
                    <i class="fas fa-box-open fa-2x mb-2"></i>
                    <p>No recent deliveries found.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const webhookId = '{{ isset($webhook->id) ? $webhook->id : '' }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
     // Toastr options
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-bottom-right",
    };

    // --- Form Elements ---
    const eventSelect = document.getElementById('event_name');
    const eventPreview = document.getElementById('eventPreview');
    const eventDescription = document.getElementById('eventDescription');
    const descriptionTextarea = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    const urlInput = document.getElementById('url');
    const urlValidation = document.getElementById('urlValidation');
    const testBtn = document.getElementById('testBtn');
    const webhookForm = document.getElementById('webhookForm');
    const updateBtn = document.getElementById('updateBtn');

    // --- Security Elements ---
    const toggleSecretBtn = document.getElementById('toggleSecretBtn');
    const copySecretBtn = document.getElementById('copySecretBtn');
    const regenerateSecretBtn = document.getElementById('regenerateSecretBtn');
    const secretInput = document.getElementById('signing_secret');

    // --- Initializations ---
    if (eventSelect) updateEventPreview();
    if (descriptionTextarea) updateCharCount();
    if (urlInput) validateUrl();

    // --- Event Listeners ---
    if (eventSelect) eventSelect.addEventListener('change', updateEventPreview);
    if (descriptionTextarea) descriptionTextarea.addEventListener('input', updateCharCount);
    if (testBtn) testBtn.addEventListener('click', testWebhook);
    
    if (toggleSecretBtn) toggleSecretBtn.addEventListener('click', toggleSecretVisibility);
    if (copySecretBtn) copySecretBtn.addEventListener('click', copySecretToClipboard);
    if (regenerateSecretBtn) regenerateSecretBtn.addEventListener('click', regenerateSecret);
    
    if (webhookForm) {
        webhookForm.addEventListener('submit', function() {
            if (updateBtn) {
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            }
        });
    }

    // --- Functions ---
    
    function updateEventPreview() {
        if (!eventSelect || !eventPreview || !eventDescription) return;
        
        const selectedOption = eventSelect.options[eventSelect.selectedIndex];
        const description = selectedOption.getAttribute('data-description');
        if (description) {
            eventDescription.innerHTML = `<i class="fas fa-info-circle text-info"></i> <strong>Description:</strong> ${description}`;
            eventPreview.style.display = 'block';
        } else {
            eventPreview.style.display = 'none';
        }
    }

    function updateCharCount() {
        if (!descriptionTextarea || !charCount) return;
        
        const count = descriptionTextarea.value.length;
        charCount.textContent = `${count}/500`;
    }

    function validateUrl() {
        if (!urlInput || !urlValidation) return;
        
        const url = urlInput.value;
        if (!url) {
            urlValidation.innerHTML = '';
            return;
        }
        try {
            const urlObject = new URL(url);
            if (urlObject.protocol === 'https:') {
                urlValidation.innerHTML = '<i class="fas fa-check-circle"></i> URL is valid and secure (HTTPS).';
                urlValidation.className = 'validation-feedback positive';
            } else {
                urlValidation.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Warning: URL is not secure. Use HTTPS.';
                urlValidation.className = 'validation-feedback negative';
            }
        } catch (_) {
            urlValidation.innerHTML = '<i class="fas fa-times-circle"></i> Invalid URL format.';
            urlValidation.className = 'validation-feedback negative';
        }
    }

    function testWebhook() {
        if (!urlInput || !eventSelect) {
            toastr.error('Form elements not found.');
            return;
        }
        
        const url = urlInput.value;
        const eventName = eventSelect.value;
        
        if (!url || !eventName) {
            toastr.error('Please provide a valid Endpoint URL and select an Event Type before testing.');
            return;
        }
        
        if (!testBtn) return;
        
        testBtn.classList.add('testing');
        testBtn.disabled = true;
        testBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing...';
        
        fetch(`{{ route('admin.webhooks.test', '') }}/${webhookId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                url: url,
                event_name: eventName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(`Test event sent successfully! Endpoint responded with HTTP ${data.status_code || 'Unknown'}.`);
            } else {
                toastr.error(`Test failed: ${data.message || 'Unknown error.'}`);
            }
        })
        .catch(error => {
            toastr.error('An unexpected error occurred while sending the test webhook.');
            console.error('Test Webhook Error:', error);
        })
        .finally(() => {
            if (testBtn) {
                testBtn.classList.remove('testing');
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Test Webhook';
            }
        });
    }
    
    function toggleSecretVisibility() {
        if (!secretInput || !toggleSecretBtn) return;
        
        const icon = toggleSecretBtn.querySelector('i');
        if (!icon) return;
        
        if (secretInput.type === 'password') {
            secretInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            secretInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function copySecretToClipboard() {
        if (!secretInput) return;
        
        const originalType = secretInput.type;
        secretInput.type = 'text'; // Temporarily make it text to select
        secretInput.select();
        
        try {
            document.execCommand('copy');
            toastr.info('Secret key copied to clipboard.');
        } catch (err) {
            toastr.error('Failed to copy secret key.');
        }
        
        secretInput.type = originalType; // Revert back
    }

    function regenerateSecret() {
        if (!confirm('Are you sure you want to regenerate the signing secret? The old secret will stop working immediately.')) {
            return;
        }

        if (!regenerateSecretBtn || !secretInput) return;

        regenerateSecretBtn.disabled = true;
        regenerateSecretBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(`{{ route('admin.webhooks.regenerateSecret', '') }}/${webhookId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.secret) {
                secretInput.value = data.secret;
                toastr.success('Signing secret has been regenerated successfully!');
            } else {
                toastr.error('Failed to regenerate secret. Please try again.');
            }
        })
        .catch(error => {
            toastr.error('An error occurred while regenerating the secret.');
            console.error('Regenerate Secret Error:', error);
        })
        .finally(() => {
            if (regenerateSecretBtn) {
                regenerateSecretBtn.disabled = false;
                regenerateSecretBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
            }
        });
    }
});
</script>
@endpush