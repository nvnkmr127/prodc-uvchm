@extends('layouts.theme')
@section('title', 'All Expenses')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">All Expenses</h1>
    <a href="{{ route('admin.expenses.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Log New Expense</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Vendor</th><th class="text-right">Amount</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, Y') }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->category->name }}</td>
                            <td>{{ $expense->vendor ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($expense->amount, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" class="d-inline"> @csrf @method('DELETE') <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No expenses logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection