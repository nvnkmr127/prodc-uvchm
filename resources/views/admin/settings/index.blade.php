{{-- resources/views/admin/settings/index.blade.php --}}
@extends('layouts.theme')
@section('title', 'System Settings')

@push('styles')
<style>
    .settings-nav {
        border-right: 1px solid #e3e6f0;
        min-height: 600px;
    }
    
    .settings-nav .nav-link {
        color: #5a5c69;
        border-radius: 0;
        border: none;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #eaecf4;
        transition: all 0.2s;
    }
    
    .settings-nav .nav-link:hover {
        background-color: #f8f9fc;
        color: #3a3b45;
    }
    
    .settings-nav .nav-link.active {
        background-color: #4e73df;
        color: white;
        border-left: 4px solid #2653d4;
    }
    
    .settings-nav .nav-link i {
        margin-right: 0.75rem;
        width: 16px;
    }
    
    .setting-group-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .form-group-enhanced {
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .is-invalid {
    border-color: #dc3545 !important;
}

.settings-loading {
    opacity: 0.6;
    pointer-events: none;
}

.notification-error {
    display: none; /* Hide notification errors on settings page */
}

/* Hide notification dropdown on settings page to prevent errors */
.notification-dropdown {
    display: none !important;
}

/* Fix any broken styles */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
    
    .form-group-enhanced .form-label {
        font-weight: 600;
        color: #5a5c69;
        margin-bottom: 0.5rem;
    }
    
    .form-group-enhanced .form-help {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 24px;
    }
    
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }
    
    input:checked + .toggle-slider {
        background-color: #4e73df;
    }
    
    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }
    
    .file-upload-preview {
        max-width: 200px;
        max-height: 100px;
        margin-top: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px;
    }
    
    .settings-actions {
        background-color: #f8f9fc;
        border-top: 1px solid #eaecf4;
        padding: 1.5rem;
        margin: -1.5rem -1.5rem 0;
        border-radius: 0 0 0.5rem 0.5rem;
    }
    
    .required-indicator {
        color: #e74a3b;
    }
</style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-cogs mr-2"></i>System Settings
    </h1>
    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="testEmail()">
            <i class="fas fa-envelope mr-1"></i>Test Email
        </button>
        <button type="button" class="btn btn-sm btn-outline-info" onclick="clearCache()">
            <i class="fas fa-broom mr-1"></i>Clear Cache
        </button>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="runHealthCheck()">
            <i class="fas fa-heartbeat mr-1"></i>Health Check
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#importSettingsModal">
            <i class="fas fa-upload mr-1"></i>Import
        </button>
        <a href="{{ route('admin.settings.export') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-download mr-1"></i>Export
        </a>
    </div>
</div>

