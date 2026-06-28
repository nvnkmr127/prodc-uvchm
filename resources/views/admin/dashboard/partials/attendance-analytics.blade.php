                <!-- Attendance Analytics Widget -->
                <div class="widget-card animate-fade-in animate-delay-300">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-chart-area"></i>Student Attendance Analytics
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                                data-toggle="dropdown">
                                View
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('daily')">Daily View</a>
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('weekly')">Weekly View</a>
                                <a class="dropdown-item" href="#" onclick="changeAttendanceView('monthly')">Monthly View</a>
                            </div>
                        </div>
                    </div>
                    <div class="widget-body">
                        <!-- Attendance Overview Stats -->
                        <div class="attendance-overview">
                            <div class="attendance-stat">
                                <div class="attendance-percentage excellent">
                                    {{ $dashboard_data['attendance_stats']['excellent'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Excellent (90%+)</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-percentage good">
                                    {{ $dashboard_data['attendance_stats']['good'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Good (75-89%)</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-percentage average">
                                    {{ $dashboard_data['attendance_stats']['average'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Average (60-74%)</div>
                            </div>
                            <div class="attendance-stat">
                                <div class="attendance-percentage poor">
                                    {{ $dashboard_data['attendance_stats']['poor'] ?? 0 }}%
                                </div>
                                <div class="attendance-label">Needs Attention</div>
                            </div>
                        </div>

                        <!-- Attendance Chart -->
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
