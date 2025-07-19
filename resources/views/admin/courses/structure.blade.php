@extends('layouts.theme')
@section('title', 'Manage Course Structure')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Manage Structure for: <strong>{{ $course->name }}</strong></h1>

{{-- List of existing terms --}}
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Defined Terms</h6></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Sequence</th><th>Term Name</th><th>Type</th><th>Action</th></tr></thead>
            <tbody>
                @forelse ($course->terms as $term)
                <tr>
                    <td>{{ $term->sequence }}</td>
                    <td>{{ $term->name }}</td>
                    <td><span class="badge badge-{{ $term->type == 'Academic' ? 'info' : 'success' }}">{{ $term->type }}</span></td>
                    <td>
                        <form action="{{ route('admin.course-terms.destroy', $term) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center">No terms defined yet. Add one below.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Form to add a new term --}}
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Add New Term to Structure</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.courses.structure.store', $course) }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-5 mb-3"><label>Term Name</label><input type="text" name="name" class="form-control" placeholder="e.g., Semester 1, Industrial Training" required></div>
                <div class="col-md-4 mb-3"><label>Type</label><select name="type" class="form-control"><option value="Academic">Academic (College)</option><option value="Training">Training (Internship)</option></select></div>
                <div class="col-md-3 mb-3"><label>Sequence</label><input type="number" name="sequence" class="form-control" value="{{ $course->terms->count() + 1 }}" required></div>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Add Term</button>
        </form>
    </div>
</div>
@endsection