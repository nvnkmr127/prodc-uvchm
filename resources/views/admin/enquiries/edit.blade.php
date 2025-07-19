@extends('layouts.theme')
@section('title', 'Manage Enquiry: ' . $enquiry->student_name)

@push('styles')
<style>
    .modern-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
    }
    
    .enquiry-card {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: none;
        overflow: hidden;
    }
    
    .enquiry-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1.5rem;
    }
    
    .timeline-card {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: none;
        overflow: hidden;
    }
    
    .timeline-card .card-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        border: none;
        padding: 1.5rem;
    }
    
    .form-control {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 12px 16px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    
    .required::after {
        content: " *";
        color: #e74c3c;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        border: none;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        color: #2d5a27;
        transition: all 0.3s ease;
    }
    
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        color: #2d5a27;
    }
    
    .btn-info {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border: none;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-info:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    /* Timeline Styles */
    .timeline {
        position: relative;
        padding-left: 0;
        list-style: none;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 30px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
        padding-left: 80px;
    }
    
    .timeline-icon {
        position: absolute;
        left: 15px;
        top: 0;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        z-index: 1;
    }
    
    .timeline-body {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-left: 4px solid #667eea;
    }
    
    .timeline-body p {
        margin-bottom: 0.5rem;
        color: #2c3e50;
        line-height: 1.6;
    }
    
    .timeline-meta {
        font-size: 0.85em;
        color: #7f8c8d;
        border-top: 1px solid #ecf0f1;
        padding-top: 0.5rem;
        margin-top: 1rem;
    }
    
    .follow-up-form {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        border: 2px dashed #dee2e6;
    }
    
    .status-badge {
        padding: 0.4em 0.8em;
        border-radius: 20px;
        font-size: 0.75em;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .student-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 1rem;
    }
    
    .action-buttons {
        border-top: 1px solid #e9ecef;
        padding-top: 1.5rem;
        margin-top: 2rem;
    }
    
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        margin-right: 0.5rem;
    }
</style>
@endpush

@section('content')
<!-- Modern Header -->
<div class="modern-header">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h2 mb-2">Manage Lead / Enquiry</h1>
            <p class="mb-0 opacity-75">{{ $enquiry->student_name }} • {{ $enquiry->phone_number }}</p>
        </div>
        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Back to Enquiry Hub
        </a>
    </div>
</div>

