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
                                // For password fields, we need to check if there's an encrypted value without decrypting it
                                if ($field['type'] === 'password') {
                                    $setting = $settings->get($fieldKey);
                                    $currentValue = $setting && !empty($setting->getAttributes()['value']) ? '***ENCRYPTED***' : '';
                                } else {
                                    $currentValue = old($fieldKey, $settings->get($fieldKey)?->value ?? ($field['default'] ?? ''));
                                }
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
                                    
                                    @if($activeTab === 'backup')
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-success" onclick="createManualBackup('database')">
                                                <i class="fas fa-database mr-2"></i>Backup Database
                                            </button>
                                            <button type="button" class="btn btn-outline-info" onclick="createManualBackup('code')">
                                                <i class="fas fa-code mr-2"></i>Backup Code
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" onclick="cleanupOldBackups()">
                                                <i class="fas fa-trash mr-2"></i>Cleanup Old
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    {{-- Backup Status Section --}}
                    @if($activeTab === 'backup')
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fab fa-google-drive mr-2"></i>Google Drive Status
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="gdrive-status" class="mb-3">
                                            <span class="badge badge-secondary">Checking...</span>
                                        </div>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-primary" onclick="authorizeGoogleDrive()">
                                                <i class="fas fa-link mr-1"></i>Authorize
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" onclick="testGoogleDriveConnection()">
                                                <i class="fas fa-check mr-1"></i>Test Connection
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-history mr-2"></i>Recent Backups
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="recent-backups">
                                            <div class="text-center text-muted">
                                                <i class="fas fa-spinner fa-spin"></i> Loading...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
// Settings Page JavaScript Functions - CLEANED VERSION

// Test Email Configuration
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

// Clear Cache
function clearCache() {
    if (!confirm('Clear all cache?')) return;
    
    const button = event?.target;
    if (button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }

    fetch('{{ route("admin.settings.clear-cache") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.success ? 'success' : 'error', data.message);
        if(data.success) {
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    });
}

// Run Health Check
function runHealthCheck() {
    const button = event?.target;
    if (button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 3000);
    }

    fetch('{{ route("admin.settings.health-check") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        showAlert('info', data.message || 'Health check completed');
    })
    .catch(error => {
        showAlert('error', 'Health check failed: ' + error.message);
    });
}

// Optimize Database
function optimizeDatabase() {
    if (!confirm('Optimize database? This may take time.')) return;
    
    const button = event?.target;
    if (button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Optimizing...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 5000);
    }

    fetch('{{ route("admin.settings.optimize") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.success ? 'success' : 'error', data.message);
    })
    .catch(error => {
        showAlert('info', 'Database optimization feature not yet configured');
    });
}

// Create Backup
function createBackup() {
    if (!confirm('Create a backup? This may take time.')) return;
    
    const button = event?.target;
    if (button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 3000);
    }

    fetch('/admin/settings/create-backup', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.success ? 'success' : 'error', data.message);
    })
    .catch(error => {
        showAlert('info', 'Backup creation feature not yet configured');
    });
}

// Seed Defaults
function seedDefaults() {
    if (!confirm('Seed default settings?')) return;
    
    const button = event?.target;
    if (button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Seeding...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }

    fetch('/admin/settings/seed-defaults', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.success ? 'success' : 'error', data.message);
        if (data.success) {
            setTimeout(() => window.location.reload(), 2000);
        }
    })
    .catch(error => {
        showAlert('info', 'Seed defaults feature not yet configured');
    });
}

// Improved Toggle Maintenance Mode with better user guidance
function toggleMaintenance() {
    const currentUrl = window.location.origin;
    const secretUrl = currentUrl + '/maintenance-bypass-secret';
    
    const confirmMessage = `
    ⚠️ IMPORTANT: Maintenance Mode Toggle
    
    This will put your site in maintenance mode, showing a "Service Unavailable" page to all visitors.
    
    TO ACCESS YOUR SITE AFTER ENABLING MAINTENANCE MODE:
    🔗 Use this secret URL: ${secretUrl}
    
    📝 Save this URL before proceeding!
    
    Do you want to continue?
    `;
    
    if (!confirm(confirmMessage)) {
        return;
    }

    const button = event?.target || document.querySelector('[onclick="toggleMaintenance()"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Toggling...';
    button.disabled = true;

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
            if (data.mode) {
                // Maintenance mode was enabled
                const instructions = `
                🔧 Maintenance Mode Enabled Successfully!
                
                Your site is now in maintenance mode.
                
                🔗 SECRET ACCESS URL (save this!):
                ${secretUrl}
                
                📋 Alternative ways to disable maintenance mode:
                1. Use the secret URL above to access admin panel
                2. Run command: php artisan up
                3. Delete file: storage/framework/down
                
                Click OK to be redirected to the secret URL.
                `;
                
                alert(instructions);
                
                // Redirect to secret URL after a delay
                setTimeout(() => {
                    window.location.href = secretUrl;
                }, 2000);
            } else {
                // Maintenance mode was disabled
                showAlert('success', '✅ Maintenance mode disabled successfully!');
            }
        } else {
            showAlert('error', 'Failed to toggle maintenance mode: ' + data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
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

// File preview function
function previewFile(input, fieldKey) {
    const file = input.files[0];
    const preview = document.getElementById('preview_' + fieldKey);
    
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = preview.querySelector('img');
            if (img) {
                img.src = e.target.result;
                preview.style.display = 'block';
            }
        }
        reader.readAsDataURL(file);
    } else if (preview) {
        preview.style.display = 'none';
    }
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
    const container = document.querySelector('.col-lg-9') || document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }

    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert-temp');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Form validation
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
        showAlert('error', 'Please fill in all required fields');
    }
    
    return isValid;
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Settings page JavaScript initialized');
    
    // Add form validation
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            if (!validateSettingsForm()) {
                e.preventDefault();
            }
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

    // Prevent form double submission
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'settingsForm') {
            const submitButton = e.target.querySelector('button[type="submit"]');
            if (submitButton && !submitButton.disabled) {
                setTimeout(() => {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
                }, 100);
            }
        }
    });
});

// Disable notification system on settings page to prevent 401 errors
if (typeof window.EnhancedNotificationSystem !== 'undefined') {
    window.EnhancedNotificationSystem = {
        init: () => console.log('Notification system disabled on settings page'),
        updateNotificationCount: () => {},
        loadNotifications: () => {}
    };
}

