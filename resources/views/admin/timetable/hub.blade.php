@extends('layouts.theme')
@section('title', 'Timetable Command Center')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4 no-print">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-calendar-alt me-2"></i>Timetable Command Center
    </h1>
    <div>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#generateModal">
            <i class="fas fa-cogs fa-sm text-white-50"></i> Generate Schedule
        </button>
        <button class="btn btn-info shadow-sm" data-toggle="modal" data-target="#quickScheduleModal">
            <i class="fas fa-bolt fa-sm text-white-50"></i> Quick Schedule
        </button>
        <button class="btn btn-warning shadow-sm" data-toggle="modal" data-target="#labTimetableModal">
            <i class="fas fa-flask fa-sm text-white-50"></i> Generate Lab Timetable
        </button>
        <button class="btn btn-secondary shadow-sm" onclick="window.print()">
            <i class="fas fa-print fa-sm"></i> Print View
        </button>
        <a href="{{ route('admin.timetable.hub.pdf') }}" id="pdfExportLink" class="btn btn-danger btn-sm shadow-sm">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>
</div>

{{-- Enhanced Alert Messages with Better Reporting --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>{{ session('success') }}</strong>
        
        @if(session('report'))
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <strong>📊 Generation Summary:</strong>
                        <div class="small mt-2">
                            @php
                                $report = session('report');
                                $lines = explode("\n", $report);
                                $summaryLines = array_slice($lines, 0, 6); // First 6 lines contain summary
                            @endphp
                            @foreach($summaryLines as $line)
                                @if(trim($line))
                                    <div>{{ $line }}</div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-sm btn-outline-primary" data-toggle="collapse" data-target="#fullReport">
                            <i class="fas fa-eye"></i> View Full Report
                        </button>
                        <div class="collapse mt-2" id="fullReport">
                            <div class="small" style="max-height: 250px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px;">
                                <pre style="font-size: 11px; margin: 0;">{{ session('report') }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Generation Failed:</strong> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- Quick Stats Dashboard --}}
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Classes Scheduled
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalClasses">-</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Lab Sessions
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="labSessions">-</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-flask fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Active Faculty
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeFaculty">-</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Conflicts Detected
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="conflicts">-</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Lab Timetable Prerequisites Check --}}
@if(isset($courses) && $courses->count() > 0)
<div class="card shadow mb-4">
    <div class="card-header bg-warning text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-flask mr-2"></i>Lab Timetable Prerequisites Checklist
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Course Setup</h6>
                @php
                    $firstCourse = $courses->first();
                    $totalTerms = $courses->sum(function($course) { return $course->terms->count(); });
                    $totalLabSubjects = $courses->sum(function($course) { return $course->subjects->where('requires_lab', true)->count(); });
                    $totalBatches = $courses->sum(function($course) { return $course->batches->count(); });
                @endphp
                
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" disabled {{ $totalTerms > 0 ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Course Terms Created ({{ $totalTerms }})
                    </label>
                </div>
                
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" disabled {{ $totalLabSubjects > 0 ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Lab Subjects Defined ({{ $totalLabSubjects }})
                    </label>
                </div>
                
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" disabled {{ $totalBatches > 0 ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Batches Created ({{ $totalBatches }})
                    </label>
                </div>
            </div>
            
            <div class="col-md-6">
                <h6 class="text-success">Lab Infrastructure</h6>
                @php
                    $labClassrooms = isset($classrooms) ? $classrooms->where('type', 'lab')->count() : 0;
                    $totalPracticalGroups = $courses->sum(function($course) { 
                        return $course->batches->sum(function($batch) { 
                            return $batch->practicalGroups ? $batch->practicalGroups->count() : 0; 
                        }); 
                    });
                    $totalFaculty = isset($faculties) ? $faculties->count() : 0;
                @endphp
                
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" disabled {{ $labClassrooms > 0 ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Lab Classrooms Available ({{ $labClassrooms }})
                    </label>
                </div>
                
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" disabled {{ $totalPracticalGroups > 0 ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Practical Groups Created ({{ $totalPracticalGroups }})
                    </label>
                </div>
                
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" disabled {{ $totalFaculty > 0 ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Faculty Available ({{ $totalFaculty }})
                    </label>
                </div>
            </div>
        </div>
        
        @php
            $allPrerequisitesMet = $totalTerms > 0 && $totalLabSubjects > 0 && 
                                 $totalBatches > 0 && $labClassrooms > 0 && 
                                 $totalPracticalGroups > 0;
        @endphp
        
        <div class="mt-3">
            @if($allPrerequisitesMet)
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>
                    All prerequisites met! You can now generate lab timetables.
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Some prerequisites are missing. Complete the setup before generating lab timetables.
                </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- Enhanced Filter Bar --}}
<div class="card shadow mb-4 no-print">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-filter me-2"></i>Smart Calendar Filters
        </h6>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-2 mb-2">
                <label>Course</label>
                <select id="courseFilter" class="form-control">
                    <option value="">-- All Courses --</option>
                    @if(isset($courses))
                        @foreach($courses as $course)
                            <option value="{{$course->id}}">{{$course->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label>Academic Year</label>
                <select id="academicYearFilter" class="form-control">
                    <option value="">-- All Years --</option>
                    @if(isset($academicYears))
                        @foreach($academicYears as $year)
                            <option value="{{$year->id}}" {{ $year->is_current ? 'selected' : '' }}>
                                {{$year->name}} @if($year->is_current)(Current)@endif
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label>Faculty</label>
                <select id="facultyFilter" class="form-control">
                    <option value="">-- All Faculty --</option>
                    @if(isset($faculties))
                        @foreach($faculties as $faculty)
                            <option value="{{$faculty->id}}">{{$faculty->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label>Classroom</label>
                <select id="classroomFilter" class="form-control">
                    <option value="">-- All Rooms --</option>
                    @if(isset($classrooms))
                        @foreach($classrooms as $room)
                            <option value="{{$room->id}}">
                                {{$room->name}} 
                                @if($room->type == 'lab')<span class="badge badge-info">Lab</span>@endif
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label>Session Type</label>
                <select id="sessionTypeFilter" class="form-control">
                    <option value="">-- All Types --</option>
                    <option value="regular">Regular Classes</option>
                    <option value="lab">Lab Sessions</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button id="clearFilters" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Calendar Display --}}
<div class="card shadow mb-4 printable">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-info">
            <i class="fas fa-calendar-alt me-2"></i>Interactive Schedule Calendar
        </h6>
        <div class="no-print">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-info" onclick="refreshCalendar()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="detectConflicts()">
                    <i class="fas fa-search"></i> Check Conflicts
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div id='calendar'></div>
    </div>
</div>

{{-- Lab Timetable Generation Modal --}}
<div class="modal fade no-print" id="labTimetableModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-flask me-2"></i>Generate Lab Timetable
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    This will generate lab sessions specifically for practical groups. Make sure students are allocated to practical groups first.
                </div>
                
                <form id="labTimetableForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lab_course_id" class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>Course *
                                </label>
                                <select name="course_id" id="lab_course_id" class="form-control" required>
                                    <option value="">-- Select Course --</option>
                                    @if(isset($courses))
                                        @foreach($courses as $course)
                                            <option value="{{$course->id}}">{{$course->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lab_course_term_id" class="form-label">
                                    <i class="fas fa-bookmark me-1"></i>Term *
                                </label>
                                <select name="course_term_id" id="lab_course_term_id" class="form-control" required disabled>
                                    <option value="">-- Select Course First --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="lab_academic_year_id" class="form-label">Academic Year *</label>
                                <select name="academic_year_id" id="lab_academic_year_id" class="form-control" required>
                                    @if(isset($academicYears))
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                                {{ $year->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="lab_start_date" class="form-label">Start Date *</label>
                                <input type="date" name="start_date" id="lab_start_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="lab_end_date" class="form-label">End Date *</label>
                                <input type="date" name="end_date" id="lab_end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Lab-specific options --}}
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-white">
                            <h6 class="mb-0">Lab Scheduling Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lab_sessions_per_week" class="form-label">
                                            Lab Sessions per Week *
                                        </label>
                                        <select name="lab_sessions_per_week" id="lab_sessions_per_week" class="form-control" required>
                                            <option value="1">1 session per week</option>
                                            <option value="2" selected>2 sessions per week</option>
                                            <option value="3">3 sessions per week</option>
                                            <option value="4">4 sessions per week</option>
                                            <option value="5">5 sessions per week</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="session_duration" class="form-label">
                                            Session Duration (hours) *
                                        </label>
                                        <select name="session_duration" id="session_duration" class="form-control" required>
                                            <option value="1">1 hour</option>
                                            <option value="2" selected>2 hours</option>
                                            <option value="3">3 hours</option>
                                            <option value="4">4 hours</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="rotate_subjects" name="rotate_subjects" checked>
                                <label class="form-check-label" for="rotate_subjects">
                                    Rotate lab subjects across weeks
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="avoid_consecutive" name="avoid_consecutive" checked>
                                <label class="form-check-label" for="avoid_consecutive">
                                    Avoid consecutive lab sessions for same group
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Prerequisites Check:</h6>
                        <div id="labPrerequisites">
                            <div class="small text-muted">Select a course to check prerequisites...</div>
                        </div>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="generateLabTimetableBtn">
                    <i class="fas fa-flask"></i> Generate Lab Timetable
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Enhanced Generate Schedule Modal --}}
<div class="modal fade no-print" id="generateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-cogs me-2"></i>Smart Timetable Generator
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This will replace any existing schedule for the selected parameters. 
                    The system will automatically prevent overlapping and conflicts.
                </div>
                
                <form id="generateForm" action="{{ route('admin.timetable.generate') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>1. Select Course *
                                </label>
                                <select name="course_id" id="course_id" class="form-control" required>
                                    <option value="">-- Select a Course --</option>
                                    @if(isset($courses))
                                        @foreach($courses as $course)
                                            <option value="{{$course->id}}" data-batches="{{$course->batches_count ?? 0}}">
                                                {{$course->name}} ({{$course->batches_count ?? 0}} batches)
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="form-text text-muted">
                                    Choose the course for which to generate the timetable
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course_term_id" class="form-label">
                                    <i class="fas fa-bookmark me-1"></i>2. Select Term *
                                </label>
                                <select name="course_term_id" id="course_term_id" class="form-control" required disabled>
                                    <option value="">-- Select Course First --</option>
                                </select>
                                <small class="form-text text-muted">
                                    Academic term/semester for the timetable
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="academic_year_id" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>3. Academic Year *
                                </label>
                                <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                                    @if(isset($academicYears))
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                                {{ $year->name }}
                                                @if($year->is_current) (Current) @endif
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">
                                    <i class="fas fa-play me-1"></i>4. Start Date *
                                </label>
                                <input type="date" name="start_date" id="start_date" class="form-control" required>
                                <small class="form-text text-muted">
                                    Timetable start date
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">
                                    <i class="fas fa-stop me-1"></i>5. End Date *
                                </label>
                                <input type="date" name="end_date" id="end_date" class="form-control" required>
                                <small class="form-text text-muted">
                                    Timetable end date
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Generation Options --}}
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Generation Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="avoid_conflicts" name="avoid_conflicts" checked>
                                        <label class="form-check-label" for="avoid_conflicts">
                                            <strong>Prevent All Conflicts</strong>
                                            <small class="d-block text-muted">Ensures no overlapping classes</small>
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="lab_priority" name="lab_priority" checked>
                                        <label class="form-check-label" for="lab_priority">
                                            <strong>Lab Session Priority</strong>
                                            <small class="d-block text-muted">Schedule lab sessions first</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="balance_workload" name="balance_workload" checked>
                                        <label class="form-check-label" for="balance_workload">
                                            <strong>Balance Faculty Workload</strong>
                                            <small class="d-block text-muted">Distribute classes evenly</small>
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="respect_holidays" name="respect_holidays" checked>
                                        <label class="form-check-label" for="respect_holidays">
                                            <strong>Respect Holidays & Events</strong>
                                            <small class="d-block text-muted">Skip holidays and events</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>Smart Generation Features:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="mb-0 small">
                                    <li>✅ Zero-conflict scheduling</li>
                                    <li>✅ Lab group integration</li>
                                    <li>✅ Faculty availability check</li>
                                    <li>✅ Optimal classroom utilization</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="mb-0 small">
                                    <li>✅ Holiday & event avoidance</li>
                                    <li>✅ Workload balancing</li>
                                    <li>✅ Real-time conflict detection</li>
                                    <li>✅ Detailed generation reports</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="generateBtn" form="generateForm">
                    <i class="fas fa-cogs"></i> Generate Smart Schedule
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Quick Schedule Modal for Single Classes --}}
<div class="modal fade no-print" id="quickScheduleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-bolt me-2"></i>Quick Class Scheduler
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Schedule individual classes or make quick adjustments</p>
                
                <form id="quickScheduleForm" action="{{ route('admin.timetable.quick-schedule') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quick_batch_id" class="form-label">Batch *</label>
                                <select name="batch_id" id="quick_batch_id" class="form-control" required>
                                    <option value="">-- Select Batch --</option>
                                    @if(isset($courses))
                                        @foreach($courses as $course)
                                            <optgroup label="{{$course->name}}">
                                                @foreach($course->batches as $batch)
                                                    <option value="{{$batch->id}}">{{$batch->name}}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quick_subject_id" class="form-label">Subject *</label>
                                <select name="subject_id" id="quick_subject_id" class="form-control" required>
                                    <option value="">-- Select Subject --</option>
                                    @if(isset($courses))
                                        @foreach($courses as $course)
                                            <optgroup label="{{$course->name}}">
                                                @foreach($course->subjects as $subject)
                                                    <option value="{{$subject->id}}" data-requires-lab="{{$subject->requires_lab ? '1' : '0'}}">
                                                        {{$subject->name}} @if($subject->requires_lab)(Lab)@endif
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quick_date" class="form-label">Date *</label>
                                <input type="date" name="schedule_date" id="quick_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quick_time_slot" class="form-label">Time Slot *</label>
                                <select name="time_slot_id" id="quick_time_slot" class="form-control" required>
                                    <option value="">-- Select Time --</option>
                                    @if(isset($timeSlots))
                                        @foreach($timeSlots as $slot)
                                            <option value="{{$slot->id}}">{{$slot->start_time}} - {{$slot->end_time}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quick_faculty" class="form-label">Faculty *</label>
                                <select name="user_id" id="quick_faculty" class="form-control" required>
                                    <option value="">-- Select Faculty --</option>
                                    @if(isset($faculties))
                                        @foreach($faculties as $faculty)
                                            <option value="{{$faculty->id}}">{{$faculty->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div id="classroomSection" class="mb-3">
                        <label for="quick_classroom" class="form-label">Classroom *</label>
                        <select name="classroom_id" id="quick_classroom" class="form-control" required>
                            <option value="">-- Select Classroom --</option>
                            @if(isset($classrooms))
                                @foreach($classrooms as $room)
                                    <option value="{{$room->id}}" data-type="{{$room->type}}">
                                        {{$room->name}} ({{ucfirst($room->type)}})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    
                    <div id="practicalGroupSection" class="mb-3" style="display: none;">
                        <label for="quick_practical_group" class="form-label">Practical Group</label>
                        <select name="practical_group_id" id="quick_practical_group" class="form-control">
                            <option value="">-- Select Group (for lab sessions) --</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        The system will automatically check for conflicts before scheduling.
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success" form="quickScheduleForm">
                    <i class="fas fa-plus"></i> Schedule Class
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        events: {
            url: '{{ route("admin.timetable.events") }}',
            failure: function() { 
                showNotification('Error fetching timetable events!', 'error'); 
            },
            success: function(data) {
                updateDashboardStats(data);
            }
        },
        editable: true,
        eventDrop: function(info) {
            if (!confirm("Are you sure you want to move this class?")) { 
                info.revert(); 
            } else {
                handleEventMove(info);
            }
        },
        eventDidMount: function(info) {
            // Enhanced tooltips with more info
            $(info.el).tooltip({
                title: info.event.extendedProps.description || 'No description available',
                placement: 'top', 
                trigger: 'hover', 
                container: 'body', 
                html: true
            });
            
            // Add visual indicators for lab sessions
            if (info.event.extendedProps.isLabSession) {
                info.el.classList.add('lab-session');
                info.el.style.borderLeft = '4px solid #17a2b8';
            }
        },
        eventClick: function(info) {
            showEventDetails(info.event);
        }
    });
    calendar.render();

    // Enhanced update function with stats
    function updateData() {
        const courseId = document.getElementById('courseFilter').value;
        const academicYearId = document.getElementById('academicYearFilter').value;
        const facultyId = document.getElementById('facultyFilter').value;
        const classroomId = document.getElementById('classroomFilter').value;
        const sessionType = document.getElementById('sessionTypeFilter').value;

        var eventSourceUrl = new URL('{{ route("admin.timetable.events") }}');
        if(courseId) eventSourceUrl.searchParams.set('course_id', courseId);
        if(academicYearId) eventSourceUrl.searchParams.set('academic_year_id', academicYearId);
        if(facultyId) eventSourceUrl.searchParams.set('faculty_id', facultyId);
        if(classroomId) eventSourceUrl.searchParams.set('classroom_id', classroomId);
        if(sessionType) eventSourceUrl.searchParams.set('session_type', sessionType);
        
        calendar.getEventSources().forEach(source => source.remove());
        calendar.addEventSource({
            url: eventSourceUrl.toString(),
            success: function(data) {
                updateDashboardStats(data);
            }
        });

        // Update PDF link
        var pdfLink = document.getElementById('pdfExportLink');
        var pdfUrl = new URL('{{ route("admin.timetable.hub.pdf") }}');
        if(courseId) pdfUrl.searchParams.set('course_id', courseId);
        if(academicYearId) pdfUrl.searchParams.set('academic_year_id', academicYearId);
        if(facultyId) pdfUrl.searchParams.set('faculty_id', facultyId);
        if(classroomId) pdfUrl.searchParams.set('classroom_id', classroomId);
        pdfLink.href = pdfUrl.toString();
    }

    // Update dashboard statistics
    function updateDashboardStats(events) {
        const totalClasses = events.length;
        const labSessions = events.filter(e => e.isLabSession).length;
        const uniqueFaculty = [...new Set(events.map(e => e.facultyId).filter(id => id))].length;
        
        document.getElementById('totalClasses').textContent = totalClasses;
        document.getElementById('labSessions').textContent = labSessions;
        document.getElementById('activeFaculty').textContent = uniqueFaculty;
        
        // Check for conflicts
        detectConflictsInEvents(events);
    }

    // Conflict detection
    function detectConflictsInEvents(events) {
        let conflicts = 0;
        const timeSlots = {};
        
        events.forEach(event => {
            const key = `${event.start}-${event.facultyId}`;
            if (timeSlots[key]) {
                conflicts++;
            } else {
                timeSlots[key] = event;
            }
        });
        
        document.getElementById('conflicts').textContent = conflicts;
        
        // Update conflict indicator color
        const conflictCard = document.getElementById('conflicts').closest('.card');
        if (conflicts > 0) {
            conflictCard.classList.remove('border-left-warning');
            conflictCard.classList.add('border-left-danger');
        } else {
            conflictCard.classList.remove('border-left-danger');
            conflictCard.classList.add('border-left-success');
        }
    }

    // Handle event movement with conflict checking
    function handleEventMove(info) {
        fetch('{{ route("admin.timetable.move") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: info.event.id,
                new_date: info.event.start.toISOString().split('T')[0],
                new_start_time: info.event.start.toTimeString().substr(0,5)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Class moved successfully!', 'success');
                calendar.refetchEvents();
            } else {
                showNotification(data.message || 'Failed to move class', 'error');
                info.revert();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to update schedule', 'error');
            info.revert();
        });
    }

    // Show detailed event information
    function showEventDetails(event) {
        const details = `
            <div class="modal fade" id="eventDetailsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header ${event.extendedProps.isLabSession ? 'bg-info' : 'bg-primary'} text-white">
                            <h5 class="modal-title">
                                <i class="fas ${event.extendedProps.isLabSession ? 'fa-flask' : 'fa-chalkboard-teacher'} me-2"></i>
                                ${event.title}
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>📅 Schedule Details</h6>
                                    <p><strong>Date:</strong> ${event.start.toLocaleDateString()}</p>
                                    <p><strong>Time:</strong> ${event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                    <p><strong>Type:</strong> ${event.extendedProps.isLabSession ? 'Lab Session' : 'Regular Class'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>👥 Participants</h6>
                                    <p><strong>Faculty:</strong> ${event.extendedProps.facultyName || 'N/A'}</p>
                                    <p><strong>Room:</strong> ${event.extendedProps.classroomName || 'N/A'}</p>
                                    <p><strong>Students:</strong> ${event.extendedProps.studentCount || 'N/A'}</p>
                                </div>
                            </div>
                            ${event.extendedProps.practicalGroup ? `
                                <div class="alert alert-info">
                                    <strong>Lab Group:</strong> ${event.extendedProps.practicalGroup}
                                </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-warning" onclick="editEvent(${event.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger" onclick="deleteEvent(${event.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(details);
        $('#eventDetailsModal').modal('show');
        $('#eventDetailsModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }

    // Event listeners for filters
    document.getElementById('courseFilter').addEventListener('change', updateData);
    document.getElementById('academicYearFilter').addEventListener('change', updateData);
    document.getElementById('facultyFilter').addEventListener('change', updateData);
    document.getElementById('classroomFilter').addEventListener('change', updateData);
    document.getElementById('sessionTypeFilter').addEventListener('change', updateData);
    
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('courseFilter').value = '';
        document.getElementById('academicYearFilter').value = '';
        document.getElementById('facultyFilter').value = '';
        document.getElementById('classroomFilter').value = '';
        document.getElementById('sessionTypeFilter').value = '';
        updateData();
    });

    // Course selection handler for term loading
    document.getElementById('course_id').addEventListener('change', function() {
        const courseId = this.value;
        const termSelect = document.getElementById('course_term_id');
        
        termSelect.innerHTML = '<option value="">Loading...</option>';
        termSelect.disabled = true;
        
        if (courseId) {
            // Use the correct route URL
            fetch(`{{ url('/admin/courses') }}/${courseId}/terms`)
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Expected JSON response');
                    }
                    
                    return response.json();
                })
                .then(data => {
                    termSelect.innerHTML = '<option value="">-- Select Term --</option>';
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(term => {
                            termSelect.innerHTML += `<option value="${term.id}">${term.name}</option>`;
                        });
                    } else {
                        termSelect.innerHTML += '<option value="">No terms available</option>';
                    }
                    
                    termSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading terms:', error);
                    termSelect.innerHTML = '<option value="">Error loading terms</option>';
                    termSelect.disabled = false;
                    
                    // Show user-friendly error message
                    showNotification('Failed to load course terms. Please try refreshing the page.', 'error');
                });
        } else {
            termSelect.innerHTML = '<option value="">-- Select Course First --</option>';
            termSelect.disabled = true;
        }
    });

    // Lab course selection handler
    document.getElementById('lab_course_id').addEventListener('change', function() {
        const courseId = this.value;
        const termSelect = document.getElementById('lab_course_term_id');
        
        termSelect.innerHTML = '<option value="">Loading...</option>';
        termSelect.disabled = true;
        
        if (courseId) {
            // Load terms
            fetch(`{{ url('/admin/courses') }}/${courseId}/terms`)
                .then(response => response.json())
                .then(data => {
                    termSelect.innerHTML = '<option value="">-- Select Term --</option>';
                    data.forEach(term => {
                        termSelect.innerHTML += `<option value="${term.id}">${term.name}</option>`;
                    });
                    termSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading terms:', error);
                    termSelect.innerHTML = '<option value="">Error loading terms</option>';
                });
            
            // Check prerequisites
            checkLabPrerequisites(courseId);
        } else {
            termSelect.innerHTML = '<option value="">-- Select Course First --</option>';
            termSelect.disabled = true;
            document.getElementById('labPrerequisites').innerHTML = '<div class="small text-muted">Select a course to check prerequisites...</div>';
        }
    });

    // Enhanced form validation and submission
    const generateForm = document.getElementById('generateForm');
    if (generateForm) {
        generateForm.addEventListener('submit', function(e) {
            const courseId = document.getElementById('course_id').value;
            const termId = document.getElementById('course_term_id').value;
            const academicYearId = document.getElementById('academic_year_id').value;
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const generateBtn = document.getElementById('generateBtn');
            
            // Validation
            if (!courseId || !termId || !academicYearId || !startDate || !endDate) {
                e.preventDefault();
                showNotification('Please fill in all required fields.', 'error');
                return false;
            }
            
            // Date validation
            const start = new Date(startDate);
            const end = new Date(endDate);
            const today = new Date();
            
            if (start >= end) {
                e.preventDefault();
                showNotification('End date must be after start date.', 'error');
                return false;
            }
            
            if (start < today.setDate(today.getDate() - 1)) {
                if (!confirm('Start date is in the past. Continue anyway?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Show loading state
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            generateBtn.disabled = true;
            
            // Show confirmation for long date ranges
            const daysDiff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            if (daysDiff > 90) {
                if (!confirm(`You are generating a timetable for ${daysDiff} days. This may take a while. Continue?`)) {
                    e.preventDefault();
                    generateBtn.innerHTML = '<i class="fas fa-cogs"></i> Generate Smart Schedule';
                    generateBtn.disabled = false;
                    return false;
                }
            }
        });
    }

    // Quick schedule form handlers
    const quickForm = document.getElementById('quickScheduleForm');
    if (quickForm) {
        // Subject change handler for lab detection
        document.getElementById('quick_subject_id').addEventListener('change', function() {
            const requiresLab = this.options[this.selectedIndex].dataset.requiresLab === '1';
            const practicalGroupSection = document.getElementById('practicalGroupSection');
            const classroomSelect = document.getElementById('quick_classroom');
            
            if (requiresLab) {
                practicalGroupSection.style.display = 'block';
                // Filter classrooms to show only labs
                Array.from(classroomSelect.options).forEach(option => {
                    if (option.value && option.dataset.type !== 'lab') {
                        option.style.display = 'none';
                    } else {
                        option.style.display = 'block';
                    }
                });
            } else {
                practicalGroupSection.style.display = 'none';
                // Show all classrooms except labs
                Array.from(classroomSelect.options).forEach(option => {
                    if (option.value && option.dataset.type === 'lab') {
                        option.style.display = 'none';
                    } else {
                        option.style.display = 'block';
                    }
                });
            }
        });

        // Batch change handler for practical groups
        document.getElementById('quick_batch_id').addEventListener('change', function() {
            const batchId = this.value;
            const groupSelect = document.getElementById('quick_practical_group');
            
            if (batchId) {
                fetch(`{{ url('/admin/batches') }}/${batchId}/practical-groups`)
                    .then(response => response.json())
                    .then(data => {
                        groupSelect.innerHTML = '<option value="">-- Select Group --</option>';
                        data.forEach(group => {
                            groupSelect.innerHTML += `<option value="${group.id}">${group.name}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Error loading practical groups:', error);
                        groupSelect.innerHTML = '<option value="">Error loading groups</option>';
                    });
            }
        });
    }

    // Lab timetable generation
    document.getElementById('generateLabTimetableBtn').addEventListener('click', function() {
        const form = document.getElementById('labTimetableForm');
        const formData = new FormData(form);
        const btn = this;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        btn.disabled = true;
        
        fetch('{{ route("admin.timetable.generate-lab") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Lab timetable generated successfully!', 'success');
                $('#labTimetableModal').modal('hide');
                calendar.refetchEvents();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to generate lab timetable', 'error');
        })
        .finally(() => {
            btn.innerHTML = '<i class="fas fa-flask"></i> Generate Lab Timetable';
            btn.disabled = false;
        });
    });

    // Utility functions
    window.refreshCalendar = function() {
        calendar.refetchEvents();
        showNotification('Calendar refreshed!', 'success');
    };

    window.detectConflicts = function() {
        // This would call a dedicated conflict detection endpoint
        fetch('{{ route("admin.timetable.conflicts") }}')
            .then(response => response.json())
            .then(data => {
                if (data.conflicts.length === 0) {
                    showNotification('No conflicts detected!', 'success');
                } else {
                    showNotification(`${data.conflicts.length} conflicts found. Check the report for details.`, 'warning');
                    // Could show detailed conflict report here
                }
            })
            .catch(error => {
                console.error('Error checking conflicts:', error);
                showNotification('Failed to check conflicts', 'error');
            });
    };

    window.editEvent = function(eventId) {
        // Implement edit functionality
        window.location.href = `{{ url('/admin/timetable/edit') }}/${eventId}`;
    };

    window.deleteEvent = function(eventId) {
        if (confirm('Are you sure you want to delete this class?')) {
            fetch(`{{ url('/admin/timetable') }}/${eventId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Class deleted successfully!', 'success');
                    calendar.refetchEvents();
                    $('#eventDetailsModal').modal('hide');
                } else {
                    showNotification('Failed to delete class', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting event:', error);
                showNotification('Failed to delete class', 'error');
            });
        }
    };

    function checkLabPrerequisites(courseId) {
        fetch(`{{ url('/admin/courses') }}/${courseId}/lab-prerequisites`)
            .then(response => response.json())
            .then(data => {
                let html = '<ul class="list-unstyled mb-0">';
                
                html += `<li class="${data.has_terms ? 'text-success' : 'text-danger'}">
                    <i class="fas ${data.has_terms ? 'fa-check' : 'fa-times'} me-1"></i>
                    Course Terms: ${data.terms_count}
                </li>`;
                
                html += `<li class="${data.has_lab_subjects ? 'text-success' : 'text-danger'}">
                    <i class="fas ${data.has_lab_subjects ? 'fa-check' : 'fa-times'} me-1"></i>
                    Lab Subjects: ${data.lab_subjects_count}
                </li>`;
                
                html += `<li class="${data.has_practical_groups ? 'text-success' : 'text-danger'}">
                    <i class="fas ${data.has_practical_groups ? 'fa-check' : 'fa-times'} me-1"></i>
                    Practical Groups: ${data.practical_groups_count}
                </li>`;
                
                html += `<li class="${data.has_lab_classrooms ? 'text-success' : 'text-danger'}">
                    <i class="fas ${data.has_lab_classrooms ? 'fa-check' : 'fa-times'} me-1"></i>
                    Lab Classrooms: ${data.lab_classrooms_count}
                </li>`;
                
                html += '</ul>';
                
                document.getElementById('labPrerequisites').innerHTML = html;
                
                // Enable/disable generate button based on prerequisites
                const allMet = data.has_terms && data.has_lab_subjects && data.has_practical_groups && data.has_lab_classrooms;
                document.getElementById('generateLabTimetableBtn').disabled = !allMet;
            })
            .catch(error => {
                console.error('Error checking prerequisites:', error);
                document.getElementById('labPrerequisites').innerHTML = '<div class="text-danger">Error checking prerequisites</div>';
            });
    }

    function showNotification(message, type = 'info') {
        const alertClass = type === 'error' ? 'alert-danger' : 
                          type === 'success' ? 'alert-success' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const notification = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        $('body').append(notification);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            $('.alert').last().fadeOut();
        }, 5000);
    }
    
    // Initialize with current academic year filter
    updateData();
});
</script>

{{-- FullCalendar CSS --}}
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />

{{-- Enhanced Custom CSS --}}
<style>
@media print {
    .no-print { display: none !important; }
    .printable { page-break-inside: avoid; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}

/* Calendar Enhancements */
.fc-event {
    cursor: pointer;
    border-radius: 4px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.fc-event:hover {
    opacity: 0.8;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.fc-event.lab-session {
    background: linear-gradient(135deg, #17a2b8, #138496);
    border-color: #117a8b;
}

.fc-event.lab-session::before {
    content: "🧪";
    margin-right: 4px;
}

/* Enhanced Modal Styling */
.modal-xl { max-width: 1200px; }
.modal-content {
    border-radius: 10px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header {
    border-radius: 10px 10px 0 0;
    border-bottom: none;
}

/* Form Enhancements */
.form-label {
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 8px;
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

/* Alert Enhancements */
.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
}

/* Button Enhancements */
.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-primary {
    background: linear-gradient(135deg, #4e73df, #2653d4);
    border: none;
}

.btn-success {
    background: linear-gradient(135deg, #1cc88a, #13855c);
    border: none;
}

.btn-info {
    background: linear-gradient(135deg, #36b9cc, #258391);
    border: none;
}

.btn-warning {
    background: linear-gradient(135deg, #f6c23e, #dda20a);
    border: none;
}

/* Card Enhancements */
.card {
    border-radius: 10px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fc, #eaecf4);
    border-bottom: 1px solid #e3e6f0;
    border-radius: 10px 10px 0 0;
}

/* Dashboard Stats Cards */
.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
.border-left-danger { border-left: 0.25rem solid #e74a3b !important; }

/* Loading States */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Custom Scrollbars */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Calendar specific */
#calendar {
    background: white;
    border-radius: 8px;
    padding: 15px;
}

.fc-toolbar {
    margin-bottom: 1rem;
}

.fc-button-primary {
    background: #4e73df;
    border-color: #4e73df;
}

.fc-button-primary:hover {
    background: #2e59d9;
    border-color: #2653d4;
}

/* Form Check Enhancements */
.form-check-input:checked {
    background-color: #4e73df;
    border-color: #4e73df;
}

.form-check-label {
    font-weight: 500;
}

/* Badge Enhancements */
.badge {
    font-size: 0.75em;
    border-radius: 4px;
    padding: 0.25em 0.5em;
}

.badge-info {
    background: linear-gradient(135deg, #17a2b8, #138496);
}

/* Tooltip Enhancements */
.tooltip {
    font-size: 0.875rem;
}

.tooltip-inner {
    background: rgba(0,0,0,0.9);
    border-radius: 6px;
    padding: 8px 12px;
}

/* Animation Classes */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translate3d(0, 40px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

/* Responsive Enhancements */
@media (max-width: 768px) {
    .modal-xl {
        max-width: 95%;
    }
    
    .btn-group .btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .row.mb-4 .col-xl-3 {
        margin-bottom: 1rem;
    }
}

/* Notification positioning */
.position-fixed {
    position: fixed !important;
}

/* Safe handling of undefined variables */
.text-muted {
    color: #6c757d !important;
}

/* Better spacing for mobile */
@media (max-width: 576px) {
    .d-sm-flex {
        flex-direction: column;
        align-items: stretch !important;
    }
    
    .d-sm-flex > div {
        margin-top: 1rem;
    }
    
    .btn {
        margin-bottom: 0.5rem;
    }
}
</style>
@endpush