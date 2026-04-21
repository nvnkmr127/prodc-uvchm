@extends('layouts.theme')

@section('title', 'Lifecycle & Retention Analysis')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0 mb-4">
            <li class="breadcrumb-item"><a href="{{ route('admin.analytics.student.index') }}">Analytics</a></li>
            <li class="breadcrumb-item active">Lifecycle & Retention</li>
        </ol>
    </nav>

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0 text-gray-800">Lifecycle & Retention Analysis</h1>
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
            <div class="form-group mr-2 border-left pl-2">
                <small class="mr-2 text-muted uppercase font-weight-bold">Period:</small>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="form-group mr-2">
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
        </form>
    </div>

    <div class="row">
        <!-- Funnel Analysis -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white border-bottom-0">
                    <h6 class="m-0 font-weight-bold text-primary">Admission Funnel Leakage</h6>
                </div>
                <div class="card-body">
                    <div class="admission-funnel">
                        <div class="funnel-step border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-weight-bold">Total Enquiries</span>
                                <span class="badge badge-primary px-3 rounded-pill">{{ $funnelData['enquiries'] }}</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-primary" style="width: 100%"></div>
                            </div>
                        </div>
                        <div class="funnel-step border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-weight-bold">Admissions Created</span>
                                <span class="badge badge-info px-3 rounded-pill">{{ $funnelData['admissions'] }}</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-info" style="width: {{ $funnelData['enquiries'] > 0 ? ($funnelData['admissions'] / $funnelData['enquiries']) * 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block text-right">{{ $funnelData['enquiries'] > 0 ? round(($funnelData['admissions'] / $funnelData['enquiries']) * 100, 1) : 0 }}% conversion from Enquiry</small>
                        </div>
                        <div class="funnel-step">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-weight-bold">Approved Admissions</span>
                                <span class="badge badge-success px-3 rounded-pill">{{ $funnelData['approved'] }}</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: {{ $funnelData['enquiries'] > 0 ? ($funnelData['approved'] / $funnelData['enquiries']) * 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block text-right text-success font-weight-bold">{{ $funnelData['enquiries'] > 0 ? round(($funnelData['approved'] / $funnelData['enquiries']) * 100, 1) : 0 }}% Final Success Rate</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cohort Retention -->
            <div class="card shadow mb-4 border-0" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                <div class="card-header py-3 bg-transparent border-0">
                    <h6 class="m-0 font-weight-bold text-white">Admissions by Year (Approved)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless text-white mb-0">
                            <thead class="bg-white-transparent" style="background: rgba(255,255,255,0.1);">
                                <tr>
                                    <th class="small py-2">Year</th>
                                    <th class="small py-2 text-center">Total</th>
                                    <th class="small py-2 text-center">Approved</th>
                                    <th class="small py-2 text-right">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cohortRetention as $cohort)
                                <tr class="border-bottom" style="border-color: rgba(255,255,255,0.1) !important;">
                                    <td class="py-2">{{ $cohort->year }}</td>
                                    <td class="py-2 text-center">{{ $cohort->total }}</td>
                                    <td class="py-2 text-center">{{ $cohort->approved }}</td>
                                    <td class="py-2 text-right small font-weight-bold">
                                        {{ $cohort->total > 0 ? round(($cohort->approved / $cohort->total) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dropout Risks -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>Critical Dropout Risks
                    </h6>
                    <span class="badge badge-danger px-3 rounded-pill">{{ count($dropoutRiskStudents) }} High Risk</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Students identified as high risk due to <strong>low attendance (<75%)</strong> and <strong>pending fee balances</strong>.</p>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm border-0" id="riskTable">
                            <thead>
                                <tr class="bg-light">
                                    <th>Student</th>
                                    <th>Batch / Course</th>
                                    <th>Enrolled</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dropoutRiskStudents as $student)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold text-gray-800">{{ $student->name }}</div>
                                        <div class="small text-muted">{{ $student->enrollment_number }}</div>
                                    </td>
                                    <td>
                                        <div class="small">{{ $student->batch->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-primary">{{ $student->batch->course->name ?? 'N/A' }}</div>
                                    </td>
                                    <td>{{ $student->created_at->format('M Y') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-sm btn-outline-danger px-3 rounded-pill">Notify</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                        <h6 class="text-gray-800">No high-risk students found</h6>
                                        <p class="small text-muted">All active students seem to be on track with attendance and payments.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
