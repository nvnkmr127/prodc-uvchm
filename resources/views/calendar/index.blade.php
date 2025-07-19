@extends('layouts.theme')
@section('title', 'My Calendar')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">My Calendar</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        {{-- This is the container where FullCalendar will render the calendar --}}
        <div id='calendar'></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    // Initialize FullCalendar
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth', // Start with the month view
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay' // Add buttons to switch views
        },
        // This is where we pass the event data from our controller to the calendar
        events: {!! $events !!},

        // This makes events clickable if they have a URL (like our follow-ups)
        eventClick: function(info) {
            if (info.event.url) {
                window.open(info.event.url, "_blank");
                info.jsEvent.preventDefault(); // Prevent the browser from following the link
            }
        },

        // This adds a helpful tooltip to show the classroom for timetable entries
        eventDidMount: function(info) {
            if (info.event.extendedProps.description) {
                // Using Bootstrap's tooltip functionality
                $(info.el).tooltip({
                    title: info.event.extendedProps.description,
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body'
                });
            }
        }
    });

    calendar.render();
});
</script>
@endpush