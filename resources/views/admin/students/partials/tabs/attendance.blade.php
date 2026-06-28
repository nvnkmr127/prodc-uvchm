                                <div class="tab-pane fade" id="attendance" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-12">
                                            {{-- Month Selector --}}
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <h5 class="text-primary mb-0">
                                                    <i class="fas fa-calendar-check mr-2"></i>Attendance Overview
                                                </h5>
                                                <div class="form-group mb-0">
                                                    <select class="form-control" id="attendanceMonth"
                                                        onchange="loadAttendanceData()">
                                                        @for($i = 0; $i < 12; $i++)
                                                            @php
    $month = now()->subMonths($i);
                                                            @endphp
                                                            <option value="{{ $month->format('Y-m') }}" {{ $i === 0 ? 'selected' : '' }}>
                                                                {{ $month->format('F Y') }}
                                                            </option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Loading State --}}
                                            <div id="attendanceLoading" class="text-center py-5">
                                                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                                                <p class="text-muted">Loading attendance data...</p>
                                            </div>

                                            {{-- Attendance Content --}}
                                            <div id="attendanceContent" style="display: none;">

                                                {{-- Summary Cards --}}
                                                <div class="row mb-4">
                                                    {{-- Working Days Card (NEW) --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-primary shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                                            Working Days
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="totalWorkingDays">
                                                                            0
                                                                        </div>
                                                                        <small class="text-muted" style="font-size: 0.65rem;">(Till
                                                                            Date)</small>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Percentage Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-info shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                                            Attendance
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="tabAttendancePercentage">
                                                                            0%
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Present Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-success shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                                            Present
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="presentDays">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-check fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Absent Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-danger shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                                            Absent
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="absentDays">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-times fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Late Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-warning shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                                            Late
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="lateDays">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Holidays Card --}}
                                                    <div class="col-md-2 mb-3">
                                                        <div class="card border-left-secondary shadow-sm h-100">
                                                            <div class="card-body py-3 px-2">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                                            Holidays
                                                                        </div>
                                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"
                                                                            id="holidayDays">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-umbrella-beach fa-2x text-gray-300"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Attendance Status Alert --}}
                                                <div class="alert" id="attendanceStatusAlert" style="display: none;">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-info-circle mr-2"></i>
                                                        <div>
                                                            <strong id="attendanceStatusTitle">Attendance Status</strong>
                                                            <div class="small" id="attendanceStatusMessage"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Monthly Calendar View --}}
                                                <div class="card shadow-sm">
                                                    <div class="card-header py-3">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-calendar mr-2"></i>
                                                            <span id="calendarTitle">Monthly Attendance</span>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div id="attendanceCalendar">
                                                            {{-- Calendar will be populated via JavaScript --}}
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Recent Records Table --}}
                                                <div class="card shadow-sm mt-4">
                                                    <div class="card-header py-3">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-history mr-2"></i>Recent Attendance Records
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm" id="attendanceRecordsTable">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Date</th>
                                                                        <th>Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    {{-- Records will be populated via JavaScript --}}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Error State --}}
                                            <div id="attendanceError" style="display: none;">
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    <strong>Error loading attendance data</strong>
                                                    <p class="mb-0 mt-2" id="attendanceErrorMessage"></p>
                                                    <button class="btn btn-sm btn-outline-danger mt-2"
                                                        onclick="loadAttendanceData()">
                                                        <i class="fas fa-redo mr-1"></i>Retry
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    {{-- Recent Activity Timeline (Optional) --}}
                    @if($recentActivity->count() > 0)
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-history mr-2"></i>Recent Activity Timeline
                                </h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item activity-filter" href="#" data-type="all">
                                            <i class="fas fa-list mr-2"></i>All Activities
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item activity-filter" href="#" data-type="payment">
                                            <i class="fas fa-money-bill-wave mr-2"></i>Payments Only
                                        </a>
                                        <a class="dropdown-item activity-filter" href="#" data-type="concession">
                                            <i class="fas fa-percent mr-2"></i>Concessions Only
                                        </a>
                                        <a class="dropdown-item activity-filter" href="#" data-type="attendance">
                                            <i class="fas fa-user-check mr-2"></i>Attendance Only
                                        </a>
                                        <a class="dropdown-item activity-filter" href="#" data-type="spatie_log">
                                            <i class="fas fa-cogs mr-2"></i>System Changes
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline" id="activityTimeline" style="max-height: 500px; overflow-y: auto;">
                                    @foreach($recentActivity as $activity)
                                        <div class="timeline-item" data-type="{{ $activity['type'] ?? 'general' }}">
                                            <div class="timeline-marker bg-{{ $activity['color'] ?? 'primary' }}">
                                                <i class="fas {{ $activity['icon'] ?? 'fa-info-circle' }}"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="timeline-header">
                                                        <h6 class="mb-1 font-weight-bold">{{ $activity['title'] ?? 'Activity' }}</h6>
                                                        <p class="text-muted mb-1">
                                                            {{ $activity['description'] ?? 'No description available' }}</p>
                                                    </div>
                                                    <div class="timeline-meta text-right">
                                                        <small
                                                            class="text-muted d-block">{{ $activity['timestamp']->format('M d, Y') }}</small>
                                                        <small class="text-muted">{{ $activity['timestamp']->format('h:i A') }}</small>
                                                    </div>
                                                </div>

                                                @if(!empty($activity['properties'] ?? []))
                                                    <div class="timeline-details">
                                                        <button class="btn btn-sm btn-outline-secondary toggle-details" type="button"
                                                            data-toggle="collapse" data-target="#details-{{ $loop->index }}">
                                                            <i class="fas fa-chevron-down"></i> Details
                                                        </button>
                                                        <div class="collapse mt-2" id="details-{{ $loop->index }}">
                                                            <div class="card card-body bg-light">
                                                                @if(($activity['type'] ?? '') === 'payment')
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <small><strong>Amount:</strong>
                                                                                ₹{{ number_format($activity['properties']['amount'] ?? 0, 2) }}</small><br>
                                                                            <small><strong>Method:</strong>
                                                                                {{ ucfirst($activity['properties']['method'] ?? 'Unknown') }}</small>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <small><strong>Receipt:</strong>
                                                                                {{ $activity['properties']['receipt'] ?? 'N/A' }}</small><br>
                                                                            <small><strong>Components:</strong>
                                                                                {{ $activity['properties']['components'] ?? 0 }} items</small>
                                                                        </div>
                                                                    </div>
                                                                @elseif(($activity['type'] ?? '') === 'concession')
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <small><strong>Amount:</strong>
                                                                                ₹{{ number_format($activity['properties']['amount'] ?? 0, 2) }}</small><br>
                                                                            <small><strong>Status:</strong>
                                                                                {{ ucfirst($activity['properties']['status'] ?? 'Unknown') }}</small>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            @if(!empty($activity['properties']['reason'] ?? null))
                                                                                <small><strong>Reason:</strong>
                                                                                    {{ $activity['properties']['reason'] }}</small>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div class="properties-list">
                                                                        @foreach(($activity['properties'] ?? []) as $key => $value)
                                                                            @if(!is_array($value))
                                                                                <small><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                                                    {{ $value }}</small><br>
                                                                            @endif
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="timeline-footer mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user mr-1"></i>{{ $activity['user'] ?? 'System' }}
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-clock mr-1"></i>{{ $activity['timestamp']->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Load More Button --}}

                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right Sidebar - Quick Actions --}}
                <div class="col-lg-4">
                    {{-- Financial Summary Card --}}
                    <div class="card modern-card mb-4">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-chart-pie mr-2"></i> Financial Summary
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div class="h4 font-weight-bold text-gray-800 amount-counter">
                                    ₹{{ number_format(isset($financialSummary['total_amount']) ? $financialSummary['total_amount'] : 0, 0) }}
                                </div>
                                <small class="text-muted">Total Fee Amount</small>
                            </div>

                            <div class="progress mb-3" style="height: 15px;">
                                <div class="progress-bar 
                                    @if(isset($financialSummary['payment_percentage']) && $financialSummary['payment_percentage'] >= 75) bg-success 
                                    @elseif(isset($financialSummary['payment_percentage']) && $financialSummary['payment_percentage'] >= 50) bg-warning 
                                    @else bg-danger @endif" role="progressbar"
                                    style="width: {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}%">
                                    {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}%
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light">
                                        <div class="text-success font-weight-bold">
                                            ₹{{ number_format(isset($financialSummary['paid_amount']) ? $financialSummary['paid_amount'] : 0, 0) }}
                                        </div>
                                        <div class="small text-muted">Paid</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-light">
                                        <div class="text-danger font-weight-bold">
                                            ₹{{ number_format(isset($financialSummary['remaining_amount']) ? $financialSummary['remaining_amount'] : 0, 0) }}
                                        </div>
                                        <div class="small text-muted">Due</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions Card --}}
                    <div class="card modern-card">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-bolt mr-2"></i> Quick Actions
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="quick-action-card" onclick="openPaymentModal()">
                                <div class="d-flex align-items-center">
                                    <div class="quick-action-icon bg-success text-white">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">Record Payment</div>
                                        <small class="text-muted">Add new payment entry</small>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-action-card" data-toggle="modal" data-target="#applyConcessionModal">
                                <div class="d-flex align-items-center">
                                    <div class="quick-action-icon bg-warning text-white">
                                        <i class="fas fa-percent"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">Apply Concession</div>
                                        <small class="text-muted">
                                            Discount fee components
                                            @if(($student->gender ?? null) === 'Female' && setting('womens_discount_percentage', 0) > 0)
                                                <br><span class="badge badge-success mt-1">
                                                    <i class="fas fa-female"></i> {{ setting('womens_discount_percentage') }}% Eligible
                                                </span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-action-card"
                                onclick="window.location.href='{{ route('admin.students.edit', $student) }}'">
                                <div class="d-flex align-items-center">
                                    <div class="quick-action-icon bg-primary text-white">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">Edit Profile</div>
                                        <small class="text-muted">Update student information</small>
                                    </div>
                                </div>
                            </div>

                            @if(Route::has('admin.payments.component-dashboard'))
                                <div class="quick-action-card"
                                    onclick="window.location.href='{{ route('admin.payments.component-dashboard', $student) }}'">
                                    <div class="d-flex align-items-center">
                                        <div class="quick-action-icon bg-info text-white">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">Fee Dashboard</div>
                                            <small class="text-muted">Detailed fee management</small>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="quick-action-card" onclick="window.print()">
                                <div class="d-flex align-items-center">
                                    <div class="quick-action-icon bg-secondary text-white">
                                        <i class="fas fa-print"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">Print Profile</div>
                                        <small class="text-muted">Generate printable version</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
