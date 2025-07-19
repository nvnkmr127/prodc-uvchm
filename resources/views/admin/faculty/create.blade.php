@extends('layouts.theme')
@section('title', 'Add New Faculty')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Faculty Member</h1>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.faculty.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" name="name" id="name" required value="{{ old('name') }}">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" id="email" required value="{{ old('email') }}">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" name="password" id="password" required>
            </div>
             <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" required>
            </div>

            <button type="submit" class="btn btn-primary">Save Faculty Member</button>
            <a href="{{ route('admin.faculty.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection