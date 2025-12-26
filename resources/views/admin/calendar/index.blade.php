@extends('layouts.theme')
@section('title', 'Follow-up Calendar')

@push('styles')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        .fc-event { cursor: pointer; border: none !important; }
        .fc-daygrid-event { padding: 2px 5px; border-radius: 4px; }
        .fc-daygrid-event .fc-event-title { font-weight: 600; font-size: 0.85rem; }
        /* Remove underline from links */
        a.fc-event:hover { text-decoration: none; }
        /* Fix toolbar responsiveness */
        .fc-header-toolbar { flex-wrap: wrap; gap: 10px; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Follow-up Calendar</h1>
        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-list fa-sm text-white-50 mr-1"></i> List View
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body p-4">
            <div id='calendar'></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 'auto', // Adjusts height automatically
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listWeek'
                },
                // Use simple URL string for events source
                events: "{{ route('admin.follow-ups.calendar') }}",
                
                // Handle Event Click
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.open(info.event.url, '_blank'); // Opens edit page in new tab
                    }
                },
                
                // Show loading spinner
                loading: function(isLoading) {
                    if (isLoading) {
                        document.body.style.cursor = 'wait';
                    } else {
                        document.body.style.cursor = 'default';
                    }
                }
            });
            calendar.render();
        });
    </script>
@endpush