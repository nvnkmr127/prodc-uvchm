<style>
    /* Modal Specific Styles */
    .modal-profile-avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: #eaecf4;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        font-weight: 800;
        color: #4e73df;
        margin: 0 auto 0.5rem;
        border: 3px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .form-label-small {
        font-size: 0.7rem;
        text-transform: uppercase;
        font-weight: 700;
        color: #858796;
        margin-bottom: 0.1rem;
    }

    .form-control-sm-custom {
        border-radius: 0.25rem;
        font-size: 0.85rem;
        padding: 0.4rem 0.7rem;
    }

    .quick-sched-btn {
        border: 1px solid #e3e6f0;
        background: white;
        color: #5a5c69;
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.2rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .quick-sched-btn:hover, .quick-sched-btn.active {
        background: #4e73df;
        color: white;
        border-color: #4e73df;
    }

    .timeline-container-modal {
        max-height: 600px; 
        overflow-y: auto; 
        padding-right: 5px;
        border-top: 1px solid #eaecf4;
        padding-top: 1rem;
    }

    /* Scrollbar styling for the modal timeline */
    .timeline-container-modal::-webkit-scrollbar {
        width: 6px;
    }
    .timeline-container-modal::-webkit-scrollbar-thumb {
        background-color: #d1d3e2;
        border-radius: 10px;
    }
    .shadow-xs {
        box-shadow: 0 .125rem .25rem 0 rgba(58,59,69,.05)!important;
    }
    .profile-scroll-container {
        max-height: 580px;
        overflow-y: auto;
        padding-right: 8px;
    }
    .profile-scroll-container::-webkit-scrollbar {
        width: 4px;
    }
    .profile-scroll-container::-webkit-scrollbar-thumb {
        background-color: #eaecf4;
        border-radius: 10px;
    }
</style>

<div class="container-fluid px-10">
    <div class="row">
        <div class="col-md-5 border-right profile-scroll-container">
            
            <div class="text-center mb-3">
                <div class="modal-profile-avatar">
                    {{ strtoupper(substr($enquiry->student_name, 0, 1)) }}
                </div>
                <h5 class="font-weight-bold text-gray-800 mb-1">{{ $enquiry->student_name }}</h5>
                <div class="badge badge-primary">{{ $enquiry->course->name ?? 'General Enquiry' }}</div>
                
                <div class="d-flex justify-content-center gap-2 mt-2">
                    <a href="tel:{{ $enquiry->phone_number }}" class="btn btn-success btn-sm btn-circle" title="Call">
                        <i class="fas fa-phone"></i>
                    </a>
                    <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $enquiry->phone_number) }}" target="_blank" class="btn btn-warning btn-sm btn-circle" title="WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>

            <form action="{{ route('admin.enquiries.update', $enquiry->id) }}" method="POST" id="modalEditForm">
                @csrf
                @method('PUT')

                <!-- 1. BASIC INFORMATION -->
                <div class="mb-3 p-3 bg-white rounded border shadow-xs">
                    <h6 class="font-weight-bold text-gray-800 mb-3 small text-uppercase">
                        <i class="fas fa-user-circle text-primary mr-2"></i>Basic Information
                    </h6>
                    <div class="form-group mb-3">
                        <label class="form-label-small">Full Student Name</label>
                        <input type="text" name="student_name" class="form-control form-control-sm-custom border-0 bg-light" value="{{ $enquiry->student_name }}" required>
                    </div>

                    <div class="row">
                        <div class="col-6 pr-1">
                            <div class="form-group mb-2">
                                <label class="form-label-small">Primary Phone</label>
                                <input type="tel" name="phone_number" class="form-control form-control-sm-custom border-0 bg-light font-weight-bold" value="{{ $enquiry->phone_number }}" required>
                            </div>
                        </div>
                        <div class="col-6 pl-1">
                            <div class="form-group mb-2">
                                <label class="form-label-small">Gender</label>
                                <select name="gender" class="form-control form-control-sm-custom border-0 bg-light">
                                    <option value="">Select...</option>
                                    @foreach(['Male', 'Female', 'Other'] as $g)
                                        <option value="{{ $g }}" {{ $enquiry->gender == $g ? 'selected' : '' }}>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. STATUS & ASSIGNMENT -->
                <div class="mb-3 p-3 bg-white rounded border shadow-xs">
                    <h6 class="font-weight-bold text-gray-800 mb-3 small text-uppercase">
                        <i class="fas fa-stream text-warning mr-2"></i>Status & Source
                    </h6>
                    <div class="row">
                        <div class="col-6 pr-1">
                            <div class="form-group mb-3">
                                <label class="form-label-small">Current Status</label>
                                <select name="status" class="form-control form-control-sm-custom font-weight-bold {{ $enquiry->status == 'Admitted' ? 'text-success' : 'text-primary' }} border-0 bg-light">
                                    @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Interested Next Year', 'Next Entrance Exam', 'Not Interested', 'Admitted'] as $status)
                                        <option value="{{ $status }}" {{ $enquiry->status == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6 pl-1">
                            <div class="form-group mb-3">
                                <label class="form-label-small">Assigned Counselor</label>
                                <select name="assigned_to_user_id" class="form-control form-control-sm-custom border-0 bg-light">
                                     <option value="">Unassigned</option>
                                    @foreach($counselors as $counselor)
                                        <option value="{{ $counselor->id }}" {{ $enquiry->assigned_to_user_id == $counselor->id ? 'selected' : '' }}>
                                            {{ $counselor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label-small">Lead Source</label>
                        <select name="source" class="form-control form-control-sm-custom border-0 bg-light" id="modalSourceSelect">
                            <option value="">Select Source</option>
                            @foreach(\App\Models\Enquiry::SOURCES as $sourceKey => $sourceVal)
                                <option value="{{ $sourceKey }}" {{ $enquiry->source == $sourceKey ? 'selected' : '' }}>{{ $sourceVal }}</option>
                            @endforeach
                            {{-- Important: Display current source if it's not in the standard list (e.g. from bulk import or old data) --}}
                            @if($enquiry->source && !array_key_exists($enquiry->source, \App\Models\Enquiry::SOURCES))
                                <option value="{{ $enquiry->source }}" selected>{{ $enquiry->source }} (Custom)</option>
                            @endif
                        </select>
                    </div>

                    <div class="form-group mb-0" id="modalReferralWrapper" style="display: none;">
                        <label class="form-label-small" id="modalReferralLabel">Referral Name</label>
                        <input type="text" name="referral_name" id="modalReferralInput" class="form-control form-control-sm-custom border-0 bg-light" value="{{ $enquiry->referral_name }}">
                    </div>
                </div>

                <!-- 3. TEST & KIT SECTION (Conditional) -->
                <div class="mb-3 p-3 bg-white rounded border shadow-xs" style="border-left: 4px solid #4e73df !important;">
                    <h6 class="font-weight-bold text-primary mb-3 small text-uppercase">
                        <i class="fas fa-clipboard-check mr-2"></i>Entrance & Package
                    </h6>
                    
                    <div class="form-group mb-3">
                        <label class="form-label-small">Test Attended?</label>
                        <select name="test_attended" id="modalTestAttended" class="form-control form-control-sm-custom border-0 bg-light font-weight-bold">
                            <option value="0" {{ !$enquiry->test_attended ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $enquiry->test_attended ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div id="testConditionalFields" style="{{ $enquiry->test_attended ? 'display:block' : 'display:none' }}">
                        <div class="row mb-3">
                            <div class="col-6 pr-1">
                                <div class="form-group mb-0">
                                    <label class="form-label-small">Test Marks</label>
                                    <input type="number" name="test_marks" class="form-control form-control-sm-custom border-0 bg-light" value="{{ $enquiry->test_marks }}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-6 pl-1">
                                <div class="form-group mb-0">
                                    <label class="form-label-small">Discount Offered (₹)</label>
                                    <input type="number" name="discount_offered" class="form-control form-control-sm-custom border-0 bg-light font-weight-bold text-success" value="{{ $enquiry->discount_offered }}" placeholder="0">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label-small text-primary">Final Agreed Fee (₹)</label>
                            <input type="number" name="agreed_fee" class="form-control form-control-sm-custom border-0 bg-primary text-white font-weight-bold" value="{{ $enquiry->agreed_fee }}" placeholder="Total Package Fee">
                        </div>

                        <div class="row bg-light rounded p-2 mx-0 mb-3">
                            <div class="col-12">
                                <label class="form-label-small mb-2 d-inline-block">Kit Assignments:</label>
                            </div>
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="include_uniform" value="1" class="custom-control-input" id="modalUniformCheck" {{ $enquiry->include_uniform ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold small" for="modalUniformCheck">Uniform Marked</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                     <input type="checkbox" name="include_books" value="1" class="custom-control-input" id="modalBooksCheck" {{ $enquiry->include_books ? 'checked' : '' }}>
                                     <label class="custom-control-label font-weight-bold small" for="modalBooksCheck">Books Marked</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label-small ml-1">Current Residential Address</label>
                    <textarea name="address" class="form-control form-control-sm-custom border-0 bg-light" rows="2" placeholder="Address information...">{{ $enquiry->address }}</textarea>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <div class="row no-gutters">
                        <div class="col-8 pr-1">
                            <button type="submit" class="btn btn-primary btn-block shadow-sm font-weight-bold py-2">
                                <i class="fas fa-save mr-1"></i> Update Profile
                            </button>
                        </div>
                        <div class="col-4 pl-1">
                            @if($enquiry->status !== 'Admitted')
                                <a href="{{ route('admin.enquiries.convertToAdmission', $enquiry->id) }}" 
                                   class="btn btn-success btn-block shadow-sm font-weight-bold py-2" 
                                   title="Convert to Student">
                                    <i class="fas fa-user-check"></i>
                                </a>
                            @else
                                <button type="button" class="btn btn-light btn-block text-success disabled font-weight-bold py-2">
                                    <i class="fas fa-check-double"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
            <div class="mb-5 py-3"></div> <!-- Extra breathing room at bottom -->
        </div>

        <div class="col-md-7">
            
            <div class="bg-light p-3 rounded mb-3 border">
                <h6 class="font-weight-bold text-gray-800 mb-2 small text-uppercase">
                    <i class="fas fa-bolt text-warning mr-2"></i>Quick Actions
                </h6>
                
                <div class="mb-3">
                    <div class="small text-muted mb-1 font-weight-bold">Call Outcomes</div>
                    <div class="d-flex flex-wrap gap-1" style="gap: 5px;">
                        <button type="button" class="btn btn-xs btn-white border shadow-sm quick-response-btn py-1 px-2 mb-1" data-text="Call not picked">
                            <i class="fas fa-phone-slash text-danger mr-1"></i> No Pick
                        </button>
                        <button type="button" class="btn btn-xs btn-white border shadow-sm quick-response-btn py-1 px-2 mb-1" data-text="Asked to call back later">
                            <i class="fas fa-clock text-warning mr-1"></i> Call Back
                        </button>
                        <button type="button" class="btn btn-xs btn-white border shadow-sm quick-response-btn py-1 px-2 mb-1" data-text="Wrong Number / Invalid">
                            <i class="fas fa-times-circle text-muted mr-1"></i> Wrong #
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted mb-1 font-weight-bold">Sentiment</div>
                    <div class="d-flex flex-wrap gap-1" style="gap: 5px;">
                        <button type="button" class="btn btn-xs btn-white border shadow-sm quick-response-btn py-1 px-2 mb-1" data-text="Student is Very Interested">
                            <i class="fas fa-heart text-danger mr-1"></i> Hot Lead
                        </button>
                        <button type="button" class="btn btn-xs btn-white border shadow-sm quick-response-btn py-1 px-2 mb-1" data-text="Standard Interest shown">
                            <i class="fas fa-thumbs-up text-success mr-1"></i> Interested
                        </button>
                        <button type="button" class="btn btn-xs btn-white border shadow-sm quick-response-btn py-1 px-2 mb-1" data-text="Not interested at this moment">
                            <i class="fas fa-thumbs-down text-secondary mr-1"></i> Not Interested
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted mb-1 font-weight-bold">Status Transitions</div>
                    <div class="d-flex flex-wrap gap-1" style="gap: 5px;">
                        <button type="button" class="btn btn-xs btn-white border shadow-sm quick-response-btn py-1 px-2 mb-1" data-text="College visit planned on ">
                            <i class="fas fa-building text-info mr-1"></i> Visit Plan
                        </button>
                        <button type="button" class="btn btn-xs btn-white border shadow-sm quick-response-btn py-1 px-2 mb-1" data-text="Looking for Next Academic Year">
                            <i class="fas fa-calendar-alt text-primary mr-1"></i> Next Year
                        </button>
                    </div>
                </div>

                <form action="{{ route('admin.enquiries.follow-ups.store', $enquiry->id) }}" method="POST" id="ajaxFollowUpForm">
                    @csrf
                    <textarea name="notes" id="followUpNotes" class="form-control form-control-sm mb-2" rows="2" placeholder="Enter call notes, discussion points..." required></textarea>
                    
                    <div class="d-flex align-items-center mb-2" style="gap: 10px;">
                        <div class="flex-grow-1">
                            <label class="form-label-small">Call Outcome / Tag</label>
                            <select name="outcome" class="form-control form-control-sm border-primary">
                                <option value="">Select Outcome...</option>
                                <option value="Positive">Positive / Interested</option>
                                <option value="Call Back">Wants Call Back</option>
                                <option value="Not Picked">Not Picked / Switch Off</option>
                                <option value="Wrong Number">Wrong / Invalid Number</option>
                                <option value="Not Interested">Not Interested / Drop</option>
                                <option value="Admitted">Ready for Admission</option>
                            </select>
                        </div>
                        <div style="width: 150px;">
                            <label class="form-label-small">Next Follow-up</label>
                            <input type="date" name="next_follow_up_date" id="modalNextDate" 
                                   class="form-control form-control-sm" 
                                   min="{{ date('Y-m-d') }}"
                                   value="{{ $enquiry->next_follow_up_date ? \Carbon\Carbon::parse($enquiry->next_follow_up_date)->format('Y-m-d') : '' }}">
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="btn-group">
                                <button type="button" class="quick-sched-btn" onclick="modalSetDays(1)">+1</button>
                                <button type="button" class="quick-sched-btn" onclick="modalSetDays(3)">+3</button>
                                <button type="button" class="quick-sched-btn" onclick="modalSetDays(7)">+7</button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>

            <h6 class="font-weight-bold text-gray-800 mb-2 pl-1">Interaction History</h6>
            <div class="timeline-container-modal">
                @if($timeline->count() > 0)
<ul class="list-unstyled" id="modalTimelineList">
                            @foreach($timeline as $item)
                            @php
                                $isNote = isset($item->notes);
                                $bgClass = $isNote ? 'bg-white border-left-primary shadow-sm' : 'bg-light border';
                                $borderClass = $isNote ? 'border-left: 3px solid #4e73df !important;' : '';
                            @endphp
                            
                            <li class="mb-2 p-2 rounded {{ $bgClass }}" style="{{ $borderClass }}">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <span class="font-weight-bold text-dark small mr-2">
                                            {{ $item->user->name ?? ($item->causer->name ?? 'System') }}
                                        </span>
                                        @if($isNote)
                                            <span class="badge badge-primary" style="font-size: 0.6rem;">Note</span>
                                            @if(!empty($item->outcome))
                                                <span class="badge badge-outline-primary ml-1" style="font-size: 0.6rem; border: 1px solid #4e73df; color: #4e73df;">
                                                    <i class="fas fa-tag mr-1"></i> {{ $item->outcome }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary" style="font-size: 0.6rem;">Log</span>
                                        @endif
                                    </div>
                                    <small class="text-muted" style="font-size: 0.7rem;">
                                        {{ $item->created_at->format('d M, h:i A') }}
                                    </small>
                                </div>
                                
                                 <div class="text-dark small">
                                            @if($isNote)
                                                {!! nl2br(e($item->notes)) !!}
                                            @else
                                                @if(isset($item->properties['attributes']))
                                                    <ul class="pl-3 mb-0">
                                                        @foreach($item->properties['attributes'] as $key => $newValue)
                                                            @php
                                                                $oldValue = $item->properties['old'][$key] ?? null;
                                                                // Skip unchanged fields
                                                                if($newValue == $oldValue) continue; 
                                                                if($key == 'updated_at') continue;

                                                                // Format Labels and Values
                                                                $label = ucwords(str_replace(['_', 'id'], [' ', ''], $key));
                                                                
                                                                if ($key == 'status') {
                                                                    // Status Styling
                                                                    $newValue = "<span class='badge badge-info'>$newValue</span>";
                                                                    $oldValue = $oldValue ? "<span class='badge badge-secondary'>$oldValue</span>" : 'None';
                                                                } elseif ($key == 'next_follow_up_date') {
                                                                    $label = "Follow-up Date";
                                                                    $oldValue = $oldValue ? \Carbon\Carbon::parse($oldValue)->format('d M Y') : 'None';
                                                                    $newValue = $newValue ? \Carbon\Carbon::parse($newValue)->format('d M Y') : 'None';
                                                                } elseif ($key == 'assigned_to_user_id') {
                                                                    $label = "Counselor";
                                                                    $oldValue = \App\Models\User::find($oldValue)->name ?? 'Unassigned';
                                                                    $newValue = \App\Models\User::find($newValue)->name ?? 'Unassigned';
                                                                }
                                                            @endphp
                                                            <li>
                                                                <strong>{{ $label }}:</strong> 
                                                                @if($oldValue) 
                                                                    <span class="text-strike text-muted mx-1">{!! $oldValue !!}</span> 
                                                                    <i class="fas fa-arrow-right mx-1 text-gray-400"></i> 
                                                                @endif
                                                                <span class="font-weight-bold">{!! $newValue !!}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                    
                                                    {{-- Fallback if empty attributes (e.g. created log) --}}
                                                    @if(empty($item->properties['attributes']))
                                                        {{ $item->description }}
                                                    @endif
                                                @else
                                                    {{ $item->description }}
                                                @endif
                                            @endif
                                        </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fa-2x mb-2 text-gray-300"></i>
                        <p class="small mb-0">No history recorded yet.</p>
                    </div>
                @endif
            <div class="mb-5 py-5"></div> <!-- Extra breathing room for history list -->
        </div>
    </div>
</div>

<script>
    // [NEW] AJAX Follow-up Submission
    $('#ajaxFollowUpForm').on('submit', function(e) {
        e.preventDefault(); // Prevent page reload

        let form = $(this);
        let url = form.attr('action');
        let submitBtn = form.find('button[type="submit"]');
        let originalBtnContent = submitBtn.html();

        // Loading State
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: url,
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    // 1. Build new timeline item HTML
                    let newItem = `
                        <li class="mb-2 p-2 rounded bg-white border-left-primary shadow-sm" style="border-left: 3px solid #4e73df !important; display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center">
                                    <span class="font-weight-bold text-dark small mr-2">${response.data.user_name}</span>
                                    <span class="badge badge-primary" style="font-size: 0.6rem;">Note</span>
                                    ${response.data.outcome ? `
                                        <span class="badge badge-outline-primary ml-1" style="font-size: 0.6rem; border: 1px solid #4e73df; color: #4e73df;">
                                            <i class="fas fa-tag mr-1"></i> ${response.data.outcome}
                                        </span>` : ''}
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">${response.data.created_at}</small>
                            </div>
                            <div class="text-dark small">
                                ${response.data.notes}
                            </div>
                        </li>`;

                    // 2. Prepend to list and animate
                    let list = $('#modalTimelineList');
                    if (list.length === 0) {
                        // If list doesn't exist (empty state), create it
                        $('.timeline-container-modal').html('<ul class="list-unstyled" id="modalTimelineList"></ul>');
                        list = $('#modalTimelineList');
                    }
                    
                    $(newItem).prependTo(list).slideDown();

                    // 3. Reset Form
                    form.find('textarea').val('');
                    
                    // 4. Update the dashboard background if it exists
                    if (typeof fetchEnquiries === 'function') {
                        fetchEnquiries();
                    }
                }
            },
            error: function(xhr) {
                alert('Error saving follow-up. Please try again.');
            },
            complete: function() {
                // Restore button
                submitBtn.prop('disabled', false).html(originalBtnContent);
            }
        });
    });

    // --- Existing Logic for Modal Interactions (Keep your existing scripts below) ---

    // 1. Quick Schedule Logic
    function modalSetDays(days) {
        const date = new Date();
        date.setDate(date.getDate() + days);
        
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        
        document.getElementById('modalNextDate').value = `${yyyy}-${mm}-${dd}`;
    }
    
    // --- Quick Response Logic (Smart Replace) ---
    $(document).on('click', '.quick-response-btn', function() {
        const newText = $(this).data('text');
        const textarea = $('#followUpNotes');
        let currentVal = textarea.val();

        // 1. Check if any OTHER chip text is already in the box
        let replaced = false;
        $('.quick-response-btn').each(function() {
            const chipText = $(this).data('text');
            // If we find a chip text that matches (and it's not the one we just clicked)
            if (currentVal.includes(chipText)) {
                // Swap the old text with the new text
                currentVal = currentVal.replace(chipText, newText);
                replaced = true;
                return false; // Stop the loop since we found and replaced one
            }
        });

        // 2. If nothing was replaced (box was empty or had only custom text), append the new one
        if (!replaced) {
            if (currentVal.length > 0) {
                // Avoid double separators if the user already typed " - "
                if (currentVal.trim().endsWith('-')) {
                    currentVal = currentVal.trim() + ' ' + newText;
                } else {
                    currentVal = currentVal.trim() + ' - ' + newText;
                }
            } else {
                currentVal = newText;
            }
        }

        // 3. Update and Focus
        textarea.val(currentVal);
        textarea.focus();
    });

    // 2. Source / Referral Toggle Logic
    (function() {
        const modalSourceSelect = document.getElementById('modalSourceSelect');
        const modalReferralWrapper = document.getElementById('modalReferralWrapper');
        const modalReferralInput = document.getElementById('modalReferralInput');
        const modalReferralLabel = document.getElementById('modalReferralLabel');
        
        const sourcesRequiringName = ['Agent', 'Referrals', 'pro', 'list', 'Student Refer', 'Other'];

        function modalToggleReferralField() {
            if (sourcesRequiringName.includes(modalSourceSelect.value)) {
                modalReferralWrapper.style.display = 'block';
                
                let label = 'Referral Name';
                switch (modalSourceSelect.value) {
                    case 'Agent': label = 'Agent Name'; break;
                    case 'Referrals': label = 'Referral Name'; break;
                    case 'Student Refer': label = 'Student Name'; break;
                    case 'Other': label = 'Specify Details'; break;
                }
                modalReferralLabel.textContent = label;
            } else {
                modalReferralWrapper.style.display = 'none';
            }
        }

        if(modalSourceSelect) {
            modalSourceSelect.addEventListener('change', modalToggleReferralField);
            modalToggleReferralField(); 
        }
    })();

    // [NEW] AJAX Profile Update Submission
    $('#modalEditForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let submitBtn = form.find('button[type="submit"]');
        let originalContent = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(res) {
                // Success feedback
                submitBtn.removeClass('btn-primary').addClass('btn-success').html('<i class="fas fa-check mr-1"></i> Saved');
                setTimeout(() => {
                    submitBtn.removeClass('btn-success').addClass('btn-primary').html(originalContent);
                }, 2000);
                
                // Refresh the table behind the modal if fetchEnquiries exists
                if (typeof fetchEnquiries === 'function') {
                    fetchEnquiries();
                }
            },
            error: function() {
                alert('Failed to update profile.');
                submitBtn.prop('disabled', false).html(originalContent);
            }
        });
    });

    // [NEW] Toggle Test Fields logic
    $('#modalTestAttended').on('change', function() {
        if ($(this).val() == '1') {
            $('#testConditionalFields').slideDown();
        } else {
            $('#testConditionalFields').slideUp();
        }
    });
</script>
