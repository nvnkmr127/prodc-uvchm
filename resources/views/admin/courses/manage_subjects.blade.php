@extends('layouts.theme')
@section('title', 'Manage Course Subjects')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-book-open me-2"></i>Manage Subjects for: <strong class="text-primary">{{ $course->name }}</strong>
    </h1>
    <div>
        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Courses
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

{{-- Course Information Card --}}
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-info-circle me-2"></i>Course Information
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Course Name:</strong> {{ $course->name }}</p>
                <p><strong>Course Code:</strong> {{ $course->code ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Duration:</strong> {{ $course->duration ?? 'N/A' }}</p>
                <p><strong>Currently Assigned Subjects:</strong> 
                    <span class="badge badge-primary">{{ $course->subjects->count() }}</span>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Subject Assignment Form --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-success">
            <i class="fas fa-tasks me-2"></i>Subject Assignment
        </h6>
        <div>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAll()">
                <i class="fas fa-check-double"></i> Select All
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                <i class="fas fa-times"></i> Deselect All
            </button>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.courses.subjects.update', $course) }}" method="POST" id="subjectForm">
            @csrf
            @method('PUT')
            
            <div class="form-group mb-4">
                <label class="form-label text-gray-800 font-weight-bold mb-3">
                    <i class="fas fa-list-check me-2"></i>Select subjects to assign to this course:
                </label>
                
                {{-- Search and Filter --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input type="text" id="searchSubjects" class="form-control" placeholder="Search subjects...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select id="filterType" class="form-control">
                            <option value="">All Subjects</option>
                            <option value="lab">Lab Subjects Only</option>
                            <option value="theory">Theory Subjects Only</option>
                            <option value="assigned">Currently Assigned</option>
                            <option value="unassigned">Not Assigned</option>
                        </select>
                    </div>
                </div>

                {{-- Subject Selection Grid --}}
                <div class="row" id="subjectsGrid">
                    @foreach ($allSubjects as $subject)
                        <div class="col-md-4 col-lg-3 mb-3 subject-item" 
                             data-name="{{ strtolower($subject->name) }}" 
                             data-type="{{ $subject->requires_lab ? 'lab' : 'theory' }}"
                             data-assigned="{{ $course->subjects->contains($subject) ? 'true' : 'false' }}">
                            <div class="card h-100 subject-card {{ $course->subjects->contains($subject) ? 'border-success' : 'border-light' }}">
                                <div class="card-body p-3">
                                    <div class="form-check">
                                        <input class="form-check-input subject-checkbox" 
                                               type="checkbox" 
                                               name="subjects[]" 
                                               value="{{ $subject->id }}" 
                                               id="subject{{ $subject->id }}"
                                               {{ $course->subjects->contains($subject) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="subject{{ $subject->id }}">
                                            <strong>{{ $subject->name }}</strong>
                                            @if($subject->code)
                                                <small class="d-block text-muted">{{ $subject->code }}</small>
                                            @endif
                                        </label>
                                    </div>
                                    
                                    {{-- Subject Type Badge --}}
                                    <div class="mt-2">
                                        @if($subject->requires_lab)
                                            <span class="badge badge-info">
                                                <i class="fas fa-flask"></i> Lab Required
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-book"></i> Theory
                                            </span>
                                        @endif
                                    </div>
                                    
                                    {{-- Additional Info --}}
                                    @if($subject->description)
                                        <small class="text-muted d-block mt-2">
                                            {{ Str::limit($subject->description, 50) }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- No subjects found message --}}
                <div id="noSubjectsFound" class="text-center text-muted" style="display: none;">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <p>No subjects found matching your criteria.</p>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
                
                {{-- Show preview button --}}
                <button type="button" class="btn btn-info btn-lg ms-2" data-toggle="modal" data-target="#previewModal">
                    <i class="fas fa-eye me-2"></i>Preview Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Assignment Summary Card --}}
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-chart-bar me-2"></i>Assignment Summary
        </h6>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="p-3">
                    <h4 class="text-primary" id="totalSubjects">{{ $allSubjects->count() }}</h4>
                    <small class="text-muted">Total Subjects</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <h4 class="text-success" id="assignedCount">{{ $course->subjects->count() }}</h4>
                    <small class="text-muted">Currently Assigned</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <h4 class="text-info" id="labSubjects">{{ $allSubjects->where('requires_lab', true)->count() }}</h4>
                    <small class="text-muted">Lab Subjects</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <h4 class="text-secondary" id="theorySubjects">{{ $allSubjects->where('requires_lab', false)->count() }}</h4>
                    <small class="text-muted">Theory Subjects</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Preview Subject Assignment Changes
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">
                            <i class="fas fa-plus-circle me-1"></i>Subjects to be Added:
                        </h6>
                        <ul id="addedSubjects" class="list-unstyled"></ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger">
                            <i class="fas fa-minus-circle me-1"></i>Subjects to be Removed:
                        </h6>
                        <ul id="removedSubjects" class="list-unstyled"></ul>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> These changes will take effect immediately after saving.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('subjectForm').submit();">
                    <i class="fas fa-save me-2"></i>Confirm & Save
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectCheckboxes = document.querySelectorAll('.subject-checkbox');
    const searchInput = document.getElementById('searchSubjects');
    const filterSelect = document.getElementById('filterType');
    const subjectsGrid = document.getElementById('subjectsGrid');
    const noSubjectsFound = document.getElementById('noSubjectsFound');
    const assignedCountEl = document.getElementById('assignedCount');
    
    // Store original assignments for comparison
    const originalAssignments = Array.from(subjectCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => ({ id: cb.value, name: cb.closest('.subject-card').querySelector('strong').textContent }));

    // Update assignment count
    function updateAssignedCount() {
        const checkedCount = document.querySelectorAll('.subject-checkbox:checked').length;
        assignedCountEl.textContent = checkedCount;
    }

    // Handle checkbox changes
    subjectCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.subject-card');
            if (this.checked) {
                card.classList.remove('border-light');
                card.classList.add('border-success');
            } else {
                card.classList.remove('border-success');
                card.classList.add('border-light');
            }
            updateAssignedCount();
        });
    });

    // Search functionality
    searchInput.addEventListener('input', function() {
        filterSubjects();
    });

    // Filter functionality
    filterSelect.addEventListener('change', function() {
        filterSubjects();
    });

    function filterSubjects() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterType = filterSelect.value;
        const subjectItems = document.querySelectorAll('.subject-item');
        let visibleCount = 0;

        subjectItems.forEach(item => {
            const name = item.dataset.name;
            const type = item.dataset.type;
            const assigned = item.dataset.assigned === 'true';
            const checkbox = item.querySelector('.subject-checkbox');
            const currentlyChecked = checkbox.checked;

            let matchesSearch = name.includes(searchTerm);
            let matchesFilter = true;

            if (filterType === 'lab') {
                matchesFilter = type === 'lab';
            } else if (filterType === 'theory') {
                matchesFilter = type === 'theory';
            } else if (filterType === 'assigned') {
                matchesFilter = currentlyChecked;
            } else if (filterType === 'unassigned') {
                matchesFilter = !currentlyChecked;
            }

            if (matchesSearch && matchesFilter) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0) {
            noSubjectsFound.style.display = 'block';
            subjectsGrid.style.display = 'none';
        } else {
            noSubjectsFound.style.display = 'none';
            subjectsGrid.style.display = 'flex';
        }
    }

    // Preview modal functionality
    document.querySelector('[data-target="#previewModal"]').addEventListener('click', function() {
        const currentAssignments = Array.from(subjectCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => ({ 
                id: cb.value, 
                name: cb.closest('.subject-card').querySelector('strong').textContent 
            }));

        const originalIds = originalAssignments.map(a => a.id);
        const currentIds = currentAssignments.map(a => a.id);

        const added = currentAssignments.filter(a => !originalIds.includes(a.id));
        const removed = originalAssignments.filter(a => !currentIds.includes(a.id));

        // Update modal content
        const addedList = document.getElementById('addedSubjects');
        const removedList = document.getElementById('removedSubjects');

        addedList.innerHTML = added.length > 0 
            ? added.map(s => `<li class="text-success"><i class="fas fa-plus me-2"></i>${s.name}</li>`).join('')
            : '<li class="text-muted">No subjects to add</li>';

        removedList.innerHTML = removed.length > 0
            ? removed.map(s => `<li class="text-danger"><i class="fas fa-minus me-2"></i>${s.name}</li>`).join('')
            : '<li class="text-muted">No subjects to remove</li>';
    });

    // Form submission with loading state
    const form = document.getElementById('subjectForm');
    const saveBtn = document.getElementById('saveBtn');

    form.addEventListener('submit', function(e) {
        // Show loading state
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving Changes...';
        saveBtn.disabled = true;
    });
});

