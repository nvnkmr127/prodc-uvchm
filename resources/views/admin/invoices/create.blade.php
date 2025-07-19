@extends('layouts.theme')
@section('title', 'Generate Batch Invoices')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Generate Invoices for a Batch</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <p>This tool will generate an invoice for every student in the selected batch, based on the batch's defined fee structure.</p>
        
        {{-- Display errors if they exist --}}
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.invoices.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="batch_id" class="form-label">Select Batch*</label>
                    <select name="batch_id" class="form-control" required>
                        <option value="">-- Select a Batch --</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ old('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }} ({{ $batch->course->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                {{-- REVISED: The academic period/term field has been removed for simplicity and correctness. --}}

                <div class="col-md-6 mb-3">
                    <label for="due_date" class="form-label">Payment Due Date*</label>
                    <input type="date" class="form-control" name="due_date" value="{{ old('due_date', date('Y-m-d')) }}" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Generate Invoices</button>
        </form>
    </div>
</div>
@endsection
