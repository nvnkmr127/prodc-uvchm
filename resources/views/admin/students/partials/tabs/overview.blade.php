                                {{-- Overview Tab --}}
                                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                    <div class="row">
                                        {{-- Personal Information Column --}}
                                        <div class="col-md-6">
                                            <div class="card shadow-sm mb-4 border-left-primary h-100">
                                                <div class="card-header bg-white py-3">
                                                    <h6 class="m-0 font-weight-bold text-primary">
                                                        <i class="fas fa-user-circle mr-2"></i> Personal Details
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-borderless table-sm">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="font-weight-bold w-40">
                                                                        <i class="fas fa-venus-mars text-muted mr-2"></i>Gender:
                                                                    </td>
                                                                    <td>{{ $student->gender ?? 'Not specified' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-calendar-alt text-muted mr-2"></i>Date of Birth:
                                                                    </td>
                                                                    <td>
                                                                        {{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M, Y') : 'Not recorded' }}
                                                                    </td>
                                                                </tr>
                                                                @if($student->age)
                                                                    <tr>
                                                                        <td class="font-weight-bold">
                                                                            <i class="fas fa-hourglass-half text-muted mr-2"></i>Age:
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge badge-info">{{ $student->age }}</span>
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-user-tie text-muted mr-2"></i>Father's Name:
                                                                    </td>
                                                                    <td>{{ $student->father_name ?? 'Not provided' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-phone text-muted mr-2"></i>Mobile:
                                                                    </td>
                                                                    <td>{{ $student->student_mobile ?? 'Not provided' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-phone-alt text-muted mr-2"></i>Father's Mobile:
                                                                    </td>
                                                                    <td>{{ $student->father_mobile ?? 'Not provided' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-map-marker-alt text-muted mr-2"></i>Address:
                                                                    </td>
                                                                    <td>
                                                                        {{ $student->admission->address ?? $student->village ?? 'Not provided' }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Overall Attendance Card --}}
                                            <div class="card shadow-sm mb-4 border-left-success">
                                                <div class="card-header bg-white py-3">
                                                    <h6 class="m-0 font-weight-bold text-success">
                                                        <i class="fas fa-calendar-check mr-2"></i> Overall Attendance
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row text-center mb-3">
                                                        <div class="col-4 border-right">
                                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-1">Working Days</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalWorkingDays ?? 0 }}</div>
                                                        </div>
                                                        <div class="col-4 border-right">
                                                            <div class="text-xs font-weight-bold text-uppercase text-success mb-1">Present</div>
                                                            <div class="h5 mb-0 font-weight-bold text-success">{{ $presentDays ?? 0 }}</div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="text-xs font-weight-bold text-uppercase text-danger mb-1">Absent</div>
                                                            <div class="h5 mb-0 font-weight-bold text-danger">{{ $absentDays ?? 0 }}</div>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="text-xs font-weight-bold">Attendance Percentage</span>
                                                            <span class="text-xs font-weight-bold text-{{ ($attendancePercentage ?? 0) >= 75 ? 'success' : 'danger' }}">{{ $attendancePercentage ?? 0 }}%</span>
                                                        </div>
                                                        <div class="progress progress-sm">
                                                            <div class="progress-bar bg-{{ ($attendancePercentage ?? 0) >= 75 ? 'success' : 'danger' }}" role="progressbar" style="width: {{ $attendancePercentage ?? 0 }}%" aria-valuenow="{{ $attendancePercentage ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Academic & Source Information Column --}}
                                        <div class="col-md-6">
                                            {{-- Academic Info --}}
                                            <div class="card shadow-sm mb-4 border-left-info">
                                                <div class="card-header bg-white py-3">
                                                    <h6 class="m-0 font-weight-bold text-info">
                                                        <i class="fas fa-graduation-cap mr-2"></i> Academic Details
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-borderless table-sm">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="font-weight-bold w-40">
                                                                        <i class="fas fa-book text-muted mr-2"></i>Course:
                                                                    </td>
                                                                    <td>{{ optional($student->batch)->course->name ?? 'Not assigned' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-chalkboard-teacher text-muted mr-2"></i>Batch:
                                                                    </td>
                                                                    <td>{{ optional($student->batch)->name ?? 'Not assigned' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-calendar-check text-muted mr-2"></i>Admission Date:
                                                                    </td>
                                                                    <td>
                                                                        {{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d M, Y') : 'Not recorded' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-info-circle text-muted mr-2"></i>Status:
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-{{ $student->status === 'active' ? 'success' : ($student->status === 'graduated' ? 'info' : 'danger') }}">
                                                                            {{ ucfirst($student->status) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Source Info --}}
                                            <div class="card shadow-sm mb-4 border-left-warning">
                                                <div class="card-header bg-white py-3">
                                                    <h6 class="m-0 font-weight-bold text-warning">
                                                        <i class="fas fa-bullhorn mr-2"></i> Source Information
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-borderless table-sm">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="font-weight-bold w-40">
                                                                        <i class="fas fa-share-alt text-muted mr-2"></i>Source:
                                                                    </td>
                                                                    <td>
                                                                        @if($student->source ?? null)
                                                                            <span class="badge badge-primary px-2 py-1">{{ ucfirst($student->source) }}</span>
                                                                        @else
                                                                            <span class="text-muted">Not provided</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="font-weight-bold">
                                                                        <i class="fas fa-user-tag text-muted mr-2"></i>Referral Name:
                                                                    </td>
                                                                    <td>
                                                                        @if($student->referral_name ?? null)
                                                                            <span class="font-weight-bold text-dark">
                                                                                {{ $student->referral_name }}
                                                                            </span>
                                                                            @if($student->referrer)
                                                                                <div class="small text-muted mt-1">
                                                                                    <i class="fas fa-id-badge mr-1"></i> {{ $student->referrer->enrollment_number }}
                                                                                    <span class="mx-1">|</span>
                                                                                    <i class="fas fa-layer-group mr-1"></i> {{ optional($student->referrer->batch)->name ?? 'No Batch' }}
                                                                                </div>
                                                                            @endif
                                                                        @else
                                                                            <span class="text-muted">Not provided</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                {{-- Fee Components Tab --}}
