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
                        <div class="text-xs text-muted">{{ $course->duration ?? $course->duration_months . ' months' ?? 'Duration not set' }}</div>
                        @if($course->code)
                            <div class="text-xs text-muted">Code: {{ $course->code }}</div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink{{ $course->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink{{ $course->id }}">
                                {{-- Course Management --}}
                                <h6 class="dropdown-header">Course Management</h6>
                                <a class="dropdown-item" href="{{ route('admin.courses.edit', $course) }}">
                                    <i class="fas fa-edit fa-sm fa-fw mr-2 text-primary"></i>
                                    Edit Course
                                </a>
                                
                                {{-- NEW: Course Structure (Terms) --}}
                                <a class="dropdown-item" href="{{ route('admin.courses.structure.show', $course) }}">
                                    <i class="fas fa-sitemap fa-sm fa-fw mr-2 text-info"></i>
                                    Manage Structure (Terms)
                                </a>
                                
                                {{-- NEW: Subject Assignment --}}
                                <a class="dropdown-item" href="{{ route('admin.courses.subjects.edit', $course) }}">
                                    <i class="fas fa-book fa-sm fa-fw mr-2 text-success"></i>
                                    Assign Subjects
                                </a>
                                
                                <div class="dropdown-divider"></div>
                                
                                {{-- Batch Management --}}
                                <h6 class="dropdown-header">Batch Management</h6>
                                <a class="dropdown-item" href="{{ route('admin.batches.create') }}?course_id={{ $course->id }}">
                                    <i class="fas fa-users fa-sm fa-fw mr-2 text-info"></i>
                                    Create New Batch
                                </a>
                                
                                @if($course->batches->count() > 0)
                                    <a class="dropdown-item" href="{{ route('admin.batches.index') }}?course_id={{ $course->id }}">
                                        <i class="fas fa-list fa-sm fa-fw mr-2 text-secondary"></i>
                                        View All Batches ({{ $course->batches->count() }})
                                    </a>
                                @endif
                                
                                <div class="dropdown-divider"></div>
                                
                                {{-- Lab & Scheduling --}}
                                <h6 class="dropdown-header">Lab & Scheduling</h6>
                                
                                {{-- NEW: Lab Allocation --}}
                                @if($course->batches->count() > 0)
                                    <a class="dropdown-item" href="{{ route('admin.lab-allocation.index') }}?course_id={{ $course->id }}">
                                        <i class="fas fa-flask fa-sm fa-fw mr-2 text-warning"></i>
                                        Lab Allocation
                                    </a>
                                @else
                                    <span class="dropdown-item-text text-muted">
                                        <i class="fas fa-flask fa-sm fa-fw mr-2"></i>
                                        Lab Allocation (No batches)
                                    </span>
                                @endif
                                
                                {{-- NEW: Timetable Management --}}
                                <a class="dropdown-item" href="{{ route('admin.timetable.hub') }}?course_id={{ $course->id }}">
                                    <i class="fas fa-calendar-alt fa-sm fa-fw mr-2 text-purple"></i>
                                    Timetable Management
                                </a>
                                
                                <div class="dropdown-divider"></div>
                                
                                {{-- Financial Management --}}
                                <h6 class="dropdown-header">Financial</h6>
                                
                                {{-- Fee Structures --}}
                                <a class="dropdown-item" href="{{ route('admin.fee-structures.index') }}?course_id={{ $course->id }}">
                                    <i class="fas fa-money-bill-wave fa-sm fa-fw mr-2 text-success"></i>
                                    Fee Structures
                                </a>
                                
                                <div class="dropdown-divider"></div>
                                
                                {{-- Danger Zone --}}
                                <form action="{{ route('admin.courses.destroy', $course) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this course? This will also delete all associated batches, students, and data.')">
                                        <i class="fas fa-trash fa-sm fa-fw mr-2"></i>
                                        Delete Course
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Course Description --}}
                <p class="card-text small text-muted mb-3">{{ $course->description ?? 'No description provided.' }}</p>

                {{-- Quick Status Overview --}}
                <div class="row text-center small mb-2">
                    <div class="col-4">
                        <div class="text-xs text-muted">Terms</div>
                        <div class="font-weight-bold text-info">{{ $course->terms->count() ?? 0 }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-xs text-muted">Subjects</div>
                        <div class="font-weight-bold text-success">{{ $course->subjects->count() ?? 0 }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-xs text-muted">Lab Groups</div>
                        <div class="font-weight-bold text-warning">
                            {{ $course->batches->sum(function($batch) { return $batch->practicalGroups->count(); }) }}
                        </div>
                    </div>
                </div>

                {{-- Quick Action Buttons --}}
                <div class="row">
                    <div class="col-12">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            {{-- Structure Management --}}
                            <a href="{{ route('admin.courses.structure.show', $course) }}" 
                               class="btn btn-outline-info btn-sm" 
                               title="Manage Course Structure">
                                <i class="fas fa-sitemap"></i>
                            </a>
                            
                            {{-- Subject Assignment --}}
                            <a href="{{ route('admin.courses.subjects.edit', $course) }}" 
                               class="btn btn-outline-success btn-sm" 
                               title="Assign Subjects">
                                <i class="fas fa-book"></i>
                            </a>
                            
                            {{-- Lab Allocation --}}
                            @if($course->batches->count() > 0)
                                <a href="{{ route('admin.lab-allocation.index') }}?course_id={{ $course->id }}" 
                                   class="btn btn-outline-warning btn-sm" 
                                   title="Lab Allocation">
                                    <i class="fas fa-flask"></i>
                                </a>
                            @else
                                <button class="btn btn-outline-secondary btn-sm" disabled title="Create batches first">
                                    <i class="fas fa-flask"></i>
                                </button>
                            @endif
                            
                            {{-- Timetable --}}
                            <a href="{{ route('admin.timetable.hub') }}?course_id={{ $course->id }}" 
                               class="btn btn-outline-primary btn-sm" 
                               title="Timetable Management">
                                <i class="fas fa-calendar-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
            
            {{-- Enhanced Footer with Better Stats --}}
            <div class="card-footer bg-transparent border-0">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="font-weight-bold text-primary">{{ $course->batches_count ?? $course->batches->count() }}</div>
                        <div class="text-xs text-muted">Batches</div>
                    </div>
                    <div class="col-4">
                        <div class="font-weight-bold text-success">{{ $course->students_count ?? $course->batches->sum(function($batch) { return $batch->students->count(); }) }}</div>
                        <div class="text-xs text-muted">Students</div>
                    </div>
                    <div class="col-4">
                        @php
                            $activeBatches = $course->batches->where('status', 'active')->count();
                        @endphp
                        <div class="font-weight-bold {{ $activeBatches > 0 ? 'text-info' : 'text-muted' }}">{{ $activeBatches }}</div>
                        <div class="text-xs text-muted">Active</div>
                    </div>
                </div>
                
                {{-- Course Completion Status --}}
                <div class="mt-2">
                    @php
                        $hasTerms = $course->terms->count() > 0;
                        $hasSubjects = $course->subjects->count() > 0;
                        $hasBatches = $course->batches->count() > 0;
                        $setupComplete = $hasTerms && $hasSubjects && $hasBatches;
                    @endphp
                    
                    <div class="progress" style="height: 4px;">
                        @php
                            $completionPercentage = 0;
                            if($hasTerms) $completionPercentage += 33;
                            if($hasSubjects) $completionPercentage += 33;
                            if($hasBatches) $completionPercentage += 34;
                        @endphp
                        <div class="progress-bar bg-{{ $setupComplete ? 'success' : ($completionPercentage > 50 ? 'warning' : 'danger') }}" 
                             style="width: {{ $completionPercentage }}%"></div>
                    </div>
                    <div class="text-xs text-center mt-1">
                        @if($setupComplete)
                            <span class="text-success"><i class="fas fa-check"></i> Setup Complete</span>
                        @else
                            <span class="text-warning">{{ $completionPercentage }}% Setup</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                <i class="fas fa-graduation-cap fa-3x text-gray-300 my-3"></i>
                <h3 class="text-gray-600">No courses found</h3>
                <p class="lead text-muted">Get started by creating your first course</p>
                <p class="text-muted">Courses are the foundation of your academic management system. You'll need them to create batches, manage students, and generate timetables.</p>
                <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus mr-2"></i>Create Your First Course
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>

{{-- Quick Stats Overview (if there are courses) --}}
@if($courses->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-bar mr-2"></i>System Overview
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2">
                        <div class="h4 mb-0 font-weight-bold text-primary">{{ $courses->count() }}</div>
                        <div class="text-xs text-uppercase text-muted">Total Courses</div>
                    </div>
                    <div class="col-md-2">
                        <div class="h4 mb-0 font-weight-bold text-info">{{ $courses->sum(function($c) { return $c->batches->count(); }) }}</div>
                        <div class="text-xs text-uppercase text-muted">Total Batches</div>
                    </div>
                    <div class="col-md-2">
                        <div class="h4 mb-0 font-weight-bold text-success">{{ $courses->sum(function($c) { return $c->subjects->count(); }) }}</div>
                        <div class="text-xs text-uppercase text-muted">Assigned Subjects</div>
                    </div>
                    <div class="col-md-2">
                        <div class="h4 mb-0 font-weight-bold text-warning">{{ $courses->sum(function($c) { return $c->terms->count(); }) }}</div>
                        <div class="text-xs text-uppercase text-muted">Course Terms</div>
                    </div>
                    <div class="col-md-2">
                        @php
                            $totalLabGroups = $courses->sum(function($course) {
                                return $course->batches->sum(function($batch) {
                                    return $batch->practicalGroups->count();
                                });
                            });
                        @endphp
                        <div class="h4 mb-0 font-weight-bold text-purple">{{ $totalLabGroups }}</div>
                        <div class="text-xs text-uppercase text-muted">Lab Groups</div>
                    </div>
                    <div class="col-md-2">
                        @php
                            $fullySetupCourses = $courses->filter(function($course) {
                                return $course->terms->count() > 0 && 
                                       $course->subjects->count() > 0 && 
                                       $course->batches->count() > 0;
                            })->count();
                        @endphp
                        <div class="h4 mb-0 font-weight-bold text-success">{{ $fullySetupCourses }}</div>
                        <div class="text-xs text-uppercase text-muted">Setup Complete</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
.text-purple {
    color: #6f42c1 !important;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.dropdown-header {
    font-size: 0.7rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
}

.card:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.progress {
    border-radius: 10px;
}

.card-footer {
    background-color: rgba(0,0,0,.03) !important;
}

.dropdown-item:hover {
    background-color: #f8f9fc;
}

.dropdown-item.text-danger:hover {
    background-color: #f8d7da;
    color: #721c24 !important;
}
</style>
@endpush