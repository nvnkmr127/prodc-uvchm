@extends('layouts.theme')
@section('title', 'Add New Enquiry')

@push('styles')
<style>
    .modern-form {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 15px;
        padding: 2rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-control {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 12px 16px;
        font-size: 14px;
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
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .btn-secondary {
        background: #6c757d;
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        background: #5a6268;
    }
    
    .form-section {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .section-title {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #ecf0f1;
    }
    
    .icon-input {
        position: relative;
    }
    
    .icon-input .fas {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        z-index: 10;
    }
    
    .icon-input .form-control {
        padding-left: 45px;
    }
    
    .modern-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
    }
    
    .dynamic-field {
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
</style>
@endpush

@section('content')
<!-- Modern Header -->
<div class="modern-header">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h2 mb-2">Add New Admission Enquiry</h1>
            <p class="mb-0 opacity-75">Capture detailed information about prospective students</p>
        </div>
        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Back to Enquiry Hub
        </a>
    </div>
</div>

<div class="modern-form">
    <form action="{{ route('admin.enquiries.store') }}" method="POST" id="enquiryForm">
        @csrf
        
        <!-- Student Information Section -->
        <div class="form-section">
            <h4 class="section-title">
                <i class="fas fa-user-graduate text-primary"></i> Student Information
            </h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group icon-input">
                        <label for="student_name" class="form-label required">Student Name</label>
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               class="form-control @error('student_name') is-invalid @enderror" 
                               id="student_name"
                               name="student_name" 
                               value="{{ old('student_name') }}" 
                               required
                               placeholder="Enter student's full name">
                        @error('student_name') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group icon-input">
                        <label for="phone_number" class="form-label required">Phone Number</label>
                        <i class="fas fa-phone"></i>
                        <input type="tel" 
                               class="form-control @error('phone_number') is-invalid @enderror" 
                               id="phone_number"
                               name="phone_number" 
                               value="{{ old('phone_number') }}" 
                               required
                               placeholder="Enter 10-digit phone number">
                        @error('phone_number') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="form-group icon-input">
                <label for="address" class="form-label">Address / Village</label>
                <i class="fas fa-map-marker-alt"></i>
                <input type="text"
                       class="form-control"
                       id="address"
                       name="address"
                       value="{{ old('address') }}"
                       placeholder="Enter address or village">
            </div>
        </div>

        <!-- Course & Assignment Section -->
        <div class="form-section">
            <h4 class="section-title">
                <i class="fas fa-graduation-cap text-success"></i> Course & Assignment
            </h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="course_id" class="form-label">Course of Interest</label>
                        <select name="course_id" id="course_id" class="form-control">
                            <option value="">-- Select a Course --</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="assigned_to_user_id" class="form-label">Assign To Counselor</label>
                        <select name="assigned_to_user_id" id="assigned_to_user_id" class="form-control">
                            <option value="">-- Select Counselor --</option>
                            @foreach($counselors as $counselor)
                                <option value="{{ $counselor->id }}" {{ old('assigned_to_user_id') == $counselor->id ? 'selected' : '' }}>
                                    {{ $counselor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Source Information Section -->
        <div class="form-section">
            <h4 class="section-title">
                <i class="fas fa-search text-info"></i> Source Information
            </h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="source" class="form-label">Source of Enquiry</label>
                        <select name="source" id="source_select" class="form-control">
                            <option value="">-- Please Select --</option>
                            <option value="Website" {{ old('source') == 'Website' ? 'selected' : '' }}>Website / Google</option>
                            <option value="Social Media" {{ old('source') == 'Social Media' ? 'selected' : '' }}>Social Media</option>
                            <option value="Agent" {{ old('source') == 'Agent' ? 'selected' : '' }}>Agent</option>
                            <option value="Referrals" {{ old('source') == 'Referrals' ? 'selected' : '' }}>Referrals</option>
                            <option value="Walk-in" {{ old('source') == 'Walk-in' ? 'selected' : '' }}>Walk-in</option>
                            <option value="Other" {{ old('source') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group dynamic-field" id="referral_name_wrapper" style="display: none;">
                        <label for="referral_name" class="form-label" id="referral_name_label">Referral/Agent Name</label>
                        <input type="text" 
                               class="form-control" 
                               id="referral_name" 
                               name="referral_name"
                               value="{{ old('referral_name') }}"
                               placeholder="Enter referral or agent name">
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information Section -->
        <div class="form-section">
            <h4 class="section-title">
                <i class="fas fa-sticky-note text-warning"></i> Additional Information
            </h4>
            
            <div class="form-group">
                <label for="notes" class="form-label">Initial Notes</label>
                <textarea name="notes" 
                          id="notes"
                          class="form-control" 
                          rows="4"
                          placeholder="Add any initial notes about the enquiry...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg mr-3">
                <i class="fas fa-save"></i> Save Enquiry
            </button>
            <a href="{{ route('admin.enquiries.index') }}" class="btn btn-secondary btn-lg">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sourceSelect = document.getElementById('source_select');
        const referralWrapper = document.getElementById('referral_name_wrapper');
        const referralInput = document.getElementById('referral_name');
        const referralLabel = document.getElementById('referral_name_label');

        function toggleReferralField() {
            if (sourceSelect.value === 'Agent') {
                referralWrapper.style.display = 'block';
                referralInput.required = true;
                referralLabel.textContent = 'Agent Name';
                referralInput.placeholder = 'Enter agent name';
            } else if (sourceSelect.value === 'Referrals' || sourceSelect.value === 'Referral') {
                referralWrapper.style.display = 'block';
                referralInput.required = true;
                referralLabel.textContent = 'Referral Name';
                referralInput.placeholder = 'Enter referral name';
            } else {
                referralWrapper.style.display = 'none';
                referralInput.value = '';
                referralInput.required = false;
            }
        }

        sourceSelect.addEventListener('change', toggleReferralField);
        
        // Trigger change on page load to show field if old value is selected
        toggleReferralField();
        
        // Form validation
        const form = document.getElementById('enquiryForm');
        const phoneInput = document.getElementById('phone_number');
        
        phoneInput.addEventListener('input', function() {
            // Remove non-digits
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
        
        form.addEventListener('submit', function(e) {
            const phone = phoneInput.value;
            if (phone.length < 10) {
                e.preventDefault();
                alert('Please enter a valid 10-digit phone number');
                phoneInput.focus();
                return false;
            }
        });
    });
</script>
@endpush