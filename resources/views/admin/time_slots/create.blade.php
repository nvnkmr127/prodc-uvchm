@extends('layouts.theme')
@section('title', 'Add Time Slot')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-clock me-2"></i>Add New Time Slot
    </h1>
    <div>
        <a href="{{ route('admin.time-slots.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Time Slots
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
            <i class="fas fa-plus-circle me-2"></i>Time Slot Information
        </h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.time-slots.store') }}" method="POST" id="timeSlotForm">
            @csrf
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-tag me-1"></i>Time Slot Name *
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name"
                               class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name') }}" 
                               placeholder="e.g., Period 1, Morning Session"
                               maxlength="50">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Optional descriptive name for this time slot</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="sort_order" class="form-label">
                            <i class="fas fa-sort-numeric-up me-1"></i>Display Order
                        </label>
                        <input type="number" 
                               name="sort_order" 
                               id="sort_order"
                               class="form-control @error('sort_order') is-invalid @enderror" 
                               value="{{ old('sort_order', 0) }}" 
                               min="0" 
                               max="100">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Lower numbers appear first in listings</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="start_time" class="form-label">
                            <i class="fas fa-play me-1"></i>Start Time *
                        </label>
                        <input type="time" 
                               name="start_time" 
                               id="start_time"
                               class="form-control @error('start_time') is-invalid @enderror" 
                               value="{{ old('start_time') }}" 
                               required>
                        @error('start_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="end_time" class="form-label">
                            <i class="fas fa-stop me-1"></i>End Time *
                        </label>
                        <input type="time" 
                               name="end_time" 
                               id="end_time"
                               class="form-control @error('end_time') is-invalid @enderror" 
                               value="{{ old('end_time') }}" 
                               required>
                        @error('end_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Duration Display --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info" id="durationDisplay" style="display: none;">
                        <i class="fas fa-hourglass-half me-2"></i>
                        <strong>Duration:</strong> <span id="durationText">0 minutes</span>
                    </div>
                </div>
            </div>

            {{-- Quick Time Presets --}}
            <div class="card border-success mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Quick Time Presets
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Click on a preset to quickly set common time slots:</p>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm w-100 time-preset" 
                                    data-start="08:00" data-end="09:00" data-name="Period 1">
                                <i class="fas fa-sun me-1"></i>08:00 - 09:00<br><small>Period 1</small>
                            </button>
                        </div>
                        <div class="col-md-4 mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm w-100 time-preset" 
                                    data-start="09:00" data-end="10:00" data-name="Period 2">
                                <i class="fas fa-sun me-1"></i>09:00 - 10:00<br><small>Period 2</small>
                            </button>
                        </div>
                        <div class="col-md-4 mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm w-100 time-preset" 
                                    data-start="10:00" data-end="11:00" data-name="Period 3">
                                <i class="fas fa-sun me-1"></i>10:00 - 11:00<br><small>Period 3</small>
                            </button>
                        </div>
                        <div class="col-md-4 mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm w-100 time-preset" 
                                    data-start="11:30" data-end="12:30" data-name="Period 4">
                                <i class="fas fa-sun me-1"></i>11:30 - 12:30<br><small>Period 4</small>
                            </button>
                        </div>
                        <div class="col-md-4 mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm w-100 time-preset" 
                                    data-start="13:00" data-end="14:00" data-name="Period 5">
                                <i class="fas fa-sun-o me-1"></i>13:00 - 14:00<br><small>Period 5</small>
                            </button>
                        </div>
                        <div class="col-md-4 mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm w-100 time-preset" 
                                    data-start="14:00" data-end="16:00" data-name="Lab Session">
                                <i class="fas fa-flask me-1"></i>14:00 - 16:00<br><small>Lab Session</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Advanced Options --}}
            <div class="card border-info mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-cog me-2"></i>Advanced Options
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="is_active" 
                                       value="1" 
                                       id="is_active"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <strong>Time slot is active</strong>
                                    <small class="d-block text-muted">Inactive slots won't appear in scheduling</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" 
                                          id="description"
                                          class="form-control @error('description') is-invalid @enderror" 
                                          rows="2"
                                          placeholder="Optional notes about this time slot">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Conflict Detection --}}
            <div class="alert alert-warning" id="conflictWarning" style="display: none;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Potential Conflict:</strong> <span id="conflictMessage"></span>
            </div>

            {{-- Action Buttons --}}
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                    <i class="fas fa-save me-2"></i>Create Time Slot
                </button>
                <a href="{{ route('admin.time-slots.index') }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
                
                {{-- Preview Button --}}
                <button type="button" class="btn btn-info btn-lg ms-2" id="previewBtn">
                    <i class="fas fa-eye me-2"></i>Preview
                </button>
                
                {{-- Add Another Button --}}
                <button type="button" class="btn btn-success btn-lg ms-2" id="addAnotherBtn">
                    <i class="fas fa-plus me-2"></i>Save & Add Another
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Existing Time Slots Preview --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-info">
            <i class="fas fa-list me-2"></i>Existing Time Slots Preview
        </h6>
    </div>
    <div class="card-body">
        <div class="row" id="existingSlots">
            <div class="col-12 text-center text-muted">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <p>Loading existing time slots...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const nameInput = document.getElementById('name');
    const durationDisplay = document.getElementById('durationDisplay');
    const durationText = document.getElementById('durationText');
    const conflictWarning = document.getElementById('conflictWarning');
    const conflictMessage = document.getElementById('conflictMessage');
    const form = document.getElementById('timeSlotForm');
    const saveBtn = document.getElementById('saveBtn');
    
    // Load existing time slots
    loadExistingTimeSlots();
    
    // Time preset buttons
    document.querySelectorAll('.time-preset').forEach(button => {
        button.addEventListener('click', function() {
            const start = this.dataset.start;
            const end = this.dataset.end;
            const name = this.dataset.name;
            
            startTimeInput.value = start;
            endTimeInput.value = end;
            nameInput.value = name;
            
            // Trigger change events
            startTimeInput.dispatchEvent(new Event('change'));
            endTimeInput.dispatchEvent(new Event('change'));
            
            // Visual feedback
            this.classList.add('btn-success');
            this.classList.remove('btn-outline-success');
            setTimeout(() => {
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-success');
            }, 1000);
        });
    });
    
    // Calculate duration and check conflicts
    function updateTimeInfo() {
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;
        
        if (startTime && endTime) {
            const start = new Date(`2000-01-01T${startTime}`);
            const end = new Date(`2000-01-01T${endTime}`);
            
            if (end > start) {
                const diffMs = end - start;
                const diffMins = Math.floor(diffMs / 60000);
                const hours = Math.floor(diffMins / 60);
                const minutes = diffMins % 60;
                
                let durationStr = '';
                if (hours > 0) {
                    durationStr += `${hours} hour${hours > 1 ? 's' : ''}`;
                    if (minutes > 0) {
                        durationStr += ` ${minutes} minute${minutes > 1 ? 's' : ''}`;
                    }
                } else {
                    durationStr = `${minutes} minute${minutes > 1 ? 's' : ''}`;
                }
                
                durationText.textContent = durationStr;
                durationDisplay.style.display = 'block';
                
                // Auto-generate name if empty
                if (!nameInput.value.trim()) {
                    const startFormatted = start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const endFormatted = end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    nameInput.value = `${startFormatted} - ${endFormatted}`;
                }
                
                // Check for conflicts
                checkTimeConflicts(startTime, endTime);
            } else {
                durationDisplay.style.display = 'none';
                conflictWarning.style.display = 'none';
            }
        } else {
            durationDisplay.style.display = 'none';
            conflictWarning.style.display = 'none';
        }
    }
    
    // Check for time conflicts
    function checkTimeConflicts(startTime, endTime) {
        // This would typically make an AJAX call to check existing time slots
        // For now, we'll simulate it
        fetch(`{{ route('admin.time-slots.index') }}?check_conflict=1&start=${startTime}&end=${endTime}`)
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    conflictMessage.textContent = data.message;
                    conflictWarning.style.display = 'block';
                } else {
                    conflictWarning.style.display = 'none';
                }
            })
            .catch(error => {
                console.log('Conflict check not available');
                conflictWarning.style.display = 'none';
            });
    }
    
    // Load existing time slots for preview
    function loadExistingTimeSlots() {
        fetch(`{{ route('admin.time-slots.index') }}?ajax=1`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('existingSlots');
                if (data.timeSlots && data.timeSlots.length > 0) {
                    container.innerHTML = data.timeSlots.map(slot => `
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="card border-left-primary h-100">
                                <div class="card-body p-3">
                                    <h6 class="card-title text-primary">${slot.name || 'Unnamed'}</h6>
                                    <p class="card-text">
                                        <i class="fas fa-clock me-1"></i>
                                        ${slot.start_time} - ${slot.end_time}
                                    </p>
                                    <small class="text-muted">
                                        Duration: ${slot.duration || calculateDuration(slot.start_time, slot.end_time)}
                                    </small>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="col-12 text-center text-muted">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <p>No time slots created yet</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('existingSlots').innerHTML = `
                    <div class="col-12 text-center text-muted">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Unable to load existing time slots</p>
                    </div>
                `;
            });
    }
    
    function calculateDuration(start, end) {
        const startTime = new Date(`2000-01-01T${start}`);
        const endTime = new Date(`2000-01-01T${end}`);
        const diffMs = endTime - startTime;
        const diffMins = Math.floor(diffMs / 60000);
        const hours = Math.floor(diffMins / 60);
        const minutes = diffMins % 60;
        
        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else {
            return `${minutes}m`;
        }
    }
    
    // Event listeners
    startTimeInput.addEventListener('change', updateTimeInfo);
    endTimeInput.addEventListener('change', updateTimeInfo);
    
    // Form validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        if (!startTimeInput.value || !endTimeInput.value) {
            isValid = false;
            showNotification('Please provide both start and end times', 'error');
        }
        
        // Check time logic
        if (startTimeInput.value && endTimeInput.value) {
            const start = new Date(`2000-01-01T${startTimeInput.value}`);
            const end = new Date(`2000-01-01T${endTimeInput.value}`);
            
            if (end <= start) {
                isValid = false;
                showNotification('End time must be after start time', 'error');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
        saveBtn.disabled = true;
    });
    
    // Add another functionality
    document.getElementById('addAnotherBtn').addEventListener('click', function() {
        // Add hidden field to indicate "add another"
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'add_another';
        hiddenField.value = '1';
        form.appendChild(hiddenField);
        
        form.submit();
    });
    
    // Preview functionality
    document.getElementById('previewBtn').addEventListener('click', function() {
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;
        const name = nameInput.value || 'Unnamed Time Slot';
        
        if (startTime && endTime) {
            showNotification(`Preview: ${name} (${startTime} - ${endTime})`, 'info');
        } else {
            showNotification('Please fill in start and end times to preview', 'warning');
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

.time-preset {
    height: 60px;
    line-height: 1.2;
}

.time-preset:hover {
    transform: translateY(-2px);
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-success {
    border-color: #1cc88a !important;
}

.border-info {
    border-color: #36b9cc !important;
}

.bg-success {
    background: linear-gradient(135deg, #1cc88a, #13855c) !important;
}

.bg-info {
    background: linear-gradient(135deg, #36b9cc, #258391) !important;
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

.position-fixed {
    position: fixed !important;
}

#existingSlots .card {
    transition: all 0.2s ease;
}

#existingSlots .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
</style>
@endpush