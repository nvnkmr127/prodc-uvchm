@extends('layouts.placement')

@section('title', 'Job Placements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Job Placements</h1>
            <p class="text-muted mb-0">Manage and export student placement records.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary mr-2" data-toggle="modal" data-target="#bulkUpdateModal" id="bulkUpdateButton" disabled>
                <i class="fas fa-edit mr-1"></i> Bulk Update (<span id="selectedCount">0</span>)
            </button>
            <a href="{{ route('placement-portal.export', request()->all()) }}" class="btn btn-success">
                <i class="fas fa-file-excel mr-1"></i> Export Report
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form action="{{ route('placement-portal.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Search students..." value="{{ request('search') }}">
                </div>
                <div class="form-group mr-2">
                    <select name="batch_id" class="form-control" style="max-width: 300px;">
                        <option value="">All Courses & Batches</option>
                        @foreach($courses as $course)
                            <optgroup label="{{ $course->name }}">
                                @foreach($course->batches as $batch)
                                    <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                        {{ $batch->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2">
                    <select name="placement_status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="Not Placed" {{ request('placement_status') == 'Not Placed' ? 'selected' : '' }}>Not Placed</option>
                        <option value="Training" {{ request('placement_status') == 'Training' ? 'selected' : '' }}>Training</option>
                        <option value="Internship" {{ request('placement_status') == 'Internship' ? 'selected' : '' }}>Internship</option>
                        <option value="Job" {{ request('placement_status') == 'Job' ? 'selected' : '' }}>Job</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('placement-portal.index') }}" class="btn btn-secondary ml-2">Clear</a>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 40px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAll">
                                    <label class="custom-control-label" for="selectAll"></label>
                                </div>
                            </th>
                            <th>Student</th>
                            <th>Contact</th>
                            <th>Batch</th>
                            <th>Placement Status</th>
                            <th>Company / Placed At</th>
                            <th>Designation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input student-checkbox" id="student{{ $student->id }}" value="{{ $student->id }}">
                                        <label class="custom-control-label" for="student{{ $student->id }}"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $student->photo_url }}" class="rounded-circle mr-2" width="40" height="40" style="object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&size=40&background=4e73df&color=fff&rounded=true&bold=true'">
                                        <div>
                                            <div class="font-weight-bold">{{ $student->name }}</div>
                                            <small class="text-muted">{{ $student->enrollment_number }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="fas fa-phone fa-sm mr-1"></i> {{ $student->student_mobile }}</div>
                                    @if($student->email)
                                    <div><i class="fas fa-envelope fa-sm mr-1"></i> {{ $student->email }}</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $student->batch->name ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $student->batch->course->name ?? '' }}</small>
                                </td>
                                <td>
                                    @if($student->placement_status == 'Job')
                                        <span class="badge badge-success">Job</span>
                                    @elseif($student->placement_status == 'Internship')
                                        <span class="badge badge-info">Internship</span>
                                    @elseif($student->placement_status == 'Training')
                                        <span class="badge badge-warning">Training</span>
                                    @else
                                        <span class="badge badge-secondary">Not Placed</span>
                                    @endif
                                </td>
                                <td>{{ $student->placed_at ?? '-' }}</td>
                                <td>{{ $student->placement_designation ?? '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#placementModal{{ $student->id }}">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No student records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Bulk Update Modal -->
            <div class="modal fade" id="bulkUpdateModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Bulk Update Placements</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="bulkUpdateForm" action="{{ route('placement-portal.bulk-update') }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    You are about to update <strong id="modalSelectedCount">0</strong> students.
                                </div>
                                <div id="hiddenInputsContainer"></div>
                                <div class="form-group">
                                    <label>Placement Status</label>
                                    <select name="placement_status" class="form-control" required>
                                        <option value="Not Placed">Not Placed</option>
                                        <option value="Training">Training</option>
                                        <option value="Internship">Internship</option>
                                        <option value="Job">Job</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Company / Placed At</label>
                                    <input type="text" name="placed_at" class="form-control" placeholder="e.g. Google, TCS, Local Hospital">
                                </div>
                                <div class="form-group">
                                    <label>Designation</label>
                                    <input type="text" name="placement_designation" class="form-control" placeholder="e.g. Software Engineer, Trainee">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Apply Bulk Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Individual Modals outside the table to prevent flickering -->
            @foreach($students as $student)
                <div class="modal fade" id="placementModal{{ $student->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Placement for {{ $student->name }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form action="{{ route('placement-portal.update', $student) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Placement Status</label>
                                        <select name="placement_status" class="form-control" required>
                                            <option value="Not Placed" {{ $student->placement_status == 'Not Placed' ? 'selected' : '' }}>Not Placed</option>
                                            <option value="Training" {{ $student->placement_status == 'Training' ? 'selected' : '' }}>Training</option>
                                            <option value="Internship" {{ $student->placement_status == 'Internship' ? 'selected' : '' }}>Internship</option>
                                            <option value="Job" {{ $student->placement_status == 'Job' ? 'selected' : '' }}>Job</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Company / Placed At</label>
                                        <input type="text" name="placed_at" class="form-control" value="{{ $student->placed_at }}" placeholder="e.g. Google, TCS, Local Hospital">
                                    </div>
                                    <div class="form-group">
                                        <label>Designation</label>
                                        <input type="text" name="placement_designation" class="form-control" value="{{ $student->placement_designation }}" placeholder="e.g. Software Engineer, Trainee">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAll');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox');
        const bulkUpdateButton = document.getElementById('bulkUpdateButton');
        const selectedCountSpan = document.getElementById('selectedCount');
        const modalSelectedCount = document.getElementById('modalSelectedCount');
        const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');
        const bulkUpdateForm = document.getElementById('bulkUpdateForm');

        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            selectedCountSpan.textContent = checkedCount;
            modalSelectedCount.textContent = checkedCount;
            
            if (checkedCount > 0) {
                bulkUpdateButton.removeAttribute('disabled');
            } else {
                bulkUpdateButton.setAttribute('disabled', 'disabled');
            }
        }

        selectAll.addEventListener('change', function () {
            studentCheckboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateSelectedCount();
        });

        studentCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                const allChecked = document.querySelectorAll('.student-checkbox:checked').length === studentCheckboxes.length;
                selectAll.checked = allChecked;
                updateSelectedCount();
            });
        });

        bulkUpdateForm.addEventListener('submit', function (e) {
            hiddenInputsContainer.innerHTML = '';
            document.querySelectorAll('.student-checkbox:checked').forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'student_ids[]';
                input.value = cb.value;
                hiddenInputsContainer.appendChild(input);
            });
        });
    });
</script>
@endsection
