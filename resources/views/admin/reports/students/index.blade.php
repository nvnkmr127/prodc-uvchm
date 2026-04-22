@extends('layouts.theme')

@section('title', 'Student Data Intelligence Report')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Student Data Intelligence</h1>
            <p class="text-muted small mb-0">Comprehensive reporting with financial and academic insights</p>
        </div>
        <div class="d-flex">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary shadow-sm mr-2">
                <i class="fas fa-print fa-sm mr-1"></i> Print Report
            </button>
            <a href="{{ route('admin.reports.students.export', request()->all()) }}" class="btn btn-sm btn-success shadow-sm">
                <i class="fas fa-file-excel fa-sm mr-1"></i> Export Excel/CSV
            </a>
        </div>
    </div>

    <!-- Quick Stats Summary Row -->
    <div class="row mb-4">
        @php
            $totalStudents = $paginatedResults->total();
            $totalFees = 0;
            $totalPaid = 0;
            $totalDue = 0;
            
            // Note: For a true global summary, we'd need another query, 
            // but for the report we can show stats from the current page or a clone query.
            // For now, let's keep it simple or show 'Filtered View' stats.
        @endphp
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow-sm h-100 py-2 border-0 stats-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xxs font-weight-bold text-primary text-uppercase mb-1">Filtered Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalStudents) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-200"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow-sm h-100 py-2 border-0 stats-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xxs font-weight-bold text-info text-uppercase mb-1">Active Programs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $courses->count() }} Courses</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-200"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow-sm h-100 py-2 border-0 stats-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xxs font-weight-bold text-warning text-uppercase mb-1">Internship Flag</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ request('is_internship') ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-200"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow-sm h-100 py-2 border-0 stats-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xxs font-weight-bold text-success text-uppercase mb-1">Data Integrity</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">No Dropouts</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-200"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filters Toolset -->
    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 bg-white border-bottom-0">
            <h6 class="m-0 font-weight-bold text-dark">Data Filter Console</h6>
        </div>
        <div class="card-body bg-light border-top border-bottom">
            <form action="{{ route('admin.reports.students.index') }}" method="GET" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="small text-muted font-weight-bold">Course</label>
                        <select name="course_id" class="form-control select2" onchange="this.form.submit()">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="small text-muted font-weight-bold">Batch</label>
                        <select name="batch_id" class="form-control select2" onchange="this.form.submit()">
                            <option value="">All Batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="small text-muted font-weight-bold">Internship Only</label>
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" name="is_internship" value="1" class="custom-control-input" id="internshipToggle" {{ request('is_internship') ? 'checked' : '' }} onchange="this.form.submit()">
                            <label class="custom-control-label font-weight-bold text-primary" for="internshipToggle">Enable Filter</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="small text-muted font-weight-bold">Global Search</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Name, Enrollment, Mobile, Village..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                                <a href="{{ route('admin.reports.students.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-sync-alt fa-sm"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Enhanced Data Table -->
    <div class="card shadow mb-4 border-0">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Student Dataset</h6>
            <span class="small font-weight-bold text-muted">Showing {{ $paginatedResults->firstItem() }} to {{ $paginatedResults->lastItem() }} of {{ $paginatedResults->total() }} records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-items-center mb-0">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-uppercase text-xxs font-weight-bolder opacity-7">Identification</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">Contact & Family</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">Academic Path</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7">Location</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7 text-right">Fee Summary</th>
                            <th class="py-3 text-uppercase text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedResults as $student)
                            @php
                                $financials = $student->getFinancialSummary();
                            @endphp
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex flex-column">
                                        <span class="text-dark font-weight-bold mb-0">{{ $student->name }}</span>
                                        <span class="text-xxs text-primary font-weight-bold">{{ $student->enrollment_number ?: 'NO ENROLLMENT' }}</span>
                                        <span class="text-xxs text-muted">ID: #{{ $student->id }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="small font-weight-bold text-dark"><i class="fas fa-phone-alt mr-1 text-muted"></i> {{ $student->student_mobile }}</span>
                                        <span class="small text-muted mb-1"><i class="fas fa-envelope mr-1 text-muted"></i> {{ $student->email ?: 'N/A' }}</span>
                                        <div class="border-top pt-1 mt-1">
                                            <span class="text-xxs font-weight-bold text-uppercase text-muted">Father:</span>
                                            <span class="small d-block">{{ $student->father_name ?: 'N/A' }}</span>
                                            <span class="text-xxs text-muted">{{ $student->father_mobile ?: '' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="small font-weight-bold text-dark">{{ $student->course_name }}</span>
                                        <span class="small text-primary mb-1">{{ $student->batch_name }}</span>
                                        @if($student->batch && $student->batch->is_on_internship)
                                            <span class="badge badge-warning-soft text-warning small px-2 py-0" style="font-size: 0.6rem; width: fit-content;">INTERNSHIP</span>
                                        @endif
                                        <span class="text-xxs text-muted mt-1">Adm: {{ $student->admission_date ? $student->admission_date->format('d M, Y') : 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="small"><i class="fas fa-map-marker-alt text-danger opacity-5 mr-1"></i> {{ $student->village ?: 'N/A' }}</span>
                                        <span class="text-xxs text-muted">Source: {{ $student->source ?: 'Direct' }}</span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="small font-weight-bold text-dark">Total: {{ number_format($financials['total_fees'], 2) }}</span>
                                        <span class="small text-success">Paid: {{ number_format($financials['total_paid'], 2) }}</span>
                                        <span class="small font-weight-bold {{ $financials['total_outstanding'] > 0 ? 'text-danger' : 'text-muted' }}">
                                            Due: {{ number_format($financials['total_outstanding'], 2) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-pill {{ $student->status == 'active' ? 'badge-success-soft text-success' : 'badge-secondary-soft text-secondary' }} px-3">
                                        {{ ucfirst($student->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3 opacity-3"></i>
                                        <p class="h5">No student records found matching your criteria</p>
                                        <a href="{{ route('admin.reports.students.index') }}" class="btn btn-sm btn-primary">Clear All Filters</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-4 d-flex justify-content-center">
            {{ $paginatedResults->links() }}
        </div>
    </div>
</div>

@push('styles')
<style>
    .stats-card { transition: transform 0.2s; cursor: default; }
    .stats-card:hover { transform: translateY(-3px); }
    .text-xxs { font-size: 0.65rem; line-height: 1.2; }
    .badge-success-soft { background-color: #e6f9f0; color: #1cc88a; }
    .badge-secondary-soft { background-color: #f4f5f7; color: #858796; }
    .badge-warning-soft { background-color: #fffbf0; color: #f6c23e; border: 1px solid #ffeeba; }
    
    .table thead th {
        background-color: #f8f9fc;
        border-bottom: 2px solid #e3e6f0;
    }
    
    .table td {
        vertical-align: middle;
        padding: 0.75rem;
    }

    .custom-switch .custom-control-label::before {
        height: 1.5rem;
        width: 2.75rem;
        border-radius: 1rem;
    }
    .custom-switch .custom-control-label::after {
        width: calc(1.5rem - 4px);
        height: calc(1.5rem - 4px);
        border-radius: 1rem;
    }
    .custom-switch .custom-control-input:checked ~ .custom-control-label::after {
        transform: translateX(1.25rem);
    }
    
    @media print {
        .d-print-none, .card-footer, .card-header .btn, form, .input-group, .custom-switch { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table { width: 100% !important; }
    }
</style>
@endpush
@endsection