// Global functions for select/deselect all
function selectAll() {
    const checkboxes = document.querySelectorAll('.subject-checkbox:not(:checked)');
    checkboxes.forEach(cb => {
        cb.checked = true;
        cb.dispatchEvent(new Event('change'));
    });
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.subject-checkbox:checked');
    checkboxes.forEach(cb => {
        cb.checked = false;
        cb.dispatchEvent(new Event('change'));
    });
}

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
.subject-card {
    transition: all 0.2s ease;
    cursor: pointer;
    border-width: 2px !important;
}

.subject-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.subject-card.border-success {
    border-color: #28a745 !important;
    background-color: #f8fff9;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.badge {
    font-size: 0.75em;
}

.badge-info {
    background-color: #17a2b8;
}

.badge-secondary {
    background-color: #6c757d;
}

.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
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

.input-group-text {
    background-color: #f8f9fc;
    border-color: #d1d3e2;
}

.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

#subjectsGrid {
    max-height: 600px;
    overflow-y: auto;
}

.position-fixed {
    position: fixed !important;
}

/* Custom scrollbar for subjects grid */
#subjectsGrid::-webkit-scrollbar {
    width: 6px;
}

#subjectsGrid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#subjectsGrid::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

#subjectsGrid::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.modal-content {
    border-radius: 10px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header {
    border-radius: 10px 10px 0 0;
    border-bottom: none;
}
</style>
@endpush