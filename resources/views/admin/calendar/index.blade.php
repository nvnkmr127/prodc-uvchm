@extends('layouts.theme')
@section('title', 'Follow-up Calendar')

@push('styles')
    {{-- Add FullCalendar CSS --}}
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        /* Custom styles to make the calendar look better with the theme */
        .fc-event {
            cursor: pointer;
        }
        .fc-daygrid-event .fc-event-title {
            font-weight: 600;
            color: #fff;
        }
        .fc-h-event {
            border: 1px solid #e3e6f0;
        }
    </style>
@endpush

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Follow-up Calendar</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div id='calendar'></div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- Add FullCalendar JS --}}
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth', // Start with month view
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                // Fetch events from our controller
                events: '{{ route("admin.follow-ups.calendar") }}',
                eventClick: function(info) {
                    // Prevent the default browser action
                    info.jsEvent.preventDefault(); 
                    
                    // If the event has a URL, go to it
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                },
                loading: function(isLoading) {
                    // Optional: show a loading indicator
                    if (isLoading) {
                        // You could show a spinner here
                    } else {
                        // And hide it here
                    }
                }
            });
            calendar.render();
        });
    </script>
@endpush
