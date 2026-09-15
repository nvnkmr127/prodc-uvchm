@extends('layouts.theme')

@section('title', 'My Mentees')

@push('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: none;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .student-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
    }
    .student-avatar-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #eaecf4;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #5a5c69;
        font-size: 1rem;
    }
    .btn-contact {
        padding: 3px 8px;
        font-size: 0.75rem;
        border-radius: 15px;
        font-weight: 600;
    }
    .progress-attendance {
        height: 8px;
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <!-- Header & Academic Year Banner -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                <i class="fas fa-user-graduate text-primary mr-2"></i>My Mentees
            </h1>
            <p class="text-muted mb-0 small">
                Monitor student attendance, track fee payments, and coordinate with parents for your assigned mentees.
                @if($academicYear)
                    <span class="badge badge-light border text-primary ml-1">
                        <i class="fas fa-calendar-alt"></i> {{ $academicYear->name }}
                    </span>
                @endif
            </p>
            @if($mentor->id === auth()->id())
                <form method="POST" action="{{ route('my-availability.toggle') }}" class="mt-2">
                    @csrf
                    @if(auth()->user()->is_available)
                        <span class="badge badge-success mr-1"><i class="fas fa-circle mr-1" style="font-size:.5rem;"></i>Available for allocations</span>
                        <button type="submit" class="btn btn-xs btn-outline-secondary">Mark unavailable</button>
                    @else
                        <span class="badge badge-warning mr-1"><i class="fas fa-pause mr-1"></i>Unavailable for new allocations</span>
                        <button type="submit" class="btn btn-xs btn-outline-success">Mark available</button>
                    @endif
                </form>
            @endif
        </div>

        @if($isElevatedUser && $allMentors->count() > 1)
            <div class="mt-3 mt-sm-0">
                <form method="GET" action="{{ route('my-mentees.index') }}" class="form-inline">
                    <label class="small font-weight-bold mr-2 text-muted">Viewing As Mentor:</label>
                    <select name="mentor_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()">
                        @foreach($allMentors as $m)
                            <option value="{{ $m->id }}" {{ $mentor->id == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->roles->pluck('name')->first() ?? 'Staff' }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Metric KPI Cards -->
    <div class="row mb-4">
        <!-- Total Mentees -->
        <div class="col-xl-3 col-md-6 mb-3">
            <a href="{{ route('my-mentees.index', array_filter(['mentor_id' => request('mentor_id')])) }}" class="text-decoration-none">
                <div class="card kpi-card shadow-sm border-left-primary h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Mentees</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $totalMentees }}</div>
                                <div class="text-xs text-muted mt-1">Assigned to {{ $mentor->name }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Average Attendance -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card shadow-sm border-left-{{ $avgAttendance >= 75 ? 'success' : 'warning' }} h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-{{ $avgAttendance >= 75 ? 'success' : 'warning' }} text-uppercase mb-1">
                                Avg Attendance
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $avgAttendance }}%</div>
                            <div class="progress progress-attendance mt-2">
                                <div class="progress-bar bg-{{ $avgAttendance >= 75 ? 'success' : 'warning' }}"
                                     role="progressbar" style="width: {{ min(100, $avgAttendance) }}%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Fees Outstanding -->
        <div class="col-xl-3 col-md-6 mb-3">
            <a href="{{ route('my-mentees.index', array_filter(['mentor_id' => request('mentor_id'), 'fee_filter' => 'pending'])) }}" class="text-decoration-none">
                <div class="card kpi-card shadow-sm border-left-danger h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Fee Dues to Follow Up</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">₹{{ number_format($totalOutstandingFees) }}</div>
                                <div class="text-xs text-danger mt-1"><i class="fas fa-filter mr-1"></i>Click to view {{ $menteesData->where('total_outstanding', '>', 0)->count() }} due students</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-invoice-dollar fa-2x text-danger opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Attendance Alerts (< 75%) -->
        <div class="col-xl-3 col-md-6 mb-3">
            <a href="{{ route('my-mentees.index', array_filter(['mentor_id' => request('mentor_id'), 'attendance_filter' => 'low'])) }}" class="text-decoration-none">
                <div class="card kpi-card shadow-sm border-left-warning h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Attendance Alerts</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $lowAttendanceCount }}</div>
                                <div class="text-xs text-warning mt-1"><i class="fas fa-filter mr-1"></i>Click to view below 75%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('my-mentees.index') }}">
                @if($isElevatedUser && request('mentor_id'))
                    <input type="hidden" name="mentor_id" value="{{ request('mentor_id') }}">
                @endif
                <div class="row align-items-center">
                    <!-- Batch Filter -->
                    <div class="col-md-2 mb-2 mb-md-0">
                        <select name="batch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Batches</option>
                            @foreach($batches as $b)
                                <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mentor Group Filter -->
                    <div class="col-md-2 mb-2 mb-md-0">
                        <select name="mentor_group_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Mentor Groups</option>
                            @foreach($mentorGroups as $mg)
                                <option value="{{ $mg->id }}" {{ request('mentor_group_id') == $mg->id ? 'selected' : '' }}>{{ $mg->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Attendance Filter -->
                    <div class="col-md-2 mb-2 mb-md-0">
                        <select name="attendance_filter" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Attendance Levels</option>
                            <option value="low" {{ request('attendance_filter') === 'low' ? 'selected' : '' }}>Below 75% (Needs Warning)</option>
                            <option value="good" {{ request('attendance_filter') === 'good' ? 'selected' : '' }}>75% and Above (Good)</option>
                        </select>
                    </div>

                    <!-- Fee Status Filter -->
                    <div class="col-md-2 mb-2 mb-md-0">
                        <select name="fee_filter" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Fee Statuses</option>
                            <option value="pending" {{ request('fee_filter') === 'pending' ? 'selected' : '' }}>Pending Fee Dues</option>
                            <option value="cleared" {{ request('fee_filter') === 'cleared' ? 'selected' : '' }}>Fees Cleared</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Search student, roll, or parent..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                                @if(request()->hasAny(['batch_id', 'mentor_group_id', 'attendance_filter', 'fee_filter', 'search']))
                                    <a href="{{ route('my-mentees.index', array_filter(['mentor_id' => request('mentor_id')])) }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Mentees Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-gray-800">
                <i class="fas fa-list text-primary mr-1"></i> Mentee Students Roster (All Departments)
                <span class="badge badge-light border ml-1">{{ $menteesData->count() }} Students</span>
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>Student</th>
                            <th>Mentor Group & Team</th>
                            <th>Department & Batch</th>
                            <th>Attendance</th>
                            <th>Fee Status</th>
                            <th>Parent Coordination</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menteesData as $item)
                            @php
                                $student = $item['student'];
                                $attPct = $item['attendance_percentage'];
                                $outstanding = $item['total_outstanding'];
                                $latestNote = $item['latest_note'];
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($student->photo)
                                            <img src="{{ asset('storage/' . $student->photo) }}" class="student-avatar mr-2" alt="{{ $student->name }}">
                                        @else
                                            <div class="student-avatar-placeholder mr-2">
                                                {{ strtoupper(substr($student->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('admin.students.show', $student->id) }}" class="font-weight-bold text-gray-900 text-decoration-none" title="Open Full Student Profile">
                                                {{ $student->name }}
                                                <i class="fas fa-external-link-alt fa-xs text-primary ml-1"></i>
                                            </a>
                                            <div class="small text-muted">Roll: {{ $student->enrollment_number ?? 'N/A' }}</div>
                                            @if($student->student_mobile)
                                                <div class="small text-muted">
                                                    <i class="fas fa-mobile-alt fa-xs mr-1"></i>{{ $student->student_mobile }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($student->mentorGroup)
                                        <span class="badge badge-primary font-weight-bold mb-1">
                                            <i class="fas fa-users mr-1"></i>{{ $student->mentorGroup->name }}
                                        </span>
                                    @else
                                        <span class="badge badge-light border text-muted mb-1">Direct Assignment</span>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.73rem; line-height: 1.3;">
                                        <div><i class="fas fa-chalkboard-teacher text-info mr-1"></i>Faculty: <strong>{{ $student->faculty?->name ?? $student->mentorGroup?->faculty?->name ?? '-' }}</strong></div>
                                        <div><i class="fas fa-user-shield text-success mr-1"></i>Counselor: <strong>{{ $student->counselor?->name ?? $student->mentorGroup?->counselor?->name ?? '-' }}</strong></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-gray-800 small">{{ $student->batch?->course?->name ?? 'N/A' }}</div>
                                    <span class="badge badge-light border text-muted small">{{ $student->batch?->name ?? 'N/A' }}</span>
                                </td>
                                <td style="min-width: 130px;">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="font-weight-bold small {{ $attPct < 75 ? 'text-danger' : 'text-success' }}">
                                            {{ $attPct }}%
                                        </span>
                                        @if($attPct < 75)
                                            <span class="badge badge-danger" style="font-size: 0.65rem;">Low</span>
                                        @else
                                            <span class="badge badge-success" style="font-size: 0.65rem;">Good</span>
                                        @endif
                                    </div>
                                    <div class="progress progress-attendance">
                                        <div class="progress-bar bg-{{ $attPct >= 75 ? 'success' : ($attPct >= 60 ? 'warning' : 'danger') }}"
                                             role="progressbar" style="width: {{ min(100, $attPct) }}%"></div>
                                    </div>
                                </td>
                                <td>
                                    @if($outstanding > 0)
                                        <div class="font-weight-bold text-danger">
                                            ₹{{ number_format($outstanding) }}
                                        </div>
                                        <div class="text-muted small">Paid: ₹{{ number_format($item['total_paid']) }}</div>
                                    @else
                                        <span class="badge badge-pill badge-success">
                                            <i class="fas fa-check-circle mr-1"></i> Fees Cleared
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">
                                        @if($student->father_name)
                                            <span class="text-muted">Father:</span> <strong>{{ $student->father_name }}</strong>
                                        @endif
                                        @php
                                            $parentName = $student->father_name ?? ($student->mother_name ?? 'Parent');
                                            $parentMobile = $student->father_mobile ?: $student->mother_mobile;
                                            $cleanMobile = preg_replace('/[^0-9]/', '', $parentMobile ?? '');
                                            if (strlen($cleanMobile) === 10) {
                                                $cleanMobile = '91' . $cleanMobile;
                                            }
                                            $waMsg = "Dear {$parentName}, greetings from {$mentor->name} (Mentor) from college regarding your ward {$student->name} (Roll: " . ($student->enrollment_number ?? 'N/A') . ").";
                                            if ($attPct < 75) {
                                                $waMsg .= " Please note that current attendance is {$attPct}%, which is below the mandatory 75%.";
                                            }
                                            if ($outstanding > 0) {
                                                $waMsg .= " Pending fee balance is Rs. " . number_format($outstanding) . ".";
                                            }
                                            $waMsg .= " Please contact me if you have any questions.";
                                        @endphp
                                        @if($student->father_mobile)
                                            <div class="mt-1">
                                                <a href="tel:{{ $student->father_mobile }}" class="btn btn-outline-primary btn-contact mr-1" title="Call Father">
                                                    <i class="fas fa-phone-alt mr-1"></i>{{ $student->father_mobile }}
                                                </a>
                                                <a href="https://wa.me/{{ $cleanMobile }}?text={{ urlencode($waMsg) }}" target="_blank" class="btn btn-outline-success btn-contact" title="WhatsApp Parent with pre-filled details">
                                                    <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                                                </a>
                                            </div>
                                        @elseif($student->mother_mobile)
                                            <div class="mt-1">
                                                <a href="tel:{{ $student->mother_mobile }}" class="btn btn-outline-primary btn-contact mr-1" title="Call Mother">
                                                    <i class="fas fa-phone-alt mr-1"></i>Mother: {{ $student->mother_mobile }}
                                                </a>
                                                <a href="https://wa.me/{{ $cleanMobile }}?text={{ urlencode($waMsg) }}" target="_blank" class="btn btn-outline-success btn-contact" title="WhatsApp Parent">
                                                    <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                                                </a>
                                            </div>
                                        @else
                                            <span class="text-muted">No parent phone recorded</span>
                                        @endif

                                        @if($latestNote)
                                            <div class="mt-1 text-muted" style="font-size: 0.72rem;">
                                                <i class="fas fa-comment-dots text-info mr-1"></i>
                                                <span class="font-weight-bold">{{ $latestNote->outcome ?? 'Note' }}:</span>
                                                {{ Str::limit($latestNote->notes, 35) }}
                                                ({{ $latestNote->created_at->format('M d') }})
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-success shadow-sm"
                                                onclick="openNoteModal('{{ $student->id }}', '{{ addslashes($student->name) }}', '{{ $student->father_mobile }}', '{{ $attPct }}', '{{ $outstanding }}')" title="Log Parent Call">
                                            <i class="fas fa-phone-volume"></i> Log Call
                                        </button>
                                        <a href="{{ route('my-mentees.show', $student->id) }}" class="btn btn-sm btn-outline-info shadow-sm" title="Mentee Interaction History">
                                            <i class="fas fa-comments"></i> Notes
                                        </a>
                                        <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-sm btn-primary shadow-sm" title="Full Student Profile">
                                            <i class="fas fa-user"></i> Profile
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-user-friends fa-3x text-gray-300 mb-3"></i>
                                    <p class="text-muted font-weight-bold mb-1">No mentees found matching your filters.</p>
                                    <p class="text-muted small">If you don't see your mentees, ask the Admin to assign students to you in Mentor Allocation.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Log Parent Call / Coordination Note Modal -->
<div class="modal fade" id="logNoteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="noteForm" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-phone-alt mr-1"></i> Log Parent Coordination
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                        <div>
                            <strong class="text-gray-900" id="noteStudentName"></strong>
                            <div class="small text-muted" id="noteFatherPhone"></div>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-pill badge-info" id="noteAttendanceBadge"></span>
                            <span class="badge badge-pill badge-danger" id="noteFeeBadge"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Call / Interaction Outcome</label>
                        <select name="outcome" class="form-control form-control-sm" required>
                            <option value="Fee Payment Promised">Fee Payment Promised</option>
                            <option value="Attendance Warning Given">Attendance Warning Given</option>
                            <option value="Parent Meeting Scheduled">Parent Meeting Scheduled</option>
                            <option value="Academic Progress Discussed">Academic Progress Discussed</option>
                            <option value="Call Not Answered / Switched Off">Call Not Answered / Switched Off</option>
                            <option value="General Check-in">General Check-in</option>
                            <option value="Resolved">Issue Resolved</option>
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label class="font-weight-bold small d-block mb-1">Quick Note Presets (Click to fill):</label>
                        <div class="btn-group-sm mb-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="insertQuickNote('Fee Payment Promised', 'Spoke with parent. Fee payment promised to be cleared within a week.')">💰 Fee Promised</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="insertQuickNote('Attendance Warning Given', 'Parent notified about low attendance. Parent assured student will attend classes regularly.')">⚠️ Attendance Warned</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="insertQuickNote('General Check-in', 'Parent informed student was unwell with medical reason, will submit note.')">🩺 Medical Leave</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="insertQuickNote('Call Not Answered / Switched Off', 'Tried calling parent, phone was ringing but unanswered. Will retry tomorrow.')">📵 Unanswered</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="insertQuickNote('Parent Meeting Scheduled', 'Parent agreed to visit college for a personal progress review.')">🤝 Meeting Fixed</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Discussion Details / Remarks</label>
                        <textarea id="noteRemarks" name="notes" class="form-control" rows="3" required
                                  placeholder="e.g., Spoke to father. He promised to clear pending fee by Friday. Discussed low attendance (65%) and advised sending student regularly."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Save Coordination Log
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openNoteModal(studentId, studentName, fatherPhone, attendancePct, feeDue) {
        document.getElementById('noteStudentName').innerText = studentName;
        document.getElementById('noteFatherPhone').innerText = fatherPhone ? `Father Mobile: ${fatherPhone}` : 'No Father Phone';
        document.getElementById('noteAttendanceBadge').innerText = `Attendance: ${attendancePct}%`;
        document.getElementById('noteFeeBadge').innerText = feeDue > 0 ? `Due: ₹${Number(feeDue).toLocaleString()}` : 'Fees Cleared';

        const form = document.getElementById('noteForm');
        form.action = `/my-mentees/${studentId}/notes`;

        $('#logNoteModal').modal('show');
    }

    function insertQuickNote(outcome, text) {
        $('#noteForm select[name="outcome"]').val(outcome);
        $('#noteRemarks').val(text).focus();
    }
</script>
@endpush
