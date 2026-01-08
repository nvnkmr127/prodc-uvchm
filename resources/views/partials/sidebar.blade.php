<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.dashboard') }}">
        <div class="sidebar-brand-icon">
            <img src="{{ asset('storage/settings/1753508439_UV Foundation (1).png') }}" alt="Logo"
                style="max-height: 50px; width: auto; background: white; border-radius: 5px; padding: 2px;">
        </div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Calendar Link (Restored) -->
    @can('view backend')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.calendar.index') }}">
                <i class="fas fa-fw fa-calendar-week"></i>
                <span>My Calendar</span>
            </a>
        </li>
    @endcan

    <!-- Quick Actions Section -->
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Quick Actions</div>

    @can('create students')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.students.create') }}">
                <i class="fas fa-fw fa-user-plus"></i>
                <span>Add Student</span>
            </a>
        </li>
    @endcan

    @can('view attendance')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.attendance.dashboard') }}">
                <i class="fas fa-fw fa-chart-line"></i>
                <span>View Attendance</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.attendance.single.index') }}">
                <i class="fas fa-fw fa-user-check"></i>
                <span>Single Student</span>
            </a>
        </li>
    @endcan

    @can('view students')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.students.index') }}">
                <i class="fas fa-fw fa-users"></i>
                <span>View Students</span>
            </a>
            <a class="nav-link" href="{{ route('admin.enquiries.index') }}">
                <i class="fas fa-list"></i> <span>Manage Enquiries</span>
            </a>
        </li>
    @endcan

    @can('manage students')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.student-requests.index') }}">
                <i class="fas fa-fw fa-user-edit"></i>
                <span>Profile Requests</span>

                @if($pendingReqCount > 0)
                    <span class="badge badge-danger badge-counter ml-1">{{ $pendingReqCount }}</span>
                @endif
            </a>
        </li>
    @endcan


    @can('view financials')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.component-payments.index') }}">
                <i class="fas fa-fw fa-file-invoice-dollar"></i>
                <span>Invoices & Payments</span>
            </a>
        </li>
    @endcan

    <!-- Lead Management -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view enquiries'))
        <hr class="sidebar-divider">
        <div class="sidebar-heading">Lead Management</div>
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseEnquiries"
                aria-expanded="false">
                <i class="fas fa-fw fa-users-cog"></i>
                <span>Enquiries</span>
            </a>
            <div id="collapseEnquiries" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Enquiry Management</h6>

                    <a class="collapse-item" href="{{ route('admin.enquiries.create') }}">
                        <i class="fas fa-plus"></i> Add New Enquiry
                    </a>
                    <a class="collapse-item" href="{{ route('enquiry.public.create') }}">
                        <i class="fas fa-globe"></i> Public Enquiry Form
                    </a>
                </div>
            </div>
        </li>
    @endif

    <!-- Core Modules -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->canAny(['view courses', 'view batches', 'view subjects']))
        <hr class="sidebar-divider">
        <div class="sidebar-heading">Core Modules</div>

        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAcademics"
                aria-expanded="false">
                <i class="fas fa-fw fa-book-open"></i>
                <span>Academics</span>
            </a>
            <div id="collapseAcademics" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    @if(auth()->user()->hasRole('super-admin'))
                        <a class="collapse-item" href="{{ route('admin.courses.index') }}">Courses</a>
                        <a class="collapse-item" href="{{ route('admin.batches.index') }}">Batches</a>
                        <a class="collapse-item" href="{{ route('admin.subjects.index') }}">Subjects</a>
                    @else
                        @can('view courses')
                            <a class="collapse-item" href="{{ route('admin.courses.index') }}">Courses</a>
                        @endcan
                        @can('view batches')
                            <a class="collapse-item" href="{{ route('admin.batches.index') }}">Batches</a>
                        @endcan
                        @can('view subjects')
                            <a class="collapse-item" href="{{ route('admin.subjects.index') }}">Subjects</a>
                        @endcan
                    @endif
                </div>
            </div>
        </li>
    @endif

    <!-- People Management -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->canAny(['view admissions', 'view students', 'view faculty']))
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePeople"
                aria-expanded="false">
                <i class="fas fa-fw fa-users"></i>
                <span>People</span>
            </a>
            <div id="collapsePeople" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    @if(auth()->user()->hasRole('super-admin'))
                        <a class="collapse-item" href="{{ route('admin.admissions.index') }}">Admissions</a>
                        <a class="collapse-item" href="{{ route('admin.students.index') }}">Students</a>
                        <a class="collapse-item" href="{{ route('admin.faculty.index') }}">Faculty</a>
                        <a class="collapse-item" href="{{ route('admin.alumni.index') }}">Alumni Network</a>
                        <a class="collapse-item" href="{{ route('admin.enquiries.index') }}">Enquiry Hub</a>
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">Biometric System:</h6>
                        <a class="collapse-item" href="{{ route('admin.students.biometric-mapping') }}">
                            <i class="fas fa-fingerprint text-primary"></i> Biometric Mapping

                            @if($unmappedCount > 0)
                                <span class="badge badge-warning ml-1">{{ $unmappedCount }}</span>
                            @endif
                        </a>
                    @else
                        @can('view admissions')
                            <a class="collapse-item" href="{{ route('admin.admissions.index') }}">Admissions</a>
                            <a class="collapse-item" href="{{ route('admin.enquiries.index') }}">Enquiry Hub</a>
                        @endcan

                        @can('view students')
                            <a class="collapse-item" href="{{ route('admin.students.index') }}">Students</a>
                            @can('manage students')
                                <a class="collapse-item" href="{{ route('admin.students.biometric-mapping') }}">
                                    <i class="fas fa-fingerprint text-primary"></i> Biometric Mapping

                                    @if($unmappedCount > 0)
                                        <span class="badge badge-warning ml-1">{{ $unmappedCount }}</span>
                                    @endif
                                </a>
                                {{-- Added Duplicate Check Link --}}
                                {{-- Note: Duplicate check is an AJAX service, usually doesn't have an index.
                                If Client requested a UI for it, we'd need to build one.
                                For now, skipping adding a broken link since no index view exists.
                                Instead, clarifying in inventory that it's a service. --}}
                            @endcan
                        @endcan

                        @can('view faculty')
                            <a class="collapse-item" href="{{ route('admin.faculty.index') }}">Faculty</a>
                        @endcan
                    @endif
                </div>
            </div>
        </li>
    @endif

    <!-- Financials -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->canAny(['view financials', 'view invoices']))
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFinancials"
                aria-expanded="false">
                <i class="fas fa-fw fa-dollar-sign"></i>
                <span>Financials</span>
            </a>
            <div id="collapseFinancials" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    @if(auth()->user()->hasRole('super-admin'))
                        <a class="collapse-item" href="{{ route('admin.fee-categories.index') }}">Fee Categories</a>
                        <a class="collapse-item" href="{{ route('admin.fee-structures.index') }}">Fee Structures</a>
                        <a class="collapse-item" href="{{ route('admin.fee-category-analysis.index') }}">Fee Category
                            Analysis</a>
                        <a class="collapse-item" href="{{ route('admin.component-payments.index') }}">Invoices &
                            Payments</a>

                        <h6 class="collapse-header">Payment Follow-up:</h6>
                        <a class="collapse-item" href="{{ route('admin.payment-reminders.dashboard') }}">
                            <i class="fas fa-bell text-warning"></i> Reminder Dashboard
                        </a>

                        <a class="collapse-item" href="{{ route('admin.payment-reminders.index') }}">
                            <i class="fas fa-clock"></i> All Reminders
                        </a>
                        <a class="collapse-item" href="{{ route('admin.expense-categories.index') }}">Expense
                            Categories</a>
                        <a class="collapse-item" href="{{ route('admin.expenses.index') }}">Log Expenses</a>
                        <div class="dropdown-divider"></div>
                        <h6 class="collapse-header">Payment Reminders:</h6>
                        <a class="collapse-item" href="{{ route('admin.payment-reminders.dashboard') }}">
                            <i class="fas fa-tachometer-alt fa-sm fa-fw mr-1"></i> Dashboard
                        </a>
                        <a class="collapse-item" href="{{ route('admin.payment-reminders.index') }}">
                            <i class="fas fa-list fa-sm fa-fw mr-1"></i> All Reminders
                        </a>

                        <a class="collapse-item" href="{{ route('admin.payment-reminders.settings.index') }}">
                            <i class="fas fa-cog fa-sm fa-fw mr-1"></i> Settings
                        </a>
                    @else
                        @can('view financials')
                            <a class="collapse-item" href="{{ route('admin.fee-categories.index') }}">Fee Categories</a>
                            <a class="collapse-item" href="{{ route('admin.fee-structures.index') }}">Fee Structures</a>
                            <a class="collapse-item" href="{{ route('admin.fee-category-analysis.index') }}">Fee Category
                                Analysis</a>
                            <a class="collapse-item" href="{{ route('admin.component-payments.index') }}">Invoices &
                                Payments</a>
                            <a class="collapse-item" href="{{ route('admin.expense-categories.index') }}">Expense
                                Categories</a>
                            <a class="collapse-item" href="{{ route('admin.expenses.index') }}">Log Expenses</a>
                            <div class="dropdown-divider"></div>
                            <h6 class="collapse-header">Payment Reminders:</h6>
                            <a class="collapse-item" href="{{ route('admin.payment-reminders.dashboard') }}">
                                <i class="fas fa-tachometer-alt fa-sm fa-fw mr-1"></i> Dashboard
                            </a>
                            <a class="collapse-item" href="{{ route('admin.payment-reminders.index') }}">
                                <i class="fas fa-list fa-sm fa-fw mr-1"></i> All Reminders
                            </a>

                            <a class="collapse-item" href="{{ route('admin.payment-reminders.settings.index') }}">
                                <i class="fas fa-cog fa-sm fa-fw mr-1"></i> Settings
                            </a>
                        @endcan
                    @endif
                </div>
            </div>
        </li>
    @endif

    <!-- Operations -->
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Operations</div>

    <!-- Timetable -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTimetable"
            aria-expanded="false">
            <i class="fas fa-fw fa-calendar-alt"></i>
            <span>Timetable</span>
        </a>
        <div id="collapseTimetable" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Setup:</h6>
                @if(auth()->user()->hasRole('super-admin'))
                    <a class="collapse-item" href="{{ route('admin.classrooms.index') }}">Classrooms</a>
                    <a class="collapse-item" href="{{ route('admin.time-slots.index') }}">Time Slots</a>
                    <a class="collapse-item" href="{{ route('admin.holidays.index') }}">Holidays</a>
                    <a class="collapse-item" href="{{ route('admin.events.index') }}">Event Scheduler</a>
                @else
                    @can('view timetable')
                        <a class="collapse-item" href="{{ route('admin.classrooms.index') }}">Classrooms</a>
                        <a class="collapse-item" href="{{ route('admin.time-slots.index') }}">Time Slots</a>
                        <a class="collapse-item" href="{{ route('admin.holidays.index') }}">Holidays</a>
                        <a class="collapse-item" href="{{ route('admin.events.index') }}">Event Scheduler</a>
                    @endcan
                @endif
                <div class="dropdown-divider"></div>
                <h6 class="collapse-header">Management:</h6>
                @if(auth()->user()->hasRole('super-admin'))
                    <a class="collapse-item" href="{{ route('admin.timetable.hub') }}">Timetable Hub</a>
                @else
                    @can('view timetable')
                        <a class="collapse-item" href="{{ route('admin.timetable.hub') }}">Timetable Hub</a>
                    @endcan
                @endif
            </div>
        </div>
    </li>

    <!-- Attendance & Labs -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAttendance"
            aria-expanded="false">
            <i class="fas fa-fw fa-check-square"></i>
            <span>Attendance & Labs</span>
        </a>
        <div id="collapseAttendance" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Attendance Management:</h6>
                @can('view attendance')
                    <a class="collapse-item" href="{{ route('admin.daily-attendance.index') }}">
                        <i class="fas fa-list fa-sm fa-fw mr-1"></i> Daily Records
                    </a>
                    <a class="collapse-item" href="{{ route('admin.daily-attendance.show') }}">
                        <i class="fas fa-broadcast-tower fa-sm fa-fw mr-1"></i> Live Attendance
                    </a>
                @endcan
                @can('view attendance')
                    {{--
                    <a class="collapse-item" href="{{ route('admin.attendance.dashboard') }}">
                        <i class="fas fa-tachometer-alt fa-sm fa-fw mr-1"></i> Dashboard
                    </a>
                    --}}
                @endcan

                @can('take attendance')
                    <a class="collapse-item" href="{{ route('admin.daily-attendance.create') }}">
                        <i class="fas fa-plus fa-sm fa-fw mr-1"></i> Mark Attendance
                    </a>
                @endcan

                @can('view attendance')
                    {{--
                    <a class="collapse-item" href="{{ route('attendance.analytics.index') }}">
                        <i class="fas fa-chart-line fa-sm fa-fw mr-1"></i> Analytics
                    </a>
                    <a class="collapse-item" href="{{ route('attendance.reports.index') }}">
                        <i class="fas fa-file-alt fa-sm fa-fw mr-1"></i> Reports
                    </a>
                    --}}
                @endcan

                <div class="dropdown-divider"></div>
                <h6 class="collapse-header">Import & Export:</h6>
                @can('manage attendance')
                    <a class="collapse-item" href="{{ route('admin.attendance.import.show') }}">
                        <i class="fas fa-upload fa-sm fa-fw mr-1"></i> Import Attendance
                    </a>
                    <a class="collapse-item" href="{{ route('admin.attendance.import.sample') }}">
                        <i class="fas fa-download fa-sm fa-fw mr-1"></i> Download Sample
                    </a>
                @endcan

                @can('export attendance')
                    <a class="collapse-item" href="{{ route('admin.attendance.export.today') }}">
                        <i class="fas fa-file-export fa-sm fa-fw mr-1"></i> Export Today
                    </a>
                @endcan

                <div class="dropdown-divider"></div>
                <h6 class="collapse-header">Notifications:</h6>
                @can('manage attendance')
                    {{--
                    <a class="collapse-item" href="{{ route('attendance.notifications.index') }}">
                        <i class="fas fa-bell fa-sm fa-fw mr-1"></i> Attendance Alerts
                    </a>
                    --}}
                @endcan

                <div class="dropdown-divider"></div>
                <h6 class="collapse-header">Lab Management:</h6>
                @can('manage attendance')
                    <a class="collapse-item" href="{{ route('admin.lab-allocation.index') }}">
                        <i class="fas fa-flask fa-sm fa-fw mr-1"></i> Lab Allocation
                    </a>
                @endcan

                <div class="dropdown-divider"></div>
                <h6 class="collapse-header">Document Generation:</h6>
                @can('manage documents')
                    <a class="collapse-item" href="{{ route('admin.id-cards.show') }}">
                        <i class="fas fa-id-card fa-sm fa-fw mr-1"></i> ID Card Generator
                    </a>
                    <a class="collapse-item" href="{{ route('admin.id-card-templates.index') }}">
                        <i class="fas fa-id-badge fa-sm fa-fw mr-1"></i> ID Card Templates
                    </a>
                    <a class="collapse-item" href="{{ route('admin.certificate-templates.index') }}">
                        <i class="fas fa-certificate fa-sm fa-fw mr-1"></i> Certificate Templates
                    </a>
                    <a class="collapse-item" href="{{ route('admin.certificate.generator.show') }}">
                        <i class="fas fa-award fa-sm fa-fw mr-1"></i> Certificate Generator
                    </a>
                    <a class="collapse-item" href="{{ route('admin.certificate-generator.bulk') }}">
                        <i class="fas fa-file-archive fa-sm fa-fw mr-1"></i> Bulk Certificates
                    </a>
                @endcan

                @if(auth()->user()->hasRole('super-admin'))
                    <div class="dropdown-divider"></div>
                    <h6 class="collapse-header">Admin Tools:</h6>
                    <a class="collapse-item" href="{{ route('admin.daily-attendance.index') }}">
                        <i class="fas fa-calendar-day fa-sm fa-fw mr-1"></i> Daily Attendance
                    </a>
                    <a class="collapse-item" href="{{ route('admin.attendance.import.show') }}">
                        <i class="fas fa-upload fa-sm fa-fw mr-1"></i> Bulk Import
                    </a>
                    <a class="collapse-item" href="{{ route('admin.attendance.settings') }}">
                        <i class="fas fa-cog fa-sm fa-fw mr-1"></i> Settings
                    </a>
                @else
                    @if(auth()->user()->canAny(['manage attendance', 'view id-cards', 'view certificates']))
                        <div class="dropdown-divider"></div>
                        <h6 class="collapse-header">Admin Tools:</h6>
                        @can('manage attendance')
                            <a class="collapse-item" href="{{ route('admin.daily-attendance.create') }}">
                                <i class="fas fa-calendar-day fa-sm fa-fw mr-1"></i> Mark Attendance
                            </a>
                            <a class="collapse-item" href="{{ route('admin.attendance.import.show') }}">
                                <i class="fas fa-upload fa-sm fa-fw mr-1"></i> Bulk Import
                            </a>
                        @endcan
                    @endif
                @endif
            </div>
        </div>
    </li>

    <!-- Student Portal -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('manage students'))
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseStudentPortal"
                aria-expanded="false">
                <i class="fas fa-fw fa-user-graduate"></i>
                <span>Student Portal</span>
            </a>
            <div id="collapseStudentPortal" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Portal Management:</h6>
                    <a class="collapse-item" href="{{ route('admin.student-requests.index') }}">
                        <i class="fas fa-tasks fa-sm fa-fw mr-1"></i> Profile Requests
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="collapse-header">Activity Monitoring:</h6>
                    <a class="collapse-item" href="{{ route('admin.student-portal-logs.dashboard') }}">
                        <i class="fas fa-tachometer-alt fa-sm fa-fw mr-1"></i> Live Dashboard
                    </a>
                    <a class="collapse-item" href="{{ route('admin.student-portal-logs.index') }}">
                        <i class="fas fa-list fa-sm fa-fw mr-1"></i> Activity Logs
                    </a>
                </div>
            </div>
        </li>
    @endif

    <!-- Inventory -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->canAny(['view inventory', 'view assets']))
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseInventory"
                aria-expanded="false">
                <i class="fas fa-fw fa-box"></i>
                <span>Inventory</span>
            </a>
            <div id="collapseInventory" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    @if(auth()->user()->hasRole('super-admin'))
                        <a class="collapse-item" href="{{ route('admin.asset-categories.index') }}">Asset Categories</a>
                        <a class="collapse-item" href="{{ route('admin.assets.index') }}">Manage Assets</a>
                        <a class="collapse-item" href="{{ route('admin.audits.index') }}">Conduct Audit</a>
                    @else
                        @can('view inventory')
                            <a class="collapse-item" href="{{ route('admin.asset-categories.index') }}">Asset Categories</a>
                            <a class="collapse-item" href="{{ route('admin.assets.index') }}">Manage Assets</a>
                            <a class="collapse-item" href="{{ route('admin.audits.index') }}">Conduct Audit</a>
                        @endcan
                    @endif
                </div>
            </div>
        </li>
    @endif

    <!-- Front Office -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view visitors'))
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFO" aria-expanded="false">
                <i class="fas fa-fw fa-address-book"></i>
                <span>Front Office</span>
            </a>
            <div id="collapseFO" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Front Office Book</h6>
                    <a class="collapse-item" href="{{ route('admin.visitors.index') }}">Visitor Book</a>
                </div>
            </div>
        </li>
    @endif

    <!-- HR Management -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view hr'))
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseHR" aria-expanded="false">
                <i class="fas fa-fw fa-briefcase"></i>
                <span>HR Management</span>
            </a>
            <div id="collapseHR" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">HR & Payroll:</h6>
                    @if(auth()->user()->hasRole('super-admin'))
                        <a class="collapse-item" href="{{ route('admin.leave-types.index') }}">Leave Types</a>
                        <a class="collapse-item" href="{{ route('admin.leave-applications.index') }}">Leave
                            Applications</a>
                        <a class="collapse-item" href="{{ route('admin.salary-components.index') }}">Salary
                            Components</a>
                        <a class="collapse-item" href="{{ route('admin.payslips.index') }}">Generate Payslips</a>
                    @else
                        @can('view hr')
                            <a class="collapse-item" href="{{ route('admin.leave-types.index') }}">Leave Types</a>
                            <a class="collapse-item" href="{{ route('admin.leave-applications.index') }}">Leave
                                Applications</a>
                            <a class="collapse-item" href="{{ route('admin.salary-components.index') }}">Salary
                                Components</a>
                            <a class="collapse-item" href="{{ route('admin.payslips.index') }}">Generate Payslips</a>
                        @endcan
                    @endif
                </div>
            </div>
        </li>
    @endif

    <!-- System Administration -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->canAny(['view settings', 'view users', 'manage users', 'manage permissions']))
        <hr class="sidebar-divider">
        <div class="sidebar-heading">System</div>
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAdmin"
                aria-expanded="false">
                <i class="fas fa-fw fa-cogs"></i>
                <span>Administration</span>
            </a>
            <div id="collapseAdmin" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    @if(auth()->user()->hasRole('super-admin'))
                        <h6 class="collapse-header">User & Access Control</h6>
                        <a class="collapse-item" href="{{ route('admin.users.index') }}">
                            <i class="fas fa-users fa-sm fa-fw mr-1"></i> User Management
                        </a>
                        <a class="collapse-item" href="{{ route('admin.roles.index') }}">
                            <i class="fas fa-user-shield fa-sm fa-fw mr-1"></i> Roles
                        </a>
                        <a class="collapse-item" href="{{ route('admin.permissions.index') }}">
                            <i class="fas fa-shield-alt fa-sm fa-fw mr-1"></i> Permissions
                        </a>
                        <a class="collapse-item" href="{{ route('admin.permission-management.index') }}">
                            <i class="fas fa-tools fa-sm fa-fw mr-1"></i> Permission Manager
                        </a>

                        <div class="dropdown-divider"></div>
                        <h6 class="collapse-header">System Configuration</h6>
                        <a class="collapse-item" href="{{ route('admin.settings.index') }}">
                            <i class="fas fa-cog fa-sm fa-fw mr-1"></i> Settings
                        </a>
                        <a class="collapse-item" href="{{ route('admin.settings.health-check') }}">
                            <i class="fas fa-heartbeat fa-sm fa-fw mr-1"></i> Health Check
                        </a>

                        <a class="collapse-item" href="{{ route('admin.backups.index') }}">
                            <i class="fas fa-archive fa-sm fa-fw mr-1"></i> Backup & Restore
                        </a>

                        <a class="collapse-item" href="{{ route('admin.activity-log.index') }}">
                            <i class="fas fa-history fa-sm fa-fw mr-1"></i> Activity Log
                        </a>
                        <a class="collapse-item" href="{{ route('admin.notifications-management.index') }}">
                            <i class="fas fa-bell fa-sm fa-fw mr-1"></i> Notification Mgmt
                        </a>
                        <a class="collapse-item" href="{{ route('admin.academic-years.index') }}">
                            <i class="fas fa-calendar-alt fa-sm fa-fw mr-1"></i> Academic Years
                        </a>
                        <div class="dropdown-divider"></div>
                        <h6 class="collapse-header">Developer Tools</h6>
                        <a class="collapse-item" href="{{ route('admin.api-tokens.index') }}">
                            <i class="fas fa-code fa-sm fa-fw mr-1"></i> API Tokens
                        </a>
                        <a class="collapse-item" href="{{ route('admin.webhooks.index') }}">
                            <i class="fas fa-project-diagram fa-sm fa-fw mr-1"></i> Webhooks
                        </a>

                        <div class="dropdown-divider"></div>
                        <h6 class="collapse-header">API Management</h6>
                        <a class="collapse-item" href="{{ route('admin.api-tokens.index') }}">
                            <i class="fas fa-key fa-sm fa-fw mr-1"></i> API Tokens
                        </a>
                        <a class="collapse-item" href="{{ route('admin.api-documentation.index') }}">
                            <i class="fas fa-book fa-sm fa-fw mr-1"></i> API Documentation
                        </a>
                        <a class="collapse-item" href="/api/documentation">
                            <i class="fas fa-code fa-sm fa-fw mr-1"></i> Swagger UI
                        </a>
                        <a class="collapse-item" href="{{ route('admin.webhooks.index') }}">
                            <i class="fas fa-exchange-alt fa-sm fa-fw mr-1"></i> Webhooks
                        </a>
                    @endif
                </div>
            </div>
        </li>
    @endif

    <!-- Notifications -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseNotifications"
            aria-expanded="true" aria-controls="collapseNotifications">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        <div id="collapseNotifications" class="collapse" aria-labelledby="headingNotifications"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Notification Management:</h6>
                <a class="collapse-item" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="collapse-item" href="javascript:void(0)" onclick="showNotificationMessage()">
                    <i class="fas fa-list"></i> All Notifications
                </a>
                <a class="collapse-item" href="javascript:void(0)" onclick="showNotificationMessage()">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <h6 class="collapse-header">Quick Actions:</h6>
                <a class="collapse-item" href="javascript:void(0)" onclick="testNotificationSystem()">
                    <i class="fas fa-vial"></i> Test System
                </a>
                <a class="collapse-item" href="javascript:void(0)" onclick="sendFeeReminders()">
                    <i class="fas fa-money-bill-wave"></i> Send Fee Reminders
                </a>
                <a class="collapse-item" href="javascript:void(0)" onclick="checkSystemHealth()">
                    <i class="fas fa-heartbeat"></i> Health Check
                </a>
            </div>
        </div>
    </li>

    <!-- Reports -->
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view reports'))
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports"
                aria-expanded="false">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Reports</span>
            </a>
            <div id="collapseReports" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    @if(auth()->user()->hasRole('super-admin'))
                        <a class="collapse-item" href="{{ route('admin.reports.attendance.index') }}">Attendance
                            Reports</a>
                        <a class="collapse-item" href="{{ route('admin.reports.financial.show') }}">Financial
                            Reports</a>
                        <a class="collapse-item" href="{{ route('admin.reports.assets.index') }}">Asset Reports</a>
                        <a class="collapse-item" href="{{ route('admin.reports.admissions.index') }}">Admissions
                            Funnel</a>
                        <a class="collapse-item" href="{{ route('admin.reports.referrals.index') }}">Referral
                            Tracking</a>
                    @else
                        @can('view reports')
                            <a class="collapse-item" href="{{ route('admin.reports.attendance.index') }}">Attendance
                                Reports</a>
                            <a class="collapse-item" href="{{ route('admin.reports.financial.show') }}">Financial
                                Reports</a>
                            <a class="collapse-item" href="{{ route('admin.reports.assets.index') }}">Asset Reports</a>
                            <a class="collapse-item" href="{{ route('admin.reports.admissions.index') }}">Admissions
                                Funnel</a>
                            <a class="collapse-item" href="{{ route('admin.reports.referrals.index') }}">Referral
                                Tracking</a>
                        @endcan
                    @endif
                </div>
            </div>
        </li>
    @endif

    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>