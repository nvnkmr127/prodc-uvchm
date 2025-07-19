@extends('layouts.theme')
@section('title', 'Edit Asset')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Asset</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.assets.update', $asset) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="row">
                <div class="col-md-8"><div class="mb-3"><label>Asset Name</label><input type="text" name="name" class="form-control" value="{{ $asset->name }}" required></div></div>
                <div class="col-md-4"><div class="mb-3"><label>Asset Code (Optional)</label><input type="text" name="asset_code" value="{{ $asset->asset_code }}" class="form-control"></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Asset Category</label><select name="asset_category_id" class="form-control" required>@foreach($categories as $category)<option value="{{ $category->id }}" @if($asset->asset_category_id == $category->id) selected @endif>{{ $category->name }}</option>@endforeach</select></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Location</label><input type="text" name="location" value="{{ $asset->location }}" class="form-control" required></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Condition</label><select name="condition" class="form-control" required>@foreach(['Good', 'Fair', 'Needs Repair', 'Damaged', 'Missing'] as $condition)<option @if($asset->condition == $condition) selected @endif>{{ $condition }}</option>@endforeach</select></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Quantity</label><input type="number" name="quantity" value="{{ $asset->quantity }}" class="form-control" required></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Purchase Date</label><input type="date" name="purchase_date" value="{{ $asset->purchase_date }}" class="form-control"></div></div>
                <div class="col-md-6"><div class="mb-3"><label>Purchase Price</label><input type="number" step="0.01" name="purchase_price" value="{{ $asset->purchase_price }}" class="form-control"></div></div>
            </div>
            <button type="submit" class="btn btn-primary">Update Asset</button>
        </form>
    </div>
</div>
@endsection