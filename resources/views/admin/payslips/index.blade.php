@extends('layouts.theme')
@section('title', 'Generated Payslips')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Generated Payslips</h1>
    <a href="{{ route('admin.payslips.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Generate New Payslips</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>For Month/Year</th><th>Faculty Name</th><th>Net Salary</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse ($payslips as $payslip)
                    <tr>
                        <td>{{ $payslip->month }} {{ $payslip->year }}</td>
                        <td>{{ $payslip->user->name }}</td>
                        <td>{{ number_format($payslip->net_salary, 2) }}</td>
                        <td><span class="badge badge-success">{{ $payslip->status }}</span></td>
                        <td><a href="{{ route('admin.payslips.show', $payslip) }}" target="_blank" class="btn btn-info btn-sm">View Payslip</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No payslips generated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection