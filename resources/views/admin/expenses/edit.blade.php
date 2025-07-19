@extends('layouts.theme')
@section('title', 'Edit Expense')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Expense</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.expenses.update', $expense) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="row">
                <div class="col-md-6 mb-3"><label>Expense Category</label><select name="expense_category_id" class="form-control" required>@foreach($categories as $category)<option value="{{ $category->id }}" @if($expense->expense_category_id == $category->id) selected @endif>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-6 mb-3"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ $expense->amount }}" required></div>
                <div class="col-md-12 mb-3"><label>Description</label><input type="text" name="description" class="form-control" value="{{ $expense->description }}" required></div>
                <div class="col-md-6 mb-3"><label>Expense Date</label><input type="date" name="expense_date" class="form-control" value="{{ $expense->expense_date }}" required></div>
                <div class="col-md-6 mb-3"><label>Vendor (Optional)</label><input type="text" name="vendor" class="form-control" value="{{ $expense->vendor }}"></div>
            </div>
            <button type="submit" class="btn btn-primary">Update Expense</button>
        </form>
    </div>
</div>
@endsection