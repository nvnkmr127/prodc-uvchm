@extends('layouts.theme')

@section('title', 'Manage Students for ' . $batch->name)

@push('styles')
<style>
    .dual-listbox {
        display: flex;
        gap: 1rem;
    }
    .dual-listbox .list-box {
        flex: 1;
        border: 1px solid #d1d3e2;
        border-radius: .35rem;
        padding: 1rem;
    }
    .dual-listbox .list-box .list-title {
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .dual-listbox .list-box .filter-input {
        margin-bottom: 0.5rem;
    }
    .dual-listbox .list-box select {
        width: 100%;
        height: 300px;
        border-radius: .35rem;
        border-color: #d1d3e2;
    }
    .dual-listbox .controls {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.5rem;
    }
</style>
@endpush

@section('content')

{{-- 1. Page Header --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Students for: <strong>{{ $batch->name }}</strong></h1>
    <a href="{{ route('admin.batches.index') }}" class="btn btn-sm btn-light shadow-sm"><i class="fas fa-arrow-left fa-sm text-gray-600"></i> Back to Batch List</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('admin.batches.syncStudents', $batch) }}" method="POST" id="manage-students-form">
    @csrf
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Student Assignment</h6>
        </div>
        <div class="card-body">
            <div class="dual-listbox">
                <!-- Available Students List -->
                <div class="list-box">
                    <div class="list-title">Available Students (<span id="unassigned-count">{{ $unassignedStudents->count() }}</span>)</div>
                    <input type="text" class="form-control form-control-sm filter-input" id="unassigned-filter" placeholder="Search available...">
                    <select multiple id="unassigned-students">
                        @foreach($unassignedStudents as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->enrollment_number }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Controls -->
                <div class="controls">
                    <button type="button" class="btn btn-secondary" id="add-selected" title="Add Selected">&gt;</button>
                    <button type="button" class="btn btn-secondary" id="add-all" title="Add All">&gt;&gt;</button>
                    <button type="button" class="btn btn-secondary" id="remove-selected" title="Remove Selected">&lt;</button>
                    <button type="button" class="btn btn-secondary" id="remove-all" title="Remove All">&lt;&lt;</button>
                </div>

                <!-- Assigned Students List -->
                <div class="list-box">
                    <div class="list-title">Students in this Batch (<span id="assigned-count">{{ $studentsInBatch->count() }}</span>)</div>
                    <input type="text" class="form-control form-control-sm filter-input" id="assigned-filter" placeholder="Search assigned...">
                    <select multiple name="assigned_student_ids[]" id="assigned-students">
                        @foreach($studentsInBatch as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->enrollment_number }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('admin.batches.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const unassignedList = $('#unassigned-students');
    const assignedList = $('#assigned-students');

    function moveOptions(sourceList, destList) {
        sourceList.find('option:selected').appendTo(destList);
        updateCounts();
    }

    function moveAllOptions(sourceList, destList) {
        sourceList.find('option').appendTo(destList);
        updateCounts();
    }
    
    function updateCounts() {
        $('#unassigned-count').text($('#unassigned-students option').length);
        $('#assigned-count').text($('#assigned-students option').length);
    }

    function filterList(input, list) {
        const filter = $(input).val().toUpperCase();
        list.find('option').each(function () {
            const text = $(this).text().toUpperCase();
            $(this).toggle(text.indexOf(filter) > -1);
        });
    }

    // Event handlers
    $('#add-selected').on('click', () => moveOptions(unassignedList, assignedList));
    $('#add-all').on('click', () => moveAllOptions(unassignedList, assignedList));
    $('#remove-selected').on('click', () => moveOptions(assignedList, unassignedList));
    $('#remove-all').on('click', () => moveAllOptions(assignedList, unassignedList));

    $('#unassigned-filter').on('keyup', function() { filterList(this, unassignedList); });
    $('#assigned-filter').on('keyup', function() { filterList(this, assignedList); });

    // Before submitting, select all options in the 'assigned' list
    $('#manage-students-form').on('submit', function () {
        $('#assigned-students option').prop('selected', true);
    });
});
</script>
@endpush
