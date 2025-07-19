@extends('layouts.theme')
@section('title', 'Salary Structure for ' . $user->name)

@section('content')
<h1 class="h3 mb-4 text-gray-800">Salary Structure for: <strong>{{ $user->name }}</strong></h1>

{{-- List of existing components --}}
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Current Salary Components</h6></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Component</th><th>Type</th><th class="text-right">Amount</th><th>Action</th></tr></thead>
            <tbody>
                @php 
                    $totalEarnings = 0;
                    $totalDeductions = 0;
                @endphp
                @forelse($salaryStructure as $structure)
                    <tr>
                        <td>{{ $structure->salaryComponent->name }}</td>
                        <td><span class="badge badge-{{ $structure->salaryComponent->type == 'Earning' ? 'success' : 'danger' }}">{{ $structure->salaryComponent->type }}</span></td>
                        <td class="text-right">{{ number_format($structure->amount, 2) }}</td>
                        <td>
                            <form action="{{ route('admin.faculty.salary.destroy', $structure) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @php
                        if ($structure->salaryComponent->type == 'Earning') $totalEarnings += $structure->amount;
                        else $totalDeductions += $structure->amount;
                    @endphp
                @empty
                <tr><td colspan="4" class="text-center">No salary components assigned yet.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-active">
                <tr><td colspan="2" class="text-right"><strong>Total Earnings:</strong></td><td colspan="2" class="text-right font-weight-bold">{{ number_format($totalEarnings, 2) }}</td></tr>
                <tr><td colspan="2" class="text-right"><strong>Total Deductions:</strong></td><td colspan="2" class="text-right font-weight-bold text-danger">- {{ number_format($totalDeductions, 2) }}</td></tr>
                <tr class="table-success"><td colspan="2" class="text-right h5"><strong>Net Salary:</strong></td><td colspan="2" class="text-right h5 font-weight-bold">{{ number_format($totalEarnings - $totalDeductions, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Form to add a new component --}}
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Add New Component to Structure</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.faculty.salary.store', $user) }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6"><label>Salary Component</label><select name="salary_component_id" class="form-control" required><option value="">-- Select --</option>@foreach($components as $component)<option value="{{ $component->id }}">{{ $component->name }} ({{$component->type}})</option>@endforeach</select></div>
                <div class="col-md-6"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Component</button>
        </form>
    </div>
</div>
@endsection