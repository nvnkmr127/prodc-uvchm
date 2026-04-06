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
            <select class="form-control form-control-sm border-0 bg-light select-counselor" onchange="quickUpdate({{ $enquiry->id }}, 'assigned_to_user_id', this.value)">
                <option value="">Unassigned</option>
                @foreach($counselors as $counselor)
                    <option value="{{ $counselor->id }}" {{ $enquiry->assigned_to_user_id == $counselor->id ? 'selected' : '' }}>
                        {{ $counselor->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="form-control form-control-sm border-0 font-weight-bold status-{{ Str::slug($enquiry->status) }}"
                onchange="quickUpdate({{ $enquiry->id }}, 'status', this.value)" style="cursor:pointer; min-width: 100px;">
                @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Interested Next Year', 'Admitted', 'Next Entrance Exam', 'Not Interested'] as $s)
                    <option value="{{ $s }}" {{ $enquiry->status == $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="date" class="form-control form-control-sm border-0 {{ $isOverdue ? 'text-danger font-weight-bold' : ($isDueToday ? 'text-primary' : '') }}"
                value="{{ $enquiry->next_follow_up_date ? \Carbon\Carbon::parse($enquiry->next_follow_up_date)->format('Y-m-d') : '' }}"
                onchange="quickUpdate({{ $enquiry->id }}, 'next_follow_up_date', this.value)">
        </td>
        <td>
            @if($enquiry->source)
                <span class="badge badge-outline-secondary">{{ $enquiry->source }}</span>
            @else
                <span class="text-muted small">—</span>
            @endif
        </td>
        <td class="text-right pr-4">
            <div class="d-flex justify-content-end gap-1">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-circle px-2" onclick="openEnquiryModal({{ $enquiry->id }})"
                    title="Quick Edit Profile">
                    <i class="fas fa-user-edit"></i>
                </button>
                <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $enquiry->phone_number) }}" target="_blank"
                    class="btn btn-outline-success btn-sm rounded-circle px-2" title="WhatsApp Message">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <form action="{{ route('admin.enquiries.destroy', $enquiry->id) }}" method="POST" class="ml-1"
                    onsubmit="return confirm('Delete this record?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle px-2" title="Delete">
                        <i class="fas fa-trash"></i>
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
            <p>Try adjusting your search or filters to find what you're looking for.</p>
        </td>
    </tr>
@endforelse