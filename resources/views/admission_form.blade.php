<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="col-md-8 offset-md-2">
            <h2>College Admission Application Form</h2>
            <p>Please fill out the form below to apply for a course.</p>
            <hr>
            @if ($errors->any())
                <div class="alert alert-danger">Please correct the errors below.</div>
            @endif
            <form action="{{ route('admission.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="full_name" class="form-label">Full Name*</label>
                        <input type="text" class="form-control" name="full_name" value="{{ old('full_name') }}" required>
                    </div>
                    {{-- FIX: Added the missing Gender field --}}
                    <div class="col-md-4 mb-3">
                        <label for="gender" class="form-label">Gender*</label>
                        <select class="form-select" name="gender" required>
                            <option value="">-- Select --</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address*</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                    </div>
                     <div class="col-md-6 mb-3">
                        <label for="phone_number" class="form-label">Mobile Number*</label>
                        <input type="text" class="form-control" name="phone_number" value="{{ old('phone_number') }}" required>
                    </div>
                </div>
                 <div class="mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth*</label>
                    <input type="date" class="form-control" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Village / Full Address*</label>
                    <textarea class="form-control" name="address" rows="3" required>{{ old('address') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="education_qualification" class="form-label">Education Qualification</label>
                    <textarea class="form-control" name="education_qualification" rows="2" placeholder="e.g., 12th Standard, B.Com, etc.">{{ old('education_qualification') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="course_id" class="form-label">Select Course to Apply For*</label>
                    <select class="form-select" name="course_id" required>
                        <option value="">-- Please Select a Course --</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="source" class="form-label">How did you hear about us?</label>
                    <select class="form-select" name="source" id="source_select">
                        <option value="">-- Please Select --</option>
                        <option value="Social Media">Social Media</option>
                        <option value="Website">Website / Google Search</option>
                        <option value="Agent">Educational Agent</option>
                        <option value="Friend/Family">Friend / Family Referral</option>
                        <option value="Influencer">Influencer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3" id="referral_name_wrapper" style="display: none;">
                    <label for="referral_name" class="form-label" id="referral_name_label">Referral Name</label>
                    <input type="text" class="form-control" id="referral_name" name="referral_name">
                </div>
                <button type="submit" class="btn btn-primary mt-3">Submit Application</button>
            </form>
        </div>
    </div>
    <script>
         document.addEventListener('DOMContentLoaded', function() {
            const sourceSelect = document.getElementById('source_select');
            const referralWrapper = document.getElementById('referral_name_wrapper');
            const referralLabel = document.getElementById('referral_name_label');
            const referralInput = document.getElementById('referral_name');
            const sourcesRequiringName = ['Agent', 'Friend/Family', 'Influencer'];

            sourceSelect.addEventListener('change', function() {
                if (sourcesRequiringName.includes(this.value)) {
                    if(this.value === 'Agent') { referralLabel.textContent = 'Agent Name'; }
                    else if(this.value === 'Friend/Family') { referralLabel.textContent = 'Friend/Family Name'; }
                    else { referralLabel.textContent = 'Influencer Name'; }
                    referralWrapper.style.display = 'block';
                    referralInput.required = true;
                } else {
                    referralWrapper.style.display = 'none';
                    referralInput.value = '';
                    referralInput.required = false;
                }
            });
        });
    </script>
</body>
</html>
