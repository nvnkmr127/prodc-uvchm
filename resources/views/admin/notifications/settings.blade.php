@extends('layouts.theme')

@section('title', 'Notification Settings')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">⚙️ Notification Settings</h1>
        <a href="{{ route('admin.notifications.dashboard') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="row">
        <!-- Global Settings -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">🌐 Global Settings</h6>
                </div>
                <div class="card-body">
                    <form id="globalSettingsForm">
                        @csrf
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="email_notifications" 
                                       {{ $globalSettings['email_notifications'] ?? true ? 'checked' : '' }}>
                                <label class="custom-control-label" for="email_notifications">Enable Email Notifications</label>
                            </div>
                            <small class="form-text text-muted">Send notifications via email</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="sms_notifications"
                                       {{ $globalSettings['sms_notifications'] ?? false ? 'checked' : '' }}>
                                <label class="custom-control-label" for="sms_notifications">Enable SMS Notifications</label>
                            </div>
                            <small class="form-text text-muted">Send notifications via SMS</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="push_notifications"
                                       {{ $globalSettings['push_notifications'] ?? true ? 'checked' : '' }}>
                                <label class="custom-control-label" for="push_notifications">Enable Push Notifications</label>
                            </div>
                            <small class="form-text text-muted">Show browser notifications</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="sound_notifications"
                                       {{ $globalSettings['sound_notifications'] ?? true ? 'checked' : '' }}>
                                <label class="custom-control-label" for="sound_notifications">Enable Sound Notifications</label>
                            </div>
                            <small class="form-text text-muted">Play sounds for notifications</small>
                        </div>

                        <div class="form-group">
                            <label for="fee_reminder_days">Fee Reminder Days</label>
                            <input type="number" class="form-control" id="fee_reminder_days" 
                                   value="{{ $globalSettings['fee_reminder_days'] ?? 7 }}" min="1" max="30">
                            <small class="form-text text-muted">Days before due date to send reminders</small>
                        </div>

                        <div class="form-group">
                            <label for="minimum_attendance_percentage">Minimum Attendance Percentage</label>
                            <input type="number" class="form-control" id="minimum_attendance_percentage" 
                                   value="{{ $globalSettings['minimum_attendance_percentage'] ?? 75 }}" min="50" max="100">
                            <small class="form-text text-muted">Threshold for low attendance alerts</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Global Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- User Preferences -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">👤 Your Preferences</h6>
                </div>
                <div class="card-body">
                    <form id="userPreferencesForm">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Email</th>
                                        <th>Sound</th>
                                        <th>Push</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['financial', 'academic', 'system', 'attendance'] as $category)
                                    <tr>
                                        <td><strong>{{ ucfirst($category) }}</strong></td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="email_{{ $category }}" checked>
                                                <label class="custom-control-label" for="email_{{ $category }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="sound_{{ $category }}" checked>
                                                <label class="custom-control-label" for="sound_{{ $category }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="push_{{ $category }}" checked>
                                                <label class="custom-control-label" for="push_{{ $category }}"></label>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-check"></i> Save My Preferences
                        </button>
                        
                        <button type="button" class="btn btn-warning" onclick="resetPreferences()">
                            <i class="fas fa-undo"></i> Reset to Default
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sound Test -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">🔊 Sound Test</h6>
                </div>
                <div class="card-body">
                    <p>Test notification sounds:</p>
                    <button class="btn btn-outline-success mr-2" onclick="playSound('success')">
                        <i class="fas fa-check"></i> Success Sound
                    </button>
                    <button class="btn btn-outline-warning mr-2" onclick="playSound('warning')">
                        <i class="fas fa-exclamation-triangle"></i> Warning Sound
                    </button>
                    <button class="btn btn-outline-danger mr-2" onclick="playSound('error')">
                        <i class="fas fa-times"></i> Error Sound
                    </button>
                    <button class="btn btn-outline-info mr-2" onclick="playSound('info')">
                        <i class="fas fa-info"></i> Info Sound
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Global Settings Form
document.getElementById('globalSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        email_notifications: document.getElementById('email_notifications').checked,
        sms_notifications: document.getElementById('sms_notifications').checked,
        push_notifications: document.getElementById('push_notifications').checked,
        sound_notifications: document.getElementById('sound_notifications').checked,
        fee_reminder_days: parseInt(document.getElementById('fee_reminder_days').value),
        minimum_attendance_percentage: parseInt(document.getElementById('minimum_attendance_percentage').value),
    };
    
    fetch('{{ route("admin.notifications.settings.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Global settings saved successfully!');
        } else {
            showAlert('error', 'Failed to save settings');
        }
    })
    .catch(error => {
        showAlert('error', 'Error: ' + error.message);
    });
});

// User Preferences Form
document.getElementById('userPreferencesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const preferences = [];
    ['financial', 'academic', 'system', 'attendance'].forEach(category => {
        ['email', 'sound', 'push'].forEach(type => {
            preferences.push({
                notification_type: type,
                category: category,
                enabled: document.getElementById(type + '_' + category).checked
            });
        });
    });
    
    fetch('{{ route("admin.notifications.settings.preferences") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({preferences: preferences})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Preferences saved successfully!');
        } else {
            showAlert('error', 'Failed to save preferences');
        }
    });
});

function resetPreferences() {
    if (confirm('Reset all preferences to default?')) {
        fetch('{{ route("admin.notifications.settings.reset") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Preferences reset successfully!');
                setTimeout(() => location.reload(), 1000);
            }
        });
    }
}

function playSound(type) {
    // Play notification sound
    if (window.NotificationSystem) {
        window.NotificationSystem.playNotificationSound(type);
    } else {
        // Fallback - create audio element
        const audio = new Audio('/sounds/' + type + '.mp3');
        audio.play().catch(e => {
            // If sound file doesn't exist, use system beep
            console.log('Sound file not found, using system beep');
            // Create a short beep sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = type === 'error' ? 400 : (type === 'warning' ? 600 : 800);
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        });
    }
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    document.querySelectorAll('.alert').forEach(alert => alert.remove());
    document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', alertHtml);
    
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
    }, 5000);
}
</script>
@endpush
