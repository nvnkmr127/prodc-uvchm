@extends('layouts.theme')

@section('content')
    <div class="container d-flex flex-column justify-content-center align-items-center min-vh-100">
        <div class="text-center">
            <h1 class="display-1 fw-bold text-warning">403</h1>
            <h2 class="h4 mb-4">Forbidden</h2>
            <p class="text-muted mb-4">You do not have permission to access this resource.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
        </div>
    </div>
@endsection