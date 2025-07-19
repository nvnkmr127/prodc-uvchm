<tr>
    <td>
        {{-- This makes the main description clickable --}}
        @if($activity->subject)
            @if($activity->subject_type == 'App\Models\Student')
                <a href="{{ route('admin.students.show', $activity->subject) }}">{{ $activity->description }}</a>
            @elseif($activity->subject_type == 'App\Models\Invoice')
                <a href="{{ route('admin.invoices.show', $activity->subject) }}">{{ $activity->description }}</a>
            @else
                {{ $activity->description }}
            @endif
        @else
            {{ $activity->description }}
        @endif
    </td>
    <td>{{ $activity->causer->name ?? 'System' }}</td>
    <td>
        @if($activity->properties->has('old') || $activity->properties->has('attributes'))
            <table class="table table-sm table-bordered m-0">
                @foreach($activity->properties['attributes'] as $key => $value)
                    @if(isset($activity->properties['old'][$key]) && $activity->properties['old'][$key] != $value)
                        <tr>
                            <td class="p-1"><strong>{{ Str::title(str_replace('_', ' ', $key)) }}</strong></td>
                            <td class="p-1"><span class="text-danger">{{ $activity->properties['old'][$key] }}</span> &rarr; <span class="text-success">{{ $value }}</span></td>
                        </tr>
                    @endif
                @endforeach
            </table>
        @endif
    </td>
    <td>{{ $activity->created_at->format('d M, Y h:i A') }}</td>
</tr>