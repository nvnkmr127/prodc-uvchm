{{-- resources/views/admin/students/import.blade.php --}}
@extends('layouts.theme')
@section('title', 'Import Students')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Bulk Import Students from Excel/CSV</h1>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {!! nl2br(e(session('success'))) !!}
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('import_errors'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Import Errors:</strong>
        <ul class="mt-2 mb-0">
            @foreach(session('import_errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Validation Errors:</strong>
        <ul class="mt-2 mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Upload Students File</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.students.import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    
                    <div class="form-group mb-3">
                        <label for="batch_id" class="form-label">
                            Select Batch <span class="text-danger">*</span>
                        </label>
                        <select name="batch_id" id="batch_id" class="form-control @error('batch_id') is-invalid @enderror" required>
                            <option value="">-- Select Batch --</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ old('batch_id') == $batch->id ? 'selected' : '' }}>
                                    {{ $batch->name }} ({{ $batch->course->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('batch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="import_file" class="form-label">
                            Select Excel/CSV File <span class="text-danger">*</span>
                        </label>
                        <input type="file" 
                               name="import_file" 
                               id="import_file" 
                               class="form-control @error('import_file') is-invalid @enderror" 
                               accept=".xlsx,.xls,.csv" 
                               required>
                        <small class="form-text text-muted">
                            Supported formats: .xlsx, .xls, .csv (Max size: 2MB)
                        </small>
                        @error('import_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-group mb-3">
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                <i class="fas fa-upload mr-2"></i>Import Students
                            </button>
                            <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Students
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-info-circle mr-2"></i>Instructions
                </h6>
            </div>
            <div class="card-body">
                <ol class="mb-3">
                    <li>Download the sample template below</li>
                    <li>Fill in your student data following the exact column structure</li>
                    <li>Select the target batch for all imported students</li>
                    <li>Upload your completed file</li>
                </ol>
                
                <div class="text-center">
                    <a href="{{ route('admin.students.import.sample') }}" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-download mr-1"></i>Download Sample Template
                    </a>
                </div>
                
                <hr>
                
                <h6 class="text-muted">Required Columns:</h6>
                <ul class="list-unstyled small">
                    <li><code>full_name</code> - Student's full name</li>
                    <li><code>gender</code> - Male/Female/Other</li>
                    <li><code>admission_date</code> - YYYY-MM-DD format</li>
                    <li><code>student_mobile</code> - 10 digit mobile number</li>
                    <li><code>father_mobile</code> - 10 digit mobile number</li>
                    <li><code>source</code> - Lead source (optional)</li>
                    <li><code>referral_name</code> - Referrer name (optional)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importForm');
    const submitBtn = document.getElementById('submitBtn');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
    });
    
    // Re-enable form if there are validation errors
    @if($errors->any() || session('error') || session('import_errors'))
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
        submitBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>Import Students';
    @endif
});
</script>
@endpush
@endsection