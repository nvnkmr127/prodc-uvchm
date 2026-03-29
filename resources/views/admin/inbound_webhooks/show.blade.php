@extends('layouts.theme')
@section('title', 'Configure Webhook Mapping')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Mapping Configuration Column (Left) -->
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inbound-webhooks.index') }}">Inbound Webhooks</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mapping Configuration</li>
                </ol>
            </nav>
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary">Webhook: {{ $inboundWebhook->name }}</h6>
                        <small class="text-muted"><code>{{ $inboundWebhook->url }}</code></small>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.inbound-webhooks.update-mapping', $inboundWebhook) }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Internal Field (Enquiry)</th>
                                        <th>Webhook JSON Key / Path</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($enquiryFields as $key => $label)
                                        <tr>
                                            <td><strong>{{ $label }}</strong></td>
                                            <td>
                                                <input type="text" 
                                                       name="mapping[{{ $key }}]" 
                                                       class="form-control" 
                                                       value="{{ $inboundWebhook->mapping_rules[$key] ?? '' }}" 
                                                       placeholder="e.g. {{ $key == 'student_name' ? 'full_name' : ($key == 'phone_number' ? 'phone' : '') }}">
                                                @if($inboundWebhook->last_payload)
                                                    <small class="text-info mt-1 d-block">Tip: Use dots for nested keys (e.g. <code>user.data.{{ $key }}</code>)</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info border-left-info shadow-sm pt-4">
                            <h5 class="font-weight-bold"><i class="fas fa-lock mr-2"></i> Security Token Required</h5>
                            <p>To secure this endpoint, include this token in your request headers or body:</p>
                            <p>Header: <code>X-Webhook-Token: {{ $inboundWebhook->secret_token }}</code></p>
                            <p>Query Parameter: <code>?token={{ $inboundWebhook->secret_token }}</code></p>
                        </div>

                        <!-- Integration Guide -->
                        <div class="card bg-light border-0 mt-4">
                            <div class="card-body">
                                <h6 class="font-weight-bold text-dark"><i class="fas fa-info-circle mr-1"></i> How to connect with external platforms:</h6>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="small">
                                            <strong>1. Copy URL</strong><br>
                                            Copy the Webhook URL above and paste it into Zapier, Pabbly, or your Facebook App.
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small">
                                            <strong>2. Add Security</strong><br>
                                            Add a header named <code>X-Webhook-Token</code> with your secret token value.
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small">
                                            <strong>3. Send Test</strong><br>
                                            Send a test payload. Once received, it will appear in the "Last Received Data" section on the right.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="fas fa-save mr-1"></i> Save Mapping Rules
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Last Received Payload Column (Right) -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-secondary text-white">
                    <h6 class="m-0 font-weight-bold">Last Received Data</h6>
                </div>
                <div class="card-body p-0">
                    @if($inboundWebhook->last_payload)
                        <div class="p-3 bg-dark text-white rounded-0" style="max-height: 500px; overflow-y: auto;">
                            <pre class="text-success mb-0" style="font-size: 0.85rem;">{{ json_encode($inboundWebhook->last_payload, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                        <div class="p-3 bg-light border-top">
                            <p class="small text-muted mb-0">Received on: {{ $inboundWebhook->last_called_at->format('M d, Y H:i:s') }}</p>
                        </div>
                    @else
                        <div class="text-center py-5 px-3">
                            <i class="fas fa-satellite-dish fa-3x text-gray-300 mb-3"></i>
                            <h6 class="text-muted font-weight-bold">No Data Received Yet</h6>
                            <p class="small text-gray-500">Send a test JSON from Facebook/Zapier to this URL to see exactly what fields are available for mapping.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Developer Integration Help -->
            <div class="card shadow mb-4 border-left-success">
                <div class="card-body">
                    <h6 class="font-weight-bold text-success">Quick Test (cURL)</h6>
                    <small class="text-muted">Use this to simulate an incoming lead:</small>
                    <div class="bg-light p-2 mt-2 rounded">
                        <code style="word-break: break-all;">curl -X POST {{ $inboundWebhook->url }} \
-H "X-Webhook-Token: {{ $inboundWebhook->secret_token }}" \
-d '{ "full_name": "Test Student", "mobile": "9876543210" }'</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
