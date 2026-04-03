@foreach($activities as $activity)
    <tr class="action-row">
        <td class="px-4 py-3">
            <div class="d-flex align-items-center">
                <div class="icon-box mr-2 {{ $activity->event == 'created' ? 'bg-success-soft text-success' : ($activity->event == 'deleted' ? 'bg-danger-soft text-danger' : 'bg-primary-soft text-primary') }}" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(0,0,0,0.03);">
                    <i class="fas {{ $activity->event == 'created' ? 'fa-plus-circle' : ($activity->event == 'deleted' ? 'fa-trash-alt' : 'fa-edit') }} fa-sm"></i>
                </div>
                <span class="text-xs font-weight-bold text-uppercase">{{ $activity->event }}</span>
            </div>
        </td>
        <td class="py-3">
            <span class="badge badge-light border py-1 px-2 small font-weight-bold" style="font-size: 0.65rem;">
                <i class="fas fa-layer-group mr-1 text-muted"></i> {{ class_basename($activity->subject_type) }}
            </span>
        </td>
        <td class="py-3">
            <div class="font-weight-bold text-gray-900 small mb-1">{{ $activity->description }}</div>
            
            {{-- Context Note --}}
            @php
                $contextNote = null;
                try {
                    if ($activity->subject) {
                        $subject = $activity->subject;
                        if (isset($subject->name)) $contextNote = $subject->name;
                        elseif (isset($subject->first_name)) $contextNote = $subject->first_name . ' ' . ($subject->last_name ?? '');
                        elseif (isset($subject->amount)) $contextNote = '₹' . number_format($subject->amount);
                        elseif (isset($subject->title)) $contextNote = $subject->title;
                    }
                } catch (\Exception $e) {}
            @endphp
            
            @if($contextNote)
                <span class="badge badge-primary-soft text-primary py-1 px-2 small" style="font-size: 0.65rem; border: 1px dashed #4e73df;">
                    <i class="fas fa-link mr-1"></i> {{ $contextNote }}
                </span>
            @endif
            <span class="text-xs text-muted ml-1">ID: {{ $activity->subject_id }}</span>
        </td>
        <td class="py-3">
            @if($activity->event == 'updated' && isset($activity->properties['attributes']))
                <button class="btn btn-link btn-sm p-0 text-primary font-weight-bold text-xs" type="button" data-toggle="collapse" data-target="#changes-{{ $activity->id }}">
                    <i class="fas fa-history mr-1"></i> View Changes
                </button>
                <div class="collapse" id="changes-{{ $activity->id }}">
                    <div class="mt-2 p-2 rounded bg-light border small">
                        <table class="table table-sm table-borderless mb-0" style="font-size: 0.7rem;">
                            @foreach($activity->properties['attributes'] as $key => $value)
                                @if(!is_array($value) && !in_array($key, ['created_at', 'updated_at', 'id', 'user_id', 'created_by']))
                                    <tr>
                                        <td class="text-muted pr-2 py-0" style="width: 80px;">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                        <td class="py-0">
                                            @if(isset($activity->properties['old'][$key]))
                                                <span class="text-muted strike-through" style="text-decoration: line-through; opacity: 0.6;">{{ $activity->properties['old'][$key] ?: 'N/A' }}</span>
                                                <i class="fas fa-arrow-right fa-xs mx-1 opacity-50"></i>
                                            @endif
                                            <span class="text-primary font-weight-bold">{{ $value ?: 'Null' }}</span>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </table>
                    </div>
                </div>
            @else
                <span class="text-xs text-muted italic">No property changes recorded</span>
            @endif
        </td>
        <td class="px-4 py-3 text-right">
            <div class="text-gray-900 font-weight-bold small">{{ $activity->created_at->format('h:i A') }}</div>
            <div class="text-xs text-muted">{{ $activity->created_at->format('M d, Y') }}</div>
        </td>
    </tr>
@endforeach
