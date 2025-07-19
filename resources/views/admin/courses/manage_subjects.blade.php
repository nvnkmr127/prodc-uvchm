<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects for Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.courses.index') }}">Back to Courses</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manage Subjects for: <strong>{{ $course->name }}</strong></h2>
        <hr>

        <form action="{{ route('admin.courses.subjects.update', $course) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="mb-2">Assign subjects to this course:</label>

                {{-- This will list all subjects as checkboxes --}}
                <div class="row">
                    @foreach ($allSubjects as $subject)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="subjects[]" 
                                       value="{{ $subject->id }}" 
                                       id="subject{{ $subject->id }}"
                                       {{-- This checks the box if the subject is already assigned --}}
                                       @if($course->subjects->contains($subject)) checked @endif
                                >
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
                <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>