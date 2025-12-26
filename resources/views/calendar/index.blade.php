@extends('layouts.theme')
@section('title', 'Follow-up Calendar')

@push('styles')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        /* --- Layout Styles --- */
        .calendar-wrapper-card {
            background: #fff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            display: flex;
            flex-direction: column;
            height: 80vh; /* Fixed height for the split layout */
        }

        /* --- Sidebar List --- */
        .events-sidebar {
            background: #f8f9fc;
            border-right: 1px solid #e3e6f0;
            overflow-y: auto;
            height: 100%;
        }
        
        .sidebar-title {
            padding: 1.25rem;
            background: #fff;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 700;
            color: #4e73df;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .event-item {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #eaecf4;
            cursor: pointer;
            transition: background 0.2s;
            border-left: 4px solid transparent;
        }
        .event-item:hover {
            background: #fff;
            padding-left: 1.5rem;
        }
        
        /* Status Indicators */
        .item-overdue { border-left-color: #e74a3b !important; }
        .item-today { border-left-color: #f6c23e !important; }
        .item-future { border-left-color: #4e73df !important; }

        /* --- Calendar Area --- */
        .calendar-main {
            padding: 1.5rem;
            overflow-y: auto;
            height: 100%;
        }
        
        .fc-event { cursor: pointer; border: none; font-size: 0.85rem; padding: 2px 4px; }
        .fc-toolbar-title { font-size: 1.5rem !important; color: #5a5c69; }
        .fc-button-primary { background-color: #4e73df !important; border-color: #4e73df !important; }

        @media (max-width: 768px) {
            .calendar-wrapper-card { height: auto; }
            .events-sidebar { height: 300px; border-right: none; border-bottom: 1px solid #e3e6f0; }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid h-100">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Follow-up Schedule</h1>
        </div>

    <div class="card calendar-wrapper-card mb-4">
        <div class="row no-gutters h-100">
            <div class="col-lg-3 events-sidebar">
                <div class="list-group list-group-flush">
                    @forelse($enquiries as $enquiry)
                        <div class="event-item" onclick="openEnquiryModal({{ $enquiry->id }})">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="font-weight-bold text-dark mb-0">{{ $enquiry->student_name }}</h6>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($enquiry->next_follow_up_date)->format('M d') }}
                                </small>
                            </div>
                            <div class="small text-muted">{{ $enquiry->phone_number }}</div>
                        </div>
                    @empty
                        <div class="p-3 text-center text-muted">No events</div>
                    @endforelse
                </div>
            </div>

            <div class="col-lg-9 h-100">
                <div class="calendar-main">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="enquiryQuickViewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0">
            <div class="modal-body p-4" id="enquiryQuickViewContent">
                </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script>
        var calendar; 

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            // Inject data directly (No network call)
            var eventsData = @json($events);

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: '100%',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listWeek'
                },
                events: eventsData,
                
                // Click to open Edit Page
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.open(info.event.url, '_blank');
                    }
                },
                
                eventDidMount: function(info) {
                    info.el.title = info.event.title + "\n" + info.event.extendedProps.phone;
                }
            });
            
            calendar.render();
        });
        
        // In your script section of resources/views/calendar/index.blade.php

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var eventsData = @json($events);

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: '100%',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        events: eventsData,
        
        // --- NEW: Enable Drag and Drop ---
        editable: true, // Allows dragging
        droppable: true,
        
        // Only allow dragging for Enquiry events, not Holidays
        eventAllow: function(dropInfo, draggedEvent) {
            // Check extendedProps we set in controller
            return draggedEvent.extendedProps.type === 'enquiry';
        },

        // --- NEW: Handle the Drop Event ---
        eventDrop: function(info) {
            // Ask for confirmation (Optional, remove if you want instant update)
            if (!confirm("Reschedule " + info.event.title + " to " + info.event.start.toLocaleDateString() + "?")) {
                info.revert(); // Undo the move if they cancel
                return;
            }

            // Format date to YYYY-MM-DD
            // Note: FullCalendar dates are native JS Date objects
            // We need to adjust for timezone offset to ensure strict YYYY-MM-DD match
            var date = new Date(info.event.start);
            var dateString = new Date(date.getTime() - (date.getTimezoneOffset() * 60000 ))
                            .toISOString()
                            .split("T")[0];

            // Send AJAX request
            fetch('{{ route("admin.follow-ups.update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    id: info.event.id,
                    start: dateString
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Use a toaster if you have one (e.g., toastr.success)
                    alert("Rescheduled Successfully!"); 
                } else {
                    alert("Error updating date.");
                    info.revert();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("System error occurred.");
                info.revert();
            });
        },
        
        // --- 1. HANDLE CLICK ---
                eventClick: function(info) {
                    // Prevent default link behavior
                    info.jsEvent.preventDefault();

                    // Check if it's an Enquiry event
                    if (info.event.extendedProps.type === 'enquiry') {
                        // Open the Modal
                        openEnquiryModal(info.event.id);
                    } else if (info.event.url) {
                        // If it's another type (e.g., generic event) with a URL, follow it
                        window.open(info.event.url, '_blank');
                    }
                },
        
        eventDidMount: function(info) {
            // Enhance tooltip to show status
            var status = info.event.extendedProps.status || '';
            info.el.title = info.event.title + "\nStatus: " + status;
        }
    });
    
    calendar.render();
});

// --- 2. AJAX MODAL FUNCTION ---
        function openEnquiryModal(id) {
            // Show Modal with Loading Spinner
            $('#enquiryQuickViewModal').modal('show');
            $('#enquiryQuickViewContent').html(
                '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-gray-500">Loading details...</div></div>'
            );
            
            // Fetch Content
            // This hits EnquiryController@show which we updated to return 'admin.enquiries.modal_show'
            $.get('/admin/enquiries/' + id, function(data) {
                $('#enquiryQuickViewContent').html(data);
            }).fail(function() {
                $('#enquiryQuickViewContent').html(
                    '<div class="text-center py-5 text-danger"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>Failed to load details.</div>'
                );
            });
        }

        function focusDate(dateStr) {
            if(calendar) {
                calendar.gotoDate(dateStr);
            }
        }
    </script>
@endpush