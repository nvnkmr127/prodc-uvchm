<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'College Management System')</title>

    <!-- Bootstrap CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome from CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom Notification CSS -->
    <style>
        :root {
            --primary-color: #667eea;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #28a745;
            --error-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --text-color: #333;
            --border-color: #e9ecef;
            --bg-light: #f8f9fa;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
            color: var(--text-color);
            padding-top: 20px;
        }

        /* Notification Bell */
        .notification-bell {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            cursor: pointer;
        }

        .bell-icon {
            position: relative;
            background: var(--primary-gradient);
            color: white;
            padding: 15px;
            border-radius: 50%;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            font-size: 18px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
        }

        .bell-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .bell-icon.ringing {
            animation: bellRing 0.5s ease-in-out 3;
        }

        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--error-color);
            color: white;
            border-radius: 50%;
            min-width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            transform: scale(0);
            transition: transform 0.3s ease;
        }

        .notification-count.show {
            transform: scale(1);
        }

        .notification-count.pulse {
            animation: pulse 2s infinite;
        }

        @keyframes bellRing {

            0%,
            100% {
                transform: rotate(0);
            }

            25% {
                transform: rotate(15deg);
            }

            75% {
                transform: rotate(-15deg);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Notification Panel */
        .notification-panel {
            position: fixed;
            top: 0;
            right: -450px;
            width: 420px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
            transition: right 0.3s ease;
            z-index: 999;
            display: flex;
            flex-direction: column;
        }

        .notification-panel.open {
            right: 0;
        }

        .notification-header {
            padding: 20px;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-icon {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .close-panel {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background 0.2s ease;
        }

        .close-panel:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Notification Filters */
        .notification-filters {
            padding: 15px 20px;
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 6px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 15px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .filter-tab.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .filter-tab:hover:not(.active) {
            background: #f1f3f4;
            border-color: #c1c7cd;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 11px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary-small {
            background: #007bff;
            color: white;
        }

        .btn-secondary-small {
            background: #6c757d;
            color: white;
        }

        /* Notification List */
        .notification-list {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        .notification-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .notification-item:hover {
            background: var(--bg-light);
        }

        .notification-item.unread {
            background: linear-gradient(90deg, #fff3cd 0%, #ffffff 20%);
            border-left: 4px solid var(--warning-color);
        }

        .notification-item.urgent {
            background: linear-gradient(90deg, #f8d7da 0%, #ffffff 20%);
            border-left: 4px solid var(--error-color);
        }

        .priority-indicator {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .priority-urgent {
            background: var(--error-color);
        }

        .priority-high {
            background: var(--warning-color);
        }

        .priority-normal {
            background: var(--success-color);
        }

        .priority-low {
            background: #6c757d;
        }

        .notification-content {
            margin-right: 20px;
        }

        .notification-header-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .notification-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-color);
            margin: 0;
            line-height: 1.3;
        }

        .notification-time {
            font-size: 11px;
            color: #6c757d;
            white-space: nowrap;
        }

        .notification-message {
            font-size: 13px;
            color: #555;
            line-height: 1.4;
            margin: 0 0 10px 0;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-category {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            background: #e9ecef;
            border-radius: 12px;
            font-size: 10px;
            color: #666;
            text-transform: capitalize;
        }

        .category-financial {
            background: #ffeaa7;
            color: #d63031;
        }

        .category-academic {
            background: #a8e6cf;
            color: #00b894;
        }

        .category-system {
            background: #fab1a0;
            color: #e17055;
        }

        .category-attendance {
            background: #fd79a8;
            color: #e84393;
        }

        .notification-action {
            padding: 4px 8px;
            background: #007bff;
            color: white;
            border-radius: 4px;
            font-size: 11px;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .notification-action:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
        }

        /* Loading and Empty States */
        .loading,
        .no-notifications {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .spinner {
            display: inline-block;
            width: 32px;
            height: 32px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .no-notifications i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #dee2e6;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }

        .toast {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-success {
            border-left-color: var(--success-color);
        }

        .toast-error {
            border-left-color: var(--error-color);
        }

        .toast-warning {
            border-left-color: var(--warning-color);
        }

        .toast-info {
            border-left-color: var(--info-color);
        }

        .toast-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px 8px 16px;
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
        }

        .toast-title {
            font-weight: 600;
            font-size: 14px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: #6c757d;
            padding: 4px;
            line-height: 1;
        }

        .toast-close:hover {
            color: #343a40;
        }

        .toast-body {
            padding: 12px 16px 16px 16px;
            font-size: 13px;
            color: #555;
            line-height: 1.4;
        }

        /* Demo specific styles */
        .demo-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }

        .demo-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .demo-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .demo-card h3 {
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .demo-btn {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .demo-btn.success {
            background: var(--success-color);
            color: white;
        }

        .demo-btn.error {
            background: var(--error-color);
            color: white;
        }

        .demo-btn.warning {
            background: var(--warning-color);
            color: #333;
        }

        .demo-btn.info {
            background: var(--info-color);
            color: white;
        }

        .demo-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .notification-bell {
                top: 10px;
                right: 10px;
            }

            .notification-panel {
                width: 100vw;
                right: -100vw;
            }

            .toast-container {
                left: 10px;
                right: 10px;
                max-width: none;
            }

            .demo-controls {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Notification Bell -->
    <div class="notification-bell" onclick="toggleNotificationPanel()">
        <button class="bell-icon" id="bellIcon">
            <i class="fas fa-bell"></i>
            <div class="notification-count" id="notificationCount">0</div>
        </button>
    </div>

    <!-- Notification Panel -->
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h3><i class="fas fa-bell me-2"></i>Notifications</h3>
            <div class="header-actions">
                <button class="btn-icon" onclick="refreshNotifications()" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="close-panel" onclick="toggleNotificationPanel()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="notification-filters">
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">All</button>
                <button class="filter-tab" data-filter="financial">Financial</button>
                <button class="filter-tab" data-filter="academic">Academic</button>
                <button class="filter-tab" data-filter="system">System</button>
                <button class="filter-tab" data-filter="attendance">Attendance</button>
            </div>
            <div class="filter-actions">
                <button class="btn-small btn-primary-small" onclick="markAllAsRead()">
                    <i class="fas fa-check-double"></i> Mark All Read
                </button>
                <button class="btn-small btn-secondary-small" onclick="refreshNotifications()">
                    <i class="fas fa-refresh"></i> Refresh
                </button>
            </div>
        </div>

        <div class="notification-list" id="notificationList">
            <div class="loading">
                <div class="spinner"></div>
                <div>Loading notifications...</div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Main Content -->
    <div class="container-fluid">
        @yield('content')
    </div>

    <!-- Audio Elements -->
    <audio id="notificationSound" preload="auto" style="display: none;">
        <source
            src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+Dyvmk_"
            type="audio/wav">
    </audio>

    <audio id="urgentSound" preload="auto" style="display: none;">
        <source
            src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+Dyvmk_"
            type="audio/wav">
    </audio>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Global Configuration
        window.NotificationConfig = {
            userId: {{ auth()->id() ?? 'null' }},
            userRoles: @json(auth()->user()->roles->pluck('name') ?? []),
            unreadCount: {{ $unreadNotificationCount ?? 0 }},
            soundEnabled: true,
            apiEndpoints: {
                notifications: '{{ route("notifications.index") }}',
                markAsRead: '{{ route("notifications.mark-read", ":id") }}',
                markAllAsRead: '{{ route("notifications.mark-all-read") }}',
                unreadCount: '{{ route("notifications.unread-count") }}',
                testNotification: '{{ route("test-notification") }}'
            },
            csrfToken: '{{ csrf_token() }}'
        };

        // Notification System Class
        class CollegeNotificationSystem {
            constructor() {
                this.config = window.NotificationConfig || {};
                this.notifications = [];
                this.unreadCount = this.config.unreadCount || 0;
                this.currentFilter = 'all';
                this.soundEnabled = true;
                this.volume = 0.7;

                this.init();
            }

            async init() {
                try {
                    this.setupEventListeners();
                    this.loadNotifications();
                    this.updateNotificationCount();
                    this.requestNotificationPermission();
                    this.setupKeyboardShortcuts();
                    this.simulateRealTimeUpdates();

                    console.log('🔔 College Notification System initialized');
                } catch (error) {
                    console.error('Failed to initialize notification system:', error);
                }
            }

            simulateRealTimeUpdates() {
                // Simulate receiving notifications every 15 seconds for demo
                setInterval(() => {
                    if (Math.random() < 0.2) { // 20% chance
                        this.simulateRandomNotification();
                    }
                }, 15000);
            }

            simulateRandomNotification() {
                const types = [
                    'financial_payment_received',
                    'academic_new_admission',
                    'system_maintenance',
                    'financial_fee_reminder'
                ];
                const randomType = types[Math.floor(Math.random() * types.length)];
                this.sendTestNotification(randomType);
            }

            async sendTestNotification(type) {
                try {
                    const response = await fetch(this.config.apiEndpoints.testNotification, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ type: type })
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.handleIncomingNotification(data.notification);
                    }
                } catch (error) {
                    console.error('Failed to send test notification:', error);
                }
            }

            handleIncomingNotification(notification) {
                // Add to local storage
                this.notifications.unshift(notification);
                this.unreadCount++;

                // Update UI
                this.updateNotificationCount();
                this.renderNotifications();
                this.animateBell();

                // Show toast
                this.showToast(notification.title, notification.message, notification.type);

                // Play sound
                if (notification.play_sound && this.soundEnabled) {
                    this.playNotificationSound(notification.priority);
                }

                // Show desktop notification
                if (this.hasNotificationPermission()) {
                    this.showDesktopNotification(notification);
                }
            }

            async loadNotifications() {
                try {
                    const response = await fetch(`${this.config.apiEndpoints.notifications}?per_page=20`, {
                        headers: {
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.notifications = data.notifications.data || [];
                        this.unreadCount = data.unread_count || 0;

                        this.updateNotificationCount();
                        this.renderNotifications();
                    } else {
                        // Fallback to sample data for demo
                        this.loadSampleNotifications();
                    }
                } catch (error) {
                    console.error('Failed to load notifications:', error);
                    this.loadSampleNotifications();
                }
            }

            loadSampleNotifications() {
                this.notifications = [
                    {
                        id: 1,
                        title: 'Payment Received',
                        message: 'Payment of ₹15,000 received from John Doe',
                        type: 'success',
                        category: 'financial',
                        priority: 'normal',
                        created_at: new Date().toISOString(),
                        read_at: null,
                        action_url: '#',
                        action_text: 'View Payment'
                    },
                    {
                        id: 2,
                        title: 'New Admission',
                        message: 'New student admission: Sarah Davis for Computer Science',
                        type: 'info',
                        category: 'academic',
                        priority: 'normal',
                        created_at: new Date(Date.now() - 3600000).toISOString(),
                        read_at: new Date().toISOString(),
                        action_url: '#',
                        action_text: 'Review Application'
                    },
                    {
                        id: 3,
                        title: 'Fee Reminder',
                        message: 'Student Alice Johnson has pending dues of ₹8,500',
                        type: 'warning',
                        category: 'financial',
                        priority: 'normal',
                        created_at: new Date(Date.now() - 7200000).toISOString(),
                        read_at: null,
                        action_url: '#',
                        action_text: 'View Ledger'
                    }
                ];

                this.unreadCount = this.notifications.filter(n => !n.read_at).length;
                this.updateNotificationCount();
                this.renderNotifications();
            }

            async markAsRead(notificationId, updateUI = true) {
                try {
                    const url = this.config.apiEndpoints.markAsRead.replace(':id', notificationId);
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const notification = this.notifications.find(n => n.id === notificationId);
                        if (notification && !notification.read_at) {
                            notification.read_at = new Date().toISOString();
                            this.unreadCount = Math.max(0, this.unreadCount - 1);

                            if (updateUI) {
                                this.updateNotificationCount();
                                this.renderNotifications();
                            }
                        }
                    }
                } catch (error) {
                    console.error('Failed to mark notification as read:', error);
                }
            }

            async markAllAsRead() {
                try {
                    const response = await fetch(this.config.apiEndpoints.markAllAsRead, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        this.notifications.forEach(notification => {
                            if (!notification.read_at) {
                                notification.read_at = new Date().toISOString();
                            }
                        });

                        this.unreadCount = 0;
                        this.updateNotificationCount();
                        this.renderNotifications();

                        this.showToast('Success', 'All notifications marked as read', 'success', 2000);
                    }
                } catch (error) {
                    console.error('Failed to mark all as read:', error);
                    this.showToast('Error', 'Failed to mark all as read', 'error');
                }
            }

            renderNotifications() {
                const container = document.getElementById('notificationList');
                if (!container) return;

                let filteredNotifications = this.notifications;

                if (this.currentFilter !== 'all') {
                    filteredNotifications = this.notifications.filter(n => n.category === this.currentFilter);
                }

                if (filteredNotifications.length === 0) {
                    container.innerHTML = `
                        <div class="no-notifications">
                            <i class="fas fa-inbox"></i>
                            <p>No notifications yet</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = filteredNotifications.map(notification => {
                    const isUnread = !notification.read_at;
                    const timeAgo = this.getTimeAgo(new Date(notification.created_at));
                    const priorityClass = `priority-${notification.priority}`;
                    const categoryClass = `category-${notification.category}`;

                    return `
                        <div class="notification-item ${isUnread ? 'unread' : ''} ${notification.priority === 'urgent' ? 'urgent' : ''}" 
                             onclick="notificationSystem.markAsRead(${notification.id})" 
                             data-notification-id="${notification.id}">
                            <div class="priority-indicator ${priorityClass}"></div>
                            <div class="notification-content">
                                <div class="notification-header-item">
                                    <h5 class="notification-title">${notification.title}</h5>
                                    <span class="notification-time">${timeAgo}</span>
                                </div>
                                <p class="notification-message">${notification.message}</p>
                                <div class="notification-meta">
                                    <span class="notification-category ${categoryClass}">
                                        ${this.getCategoryIcon(notification.category)} ${notification.category}
                                    </span>
                                   ${notification.action_url && notification.action_url !== '#' ? `
                                        <a href="${notification.action_url}" class="notification-action" onclick="event.stopPropagation()">
                                            ${notification.action_text || 'View'}
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            showToast(title, message, type = 'info', duration = 5000) {
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;

                toast.innerHTML = `
                    <div class="toast-header">
                        <strong class="toast-title">
                            ${this.getTypeIcon(type)} ${title}
                        </strong>
                        <button type="button" class="toast-close" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="toast-body">${message}</div>
                `;

                const container = document.getElementById('toastContainer');
                if (container) {
                    container.appendChild(toast);

                    // Animate in
                    setTimeout(() => toast.classList.add('show'), 100);

                    // Auto remove
                    setTimeout(() => {
                        toast.classList.remove('show');
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }, duration);
                }
            }

            playNotificationSound(priority = 'normal') {
                if (!this.soundEnabled) return;

                try {
                    let soundElement;
                    if (priority === 'urgent') {
                        soundElement = document.getElementById('urgentSound');
                    } else {
                        soundElement = document.getElementById('notificationSound');
                    }

                    if (soundElement) {
                        soundElement.volume = this.volume;
                        soundElement.currentTime = 0;
                        soundElement.play().catch(e => {
                            console.log('Sound play failed (user interaction required):', e);
                        });
                    }
                } catch (error) {
                    console.log('Sound error:', error);
                }
            }

            showDesktopNotification(notification) {
                if (!this.hasNotificationPermission()) return;

                const options = {
                    body: notification.message,
                    icon: '/favicon.ico',
                    tag: `notification-${notification.id}`,
                    requireInteraction: notification.priority === 'urgent'
                };

                const desktopNotification = new Notification(notification.title, options);

                desktopNotification.onclick = () => {
                    window.focus();
                    if (notification.action_url && notification.action_url !== '#') {
                        window.location.href = notification.action_url;
                    }
                    desktopNotification.close();
                };

                if (!notification.requires_action) {
                    setTimeout(() => desktopNotification.close(), 5000);
                }
            }

            updateNotificationCount() {
                const countElement = document.getElementById('notificationCount');
                if (countElement) {
                    countElement.textContent = this.unreadCount;

                    if (this.unreadCount > 0) {
                        countElement.classList.add('show');
                        if (this.unreadCount > 99) {
                            countElement.textContent = '99+';
                        }
                    } else {
                        countElement.classList.remove('show', 'pulse');
                    }
                }
            }

            animateBell() {
                const bellIcon = document.getElementById('bellIcon');
                if (bellIcon) {
                    bellIcon.classList.add('ringing');
                    setTimeout(() => bellIcon.classList.remove('ringing'), 1500);
                }
            }

            setFilter(filter) {
                this.currentFilter = filter;

                // Update active tab
                document.querySelectorAll('.filter-tab').forEach(tab => {
                    tab.classList.remove('active');
                    if (tab.dataset.filter === filter) {
                        tab.classList.add('active');
                    }
                });

                this.renderNotifications();
            }

            setupEventListeners() {
                // Filter tabs
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('filter-tab')) {
                        this.setFilter(e.target.dataset.filter);
                    }
                });

                // Click outside to close panel
                document.addEventListener('click', (e) => {
                    const panel = document.getElementById('notificationPanel');
                    const bell = document.querySelector('.notification-bell');

                    if (panel && bell && !panel.contains(e.target) && !bell.contains(e.target)) {
                        panel.classList.remove('open');
                    }
                });
            }

            setupKeyboardShortcuts() {
                document.addEventListener('keydown', (e) => {
                    // Ctrl+Shift+N - Toggle notification panel
                    if (e.ctrlKey && e.shiftKey && e.key === 'N') {
                        e.preventDefault();
                        this.togglePanel();
                    }

                    // Ctrl+Shift+M - Mark all as read
                    if (e.ctrlKey && e.shiftKey && e.key === 'M') {
                        e.preventDefault();
                        this.markAllAsRead();
                    }

                    // Escape - Close panel
                    if (e.key === 'Escape') {
                        const panel = document.getElementById('notificationPanel');
                        if (panel && panel.classList.contains('open')) {
                            panel.classList.remove('open');
                        }
                    }
                });
            }

            async requestNotificationPermission() {
                if ('Notification' in window && Notification.permission === 'default') {
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        this.showToast('Permissions Granted', 'Desktop notifications enabled', 'success', 3000);
                    }
                }
            }

            hasNotificationPermission() {
                return 'Notification' in window && Notification.permission === 'granted';
            }

            togglePanel() {
                const panel = document.getElementById('notificationPanel');
                if (panel) {
                    panel.classList.toggle('open');
                    if (panel.classList.contains('open')) {
                        this.loadNotifications();
                    }
                }
            }

            // Utility methods
            getTimeAgo(date) {
                const now = new Date();
                const diff = now - date;
                const minutes = Math.floor(diff / 60000);
                const hours = Math.floor(diff / 3600000);
                const days = Math.floor(diff / 86400000);

                if (minutes < 1) return 'Just now';
                if (minutes < 60) return `${minutes}m ago`;
                if (hours < 24) return `${hours}h ago`;
                return `${days}d ago`;
            }

            getCategoryIcon(category) {
                const icons = {
                    financial: '<i class="fas fa-dollar-sign"></i>',
                    academic: '<i class="fas fa-graduation-cap"></i>',
                    system: '<i class="fas fa-cogs"></i>',
                    attendance: '<i class="fas fa-user-check"></i>',
                    general: '<i class="fas fa-info-circle"></i>'
                };
                return icons[category] || icons.general;
            }

            getTypeIcon(type) {
                const icons = {
                    success: '<i class="fas fa-check-circle"></i>',
                    error: '<i class="fas fa-exclamation-triangle"></i>',
                    warning: '<i class="fas fa-exclamation-circle"></i>',
                    info: '<i class="fas fa-info-circle"></i>'
                };
                return icons[type] || icons.info;
            }

            toggleSound() {
                this.soundEnabled = !this.soundEnabled;
                const message = this.soundEnabled ? 'Sound notifications enabled' : 'Sound notifications disabled';
                const type = this.soundEnabled ? 'success' : 'info';
                this.showToast('Sound Settings', message, type, 2000);
            }
        }

        // Global functions
        let notificationSystem;

        document.addEventListener('DOMContentLoaded', function () {
            notificationSystem = new CollegeNotificationSystem();
        });

        function toggleNotificationPanel() {
            if (notificationSystem) {
                notificationSystem.togglePanel();
            }
        }

        function markAllAsRead() {
            if (notificationSystem) {
                notificationSystem.markAllAsRead();
            }
        }

        function refreshNotifications() {
            if (notificationSystem) {
                notificationSystem.loadNotifications();
                notificationSystem.showToast('Refreshed', 'Notifications updated', 'success', 2000);
            }
        }

        function sendTestNotification(type) {
            if (notificationSystem) {
                notificationSystem.sendTestNotification(type);
            }
        }

        function toggleSound() {
            if (notificationSystem) {
                notificationSystem.toggleSound();
            }
        }
    </script>

    @stack('scripts')
</body>

</html>