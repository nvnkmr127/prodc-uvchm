@extends('layouts.theme')

@section('title', 'Payment Defaulters')

@push('styles')
<style>
.card-animate {
    transition: all 0.3s ease;
}
.card-animate:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.defaulter-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Payment Defaulters</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.payment-reminders.dashboard') }}">Payment Reminders</a></li>
                        <li class="breadcrumb-item active">Defaulters</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="card-title mb-0">Filters & Actions</h4>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" onclick="exportDefaulters()">
                                    <i class="mdi mdi-download me-1"></i>
                                    Export CSV
                                </button>
                                <button type="button" class="btn btn-info" onclick="updateDefaulters()">
                                    <i class="mdi mdi-refresh me-1"></i>
                                    Refresh Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" name="category" id="category">
                                    <option value="">All Categories</option>
                                    <option value="mild" {{ request('category') === 'mild' ? 'selected' : '' }}>Mild</option>
                                    <option value="moderate" {{ request('category') === 'moderate' ? 'selected' : '' }}>Moderate</option>
                                    <option value="severe" {{ request('category') === 'severe' ? 'selected' : '' }}>Severe</option>
                                    <option value="chronic" {{ request('category') === 'chronic' ? 'selected' : '' }}>Chronic</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="min_amount" class="form-label">Min Amount</label>
                                <input type="number" class="form-control" name="min_amount" id="min_amount" 
                                       value="{{ request('min_amount') }}" placeholder="Minimum overdue amount">
                            </div>
                            <div class="col-md-3">
                                <label for="course" class="form-label">Course</label>
                                <input type="text" class="form-control" name="course" id="course" 
                                       value="{{ request('course') }}" placeholder="Course name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-magnify me-1"></i>
                                        Filter
                                    </button>
                                    <a href="{{ route('payment-reminders.defaulters') }}" class="btn btn-outline-secondary">
                                        <i class="mdi mdi-close me-1"></i>
                                        Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold text-muted mb-0">Total Defaulters</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-danger mb-0">
                                <i class="mdi mdi-account-alert align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                {{ number_format(count($defaulters)) }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold text-muted mb-0">Total Overdue Amount</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-warning mb-0">
                                <i class="mdi mdi-currency-inr align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                ₹{{ number_format(collect($defaulters)->sum('total_overdue_amount'), 2) }}
                            </h4>
                        </div>
                    </div>
                </div>