@extends('layouts.theme')
@section('title', 'Search Results')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Search Results for: "{{ $searchTerm }}"</h1>

<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Matching Students</h6></div>
    <div class="card-body">
        @forelse($students as $student)
            <p><a href="{{ route('admin.students.show', $student) }}">{{ $student->name }} ({{ $student->enrollment_number }})</a></p>
        @empty
            <p>No students found.</p>
        @endforelse
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Matching Courses</h6></div>
    <div class="card-body">
         @forelse($courses as $course)
            <p><a href="{{ route('admin.courses.show', $course) }}">{{ $course->name }}</a></p>
        @empty
            <p>No courses found.</p>
        @endforelse
    </div>
</div>
@endsection