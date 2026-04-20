@extends('layouts.theme')
@section('title', 'Edit Academic Year')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Academic Year</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.academic-years.update', $academicYear) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="name">Year Name*</label>
                    <input type="text" name="name" class="form-control" value="{{ $academicYear->name }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="start_date">Start Date*</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $academicYear->start_date }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="end_date">End Date*</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $academicYear->end_date }}" required>
                </div>
                <div class="col-md-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_current" value="1" id="is_current" @if($academicYear->is_current) checked @endif>
                        <label class="form-check-label" for="is_current">
                            Mark this as the current active academic year.
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="auto_switch_enabled" value="1" id="auto_switch_enabled" @if($academicYear->auto_switch_enabled) checked @endif>
                        <label class="form-check-label" for="auto_switch_enabled">
                            Enable auto-switch (Automatically activate when start date is reached).
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Update Academic Year</button>
        </form>
    </div>
</div>
@endsection
