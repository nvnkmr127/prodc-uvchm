@extends('layouts.theme')

@section('title', 'Manage Students for ' . $batch->name)

@push('styles')
    <style>
        .student-select-box {
            height: 400px;
            width: 100%;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .student-select-box:focus {
            outline: none;
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .student-select-box option {
            padding: 8px 12px;
            border-bottom: 1px solid #f8f9fc;
            cursor: pointer;
        }

        .student-select-box option:hover {
            background-color: #f8f9fc;
        }

        .control-btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }

        .card-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
@endpush

@section('content')

    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Manage Students</h1>
            <p class="mb-0 text-muted">Batch: <strong>{{ $batch->name }}</strong> | Course: {{ $batch->course->name }}</p>
        </div>
        <a href="{{ route('admin.batches.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Batches
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form action="{{ route('admin.batches.syncStudents', $batch) }}" method="POST" id="manage-students-form">
        @csrf

        <div class="row">
            {{-- Left Column: Available Students --}}
            <div class="col-md-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary d-flex justify-content-between align-items-center">
                            Available Students
                            <span class="badge badge-primary badge-pill"
                                id="unassigned-count">{{ $unassignedStudents->count() }}</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0"><i
                                        class="fas fa-search text-gray-400"></i></span>
                            </div>
                            <input type="text" class="form-control border-left-0" id="unassigned-filter"
                                placeholder="Search by name or ID...">
                        </div>
                        <select multiple id="unassigned-students" class="student-select-box custom-scrollbar">
                            @foreach($unassignedStudents as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->enrollment_number }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-info-circle mr-1"></i> Click to select, Cmd/Ctrl+Click for multiple.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Middle Column: Controls --}}
            <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
                <button type="button" class="btn btn-primary control-btn shadow-sm" id="add-selected" title="Add Selected">
                    Add <i class="fas fa-chevron-right ml-1"></i>
                </button>
                <button type="button" class="btn btn-outline-primary control-btn" id="add-all" title="Add All">
                    Add All <i class="fas fa-angle-double-right ml-1"></i>
                </button>
                <hr class="w-100 my-3">
                <button type="button" class="btn btn-danger control-btn shadow-sm" id="remove-selected"
                    title="Remove Selected">
                    <i class="fas fa-chevron-left mr-1"></i> Remove
                </button>
                <button type="button" class="btn btn-outline-danger control-btn" id="remove-all" title="Remove All">
                    <i class="fas fa-angle-double-left mr-1"></i> Remove All
                </button>
            </div>

            {{-- Right Column: Assigned Students --}}
            <div class="col-md-5">
                <div class="card shadow mb-4 border-left-success">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-success d-flex justify-content-between align-items-center">
                            Students in Batch
                            <span class="badge badge-success badge-pill"
                                id="assigned-count">{{ $studentsInBatch->count() }}</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0"><i
                                        class="fas fa-search text-gray-400"></i></span>
                            </div>
                            <input type="text" class="form-control border-left-0" id="assigned-filter"
                                placeholder="Search assigned students...">
                        </div>
                        <select multiple name="assigned_student_ids[]" id="assigned-students"
                            class="student-select-box custom-scrollbar">
                            @foreach($studentsInBatch as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->enrollment_number }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-check-circle mr-1 text-success"></i> These students are currently assigned.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="card shadow mb-4">
            <div class="card-body text-right">
                <a href="{{ route('admin.batches.index') }}"
                    class="btn btn-light text-secondary font-weight-bold mr-2">Cancel</a>
                <button type="submit" class="btn btn-success font-weight-bold px-4">
                    <i class="fas fa-save mr-2"></i> Save Assignments
                </button>
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
                const selected = sourceList.find('option:selected');
                if (selected.length === 0) return;

                selected.fadeOut(150, function () {
                    $(this).appendTo(destList).fadeIn(150).prop('selected', false);
                    updateCounts();
                    sortList(destList);
                });
            }

            function moveAllOptions(sourceList, destList) {
                const options = sourceList.find('option');
                if (options.length === 0) return;

                options.fadeOut(150, function () {
                    $(this).appendTo(destList).fadeIn(150);
                    updateCounts();
                    sortList(destList);
                });
            }

            function updateCounts() {
                // Use timeout to ensure DOM update is complete if animation is involved, 
                // though here we are updating text directly which is instant.
                // But since we use fadeOut callback, we just update there.
                // For backup in case:
                setTimeout(() => {
                    $('#unassigned-count').text($('#unassigned-students option').length);
                    $('#assigned-count').text($('#assigned-students option').length);
                }, 200);
            }

            function filterList(input, list) {
                const filter = $(input).val().toUpperCase();
                list.find('option').each(function () {
                    const text = $(this).text().toUpperCase();
                    if (text.indexOf(filter) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            // Helper to sort options alphabetically
            function sortList(list) {
                const options = list.find('option');
                options.sort(function (a, b) {
                    return $(a).text().toUpperCase().localeCompare($(b).text().toUpperCase());
                });
                list.append(options);
            }

            // Event handlers
            $('#add-selected').on('click', () => moveOptions(unassignedList, assignedList));
            $('#add-all').on('click', () => moveAllOptions(unassignedList, assignedList));
            $('#remove-selected').on('click', () => moveOptions(assignedList, unassignedList));
            $('#remove-all').on('click', () => moveAllOptions(assignedList, unassignedList));

            $('#unassigned-filter').on('keyup', function () { filterList(this, unassignedList); });
            $('#assigned-filter').on('keyup', function () { filterList(this, assignedList); });

            // Double click to move
            unassignedList.on('dblclick', 'option', function () {
                moveOptions(unassignedList, assignedList);
            });
            assignedList.on('dblclick', 'option', function () {
                moveOptions(assignedList, unassignedList);
            });

            // Before submitting, select all options in the 'assigned' list
            $('#manage-students-form').on('submit', function () {
                $('#assigned-students option').prop('selected', true);
            });
        });
    </script>
@endpush