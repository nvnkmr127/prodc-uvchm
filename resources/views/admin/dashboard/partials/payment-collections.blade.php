                <!-- Payment Collections Section -->
                <div class="widget-card animate-fade-in animate-delay-200">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-chart-bar"></i>My Payment Collections
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button"
                                data-toggle="dropdown">
                                Export
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="#" onclick="exportPaymentData('pdf')">PDF Report</a>
                                <a class="dropdown-item" href="#" onclick="exportPaymentData('excel')">Excel Sheet</a>
                            </div>
                        </div>
                    </div>
                    <div class="widget-body">
                        <!-- Time Period Selector -->
                        <div class="period-selector">
                            <button class="period-btn active" data-period="today">Today</button>
                            <button class="period-btn" data-period="yesterday">Yesterday</button>
                            <button class="period-btn" data-period="this_week">This Week</button>
                            <button class="period-btn" data-period="last_7_days">Last 7 Days</button>
                            <button class="period-btn" data-period="this_month">This Month</button>
                            <button class="period-btn" data-period="last_30_days">Last 30 Days</button>
                        </div>

                        <!-- Payment Stats Grid -->
                        <div class="payment-stats" id="payment-stats">
                            <div class="payment-stat">
                                <div class="payment-stat-amount text-success" id="total-collected">
                                    ₹{{ number_format($dashboard_data['my_collections']['today'] ?? 0) }}
                                </div>
                                <div class="payment-stat-label">Total Collected</div>
                            </div>
                            <div class="payment-stat">
                                <div class="payment-stat-amount text-primary" id="transactions-count">
                                    {{ $dashboard_data['my_collections']['transactions'] ?? 0 }}
                                </div>
                                <div class="payment-stat-label">Transactions</div>
                            </div>
                            <div class="payment-stat">
                                <div class="payment-stat-amount text-info" id="avg-payment">
                                    ₹{{ number_format($dashboard_data['my_collections']['avg_amount'] ?? 0) }}
                                </div>
                                <div class="payment-stat-label">Average Payment</div>
                            </div>
                            <div class="payment-stat">
                                <div class="payment-stat-amount text-warning" id="online-percentage">
                                    {{ $dashboard_data['my_collections']['online_percentage'] ?? 0 }}%
                                </div>
                                <div class="payment-stat-label">Online Payments</div>
                            </div>
                        </div>

                        <!-- Payment Trends Chart -->
                        <div class="chart-container">
                            <canvas id="paymentTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
