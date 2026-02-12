@extends('layouts.theme')
@section('title', 'Timetable Generator')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Timetable Generator</h1>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Generate Schedule</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.timetable.generate') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="course_id">Select Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">-- Select a Course --</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" {{ $selectedCourse && $selectedCourse->id == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Schedule</button>
        </form>
    </div>
</div>

{{-- This section will display the generated timetable grid --}}
@if($selectedCourse)
<div class="card shadow mb-4 printable">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Schedule for {{ $selectedCourse->name }}</h6>
        {{-- THE BUTTONS ARE NOW HERE, and will only show when a schedule exists --}}
        <div class="no-print">
            <button type="button" id="print-btn" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i> Print</button>
            <a href="{{ route('admin.timetable.downloadPDF', $selectedCourse) }}" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> Download PDF</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead class="table-dark">
                    <tr>
                        <th>Time</th>
                        @foreach ($weekdays as $day)
                            <th>{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($timeSlots as $slot)
                        <tr>
                            <td class="align-middle bg-light" style="width: 12%;">
                                {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                            </td>
                            @foreach ($weekdays as $day)
                                <td>
                                    @if(isset($timetable[$day][$slot->id]))
                                        @php $entry = $timetable[$day][$slot->id]; @endphp
                                        <div class="p-2 mb-1 rounded bg-light border">
                                            <strong>{{ $entry->subject->name }}</strong><br>
                                            <small><em>{{ $entry->user->name }}</em></small><br>
                                            <small class="text-muted">{{ $entry->classroom->name }}</small>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    // Check if the print button exists before adding the event listener
    const printButton = document.getElementById('print-btn');
    if (printButton) {
        printButton.addEventListener('click', function() {
            window.print();
        });
    }
</script>
@endpush