// Backup-specific functions
function authorizeGoogleDrive() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authorizing...';
    button.disabled = true;

    fetch('{{ route("admin.backups.gdrive.authorize") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.auth_url) {
            window.open(data.auth_url, '_blank');
            showAlert('info', 'Please complete authorization in the new window, then test the connection.');
        } else {
            showAlert('error', data.message || 'Failed to get authorization URL');
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function testGoogleDriveConnection() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    button.disabled = true;

    fetch('{{ route("admin.backups.gdrive.test") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Google Drive connection successful!');
        } else {
            showAlert('error', data.message || 'Google Drive connection failed');
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function createManualBackup(type) {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    button.disabled = true;

    fetch('{{ route("admin.backups.manual") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type: type })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `${type.charAt(0).toUpperCase() + type.slice(1)} backup created successfully!`);
        } else {
            showAlert('error', data.message || `Failed to create ${type} backup`);
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function cleanupOldBackups() {
    if (!confirm('This will delete old backup files based on your retention settings. Continue?')) {
        return;
    }

    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cleaning...';
    button.disabled = true;

    fetch('{{ route("admin.backups.cleanup") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Cleanup completed! Deleted ${data.deleted_count || 0} old backup files.`);
        } else {
            showAlert('error', data.message || 'Failed to cleanup backups');
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Load backup status information
function loadBackupStatus() {
    // Load Google Drive status
    fetch('{{ route("admin.backups.gdrive.test") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        const statusElement = document.getElementById('gdrive-status');
        if (data.success) {
            statusElement.innerHTML = '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Connected</span>';
        } else {
            statusElement.innerHTML = '<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Not Connected</span>';
        }
    })
    .catch(error => {
        const statusElement = document.getElementById('gdrive-status');
        statusElement.innerHTML = '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Error</span>';
    });

    // Load recent backups
    fetch('{{ route("admin.backups.gdrive.list") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        const recentElement = document.getElementById('recent-backups');
        if (data.success && data.backups && data.backups.length > 0) {
            let html = '<div class="list-group list-group-flush">';
            data.backups.slice(0, 5).forEach(backup => {
                const date = new Date(backup.created_time).toLocaleDateString();
                const size = backup.size ? (backup.size / 1024 / 1024).toFixed(2) + ' MB' : 'Unknown';
                html += `
                    <div class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="font-weight-bold">${backup.name}</small><br>
                                <small class="text-muted">${date} • ${size}</small>
                            </div>
                            <span class="badge badge-primary badge-sm">GDrive</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            recentElement.innerHTML = html;
        } else {
            recentElement.innerHTML = '<div class="text-center text-muted"><i class="fas fa-inbox"></i><br>No recent backups found</div>';
        }
    })
    .catch(error => {
        const recentElement = document.getElementById('recent-backups');
        recentElement.innerHTML = '<div class="text-center text-muted"><i class="fas fa-exclamation-triangle"></i><br>Error loading backups</div>';
    });
}

// Handle backup frequency changes
function handleBackupFrequencyChange() {
    const frequencySelect = document.querySelector('select[name="backup_frequency"]');
    const timeField = document.querySelector('input[name="maintenance_window"]');
    const timeLabel = document.querySelector('label[for="maintenance_window"]');
    
    if (frequencySelect && timeField && timeLabel) {
        const updateTimeLabel = () => {
            const frequency = frequencySelect.value;
            switch(frequency) {
                case 'daily':
                    timeLabel.textContent = 'Daily Backup Time';
                    const dailyHelp = timeField.parentElement.querySelector('.form-help');
                    if (dailyHelp) dailyHelp.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Time to run daily backups (24-hour format)';
                    break;
                case 'weekly':
                    timeLabel.textContent = 'Weekly Backup Time';
                    const weeklyHelp = timeField.parentElement.querySelector('.form-help');
                    if (weeklyHelp) weeklyHelp.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Time to run weekly backups on Sundays (24-hour format)';
                    break;
                case 'monthly':
                    timeLabel.textContent = 'Monthly Backup Time';
                    const monthlyHelp = timeField.parentElement.querySelector('.form-help');
                    if (monthlyHelp) monthlyHelp.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Time to run monthly backups on the 1st day (24-hour format)';
                    break;
            }
        };
        
        frequencySelect.addEventListener('change', updateTimeLabel);
        updateTimeLabel(); // Initialize on page load
    }
}

// Load backup status when backup tab is active
if (window.location.hash === '#backup' || new URLSearchParams(window.location.search).get('tab') === 'backup') {
    document.addEventListener('DOMContentLoaded', () => {
        loadBackupStatus();
        handleBackupFrequencyChange();
    });
}

// Global error handler
window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('settings')) {
        console.error('Settings page error:', e.error);
    }
});
</script>
<script>
// FIXED: Global showAlert function
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'error' ? 'alert-danger' : 
                     type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-triangle' : 
                     type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${iconClass} mr-2"></i>${message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;

    // Remove existing alerts
    document.querySelectorAll('.alert').forEach(alert => alert.remove());
    
    // Insert new alert at the top of the content
    const container = document.querySelector('.col-lg-9') || document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) alert.remove();
        }, 5000);
    }
}

// FIXED: Load backup status with better error handling
function loadBackupStatus() {
    // Load Google Drive status
    fetch('{{ route("admin.backups.gdrive.test") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        const statusElement = document.getElementById('gdrive-status');
        if (statusElement) {
            if (data.success) {
                statusElement.innerHTML = '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Connected</span>';
                if (data.user_email) {
                    statusElement.innerHTML += `<small class="d-block text-muted mt-1">${data.user_email}</small>`;
                }
            } else {
                statusElement.innerHTML = '<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Not Connected</span>';
                statusElement.innerHTML += `<small class="d-block text-muted mt-1">${data.message || 'Connection failed'}</small>`;
            }
        }
    })
    .catch(error => {
        console.error('Google Drive status check failed:', error);
        const statusElement = document.getElementById('gdrive-status');
        if (statusElement) {
            statusElement.innerHTML = '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Error</span>';
            statusElement.innerHTML += '<small class="d-block text-muted mt-1">Status check failed</small>';
        }
    });

    // Load recent backups with error handling
    fetch('{{ route("admin.backups.gdrive.list") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        const recentElement = document.getElementById('recent-backups');
        if (recentElement) {
            if (data.success && data.backups && data.backups.length > 0) {
                let html = '<div class="list-group list-group-flush">';
                data.backups.slice(0, 5).forEach(backup => {
                    const date = new Date(backup.created_time || backup.created_at).toLocaleDateString();
                    const size = backup.size ? formatFileSize(backup.size) : 'Unknown';
                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${backup.filename || backup.name || 'Unknown'}</h6>
                                <small class="text-muted">${date} • ${size}</small>
                            </div>
                            <span class="badge badge-secondary">${backup.type || 'backup'}</span>
                        </div>
                    `;
                });
                html += '</div>';
                recentElement.innerHTML = html;
            } else {
                recentElement.innerHTML = '<p class="text-muted mb-0">No recent backups found.</p>';
            }
        }
    })
    .catch(error => {
        console.error('Recent backups load failed:', error);
        const recentElement = document.getElementById('recent-backups');
        if (recentElement) {
            recentElement.innerHTML = '<p class="text-muted mb-0">Failed to load recent backups.</p>';
        }
    });
}

// FIXED: Helper function for file sizes
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// FIXED: Handle backup frequency changes
function handleBackupFrequencyChange() {
    const frequencySelect = document.getElementById('backup_frequency');
    const timeField = document.getElementById('maintenance_window');
    const timeLabel = document.querySelector('label[for="maintenance_window"]');
    
    if (!frequencySelect || !timeField || !timeLabel) {
        console.warn('Backup frequency elements not found');
        return;
    }
    
    const updateTimeLabel = () => {
        const frequency = frequencySelect.value;
        switch(frequency) {
            case 'daily':
                timeLabel.textContent = 'Daily Backup Time';
                break;
            case 'weekly':
                timeLabel.textContent = 'Weekly Backup Time (Sundays)';
                break;
            case 'monthly':
                timeLabel.textContent = 'Monthly Backup Time (1st of month)';
                break;
            default:
                timeLabel.textContent = 'Backup Time';
        }
    };
    
    frequencySelect.addEventListener('change', updateTimeLabel);
    updateTimeLabel(); // Initialize
}

// FIXED: Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Backup settings page loaded');
    
    // Initialize backup-specific functionality if on backup tab
    const urlParams = new URLSearchParams(window.location.search);
    const isBackupTab = urlParams.get('tab') === 'backup' || window.location.hash === '#backup';
    
    if (isBackupTab) {
        // Add small delay to ensure DOM is fully ready
        setTimeout(() => {
            loadBackupStatus();
            handleBackupFrequencyChange();
        }, 100);
    }
    
    // Add global error handler for this page
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        if (event.reason.message && event.reason.message.includes('fetch')) {
            showAlert('error', 'Network request failed. Please check your connection.');
        }
    });
});

// FIXED: Add missing DOM elements check
function checkRequiredElements() {
    const required = ['gdrive-status', 'recent-backups'];
    const missing = [];
    
    required.forEach(id => {
        if (!document.getElementById(id)) {
            missing.push(id);
        }
    });
    
    if (missing.length > 0) {
        console.warn('Missing required DOM elements:', missing);
        // Create placeholder elements if missing
        missing.forEach(id => {
            const placeholder = document.createElement('div');
            placeholder.id = id;
            placeholder.innerHTML = `<p class="text-muted">Element #${id} not found</p>`;
            document.body.appendChild(placeholder);
        });
    }
}

// Run check after DOM is loaded
document.addEventListener('DOMContentLoaded', checkRequiredElements);
</script>
@endpush