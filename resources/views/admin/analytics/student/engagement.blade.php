@extends('layouts.theme')

@section('title', 'Behavioral & Engagement Insights')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0 mb-4">
            <li class="breadcrumb-item"><a href="{{ route('admin.analytics.student.index') }}">Analytics</a></li>
            <li class="breadcrumb-item active">Behavioral & Engagement</li>
        </ol>
    </nav>

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0 text-gray-800">Behavioral & Engagement Analytics</h1>
        <form class="form-inline bg-white p-2 rounded shadow-sm border">
            <div class="form-group mr-2">
                <select name="course_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mr-2">
                <select name="batch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Batches</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" {{ $batchId == $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="btn-group ml-2 border-left pl-2">
                <a href="?days=7&course_id={{ $courseId }}&batch_id={{ $batchId }}" class="btn btn-sm {{ $days == 7 ? 'btn-info' : 'btn-white' }}">7 Days</a>
                <a href="?days=30&course_id={{ $courseId }}&batch_id={{ $batchId }}" class="btn btn-sm {{ $days == 30 ? 'btn-info' : 'btn-white' }}">30 Days</a>
                <a href="?days=90&course_id={{ $courseId }}&batch_id={{ $batchId }}" class="btn btn-sm {{ $days == 90 ? 'btn-info' : 'btn-white' }}">90 Days</a>
                <input type="hidden" name="days" value="{{ $days }}">
            </div>
        </form>
    </div>

    <div class="row">
        <!-- Portal Engagement Rate -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card shadow h-100 py-2 border-0" style="background: linear-gradient(135deg, #17a2b8 0%, #0c616e 100%);">
                <div class="card-body text-white">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="opacity: 0.8;">Portal Engagement Rate</div>
                            <div class="h2 mb-0 font-weight-bold">{{ $engagementRate }}%</div>
                            <p class="small mb-0 mt-2" style="opacity: 0.8;">
                                <i class="fas fa-chart-line mr-1"></i> Active students in portal over last {{ $days }} days.
                            </p>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-fingerprint fa-4x" style="opacity: 0.2;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Active Students -->
        <div class="col-xl-8 col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 bg-white border-bottom-0">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-trophy mr-2 text-warning"></i>Most Active Students (Last {{ $days }}d)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 pl-4">Student</th>
                                    <th class="border-0 text-center">Interactions</th>
                                    <th class="border-0 text-right pr-4">Activity Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topActiveStudents as $log)
                                <tr>
                                    <td class="pl-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-info-soft p-2 mr-3" style="width: 32px; height: 32px; background: rgba(54, 185, 204, 0.1); display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user-graduate text-info small"></i>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold text-gray-800">{{ $log->student->name ?? 'Deleted Student' }}</div>
                                                <div class="text-xs text-muted">{{ $log->student->enrollment_number ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <span class="badge badge-light px-3 rounded-pill border">{{ $log->activity_count }} logs</span>
                                    </td>
                                    <td class="text-right pr-4 py-3">
                                        @php
                                            $maxCount = $topActiveStudents->first()->activity_count;
                                            $score = $maxCount > 0 ? ($log->activity_count / $maxCount) * 100 : 0;
                                        @endphp
                                        <div class="progress progress-sm mt-2" style="width: 100px; display: inline-flex;">
                                            <div class="progress-bar bg-info" style="width: {{ $score }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Counselor Performance -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-handshake mr-2"></i>Counselor Conversion Efficiency
                    </h6>
                    <span class="text-muted small">Based on overall system performance</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($counselorPerformance as $counselor)
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border shadow-none h-100 p-3" style="border-radius: 12px; transition: transform 0.2s; cursor: default;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-lg bg-primary-soft p-3 mr-3" style="background: rgba(78, 115, 223, 0.1);">
                                        <i class="fas fa-user-tie text-primary fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 font-weight-bold text-gray-800">{{ $counselor->name }}</h6>
                                        <small class="text-muted">Conversion Expert</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-xs font-weight-bold">Conversion Rate</span>
                                        <span class="text-xs font-weight-bold text-primary">{{ $counselor->conversion_rate }}%</span>
                                    </div>
                                    <div class="progress progress-sm rounded-pill overflow-hidden" style="height: 8px;">
                                        <div class="progress-bar bg-gradient-primary" style="width: {{ $counselor->conversion_rate }}%"></div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between text-center pt-2 border-top">
                                    <div class="flex-grow-1">
                                        <div class="text-xs text-muted uppercase">Leads</div>
                                        <div class="font-weight-bold">{{ $counselor->total_enquiries }}</div>
                                    </div>
                                    <div class="flex-grow-1 border-left">
                                        <div class="text-xs text-muted uppercase">Closed</div>
                                        <div class="font-weight-bold text-success">{{ $counselor->converted_enquiries }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
