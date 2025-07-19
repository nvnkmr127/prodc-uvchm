@extends('layouts.theme')
@section('title', 'Conduct Audit #' . $audit->id)

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Conducting Audit #{{ $audit->id }}</h1>
        <p class="text-muted">Started on: {{ \Carbon\Carbon::parse($audit->audit_date)->format('d M, Y') }}</p>
    </div>
    @if($audit->status == 'In Progress')
    <form action="{{ route('admin.audits.complete', $audit) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-lg btn-success shadow-sm" onclick="return confirm('Are you sure you want to complete this audit? You will not be able to make further changes.')"><i class="fas fa-check-circle fa-sm text-white-50"></i> Complete Audit</button>
    </form>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Filter Form --}}
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.audits.show', $audit) }}">
            <div class="row">
                <div class="col-md-5"><select name="category_id" class="form-control"><option value="">-- All Categories --</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-5"><input type="text" name="location" class="form-control" placeholder="Filter by Location" value="{{ request('location') }}"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </div>
        </form>
    </div>
</div>

{{-- Asset Checklist --}}
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Asset</th><th>Category / Location</th><th>Expected Condition</th><th>Audit Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($assets as $asset)
                        @php
                            $auditItem = $audit_items->where('asset_id', $asset->id)->first();
                            $status = $auditItem->status ?? 'Pending';
                            $badgeClass = 'secondary';
                            if ($status == 'Found') $badgeClass = 'success';
                            if ($status == 'Missing') $badgeClass = 'danger';
                            if ($status == 'Damaged') $badgeClass = 'warning';
                        @endphp
                        <tr class="{{ $status != 'Pending' ? 'table-light' : '' }}">
                            <td>{{ $asset->name }} <small class="text-muted d-block">{{ $asset->asset_code }}</small></td>
                            <td>{{ $asset->category->name }} <small class="text-muted d-block">{{ $asset->location }}</small></td>
                            <td>{{ $asset->condition }}</td>
                            <td><span class="badge badge-{{$badgeClass}}">{{ $status }}</span></td>
                            <td>
                                @if($audit->status == 'In Progress')
                                <div class="btn-group">
                                    <form action="{{ route('admin.audits.items.store', $audit) }}" method="POST" class="d-inline"> @csrf <input type="hidden" name="asset_id" value="{{ $asset->id }}"><input type="hidden" name="status" value="Found"><button type="submit" class="btn btn-success btn-sm" title="Mark as Found"><i class="fas fa-check"></i></button></form>
                                    <form action="{{ route('admin.audits.items.store', $audit) }}" method="POST" class="d-inline"> @csrf <input type="hidden" name="asset_id" value="{{ $asset->id }}"><input type="hidden" name="status" value="Damaged"><button type="submit" class="btn btn-warning btn-sm" title="Mark as Damaged"><i class="fas fa-exclamation-triangle"></i></button></form>
                                    <form action="{{ route('admin.audits.items.store', $audit) }}" method="POST" class="d-inline"> @csrf <input type="hidden" name="asset_id" value="{{ $asset->id }}"><input type="hidden" name="status" value="Missing"><button type="submit" class="btn btn-danger btn-sm" title="Mark as Missing"><i class="fas fa-times"></i></button></form>
                                </div>
                                @else
                                -
                                @endif
                            </td>
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