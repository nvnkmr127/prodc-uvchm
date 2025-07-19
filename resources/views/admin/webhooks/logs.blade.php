@extends('layouts.theme')
@section('title', 'Webhook Call Logs')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Webhook Logs</h1>
        <p class="mb-0 text-muted">History for <code>{{ $webhook->url }}</code></p>
    </div>
    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Webhooks
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>HTTP Code</th>
                        <th>Date</th>
                        <th>Execution Time</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>
                                @if($log->success)
                                    <span class="badge badge-success">Success</span>
                                @else
                                    <span class="badge badge-danger">Failed</span>
                                @endif
                            </td>
                            <td>{{ $log->status_code ?? 'N/A' }}</td>
                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log->execution_time_ms }} ms</td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#logModal{{ $log->id }}">
                                    View Payload & Response
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No call logs found for this webhook.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $logs->links() }}
        </div>
    </div>
</div>

{{-- Modal for each log --}}
@foreach($logs as $log)
<div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Details</h5>
                <button class="close" type="button" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <h6>Request Payload</h6>
                <pre><code class="json">{{ json_encode($log->payload, JSON_PRETTY_PRINT) }}</code></pre>
                <hr>
                <h6>Response Body</h6>
<pre><code>@php
    $responseBody = $log->response_body ?? 'No response body captured.';
    $decoded = json_decode($responseBody);
    // Check if it's valid JSON, then pretty-print it.
    if (json_last_error() === JSON_ERROR_NONE) {
        echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    } else {
        echo htmlspecialchars($responseBody, ENT_QUOTES, 'UTF-8');
    }
@endphp</code></pre>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection