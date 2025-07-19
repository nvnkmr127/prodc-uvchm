@extends('layouts.theme')
@section('title', 'Certificate Generator')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Certificate Generator</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Generate a New Certificate</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.certificate.generator.generate') }}" method="POST" target="_blank">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>1. Select Student*</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Select a Student --</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->enrollment_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>2. Select Certificate Template*</label>
                    <select name="template_id" class="form-control" required>
                         <option value="">-- Select a Template --</option>
                         @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate PDF</button>
        </form>
    </div>
</div>
@endsection