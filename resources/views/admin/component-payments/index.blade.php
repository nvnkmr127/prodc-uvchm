@extends('layouts.theme')

@section('title', 'Payment Details: ' . $componentPayment->receipt_number)

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Payment Details</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.component-payments.index') }}">Component
                                Payments</a></li>
                        <li class="breadcrumb-item active">{{ $componentPayment->receipt_number }}</li>
                    </ol>
                </nav>
            </div>
            <div class="btn-group">
                <a href="{{ route('admin.component-payments.index') }}" class="btn btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to List
                </a>
                @if(Route::has('public.receipt.show'))
                    <a href="{{ route('public.receipt.show', $componentPayment->receipt_number) }}"
                        class="btn btn-primary shadow-sm" target="_blank">
                        <i class="fas fa-print fa-sm text-white-50 mr-1"></i> Print Receipt
                    </a>
                @endif
            </div>
        </div>

        <div class="row">
            <!-- left column -->
            <div class="col-xl-8 col-lg-7">
                <!-- Payment Info -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Payment Information</h6>
                        <span
                            class="badge badge-{{ $componentPayment->status === 'completed' ? 'success' : 'warning' }} px-3 py-2">
                            {{ ucfirst($componentPayment->status) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4 text-center border-right">
                                <label class="small font-weight-bold text-muted text-uppercase mb-1">Total Amount</label>
                                <div class="h3 font-weight-bold text-success">
                                    ₹{{ number_format($componentPayment->amount, 2) }}
                                </div>
                            </div>
                            <div class="col-md-4 text-center border-right">
                                <label class="small font-weight-bold text-muted text-uppercase mb-1">Receipt Number</label>
                                <div class="h5 font-weight-bold text-gray-800 mt-2">
                                    {{ $componentPayment->receipt_number }}
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <label class="small font-weight-bold text-muted text-uppercase mb-1">Payment Date</label>
                                <div class="h5 font-weight-bold text-gray-800 mt-2">
                                    {{ $componentPayment->payment_date ? $componentPayment->payment_date->format('d M, Y') : 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong class="text-gray-900">Payment Method:</strong>
                                <span
                                    class="badge badge-secondary ml-2">{{ ucfirst(str_replace('_', ' ', $componentPayment->payment_method)) }}</span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong class="text-gray-900">Transaction ID:</strong>
                                <p class="text-muted d-inline ml-2">{{ $componentPayment->transaction_id ?? 'N/A' }}</p>
                            </div>
                            <div class="col-12">
                                <strong class="text-gray-900">Notes:</strong>
                                <p class="text-muted mt-1 p-2 bg-light rounded">
                                    {{ $componentPayment->notes ?? 'No notes provided.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Components Breakdown -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">Fee Components Breakdown</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Fee Category</th>
                                        <th class="text-right">Amount Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($componentPayment->componentItems as $item)
                                        <tr>
                                            <td>
                                                <span class="font-weight-bold text-gray-700">
                                                    {{ $item->studentFee->feeCategory->name ?? 'Unknown Category' }}
                                                </span>
                                            </td>
                                            <td class="text-right font-weight-bold text-success">
                                                ₹{{ number_format($item->amount_paid, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-3 text-muted">
                                                No component details available.
                                            </td>
                                        </tr>
                                    @endforelse
                                    <tr class="bg-gray-100">
                                        <td class="text-right font-weight-bold">Total:</td>
                                        <td class="text-right font-weight-bold text-success">
                                            ₹{{ number_format($componentPayment->amount, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Student Info -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-secondary">Student Details</h6>
                    </div>
                    <div class="card-body">
                        @if($componentPayment->student)
                            <div class="text-center mb-4">
                                <div class="avatar-circle mx-auto mb-3"
                                    style="width: 80px; height: 80px; background: #6c757d; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 32px; font-weight: bold;">
                                    {{ substr($componentPayment->student->name, 0, 1) }}
                                </div>
                                <h5 class="font-weight-bold">{{ $componentPayment->student->name }}</h5>
                                <p class="text-muted">{{ $componentPayment->student->enrollment_number }}</p>
                                <a href="{{ route('admin.students.show', $componentPayment->student->id) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    View Profile
                                </a>
                            </div>

                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Course
                                    <span
                                        class="font-weight-bold">{{ $componentPayment->student->batch->course->name ?? 'N/A' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Batch
                                    <span class="font-weight-bold">{{ $componentPayment->student->batch->name ?? 'N/A' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Mobile
                                    <span
                                        class="font-weight-bold">{{ $componentPayment->student->student_mobile ?? 'N/A' }}</span>
                                </li>
                            </ul>
                        @else
                            <div class="alert alert-warning text-center">
                                Student information not available.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Meta Info -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-secondary">Record Info</h6>
                    </div>
                    <div class="card-body small">
                        <p class="mb-2">
                            <strong>Created By:</strong> <br>
                            {{ $componentPayment->createdBy->name ?? 'System' }}
                        </p>
                        <p class="mb-2">
                            <strong>Created At:</strong> <br>
                            {{ $componentPayment->created_at->format('d M, Y h:i A') }}
                        </p>
                        @if($componentPayment->updated_at != $componentPayment->created_at)
                            <p class="mb-0">
                                <strong>Last Updated:</strong> <br>
                                {{ $componentPayment->updated_at->format('d M, Y h:i A') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection