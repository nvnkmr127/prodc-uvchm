@extends('layouts.theme')
@section('title', 'Fee Structures')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Fee Structures</h1>
    <a href="{{ route('admin.fee-structures.create') }}" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Fee Structure
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Fee Structures</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Batch Name</th>
                        <th>Course</th>
                        <th class="text-right">Total Amount</th>
                        <th class="text-center">Components</th>
                        <th class="text-center">Payment Terms</th> {{-- REVISED: New Column --}}
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeStructures as $structure)
                    <tr>
                        @if($structure->batch)
                            <td>
                                <a href="{{ route('admin.fee-structures.show', $structure) }}">
                                    {{ $structure->batch->name }}
                                </a>
                            </td>
                            <td>{{ $structure->batch->course->name ?? 'N/A' }}</td>
                            <td class="text-right font-weight-bold">₹{{ number_format($structure->total_amount, 2) }}</td>
                            <td class="text-center">{{ $structure->feeCategories->count() }}</td>
                            {{-- REVISED: Display the payment terms --}}
                            <td class="text-center">{{ $structure->payment_terms }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.fee-structures.show', $structure) }}" class="btn btn-info btn-circle btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.fee-structures.edit', $structure) }}" class="btn btn-warning btn-circle btn-sm" title="Edit Structure">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.fee-structures.destroy', $structure) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this fee structure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-circle btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        @else
                            <td colspan="5">
                                <span class="text-danger">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Invalid Record: Associated batch has been deleted.
                                </span>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('admin.fee-structures.destroy', $structure) }}" method="POST" class="d-inline" onsubmit="return confirm('This is an invalid record. Are you sure you want to permanently delete it?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-circle btn-sm" title="Delete Invalid Record">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">
                            <p class="lead my-3">No fee structures found.</p>
                            <a href="{{ route('admin.fee-structures.create') }}" class="btn btn-primary">Add New Fee Structure</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
