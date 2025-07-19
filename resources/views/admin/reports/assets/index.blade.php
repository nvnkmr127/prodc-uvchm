@extends('layouts.theme')
@section('title', 'Asset Reports')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Asset Reports</h1>

{{-- Filter Form --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Assets</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.reports.assets.index') }}" method="GET">
            <div class="row">
                <div class="col-md-4">
                    <label>Filter by Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- All Categories --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Filter by Condition</label>
                    <select name="condition" class="form-control">
                        <option value="">-- All Conditions --</option>
                        @foreach(['Good', 'Fair', 'Needs Repair', 'Damaged', 'Missing'] as $condition)
                            <option value="{{ $condition }}" {{ request('condition') == $condition ? 'selected' : '' }}>
                                {{ $condition }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                 <div class="col-md-2 d-flex align-items-end">
                    <a href="{{ route('admin.reports.assets.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Report Results --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Report Results ({{ $assets->count() }} items found)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Condition</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td>{{ $asset->name }} <br><small class="text-muted">{{ $asset->asset_code }}</small></td>
                            <td>{{ $asset->category?->name ?? 'N/A' }}</td>
                            <td>{{ $asset->location }}</td>
                            <td>{{ $asset->condition }}</td>
                            <td>{{ $asset->quantity }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No assets found matching your criteria.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection