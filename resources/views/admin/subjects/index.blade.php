@extends('layouts.theme')

@section('title', 'Manage Subjects')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Subjects</h1>
    <a href="{{ route('admin.subjects.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Subject</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
 <div class="card shadow mb-4">
<div class="card-header py-3">
<h6 class="m-0 font-weight-bold text-primary">All Subjects</h6>
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
<thead class="thead-light">
<tr>
<th>Subject Name</th>
<th>Code</th>
<th>Requires Lab?</th>
<th>Usage Stats</th>
<th style="width: 15%;">Actions</th>
</tr>
</thead>
<tbody>
@forelse ($subjects as $subject)
<tr>
<td><strong>{{ $subject->name }}</strong></td>
<td>{{ $subject->code }}</td>
<td>
@if($subject->requires_lab)
<span class="badge badge-info">Yes</span>
@else
<span class="badge badge-secondary">No</span>
@endif
</td>
<td>
<small class="d-block">Assigned to: {{ $subject->courses_count }} Course(s)</small>
<small class="d-block">Taught by: {{ $subject->faculty_count }} Faculty</small>
</td>
<td>
<a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
<form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST" class="d-inline">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')" title="Delete"><i class="fas fa-trash"></i></button>
</form>
</td>
</tr>
@empty
<tr>
<td colspan="5" class="text-center">No subjects found. Add one to get started!</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
</div>
@endsection