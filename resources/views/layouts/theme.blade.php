<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(Auth::check() && Auth::user()->tokens()->count() > 0)
        <meta name="api-token" content="{{ Auth::user()->tokens()->first()->name ?? '' }}">
    @endif
    <title>@yield('title', 'Dashboard') - {{ setting('app_name', 'College Management System') }}</title>

    <!-- Font Awesome -->
    <link href="{{ asset('admin_theme/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap & Theme -->
    <link href="{{ asset('admin_theme/css/sb-admin-2.min.css') }}" rel="stylesheet">
    <!-- External Libraries -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link href="{{ asset('admin_theme/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/gridstack@10.1.2/dist/gridstack.min.css" rel="stylesheet" />
    <link href="{{ asset('css/modern-theme.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('css/mobile-overrides.css') }}?v={{ time() }}" rel="stylesheet">

    @stack('styles')
</head>

<body id="page-top">


    <!-- Toast Notification Container -->
    <div id="notificationToastContainer" aria-live="polite" aria-atomic="true"></div>

    <div id="wrapper">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center"
                href="{{ route('admin.dashboard') }}">
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
                        @php
                            $pendingReqCount = \Illuminate\Support\Facades\DB::table('student_profile_requests')->where('status', 'pending')->count();
                        @endphp
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
                                    @php
                                        $unmappedCount = \App\Models\Student::where('status', 'active')
                                            ->whereNull('biometric_employee_code')->count();
                                    @endphp
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
                                            @php
                                                $unmappedCount = \App\Models\Student::where('status', 'active')
                                                    ->whereNull('biometric_employee_code')->count();
                                            @endphp
                                            @if($unmappedCount > 0)
                                                <span class="badge badge-warning ml-1">{{ $unmappedCount }}</span>
                                            @endif
                                        </a>
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
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFO"
                        aria-expanded="false">
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
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseHR"
                        aria-expanded="false">
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
                                <a class="collapse-item" href="{{ route('admin.academic-years.index') }}">
                                    <i class="fas fa-calendar-alt fa-sm fa-fw mr-1"></i> Academic Years
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

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow-sm glass-panel mx-3 mt-3 rounded-lg"
                    style="border-radius: 1.25rem;">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3 text-primary">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <form
                        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search position-relative">
                        <div class="input-group">
                            <input type="text" class="form-control bg-transparent border-0 small"
                                placeholder="Quick search (Ctrl + K)..." aria-label="Search"
                                aria-describedby="basic-addon2" id="global-search-input"
                                style="background: rgba(255,255,255,0.5) !important;">
                            <div class="input-group-append">
                                <button class="btn btn-light" type="button">
                                    <i class="fas fa-search fa-sm text-gray-500"></i>
                                </button>
                            </div>
                        </div>
                        <div id="ajax-search-results" class="dropdown-menu shadow animated--grow-in mt-2"
                            style="width: 300px; display: none; position: absolute; top: 100%; left: 0; z-index: 1000; max-height: 400px; overflow-y: auto;">
                            <!-- Results will be loaded here -->
                        </div>
                    </form>

                    <!-- Top Navigation -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Academic Year Switcher -->
                        @if(isset($allAcademicYears) && $allAcademicYears->isNotEmpty())
                            <li class="nav-item dropdown no-arrow mx-1">
                                <a class="nav-link dropdown-toggle" href="#" id="yearDropdown" role="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Academic Year">
                                    <i class="fas fa-calendar-alt fa-fw text-gray-600"></i>
                                    <span class="d-none d-lg-inline text-gray-600 small">
                                        {{ $allAcademicYears->firstWhere('id', $selectedAcademicYearId)->name ?? 'Select Year' }}
                                    </span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                    aria-labelledby="yearDropdown">
                                    <h6 class="dropdown-header">Switch Academic Year</h6>
                                    <form action="{{ route('admin.academic-years.switch') }}" method="POST"
                                        id="academicYearForm">
                                        @csrf
                                        <input type="hidden" name="academic_year_id" id="selected_year_input">
                                    </form>
                                    @foreach($allAcademicYears as $year)
                                        <a class="dropdown-item switch-year-btn" href="#" data-year-id="{{ $year->id }}">
                                            <i
                                                class="fas fa-check fa-sm fa-fw mr-2 text-gray-400 {{ $year->id == $selectedAcademicYearId ? '' : 'invisible' }}"></i>
                                            {{ $year->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </li>
                        @endif

                        <!-- Quick Actions -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="quickActionsDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Quick Actions">
                                <i class="fas fa-plus-circle fa-fw text-gray-600"></i>
                                <span class="d-none d-lg-inline text-gray-600 small">Quick Add</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="quickActionsDropdown" style="min-width: 280px;">
                                <h6 class="dropdown-header">
                                    <i class="fas fa-bolt text-primary"></i> Quick Actions
                                </h6>
                                @can('create students')
                                    <a class="dropdown-item d-flex align-items-center py-3"
                                        href="{{ route('admin.students.create') }}">
                                        <div class="mr-3">
                                            <div class="icon-circle bg-success">
                                                <i class="fas fa-user-plus text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small text-gray-500">Add New</div>
                                            <strong class="text-gray-800">Student</strong>
                                        </div>
                                    </a>
                                @endcan

                                @can('take attendance')
                                    <a class="dropdown-item d-flex align-items-center py-3"
                                        href="{{ route('admin.daily-attendance.create') }}">
                                        <div class="mr-3">
                                            <div class="icon-circle bg-info">
                                                <i class="fas fa-user-check text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small text-gray-500">Take</div>
                                            <strong class="text-gray-800">Attendance</strong>
                                        </div>
                                    </a>
                                @endcan

                                @can('create enquiries')
                                    <a class="dropdown-item d-flex align-items-center py-3"
                                        href="{{ route('admin.enquiries.create') }}">
                                        <div class="mr-3">
                                            <div class="icon-circle bg-warning">
                                                <i class="fas fa-user-plus text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small text-gray-500">Add</div>
                                            <strong class="text-gray-800">Enquiry</strong>
                                        </div>
                                    </a>
                                @endcan

                                @can('manage courses')
                                    <a class="dropdown-item d-flex align-items-center py-3"
                                        href="{{ route('admin.courses.create') }}">
                                        <div class="mr-3">
                                            <div class="icon-circle bg-primary">
                                                <i class="fas fa-graduation-cap text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small text-gray-500">Create</div>
                                            <strong class="text-gray-800">Course</strong>
                                        </div>
                                    </a>
                                @endcan

                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center small text-gray-500"
                                    href="{{ route('admin.dashboard') }}">
                                    <i class="fas fa-tachometer-alt mr-1"></i> View Dashboard
                                </a>
                            </div>
                        </li>

                        <!-- Enhanced Notifications -->
                        <li class="nav-item dropdown no-arrow mx-1" id="notificationDropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <span class="badge badge-danger badge-counter" id="notificationCount"
                                    style="display: none;">0</span>
                            </a>

                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="alertsDropdown" style="width: 350px;">
                                <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>Alerts Center</span>
                                    <a href="#" class="text-white small" onclick="markAllRead(event)"
                                        style="text-decoration: underline;">Mark All Read</a>
                                </h6>

                                <div id="notificationList" style="max-height: 300px; overflow-y: auto;">
                                    <div class="text-center py-3 text-gray-500 small">Loading...</div>
                                </div>

                                <a class="dropdown-item text-center small text-gray-500"
                                    href="{{ route('admin.notifications.index') }}">Show All Alerts</a>
                            </div>
                        </li>

                        <audio id="sound_success" src="{{ asset('sounds/success.mp3') }}" preload="auto"></audio>
                        <audio id="sound_warning" src="{{ asset('sounds/warning.mp3') }}" preload="auto"></audio>
                        <audio id="sound_error" src="{{ asset('sounds/error.mp3') }}" preload="auto"></audio>
                        <audio id="sound_info" src="{{ asset('sounds/notification.mp3') }}" preload="auto"></audio>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                // 1. Load immediately
                                loadNotifications();

                                // 2. Poll every 30 seconds
                                setInterval(loadNotifications, 30000);
                            });

                            let previousCount = 0;

                            function loadNotifications() {
                                $.ajax({
                                    url: '{{ route("admin.notifications.recent") }}',
                                    method: 'GET',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            updateBellUI(response);
                                        }
                                    },
                                    error: function (err) {
                                        console.error('Notification poll error:', err);
                                    }
                                });
                            }

                            function updateBellUI(data) {
                                const count = data.unread_count;
                                const list = data.notifications;

                                // Update Badge
                                const badge = $('#notificationCount');
                                if (count > 0) {
                                    badge.text(count > 99 ? '99+' : count).show();

                                    // Play sound if new notifications arrived
                                    if (count > previousCount) {
                                        playSound(list[0]?.type || 'info');
                                    }
                                } else {
                                    badge.hide();
                                }
                                previousCount = count;

                                // Update Dropdown List
                                const container = $('#notificationList');

                                if (!list || list.length === 0) {
                                    container.html('<div class="text-center py-3 text-gray-500 small">No new notifications</div>');
                                    return;
                                }

                                let html = '';
                                list.forEach(notif => {
                                    // Choose icon based on type
                                    let icon = 'fa-info-circle';
                                    let bg = 'bg-primary';

                                    if (notif.type === 'warning') { icon = 'fa-exclamation-triangle'; bg = 'bg-warning'; }
                                    else if (notif.type === 'error') { icon = 'fa-exclamation-circle'; bg = 'bg-danger'; }
                                    else if (notif.type === 'success') { icon = 'fa-check-circle'; bg = 'bg-success'; }

                                    // Format Time (simple JS fallback if human readable not provided)
                                    const time = notif.created_at_human || new Date(notif.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                                    html += `
                                        <a class="dropdown-item d-flex align-items-center" href="${notif.action_url || '#'}" onclick="markAsRead(${notif.id})">
                                            <div class="mr-3">
                                                <div class="icon-circle ${bg}">
                                                    <i class="fas ${icon} text-white"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="small text-gray-500">${time}</div>
                                                <span class="font-weight-bold d-block text-truncate" style="max-width: 200px;">${notif.title}</span>
                                                <span class="small text-gray-600 text-truncate" style="max-width: 200px; display:block;">${notif.message}</span>
                                            </div>
                                        </a>
                                    `;
                                });

                                container.html(html);
                            }

                            function markAsRead(id) {
                                $.ajax({
                                    url: '{{ route("admin.notifications.mark-read") }}',
                                    method: 'POST',
                                    data: {
                                        id: id,
                                        _token: '{{ csrf_token() }}'
                                    },
                                    success: function () {
                                        loadNotifications();
                                    }
                                });
                            }

                            function markAllRead(e) {
                                if (e) {
                                    e.preventDefault();
                                    e.stopPropagation(); // Keep dropdown open
                                }

                                $.ajax({
                                    url: '{{ route("admin.notifications.mark-all-read") }}',
                                    method: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}'
                                    },
                                    success: function () {
                                        loadNotifications(); // Refresh UI
                                        showToast('success', 'All notifications marked as read');
                                    }
                                });
                            }

                            function playSound(type) {
                                const audio = document.getElementById('sound_' + type) || document.getElementById('sound_info');
                                if (audio) {
                                    audio.play().catch(e => console.log('Audio autoplay blocked by browser policy'));
                                }
                            }

                            // Show Toast Notification
                            function showToast(type, message, title = '') {
                                const toast = $(`
                                    <div class="notification-toast ${type}">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} fa-lg"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                ${title ? `<div class="font-weight-bold">${title}</div>` : ''}
                                                <div class="${title ? 'small' : ''}">${message}</div>
                                            </div>
                                            <button class="btn btn-sm btn-link text-gray-600 ml-2" onclick="$(this).closest('.notification-toast').removeClass('show')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                `);

                                $('#notificationToastContainer').append(toast);
                                setTimeout(() => toast.addClass('show'), 100);
                                setTimeout(() => {
                                    toast.removeClass('show');
                                    setTimeout(() => toast.remove(), 300);
                                }, 5000);
                            }

                            // Test System Functions
                            function testNotificationSystem() {
                                showToast('info', 'Testing notification system...', 'System Test');
                            }

                            function sendFeeReminders() {
                                showToast('warning', 'Sending fee reminders...', 'Fee Reminders');
                            }

                            function checkSystemHealth() {
                                showToast('success', 'System health check completed!', 'Health Check');
                            }

                            function showNotificationMessage() {
                                // Redirect to full notification page
                                window.location.href = '{{ route("admin.notifications.index") }}';
                            }
                        </script>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Enhanced User Menu -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span
                                    class="mr-2 d-none d-lg-inline text-gray-600 small font-weight-bold">{{ Auth::user()->name }}</span>
                                <img class="img-profile rounded-circle"
                                    src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=667eea&color=fff&size=32"
                                    alt="Profile Picture">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <div class="dropdown-header text-center py-3">
                                    <img class="img-profile rounded-circle mb-2"
                                        src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=667eea&color=fff&size=64"
                                        alt="Profile Picture" style="width: 64px; height: 64px;">
                                    <div class="font-weight-bold text-gray-800">{{ Auth::user()->name }}</div>
                                    <div class="small text-gray-500">{{ Auth::user()->email }}</div>
                                    @if(Auth::user()->roles->isNotEmpty())
                                        <span
                                            class="badge badge-primary mt-1">{{ Auth::user()->roles->first()->name }}</span>
                                    @endif
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile Settings
                                </a>
                                <a class="dropdown-item" href="{{ route('admin.calendar.index') }}">
                                    <i class="fas fa-calendar fa-sm fa-fw mr-2 text-gray-400"></i>
                                    My Calendar
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="#" data-toggle="modal"
                                    data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <!-- Main Content Area -->
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>

            <!-- Modern Footer -->
            <footer class="sticky-footer bg-white no-print">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span class="text-gray-600">Copyright &copy; {{ setting('college_name', 'Your College') }}
                            {{ date('Y') }}</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Enhanced Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 bg-gradient-primary text-white">
                    <h5 class="modal-title" id="logoutModalLabel">
                        <i class="fas fa-sign-out-alt mr-2"></i>Confirm Logout
                    </h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-question-circle fa-3x text-warning mb-3"></i>
                    <h6 class="font-weight-bold text-gray-800 mb-2">Are you sure you want to logout?</h6>
                    <p class="text-gray-600 mb-0">You will need to sign in again to access your account.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button class="btn btn-secondary px-4" type="button" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="{{ asset('admin_theme/vendor/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin_theme/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('admin_theme/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('admin_theme/js/sb-admin-2.min.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="{{ asset('admin_theme/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin_theme/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@10.1.2/dist/gridstack-all.js"></script>

    <!-- Enhanced JavaScript Functionality -->
    <script>
        $(document).ready(function () {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Initialize popovers
            $('[data-toggle="popover"]').popover();

            // Enhanced Global Search
            let searchTimeout;
            $('#global-search-input').on('input', function () {
                clearTimeout(searchTimeout);
                const query = $(this).val().trim();

                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        performGlobalSearch(query);
                    }, 300);
                } else {
                    $('#ajax-search-results').fadeOut();
                }
            });

            // Hide search results when clicking outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.navbar-search').length) {
                    $('#ajax-search-results').fadeOut();
                }
            });

            // Academic Year Switcher
            $('.switch-year-btn').on('click', function (e) {
                e.preventDefault();
                const yearId = $(this).data('year-id');
                $('#selected_year_input').val(yearId);
                $('#academicYearForm').submit();
            });

            // Load notifications on dropdown open
            $('#alertsDropdown').on('show.bs.dropdown', function () {
                loadNotifications();
            });

            // Auto-refresh notifications every 30 seconds
            setInterval(loadNotifications, 30000);

            // Load initial notifications
            loadNotifications();
        });

        // Global Search Function - FIXED
        function performGlobalSearch(query) {
            $('#ajax-search-results').html('<div class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>').fadeIn();

            $.ajax({
                url: '{{ route("admin.global-search") }}',
                method: 'GET',
                data: { q: query },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    let html = '';

                    if (response.results && response.results.length > 0) {
                        response.results.forEach(function (result) {
                            // Determine icon based on type
                            let iconClass = 'fa-search';
                            let bgColor = 'info';

                            if (result.icon) {
                                iconClass = result.icon;
                            }

                            if (result.type === 'Student') {
                                bgColor = 'primary';
                            } else if (result.type === 'Faculty') {
                                bgColor = 'success';
                            } else if (result.type === 'Batch') {
                                bgColor = 'warning';
                            } else if (result.type === 'Course') {
                                bgColor = 'info';
                            }

                            html += `
                                <a href="${result.url}" class="dropdown-item d-flex align-items-center py-3">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-${bgColor}">
                                            <i class="fas ${iconClass} text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold text-gray-800">${result.title}</div>
                                        <div class="small text-gray-500">${result.subtitle || ''}</div>
                                        <span class="badge badge-${bgColor} badge-sm mt-1">${result.type}</span>
                                    </div>
                                </a>
                            `;
                        });
                    } else {
                        html = '<div class="p-3 text-center text-gray-500"><i class="fas fa-search mr-2"></i>No results found for "' + query + '"</div>';
                    }

                    $('#ajax-search-results').html(html);
                },
                error: function (xhr, status, error) {
                    console.error('Search error:', error);
                    $('#ajax-search-results').html('<div class="p-3 text-center text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Search failed. Please try again.</div>');
                }
            });
        }

        // Load Notifications
        function loadNotifications() {
            // Simple fallback implementation - replace with actual notification loading
            updateNotificationCount(0);
            updateNotificationList([]);

            // Uncomment this when you create the proper notification routes
            /*
            $.ajax({
                url: '{{ url("/admin/notifications/recent") }}',
            method: 'GET',
                headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                updateNotificationCount(response.unread_count);
                updateNotificationList(response.notifications);
            },
            error: function() {
                $('#notificationList').html('<div class="p-3 text-center text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load notifications</div>');
            }
        });
            */
        }

        // Update Notification Count
        function updateNotificationCount(count) {
            const badge = $('#notificationCount');
            if (count > 0) {
                badge.text(count > 99 ? '99+' : count).show();
            } else {
                badge.hide();
            }
        }

        // Update Notification List
        function updateNotificationList(notifications) {
            let html = '';

            if (notifications && notifications.length > 0) {
                notifications.forEach(function (notification) {
                    const isUnread = !notification.read_at;
                    html += `
                        <a href="#" class="dropdown-item d-flex align-items-center py-3 ${isUnread ? 'bg-light' : ''}" onclick="markNotificationAsRead('${notification.id}')">
                            <div class="mr-3">
                                <div class="icon-circle bg-${notification.type === 'success' ? 'success' : notification.type === 'warning' ? 'warning' : 'info'}">
                                    <i class="fas fa-${notification.icon || 'bell'} text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold text-gray-800">${notification.title}</div>
                                <div class="small text-gray-600">${notification.message}</div>
                                <div class="small text-gray-400">${notification.created_at_human}</div>
                                ${isUnread ? '<span class="badge badge-primary badge-sm">New</span>' : ''}
                            </div>
                        </a>
                    `;
                });
            } else {
                html = '<div class="p-3 text-center text-gray-500"><i class="fas fa-bell-slash mr-2"></i>No notifications</div>';
            }

            $('#notificationList').html(html);
            $('#loadingNotifications').hide();
        }

        // Mark Notification as Read
        function markNotificationAsRead(notificationId) {
            $.ajax({
                url: '{{ route("admin.notifications.mark-read") }}',
                method: 'POST',
                data: {
                    notification_id: notificationId,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function () {
                    loadNotifications();
                }
            });
        }

        // Mark All Notifications as Read
        function markAllNotificationsAsRead() {
            $.ajax({
                url: '{{ route("admin.notifications.mark-all-read") }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function () {
                    loadNotifications();
                    showToast('success', 'All notifications marked as read!');
                }
            });
        }

        // Show Toast Notification
        function showToast(type, message, title = '') {
            const toast = $(`
                <div class="notification-toast ${type}">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            ${title ? `<div class="font-weight-bold">${title}</div>` : ''}
                            <div class="${title ? 'small' : ''}">${message}</div>
                        </div>
                        <button class="btn btn-sm btn-link text-gray-600 ml-2" onclick="$(this).closest('.notification-toast').removeClass('show')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `);

            $('#notificationToastContainer').append(toast);
            setTimeout(() => toast.addClass('show'), 100);
            setTimeout(() => {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Test System Functions (to be implemented based on your needs)
        function testNotificationSystem() {
            showToast('info', 'Testing notification system...', 'System Test');
        }

        function sendFeeReminders() {
            showToast('warning', 'Sending fee reminders...', 'Fee Reminders');
        }

        function checkSystemHealth() {
            showToast('success', 'System health check completed!', 'Health Check');
        }

        function showNotificationMessage() {
            showToast('info', 'Notification system will be implemented by your developer', 'Coming Soon');
        }
    </script>

    <!-- Global Search Modal (Ctrl+K) - Enhanced Modern UI -->
    <div class="modal fade" id="globalSearchModal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content"
                style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;">
                <!-- Search Header -->
                <div class="modal-body p-0">
                    <!-- Search Input Area -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px 30px;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-search text-white mr-3" style="font-size: 1.3rem; opacity: 0.9;"></i>
                            <input type="text" id="globalSearchInput" class="form-control form-control-lg"
                                placeholder="Search students, courses, batches, faculty..."
                                style="border: none; background: rgba(255,255,255,0.2); color: white; font-size: 1.15rem; border-radius: 8px; padding: 12px 20px;"
                                autocomplete="off">
                            <button type="button" class="btn btn-link text-white ml-2" data-dismiss="modal"
                                style="font-size: 1.2rem; padding: 8px 12px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <!-- Search Tips -->
                        <div class="mt-3 d-flex justify-content-between align-items-center" style="opacity: 0.85;">
                            <small class="text-white">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Start typing to search...
                            </small>
                            <div>
                                <kbd
                                    style="background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem;">ESC</kbd>
                                <span class="text-white ml-1" style="font-size: 0.8rem;">to close</span>
                            </div>
                        </div>
                    </div>

                    <!-- Search Results Area -->
                    <div id="globalSearchResults"
                        style="max-height: 65vh; overflow-y: auto; min-height: 300px; background: #f8f9fc;">
                        <!-- Empty State -->
                        <div class="text-center py-5 px-4" id="searchEmptyState">
                            <div class="mb-4">
                                <i class="fas fa-search fa-4x mb-3" style="color: #d1d3e2;"></i>
                            </div>
                            <h5 class="text-gray-700 mb-2">Quick Search</h5>
                            <p class="text-gray-600 mb-4">Find students, courses, batches, and faculty members instantly
                            </p>

                            <!-- Quick Tips -->
                            <div class="row text-left mt-4">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-user-graduate text-primary mr-2 mt-1"></i>
                                        <div>
                                            <strong class="d-block text-gray-800">Students</strong>
                                            <small class="text-muted">Search by name, enrollment, or mobile</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-book text-info mr-2 mt-1"></i>
                                        <div>
                                            <strong class="d-block text-gray-800">Courses</strong>
                                            <small class="text-muted">Search by name or course code</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-users text-warning mr-2 mt-1"></i>
                                        <div>
                                            <strong class="d-block text-gray-800">Batches</strong>
                                            <small class="text-muted">Find batch by name</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-chalkboard-teacher text-success mr-2 mt-1"></i>
                                        <div>
                                            <strong class="d-block text-gray-800">Faculty</strong>
                                            <small class="text-muted">Search by name or email</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Keyboard Shortcut -->
                            <div class="mt-4 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="fas fa-keyboard mr-1"></i>
                                    Press <kbd
                                        style="background: #e3e6f0; border: 1px solid #d1d3e2; padding: 2px 8px; border-radius: 4px;">Ctrl+K</kbd>
                                    to open search from anywhere
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom scrollbar for search results */
        #globalSearchResults::-webkit-scrollbar {
            width: 8px;
        }

        #globalSearchResults::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #globalSearchResults::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #globalSearchResults::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Search input placeholder */
        #globalSearchInput::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Search result items hover effect */
        .search-result-item {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .search-result-item:hover {
            background-color: #fff !important;
            border-left-color: #667eea;
            transform: translateX(5px);
        }

        /* Modal animation */
        .modal.fade .modal-dialog {
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.2s ease-out;
        }

        .modal.show .modal-dialog {
            transform: scale(1);
            opacity: 1;
        }
    </style>

    <script>
        // Global Search with Ctrl+K Functionality
        class GlobalSearch {
            constructor() {
                this.modal = $('#globalSearchModal');
                this.input = $('#globalSearchInput');
                this.resultsContainer = $('#globalSearchResults');
                this.searchTimeout = null;
                this.init();
            }

            init() {
                // Keyboard shortcut: Ctrl+K or Cmd+K
                $(document).on('keydown', (e) => {
                    // Check for Ctrl+K (Windows) or Cmd+K (Mac)
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                        e.preventDefault();
                        this.open();
                    }
                });

                // Handle input
                this.input.on('input', () => {
                    clearTimeout(this.searchTimeout);
                    const query = this.input.val().trim();

                    if (query.length < 2) {
                        this.showEmptyState();
                        return;
                    }

                    this.showLoading();

                    this.searchTimeout = setTimeout(() => {
                        this.performSearch(query);
                    }, 300);
                });

                // Handle ESC key
                this.modal.on('keydown', (e) => {
                    if (e.key === 'Escape') {
                        this.close();
                    }
                });

                // Clear on modal hide
                this.modal.on('hidden.bs.modal', () => {
                    this.input.val('');
                    this.showEmptyState();
                });
            }

            open() {
                this.modal.modal('show');
                setTimeout(() => this.input.focus(), 300);
            }

            close() {
                this.modal.modal('hide');
            }

            showLoading() {
                this.resultsContainer.html(`
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <p class="text-muted">Searching...</p>
                </div>
            `);
            }

            showEmptyState() {
                this.resultsContainer.html(`
                <div class="text-center text-muted py-5">
                    <i class="fas fa-search fa-3x mb-3" style="opacity: 0.3;"></i>
                    <p>Type to search across students, courses, batches, and more...</p>
                    <small class="text-muted">Press <kbd>Ctrl+K</kbd> to open search anytime</small>
                </div>
            `);
            }

            performSearch(query) {
                $.ajax({
                    url: '{{ route("admin.global-search") }}',
                    method: 'GET',
                    data: { q: query },
                    success: (response) => {
                        this.displayResults(response.results, query);
                    },
                    error: () => {
                        this.resultsContainer.html(`
                        <div class="text-center text-danger py-5">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p>Search failed. Please try again.</p>
                        </div>
                    `);
                    }
                });
            }

            displayResults(results, query) {
                if (!results || results.length === 0) {
                    this.resultsContainer.html(`
                    <div class="text-center py-5 px-4">
                        <div class="mb-3">
                            <i class="fas fa-search-minus fa-4x" style="color: #d1d3e2;"></i>
                        </div>
                        <h6 class="text-gray-700 mb-2">No results found</h6>
                        <p class="text-muted mb-0">No matches for "<strong class="text-gray-800">${query}</strong>"</p>
                        <small class="text-muted">Try different keywords or check your spelling</small>
                    </div>
                `);
                    return;
                }

                let html = `
                <div class="px-3 py-2 border-bottom bg-white">
                    <small class="text-muted font-weight-bold text-uppercase">
                        <i class="fas fa-check-circle text-success mr-1"></i>
                        Found ${results.length} result${results.length > 1 ? 's' : ''}
                    </small>
                </div>
                <div class="search-results-list">
            `;

                results.forEach((result, index) => {
                    const iconBgColors = {
                        'Student': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                        'Course': 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                        'Batch': 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                        'Faculty': 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
                    };

                    const badgeColors = {
                        'Student': 'badge-primary',
                        'Course': 'badge-info',
                        'Batch': 'badge-warning',
                        'Faculty': 'badge-success'
                    };

                    html += `
                    <a href="${result.url}"
                       class="search-result-item d-block p-3 text-decoration-none"
                       style="background: ${index % 2 === 0 ? '#ffffff' : '#f8f9fc'}; border-left: 3px solid transparent; transition: all 0.2s ease;">
                        <div class="d-flex align-items-center">
                            <div class="mr-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: ${iconBgColors[result.type] || '#e3e6f0'}; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <i class="fas ${result.icon} text-white" style="font-size: 1.2rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-gray-800" style="font-weight: 600; font-size: 0.95rem;">
                                            ${result.title}
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                                            <i class="fas fa-info-circle mr-1" style="font-size: 0.75rem;"></i>
                                            ${result.subtitle || 'No additional info'}
                                        </p>
                                    </div>
                                    <span class="badge ${badgeColors[result.type] || 'badge-secondary'} ml-2" style="font-size: 0.7rem; padding: 4px 10px;">
                                        ${result.type}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-2">
                                <i class="fas fa-chevron-right text-gray-400" style="font-size: 0.8rem;"></i>
                            </div>
                        </div>
                    </a>
                `;
                });

                html += '</div>';
                html += `<div class="text-center mt-3 mb-2"><small class="text-muted">Showing ${results.length} result(s)</small></div>`;

                this.resultsContainer.html(html);
            }
        }

        // Initialize Global Search
        $(document).ready(function () {
            window.globalSearch = new GlobalSearch();
        });
    </script>

    @stack('scripts')

</body>

</html>