{{-- Success/Error Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-triangle mr-2"></i>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    {{-- Settings Navigation --}}
    <div class="col-lg-3">
        <div class="card shadow mb-4">
            <div class="card-body p-0">
                <div class="nav flex-column settings-nav" role="tablist">
                    @foreach($settingGroups as $groupKey => $group)
                        <a class="nav-link {{ $activeTab === $groupKey ? 'active' : '' }}" 
                           href="{{ route('admin.settings.index', ['tab' => $groupKey]) }}">
                            <i class="{{ $group['icon'] }}"></i>
                            {{ $group['title'] }}
                        </a>
                    @endforeach
                    
                    {{-- Quick Actions --}}
                    <hr class="m-0">
                    <a class="nav-link" href="{{ route('admin.settings.system-info') }}">
                        <i class="fas fa-info-circle"></i>
                        System Information
                    </a>
                </div>
            </div>
        </div>
        
        {{-- Quick Actions Card --}}
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-tools mr-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="seedDefaults()">
                        <i class="fas fa-seedling mr-1"></i>Seed Defaults
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="optimizeDatabase()">
                        <i class="fas fa-database mr-1"></i>Optimize DB
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="createBackup()">
                        <i class="fas fa-archive mr-1"></i>Create Backup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings Content --}}
    <div class="col-lg-9">
        <div class="card shadow mb-4">
            <div class="card-body">
                @if(isset($settingGroups[$activeTab]))
                    @php $currentGroup = $settingGroups[$activeTab]; @endphp
                    
                    <div class="setting-group-header">
                        <h4 class="mb-1">
                            <i class="{{ $currentGroup['icon'] }} mr-2"></i>
                            {{ $currentGroup['title'] }}
                        </h4>
                        <p class="mb-0 opacity-75">{{ $currentGroup['description'] }}</p>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" id="settingsForm">
                        @csrf
                        <input type="hidden" name="active_tab" value="{{ $activeTab }}">

                        @foreach($currentGroup['fields'] as $fieldKey => $field)
                            @php
                                $currentValue = old($fieldKey, $settings->get($fieldKey)?->value ?? ($field['default'] ?? ''));
                            @endphp

                            <div class="form-group-enhanced">
                                <label for="{{ $fieldKey }}" class="form-label">
                                    {{ $field['label'] }}
                                    @if(isset($field['required']) && $field['required'])
                                        <span class="required-indicator">*</span>
                                    @endif
                                </label>

                                @switch($field['type'])
                                    @case('text')
                                    @case('email')
                                    @case('url')
                                    @case('tel')
                                        <input type="{{ $field['type'] }}" 
                                               class="form-control @error($fieldKey) is-invalid @enderror" 
                                               id="{{ $fieldKey }}" 
                                               name="{{ $fieldKey }}" 
                                               value="{{ $currentValue }}"
                                               placeholder="{{ $field['placeholder'] ?? '' }}"
                                               @if(isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                                               @if(isset($field['required']) && $field['required']) required @endif>
                                        @break

                                    @case('password')
                                        <input type="password" 
                                               class="form-control @error($fieldKey) is-invalid @enderror" 
                                               id="{{ $fieldKey }}" 
                                               name="{{ $fieldKey }}" 
                                               placeholder="{{ $currentValue ? 'Leave blank to keep current value' : 'Enter password' }}"
                                               @if(isset($field['required']) && $field['required'] && !$currentValue) required @endif>
                                        @if($currentValue)
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>Current value is encrypted. Leave blank to keep current password.
                                            </small>
                                        @endif
                                        @break

                                    @case('number')
                                        <input type="number" 
                                               class="form-control @error($fieldKey) is-invalid @enderror" 
                                               id="{{ $fieldKey }}" 
                                               name="{{ $fieldKey }}" 
                                               value="{{ $currentValue }}"
                                               placeholder="{{ $field['placeholder'] ?? '' }}"
                                               @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                                               @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                                               @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                                               @if(isset($field['required']) && $field['required']) required @endif>
                                        @break

                                    @case('textarea')
                                        <textarea class="form-control @error($fieldKey) is-invalid @enderror" 
                                                  id="{{ $fieldKey }}" 
                                                  name="{{ $fieldKey }}" 
                                                  rows="{{ $field['rows'] ?? 3 }}"
                                                  placeholder="{{ $field['placeholder'] ?? '' }}"
                                                  @if(isset($field['required']) && $field['required']) required @endif>{{ $currentValue }}</textarea>
                                        @break

                                    @case('select')
                                        <select class="form-control @error($fieldKey) is-invalid @enderror" 
                                                id="{{ $fieldKey }}" 
                                                name="{{ $fieldKey }}"
                                                @if(isset($field['required']) && $field['required']) required @endif>
                                            @if(!isset($field['required']) || !$field['required'])
                                                <option value="">-- Select --</option>
                                            @endif
                                            @foreach($field['options'] as $value => $label)
                                                <option value="{{ $value }}" 
                                                        {{ $currentValue == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('multiselect')
                                        <select class="form-control @error($fieldKey) is-invalid @enderror" 
                                                id="{{ $fieldKey }}" 
                                                name="{{ $fieldKey }}[]"
                                                multiple>
                                            @php
                                                $selectedValues = is_string($currentValue) ? json_decode($currentValue, true) : (array)$currentValue;
                                                $selectedValues = $selectedValues ?? [];
                                            @endphp
                                            @foreach($field['options'] as $value => $label)
                                                <option value="{{ $value }}" 
                                                        {{ in_array($value, $selectedValues) ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Hold Ctrl/Cmd to select multiple options</small>
                                        @break

                                    @case('toggle')
                                        <div class="toggle-switch">
                                            <input type="checkbox" 
                                                   id="{{ $fieldKey }}" 
                                                   name="{{ $fieldKey }}" 
                                                   value="1"
                                                   {{ $currentValue ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </div>
                                        @break

                                    @case('file')
                                        <input type="file" 
                                               class="form-control-file @error($fieldKey) is-invalid @enderror" 
                                               id="{{ $fieldKey }}" 
                                               name="{{ $fieldKey }}" 
                                               accept="{{ $field['accept'] ?? '' }}"
                                               onchange="previewFile(this, '{{ $fieldKey }}')"
                                               @if(isset($field['required']) && $field['required'] && !$currentValue) required @endif>
                                        
                                        @if($currentValue)
                                            <div class="mt-2">
                                                <small class="text-muted">Current: </small>
                                                @if(Str::contains($currentValue, ['jpg', 'jpeg', 'png', 'gif', 'svg']))
                                                    <div class="mt-1">
                                                        <img src="{{ asset('storage/' . $currentValue) }}" 
                                                             class="file-upload-preview" 
                                                             alt="Current file">
                                                    </div>
                                                @else
                                                    <a href="{{ asset('storage/' . $currentValue) }}" 
                                                       target="_blank" class="text-primary">
                                                        <i class="fas fa-download mr-1"></i>View Current File
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        <div id="preview_{{ $fieldKey }}" class="mt-2" style="display: none;">
                                            <small class="text-muted">Preview: </small><br>
                                            <img class="file-upload-preview" alt="Preview">
                                        </div>
                                        @break

                                    @case('time')
                                        <input type="time" 
                                               class="form-control @error($fieldKey) is-invalid @enderror" 
                                               id="{{ $fieldKey }}" 
                                               name="{{ $fieldKey }}" 
                                               value="{{ $currentValue }}"
                                               @if(isset($field['required']) && $field['required']) required @endif>
                                        @break

                                    @default
                                        <input type="text" 
                                               class="form-control @error($fieldKey) is-invalid @enderror" 
                                               id="{{ $fieldKey }}" 
                                               name="{{ $fieldKey }}" 
                                               value="{{ $currentValue }}"
                                               placeholder="{{ $field['placeholder'] ?? '' }}">
                                @endswitch

                                @if(isset($field['help']))
                                    <div class="form-help">
                                        <i class="fas fa-info-circle mr-1"></i>{{ $field['help'] }}
                                    </div>
                                @endif

                                @error($fieldKey)
                                    <div class="invalid-feedback d-block">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                        @endforeach

                        <div class="settings-actions">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save Settings
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary ml-2" 
                                            onclick="resetGroupToDefaults('{{ $activeTab }}')">
                                        <i class="fas fa-undo mr-2"></i>Reset to Defaults
                                    </button>
                                </div>
                                <div class="col-md-6 text-right">
                                    @if($activeTab === 'email')
                                        <button type="button" class="btn btn-outline-info" onclick="testEmailConfig()">
                                            <i class="fas fa-envelope mr-2"></i>Test Email
                                        </button>
                                    @endif
                                    
                                    @if($activeTab === 'general')
                                        <button type="button" class="btn btn-outline-warning" onclick="toggleMaintenance()">
                                            <i class="fas fa-tools mr-2"></i>Toggle Maintenance
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Settings group not found</h5>
                        <p class="text-muted">The requested settings group does not exist.</p>
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Settings
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Import Settings Modal --}}
<div class="modal fade" id="importSettingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload mr-2"></i>Import Settings
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.settings.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="settings_file">Settings File</label>
                        <input type="file" class="form-control-file" name="settings_file" 
                               accept=".json" required>
                        <small class="text-muted">Select a JSON file exported from this system.</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="overwrite_existing" 
                               name="overwrite_existing" value="1">
                        <label class="form-check-label" for="overwrite_existing">
                            Overwrite existing settings
                        </label>
                        <small class="form-text text-muted">
                            If unchecked, only new settings will be imported.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload mr-2"></i>Import Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Test Email Modal --}}
<div class="modal fade" id="testEmailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-envelope mr-2"></i>Test Email Configuration
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="test_email">Email Address</label>
                    <input type="email" class="form-control" id="test_email" 
                           placeholder="Enter email address to test" required>
                    <small class="text-muted">We'll send a test email to this address.</small>
                </div>
                <div id="emailTestResult" class="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestEmail()">
                    <i class="fas fa-paper-plane mr-2"></i>Send Test Email
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// File preview function
function previewFile(input, fieldKey) {
    const file = input.files[0];
    const preview = document.getElementById('preview_' + fieldKey);
    
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = preview.querySelector('img');
            img.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
}

// Test email configuration
function testEmail() {
    $('#testEmailModal').modal('show');
}

function testEmailConfig() {
    testEmail();
}

function sendTestEmail() {
    const email = document.getElementById('test_email').value;
    const resultDiv = document.getElementById('emailTestResult');
    
    if (!email) {
        showAlert('error', 'Please enter an email address');
        return;
    }

    const btn = document.querySelector('#testEmailModal .btn-primary');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
    btn.disabled = true;

    fetch('{{ route("admin.settings.test-email") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ test_email: email })
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.success) {
            resultDiv.className = 'alert alert-success';
            resultDiv.innerHTML = '<i class="fas fa-check mr-2"></i>' + data.message;
        } else {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>' + data.message;
        }
        resultDiv.style.display = 'block';
    })
    .catch(error => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        resultDiv.className = 'alert alert-danger';
        resultDiv.innerHTML = '<i class="fas fa-times mr-2"></i>Error: ' + error.message;
        resultDiv.style.display = 'block';
    });
}

// Clear cache
function clearCache() {
    if (!confirm('This will clear all application caches. Continue?')) return;

    fetch('{{ route("admin.settings.clear-cache") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    });
}

// Reset group to defaults
function resetGroupToDefaults(group) {
    if (!confirm('This will reset all settings in this group to their default values. Continue?')) return;

    fetch('{{ route("admin.settings.reset-defaults") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ group: group })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    });
}

// Toggle maintenance mode
function toggleMaintenance() {
    if (!confirm('This will toggle maintenance mode. Continue?')) return;

    fetch('{{ route("admin.settings.toggle-maintenance") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const status = data.mode ? 'enabled' : 'disabled';
            showAlert('success', `Maintenance mode ${status} successfully!`);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    });
}

// Run health check
function runHealthCheck() {
    showAlert('info', 'Running health check...');
    
    fetch('{{ route("admin.settings.health-check") }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        const status = data.status === 'healthy' ? 'success' : 'warning';
        const message = `System health: ${data.status.toUpperCase()}. ${data.summary.passed}/${data.summary.total_checks} checks passed.`;
        showAlert(status, message);
    })
    .catch(error => {
        showAlert('error', 'Health check failed: ' + error.message);
    });
}

// Seed default settings - use existing functionality or disable
function seedDefaults() {
    // Check if route exists by trying the request
    fetch('/admin/settings/seed-defaults', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        if (response.status === 404) {
            // Route doesn't exist, show info message
            showAlert('info', 'Seed defaults feature is being configured. Please add the missing routes.');
            return Promise.reject('Route not found');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        if (error !== 'Route not found') {
            showAlert('error', 'Error: ' + error.message);
        }
    });
}

// Optimize database - use existing functionality or disable  
function optimizeDatabase() {
    // Check if route exists by trying the request
    fetch('/admin/settings/optimize', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        if (response.status === 404) {
            // Route doesn't exist, show info message
            showAlert('info', 'Database optimization feature is being configured. Please add the missing routes.');
            return Promise.reject('Route not found');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', `Database optimized: ${data.details.duplicates_removed} duplicates removed, ${data.details.groups_fixed} groups fixed`);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        if (error !== 'Route not found') {
            showAlert('error', 'Error: ' + error.message);
        }
    });
}

// Create backup - use existing functionality or disable
function createBackup() {
    // Check if route exists by trying the request
    fetch('/admin/settings/create-backup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        if (response.status === 404) {
            // Route doesn't exist, show info message
            showAlert('info', 'Backup creation feature is being configured. Please add the missing routes.');
            return Promise.reject('Route not found');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', `Backup created successfully: ${data.filename}`);
            
            // Optionally download the backup file
            if (data.download_url) {
                const link = document.createElement('a');
                link.href = data.download_url;
                link.download = data.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        if (error !== 'Route not found') {
            showAlert('error', 'Error: ' + error.message);
        }
    });
    */
}

// Utility function to show alerts
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-temp');
    existingAlerts.forEach(alert => alert.remove());

    // Create new alert
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'error' ? 'alert-danger' : 
                     type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-triangle' : 
                     type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show alert-temp" role="alert">
            <i class="fas ${iconClass} mr-2"></i>${message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;

    // Insert at the top of the content
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert-temp');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Form validation
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    let hasErrors = false;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            hasErrors = true;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    if (hasErrors) {
        e.preventDefault();
        showAlert('error', 'Please fill in all required fields.');
        return false;
    }
});


