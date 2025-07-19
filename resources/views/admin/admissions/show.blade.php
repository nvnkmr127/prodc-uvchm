@extends('layouts.theme')
@section('title', 'Admission Details: ' . $admission->full_name)

@push('styles')
<style>
    /* Styles for the follow-up timeline */
    .timeline {
        list-style: none;
        padding: 0;
        position: relative;
    }
    .timeline:before {
        top: 0;
        bottom: 0;
        position: absolute;
        content: " ";
        width: 3px;
        background-color: #e9ecef;
        left: 20px;
        margin-left: -1.5px;
    }
    .timeline > li {
        margin-bottom: 20px;
        position: relative;
    }
    .timeline > li:before, .timeline > li:after {
        content: " ";
        display: table;
    }
    .timeline > li:after {
        clear: both;
    }
    .timeline > li > .timeline-panel {
        width: calc( 100% - 65px );
        float: right;
        border: 1px solid #d4d4d4;
        border-radius: 2px;
        padding: 20px;
        position: relative;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
    }
    .timeline > li > .timeline-badge {
        color: #fff;
        width: 40px;
        height: 40px;
        line-height: 40px;
        font-size: 1.4em;
        text-align: center;
        position: absolute;
        top: 16px;
        left: 0px;
        z-index: 100;
        border-radius: 50%;
    }
</style>
@endpush

@section('content')

{{-- 1. Page Header --}}
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Admission Details</h1>
    <a href="{{ route('admin.admissions.index') }}" class="btn btn-sm btn-light shadow-sm"><i class="fas fa-arrow-left fa-sm text-gray-600"></i> Back to List</a>
</div>

{{-- Session Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
@endif

<div class="row">
    <div class="col-lg-8">
        {{-- Applicant Details Card --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Applicant Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><strong>Full Name</strong><p>{{ $admission->full_name }}</p></div>
                    <div class="col-md-6 mb-3"><strong>Gender</strong><p>{{ $admission->gender ?? 'Not Provided' }}</p></div>
                    <div class="col-md-6 mb-3"><strong>Email Address</strong><p>{{ $admission->email }}</p></div>
                    <div class="col-md-6 mb-3"><strong>Mobile Number</strong><p>{{ $admission->phone_number }}</p></div>
                    <div class="col-md-6 mb-3"><strong>Date of Birth</strong><p>{{ \Carbon\Carbon::parse($admission->date_of_birth)->format('d M, Y') }}</p></div>
                    <div class="col-md-12 mb-3"><strong>Address</strong><p class="text-muted">{{ $admission->address }}</p></div>
                    <div class="col-md-12 mb-0"><strong>Education Qualification</strong><p class="text-muted">{{ $admission->education_qualification ?? 'N/A' }}</p></div>
                </div>
            </div>
        </div>

        {{-- Follow-up Section --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Follow-up History & Notes</h6>
            </div>
            <div class="card-body">
                {{-- Form to Add New Note --}}
                <form action="{{ route('admin.admissions.follow-ups.store', $admission) }}" method="POST" class="mb-4">
                    @csrf
                    <div class="form-group">
                        <textarea name="note" class="form-control" rows="3" placeholder="Add a new note, e.g., 'Called applicant, they are interested and will apply by Friday.'..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
                </form>
                <hr>
                {{-- Timeline of Existing Notes --}}
                @if($admission->followUps->isEmpty())
                    <p class="text-center text-muted">No follow-up notes have been added yet.</p>
                @else
                    <ul class="timeline">
                        @foreach($admission->followUps as $followUp)
                        <li>
                            <div class="timeline-badge bg-primary"><i class="fas fa-comment"></i></div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <p><small class="text-muted"><i class="fas fa-user mr-1"></i> {{ $followUp->user->name ?? 'System' }} | <i class="fas fa-clock mr-1"></i> {{ $followUp->created_at->format('d M, Y \a\t h:i A') }}</small></p>
                                </div>
                                <div class="timeline-body">
                                    <p>{{ $followUp->note }}</p>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>
    <div class="col-lg-4">
        {{-- Application Details Card --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Application Status</h6>
                @php
                    $badgeClass = 'warning';
                    if ($admission->status == 'approved') $badgeClass = 'success';
                    if ($admission->status == 'rejected') $badgeClass = 'danger';
                @endphp
                <span class="badge badge-{{ $badgeClass }} p-2">{{ ucfirst($admission->status) }}</span>
            </div>
            <div class="card-body">
                <div class="mb-3"><strong>Course Applied For</strong><p>{{ $admission->course->name }}</p></div>
                <div class="mb-3"><strong>Inquiry Source</strong><p>{{ $admission->source ?? 'N/A' }}</p></div>
                @if($admission->referral_name)
                    <div class="mb-3"><strong>Referral Name</strong><p>{{ $admission->referral_name }}</p></div>
                @endif
                <div class="mb-0"><strong>Application Date</strong><p>{{ $admission->created_at->format('d M, Y h:i A') }}</p></div>
            </div>
            @if($admission->status == 'pending')
                <div class="card-footer text-center">
                    <form action="{{ route('admin.admissions.approve', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this admission? This will create a student profile and fee plan.');">
                        @csrf
                        <button type="submit" class="btn btn-success"><i class="fas fa-check mr-2"></i>Approve</button>
                    </form>
                    <form action="{{ route('admin.admissions.reject', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this admission?');">
                        @csrf
                        <button type="submit" class="btn btn-danger"><i class="fas fa-times mr-2"></i>Reject</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
