@extends('layouts.theme')

@section('content')
    <div class="container d-flex flex-column justify-content-center align-items-center min-vh-100">
        <div class="text-center">
            <h1 class="display-1 fw-bold text-danger">500</h1>
            <h2 class="h4 mb-4">Server Error</h2>
            <p class="text-muted mb-4">Something went wrong on our servers. We are working to fix it.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
        </div>
    </div>
@endsection