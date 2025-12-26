@extends('layouts.theme')
@section('title', 'Manage Lead: ' . $enquiry->student_name)

@push('styles')
<style>
    /* Modern Gradient Header */
    .crm-header {
        background: linear-gradient(120deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 2rem;
        border-radius: 1rem;
        margin-bottom: -2rem; /* Overlap effect */
        padding-bottom: 4rem;
        position: relative;
        z-index: 0;
        box-shadow: 0 4px 20px rgba(78, 115, 223, 0.2);
    }

    /* Cards floating over header */
    .overlap-container {
        margin-top: -2rem;
        position: relative;
        z-index: 1;
    }

    .crm-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
        background: white;
        overflow: hidden;
        height: 100%;
        transition: transform 0.2s;
    }

    .crm-card-header {
        background: white;
        border-bottom: 1px solid #f0f2f5;
        padding: 1.5rem;
        font-weight: 700;
        color: #4e73df;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Profile Styles */
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: #eaecf4;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 800;
        color: #4e73df;
        margin: 0 auto 1rem;
        border: 4px solid white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    /* Form Styling */
    .form-label-group {
        margin-bottom: 1.25rem;
    }
    
    .form-label-group label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #858796;
        margin-bottom: 0.25rem;
    }

    .form-control {
        border-radius: 0.35rem;
        border: 1px solid #d1d3e2;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.15);
        border-color: #bac8f3;
    }

    /* Timeline Feed */
    .timeline-feed {
        position: relative;
        padding: 1rem 0;
        list-style: none;
    }

    .timeline-feed::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 24px;
        width: 2px;
        background: #e3e6f0;
    }

    .timeline-item {
        position: relative;
        padding-left: 60px;
        margin-bottom: 1.5rem;
    }

    .timeline-marker {
        position: absolute;
        left: 12px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 2px solid white;
        text-align: center;
        line-height: 22px;
        font-size: 0.7rem;
        color: white;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .timeline-content {
        background: #f8f9fc;
        border-radius: 0.5rem;
        padding: 1rem;
        font-size: 0.9rem;
        position: relative;
    }

    .text-strike {
        text-decoration: line-through;
        opacity: 0.7;
    }
    
    .quick-sched-btn {
        border: 1px solid #e3e6f0;
        background: white;
        color: #5a5c69;
        font-size: 0.8rem;
        padding: 0.35rem 0.75rem;
        border-radius: 0.35rem;
        transition: all 0.2s;
    }
    
    .quick-sched-btn:hover, .quick-sched-btn.active {
        background: #4e73df;
        color: white;
        border-color: #4e73df;
    }
</style>
@endpush

@section('content')
<div class="container-fluid mb-5">
    <div class="crm-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="h3 font-weight-bold mb-1">Manage Enquiry</h1>
                <p class="mb-0 text-white-50">
                    Lead ID: #{{ $enquiry->id }} | Created {{ $enquiry->created_at->diffForHumans() }}
                </p>
            </div>
            <a href="{{ route('admin.enquiries.index') }}" class="btn btn-light btn-sm shadow-sm">
                <i class="fas fa-times mr-1"></i> Close
            </a>
        </div>
    </div>

    <div class="row overlap-container">
        
        <div class="col-lg-4 mb-4">
            <div class="crm-card h-100">
                <div class="card-body pt-5 pb-4">
                    <div class="text-center">
                        <div class="profile-avatar">
                            {{ strtoupper(substr($enquiry->student_name, 0, 1)) }}
                        </div>
                        <h4 class="font-weight-bold text-gray-800 mb-1">{{ $enquiry->student_name }}</h4>
                        <p class="text-muted small mb-3">
                            {{ $enquiry->course->name ?? 'General Enquiry' }}
                        </p>
                        
                        <div class="d-flex justify-content-center gap-2 mb-4">
                            <a href="tel:{{ $enquiry->phone_number }}" class="btn btn-outline-success btn-sm mx-1">
                                <i class="fas fa-phone mr-1"></i> Call
                            </a>
                            <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $enquiry->phone_number) }}" target="_blank" class="btn btn-outline-success btn-sm mx-1">
                                <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                    
                    <hr class="my-4">

                    <form action="{{ route('admin.enquiries.update', $enquiry->id) }}" method="POST" id="profileForm">
                        @csrf
                        @method('PUT')

                        <div class="form-label-group">
                            <label>Student Name</label>
                            <input type="text" name="student_name" class="form-control" value="{{ $enquiry->student_name }}" required>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-label-group">
                                    <label>Phone</label>
                                    <input type="tel" name="phone_number" class="form-control" value="{{ $enquiry->phone_number }}" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-label-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select...</option>
                                        @foreach(['Male', 'Female', 'Other'] as $g)
                                            <option value="{{ $g }}" {{ $enquiry->gender == $g ? 'selected' : '' }}>{{ $g }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-label-group">
                            <label>Current Status</label>
                            <select name="status" class="form-control font-weight-bold text-{{ $enquiry->status == 'New' ? 'primary' : 'dark' }}">
                                @foreach(['New', 'Contacted', 'Interested', 'Follow-up', 'Not Interested', 'Admitted'] as $status)
                                    <option value="{{ $status }}" {{ $enquiry->status == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-label-group">
                            <label>Source</label>
                            <select name="source" class="form-control" id="sourceSelect">
                                <option value="">-- Select Source --</option>
                                @php
                                    // Standard sources
                                    $sources = ['Website', 'Social Media', 'Agent', 'Referrals', 'pro', 'list', 'Student Refer', 'Walk-in', 'Other'];
                                    // Add current source if not in list
                                    if($enquiry->source && !in_array($enquiry->source, $sources)) {
                                        array_push($sources, $enquiry->source);
                                    }
                                @endphp
                                @foreach($sources as $src)
                                    <option value="{{ $src }}" {{ $enquiry->source == $src ? 'selected' : '' }}>{{ $src }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-label-group" id="referralWrapper" style="{{ $enquiry->referral_name ? 'display:block;' : 'display:none;' }}">
                            <label id="referralLabel">Referral Name</label>
                            <input type="text" name="referral_name" id="referralInput" class="form-control" value="{{ $enquiry->referral_name }}">
                        </div>

                        <div class="form-label-group">
                            <label>Assigned Counselor</label>
                            <select name="assigned_to_user_id" class="form-control">
                                <option value="">Unassigned</option>
                                @foreach($counselors as $counselor)
                                    <option value="{{ $counselor->id }}" {{ $enquiry->assigned_to_user_id == $counselor->id ? 'selected' : '' }}>
                                        {{ $counselor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-label-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ $enquiry->address }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block mt-4 shadow-sm">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </form>
                    
                    @if($enquiry->status !== 'Admitted')
                        <div class="mt-3">
                            <a href="{{ route('admin.enquiries.convertToAdmission', $enquiry->id) }}" 
                               onclick="return confirm('Convert this enquiry to a student?')"
                               class="btn btn-light btn-block text-success border-success">
                                <i class="fas fa-graduation-cap mr-2"></i> Convert to Admission
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            
            <div class="crm-card mb-4">
                <div class="card-body bg-light border-bottom">
                    <form action="{{ route('admin.enquiries.follow-ups.store', $enquiry->id) }}" method="POST">
                        @csrf
                        <div class="d-flex">
                            <div class="mr-3">
                                <div class="rounded-circle bg-white p-2 text-primary shadow-sm">
                                    <i class="fas fa-pen fa-lg"></i>
                                </div>
                            </div>
                            <div class="w-100">
                                <h6 class="font-weight-bold text-gray-800 mb-2">Log Interaction / Next Step</h6>
                                <textarea name="notes" class="form-control border-0 shadow-sm mb-3" rows="2" placeholder="What did you discuss? e.g. 'Called parent, asked to call back on Monday...'" required></textarea>
                                
                                <div class="d-flex flex-wrap align-items-center justify-content-between">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <span class="mr-2 small font-weight-bold text-gray-600">Next Follow-up:</span>
                                        <input type="date" name="next_follow_up_date" id="nextDate" 
                                               class="form-control form-control-sm mr-2" 
                                               style="width: 140px;" 
                                               min="{{ date('Y-m-d') }}"
                                               value="{{ $enquiry->next_follow_up_date ? \Carbon\Carbon::parse($enquiry->next_follow_up_date)->format('Y-m-d') : '' }}">
                                        
                                        <div class="btn-group">
                                            <button type="button" class="quick-sched-btn" onclick="setDays(1)">+1 Day</button>
                                            <button type="button" class="quick-sched-btn" onclick="setDays(3)">+3 Days</button>
                                            <button type="button" class="quick-sched-btn" onclick="setDays(7)">+1 Week</button>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                        <i class="fas fa-paper-plane mr-1"></i> Post Note
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="crm-card">
                <div class="crm-card-header">
                    <span><i class="fas fa-history mr-2"></i> Activity History</span>
                    <span class="badge badge-light text-gray-600">{{ $timeline->count() }} Activities</span>
                </div>
                <div class="card-body">
                    @if($timeline->count() > 0)
                        <ul class="timeline-feed">
                            @foreach($timeline as $item)
                                @php
                                    $isNote = isset($item->notes); // Check if it's a manual note
                                    
                                    // Styles
                                    $icon = $isNote ? 'fa-comment-alt' : 'fa-circle';
                                    $bg = $isNote ? 'bg-primary' : 'bg-secondary';
                                    $bgColor = $isNote ? '#eef2ff' : '#fff';
                                    $borderColor = $isNote ? 'border-left: 3px solid #4e73df;' : 'border: 1px solid #f0f0f0;';
                                @endphp

                                <li class="timeline-item">
                                    <div class="timeline-marker {{ $bg }}">
                                        <i class="fas {{ $icon }}"></i>
                                    </div>
                                    <div class="timeline-content" style="background: {{ $bgColor }}; {{ $borderColor }}">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="font-weight-bold text-gray-800" style="font-size: 0.9rem;">
                                                {{ $item->user->name ?? ($item->causer->name ?? 'System') }}
                                            </span>
                                            <small class="text-muted">
                                                {{ $item->created_at->format('d M Y, h:i A') }}
                                                ({{ $item->created_at->diffForHumans() }})
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
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-stream fa-3x mb-3 opacity-50"></i>
                            <p>No history found. Start by adding a note!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // 1. Quick Schedule Logic
    function setDays(days) {
        const date = new Date();
        date.setDate(date.getDate() + days);
        
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        
        document.getElementById('nextDate').value = `${yyyy}-${mm}-${dd}`;
        
        document.querySelectorAll('.quick-sched-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
    }

    // 2. Visual Feedback on Change
    document.getElementById('profileForm').addEventListener('change', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-warning');
        btn.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Save Changes';
    });

    // 3. Source / Referral Logic (Matches Create Page)
    const sourceSelect = document.getElementById('sourceSelect');
    const referralWrapper = document.getElementById('referralWrapper');
    const referralInput = document.getElementById('referralInput');
    const referralLabel = document.getElementById('referralLabel');
    
    const sourcesRequiringName = ['Agent', 'Referrals', 'pro', 'list', 'Student Refer', 'Other'];

    function toggleReferralField() {
        if (sourcesRequiringName.includes(sourceSelect.value)) {
            referralWrapper.style.display = 'block';
            referralInput.required = true;
            
            let label = 'Referral Name';
            let placeholder = 'Enter name';

            switch (sourceSelect.value) {
                case 'Agent': label = 'Agent Name'; break;
                case 'Referrals': label = 'Referral Person Name'; break;
                case 'pro': label = 'Pro Person Name'; break;
                case 'list': label = 'List Person Name'; break;
                case 'Student Refer': label = 'Student Name'; break;
                case 'Other': label = 'Specify Other'; break;
            }
            referralLabel.textContent = label;
            referralInput.placeholder = placeholder;
        } else {
            referralWrapper.style.display = 'none';
            referralInput.required = false;
            // Don't clear value immediately in edit mode to preserve data if user toggles back
        }
    }

    sourceSelect.addEventListener('change', toggleReferralField);
    // Run on load to set initial state
    toggleReferralField(); 
</script>
@endpush