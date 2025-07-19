@extends('layouts.theme')
@section('title', 'Edit Certificate Template')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Certificate Template</h1>
 <div class="row">
    <div class="col-lg-8">
        <form action="{{ route('admin.certificate-templates.update', $certificateTemplate) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="form-group mb-3"><label>Template Name*</label><input type="text" name="name" class="form-control" value="{{ $certificateTemplate->name }}" required></div>
                    <div class="form-group mb-3"><label>Certificate Body*</label><textarea name="body" id="certificate-editor" class="form-control" rows="20">{{ $certificateTemplate->body }}</textarea></div>
                    <button type="submit" class="btn btn-primary">Update Template</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        {{-- Placeholder card from create view goes here --}}
    </div>
</div>
@endsection
@push('scripts')
<script>
    tinymce.init({ selector: '#certificate-editor', height: 500, menubar: false, plugins: 'preview wordcount table lists link image', toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image table' });
</script>
@endpush