<div class="row">
    <!-- Left Column: Student Details & Actions -->
    <div class="col-lg-5">
        <div class="card enquiry-card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Student Profile & Actions</h6>
            </div>
            <div class="card-body">
                <!-- Student Avatar -->
                <div class="text-center">
                    <div class="student-avatar mx-auto">
                        {{ strtoupper(substr($enquiry->student_name, 0, 1)) }}
                    </div>
                    <h5 class="font-weight-bold">{{ $enquiry->student_name }}</h5>
                    @if($enquiry->course)
                        <span class="badge badge-primary">{{ $enquiry->course->name }}</span>
                    @endif
                </div>

                <!-- Main Form for Admitting Student -->
                <form action="{{ route('admin.enquiries.admit', $enquiry) }}" method="POST" id="admitStudentForm">
                    @csrf
                    
                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-user text-primary"></i>
                            Basic Information
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Student Name</label>
                            <input type="text" name="student_name" class="form-control" value="{{ $enquiry->student_name }}" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Phone Number</label>
                                    <input type="tel" name="phone_number" class="form-control" value="{{ $enquiry->phone_number }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="{{ $enquiry->email }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Gender</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" {{ $enquiry->gender == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ $enquiry->gender == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ $enquiry->gender == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label required">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="{{ optional($enquiry->date_of_birth)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Education Qualification</label>
                            <input type="text" name="education_qualification" class="form-control" value="{{ $enquiry->education_qualification }}" placeholder="e.g., 12th Grade, Bachelor's Degree">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Enter full address">{{ $enquiry->address }}</textarea>
                        </div>
                    </div>

                    <!-- Enquiry Management Section -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-cogs text-info"></i>
                            Enquiry Management
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Not Interested', 'Admitted'] as $status)
                                    <option value="{{ $status }}" {{ $enquiry->status == $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Assign To Counselor</label>
                            <select name="assigned_to_user_id" class="form-control">
                                <option value="">-- Select Counselor --</option>
                                @foreach($counselors as $counselor)
                                    <option value="{{ $counselor->id }}" {{ $enquiry->assigned_to_user_id == $counselor->id ? 'selected' : '' }}>
                                        {{ $counselor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Next Follow-up Date</label>
                            <input type="date" name="next_follow_up_date" class="form-control" value="{{ $enquiry->next_follow_up_date }}" min="{{ date('Y-m-d') }}">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Course Interest</label>
                            <select name="course_id" class="form-control">
                                <option value="">-- Select Course --</option>
                                @foreach($courses ?? [] as $course)
                                    <option value="{{ $course->id }}" {{ $enquiry->course_id == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="button" onclick="updateEnquiry()" class="btn btn-info">
                            <i class="fas fa-save"></i> Update Details
                        </button>
                        <button type="submit" class="btn btn-success float-right" onclick="return confirmAdmission()">
                            <i class="fas fa-user-graduate"></i> Admit Student
                        </button>
                    </div>
                </form>

                <!-- Hidden form for updating enquiry only -->
                <form action="{{ route('admin.enquiries.update', $enquiry) }}" method="POST" id="updateEnquiryForm" style="display: none;">
                    @csrf
                    @method('PATCH')
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Timeline -->
    <div class="col-lg-7">
        <div class="card timeline-card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Interaction Timeline</h6>
            </div>
            <div class="card-body">
                <!-- Add Follow-up Form -->
                <div class="follow-up-form">
                    <h6 class="font-weight-bold mb-3">
                        <i class="fas fa-plus-circle text-primary"></i> Add New Follow-up
                    </h6>
                    <form action="{{ route('admin.enquiries.follow-ups.store', $enquiry) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <textarea name="notes" 
                                      class="form-control" 
                                      rows="3" 
                                      required 
                                      placeholder="Add details about your interaction with the student..."></textarea>
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Add Note
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Timeline -->
                @if($timeline && $timeline->count() > 0)
                    <ul class="timeline">
                        @foreach($timeline as $item)
                            <li class="timeline-item">
                                <div class="timeline-icon bg-primary">
                                    <i class="fas fa-comment-dots"></i>
                                </div>
                                <div class="timeline-body">
                                    <p>{{ $item->notes }}</p>
                                    <div class="timeline-meta">
                                        <i class="fas fa-user"></i> <strong>{{ optional($item->user)->name ?? 'Unknown User' }}</strong>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock"></i> {{ $item->created_at->format('d M, Y h:i A') }}
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-calendar"></i> {{ $item->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center p-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No interactions recorded yet</h5>
                        <p class="text-muted">Start by adding your first follow-up note above.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateEnquiry() {
        // Copy all form data from the main form to the update form
        const mainForm = document.getElementById('admitStudentForm');
        const updateForm = document.getElementById('updateEnquiryForm');
        
        // Get all form elements from the main form
        const formData = new FormData(mainForm);
        
        // Clear existing hidden inputs in update form
        updateForm.querySelectorAll('input[type="hidden"]:not([name="_token"]):not([name="_method"])').forEach(input => {
            input.remove();
        });
        
        // Add all form data as hidden inputs to update form
        for (let [key, value] of formData.entries()) {
            if (key !== '_token') { // Skip CSRF token as it already exists
                let hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = key;
                hiddenInput.value = value;
                updateForm.appendChild(hiddenInput);
            }
        }
        
        // Submit the update form
        updateForm.submit();
    }
    
    function confirmAdmission() {
        return confirm('Are you sure you want to admit this student? This will create a permanent student profile and cannot be undone.');
    }
    
    // Auto-save functionality (optional)
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('admitStudentForm');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        // Add change listeners for auto-save indication
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                // You can add visual feedback here that changes need to be saved
                const updateBtn = document.querySelector('button[onclick="updateEnquiry()"]');
                if (updateBtn && !updateBtn.classList.contains('btn-warning')) {
                    updateBtn.classList.remove('btn-info');
                    updateBtn.classList.add('btn-warning');
                    updateBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Save Changes';
                }
            });
        });
        
        // Phone number formatting
        const phoneInput = document.querySelector('input[name="phone_number"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
            });
        }
        
        // Email validation
        const emailInput = document.querySelector('input[name="email"]');
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const email = this.value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email && !emailRegex.test(email)) {
                    this.setCustomValidity('Please enter a valid email address');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    });
</script>
@endpush