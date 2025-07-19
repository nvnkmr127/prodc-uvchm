@extends('layouts.notification-layout')

@section('title', 'Real-time Notification System Demo')

@section('content')
<div class="demo-container">
    <div class="text-center mb-5">
        <h1 class="display-4">🔔 Real-time Notification System</h1>
        <p class="lead">Complete notification system with sound alerts, real-time updates, and comprehensive management</p>
        <div class="alert alert-info">
            <strong>Demo Features:</strong> Click buttons below to test different notification types. 
            Use <kbd>Ctrl+Shift+N</kbd> to toggle panel, <kbd>Ctrl+Shift+M</kbd> to mark all read.
        </div>
    </div>

    <div class="demo-controls">
        <div class="demo-card">
            <h3>💰 Financial Notifications</h3>
            <button class="demo-btn success" onclick="sendTestNotification('financial_payment_received')">
                <i class="fas fa-money-bill-wave"></i> Payment Received
            </button>
            <button class="demo-btn error" onclick="sendTestNotification('financial_payment_failed')">
                <i class="fas fa-times-circle"></i> Payment Failed
            </button>
            <button class="demo-btn warning" onclick="sendTestNotification('financial_fee_reminder')">
                <i class="fas fa-exclamation-triangle"></i> Fee Reminder
            </button>
            <button class="demo-btn error" onclick="sendTestNotification('financial_overdue_payment')">
                <i class="fas fa-clock"></i> Overdue Payment (Urgent)
            </button>
        </div>

        <div class="demo-card">
            <h3>📚 Academic Notifications</h3>
            <button class="demo-btn info" onclick="sendTestNotification('academic_new_admission')">
                <i class="fas fa-user-plus"></i> New Admission
            </button>
            <button class="demo-btn warning" onclick="sendTestNotification('academic_low_attendance')">
                <i class="fas fa-user-times"></i> Low Attendance Alert
            </button>
            <button class="demo-btn success" onclick="sendTestNotification('academic_exam_scheduled')">
                <i class="fas fa-calendar-check"></i> Exam Scheduled
            </button>
            <button class="demo-btn info" onclick="sendTestNotification('academic_grade_updated')">
                <i class="fas fa-trophy"></i> Grades Updated
            </button>
        </div>

        <div class="demo-card">
            <h3>⚠️ System Alerts</h3>
            <button class="demo-btn error" onclick="sendTestNotification('system_error')">
                <i class="fas fa-exclamation-triangle"></i> Critical System Error
            </button>
            <button class="demo-btn warning" onclick="sendTestNotification('system_maintenance')">
                <i class="fas fa-tools"></i> Maintenance Alert
            </button>
            <button class="demo-btn success" onclick="sendTestNotification('system_backup')">
                <i class="fas fa-database"></i> Backup Complete
            </button>
            <button class="demo-btn info" onclick="sendTestNotification('system_update')">
                <i class="fas fa-download"></i> System Update
            </button>
        </div>

        <div class="demo-card">
            <h3>🎵 Sound & Settings</h3>
            <button class="demo-btn info" onclick="toggleSound()">
                <i class="fas fa-volume-up"></i> Toggle Sound
            </button>
            <button class="demo-btn warning" onclick="notificationSystem.playNotificationSound('normal')">
                <i class="fas fa-play"></i> Test Normal Sound
            </button>
            <button class="demo-btn error" onclick="notificationSystem.playNotificationSound('urgent')">
                <i class="fas fa-volume-up"></i> Test Urgent Sound
            </button>
            <button class="demo-btn success" onclick="notificationSystem.requestNotificationPermission()">
                <i class="fas fa-bell"></i> Request Permissions
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="demo-card">
                <h3>🎯 System Status</h3>
                <div id="systemStatus">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Notification System:</span>
                        <span class="badge bg-success">Active</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Sound Notifications:</span>
                        <span class="badge bg-success" id="soundStatus">Enabled</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Desktop Notifications:</span>
                        <span class="badge bg-warning" id="desktopStatus">Click to Enable</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Auto Refresh:</span>
                        <span class="badge bg-info">Every 15s</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="demo-card">
                <h3>📊 Statistics</h3>
                <div id="notificationStats">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Total Notifications:</span>
                        <span class="badge bg-primary" id="totalCount">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Unread Count:</span>
                        <span class="badge bg-warning" id="unreadCount">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Last Updated:</span>
                        <span class="badge bg-info" id="lastUpdated">Just now</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Session Duration:</span>
                        <span class="badge bg-secondary" id="sessionDuration">0m</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="demo-card mt-4">
        <h3>📋 Keyboard Shortcuts</h3>
        <div class="row">
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <kbd>Ctrl + Shift + N</kbd>
                    <span class="text-muted">Toggle Panel</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <kbd>Ctrl + Shift + M</kbd>
                    <span class="text-muted">Mark All Read</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <kbd>Escape</kbd>
                    <span class="text-muted">Close Panel</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update statistics periodically
    let sessionStart = Date.now();
    
    setInterval(() => {
        // Update session duration
        const duration = Math.floor((Date.now() - sessionStart) / 60000);
        document.getElementById('sessionDuration').textContent = duration + 'm';
        
        // Update last updated
        document.getElementById('lastUpdated').textContent = 'Just now';
        
        // Update notification counts
        if (window.notificationSystem) {
            document.getElementById('totalCount').textContent = notificationSystem.notifications.length;
            document.getElementById('unreadCount').textContent = notificationSystem.unreadCount;
        }
        
        // Update sound status
        if (window.notificationSystem) {
            document.getElementById('soundStatus').textContent = notificationSystem.soundEnabled ? 'Enabled' : 'Disabled';
            document.getElementById('soundStatus').className = `badge ${notificationSystem.soundEnabled ? 'bg-success' : 'bg-secondary'}`;
        }
        
        // Update desktop notification status
        if ('Notification' in window) {
            const status = Notification.permission === 'granted' ? 'Enabled' : 
                          Notification.permission === 'denied' ? 'Blocked' : 'Click to Enable';
            const badgeClass = Notification.permission === 'granted' ? 'bg-success' : 
                              Notification.permission === 'denied' ? 'bg-danger' : 'bg-warning';
            
            document.getElementById('desktopStatus').textContent = status;
            document.getElementById('desktopStatus').className = `badge ${badgeClass}`;
        }
    }, 5000);
</script>
@endsection