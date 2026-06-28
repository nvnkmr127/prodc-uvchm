        <!-- Quick Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon enrollment">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="stat-number">{{ $dashboard_data['my_students_count'] ?? 0 }}</div>
                <div class="stat-label">My Students</div>
                <div class="stat-trend trend-positive">
                    <i class="fas fa-arrow-up mr-1"></i>
                    {{ $dashboard_data['students_growth'] ?? 0 }}% this month
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon payments">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="stat-number" id="my-collections-today">
                    ₹{{ number_format($dashboard_data['my_collections']['today'] ?? 0) }}</div>
                <div class="stat-label">My Collections Today</div>
                <div class="stat-trend trend-positive">
                    <i class="fas fa-arrow-up mr-1"></i>
                    {{ $dashboard_data['collections_growth'] ?? 0 }}% vs yesterday
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon attendance">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-number">{{ $dashboard_data['avg_attendance'] ?? 0 }}%</div>
                <div class="stat-label">Avg Attendance</div>
                <div
                    class="stat-trend {{ ($dashboard_data['attendance_trend'] ?? 0) >= 0 ? 'trend-positive' : 'trend-negative' }}">
                    <i class="fas fa-arrow-{{ ($dashboard_data['attendance_trend'] ?? 0) >= 0 ? 'up' : 'down' }} mr-1"></i>
                    {{ abs($dashboard_data['attendance_trend'] ?? 0) }}% this week
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon activity">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-number">{{ $dashboard_data['my_activities_count'] ?? 0 }}</div>
                <div class="stat-label">My Activities</div>
                <div class="stat-trend trend-positive">
                    <i class="fas fa-clock mr-1"></i>
                    Last {{ $dashboard_data['last_activity_time'] ?? 'unknown' }}
                </div>
            </div>
