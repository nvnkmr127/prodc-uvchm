@extends('layouts.theme')
@section('title', 'Timetable Command Center')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4 no-print">
    <h1 class="h3 mb-0 text-gray-800">Timetable Command Center</h1>
    <div>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#generateModal"><i class="fas fa-cogs fa-sm text-white-50"></i> Generate Schedule</button>
        <button class="btn btn-secondary shadow-sm" onclick="window.print()"><i class="fas fa-print fa-sm"></i> Print View</button>
        <a href="{{ route('admin.timetable.hub.pdf') }}" id="pdfExportLink" class="btn btn-danger btn-sm shadow-sm"><i class="fas fa-file-pdf"></i> Export PDF</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

{{-- Filter Bar --}}
<div class="card shadow mb-4 no-print">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Calendar View</h6>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-3 mb-2"><label>Course</label><select id="courseFilter" class="form-control"><option value="">-- All --</option>@foreach($courses as $course)<option value="{{$course->id}}">{{$course->name}}</option>@endforeach</select></div>
            <div class="col-md-3 mb-2"><label>Faculty</label><select id="facultyFilter" class="form-control"><option value="">-- All --</option>@foreach($faculties as $faculty)<option value="{{$faculty->id}}">{{$faculty->name}}</option>@endforeach</select></div>
            <div class="col-md-3 mb-2"><label>Classroom</label><select id="classroomFilter" class="form-control"><option value="">-- All --</option>@foreach($classrooms as $room)<option value="{{$room->id}}">{{$room->name}}</option>@endforeach</select></div>
            <div class="col-md-3 mb-2"><button id="clearFilters" class="btn btn-secondary">Clear Filters</button></div>
        </div>
    </div>
</div>

{{-- Calendar Display --}}
<div class="card shadow mb-4 printable">
    <div class="card-body">
        <div id='calendar'></div>
    </div>
</div>

{{-- Generate Schedule Modal --}}
<div class="modal fade no-print" id="generateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate New Timetable</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="generateForm" action="{{ route('admin.timetable.generate') }}" method="POST">
                    @csrf
                    <p class="text-danger small">Warning: This will delete any existing schedule for the selected course, term, and date range.</p>
                    <div class="mb-3">
                        <label>1. Select Course*</label>
                        {{-- We add a data-url attribute here to hold the URL template --}}
                        <select name="course_id" id="modalCourseSelect" class="form-control" data-url="{{ route('admin.courses.terms.get', ['course' => 'COURSE_ID_PLACEHOLDER']) }}" required>
                            <option value="">-- Select a Course --</option>
                            @foreach($courses as $course)
                                <option value="{{$course->id}}">{{$course->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>2. Select Academic Term*</label>
                        <select name="course_term_id" id="modalTermSelect" class="form-control" required disabled>
                            <option value="">-- Select a Course First --</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>3. Start Date*</label><input type="date" name="start_date" class="form-control" required></div>
                        <div class="col-6 mb-3"><label>4. End Date*</label><input type="date" name="end_date" class="form-control" required></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Schedule</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        events: {
            url: '{{ route("admin.timetable.events") }}',
            failure: function() { alert('Error fetching events!'); },
        },
        editable: true,
        eventDrop: function(info) {
            if (!confirm("Are you sure you want to move this class?")) { info.revert(); }
            else {
                // Placeholder for advanced conflict check & save
                alert('Conflict check and save logic would run here.');
                info.revert();
            }
        },
        eventDidMount: function(info) {
            $(info.el).tooltip({
                title: info.event.extendedProps.description,
                placement: 'top', trigger: 'hover', container: 'body', html: true
            });
        }
    });
    calendar.render();

    // Function to update calendar and export links
    function updateData() {
        const courseId = document.getElementById('courseFilter').value;
        const facultyId = document.getElementById('facultyFilter').value;
        const classroomId = document.getElementById('classroomFilter').value;

        var eventSourceUrl = new URL('{{ route("admin.timetable.events") }}');
        if(courseId) eventSourceUrl.searchParams.set('course_id', courseId);
        if(facultyId) eventSourceUrl.searchParams.set('faculty_id', facultyId);
        if(classroomId) eventSourceUrl.searchParams.set('classroom_id', classroomId);
        
        calendar.getEventSources().forEach(source => source.remove());
        calendar.addEventSource(eventSourceUrl.toString());

        var pdfLink = document.getElementById('pdfExportLink');
        var pdfUrl = new URL('{{ route("admin.timetable.hub.pdf") }}');
        if(courseId) pdfUrl.searchParams.set('course_id', courseId);
        if(facultyId) pdfUrl.searchParams.set('faculty_id', facultyId);
        if(classroomId) pdfUrl.searchParams.set('classroom_id', classroomId);
        pdfLink.href = pdfUrl.toString();
    }

    document.getElementById('courseFilter').addEventListener('change', updateData);
    document.getElementById('facultyFilter').addEventListener('change', updateData);
    document.getElementById('classroomFilter').addEventListener('change', updateData);
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('courseFilter').value = '';
        document.getElementById('facultyFilter').value = '';
        document.getElementById('classroomFilter').value = '';
        updateData();
    });

    // JavaScript for the modal's dynamic dropdown
    const modalCourseSelect = document.getElementById('modalCourseSelect');
    const termSelect = document.getElementById('modalTermSelect');
    
    modalCourseSelect.addEventListener('change', function() {
        const courseId = this.value;
        const urlTemplate = this.dataset.url; // Get the URL template from the data attribute

        termSelect.innerHTML = '<option value="">Loading...</option>';
        termSelect.disabled = true;

        if (!courseId) {
            termSelect.innerHTML = '<option value="">-- Select a Course First --</option>';
            return;
        }

        // Replace the placeholder with the actual course ID
        const url = urlTemplate.replace('COURSE_ID_PLACEHOLDER', courseId);

        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            termSelect.innerHTML = '<option value="">-- Select a Term --</option>';
            if (data.length > 0) {
                data.forEach(term => {
                    if (term.type === 'Academic') {
                        termSelect.innerHTML += `<option value="${term.id}">${term.name}</option>`;
                    }
                });
                termSelect.disabled = false;
            } else {
                termSelect.innerHTML = '<option value="">-- No Academic Terms Found --</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching terms:', error);
            termSelect.innerHTML = '<option value="">-- Error loading terms --</option>';
        });
    });
});
</script>
@endpush
