@extends('layouts.theme')
@section('title', 'Lab Group Allocation')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Lab Group Allocation</h1>

@if(session('success'))<div class="alert alert-success">{!! nl2br(e(session('success'))) !!}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

{{-- Form to select a batch --}}
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Select a Batch to Manage</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.lab-allocation.index') }}" method="GET">
            <div class="row align-items-end">
                <div class="col-md-10">
                    <label>Batch</label>
                    <select name="batch_id" class="form-control" required onchange="this.form.submit()">
                        <option value="">-- Select a Batch --</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $selectedBatch && $selectedBatch->id == $batch->id ? 'selected' : '' }}>
                                {{ $batch->course->name }} - {{ $batch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

@if($selectedBatch)
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h3 class="h5 mb-0 text-gray-800">Groups for: <strong>{{ $selectedBatch->name }}</strong></h3>
    <button class="btn btn-sm btn-success shadow-sm" data-toggle="modal" data-target="#automateModal"><i class="fas fa-robot fa-sm text-white-50"></i> Run Automated Allocation</button>
</div>

<div class="row">
    @forelse ($practicalGroups as $group)
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ $group->name }}</div>
                            <div class="mb-1"><strong>Lab:</strong> {{ $group->classroom->name }}</div>
                            <div class="mb-2"><strong>Students:</strong> {{ $group->students_count }} / {{ $group->classroom->capacity }}</div>
                            <a href="{{ route('admin.lab-allocation.group.manage', $group) }}" class="btn btn-warning btn-sm">Manage Manually</a>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">No practical groups created yet for this batch. Use the automate button to start.</div></div>
    @endforelse
</div>

<!-- Automate Allocation Modal -->
<div class="modal fade" id="automateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Automate Allocation</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p>This will assign all un-grouped students from <strong>{{ $selectedBatch->name }}</strong> into new practical groups.</p>
                <form action="{{ route('admin.lab-allocation.automate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
                    <div class="form-group mb-3">
                        <label for="course_term_id">For which Academic Term?</label>
                        <select name="course_term_id" class="form-control" required>
                            <option value="">-- Select a Term --</option>
                            @foreach($selectedBatch->course->terms->where('type', 'Academic') as $term)
                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Run Automation</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
