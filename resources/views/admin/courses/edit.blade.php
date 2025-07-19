@extends('layouts.theme')
@section('title', 'Edit Course')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Course: {{ $course->name }}</h1>
    <a href="{{ route('admin.courses.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Courses
    </a>
</div>

{{-- Display validation errors --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Course Details</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.courses.update', $course) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group mb-3">
                        <label for="name">Course Name*</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $course->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="enrollment_prefix">Enrollment Prefix</label>
                        <input type="text" name="enrollment_prefix" class="form-control @error('enrollment_prefix') is-invalid @enderror" 
                               value="{{ old('enrollment_prefix', $course->enrollment_prefix) }}" maxlength="10">
                        <small class="form-text text-muted">Used in enrollment numbers (e.g., ADHM)</small>
                        @error('enrollment_prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="code">Course Code</label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" 
                               value="{{ old('code', $course->code) }}" maxlength="50">
                        <small class="form-text text-muted">Short code for this course (e.g., DHM)</small>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="duration_in_years">Duration (Years)*</label>
                        <input type="number" step="0.5" name="duration_in_years" class="form-control @error('duration_in_years') is-invalid @enderror" 
                               value="{{ old('duration_in_years', $course->duration_in_years) }}" required>
                        @error('duration_in_years')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="duration_months">Duration (Months)*</label>
                        <input type="number" name="duration_months" class="form-control @error('duration_months') is-invalid @enderror" 
                               value="{{ old('duration_months', $course->duration_months) }}" required>
                        @error('duration_months')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="max_batch_size">Max Batch Size*</label>
                        <input type="number" name="max_batch_size" class="form-control @error('max_batch_size') is-invalid @enderror" 
                               value="{{ old('max_batch_size', $course->max_batch_size ?? 30) }}" required>
                        <small class="form-text text-muted">Maximum students per lab group</small>
                        @error('max_batch_size')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group mb-3">
                        <label for="description">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" 
                                  placeholder="Enter a brief description of the course...">{{ old('description', $course->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Course</button>
            <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection