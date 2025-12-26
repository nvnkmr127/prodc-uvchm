@extends('layouts.theme')

@section('title', 'Manage Subjects')

@section('content')
<h2 class="h3 mb-4 text-gray-800">
    Manage Subjects for: <strong>{{ $faculty->name }}</strong>
</h2>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.faculty.subjects.update', $faculty) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label class="mb-2">Assign subjects this faculty member can teach:</label>
                
                @if($allSubjects && $allSubjects->count() > 0)
                    <div class="row">
                        @foreach ($allSubjects as $subject)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        name="subjects[]" 
                                        value="{{ $subject->id }}" 
                                        id="subject{{ $subject->id }}"
                                        {{ $faculty->subjects && $faculty->subjects->contains($subject) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="subject{{ $subject->id }}">
                                        {{ $subject->name }}
                                        @if($subject->requires_lab)
                                            <span class="badge badge-info ml-1">Lab</span>
                                        @endif
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        No subjects found. Please create subjects first.
                    </div>
                @endif
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="{{ route('admin.faculty.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Display current assignments --}}
@if($faculty->subjects && $faculty->subjects->count() > 0)
    <div class="card shadow">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Currently Assigned Subjects
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($faculty->subjects as $subject)
                    <div class="col-md-4 mb-2">
                        <span class="badge badge-success">
                            {{ $subject->name }}
                            @if($subject->requires_lab)
                                <i class="fas fa-flask ml-1"></i>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection