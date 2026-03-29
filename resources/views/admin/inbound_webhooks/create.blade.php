@extends('layouts.theme')
@section('title', 'Create Inbound Webhook')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inbound-webhooks.index') }}">Inbound Webhooks</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create New Source</li>
                </ol>
            </nav>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Create New Inbound Webhook</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.inbound-webhooks.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="name">Friendly Name</label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="e.g., Facebook MBA Campaign" required>
                        </div>

                        <div class="form-group">
                            <label for="source_name">Lead Source Label (Internal)</label>
                            <input type="text" class="form-control" name="source_name" id="source_name" value="Social Media" required>
                            <small class="text-muted">Enquiries created via this webhook will have this "Source" in the CRM.</small>
                        </div>

                        <div class="form-group">
                            <label for="auto_followup_days">Auto-Schedule First Follow-up (Days)</label>
                            <input type="number" class="form-control" name="auto_followup_days" id="auto_followup_days" value="0" min="0" required>
                            <small class="text-muted">0 = schedule for today, 1 = tomorrow, etc.</small>
                        </div>

                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body py-3">
                                <div class="custom-control custom-switch mb-2">
                                    <input type="checkbox" class="custom-control-input" id="auto_assign" name="auto_assign" value="1" checked onchange="toggleAssignment()">
                                    <label class="custom-control-label font-weight-bold" for="auto_assign">Auto-Assign Leads</label>
                                </div>
                                <small class="text-muted d-block mb-3">If enabled, leads will be distributed among active counselors automatically.</small>

                                <div id="manual_assignment_box" style="display: none;">
                                    <label for="assigned_to_user_id" class="small font-weight-bold">Assign to Specific Counselor</label>
                                    <select class="form-control" name="assigned_to_user_id" id="assigned_to_user_id">
                                        <option value="">-- No Default (Unassigned) --</option>
                                        @foreach($counselors as $counselor)
                                            <option value="{{ $counselor->id }}">{{ $counselor->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Fixed assignment for every lead from this source.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="slug">Custom URL Slug (Optional)</label>
                            <input type="text" class="form-control" name="slug" id="slug" placeholder="fb-campaign-2024">
                            <small class="text-muted">Leave blank to generate automatically.</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Note / Description (Optional)</label>
                            <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Create Webhook & Proceed to Mapping
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    function toggleAssignment() {
        const auto = document.getElementById('auto_assign').checked;
        document.getElementById('manual_assignment_box').style.display = auto ? 'none' : 'block';
    }
    // Initialize
    window.onload = toggleAssignment;
</script>
@endpush
@endsection
