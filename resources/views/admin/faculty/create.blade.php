@extends('layouts.theme')
@section('title', 'Add New Faculty')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Add New Faculty Member</h1>
    <a href="{{ route('admin.faculty.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Faculty
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> Please check the errors below.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Faculty Information</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.faculty.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="name" class="form-label font-weight-bold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" required value="{{ old('name') }}" placeholder="Enter full name">
                </div>
            </div>

            <hr class="my-4">
            <h5 class="h6 font-weight-bold text-gray-800 mb-3">Profile Details (Optional)</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="employee_id" class="form-label font-weight-bold">Employee ID</label>
                    <input type="text" class="form-control" name="employee_id" id="employee_id" value="{{ old('employee_id') }}" placeholder="e.g. EMP-101">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="department" class="form-label font-weight-bold">Department</label>
                    <input type="text" class="form-control" name="department" id="department" value="{{ old('department') }}" placeholder="e.g. Computer Science">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label font-weight-bold">Phone Number</label>
                    <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone') }}" placeholder="e.g. +1234567890">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="biometric_employee_code" class="form-label font-weight-bold">Biometric ID / Employee Code</label>
                    <input type="text" class="form-control" name="biometric_employee_code" id="biometric_employee_code" value="{{ old('biometric_employee_code') }}" placeholder="e.g. 9101 (Leave empty to auto-generate)">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary shadow-sm">
                    <i class="fas fa-save mr-1"></i> Save Faculty Member
                </button>
                <a href="{{ route('admin.faculty.index') }}" class="btn btn-secondary shadow-sm">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection