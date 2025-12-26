@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4">
        <h1 class="mt-4">Bulk Certificate Generation</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Certificate Generator</li>
        </ol>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Generate Bulk Certificates</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.certificate-generator.bulk') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <label for="batch_id" class="form-label font-weight-bold">Select Batch</label>
                                <select name="batch_id" id="batch_id" class="form-control form-select" required>
                                    <option value="">-- Select Batch --</option>
                                    @foreach($batches as $batch)
                                        <option value="{{ $batch->id }}">
                                            {{ $batch->name }} ({{ $batch->course->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Certificates will be generated for all active students in this
                                    batch.</small>
                            </div>

                            <div class="mb-4">
                                <label for="template_id" class="form-label font-weight-bold">Select Template</label>
                                <select name="template_id" id="template_id" class="form-control form-select" required>
                                    <option value="">-- Select Template --</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-lg btn-success">
                                    <i class="fas fa-file-archive me-2"></i> Generate & Download ZIP
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection