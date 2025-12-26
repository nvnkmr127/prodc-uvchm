@extends('layouts.theme')
@section('title', 'Webhook Call Logs')

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

        .code-display {
            background: #1f2937;
            color: #e5e7eb;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Monaco', monospace;
            font-size: 0.85rem;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('admin.webhooks.index') }}">Webhooks</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.webhooks.show', $webhook) }}">Details</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Logs</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Execution History</h1>
                <p class="mb-0 text-muted">Endpoint: <code>{{ $webhook->url }}</code></p>
            </div>
        </div>

        <div class="card card-premium shadow mb-4">
            <div class="card-body">
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-top-0">Outcome</th>
                                    <th class="border-top-0">HTTP Code</th>
                                    <th class="border-top-0">Timestamp</th>
                                    <th class="border-top-0">Duration</th>
                                    <th class="border-top-0 text-right">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $log)
                                    <tr>
                                        <td>
                                            @if($log->success)
                                                <span class="badge badge-soft-success rounded-pill px-3">Success</span>
                                            @else
                                                <span class="badge badge-soft-danger rounded-pill px-3">Failed</span>
                                            @endif
                                        </td>
                                        <td class="font-weight-bold text-gray-700">{{ $log->status_code ?? '---' }}</td>
                                        <td>
                                            <div class="text-dark">{{ $log->created_at->format('M d, Y H:i:s') }}</div>
                                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>{{ $log->execution_time_ms }}<span class="text-muted small">ms</span></td>
                                        <td class="text-right">
                                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                                data-target="#logModal{{ $log->id }}">
                                                <i class="fas fa-code mr-1"></i> Inspect
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $logs->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-clipboard-list fa-3x text-gray-300"></i>
                        </div>
                        <h5 class="text-gray-500">No logs available</h5>
                        <p class="text-muted">Calls to this webhook will appear here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @foreach($logs as $log)
        <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-light border-bottom-0">
                        <h5 class="modal-title font-weight-bold text-gray-800">Transaction Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="p-3 bg-white">
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab-{{ $log->id }}" role="tablist">
                                    <a class="nav-item nav-link active" data-toggle="tab" href="#payload-{{ $log->id }}"
                                        role="tab">Request Payload</a>
                                    <a class="nav-item nav-link" data-toggle="tab" href="#response-{{ $log->id }}"
                                        role="tab">Response Body</a>
                                </div>
                            </nav>
                            <div class="tab-content pt-3" id="nav-tabContent-{{ $log->id }}">
                                <div class="tab-pane fade show active" id="payload-{{ $log->id }}" role="tabpanel">
                                    <pre
                                        class="code-display mb-0"><code>{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </div>
                                <div class="tab-pane fade" id="response-{{ $log->id }}" role="tabpanel">
                                    <pre class="code-display mb-0"><code>@php
                                        $resp = $log->response_body;
                                        $json = json_decode($resp);
                                        echo (json_last_error() === JSON_ERROR_NONE)
                                            ? json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                            : htmlspecialchars($resp);
                                    @endphp</code></pre>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                ID: {{ $log->id }} | {{ $log->created_at }}
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@endsection