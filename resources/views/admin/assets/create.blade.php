@extends('layouts.theme')
@section('title', 'Add New Asset')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Asset</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.assets.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-8"><div class="mb-3"><label>Asset Name</label><input type="text" name="name" class="form-control" required></div></div>
                <div class="col-md-4"><div class="mb-3"><label>Asset Code (Optional)</label><input type="text" name="asset_code" class="form-control"></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Asset Category</label><select name="asset_category_id" class="form-control" required><option value="">-- Select --</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Location</label><input type="text" name="location" class="form-control" required></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Condition</label><select name="condition" class="form-control" required><option>Good</option><option>Fair</option><option>Needs Repair</option><option>Damaged</option><option>Missing</option></select></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Quantity</label><input type="number" name="quantity" class="form-control" value="1" required></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Purchase Price</label><input type="number" step="0.01" name="purchase_price" class="form-control"></div></div>
            </div>
            <button type="submit" class="btn btn-primary">Save Asset</button>
        </form>
    </div>
</div>
@endsection