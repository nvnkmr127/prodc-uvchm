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
