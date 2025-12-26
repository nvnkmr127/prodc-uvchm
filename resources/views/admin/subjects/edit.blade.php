@extends('layouts.theme')
@section('title', 'Edit Subject')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-book me-2"></i>Edit Subject
    </h1>
    <div>
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Subjects
        </a>
    </div>
</div>

{{-- Display validation errors --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- Display success message --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-edit me-2"></i>Subject Information
        </h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.subjects.update', $subject) }}" method="POST" id="subjectForm">
            @csrf
            @method('PATCH')
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-book me-1"></i>Subject Name *
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name"
                               class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $subject->name) }}" 
                               required
                               placeholder="Enter subject name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label for="code" class="form-label">
                            <i class="fas fa-code me-1"></i>Subject Code
                        </label>
                        <input type="text" 
                               name="code" 
                               id="code"
                               class="form-control @error('code') is-invalid @enderror" 
                               value="{{ old('code', $subject->code) }}"
                               placeholder="e.g., CS101">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Optional unique identifier</small>
                    </div>
                </div>
            </div>

            {{-- Lab Requirements Section --}}
            <div class="card border-info mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-flask me-2"></i>Lab Requirements
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="requires_lab" 
                               value="1" 
                               id="requires_lab"
                               {{ old('requires_lab', $subject->requires_lab) ? 'checked' : '' }}>
                        <label class="form-check-label" for="requires_lab">
                            <strong>This subject requires dedicated lab sessions</strong>
                        </label>
                    </div>
                    
                    {{-- Additional lab fields (shown when lab is required) --}}
                    <div id="labDetails" class="mt-3" style="{{ old('requires_lab', $subject->requires_lab) ? 'display: block;' : 'display: none;' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="lab_hours" class="form-label">Lab Hours per Week</label>
                                    <input type="number" 
                                           name="lab_hours" 
                                           id="lab_hours"
                                           class="form-control @error('lab_hours') is-invalid @enderror" 
                                           value="{{ old('lab_hours', $subject->lab_hours ?? '') }}"
                                           min="1" 
                                           max="10"
                                           placeholder="e.g., 2">
                                    @error('lab_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="theory_hours" class="form-label">Theory Hours per Week</label>
                                    <input type="number" 
                                           name="theory_hours" 
                                           id="theory_hours"
                                           class="form-control @error('theory_hours') is-invalid @enderror" 
                                           value="{{ old('theory_hours', $subject->theory_hours ?? '') }}"
                                           min="1" 
                                           max="10"
                                           placeholder="e.g., 3">
                                    @error('theory_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Lab subjects will require practical groups to be created for each batch. 
                            Make sure appropriate lab classrooms are available before scheduling.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Additional Information Section --}}
            <div class="form-group mb-3">
                <label for="description" class="form-label">
                    <i class="fas fa-align-left me-1"></i>Description
                </label>
                <textarea name="description" 
                          id="description"
                          class="form-control @error('description') is-invalid @enderror" 
                          rows="3"
                          placeholder="Brief description of the subject (optional)">{{ old('description', $subject->description ?? '') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Subject Status --}}
            <div class="form-check mb-3">
                <input class="form-check-input" 
                       type="checkbox" 
                       name="is_active" 
                       value="1" 
                       id="is_active"
                       {{ old('is_active', $subject->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    <strong>Subject is active</strong>
                    <small class="d-block text-muted">Inactive subjects won't appear in course assignments</small>
                </label>
            </div>

            {{-- Action Buttons --}}
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save me-2"></i>Update Subject
                </button>
                <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
                
                {{-- Show additional actions if subject is used --}}
                @if($subject->courses->count() > 0 || $subject->faculty->count() > 0)
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>More Actions
                        </button>
                        <div class="dropdown-menu">
                            @if($subject->courses->count() > 0)
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#coursesModal">
                                    <i class="fas fa-graduation-cap me-2"></i>View Assigned Courses ({{ $subject->courses->count() }})
                                </a>
                            @endif
                            @if($subject->faculty->count() > 0)
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#facultyModal">
                                    <i class="fas fa-users me-2"></i>View Assigned Faculty ({{ $subject->faculty->count() }})
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Subject Usage Information --}}
@if($subject->courses->count() > 0 || $subject->faculty->count() > 0)
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-info">
            <i class="fas fa-info-circle me-2"></i>Subject Usage Information
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Assigned to Courses</h6>
                @if($subject->courses->count() > 0)
                    <ul class="list-unstyled">
                        @foreach($subject->courses as $course)
                            <li class="mb-1">
                                <i class="fas fa-graduation-cap text-primary me-2"></i>
                                {{ $course->name }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">Not assigned to any courses</p>
                @endif
            </div>
            
            <div class="col-md-6">
                <h6 class="text-success">Assigned Faculty</h6>
                @if($subject->faculty->count() > 0)
                    <ul class="list-unstyled">
                        @foreach($subject->faculty as $faculty)
                            <li class="mb-1">
                                <i class="fas fa-user text-success me-2"></i>
                                {{ $faculty->name }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">No faculty assigned</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle lab details based on requires_lab checkbox
    const requiresLabCheckbox = document.getElementById('requires_lab');
    const labDetails = document.getElementById('labDetails');
    
    requiresLabCheckbox.addEventListener('change', function() {
        if (this.checked) {
            labDetails.style.display = 'block';
            // Make lab hours required when lab is selected
            document.getElementById('lab_hours').setAttribute('required', 'required');
        } else {
            labDetails.style.display = 'none';
            // Remove required attribute when lab is not selected
            document.getElementById('lab_hours').removeAttribute('required');
            // Clear lab-related fields
            document.getElementById('lab_hours').value = '';
            document.getElementById('theory_hours').value = '';
        }
    });
    
    // Form validation
    const form = document.getElementById('subjectForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Check subject name
        const nameField = document.getElementById('name');
        if (!nameField.value.trim()) {
            isValid = false;
            nameField.classList.add('is-invalid');
        } else {
            nameField.classList.remove('is-invalid');
        }
        
        // Check lab hours if lab is required
        if (requiresLabCheckbox.checked) {
            const labHoursField = document.getElementById('lab_hours');
            if (!labHoursField.value || labHoursField.value < 1) {
                isValid = false;
                labHoursField.classList.add('is-invalid');
                
                // Show error message
                let errorDiv = labHoursField.nextElementSibling;
                if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    labHoursField.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = 'Lab hours are required when lab is selected';
            } else {
                labHoursField.classList.remove('is-invalid');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Please fix the errors in the form', 'error');
            return false;
        }
        
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        submitBtn.disabled = true;
    });
    
    // Auto-generate subject code based on name (optional)
    const nameField = document.getElementById('name');
    const codeField = document.getElementById('code');
    
    nameField.addEventListener('blur', function() {
        if (!codeField.value && this.value) {
            // Generate a simple code from the name
            let code = this.value.trim()
                .toUpperCase()
                .replace(/[^A-Z0-9\s]/g, '')
                .split(' ')
                .map(word => word.substring(0, 3))
                .join('');
            
            if (code.length > 6) {
                code = code.substring(0, 6);
            }
            
            codeField.value = code;
        }
    });
});

function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 
                      type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', notification);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            alerts[alerts.length - 1].style.display = 'none';
        }
    }, 5000);
}
</script>
@endpush

@push('styles')
<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-check-input:checked {
    background-color: #4e73df;
    border-color: #4e73df;
}

.border-info {
    border-color: #36b9cc !important;
}

.bg-info {
    background: linear-gradient(135deg, #36b9cc, #258391) !important;
}

#labDetails {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        overflow: hidden;
    }
    to {
        opacity: 1;
        max-height: 500px;
    }
}

.position-fixed {
    position: fixed !important;
}
</style>
@endpush