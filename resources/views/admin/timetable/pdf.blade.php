<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Timetable - {{ $course->name ?? '' }}</title>
    <style>
        body { font-family: sans-serif; font-size: 9px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .header img { max-height: 70px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 0; font-size: 10px; color: #555; }
        .report-title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .report-period { text-align: center; font-size: 11px; color: #555; margin-bottom: 15px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ccc; padding: 4px; text-align: center; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .time-slot { font-weight: bold; width: 10%; vertical-align: middle; }
        .class-entry { padding: 2px; margin-bottom: 1px; border-radius: 2px; background-color: #f8f9fc; }
        .class-entry strong { font-size: 10px; }
        .class-entry small { font-size: 8px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        @if(setting('college_logo'))
            <img src="{{ public_path('storage/' . setting('college_logo')) }}" alt="College Logo">
        @endif
        <h1>{{ setting('college_name', 'My College') }}</h1>
        <p>{{ setting('college_address', 'College Address, City, State') }}</p>
    </div>

    <h2 class="report-title">Academic Timetable</h2>
    <p class="report-period">
        @if($course)
            <strong>Course:</strong> {{ $course->name }} 
        @endif
        @if($term)
            | <strong>Term:</strong> {{ $term->name }}
        @endif
    </p>
    @if($startDate && $endDate)
        <p class="report-period">{{ $startDate->format('d M, Y') }} to {{ $endDate->format('d M, Y') }}</p>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th class="time-slot">Time</th>
                @foreach ($weekdays as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($timeSlots as $slot)
                <tr>
                    <td class="time-slot">
                        {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}
                    </td>
                    @foreach ($weekdays as $day)
                        <td>
                            @php
                                $entry = $timetable->get($day)?->get($slot->id);
                            @endphp
                            @if($entry)
                                <div class="class-entry">
                                    <strong>{{ $entry->subject?->name ?? 'N/A' }}</strong><br>
                                    <small>{{ $entry->batch?->name ?? 'N/A' }}</small><br>
                                    <small><em>{{ $entry->user?->name ?? 'N/A' }}</em></small><br>
                                    <small>Room: {{ $entry->classroom?->name ?? 'N/A' }}</small>
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
