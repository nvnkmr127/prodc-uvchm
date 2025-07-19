@extends('layouts.theme')
@section('title', 'Add Academic Year')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Academic Year</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.academic-years.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="name">Year Name*</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., 2025-2026" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="start_date">Start Date*</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="end_date">End Date*</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
                <div class="col-md-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_current" value="1" id="is_current">
                        <label class="form-check-label" for="is_current">
                            Mark this as the current active academic year.
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Academic Year</button>
        </form>
    </div>
</div>
@endsection
