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
    <tr class="{{ $isOverdue ? 'row-urgent' : ($isDueToday ? 'row-due-today' : '') }} hover-row">
        <td class="pl-3">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input enquiry-checkbox" id="check_{{ $enquiry->id }}"
                    value="{{ $enquiry->id }}">
                <label class="custom-control-label" for="check_{{ $enquiry->id }}"></label>
            </div>
        </td>
        <td>
            <div class="student-trigger" onclick="openEnquiryModal({{ $enquiry->id }})">
                <div class="student-avatar-small" style="background: {{ '#' . substr(md5($enquiry->student_name), 0, 6) }};">
                    {{ strtoupper(substr($enquiry->student_name, 0, 1)) }}
                </div>
                <div class="ml-2">
                    <h6 class="mb-0 font-weight-bold text-gray-800">{{ $enquiry->student_name }}</h6>
                    <div class="small text-muted d-flex align-items-center mt-1">
                        <i class="fas fa-phone-alt fa-xs mr-1 text-primary"></i> {{ $enquiry->phone_number }}
                    </div>
                </div>
            </div>
        </td>
        <td>
            <select class="inline-edit small font-weight-bold text-gray-700" onchange="quickUpdate({{ $enquiry->id }}, 'course_id', this.value)">
                <option value="">Select Course</option>
                @foreach($courses as $id => $name)
                    <option value="{{ $id }}" {{ $enquiry->course_id == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="inline-edit small font-weight-bold {{ $sourceClass }}" onchange="quickUpdate({{ $enquiry->id }}, 'source', this.value)">
                <option value="">No Source</option>
                @foreach(\App\Models\Enquiry::SOURCES as $val => $label)
                    <option value="{{ $val }}" {{ $enquiry->source == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="inline-edit small font-weight-bold text-gray-700" onchange="quickUpdate({{ $enquiry->id }}, 'assigned_to_user_id', this.value)">
                <option value="">Unassigned</option>
                @foreach($counselors as $counselor)
                    <option value="{{ $counselor->id }}" {{ $enquiry->assigned_to_user_id == $counselor->id ? 'selected' : '' }}>
                        {{ $counselor->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="follow-up-cell">
            @if($isOverdue)
                <span class="badge badge-danger-soft badge-pill mb-1 d-block" style="font-size: 0.65rem;">
                    <i class="fas fa-exclamation-circle mr-1"></i> OVERDUE
                </span>
            @elseif($isDueToday)
                <span class="badge badge-warning-soft badge-pill mb-1 d-block" style="font-size: 0.65rem;">
                    <i class="fas fa-clock mr-1"></i> DUE TODAY
                </span>
            @endif
            <input type="date" class="inline-edit {{ $isOverdue ? 'text-urgent font-weight-bold' : ($isDueToday ? 'text-due-today font-weight-bold' : 'small text-muted') }}"
                value="{{ $enquiry->next_follow_up_date ? \Carbon\Carbon::parse($enquiry->next_follow_up_date)->format('Y-m-d') : '' }}"
                onchange="quickUpdate({{ $enquiry->id }}, 'next_follow_up_date', this.value)">
        </td>
        <td class="text-center">
            <select class="inline-edit badge-pill-custom status-{{ Str::slug($enquiry->status) }} border-0 font-weight-bold" 
                onchange="quickUpdate({{ $enquiry->id }}, 'status', this.value)" 
                style="appearance: none; text-align-last: center; cursor: pointer;">
                @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Admitted', 'Interested Next Year', 'Not Interested'] as $status)
                    <option value="{{ $status }}" {{ $enquiry->status == $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                <button type="button" class="btn btn-icon btn-sm btn-light-primary mr-1" onclick="openEnquiryModal({{ $enquiry->id }})" title="Quick View">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="dropdown no-arrow">
                    <button class="btn btn-icon btn-sm btn-light-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                        <a class="dropdown-item" href="tel:{{ $enquiry->phone_number }}">
                            <i class="fas fa-phone-alt fa-sm fa-fw mr-2 text-success"></i> Call Now
                        </a>
                        <a class="dropdown-item" href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $enquiry->phone_number) }}" target="_blank">
                            <i class="fab fa-whatsapp fa-sm fa-fw mr-2 text-warning"></i> WhatsApp
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('admin.enquiries.edit', $enquiry->id) }}">
                            <i class="fas fa-user-edit fa-sm fa-fw mr-2 text-info"></i> Full Profile
                        </a>
                        @if($enquiry->status !== 'Admitted')
                        <a class="dropdown-item" href="{{ route('admin.enquiries.convertToAdmission', $enquiry->id) }}">
                            <i class="fas fa-user-check fa-sm fa-fw mr-2 text-success"></i> Convert to Admission
                        </a>
                        @endif
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('admin.enquiries.destroy', $enquiry->id) }}" method="POST" onsubmit="return confirm('Delete permanently?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-trash-alt fa-sm fa-fw mr-2"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center py-5">
            <div class="empty-state py-5">
                <img src="https://img.icons8.com/bubbles/100/000000/empty-box.png" class="mb-3 opacity-50" style="width: 120px;">
                <h5 class="text-gray-500 font-weight-bold">No Enquiries Found</h5>
                <p class="text-muted small">Try adjusting your filters or search term</p>
                <button type="button" onclick="resetFilters()" class="btn btn-sm btn-primary px-4 mt-2">Clear Filters</button>
            </div>
        </td>
    </tr>
@endforelse