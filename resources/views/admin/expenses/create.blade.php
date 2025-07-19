@extends('layouts.theme')
@section('title', 'Log New Expense')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Log New Expense</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.expenses.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3"><label>Expense Category</label><select name="expense_category_id" class="form-control" required><option value="">-- Select --</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-6 mb-3"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                <div class="col-md-12 mb-3"><label>Description</label><input type="text" name="description" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label>Expense Date</label><input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                <div class="col-md-6 mb-3"><label>Vendor (Optional)</label><input type="text" name="vendor" class="form-control"></div>
            </div>
            <button type="submit" class="btn btn-primary">Save Expense</button>
        </form>
    </div>
</div>
@endsection