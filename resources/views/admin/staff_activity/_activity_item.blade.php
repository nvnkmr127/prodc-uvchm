@foreach($activities as $activity)
    <li class="timeline-item">
        <div class="timeline-dot"></div>
        <div class="card action-card border-0 shadow-sm" style="border-radius: 1.25rem; transition: transform 0.2s;">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="icon-box mr-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="min-width: 42px; height: 42px; {{ $activity->event == 'created' ? 'background: #ecfdf5; color: #10b981;' : ($activity->event == 'deleted' ? 'background: #fef2f2; color: #ef4444;' : 'background: #f0f9ff; color: #0ea5e9;') }}">
                        <i class="fas {{ $activity->event == 'created' ? 'fa-plus' : ($activity->event == 'deleted' ? 'fa-minus-circle' : 'fa-exchange-alt') }}"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="font-weight-extrabold text-gray-900" style="font-size: 0.95rem; letter-spacing: -0.2px;">{{ $activity->description }}</span>
                            <span class="text-muted small font-weight-bold opacity-75">{{ $activity->created_at->format('M d, H:i') }}</span>
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

                        <div class="d-flex align-items-center mb-3">
                            <span class="badge border-0 bg-light text-muted py-1 px-3 font-weight-bold mr-2" style="font-size: 0.65rem; border-radius: 5px;">
                                {{ class_basename($activity->subject_type) }}
                            </span>
                            @if($contextNote)
                                <span class="badge border-0 bg-primary-soft text-primary py-1 px-3 font-weight-extrabold mr-2" style="font-size: 0.65rem; border-radius: 5px;">
                                    {{ $contextNote }}
                                </span>
                            @endif
                            <span class="text-xs text-muted font-weight-bold">#{{ $activity->subject_id }}</span>
                        </div>

                        {{-- Change Tracking --}}
                        @if($activity->event == 'updated' && isset($activity->properties['attributes']))
                            <div class="mt-3 p-3 rounded-xl bg-white border shadow-sm" style="border-radius: 1rem;">
                                <div class="text-xs font-weight-extrabold text-primary mb-3 text-uppercase tracking-wider">Operational Modification Data</div>
                                <table class="table table-sm table-borderless mb-0" style="font-size: 0.75rem;">
                                    @foreach($activity->properties['attributes'] as $key => $value)
                                        @if(!is_array($value) && !in_array($key, ['created_at', 'updated_at', 'id', 'user_id', 'created_by']))
                                            <tr>
                                                <td class="text-muted font-weight-bold px-0 py-1" style="width: 130px;">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                <td class="text-gray-800 py-1 font-weight-bold">
                                                    @if(isset($activity->properties['old'][$key]))
                                                        <span class="text-muted strike-through mr-2 opacity-50" style="text-decoration: line-through;">{{ $activity->properties['old'][$key] ?: 'None' }}</span>
                                                        <i class="fas fa-long-arrow-alt-right text-primary mx-2"></i>
                                                    @endif
                                                    <span class="text-primary">{{ $value ?: 'Null' }}</span>
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
