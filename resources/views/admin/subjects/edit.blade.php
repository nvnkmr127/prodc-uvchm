@extends('layouts.theme')
@section('title', 'Edit Subject')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Subject</h1>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.subjects.update', $subject) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group mb-3">
                <label for="name">Subject Name</label>
                <input type="text" name="name" class="form-control" value="{{ $subject->name }}" required>
            </div>

            <div class="form-group mb-3">
                <label for="code">Subject Code (Optional)</label>
                <input type="text" name="code" class="form-control" value="{{ $subject->code }}">
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="requires_lab" value="1" id="requires_lab"
                    {{-- This line pre-checks the box if the value is true --}}
                    @if($subject->requires_lab) checked @endif
                >
                <label class="form-check-label" for="requires_lab">
                    This subject requires a dedicated lab session.
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Update Subject</button>
            <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection