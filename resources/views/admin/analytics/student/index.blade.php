@extends('layouts.theme')

@section('title', 'Student Analytics Overview')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0 mb-4">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Student Analytics Hub</li>
        </ol>
    </nav>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-pie mr-2 text-primary"></i>Student Analytics Hub
        </h1>
        <div>
            <a href="{{ route('admin.analytics.student.lifecycle') }}" class="btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-sync-alt fa-sm text-white-50 mr-1"></i> Lifecycle Analytics
            </a>
            <a href="{{ route('admin.analytics.student.engagement') }}" class="btn btn-sm btn-info shadow-sm">
                <i class="fas fa-user-clock fa-sm text-white-50 mr-1"></i> Engagement Insights
            </a>
        </div>
    </div>

    <!-- Quick Stats Overview -->
    <div class="row">
        <!-- Lifecycle Overview -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Lifecycle Overview (Retention)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $lifecycleStats['total_active'] }} Active Students</div>
                            <div class="mt-2 small">
                                <span class="text-danger mr-2">
                                    <i class="fas fa-user-minus"></i> {{ $lifecycleStats['dropout_count'] }} Dropouts
                                </span>
                                <span class="text-success">
                                    <i class="fas fa-user-plus"></i> {{ $lifecycleStats['recent_conversions'] }} New Conversions (30d)
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-id-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engagement Overview -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Digital Engagement (Portal)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $engagementStats['daily_active_portal'] }} Active Today</div>
                            <div class="mt-2 small">
                                <span class="text-info mr-2">
                                    <i class="fas fa-users"></i> {{ $engagementStats['weekly_active_portal'] }} Active this week
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-mouse-pointer fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Analytics Sections -->
    <div class="row">
        <!-- Lifecycle Feature Card -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-0">
                    <h6 class="m-0 font-weight-bold text-primary">Student Lifecycle & Retention</h6>
                    <a href="{{ route('admin.analytics.student.lifecycle') }}" class="btn btn-primary btn-sm rounded-pill px-3">Deep Dive</a>
                </div>
                <div class="card-body p-0">
                    <div class="p-4" style="background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);">
                        <p class="text-muted small mb-4">Identify dropout risks and analyze your enrollment funnel efficiency.</p>
                        <div class="list-group list-group-flush border-0 bg-transparent">
                            <div class="list-group-item bg-transparent px-0 border-0 d-flex align-items-center mb-2">
                                <div class="bg-primary-soft rounded p-2 mr-3" style="width: 40px; height: 40px; background: rgba(78, 115, 223, 0.1); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-exclamation-triangle text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-gray-800">Dropout Risk Prediction</h6>
                                    <small class="text-muted">Correlates attendance and fee status.</small>
                                </div>
                            </div>
                            <div class="list-group-item bg-transparent px-0 border-0 d-flex align-items-center mb-2">
                                <div class="bg-success-soft rounded p-2 mr-3" style="width: 40px; height: 40px; background: rgba(28, 200, 138, 0.1); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-filter text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-gray-800">Enrollment Funnel Leakage</h6>
                                    <small class="text-muted">Track enquiry to admission conversion.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Behavioral Feature Card -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-0">
                    <h6 class="m-0 font-weight-bold text-info">Behavioral & Engagement</h6>
                    <a href="{{ route('admin.analytics.student.engagement') }}" class="btn btn-info btn-sm rounded-pill px-3">Explore Insights</a>
                </div>
                <div class="card-body p-0">
                    <div class="p-4" style="background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);">
                        <p class="text-muted small mb-4">Analyze how students interact with the system and evaluate counselor performance.</p>
                        <div class="list-group list-group-flush border-0 bg-transparent">
                            <div class="list-group-item bg-transparent px-0 border-0 d-flex align-items-center mb-2">
                                <div class="bg-info-soft rounded p-2 mr-3" style="width: 40px; height: 40px; background: rgba(54, 185, 204, 0.1); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-bolt text-info"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-gray-800">Engagement Score</h6>
                                    <small class="text-muted">Portal activity based user ranking.</small>
                                </div>
                            </div>
                            <div class="list-group-item bg-transparent px-0 border-0 d-flex align-items-center mb-2">
                                <div class="bg-warning-soft rounded p-2 mr-3" style="width: 40px; height: 40px; background: rgba(246, 194, 62, 0.1); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-medal text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-gray-800">Counselor Performance</h6>
                                    <small class="text-muted">Conversion metrics per staff member.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft { background-color: rgba(78, 115, 223, 0.1); }
    .bg-info-soft { background-color: rgba(54, 185, 204, 0.1); }
    .bg-success-soft { background-color: rgba(28, 200, 138, 0.1); }
    .bg-warning-soft { background-color: rgba(246, 194, 62, 0.1); }
</style>
@endsection
