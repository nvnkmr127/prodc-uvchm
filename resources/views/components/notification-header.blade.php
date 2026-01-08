<li class="nav-item dropdown no-arrow mx-1" id="notificationDropdown">
    <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bell fa-fw"></i>
        <!-- Counter Badge -->
        <span class="badge badge-danger badge-counter" id="notificationCount" style="display: none;">0</span>
    </a>
    <!-- Dropdown List -->
    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
        aria-labelledby="alertsDropdown" style="width: 400px;">
        <h6 class="dropdown-header">
            <i class="fas fa-bell"></i> Notifications
            <button class="btn btn-sm btn-link float-right" onclick="markAllNotificationsAsRead()">
                Mark All Read
            </button>
        </h6>

        <div id="notificationList" style="max-height: 300px; overflow-y: auto;">
            <!-- Notifications will be loaded here -->
            <div class="text-center py-3" id="loadingNotifications">
                <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>
        </div>

        <a class="dropdown-item text-center small text-gray-500" href="{{ route('notifications.index') }}">
            Show All Notifications
        </a>
    </div>
</li>

<!-- Notification Sounds (Hidden Audio Elements) -->
<audio id="notificationSound_success" preload="auto">
    <source src="/sounds/success.mp3" type="audio/mpeg">
    <source
        src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmMaBC18"
        type="audio/wav">
</audio>

<audio id="notificationSound_warning" preload="auto">
    <source src="/sounds/warning.mp3" type="audio/mpeg">
    <source
        src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmMaBC18"
        type="audio/wav">
</audio>

<audio id="notificationSound_error" preload="auto">
    <source src="/sounds/error.mp3" type="audio/mpeg">
    <source
        src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmMaBC18"
        type="audio/wav">
</audio>

<audio id="notificationSound_info" preload="auto">
    <source src="/sounds/info.mp3" type="audio/mpeg">
    <source
        src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmMaBC18"
        type="audio/wav">
</audio>