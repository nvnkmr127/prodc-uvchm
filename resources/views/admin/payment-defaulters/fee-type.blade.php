<?php
// 6. Fee-Type Specific View
// resources/views/admin/payment-defaulters/fee-type.blade.php
?>
@extends('layouts.admin')

@section('title', 'Unpaid ' . ucwords(str_replace('_', ' ', $feeType)) . ' Fees')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Unpaid {{ ucwords(str_replace('_', ' ', $feeType)) }} Fees</h4>
                </div>
                
                <!-- Filter Form -->
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="fee_type" class="form-select">
                                <option value="">Select Fee Type</option>
                                @foreach($feeCategories as $category)
                                    <option value="{{ $category->category_type }}" 
                                            {{ $feeType == $category->category_type ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="course_id" class="form-select">
                                <option value="">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="batch_id" class="form-select">
                                <option value="">All Batches</option>
                                @foreach($batches as $batch)
                                    <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                        {{ $batch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>{{ $stats['total_students'] }}</h5>
                                    <p>Total Students</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>{{ $stats['paid_students'] }}</h5>
                                    <p>Paid</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>{{ $stats['unpaid_students'] }}</h5>
                                    <p>Unpaid</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5>{{ $stats['partial_paid_students'] }}</h5>
                                    <p>Partial</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>{{ number_format($stats['collection_percentage'], 1) }}%</h5>
                                    <p>Collection Rate</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h5>₹{{ number_format($stats['pending_amount']/1000, 0) }}K</h5>
                                    <p>Pending Amount</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unpaid Students Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course/Batch</th>
                                    <th>Unpaid Amount</th>
                                    <th>Oldest Due Date</th>
                                    <th>Overdue Days</th>
                                    <th>Invoice Count</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($unpaidStudents as $unpaid)
                                <tr>
                                    <td>
                                        <strong>{{ $unpaid['student']->name }}</strong><br>
                                        <small>{{ $unpaid['student']->enrollment_number }}</small>
                                    </td>
                                    <td>
                                        {{ $unpaid['student']->course_name }}<br>
                                        <small>{{ $unpaid['student']->batch_name }}</small>
                                    </td>
                                    <td>
                                        <span class="text-danger font-weight-bold">
                                            ₹{{ number_format($unpaid['unpaid_amount']) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($unpaid['oldest_due_date'])->format('d-m-Y') }}
                                    </td>
                                    <td>
                                        @if($unpaid['overdue_days'] > 0)
                                            <span class="badge badge-danger">{{ $unpaid['overdue_days'] }} days</span>
                                        @else
                                            <span class="badge badge-warning">{{ abs($unpaid['overdue_days']) }} days left</span>
                                        @endif
                                    </td>
                                    <td>{{ $unpaid['invoice_count'] }}</td>
                                    <td>
                                        @if($unpaid['student']->student_mobile)
                                            <a href="tel:{{ $unpaid['student']->student_mobile }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                        @endif
                                        @if($unpaid['student']->email)
                                            <a href="mailto:{{ $unpaid['student']->email }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.students.show', $unpaid['student']->id) }}" 
                                               class="btn btn-outline-primary" title="View Student">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.invoices.ledger', $unpaid['student']->id) }}" 
                                               class="btn btn-outline-success" title="View Ledger">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                            <button class="btn btn-outline-warning" title="Send Reminder">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <em>All students have paid their {{ ucwords(str_replace('_', ' ', $feeType)) }} fees! 🎉</em>
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
