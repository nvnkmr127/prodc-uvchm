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
                <div class="card-header py-3 bg-secondary text-white d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Last Received Data</h6>
                    @if($inboundWebhook->last_payload)
                        <span class="badge badge-light">Interactive Picker Active</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($inboundWebhook->last_payload)
                        <div class="p-3 bg-light border-bottom">
                            <p class="small text-muted mb-2"><strong>Tip:</strong> Click any <span class="badge badge-primary shadow-sm" style="background-color: #4e73df; color: white;">blue</span> path below to copy it to your active mapping field.</p>
                            <div id="jsonPathPicker" style="max-height: 400px; overflow-y: auto;">

                                @php
                                    function flattenJson($data, &$paths, $prefix = '') {
                                        foreach ($data as $key => $value) {
                                            $path = $prefix ? "$prefix.$key" : $key;
                                            
                                            // Handle special array structure for "fields"
                                            if ($key === 'fields' && is_array($value)) {
                                                foreach ($value as $index => $item) {
                                                    $itemPath = "$path.$index";
                                                    if (is_array($item) && isset($item['type'])) {
                                                        $type = $item['type'];
                                                        $advancedPath = "fields[type=$type]";
                                                        flattenJson($item, $paths, $advancedPath);
                                                    } elseif (is_array($item) && isset($item['question'])) {
                                                        $q = Str::limit($item['question'], 20);
                                                        $advancedPath = "fields[question=$q]";
                                                        flattenJson($item, $paths, $advancedPath);
                                                    }
                                                    flattenJson($item, $paths, $itemPath);
                                                }
                                                continue;
                                            }

                                            if (is_array($value)) {
                                                flattenJson($value, $paths, $path);
                                            } else {
                                                $paths[$path] = $value;
                                            }
                                        }
                                    }
                                    $allPaths = [];
                                    flattenJson($inboundWebhook->last_payload, $allPaths);
                                @endphp

                                <div class="list-group list-group-flush shadow-sm">
                                    @foreach($allPaths as $path => $value)
                                        <button type="button" 
                                                class="list-group-item list-group-item-action p-2 small d-flex justify-content-between align-items-center path-selector-btn"
                                                onclick="copyPathToActive('{{ $path }}', '{{ Str::limit(is_string($value) ? $value : json_encode($value), 30) }}')">
                                            <code class="text-primary font-weight-bold">{{ $path }}</code>
                                            <span class="text-muted text-truncate ml-2" style="max-width: 150px;">{{ is_string($value) ? $value : json_encode($value) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="p-3 bg-light border-top">
                            <p class="small text-muted mb-0">Received on: {{ $inboundWebhook->last_called_at->format('M d, Y H:i:s') }}</p>
                            <button type="button" class="btn btn-sm btn-link p-0 mt-1" data-toggle="modal" data-target="#rawJsonModal">View Raw JSON</button>
                        </div>

                        <!-- Modal for raw JSON -->
                        <div class="modal fade" id="rawJsonModal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Raw Received Payload</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div class="bg-dark p-3">
                                            <pre class="text-success mb-0" style="font-size: 0.85rem;">{{ json_encode($inboundWebhook->last_payload, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

            <!-- Global Webhook Settings -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-cog mr-1"></i> Global Webhook Settings</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.inbound-webhooks.update', $inboundWebhook) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold">Webhook Friendly Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $inboundWebhook->name }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold">CRM Source Label</label>
                            <input type="text" name="source_name" class="form-control" value="{{ $inboundWebhook->source_name ?? 'Inbound Webhook' }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold">Auto-Followup Offset (Days)</label>
                            <input type="number" name="auto_followup_days" class="form-control" value="{{ $inboundWebhook->auto_followup_days }}" min="0" required>
                        </div>
                        <div class="form-group mb-3">
                            <div class="custom-control custom-switch custom-control-lg">
                                <input type="checkbox" class="custom-control-input" id="autoAssignSwitch" name="auto_assign" value="1" {{ $inboundWebhook->auto_assign ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="autoAssignSwitch">Auto-Assignment (Round Robin)</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm btn-block shadow-sm">
                            <i class="fas fa-check-circle mr-1"></i> Update Global Settings
                        </button>
                    </form>
                </div>
            </div>

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

    <!-- Webhook History Logs (New Row) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history mr-1"></i> Recent Activity Logs (Last 50)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Method/IP</th>
                                    <th>Payload Preview</th>
                                    <th>Result</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inboundWebhook->logs as $log)
                                    <tr>
                                        <td>
                                            <span class="small text-muted">{{ $log->created_at->format('M d, H:i:s') }}</span>
                                            <br>
                                            <small class="text-gray-500">{{ $log->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @if($log->status_code >= 200 && $log->status_code < 300)
                                                <span class="badge badge-success">{{ $log->status_code }} OK</span>
                                            @elseif($log->status_code >= 400 && $log->status_code < 500)
                                                <span class="badge badge-warning">{{ $log->status_code }} Error</span>
                                            @else
                                                <span class="badge badge-danger">{{ $log->status_code }} Fail</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code>{{ $log->method }}</code><br>
                                            <small class="text-muted">{{ $log->ip_address }}</small>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 250px;">
                                                <small class="text-muted">
                                                    @php $keys = array_keys($log->payload ?? []); @endphp
                                                    {{ implode(', ', array_slice($keys, 0, 5)) }}{{ count($keys) > 5 ? '...' : '' }}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($log->enquiry_id)
                                                <a href="{{ route('admin.enquiries.show', $log->enquiry_id) }}" class="btn btn-xs btn-outline-success">
                                                    <i class="fas fa-user-plus mr-1"></i> Lead Created #{{ $log->enquiry_id }}
                                                </a>
                                            @elseif($log->error_message)
                                                <span class="text-danger small"><i class="fas fa-exclamation-triangle mr-1"></i> {{ Str::limit($log->error_message, 50) }}</span>
                                            @else
                                                <small class="text-muted">Received</small>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#logModal{{ $log->id }}">
                                                <i class="fas fa-eye shadow-sm"></i>
                                            </button>

                                            <!-- Modal for log details -->
                                            <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-lg text-left" role="document">
                                                    <div class="modal-content text-left">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Webhook Call Details - {{ $log->created_at }}</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body p-0">
                                                            <div class="p-3 bg-light border-bottom">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Status Code:</strong> {{ $log->status_code }}</p>
                                                                        <p class="mb-1"><strong>IP Address:</strong> {{ $log->ip_address }}</p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Method:</strong> {{ $log->method }}</p>
                                                                        <p class="mb-1"><strong>Timestamp:</strong> {{ $log->created_at->toDateTimeString() }}</p>
                                                                    </div>
                                                                </div>
                                                                @if($log->error_message)
                                                                    <div class="alert alert-danger mt-2 mb-0 py-2 px-3 small">
                                                                        <strong>Error Detail:</strong> {{ $log->error_message }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="bg-dark p-3">
                                                                <h6 class="text-white small font-weight-bold mb-2 uppercase">Full Payload:</h6>
                                                                <pre class="text-success mb-0" style="font-size: 0.85rem; max-height: 400px; overflow-y: auto;">{{ json_encode($log->payload, JSON_PRETTY_PRINT) }}</pre>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            @if($log->enquiry_id)
                                                                <a href="{{ route('admin.enquiries.show', $log->enquiry_id) }}" class="btn btn-primary">View Created Lead</a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">No historical calls found.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let activeInput = null;

    $(document).ready(function() {
        // Track which input was last focused
        $('input[name^="mapping"]').on('focus', function() {
            activeInput = $(this);
            // Highlight the active row
            $('tr').removeClass('table-primary');
            $(this).closest('tr').addClass('table-primary');
        });

        // Initialize tooltips if any
        $('[data-toggle="tooltip"]').tooltip();
    });

    function copyPathToActive(path, value) {
        if (activeInput) {
            activeInput.val(path).fadeOut(100).fadeIn(100);
            
            // Show a tiny success toast or indicator if possible, 
            // but just the fade effect is good for now
            console.log('Mapped ' + path + ' to ' + activeInput.attr('name'));
        } else {
            alert('Please click on a mapping input field first to select where you want to put this path.');
        }
    }
</script>
<style>
    .path-selector-btn {
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }
    .path-selector-btn:hover {
        border-left: 3px solid #4e73df;
        background-color: #f8f9fc;
    }
    .table-primary {
        background-color: rgba(78, 115, 223, 0.05) !important;
    }
    #jsonPathPicker code {
        cursor: pointer;
    }
</style>
@endpush

