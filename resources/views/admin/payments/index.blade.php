@extends('layouts.theme')

@section('title', 'Component Payments')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Component Payments</h1>
            <div>
                @can('create payments')
                    <a href="{{ route('admin.component-payments.create') }}" class="btn btn-primary shadow-sm">
                        <i class="fas fa-plus mr-1"></i> New Payment
                    </a>
                @endcan
                <a href="{{ route('admin.component-payments.export') }}" class="btn btn-success shadow-sm">
                    <i class="fas fa-download mr-1"></i> Export
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Collected</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    ₹{{ number_format($payments->sum('amount'), 2) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Transactions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $payments->total() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter mr-1"></i> Filter Payments
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.component-payments.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small font-weight-bold">Student</label>
                        <input type="text" name="student_search" class="form-control"
                            value="{{ request('student_search') }}" placeholder="Name or Enrollment ID">
                    </div>

                    {{-- [NEW] Fee Component Filter --}}
                    <div class="col-md-3">
                        <label class="form-label small font-weight-bold">Fee Component</label>
                        <select name="fee_category_id" class="form-control">
                            <option value="">All Components</option>
                            @foreach($feeCategories as $category)
                                <option value="{{ $category->id }}" {{ request('fee_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small font-weight-bold">Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="">All</option>
                            <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="online" {{ request('payment_method') == 'online' ? 'selected' : '' }}>Online
                            </option>
                            <option value="upi" {{ request('payment_method') == 'upi' ? 'selected' : '' }}>UPI</option>
                            <option value="cheque" {{ request('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque
                            </option>
                            <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small font-weight-bold">Date Range</label>
                        <input type="date" name="date_from" class="form-control mb-1" value="{{ request('date_from') }}"
                            placeholder="From">
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"
                            placeholder="To">
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-1">
                        <button type="submit" class="btn btn-primary w-100 mr-2">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('admin.component-payments.index') }}" class="btn btn-secondary w-50">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Payment List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead class="bg-light">
                            <tr>
                                <th>Receipt #</th>
                                <th>Student</th>
                                <th>Collected By</th> {{-- [NEW COLUMN] --}}
                                <th>Components Breakdown</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="font-weight-bold text-primary">
                                        {{ $payment->receipt_number }}
                                        @if($payment->transaction_id)
                                            <br><small class="text-muted" title="Transaction ID">TXN:
                                                {{ $payment->transaction_id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->student)
                                            <a href="{{ route('admin.students.show', $payment->student_id) }}"
                                                class="font-weight-bold text-dark">
                                                {{ $payment->student->name }}
                                            </a>
                                            <small class="d-block text-muted">{{ $payment->student->enrollment_number }}</small>
                                        @else
                                            <span class="text-muted">Unknown Student</span>
                                        @endif
                                    </td>
                                    <td> {{-- [NEW COLUMN DATA] --}}
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle-sm bg-secondary text-white mr-2"
                                                style="width: 24px; height: 24px; font-size: 10px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                                {{ substr($payment->createdBy?->name ?? 'S', 0, 1) }}
                                            </div>
                                            <span
                                                class="small font-weight-bold">{{ $payment->createdBy?->name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($payment->componentItems->count() > 0)
                                            <ul class="list-unstyled mb-0 small">
                                                @foreach($payment->componentItems as $item)
                                                    <li class="d-flex justify-content-between">
                                                        <span>{{ $item->studentFee->feeCategory->name ?? 'Fee' }}:</span>
                                                        <span
                                                            class="font-weight-bold">₹{{ number_format($item->amount_paid, 0) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted small">No details</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="h6 font-weight-bold text-success mb-0">
                                            ₹{{ number_format($payment->amount, 2) }}</div>
                                        @if($payment->status != 'completed')
                                            <span class="badge badge-warning">{{ ucfirst($payment->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $payment->payment_date ? $payment->payment_date->format('d M, Y') : 'N/A' }}
                                        <small class="d-block text-muted">{{ $payment->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.component-payments.show', $payment->id) }}"
                                                class="btn btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('public.receipt.show', $payment->receipt_number) }}"
                                                class="btn btn-outline-info" target="_blank" title="Print Receipt">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-file-invoice-dollar fa-3x mb-3 text-gray-300"></i>
                                        <p class="mb-0">No payments found matching your criteria.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $payments->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                // Date range validation
                $('input[name="date_from"], input[name="date_to"]').on('change', function () {
                    const dateFrom = $('input[name="date_from"]').val();
                    const dateTo = $('input[name="date_to"]').val();

                    if (dateFrom && dateTo && dateFrom > dateTo) {
                        alert('Start date cannot be later than end date');
                        $(this).val('');
                    }
                });
            });
        </script>
    @endpush

@endsection