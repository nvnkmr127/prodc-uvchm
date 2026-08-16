                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow-sm glass-panel mx-3 mt-3 rounded-lg" style="border-radius: 1.25rem;">
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
                        <!-- Academic Year Switcher (Premium Context Indicator) -->
                        @if(isset($allAcademicYears) && $allAcademicYears->isNotEmpty())
                            @php
                                $selectedYear = $allAcademicYears->firstWhere('id', $selectedAcademicYearId);
                            @endphp
                            <li class="nav-item dropdown no-arrow mx-2 d-none d-sm-block">
                                <a class="nav-link dropdown-toggle px-3" href="#" id="yearDropdown" role="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" 
                                    style="background: rgba(231, 234, 243, 0.7); border-radius: 50px; height: 38px; margin-top: 16px; border: 1px solid #d1d3e2;">
                                    <i class="fas fa-calendar-check fa-fw text-primary mr-1"></i>
                                    <span class="text-gray-800 small font-weight-bold">
                                        {{ $selectedYear?->name ?? 'Select Session' }}
                                    </span>
                                    <i class="fas fa-chevron-down ml-2 text-gray-400" style="font-size: 0.7rem;"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow border-0 animated--fade-in mt-2"
                                    aria-labelledby="yearDropdown" style="border-radius: 12px; min-width: 240px;">
                                    <div class="dropdown-header bg-light py-2" style="border-radius: 12px 12px 0 0;">
                                        <h6 class="m-0 font-weight-bold text-primary small uppercase">System Session Context</h6>
                                    </div>
                                    <form action="{{ route('admin.academic-years.switch') }}" method="POST" id="academicYearForm">
                                        @csrf
                                        <input type="hidden" name="academic_year_id" id="selected_year_input">
                                    </form>
                                    <div class="py-1" style="max-height: 300px; overflow-y: auto;">
                                        @foreach($allAcademicYears as $year)
                                            <a class="dropdown-item switch-year-btn d-flex align-items-center justify-content-between py-2 {{ $year->id == $selectedAcademicYearId ? 'bg-light font-weight-bold' : '' }}" 
                                               href="#" data-year-id="{{ $year->id }}">
                                                <div>
                                                    <i class="fas fa-calendar-alt fa-sm fa-fw mr-2 {{ $year->id == $selectedAcademicYearId ? 'text-primary' : 'text-gray-400' }}"></i>
                                                    {{ $year->name }}
                                                </div>
                                                @if($year->is_current)
                                                    <span class="badge badge-success-light text-success px-2 py-1" style="font-size: 0.65rem; background: #e8f5e9;">Current</span>
                                                @endif
                                                @if($year->id == $selectedAcademicYearId)
                                                    <i class="fas fa-check text-success ml-2" style="font-size: 0.8rem;"></i>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                    <div class="dropdown-divider m-0"></div>
                                    <a class="dropdown-item text-center small text-primary py-2 font-weight-bold" href="{{ route('admin.academic-years.index') }}">
                                        <i class="fas fa-cog mr-1"></i> Manage Years
                                    </a>
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

                        @push('scripts')
                        <script>
                            $(document).ready(function () {
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
                                        if (response.success && response.notifications.length > 0) {
                                            const totalCount = response.notifications.length;
                                            $('#notificationCount').text(totalCount > 9 ? '9+' : totalCount).show();

                                            // Play sound if new notifications arrived
                                            if (totalCount > previousCount && previousCount !== 0) {
                                                const sound = document.getElementById('sound_info');
                                                if (sound) sound.play().catch(e => console.log('Sound error:', e));
                                            }
                                            previousCount = totalCount;
                                            renderNotifications(response.notifications);
                                        } else {
                                            $('#notificationCount').hide();
                                            renderNotifications([]);
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
                                            <button type="button" class="btn btn-sm btn-link text-gray-600 ml-2" onclick="$(this).closest('.notification-toast').removeClass('show')">
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
                        @auth
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
                                        @if(method_exists(Auth::user(), 'roles') && Auth::user()->roles->isNotEmpty())
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
                        @endauth
                    </ul>
                </nav>
