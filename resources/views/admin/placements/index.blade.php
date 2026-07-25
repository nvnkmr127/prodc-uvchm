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
                                    <button type="button" class="btn btn-sm btn-primary shadow-sm rounded-pill px-3" 
                                        data-toggle="modal" 
                                        data-target="#placementUpdateModal"
                                        data-id="{{ $student->id }}"
                                        data-name="{{ e($student->name) }}"
                                        data-enrollment="{{ $student->enrollment_number }}"
                                        data-photo="{{ $student->photo_url }}"
                                        data-batch="{{ $student->batch->name ?? 'N/A' }}"
                                        data-course="{{ $student->batch->course->name ?? '' }}"
                                        data-status="{{ $student->placement_status ?? 'Not Placed' }}"
                                        data-placed-at="{{ $student->placed_at ?? '' }}"
                                        data-designation="{{ $student->placement_designation ?? '' }}"
                                        data-update-url="{{ route('placement-portal.update', $student) }}">
                                        <i class="fas fa-edit mr-1"></i> Update
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

            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modern Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modern-modal-content">
            <div class="modern-modal-header header-bulk">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-users-cog text-info"></i> Bulk Update Placements
                    </h5>
                    <div class="modal-subtitle">Batch update status and placement records</div>
                </div>
                <button type="button" class="close-modern" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="bulkUpdateForm" action="{{ route('placement-portal.bulk-update') }}" method="POST">
                @csrf
                <div class="modal-body modern-modal-body">
                    <div class="student-quick-info">
                        <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-circle mr-2" style="width: 42px; height: 42px; flex-shrink: 0;">
                            <i class="fas fa-user-check fa-lg"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-dark">Selected Students</div>
                            <small class="text-muted"><strong id="modalSelectedCount" class="text-primary font-weight-bold">0</strong> students will be updated simultaneously.</small>
                        </div>
                    </div>

                    <div id="hiddenInputsContainer"></div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1">Placement Status</label>
                        <div class="status-pills-container" id="bulkStatusPills">
                            <button type="button" class="status-pill-btn active" data-status="Not Placed">
                                <i class="fas fa-minus-circle"></i> Not Placed
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Training">
                                <i class="fas fa-chalkboard-teacher"></i> Training
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Internship">
                                <i class="fas fa-briefcase"></i> Internship
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Job">
                                <i class="fas fa-check-circle"></i> Job
                            </button>
                        </div>
                        <input type="hidden" name="placement_status" id="bulk_placement_status" value="Not Placed" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1"><i class="fas fa-building text-muted mr-1"></i> Company / Placed At</label>
                        <input type="text" name="placed_at" class="form-control" style="border-radius: 10px;" placeholder="e.g. Google, TCS, Local Hospital">
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark mb-1"><i class="fas fa-user-tag text-muted mr-1"></i> Designation</label>
                        <input type="text" name="placement_designation" class="form-control" style="border-radius: 10px;" placeholder="e.g. Software Engineer, Trainee">
                    </div>
                </div>
                <div class="modern-modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-save mr-1"></i> Apply Bulk Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modern Single Student Placement Update Modal -->
<div class="modal fade" id="placementUpdateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modern-modal-content">
            <div class="modern-modal-header">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-user-graduate"></i> Update Placement Record
                    </h5>
                    <div class="modal-subtitle">Modify placement status and company details</div>
                </div>
                <button type="button" class="close-modern" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="singlePlacementForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body modern-modal-body">
                    <!-- Student Summary Card -->
                    <div class="student-quick-info">
                        <img id="modalStudentPhoto" src="" alt="Student Photo" onerror="this.src='https://ui-avatars.com/api/?name=Student&size=40&background=4e73df&color=fff&rounded=true&bold=true'">
                        <div class="flex-grow-1">
                            <div id="modalStudentName" class="font-weight-bold text-dark h6 mb-0"></div>
                            <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                <span id="modalStudentEnrollment"></span> &bull; 
                                <span id="modalStudentBatch"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1">Placement Status</label>
                        <div class="status-pills-container" id="singleStatusPills">
                            <button type="button" class="status-pill-btn" data-status="Not Placed">
                                <i class="fas fa-minus-circle"></i> Not Placed
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Training">
                                <i class="fas fa-chalkboard-teacher"></i> Training
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Internship">
                                <i class="fas fa-briefcase"></i> Internship
                            </button>
                            <button type="button" class="status-pill-btn" data-status="Job">
                                <i class="fas fa-check-circle"></i> Job
                            </button>
                        </div>
                        <input type="hidden" name="placement_status" id="single_placement_status" value="Not Placed" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1"><i class="fas fa-building text-muted mr-1"></i> Company / Placed At</label>
                        <input type="text" name="placed_at" id="modalPlacedAt" class="form-control" style="border-radius: 10px;" placeholder="e.g. Google, TCS, Local Hospital">
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark mb-1"><i class="fas fa-user-tag text-muted mr-1"></i> Designation</label>
                        <input type="text" name="placement_designation" id="modalDesignation" class="form-control" style="border-radius: 10px;" placeholder="e.g. Software Engineer, Trainee">
                    </div>
                </div>
                <div class="modern-modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm"><i class="fas fa-save mr-1"></i> Save Changes</button>
                </div>
            </form>
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
            if (selectedCountSpan) selectedCountSpan.textContent = checkedCount;
            if (modalSelectedCount) modalSelectedCount.textContent = checkedCount;
            
            if (bulkUpdateButton) {
                if (checkedCount > 0) {
                    bulkUpdateButton.removeAttribute('disabled');
                } else {
                    bulkUpdateButton.setAttribute('disabled', 'disabled');
                }
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                studentCheckboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateSelectedCount();
            });
        }

        studentCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                const allChecked = document.querySelectorAll('.student-checkbox:checked').length === studentCheckboxes.length;
                if (selectAll) selectAll.checked = allChecked;
                updateSelectedCount();
            });
        });

        if (bulkUpdateForm) {
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
        }

        // Status Pill Selection Helper
        function setupStatusPills(containerId, hiddenInputId) {
            const container = document.getElementById(containerId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!container || !hiddenInput) return;

            const pillButtons = container.querySelectorAll('.status-pill-btn');
            pillButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    pillButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    hiddenInput.value = this.getAttribute('data-status');
                });
            });
        }

        function setStatusPillValue(containerId, hiddenInputId, statusValue) {
            const container = document.getElementById(containerId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!container || !hiddenInput) return;

            const targetStatus = statusValue || 'Not Placed';
            hiddenInput.value = targetStatus;

            const pillButtons = container.querySelectorAll('.status-pill-btn');
            pillButtons.forEach(btn => {
                if (btn.getAttribute('data-status') === targetStatus) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        setupStatusPills('bulkStatusPills', 'bulk_placement_status');
        setupStatusPills('singleStatusPills', 'single_placement_status');

        // Dynamic Single Student Modal Trigger
        $('#placementUpdateModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            if (!button.length) return;

            const modal = $(this);
            const form = modal.find('#singlePlacementForm');

            form.attr('action', button.data('update-url'));
            modal.find('#modalStudentName').text(button.data('name'));
            modal.find('#modalStudentEnrollment').text(button.data('enrollment') || 'No Reg #');
            modal.find('#modalStudentBatch').text((button.data('batch') || '') + (button.data('course') ? ' - ' + button.data('course') : ''));
            modal.find('#modalStudentPhoto').attr('src', button.data('photo'));
            modal.find('#modalPlacedAt').val(button.data('placed-at') || '');
            modal.find('#modalDesignation').val(button.data('designation') || '');

            setStatusPillValue('singleStatusPills', 'single_placement_status', button.data('status'));
        });
    });
</script>
@endsection

