@extends('layouts.theme')
@section('title', 'Manage Courses')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Courses</h1>
    <a href="{{ route('admin.courses.create') }}" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Course
    </a>
</div>

{{-- Display Success/Error Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif


<div class="row">
    @forelse ($courses as $course)
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center mb-3">
                    <div class="col">
                        <h5 class="font-weight-bold text-primary mb-1">{{ $course->name }}</h5>
                        <div class="text-xs text-muted">{{ $course->duration }}</div>
                    </div>
                    <div class="col-auto">
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                <a class="dropdown-item" href="{{ route('admin.courses.edit', $course) }}">
                                    <i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Edit
                                </a>
                                <div class="dropdown-divider"></div>
                                <form action="{{ route('admin.courses.destroy', $course) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this course?')">
                                        <i class="fas fa-trash fa-sm fa-fw mr-2"></i>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Course Description --}}
                <p class="card-text small">{{ $course->description ?? 'No description provided.' }}</p>

            </div>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-around text-center">
                 <div>
                    <div class="font-weight-bold">{{ $course->batches_count }}</div>
                    <div class="text-xs text-muted">Batches</div>
                </div>
                 <div class="border-left mx-2"></div>
                <div>
                    <div class="font-weight-bold">{{ $course->students_count }}</div>
                    <div class="text-xs text-muted">Students</div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                <i class="fas fa-box-open fa-3x text-gray-300 my-3"></i>
                <p class="lead">No courses found.</p>
                <p>Get started by adding your first course.</p>
                <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">Add New Course</a>
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection