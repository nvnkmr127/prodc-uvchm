@extends('layouts.theme')
@section('title', 'Edit ID Card Template')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit ID Card Template</h1>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Template Editor</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.id-card-templates.update', $idCardTemplate) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="form-group mb-3">
                        <label for="name">Template Name*</label>
                        <input type="text" name="name" class="form-control" value="{{ $idCardTemplate->name }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="content">Template HTML Content*</label>
                        <textarea name="content" class="form-control" rows="25" required>{{ $idCardTemplate->content }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Template</button>
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
