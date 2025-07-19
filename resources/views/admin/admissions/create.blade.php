{{-- This view assumes you have a base layout file, e.g., @extends('layouts.admin') --}}
{{-- You will need to adjust the HTML structure and classes to match your admin panel's design --}}

@extends('layouts.admin') {{-- Replace with your actual layout file --}}

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Finalize Admission for: {{ $enquiry->student_name }}</h2>
            <p><strong>Enquiry For Course:</strong> {{ $enquiry->course->name ?? 'Not specified' }}</p>
        </div>
        <div class="card-body">
            {{-- Display validation errors if any --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.admissions.finalize') }}">
                @csrf
                {{-- Hidden field to pass the original enquiry ID for processing --}}
                <input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">
                {{-- Hidden field to pass the course ID --}}
                <input type="hidden" name="course_id" value="{{ $enquiry->course_id }}">

                <h4>Student Details</h4>
                <p>Please review the information from the enquiry and complete the required fields below.</p>
                <hr>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="full_name" class="form-label"><strong>Full Name *</strong></label>
                        {{-- Pre-filled from the enquiry record --}}
                        <input type="text" class="form-control" id="full_name" name="full_name" value="{{ old('full_name', $enquiry->student_name) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label"><strong>Email Address *</strong></label>
                        {{-- Admin must fill this required field --}}
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $enquiry->email ?? '') }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="phone_number" class="form-label"><strong>Phone Number *</strong></label>
                        {{-- Pre-filled from the enquiry record --}}
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $enquiry->phone_number) }}" required>
                    </div>
                     <div class="col-md-4 mb-3">
                        <label for="date_of_birth" class="form-label"><strong>Date of Birth *</strong></label>
                        {{-- Admin must fill this required field --}}
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="gender" class="form-label"><strong>Gender *</strong></label>
                         {{-- Admin must select the gender --}}
                        <select class="form-control" name="gender" id="gender" required>
                            <option value="" disabled selected>-- Select Gender --</option>
                            <option value="Male" @if(old('gender') == 'Male') selected @endif>Male</option>
                            <option value="Female" @if(old('gender') == 'Female') selected @endif>Female</option>
                            <option value="Other" @if(old('gender') == 'Other') selected @endif>Other</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="address" class="form-label"><strong>Full Address *</strong></label>
                        {{-- Pre-filled from the enquiry record --}}
                        <textarea class="form-control" id="address" name="address" rows="3" required>{{ old('address', $enquiry->address) }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Finalize and Approve Admission</button>
                    <a href="{{ route('admin.enquiries.edit', $enquiry) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection