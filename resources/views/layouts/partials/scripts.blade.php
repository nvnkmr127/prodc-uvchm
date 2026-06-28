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
    <!-- Core Libraries (Already loaded in head) -->
    {{-- <script src="{{ asset('admin_theme/vendor/jquery/jquery.js') }}"></script> --}}
    {{-- <script src="{{ asset('admin_theme/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script> --}}
    {{-- <script src="{{ asset('admin_theme/vendor/jquery-easing/jquery.easing.min.js') }}"></script> --}}
    {{-- <script src="{{ asset('admin_theme/js/sb-admin-2.min.js') }}"></script> --}}


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
