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
            <thead>
                <tr>
                    <th>For Month/Year</th>
                    <th>Faculty Name</th>
                    <th>Attendance Details</th>
                    <th>Salary Multiplier</th>
                    <th>Net Salary</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payslips as $payslip)
                    <tr>
                        <td>{{ $payslip->month }} {{ $payslip->year }}</td>
                        <td>{{ $payslip->user->name }}</td>
                        <td>
                            @if(isset($payslip->working_days))
                                <span class="font-weight-bold text-gray-800">{{ $payslip->days_present }}</span> / {{ $payslip->working_days }} Present Days
                                @if($payslip->leave_days > 0)
                                    <span class="badge badge-info ml-1" title="Approved Leaves">+{{ $payslip->leave_days }} Approved Leaves</span>
                                @endif
                            @else
                                <span class="text-muted">N/A (Default 100%)</span>
                            @endif
                        </td>
                        <td>
                            @if(isset($payslip->payment_multiplier))
                                <span class="font-weight-bold text-primary">{{ number_format($payslip->payment_multiplier * 100, 1) }}%</span>
                            @else
                                <span class="text-muted">100.0%</span>
                            @endif
                        </td>
                        <td>{{ number_format($payslip->net_salary, 2) }}</td>
                        <td><span class="badge badge-success">{{ $payslip->status }}</span></td>
                        <td><a href="{{ route('admin.payslips.show', $payslip) }}" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-eye fa-sm mr-1"></i> View Payslip</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No payslips generated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection