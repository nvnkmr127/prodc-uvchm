@extends('layouts.theme')

@section('title', $student->name . ' - Mentee Details')

@push('styles')
<style>
    .mentee-header-card {
        border-radius: 12px;
        background: #fff;
        border-left: 5px solid #4e73df;
    }
    .profile-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #eaecf4;
    }
    .profile-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #eaecf4;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        color: #5a5c69;
    }
    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 25px;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: -25px;
        width: 2px;
        background: #e3e6f0;
    }
    .timeline-item:last-child::before {
        bottom: 0;
    }
    .timeline-dot {
        position: absolute;
        left: 0;
        top: 2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #4e73df;
        border: 3px solid #fff;
        box-shadow: 0 0 0 1px #4e73df;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <!-- Top Navigation -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <a href="{{ route('my-mentees.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="fas fa-arrow-left mr-1"></i> Back to My Mentees
            </a>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                Mentee Profile: {{ $student->name }}
            </h1>
        </div>
        <div class="d-flex align-items-center">
            <form method="POST" action="{{ route('my-mentees.escalate', $student->id) }}" class="d-inline mr-2">
                @csrf
                @if($student->needs_escalation)
                    <button type="submit" class="btn btn-sm btn-danger shadow-sm" title="Clear escalation flag">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Escalated — Clear
                    </button>
                @else
                    <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm" title="Flag this mentee for escalation">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Flag Escalation
                    </button>
                @endif
            </form>
            <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-sm btn-primary shadow-sm mr-2" title="Open Full Student Record">
                <i class="fas fa-user-circle mr-1"></i> Full Student Profile
            </a>
            @if($academicYear)
                <span class="badge badge-light border text-primary p-2">
                    <i class="fas fa-calendar-alt mr-1"></i> {{ $academicYear->name }}
                </span>
            @endif
        </div>
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

    <!-- Profile Overview Card -->
    <div class="card shadow-sm mentee-header-card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    @if($student->photo)
                        <img src="{{ asset('storage/' . $student->photo) }}" class="profile-photo shadow-sm" alt="{{ $student->name }}">
                    @else
                        <div class="profile-placeholder shadow-sm">
                            {{ strtoupper(substr($student->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="col">
                    <div class="d-flex align-items-center justify-content-between">
                        <h4 class="font-weight-bold text-gray-900 mb-1">{{ $student->name }}</h4>
                        <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Full ERP Profile">
                            <i class="fas fa-external-link-alt mr-1"></i> Full ERP Profile
                        </a>
                    </div>
                    <div class="text-muted small mb-2">
                        <span>Roll: <strong>{{ $student->enrollment_number ?? 'N/A' }}</strong></span>
                        <span class="mx-2">&bull;</span>
                        <span>Department/Course: <strong>{{ $student->batch?->course?->name ?? 'N/A' }}</strong></span>
                        <span class="mx-2">&bull;</span>
                        <span>Batch: <strong>{{ $student->batch?->name ?? 'N/A' }}</strong></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge badge-pill badge-{{ $student->status === 'active' ? 'success' : 'secondary' }} mr-2">
                            {{ ucfirst($student->status) }}
                        </span>
                        @if($student->mentorGroup)
                            <span class="badge badge-pill badge-primary mr-2" title="Mentor Group">
                                <i class="fas fa-users mr-1"></i> Group: {{ $student->mentorGroup->name }}
                            </span>
                        @endif
                        <span class="badge badge-pill badge-info mr-2" title="Faculty Mentor">
                            <i class="fas fa-chalkboard-teacher mr-1"></i> Faculty: {{ $student->faculty?->name ?? $student->mentorGroup?->faculty?->name ?? 'Unassigned' }}
                        </span>
                        <span class="badge badge-pill badge-success mr-2" title="Counselor">
                            <i class="fas fa-user-shield mr-1"></i> Counselor: {{ $student->counselor?->name ?? $student->mentorGroup?->counselor?->name ?? 'Unassigned' }}
                        </span>
                        @if($student->village)
                            <span class="badge badge-pill badge-light border"><i class="fas fa-map-marker-alt mr-1"></i>{{ $student->village }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3 Core Summary Cards (Attendance, Fees, Parent Contacts) -->
    <div class="row mb-4">
        <!-- Attendance Metric -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-left-{{ $attendancePct >= 75 ? 'success' : 'danger' }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="font-weight-bold text-xs text-uppercase text-{{ $attendancePct >= 75 ? 'success' : 'danger' }}">
                            Attendance Record
                        </span>
                        <span class="badge badge-{{ $attendancePct >= 75 ? 'success' : 'danger' }}">
                            {{ $attendancePct >= 75 ? 'Good' : 'Needs Follow-up' }}
                        </span>
                    </div>
                    <div class="h3 font-weight-bold text-gray-800 mb-2">{{ $attendancePct }}%</div>
                    <div class="progress" style="height: 10px; border-radius: 5px;">
                        <div class="progress-bar bg-{{ $attendancePct >= 75 ? 'success' : ($attendancePct >= 60 ? 'warning' : 'danger') }}"
                             role="progressbar" style="width: {{ min(100, $attendancePct) }}%"></div>
                    </div>
                    <div class="small text-muted mt-2">Calculated for the active academic year</div>
                </div>
            </div>
        </div>

        <!-- Fee Metric -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-left-{{ ($financial['total_outstanding'] ?? 0) > 0 ? 'danger' : 'success' }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="font-weight-bold text-xs text-uppercase text-{{ ($financial['total_outstanding'] ?? 0) > 0 ? 'danger' : 'success' }}">
                            Fee Status
                        </span>
                        <span class="badge badge-{{ ($financial['total_outstanding'] ?? 0) > 0 ? 'danger' : 'success' }}">
                            {{ ($financial['total_outstanding'] ?? 0) > 0 ? 'Outstanding Due' : 'Paid in Full' }}
                        </span>
                    </div>
                    <div class="h3 font-weight-bold text-gray-800 mb-1">
                        ₹{{ number_format($financial['total_outstanding'] ?? 0) }}
                        <span class="text-xs font-weight-normal text-muted">pending</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2">
                        <span>Total Fees: ₹{{ number_format($financial['total_fees'] ?? 0) }}</span>
                        <span>Paid: ₹{{ number_format($financial['total_paid'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parent Contact -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body">
                    <span class="font-weight-bold text-xs text-uppercase text-primary d-block mb-2">
                        Parent & Student Contacts
                    </span>
                    @php
                        $parentName = $student->father_name ?? ($student->mother_name ?? 'Parent');
                        $mentorName = auth()->user()->name;
                        $waMsg = "Dear {$parentName}, greetings from {$mentorName} (Mentor) from college regarding your ward {$student->name} (Roll: " . ($student->enrollment_number ?? 'N/A') . ").";
                        if ($attendancePct < 75) {
                            $waMsg .= " Please note that current attendance is {$attendancePct}%, which is below the mandatory 75%.";
                        }
                        if (($financial['total_outstanding'] ?? 0) > 0) {
                            $waMsg .= " Pending fee balance is Rs. " . number_format($financial['total_outstanding']) . ".";
                        }
                        $waMsg .= " Please feel free to reply or call me to discuss your ward's progress.";
                    @endphp
                    <div class="small mb-2">
                        <strong>Father:</strong> {{ $student->father_name ?? 'N/A' }}
                        @if($student->father_mobile)
                            @php
                                $fMobile = preg_replace('/[^0-9]/', '', $student->father_mobile);
                                if (strlen($fMobile) === 10) { $fMobile = '91' . $fMobile; }
                            @endphp
                            <div class="mt-1">
                                <a href="tel:{{ $student->father_mobile }}" class="btn btn-sm btn-outline-primary py-0 px-2 mr-1">
                                    <i class="fas fa-phone-alt fa-xs mr-1"></i>{{ $student->father_mobile }}
                                </a>
                                <a href="https://wa.me/{{ $fMobile }}?text={{ urlencode($waMsg) }}" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" title="WhatsApp Father">
                                    <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                                </a>
                            </div>
                        @endif
                    </div>
                    @if($student->mother_mobile)
                        @php
                            $mMobie = preg_replace('/[^0-9]/', '', $student->mother_mobile);
                            if (strlen($mMobie) === 10) { $mMobie = '91' . $mMobie; }
                        @endphp
                        <div class="small mb-2">
                            <strong>Mother:</strong> {{ $student->mother_name ?? 'Mother' }}
                            <div class="mt-1">
                                <a href="tel:{{ $student->mother_mobile }}" class="btn btn-sm btn-outline-primary py-0 px-2 mr-1">
                                    <i class="fas fa-phone-alt fa-xs mr-1"></i>{{ $student->mother_mobile }}
                                </a>
                                <a href="https://wa.me/{{ $mMobie }}?text={{ urlencode($waMsg) }}" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" title="WhatsApp Mother">
                                    <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                                </a>
                            </div>
                        </div>
                    @endif
                    @if($student->student_mobile)
                        <div class="small">
                            <strong>Student:</strong>
                            <a href="tel:{{ $student->student_mobile }}" class="text-primary">{{ $student->student_mobile }}</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="menteeTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold" id="coordination-tab" data-toggle="tab" href="#coordination" role="tab">
                        <i class="fas fa-comments text-primary mr-1"></i> Parent Coordination Log
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                        <i class="fas fa-calendar-check text-success mr-1"></i> Recent Attendance Records
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold" id="fees-tab" data-toggle="tab" href="#fees" role="tab">
                        <i class="fas fa-receipt text-info mr-1"></i> Fee Installments Breakdown
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="menteeTabContent">

                <!-- Tab 1: Parent Coordination Log -->
                <div class="tab-pane fade show active" id="coordination" role="tabpanel">
                    <div class="row">
                        <!-- Add Note Form -->
                        <div class="col-lg-5 mb-4">
                            <div class="card bg-light border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="font-weight-bold text-gray-900 mb-3">
                                        <i class="fas fa-plus-circle text-primary mr-1"></i> Record Parent Call / Meeting
                                    </h6>
                                    <form id="showNoteForm" method="POST" action="{{ route('my-mentees.notes.store', $student->id) }}">
                                        @csrf
                                        <div class="form-row">
                                            <div class="form-group col-7">
                                                <label class="font-weight-bold small">Interaction Type</label>
                                                <select name="interaction_type" class="form-control form-control-sm">
                                                    <option value="Phone Call">Phone Call</option>
                                                    <option value="In-Person Meeting">In-Person Meeting</option>
                                                    <option value="WhatsApp / Message">WhatsApp / Message</option>
                                                    <option value="Parent Meeting">Parent Meeting</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-5">
                                                <label class="font-weight-bold small">Follow-up Date</label>
                                                <input type="date" name="follow_up_date" class="form-control form-control-sm">
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
                                                <option value="Issue Resolved">Issue Resolved</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="font-weight-bold small d-block mb-1">Quick Note Presets:</label>
                                            <div class="btn-group-sm mb-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="applyPreset('Fee Payment Promised', 'Spoke with parent. Fee payment promised to be cleared within a week.')">💰 Fee Promised</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="applyPreset('Attendance Warning Given', 'Parent notified about low attendance. Parent assured student will attend classes regularly.')">⚠️ Attendance Warned</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="applyPreset('General Check-in', 'Parent informed student was unwell with medical reason, will submit note.')">🩺 Medical Leave</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="applyPreset('Call Not Answered / Switched Off', 'Tried calling parent, phone was ringing but unanswered. Will retry tomorrow.')">📵 Unanswered</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 mr-1 mb-1" onclick="applyPreset('Parent Meeting Scheduled', 'Parent agreed to visit college for a personal progress review.')">🤝 Meeting Fixed</button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold small">Discussion Details & Next Steps</label>
                                            <textarea id="showNoteRemarks" name="notes" class="form-control" rows="3" required
                                                      placeholder="e.g., Called father. Discussed student's absence for 4 consecutive days. Father promised student will attend from tomorrow and pay fee next week."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold shadow-sm">
                                            <i class="fas fa-save mr-1"></i> Save Coordination Note
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Interaction History Timeline -->
                        <div class="col-lg-7">
                            <h6 class="font-weight-bold text-gray-900 mb-3">
                                <i class="fas fa-history text-secondary mr-1"></i> Coordination History ({{ $timeline->count() }})
                            </h6>
                            @forelse($timeline as $note)
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="badge badge-pill badge-primary font-weight-bold">
                                                    {{ $note->outcome ?? 'Follow-up' }}
                                                </span>
                                                <span class="small text-muted">
                                                    {{ $note->created_at->format('d M Y, h:i A') }}
                                                </span>
                                            </div>
                                            <p class="text-gray-800 small mb-1">{{ $note->notes }}</p>
                                            <div class="small text-muted" style="font-size: 0.75rem;">
                                                Logged by: <strong>{{ $note->user?->name ?? 'User' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class="fas fa-comment-slash fa-2x text-gray-300 mb-2"></i>
                                    <p class="text-muted small mb-0">No parent coordination records logged yet.</p>
                                    <p class="text-muted small">Use the form on the left after calling the parent to record notes.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Attendance Records -->
                <div class="tab-pane fade" id="attendance" role="tabpanel">
                    <h6 class="font-weight-bold text-gray-900 mb-3">Recent Attendance Logs (Last 20 Days)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Check In / Out</th>
                                    <th>Late Minutes</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAttendances as $att)
                                    <tr>
                                        <td>{{ $att->attendance_date->format('d M Y') }}</td>
                                        <td>
                                            @if($att->status === 'present')
                                                <span class="badge badge-success">Present</span>
                                            @elseif($att->status === 'absent')
                                                <span class="badge badge-danger">Absent</span>
                                            @elseif($att->status === 'late')
                                                <span class="badge badge-warning">Late</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($att->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $att->check_in_time ? $att->check_in_time->format('h:i A') : '-' }} /
                                            {{ $att->check_out_time ? $att->check_out_time->format('h:i A') : '-' }}
                                        </td>
                                        <td>{{ $att->late_minutes > 0 ? $att->late_minutes . ' mins' : '-' }}</td>
                                        <td>{{ $att->notes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No attendance records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: Fee Breakdown -->
                <div class="tab-pane fade" id="fees" role="tabpanel">
                    <h6 class="font-weight-bold text-gray-900 mb-3">Fee Installments</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Installment</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Concession</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($student->studentFees as $fee)
                                    @php
                                        $balance = max(0, $fee->amount - $fee->concession_amount - $fee->paid_amount);
                                    @endphp
                                    <tr>
                                        <td class="font-weight-bold">{{ $fee->feeCategory?->name ?? 'Tuition Fee' }}</td>
                                        <td>{{ $fee->installment_number }} of {{ $fee->total_installments }}</td>
                                        <td>₹{{ number_format($fee->amount) }}</td>
                                        <td class="text-success font-weight-bold">₹{{ number_format($fee->paid_amount) }}</td>
                                        <td>₹{{ number_format($fee->concession_amount) }}</td>
                                        <td>{{ $fee->due_date ? $fee->due_date->format('d M Y') : '-' }}</td>
                                        <td>
                                            @if($balance <= 0)
                                                <span class="badge badge-success">Paid</span>
                                            @elseif($fee->paid_amount > 0)
                                                <span class="badge badge-warning">Partial (₹{{ number_format($balance) }} due)</span>
                                            @else
                                                <span class="badge badge-danger">Unpaid (₹{{ number_format($balance) }})</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No fee records found for this student.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function applyPreset(outcome, text) {
        $('#showNoteForm select[name="outcome"]').val(outcome);
        $('#showNoteRemarks').val(text).focus();
    }
</script>
@endpush
