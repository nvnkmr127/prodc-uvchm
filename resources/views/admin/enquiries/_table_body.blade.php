@forelse($enquiries as $enquiry)
    @php
        $date = $enquiry->next_follow_up_date;
        $isOverdue = false;
        $isDueToday = false;
        if ($date && $enquiry->status != 'Admitted') {
            $followUpDate = \Carbon\Carbon::parse($date)->startOfDay();
            $today        = now()->startOfDay();
            if ($followUpDate->lt($today)) {
                $isOverdue = true;
            } elseif ($followUpDate->eq($today)) {
                $isDueToday = true;
            }
        }

        // Source badge CSS class
        $sourceClass = 'source-' . Str::slug($enquiry->source ?? 'other');
    @endphp
    <tr class="{{ $isOverdue ? 'row-urgent' : ($isDueToday ? 'row-due-today' : '') }}">
        <td class="pl-3">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input enquiry-checkbox" id="check_{{ $enquiry->id }}"
                    value="{{ $enquiry->id }}">
                <label class="custom-control-label" for="check_{{ $enquiry->id }}"></label>
            </div>
        </td>
        <td>
            <div class="student-trigger" onclick="openEnquiryModal({{ $enquiry->id }})">
                <div class="student-avatar-small">
                    {{ strtoupper(substr($enquiry->student_name, 0, 1)) }}
                </div>
                <div>
                    <h6 class="mb-0 font-weight-bold text-gray-800">{{ $enquiry->student_name }}</h6>
                    <div class="small text-muted">
                        <i class="fas fa-phone fa-xs mr-1"></i>{{ $enquiry->phone_number }}
                    </div>
                    @if($enquiry->address)
                        <div class="small text-muted" style="font-size:0.7rem;">
                            <i class="fas fa-map-marker-alt fa-xs mr-1"></i>{{ Str::limit($enquiry->address, 25) }}
                        </div>
                    @endif
                </div>
            </div>
        </td>
        <td>
            <span class="badge badge-light border text-gray-600">
                {{ $enquiry->course->name ?? 'General' }}
            </span>
        </td>
        <td>
            @if($enquiry->source)
                <span class="source-badge {{ $sourceClass }}">{{ $enquiry->source }}</span>
            @else
                <span class="text-muted small">—</span>
            @endif
        </td>
        <td>
            <select class="inline-edit" onchange="quickUpdate({{ $enquiry->id }}, 'assigned_to_user_id', this.value)">
                <option value="">Unassigned</option>
                @foreach($counselors as $counselor)
                    <option value="{{ $counselor->id }}" {{ $enquiry->assigned_to_user_id == $counselor->id ? 'selected' : '' }}>
                        {{ $counselor->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            @if($isOverdue)
                <div class="small text-urgent mb-1"><i class="fas fa-exclamation-circle mr-1"></i>Overdue</div>
            @elseif($isDueToday)
                <div class="small text-due-today mb-1"><i class="fas fa-clock mr-1"></i>Due Today</div>
            @endif
            <input type="date" class="inline-edit {{ $isOverdue ? 'text-urgent' : ($isDueToday ? 'text-due-today' : '') }}"
                value="{{ $enquiry->next_follow_up_date ? \Carbon\Carbon::parse($enquiry->next_follow_up_date)->format('Y-m-d') : '' }}"
                onchange="quickUpdate({{ $enquiry->id }}, 'next_follow_up_date', this.value)">
        </td>
        <td class="text-center">
            <select class="inline-edit badge-pill-custom status-{{ Str::slug($enquiry->status) }} border-0 font-weight-bold"
                onchange="quickUpdate({{ $enquiry->id }}, 'status', this.value)" style="appearance: none; -webkit-appearance: none; cursor:pointer;">
                @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Admitted', 'Interested Next Year', 'Next Entrance Exam', 'Not Interested'] as $s)
                    <option value="{{ $s }}" {{ $enquiry->status == $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <div class="d-flex align-items-center">
                <input type="checkbox" class="mr-2" {{ $enquiry->test_attended ? 'checked' : '' }} 
                    onchange="quickUpdate({{ $enquiry->id }}, 'test_attended', this.checked ? 1 : 0)" title="Attended Test">
                
                @if($enquiry->test_attended)
                    <div class="small">
                        <div class="text-primary font-weight-bold" title="Marks">
                            <i class="fas fa-poll mr-1"></i>{{ $enquiry->test_marks ?? '0' }}
                        </div>
                        @if($enquiry->discount_offered > 0)
                            <div class="text-success font-weight-bold" title="Discount">
                                <i class="fas fa-tags mr-1"></i>₹{{ number_format($enquiry->discount_offered, 0) }}
                            </div>
                        @endif
                    </div>
                @else
                    <span class="text-muted small">Pending</span>
                @endif
            </div>
        </td>
        <td class="text-center">
            <div class="btn-group">
                <button type="button" class="btn btn-light btn-sm btn-circle" onclick="openEnquiryModal({{ $enquiry->id }})"
                    title="View">
                    <i class="fas fa-eye text-primary"></i>
                </button>
                <a href="tel:{{ $enquiry->phone_number }}" class="btn btn-light btn-sm btn-circle" title="Call">
                    <i class="fas fa-phone text-success"></i>
                </a>
                <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $enquiry->phone_number) }}" target="_blank"
                    class="btn btn-light btn-sm btn-circle" title="WhatsApp">
                    <i class="fab fa-whatsapp text-warning"></i>
                </a>
                <form action="{{ route('admin.enquiries.destroy', $enquiry->id) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('Delete?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-light btn-sm btn-circle" title="Delete">
                        <i class="fas fa-trash text-danger"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
            <h5>No enquiries found</h5>
        </td>
    </tr>
@endforelse