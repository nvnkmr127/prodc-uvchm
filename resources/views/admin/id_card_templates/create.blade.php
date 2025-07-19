@extends('layouts.theme')
@section('title', 'Create ID Card Template')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Create New ID Card Template</h1>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Template Editor</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.id-card-templates.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="name">Template Name*</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Student ID Card - Vertical" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="content">Template HTML Content*</label>
                        <textarea name="content" class="form-control" rows="25" required>
{{-- This is a sample template to get you started. You can customize it freely. --}}
<div style="border: 2px solid #4e73df; width: 320px; height: 510px; font-family: sans-serif; position: relative; background: #fff;">
    <div style="background-color: #4e73df; color: white; text-align: center; padding: 10px;">
        <h5 style="margin:0;">[college_name]</h5>
    </div>
    <div style="text-align: center; padding: 15px;">
        <img src="[student_photo_url]" alt="Photo" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #ddd;">
        <h5 style="margin-top: 15px; margin-bottom: 5px; font-weight: bold;">[student_name]</h5>
        <p style="margin:0; font-size: 14px;"><strong>Course:</strong> [course_name]</p>
        <p style="margin:0; font-size: 14px;"><strong>Batch:</strong> [batch_name]</p>
        <hr>
        <p style="margin:0; font-size: 14px;"><strong>Enrollment No:</strong></p>
        <h6 style="font-weight: bold; margin-top: 5px;">[enrollment_number]</h6>
        <p style="margin:0; font-size: 14px; margin-top: 15px;"><strong>Valid Upto:</strong></p>
        <h6 style="font-weight: bold; margin-top: 5px;">[batch_end_date]</h6>
    </div>
    <div style="position: absolute; bottom: 0; width: 100%; text-align: center; background-color: #f8f9fc; padding: 10px; font-size: 10px; color: #858796;">
        <p style="margin:0;">[college_address]</p>
    </div>
</div>
                        </textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                    <a href="{{ route('admin.id-card-templates.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
             <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Available Placeholders</h6>
            </div>
            <div class="card-body">
                <p>Use these placeholders in your HTML. They will be automatically replaced with the student's data.</p>
                <ul>
                    <li><code>[student_name]</code></li>
                    <li><code>[student_photo_url]</code></li>
                    <li><code>[enrollment_number]</code></li>
                    <li><code>[course_name]</code></li>
                    <li><code>[batch_name]</code></li>
                    <li><code>[batch_end_date]</code></li>
                    <li><code>[college_name]</code></li>
                    <li><code>[college_address]</code></li>
                    <li><code>[college_logo_url]</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
