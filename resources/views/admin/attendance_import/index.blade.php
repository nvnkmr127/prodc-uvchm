@extends('layouts.theme')
@section('title', 'Import Attendance')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Bulk Import Attendance from CSV/Excel</h1>

@if(session('success'))
    <div class="alert alert-success">{!! nl2br(e(session('success'))) !!}</div>
@endif
 @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Instructions</h6></div>
    <div class="card-body">
        <p>Please prepare a CSV or Excel file with a header row containing these exact column names:</p>
        <p><code>enrollment_number,attendance_date,status</code></p>
        <ul>
            <li>The <code>attendance_date</code> must be in <strong>YYYY-MM-DD</strong> format.</li>
            <li>The <code>status</code> must be either <strong>present</strong> or <strong>absent</strong> (not case-sensitive).</li>
        </ul>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Upload File</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.attendance.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="attendance_file">Select File</label>
                <input type="file" name="attendance_file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Import Attendance</button>
        </form>
    </div>
</div>
@endsection