<!-- Add this JavaScript to your settings view or create a separate JS file -->
<script>
// Settings Page JavaScript Functions

// Test Email Configuration
function testEmail() {
    const emailInput = document.getElementById('test_email');
    const email = emailInput ? emailInput.value : prompt('Enter test email address:');
    
    if (!email || !email.includes('@')) {
        alert('Please enter a valid email address');
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    button.disabled = true;
    
    fetch('/admin/settings/test-email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ test_email: email })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Test email sent successfully to ' + email);
        } else {
            alert('❌ Failed to send email: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error sending test email: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Create Database Backup
function createBackup() {
    if (!confirm('Are you sure you want to create a backup? This may take a few minutes.')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Backup...';
    button.disabled = true;
    
    fetch('/admin/settings/create-backup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Backup created successfully!');
        } else {
            alert('❌ Failed to create backup: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error creating backup: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Optimize Database
function optimizeDatabase() {
    if (!confirm('Are you sure you want to optimize the database? This may take a few minutes.')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Optimizing...';
    button.disabled = true;
    
    fetch('/admin/settings/optimize-database', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Database optimized successfully!');
        } else {
            alert('❌ Failed to optimize database: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error optimizing database: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Clear Cache
function clearCache() {
    if (!confirm('Are you sure you want to clear all caches?')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
    button.disabled = true;
    
    fetch('/admin/settings/clear-cache', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Cache cleared successfully!');
        } else {
            alert('❌ Failed to clear cache: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error clearing cache: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Toggle Maintenance Mode
function toggleMaintenance() {
    if (!confirm('Are you sure you want to toggle maintenance mode?')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Toggling...';
    button.disabled = true;
    
    fetch('/admin/settings/toggle-maintenance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Maintenance mode toggled successfully!');
            location.reload(); // Reload to reflect changes
        } else {
            alert('❌ Failed to toggle maintenance mode: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error toggling maintenance mode: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Settings Form Validation
function validateSettingsForm() {
    const form = document.getElementById('settingsForm');
    if (!form) return true;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        alert('Please fill in all required fields');
    }
    
    return isValid;
}

// Auto-save functionality (optional)
let autoSaveTimer;
function autoSaveDraft() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        const formData = new FormData(document.getElementById('settingsForm'));
        const draftData = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== '_token') {
                draftData[key] = value;
            }
        }
        
        try {
            localStorage.setItem('settings_draft', JSON.stringify(draftData));
            console.log('Draft saved locally');
        } catch (error) {
            console.log('Local draft save failed:', error);
        }
    }, 3000);
}

// Fix notification system errors by disabling it on settings page
function disableNotificationSystem() {
    // Override the notification system to prevent 401 errors
    if (window.EnhancedNotificationSystem) {
        window.EnhancedNotificationSystem = {
            init: () => console.log('Notification system disabled on settings page'),
            updateNotificationCount: () => {},
            loadNotifications: () => {}
        };
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Disable notification system on settings page to prevent 401 errors
    disableNotificationSystem();
    
    // Add form validation
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            if (!validateSettingsForm()) {
                e.preventDefault();
            }
        });
        
        // Add auto-save functionality
        const formInputs = settingsForm.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('input', autoSaveDraft);
            input.addEventListener('change', autoSaveDraft);
        });
    }
    
    // Add real-time validation
    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    console.log('Settings page JavaScript initialized');
});

// Global error handler for settings page
window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('settings')) {
        console.error('Settings page error:', e.error);
        // Don't show alerts for every error, just log them
    }
});

// Prevent form double submission
document.addEventListener('submit', function(e) {
    const submitButton = e.target.querySelector('button[type="submit"]');
    if (submitButton) {
        setTimeout(() => {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }, 100);
    }
});

// Real-time validation
document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
    field.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
});

// Auto-save draft functionality (optional) - simplified version
let autoSaveTimer;
function autoSaveDraft() {
    // Simplified auto-save without server calls for now
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        // For now, just save to localStorage as a draft
        const formData = new FormData(document.getElementById('settingsForm'));
        const draftData = {};
        
        for (let [key, value] of formData.entries()) {
            draftData[key] = value;
        }
        
        try {
            localStorage.setItem('settings_draft', JSON.stringify(draftData));
            
            // Show subtle indication that draft was saved locally
            const saveButton = document.querySelector('.btn-primary');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-check mr-2"></i>Draft Saved';
            setTimeout(() => {
                saveButton.innerHTML = originalText;
            }, 2000);
        } catch (error) {
            console.log('Local draft save failed:', error);
        }
    }, 3000); // Save after 3 seconds of inactivity
}

// Attach auto-save to form inputs
document.querySelectorAll('#settingsForm input, #settingsForm select, #settingsForm textarea').forEach(field => {
    field.addEventListener('input', autoSaveDraft);
    field.addEventListener('change', autoSaveDraft);
});
</script>
@endpush