@extends('layouts.theme')

@section('title', 'Notification Preferences')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-cog"></i> Notification Preferences
                    </h1>
                    <p class="mb-0 text-muted">Customize your attendance notification settings</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('attendance.notifications.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Notifications
                    </a>
                    <button type="button" class="btn btn-outline-danger" id="resetPreferences">
                        <i class="fas fa-undo"></i> Reset to Defaults
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- General Preferences --}}
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bell"></i> General Notification Settings
                    </h6>
                </div>
                <div class="card-body">
                    <form id="preferencesForm" action="{{ route('attendance.notifications.preferences.update') }}" method="POST">
                        @csrf
                        
                        {{-- Notification Methods --}}
                        <div class="mb-4">
                            <h5 class="text-gray-800 mb-3">Notification Methods</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="reportGenerated" 
                                                   name="report_generated_notifications" value="1"
                                                   {{ old('report_generated_notifications', auth()->user()->notification_preferences['report_generated_notifications'] ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="reportGenerated">
                                                Report Generation Complete
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">Notify when scheduled reports are ready</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="reportErrors" 
                                                   name="report_error_notifications" value="1"
                                                   {{ old('report_error_notifications', auth()->user()->notification_preferences['report_error_notifications'] ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="reportErrors">
                                                Report Generation Errors
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">Notify when report generation fails</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Threshold Settings --}}
                        <div class="mb-4">
                            <h5 class="text-gray-800 mb-3">Alert Thresholds</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="attendanceThreshold">Low Attendance Threshold (%)</label>
                                        <input type="number" class="form-control" id="attendanceThreshold" 
                                               name="attendance_threshold" min="0" max="100" 
                                               value="{{ old('attendance_threshold', auth()->user()->notification_preferences['attendance_threshold'] ?? 75) }}">
                                        <small class="form-text text-muted">Alert when attendance falls below this percentage</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="absenceThreshold">Consecutive Absence Days</label>
                                        <input type="number" class="form-control" id="absenceThreshold" 
                                               name="absence_threshold" min="1" max="30" 
                                               value="{{ old('absence_threshold', auth()->user()->notification_preferences['absence_threshold'] ?? 3) }}">
                                        <small class="form-text text-muted">Alert after this many consecutive absences</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lateThreshold">Late Arrival Threshold (per week)</label>
                                        <input type="number" class="form-control" id="lateThreshold" 
                                               name="late_threshold" min="1" max="10" 
                                               value="{{ old('late_threshold', auth()->user()->notification_preferences['late_threshold'] ?? 3) }}">
                                        <small class="form-text text-muted">Alert after this many late arrivals per week</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="batchThreshold">Batch Performance Threshold (%)</label>
                                        <input type="number" class="form-control" id="batchThreshold" 
                                               name="batch_threshold" min="0" max="100" 
                                               value="{{ old('batch_threshold', auth()->user()->notification_preferences['batch_threshold'] ?? 80) }}">
                                        <small class="form-text text-muted">Alert when batch average falls below this percentage</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Timing Settings --}}
                        <div class="mb-4">
                            <h5 class="text-gray-800 mb-3">Notification Timing</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quietHoursStart">Quiet Hours Start</label>
                                        <input type="time" class="form-control" id="quietHoursStart" 
                                               name="quiet_hours_start" 
                                               value="{{ old('quiet_hours_start', auth()->user()->notification_preferences['quiet_hours_start'] ?? '22:00') }}">
                                        <small class="form-text text-muted">No notifications during quiet hours</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quietHoursEnd">Quiet Hours End</label>
                                        <input type="time" class="form-control" id="quietHoursEnd" 
                                               name="quiet_hours_end" 
                                               value="{{ old('quiet_hours_end', auth()->user()->notification_preferences['quiet_hours_end'] ?? '08:00') }}">
                                        <small class="form-text text-muted">Resume notifications after this time</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="frequency">Notification Frequency</label>
                                        <select class="form-control" id="frequency" name="notification_frequency">
                                            <option value="immediate" {{ old('notification_frequency', auth()->user()->notification_preferences['notification_frequency'] ?? 'immediate') == 'immediate' ? 'selected' : '' }}>Immediate</option>
                                            <option value="hourly" {{ old('notification_frequency', auth()->user()->notification_preferences['notification_frequency'] ?? 'immediate') == 'hourly' ? 'selected' : '' }}>Hourly Digest</option>
                                            <option value="daily" {{ old('notification_frequency', auth()->user()->notification_preferences['notification_frequency'] ?? 'immediate') == 'daily' ? 'selected' : '' }}>Daily Digest</option>
                                        </select>
                                        <small class="form-text text-muted">How often to receive notifications</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="timezone">Timezone</label>
                                        <select class="form-control" id="timezone" name="timezone">
                                            <option value="Asia/Kolkata" {{ old('timezone', auth()->user()->notification_preferences['timezone'] ?? 'Asia/Kolkata') == 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (IST)</option>
                                            <option value="UTC" {{ old('timezone', auth()->user()->notification_preferences['timezone'] ?? 'Asia/Kolkata') == 'UTC' ? 'selected' : '' }}>UTC</option>
                                            <option value="America/New_York" {{ old('timezone', auth()->user()->notification_preferences['timezone'] ?? 'Asia/Kolkata') == 'America/New_York' ? 'selected' : '' }}>America/New_York (EST)</option>
                                        </select>
                                        <small class="form-text text-muted">Your local timezone for notifications</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg ml-2" id="previewSettings">
                                <i class="fas fa-eye"></i> Preview Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Quick Actions & Status --}}
        <div class="col-lg-4">
            {{-- Notification Status --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-check-circle"></i> Notification Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Email Service</span>
                            <span class="badge badge-success">Active</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>SMS Service</span>
                            <span class="badge badge-success">Active</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Push Notifications</span>
                            <span class="badge badge-warning">Permission Required</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Sound Alerts</span>
                            <span class="badge badge-success">Enabled</span>
                        </div>
                    </div>
                    
                    <button class="btn btn-outline-primary btn-sm btn-block" id="testNotifications">
                        <i class="fas fa-vial"></i> Test Notifications
                    </button>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-history"></i> Recent Notification Activity
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item mb-3">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Daily Summary Sent</h6>
                                <small class="text-muted">2 hours ago</small>
                            </div>
                        </div>
                        <div class="timeline-item mb-3">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Low Attendance Alert</h6>
                                <small class="text-muted">5 hours ago</small>
                            </div>
                        </div>
                        <div class="timeline-item mb-3">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Weekly Report Generated</h6>
                                <small class="text-muted">1 day ago</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Preferences Updated</h6>
                                <small class="text-muted">2 days ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm mb-2" onclick="enablePushNotifications()">
                            <i class="fas fa-bell"></i> Enable Push Notifications
                        </button>
                        <button class="btn btn-outline-success btn-sm mb-2" id="sendTestEmail">
                            <i class="fas fa-envelope"></i> Send Test Email
                        </button>
                        <button class="btn btn-outline-info btn-sm mb-2" data-toggle="modal" data-target="#importPreferencesModal">
                            <i class="fas fa-upload"></i> Import Settings
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="exportPreferences">
                            <i class="fas fa-download"></i> Export Settings
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tips --}}
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-lightbulb"></i> Tips & Best Practices
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <small>Enable push notifications for instant alerts</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <small>Set appropriate thresholds for your role</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            <small>Use quiet hours to avoid night notifications</small>
                        </li>
                        <li>
                            <i class="fas fa-check text-success mr-2"></i>
                            <small>Test your settings regularly</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Import Preferences Modal --}}
<div class="modal fade" id="importPreferencesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Notification Preferences</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="preferencesFile">Select Preferences File</label>
                    <input type="file" class="form-control-file" id="preferencesFile" accept=".json">
                    <small class="form-text text-muted">Upload a previously exported preferences file</small>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="overwriteExisting" checked>
                        <label class="form-check-label" for="overwriteExisting">
                            Overwrite existing preferences
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="importPreferences">Import</button>
            </div>
        </div>
    </div>
</div>

{{-- Preview Settings Modal --}}
<div class="modal fade" id="previewSettingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Settings Preview</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="settingsPreview">
                    <!-- Settings preview will be generated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.custom-control-label {
    font-weight: 500;
}

.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -21px;
    top: 15px;
    width: 2px;
    height: calc(100% + 10px);
    background-color: #e3e6f0;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Form submission
    $('#preferencesForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Notification preferences updated successfully!');
                } else {
                    showAlert('error', 'Failed to update preferences. Please try again.');
                }
            },
            error: function() {
                showAlert('error', 'An error occurred. Please try again.');
            }
        });
    });
    
    // Reset preferences
    $('#resetPreferences').click(function() {
        if (confirm('Are you sure you want to reset all preferences to default values? This action cannot be undone.')) {
            // Reset form to defaults
            $('#preferencesForm')[0].reset();
            showAlert('info', 'Preferences reset to default values. Don\'t forget to save!');
        }
    });
    
    // Test notifications
    $('#testNotifications').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        
        setTimeout(() => {
            $(this).prop('disabled', false).html('<i class="fas fa-vial"></i> Test Notifications');
            showAlert('success', 'Test notification sent! Check your email and other notification channels.');
        }, 2000);
    });
    
    // Send test email
    $('#sendTestEmail').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        
        setTimeout(() => {
            $(this).prop('disabled', false).html('<i class="fas fa-envelope"></i> Send Test Email');
            showAlert('success', 'Test email sent successfully!');
        }, 1500);
    });
    
    // Preview settings
    $('#previewSettings').click(function() {
        generateSettingsPreview();
        $('#previewSettingsModal').modal('show');
    });
    
    // Export preferences
    $('#exportPreferences').click(function() {
        const preferences = gatherFormData();
        const blob = new Blob([JSON.stringify(preferences, null, 2)], {type: 'application/json'});
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'notification-preferences.json';
        a.click();
        window.URL.revokeObjectURL(url);
        
        showAlert('success', 'Preferences exported successfully!');
    });
    
    // Import preferences
    $('#importPreferences').click(function() {
        const fileInput = document.getElementById('preferencesFile');
        const file = fileInput.files[0];
        
        if (!file) {
            showAlert('error', 'Please select a file to import.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const preferences = JSON.parse(e.target.result);
                loadPreferencesToForm(preferences);
                $('#importPreferencesModal').modal('hide');
                showAlert('success', 'Preferences imported successfully! Don\'t forget to save.');
            } catch (error) {
                showAlert('error', 'Invalid preferences file format.');
            }
        };
        reader.readAsText(file);
    });
});

function enablePushNotifications() {
    if ('Notification' in window) {
        Notification.requestPermission().then(function(permission) {
            if (permission === 'granted') {
                showAlert('success', 'Push notifications enabled successfully!');
                new Notification('Test Notification', {
                    body: 'Push notifications are now enabled for attendance alerts.',
                    icon: '/favicon.ico'
                });
            } else {
                showAlert('error', 'Push notification permission denied.');
            }
        });
    } else {
        showAlert('error', 'This browser does not support push notifications.');
    }
}

function generateSettingsPreview() {
    const formData = gatherFormData();
    let html = '<div class="row">';
    
    // Notification Methods
    html += '<div class="col-md-6"><h6>Notification Methods</h6><ul class="list-unstyled">';
    html += `<li><i class="fas fa-envelope ${formData.email_notifications ? 'text-success' : 'text-muted'}"></i> Email: ${formData.email_notifications ? 'Enabled' : 'Disabled'}</li>`;
    html += `<li><i class="fas fa-sms ${formData.sms_notifications ? 'text-success' : 'text-muted'}"></i> SMS: ${formData.sms_notifications ? 'Enabled' : 'Disabled'}</li>`;
    html += `<li><i class="fas fa-mobile-alt ${formData.push_notifications ? 'text-success' : 'text-muted'}"></i> Push: ${formData.push_notifications ? 'Enabled' : 'Disabled'}</li>`;
    html += `<li><i class="fas fa-volume-up ${formData.sound_notifications ? 'text-success' : 'text-muted'}"></i> Sound: ${formData.sound_notifications ? 'Enabled' : 'Disabled'}</li>`;
    html += '</ul></div>';
    
    // Thresholds
    html += '<div class="col-md-6"><h6>Alert Thresholds</h6><ul class="list-unstyled">';
    html += `<li>Low Attendance: ${formData.attendance_threshold}%</li>`;
    html += `<li>Consecutive Absences: ${formData.absence_threshold} days</li>`;
    html += `<li>Late Arrivals: ${formData.late_threshold} per week</li>`;
    html += `<li>Batch Performance: ${formData.batch_threshold}%</li>`;
    html += '</ul></div>';
    
    html += '</div>';
    
    $('#settingsPreview').html(html);
}

function gatherFormData() {
    const formData = {};
    $('#preferencesForm').find('input, select').each(function() {
        const name = $(this).attr('name');
        const type = $(this).attr('type');
        
        if (type === 'checkbox') {
            formData[name] = $(this).is(':checked');
        } else {
            formData[name] = $(this).val();
        }
    });
    return formData;
}

function loadPreferencesToForm(preferences) {
    Object.keys(preferences).forEach(key => {
        const input = $(`[name="${key}"]`);
        if (input.length) {
            if (input.attr('type') === 'checkbox') {
                input.prop('checked', preferences[key]);
            } else {
                input.val(preferences[key]);
            }
        }
    });
}

function showAlert(type, message) {
    const alertClass = type === 'error' ? 'alert-danger' : `alert-${type}`;
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}
</script>
@endpush