@extends('layouts.theme') {{-- ✅ CHANGE FROM layouts.admin TO layouts.theme --}}

@section('content')
<div class="container-fluid">
    {{-- ✅ ADD ALERT HANDLING --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-header py-3">
            <h4 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-user-graduate me-2"></i>Finalize Admission for: {{ $enquiry->student_name }}
            </h4>
            <p class="mb-0 text-muted">
                <strong>Enquiry For Course:</strong> {{ $enquiry->course->name ?? 'Not specified' }}
            </p>
        </div>
        <div class="card-body">
            {{-- Display validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.admissions.finalize') }}">
                @csrf
                <input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">
                <input type="hidden" name="course_id" value="{{ $enquiry->course_id }}">

                <h5 class="mb-3">
                    <i class="fas fa-user me-2"></i>Student Details
                </h5>
                <p class="text-muted">Please review the information from the enquiry and complete the required fields below.</p>
                <hr>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="full_name" class="form-label">
                            <strong>Full Name <span class="text-danger">*</span></strong>
                        </label>
                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                               id="full_name" name="full_name" value="{{ old('full_name', $enquiry->student_name) }}" required>
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">
                            <strong>Email Address <span class="text-danger">*</span></strong>
                        </label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email', $enquiry->email ?? '') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="phone_number" class="form-label">
                            <strong>Phone Number <span class="text-danger">*</span></strong>
                        </label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                               id="phone_number" name="phone_number" value="{{ old('phone_number', $enquiry->phone_number) }}" required>
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                     <div class="col-md-4 mb-3">
                        <label for="date_of_birth" class="form-label">
                            <strong>Date of Birth <span class="text-danger">*</span></strong>
                        </label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" 
                               id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $enquiry->date_of_birth ? $enquiry->date_of_birth->format('Y-m-d') : '') }}" required>
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="gender" class="form-label">
                            <strong>Gender <span class="text-danger">*</span></strong>
                        </label>
                         <select class="form-control @error('gender') is-invalid @enderror" name="gender" id="gender" required>
                            <option value="" disabled {{ !old('gender', $enquiry->gender) ? 'selected' : '' }}>-- Select Gender --</option>
                            <option value="Male" {{ old('gender', $enquiry->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $enquiry->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender', $enquiry->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="address" class="form-label">
                            <strong>Full Address <span class="text-danger">*</span></strong>
                        </label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="3" required>{{ old('address', $enquiry->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if($enquiry->education_qualification)
                <div class="mb-3">
                    <label for="education_qualification" class="form-label">
                        <strong>Education Qualification</strong>
                    </label>
                    <textarea class="form-control" id="education_qualification" name="education_qualification" rows="2">{{ old('education_qualification', $enquiry->education_qualification) }}</textarea>
                </div>
                @endif

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.enquiries.edit', $enquiry) }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Finalize and Approve Admission
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection