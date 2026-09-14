@extends('layouts.theme')

@section('title', 'Faculty Attendance & Timing')

@push('styles')
<style>
    /* Modern Dashboard Styling for Faculty Attendance */
    .attendance-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.75rem;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
    }
    
    .stats-card-modern {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 4px 15px -2px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease-in-out;
        position: relative;
        overflow: hidden;
        height: 100%;
    }
    .stats-card-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }
    
    .stats-card-modern .icon-wrapper {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    
    .icon-emerald { background: #ecfdf5; color: #059669; }
    .icon-amber { background: #fffbeb; color: #d97706; }
    .icon-rose { background: #fff1f2; color: #e11d48; }
    .icon-indigo { background: #eef2ff; color: #4f46e5; }
    .icon-sky { background: #f0f9ff; color: #0284c7; }
    .icon-purple { background: #faf5ff; color: #9333ea; }
    
    .stats-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .stats-label {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .stats-subtext {
        font-size: 0.775rem;
        color: #94a3b8;
    }
    
    /* Modern Holiday Banner */
    .holiday-banner {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 1px solid #f59e0b;
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        color: #92400e;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
    }
    .holiday-banner .holiday-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #f59e0b;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    /* Filter Card */
    .filter-card-modern {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        margin-bottom: 1.75rem;
    }
    .filter-card-modern .card-header {
        background: transparent;
        border-bottom: 1px solid #f1f5f9;
        padding: 1rem 1.5rem;
    }
    
    /* Modern Badges */
    .badge-modern {
        padding: 0.35rem 0.65rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.02em;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .badge-modern-present { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-modern-late { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-modern-halfday { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-modern-absent { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
    .badge-modern-leave { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
    .badge-modern-holiday { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    
    /* Table Styling */
    .modern-table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.9rem 1rem;
        border-top: none;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .modern-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.875rem;
    }
    .modern-table tbody tr:hover {
        background-color: #f8fafc;
    }
    
    /* Avatar Circle */
    .avatar-initials {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.25);
    }
    
    /* Pulse Live Dot */
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #10b981;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        70% {
            box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }

    /* AJAX Loading state */
    #attendanceContentArea {
        transition: opacity 0.2s ease-in-out;
        position: relative;
    }
    #attendanceContentArea.loading-active {
        opacity: 0.45;
        pointer-events: none;
    }
    .ajax-loader-badge {
        display: none;
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 99;
        background: #0f172a;
        color: #fff;
        padding: 8px 18px;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        font-size: 0.85rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4">

    {{-- Filter Card (Persistent Controls) --}}
    <div class="filter-card-modern">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <span class="mr-2 text-success"><i class="fas fa-filter"></i></span>
                <span class="font-weight-bold text-gray-800" style="font-size: 0.95rem;">Instant AJAX Filters & Search</span>
            </div>
            <div class="d-flex align-items-center">
                <span id="filterStatusIndicator" class="small text-muted mr-3 d-none d-md-inline">
                    <i class="fas fa-check-circle text-success mr-1"></i> Live Auto-Sync
                </span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="resetFilters()">
                    <i class="fas fa-redo-alt mr-1"></i> Reset
                </button>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">
            <form id="filterForm" method="GET" action="{{ route('admin.faculty-attendance.index') }}">
                <div class="form-row">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Search Faculty</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" name="search" id="searchInput" class="form-control border-left-0" 
                                   placeholder="Name or ID..." value="{{ $searchQuery }}" autocomplete="off">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Department</label>
                        <select name="department" id="deptSelect" class="form-control form-control-sm custom-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ $departmentFilter == $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Status</label>
                        <select name="status" id="statusSelect" class="form-control form-control-sm custom-select">
                            <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="present" {{ $statusFilter == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="late" {{ $statusFilter == 'late' ? 'selected' : '' }}>Late Arrival</option>
                            <option value="half_day" {{ $statusFilter == 'half_day' ? 'selected' : '' }}>Half Day</option>
                            <option value="absent" {{ $statusFilter == 'absent' ? 'selected' : '' }}>Absent</option>
                            <option value="excused" {{ $statusFilter == 'excused' ? 'selected' : '' }}>On Leave</option>
                            <option value="holiday" {{ $statusFilter == 'holiday' ? 'selected' : '' }}>Holiday / Off</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Date Range</label>
                        <select name="date_range" id="dateRangePreset" class="form-control form-control-sm custom-select" onchange="handleDatePresetChange()">
                            <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="this_week" {{ $dateRange == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="last_week" {{ $dateRange == 'last_week' ? 'selected' : '' }}>Last Week</option>
                            <option value="this_month" {{ $dateRange == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $dateRange == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-12 mb-3" id="customDateInputs" style="display: {{ $dateRange == 'custom' ? 'block' : 'none' }};">
                        <div class="form-row">
                            <div class="col-6">
                                <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">Start Date</label>
                                <input type="date" name="start_date" id="startDateInput" class="form-control form-control-sm" 
                                       value="{{ $startDate ? $startDate->toDateString() : '' }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs font-weight-bold text-uppercase text-gray-600">End Date</label>
                                <input type="date" name="end_date" id="endDateInput" class="form-control form-control-sm" 
                                       value="{{ $endDate ? $endDate->toDateString() : '' }}">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Dynamic Content Area (Replaced via AJAX) --}}
    <div id="attendanceContentArea">
        <div class="ajax-loader-badge" id="ajaxLoaderBadge">
            <i class="fas fa-circle-notch fa-spin mr-2 text-success"></i> Updating records...
        </div>
        @include('admin.faculty.attendance.partials.content')
    </div>

</div>
@endsection

@push('scripts')
<script>
    let searchDebounceTimer = null;

    function handleDatePresetChange() {
        const preset = $('#dateRangePreset').val();
        if (preset === 'custom') {
            $('#customDateInputs').slideDown(150);
            $('#startDateInput, #endDateInput').prop('required', true);
        } else {
            $('#customDateInputs').slideUp(150);
            $('#startDateInput, #endDateInput').prop('required', false);
            triggerAjaxFilter();
        }
    }

    function triggerAjaxFilter(url = null) {
        const $container = $('#attendanceContentArea');
        const $loader = $('#ajaxLoaderBadge');
        
        let targetUrl = url;
        if (!targetUrl) {
            const formData = $('#filterForm').serialize();
            targetUrl = "{{ route('admin.faculty-attendance.index') }}?" + formData;
        }

        $container.addClass('loading-active');
        $loader.fadeIn(150);

        $.ajax({
            url: targetUrl,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (html) {
                $container.html(html);
                $loader.fadeOut(100);
                $container.removeClass('loading-active');

                // Update browser URL without reload
                if (window.history && window.history.pushState) {
                    window.history.pushState(null, '', targetUrl);
                }
            },
            error: function (xhr) {
                $loader.fadeOut(100);
                $container.removeClass('loading-active');
                console.error('AJAX Attendance Error:', xhr);
            }
        });
    }

    // Event Listeners for AJAX Filter
    $(document).ready(function () {
        // Search debounce
        $('#searchInput').on('input keyup', function () {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function () {
                triggerAjaxFilter();
            }, 350);
        });

        // Instant dropdown changes
        $('#deptSelect, #statusSelect').on('change', function () {
            triggerAjaxFilter();
        });

        // Custom date pickers
        $('#startDateInput, #endDateInput').on('change', function () {
            if ($('#dateRangePreset').val() === 'custom') {
                if ($('#startDateInput').val() && $('#endDateInput').val()) {
                    triggerAjaxFilter();
                }
            }
        });

        // Prevent native form submit
        $('#filterForm').on('submit', function (e) {
            e.preventDefault();
            triggerAjaxFilter();
        });

        // Delegate pagination link clicks to AJAX
        $(document).on('click', '#attendanceContentArea .pagination a', function (e) {
            e.preventDefault();
            const pageUrl = $(this).attr('href');
            if (pageUrl) {
                triggerAjaxFilter(pageUrl);
                $('html, body').animate({
                    scrollTop: $("#attendanceContentArea").offset().top - 80
                }, 250);
            }
        });

        // Browser back/forward navigation support
        window.addEventListener('popstate', function () {
            triggerAjaxFilter(window.location.href);
        });
    });

    function resetFilters() {
        $('#searchInput').val('');
        $('#deptSelect').val('');
        $('#statusSelect').val('all');
        $('#dateRangePreset').val('today');
        $('#customDateInputs').hide();
        $('#startDateInput').val('');
        $('#endDateInput').val('');
        triggerAjaxFilter("{{ route('admin.faculty-attendance.index') }}");
    }

    function exportData(format) {
        const searchParams = new URLSearchParams($('#filterForm').serialize());
        window.location.href = "{{ route('admin.faculty-attendance.export', ['format' => ':format']) }}".replace(':format', format) + '?' + searchParams.toString();
    }
</script>
@endpush
