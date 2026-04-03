@foreach($activities as $activity)
    <li class="timeline-item">
        <div class="timeline-dot"></div>
        <div class="card action-card border-0 shadow-sm" style="background: #fcfcfd;">
            <div class="card-body p-3">
                <div class="d-flex align-items-start">
                    <div class="icon-box mr-3 {{ $activity->event == 'created' ? 'bg-success-soft text-success' : ($activity->event == 'deleted' ? 'bg-danger-soft text-danger' : 'bg-primary-soft text-primary') }}" style="background: rgba(0,0,0,0.03);">
                        <i class="fas {{ $activity->event == 'created' ? 'fa-plus-circle' : ($activity->event == 'deleted' ? 'fa-trash-alt' : 'fa-edit') }}"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="font-weight-bold text-gray-900">{{ $activity->description }}</span>
                            <span class="text-muted small font-weight-bold">{{ $activity->created_at->format('M d, h:i A') }}</span>
                        </div>
                        
                        {{-- Context Note (Record Name/ID) --}}
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

                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge badge-light py-1 px-2 border small" style="font-size: 0.65rem;">
                                <i class="fas fa-layer-group mr-1"></i> {{ class_basename($activity->subject_type) }}
                            </span>
                            @if($contextNote)
                                <span class="badge badge-primary-soft text-primary py-1 px-2 border small" style="font-size: 0.65rem;">
                                    <i class="fas fa-info-circle mr-1"></i> {{ $contextNote }}
                                </span>
                            @endif
                            <span class="text-xs text-muted">ID: {{ $activity->subject_id }}</span>
                        </div>

                        {{-- Change Tracking --}}
                        @if($activity->event == 'updated' && isset($activity->properties['attributes']))
                            <div class="mt-2 p-2 rounded border-left-primary bg-white shadow-xs border" style="border-left-width: 4px;">
                                <div class="text-xs font-weight-bold text-primary mb-1 text-uppercase">Updated Details</div>
                                <table class="table table-sm table-borderless mb-0" style="font-size: 0.72rem;">
                                    @foreach($activity->properties['attributes'] as $key => $value)
                                        @if(!is_array($value) && !in_array($key, ['created_at', 'updated_at', 'id', 'user_id', 'created_by']))
                                            <tr>
                                                <td class="text-muted font-weight-bold px-0 py-1" style="width: 110px;">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                <td class="text-gray-800 py-1">
                                                    @if(isset($activity->properties['old'][$key]))
                                                        <span class="text-danger-soft strike-through mr-1" style="text-decoration: line-through; opacity: 0.6;">{{ $activity->properties['old'][$key] ?: 'None' }}</span>
                                                        <i class="fas fa-arrow-right fa-xs mx-1 opacity-50"></i>
                                                    @endif
                                                    <span class="font-weight-bold text-success">{{ $value ?: 'Null' }}</span>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </li>
@endforeach
