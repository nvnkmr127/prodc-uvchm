@extends('layouts.theme')

@section('title', 'Edit Student: ' . $student->name)

@section('content')

{{-- 1. Page Header --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Student</h1>
    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-light shadow-sm"><i class="fas fa-arrow-left fa-sm text-gray-600"></i> Back to Profile</a>
</div>

{{-- Error Handling --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.students.update', $student) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PATCH')
    <div class="row">
        <div class="col-lg-8">
            {{-- Card 1: Personal & Contact Details --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Personal Details</h5>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="name">Full Name*</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $student->name) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="gender">Gender*</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="Male" {{ old('gender', $student->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender', $student->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender', $student->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                         <div class="col-md-6 form-group">
                            <label for="father_name">Father's Name</label>
                            <input type="text" id="father_name" name="father_name" class="form-control" value="{{ old('father_name', $student->father_name) }}">
                        </div>
                    </div>
<h5 class="mb-3">Source Information</h5>
<div class="row">
    <div class="col-md-6 form-group">
        <label for="source">Source*</label>
        <select name="source" id="source" class="form-control" required>
            <option value="">-- Please Select --</option>
            <option value="Website" {{ old('source', $student->source) == 'Website' ? 'selected' : '' }}>Website / Google</option>
            <option value="Social Media" {{ old('source', $student->source) == 'Social Media' ? 'selected' : '' }}>Social Media</option>
            <option value="Agent" {{ old('source', $student->source) == 'Agent' ? 'selected' : '' }}>Agent</option>
            <option value="Referrals" {{ old('source', $student->source) == 'Referrals' ? 'selected' : '' }}>Referrals</option>
            <option value="pro" {{ old('source', $student->source) == 'pro' ? 'selected' : '' }}>Pro</option>
            <option value="list" {{ old('source', $student->source) == 'list' ? 'selected' : '' }}>List</option>
            <option value="Student Refer" {{ old('source', $student->source) == 'Student Refer' ? 'selected' : '' }}>Student Refer</option>
            <option value="Walk-in" {{ old('source', $student->source) == 'Walk-in' ? 'selected' : '' }}>Walk-in</option>
            <option value="Other" {{ old('source', $student->source) == 'Other' ? 'selected' : '' }}>Other</option>
        </select>
    </div>
    <div class="col-md-6 form-group" id="referral_name_wrapper" style="display: none;">
        <label for="referral_name" id="referral_name_label">Referral Person Name</label>
        <input type="text" class="form-control" id="referral_name" name="referral_name"
               value="{{ old('referral_name', $student->referral_name) }}"
               placeholder="Enter referral person name">
    </div>
</div>

                    <hr>

                    <h5 class="mb-3">Contact & Address</h5>
                     <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $student->email) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="student_mobile">Student Mobile</label>
                            <input type="text" id="student_mobile" name="student_mobile" class="form-control" value="{{ old('student_mobile', $student->student_mobile) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="father_mobile">Father Mobile</label>
                            <input type="text" id="father_mobile" name="father_mobile" class="form-control" value="{{ old('father_mobile', $student->father_mobile) }}">
                        </div>
                         <div class="col-md-6 form-group">
                            <label for="village">Village / Address</label>
                            <input type="text" id="village" name="village" class="form-control" value="{{ old('village', $student->village) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Card 2: Academic & Photo --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Academic & Photo</h6>
                    <span class="badge badge-pill badge-secondary">{{ $student->status }}</span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="{{ $student->photo ? asset('storage/' . $student->photo) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&size=128&background=4e73df&color=fff' }}" class="img-thumbnail rounded-circle" alt="Student Photo" width="128" height="128">
                    </div>
                     <div class="form-group">
                        <label>Update Photo</label>
                        <div class="custom-file">
                            <input type="file" name="photo" class="custom-file-input" id="photo">
                            <label class="custom-file-label" for="photo">Choose new file...</label>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label for="enrollment_number">Enrollment Number*</label>
                        <input type="text" id="enrollment_number" name="enrollment_number" class="form-control" value="{{ old('enrollment_number', $student->enrollment_number) }}" required>
                        <small class="form-text text-muted">Updates automatically if batch is changed.</small>
                    </div>
                    <div class="form-group">
                        <label for="admission_date">Admission Date*</label>
                        <input type="date" id="admission_date" name="admission_date" class="form-control" value="{{ old('admission_date', \Carbon\Carbon::parse($student->admission_date)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="course_id_select">Course*</label>
                        <select id="course_id_select" class="form-control" required>
                            <option value="">-- Select a Course --</option>
                             @foreach(App\Models\Course::orderBy('name')->get() as $course)
                                <option value="{{ $course->id }}" {{ optional($student->batch)->course_id == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="batch_id">Assign to Batch*</label>
                        <select id="batch_id" name="batch_id" class="form-control" required>
                            <option value="">-- Select a course first --</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="card shadow">
        <div class="card-body text-right">
             <a href="{{ route('admin.students.show', $student) }}" class="btn btn-secondary">Cancel</a>
             <button type="submit" class="btn btn-primary">Update Student</button>
        </div>
    </div>
</form>

@endsection


@push('scripts')
<script>
$(document).ready(function() {
    // File input label handler
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
    
    document.addEventListener('DOMContentLoaded', function() {
    const studentMobileInput = document.getElementById('student_mobile');
    const fatherMobileInput = document.getElementById('father_mobile');
    
    // Validation patterns
    const mobilePattern = /^[6-9]\d{9}$/;
    
    // Debounce function for API calls
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Format mobile number input (remove non-digits, limit to 10 digits)
    function formatMobileInput(input) {
        input.addEventListener('input', function(e) {
            // Remove all non-digit characters
            let value = e.target.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            
            e.target.value = value;
            
            // Validate format
            validateMobileFormat(e.target);
            
            // Check for duplicates if valid format
            if (mobilePattern.test(value)) {
                debouncedDuplicateCheck(e.target);
            }
        });
    }

    // Referral Source Logic
    const sourceSelect = document.getElementById('source');
    const referralWrapper = document.getElementById('referral_name_wrapper');
    const referralInput = document.getElementById('referral_name');
    const referralLabel = document.getElementById('referral_name_label');

    const sourcesRequiringName = ['Referrals', 'pro', 'list', 'Student Refer'];

    function toggleReferralField() {
        if (sourcesRequiringName.includes(sourceSelect.value)) {
            referralWrapper.style.display = 'block';
            referralInput.required = true;

            switch (sourceSelect.value) {
                case 'Referrals':
                    referralLabel.textContent = 'Referral Person Name';
                    referralInput.placeholder = 'Enter referral person name';
                    break;
                case 'pro':
                    referralLabel.textContent = 'Pro Person Name';
                    referralInput.placeholder = 'Enter pro person name';
                    break;
                case 'list':
                    referralLabel.textContent = 'List Person Name';
                    referralInput.placeholder = 'Enter list person name';
                    break;
                case 'Student Refer':
                    referralLabel.textContent = 'Student Referrer Name';
                    referralInput.placeholder = 'Enter student referrer name';
                    break;
                default:
                    referralLabel.textContent = 'Referral Person Name';
                    referralInput.placeholder = 'Enter referral person name';
            }
        } else {
            referralWrapper.style.display = 'none';
            referralInput.required = false;
            referralInput.value = '';
        }
    }

    sourceSelect.addEventListener('change', toggleReferralField);

    // on page load
    toggleReferralField();


    // Validate mobile number format
    function validateMobileFormat(input) {
        const value = input.value;
        const feedbackElement = input.nextElementSibling;
        
        // Remove existing validation classes
        input.classList.remove('is-valid', 'is-invalid');
        
        if (value === '') {
            // Empty is allowed
            if (feedbackElement && feedbackElement.classList.contains('validation-feedback')) {
                feedbackElement.textContent = '';
            }
            return;
        }
        
        if (!mobilePattern.test(value)) {
            input.classList.add('is-invalid');
            showValidationMessage(input, 'Mobile number must be 10 digits starting with 6, 7, 8, or 9', 'error');
        } else {
            input.classList.add('is-valid');
            showValidationMessage(input, '✓ Valid format', 'success');
        }
    }

    // Check for duplicate mobile numbers
    function checkDuplicateMobile(input) {
        const value = input.value;
        const fieldName = input.name;
        const studentId = document.querySelector('input[name="_method"]')?.value === 'PUT' 
            ? window.location.pathname.split('/').pop() 
            : null;

        if (!value || !mobilePattern.test(value)) {
            return;
        }

        // Show loading state
        showValidationMessage(input, 'Checking availability...', 'loading');

        // Make AJAX request to check duplicates
        fetch('/admin/students/check-mobile-duplicate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                mobile: value,
                field: fieldName,
                student_id: studentId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.duplicate) {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
                showValidationMessage(input, data.message, 'error');
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                showValidationMessage(input, '✓ Available', 'success');
            }
        })
        .catch(error => {
            console.error('Error checking mobile duplicate:', error);
            showValidationMessage(input, 'Could not verify availability', 'warning');
        });
    }

    // Debounced duplicate check
    const debouncedDuplicateCheck = debounce(checkDuplicateMobile, 800);

    // Show validation message
    function showValidationMessage(input, message, type) {
        let feedbackElement = input.nextElementSibling;
        
        // Create feedback element if it doesn't exist
        if (!feedbackElement || !feedbackElement.classList.contains('validation-feedback')) {
            feedbackElement = document.createElement('div');
            feedbackElement.className = 'validation-feedback';
            input.parentNode.insertBefore(feedbackElement, input.nextSibling);
        }
        
        // Set message and styling
        feedbackElement.textContent = message;
        feedbackElement.className = 'validation-feedback';
        
        switch(type) {
            case 'success':
                feedbackElement.classList.add('valid-feedback');
                feedbackElement.style.display = 'block';
                break;
            case 'error':
                feedbackElement.classList.add('invalid-feedback');
                feedbackElement.style.display = 'block';
                break;
            case 'loading':
                feedbackElement.classList.add('text-info');
                feedbackElement.style.display = 'block';
                break;
            case 'warning':
                feedbackElement.classList.add('text-warning');
                feedbackElement.style.display = 'block';
                break;
        }
    }

    // Cross-validation: Check if student and father mobiles are the same
    function checkSameMobiles() {
        const studentMobile = studentMobileInput?.value;
        const fatherMobile = fatherMobileInput?.value;
        
        if (studentMobile && fatherMobile && studentMobile === fatherMobile) {
            fatherMobileInput.classList.add('is-invalid');
            showValidationMessage(fatherMobileInput, 'Father mobile cannot be same as student mobile', 'error');
            return false;
        }
        return true;
    }

    // Initialize validation for both inputs
    if (studentMobileInput) {
        formatMobileInput(studentMobileInput);
        studentMobileInput.addEventListener('blur', checkSameMobiles);
    }
    
    if (fatherMobileInput) {
        formatMobileInput(fatherMobileInput);
        fatherMobileInput.addEventListener('blur', checkSameMobiles);
    }

    // Form submission validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Check all mobile inputs for validity
            [studentMobileInput, fatherMobileInput].forEach(input => {
                if (input && input.value) {
                    if (!mobilePattern.test(input.value)) {
                        input.classList.add('is-invalid');
                        showValidationMessage(input, 'Invalid mobile number format', 'error');
                        isValid = false;
                    }
                    
                    if (input.classList.contains('is-invalid')) {
                        isValid = false;
                    }
                }
            });
            
            // Check if mobiles are the same
            if (!checkSameMobiles()) {
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first invalid input
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
        });
    }
});

    // Dynamic Batch Loading
    const courseSelect = $('#course_id_select');
    const batchSelect = $('#batch_id');
    const currentBatchId = {{ $student->batch_id ?? 'null' }};

    function loadBatches() {
        const courseId = courseSelect.val();
        batchSelect.html('<option value="">Loading...</option>').prop('disabled', true);

        if (courseId) {
            $.ajax({
                url: `/admin/get-batches-for-course/${courseId}`,
                type: 'GET',
                success: function(data) {
                    batchSelect.html('<option value="">-- Select a Batch --</option>');
                    if (data.length > 0) {
                        $.each(data, function(key, batch) {
                            // Pre-select the student's current batch
                            const isSelected = batch.id == currentBatchId ? 'selected' : '';
                            batchSelect.append(`<option value="${batch.id}" ${isSelected}>${batch.name}</option>`);
                        });
                        batchSelect.prop('disabled', false);
                    } else {
                        batchSelect.html('<option value="">-- No batches found --</option>');
                    }
                },
                error: function() {
                    batchSelect.html('<option value="">-- Error loading batches --</option>');
                }
            });
        } else {
            batchSelect.html('<option value="">-- Select a course first --</option>').prop('disabled', true);
        }
    }
    
    courseSelect.on('change', loadBatches);

    // Trigger on page load to populate batches for the pre-selected course
    if(courseSelect.val()) {
        loadBatches();
    }
});
</script>
@endpush
