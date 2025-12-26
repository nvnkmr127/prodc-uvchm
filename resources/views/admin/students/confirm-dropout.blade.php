@extends('layouts.theme')

@section('title', 'Confirm Student Dropout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        Confirm Student Dropout
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong>Important:</strong> This action will mark the student as dropout and exclude them from all active operations while preserving all payment records.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Student Information</h5>
                            <table class="table table-bordered">
                                <tr><th>Name:</th><td>{{ $student->name }}</td></tr>
                                <tr><th>Enrollment Number:</th><td>{{ $student->enrollment_number }}</td></tr>
                                <tr><th>Course:</th><td>{{ $student->course->name ?? 'N/A' }}</td></tr>
                                <tr><th>Batch:</th><td>{{ $student->batch->name ?? 'N/A' }}</td></tr>
                                <tr><th>Admission Date:</th><td>{{ $student->admission_date->format('d M Y') }}</td></tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Financial Summary</h5>
                            <table class="table table-bordered">
                                <tr><th>Total Fees:</th><td>₹{{ number_format($financialSummary['total_fees'], 2) }}</td></tr>
                                <tr><th>Amount Paid:</th><td class="text-success">₹{{ number_format($financialSummary['total_paid'], 2) }}</td></tr>
                                <tr><th>Outstanding:</th><td class="text-danger">₹{{ number_format($financialSummary['total_outstanding'], 2) }}</td></tr>
                                <tr><th>Concessions:</th><td class="text-info">₹{{ number_format($financialSummary['total_concession'] ?? 0, 2) }}</td></tr>
                            </table>
                            
                            <div class="alert alert-info">
                                <strong>Payment Preservation:</strong> All {{ $student->payments->count() }} payment records will be preserved and remain accessible for future reference.
                            </div>
                        </div>
                    </div>
                    
                    <form action="{{ route('admin.students.process-dropout', $student) }}" method="POST" onsubmit="return confirm('Are you absolutely sure you want to mark this student as dropout? This action affects reporting and calculations.')">
                        @csrf
                        
                        <div class="form-group">
                            <label for="dropout_date">Dropout Date</label>
                            <input type="date" class="form-control" name="dropout_date" id="dropout_date" 
                                   value="{{ old('dropout_date', today()->format('Y-m-d')) }}" 
                                   max="{{ today()->format('Y-m-d') }}" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="reason">Dropout Reason</label>
                            <textarea class="form-control" name="reason" id="reason" rows="3" 
                                      placeholder="Please provide the reason for dropout..." required>{{ old('reason') }}</textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="confirm_preservation" id="confirm_preservation" value="1" required>
                            <label class="form-check-label" for="confirm_preservation">
                                I understand that this student will be excluded from active operations but all payment records will be preserved.
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-user-times"></i> Confirm Dropout
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection