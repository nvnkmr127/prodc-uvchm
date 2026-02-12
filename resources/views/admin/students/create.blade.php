@extends('layouts.theme')
@section('title', 'Add New Student')
@section('content')
    {{-- 1. Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Student</h1>
        <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-light shadow-sm"><i
                class="fas fa-arrow-left fa-sm text-gray-600"></i> Back to List</a>
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

    <form action="{{ route('admin.students.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
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
                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}"
                                    required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="gender">Gender*</label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="father_name">Father's Name</label>
                                <input type="text" id="father_name" name="father_name" class="form-control"
                                    value="{{ old('father_name') }}">
                            </div>
                        </div>

                        <h5 class="mb-3">Source Information</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="source">Source*</label>
                                <select name="source" id="source" class="form-control" required>
                                    <option value="">-- Please Select --</option>
                                    <option value="Website" {{ old('source') == 'Website' ? 'selected' : '' }}>Website /
                                        Google</option>
                                    <option value="Social Media" {{ old('source') == 'Social Media' ? 'selected' : '' }}>
                                        Social Media</option>
                                    <option value="Agent" {{ old('source') == 'Agent' ? 'selected' : '' }}>Agent</option>
                                    <option value="Referrals" {{ old('source') == 'Referrals' ? 'selected' : '' }}>Referrals
                                    </option>
                                    <option value="pro" {{ old('source') == 'pro' ? 'selected' : '' }}>Pro</option>
                                    <option value="list" {{ old('source') == 'list' ? 'selected' : '' }}>List</option>
                                    <option value="Student Refer" {{ old('source') == 'Student Refer' ? 'selected' : '' }}>
                                        Student Refer</option>
                                    <option value="Walk-in" {{ old('source') == 'Walk-in' ? 'selected' : '' }}>Walk-in
                                    </option>
                                    <option value="Other" {{ old('source') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group" id="referral_name_wrapper" style="display: none;">
                                <label for="referral_name" id="referral_name_label">Referral Person Name</label>
                                <input type="text" class="form-control" id="referral_name" name="referral_name"
                                    value="{{ old('referral_name') }}" placeholder="Enter referral person name">
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3">Contact & Address</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-control" value="{{ old('dob') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="student_mobile">Student Mobile</label>
                                <input type="text" id="student_mobile" name="student_mobile" class="form-control"
                                    value="{{ old('student_mobile') }}">
                                <div class="validation-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="father_mobile">Father Mobile</label>
                                <input type="text" id="father_mobile" name="father_mobile" class="form-control"
                                    value="{{ old('father_mobile') }}">
                                <div class="validation-feedback"></div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="village">Village / Address</label>
                                <input type="text" id="village" name="village" class="form-control"
                                    value="{{ old('village') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Card 2: Academic & Photo --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Academic Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="admission_date">Admission Date*</label>
                            <input type="date" id="admission_date" name="admission_date" class="form-control"
                                value="{{ old('admission_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="course_id_select">Course*</label>
                            <select id="course_id_select" name="course_id" class="form-control" required>
                                <option value="">-- Select a Course --</option>
                                @foreach(App\Models\Course::orderBy('name')->get() as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="batch_id">Assign to Batch*</label>
                            <select id="batch_id" name="batch_id" class="form-control" required>
                                <option value="">-- Select a course first --</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="biometric_employee_code">Biometric Employee Code</label>
                            <input type="text" class="form-control @error('biometric_employee_code') is-invalid @enderror"
                                id="biometric_employee_code" name="biometric_employee_code"
                                value="{{ old('biometric_employee_code', $student->biometric_employee_code ?? '') }}"
                                placeholder="Enter biometric device employee code">

                            @error('biometric_employee_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="form-text text-muted">
                                This code is used by biometric devices for attendance tracking.
                                <a href="#" id="generateSuggestion" class="text-primary">Generate from enrollment number</a>
                            </small>
                        </div>


                        <div class="form-group">
                            <label>Student Photo</label>
                            <div class="custom-file">
                                <input type="file" name="photo" class="custom-file-input" id="photo">
                                <label class="custom-file-label" for="photo">Choose file...</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="card shadow">
            <div class="card-body text-right">
                <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Student</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // ==========================================
            // 1. FILE INPUT HANDLER
            // ==========================================
            $('.custom-file-input').on('change', function () {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });

            // ==========================================
            // 2. MOBILE VALIDATION VARIABLES & FUNCTIONS
            // ==========================================
            const studentMobileInput = document.getElementById('student_mobile');
            const fatherMobileInput = document.getElementById('father_mobile');
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
                input.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');

                    if (value.length > 10) {
                        value = value.slice(0, 10);
                    }

                    e.target.value = value;
                    validateMobileFormat(e.target);

                    if (mobilePattern.test(value)) {
                        debouncedDuplicateCheck(e.target);
                    }
                });
            }

            // Validate mobile number format
            function validateMobileFormat(input) {
                const value = input.value;

                input.classList.remove('is-valid', 'is-invalid');

                if (value === '') {
                    showValidationMessage(input, '', 'clear');
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

                if (!value || !mobilePattern.test(value)) {
                    return;
                }

                showValidationMessage(input, 'Checking availability...', 'loading');

                fetch('/admin/students/check-mobile-duplicate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        mobile: value,
                        field: fieldName,
                        student_id: null // For create, no existing student ID
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

            // Show validation message
            function showValidationMessage(input, message, type) {
                let feedbackElement = input.nextElementSibling;

                if (!feedbackElement || !feedbackElement.classList.contains('validation-feedback')) {
                    feedbackElement = document.createElement('div');
                    feedbackElement.className = 'validation-feedback';
                    input.parentNode.insertBefore(feedbackElement, input.nextSibling);
                }

                feedbackElement.textContent = message;
                feedbackElement.className = 'validation-feedback';

                switch (type) {
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
                    case 'clear':
                        feedbackElement.style.display = 'none';
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

            // Debounced duplicate check
            const debouncedDuplicateCheck = debounce(checkDuplicateMobile, 800);

            // Initialize validation for mobile inputs
            if (studentMobileInput) {
                formatMobileInput(studentMobileInput);
                studentMobileInput.addEventListener('blur', checkSameMobiles);
            }

            if (fatherMobileInput) {
                formatMobileInput(fatherMobileInput);
                fatherMobileInput.addEventListener('blur', checkSameMobiles);
            }

            // ==========================================
            // 3. SOURCE AND REFERRAL FIELD LOGIC
            // ==========================================
            const sourceSelect = document.getElementById('source');
            const referralWrapper = document.getElementById('referral_name_wrapper');
            const referralInput = document.getElementById('referral_name');
            const referralLabel = document.getElementById('referral_name_label');

            // ✅ UPDATED: Sources that require referral person name (Added Agent and Other)
            const sourcesRequiringName = ['Agent', 'Referrals', 'pro', 'list', 'Student Refer', 'Other'];

            function toggleReferralField() {
                if (sourcesRequiringName.includes(sourceSelect.value)) {
                    referralWrapper.style.display = 'block';
                    referralInput.required = true;

                    // ✅ COMPLETE switch statement for all source types
                    switch (sourceSelect.value) {
                        case 'Agent':
                            referralLabel.textContent = 'Agent Name';
                            referralInput.placeholder = 'Enter agent name';
                            break;
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
                        case 'Other':
                            referralLabel.textContent = 'Contact Person Name';
                            referralInput.placeholder = 'Enter contact person name';
                            break;
                        default:
                            referralLabel.textContent = 'Referral Person Name';
                            referralInput.placeholder = 'Enter referral person name';
                    }
                } else {
                    referralWrapper.style.display = 'none';
                    referralInput.value = '';
                    referralInput.required = false;
                }
            }

            // Add event listener for source change
            sourceSelect.addEventListener('change', toggleReferralField);

            // ✅ IMPORTANT: Trigger on page load to show field if value is already selected (e.g., from validation errors)
            toggleReferralField();

            // ==========================================
            // 3.1. AUTO-SUGGESTION LOGIC
            // ==========================================
            const suggestionList = document.createElement('ul');
            suggestionList.className = 'list-group position-absolute w-100';
            suggestionList.style.zIndex = '1000';
            suggestionList.style.display = 'none';
            suggestionList.style.maxHeight = '200px';
            suggestionList.style.overflowY = 'auto';
            referralInput.parentNode.appendChild(suggestionList);

            // Close suggestions when clicking outside
            document.addEventListener('click', function (e) {
                if (e.target !== referralInput && e.target !== suggestionList) {
                    suggestionList.style.display = 'none';
                }
            });

            // Handle input for suggestions
            const handleSuggestions = debounce(function () {
                const query = referralInput.value;
                const source = sourceSelect.value;

                if (query.length < 2) {
                    suggestionList.style.display = 'none';
                    return;
                }

                // Only search for relevant sources
                if (!sourcesRequiringName.includes(source)) {
                    return;
                }

                // Position the list dynamically
                suggestionList.style.top = (referralInput.offsetTop + referralInput.offsetHeight) + 'px';
                suggestionList.style.left = referralInput.offsetLeft + 'px';
                suggestionList.style.width = referralInput.offsetWidth + 'px';

                // Ensure parent has relative position
                referralInput.parentNode.style.position = 'relative';

                const url = `/admin/students/suggestions?query=${encodeURIComponent(query)}&source=${encodeURIComponent(source)}`;

                fetch(url)
                    .then(response => {
                        return response.json();
                    })
                    .then(data => {
                        suggestionList.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(suggestion => {
                                const item = document.createElement('li');
                                item.className = 'list-group-item list-group-item-action cursor-pointer';

                                // Check if suggestion is object (new format) or string (fallback)
                                const name = suggestion.value || suggestion;
                                const label = suggestion.label || suggestion;
                                const extra = suggestion.extra ? ` <small class="text-muted">(${suggestion.extra})</small>` : '';

                                item.innerHTML = label + extra;
                                item.style.cursor = 'pointer'; // Ensure pointer cursor
                                // Add styling as requested
                                item.style.backgroundColor = '#e3f2fd'; // Light blue
                                item.style.color = '#0d47a1'; // Dark blue text
                                item.style.fontWeight = '500';

                                item.onclick = function () {
                                    referralInput.value = name;
                                    suggestionList.style.display = 'none';
                                };
                                suggestionList.appendChild(item);
                            });
                            suggestionList.style.display = 'block';
                        } else {
                            suggestionList.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Error fetching suggestions:', error));
            }, 400);

            referralInput.addEventListener('input', handleSuggestions);
            referralInput.addEventListener('focus', function () {
                if (this.value.length >= 2) {
                    handleSuggestions();
                }
            });

            // ==========================================
            // 4. DYNAMIC BATCH LOADING
            // ==========================================
            const courseSelect = $('#course_id_select');
            const batchSelect = $('#batch_id');

            courseSelect.on('change', function () {
                const courseId = $(this).val();
                batchSelect.html('<option value="">Loading...</option>').prop('disabled', true);

                if (courseId) {
                    $.ajax({
                        url: `/admin/get-batches-for-course/${courseId}`,
                        type: 'GET',
                        success: function (data) {
                            batchSelect.html('<option value="">-- Select a Batch --</option>');
                            if (data.length > 0) {
                                $.each(data, function (key, batch) {
                                    const isSelected = '{{ old("batch_id") }}' == batch.id ? 'selected' : '';
                                    batchSelect.append(`<option value="${batch.id}" ${isSelected}>${batch.name}</option>`);
                                });
                                batchSelect.prop('disabled', false);
                            } else {
                                batchSelect.html('<option value="">-- No batches found --</option>');
                            }
                        },
                        error: function () {
                            batchSelect.html('<option value="">-- Error loading batches --</option>');
                        }
                    });
                } else {
                    batchSelect.html('<option value="">-- Select a course first --</option>').prop('disabled', true);
                }
            });

            // Trigger change on page load if a course is already selected (e.g., from old input)
            if (courseSelect.val()) {
                courseSelect.trigger('change');
            }

            // ==========================================
            // 5. FORM SUBMISSION VALIDATION
            // ==========================================
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function (e) {
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

                    // ✅ Check if referral field is required and filled
                    if (sourcesRequiringName.includes(sourceSelect.value) && !referralInput.value.trim()) {
                        referralInput.classList.add('is-invalid');
                        showValidationMessage(referralInput, 'This field is required for the selected source', 'error');
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

            // ==========================================
            // 6. DEBUG LOGGING (Remove in production)
            // ==========================================
            form.addEventListener('submit', function (e) {
                console.log('Form submitting with source:', sourceSelect.value);
                console.log('Referral name:', referralInput.value);
                console.log('Referral field required:', sourcesRequiringName.includes(sourceSelect.value));
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            // Generate biometric code suggestion from enrollment number
            $('#generateSuggestion').click(function (e) {
                e.preventDefault();

                var enrollmentNumber = $('#enrollment_number').val();
                if (!enrollmentNumber) {
                    alert('Please enter enrollment number first');
                    return;
                }

                // Generate suggestion by removing common prefixes
                var suggestion = enrollmentNumber.replace(/^(UVCHM-|UV-|ENR-|STD-)/i, '');
                suggestion = suggestion.replace(/[^a-zA-Z0-9\-]/g, '');

                $('#biometric_employee_code').val(suggestion);
            });

            // Auto-generate when enrollment number changes (optional)
            $('#enrollment_number').blur(function () {
                var biometricField = $('#biometric_employee_code');
                if (!biometricField.val() && $(this).val()) {
                    var suggestion = $(this).val().replace(/^(UVCHM-|UV-|ENR-|STD-)/i, '');
                    suggestion = suggestion.replace(/[^a-zA-Z0-9\-]/g, '');
                    biometricField.val(suggestion);
                }
            });
        });
    </script>


@endpush