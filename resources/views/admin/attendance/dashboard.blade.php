{{-- resources/views/admin/attendance/dashboard.blade.php --}}
@extends('layouts.theme')

@section('title', 'Attendance Dashboard')

@push('styles')
    <style>
        .activity-item:hover {
            background-color: #f8f9fc;
        }

        .activity-avatar .avatar-title {
            width: 32px;
            height: 32px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar .avatar-title {
            width: 40px;
            height: 40px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-buttons .btn {
            margin: 1px;
        }

        #activity-indicator {
            animation: pulse 2s infinite;
            border-radius: 50%;
            font-size: 8px;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .activity-feed {
            scrollbar-width: thin;
            scrollbar-color: #d1d3e2 transparent;
        }

        .activity-feed::-webkit-scrollbar {
            width: 6px;
        }

        .badge-danger-soft {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .badge-success-soft {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .badge-primary-soft {
            background-color: #e0e7ff;
            color: #4f46e5;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .btn-white {
            background-color: #fff;
            color: #4b5563;
        }

        .btn-white:hover {
            background-color: #f9fafb;
            color: #111827;
        }

        .text-danger-500 {
            color: #ef4444;
        }

        .icon-circle {
            height: 2.5rem;
            width: 2.5rem;
            border-radius: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-feed::-webkit-scrollbar-track {
            background: transparent;
        }

        .activity-feed::-webkit-scrollbar-thumb {
            background-color: #d1d3e2;
            border-radius: 3px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Page Header --}}
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt text-primary"></i> Attendance Dashboard
                <small class="text-muted">{{ $selectedDate->format('F j, Y') }}</small>
            </h1>
            <div class="d-sm-flex">
                <button class="btn btn-primary btn-sm mr-2" onclick="refreshDashboard()">
                    <i class="fas fa-sync" id="refresh-icon"></i> Refresh
                </button>
                <span class="badge badge-info" id="last-updated">
                    Last updated: {{ now()->format('H:i:s') }}
                </span>
            </div>
        </div>

        {{-- Date and Filters --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.attendance.dashboard') }}" class="row">
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control"
                            value="{{ $selectedDate->format('Y-m-d') }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label for="course_id" class="form-label">Course</label>
                        <select name="course_id" id="course_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="batch_id" class="form-label">Batch</label>
                        <select name="batch_id" id="batch_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ $batchId == $batch->id ? 'selected' : '' }}>
                                    {{ $batch->name }} ({{ $batch->course->name ?? 'No Course' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex">
                            <a href="{{ route('admin.daily-attendance.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus mr-1"></i>Mark Attendance
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modern Stats Cards --}}
        <div class="row mb-4" id="stats-cards">
            <div class="col-xl col-lg col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 overflow-hidden"
                    style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <div class="card-body text-white">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="opacity: 0.8;">Total
                                    Students</div>
                                <div class="h3 mb-0 font-weight-bold" id="total-students">
                                    {{ $todayStats['students']['total'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="p-3 rounded-circle" style="background: rgba(255,255,255,0.2);">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl col-lg col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 overflow-hidden"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="card-body text-white">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="opacity: 0.8;">Present
                                    Today</div>
                                <div class="h3 mb-0 font-weight-bold" id="present-students">
                                    {{ $todayStats['students']['present'] }}
                                </div>
                                <div class="small mt-1" id="present-percentage" style="opacity: 0.9;">
                                    {{ $todayStats['students']['percentage'] }}% of total
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="p-3 rounded-circle" style="background: rgba(255,255,255,0.2);">
                                    <i class="fas fa-user-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl col-lg col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 overflow-hidden"
                    style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <div class="card-body text-white">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="opacity: 0.8;">Absent Today
                                </div>
                                <div class="h3 mb-0 font-weight-bold" id="absent-students">
                                    {{ $todayStats['students']['absent'] }}
                                </div>
                                <div class="small mt-1" id="absent-percentage" style="opacity: 0.9;">
                                    {{ $todayStats['students']['absent_percentage'] }}% of total
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="p-3 rounded-circle" style="background: rgba(255,255,255,0.2);">
                                    <i class="fas fa-user-times fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl col-lg col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 overflow-hidden"
                    style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">
                    <div class="card-body text-white">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="opacity: 0.8;">Never
                                    Punched</div>
                                <div class="h3 mb-0 font-weight-bold" id="never-punched-stat">
                                    {{ $todayStats['students']['never_punched'] ?? 0 }}
                                </div>
                                <div class="small mt-1" id="never-punched-percentage" style="opacity: 0.9;">
                                    {{ $todayStats['students']['never_punched_percentage'] ?? 0 }}% of total
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="p-3 rounded-circle" style="background: rgba(255,255,255,0.2);">
                                    <i class="fas fa-user-slash fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl col-lg col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 overflow-hidden"
                    style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                    <div class="card-body text-white">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="opacity: 0.8;">On
                                    Internship</div>
                                <div class="h3 mb-0 font-weight-bold" id="internship-students">
                                    {{ $todayStats['students']['internship'] ?? 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="p-3 rounded-circle" style="background: rgba(255,255,255,0.2);">
                                    <i class="fas fa-briefcase fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Dashboard Content --}}
        <div class="row">
            {{-- Absent Students List --}}
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div
                        class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between bg-white border-bottom shadow-sm">
                        <div class="mb-2 mb-md-0">
                            <h6 class="m-0 font-weight-bold text-danger d-flex align-items-center">
                                <div class="icon-circle bg-danger-soft mr-2">
                                    <i class="fas fa-user-times text-danger"></i>
                                </div>
                                <span>Absent Students Today</span>
                                <span class="badge badge-danger-soft ml-2 px-2 py-1"
                                    id="absent-count">{{ $absentStudents->filter(fn($s) => !empty($s['last_attendance']))->count() }}</span>
                            </h6>
                        </div>

                        <div class="d-flex flex-wrap align-items-center">
                            {{-- Unified Filter Controls --}}
                            <div class="input-group input-group-sm mr-2" style="width: 200px;">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light border-right-0"><i
                                            class="fas fa-search text-muted"></i></span>
                                </div>
                                <input type="text" id="absent-search" class="form-control bg-light border-left-0"
                                    placeholder="Search..." onkeyup="filterAbsentStudents()">
                            </div>

                            <div class="input-group input-group-sm mr-2" style="width: 150px;">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light border-right-0"><i
                                            class="fas fa-calendar-alt text-muted"></i></span>
                                </div>
                                <select id="last-present-filter" class="form-control bg-light border-left-0"
                                    onchange="filterAbsentStudents()">
                                    <option value="all">Any History</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="last_3_days">Last 3 Days</option>
                                    <option value="last_7_days">7 Days</option>
                                    <option value="older">Older</option>
                                </select>
                            </div>

                            {{-- Quick Actions --}}
                            <div class="btn-group btn-group-sm mr-2">
                                <button class="btn btn-light border shadow-sm px-3" onclick="selectAllAbsent()"
                                    title="Select All">
                                    <i class="fas fa-check-square"></i>
                                </button>
                                <button class="btn btn-light border shadow-sm px-3" onclick="markSelectedPresent()"
                                    title="Mark Selected Present">
                                    <i class="fas fa-user-check text-success"></i>
                                </button>
                                <button class="btn btn-light border shadow-sm px-3" onclick="refreshAttendanceData()"
                                    title="Refresh List">
                                    <i class="fas fa-sync-alt text-primary" id="refresh-icon-absent"></i>
                                </button>
                            </div>

                            <div class="dropdown no-arrow">
                                <button class="btn btn-link btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                    <button class="dropdown-item" onclick="exportAbsentList()">
                                        <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                                        Export CSV
                                    </button>
                                    <button class="dropdown-item text-danger" onclick="clearSelections()">
                                        <i class="fas fa-redo fa-sm fa-fw mr-2"></i>
                                        Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="absent-students-container" style="max-height: 600px; overflow-y: auto;">
                            @php
                                $filteredAbsentStudents = $absentStudents->filter(function ($student) {
                                    return !empty($student['last_attendance']);
                                });
                            @endphp

                            @if($filteredAbsentStudents->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle" id="absent-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input"
                                                            id="select-all-absent">
                                                        <label class="custom-control-label" for="select-all-absent"></label>
                                                    </div>
                                                </th>
                                                <th style="cursor: pointer;" onclick="sortTable('absent-table', 1)">Student
                                                    Details <i class="fas fa-sort fa-sm text-muted ml-1"></i></th>
                                                <th style="cursor: pointer;" onclick="sortTable('absent-table', 2)">Academic
                                                    Info <i class="fas fa-sort fa-sm text-muted ml-1"></i></th>
                                                <th>Contact Info</th>
                                                <th style="cursor: pointer;" onclick="sortTable('absent-table', 4)">Last Synced
                                                    <i class="fas fa-sort fa-sm text-muted ml-1"></i>
                                                </th>
                                                <th class="text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="absent-students-tbody">
                                            @foreach($filteredAbsentStudents as $student)
                                                @php
                                                    $lastSeenDate = $student['last_attendance'] ? \Carbon\Carbon::parse($student['last_attendance']) : null;
                                                    $daysAgo = $lastSeenDate ? $lastSeenDate->diffInDays(now()) : 999;
                                                @endphp
                                                <tr id="absent-row-{{ $student['id'] }}" data-days-ago="{{ $daysAgo }}">
                                                    <td class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input absent-checkbox"
                                                                id="check-{{ $student['id'] }}" value="{{ $student['id'] }}">
                                                            <label class="custom-control-label"
                                                                for="check-{{ $student['id'] }}"></label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-md mr-3">
                                                                <div
                                                                    class="avatar-title bg-soft-danger text-danger rounded-circle font-weight-bold">
                                                                    {{ substr($student['name'], 0, 1) }}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="font-weight-bold text-dark">{{ $student['name'] }}</div>
                                                                <div class="small text-muted">ID:
                                                                    {{ $student['enrollment_number'] }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="badge badge-soft-primary mb-1">{{ $student['batch_name'] }}
                                                        </div>
                                                        <div class="small text-muted">{{ $student['course_name'] }}</div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            @if($student['student_mobile'])
                                                                <a href="tel:{{ $student['student_mobile'] }}"
                                                                    class="text-decoration-none mb-1">
                                                                    <i class="fas fa-phone-alt fa-fw text-muted mr-1"></i>
                                                                    <span class="text-dark">{{ $student['student_mobile'] }}</span>
                                                                </a>
                                                            @endif
                                                            @if($student['father_mobile'])
                                                                <a href="tel:{{ $student['father_mobile'] }}"
                                                                    class="text-decoration-none"
                                                                    title="Father: {{ $student['father_name'] }}">
                                                                    <i class="fas fa-user-friends fa-fw text-muted mr-1"></i>
                                                                    <span class="text-muted">{{ $student['father_mobile'] }}</span>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($lastSeenDate)
                                                            <div
                                                                class="font-weight-bold {{ $daysAgo > 7 ? 'text-danger' : 'text-dark' }}">
                                                                {{ $lastSeenDate->format('M d, Y') }}
                                                            </div>
                                                            <div class="small text-muted">{{ $lastSeenDate->diffForHumans() }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-right">
                                                        <button class="btn btn-sm btn-success shadow-sm"
                                                            onclick="markStudentPresent({{ $student['id'] }}, '{{ $student['name'] }}')"
                                                            data-toggle="tooltip" title="Mark Present">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <div class="icon-circle bg-soft-success text-success mx-auto"
                                            style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                            <i class="fas fa-check fa-2x"></i>
                                        </div>
                                    </div>
                                    <h5 class="text-gray-800">No Action Required</h5>
                                    <p class="text-muted mb-0">No active students found with missing attendance history.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Long Term Absent Students (> 7 Days) --}}
                <div class="card shadow mt-4 border-left-danger">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                        <h6 class="m-0 font-weight-bold text-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Critical Absentees (> 7 Days)
                            @php
                                $longTermAbsent = $absentStudents->filter(function ($student) {
                                    if (empty($student['last_attendance']))
                                        return false;
                                    return \Carbon\Carbon::parse($student['last_attendance'])->diffInDays(now()) > 7;
                                });
                            @endphp
                            <span class="badge badge-danger ml-2">{{ $longTermAbsent->count() }}</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            @if($longTermAbsent->count() > 0)
                                <table class="table table-hover align-middle mb-0" id="long-term-absent-table">
                                    <thead class="bg-light text-dark">
                                        <tr>
                                            <th style="cursor: pointer;" onclick="sortTable('long-term-absent-table', 0)"
                                                class="border-top-0 pl-4">Student Details <i
                                                    class="fas fa-sort fa-sm text-muted ml-1"></i></th>
                                            <th style="cursor: pointer;" onclick="sortTable('long-term-absent-table', 1)"
                                                class="border-top-0">Batch Info <i
                                                    class="fas fa-sort fa-sm text-muted ml-1"></i></th>
                                            <th class="border-top-0">Contact Info</th>
                                            <th style="cursor: pointer;" onclick="sortTable('long-term-absent-table', 3)"
                                                class="border-top-0 text-right pr-4">Last Present <i
                                                    class="fas fa-sort fa-sm text-muted ml-1"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($longTermAbsent as $student)
                                            <tr>
                                                <td class="pl-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm mr-3">
                                                            <div
                                                                class="avatar-title bg-soft-danger text-danger rounded-circle font-weight-bold">
                                                                {{ substr($student['name'], 0, 1) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="font-weight-bold text-dark">{{ $student['name'] }}</div>
                                                            <div class="small text-muted">{{ $student['enrollment_number'] }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="badge badge-soft-primary mb-1">{{ $student['batch_name'] }}</div>
                                                    <div class="small text-muted">{{ $student['course_name'] }}</div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        @if($student['student_mobile'])
                                                            <a href="tel:{{ $student['student_mobile'] }}"
                                                                class="text-decoration-none mb-1">
                                                                <i class="fas fa-phone-alt fa-fw text-muted mr-1"></i>
                                                                <span class="text-dark">{{ $student['student_mobile'] }}</span>
                                                                <span class="badge badge-light border ml-1">Student</span>
                                                            </a>
                                                        @endif
                                                        @if($student['father_mobile'])
                                                            <a href="tel:{{ $student['father_mobile'] }}" class="text-decoration-none"
                                                                title="Father: {{ $student['father_name'] }}">
                                                                <i class="fas fa-user-friends fa-fw text-muted mr-1"></i>
                                                                <span class="text-muted">{{ $student['father_mobile'] }}</span>
                                                                <span class="badge badge-light border ml-1">Father</span>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-right pr-4">
                                                    <div class="font-weight-bold text-danger">
                                                        {{ round(\Carbon\Carbon::parse($student['last_attendance'])->diffInDays(now())) }}
                                                        days ago
                                                    </div>
                                                    <div class="small text-muted">
                                                        {{ \Carbon\Carbon::parse($student['last_attendance'])->format('M d, Y') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h5 class="text-success">Excellent!</h5>
                                    <p class="text-muted">No students found with critical absence (> 7 days).</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Live Attendance Activity --}}
            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list mr-2"></i>Live Attendance Activity
                            <span class="badge badge-primary" id="activity-indicator">●</span>
                        </h6>
                        <div class="dropdown no-arrow">
                            <button class="btn btn-link btn-sm dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                <button class="dropdown-item" onclick="toggleAutoRefresh()">
                                    <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                                    <span id="auto-refresh-text">Enable Auto Refresh</span>
                                </button>
                                <button class="dropdown-item" onclick="clearActivity()">
                                    <i class="fas fa-eraser fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Clear Activity
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="activity-feed" id="activity-feed" style="max-height: 1150px; overflow-y: auto;">
                            @if($recentActivity->count() > 0)
                                @foreach($recentActivity as $activity)
                                    <div class="activity-item border-bottom p-3" data-activity-id="{{ $activity['id'] }}">
                                        <div class="d-flex align-items-center">
                                            <div class="activity-avatar mr-3">
                                                <div
                                                    class="avatar-title bg-{{ $activity['status'] == 'present' ? 'success' : ($activity['status'] == 'late' ? 'warning' : 'danger') }} text-white rounded-circle">
                                                    <i
                                                        class="fas fa-{{ $activity['status'] == 'present' ? 'check' : ($activity['status'] == 'late' ? 'clock' : 'times') }}"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="font-weight-bold">{{ $activity['student_name'] }}</div>
                                                <div class="small text-muted">{{ $activity['enrollment_number'] }} •
                                                    {{ $activity['batch_name'] }}
                                                </div>
                                                <div class="small">
                                                    <span
                                                        class="badge badge-{{ $activity['status'] == 'present' ? 'success' : ($activity['status'] == 'late' ? 'warning' : 'danger') }}">
                                                        {{ ucfirst($activity['status']) }}
                                                    </span>
                                                    @if($activity['check_in_time'])
                                                        • {{ \Carbon\Carbon::parse($activity['check_in_time'])->format('H:i') }}
                                                    @endif
                                                    @if($activity['late_minutes'] > 0)
                                                        <span class="text-warning">({{ $activity['late_minutes'] }}m late)</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted">
                                                    {{ \Carbon\Carbon::parse($activity['marked_at'])->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4" id="no-activity">
                                    <i class="fas fa-list fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No attendance activity yet</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Students Who Never Punched --}}
            <div class="col-12 mb-4">
                <div class="card shadow border-left-secondary">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                        <h6 class="m-0 font-weight-bold text-secondary">
                            <i class="fas fa-user-slash mr-2"></i>Students Who Never Punched
                            @if(isset($neverPunchedStudents) && $neverPunchedStudents->count() > 0)
                                <span class="badge badge-secondary ml-2">{{ $neverPunchedStudents->count() }}</span>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            @if(isset($neverPunchedStudents) && $neverPunchedStudents->count() > 0)
                                <table class="table table-hover align-middle mb-0" id="never-punched-table">
                                    <thead class="bg-light text-dark">
                                        <tr>
                                            <th style="cursor: pointer;" onclick="sortTable('never-punched-table', 0)"
                                                class="border-top-0 pl-4">Student Details <i
                                                    class="fas fa-sort fa-sm text-muted ml-1"></i></th>
                                            <th style="cursor: pointer;" onclick="sortTable('never-punched-table', 1)"
                                                class="border-top-0">Batch Info <i
                                                    class="fas fa-sort fa-sm text-muted ml-1"></i>
                                            </th>
                                            <th class="border-top-0">Contact Info</th>
                                            <th class="border-top-0 text-right pr-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($neverPunchedStudents as $student)
                                            <tr>
                                                <td class="pl-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm mr-3">
                                                            <div
                                                                class="avatar-title bg-soft-secondary text-secondary rounded-circle font-weight-bold">
                                                                {{ substr($student['name'], 0, 1) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="font-weight-bold text-dark">{{ $student['name'] }}</div>
                                                            <div class="small text-muted">{{ $student['enrollment_number'] }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="badge badge-soft-primary mb-1">{{ $student['batch_name'] }}</div>
                                                    <div class="small text-muted">{{ $student['course_name'] }}</div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        @if($student['student_mobile'])
                                                            <a href="tel:{{ $student['student_mobile'] }}"
                                                                class="text-decoration-none mb-1">
                                                                <i class="fas fa-phone-alt fa-fw text-muted mr-1"></i>
                                                                <span class="text-dark">{{ $student['student_mobile'] }}</span>
                                                            </a>
                                                        @endif
                                                        @if($student['father_mobile'])
                                                            <a href="tel:{{ $student['father_mobile'] }}" class="text-decoration-none"
                                                                title="Father: {{ $student['father_name'] }}">
                                                                <i class="fas fa-user-friends fa-fw text-muted mr-1"></i>
                                                                <span class="text-muted">{{ $student['father_mobile'] }}</span>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-right pr-4">
                                                    <span class="badge badge-secondary">Never Punched</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h5 class="text-success">All Clear!</h5>
                                    <p class="text-muted">Every active student has at least one attendance record.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>


        </div>

        {{-- Attendance Leaderboard --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-trophy mr-2"></i>Attendance Performance
                        </h6>
                        <div class="d-flex align-items-center">
                            <select id="leaderboard-period" class="form-control form-control-sm" style="width: 150px;"
                                onchange="loadLeaderboard()">
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_week">This Week</option>
                                <option value="last_week">Last Week</option>
                                <option value="last_30_days">Last 30 Days</option>
                            </select>
                            <button class="btn btn-sm btn-link text-muted ml-2" onclick="loadLeaderboard()"
                                title="Refresh Leaderboard">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="leaderboard-container">
                            {{-- Top Attendance --}}
                            <div class="col-lg-6 mb-4 mb-lg-0">
                                <div class="card border-left-success h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="m-0 font-weight-bold text-success">
                                            <i class="fas fa-thumbs-up mr-2"></i>Top Attendance <span
                                                class="small text-muted" id="top-period-label"></span>
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th class="pl-3" style="width: 50px;">Rank</th>
                                                        <th>Student</th>
                                                        <th>Enrollment</th>
                                                        <th>Course/Batch</th>
                                                        <th class="text-center">Days</th>
                                                        <th class="text-right pr-3">%</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="top-attendance-body">
                                                    <tr>
                                                        <td colspan="4" class="text-center py-4">
                                                            <div class="spinner-border text-primary spinner-border-sm"
                                                                role="status"></div>
                                                            <span class="ml-2 small text-muted">Loading...</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Low Attendance --}}
                            <div class="col-lg-6">
                                <div class="card border-left-danger h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="m-0 font-weight-bold text-danger">
                                            <i class="fas fa-thumbs-down mr-2"></i>Needs Improvement <span
                                                class="small text-muted" id="low-period-label"></span>
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th class="pl-3">Student</th>
                                                        <th>Enrollment</th>
                                                        <th>Course/Batch</th>
                                                        <th class="text-center">Days</th>
                                                        <th class="text-right pr-3">%</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="low-attendance-body">
                                                    <tr>
                                                        <td colspan="4" class="text-center py-4">
                                                            <div class="spinner-border text-primary spinner-border-sm"
                                                                role="status"></div>
                                                            <span class="ml-2 small text-muted">Loading...</span>
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
                </div>
            </div>
        </div>

        {{-- Weekly Attendance Trend --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-line mr-2"></i>Weekly Attendance Trend
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyTrendChart" width="400" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions Modal --}}
        <div class="modal fade" id="bulkActionModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bulk Mark Present</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to mark <span id="selected-count">0</span> students as present?</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            This action will mark the selected students as present for
                            {{ $selectedDate->format('F j, Y') }}.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="confirmBulkMarkPresent()">
                            <i class="fas fa-check mr-2"></i>Mark Present
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Include required libraries --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script>
        // Wait for jQuery and DOM to be ready
        $(document).ready(function () {

            // Auto refresh setup
            let autoRefreshInterval = null;
            let isAutoRefreshEnabled = false;

            // Implement client-side filtering
            window.filterAbsentStudents = function () {
                let searchInput = document.getElementById("absent-search");
                let filterText = searchInput.value.toUpperCase();

                let dateFilter = document.getElementById("last-present-filter").value;

                let table = document.getElementById("absent-students-tbody");
                if (!table) return; // Guard clause if table is empty

                let tr = table.getElementsByTagName("tr");

                for (let i = 0; i < tr.length; i++) {
                    let row = tr[i];
                    let nameColumn = row.getElementsByTagName("td")[1]; // Name column
                    let daysAgo = parseInt(row.getAttribute('data-days-ago') || 0);

                    let matchesSearch = true;
                    let matchesDate = true;

                    // Search Filter
                    if (nameColumn) {
                        let txtValue = nameColumn.textContent || nameColumn.innerText;
                        if (txtValue.toUpperCase().indexOf(filterText) === -1) {
                            matchesSearch = false;
                        }
                    }

                    // Date Date Filter
                    if (dateFilter !== 'all') {
                        if (dateFilter === 'yesterday') {
                            if (daysAgo !== 1) matchesDate = false;
                        } else if (dateFilter === 'last_3_days') {
                            if (daysAgo > 3) matchesDate = false;
                        } else if (dateFilter === 'last_7_days') {
                            if (daysAgo > 7) matchesDate = false;
                        } else if (dateFilter === 'older') {
                            if (daysAgo <= 7) matchesDate = false;
                        }
                    }

                    if (matchesSearch && matchesDate) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                }

                // Update selection count after filtering might be nice, but simple for now
            };

            // Generic Table Sort Function
            window.sortTable = function (tableId, n) {
                var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
                table = document.getElementById(tableId);
                switching = true;
                dir = "asc";

                while (switching) {
                    switching = false;
                    rows = table.rows;

                    for (i = 1; i < (rows.length - 1); i++) {
                        shouldSwitch = false;
                        x = rows[i].getElementsByTagName("TD")[n];
                        y = rows[i + 1].getElementsByTagName("TD")[n];

                        // Use text content for comparison
                        let xContent = x.textContent || x.innerText;
                        let yContent = y.textContent || y.innerText;

                        // Check if it's a date or number (simple heuristic)
                        let xNum = parseFloat(xContent.replace(/[^0-9.-]+/g, ""));
                        let yNum = parseFloat(yContent.replace(/[^0-9.-]+/g, ""));

                        if (!isNaN(xNum) && !isNaN(yNum) && xContent.includes('days')) {
                            // Sort by number if it looks like "X days ago"
                            if (dir == "asc") {
                                if (xNum > yNum) { shouldSwitch = true; break; }
                            } else if (dir == "desc") {
                                if (xNum < yNum) { shouldSwitch = true; break; }
                            }
                        } else {
                            // String sort
                            if (dir == "asc") {
                                if (xContent.toLowerCase() > yContent.toLowerCase()) { shouldSwitch = true; break; }
                            } else if (dir == "desc") {
                                if (xContent.toLowerCase() < yContent.toLowerCase()) { shouldSwitch = true; break; }
                            }
                        }
                    }

                    if (shouldSwitch) {
                        rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                        switching = true;
                        switchcount++;
                    } else {
                        if (switchcount == 0 && dir == "asc") {
                            dir = "desc";
                            switching = true;
                        }
                    }
                }
            };

            // Function to load leaderboard data
            window.loadLeaderboard = function () {
                const period = $('#leaderboard-period').val();
                const container = $('#leaderboard-container');
                const topBody = $('#top-attendance-body');
                const lowBody = $('#low-attendance-body');

                // Show loading state
                const loadingHtml = `
                                                                                            <tr>
                                                                                                <td colspan="6" class="text-center py-4">
                                                                                                    <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                                                                                                    <span class="ml-2 small text-muted">Loading...</span>
                                                                                                </td>
                                                                                            </tr>`;
                topBody.html(loadingHtml);
                const loadingHtmlLow = `
                                                                                            <tr>
                                                                                                <td colspan="5" class="text-center py-4">
                                                                                                    <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                                                                                                    <span class="ml-2 small text-muted">Loading...</span>
                                                                                                </td>
                                                                                            </tr>`;
                lowBody.html(loadingHtmlLow);

                $.ajax({
                    url: "{{ route('admin.attendance.leaderboard') }}",
                    method: 'GET',
                    data: {
                        period: period,
                        course_id: $('#course_id').val(),
                        batch_id: $('#batch_id').val()
                    },
                    success: function (response) {
                        if (response.success) {
                            const data = response.data;
                            // params: tbody, students, theme, totalDays, showRank
                            renderLeaderboardTable(topBody, data.top_attendance, 'success', data.total_working_days, true);
                            renderLeaderboardTable(lowBody, data.low_attendance, 'danger', data.total_working_days, false);

                            $('#top-period-label').text('(' + data.period_label + ')');
                            $('#low-period-label').text('(' + data.period_label + ')');
                        }
                    },
                    error: function (err) {
                        console.error('Error loading leaderboard:', err);
                        const errorHtml = `<tr><td colspan="6" class="text-center text-danger py-3">Failed to load data</td></tr>`;
                        topBody.html(errorHtml);
                        lowBody.html(errorHtml);
                    }
                });
            };

            // Initialize chart
            initWeeklyTrendChart();

            // Load Leaderboard
            loadLeaderboard();

            function renderLeaderboardTable(tbody, students, theme, totalDays, showRank) {
                const colSpan = showRank ? 6 : 5;
                if (students.length === 0) {
                    tbody.html(`<tr><td colspan="${colSpan}" class="text-center text-muted py-3">No data available</td></tr>`);
                    return;
                }

                let html = '';
                students.forEach((student, index) => {
                    const avatarColor = theme === 'success' ? 'success' : 'danger';
                    const percentageClass = theme === 'success'
                        ? (student.percentage >= 90 ? 'text-success font-weight-bold' : (student.percentage >= 75 ? 'text-primary' : 'text-dark'))
                        : (student.percentage < 50 ? 'text-danger font-weight-bold' : 'text-dark');

                    let rankHtml = '';
                    if (showRank) {
                        let rankIcon = '';
                        if (index === 0) rankIcon = '<i class="fas fa-crown text-warning"></i>';
                        else if (index === 1) rankIcon = '<i class="fas fa-medal text-secondary"></i>';
                        else if (index === 2) rankIcon = '<i class="fas fa-medal text-warning" style="color: #cd7f32 !important;"></i>';
                        else rankIcon = `<span class="badge badge-light border text-muted" style="width: 25px;">${index + 1}</span>`;

                        rankHtml = `<td class="pl-3 align-middle text-center font-weight-bold">${rankIcon}</td>`;
                    } else {
                        // For low attendance table, first col is student name, so padding needed
                        rankHtml = '';
                    }

                    // Adjust padding for first column if rank is not shown
                    const nameCellClass = showRank ? "align-middle" : "pl-3 py-2 align-middle";

                    const profileUrl = "{{ route('admin.students.show', '') }}/" + student.id;

                    html += `
                                                                                                <tr>
                                                                                                    ${rankHtml}
                                                                                                    <td class="${nameCellClass}">
                                                                                                        <div class="d-flex align-items-center">
                                                                                                            <div class="avatar avatar-xs mr-2">
                                                                                                                <div class="avatar-title bg-soft-${avatarColor} text-${avatarColor} rounded-circle font-weight-bold" style="font-size: 10px; width: 24px; height: 24px;">
                                                                                                                    ${student.avatar}
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="text-truncate" style="max-width: 150px;" title="${student.name}">
                                                                                                                <a href="${profileUrl}" class="small font-weight-bold text-dark text-decoration-none hover-primary">
                                                                                                                    ${student.name}
                                                                                                                </a>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </td>
                                                                                                    <td class="align-middle small text-muted">${student.enrollment_number || '-'}</td>
                                                                                                    <td class="align-middle small">
                                                                                                        <div class="d-flex flex-column">
                                                                                                            <span class="text-primary font-weight-bold" style="font-size: 10px;">${student.course_name}</span>
                                                                                                            <span class="text-muted" style="font-size: 10px;">${student.batch_name}</span>
                                                                                                        </div>
                                                                                                    </td>
                                                                                                    <td class="text-center align-middle small">
                                                                                                        <b>${student.present_days}</b><span class="text-muted">/${student.total_days}</span>
                                                                                                    </td>
                                                                                                    <td class="text-right pr-3 align-middle">
                                                                                                        <span class="${percentageClass} small">${student.percentage}%</span>
                                                                                                    </td>
                                                                                                </tr>
                                                                                            `;
                });
                tbody.html(html);
            }

            // Select all functionality
            $('#select-all-absent').change(function () {
                $('.absent-checkbox').prop('checked', this.checked);
                updateSelectedCount();
            });

            $(document).on('change', '.absent-checkbox', function () {
                updateSelectedCount();
                updateSelectAllState();
            });

            function updateSelectedCount() {
                const selectedCount = $('.absent-checkbox:checked').length;
                $('#selected-count').text(selectedCount);
            }

            function updateSelectAllState() {
                const totalCheckboxes = $('.absent-checkbox').length;
                const checkedCheckboxes = $('.absent-checkbox:checked').length;

                $('#select-all-absent').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
                $('#select-all-absent').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
            }

            // Auto refresh toggle
            window.toggleAutoRefresh = function () {
                if (isAutoRefreshEnabled) {
                    clearInterval(autoRefreshInterval);
                    isAutoRefreshEnabled = false;
                    $('#auto-refresh-text').text('Enable Auto Refresh');
                    $('#activity-indicator').removeClass('text-success').addClass('text-primary');
                } else {
                    autoRefreshInterval = setInterval(function () {
                        refreshAttendanceData();
                    }, 30000); // Refresh every 30 seconds
                    isAutoRefreshEnabled = true;
                    $('#auto-refresh-text').text('Disable Auto Refresh');
                    $('#activity-indicator').removeClass('text-primary').addClass('text-success');
                }
            };

            // Refresh dashboard
            window.refreshDashboard = function () {
                $('#refresh-icon').addClass('fa-spin');
                refreshAttendanceData();
            };

            function refreshAttendanceData() {
                const params = {
                    date: $('#date').val(),
                    batch_id: $('#batch_id').val(),
                    course_id: $('#course_id').val()
                };

                $('#refresh-icon, #refresh-icon-absent').addClass('fa-spin');

                // Refresh stats
                $.get("{{ route('admin.attendance.dashboard.stats.ajax') }}", params)
                    .done(function (response) {
                        if (response.success) {
                            updateStatsCards(response.data);
                            $('#last-updated').text('Last updated: ' + response.last_updated);
                        }
                    })
                    .fail(function (xhr, status, error) {
                        console.error('Stats refresh failed:', error);
                    });

                // Refresh absent students
                $.get("{{ route('admin.attendance.dashboard.absent.ajax') }}", params)
                    .done(function (response) {
                        if (response.success) {
                            updateAbsentStudents(response.data);
                            $('#absent-count').text(response.count);
                        }
                    })
                    .fail(function (xhr, status, error) {
                        console.error('Absent students refresh failed:', error);
                    });

                // Refresh activity
                $.get("{{ route('admin.attendance.dashboard.activity.ajax') }}", params)
                    .done(function (response) {
                        if (response.success) {
                            updateRecentActivity(response.data);
                        }
                    })
                    .fail(function (xhr, status, error) {
                        console.error('Activity refresh failed:', error);
                    })
                    .always(function () {
                        $('#refresh-icon, #refresh-icon-absent').removeClass('fa-spin');
                    });
            }

            function updateStatsCards(stats) {
                // Update the statistics cards
                $('#total-students').text(stats.students.total);
                $('#present-students').text(stats.students.present);
                $('#present-percentage').text(stats.students.percentage + '% of total');
                $('#absent-students').text(stats.students.absent);
                $('#absent-percentage').text(stats.students.absent_percentage + '% of total');
                $('#never-punched-stat').text(stats.students.never_punched);
                $('#never-punched-percentage').text(stats.students.never_punched_percentage + '% of total');
            }

            function updateAbsentStudents(students) {
                if (students.length === 0) {
                    $('#absent-students-container').html(`
                                                                                                            <div class="text-center py-4">
                                                                                                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                                                                                <h5 class="text-success">All Students Present!</h5>
                                                                                                                <p class="text-muted">No absent students found for ${$('#date').val()}</p>
                                                                                                            </div>
                                                                                                        `);
                    return;
                }

                let tbody = '';
                students.forEach(function (student) {
                    let lastSeenDate = student.last_attendance ? moment(student.last_attendance) : null;
                    let daysAgo = lastSeenDate ? moment().diff(lastSeenDate, 'days') : 999;

                    tbody += `
                                                                                                            <tr id="absent-row-${student.id}" data-days-ago="${daysAgo}">
                                                                                                                <td><input type="checkbox" class="absent-checkbox" value="${student.id}"></td>
                                                                                                                <td>
                                                                                                                    <div class="d-flex align-items-center">
                                                                                                                        <div class="avatar avatar-sm mr-3">
                                                                                                                            <div class="avatar-title bg-danger text-white rounded-circle">
                                                                                                                                ${student.name.charAt(0)}
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div>
                                                                                                                            <div class="font-weight-bold">${student.name}</div>
                                                                                                                            <div class="small text-muted">${student.enrollment_number}</div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </td>
                                                                                                                <td>
                                                                                                                    <div>${student.batch_name}</div>
                                                                                                                    <small class="text-muted">${student.course_name}</small>
                                                                                                                </td>
                                                                                                                <td>
                                                                                                                    <div class="contact-buttons">
                                                                                                                        ${student.student_mobile ? `<a href="tel:${student.student_mobile}" class="btn btn-sm btn-outline-primary mr-1" title="Student: ${student.student_mobile}"><i class="fas fa-phone"></i></a>` : ''}
                                                                                                                        ${student.father_mobile ? `<a href="tel:${student.father_mobile}" class="btn btn-sm btn-outline-secondary" title="Father: ${student.father_mobile}"><i class="fas fa-phone"></i> F</a>` : ''}
                                                                                                                    </div>
                                                                                                                    <div class="small text-muted mt-1">
                                                                                                                        ${student.student_mobile ? `<div>S: ${student.student_mobile}</div>` : ''}
                                                                                                                        ${student.father_mobile ? `<div>F: ${student.father_mobile}</div>` : ''}
                                                                                                                    </div>
                                                                                                                </td>
                                                                                                                <td>
                                                                                                                    ${student.last_attendance ?
                            `<span class="badge badge-secondary">${moment(student.last_attendance).fromNow()}</span>` :
                            '<span class="badge badge-warning">Never</span>'
                        }
                                                                                                                </td>
                                                                                                                <td>
                                                                                                                    <button class="btn btn-sm btn-success" onclick="markStudentPresent(${student.id}, '${student.name}')">
                                                                                                                        <i class="fas fa-check"></i>
                                                                                                                    </button>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        `;
                });

                $('#absent-students-tbody').html(tbody);

                // Reattach event handlers
                $('.absent-checkbox').off('change').on('change', function () {
                    updateSelectedCount();
                    updateSelectAllState();
                });
            }

            function updateRecentActivity(activities) {
                if (activities.length === 0) {
                    $('#activity-feed').html(`
                                                                                                            <div class="text-center py-4" id="no-activity">
                                                                                                                <i class="fas fa-list fa-2x text-muted mb-3"></i>
                                                                                                                <p class="text-muted">No attendance activity yet</p>
                                                                                                            </div>
                                                                                                        `);
                    return;
                }

                let activityHtml = '';
                activities.forEach(function (activity) {
                    const statusColor = activity.status === 'present' ? 'success' : (activity.status === 'late' ? 'warning' : 'danger');
                    const statusIcon = activity.status === 'present' ? 'check' : (activity.status === 'late' ? 'clock' : 'times');

                    activityHtml += `
                                                                                                            <div class="activity-item border-bottom p-3" data-activity-id="${activity.id}">
                                                                                                                <div class="d-flex align-items-center">
                                                                                                                    <div class="activity-avatar mr-3">
                                                                                                                        <div class="avatar-title bg-${statusColor} text-white rounded-circle">
                                                                                                                            <i class="fas fa-${statusIcon}"></i>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="flex-grow-1">
                                                                                                                        <div class="font-weight-bold">${activity.student_name}</div>
                                                                                                                        <div class="small text-muted">${activity.enrollment_number} • ${activity.batch_name}</div>
                                                                                                                        <div class="small">
                                                                                                                            <span class="badge badge-${statusColor}">
                                                                                                                                ${activity.status.charAt(0).toUpperCase() + activity.status.slice(1)}
                                                                                                                            </span>
                                                                                                                            ${activity.check_in_time ? '• ' + moment(activity.check_in_time, 'HH:mm:ss').format('HH:mm') : ''}
                                                                                                                            ${activity.late_minutes > 0 ? `<span class="text-warning">(${activity.late_minutes}m late)</span>` : ''}
                                                                                                                        </div>
                                                                                                                        <div class="small text-muted">
                                                                                                                            ${moment(activity.marked_at).fromNow()}
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        `;
                });

                $('#activity-feed').html(activityHtml);
            }

            // Mark student present
            window.markStudentPresent = function (studentId, studentName) {
                if (confirm(`Mark ${studentName} as present?`)) {
                    $.post("{{ route('admin.attendance.dashboard.mark.present') }}", {
                        student_id: studentId,
                        date: $('#date').val(),
                        _token: '{{ csrf_token() }}'
                    })
                        .done(function (response) {
                            if (response.success) {
                                $(`#absent-row-${studentId}`).fadeOut(300, function () {
                                    $(this).remove();
                                    updateSelectedCount();
                                    updateSelectAllState();
                                });

                                // Update absent count
                                const currentCount = parseInt($('#absent-count').text());
                                $('#absent-count').text(Math.max(0, currentCount - 1));

                                // Show success message
                                showNotification('success', response.message);

                                // Refresh activity
                                refreshAttendanceData();
                            } else {
                                showNotification('error', 'Failed to mark student as present');
                            }
                        })
                        .fail(function () {
                            showNotification('error', 'Failed to mark student as present');
                        });
                }
            };

            // Clear all filters and search
            window.clearSelections = function () {
                $('#absent-search').val('');
                $('#last-present-filter').val('all');
                filterAbsentStudents();
            }

            // Select all absent students
            window.selectAllAbsent = function () {
                $('.absent-checkbox').prop('checked', true);
                updateSelectedCount();
                updateSelectAllState();
            };

            // Mark selected students present
            window.markSelectedPresent = function () {
                const selectedStudents = $('.absent-checkbox:checked');
                if (selectedStudents.length === 0) {
                    showNotification('warning', 'Please select at least one student');
                    return;
                }

                $('#selected-count').text(selectedStudents.length);
                $('#bulkActionModal').modal('show');
            };

            window.confirmBulkMarkPresent = function () {
                const selectedIds = $('.absent-checkbox:checked').map(function () {
                    return this.value;
                }).get();

                $.post("{{ route('admin.attendance.dashboard.bulk.mark.present') }}", {
                    student_ids: selectedIds,
                    date: $('#date').val(),
                    _token: '{{ csrf_token() }}'
                })
                    .done(function (response) {
                        if (response.success) {
                            // Remove marked students from the list
                            selectedIds.forEach(function (id) {
                                $(`#absent-row-${id}`).fadeOut(300, function () {
                                    $(this).remove();
                                });
                            });

                            // Update absent count
                            const currentCount = parseInt($('#absent-count').text());
                            $('#absent-count').text(Math.max(0, currentCount - selectedIds.length));

                            $('#bulkActionModal').modal('hide');
                            showNotification('success', response.message);

                            // Refresh data
                            refreshAttendanceData();
                        } else {
                            showNotification('error', 'Failed to mark students as present');
                        }
                    })
                    .fail(function () {
                        showNotification('error', 'Failed to mark students as present');
                    });
            };

            // Export absent list
            window.exportAbsentList = function () {
                const params = new URLSearchParams({
                    date: $('#date').val(),
                    batch_id: $('#batch_id').val() || '',
                    course_id: $('#course_id').val() || '',
                    export: 'csv'
                });

                window.open(`{{ route('admin.attendance.dashboard') }}?${params.toString()}`);
            };

            // Clear activity
            window.clearActivity = function () {
                if (confirm('Clear all activity from the feed?')) {
                    $('#activity-feed').html(`
                                                                                                            <div class="text-center py-4" id="no-activity">
                                                                                                                <i class="fas fa-list fa-2x text-muted mb-3"></i>
                                                                                                                <p class="text-muted">Activity cleared</p>
                                                                                                            </div>
                                                                                                        `);
                }
            };

            function showNotification(type, message) {
                const alertClass = type === 'success' ? 'alert-success' :
                    type === 'warning' ? 'alert-warning' : 'alert-danger';

                const notification = $(`
                                                                                                        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                                                                                                             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                                                                                                            ${message}
                                                                                                            <button type="button" class="close" data-dismiss="alert">
                                                                                                                <span>&times;</span>
                                                                                                            </button>
                                                                                                        </div>
                                                                                                    `);

                $('body').append(notification);

                setTimeout(function () {
                    notification.alert('close');
                }, 5000);
            }

            function initWeeklyTrendChart() {
                const ctx = document.getElementById('weeklyTrendChart');
                if (!ctx) {
                    console.error('Chart canvas not found');
                    return;
                }

                const weeklyData = @json($weeklyTrend);

                if (typeof Chart === 'undefined') {
                    console.error('Chart.js not loaded');
                    return;
                }

                try {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: weeklyData.map(d => d.day),
                            datasets: [{
                                label: 'Attendance %',
                                data: weeklyData.map(d => d.percentage),
                                borderColor: '#4e73df',
                                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: function (value) {
                                            return value + '%';
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Chart initialization error:', error);
                }
            }

            refreshAttendanceData();

            // Auto-enable refresh after 5 seconds
            // Auto-enable refresh
            if (!isAutoRefreshEnabled) {
                toggleAutoRefresh();
            }

            // Add scroll to top functionality for table
            window.scrollTableToTop = function () {
                $('.table-responsive').animate({ scrollTop: 0 }, 300);
            };

            // Add keyboard shortcuts
            $(document).keydown(function (e) {
                // Ctrl + R to refresh
                if (e.ctrlKey && e.keyCode === 82) {
                    e.preventDefault();
                    refreshDashboard();
                }

                // Ctrl + A to select all absent students (when focus is on table)
                if (e.ctrlKey && e.keyCode === 65 && $('.table-responsive:focus').length > 0) {
                    e.preventDefault();
                    selectAllAbsent();
                }

                // ESC to clear selections
                if (e.keyCode === 27) {
                    $('.absent-checkbox').prop('checked', false);
                    $('#select-all-absent').prop('checked', false);
                    updateSelectedCount();
                }
            });

            // Make table focusable for keyboard shortcuts
            $('.table-responsive').attr('tabindex', '0');

            // Add loading state for table updates
            function showTableLoading() {
                $('#absent-students-container').addClass('table-loading');
            }

            function hideTableLoading() {
                $('#absent-students-container').removeClass('table-loading');
            }

            // Update refresh function to show loading
            const originalRefreshAttendanceData = refreshAttendanceData;
            refreshAttendanceData = function () {
                showTableLoading();
                originalRefreshAttendanceData();
                hideTableLoading();
            };

            // Add scroll indicators
            function addScrollIndicators() {
                const tableContainer = $('.table-responsive');

                tableContainer.on('scroll', function () {
                    const scrollTop = $(this).scrollTop();
                    const scrollHeight = this.scrollHeight;
                    const height = $(this).height();

                    // Show scroll to top button if scrolled down
                    if (scrollTop > 100) {
                        if (!$('.scroll-to-top-table').length) {
                            $(this).append(`
                                                                                                                    <button class="btn btn-sm btn-primary scroll-to-top-table" 
                                                                                                                            onclick="scrollTableToTop()" 
                                                                                                                            style="position: absolute; bottom: 10px; right: 10px; z-index: 20; border-radius: 50%; width: 40px; height: 40px;">
                                                                                                                        <i class="fas fa-arrow-up"></i>
                                                                                                                    </button>
                                                                                                                `);
                        }
                    } else {
                        $('.scroll-to-top-table').remove();
                    }
                });
            }

            // Initialize scroll indicators
            setTimeout(addScrollIndicators, 1000);
        });
    </script>
@endpush