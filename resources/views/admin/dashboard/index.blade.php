{{-- resources/views/admin/dashboard/super-admin.blade.php --}}
@extends('layouts.theme')

@section('title', 'Super Admin Dashboard')

@push('styles')
<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

/* Header Section */
.dashboard-header {
    background: var(--primary-gradient);
    color: white;
    padding: 2.5rem 0;
    margin-bottom: 2rem;
    border-radius: 15px;
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="2" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1.5" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.dashboard-header .content {
    position: relative;
    z-index: 1;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.8rem;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.stat-card:hover::before {
    transform: scaleX(1);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: var(--primary-gradient);
}

.stat-icon.success { background: var(--success-gradient); }
.stat-icon.warning { background: var(--warning-gradient); }
.stat-icon.info { background: var(--info-gradient); }
.stat-icon.dark { background: var(--dark-gradient); }

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-change {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
    margin-top: 0.8rem;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-weight: 600;
}

.stat-change.positive {
    background: rgba(39, 174, 96, 0.1);
    color: #27ae60;
}

.stat-change.negative {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

/* Main Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

/* Widget Cards */
.widget-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.05);
}

.widget-header {
    padding: 1.5rem 1.8rem 1rem;
    border-bottom: 1px solid #f1f3f4;
    background: #fafbfc;
}

.widget-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
}

.widget-title i {
    margin-right: 0.8rem;
    color: #667eea;
}

.widget-body {
    padding: 1.8rem;
}

/* Chart Container */
.chart-container {
    position: relative;
    height: 350px;
    margin-top: 1rem;
}

/* Activity Feed */
.activity-feed {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem 0;
    border-bottom: 1px solid #f1f3f4;
    transition: all 0.2s ease;
}

.activity-item:hover {
    background: #f8f9fa;
    margin: 0 -1rem;
    padding: 1rem;
    border-radius: 8px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-avatar {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: var(--primary-gradient);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.3rem;
}

.activity-meta {
    font-size: 0.85rem;
    color: #7f8c8d;
}

/* Quick Actions Grid */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.quick-action-btn {
    padding: 1rem;
    border-radius: 12px;
    border: none;
    background: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    text-decoration: none;
    color: #2c3e50;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    font-weight: 600;
}

.quick-action-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    color: #2c3e50;
    text-decoration: none;
}

.quick-action-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.8rem;
    font-size: 1.3rem;
    color: white;
}

/* System Health */
.health-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.health-item {
    padding: 1rem;
    border-radius: 10px;
    background: #f8f9fa;
    display: flex;
    justify-content: between;
    align-items: center;
}

.health-status {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 0.8rem;
}

.health-status.healthy { background: #27ae60; }
.health-status.warning { background: #f39c12; }
.health-status.critical { background: #e74c3c; }

/* System Alerts */
.alert-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 12px;
    border-left: 4px solid;
    transition: all 0.2s ease;
}

.alert-item:hover {
    transform: translateX(5px);
}

.alert-critical { 
    background: rgba(231, 76, 60, 0.1); 
    border-left-color: #e74c3c; 
}

.alert-warning { 
    background: rgba(243, 156, 18, 0.1); 
    border-left-color: #f39c12; 
}

.alert-info { 
    background: rgba(52, 152, 219, 0.1); 
    border-left-color: #3498db; 
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
}

/* Performance Cards */
.performance-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-top: 2rem;
}

.performance-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.performance-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.performance-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.performance-label {
    color: #7f8c8d;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Floating Action Button */
.fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: var(--primary-gradient);
    color: white;
    border: none;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    font-size: 1.4rem;
    transition: all 0.3s ease;
    z-index: 1000;
}

.fab:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .performance-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-number {
        font-size: 2rem;
    }
}

/* Animation Keyframes */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-up {
    animation: slideInUp 0.6s ease-out;
}

/* Custom Scrollbar */
.activity-feed::-webkit-scrollbar {
    width: 6px;
}

.activity-feed::-webkit-scrollbar-track {
    background: #f1f3f4;
    border-radius: 3px;
}

.activity-feed::-webkit-scrollbar-thumb {
    background: #c1c8cd;
    border-radius: 3px;
}

.activity-feed::-webkit-scrollbar-thumb:hover {
    background: #a8b2ba;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Enhanced Header Section -->
    <div class="dashboard-header">
        <div class="content">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-crown" style="font-size: 1.8rem;"></i>
                            </div>
                        </div>
                        <div>
                            <h1 class="mb-1" style="font-size: 2.2rem; font-weight: 700;">Super Admin Dashboard</h1>
                            <p class="mb-0 opacity-75" style="font-size: 1.1rem;">Complete system oversight and management center</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex flex-column align-items-lg-end">
                        <div class="mb-2">
                            <span class="badge" style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem;">
                                <i class="fas fa-clock me-2"></i>{{ now()->format('M d, Y • H:i') }}
                            </span>
                        </div>
                        <div class="text-sm opacity-75">
                            Last login: {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'First time' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card animate-slide-up">
            <div class="stat-header">
                <div>
                    <div class="stat-number text-primary">{{ number_format($dashboard_data['total_students'] ?? 0) }}</div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up me-1"></i>
                {{ $dashboard_data['student_growth'] ?? 0 }}% this month
            </div>
        </div>
        
        <div class="stat-card animate-slide-up" style="animation-delay: 0.1s;">
            <div class="stat-header">
                <div>
                    <div class="stat-number text-success">₹{{ number_format($dashboard_data['total_revenue'] ?? 0) }}</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-icon success">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="stat-change {{ ($dashboard_data['revenue_growth'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                <i class="fas fa-arrow-{{ ($dashboard_data['revenue_growth'] ?? 0) >= 0 ? 'up' : 'down' }} me-1"></i>
                {{ abs($dashboard_data['revenue_growth'] ?? 0) }}% from last month
            </div>
        </div>
        
        <div class="stat-card animate-slide-up" style="animation-delay: 0.2s;">
            <div class="stat-header">
                <div>
                    <div class="stat-number text-info">{{ $dashboard_data['active_courses'] ?? 0 }}</div>
                    <div class="stat-label">Active Courses</div>
                </div>
                <div class="stat-icon info">
                    <i class="fas fa-graduation-cap"></i>
                </div>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-layer-group me-1"></i>
                {{ $dashboard_data['total_batches'] ?? 0 }} Active Batches
            </div>
        </div>
        
        <div class="stat-card animate-slide-up" style="animation-delay: 0.3s;">
            <div class="stat-header">
                <div>
                    <div class="stat-number text-warning">{{ $dashboard_data['total_faculty'] ?? 0 }}</div>
                    <div class="stat-label">Faculty Members</div>
                </div>
                <div class="stat-icon warning">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-user-check me-1"></i>
                {{ $dashboard_data['active_faculty'] ?? 0 }} Currently Active
            </div>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Left Column - Charts and Analytics -->
        <div>
            <!-- Revenue Analytics Chart -->
            <div class="widget-card">
                <div class="widget-header">
                    <h6 class="widget-title">
                        <i class="fas fa-chart-area"></i>Revenue Analytics
                    </h6>
                </div>
                <div class="widget-body">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Fee Collection Overview -->
            <div class="widget-card">
                <div class="widget-header">
                    <h6 class="widget-title">
                        <i class="fas fa-money-bill-wave"></i>Fee Collection Overview
                    </h6>
                </div>
                <div class="widget-body">
                    @if(isset($dashboard_data['fee_collection']))
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="h3 text-primary mb-1">₹{{ number_format($dashboard_data['fee_collection']['total_billed'] ?? 0) }}</div>
                            <small class="text-muted text-uppercase font-weight-bold">Total Billed</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h3 text-success mb-1">₹{{ number_format($dashboard_data['fee_collection']['total_collected'] ?? 0) }}</div>
                            <small class="text-muted text-uppercase font-weight-bold">Collected</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h3 text-danger mb-1">₹{{ number_format($dashboard_data['fee_collection']['outstanding'] ?? 0) }}</div>
                            <small class="text-muted text-uppercase font-weight-bold">Outstanding</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h3 text-info mb-1">{{ $dashboard_data['fee_collection']['collection_rate'] ?? 0 }}%</div>
                            <small class="text-muted text-uppercase font-weight-bold">Collection Rate</small>
                        </div>
                    </div>
                    
                    <!-- Collection Progress Bar -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="font-weight-bold">Collection Progress</span>
                            <span class="text-muted">{{ $dashboard_data['fee_collection']['collection_rate'] ?? 0 }}%</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 10px;">
                            <div class="progress-bar" 
                                 style="width: {{ $dashboard_data['fee_collection']['collection_rate'] ?? 0 }}%; background: var(--success-gradient);"
                                 role="progressbar">
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Right Column - Activities and Quick Actions -->
        <div>
            <!-- Quick Actions -->
            <div class="widget-card">
                <div class="widget-header">
                    <h6 class="widget-title">
                        <i class="fas fa-bolt"></i>Quick Actions
                    </h6>
                </div>
                <div class="widget-body">
                    <div class="quick-actions-grid">
                        @if(Route::has('admin.students.index'))
                        <a href="{{ route('admin.students.index') }}" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: var(--primary-gradient);">
                                <i class="fas fa-users"></i>
                            </div>
                            <span>Manage Students</span>
                        </a>
                        @endif
                        
                        @if(Route::has('admin.component-payments.index'))
                        <a href="{{ route('admin.component-payments.index') }}" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: var(--success-gradient);">
                                <i class="fas fa-money-bill"></i>
                            </div>
                            <span>Payment Records</span>
                        </a>
                        @endif
                        
                        @if(Route::has('admin.enquiries.index'))
                        <a href="{{ route('admin.enquiries.index') }}" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: var(--warning-gradient);">
                                <i class="fas fa-phone"></i>
                            </div>
                            <span>Enquiries</span>
                        </a>
                        @endif
                        
                        @if(Route::has('admin.settings.index'))
                        <a href="{{ route('admin.settings.index') }}" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: var(--dark-gradient);">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <span>System Settings</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- System Health -->
            <div class="widget-card">
                <div class="widget-header">
                    <h6 class="widget-title">
                        <i class="fas fa-heartbeat"></i>System Health
                    </h6>
                </div>
                <div class="widget-body">
                    <div class="health-grid">
                        <div class="health-item">
                            <div class="d-flex align-items-center">
                                <div class="health-status healthy"></div>
                                <span class="font-weight-600">Database</span>
                            </div>
                            <span class="text-success font-weight-bold">Healthy</span>
                        </div>
                        <div class="health-item">
                            <div class="d-flex align-items-center">
                                <div class="health-status healthy"></div>
                                <span class="font-weight-600">Cache</span>
                            </div>
                            <span class="text-success font-weight-bold">Healthy</span>
                        </div>
                        <div class="health-item">
                            <div class="d-flex align-items-center">
                                <div class="health-status warning"></div>
                                <span class="font-weight-600">Storage</span>
                            </div>
                            <span class="text-warning font-weight-bold">{{ $dashboard_data['storage_used'] ?? '45%' }}</span>
                        </div>
                        <div class="health-item">
                            <div class="d-flex align-items-center">
                                <div class="health-status healthy"></div>
                                <span class="font-weight-600">Response</span>
                            </div>
                            <span class="text-success font-weight-bold">{{ $dashboard_data['response_time'] ?? '120ms' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="widget-card">
                <div class="widget-header">
                    <h6 class="widget-title">
                        <i class="fas fa-history"></i>Recent Activities
                    </h6>
                </div>
                <div class="widget-body">
                    <div class="activity-feed">
                        @if(isset($dashboard_data['recent_enquiries']) && count($dashboard_data['recent_enquiries']) > 0)
                            @foreach($dashboard_data['recent_enquiries'] as $enquiry)
                            <div class="activity-item">
                                <div class="activity-avatar">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text">New enquiry from {{ $enquiry['name'] }}</div>
                                    <div class="activity-meta">{{ $enquiry['course'] }} • {{ $enquiry['created_at'] }}</div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent activities</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- System Alerts -->
            @if(isset($dashboard_data['system_alerts']) && count($dashboard_data['system_alerts']) > 0)
            <div class="widget-card">
                <div class="widget-header">
                    <h6 class="widget-title">
                        <i class="fas fa-exclamation-triangle"></i>System Alerts
                    </h6>
                </div>
                <div class="widget-body">
                    @foreach($dashboard_data['system_alerts'] as $alert)
                    <div class="alert-item alert-{{ $alert['level'] }}">
                        <div class="alert-icon" style="background: {{ $alert['level'] === 'critical' ? '#e74c3c' : ($alert['level'] === 'warning' ? '#f39c12' : '#3498db') }};">
                            <i class="fas fa-{{ $alert['icon'] ?? 'exclamation-triangle' }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold mb-1">{{ $alert['title'] }}</div>
                            <div class="text-muted small mb-2">{{ $alert['message'] }}</div>
                            <div class="text-xs text-muted">{{ $alert['time'] }}</div>
                        </div>
                        @if(isset($alert['action_url']))
                        <div>
                            <a href="{{ $alert['action_url'] }}" class="btn btn-sm btn-outline-primary">
                                Resolve
                            </a>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Performance Metrics Footer -->
    <div class="performance-grid">
        <div class="performance-card">
            <div class="performance-value text-primary">{{ $dashboard_data['active_users'] ?? 0 }}</div>
            <div class="performance-label">Active Users</div>
        </div>
        <div class="performance-card">
            <div class="performance-value text-success">{{ $dashboard_data['server_uptime'] ?? '99.9%' }}</div>
            <div class="performance-label">Server Uptime</div>
        </div>
        <div class="performance-card">
            <div class="performance-value text-info">{{ $dashboard_data['response_time'] ?? '120ms' }}</div>
            <div class="performance-label">Avg Response Time</div>
        </div>
        <div class="performance-card">
            <div class="performance-value text-warning">{{ $dashboard_data['storage_used'] ?? '45%' }}</div>
            <div class="performance-label">Storage Used</div>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<button class="fab" data-toggle="modal" data-target="#dashboardCustomizationModal" title="Customize Dashboard">
    <i class="fas fa-palette"></i>
</button>

<!-- Dashboard Customization Modal -->
<div class="modal fade" id="dashboardCustomizationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: var(--primary-gradient); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title">
                    <i class="fas fa-palette me-2"></i>Dashboard Customization
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0" style="background: #f8f9fa; border-radius: 12px;">
                            <div class="card-body text-center">
                                <i class="fas fa-desktop fa-3x text-primary mb-3"></i>
                                <h6 class="font-weight-bold">Dashboard Builder</h6>
                                <p class="text-muted small">Customize widgets and layout</p>
                                @if(Route::has('admin.dashboard-builder.index'))
                                <a href="{{ route('admin.dashboard-builder.index') }}" class="btn btn-primary btn-sm">
                                    Open Builder
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0" style="background: #f8f9fa; border-radius: 12px;">
                            <div class="card-body text-center">
                                <i class="fas fa-cog fa-3x text-secondary mb-3"></i>
                                <h6 class="font-weight-bold">System Settings</h6>
                                <p class="text-muted small">Configure system preferences</p>
                                @if(Route::has('admin.settings.index'))
                                <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary btn-sm">
                                    Open Settings
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0" style="background: #f8f9fa; border-radius: 12px;">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-3x text-success mb-3"></i>
                                <h6 class="font-weight-bold">Analytics</h6>
                                <p class="text-muted small">View detailed reports</p>
                                <button class="btn btn-success btn-sm" onclick="alert('Advanced analytics coming soon!')">
                                    View Reports
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0" style="background: #f8f9fa; border-radius: 12px;">
                            <div class="card-body text-center">
                                <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                                <h6 class="font-weight-bold">Notifications</h6>
                                <p class="text-muted small">Manage notification settings</p>
                                <button class="btn btn-warning btn-sm" onclick="alert('Notification settings coming soon!')">
                                    Configure
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    @if(isset($dashboard_data['revenue_chart']) && isset($dashboard_data['revenue_chart']['labels']))
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: @json($dashboard_data['revenue_chart']['labels']),
                datasets: [{
                    label: 'Revenue',
                    data: @json($dashboard_data['revenue_chart']['data']),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        cornerRadius: 8,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ₹' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#7f8c8d'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(127, 140, 141, 0.1)'
                        },
                        ticks: {
                            color: '#7f8c8d',
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    @endif

    // Animate stat cards on load
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Add hover effects to performance cards
    const performanceCards = document.querySelectorAll('.performance-card');
    performanceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Real-time clock update
    function updateClock() {
        const now = new Date();
        const options = {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        const timeString = now.toLocaleDateString('en-US', options).replace(',', ' •');
        
        const clockElement = document.querySelector('.dashboard-header .badge');
        if (clockElement) {
            clockElement.innerHTML = `<i class="fas fa-clock me-2"></i>${timeString}`;
        }
    }

    // Update clock every minute
    setInterval(updateClock, 60000);

    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        // You can implement AJAX refresh here
        console.log('Refreshing dashboard data...');
        // location.reload(); // Uncomment for full page refresh
    }, 300000);

    // Add smooth scroll to anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add loading states to quick action buttons
    document.querySelectorAll('.quick-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const icon = this.querySelector('.quick-action-icon i');
            const originalClass = icon.className;
            
            icon.className = 'fas fa-spinner fa-spin';
            
            setTimeout(() => {
                icon.className = originalClass;
            }, 1000);
        });
    });

    // Initialize tooltips
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Utility functions
function refreshDashboard() {
    // Show loading indicator
    const fab = document.querySelector('.fab');
    const originalHTML = fab.innerHTML;
    fab.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Simulate refresh
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function exportDashboard() {
    alert('Dashboard export functionality coming soon!');
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + R for refresh
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshDashboard();
    }
    
    // Ctrl + D for dashboard builder
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        const builderLink = document.querySelector('a[href*="dashboard-builder"]');
        if (builderLink) {
            window.location.href = builderLink.href;
        }
    }
});
</script>
@endpush