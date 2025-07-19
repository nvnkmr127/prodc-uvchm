@extends('layouts.theme')
@section('title', 'ID Card Generator')

@section('content')
<h1 class="h3 mb-4 text-gray-800">ID Card Generator</h1>

<div class="card shadow mb-4 no-print">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Generate ID Cards</h6>
    </div>
    <div class="card-body">
        <form method="GET">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label>1. Select Batch</label>
                    <select name="batch_id" class="form-control" required>
                        <option value="">-- Select Batch --</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{$batch->course->name}} - {{$batch->name}}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label>2. Select ID Card Template</label>
                    <select name="template_id" class="form-control" required>
                        <option value="">-- Select Template --</option>
                        @foreach($templates as $template)
                             <option value="{{ $template->id }}" {{ request('template_id') == $template->id ? 'selected' : '' }}>
                                {{ $template->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(isset($students))
    <div class="d-flex justify-content-end mb-3 no-print">
        <button type="button" onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Print Generated Cards</button>
    </div>
    <div class="row printable">
        @forelse($students as $student)
            <div class="col-xl-4 col-md-6 mb-4">
                {{-- 
                    This dynamically renders the ID card by passing the student object
                    and the selected template's HTML content to our new helper function.
                --}}
                {!! \App\Http\Controllers\Admin\IdCardController::renderCard($student, $selectedTemplate->content) !!}
            </div>
        @empty
             <div class="col-12">
                <div class="alert alert-warning">No students found in the selected batch.</div>
            </div>
        @endforelse
    </div>
@endif
@endsection
