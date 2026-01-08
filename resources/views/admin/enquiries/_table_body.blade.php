@forelse($enquiries as $enquiry)
    <tr class="{{ $enquiry->is_urgent ? 'row-urgent' : '' }}">
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
                    {{ $enquiry->initials }}
                </div>
                <div>
                    <h6 class="mb-0 font-weight-bold text-gray-800">{{ $enquiry->student_name }}
                    </h6>
                    <div class="small text-muted">
                        <i class="fas fa-phone fa-xs mr-1"></i>{{ $enquiry->phone_number }}
                    </div>
                </div>
            </div>
        </td>
        <td>
            <span class="badge badge-light border text-gray-600">
                {{ $enquiry->course->name ?? 'General' }}
            </span>
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
            <input type="date" class="inline-edit {{ $enquiry->is_urgent ? 'text-urgent' : '' }}"
                value="{{ $enquiry->next_follow_up_date ? $enquiry->next_follow_up_date->format('Y-m-d') : '' }}"
                onchange="quickUpdate({{ $enquiry->id }}, 'next_follow_up_date', this.value)">
        </td>
        <td class="text-center">
            <span class="badge badge-pill-custom status-{{ Str::slug($enquiry->status) }}">
                {{ $enquiry->status }}
            </span>
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
                @hasanyrole('admin|super-admin|college-admin')
                    <form action="{{ route('admin.enquiries.destroy', $enquiry->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Delete?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-light btn-sm btn-circle" title="Delete">
                            <i class="fas fa-trash text-danger"></i>
                        </button>
                    </form>
                @endhasanyrole
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
            <h5>No enquiries found</h5>
        </td>
    </tr>
@endforelse