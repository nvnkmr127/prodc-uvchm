@extends('layouts.theme')
@section('title', 'Create Certificate Template')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Create New Certificate Template</h1>
<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('admin.certificate-templates.store') }}" method="POST">
            @csrf
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="form-group mb-3"><label>Template Name*</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group mb-3"><label>Certificate Body*</label><textarea name="body" id="certificate-editor" class="form-control" rows="20"></textarea></div>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
             <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Available Placeholders</h6></div>
             <div class="card-body">
                <p>Use these in your template:</p>
                <ul><li><code>[student_name]</code></li><li><code>[enrollment_number]</code></li><li><code>[course_name]</code></li><li><code>[batch_name]</code></li><li><code>[issue_date]</code></li><li><code>[college_name]</code></li></ul>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    tinymce.init({ selector: '#certificate-editor', height: 500, menubar: false, plugins: 'preview wordcount table lists link image', toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image table' });
</script>
@endpush