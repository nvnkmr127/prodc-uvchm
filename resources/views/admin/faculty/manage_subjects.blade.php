@extends('layouts.theme')
@section('title', 'Manage Subjects')
@section('content')
<h2 class="h3 mb-4 text-gray-800">Manage Subjects for: <strong>{{ $faculty->name }}</strong></h2>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.faculty.subjects.update', $faculty) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="mb-2">Assign subjects this faculty member can teach:</label>
                <div class="row">
                    @foreach ($allSubjects as $subject)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subjects[]" value="{{ $subject->id }}" id="subject{{ $subject->id }}"
                                       @if($faculty->subjects->contains($subject)) checked @endif >
                                <label class="form-check-label" for="subject{{ $subject->id }}">
                                    {{ $subject->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.faculty.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection