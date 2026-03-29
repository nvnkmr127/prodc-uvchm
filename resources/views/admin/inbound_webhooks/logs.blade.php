@extends('layouts.theme')
@section('title', 'Inbound Webhook Logs')

@push('styles')
    <style>
        .card-premium {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .badge-soft-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-soft-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-soft-info {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .code-display {
            background: #1f2937;
            color: #e5e7eb;
            padding: 1.25rem;
            border-radius: 8px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.85rem;
            max-height: 500px;
            overflow-y: auto;
            border-left: 4px solid #3b82f6;
        }

        .log-row:hover {
            background-color: #f9fafb;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('admin.inbound-webhooks.index') }}">Inbound Webhooks</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.inbound-webhooks.show', $inboundWebhook) }}">Details</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Execution Logs</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Webhook Activity History</h1>
                <p class="mb-0 text-muted">Source: <span class="badge badge-light border">{{ $inboundWebhook->source_name ?? $inboundWebhook->name }}</span></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.inbound-webhooks.show', $inboundWebhook) }}" class="btn btn-outline-primary shadow-sm mr-2">
                    <i class="fas fa-cog mr-1"></i> Configure Mapping
                </a>
                <button onclick="window.location.reload()" class="btn btn-white border shadow-sm">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <div class="card card-premium shadow mb-4">
            <div class="card-body p-0">
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-gray-700">
                                <tr>
                                    <th class="border-top-0 pl-4 py-3">Status</th>
                                    <th class="border-top-0 py-3">HTTP</th>
                                    <th class="border-top-0 py-3">Timestamp</th>
                                    <th class="border-top-0 py-3">IP Address</th>
                                    <th class="border-top-0 py-3">Result</th>
                                    <th class="border-top-0 text-right pr-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $log)
                                    <tr class="log-row">
                                        <td class="pl-4">
                                            @if($log->status_code >= 200 && $log->status_code < 300)
                                                <span class="badge badge-soft-success rounded-pill px-3 py-1">
                                                    <i class="fas fa-check-circle mr-1"></i> Success
                                                </span>
                                            @else
                                                <span class="badge badge-soft-danger rounded-pill px-3 py-1">
                                                    <i class="fas fa-exclamation-circle mr-1"></i> Failed
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="font-weight-bold {{ $log->status_code >= 400 ? 'text-danger' : 'text-dark' }}">
                                                {{ $log->status_code }}
                                            </span>
                                            <small class="text-muted ml-1">{{ $log->method }}</small>
                                        </td>
                                        <td>
                                            <div class="text-dark font-weight-500">{{ $log->created_at->format('M d, H:i:s') }}</div>
                                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <code class="text-muted small">{{ $log->ip_address }}</code>
                                        </td>
                                        <td>
                                            @if($log->enquiry_id)
                                                <a href="{{ route('admin.enquiries.show', $log->enquiry_id) }}" class="badge badge-soft-info px-2 py-1">
                                                    <i class="fas fa-user-plus mr-1"></i> Lead #{{ $log->enquiry_id }}
                                                </a>
                                            @elseif($log->error_message)
                                                <span class="text-danger small font-italic" title="{{ $log->error_message }}">
                                                    {{ Str::limit($log->error_message, 40) }}
                                                </span>
                                            @else
                                                <span class="text-muted small">Processed</span>
                                            @endif
                                        </td>
                                        <td class="text-right pr-4">
                                            <button class="btn btn-sm btn-white border shadow-sm" data-toggle="modal"
                                                data-target="#logModal{{ $log->id }}">
                                                <i class="fas fa-eye mr-1"></i> Inspect
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($logs->hasPages())
                        <div class="card-footer bg-white border-top-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small text-muted">
                                    Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} entries
                                </div>
                                {{ $logs->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="mb-3 text-gray-300">
                            <i class="fas fa-terminal fa-4x"></i>
                        </div>
                        <h4 class="text-gray-500 font-weight-bold">No Activity Recorded</h4>
                        <p class="text-muted mb-0">Webhook requests will be logged here as they arrive.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @foreach($logs as $log)
        <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-light border-bottom-0 py-3">
                        <h5 class="modal-title font-weight-bold text-gray-800">
                            <i class="fas fa-file-code text-primary mr-2"></i> Payload Inspection
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4 bg-white">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="small font-weight-bold text-gray-600 mb-0 uppercase">Request Data (JSON)</label>
                                <button class="btn btn-sm btn-link text-primary p-0" onclick="copyModalCode(this, 'payload-{{ $log->id }}')">
                                    <i class="fas fa-copy mr-1"></i> Copy
                                </button>
                            </div>
                            <pre id="payload-{{ $log->id }}" class="code-display mb-0"><code>{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>
                        
                        @if($log->error_message)
                            <div class="alert alert-danger border-0 bg-light-danger mb-0">
                                <h6 class="font-weight-bold"><i class="fas fa-bug mr-2"></i> Error Details</h6>
                                <p class="mb-0 small font-italic">{{ $log->error_message }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light border-top-0 py-3">
                        <div class="mr-auto">
                            <span class="text-muted small">Log ID: <code>{{ $log->id }}</code></span>
                            <span class="mx-2 text-gray-300">|</span>
                            <span class="text-muted small">{{ $log->created_at->toDayDateTimeString() }}</span>
                        </div>
                        <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@push('scripts')
    <script>
        function copyModalCode(btn, elementId) {
            const code = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(code).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-1 text-success"></i> Copied!';
                setTimeout(() => btn.innerHTML = originalHtml, 2000);
            });
        }
    </script>
@endpush
@endsection
