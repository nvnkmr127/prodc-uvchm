@extends('layouts.theme')
@section('title', 'Financial Hub')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Financial Hub</h1>
    {{-- The button for manual batch invoice generation has been removed to prevent errors. --}}
</div>

{{-- Search Form --}}
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Find Student Ledger</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.invoices.index') }}" method="GET">
            <div class="form-group">
                <label for="search">Search by Student Name or Enrollment Number</label>
                <input type="text" name="search" class="form-control" placeholder="Enter name or enrollment #" value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-info mt-2">Search</button>
        </form>
    </div>
</div>

@if(isset($students))
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Search Results</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Student Name</th><th>Enrollment #</th><th>Batch</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->enrollment_number }}</td>
                            <td>{{ $student->batch->name ?? 'N/A' }}</td>
                            <td><a href="{{ route('admin.financials.student.ledger', $student) }}" class="btn btn-primary btn-sm">View Ledger</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No students found matching your search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
{{-- This section shows recent invoices when you are not searching. --}}
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Recent Invoices</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Student Name</th>
                        <th class="text-right">Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Due Date</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($recentInvoices)
                        @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                    @if($invoice->term_number)
                                        <span class="badge badge-info ml-1">Term {{ $invoice->term_number }}</span>
                                    @endif
                                </td>
                                <td>{{ $invoice->student->name ?? 'N/A' }}</td>
                                <td class="text-right">₹{{ number_format($invoice->total_amount, 2) }}</td>
                                <td class="text-center">
                                    @if($invoice->status == 'paid')
                                        <span class="badge badge-success">Paid</span>
                                    @elseif($invoice->status == 'partially_paid')
                                        <span class="badge badge-warning">Partially Paid</span>
                                    @else
                                        <span class="badge badge-danger">Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-primary btn-sm">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">No recent invoices found.</td></tr>
                        @endforelse
                    @else
                        <tr><td colspan="6" class="text-center">Recent invoice data is not available. (Requires controller update)</td></tr>
                    @endisset
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
