@extends('layouts.theme')
@section('title', 'Manage Course Structure')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        Manage Structure for: <strong class="text-primary">{{ $course->name }}</strong>
    </h1>
    <a href="{{ route('admin.courses.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Courses
    </a>
</div>

{{-- Display Success/Error Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- Course Overview --}}
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-left-primary shadow">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Course Information</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $course->name }}</div>
                        @if($course->description)
                            <div class="text-sm text-gray-600 mt-1">{{ $course->description }}</div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-info shadow">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Structure Stats</div>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $course->terms->count() }}</div>
                                <div class="text-xs text-muted">Terms</div>
                            </div>
                            <div class="col-6">
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $course->batches->count() }}</div>
                                <div class="text-xs text-muted">Batches</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-sitemap fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- List of existing terms --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list-ol me-2"></i>Defined Terms
        </h6>
        @if($course->terms->count() > 0)
            <span class="badge badge-primary">{{ $course->terms->count() }} term{{ $course->terms->count() !== 1 ? 's' : '' }}</span>
        @endif
    </div>
    <div class="card-body">
        @if($course->terms->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 15%;">Sequence</th>
                            <th style="width: 45%;">Term Name</th>
                            <th style="width: 20%;">Type</th>
                            <th style="width: 20%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($course->terms->sortBy('sequence') as $term)
                        <tr>
                            <td class="text-center">
                                <span class="badge badge-secondary">{{ $term->sequence }}</span>
                            </td>
                            <td>
                                <strong>{{ $term->name }}</strong>
                            </td>
                            <td>
                                <span class="badge badge-{{ $term->type == 'Academic' ? 'info' : 'success' }}">
                                    <i class="fas {{ $term->type == 'Academic' ? 'fa-graduation-cap' : 'fa-briefcase' }} me-1"></i>
                                    {{ $term->type }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    {{-- Edit Button (if you want to add edit functionality later) --}}
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            title="Edit Term" onclick="editTerm({{ $term->id }}, '{{ $term->name }}', '{{ $term->type }}', {{ $term->sequence }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    {{-- Delete Button - FIXED ROUTE --}}
                                    <form action="{{ route('admin.courses.structure.destroy', $term) }}" method="POST" class="d-inline">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                title="Delete Term"
                                                onclick="return confirm('Are you sure you want to delete the term \'{{ $term->name }}\'? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-sitemap fa-3x text-gray-300 mb-3"></i>
                <h5 class="text-gray-600">No terms defined yet</h5>
                <p class="text-muted">Add your first term using the form below to get started.</p>
            </div>
        @endif
    </div>
</div>

{{-- Form to add a new term --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-plus me-2"></i>Add New Term to Structure
        </h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.courses.structure.store', $course) }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label for="name" class="form-label font-weight-bold">Term Name *</label>
                    <input type="text" 
                           name="name" 
                           id="name"
                           class="form-control @error('name') is-invalid @enderror" 
                           placeholder="e.g., Semester 1, Industrial Training" 
                           value="{{ old('name') }}"
                           required>
                    <small class="form-text text-muted">
                        Enter a descriptive name for this academic term
                    </small>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="type" class="form-label font-weight-bold">Type *</label>
                    <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                        <option value="Academic" {{ old('type') == 'Academic' ? 'selected' : '' }}>
                            <i class="fas fa-graduation-cap"></i> Academic (College)
                        </option>
                        <option value="Training" {{ old('type') == 'Training' ? 'selected' : '' }}>
                            <i class="fas fa-briefcase"></i> Training (Internship)
                        </option>
                    </select>
                    <small class="form-text text-muted">
                        Academic for regular semesters, Training for internships
                    </small>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="sequence" class="form-label font-weight-bold">Sequence *</label>
                    <input type="number" 
                           name="sequence" 
                           id="sequence"
                           class="form-control @error('sequence') is-invalid @enderror" 
                           value="{{ old('sequence', $course->terms->count() + 1) }}" 
                           min="1"
                           required>
                    <small class="form-text text-muted">
                        Order of this term (1, 2, 3...)
                    </small>
                    @error('sequence')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Term
                    </button>
                    <button type="reset" class="btn btn-outline-secondary ml-2">
                        <i class="fas fa-undo me-2"></i>Reset
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Quick Setup Guide --}}
@if($course->terms->count() == 0)
<div class="card shadow border-left-warning">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h6 class="font-weight-bold text-warning">
                    <i class="fas fa-lightbulb me-2"></i>Quick Setup Guide
                </h6>
                <p class="text-muted mb-0">
                    Course terms define the academic structure. For most programs, you'll want to create terms like:
                </p>
                <ul class="text-muted mt-2 mb-0">
                    <li><strong>Academic:</strong> Semester 1, Semester 2, etc.</li>
                    <li><strong>Training:</strong> Industrial Training, Internship, etc.</li>
                </ul>
            </div>
            <div class="col-md-4 text-center">
                <i class="fas fa-rocket fa-2x text-warning"></i>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

{{-- Add Edit Modal (for future enhancement) --}}
@push('scripts')
<script>
function editTerm(id, name, type, sequence) {
    // For now, just show an alert. You can implement a modal later
    alert('Edit functionality will be added in a future update.\n\nTerm: ' + name + '\nType: ' + type + '\nSequence: ' + sequence);
}

// Auto-increment sequence when adding new term
document.addEventListener('DOMContentLoaded', function() {
    const sequenceInput = document.getElementById('sequence');
    const form = sequenceInput.closest('form');
    
    form.addEventListener('reset', function() {
        setTimeout(() => {
            sequenceInput.value = {{ $course->terms->count() + 1 }};
        }, 10);
    });
});
</script>
@endpush

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.075);
}

.btn-group .btn {
    border-radius: 0.35rem;
}

.btn-group .btn:not(:last-child) {
    margin-right: 2px;
}

.card:hover {
    transform: translateY(-1px);
    transition: all 0.3s ease;
}

.form-label {
    color: #5a5c69;
    margin-bottom: 0.5rem;
}

.badge {
    font-size: 0.875em;
}

.alert {
    border-radius: 0.5rem;
}
</style>
@endpush