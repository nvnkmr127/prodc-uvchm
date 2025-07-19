@extends('layouts.theme')
@section('title', 'Inventory Audits')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Inventory Audits</h1>
    {{-- This form is just a button that starts a new audit --}}
    <form action="{{ route('admin.audits.store') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Start New Audit</button>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Audit History</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Audit ID</th>
                        <th>Date Started</th>
                        <th>Audited By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audits as $audit)
                        <tr>
                            <td>#{{ $audit->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($audit->audit_date)->format('d M, Y') }}</td>
                            <td>{{ $audit->user->name ?? 'N/A' }}</td>
                            <td>
                                @php
                                    $badgeClass = $audit->status == 'Completed' ? 'success' : 'warning';
                                @endphp
                                <span class="badge badge-{{$badgeClass}}">{{ $audit->status }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.audits.show', $audit) }}" class="btn btn-info btn-sm">
                                    {{ $audit->status == 'Completed' ? 'View Report' : 'Continue Audit' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No audits have been performed yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection